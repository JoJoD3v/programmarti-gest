<?php

namespace App\Ticketing\DTO;

use App\Models\SupportTicket;

final readonly class MatchResult
{
    public function __construct(
        public ?SupportTicket $ticket,
        public string $criterion,
        public ?SupportTicket $related = null,
    ) {}
}
