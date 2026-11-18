<?php

namespace Tests\Feature;

use App\Ticketing\Mailbox\GoogleOAuth;
use App\Ticketing\Mailbox\GoogleOAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleOAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ticketing.mailbox.oauth.client_id' => 'cid',
            'ticketing.mailbox.oauth.client_secret' => 'sec',
        ]);
        Cache::flush();
    }

    public function test_configured_requires_client_id_and_secret(): void
    {
        $this->assertTrue(app(GoogleOAuth::class)->configured());

        config(['ticketing.mailbox.oauth.client_secret' => '']);

        $this->assertFalse(app(GoogleOAuth::class)->configured());
    }

    public function test_authorization_url_carries_the_required_parameters(): void
    {
        $url = app(GoogleOAuth::class)->authorizationUrl('st4te', 'negozio@gmail.com');

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);
        $this->assertSame('offline', $query['access_type']);
        $this->assertSame('consent', $query['prompt']);
        $this->assertSame('https://mail.google.com/ openid email', $query['scope']);
        $this->assertSame('negozio@gmail.com', $query['login_hint']);
        $this->assertSame('st4te', $query['state']);
        $this->assertSame('cid', $query['client_id']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame(route('support.mailboxes.google.callback'), $query['redirect_uri']);
    }

    public function test_exchange_code_reads_tokens_and_email(): void
    {
        $payload = rtrim(strtr(base64_encode(json_encode(['email' => 'negozio@gmail.com'])), '+/', '-_'), '=');

        Http::fake(['oauth2.googleapis.com/token' => Http::response([
            'refresh_token' => 'r1',
            'access_token' => 'a1',
            'expires_in' => 3599,
            'id_token' => 'hdr.'.$payload.'.sig',
        ])]);

        $result = app(GoogleOAuth::class)->exchangeCode('the-code');

        $this->assertSame([
            'refresh_token' => 'r1',
            'access_token' => 'a1',
            'expires_in' => 3599,
            'email' => 'negozio@gmail.com',
        ], $result);

        Http::assertSent(fn ($request) => $request['grant_type'] === 'authorization_code'
            && $request['code'] === 'the-code'
            && $request['redirect_uri'] === route('support.mailboxes.google.callback'));
    }

    public function test_access_token_is_cached_per_refresh_token(): void
    {
        Http::fakeSequence('oauth2.googleapis.com/token')
            ->push(['access_token' => 'a1', 'expires_in' => 3599])
            ->push(['access_token' => 'a2', 'expires_in' => 3599]);

        $oauth = app(GoogleOAuth::class);

        $this->assertSame('a1', $oauth->accessToken('r1'));
        $this->assertSame('a1', $oauth->accessToken('r1'));
        Http::assertSentCount(1);

        $this->assertSame('a2', $oauth->accessToken('r2'));
        Http::assertSentCount(2);
    }

    public function test_cached_access_token_is_not_stored_in_clear(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'secret-access', 'expires_in' => 3599])]);

        app(GoogleOAuth::class)->accessToken('r1');

        $stored = Cache::get('ticketing:google-access-token:'.sha1('r1'));

        $this->assertNotNull($stored);
        $this->assertStringNotContainsString('secret-access', $stored);
    }

    public function test_invalid_grant_is_revoked(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);

        try {
            app(GoogleOAuth::class)->accessToken('r1');
            $this->fail('Expected GoogleOAuthException');
        } catch (GoogleOAuthException $e) {
            $this->assertTrue($e->revoked);
        }
    }

    public function test_server_errors_are_temporary(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response('boom', 503)]);

        try {
            app(GoogleOAuth::class)->accessToken('r1');
            $this->fail('Expected GoogleOAuthException');
        } catch (GoogleOAuthException $e) {
            $this->assertFalse($e->revoked);
        }
    }

    public function test_network_errors_are_temporary(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        try {
            app(GoogleOAuth::class)->accessToken('r1');
            $this->fail('Expected GoogleOAuthException');
        } catch (GoogleOAuthException $e) {
            $this->assertFalse($e->revoked);
        }
    }
}
