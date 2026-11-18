<?php

namespace App\Console\Commands;

use App\Models\SupportTicket;
use App\Ticketing\Actions\TicketStateMachine;
use App\Ticketing\Enums\CloseReason;
use App\Ticketing\Enums\DeliveryStatus;
use App\Ticketing\Enums\MessageDirection;
use App\Ticketing\Enums\MessageType;
use App\Ticketing\Enums\TicketStatus;
use App\Ticketing\Support\WorkingDays;
use Illuminate\Console\Command;

class AutoCloseTicketsCommand extends Command
{
    protected $signature = 'ticketing:auto-close';

    protected $description = 'Chiude i ticket in attesa del cliente da N giorni lavorativi dall\'ultima risposta';

    public function handle(TicketStateMachine $machine): int
    {
        $limit = WorkingDays::subtract(now(), (int) config('ticketing.auto_close_days'));
        $closed = 0;

        SupportTicket::query()
            ->where('status', TicketStatus::Pending->value)
            ->where('last_outbound_at', '<=', $limit)
            ->chunkById(100, function ($tickets) use ($machine, &$closed) {
                foreach ($tickets as $ticket) {
                    // safety: the customer must never be the last one to have written
                    $last = $ticket->lastMessage();

                    if ($last === null
                        || $last->direction !== MessageDirection::Out
                        || $last->type !== MessageType::Reply
                        || $last->delivery_status !== DeliveryStatus::Sent) {
                        continue;
                    }

                    $machine->close($ticket, CloseReason::Auto);
                    $closed++;
                }
            });

        $this->info("Ticket chiusi automaticamente: {$closed}");

        return self::SUCCESS;
    }
}
