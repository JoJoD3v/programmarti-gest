<?php

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\SupportSavedReply;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Ticketing\DTO\ConnectionStatus;
use App\Ticketing\Enums\DeliveryStatus;
use App\Ticketing\Enums\MessageDirection;
use App\Ticketing\Enums\MessageType;
use App\Ticketing\Enums\TicketStatus;
use App\Ticketing\Fakes\FakeMailboxClient;
use App\Ticketing\Jobs\ApplyMailboxLabelJob;
use App\Ticketing\Jobs\SyncMailboxJob;
use App\Ticketing\Support\HtmlSanitizer;
use App\Ticketing\Support\SendLimiter;
use App\Ticketing\Support\TicketSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsSupportFixtures;
use Tests\TestCase;

class SupportMailAndRepliesTest extends TestCase
{
    use BuildsSupportFixtures, RefreshDatabase;

    private function ticketWithInbound(User $agent, array $messageOverrides = []): SupportTicket
    {
        $ticket = SupportTicket::factory()->assignedTo($agent)->create([
            'customer_email' => 'cliente@example.com',
            'subject' => "Dov'è l'ordine?",
            'mailbox_id' => $this->defaultMailbox->id,
        ]);
        SupportTicketMessage::create($messageOverrides + [
            'support_ticket_id' => $ticket->id,
            'direction' => MessageDirection::In,
            'type' => MessageType::Reply,
            'delivery_status' => DeliveryStatus::Received,
            'gmail_message_id' => (string) fake()->unique()->numerify('##########'),
            'message_id_header' => '<in1@cliente.example>',
            'references' => '<root@cliente.example>',
            'from_email' => 'cliente@example.com',
            'subject' => "Dov'è l'ordine?",
            'body_text' => 'Aiuto',
            'received_at' => now(),
        ]);

        return $ticket;
    }

    public function test_sync_creates_tickets_moves_the_cursor_and_never_duplicates(): void
    {
        $this->seedTypes();
        foreach ([1, 2, 3] as $uid) {
            $this->mailbox()->push($this->email(['uid' => $uid, 'fromEmail' => "c{$uid}@example.com"]));
        }

        SyncMailboxJob::dispatchSync($this->defaultMailbox->id);
        SyncMailboxJob::dispatchSync($this->defaultMailbox->id);

        $this->assertSame(3, SupportTicket::count());
        $this->assertSame(3, SupportTicket::query()->where('mailbox_id', $this->defaultMailbox->id)->count());
        $state = $this->defaultMailbox->fresh();
        $this->assertSame(3, (int) $state->last_uid);
        $this->assertSame(0, $state->consecutive_failures);
        $this->assertNotNull($state->last_synced_at);
    }

    public function test_rejected_credentials_disconnect_once_notify_once_and_stop_retrying(): void
    {
        $admin = $this->admin();
        $this->mailbox()->failWith = FakeMailboxClient::authFailure();

        SyncMailboxJob::dispatchSync($this->defaultMailbox->id);
        $this->mailbox()->failWith = new \RuntimeException('non deve essere richiamato');
        SyncMailboxJob::dispatchSync($this->defaultMailbox->id);

        $state = $this->defaultMailbox->fresh();
        $this->assertTrue($state->isDisconnected());
        $this->assertCount(1, $admin->notifications);
    }

    public function test_network_errors_count_failures_and_reset_on_success(): void
    {
        $this->mailbox()->failWith = new \RuntimeException('timeout');

        SyncMailboxJob::dispatchSync($this->defaultMailbox->id);
        SyncMailboxJob::dispatchSync($this->defaultMailbox->id);
        $this->assertSame(2, $this->defaultMailbox->fresh()->consecutive_failures);
        $this->assertFalse($this->defaultMailbox->fresh()->isDisconnected());

        $this->mailbox()->failWith = null;
        SyncMailboxJob::dispatchSync($this->defaultMailbox->id);
        $this->assertSame(0, $this->defaultMailbox->fresh()->consecutive_failures);
    }

    public function test_subject_token_is_not_duplicated(): void
    {
        $this->assertSame('Ordine in ritardo', TicketSubject::forReply('Ordine in ritardo', 'T-1042'));
        $this->assertSame('Ordine in ritardo', TicketSubject::forReply('Re: RE: Ordine in ritardo [T-1042]', 'T-1042'));
    }

