<?php

namespace App\Ticketing\Jobs;

use App\Models\Mailbox;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\Mailbox\Imap\ImapException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Labels live on Gmail only (never moves or deletes): a failure here must not affect the ticket.
 * Plain IMAP mailboxes (and messages without a mailbox) are skipped.
 */
class ApplyMailboxLabelJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 60;

    /** @var list<int> */
    public array $backoff = [60, 300, 1800];

    public function __construct(public ?int $mailboxId, public string $gmailMessageId, public string $label) {}

    public function handle(MailboxClient $client): void
    {
        $mailbox = $this->mailboxId ? Mailbox::query()->find($this->mailboxId) : null;

        if ($mailbox === null || ! $mailbox->isGmail() || $mailbox->isDisconnected()) {
            return;
        }

        try {
            $client->addLabel($mailbox, $this->gmailMessageId, $this->label);
        } catch (ImapException $exception) {
            if (! $exception->authenticationFailed) {
                throw $exception;
            }

            $mailbox->disconnect($exception->getMessage());
            $this->fail($exception);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('Ticketing: label Gmail non applicata', [
            'gmail_message_id' => $this->gmailMessageId,
            'label' => $this->label,
            'error' => $exception->getMessage(),
        ]);
    }
}
