<?php

namespace App\Providers;

use App\Ticketing\Classification\AnthropicClassifier;
use App\Ticketing\Classification\TieredMessageClassifier;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\Contracts\MessageClassifier;
use App\Ticketing\Contracts\ReplyDrafter;
use App\Ticketing\Drafts\AnthropicReplyDrafter;
use App\Ticketing\Events\CustomerMessageReceived;
use App\Ticketing\Fakes\FakeMailboxClient;
use App\Ticketing\Fakes\FakeMessageClassifier;
use App\Ticketing\Fakes\FakeReplyDrafter;
use App\Ticketing\Listeners\QueueDraftPregeneration;
use App\Ticketing\Mailbox\MailboxRouter;
use App\Ticketing\Support\TicketingSettings;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * External services sit behind contracts: real implementations in production, in-memory fakes under test.
 */
class TicketingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(CustomerMessageReceived::class, QueueDraftPregeneration::class);
    }

    public function register(): void
    {
        $this->app->singleton(TicketingSettings::class);

        if ($this->app->environment('testing')) {
            $this->app->singleton(MailboxClient::class, FakeMailboxClient::class);
            $this->app->singleton(MessageClassifier::class, FakeMessageClassifier::class);
            $this->app->singleton(ReplyDrafter::class, FakeReplyDrafter::class);

            return;
        }

        $this->app->singleton(MailboxClient::class, MailboxRouter::class);
        $this->app->singleton(MessageClassifier::class, fn ($app) => $app->make(TieredMessageClassifier::class, [
            'llm' => $app->make(AnthropicClassifier::class),
        ]));
        $this->app->singleton(ReplyDrafter::class, AnthropicReplyDrafter::class);
    }
}
