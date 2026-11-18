<?php

namespace App\Ticketing\Jobs;

use App\Models\SupportTicket;
use App\Models\SupportTicketType;
use App\Models\SupportTicketTypeChange;
use App\Ticketing\Actions\TicketAssigner;
use App\Ticketing\Contracts\MessageClassifier;
use App\Ticketing\DTO\InboundEmail;
use App\Ticketing\Enums\TypeSource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs after the ticket exists: classification enriches it, it never decides whether it exists.
 */
class ClassifyTicketJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 90;

    /** @var list<int> */
    public array $backoff = [30, 180];

    public function __construct(public int $ticketId, public InboundEmail $email) {}

    public function handle(MessageClassifier $classifier, TicketAssigner $assigner): void
    {
        $ticket = SupportTicket::query()->with('type')->find($this->ticketId);

        if ($ticket === null) {
            return;
        }

        if ($ticket->type_source !== TypeSource::Manual) {
            $this->applyClassification($ticket, $classifier);
        }

        if ($ticket->assigned_user_id === null && ($ticket->type === null || $ticket->type->assignable)) {
            $assigner->assignAutomatically($ticket);
        }
    }

    private function applyClassification(SupportTicket $ticket, MessageClassifier $classifier): void
    {
        $types = SupportTicketType::query()->active()->orderBy('sort')->get();
        $result = $classifier->classify($this->email, $types);

        $type = $result->ticketTypeSlug ? $types->firstWhere('slug', $result->ticketTypeSlug) : null;
        $source = match ($result->method) {
            'rule' => TypeSource::Rule,
            'llm' => TypeSource::Ai,
            default => TypeSource::Fallback,
        };

        $changes = [
            'support_ticket_type_id' => $type?->id ?? $ticket->support_ticket_type_id,
            'type_source' => $type ? $source : TypeSource::Fallback,
            'ai_confidence' => $result->confidence,
            'ai_summary' => $result->summary,
            'language' => $result->language,
        ];

        $previousTypeId = $ticket->support_ticket_type_id;
        $ticket->forceFill($changes)->save();

        if ($type !== null && $type->id !== $previousTypeId) {
            SupportTicketTypeChange::create([
                'support_ticket_id' => $ticket->id,
                'from_type_id' => $previousTypeId,
                'to_type_id' => $type->id,
                'changed_by' => null,
            ]);
        }

        $ticket->load('type');
    }
}
