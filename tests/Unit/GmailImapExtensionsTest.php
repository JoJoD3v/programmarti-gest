<?php

namespace Tests\Unit;

use App\Ticketing\Mailbox\Imap\GmailImapExtensions;
use PHPUnit\Framework\TestCase;

class GmailImapExtensionsTest extends TestCase
{
    public function test_parses_fetch_with_gmail_extensions_and_literal_body(): void
    {
        $raw = "From: a@b.it\r\nSubject: Ciao\r\n\r\nTesto";
        $line = '* 12 FETCH (UID 345 X-GM-MSGID 1742857409128314000 X-GM-THRID 1742857409128314111 '
            .'X-GM-LABELS ("\\\\Inbox" "gestionale/importata" "Etichetta con spazi") FLAGS (\\Seen) '
            .'INTERNALDATE "27-Oct-2026 10:15:30 +0100" BODY[] {'.strlen($raw)."}\r\n{$raw})";

        $messages = GmailImapExtensions::parseFetchResponses([$line]);

        $this->assertCount(1, $messages);
        $message = $messages[0];
        $this->assertSame(345, $message['uid']);
        $this->assertSame('1742857409128314000', $message['msgid']);
        $this->assertSame('1742857409128314111', $message['thrid']);
        $this->assertSame(['\\Inbox', 'gestionale/importata', 'Etichetta con spazi'], $message['labels']);
        $this->assertSame(['\\Seen'], $message['flags']);
        $this->assertSame($raw, $message['body']);
        $this->assertSame('2026-10-27 10:15:30', $message['internal_date']->format('Y-m-d H:i:s'));
    }

    public function test_literal_containing_parentheses_and_quotes_does_not_break_parsing(): void
    {
        $raw = "Subject: (test) \"quoted\" )\r\n\r\nbody";
        $line = '* 1 FETCH (UID 7 X-GM-MSGID 99 BODY[] {'.strlen($raw)."}\r\n{$raw})";

        $this->assertSame($raw, GmailImapExtensions::parseFetchResponses([$line])[0]['body']);
    }

    public function test_search_ignores_empty_result_and_sorts(): void
    {
        $this->assertSame([], GmailImapExtensions::parseSearch(['* SEARCH']));
        $this->assertSame([3, 9, 12], GmailImapExtensions::parseSearch(['* SEARCH 12 3 9']));
    }

    public function test_examine_reads_uidvalidity_and_uidnext(): void
    {
        $info = GmailImapExtensions::parseExamine(['* OK [UIDVALIDITY 1234567] UIDs valid', '* OK [UIDNEXT 8801] Predicted next UID', '* 4 EXISTS']);

        $this->assertSame('1234567', $info['uidvalidity']);
        $this->assertSame(8801, $info['uidnext']);
    }

    public function test_special_use_folders_are_found_whatever_their_localized_name(): void
    {
        $folders = GmailImapExtensions::parseSpecialUse([
            '* LIST (\\HasNoChildren) "/" "INBOX"',
            '* LIST (\\HasNoChildren \\All) "/" "[Gmail]/Tutti i messaggi"',
            '* LIST (\\HasNoChildren \\Sent) "/" "[Gmail]/Posta inviata"',
            '* LIST (\\HasNoChildren \\Junk) "/" "[Gmail]/Spam"',
            '* LIST (\\HasNoChildren \\Trash) "/" "[Gmail]/Cestino"',
        ]);

        $this->assertSame('[Gmail]/Tutti i messaggi', $folders['all']);
        $this->assertSame('[Gmail]/Posta inviata', $folders['sent']);
        $this->assertSame('[Gmail]/Cestino', $folders['trash']);
    }
}
