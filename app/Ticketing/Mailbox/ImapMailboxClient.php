<?php

namespace App\Ticketing\Mailbox;

use App\Models\Mailbox;
use App\Ticketing\DTO\InboundEmail;
use App\Ticketing\Mailbox\Imap\GmailImapExtensions;
use App\Ticketing\Mailbox\Imap\ImapConnection;
use App\Ticketing\Mailbox\Imap\ImapException;
use App\Ticketing\Mailbox\Imap\ImapResponse;

/**
 * Any IMAP/SMTP server (e.g. the hosting mailbox), username and password over TLS.
 * No Gmail extensions: messages are identified by their Message-ID (UID as fallback), threads are matched
 * by headers and the [T-…] subject token, labels and the thread trash are not available.
 * The copy of sent replies is not appended to "Sent": replies are visible in the ticket.
 */
class ImapMailboxClient extends GmailImapMailboxClient
{
    public function addLabel(Mailbox $mailbox, string $gmailMessageId, string $label): void {}

    public function trashThread(Mailbox $mailbox, string $gmailThreadId): int
    {
        return 0;
    }

    public function restoreThread(Mailbox $mailbox, string $gmailThreadId): int
    {
        return 0;
    }

    public function listTrash(Mailbox $mailbox, int $limit = 100): array
    {
        return [];
    }

    protected function foldersReadable(ImapResponse $list): bool
    {
        return $list->ok();
    }

    protected function unreadableFoldersMessage(): string
    {
        return 'Accesso riuscito ma le cartelle della casella non sono leggibili.';
    }

    /**
     * @param  list<int>  $uids
     * @return list<InboundEmail>
     */
    protected function fetchBatch(ImapConnection $imap, Mailbox $mailbox, array $uids, string $uidValidity): array
    {
        $response = $imap->command('UID FETCH '.implode(',', $uids).' (UID FLAGS INTERNALDATE BODY.PEEK[])');

        if (! $response->ok()) {
            throw new ImapException("Download messaggi fallito: {$response->text}");
        }

        $emails = [];
        foreach (GmailImapExtensions::parseFetchResponses($response->untagged) as $fetched) {
            if ($fetched['body'] === null) {
                continue;
            }

            $emails[] = $this->parser->parse($fetched['body'], [
                'gmail_message_id' => self::messageKey($mailbox, $fetched['body'], $uidValidity, $fetched['uid']),
                'gmail_thread_id' => null,
                'uid' => $fetched['uid'],
                'labels' => [],
                'received_at' => $fetched['internal_date'],
                'mailbox_id' => $mailbox->id,
            ], (int) config('ticketing.mailbox.max_attachment_kb') * 1024);
        }

        return $emails;
    }

    /**
     * Stable dedup key: the Message-ID survives a UIDVALIDITY reset, the UID is the fallback for messages without one.
     */
    public static function messageKey(Mailbox $mailbox, string $raw, string $uidValidity, int $uid): string
    {
        $headers = strstr($raw, "\r\n\r\n", true) ?: (strstr($raw, "\n\n", true) ?: $raw);
        $messageId = preg_match('/^Message-ID:\s*(<[^>]+>)/im', $headers, $match) === 1 ? $match[1] : null;

        return 'imap-'.$mailbox->id.'-'.sha1($messageId ?? "uid:{$uidValidity}:{$uid}");
    }
}
