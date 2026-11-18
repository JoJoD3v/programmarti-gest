<?php

namespace App\Ticketing\Fakes;

use App\Models\Mailbox;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\DTO\ConnectionStatus;
use App\Ticketing\DTO\InboundEmail;
use App\Ticketing\DTO\MailboxDelta;
use App\Ticketing\DTO\OutboundEmail;
use App\Ticketing\DTO\SentEmail;
use App\Ticketing\DTO\SyncCursor;
use App\Ticketing\DTO\TrashedEmail;
use App\Ticketing\Mailbox\Imap\ImapException;
use App\Ticketing\Mailbox\MailboxAccount;
use RuntimeException;

/**
 * In-memory mailbox for tests: queue messages with push(), inspect labels and sent mail afterwards.
 * One shared inbox whatever the mailbox; $sentFrom records which mailbox each message left from.
 */
class FakeMailboxClient implements MailboxClient
{
    /** @var list<InboundEmail> */
    public array $inbox = [];

    /** @var list<array{id: string, label: string}> */
    public array $labels = [];

    /** @var list<OutboundEmail> */
    public array $sent = [];

    /** @var list<int|null> mailbox id of each sent message */
    public array $sentFrom = [];

    public string $uidValidity = '1';

    public ?\Throwable $failWith = null;

    public ?\Throwable $sendFailure = null;

    public ?\Throwable $labelFailure = null;

    /** @var list<string> */
    public array $trashedThreads = [];

    public ?\Throwable $trashFailure = null;

    /** @var list<TrashedEmail> */
    public array $trash = [];

    /** @var list<string> */
    public array $restoredThreads = [];

    public ConnectionStatus $status;

    public ?MailboxAccount $testedWith = null;

    public function __construct()
    {
        $this->status = new ConnectionStatus(true, true);
    }

    public function push(InboundEmail $email): void
    {
        $this->inbox[] = $email;
    }

    public function fetchNew(Mailbox $mailbox, SyncCursor $cursor, int $limit = 50): MailboxDelta
    {
        if ($this->failWith) {
            throw $this->failWith;
        }

        $last = $cursor->uidValidity === $this->uidValidity ? (int) $cursor->lastUid : 0;
        $new = array_values(array_filter($this->inbox, fn (InboundEmail $email) => ($email->uid ?? 0) > $last));
        usort($new, fn (InboundEmail $a, InboundEmail $b) => $a->uid <=> $b->uid);

        $batch = array_slice($new, 0, $limit);
        $lastUid = $batch === [] ? $last : max(array_map(fn (InboundEmail $email) => $email->uid, $batch));

        return new MailboxDelta($batch, new SyncCursor($this->uidValidity, $lastUid), count($new) > $limit);
    }

    public function addLabel(Mailbox $mailbox, string $gmailMessageId, string $label): void
    {
        if ($this->labelFailure) {
            throw $this->labelFailure;
        }

        $this->labels[] = ['id' => $gmailMessageId, 'label' => $label];
    }

    public function trashThread(Mailbox $mailbox, string $gmailThreadId): int
    {
        if ($this->trashFailure) {
            throw $this->trashFailure;
        }

        $this->trashedThreads[] = $gmailThreadId;

        return 1;
    }

    public function restoreThread(Mailbox $mailbox, string $gmailThreadId): int
    {
        if ($this->trashFailure) {
            throw $this->trashFailure;
        }

        $this->restoredThreads[] = $gmailThreadId;

        return 1;
    }

    public function listTrash(Mailbox $mailbox, int $limit = 100): array
    {
        if ($this->trashFailure) {
            throw $this->trashFailure;
        }

        return array_slice($this->trash, 0, $limit);
    }

    public function send(Mailbox $mailbox, OutboundEmail $email): SentEmail
    {
        if ($this->sendFailure) {
            throw $this->sendFailure;
        }

        $this->sent[] = $email;
        $this->sentFrom[] = $mailbox->id;

        return new SentEmail($email->messageId);
    }

    public function testConnection(Mailbox $mailbox, ?MailboxAccount $account = null): ConnectionStatus
    {
        $this->testedWith = $account;

        return $this->status;
    }

    public static function authFailure(): ImapException
    {
        return ImapException::authentication('Login IMAP rifiutato: AUTHENTICATIONFAILED');
    }

    public function labelsOf(string $gmailMessageId): array
    {
        return array_values(array_map(
            fn (array $row) => $row['label'],
            array_filter($this->labels, fn (array $row) => $row['id'] === $gmailMessageId),
        ));
    }

    public function lastSent(): OutboundEmail
    {
        return end($this->sent) ?: throw new RuntimeException('Nessuna email inviata.');
    }
}
