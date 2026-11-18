<?php

namespace App\Console\Commands;

use App\Models\SupportTicket;
use App\Ticketing\Actions\TicketAssigner;
use App\Ticketing\Jobs\ClassifyTicketJob;
use App\Ticketing\Support\InboundEmailFactory;
use App\Ticketing\Support\TicketNotifier;
use Illuminate\Console\Command;

/**
 * Safety net, every few minutes: re-classifies tickets left "da classificare" (LLM down), assigns the ones nobody
 * got (waiting for the AI, no employee available) and warns admins when nobody can take them.
 */
class RetryPendingTicketsCommand extends Command
{
    protected $signature = 'ticketing:retry-pending';

    protected $description = 'Riprova classificazione e assegnazione dei ticket rimasti indietro';

    public function handle(TicketAssigner $assigner): int
    {
        $retried = $assigned = 0;

        SupportTicket::query()
            ->whereNull('support_ticket_type_id')
            ->where('created_at', '>=', now()->subHours(48))
            ->where(fn ($query) => $query->whereNull('type_source')->orWhere('type_source', 'fallback'))
            ->each(function (SupportTicket $ticket) use (&$retried) {
                $message = $ticket->messages()->oldest('id')->first();

                if ($message !== null) {
                    ClassifyTicketJob::dispatch($ticket->id, InboundEmailFactory::fromMessage($message));
                    $retried++;
                }
            });

        $stuck = 0;

        SupportTicket::query()->unassigned()->notClosed()->with('type')->each(function (SupportTicket $ticket) use ($assigner, &$assigned, &$stuck) {
            $waitingForAi = $ticket->type === null && $ticket->created_at->gt(now()->subMinutes((int) config('ticketing.unclassified_assign_after_minutes')));

            if ($waitingForAi || ! $assigner->isAssignable($ticket)) {
                return;
            }

            if ($assigner->assignAutomatically($ticket) !== null) {
                $assigned++;
            } elseif ($ticket->created_at->lt(now()->subMinutes((int) config('ticketing.alerts.unassigned_minutes')))) {
                $stuck++;
            }
        });

        if ($stuck > 0) {
            TicketNotifier::admins(
                'unassigned',
                "{$stuck} ticket da assegnare ma nessun dipendente è abilitato e disponibile.",
                route('support.assignments.index'),
                onceKey: 'unassigned-tickets',
            );
        } else {
            TicketNotifier::clearAlert('unassigned-tickets');
        }

        $this->info("Riclassificazioni: {$retried}, assegnazioni: {$assigned}, bloccati: {$stuck}");

        return self::SUCCESS;
    }
}