    public function test_reply_goes_out_in_the_thread_to_the_customer_and_moves_the_ticket_to_pending(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticketWithInbound($agent);

        $this->actingAs($agent)->post(route('support-tickets.reply', $ticket), [
            'body' => 'Il pacco parte domani.',
            'to' => 'attaccante@evil.example', // must be ignored
        ])->assertRedirect(route('support-tickets.show', $ticket));

        $sent = $this->mailbox()->lastSent();
        $this->assertSame('cliente@example.com', $sent->to);
        $this->assertSame("Dov'è l'ordine?", $sent->subject);
        $this->assertSame('<in1@cliente.example>', $sent->inReplyTo);
        $this->assertSame(['<root@cliente.example>', '<in1@cliente.example>'], $sent->references);
        $this->assertStringEndsWith('@'.Str::after($this->defaultMailbox->address, '@').'>', $sent->messageId);
        $this->assertSame([$this->defaultMailbox->id], $this->mailbox()->sentFrom);

        $ticket->refresh();
        $this->assertSame(TicketStatus::Pending, $ticket->status);
        $this->assertNotNull($ticket->first_response_at);
        $out = $ticket->messages()->where('direction', 'out')->first();
        $this->assertSame(DeliveryStatus::Sent, $out->delivery_status);
        $this->assertSame($sent->messageId, $out->message_id_header);
        $this->assertSame($this->defaultMailbox->address, $out->from_email);
    }

    public function test_email_arriving_on_a_second_mailbox_opens_a_ticket_there_and_is_answered_from_it(): void
    {
        $this->seedTypes();
        $second = $this->mailboxRow(['name' => 'Commerciale']);
        $this->mailbox()->push($this->email(['uid' => 1, 'to' => [$second->address], 'mailboxId' => $second->id]));

        SyncMailboxJob::dispatchSync($second->id);

        $ticket = SupportTicket::query()->sole();
        $this->assertSame($second->id, $ticket->mailbox_id);

        $this->actingAs($this->admin())->post(route('support-tickets.reply', $ticket), ['body' => 'Verifichiamo subito.'])->assertRedirect();

        $this->assertSame([$second->id], $this->mailbox()->sentFrom);
        $this->assertStringEndsWith('@'.Str::after($second->address, '@').'>', $this->mailbox()->lastSent()->messageId);
        $this->assertSame($second->address, $ticket->messages()->where('direction', 'out')->sole()->from_email);
    }

    public function test_reply_with_unresolved_placeholder_is_rejected(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticketWithInbound($agent);

        $this->actingAs($agent)->post(route('support-tickets.reply', $ticket), ['body' => 'Il tracking è [[DA COMPLETARE: tracking]]'])
            ->assertSessionHasErrors('body');

        $this->assertSame([], $this->mailbox()->sent);
    }

    public function test_ticket_list_starts_from_newest_and_sorts_by_column(): void
    {
        $admin = $this->admin();
        $older = SupportTicket::factory()->create(['customer_name' => 'Bianchi']);
        $newer = SupportTicket::factory()->create(['customer_name' => 'Abate']);

        $this->actingAs($admin)->get(route('support-tickets.index'))->assertSeeInOrder([$newer->public_id, $older->public_id]);
        $this->actingAs($admin)->get(route('support-tickets.index', ['sort' => 'ticket', 'dir' => 'asc']))->assertSeeInOrder([$older->public_id, $newer->public_id]);
        $this->actingAs($admin)->get(route('support-tickets.index', ['sort' => 'customer', 'dir' => 'asc']))->assertSeeInOrder([$newer->public_id, $older->public_id]);
        $this->actingAs($admin)->get(route('support-tickets.index', ['sort' => 'evil', 'dir' => 'sideways']))->assertOk();
    }

    public function test_employees_cannot_reply_to_tickets_that_are_not_theirs(): void
    {
        $owner = $this->agent();
        $intruder = $this->agent();
        $plain = User::factory()->create();
        $ticket = $this->ticketWithInbound($owner);

        $this->actingAs($intruder)->post(route('support-tickets.reply', $ticket), ['body' => 'ciao'])->assertForbidden();
        $this->actingAs($intruder)->get(route('support-tickets.show', $ticket))->assertForbidden();
        $this->actingAs($plain)->get(route('support-tickets.index'))->assertForbidden();
        $this->actingAs($this->admin())->post(route('support-tickets.reply', $ticket), ['body' => 'ok dall\'admin'])->assertRedirect();
        $this->assertCount(1, $this->mailbox()->sent);
    }

