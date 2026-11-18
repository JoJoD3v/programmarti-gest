<?php

namespace App\Ticketing\Support;

use App\Models\SupportTicket;
use App\Models\User;
use App\Ticketing\Notifications\TicketingNotification;
use Illuminate\Support\Facades\Cache;

class TicketNotifier
{
    public static function user(?User $user, string $kind, string $message, ?SupportTicket $ticket = null): void
    {
        if ($user === null || ! $user->is_active) {
            return;
        }

        $user->notify(new TicketingNotification($kind, $message, $ticket ? route('support-tickets.show', $ticket) : null));
    }

    /**
     * Alert every active admin. With $onceKey the alert is sent a single time until clearAlert() is called
     * (a revoked app password must not flood the inbox every minute).
     */
    public static function admins(string $kind, string $message, ?string $url = null, ?string $onceKey = null): void
    {
        if ($onceKey !== null && ! Cache::add("ticketing.alert.{$onceKey}", true, now()->addDays(7))) {
            return;
        }

        User::query()->active()->get()
            ->filter(fn (User $user) => $user->isTicketingAdmin())
            ->each(fn (User $admin) => $admin->notify(new TicketingNotification($kind, $message, $url)));
    }

    public static function clearAlert(string $onceKey): void
    {
        Cache::forget("ticketing.alert.{$onceKey}");
    }
}
