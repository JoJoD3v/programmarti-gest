<?php

namespace App\Console\Commands;

use App\Models\Mailbox;
use App\Models\SupportTicket;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\Enums\TicketStatus;
use App\Ticketing\Mailbox\Imap\ImapException;
use App\Ticketing\Support\TicketingSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Moves the Gmail threads of tickets closed for more than N hours to the Gmail trash (N from "Impostazioni assistenza").
 * The ticket keeps its full copy in the database; a thread shared with a ticket still open or closed too recently is skipped.
 */
class TrashClosedTicketThreadsCommand extends Command
{
    protected $signature = 'ticketing:trash-closed-threads';

    protected $description = 'Sposta nel cestino Gmail le email dei ticket chiusi da oltre il ritardo impostato';

    public function handle(TicketingSettings $settings, MailboxClient $client): int
    {
        $hours = $settings->get('gmail_trash_after_hours');

        if ($hours === null) {
            $this->info('Pulizia Gmail disattivata: nessuna azione.');

            return self::SUCCESS;
        }

        $cutoff = now()->subHours((int) $hours);
        $trashed = 0;

        // Gmail threads only: plain IMAP mailboxes have no thread trash
        $threads = SupportTicket::query()
            ->where('status', TicketStatus::Closed->value)
            ->where('closed_at', '<=', $cutoff)
            ->whereNotNull('gmail_thread_id')
            ->whereHas('mailbox', fn ($query) => $query->where('driver', Mailbox::DRIVER_GMAIL)->whereNull('disconnected_at'))
            ->where(fn ($query) => $query->whereNull('gmail_trashed_at')->orWhereColumn('gmail_trashed_at', '<', 'closed_at'))
            ->distinct()
            ->get(['gmail_thread_id', 'mailbox_id']);

        $mailboxes = Mailbox::query()->whereIn('id', $threads->pluck('mailbox_id')->unique())->get()->keyBy('id');

        foreach ($threads as $thread) {
            $threadId = $thread->gmail_thread_id;
            $stillInUse = SupportTicket::query()
                ->where('gmail_thread_id', $threadId)
                ->where(fn ($query) => $query->where('status', '!=', TicketStatus::Closed->value)->orWhere('closed_at', '>', $cutoff))
                ->exists();

            if ($stillInUse) {
                continue;
            }

            try {
                $client->trashThread($mailboxes[$thread->mailbox_id], $threadId);
            } catch (ImapException $exception) {
                Log::warning('Ticketing: thread Gmail non spostato nel cestino', ['gmail_thread_id' => $threadId, 'error' => $exception->getMessage()]);

                continue;
            }

            SupportTicket::query()->where('gmail_thread_id', $threadId)->update(['gmail_trashed_at' => now()]);
            $trashed++;
        }

        $this->info("Thread Gmail spostati nel cestino: {$trashed}");

        return self::SUCCESS;
    }
}
