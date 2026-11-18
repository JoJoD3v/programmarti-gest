<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;
use App\Ticketing\Support\TicketingAgent;

/**
 * Admins see and manage every ticket; enabled employees only the tickets assigned to them; nobody else gets in.
 */
class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return TicketingAgent::canAccess($user);
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isTicketingAdmin() || ($user->isTicketAgent() && $ticket->assigned_user_id === $user->id);
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function close(User $user, SupportTicket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function changeType(User $user, SupportTicket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function reassign(User $user): bool
    {
        return $user->is_active && $user->isTicketingAdmin();
    }

    public function manage(User $user): bool
    {
        return $user->is_active && $user->isTicketingAdmin();
    }
}
