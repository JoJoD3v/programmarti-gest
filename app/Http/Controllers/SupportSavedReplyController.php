<?php

namespace App\Http\Controllers;

use App\Http\Requests\Support\SaveSupportSavedReplyRequest;
use App\Http\Requests\Support\StoreSavedReplyFromTextRequest;
use App\Models\SupportSavedReply;
use App\Models\SupportTicket;
use App\Models\SupportTicketType;
use App\Models\User;
use App\Ticketing\Enums\SavedReplyScope;
use App\Ticketing\Support\SavedReplyVariables;
use App\Ticketing\Support\TicketingSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupportSavedReplyController extends Controller
{
    /**
     * Example values used by the preview box on the edit page.
     *
     * @var array<string, string>
     */
    private const PREVIEW_SAMPLES = [
        '{cliente_nome}' => 'Mario',
        '{progetto}' => 'Sito vetrina',
        '{numero_ticket}' => 'T-0001',
        '{dipendente_nome}' => 'Giulia',
    ];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SupportTicket::class);

        $user = $request->user();
        $personalEnabled = $this->personalEnabled();

        $q = trim((string) $request->get('q', ''));
        $typeFilter = (string) $request->get('type', '');
        $scopeFilter = (string) $request->get('scope', '');

        $query = $this->visibleQuery($user)->with(['type', 'owner']);

        if ($q !== '') {
            $query->where(function (Builder $sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")->orWhere('body', 'like', "%{$q}%");
            });
        }

        if ($typeFilter !== '') {
            $query->where('support_ticket_type_id', $typeFilter);
        }

        if (in_array($scopeFilter, ['global', 'personal'], true)) {
            $query->where('scope', $scopeFilter);
        }

        $replies = $query->orderBy('sort')->orderBy('title')->paginate(20)->withQueryString();
        $editableIds = $replies->getCollection()
            ->filter(fn (SupportSavedReply $reply) => $this->canModify($user, $reply))
            ->pluck('id')
            ->all();
        $types = SupportTicketType::query()->orderBy('sort')->orderBy('name')->get();
        $canCreate = $user->isTicketingAdmin() || $personalEnabled;

        return view('support.saved-replies.index', compact(
            'replies', 'types', 'q', 'typeFilter', 'scopeFilter', 'editableIds', 'canCreate', 'personalEnabled'
        ));
    }

    public function create(Request $request): View
    {
        Gate::authorize('viewAny', SupportTicket::class);
        abort_unless($request->user()->isTicketingAdmin() || $this->personalEnabled(), 403);

        $reply = new SupportSavedReply([
            'scope' => $request->user()->isTicketingAdmin() ? SavedReplyScope::Global : SavedReplyScope::Personal,
            'active' => true,
            'sort' => 0,
        ]);

        return view('support.saved-replies.create', $this->formData($request->user(), $reply));
    }

    public function store(SaveSupportSavedReplyRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $scope = SavedReplyScope::tryFrom((string) ($data['scope'] ?? '')) ?? ($user->isTicketingAdmin() ? SavedReplyScope::Global : SavedReplyScope::Personal);

        $this->authorizeScope($user, $scope);

        SupportSavedReply::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'support_ticket_type_id' => $data['support_ticket_type_id'] ?? null,
            'scope' => $scope,
            'owner_user_id' => $scope === SavedReplyScope::Personal ? $user->id : null,
            'active' => $data['active'] ?? true,
            'sort' => (int) ($data['sort'] ?? 0),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return redirect()->route('support.saved-replies.index')->with('success', 'Risposta salvata creata.');
    }

    public function edit(Request $request, SupportSavedReply $reply): View
    {
        Gate::authorize('viewAny', SupportTicket::class);
        abort_unless($this->canModify($request->user(), $reply), 403);

        $preview = strtr((string) old('body', $reply->body), self::PREVIEW_SAMPLES);

        return view('support.saved-replies.edit', $this->formData($request->user(), $reply) + compact('preview'));
    }

    public function update(SaveSupportSavedReplyRequest $request, SupportSavedReply $reply): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->canModify($user, $reply), 403);

        $data = $request->validated();

        $reply->update([
            'title' => $data['title'],
            'body' => $data['body'],
            'support_ticket_type_id' => $data['support_ticket_type_id'] ?? null,
            'active' => $data['active'] ?? $reply->active,
            'sort' => (int) ($data['sort'] ?? 0),
            'updated_by' => $user->id,
        ]);

        return redirect()->route('support.saved-replies.index')->with('success', 'Risposta salvata aggiornata.');
    }

    public function destroy(Request $request, SupportSavedReply $reply): RedirectResponse
    {
        Gate::authorize('viewAny', SupportTicket::class);
        abort_unless($this->canModify($request->user(), $reply), 403);

        $reply->delete();

        return redirect()->route('support.saved-replies.index')->with('success', 'Risposta salvata eliminata.');
    }

    public function duplicate(Request $request, SupportSavedReply $reply): RedirectResponse
    {
        Gate::authorize('viewAny', SupportTicket::class);

        $user = $request->user();
        abort_unless($this->visibleQuery($user)->whereKey($reply->id)->exists(), 403);

        $scope = $user->isTicketingAdmin() && $reply->scope === SavedReplyScope::Global ? SavedReplyScope::Global : SavedReplyScope::Personal;
        $this->authorizeScope($user, $scope);

        SupportSavedReply::create([
            'title' => $reply->title.' (copia)',
            'body' => $reply->body,
            'support_ticket_type_id' => $reply->support_ticket_type_id,
            'scope' => $scope,
            'owner_user_id' => $scope === SavedReplyScope::Personal ? $user->id : null,
            'active' => $reply->active,
            'sort' => $reply->sort,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return redirect()->route('support.saved-replies.index')->with('success', 'Risposta duplicata.');
    }

    public function promote(Request $request, SupportSavedReply $reply): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        if ($reply->scope !== SavedReplyScope::Personal) {
            return redirect()->route('support.saved-replies.index')->with('error', 'La risposta è già globale.');
        }

        $reply->update([
            'scope' => SavedReplyScope::Global,
            'owner_user_id' => null,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('support.saved-replies.index')->with('success', 'Risposta resa globale: ora la vedono tutti.');
    }

    public function fromText(StoreSavedReplyFromTextRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $body = $data['body'];

        if (! empty($data['support_ticket_id'])) {
            $ticket = SupportTicket::findOrFail($data['support_ticket_id']);
            Gate::authorize('view', $ticket);
            $body = (new SavedReplyVariables)->abstract($body, $ticket);
        }

        $scope = ($data['scope'] ?? null) === 'global' && $user->isTicketingAdmin() ? SavedReplyScope::Global : SavedReplyScope::Personal;
        $this->authorizeScope($user, $scope);

        SupportSavedReply::create([
            'title' => $data['title'],
            'body' => $body,
            'support_ticket_type_id' => $data['support_ticket_type_id'] ?? null,
            'scope' => $scope,
            'owner_user_id' => $scope === SavedReplyScope::Personal ? $user->id : null,
            'active' => true,
            'sort' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return redirect()->back()->with('success', 'Risposta salvata.');
    }

    private function personalEnabled(): bool
    {
        return app(TicketingSettings::class)->bool('personal_saved_replies_enabled');
    }

    /**
     * Admins see every global reply (and every personal one while personal replies are enabled);
     * enabled employees see the global ones plus their own personal ones.
     *
     * @return Builder<SupportSavedReply>
     */
    private function visibleQuery(User $user): Builder
    {
        $personalEnabled = $this->personalEnabled();
        $query = SupportSavedReply::query();

        if ($user->isTicketingAdmin()) {
            return $personalEnabled ? $query : $query->where('scope', SavedReplyScope::Global->value);
        }

        return $query->where(function (Builder $sub) use ($user, $personalEnabled) {
            $sub->where('scope', SavedReplyScope::Global->value);

            if ($personalEnabled) {
                $sub->orWhere(fn (Builder $own) => $own->where('scope', SavedReplyScope::Personal->value)->where('owner_user_id', $user->id));
            }
        });
    }

    private function canModify(User $user, SupportSavedReply $reply): bool
    {
        if ($reply->scope === SavedReplyScope::Global) {
            return $user->isTicketingAdmin();
        }

        return $this->personalEnabled() && $reply->owner_user_id === $user->id;
    }

    private function authorizeScope(User $user, SavedReplyScope $scope): void
    {
        if ($scope === SavedReplyScope::Global) {
            abort_unless($user->isTicketingAdmin(), 403);

            return;
        }

        abort_unless($this->personalEnabled(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(User $user, SupportSavedReply $reply): array
    {
        $scopeOptions = [];

        if ($user->isTicketingAdmin()) {
            $scopeOptions[SavedReplyScope::Global->value] = SavedReplyScope::Global->label();
        }

        if ($this->personalEnabled()) {
            $scopeOptions[SavedReplyScope::Personal->value] = SavedReplyScope::Personal->label();
        }

        return [
            'reply' => $reply,
            'types' => SupportTicketType::query()->active()->orderBy('sort')->orderBy('name')->get(),
            'scopeOptions' => $scopeOptions,
            'variables' => SavedReplyVariables::catalog(),
        ];
    }
}
