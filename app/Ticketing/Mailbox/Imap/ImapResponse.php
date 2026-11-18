<?php

namespace App\Ticketing\Mailbox\Imap;

final readonly class ImapResponse
{
    /**
     * @param  list<string>  $untagged  every untagged line, literals kept inline as "{N}\r\n<bytes>"
     */
    public function __construct(
        public string $status, // OK | NO | BAD
        public string $text,
        public array $untagged,
    ) {}

    public function ok(): bool
    {
        return $this->status === 'OK';
    }
}
