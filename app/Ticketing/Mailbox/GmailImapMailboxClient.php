<?php

namespace App\Ticketing\Mailbox;

use App\Models\Mailbox;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\DTO\ConnectionStatus;
use App\Ticketing\DTO\InboundEmail;
use App\Ticketing\DTO\MailboxDelta;
use App\Ticketing\DTO\OutboundEmail;
use App\Ticketing\DTO\SentEmail;
use App\Ticketing\DTO\SyncCursor;
use App\Ticketing\DTO\TrashedEmail;
use App\Ticketing\Mailbox\Imap\GmailImapExtensions;
use App\Ticketing\Mailbox\Imap\ImapConnection;
use App\Ticketing\Mailbox\Imap\ImapException;
use App\Ticketing\Mailbox\Imap\ImapResponse;
use Carbon\CarbonImmutable;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\Auth\PlainAuthenticator;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\MailMimeParser;

/**
 * Gmail over IMAP (read, with X-GM-* extensions) and SMTP (send), authenticated with XOAUTH2 when Google is
 * connected, otherwise with the app password. Host, port and credentials come from the Mailbox row.
 * Never deletes or marks anything as read: it reads with BODY.PEEK, adds labels and, only for closed tickets
 * when enabled in the settings, moves whole threads to the Gmail trash (recoverable for 30 days) and back.
 * ImapMailboxClient extends it for plain IMAP servers, reusing connection, UID search and SMTP.
 */
class GmailImapMailboxClient implements MailboxClient
{
    public function __construct(protected readonly EmailParser $parser = new EmailParser) {}

    public function fetchNew(Mailbox $mailbox, SyncCursor $cursor, int $limit = 50): MailboxDelta
    {
        $imap = $this->connect($mailbox);

        try {
            $folder = $mailbox->folder;
            $examine = $imap->command('EXAMINE '.$imap->quote($folder));
            if (! $examine->ok()) {
                throw new ImapException("Cartella {$folder} non accessibile: {$examine->text}");
            }

            $info = GmailImapExtensions::parseExamine($examine->untagged);
            $uidValidity = $info['uidvalidity'];

            $uids = $this->searchNewUids($imap, $cursor, $uidValidity);
            $hasMore = count($uids) > $limit;
            $uids = array_slice($uids, 0, $limit);

            $messages = [];
            foreach (array_chunk($uids, 10) as $batch) {
                $messages = array_merge($messages, $this->fetchBatch($imap, $mailbox, $batch, (string) $uidValidity));
            }

            $lastUid = $uids === []
                ? ($cursor->isEmpty() || $cursor->uidValidity !== $uidValidity ? max(0, ($info['uidnext'] ?? 1) - 1) : $cursor->lastUid)
                : max($uids);

            return new MailboxDelta($messages, new SyncCursor($uidValidity, $lastUid), $hasMore);
        } finally {
            $imap->logout();
        }
    }

    public function addLabel(Mailbox $mailbox, string $gmailMessageId, string $label): void
    {
        if (! ctype_digit($gmailMessageId)) {
            throw new ImapException('Id messaggio Gmail non valido.');
        }

        $imap = $this->connect($mailbox);

        try {
            $folders = GmailImapExtensions::parseSpecialUse($imap->command('LIST "" "*"')->untagged);
            $all = $folders['all'] ?? throw new ImapException('Cartella "Tutti i messaggi" non trovata (SPECIAL-USE \\All).');

            foreach ($this->labelPaths($label) as $path) {
                $imap->command('CREATE '.$imap->quote($path)); // "already exists" is fine
            }

            if (! $imap->command('SELECT '.$imap->quote($all))->ok()) {
                throw new ImapException('Impossibile aprire la cartella Tutti i messaggi.');
            }

            $uids = GmailImapExtensions::parseSearch($imap->command("UID SEARCH X-GM-MSGID {$gmailMessageId}")->untagged);
            if ($uids === []) {
                throw new ImapException("Messaggio {$gmailMessageId} non trovato per applicare la label.");
            }

            $store = $imap->command('UID STORE '.$uids[0].' +X-GM-LABELS ('.$imap->quote($label).')');
            if (! $store->ok()) {
                throw new ImapException("Label non applicata: {$store->text}");
            }
        } finally {
            $imap->logout();
        }
    }

