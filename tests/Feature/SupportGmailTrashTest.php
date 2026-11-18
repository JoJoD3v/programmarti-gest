<?php

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\SupportTicket;
use App\Models\User;
use App\Ticketing\DTO\TrashedEmail;
use App\Ticketing\Enums\TicketStatus;
use App\Ticketing\Mailbox\GmailImapMailboxClient;
use App\Ticketing\Mailbox\Imap\ImapConnection;
use App\Ticketing\Mailbox\Imap\ImapException;
use App\Ticketing\Mailbox\Imap\ImapResponse;
use App\Ticketing\Support\TicketingSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSupportFixtures;
use Tests\TestCase;

class SupportGmailTrashTest extends TestCase
{
    use BuildsSupportFixtures, RefreshDatabase;

    private function closedTicket(string $threadId, int $hoursAgo, ?Mailbox $mailbox = null): SupportTicket
    {
        return SupportTicket::factory()->closed()->create([
            'gmail_thread_id' => $threadId,
            'closed_at' => now()->subHours($hoursAgo),
            'mailbox_id' => ($mailbox ?? $this->defaultMailbox)->id,
        ]);
    }

    private function enable(int $hours = 24): void
    {
        app(TicketingSettings::class)->set('gmail_trash_after_hours', $hours);
    }

    public function test_does_nothing_while_disabled(): void
    {
        $this->closedTicket('111', 100);

        $this->artisan('ticketing:trash-closed-threads')->assertSuccessful();

        $this->assertSame([], $this->mailbox()->trashedThreads);
    }

    public function test_trashes_threads_of_tickets_closed_longer_than_the_delay(): void
    {
        $this->enable(24);
        $old = $this->closedTicket('111', 25);
        $recent = $this->closedTicket('222', 23);

        $this->artisan('ticketing:trash-closed-threads')->assertSuccessful();

        $this->assertSame(['111'], $this->mailbox()->trashedThreads);
        $this->assertNotNull($old->fresh()->gmail_trashed_at);
        $this->assertNull($recent->fresh()->gmail_trashed_at);
    }

    public function test_does_not_trash_the_same_thread_twice(): void
    {
        $this->enable();
        $this->closedTicket('111', 30);

        $this->artisan('ticketing:trash-closed-threads');
        $this->artisan('ticketing:trash-closed-threads');

        $this->assertSame(['111'], $this->mailbox()->trashedThreads);
    }

    public function test_trashes_again_a_ticket_reopened_and_closed_after_the_first_trash(): void
    {
        $this->enable();
        $ticket = $this->closedTicket('111', 30);
        $ticket->forceFill(['gmail_trashed_at' => now()->subHours(40)])->save();

        $this->artisan('ticketing:trash-closed-threads');

        $this->assertSame(['111'], $this->mailbox()->trashedThreads);
    }

    public function test_skips_a_thread_shared_with_a_ticket_still_open(): void
    {
        $this->enable();
        $this->closedTicket('111', 30);
        SupportTicket::factory()->create(['gmail_thread_id' => '111', 'status' => TicketStatus::Open, 'mailbox_id' => $this->defaultMailbox->id]);

        $this->artisan('ticketing:trash-closed-threads');

        $this->assertSame([], $this->mailbox()->trashedThreads);
    }

    public function test_an_imap_failure_leaves_the_ticket_to_retry_later(): void
    {
        $this->enable();
        $ticket = $this->closedTicket('111', 30);
        $this->mailbox()->trashFailure = new ImapException('timeout');

        $this->artisan('ticketing:trash-closed-threads')->assertSuccessful();

        $this->assertNull($ticket->fresh()->gmail_trashed_at);
    }

    public function test_ignores_closed_tickets_of_an_imap_mailbox(): void
    {
        $this->enable();
        $imap = $this->mailboxRow(['driver' => Mailbox::DRIVER_IMAP, 'imap_host' => 'mail.programmarti.it', 'smtp_host' => 'mail.programmarti.it']);
        $imapTicket = $this->closedTicket('333', 30, $imap);
        $gmailTicket = $this->closedTicket('111', 30);

        $this->artisan('ticketing:trash-closed-threads')->assertSuccessful();

        $this->assertSame(['111'], $this->mailbox()->trashedThreads);
        $this->assertNull($imapTicket->fresh()->gmail_trashed_at);
        $this->assertNotNull($gmailTicket->fresh()->gmail_trashed_at);
    }

    public function test_ignores_closed_tickets_of_a_disconnected_gmail_mailbox(): void
    {
        $this->enable();
        $this->defaultMailbox->forceFill(['disconnected_at' => now()])->save();
        $ticket = $this->closedTicket('111', 30);

        $this->artisan('ticketing:trash-closed-threads')->assertSuccessful();

        $this->assertSame([], $this->mailbox()->trashedThreads);
        $this->assertNull($ticket->fresh()->gmail_trashed_at);
    }

    public function test_admin_sets_and_clears_the_delay_from_the_settings_page(): void
    {
        $admin = $this->admin();
        $base = ['drafts_audience' => 'admins'];

        $this->actingAs($admin)->put(route('support.settings.update'), $base + ['gmail_trash_after_hours' => 48])->assertSessionHasNoErrors();
        $this->assertSame(48, app(TicketingSettings::class)->get('gmail_trash_after_hours'));

        $this->actingAs($admin)->put(route('support.settings.update'), $base + ['gmail_trash_after_hours' => 0])->assertSessionHasErrors('gmail_trash_after_hours');

        $this->actingAs($admin)->put(route('support.settings.update'), $base + ['gmail_trash_after_hours' => ''])->assertSessionHasNoErrors();
        $this->assertNull(app(TicketingSettings::class)->get('gmail_trash_after_hours'));

        $this->actingAs($admin)->get(route('support.settings.edit'))->assertOk()->assertSee('Pulizia casella Gmail');
    }

