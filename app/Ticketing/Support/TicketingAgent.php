<?php

namespace App\Ticketing\Support;

use App\Models\User;

/**
 * Single source of truth for "who works on tickets": policies, menu and queries all go through here.
 */
class TicketingAgent
{
    public static function forUser(User $user): ?User
    {
        return self::isEnabled($user) ? $user : null;
    }

    public static function isEnabled(User $user): bool
    {
        return $user->isTicketAgent();
    }

    /**
     * Admins see the whole section; employees only when enabled.
     */
    public static function canAccess(User $user): bool
    {
        return $user->is_active && ($user->isTicketingAdmin() || $user->can_manage_tickets);
    }
}
