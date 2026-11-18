<?php

namespace App\Ticketing\DTO;

final readonly class ConnectionStatus
{
    public function __construct(
        public bool $imapOk,
        public bool $smtpOk,
        public ?string $error = null,
    ) {}

    public function ok(): bool
    {
        return $this->imapOk && $this->smtpOk;
    }
}