    public function test_trash_page_lists_threads_and_links_their_ticket(): void
    {
        $ticket = $this->closedTicket('111', 30);
        $this->mailbox()->trash = [
            new TrashedEmail('111', 'cliente@example.com', 'Mario Rossi', 'Reso ordine 42', CarbonImmutable::now()),
            new TrashedEmail('111', 'negozio@gmail.com', null, 'Re: Reso ordine 42', CarbonImmutable::now()->subHour()),
            new TrashedEmail('999', 'spam@example.com', null, 'Offerta', CarbonImmutable::now()->subDay()),
        ];

        $this->actingAs($this->admin())->get(route('support.gmail-trash.index'))
            ->assertOk()
            ->assertSeeInOrder(['Mario Rossi', 'Reso ordine 42', '2 email', $ticket->public_id, 'Offerta'])
            ->assertSee(route('support.gmail-trash.restore', [$this->defaultMailbox, '999']));
    }

    public function test_trash_page_switches_mailbox_and_imap_mailboxes_cannot_restore(): void
    {
        $second = $this->mailboxRow(['name' => 'Seconda casella']);
        $imap = $this->mailboxRow(['name' => 'Hosting', 'driver' => Mailbox::DRIVER_IMAP]);
        $this->mailbox()->trash = [new TrashedEmail('999', 'spam@example.com', null, 'Offerta', CarbonImmutable::now())];
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('support.gmail-trash.index', ['mailbox' => $second->id]))
            ->assertOk()
            ->assertSee($second->address)
            ->assertDontSee($imap->address)
            ->assertSee(route('support.gmail-trash.restore', [$second, '999']));

        $this->actingAs($admin)->post(route('support.gmail-trash.restore', [$imap, '999']))->assertNotFound();
        $this->assertSame([], $this->mailbox()->restoredThreads);
    }

    public function test_trash_page_shows_the_imap_error_instead_of_failing(): void
    {
        $this->mailbox()->trashFailure = new ImapException('timeout');

        $this->actingAs($this->admin())->get(route('support.gmail-trash.index'))
            ->assertOk()
            ->assertSee('Impossibile leggere il cestino Gmail: timeout');
    }

    public function test_ticket_agents_see_and_restore_and_other_employees_cannot(): void
    {
        $employee = User::factory()->create(['can_manage_tickets' => false]);

        $this->actingAs($employee)->get(route('support.gmail-trash.index'))->assertForbidden();
        $this->actingAs($employee)->post(route('support.gmail-trash.restore', [$this->defaultMailbox, '111']))->assertForbidden();
        $this->assertSame([], $this->mailbox()->restoredThreads);

        $agent = $this->agent();
        $this->actingAs($agent)->get(route('support.gmail-trash.index'))->assertOk()->assertSee(route('support.gmail-trash.index'));
        $this->actingAs($agent)->post(route('support.gmail-trash.restore', [$this->defaultMailbox, '111']))->assertSessionHas('success');
        $this->assertSame(['111'], $this->mailbox()->restoredThreads);
    }

    public function test_real_client_reads_the_trash_and_moves_threads_both_ways(): void
    {
        $headers = "From: Mario Rossi <cliente@example.com>\r\nSubject: =?UTF-8?Q?Reso_=C3=A8_pronto?=\r\n\r\n";
        $imap = new class('imap.example.test', 993) extends ImapConnection
        {
            /** @var list<string> */
            public array $commands = [];

            public string $headers = '';

            public function connect(): void {}

            public function login(string $username, string $password): void {}

            public function command(string $command): ImapResponse
            {
                $this->commands[] = $command;

                return match (true) {
                    str_starts_with($command, 'LIST') => new ImapResponse('OK', 'done', [
                        '* LIST (\HasNoChildren \All) "/" "[Gmail]/Tutti i messaggi"',
                        '* LIST (\HasNoChildren \Trash) "/" "[Gmail]/Cestino"',
                    ]),
                    str_starts_with($command, 'UID SEARCH') => new ImapResponse('OK', 'done', ['* SEARCH 4 9']),
                    str_starts_with($command, 'UID FETCH') => new ImapResponse('OK', 'done', [
                        '* 1 FETCH (UID 4 X-GM-THRID 111 INTERNALDATE "17-Nov-2026 10:00:00 +0000" BODY[HEADER] {'.strlen($this->headers)."}\r\n{$this->headers})",
                    ]),
                    default => new ImapResponse('OK', 'done', []),
                };
            }

            public function logout(): void {}
        };
        $imap->headers = $headers;
        $client = new class($imap) extends GmailImapMailboxClient
        {
            public function __construct(public ImapConnection $imap)
            {
                parent::__construct();
            }

            protected function newImap(Mailbox $mailbox): ImapConnection
            {
                return $this->imap;
            }
        };

        $trash = $client->listTrash($this->defaultMailbox);
        $this->assertCount(1, $trash);
        $this->assertSame(['111', 'cliente@example.com', 'Mario Rossi', 'Reso è pronto'], [$trash[0]->gmailThreadId, $trash[0]->fromAddress, $trash[0]->fromName, $trash[0]->subject]);

        $this->assertSame(2, $client->trashThread($this->defaultMailbox, '111'));
        $this->assertSame(2, $client->restoreThread($this->defaultMailbox, '111'));
        $moves = array_values(array_filter($imap->commands, fn (string $command) => str_starts_with($command, 'UID MOVE')));
        $this->assertSame(['UID MOVE 4,9 "[Gmail]/Cestino"', 'UID MOVE 4,9 "INBOX"'], $moves);
    }
}
