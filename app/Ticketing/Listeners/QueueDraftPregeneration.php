<?php

namespace App\Ticketing\Listeners;

use App\Ticketing\Drafts\DraftsFeature;
use App\Ticketing\Events\CustomerMessageReceived;
use App\Ticketing\Jobs\PregenerateDraftJob;

class QueueDraftPregeneration
{
    public function __construct(private readonly DraftsFeature $feature) {}

    public function handle(CustomerMessageReceived $event): void
    {
        if ($this->feature->pregenerateEnabled() && $event->ticket->client_id !== null) {
            PregenerateDraftJob::dispatch($event->ticket->id);
        }
    }
}
