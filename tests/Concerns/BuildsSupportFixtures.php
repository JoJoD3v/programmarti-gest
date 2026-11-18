<?php

namespace Tests\Concerns;

use App\Models\Mailbox;
use App\Models\SupportTicket;
use App\Models\User;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\Contracts\MessageClassifier;
use App\Ticketing\Contracts\ReplyDrafter;
use App\Ticketing\DTO\InboundEmail;
use App\Ticketing\Fakes\FakeMailboxClient;
use App\Ticketing\Fakes\FakeMessageClassifier;
use App\Ticketing\Fakes\FakeReplyDrafter;
use Carbon\CarbonImmutable;
use Database\Seeders\SupportTicketTypeSeeder;

trait BuildsSupportFixtures
{
    private int $emailCounter = 0;

    private int $mailboxCounter = 0;

    protected Mailbox $defaultMailbox;

    /**
     * Run by Laravel's setUpTraits(): every test starts with one connected Gmail mailbox.
     */
    protected function setUpBuildsSupportFixtures(): void
    {
        $this->defaultMailbox = $this->mailboxRow();
    }

    protected function seedTypes(): void
    {
        $this->seed(SupportTicketTypeSeeder::class);
    }

    protected function mailbox(): FakeMailboxClient
    {
        return app(MailboxClient::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function mailboxRow(array $attributes = []): Mailbox
    {
        $n = ++$this->mailboxCounter;

        return Mailbox::create($attributes + [
            'name' => 'ProgrammArti',
            'address' => $n === 1 ? 'assistenza@gmail.com' : "casella{$n}@programmarti.it",
            'driver' => Mailbox::DRIVER_GMAIL,
            'imap_host' => 'imap.gmail.com',
            'imap_port' => 993,
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 465,
            'username' => 'assistenza@gmail.com',
            'password' => 'abcdabcdabcdabcd',
        ]);
    }

    protected function classifier(): FakeMessageClassifier
    {
        return app(MessageClassifier::class);
    }

    protected function drafter(): FakeReplyDrafter
    {
        return app(ReplyDrafter::class);
    }

    protected function agent(array $attributes = []): User
    {
        return User::factory()->ticketAgent()->create($attributes);
    }

    protected function admin(): User
    {
        return User::factory()->amministratore()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides  constructor argument names of InboundEmail
     */
    protected function email(array $overrides = []): InboundEmail
    {
        $n = ++$this->emailCounter;

        return new InboundEmail(...array_merge([
            'gmailMessageId' => (string) (1000000 + $n),
            'gmailThreadId' => (string) (5000000 + $n),
            'uid' => $n,
            'messageIdHeader' => "<msg{$n}@cliente.example>",
            'inReplyTo' => null,
            'references' => [],
            'fromEmail' => 'cliente@example.com',
            'fromName' => 'Mario Rossi',
            'to' => ['assistenza@gmail.com'],
            'subject' => 'Il sito non si apre',
            'textBody' => 'Buongiorno, da stamattina il sito mostra una pagina bianca.',
            'htmlBody' => null,
            'receivedAt' => CarbonImmutable::now(),
            'labels' => ['\\Inbox'],
            'headers' => [],
            'attachments' => [],
            'mailboxId' => $this->defaultMailbox->id,
        ], $overrides));
    }

    protected function ticketFor(string $email = 'cliente@example.com', array $attributes = []): SupportTicket
    {
        return SupportTicket::factory()->create(['customer_email' => $email, 'mailbox_id' => $this->defaultMailbox->id] + $attributes);
    }
}