    public function trashThread(Mailbox $mailbox, string $gmailThreadId): int
    {
        return $this->moveThread($mailbox, $gmailThreadId, 'all', 'trash');
    }

    public function restoreThread(Mailbox $mailbox, string $gmailThreadId): int
    {
        return $this->moveThread($mailbox, $gmailThreadId, 'trash', 'INBOX');
    }

    public function listTrash(Mailbox $mailbox, int $limit = 100): array
    {
        $imap = $this->connect($mailbox);

        try {
            $trash = $this->specialFolder($imap, 'trash');

            if (! $imap->command('EXAMINE '.$imap->quote($trash))->ok()) {
                throw new ImapException('Impossibile aprire il cestino.');
            }

            $uids = array_slice(GmailImapExtensions::parseSearch($imap->command('UID SEARCH ALL')->untagged), -$limit);

            if ($uids === []) {
                return [];
            }

            $response = $imap->command('UID FETCH '.implode(',', $uids).' (UID X-GM-THRID INTERNALDATE BODY.PEEK[HEADER])');
            if (! $response->ok()) {
                throw new ImapException("Lettura cestino fallita: {$response->text}");
            }

            $parser = new MailMimeParser;
            $emails = [];

            foreach (GmailImapExtensions::parseFetchResponses($response->untagged) as $fetched) {
                if ($fetched['thrid'] === null) {
                    continue;
                }

                $headers = $parser->parse((string) $fetched['body'], false);
                $from = $headers->getHeader('from');

                $emails[] = new TrashedEmail(
                    gmailThreadId: $fetched['thrid'],
                    fromAddress: $from instanceof AddressHeader ? $from->getEmail() : null,
                    fromName: $from instanceof AddressHeader ? ($from->getPersonName() ?: null) : null,
                    subject: (string) ($headers->getHeaderValue('subject') ?? ''),
                    date: $fetched['internal_date'],
                );
            }

            return array_reverse($emails); // newest first
        } finally {
            $imap->logout();
        }
    }

    /**
     * Moves every message of a thread from one folder (a SPECIAL-USE key) to another (a SPECIAL-USE key or a folder name).
     */
    private function moveThread(Mailbox $mailbox, string $gmailThreadId, string $from, string $to): int
    {
        // interpolated into an IMAP command: Gmail thread ids are decimal only
        if (! ctype_digit($gmailThreadId)) {
            throw new ImapException('Id thread Gmail non valido.');
        }

        $imap = $this->connect($mailbox);

        try {
            $source = $this->specialFolder($imap, $from);
            $target = $to === 'INBOX' ? 'INBOX' : $this->specialFolder($imap, $to);

            if (! $imap->command('SELECT '.$imap->quote($source))->ok()) {
                throw new ImapException("Impossibile aprire la cartella {$source}.");
            }

            $uids = GmailImapExtensions::parseSearch($imap->command("UID SEARCH X-GM-THRID {$gmailThreadId}")->untagged);

            if ($uids === []) {
                return 0; // already moved, or deleted by hand
            }

            $move = $imap->command('UID MOVE '.implode(',', $uids).' '.$imap->quote($target));
            if (! $move->ok()) {
                throw new ImapException("Thread non spostato in {$target}: {$move->text}");
            }

            return count($uids);
        } finally {
            $imap->logout();
        }
    }

    private function specialFolder(ImapConnection $imap, string $use): string
    {
        $folders = GmailImapExtensions::parseSpecialUse($imap->command('LIST "" "*"')->untagged);

        return $folders[$use] ?? throw new ImapException("Cartella Gmail non trovata (SPECIAL-USE {$use}).");
    }

