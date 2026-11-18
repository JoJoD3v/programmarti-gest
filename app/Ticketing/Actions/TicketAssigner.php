<?php

namespace App\Ticketing\Actions;

use App\Models\SupportTicket;
use App\Models\SupportTicketAssignment;
use App\Models\User;
use App\Ticketing\Enums\AssignmentReason;
use App\Ticketing\Events\TicketAssigned;
use App\Ticketing\Support\TicketingSettings;
use App\Ticketing\Support\TicketNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Least-loaded assignment among enabled and available employees, and manual reassignment among enabled ones.
 */
class TicketAssigner
{
    /**
     * Automatic assignment. Returns null when nobody can take the ticket (admins get alerted by the hourly retry).
     */
    public function assignAutomatically(SupportTicket $ticket): ?User
    {
        return DB::transaction(function () use ($ticket) {
            $fresh = SupportTicket::query()->lockForUpdate()->find($ticket->id);

            if ($fresh === null || $fresh->assigned_user_id !== null) {
                return $fresh?->loadMissing('assignee')->assignee;
            }

            if (! $this->isAssignable($fresh)) {
                return null;
            }

            $candidate = $this->pickByShare();

            if ($candidate === null) {
                return null;
            }

            $this->apply($fresh, $candidate, AssignmentReason::Auto, null);
            $ticket->setRawAttributes($fresh->getAttributes(), true);

            return $candidate;
        });
    }

    /**
     * Moves an assigned ticket to someone else chosen by load (the previous assignee is no longer available).
     */
    public function reassignAutomatically(SupportTicket $ticket, AssignmentReason $reason, ?User $by = null): ?User
    {
        return DB::transaction(function () use ($ticket, $reason, $by) {
            $target = $this->pickLeastLoaded(exclude: $ticket->assigned_user_id);

            if ($target === null) {
                return null;
            }

            $this->apply($ticket, $target, $reason, $by);

            return $target;
        });
    }

    public function reassign(SupportTicket $ticket, User $target, User $by, ?string $note = null, AssignmentReason $reason = AssignmentReason::Reassign): void
    {
        if (! $target->isTicketAgent()) {
            throw new InvalidArgumentException('Il destinatario non è abilitato alla gestione ticket.');
        }

        if ($ticket->assigned_user_id === $target->id) {
            throw new InvalidArgumentException('Il ticket è già assegnato a questa persona.');
        }

        DB::transaction(fn () => $this->apply($ticket, $target, $reason, $by, $note));
    }

    /**
     * Puts the open tickets of an employee back in circulation (disabled employee, long absence, bulk move).
     *
     * @return int tickets moved
     */
    public function redistribute(User $from, AssignmentReason $reason, ?User $by = null, ?User $to = null): int
    {
        $moved = 0;

        SupportTicket::query()->assignedTo($from)->notClosed()->orderBy('id')->each(function (SupportTicket $ticket) use ($from, $reason, $by, $to, &$moved) {
            DB::transaction(function () use ($ticket, $from, $reason, $by, $to, &$moved) {
                $target = $to ?? $this->pickLeastLoaded(exclude: $from->id);

                if ($target === null || ! $target->isTicketAgent() || $target->id === $from->id) {
                    return;
                }

                $this->apply($ticket, $target, $reason, $by);
                $moved++;
            });
        });

        return $moved;
    }

    public function isAssignable(SupportTicket $ticket): bool
    {
        // "da classificare" (no type) is assigned anyway: nobody waits for the AI
        return $ticket->type === null || $ticket->type->assignable;
    }

    /**
     * With shares set in "Impostazioni" the next ticket goes to whoever is furthest below their share of the
     * automatic assignments since the last change (max 30 days). Unavailable people are skipped and their share
     * spreads over the others; someone enabled later without a share counts as the average share.
     */
    public function pickByShare(): ?User
    {
        $shares = array_map('intval', (array) app(TicketingSettings::class)->get('assignment_shares'));

        if ($shares === []) {
            return $this->pickLeastLoaded();
        }

        $candidates = User::query()
            ->ticketingAvailable()
            ->withCount(['assignedTickets as open_tickets_count' => fn ($query) => $query->notClosed()])
            ->lockForUpdate()
            ->get();

        $defaultShare = array_sum($shares) / max(1, count(array_filter($shares)));
        $weights = $candidates->mapWithKeys(fn (User $user) => [$user->id => (float) ($shares[$user->id] ?? $defaultShare)])->filter();

        if ($weights->sum() <= 0) {
            return $this->pickLeastLoaded();
        }

        $received = SupportTicketAssignment::query()
            ->where('reason', AssignmentReason::Auto)
            ->where('created_at', '>=', $this->sharesSince())
            ->whereIn('user_id', $weights->keys())
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as total')
            ->pluck('total', 'user_id');

        $next = $received->sum() + 1;
        $total = $weights->sum();

        return $candidates
            ->filter(fn (User $user) => $weights->has($user->id))
            ->sortBy([
                fn (User $a, User $b) => $this->deficit($b, $weights, $total, $next, $received) <=> $this->deficit($a, $weights, $total, $next, $received),
                ['open_tickets_count', 'asc'],
                fn (User $a, User $b) => [$a->last_ticket_assigned_at !== null, $a->last_ticket_assigned_at, $a->id] <=> [$b->last_ticket_assigned_at !== null, $b->last_ticket_assigned_at, $b->id],
            ])
            ->first();
    }

    /**
     * @param  Collection<int, float>  $weights
     * @param  Collection<int, int>  $received
     */
    private function deficit(User $user, Collection $weights, float $total, int $next, Collection $received): float
    {
        return round($weights[$user->id] / $total * $next - (int) ($received[$user->id] ?? 0), 6);
    }

    private function sharesSince(): CarbonImmutable
    {
        $changed = app(TicketingSettings::class)->get('assignment_shares_since');
        $floor = CarbonImmutable::now()->subDays(30);

        return $changed ? max($floor, CarbonImmutable::parse($changed)) : $floor;
    }

    public function pickLeastLoaded(?int $exclude = null): ?User
    {
        return User::query()
            ->ticketingAvailable()
            ->when($exclude, fn ($query) => $query->where('id', '!=', $exclude))
            ->withCount(['assignedTickets as open_tickets_count' => fn ($query) => $query->notClosed()])
            ->orderBy('open_tickets_count')
            ->orderByRaw('last_ticket_assigned_at IS NOT NULL')
            ->orderBy('last_ticket_assigned_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    private function apply(SupportTicket $ticket, User $target, AssignmentReason $reason, ?User $by, ?string $note = null): void
    {
        $previous = $ticket->loadMissing('assignee')->assignee;

        $ticket->forceFill(['assigned_user_id' => $target->id, 'assigned_at' => now()])->save();
        $ticket->unsetRelation('assignee');
        $target->forceFill(['last_ticket_assigned_at' => now()])->save();

        SupportTicketAssignment::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $target->id,
            'previous_user_id' => $previous?->id,
            'assigned_by' => $by?->id,
            'reason' => $reason,
            'note' => $note,
        ]);

        TicketAssigned::dispatch($ticket, $previous, $by);

        TicketNotifier::user($target, 'assigned', "Ticket {$ticket->public_id} assegnato a te", $ticket);
        if ($previous !== null && $previous->id !== $by?->id) {
            TicketNotifier::user($previous, 'reassigned', "Ticket {$ticket->public_id} riassegnato ad altri", $ticket);
        }
    }
}
