<?php

namespace App\Ticketing\Contracts;

use App\Models\Mailbox;
use App\Ticketing\DTO\ConnectionStatus;
use App\Ticketing\DTO\MailboxDelta;
use App\Ticketing\DTO\OutboundEmail;
use App\Ticketing\DTO\SentEmail;
use App\Ticketing\DTO\SyncCursor;
use App\Ticketing\DTO\TrashedEmail;
use App\Ticketing\Mailbox\MailboxAccount;

/**
 * Every operation targets one mailbox. Labels and the thread trash exist only on Gmail mailboxes:
 * on plain IMAP mailboxes they are no-ops (see supportsThreads()).
 */
interface MailboxClient
{
    public function fetchNew(Mailbox $mailbox, SyncCursor $cursor, int $limit = 50): MailboxDelta;

    public function addLabel(Mailbox $mailbox, string $gmailMessageId, string $label): void;

    /**
     * Moves every message of the thread (received and sent) to the Gmail trash; returns how many were moved.
     */
    public function trashThread(Mailbox $mailbox, string $gmailThreadId): int;

    /**
     * Moves the thread back from the Gmail trash to the inbox; returns how many messages were moved.
     */
    public function restoreThread(Mailbox $mailbox, string $gmailThreadId): int;

    /**
     * Latest messages in the Gmail trash, newest first.
     *
     * @return list<TrashedEmail>
     */
    public function listTrash(Mailbox $mailbox, int $limit = 100): array;

    public function send(Mailbox $mailbox, OutboundEmail $email): SentEmail;

    /**
     * Without an account, tests the saved credentials (OAuth when connected, falling back to the app password).
     */
    public function testConnection(Mailbox $mailbox, ?MailboxAccount $account = null): ConnectionStatus;
}
