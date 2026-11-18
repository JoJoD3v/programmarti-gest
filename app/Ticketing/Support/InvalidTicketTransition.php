<?php

namespace App\Ticketing\Support;

use App\Ticketing\Enums\TicketStatus;
use DomainException;

class InvalidTicketTransition extends DomainException
{
    public static function between(TicketStatus $from, string $action): self
    {
        return new self("Transizione non valida: «{$action}» da stato «{$from->value}».");
    }
}
