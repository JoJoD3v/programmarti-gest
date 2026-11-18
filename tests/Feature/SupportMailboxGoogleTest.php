<?php

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Ticketing\DTO\ConnectionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsSupportFixtures;
use Tests\TestCase;

class SupportMailboxGoogleTest extends TestCase
{
    use BuildsSupportFixtures, RefreshDatabase;

    private const STATE_KEY = 'ticketing.google_oauth_state';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ticketing.mailbox.oauth.client_id' => 'client-id',
            'ticketing.mailbox.oauth.client_secret' => 'client-secret',
        ]);
    }

    private function connectMailbox(): Mailbox
    {
        $this->defaultMailbox->forceFill([
            'address' => 'negozio@gmail.com',
            'username' => 'negozio@gmail.com',
            'password' => 'abcdabcdabcdabcd',
        ])->save();

        return $this->defaultMailbox;
    }

    private function fakeGoogleTokens(?string $email = 'negozio@gmail.com', ?string $refreshToken = 'refresh-token-r1'): void
    {
        $idToken = $email === null ? null : 'h.'.rtrim(strtr(base64_encode(json_encode(['email' => $email])), '+/', '-_'), '=').'.s';

        Http::fake(['oauth2.googleapis.com/*' => Http::response(array_filter([
            'access_token' => 'access-token-a1',
            'expires_in' => 3600,
            'refresh_token' => $refreshToken,
            'id_token' => $idToken,
        ]))]);
    }

    /**
     * @return array<string, string>
     */
    private function callbackQuery(array $overrides = []): array
    {
        return $overrides + ['code' => 'the-code', 'state' => 'good-state'];
    }

    private function visitCallback(array $query, ?string $sessionState = 'good-state')
    {
        $session = $sessionState === null ? [] : [self::STATE_KEY => ['state' => $sessionState, 'mailbox' => $this->defaultMailbox->id]];

        return $this->actingAs($this->admin())->withSession($session)
            ->get(route('support.mailboxes.google.callback', $query));
    }

    private function assertNothingSaved(): void
    {
        $this->assertNull($this->defaultMailbox->fresh()->oauth_refresh_token);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function mailboxForm(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'ProgrammArti',
            'address' => 'negozio@gmail.com',
            'driver' => Mailbox::DRIVER_GMAIL,
            'username' => '',
            'imap_host' => '',
            'imap_port' => '',
            'smtp_host' => '',
            'smtp_port' => '',
            'folder' => '',
            'active' => '1',
        ];
    }

    public function test_redirect_requires_admin_and_stores_state(): void
    {
        $mailbox = $this->connectMailbox();

        $this->actingAs($this->agent())->get(route('support.mailboxes.google.redirect', $mailbox))->assertForbidden();

        $response = $this->actingAs($this->admin())->get(route('support.mailboxes.google.redirect', $mailbox));

        $response->assertRedirectContains('https://accounts.google.com/');
        $pending = session(self::STATE_KEY);
        $this->assertNotEmpty($pending['state']);
        $this->assertSame($mailbox->id, $pending['mailbox']);
        $this->assertStringContainsString('state='.$pending['state'], $response->headers->get('Location'));
        $this->assertStringContainsString('login_hint='.urlencode('negozio@gmail.com'), $response->headers->get('Location'));
    }

    public function test_redirect_without_client_config_explains_instead_of_failing(): void
    {
        config(['ticketing.mailbox.oauth.client_id' => null, 'ticketing.mailbox.oauth.client_secret' => null]);
        $mailbox = $this->connectMailbox();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('support.mailboxes.edit', $mailbox))->assertOk()->assertSee('GOOGLE_OAUTH_CLIENT_ID');
        $this->actingAs($admin)->get(route('support.mailboxes.google.redirect', $mailbox))->assertSessionHas('error');
        $this->assertNull(session(self::STATE_KEY));
    }

    public function test_redirect_for_a_missing_mailbox_is_not_found(): void
    {
        $this->actingAs($this->admin())->get(route('support.mailboxes.google.redirect', 999))->assertNotFound();
    }

    public function test_redirect_is_refused_for_an_imap_mailbox(): void
    {
        $mailbox = $this->mailboxRow([
            'address' => 'info@programmarti.it',
            'driver' => Mailbox::DRIVER_IMAP,
            'imap_host' => 'mail.programmarti.it',
            'smtp_host' => 'mail.programmarti.it',
            'username' => 'info@programmarti.it',
        ]);

        $this->actingAs($this->admin())->get(route('support.mailboxes.google.redirect', $mailbox))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull(session(self::STATE_KEY));
    }

    public function test_callback_rejects_wrong_state(): void
    {
        $this->connectMailbox();
        $this->fakeGoogleTokens();

        $this->visitCallback($this->callbackQuery(['state' => 'forged']))->assertSessionHas('error');

        $this->assertNothingSaved();
        Http::assertNothingSent();
    }

    public function test_callback_without_a_pending_state_is_rejected(): void
    {
        $this->connectMailbox();
        $this->fakeGoogleTokens();

        $this->visitCallback($this->callbackQuery(), sessionState: null)->assertSessionHas('error');

        $this->assertNothingSaved();
        Http::assertNothingSent();
    }

    public function test_callback_handles_access_denied(): void
    {
        $this->connectMailbox();
        $this->fakeGoogleTokens();

        $this->visitCallback(['error' => 'access_denied', 'state' => 'good-state'])->assertSessionHas('error');

        $this->assertNothingSaved();
        Http::assertNothingSent();
    }

    public function test_callback_rejects_another_account(): void
    {
        $this->connectMailbox();
        $this->fakeGoogleTokens('altro@gmail.com');

        $this->visitCallback($this->callbackQuery())->assertSessionHas('error');

        $this->assertNothingSaved();
    }

    public function test_callback_requires_a_refresh_token(): void
    {
        $this->connectMailbox();
        $this->fakeGoogleTokens(refreshToken: null);

        $this->visitCallback($this->callbackQuery())->assertSessionHas('error');

        $this->assertNothingSaved();
    }

    public function test_callback_saves_nothing_when_the_connection_test_fails(): void
    {
        $this->connectMailbox();
        $this->fakeGoogleTokens();
        $this->mailbox()->status = new ConnectionStatus(false, false, 'x');

        $this->visitCallback($this->callbackQuery())->assertSessionHas('error');

        $this->assertNothingSaved();
    }

    public function test_callback_connects_case_insensitively_and_resumes_sync(): void
    {
        $mailbox = $this->connectMailbox();
        $mailbox->forceFill(['disconnected_at' => now(), 'last_error' => 'AUTHENTICATIONFAILED'])->save();
        $this->fakeGoogleTokens('Negozio@Gmail.com');
        $admin = $this->admin();

        $this->actingAs($admin)->withSession([self::STATE_KEY => ['state' => 'good-state', 'mailbox' => $mailbox->id]])
            ->get(route('support.mailboxes.google.callback', $this->callbackQuery()))
            ->assertRedirect(route('support.mailboxes.edit', $mailbox))
            ->assertSessionHas('success');

        $mailbox->refresh();
        $this->assertSame('refresh-token-r1', $mailbox->oauth_refresh_token);
        $this->assertNotSame('refresh-token-r1', $mailbox->getRawOriginal('oauth_refresh_token'));
        $this->assertNotNull($mailbox->oauth_connected_at);
        $this->assertFalse($mailbox->isDisconnected());
        $this->assertSame('access-token-a1', $this->mailbox()->testedWith->accessToken);
        $this->assertSame($mailbox->id, $this->mailbox()->testedWith->mailbox->id);

        $this->actingAs($admin)->get(route('support.mailboxes.edit', $mailbox))
            ->assertOk()
            ->assertSee('Google OAuth')
            ->assertDontSee('refresh-token-r1')
            ->assertDontSee('access-token-a1');
    }

    public function test_page_offers_disconnect_even_when_google_is_not_configured_and_labels_last_check(): void
    {
        config(['ticketing.mailbox.oauth.client_id' => null, 'ticketing.mailbox.oauth.client_secret' => null]);
        $mailbox = $this->connectMailbox();
        $mailbox->forceFill(['oauth_refresh_token' => 'refresh-token-r1', 'oauth_connected_at' => now()])->save();

        $this->actingAs($this->admin())->get(route('support.mailboxes.edit', $mailbox))
            ->assertOk()
            ->assertSee('Scollega Google')
            ->assertSee('Ultima verifica')
            ->assertDontSee('Password verificata');
    }

    public function test_disconnecting_google_keeps_the_app_password(): void
    {
        $mailbox = $this->connectMailbox();
        $mailbox->forceFill(['oauth_refresh_token' => 'refresh-token-r1', 'oauth_connected_at' => now()])->save();

        $this->actingAs($this->admin())->delete(route('support.mailboxes.google.destroy', $mailbox))->assertSessionHas('success');

        $mailbox->refresh();
        $this->assertNull($mailbox->oauth_refresh_token);
        $this->assertNull($mailbox->oauth_connected_at);
        $this->assertSame('abcdabcdabcdabcd', $mailbox->password);
    }

    public function test_password_form_tests_the_typed_password_while_oauth_is_active(): void
    {
        $mailbox = $this->connectMailbox();
        $mailbox->forceFill(['oauth_refresh_token' => 'refresh-token-r1', 'oauth_connected_at' => now()])->save();

        $this->actingAs($this->admin())->put(route('support.mailboxes.update', $mailbox), $this->mailboxForm([
            'password' => 'wxyz wxyz wxyz wxyz',
        ]))->assertSessionHas('success');

        $tested = $this->mailbox()->testedWith;
        $this->assertFalse($tested->usesOAuth());
        $this->assertSame('wxyzwxyzwxyzwxyz', $tested->password());
        $this->assertSame('refresh-token-r1', $mailbox->fresh()->oauth_refresh_token);
    }

    public function test_changing_the_address_drops_the_oauth_link(): void
    {
        $mailbox = $this->connectMailbox();
        $mailbox->forceFill(['oauth_refresh_token' => 'refresh-token-r1', 'oauth_connected_at' => now()])->save();

        $this->actingAs($this->admin())->put(route('support.mailboxes.update', $mailbox), $this->mailboxForm([
            'address' => 'altra@gmail.com',
            'password' => 'wxyz wxyz wxyz wxyz',
        ]))->assertSessionHas('success');

        $this->assertSame(1, Mailbox::count());
        $mailbox->refresh();
        $this->assertNull($mailbox->oauth_refresh_token);
        $this->assertSame('altra@gmail.com', $mailbox->address);
    }
}
