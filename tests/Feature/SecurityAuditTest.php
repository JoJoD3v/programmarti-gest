<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Preventivo;
use App\Models\PreventivoItem;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Regression tests for the security audit of 2026-11-18.
 * Each test asserts the secure behaviour: it fails while the weakness exists.
 */
class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_link_ignores_spoofed_host_header(): void
    {
        Notification::fake();
        config(['app.url' => 'https://gestionale.example']);
        $user = User::factory()->create();

        $this->withServerVariables(['HTTP_HOST' => 'attacker.test'])
            ->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, 'https://gestionale.example/reset-password/')
                && ! str_contains($url, 'attacker.test');
        });
    }

    public function test_seeders_do_not_create_known_credentials_outside_local(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $admin = User::where('email', 'admin@programmarti.com')->firstOrFail();
        $this->assertTrue($admin->hasRole('Admin'));
        $this->assertFalse(Hash::check('password123', $admin->password));
        $this->assertDatabaseMissing('users', ['email' => 'developer@programmarti.com']);
        $this->assertDatabaseMissing('users', ['email' => 'social@programmarti.com']);
        $this->assertDatabaseCount('clients', 0);
    }

    public function test_seeders_keep_demo_credentials_in_local(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $admin = User::where('email', 'admin@programmarti.com')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $admin->password));
        $this->assertDatabaseHas('users', ['email' => 'developer@programmarti.com']);
    }

    public function test_generate_payments_does_not_delete_completed_payments(): void
    {
        $this->seed(RoleSeeder::class);
        $socialMediaManager = User::factory()->create()->assignRole('Social Media Manager');
        $project = Project::create([
            'name' => 'Sito',
            'project_type' => 'website',
            'client_id' => $this->createClient()->id,
            'payment_type' => 'installments',
            'payment_frequency' => 'monthly',
            'installment_count' => 2,
            'installment_amount' => 100,
            'start_date' => now()->toDateString(),
        ]);
        $project->generatePayments();
        $project->payments()->first()->update(['status' => 'completed', 'invoice_number' => 'INV-TEST-0001']);

        $this->actingAs($socialMediaManager)->post(route('projects.generate-payments', $project));

        $this->assertDatabaseHas('payments', ['invoice_number' => 'INV-TEST-0001', 'status' => 'completed']);
    }

    public function test_ai_enhancement_is_rate_limited(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => 'ok']]],
        ])]);
        $user = User::factory()->create();
        $client = $this->createClient();

        $statuses = collect(range(1, 30))->map(function (int $i) use ($user, $client) {
            $preventivo = Preventivo::create([
                'quote_number' => 'PREV-TEST-'.$i,
                'client_id' => $client->id,
                'description' => 'Test',
            ]);
            PreventivoItem::create(['preventivo_id' => $preventivo->id, 'description' => 'Voce', 'cost' => 10]);

            return $this->actingAs($user)->postJson(route('preventivi.enhance-ai', $preventivo))->status();
        });

        $this->assertContains(429, $statuses->all());
        $this->assertLessThan(30, Http::recorded()->count());
    }

    public function test_ai_enhancement_calls_openai_once_per_quote(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'ok']]]])]);
        $preventivo = $this->createPreventivo();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('preventivi.enhance-ai', $preventivo))->assertOk();
        $this->actingAs($user)->postJson(route('preventivi.enhance-ai', $preventivo))->assertStatus(400);

        Http::assertSentCount(1);
    }

    public function test_password_change_rotates_remember_token(): void
    {
        $user = User::factory()->create();
        $originalToken = $user->remember_token;

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'new-Password-123',
            'password_confirmation' => 'new-Password-123',
        ])->assertSessionHasNoErrors();

        $this->assertNotSame($originalToken, $user->fresh()->remember_token);
    }

    private function createPreventivo(): Preventivo
    {
        $preventivo = Preventivo::create([
            'quote_number' => 'PREV-TEST-'.fake()->unique()->numberBetween(1, 9999),
            'client_id' => $this->createClient()->id,
            'description' => 'Test',
        ]);
        PreventivoItem::create(['preventivo_id' => $preventivo->id, 'description' => 'Voce', 'cost' => 10]);

        return $preventivo;
    }

    private function createClient(): Client
    {
        return Client::create(['first_name' => 'Mario', 'last_name' => 'Test', 'email' => fake()->unique()->safeEmail()]);
    }
}
