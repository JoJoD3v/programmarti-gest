<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Preventivo;
use App\Models\PreventivoItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The quote PDF keeps line breaks and escapes HTML. Quotes are no longer linked to projects.
 */
class PreventivoLineBreakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_pdf_template_preserves_line_breaks_in_descriptions(): void
    {
        $preventivo = $this->quote(
            "This is a preventivo description\nwith line breaks\nto test formatting",
            "Work item description\nwith multiple lines\nfor testing",
            "AI enhanced description\nwith line breaks\nfor comprehensive testing",
        );

        $html = view('preventivi.pdf', compact('preventivo'))->render();

        $this->assertStringContainsString('This is a preventivo description<br />', $html);
        $this->assertStringContainsString('with line breaks<br />', $html);
        $this->assertStringContainsString('to test formatting', $html);
        $this->assertStringContainsString('Work item description<br />', $html);
        $this->assertStringContainsString('with multiple lines<br />', $html);
        $this->assertStringContainsString('AI enhanced description<br />', $html);
        $this->assertStringContainsString('for comprehensive testing', $html);
        $this->assertStringNotContainsString("This is a preventivo description\nwith line breaks", $html);
        $this->assertStringNotContainsString("Work item description\nwith multiple lines", $html);
    }

    public function test_pdf_template_handles_empty_descriptions_gracefully(): void
    {
        $preventivo = $this->quote('', '');

        $html = view('preventivi.pdf', compact('preventivo'))->render();

        $this->assertStringContainsString('Preventivo', $html);
        $this->assertStringContainsString($preventivo->quote_number, $html);
    }

    public function test_pdf_template_escapes_html_in_descriptions(): void
    {
        $preventivo = $this->quote(
            "Description with <b>HTML</b>\nand line breaks",
            "Item with <img src='x' onerror='alert(1)'>\nand line breaks",
            "AI text with <script>alert('xss')</script>\nand line breaks",
        );

        $html = view('preventivi.pdf', compact('preventivo'))->render();

        $this->assertStringContainsString('&lt;b&gt;HTML&lt;/b&gt;<br />', $html);
        $this->assertStringContainsString('&lt;img src=&#039;x&#039; onerror=&#039;alert(1)&#039;&gt;<br />', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;<br />', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringNotContainsString("<img src='x'", $html);
    }

    private function quote(string $description, string $itemDescription, ?string $aiDescription = null): Preventivo
    {
        $client = Client::create(['first_name' => 'Test', 'last_name' => 'Client', 'email' => fake()->unique()->safeEmail(), 'entity_type' => 'individual']);

        $preventivo = Preventivo::create([
            'quote_number' => 'TEST-2025-'.fake()->unique()->numerify('####'),
            'client_id' => $client->id,
            'description' => $description,
            'total_amount' => 500.00,
            'status' => 'draft',
        ]);

        PreventivoItem::create([
            'preventivo_id' => $preventivo->id,
            'description' => $itemDescription,
            'cost' => 500.00,
            'ai_enhanced_description' => $aiDescription,
        ]);

        return $preventivo;
    }
}
