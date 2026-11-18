<?php

namespace Tests\Unit;

use App\Ticketing\Mailbox\Imap\ImapConnection;
use App\Ticketing\Mailbox\Imap\ImapException;
use PHPUnit\Framework\TestCase;

class ImapXOAuth2Test extends TestCase
{
    public function test_sasl_string(): void
    {
        $this->assertSame(
            base64_encode("user=a@gmail.com\x01auth=Bearer tok\x01\x01"),
            ImapConnection::xoauth2('a@gmail.com', 'tok'),
        );
    }

    public function test_authenticate_sends_the_initial_response_and_accepts_ok(): void
    {
        $imap = $this->scripted(["* CAPABILITY IMAP4rev1\r\n", "A0001 OK a@gmail.com authenticated (Success)\r\n"]);

        $imap->authenticateXOAuth2('a@gmail.com', 'tok');

        $this->assertSame(['A0001 AUTHENTICATE XOAUTH2 '.ImapConnection::xoauth2('a@gmail.com', 'tok')."\r\n"], $imap->written);
    }

    public function test_401_challenge_is_answered_with_an_empty_line_and_is_definitive(): void
    {
        $challenge = base64_encode('{"status":"401","schemes":"Bearer","scope":"https://mail.google.com/"}');
        $imap = $this->scripted(["+ {$challenge}\r\n", "A0001 NO Invalid credentials (Failure)\r\n"]);

        try {
            $imap->authenticateXOAuth2('a@gmail.com', 'secret-token');
            $this->fail('Expected ImapException');
        } catch (ImapException $exception) {
            $this->assertTrue($exception->authenticationFailed);
            $this->assertStringNotContainsString('secret-token', $exception->getMessage());
        }

        $this->assertSame("\r\n", $imap->written[1]);
        $this->assertSame([], $imap->lines);
    }

    public function test_other_refusals_are_temporary(): void
    {
        $challenge = base64_encode('{"status":"400","schemes":"Bearer"}');
        $imap = $this->scripted(["+ {$challenge}\r\n", "A0001 NO [UNAVAILABLE] Temporary failure\r\n"]);

        try {
            $imap->authenticateXOAuth2('a@gmail.com', 'tok');
            $this->fail('Expected ImapException');
        } catch (ImapException $exception) {
            $this->assertFalse($exception->authenticationFailed);
        }
    }

    public function test_authenticationfailed_without_challenge_is_definitive(): void
    {
        $imap = $this->scripted(["A0001 NO [AUTHENTICATIONFAILED] Invalid credentials (Failure)\r\n"]);

        try {
            $imap->authenticateXOAuth2('a@gmail.com', 'tok');
            $this->fail('Expected ImapException');
        } catch (ImapException $exception) {
            $this->assertTrue($exception->authenticationFailed);
        }
    }

    /**
     * @param  list<string>  $lines  server lines, read in order
     */
    private function scripted(array $lines): ImapConnection
    {
        return new class($lines) extends ImapConnection
        {
            /** @var list<string> */
            public array $written = [];

            /**
             * @param  list<string>  $lines
             */
            public function __construct(public array $lines)
            {
                parent::__construct('imap.example.test', 993);
            }

            protected function write(string $data): void
            {
                $this->written[] = $data;
            }

            protected function readLine(): string
            {
                return array_shift($this->lines) ?? throw new ImapException('Connessione IMAP interrotta.');
            }
        };
    }
}
