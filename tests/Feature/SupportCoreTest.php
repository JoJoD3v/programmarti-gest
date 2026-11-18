<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\SupportTicketAssignment;
use App\Models\SupportTicketType;
use App\Models\User;
use App\Ticketing\Actions\TicketAssigner;
use App\Ticketing\Actions\TicketStateMachine;
use App\Ticketing\Enums\AssignmentReason;
use App\Ticketing\Enums\CloseReason;
use App\Ticketing\Enums\TicketStatus;
use App\Ticketing\Events\TicketClosed;
use App\Ticketing\Events\TicketReopened;
use App\Ticketing\Events\TicketReplied;
use App\Ticketing\Support\InvalidTicketTransition;
use App\Ticketing\Support\TicketingSettings;
use Database\Seeders\SupportTicketTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsSupportFixtures;
use Tests\TestCase;

class SupportCoreTest extends TestCase
{
    use BuildsSupportFixtures, RefreshDatabase;

    public function test_state_machine_valid_transitions_fire_events(): void
    {
        Event::fake([TicketReplied::class, TicketClosed::class, TicketReopened::class]);
        $machine = app(TicketStateMachine::class);
        $ticket = SupportTicket::factory()->create();

        $machine->reply($ticket, now());
        $this->assertSame(TicketStatus::Pending, $ticket->status);
        $this->assertNotNull($ticket->first_response_at);

        $machine->customerMessage($ticket, now());
        $this->assertSame(TicketStatus::Open, $ticket->status);

        $machine->close($ticket, CloseReason::Manual, User::factory()->create());
        $this->assertSame(TicketStatus::Closed, $ticket->status);
        $this->assertNotNull($ticket->closed_at);

        $machine->reopen($ticket, now());
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertSame(1, $ticket->reopened_count);

        Event::assertDispatched(TicketReplied::class);
        Event::assertDispatched(TicketClosed::class);
        Event::assertDispatched(TicketReopened::class);
    }

