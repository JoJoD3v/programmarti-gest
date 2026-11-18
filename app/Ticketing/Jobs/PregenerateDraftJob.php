<?php

namespace App\Ticketing\Jobs;

use App\Models\SupportTicket;
use App\Ticketing\Actions\GenerateReplyDraft;
use App\Ticketing\Drafts\DraftsFeature;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Background draft for tickets of a known client. Rechecks the switch when it runs: a job queued before the
 * feature was turned off ends without calling the API.
 */
class PregenerateDraftJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 90;

    public function __construct(public int $ticketId) {}

    public function handle(DraftsFeature $feature, GenerateReplyDraft $generate): void
    {
        if (! $feature->pregenerateEnabled()) {
            return;
        }

        $ticket = SupportTicket::query()->with('assignee')->find($this->ticketId);

        if ($ticket === null || $ticket->assignee === null || $ticket->client_id === null) {
            return;
        }

        $based = $ticket->lastInboundMessage();

        if ($based === null || $ticket->aiDrafts()->where('based_on_message_id', $based->id)->exists()) {
            return;
        }

        try {
            $generate->handle($ticket, $ticket->assignee, null, pregenerated: true);
        } catch (\Throwable $exception) {
            Log::info('Ticketing: bozza in background non generata', ['ticket_id' => $ticket->id, 'error' => $exception->getMessage()]);
        }
    }
}
