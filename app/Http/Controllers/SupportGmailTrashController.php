<?php

namespace App\Http\Controllers;

use App\Models\Mailbox;
use App\Models\SupportTicket;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\DTO\TrashedEmail;
use App\Ticketing\Mailbox\Imap\ImapException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Read-only view of the trash of a Gmail mailbox (one row per thread) with a restore to the inbox.
 * Plain IMAP mailboxes have no thread trash.
 */
class SupportGmailTrashController extends Controller
{
    public function index(Request $request, MailboxClient $client): View
    {
        Gate::authorize('viewAny', SupportTicket::class);

        $mailboxes = Mailbox::query()->where('driver', Mailbox::DRIVER_GMAIL)->orderBy('name')->get();
        $mailbox = $mailboxes->firstWhere('id', $request->integer('mailbox')) ?? $mailboxes->first();
        $threads = collect();
        $error = null;

        if ($mailbox === null) {
            $error = 'Nessuna casella Gmail collegata.';
        } else {
            try {
                $threads = collect($client->listTrash($mailbox))
                    ->groupBy(fn (TrashedEmail $email) => $email->gmailThreadId)
                    ->map(fn ($emails) => ['latest' => $emails->first(), 'count' => $emails->count()]);
            } catch (ImapException $exception) {
                $error = 'Impossibile leggere il cestino Gmail: '.$exception->getMessage();
            }
        }

        return view('support.gmail-trash.index', [
            'mailboxes' => $mailboxes,
            'mailbox' => $mailbox,
            'threads' => $threads,
            'tickets' => SupportTicket::query()->whereIn('gmail_thread_id', $threads->keys())->get()->keyBy('gmail_thread_id'),
            'error' => $error,
        ]);
    }

    public function restore(Mailbox $mailbox, string $thread, MailboxClient $client): RedirectResponse
    {
        Gate::authorize('viewAny', SupportTicket::class);
        abort_unless($mailbox->isGmail(), 404);

        try {
            $moved = $client->restoreThread($mailbox, $thread);
        } catch (ImapException $exception) {
            return back()->withErrors(['restore' => 'Ripristino non riuscito: '.$exception->getMessage()]);
        }

        return back()->with('success', $moved > 0 ? "Thread ripristinato in Posta in arrivo ({$moved} email)." : 'Il thread non è più nel cestino.');
    }
}