    public function test_state_machine_rejects_invalid_transitions(): void
    {
        $machine = app(TicketStateMachine::class);
        $closed = SupportTicket::factory()->closed()->create();
        $open = SupportTicket::factory()->create();

        foreach ([
            fn () => $machine->reply($closed, now()),
            fn () => $machine->customerMessage($closed, now()),
            fn () => $machine->close($closed, CloseReason::Manual),
            fn () => $machine->reopen($open, now()),
        ] as $transition) {
            try {
                $transition();
                $this->fail('Expected InvalidTicketTransition');
            } catch (InvalidTicketTransition) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_public_ids_are_unique_and_sequential_looking(): void
    {
        $ids = collect(range(1, 5))->map(fn () => SupportTicket::openNew(['customer_email' => 'a@b.it', 'status' => 'open'])->public_id);

        $this->assertCount(5, $ids->unique());
        $ids->each(fn ($id) => $this->assertMatchesRegularExpression('/^T-\d{4,}$/', $id));
    }

    public function test_type_seeder_is_idempotent_and_keeps_admin_edits(): void
    {
        $this->seedTypes();
        SupportTicketType::where('slug', 'modifiche')->update(['name' => 'Modifiche al sito', 'assignable' => false]);

        $this->seed(SupportTicketTypeSeeder::class);

        $this->assertSame(10, SupportTicketType::count());
        $modifiche = SupportTicketType::where('slug', 'modifiche')->first();
        $this->assertSame('Modifiche al sito', $modifiche->name);
        $this->assertFalse($modifiche->assignable);
    }

    public function test_policy_visibility(): void
    {
        $agent = $this->agent();
        $other = $this->agent();
        $plain = User::factory()->create();
        $admin = $this->admin();
        $mine = SupportTicket::factory()->assignedTo($agent)->create();
        $unassigned = SupportTicket::factory()->create();

        $this->assertTrue($agent->can('view', $mine));
        $this->assertFalse($other->can('view', $mine));
        $this->assertFalse($agent->can('view', $unassigned));
        $this->assertFalse($plain->can('viewAny', SupportTicket::class));
        $this->assertFalse($plain->can('view', $mine));
        $this->assertTrue($admin->can('view', $unassigned));
        $this->assertTrue($admin->can('reassign', SupportTicket::class));
        $this->assertFalse($agent->can('reassign', SupportTicket::class));
        $this->assertFalse(User::factory()->ticketAgent()->inactive()->create()->can('viewAny', SupportTicket::class));
    }

    public function test_settings_are_stored_cached_and_logged(): void
    {
        $settings = app(TicketingSettings::class);
        $admin = $this->admin();

        $this->assertFalse($settings->bool('drafts_enabled'));
        $settings->set('drafts_enabled', true, $admin);

        $this->assertTrue($settings->bool('drafts_enabled'), 'cache must be invalidated');
        $this->assertDatabaseHas('ticketing_setting_logs', ['key' => 'drafts_enabled', 'user_id' => $admin->id]);

        $this->get(route('support.settings.edit'))->assertRedirect(route('login'));
        $this->actingAs($this->agent())->get(route('support.settings.edit'))->assertForbidden();
        $this->actingAs($admin)->get(route('support.settings.edit'))->assertOk();
    }

    public function test_least_loaded_assignment_is_even_and_skips_disabled_and_unavailable_people(): void
    {
        $this->seedTypes();
        $agents = collect([$this->agent(), $this->agent(), $this->agent()]);
        $away = $this->agent(['ticket_available' => false]);
        $notEnabled = User::factory()->create();
        $assigner = app(TicketAssigner::class);

        SupportTicket::factory()->count(30)->create()->each(fn ($ticket) => $assigner->assignAutomatically($ticket));

        foreach ($agents as $agent) {
            $this->assertSame(10, SupportTicket::assignedTo($agent)->count());
        }
        $this->assertSame(0, SupportTicket::assignedTo($away)->count());
        $this->assertSame(0, SupportTicket::assignedTo($notEnabled)->count());
        $this->assertSame(30, SupportTicketAssignment::count());
    }

    public function test_non_assignable_types_are_not_assigned_but_unclassified_ones_are(): void
    {
        $this->seedTypes();
        $agent = $this->agent();
        $spam = SupportTicket::factory()->ofType(SupportTicketType::where('slug', 'spam')->first())->create();
        $unclassified = SupportTicket::factory()->create();
        $assigner = app(TicketAssigner::class);

        $this->assertNull($assigner->assignAutomatically($spam));
        $this->assertNull($spam->fresh()->assigned_user_id);
        $this->assertSame($agent->id, $assigner->assignAutomatically($unclassified)->id);
    }

    public function test_manual_reassignment_only_to_enabled_people_even_via_direct_request(): void
    {
        $admin = $this->admin();
        $from = $this->agent();
        $to = $this->agent();
        $plain = User::factory()->create();
        $ticket = SupportTicket::factory()->assignedTo($from)->create();

        $this->actingAs($admin)->patch(route('support-tickets.assign', $ticket), ['user_id' => $plain->id])->assertSessionHasErrors('user_id');
        $this->assertSame($from->id, $ticket->fresh()->assigned_user_id);

        $this->actingAs($admin)->patch(route('support-tickets.assign', $ticket), ['user_id' => $to->id, 'note' => 'ferie'])->assertSessionHas('success');
        $this->assertSame($to->id, $ticket->fresh()->assigned_user_id);
        $this->assertDatabaseHas('support_ticket_assignments', ['support_ticket_id' => $ticket->id, 'user_id' => $to->id, 'previous_user_id' => $from->id, 'assigned_by' => $admin->id, 'reason' => 'reassign']);

        $this->actingAs($from)->patch(route('support-tickets.assign', $ticket), ['user_id' => $from->id])->assertForbidden();
        $this->actingAs($from)->get(route('support.assignments.index'))->assertForbidden();
    }

    public function test_disabling_ticket_handling_moves_open_tickets_away(): void
    {
        $admin = User::factory()->amministratore()->create();
        $role = Role::findOrCreate('Developer');
        $leaving = $this->agent();
        $leaving->assignRole($role);
        $staying = $this->agent();
        $open = SupportTicket::factory()->count(3)->assignedTo($leaving)->create();
        $closed = SupportTicket::factory()->closed()->assignedTo($leaving)->create();

        $this->actingAs($admin)->put(route('users.update', $leaving), [
            'first_name' => $leaving->first_name, 'last_name' => $leaving->last_name, 'username' => $leaving->username,
            'email' => $leaving->email, 'role' => $role->name, 'can_manage_tickets' => 0, 'ticket_available' => 1,
        ])->assertRedirect();

        $this->assertSame(0, SupportTicket::assignedTo($leaving)->notClosed()->count());
        $this->assertSame(3, SupportTicket::assignedTo($staying)->count());
        $this->assertSame($leaving->id, $closed->fresh()->assigned_user_id, 'closed history stays untouched');
        $this->assertDatabaseHas('support_ticket_assignments', ['reason' => AssignmentReason::Disabled->value]);
    }
}
