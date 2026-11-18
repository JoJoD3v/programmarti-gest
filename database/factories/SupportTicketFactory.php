<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\SupportTicketType;
use App\Models\User;
use App\Ticketing\Enums\CloseReason;
use App\Ticketing\Enums\TicketStatus;
use App\Ticketing\Enums\TypeSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => 'tmp-'.Str::random(12),
            'status' => TicketStatus::Open,
            'customer_email' => fake()->unique()->safeEmail(),
            'customer_name' => fake()->name(),
            'subject' => fake()->sentence(4),
            'gmail_thread_id' => (string) fake()->unique()->numerify('################'),
            'last_inbound_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (SupportTicket $ticket) {
            if (str_starts_with($ticket->public_id, 'tmp-')) {
                $ticket->forceFill(['public_id' => 'T-'.(1000 + $ticket->id)])->save();
            }
        });
    }

    public function ofType(SupportTicketType $type, TypeSource $source = TypeSource::Ai): static
    {
        return $this->state(['support_ticket_type_id' => $type->id, 'type_source' => $source]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(['assigned_user_id' => $user->id, 'assigned_at' => now()]);
    }

    public function pending(): static
    {
        return $this->state(['status' => TicketStatus::Pending, 'last_outbound_at' => now(), 'first_response_at' => now()]);
    }

    public function closed(CloseReason $reason = CloseReason::Manual): static
    {
        return $this->state([
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
            'close_reason' => $reason,
            'last_outbound_at' => now(),
            'first_response_at' => now(),
        ]);
    }
}
