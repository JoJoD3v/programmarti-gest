<?php

namespace App\Ticketing\Events;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Foundation\Events\Dispatchable;

class CustomerMessageReceived
{
    use Dispatchable;

    public function __construct(public SupportTicket $ticket, public SupportTicketMessage $message) {}
}
