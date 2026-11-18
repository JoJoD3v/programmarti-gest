<?php

namespace App\Http\Controllers;

use App\Http\Requests\Support\ReplyTicketRequest;
use App\Http\Requests\Support\StoreTicketRequest;
use App\Models\Client;
use App\Models\Mailbox;
use App\Models\SupportAiDraft;
use App\Models\SupportSavedReply;
use App\Models\SupportSenderRule;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketType;
use App\Models\SupportTicketTypeChange;
use App\Models\User;
use App\Support\Sort;
use App\Ticketing\Actions\GenerateReplyDraft;
use App\Ticketing\Actions\ProcessInboundMessage;
use App\Ticketing\Actions\QueueTicketReply;
use App\Ticketing\Actions\TicketAssigner;
use App\Ticketing\Actions\TicketStateMachine;
use App\Ticketing\Drafts\DraftsFeature;
use App\Ticketing\Enums\AssignmentReason;
use App\Ticketing\Enums\CloseReason;
use App\Ticketing\Enums\TicketStatus;
use App\Ticketing\Enums\TypeSource;
use App\Ticketing\Jobs\SendTicketReplyJob;
use App\Ticketing\Support\LlmUnavailableException;
use App\Ticketing\Support\SavedReplyVariables;
use App\Ticketing\Support\TicketingSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SupportTicket::class);

        $user = $request->user();
        $isAdmin = $user->isTicketingAdmin();

        $base = SupportTicket::query()
            ->when(! $isAdmin, fn ($query) => $query->assignedTo($user));

        $counters = $isAdmin ? [
            'open' => (clone $base)->where('status', TicketStatus::Open->value)->count(),
            'pending' => (clone $base)->where('status', TicketStatus::Pending->value)->count(),
            'unassigned' => (clone $base)->unassigned()->notClosed()->count(),
            'unclassified' => (clone $base)->whereNull('support_ticket_type_id')->notClosed()->count(),
        ] : null;

        $tab = $request->query('tab', 'open');
        $q = trim((string) $request->query('q', ''));
        $typeFilter = (string) $request->query('type', '');
        $userFilter = (string) $request->query('user', '');
        $mailboxFilter = (string) $request->query('mailbox', '');

        $tickets = Sort::apply((clone $base)->with(['type', 'assignee', 'mailbox', 'client']), $request, [
            'ticket' => 'id',
            'customer' => fn ($query, string $dir) => $query->orderByRaw("COALESCE(NULLIF(customer_name, ''), customer_email) {$dir}"),
            'type' => Sort::byRelated('support_ticket_types', 'name', 'support_tickets.support_ticket_type_id'),
            'mailbox' => Sort::byRelated('mailboxes', 'name', 'support_tickets.mailbox_id'),
            'assignee' => Sort::byUser('support_tickets.assigned_user_id'),
            'status' => 'status',
            'last' => fn ($query, string $dir) => $query->orderByRaw("COALESCE(last_inbound_at, created_at) {$dir}"),
        ], 'ticket')
            ->when($tab === 'open', fn ($query) => $query->notClosed())
            ->when($tab === 'pending', fn ($query) => $query->where('status', TicketStatus::Pending->value))
            ->when($tab === 'closed', fn ($query) => $query->where('status', TicketStatus::Closed->value))
            ->when($isAdmin && $request->filled('status') && TicketStatus::tryFrom($request->query('status')), fn ($query) => $query->where('status', $request->query('status')))
            ->when($typeFilter === 'none', fn ($query) => $query->whereNull('support_ticket_type_id'))
            ->when(ctype_digit($typeFilter), fn ($query) => $query->where('support_ticket_type_id', (int) $typeFilter))
            ->when($isAdmin && $userFilter === 'none', fn ($query) => $query->unassigned())
            ->when($isAdmin && ctype_digit($userFilter), fn ($query) => $query->where('assigned_user_id', (int) $userFilter))
            ->when(ctype_digit($mailboxFilter), fn ($query) => $query->where('mailbox_id', (int) $mailboxFilter))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->query('to')))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('public_id', 'like', "{$q}%")
                        ->orWhere('customer_email', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%")
                        ->orWhere('subject', 'like', "%{$q}%");
                });
            })
            ->paginate(25)
            ->withQueryString();

        return view('support-tickets.index', [
            'tickets' => $tickets,
            'counters' => $counters,
            'tab' => $tab,
            'q' => $q,
            'typeFilter' => $typeFilter,
            'userFilter' => $userFilter,
            'mailboxFilter' => $mailboxFilter,
            'mailboxes' => Mailbox::query()->orderBy('name')->get(),
            'types' => SupportTicketType::query()->orderBy('sort')->get(),
            'agents' => $isAdmin ? User::query()->ticketingEnabled()->orderBy('last_name')->get() : collect(),
        ]);
    }

    public function create(Request $request, TicketingSettings $settings): View
    {
        Gate::authorize('viewAny', SupportTicket::class);

        return view('support-tickets.create', [
            'types' => SupportTicketType::query()->active()->orderBy('sort')->get(),
            'mailboxes' => Mailbox::query()->where('active', true)->orderBy('name')->get(),
            // "Nuovo ticket" from a client card prefills the recipient
            'client' => $request->filled('client') ? Client::query()->find($request->integer('client')) : null,
            'signature' => $settings->get('reply_signature'),
        ]);
    }

    /**
     * Opens a ticket from the gestionale: the first message is an email to the customer, assigned to whoever wrote it.
     */
    public function store(StoreTicketRequest $request, QueueTicketReply $queue, TicketAssigner $assigner, TicketStateMachine $machine): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $email = mb_strtolower($data['customer_email']);

        $ticket = SupportTicket::openNew([
            'status' => TicketStatus::Open,
            'mailbox_id' => $data['mailbox_id'],
            'client_id' => ProcessInboundMessage::clientIdFor($email),
            'customer_email' => $email,
            'customer_name' => $data['customer_name'] ?? null,
            'subject' => $data['subject'],
            'support_ticket_type_id' => $data['support_ticket_type_id'] ?? null,
            'type_source' => isset($data['support_ticket_type_id']) ? TypeSource::Manual : null,
        ]);

        if ($user->isTicketAgent()) {
            $assigner->reassign($ticket, $user, $user, null, AssignmentReason::Manual);
        } else {
            $assigner->assignAutomatically($ticket);
        }

        $machine->opened($ticket);
        $queue->handle($ticket, $user, $data['body'], $request->file('attachments', []));

        return redirect()->route('support-tickets.show', $ticket)->with('success', 'Ticket creato: email in invio.');
    }

    public function print(SupportTicket $supportTicket): View
    {
        Gate::authorize('view', $supportTicket);

        $supportTicket->load(['type', 'assignee', 'closedByUser']);

        return view('support-tickets.print', [
            'ticket' => $supportTicket,
            'messages' => $supportTicket->messages()->with(['attachments', 'sender'])->orderBy('id')->get(),
        ]);
    }

    public function show(Request $request, SupportTicket $supportTicket, TicketingSettings $settings, DraftsFeature $drafts): View
    {
        Gate::authorize('view', $supportTicket);

        $user = $request->user();
        $supportTicket->load(['type', 'assignee', 'related', 'previous', 'closedByUser', 'mailbox', 'client']);
        $messages = $supportTicket->messages()->with(['attachments', 'sender'])->orderBy('id')->get();

        $last = $messages->last();
        $canDraft = $drafts->availableFor($user)
            && $supportTicket->status === TicketStatus::Open
            && $last?->isInbound();

        $replyVariables = new SavedReplyVariables;

        $savedReplies = SupportSavedReply::query()
            ->visibleTo($user, $settings->bool('personal_saved_replies_enabled'))
            ->get()
            ->sortBy(fn (SupportSavedReply $reply) => [
                $reply->support_ticket_type_id === $supportTicket->support_ticket_type_id ? 0 : 1,
                $reply->scope->value === 'personal' ? 0 : 1,
                -$reply->usage_count,
            ])
            ->map(fn (SupportSavedReply $reply) => [
                'id' => $reply->id,
                'title' => $reply->title,
                'scope' => $reply->scope->label(),
                'body' => $replyVariables->replace($reply->body, $supportTicket, $user),
            ])
            ->values();

        $suggested = $canDraft
            ? $supportTicket->aiDrafts()->where('pregenerated', true)->where('used', false)
                ->where('based_on_message_id', $supportTicket->lastInboundMessage()?->id)->latest()->first()
            : null;

        return view('support-tickets.show', [
            'ticket' => $supportTicket,
            'messages' => $messages,
            'showImages' => $request->boolean('images'),
            'savedReplies' => $savedReplies,
            'canDraft' => $canDraft,
            'suggestedDraft' => $suggested,
            'types' => SupportTicketType::query()->active()->orderBy('sort')->get(),
            'agents' => $user->isTicketingAdmin() ? User::query()->ticketingEnabled()->orderBy('last_name')->get() : collect(),
            'otherTickets' => SupportTicket::query()->where('customer_email', $supportTicket->customer_email)
                ->where('id', '!=', $supportTicket->id)->when(! $user->isTicketingAdmin(), fn ($query) => $query->assignedTo($user))
                ->latest('id')->limit(5)->get(),
            'signature' => $settings->get('reply_signature'),
            'clientProjects' => $supportTicket->client?->projects()->latest('start_date')->limit(10)->get() ?? collect(),
            'clients' => Client::query()->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function reply(ReplyTicketRequest $request, SupportTicket $supportTicket, QueueTicketReply $queue): RedirectResponse
    {
        $data = $request->validated();

        if ($supportTicket->isClosed()) {
            return back()->with('error', 'Il ticket è chiuso: non si può rispondere.');
        }

        $queue->handle(
            $supportTicket,
            $request->user(),
            $data['body'],
            $request->file('attachments', []),
            $data['saved_reply_id'] ?? null,
            $data['ai_draft_id'] ?? null,
            (bool) ($data['close'] ?? false),
        );

        return redirect()->route('support-tickets.show', $supportTicket)->with('success', 'Risposta in invio.');
    }

    public function retry(Request $request, SupportTicket $supportTicket, int $message): RedirectResponse
    {
        Gate::authorize('reply', $supportTicket);

        $stored = $supportTicket->messages()->where('delivery_status', 'failed')->findOrFail($message);
        $stored->forceFill(['delivery_status' => 'sending', 'send_error' => null])->save();
        SendTicketReplyJob::dispatch($stored->id);

        return back()->with('success', 'Nuovo tentativo di invio avviato.');
    }

    public function close(Request $request, SupportTicket $supportTicket, TicketStateMachine $machine): RedirectResponse
    {
        Gate::authorize('close', $supportTicket);

        $data = $request->validate(['reason' => ['required', Rule::in(['manual', 'spam', 'not_relevant'])]]);

        if ($supportTicket->isClosed()) {
            return back()->with('error', 'Il ticket è già chiuso.');
        }

        $machine->close($supportTicket, CloseReason::from($data['reason']), $request->user());

        return back()->with('success', 'Ticket chiuso.');
    }

    public function changeType(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        Gate::authorize('changeType', $supportTicket);

        $data = $request->validate(['support_ticket_type_id' => ['required', Rule::exists('support_ticket_types', 'id')->where('active', true)]]);
        $this->applyManualType($supportTicket, (int) $data['support_ticket_type_id'], $request->user());

        return back()->with('success', 'Tipologia aggiornata.');
    }

    /**
     * Links the ticket to a client of the agency (or removes the link) when the sender's address is not the client's.
     */
    public function changeClient(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        Gate::authorize('view', $supportTicket);

        $data = $request->validate(['client_id' => ['nullable', 'integer', 'exists:clients,id']]);
        $supportTicket->forceFill(['client_id' => $data['client_id'] ?? null])->save();

        return back()->with('success', $supportTicket->client_id ? 'Ticket collegato al cliente.' : 'Collegamento al cliente rimosso.');
    }

    public function assign(Request $request, SupportTicket $supportTicket, TicketAssigner $assigner): RedirectResponse
    {
        Gate::authorize('reassign', SupportTicket::class);

        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('can_manage_tickets', true)->where('is_active', true)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $assigner->reassign($supportTicket, User::findOrFail($data['user_id']), $request->user(), $data['note'] ?? null, $supportTicket->assigned_user_id ? AssignmentReason::Reassign : AssignmentReason::Manual);
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Ticket assegnato.');
    }

    public function saveDraft(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        Gate::authorize('reply', $supportTicket);

        $supportTicket->forceFill(['reply_draft' => (string) $request->input('body', '')])->saveQuietly();

        return response()->json(['saved' => true]);
    }

    public function createSenderRule(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        if ($supportTicket->support_ticket_type_id === null) {
            return back()->with('error', 'Assegna prima una tipologia al ticket.');
        }

        $pattern = $request->boolean('whole_domain')
            ? '@'.Str::after($supportTicket->customer_email, '@')
            : $supportTicket->customer_email;

        SupportSenderRule::updateOrCreate(
            ['pattern' => mb_strtolower($pattern)],
            ['support_ticket_type_id' => $supportTicket->support_ticket_type_id, 'active' => true, 'note' => "Creata dal ticket {$supportTicket->public_id}"],
        );

        return back()->with('success', "Regola creata: {$pattern} → {$supportTicket->type->name}.");
    }

    public function generateDraft(Request $request, SupportTicket $supportTicket, GenerateReplyDraft $generate, DraftsFeature $feature): JsonResponse
    {
        Gate::authorize('reply', $supportTicket);
        abort_unless($feature->availableFor($request->user()), 403);

        $data = $request->validate(['instruction' => ['nullable', 'string', 'max:500']]);

        try {
            $draft = $generate->handle($supportTicket, $request->user(), $data['instruction'] ?? null);
        } catch (LlmUnavailableException $exception) {
            return response()->json(['message' => 'Bozza non disponibile al momento: riprova tra poco o scrivi la risposta a mano.'], 503);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['id' => $draft->id, 'text' => $draft->draft_text]);
    }

    public function draftFeedback(Request $request, SupportTicket $supportTicket, SupportAiDraft $draft): JsonResponse
    {
        Gate::authorize('reply', $supportTicket);
        abort_unless($draft->support_ticket_id === $supportTicket->id, 404);

        $draft->update(['feedback' => $request->validate(['feedback' => ['required', Rule::in(['up', 'down'])]])['feedback']]);

        return response()->json(['saved' => true]);
    }

    public function bulk(Request $request, TicketAssigner $assigner, TicketStateMachine $machine): RedirectResponse
    {
        Gate::authorize('reassign', SupportTicket::class);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:support_tickets,id'],
            'action' => ['required', Rule::in(['assign', 'distribute', 'type', 'spam'])],
            'user_id' => ['required_if:action,assign', 'nullable', 'integer', Rule::exists('users', 'id')->where('can_manage_tickets', true)->where('is_active', true)],
            'support_ticket_type_id' => ['required_if:action,type', 'nullable', 'integer', 'exists:support_ticket_types,id'],
        ]);

        $tickets = SupportTicket::query()->whereIn('id', $data['ids'])->get();
        $done = 0;

        foreach ($tickets as $ticket) {
            match ($data['action']) {
                'assign' => $ticket->assigned_user_id !== (int) $data['user_id']
                    ? [$assigner->reassign($ticket, User::findOrFail($data['user_id']), $request->user(), null, AssignmentReason::Bulk), $done++]
                    : null,
                'distribute' => $assigner->reassignAutomatically($ticket, AssignmentReason::Bulk, $request->user()) ? $done++ : null,
                'type' => [$this->applyManualType($ticket, (int) $data['support_ticket_type_id'], $request->user()), $done++],
                'spam' => $ticket->isClosed() ? null : [$machine->close($ticket, CloseReason::Spam, $request->user()), $done++],
            };
        }

        return back()->with('success', "Operazione eseguita su {$done} ticket.");
    }

    public function downloadAttachment(SupportTicketAttachment $attachment)
    {
        $ticket = $attachment->message->ticket;
        Gate::authorize('view', $ticket);

        abort_if($attachment->path === null || ! Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->filename);
    }

    private function applyManualType(SupportTicket $ticket, int $typeId, User $by): void
    {
        $previous = $ticket->support_ticket_type_id;

        $ticket->forceFill(['support_ticket_type_id' => $typeId, 'type_source' => TypeSource::Manual])->save();

        if ($previous !== $typeId) {
            SupportTicketTypeChange::create([
                'support_ticket_id' => $ticket->id,
                'from_type_id' => $previous,
                'to_type_id' => $typeId,
                'changed_by' => $by->id,
            ]);
        }

        $ticket->load('type');
    }
}
