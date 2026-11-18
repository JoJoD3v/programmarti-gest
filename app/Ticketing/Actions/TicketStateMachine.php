<?php

namespace App\Ticketing\Actions;

use App\Models\SupportTicket;
use App\Models\User;
use App\Ticketing\Enums\CloseReason;
use App\Ticketing\Enums\TicketStatus;
use App\Ticketing\Events\TicketClosed;
use App\Ticketing\Events\TicketOpened;
use App\Ticketing\Events\TicketReopened;
use App\Ticketing\Events\TicketReplied;
use App\Ticketing\Support\InvalidTicketTransition;
use Carbon\CarbonInterface;

/**
 * The only place that changes `support_tickets.status`.
 *
 * open ── reply ─▶ pending ── customer message ─▶ open ── close ─▶ closed ── reopen ─▶ open
 */
class TicketStateMachine
{
    public function opened(SupportTicket $ticket): void
    {
        TicketOpened::dispatch($ticket);
    }

    /**
     * A customer message arrived on a not closed ticket: pending goes back to open.
     */
    public function customerMessage(SupportTicket $ticket, CarbonInterface $at): void
    {
        if ($ticket->isClosed()) {
            throw InvalidTicketTransition::between($ticket->status, 'messaggio cliente');
        }

        $ticket->forceFill(['status' => TicketStatus::Open, 'last_inbound_at' => $at])->save();
    }

    /**
     * An employee reply went out: open (or pending) becomes pending.
     */
    public function reply(SupportTicket $ticket, CarbonInterface $at): void
    {
        if ($ticket->isClosed()) {
            throw InvalidTicketTransition::between($ticket->status, 'risposta');
        }

        $ticket->forceFill([
            'status' => TicketStatus::Pending,
            'last_outbound_at' => $at,
            'first_response_at' => $ticket->first_response_at ?? $at,
        ])->save();

        TicketReplied::dispatch($ticket);
    }

    public function close(SupportTicket $ticket, CloseReason $reason, ?User $by = null): void
    {
        if ($ticket->isClosed()) {
            throw InvalidTicketTransition::between($ticket->status, 'chiusura');
        }

        $ticket->forceFill([
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
            'closed_by' => $by?->id,
            'close_reason' => $reason,
        ])->save();

        TicketClosed::dispatch($ticket);
    }

    public function reopen(SupportTicket $ticket, CarbonInterface $at): void
    {
        if (! $ticket->isClosed()) {
            throw InvalidTicketTransition::between($ticket->status, 'riapertura');
        }

        $ticket->forceFill([
            'status' => TicketStatus::Open,
            'closed_at' => null,
            'closed_by' => null,
            'close_reason' => null,
            'last_inbound_at' => $at,
            'reopened_count' => $ticket->reopened_count + 1,
        ])->save();

        TicketReopened::dispatch($ticket);
    }
}
