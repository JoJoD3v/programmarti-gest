<?php

namespace App\Ticketing\Mailbox;

use App\Models\Mailbox;

/**
 * The credentials a client logs in with. For a Gmail mailbox with a Google refresh token stored, the account
 * authenticates with XOAUTH2; the app password stays as fallback.
 */
class MailboxAccount
{
    public function __construct(
        public readonly Mailbox $mailbox,
        public readonly ?string $accessToken = null,
    ) {}

    public function address(): string
    {
        return $this->mailbox->address;
    }

    public function username(): string
    {
        return $this->mailbox->username;
    }

    public function password(): string
    {
        return (string) $this->mailbox->password;
    }

    public function usesOAuth(): bool
    {
        return $this->accessToken !== null;
    }

    /**
     * Live credentials of a saved mailbox: a fresh Google access token when linked, a revoked link is dropped.
     */
    public static function for(Mailbox $mailbox): self
    {
        $accessToken = null;

        if ($mailbox->isGmail() && filled($mailbox->oauth_refresh_token) && app(GoogleOAuth::class)->configured()) {
            try {
                $accessToken = app(GoogleOAuth::class)->accessToken($mailbox->oauth_refresh_token);
            } catch (GoogleOAuthException $exception) {
                if (! $exception->revoked) {
                    throw $exception;
                }

                $mailbox->dropOAuth($exception->getMessage());
            }
        }

        return new self($mailbox, $accessToken);
    }
}
