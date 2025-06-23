<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Preventivo;
use App\Models\PreventivoItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventivoLineBreakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user and authenticate
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_pdf_template_preserves_line_breaks_in_descriptions()
    {
        // Create test data with line breaks
        $client = Client::create([
            'first_name' => 'Test',
            'last_name' => 'Client',
            'email' => 'test@example.com',
            'entity_type' => 'individual'
        ]);

        $project = Project::create([
            'name' => 'Test Project',
            'description' => "This is a project description\nwith multiple lines\nand line breaks",
            'project_type' => 'website',
            'client_id' => $client->id,
            'payment_type' => 'one_time',
            'total_cost' => 1000.00,
            'start_date' => now(),
            'status' => 'in_progress'
        ]);

        $preventivo = Preventivo::create([
            'quote_number' => 'TEST-2025-0001',
            'client_id' => $client->id,
            'project_id' => $project->id,
            'description' => "This is a preventivo description\nwith line breaks\nto test formatting",
            'total_amount' => 500.00,
            'status' => 'draft'
        ]);

        $item = PreventivoItem::create([
            'preventivo_id' => $preventivo->id,
            'description' => "Work item description\nwith multiple lines\nfor testing",
            'cost' => 500.00,
            'ai_enhanced_description' => "AI enhanced description\nwith line breaks\nfor comprehensive testing"
        ]);

        // Load the PDF view and check that line breaks are converted to <br> tags
        $view = view('preventivi.pdf', compact('preventivo'));
        $html = $view->render();

        // Check that line breaks in preventivo description are converted to <br>
        $this->assertStringContainsString('This is a preventivo description<br />', $html);
        $this->assertStringContainsString('with line breaks<br />', $html);
        $this->assertStringContainsString('to test formatting', $html);

        // Check that line breaks in project description are converted to <br>
        $this->assertStringContainsString('This is a project description<br />', $html);
        $this->assertStringContainsString('with multiple lines<br />', $html);
        $this->assertStringContainsString('and line breaks', $html);

        // Check that line breaks in work item description are converted to <br>
        $this->assertStringContainsString('Work item description<br />', $html);
        $this->assertStringContainsString('with multiple lines<br />', $html);
        $this->assertStringContainsString('for testing', $html);

        // Check that line breaks in AI enhanced description are converted to <br>
        $this->assertStringContainsString('AI enhanced description<br />', $html);
        $this->assertStringContainsString('with line breaks<br />', $html);
        $this->assertStringContainsString('for comprehensive testing', $html);

        // Verify that the original newline characters are not present in the output
        $this->assertStringNotContainsString("This is a preventivo description\nwith line breaks", $html);
        $this->assertStringNotContainsString("Work item description\nwith multiple lines", $html);
    }

    public function test_pdf_template_handles_empty_descriptions_gracefully()
    {
        // Create test data with empty descriptions
        $client = Client::create([
            'first_name' => 'Empty',
            'last_name' => 'Client',
            'email' => 'empty@example.com',
            'entity_type' => 'individual'
        ]);

        $project = Project::create([
            'name' => 'Empty Project',
            'description' => null,
            'project_type' => 'website',
            'client_id' => $client->id,
            'payment_type' => 'one_time',
            'total_cost' => 1000.00,
            'start_date' => now(),
            'status' => 'in_progress'
        ]);

        $preventivo = Preventivo::create([
            'quote_number' => 'TEST-2025-0002',
            'client_id' => $client->id,
            'project_id' => $project->id,
            'description' => '',
            'total_amount' => 300.00,
            'status' => 'draft'
        ]);

        $item = PreventivoItem::create([
            'preventivo_id' => $preventivo->id,
            'description' => '',
            'cost' => 300.00,
            'ai_enhanced_description' => null
        ]);

        // Load the PDF view and ensure it doesn't break with empty descriptions
        $view = view('preventivi.pdf', compact('preventivo'));
        $html = $view->render();

        // Should render without errors
        $this->assertStringContainsString('Preventivo', $html);
        $this->assertStringContainsString($preventivo->quote_number, $html);
    }

    public function test_pdf_template_escapes_html_in_descriptions()
    {
        // Create test data with potentially dangerous HTML
        $client = Client::create([
            'first_name' => 'HTML',
            'last_name' => 'Client',
            'email' => 'html@example.com',
            'entity_type' => 'individual'
        ]);

        $project = Project::create([
            'name' => 'HTML Project',
            'description' => "Project with <script>alert('xss')</script>\nand line breaks",
            'project_type' => 'website',
            'client_id' => $client->id,
            'payment_type' => 'one_time',
            'total_cost' => 1000.00,
            'start_date' => now(),
            'status' => 'in_progress'
        ]);

        $preventivo = Preventivo::create([
            'quote_number' => 'TEST-2025-0003',
            'client_id' => $client->id,
            'project_id' => $project->id,
            'description' => "Description with <b>HTML</b>\nand line breaks",
            'total_amount' => 400.00,
            'status' => 'draft'
        ]);

        $item = PreventivoItem::create([
            'preventivo_id' => $preventivo->id,
            'description' => "Item with <img src='x' onerror='alert(1)'>\nand line breaks",
            'cost' => 400.00
        ]);

        // Load the PDF view
        $view = view('preventivi.pdf', compact('preventivo'));
        $html = $view->render();

        // Check that HTML is escaped but line breaks are preserved
        $this->assertStringContainsString('&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;<br />', $html);
        $this->assertStringContainsString('&lt;b&gt;HTML&lt;/b&gt;<br />', $html);
        $this->assertStringContainsString('&lt;img src=&#039;x&#039; onerror=&#039;alert(1)&#039;&gt;<br />', $html);

        // Ensure actual script tags are not present
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=', $html);
    }
}
