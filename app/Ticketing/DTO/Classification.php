<?php

namespace App\Ticketing\DTO;

final readonly class Classification
{
    public function __construct(
        public ?string $ticketTypeSlug,
        public float $confidence,
        public string $method, // rule|llm|fallback
        public ?string $language = null,
        public ?string $summary = null,
    ) {}
}
