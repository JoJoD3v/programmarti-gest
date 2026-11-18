<?php

namespace App\Ticketing\Events;

use App\Models\SupportTicket;
use Illuminate\Foundation\Events\Dispatchable;

class TicketReplied
{
    use Dispatchable;

    public function __construct(public SupportTicket $ticket) {}
}
