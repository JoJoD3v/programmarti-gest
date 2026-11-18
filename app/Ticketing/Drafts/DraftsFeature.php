<?php

namespace App\Ticketing\Drafts;

use App\Models\User;
use App\Ticketing\Support\TicketingAgent;
use App\Ticketing\Support\TicketingSettings;

/**
 * One switch decides everything about AI drafts: UI, routes, jobs and the drafter itself check it here.
 */
class DraftsFeature
{
    public function __construct(private readonly TicketingSettings $settings) {}

    public function enabled(): bool
    {
        return $this->settings->bool('drafts_enabled');
    }

    public function pregenerateEnabled(): bool
    {
        return $this->enabled() && $this->settings->bool('drafts_pregenerate');
    }

    public function availableFor(?User $user): bool
    {
        if ($user === null || ! $this->enabled() || ! TicketingAgent::canAccess($user)) {
            return false;
        }

        return match ($this->settings->get('drafts_audience')) {
            'all' => true,
            'selected_users' => in_array($user->id, array_map('intval', (array) $this->settings->get('drafts_selected_user_ids')), true),
            default => $user->isTicketingAdmin(),
        };
    }
}
