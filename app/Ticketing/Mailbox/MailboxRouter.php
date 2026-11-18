<?php

namespace App\Ticketing\Mailbox;

use App\Models\Mailbox;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\DTO\ConnectionStatus;
use App\Ticketing\DTO\MailboxDelta;
use App\Ticketing\DTO\OutboundEmail;
use App\Ticketing\DTO\SentEmail;
use App\Ticketing\DTO\SyncCursor;

/**
 * The MailboxClient bound in the container: forwards each call to the client of the mailbox's driver.
 */
class MailboxRouter implements MailboxClient
{
    public function __construct(
        private readonly GmailImapMailboxClient $gmail,
        private readonly ImapMailboxClient $imap,
    ) {}

    public function fetchNew(Mailbox $mailbox, SyncCursor $cursor, int $limit = 50): MailboxDelta
    {
        return $this->client($mailbox)->fetchNew($mailbox, $cursor, $limit);
    }

    public function addLabel(Mailbox $mailbox, string $gmailMessageId, string $label): void
    {
        $this->client($mailbox)->addLabel($mailbox, $gmailMessageId, $label);
    }

    public function trashThread(Mailbox $mailbox, string $gmailThreadId): int
    {
        return $this->client($mailbox)->trashThread($mailbox, $gmailThreadId);
    }

    public function restoreThread(Mailbox $mailbox, string $gmailThreadId): int
    {
        return $this->client($mailbox)->restoreThread($mailbox, $gmailThreadId);
    }

    public function listTrash(Mailbox $mailbox, int $limit = 100): array
    {
        return $this->client($mailbox)->listTrash($mailbox, $limit);
    }

    public function send(Mailbox $mailbox, OutboundEmail $email): SentEmail
    {
        return $this->client($mailbox)->send($mailbox, $email);
    }

    public function testConnection(Mailbox $mailbox, ?MailboxAccount $account = null): ConnectionStatus
    {
        return $this->client($mailbox)->testConnection($mailbox, $account);
    }

    private function client(Mailbox $mailbox): MailboxClient
    {
        return $mailbox->isGmail() ? $this->gmail : $this->imap;
    }
}