    public function test_failed_delivery_marks_the_message_and_leaves_the_ticket_state_alone(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticketWithInbound($agent);
        $this->mailbox()->sendFailure = new \RuntimeException('SMTP giù');

        try {
            $this->actingAs($agent)->withoutExceptionHandling()->post(route('support-tickets.reply', $ticket), ['body' => 'Risposta']);
        } catch (\RuntimeException) {
            // the sync queue rethrows after calling failed()
        }

        $out = $ticket->messages()->where('direction', 'out')->first();
        $this->assertSame(DeliveryStatus::Failed, $out->delivery_status);
        $this->assertSame('SMTP giù', $out->send_error);
        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);

        $this->mailbox()->sendFailure = null;
        $this->actingAs($agent)->post(route('support-tickets.retry', [$ticket, $out->id]))->assertRedirect();
        $this->assertSame(DeliveryStatus::Sent, $out->fresh()->delivery_status);
        $this->assertSame(TicketStatus::Pending, $ticket->fresh()->status);
    }

    public function test_reply_is_not_attempted_while_the_mailbox_is_disconnected(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticketWithInbound($agent);
        $this->defaultMailbox->forceFill(['disconnected_at' => now()])->save();

        try {
            $this->actingAs($agent)->withoutExceptionHandling()->post(route('support-tickets.reply', $ticket), ['body' => 'Risposta']);
        } catch (\RuntimeException) {
            // the sync queue may rethrow after calling failed()
        }

        $this->assertCount(0, $this->mailbox()->sent);
        $out = $ticket->messages()->where('direction', 'out')->first();
        $this->assertSame(DeliveryStatus::Failed, $out->delivery_status);
        $this->assertSame('Casella scollegata', $out->send_error);
    }

    public function test_rejected_credentials_on_send_disconnect_without_retrying(): void
    {
        $admin = $this->admin();
        $agent = $this->agent();
        $ticket = $this->ticketWithInbound($agent);
        $this->mailbox()->sendFailure = FakeMailboxClient::authFailure();

        try {
            $this->actingAs($agent)->withoutExceptionHandling()->post(route('support-tickets.reply', $ticket), ['body' => 'Risposta']);
        } catch (\Throwable) {
            // the sync queue may rethrow after calling failed()
        }

        $this->assertTrue($this->defaultMailbox->fresh()->isDisconnected());
        $this->assertCount(1, $admin->notifications);
        $out = $ticket->messages()->where('direction', 'out')->first();
        $this->assertSame(DeliveryStatus::Failed, $out->delivery_status);
    }

    public function test_rejected_credentials_on_label_disconnect_and_notify_once(): void
    {
        $admin = $this->admin();
        $this->mailbox()->labelFailure = FakeMailboxClient::authFailure();

        try {
            ApplyMailboxLabelJob::dispatchSync($this->defaultMailbox->id, '123', 'gestionale/importata');
        } catch (\Throwable) {
            // the sync queue may rethrow after calling failed()
        }

        $this->assertTrue($this->defaultMailbox->fresh()->isDisconnected());
        $this->assertCount(1, $admin->notifications);
    }

    public function test_label_job_skips_while_disconnected_and_for_non_gmail_mailboxes(): void
    {
        $this->defaultMailbox->forceFill(['disconnected_at' => now()])->save();
        $imap = $this->mailboxRow(['driver' => Mailbox::DRIVER_IMAP]);

        ApplyMailboxLabelJob::dispatchSync($this->defaultMailbox->id, '123', 'gestionale/importata');
        ApplyMailboxLabelJob::dispatchSync($imap->id, '456', 'gestionale/importata');
        ApplyMailboxLabelJob::dispatchSync(null, '789', 'gestionale/importata');

        $this->assertSame([], $this->mailbox()->labels);
    }

    public function test_reply_and_close_and_saved_reply_counter_only_moves_on_send(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticketWithInbound($agent);
        $saved = SupportSavedReply::create(['title' => 'Grazie', 'body' => 'Grazie a lei', 'scope' => 'global', 'active' => true]);

        $this->assertSame(0, $saved->fresh()->usage_count);

        $this->actingAs($agent)->post(route('support-tickets.reply', $ticket), ['body' => 'Grazie a lei', 'saved_reply_id' => $saved->id, 'close' => 1]);

        $this->assertSame(1, $saved->fresh()->usage_count);
        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
    }

    public function test_attachments_are_stored_on_the_private_disk_and_sent(): void
    {
        Storage::fake('local');
        $agent = $this->agent();
        $ticket = $this->ticketWithInbound($agent);

        $this->actingAs($agent)->post(route('support-tickets.reply', $ticket), [
            'body' => 'In allegato',
            'attachments' => [UploadedFile::fake()->create('guida.pdf', 50, 'application/pdf')],
        ])->assertRedirect();

        $attachment = $ticket->messages()->where('direction', 'out')->first()->attachments()->first();
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertCount(1, $this->mailbox()->lastSent()->attachments);
    }

    public function test_attachment_download_is_authorized(): void
    {
        Storage::fake('local');
        $owner = $this->agent();
        $other = $this->agent();
        $ticket = $this->ticketWithInbound($owner);
        $message = $ticket->messages()->first();
        Storage::disk('local')->put('support/x.txt', 'ciao');
        $attachment = $message->attachments()->create(['filename' => 'x.txt', 'mime' => 'text/plain', 'size' => 4, 'disk' => 'local', 'path' => 'support/x.txt']);

        $this->actingAs($owner)->get(route('support-attachments.download', $attachment))->assertOk();
        $this->actingAs($other)->get(route('support-attachments.download', $attachment))->assertForbidden();
    }

    public function test_daily_send_limit_blocks_further_sends_and_alerts_admins(): void
    {
        $admin = $this->admin();
        config(['ticketing.mailbox.daily_send_limit' => 2]);
        $second = $this->mailboxRow();
        $limiter = app(SendLimiter::class);

        $this->assertTrue($limiter->reserve($this->defaultMailbox));
        $this->assertTrue($limiter->reserve($this->defaultMailbox));
        $this->assertFalse($limiter->reserve($this->defaultMailbox));
        $this->assertNotEmpty($admin->notifications);
        $this->assertTrue($limiter->reserve($second), 'the limit is counted per mailbox');
    }

    public function test_html_is_sanitized_and_remote_images_are_stripped_on_display(): void
    {
        $dirty = '<p onclick="x()">Ciao <script>alert(1)</script><a href="javascript:alert(2)">link</a>'
            .'<img src="https://tracker.example/p.gif" onerror="alert(3)"><iframe src="https://evil.example"></iframe>'
            .'<style>body{background:url(javascript:alert(4))}</style><svg onload="alert(5)"></svg><form action="/x"><input></form></p>';

        $clean = HtmlSanitizer::clean($dirty);

        foreach (['<script', 'onclick', 'onerror', 'javascript:', '<iframe', '<style', '<svg', '<form', 'onload'] as $bad) {
            $this->assertStringNotContainsStringIgnoringCase($bad, $clean);
        }
        $this->assertStringContainsString('Ciao', $clean);
        $this->assertStringNotContainsString('<img', HtmlSanitizer::forDisplay($clean, false));
        $this->assertStringContainsString('<img', HtmlSanitizer::forDisplay($clean, true));
    }

    public function test_mailbox_page_saves_only_when_the_connection_test_passes(): void
    {
        $admin = $this->admin();
        $mailbox = $this->defaultMailbox;
        $form = [
            'name' => 'ProgrammArti',
            'address' => $mailbox->address,
            'driver' => Mailbox::DRIVER_GMAIL,
            'imap_host' => '',
            'imap_port' => '',
            'smtp_host' => '',
            'smtp_port' => '',
            'password' => 'abcd efgh ijkl mnop',
            'active' => 1,
        ];

        $this->mailbox()->status = new ConnectionStatus(false, false, 'AUTHENTICATIONFAILED');
        $this->actingAs($admin)->put(route('support.mailboxes.update', $mailbox), $form)->assertSessionHas('error');
        $this->assertSame('abcdabcdabcdabcd', $mailbox->fresh()->password, 'nothing saved');

        $this->mailbox()->status = new ConnectionStatus(true, true);
        $this->actingAs($admin)->put(route('support.mailboxes.update', $mailbox), $form)->assertSessionHas('success');

        $this->assertFalse($this->mailbox()->testedWith->usesOAuth(), 'the typed credentials are tested');
        $mailbox->refresh();
        $this->assertSame('abcdefghijklmnop', $mailbox->password, 'spaces stripped');
        $this->assertNotSame('abcdefghijklmnop', $mailbox->getRawOriginal('password'), 'stored encrypted');
        $this->assertSame(['imap.gmail.com', 993, 'smtp.gmail.com', 465], [$mailbox->imap_host, (int) $mailbox->imap_port, $mailbox->smtp_host, (int) $mailbox->smtp_port]);
        $this->actingAs($admin)->get(route('support.mailboxes.edit', $mailbox))->assertOk()->assertDontSee('abcdefghijklmnop');
        $this->actingAs($this->agent())->get(route('support.mailboxes.edit', $mailbox))->assertForbidden();
        $this->actingAs($this->agent())->get(route('support.mailboxes.index'))->assertForbidden();
    }

    public function test_imap_mailbox_is_created_only_when_the_connection_test_passes(): void
    {
        $admin = $this->admin();
        $form = [
            'name' => 'Hosting',
            'address' => 'Info@ProgrammArti.it',
            'driver' => Mailbox::DRIVER_IMAP,
            'imap_host' => 'mail.programmarti.it',
            'imap_port' => 993,
            'smtp_host' => 'mail.programmarti.it',
            'smtp_port' => 587,
            'password' => 'pass word',
            'active' => 1,
        ];

        $this->actingAs($admin)->get(route('support.mailboxes.create'))->assertOk();

        $this->mailbox()->status = new ConnectionStatus(false, false, 'AUTHENTICATIONFAILED');
        $this->actingAs($admin)->post(route('support.mailboxes.store'), $form)->assertSessionHas('error');
        $this->assertSame(1, Mailbox::count());

        $this->actingAs($admin)->post(route('support.mailboxes.store'), ['imap_host' => ''] + $form)->assertSessionHasErrors('imap_host');
        $this->assertSame(1, Mailbox::count());

        $this->mailbox()->status = new ConnectionStatus(true, true);
        $response = $this->actingAs($admin)->post(route('support.mailboxes.store'), $form)->assertSessionHas('success');

        $mailbox = Mailbox::query()->where('address', 'info@programmarti.it')->sole();
        $response->assertRedirect(route('support.mailboxes.edit', $mailbox));
        $this->assertSame(Mailbox::DRIVER_IMAP, $mailbox->driver);
        $this->assertSame('pass word', $mailbox->password, 'IMAP passwords are kept as typed');
        $this->assertSame('info@programmarti.it', $mailbox->username);
        $this->assertSame(['mail.programmarti.it', 587], [$mailbox->smtp_host, (int) $mailbox->smtp_port]);
        $this->assertNotNull($mailbox->last_verified_at);
        $this->assertSame($mailbox->address, $this->mailbox()->testedWith->address());
        $this->actingAs($this->agent())->post(route('support.mailboxes.store'), $form)->assertForbidden();
    }

    public function test_stored_secrets_are_never_sent_to_a_new_server_without_retyping_the_password(): void
    {
        $admin = $this->admin();
        $imap = $this->mailboxRow(['driver' => Mailbox::DRIVER_IMAP, 'imap_host' => 'mail.programmarti.it', 'smtp_host' => 'mail.programmarti.it', 'username' => 'info@programmarti.it', 'password' => 'old-secret']);
        $form = ['name' => 'Hosting', 'address' => $imap->address, 'driver' => Mailbox::DRIVER_IMAP, 'imap_host' => 'evil.example', 'imap_port' => 993, 'smtp_host' => 'mail.programmarti.it', 'smtp_port' => 465, 'username' => 'info@programmarti.it', 'active' => 1];

        $this->actingAs($admin)->put(route('support.mailboxes.update', $imap), $form)->assertSessionHasErrors('password');
        $this->assertNull($this->mailbox()->testedWith, 'no connection attempted with the stored password');
        $this->assertSame('mail.programmarti.it', $imap->fresh()->imap_host);

        // Gmail ignores any host typed in the form: the stored password and token only ever go to Google
        $this->defaultMailbox->forceFill(['oauth_refresh_token' => 'r1'])->save();
        $this->actingAs($admin)->put(route('support.mailboxes.update', $this->defaultMailbox), [
            'name' => 'ProgrammArti', 'address' => $this->defaultMailbox->address, 'driver' => Mailbox::DRIVER_GMAIL,
            'imap_host' => 'evil.example', 'smtp_host' => 'evil.example', 'username' => $this->defaultMailbox->username, 'active' => 1,
        ])->assertSessionHas('success');

        $gmail = $this->defaultMailbox->fresh();
        $this->assertSame(['imap.gmail.com', 'smtp.gmail.com'], [$gmail->imap_host, $gmail->smtp_host]);
        $this->assertSame('r1', $gmail->oauth_refresh_token, 'unchanged target keeps the Google link');
    }

    public function test_reactivating_a_disconnected_mailbox_resumes_syncing(): void
    {
        $this->defaultMailbox->forceFill(['disconnected_at' => now(), 'last_error' => 'AUTHENTICATIONFAILED'])->save();

        $this->actingAs($this->admin())->post(route('support.mailboxes.reactivate', $this->defaultMailbox))->assertSessionHas('success');

        $this->assertFalse($this->defaultMailbox->fresh()->isDisconnected());
    }
}