    public function send(Mailbox $mailbox, OutboundEmail $email): SentEmail
    {
        $account = MailboxAccount::for($mailbox);

        $message = (new Email)
            ->from(new Address($mailbox->address, $mailbox->name))
            ->to($email->to)
            ->subject($email->subject)
            ->text($email->textBody);

        if ($email->htmlBody !== null) {
            $message->html($email->htmlBody);
        }

        $headers = $message->getHeaders();
        $headers->addIdHeader('Message-ID', trim($email->messageId, '<>'));

        if ($email->inReplyTo !== null) {
            $headers->addIdHeader('In-Reply-To', trim($email->inReplyTo, '<>'));
        }

        if ($email->references !== []) {
            $headers->addIdHeader('References', array_map(fn (string $id) => trim($id, '<>'), $email->references));
        }

        foreach ($email->attachments as $attachment) {
            $message->attachFromPath($attachment['path'], $attachment['name'], $attachment['mime']);
        }

        try {
            $this->smtp($account)->send($message);
        } catch (TransportExceptionInterface $exception) {
            if (! AuthFailure::smtp($exception)) {
                throw $exception;
            }

            if (! $account->usesOAuth()) {
                throw ImapException::authentication('SMTP: '.$exception->getMessage());
            }

            $mailbox->dropOAuth($exception->getMessage());
            $this->sendWithPassword($mailbox, $message);
        }

        $this->afterSend($mailbox, $message);

        return new SentEmail($email->messageId);
    }

    /**
     * Gmail stores the copy in "Sent" on its own: no IMAP APPEND needed.
     */
    protected function afterSend(Mailbox $mailbox, Email $message): void {}

    public function testConnection(Mailbox $mailbox, ?MailboxAccount $account = null): ConnectionStatus
    {
        $imapOk = $smtpOk = false;

        try {
            $imap = $this->connect($mailbox, $account);
            $list = $imap->command('LIST "" "*"');
            $imap->logout();
            $imapOk = $this->foldersReadable($list);
            if (! $imapOk) {
                return new ConnectionStatus(false, false, $this->unreadableFoldersMessage());
            }
        } catch (ImapException $exception) {
            return new ConnectionStatus(false, false, $exception->getMessage());
        } catch (\Throwable $exception) {
            return new ConnectionStatus(false, false, 'IMAP: '.$exception->getMessage());
        }

        try {
            $transport = $this->smtp($account ?? MailboxAccount::for($mailbox));
            if ($transport instanceof SmtpTransport) {
                $transport->start();
                $transport->stop();
            }
            $smtpOk = true;
        } catch (\Throwable $exception) {
            return new ConnectionStatus($imapOk, false, 'SMTP: '.$exception->getMessage());
        }

        return new ConnectionStatus($imapOk, $smtpOk);
    }

    protected function foldersReadable(ImapResponse $list): bool
    {
        return $list->ok() && GmailImapExtensions::parseSpecialUse($list->untagged) !== [];
    }

    protected function unreadableFoldersMessage(): string
    {
        return 'Accesso riuscito ma le cartelle Gmail non sono leggibili (IMAP abilitato?).';
    }

    /**
     * Without an explicit account, Gmail rejecting the OAuth token drops the link and retries once with the
     * app password. An explicit account (a connection test of specific credentials) is never swapped.
     */
    protected function connect(Mailbox $mailbox, ?MailboxAccount $account = null): ImapConnection
    {
        $canFallBack = $account === null;
        $account ??= MailboxAccount::for($mailbox);

        $imap = $this->newImap($account->mailbox);
        $imap->connect();

        try {
            if ($account->usesOAuth()) {
                $imap->authenticateXOAuth2($account->username(), $account->accessToken);
            } else {
                $imap->login($account->username(), $account->password());
            }
        } catch (ImapException $exception) {
            $imap->logout();

            if (! $canFallBack || ! $account->usesOAuth() || ! $exception->authenticationFailed) {
                throw $exception;
            }

            $mailbox->dropOAuth($exception->getMessage());

            return $this->connect($mailbox, MailboxAccount::for($mailbox));
        }

        return $imap;
    }

