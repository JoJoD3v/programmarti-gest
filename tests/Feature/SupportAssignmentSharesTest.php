<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use App\Ticketing\Actions\TicketAssigner;
use App\Ticketing\Support\TicketingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSupportFixtures;
use Tests\TestCase;

class SupportAssignmentSharesTest extends TestCase
{
    use BuildsSupportFixtures, RefreshDatabase;

    /**
     * @param  array<int, int>  $shares
     */
    private function setShares(array $shares): void
    {
        app(TicketingSettings::class)->setMany(['assignment_shares' => $shares, 'assignment_shares_since' => now()->toIso8601String()]);
    }

    private function assignNew(int $count): void
    {
        $assigner = app(TicketAssigner::class);
        SupportTicket::factory()->count($count)->create()->each(fn ($ticket) => $assigner->assignAutomatically($ticket));
    }

    public function test_new_tickets_follow_the_configured_percentages(): void
    {
        [$a, $b, $c] = [$this->agent(), $this->agent(), $this->agent()];
        $this->setShares([$a->id => 50, $b->id => 30, $c->id => 20]);

        $this->assignNew(10);

        $this->assertSame(5, SupportTicket::assignedTo($a)->count());
        $this->assertSame(3, SupportTicket::assignedTo($b)->count());
        $this->assertSame(2, SupportTicket::assignedTo($c)->count());

        $this->assignNew(10);

        $this->assertSame(10, SupportTicket::assignedTo($a)->count());
        $this->assertSame(6, SupportTicket::assignedTo($b)->count());
        $this->assertSame(4, SupportTicket::assignedTo($c)->count());
    }

    public function test_unavailable_people_and_zero_shares_are_skipped(): void
    {
        [$a, $b, $away, $zero] = [$this->agent(), $this->agent(), $this->agent(['ticket_available' => false]), $this->agent()];
        $this->setShares([$a->id => 40, $b->id => 20, $away->id => 40, $zero->id => 0]);

        $this->assignNew(9);

        $this->assertSame(6, SupportTicket::assignedTo($a)->count());
        $this->assertSame(3, SupportTicket::assignedTo($b)->count());
        $this->assertSame(0, SupportTicket::assignedTo($away)->count());
        $this->assertSame(0, SupportTicket::assignedTo($zero)->count());
    }

    public function test_someone_enabled_later_gets_tickets_until_they_reach_the_average_share(): void
    {
        [$a, $b] = [$this->agent(), $this->agent()];
        $this->setShares([$a->id => 50, $b->id => 50]);
        $this->assignNew(4);

        $newcomer = $this->agent();
        $this->assignNew(2);

        $this->assertSame(2, SupportTicket::assignedTo($newcomer)->count());
        $this->assertSame(2, SupportTicket::assignedTo($a)->count());

        $this->assignNew(3);

        $this->assertSame(3, SupportTicket::assignedTo($newcomer)->count());
        $this->assertSame(3, SupportTicket::assignedTo($a)->count());
        $this->assertSame(3, SupportTicket::assignedTo($b)->count());
    }

    public function test_admin_saves_shares_only_when_they_add_up_to_100(): void
    {
        $admin = $this->admin();
        [$a, $b] = [$this->agent(), $this->agent()];
        $outsider = User::factory()->create();

        $this->actingAs($admin)->put(route('support.settings.shares'), ['shares' => [$a->id => 70, $b->id => 20]])
            ->assertSessionHasErrors('shares');
        $this->assertSame([], app(TicketingSettings::class)->get('assignment_shares'));

        $this->actingAs($admin)->put(route('support.settings.shares'), ['shares' => [$a->id => 70, $b->id => 30, $outsider->id => 0]])
            ->assertSessionHasNoErrors();
        $this->assertEquals([$a->id => 70, $b->id => 30], app(TicketingSettings::class)->get('assignment_shares'));
        $this->assertNotNull(app(TicketingSettings::class)->get('assignment_shares_since'));

        $this->actingAs($admin)->get(route('support.settings.edit'))->assertOk()->assertSee('Distribuzione automatica dei ticket');

        $this->actingAs($admin)->delete(route('support.settings.shares.reset'))->assertSessionHasNoErrors();
        $this->assertSame([], app(TicketingSettings::class)->get('assignment_shares'));
    }

    public function test_employees_cannot_change_shares(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent)->put(route('support.settings.shares'), ['shares' => [$agent->id => 100]])->assertForbidden();
    }
}
