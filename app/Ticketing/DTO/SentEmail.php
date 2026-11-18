<?php

namespace App\Ticketing\DTO;

final readonly class SentEmail
{
    public function __construct(public string $messageId) {}
}
