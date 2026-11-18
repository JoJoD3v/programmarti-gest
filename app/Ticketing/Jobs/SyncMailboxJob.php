<?php

namespace App\Ticketing\Jobs;

use App\Models\Mailbox;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\DTO\SyncCursor;
use App\Ticketing\Mailbox\Imap\ImapException;
use App\Ticketing\Support\InboundEmailStore;
use App\Ticketing\Support\TicketNotifier;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * One IMAP connection per run and mailbox. The cursor moves only after the whole batch has been queued, so a crash
 * can at worst re-read messages (deduplicated by gmail_message_id), never lose them.
 */
class SyncMailboxJob
{
    use Dispatchable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public int $mailboxId, public ?string $since = null) {}

    public function handle(MailboxClient $client, InboundEmailStore $store): void
    {
        $mailbox = Mailbox::query()->find($this->mailboxId);

        if ($mailbox === null || ! $mailbox->active || $mailbox->isDisconnected()) {
            return; // an admin must reconnect it from "Caselle"
        }

        $cursor = $this->since !== null
            ? new SyncCursor
            : new SyncCursor($mailbox->uid_validity, $mailbox->last_uid);

        try {
            $delta = $client->fetchNew($mailbox, $cursor, (int) config('ticketing.mailbox.batch_size'));
        } catch (ImapException $exception) {
            if ($exception->authenticationFailed) {
                $mailbox->disconnect($exception->getMessage());

                return;
            }

            $this->fail($mailbox, $exception);

            return;
        } catch (\Throwable $exception) {
            $this->fail($mailbox, $exception);

            return;
        }

        foreach ($delta->messages as $email) {
            ProcessInboundMessageJob::dispatch($store->stash($email));
        }

        $mailbox->forceFill([
            'uid_validity' => $delta->cursor->uidValidity,
            'last_uid' => $delta->cursor->lastUid,
            'last_synced_at' => now(),
            'last_error' => null,
            'consecutive_failures' => 0,
        ])->save();

        TicketNotifier::clearAlert("sync-failing-{$mailbox->id}");
    }

    private function fail(Mailbox $mailbox, \Throwable $exception): void
    {
        $mailbox->forceFill([
            'last_error' => mb_substr($exception->getMessage(), 0, 1000),
            'consecutive_failures' => $mailbox->consecutive_failures + 1,
        ])->save();

        Log::warning('Ticketing: sync casella fallito', ['mailbox' => $mailbox->address, 'error' => $exception->getMessage(), 'failures' => $mailbox->consecutive_failures]);
    }
}
