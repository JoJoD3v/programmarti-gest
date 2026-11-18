<?php

namespace App\Ticketing\Mailbox\Imap;

use RuntimeException;

class ImapException extends RuntimeException
{
    /** Server refused the credentials: never retry, Google may lock the account after repeated failures. */
    public bool $authenticationFailed = false;

    public static function authentication(string $message): self
    {
        $exception = new self($message);
        $exception->authenticationFailed = true;

        return $exception;
    }
}
