<?php

namespace App\Ticketing\Mailbox;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class GoogleOAuth
{
    private const SCOPE = 'https://mail.google.com/ openid email';

    public function configured(): bool
    {
        return filled(config('ticketing.mailbox.oauth.client_id'))
            && filled(config('ticketing.mailbox.oauth.client_secret'));
    }

    public function authorizationUrl(string $state, string $loginHint): string
    {
        return config('ticketing.mailbox.oauth.auth_url').'?'.http_build_query([
            'client_id' => config('ticketing.mailbox.oauth.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'login_hint' => $loginHint,
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array{refresh_token: ?string, access_token: string, expires_in: int, email: ?string}
     */
    public function exchangeCode(string $code): array
    {
        $data = $this->requestTokens([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
        ]);

        return [
            'refresh_token' => $data['refresh_token'] ?? null,
            'access_token' => $data['access_token'],
            'expires_in' => (int) $data['expires_in'],
            'email' => $this->emailFromIdToken($data['id_token'] ?? null),
        ];
    }

    public function accessToken(string $refreshToken): string
    {
        $key = 'ticketing:google-access-token:'.sha1($refreshToken);

        $cached = Cache::get($key);
        if (is_string($cached)) {
            return Crypt::decryptString($cached);
        }

        $data = $this->requestTokens([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        $expiresIn = (int) $data['expires_in'];
        if ($expiresIn > 60) {
            Cache::put($key, Crypt::encryptString($data['access_token']), $expiresIn - 60);
        }

        return $data['access_token'];
    }

    private function redirectUri(): string
    {
        return route('support.mailboxes.google.callback');
    }

    /**
     * @param  array<string, string>  $params
     * @return array{access_token: string, expires_in: int|string, refresh_token?: string, id_token?: string}
     */
    private function requestTokens(array $params): array
    {
        try {
            $response = Http::asForm()
                ->timeout((int) config('ticketing.mailbox.oauth.timeout'))
                ->post(config('ticketing.mailbox.oauth.token_url'), $params + [
                    'client_id' => config('ticketing.mailbox.oauth.client_id'),
                    'client_secret' => config('ticketing.mailbox.oauth.client_secret'),
                ]);
        } catch (ConnectionException $e) {
            throw new GoogleOAuthException('Google OAuth non raggiungibile: '.$e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            throw $this->failure($response);
        }

        $data = $response->json();
        if (! is_array($data) || empty($data['access_token']) || ! isset($data['expires_in'])) {
            throw new GoogleOAuthException('Risposta di Google OAuth non valida.');
        }

        return $data;
    }

    private function failure(Response $response): GoogleOAuthException
    {
        $error = $response->status() === 400 || $response->status() === 401
            ? (string) $response->json('error', '')
            : '';

        if ($error === 'invalid_grant') {
            return GoogleOAuthException::revoked('Il collegamento con Google è stato revocato o è scaduto.');
        }

        return new GoogleOAuthException('Google OAuth ha risposto con errore HTTP '.$response->status().($error !== '' ? ' ('.$error.')' : '').'.');
    }

    private function emailFromIdToken(?string $idToken): ?string
    {
        $parts = explode('.', (string) $idToken);
        if (count($parts) < 2) {
            return null;
        }

        $json = base64_decode(strtr($parts[1], '-_', '+/'), true);
        $payload = $json === false ? null : json_decode($json, true);

        return is_array($payload) && is_string($payload['email'] ?? null) ? $payload['email'] : null;
    }
}
