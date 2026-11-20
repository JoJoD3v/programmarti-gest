<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Ticketing\Enums\DeliveryStatus;
use App\Ticketing\Enums\MessageDirection;
use App\Ticketing\Enums\MessageType;
use App\Ticketing\Enums\TicketStatus;
use App\Ticketing\Jobs\SendTicketReplyJob;
use App\Ticketing\Support\SavedReplyVariables;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\BuildsSupportFixtures;
use Tests\TestCase;

class SupportTicketCreatePrintTest extends TestCase
{
    use BuildsSupportFixtures, RefreshDatabase;

    private function ticketWithInbound(User $agent, array $attributes = []): SupportTicket
    {
        $ticket = $this->ticketFor('cliente@example.com', $attributes + ['assigned_user_id' => $agent->id, 'assigned_at' => now(), 'subject' => 'Sito']);
        SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'direction' => MessageDirection::In,
            'type' => MessageType::Reply,
            'delivery_status' => DeliveryStatus::Received,
            'message_id_header' => '<in'.$ticket->id.'@cliente.example>',
            'from_email' => 'cliente@example.com',
            'subject' => 'Sito',
            'body_text' => 'Il sito è ancora offline?',
            'received_at' => now(),
        ]);

        return $ticket;
    }

    private function client(array $attributes = []): Client
    {
        return Client::create($attributes + ['first_name' => 'Mario', 'last_name' => 'Rossi', 'email' => 'mario@example.com']);
    }

    private function project(Client $client, array $attributes = []): Project
    {
        return Project::create($attributes + [
            'name' => 'Sito vetrina Rossi',
            'project_type' => 'website',
            'client_id' => $client->id,
            'start_date' => now()->subMonth(),
            'status' => 'in_progress',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function newTicketPayload(array $overrides = []): array
    {
        return $overrides + [
            'mailbox_id' => $this->defaultMailbox->id,
            'customer_email' => 'Mario@Example.com',
            'customer_name' => 'Mario Rossi',
            'subject' => 'Il suo preventivo',
            'body' => 'Buongiorno, le inviamo il preventivo.',
        ];
    }

    public function test_agent_opens_a_ticket_that_emails_the_customer(): void
    {
        $agent = $this->agent();

        $response = $this->actingAs($agent)->post(route('support-tickets.store'), $this->newTicketPayload());

        $ticket = SupportTicket::query()->sole();
        $response->assertRedirect(route('support-tickets.show', $ticket));

        $this->assertSame('mario@example.com', $ticket->customer_email);
        $this->assertSame($agent->id, $ticket->assigned_user_id);
        $this->assertSame(TicketStatus::Pending, $ticket->status);
        $this->assertSame($this->defaultMailbox->id, $ticket->mailbox_id);
        $this->assertNull($ticket->client_id);

        $sent = $this->mailbox()->lastSent();
        $this->assertSame('mario@example.com', $sent->to);
        $this->assertSame('Il suo preventivo', $sent->subject);
    }

    public function test_new_ticket_is_sent_from_the_chosen_mailbox_and_linked_to_the_client(): void
    {
        $agent = $this->agent();
        $client = $this->client();
        $other = $this->mailboxRow(['name' => 'Commerciale', 'address' => 'commerciale@programmarti.it']);

        $this->actingAs($agent)->post(route('support-tickets.store'), $this->newTicketPayload(['mailbox_id' => $other->id]))
            ->assertRedirect();

        $ticket = SupportTicket::query()->sole();
        $this->assertSame($other->id, $ticket->mailbox_id);
        $this->assertSame($client->id, $ticket->client_id, 'the address matches a client case-insensitively');
        $this->assertSame([$other->id], $this->mailbox()->sentFrom);
        $this->assertSame('commerciale@programmarti.it', $ticket->messages()->sole()->from_email);
    }

    public function test_new_ticket_is_validated(): void
    {
        $this->actingAs($this->agent())->post(route('support-tickets.store'), ['customer_email' => 'non-una-email', 'subject' => '', 'body' => ''])
            ->assertSessionHasErrors(['mailbox_id', 'customer_email', 'subject', 'body']);

        $this->assertSame(0, SupportTicket::query()->count());
    }

    public function test_new_ticket_requires_an_active_mailbox(): void
    {
        $this->defaultMailbox->update(['active' => false]);

        $this->actingAs($this->agent())
            ->get(route('support-tickets.create'))
            ->assertOk()
            ->assertSee('Nessuna casella attiva');

        $this->post(route('support-tickets.store'), $this->newTicketPayload())
            ->assertSessionHasErrors('mailbox_id');
        $this->post(route('support-tickets.store'), $this->newTicketPayload(['mailbox_id' => 999]))
            ->assertSessionHasErrors('mailbox_id');

        $this->assertSame(0, SupportTicket::query()->count());
        $this->assertSame([], $this->mailbox()->sent);
    }

    public function test_create_page_prefills_the_client(): void
    {
        $client = $this->client(['email' => 'giulia@example.com', 'first_name' => 'Giulia', 'last_name' => 'Bianchi']);

        $this->actingAs($this->agent())
            ->get(route('support-tickets.create', ['client' => $client->id]))
            ->assertOk()
            ->assertSee('giulia@example.com')
            ->assertSee('Giulia Bianchi')
            ->assertSee('assistenza@gmail.com');
    }

    public function test_users_without_ticketing_cannot_open_tickets(): void
    {
        $this->actingAs(User::factory()->create())->get(route('support-tickets.create'))->assertForbidden();
        $this->actingAs(User::factory()->create())->post(route('support-tickets.store'), $this->newTicketPayload([
            'customer_email' => 'a@example.com', 'subject' => 'x', 'body' => 'y',
        ]))->assertForbidden();
    }

    public function test_reply_and_close_closes_immediately_even_before_delivery(): void
    {
        Queue::fake();
        $agent = $this->agent();
        $ticket = $this->ticketWithInbound($agent);

        $this->actingAs($agent)->post(route('support-tickets.reply', $ticket), ['body' => 'Risolto oggi', 'close' => 1])->assertRedirect();

        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
        $this->assertSame($agent->id, $ticket->fresh()->closed_by);
        Queue::assertPushed(SendTicketReplyJob::class);
    }

    public function test_delivery_on_a_closed_ticket_records_response_times_and_keeps_it_closed(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticketWithInbound($agent);

        $this->actingAs($agent)->post(route('support-tickets.reply', $ticket), ['body' => 'Risolto oggi', 'close' => 1]);

        $ticket->refresh();
        $this->assertSame(TicketStatus::Closed, $ticket->status);
        $this->assertNotNull($ticket->first_response_at);
        $this->assertNotNull($ticket->last_outbound_at);
        $this->assertNotNull($this->mailbox()->lastSent());
        $this->assertSame([$this->defaultMailbox->id], $this->mailbox()->sentFrom);
    }

    public function test_index_defaults_to_not_closed_tickets(): void
    {
        $admin = $this->admin();
        $agent = $this->agent();
        $open = SupportTicket::factory()->assignedTo($agent)->create(['status' => TicketStatus::Open]);
        $pending = SupportTicket::factory()->assignedTo($agent)->create(['status' => TicketStatus::Pending]);
        $closed = SupportTicket::factory()->assignedTo($agent)->create(['status' => TicketStatus::Closed]);

        $this->actingAs($admin)->get(route('support-tickets.index'))
            ->assertSee($open->public_id)->assertSee($pending->public_id)->assertDontSee($closed->public_id);

        $this->actingAs($admin)->get(route('support-tickets.index', ['tab' => 'closed']))
            ->assertSee($closed->public_id)->assertDontSee($open->public_id);
    }

    public function test_print_shows_the_conversation_and_is_authorized(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticketWithInbound($agent);

        $this->actingAs($agent)->get(route('support-tickets.print', $ticket))
            ->assertOk()->assertSee($ticket->public_id)->assertSee('Il sito è ancora offline?');

        $this->actingAs($this->agent())->get(route('support-tickets.print', $ticket))->assertForbidden();
    }

    public function test_client_can_be_linked_and_unlinked_by_who_can_view_the_ticket(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticketWithInbound($agent);
        $client = $this->client();

        $this->actingAs($this->agent())
            ->patch(route('support-tickets.client', $ticket), ['client_id' => $client->id])
            ->assertForbidden();
        $this->assertNull($ticket->fresh()->client_id);

        $this->actingAs($agent)
            ->patch(route('support-tickets.client', $ticket), ['client_id' => 999])
            ->assertSessionHasErrors('client_id');

        $this->patch(route('support-tickets.client', $ticket), ['client_id' => $client->id])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame($client->id, $ticket->fresh()->client_id);

        $this->patch(route('support-tickets.client', $ticket), ['client_id' => ''])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertNull($ticket->fresh()->client_id);

        $this->actingAs($this->admin())
            ->patch(route('support-tickets.client', $ticket), ['client_id' => $client->id])
            ->assertRedirect();
        $this->assertSame($client->id, $ticket->fresh()->client_id);
    }

    public function test_show_page_lists_the_projects_of_the_linked_client(): void
    {
        $agent = $this->agent();
        $client = $this->client();
        $this->project($client);
        $this->project($client, ['name' => 'Campagna social', 'project_type' => 'marketing_campaign', 'status' => 'completed']);
        $ticket = $this->ticketWithInbound($agent, ['client_id' => $client->id]);

        $this->actingAs($agent)->get(route('support-tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Mario Rossi')
            ->assertSee('Sito vetrina Rossi')
            ->assertSee('Campagna social')
            ->assertDontSee('Il mittente non corrisponde a nessun cliente in anagrafica.');

        $unlinked = $this->ticketWithInbound($agent);
        $this->get(route('support-tickets.show', $unlinked))
            ->assertOk()
            ->assertSee('Il mittente non corrisponde a nessun cliente in anagrafica.')
            ->assertDontSee('Sito vetrina Rossi');
    }

    public function test_project_variable_resolves_to_the_only_project_in_progress(): void
    {
        $agent = $this->agent();
        $client = $this->client();
        $this->project($client);
        $this->project($client, ['name' => 'Vecchio gestionale', 'project_type' => 'management_system', 'status' => 'completed']);
        $ticket = $this->ticketWithInbound($agent, ['client_id' => $client->id]);
        $variables = new SavedReplyVariables;

        $this->assertSame('Novità su Sito vetrina Rossi', $variables->replace('Novità su {progetto}', $ticket, $agent));

        $this->project($client, ['name' => 'Ecommerce Rossi', 'project_type' => 'ecommerce']);
        $this->assertSame('Novità su [[DA COMPLETARE: progetto]]', $variables->replace('Novità su {progetto}', $ticket->fresh(), $agent), 'two projects in progress: ambiguous');

        $unlinked = $this->ticketWithInbound($agent);
        $this->assertSame('Novità su [[DA COMPLETARE: progetto]]', $variables->replace('Novità su {progetto}', $unlinked, $agent));
    }
}
