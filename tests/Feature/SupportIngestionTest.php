<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Mailbox;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Ticketing\Actions\ProcessInboundMessage;
use App\Ticketing\Actions\TicketStateMachine;
use App\Ticketing\DTO\Classification;
use App\Ticketing\Enums\CloseReason;
use App\Ticketing\Enums\MessageType;
use App\Ticketing\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSupportFixtures;
use Tests\TestCase;

class SupportIngestionTest extends TestCase
{
    use BuildsSupportFixtures, RefreshDatabase;

    private function process($email): string
    {
        return app(ProcessInboundMessage::class)->handle($email);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTypes();
    }

    public function test_every_customer_email_becomes_a_ticket_labelled_and_assigned(): void
    {
        $agent = $this->agent();
        $email = $this->email();

        $this->assertSame(ProcessInboundMessage::CREATED, $this->process($email));

        $ticket = SupportTicket::sole();
        $this->assertSame('cliente@example.com', $ticket->customer_email);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertMatchesRegularExpression('/^T-\d+$/', $ticket->public_id);
        $this->assertSame($agent->id, $ticket->assigned_user_id);
        $this->assertSame(1, $ticket->messages()->count());
        $this->assertContains('gestionale/importata', $this->mailbox()->labelsOf($email->gmailMessageId));
        $this->assertNotNull($ticket->support_ticket_type_id, 'classification must leave a type or "da classificare"');
    }

    public function test_newsletters_become_unassigned_tickets(): void
    {
        $this->agent();
        $this->classifier()->result = new Classification('notifica_automatica', 0.9, 'llm');

        $this->process($this->email(['fromEmail' => 'news@shop.example', 'headers' => ['list-id' => '<news.shop.example>', 'precedence' => 'bulk']]));

        $ticket = SupportTicket::sole();
        $this->assertSame('notifica_automatica', $ticket->type->slug);
        $this->assertNull($ticket->assigned_user_id);
    }

    public function test_processing_the_same_message_twice_stores_it_once(): void
    {
        $email = $this->email();

        $this->process($email);
        $this->assertSame(ProcessInboundMessage::DUPLICATE, $this->process($email));

        $this->assertSame(1, SupportTicketMessage::count());
        $this->assertSame(1, SupportTicket::count());
    }

    public function test_messages_sent_by_us_are_skipped(): void
    {
        $this->assertSame(ProcessInboundMessage::OWN, $this->process($this->email(['fromEmail' => $this->defaultMailbox->address])));
        $this->assertSame(0, SupportTicket::count());
    }

    public function test_messages_sent_from_any_of_our_mailboxes_are_skipped(): void
    {
        $other = $this->mailboxRow([
            'address' => 'commerciale@programmarti.it',
            'driver' => Mailbox::DRIVER_IMAP,
            'imap_host' => 'mail.programmarti.it',
            'smtp_host' => 'mail.programmarti.it',
            'username' => 'commerciale@programmarti.it',
        ]);

        $this->assertSame(ProcessInboundMessage::OWN, $this->process($this->email(['fromEmail' => $other->address])));
        $this->assertSame(0, SupportTicket::count());
    }

    public function test_a_known_client_sender_links_the_ticket_to_the_client(): void
    {
        $client = Client::create(['first_name' => 'Mario', 'last_name' => 'Rossi', 'email' => 'Mario.Rossi@Example.com']);

        $this->process($this->email(['fromEmail' => 'mario.rossi@example.com']));
        $this->process($this->email(['fromEmail' => 'sconosciuto@example.com']));

        $this->assertSame($client->id, SupportTicket::where('customer_email', 'mario.rossi@example.com')->sole()->client_id);
        $this->assertNull(SupportTicket::where('customer_email', 'sconosciuto@example.com')->sole()->client_id);
    }

    public function test_bounces_outside_a_thread_are_discarded_with_a_label(): void
    {
        $email = $this->email(['fromEmail' => 'mailer-daemon@googlemail.com']);

        $this->assertSame(ProcessInboundMessage::DISCARDED, $this->process($email));
        $this->assertSame(0, SupportTicket::count());
        $this->assertContains('gestionale/scartata', $this->mailbox()->labelsOf($email->gmailMessageId));
    }

