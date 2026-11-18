<?php

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\DTO\OutboundEmail;
use App\Ticketing\DTO\SyncCursor;
use App\Ticketing\Jobs\SyncMailboxJob;
use App\Ticketing\Mailbox\GmailImapMailboxClient;
use App\Ticketing\Mailbox\GoogleOAuthException;
use App\Ticketing\Mailbox\Imap\ImapConnection;
use App\Ticketing\Mailbox\Imap\ImapException;
use App\Ticketing\Mailbox\Imap\ImapResponse;
use App\Ticketing\Mailbox\MailboxAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Smtp\Auth\PlainAuthenticator;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;
use Tests\Concerns\BuildsSupportFixtures;
use Tests\TestCase;
use Tests\Unit\AuthFailureTest;

class MailboxOAuthFallbackTest extends TestCase
{
    use BuildsSupportFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ticketing.mailbox.oauth.client_id' => 'cid',
            'ticketing.mailbox.oauth.client_secret' => 'sec',
        ]);
        Cache::flush();

        $this->defaultMailbox->forceFill([
            'address' => 'negozio@gmail.com',
            'username' => 'negozio@gmail.com',
            'password' => 'abcdabcdabcdabcd',
            'oauth_refresh_token' => 'r1',
            'oauth_connected_at' => now(),
        ])->save();
    }

    private function storedMailbox(): Mailbox
    {
        return $this->defaultMailbox->fresh();
    }

    public function test_for_uses_oauth_when_a_refresh_token_is_stored(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'a1', 'expires_in' => 3599])]);

        $account = MailboxAccount::for($this->storedMailbox());

        $this->assertSame('a1', $account->accessToken);
        $this->assertTrue($account->usesOAuth());
    }

    public function test_for_uses_the_app_password_without_a_refresh_token(): void
    {
        Http::fake();
        $this->storedMailbox()->forceFill(['oauth_refresh_token' => null])->save();

        $account = MailboxAccount::for($this->storedMailbox());

        $this->assertFalse($account->usesOAuth());
        $this->assertSame('abcdabcdabcdabcd', $account->password());
        Http::assertNothingSent();
    }

    public function test_for_uses_the_password_for_an_imap_mailbox_even_with_a_refresh_token(): void
    {
        Http::fake();
        $this->storedMailbox()->forceFill(['driver' => Mailbox::DRIVER_IMAP])->save();

        $account = MailboxAccount::for($this->storedMailbox());

        $this->assertFalse($account->usesOAuth());
        Http::assertNothingSent();
    }

    public function test_invalid_grant_falls_back_to_the_app_password_and_warns_once(): void
    {
        $admin = $this->admin();
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);

        $account = MailboxAccount::for($this->storedMailbox());

        $this->assertFalse($account->usesOAuth());
        $this->assertSame('abcdabcdabcdabcd', $account->password());
        $mailbox = $this->storedMailbox();
        $this->assertNull($mailbox->oauth_refresh_token);
        $this->assertNull($mailbox->oauth_connected_at);
        $this->assertSame(1, $admin->notifications()->count());

        MailboxAccount::for($this->storedMailbox());

        $this->assertSame(1, $admin->notifications()->count());
        Http::assertSentCount(1);
    }

    public function test_temporary_token_errors_keep_oauth(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response('boom', 503)]);

        try {
            MailboxAccount::for($this->storedMailbox());
            $this->fail('Expected GoogleOAuthException');
        } catch (GoogleOAuthException $exception) {
            $this->assertFalse($exception->revoked);
        }

        $this->assertSame('r1', $this->storedMailbox()->oauth_refresh_token);
    }

    public function test_imap_rejecting_a_fresh_token_falls_back_once(): void
    {
        $admin = $this->admin();
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'a1', 'expires_in' => 3599])]);
        $client = $this->client();

        $status = $client->testConnection($this->storedMailbox());

        $this->assertTrue($status->ok());
        $this->assertSame([['negozio@gmail.com', 'abcdabcdabcdabcd']], $client->imap->logins);
        $this->assertSame([['negozio@gmail.com', 'a1']], $client->imap->oauthAttempts);
        $this->assertNull($this->storedMailbox()->oauth_refresh_token);
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertFalse($client->smtpAccounts[0]->usesOAuth());
    }

    public function test_an_explicit_account_never_falls_back_to_the_password(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $mailbox = $this->storedMailbox();

        $status = $client->testConnection($mailbox, new MailboxAccount($mailbox, 'fresh'));

        $this->assertFalse($status->ok());
        $this->assertSame([], $client->imap->logins);
        $this->assertSame([['negozio@gmail.com', 'fresh']], $client->imap->oauthAttempts);
        $this->assertSame('r1', $this->storedMailbox()->oauth_refresh_token);
        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_rejected_password_after_the_fallback_is_not_retried(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'a1', 'expires_in' => 3599])]);
        $client = $this->client();
        $client->imap->rejectPassword = true;

        try {
            $client->fetchNew($this->storedMailbox(), new SyncCursor);
            $this->fail('Expected ImapException');
        } catch (ImapException $exception) {
            $this->assertTrue($exception->authenticationFailed);
        }

        $this->assertCount(1, $client->imap->oauthAttempts);
        $this->assertCount(1, $client->imap->logins);
    }

    public function test_sync_falls_back_to_the_password_in_the_same_run(): void
    {
        $admin = $this->admin();
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);
        $client = $this->client();

        $delta = $client->fetchNew($this->storedMailbox(), new SyncCursor);

        $this->assertSame([], $delta->messages);
        $this->assertSame('7', $delta->cursor->uidValidity);
        $this->assertSame(4, $delta->cursor->lastUid);
        $this->assertSame([['negozio@gmail.com', 'abcdabcdabcdabcd']], $client->imap->logins);
        $this->assertSame([], $client->imap->oauthAttempts);
        $this->assertNull($this->storedMailbox()->oauth_refresh_token);
        $this->assertSame(1, $admin->notifications()->count());
    }

    public function test_smtp_uses_only_xoauth2_with_a_token(): void
    {
        $client = new class extends GmailImapMailboxClient
        {
            public function transportFor(MailboxAccount $account): TransportInterface
            {
                return $this->smtp($account);
            }
        };

        $transport = $client->transportFor(new MailboxAccount($this->storedMailbox(), 'a1'));

        $this->assertInstanceOf(EsmtpTransport::class, $transport);
        $this->assertSame('negozio@gmail.com', $transport->getUsername());
        $this->assertSame('a1', $transport->getPassword());
        $authenticators = (new \ReflectionProperty(EsmtpTransport::class, 'authenticators'))->getValue($transport);
        $this->assertCount(1, $authenticators);
        $this->assertInstanceOf(XOAuth2Authenticator::class, $authenticators[0]);

        $password = $client->transportFor(new MailboxAccount($this->storedMailbox()));
        $this->assertSame('abcdabcdabcdabcd', $password->getPassword());
    }

    public function test_smtp_uses_only_plain_with_the_app_password(): void
    {
        $client = new class extends GmailImapMailboxClient
        {
            public function transportFor(MailboxAccount $account): TransportInterface
            {
                return $this->smtp($account);
            }
        };

        $transport = $client->transportFor(new MailboxAccount($this->storedMailbox()));

        $this->assertInstanceOf(EsmtpTransport::class, $transport);
        $authenticators = (new \ReflectionProperty(EsmtpTransport::class, 'authenticators'))->getValue($transport);
        $this->assertCount(1, $authenticators);
        $this->assertInstanceOf(PlainAuthenticator::class, $authenticators[0]);
    }

    public function test_for_uses_the_app_password_when_google_is_not_configured(): void
    {
        config(['ticketing.mailbox.oauth.client_id' => null, 'ticketing.mailbox.oauth.client_secret' => null]);
        Http::fake();

        $account = MailboxAccount::for($this->storedMailbox());

        $this->assertFalse($account->usesOAuth());
        $this->assertSame('abcdabcdabcdabcd', $account->password());
        $this->assertSame('r1', $this->storedMailbox()->oauth_refresh_token);
        Http::assertNothingSent();
    }

    public function test_sync_with_a_temporary_token_error_counts_a_failure_and_keeps_the_link(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response('boom', 503)]);
        $this->app->instance(MailboxClient::class, $this->client());

        SyncMailboxJob::dispatchSync($this->defaultMailbox->id);

        $mailbox = $this->storedMailbox();
        $this->assertSame(1, $mailbox->consecutive_failures);
        $this->assertFalse($mailbox->isDisconnected());
        $this->assertSame('r1', $mailbox->oauth_refresh_token);
    }

    public function test_smtp_rejecting_the_token_drops_oauth_and_resends_once_with_the_password(): void
    {
        $admin = $this->admin();
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'a1', 'expires_in' => 3599])]);
        $client = $this->client();
        $client->smtpFailures = [AuthFailureTest::authFailure('XOAUTH2', 334, '334 eyJzdGF0dXMiOiI0MDAiLCJzY2hlbWVzIjoiQmVhcmVyIn0=')];

        $sent = $client->send($this->storedMailbox(), $this->outbound());

        $this->assertSame('<reply1@negozio.example>', $sent->messageId);
        $this->assertCount(2, $client->smtpAccounts);
        $this->assertTrue($client->smtpAccounts[0]->usesOAuth());
        $this->assertFalse($client->smtpAccounts[1]->usesOAuth());
        $this->assertCount(1, $client->delivered);
        $this->assertSame('reply1@negozio.example', $client->delivered[0]->getHeaders()->get('Message-ID')->getId());
        $this->assertNull($this->storedMailbox()->oauth_refresh_token);
        $this->assertSame(1, $admin->notifications()->count());
    }

    public function test_smtp_rejecting_the_app_password_is_an_authentication_failure(): void
    {
        $this->storedMailbox()->forceFill(['oauth_refresh_token' => null])->save();
        $client = $this->client();
        $client->smtpFailures = [AuthFailureTest::authFailure('LOGIN', 535, '535-5.7.8 Username and Password not accepted.')];

        try {
            $client->send($this->storedMailbox(), $this->outbound());
            $this->fail('Expected ImapException');
        } catch (ImapException $exception) {
            $this->assertTrue($exception->authenticationFailed);
        }

        $this->assertCount(1, $client->smtpAccounts);
    }

    public function test_temporary_smtp_errors_keep_oauth(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'a1', 'expires_in' => 3599])]);
        $client = $this->client();
        $client->smtpFailures = [AuthFailureTest::authFailure('XOAUTH2', 454, '454 4.7.0 Too many login attempts, please try again later.')];

        try {
            $client->send($this->storedMailbox(), $this->outbound());
            $this->fail('Expected TransportException');
        } catch (TransportException) {
        }

        $this->assertCount(1, $client->smtpAccounts);
        $this->assertSame('r1', $this->storedMailbox()->oauth_refresh_token);
    }

    private function outbound(): OutboundEmail
    {
        return new OutboundEmail(
            to: 'cliente@example.com',
            subject: 'Re: richiesta',
            textBody: 'Ciao',
            htmlBody: null,
            messageId: '<reply1@negozio.example>',
        );
    }

    /**
     * Real client logic over a scripted IMAP connection and a recording SMTP transport.
     */
    private function client(): GmailImapMailboxClient
    {
        $imap = new class('imap.example.test', 993) extends ImapConnection
        {
            /** @var list<array{0: string, 1: string}> */
            public array $logins = [];

            /** @var list<array{0: string, 1: string}> */
            public array $oauthAttempts = [];

            public bool $rejectPassword = false;

            public function connect(): void {}

            public function login(string $username, string $password): void
            {
                $this->logins[] = [$username, $password];

                if ($this->rejectPassword) {
                    throw ImapException::authentication('Login IMAP rifiutato: [AUTHENTICATIONFAILED] Invalid credentials');
                }
            }

            public function authenticateXOAuth2(string $user, string $accessToken): void
            {
                $this->oauthAttempts[] = [$user, $accessToken];

                throw ImapException::authentication('Autenticazione XOAUTH2 rifiutata: Invalid credentials (Failure)');
            }

            public function command(string $command): ImapResponse
            {
                return match (true) {
                    str_starts_with($command, 'LIST') => new ImapResponse('OK', 'LIST done', ['* LIST (\\HasNoChildren \\All) "/" "[Gmail]/Tutti i messaggi"']),
                    str_starts_with($command, 'EXAMINE') => new ImapResponse('OK', '[READ-ONLY] EXAMINE done', ['* OK [UIDVALIDITY 7] UIDs valid', '* OK [UIDNEXT 5] Predicted next UID']),
                    str_starts_with($command, 'UID SEARCH') => new ImapResponse('OK', 'SEARCH done', ['* SEARCH']),
                    default => new ImapResponse('OK', 'done', []),
                };
            }

            public function logout(): void {}
        };

        return new class($imap) extends GmailImapMailboxClient
        {
            /** @var list<MailboxAccount> */
            public array $smtpAccounts = [];

            /** @var list<\Throwable> */
            public array $smtpFailures = [];

            /** @var list<Message> */
            public array $delivered = [];

            public function __construct(public ImapConnection $imap)
            {
                parent::__construct();
            }

            protected function newImap(Mailbox $mailbox): ImapConnection
            {
                return $this->imap;
            }

            protected function smtp(MailboxAccount $account): TransportInterface
            {
                $this->smtpAccounts[] = $account;
                $client = $this;

                return new class($client) implements TransportInterface
                {
                    public function __construct(private readonly object $client) {}

                    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
                    {
                        if ($this->client->smtpFailures !== []) {
                            throw array_shift($this->client->smtpFailures);
                        }

                        $this->client->delivered[] = $message;

                        return null;
                    }

                    public function __toString(): string
                    {
                        return 'recording://';
                    }
                };
            }
        };
    }
}
