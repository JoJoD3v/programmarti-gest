<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\SupportSenderRule;
use App\Models\SupportTicket;
use App\Models\SupportTicketType;
use App\Ticketing\Classification\AnthropicClassifier;
use App\Ticketing\Classification\TieredMessageClassifier;
use App\Ticketing\DTO\Classification;
use App\Ticketing\Enums\TypeSource;
use App\Ticketing\Jobs\ClassifyTicketJob;
use App\Ticketing\Support\TicketingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\BuildsSupportFixtures;
use Tests\TestCase;

class SupportIntegrationsTest extends TestCase
{
    use BuildsSupportFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTypes();
        config(['ticketing.llm.api_key' => 'test-key']);
        RateLimiter::clear('ticketing-llm-classifier');
    }

    private function toolResponse(array $input): array
    {
        return ['content' => [['type' => 'tool_use', 'name' => 'classify_email', 'input' => $input]], 'usage' => ['input_tokens' => 300, 'output_tokens' => 40]];
    }

    private function tiered(): TieredMessageClassifier
    {
        return new TieredMessageClassifier(new AnthropicClassifier, app(TicketingSettings::class));
    }

    public function test_sender_rule_wins_without_any_http_call(): void
    {
        Http::fake();
        $type = SupportTicketType::where('slug', 'notifica_automatica')->first();
        SupportSenderRule::create(['pattern' => '@paypal.com', 'support_ticket_type_id' => $type->id, 'active' => true]);
        SupportSenderRule::create(['pattern' => 'off@x.it', 'support_ticket_type_id' => $type->id, 'active' => false]);

        $result = $this->tiered()->classify($this->email(['fromEmail' => 'service@paypal.com']), SupportTicketType::active()->get());
        $this->assertSame('notifica_automatica', $result->ticketTypeSlug);
        $this->assertSame('rule', $result->method);
        $this->assertSame(1.0, $result->confidence);
        Http::assertNothingSent();

        $inactive = $this->tiered()->classify($this->email(['fromEmail' => 'off@x.it']), SupportTicketType::active()->get());
        $this->assertNotSame('rule', $inactive->method);
    }

    public function test_llm_classification_with_minimized_payload(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response($this->toolResponse([
            'ticket_type_slug' => 'assistenza_tecnica', 'confidence' => 0.93, 'language' => 'it', 'summary' => 'Il sito mostra una pagina bianca',
        ]))]);

        $email = $this->email(['fromEmail' => 'mario.rossi@example.com', 'subject' => 'Sito offline', 'textBody' => "Il sito non si apre\n> citazione vecchia"]);
        $result = $this->tiered()->classify($email, SupportTicketType::active()->get());

        $this->assertSame('assistenza_tecnica', $result->ticketTypeSlug);
        $this->assertSame('llm', $result->method);
        $this->assertSame('Il sito mostra una pagina bianca', $result->summary);

        Http::assertSent(function ($request) {
            $payload = json_encode($request->data());

            return $request->hasHeader('x-api-key', 'test-key')
                && $request['tool_choice']['name'] === 'classify_email'
                && ! str_contains($payload, 'mario.rossi@example.com')
                && str_contains($payload, 'example.com')
                && ! str_contains($payload, 'citazione vecchia')
                && ! str_contains($payload, 'order_numbers')
                && in_array('spam', $request['tools'][0]['input_schema']['properties']['ticket_type_slug']['enum'], true);
        });
    }

    public function test_known_client_sender_is_hinted_to_the_llm(): void
    {
        Client::create(['first_name' => 'Mario', 'last_name' => 'Rossi', 'email' => 'Mario.Rossi@Example.com']);
        Http::fake(['api.anthropic.com/*' => Http::response($this->toolResponse(['ticket_type_slug' => 'modifiche', 'confidence' => 0.8, 'language' => 'it', 'summary' => 'x']))]);
        $hint = "Il mittente è un cliente dell'agenzia.";

        $this->tiered()->classify($this->email(['fromEmail' => 'mario.rossi@example.com']), SupportTicketType::active()->get());
        $this->tiered()->classify($this->email(['fromEmail' => 'sconosciuto@example.com']), SupportTicketType::active()->get());

        $requests = Http::recorded();
        $this->assertCount(2, $requests);
        $this->assertStringContainsString($hint, $requests[0][0]['messages'][0]['content']);
        $this->assertStringNotContainsString($hint, $requests[1][0]['messages'][0]['content']);
    }

    public function test_tool_schema_excludes_inactive_types(): void
    {
        SupportTicketType::where('slug', 'spam')->update(['active' => false]);
        Http::fake(['api.anthropic.com/*' => Http::response($this->toolResponse(['ticket_type_slug' => 'altro', 'confidence' => 0.5, 'language' => 'it', 'summary' => 'x']))]);

        $this->tiered()->classify($this->email(), SupportTicketType::active()->get());

        Http::assertSent(fn ($request) => ! in_array('spam', $request['tools'][0]['input_schema']['properties']['ticket_type_slug']['enum'], true));
    }

    public function test_invalid_slug_becomes_altro_with_zero_confidence(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response($this->toolResponse(['ticket_type_slug' => 'inventata', 'confidence' => 0.99, 'language' => 'it', 'summary' => 'x']))]);

        $result = $this->tiered()->classify($this->email(), SupportTicketType::active()->get());

        $this->assertSame('altro', $result->ticketTypeSlug);
        $this->assertSame(0.0, $result->confidence);
    }

    public function test_llm_failures_fall_back_to_an_unclassified_result(): void
    {
        foreach ([Http::response([], 500), Http::response([], 429), Http::response(['content' => []], 200)] as $failure) {
            Http::fake(['api.anthropic.com/*' => $failure]);
            RateLimiter::clear('ticketing-llm-classifier');

            $result = $this->tiered()->classify($this->email(), SupportTicketType::active()->get());

            $this->assertNull($result->ticketTypeSlug);
            $this->assertSame('fallback', $result->method);
        }
    }

    public function test_manual_type_is_never_overwritten_by_a_reclassification(): void
    {
        $manual = SupportTicketType::where('slug', 'modifiche')->first();
        $ticket = SupportTicket::factory()->ofType($manual, TypeSource::Manual)->create();
        $this->classifier()->result = new Classification('spam', 0.9, 'llm');

        ClassifyTicketJob::dispatchSync($ticket->id, $this->email());

        $this->assertSame($manual->id, $ticket->fresh()->support_ticket_type_id);
    }

    public function test_classification_job_records_type_change_and_assigns(): void
    {
        $this->agent();
        $this->classifier()->result = new Classification('assistenza_tecnica', 0.9, 'llm', 'it', 'Sito offline');
        $ticket = SupportTicket::factory()->create(['customer_email' => 'cliente@example.com']);

        ClassifyTicketJob::dispatchSync($ticket->id, $this->email());

        $ticket->refresh();
        $this->assertSame('assistenza_tecnica', $ticket->type->slug);
        $this->assertSame(TypeSource::Ai, $ticket->type_source);
        $this->assertSame('Sito offline', $ticket->ai_summary);
        $this->assertNotNull($ticket->assigned_user_id);
        $this->assertDatabaseHas('support_ticket_type_changes', ['support_ticket_id' => $ticket->id, 'changed_by' => null]);
    }
}
