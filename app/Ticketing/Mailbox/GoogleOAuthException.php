<?php

namespace App\Ticketing\Mailbox;

use RuntimeException;

class GoogleOAuthException extends RuntimeException
{
    /** Google refused the refresh token (invalid_grant): the link is gone, never retry until reconnected. */
    public bool $revoked = false;

    public static function revoked(string $message): self
    {
        $exception = new self($message);
        $exception->revoked = true;

        return $exception;
    }
}
