<?php

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Ticketing\DTO\SyncCursor;
use App\Ticketing\Mailbox\GmailImapMailboxClient;
use App\Ticketing\Mailbox\Imap\ImapConnection;
use App\Ticketing\Mailbox\Imap\ImapResponse;
use App\Ticketing\Mailbox\ImapMailboxClient;
use App\Ticketing\Mailbox\MailboxAccount;
use App\Ticketing\Mailbox\MailboxRouter;
use Tests\TestCase;

/**
 * The plain IMAP client (hosting mailbox): no Gmail extensions, Message-ID based dedup key.
 */
class ImapMailboxClientTest extends TestCase
{
    public function test_fetch_reads_plain_imap_messages_without_gmail_extensions(): void
    {
        $mailbox = $this->mailbox();
        $raw = "From: Mario Rossi <mario@cliente.example>\r\nTo: info@programmarti.it\r\nSubject: Sito lento\r\nMessage-ID: <abc@cliente.example>\r\n\r\nIl sito e' lento.\r\n";
        $client = $this->clientAnswering([
            'EXAMINE' => new ImapResponse('OK', 'ok', ['* OK [UIDVALIDITY 77] UIDs valid', '* OK [UIDNEXT 5] next']),
            'UID SEARCH' => new ImapResponse('OK', 'ok', ['* SEARCH 4']),
            'UID FETCH' => new ImapResponse('OK', 'ok', ['* 1 FETCH (UID 4 FLAGS () INTERNALDATE "18-Nov-2026 10:00:00 +0000" BODY[] {'.strlen($raw)."}\r\n{$raw})"]),
        ]);

        $delta = $client->fetchNew($mailbox, new SyncCursor('77', 3));

        $this->assertCount(1, $delta->messages);
        $email = $delta->messages[0];
        $this->assertSame(ImapMailboxClient::messageKey($mailbox, $raw, '77', 4), $email->gmailMessageId);
        $this->assertNull($email->gmailThreadId);
        $this->assertSame(9, $email->mailboxId);
        $this->assertSame('mario@cliente.example', $email->fromEmail);
        $this->assertSame('<abc@cliente.example>', $email->messageIdHeader);
        $this->assertSame(4, $delta->cursor->lastUid);
        $this->assertStringNotContainsString('X-GM', implode(' ', $client->commands));
    }

    public function test_message_key_survives_a_uid_change_and_falls_back_to_the_uid(): void
    {
        $mailbox = $this->mailbox();
        $withId = "Message-ID:\r\n <abc@cliente.example>\r\nSubject: x\r\n\r\nbody";
        $withoutId = "Subject: x\r\n\r\nbody";

        $this->assertSame(ImapMailboxClient::messageKey($mailbox, $withId, '1', 10), ImapMailboxClient::messageKey($mailbox, $withId, '2', 99));
        $this->assertNotSame(ImapMailboxClient::messageKey($mailbox, $withoutId, '1', 10), ImapMailboxClient::messageKey($mailbox, $withoutId, '1', 11));
        $this->assertStringStartsWith('imap-9-', ImapMailboxClient::messageKey($mailbox, $withoutId, '1', 10));
    }

    public function test_labels_and_trash_are_no_ops(): void
    {
        $client = $this->clientAnswering([]);
        $mailbox = $this->mailbox();

        $client->addLabel($mailbox, 'imap-9-x', 'gestionale/importata');

        $this->assertSame(0, $client->trashThread($mailbox, '123'));
        $this->assertSame([], $client->listTrash($mailbox));
        $this->assertSame([], $client->commands);
    }

    public function test_router_picks_the_client_of_the_mailbox_driver(): void
    {
        $gmail = $this->createMock(GmailImapMailboxClient::class);
        $imap = $this->createMock(ImapMailboxClient::class);
        $gmail->expects($this->once())->method('listTrash')->willReturn([]);
        $imap->expects($this->never())->method('listTrash');
        $imap->expects($this->once())->method('trashThread')->willReturn(0);
        $gmail->expects($this->never())->method('trashThread');

        $router = new MailboxRouter($gmail, $imap);
        $router->listTrash($this->mailbox(['driver' => Mailbox::DRIVER_GMAIL]));
        $router->trashThread($this->mailbox(), '1');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function mailbox(array $attributes = []): Mailbox
    {
        return (new Mailbox)->forceFill($attributes + [
            'id' => 9,
            'name' => 'ProgrammArti',
            'address' => 'info@programmarti.it',
            'driver' => Mailbox::DRIVER_IMAP,
            'imap_host' => 'mail.example.test',
            'imap_port' => 993,
            'smtp_host' => 'mail.example.test',
            'smtp_port' => 465,
            'username' => 'info@programmarti.it',
            'password' => 'secret',
            'folder' => 'INBOX',
        ]);
    }

    /**
     * Client whose IMAP connection answers each command by prefix, without any network.
     *
     * @param  array<string, ImapResponse>  $answers
     */
    private function clientAnswering(array $answers): ImapMailboxClient
    {
        return new class($answers) extends ImapMailboxClient
        {
            /** @var list<string> */
            public array $commands = [];

            /**
             * @param  array<string, ImapResponse>  $answers
             */
            public function __construct(private readonly array $answers)
            {
                parent::__construct();
            }

            protected function connect(Mailbox $mailbox, ?MailboxAccount $account = null): ImapConnection
            {
                $client = $this;

                return new class($client) extends ImapConnection
                {
                    public function __construct(private readonly object $client)
                    {
                        parent::__construct('mail.example.test', 993);
                    }

                    public function command(string $command): ImapResponse
                    {
                        $this->client->commands[] = $command;

                        return $this->client->answer($command);
                    }

                    public function logout(): void {}
                };
            }

            public function answer(string $command): ImapResponse
            {
                foreach ($this->answers as $prefix => $response) {
                    if (str_starts_with($command, $prefix)) {
                        return $response;
                    }
                }

                return new ImapResponse('OK', 'ok', []);
            }
        };
    }
}
