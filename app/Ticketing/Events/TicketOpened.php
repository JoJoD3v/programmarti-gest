<?php

namespace App\Ticketing\Events;

use App\Models\SupportTicket;
use Illuminate\Foundation\Events\Dispatchable;

class TicketOpened
{
    use Dispatchable;

    public function __construct(public SupportTicket $ticket) {}
}