    public function test_auto_reply_outside_a_thread_is_discarded_but_inside_a_thread_it_is_recorded_without_changing_state(): void
    {
        $this->assertSame(ProcessInboundMessage::DISCARDED, $this->process($this->email(['headers' => ['auto-submitted' => 'auto-replied']])));

        $this->process($first = $this->email());
        $ticket = SupportTicket::sole();
        $ticket->forceFill(['status' => TicketStatus::Pending])->save();

        $this->process($this->email(['gmailThreadId' => $first->gmailThreadId, 'headers' => ['auto-submitted' => 'auto-replied']]));

        $this->assertSame(1, SupportTicket::count());
        $this->assertSame(TicketStatus::Pending, $ticket->fresh()->status);
        $this->assertSame(MessageType::System, $ticket->messages()->latest('id')->first()->type);
    }

    public function test_customer_reply_in_the_same_gmail_thread_joins_the_ticket_and_reopens_pending(): void
    {
        $this->process($first = $this->email());
        $ticket = SupportTicket::sole();
        $ticket->forceFill(['status' => TicketStatus::Pending, 'last_outbound_at' => now()])->save();

        $this->assertSame(ProcessInboundMessage::ATTACHED, $this->process($this->email(['gmailThreadId' => $first->gmailThreadId])));

        $this->assertSame(1, SupportTicket::count());
        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
        $this->assertSame(2, $ticket->messages()->count());
    }

    public function test_reply_is_matched_by_in_reply_to_header(): void
    {
        $this->process($first = $this->email());

        $this->process($this->email(['gmailThreadId' => 'other-thread', 'inReplyTo' => $first->messageIdHeader]));

        $this->assertSame(1, SupportTicket::count());
    }

    public function test_reply_is_matched_by_subject_token_and_sender(): void
    {
        $this->process($this->email());
        $ticket = SupportTicket::sole();

        $this->process($this->email(['gmailThreadId' => 'x', 'subject' => "Re: Il sito non si apre [{$ticket->public_id}]"]));

        $this->assertSame(1, SupportTicket::count());
    }

    public function test_a_different_sender_never_joins_the_ticket_and_is_flagged_as_related(): void
    {
        $this->process($first = $this->email());
        $original = SupportTicket::sole();

        $this->process($this->email(['gmailThreadId' => $first->gmailThreadId, 'fromEmail' => 'altro@example.com']));

        $this->assertSame(2, SupportTicket::count());
        $second = SupportTicket::where('customer_email', 'altro@example.com')->sole();
        $this->assertSame($original->id, $second->related_ticket_id);
        $this->assertSame(1, $original->messages()->count());
    }

    public function test_closed_ticket_reopens_within_the_window_and_becomes_a_new_ticket_after_it(): void
    {
        $this->process($first = $this->email());
        $ticket = SupportTicket::sole();
        app(TicketStateMachine::class)->close($ticket, CloseReason::Manual);
        $ticket->forceFill(['closed_at' => now()->subDays(3)])->save();

        $this->assertSame(ProcessInboundMessage::REOPENED, $this->process($this->email(['gmailThreadId' => $first->gmailThreadId])));
        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
        $this->assertSame(1, $ticket->fresh()->reopened_count);

        app(TicketStateMachine::class)->close($ticket->fresh(), CloseReason::Manual);
        $ticket->forceFill(['closed_at' => now()->subDays(20)])->save();

        $this->assertSame(ProcessInboundMessage::CREATED, $this->process($this->email(['gmailThreadId' => $first->gmailThreadId])));
        $newest = SupportTicket::latest('id')->first();
        $this->assertNotSame($ticket->id, $newest->id);
        $this->assertSame($ticket->id, $newest->previous_ticket_id);
    }

    public function test_classifier_failure_still_creates_an_unclassified_assigned_ticket(): void
    {
        $agent = $this->agent();
        $this->classifier()->failWith = new \RuntimeException('LLM giù');

        try {
            $this->process($this->email());
        } catch (\RuntimeException) {
            // sync queue rethrows; the ticket must exist anyway
        }

        $ticket = SupportTicket::sole();
        $this->assertNull($ticket->support_ticket_type_id);
        $this->assertSame(1, $ticket->messages()->count());
    }
}
