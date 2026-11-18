<?php

namespace App\Ticketing\DTO;

use Carbon\CarbonImmutable;

/**
 * Header summary of a message sitting in the Gmail trash.
 */
final readonly class TrashedEmail
{
    public function __construct(
        public string $gmailThreadId,
        public ?string $fromAddress,
        public ?string $fromName,
        public string $subject,
        public ?CarbonImmutable $date,
    ) {}
}
