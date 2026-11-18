<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\User;
use App\Ticketing\Actions\TicketAssigner;
use App\Ticketing\Enums\AssignmentReason;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('reassign', SupportTicket::class);

        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 30;

        $agents = User::query()->ticketingEnabled()
            ->withCount([
                'assignedTickets as open_count' => fn ($q) => $q->where('status', 'open'),
                'assignedTickets as pending_count' => fn ($q) => $q->where('status', 'pending'),
                'assignedTickets as closed_count' => fn ($q) => $q->where('status', 'closed')->where('closed_at', '>=', now()->subDays($days)),
            ])
            ->orderBy('last_name')->get();

        return view('support.assignments.index', [
            'agents' => $agents,
            'days' => $days,
            'unassigned' => SupportTicket::query()->unassigned()->notClosed()->with('type')->oldest()->limit(100)->get(),
            // employees switched off with tickets still on them (should not happen, shown for safety)
            'orphans' => User::query()->where('can_manage_tickets', false)
                ->whereHas('assignedTickets', fn ($q) => $q->notClosed())
                ->withCount(['assignedTickets as open_count' => fn ($q) => $q->notClosed()])->get(),
        ]);
    }

    public function toggleAvailability(Request $request, User $user, TicketAssigner $assigner): RedirectResponse
    {
        Gate::authorize('reassign', SupportTicket::class);
        abort_unless($user->can_manage_tickets, 404);

        $user->update(['ticket_available' => ! $user->ticket_available]);

        if (! $user->ticket_available && $request->boolean('redistribute')) {
            $moved = $assigner->redistribute($user, AssignmentReason::Bulk, $request->user());

            return back()->with('success', "{$user->name} non è più disponibile: {$moved} ticket ridistribuiti.");
        }

        return back()->with('success', $user->ticket_available
            ? "{$user->name} è di nuovo disponibile."
            : "{$user->name} non riceverà nuovi ticket. I ticket già suoi restano assegnati.");
    }

    public function redistribute(Request $request, User $user, TicketAssigner $assigner): RedirectResponse
    {
        Gate::authorize('reassign', SupportTicket::class);

        $data = $request->validate([
            'to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('can_manage_tickets', true)->where('is_active', true)],
        ]);

        $target = isset($data['to']) ? User::findOrFail($data['to']) : null;

        $moved = $assigner->redistribute($user, AssignmentReason::Bulk, $request->user(), $target);

        return back()->with($moved > 0 ? 'success' : 'error', $moved > 0
            ? "{$moved} ticket di {$user->name} riassegnati."
            : 'Nessun ticket spostato (nessun destinatario disponibile o nessun ticket aperto).');
    }
}
