<?php

namespace App\Ticketing\Events;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class TicketAssigned
{
    use Dispatchable;

    public function __construct(
        public SupportTicket $ticket,
        public ?User $previousAssignee = null,
        public ?User $assignedBy = null,
    ) {}
}