    protected function newImap(Mailbox $mailbox): ImapConnection
    {
        return new ImapConnection($mailbox->imap_host, (int) $mailbox->imap_port, (int) config('ticketing.mailbox.timeout'));
    }

    /**
     * One authenticator only (XOAUTH2 with a token, PLAIN with the password): every extra mechanism would be another failed login.
     * Port 465 uses implicit TLS; on other ports (587) Symfony negotiates STARTTLS.
     */
    protected function smtp(MailboxAccount $account): TransportInterface
    {
        $mailbox = $account->mailbox;
        $transport = new EsmtpTransport($mailbox->smtp_host, (int) $mailbox->smtp_port, (int) $mailbox->smtp_port === 465);
        $transport->setUsername($account->username());

        if ($account->usesOAuth()) {
            $transport->setPassword($account->accessToken);
            $transport->setAuthenticators([new XOAuth2Authenticator]);

            return $transport;
        }

        $transport->setPassword($account->password());
        $transport->setAuthenticators([new PlainAuthenticator]);

        return $transport;
    }

    /**
     * The single retry after Google rejected the token: same message (same Message-ID), app password.
     */
    private function sendWithPassword(Mailbox $mailbox, Email $message): void
    {
        try {
            $this->smtp(MailboxAccount::for($mailbox))->send($message);
        } catch (TransportExceptionInterface $exception) {
            throw AuthFailure::smtp($exception)
                ? ImapException::authentication('SMTP: '.$exception->getMessage())
                : $exception;
        }
    }

    /**
     * @return list<int>
     */
    protected function searchNewUids(ImapConnection $imap, SyncCursor $cursor, ?string $uidValidity): array
    {
        if ($cursor->isEmpty() || $cursor->uidValidity !== $uidValidity) {
            $since = CarbonImmutable::now()->subDays((int) config('ticketing.mailbox.initial_days'))->format('d-M-Y');
            $response = $imap->command("UID SEARCH SINCE {$since}");
        } else {
            $response = $imap->command('UID SEARCH UID '.($cursor->lastUid + 1).':*');
        }

        $uids = GmailImapExtensions::parseSearch($response->untagged);

        // "N:*" always returns the last message even when its UID is below N
        if (! $cursor->isEmpty() && $cursor->uidValidity === $uidValidity) {
            $uids = array_values(array_filter($uids, fn (int $uid) => $uid > $cursor->lastUid));
        }

        return $uids;
    }

    /**
     * @param  list<int>  $uids
     * @return list<InboundEmail>
     */
    protected function fetchBatch(ImapConnection $imap, Mailbox $mailbox, array $uids, string $uidValidity): array
    {
        $response = $imap->command('UID FETCH '.implode(',', $uids).' (UID X-GM-MSGID X-GM-THRID X-GM-LABELS FLAGS INTERNALDATE BODY.PEEK[])');

        if (! $response->ok()) {
            throw new ImapException("Download messaggi fallito: {$response->text}");
        }

        $emails = [];
        foreach (GmailImapExtensions::parseFetchResponses($response->untagged) as $fetched) {
            if ($fetched['msgid'] === null || $fetched['body'] === null) {
                continue;
            }

            $emails[] = $this->parser->parse($fetched['body'], [
                'gmail_message_id' => $fetched['msgid'],
                'gmail_thread_id' => $fetched['thrid'],
                'uid' => $fetched['uid'],
                'labels' => $fetched['labels'],
                'received_at' => $fetched['internal_date'],
                'mailbox_id' => $mailbox->id,
            ], (int) config('ticketing.mailbox.max_attachment_kb') * 1024);
        }

        return $emails;
    }

    /**
     * "gestionale/importata" needs "gestionale" first.
     *
     * @return list<string>
     */
    private function labelPaths(string $label): array
    {
        $paths = [];
        $current = '';

        foreach (explode('/', $label) as $segment) {
            $current = $current === '' ? $segment : "{$current}/{$segment}";
            $paths[] = $current;
        }

        return $paths;
    }
}
