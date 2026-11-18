<?php

namespace App\Console\Commands;

use App\Models\Mailbox;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\DTO\SyncCursor;
use App\Ticketing\Jobs\ProcessInboundMessageJob;
use App\Ticketing\Support\InboundEmailStore;
use Illuminate\Console\Command;

class ReimportMessageCommand extends Command
{
    protected $signature = 'ticketing:reimport {gmail_message_id : Id Gmail (X-GM-MSGID) del messaggio} {--since=7 : Giorni indietro in cui cercarlo}';

    protected $description = 'Ripesca un\'email scartata per errore (label gestionale/scartata) e la rielabora';

    public function handle(MailboxClient $client, InboundEmailStore $store): int
    {
        config(['ticketing.mailbox.initial_days' => (int) $this->option('since')]);

        $email = null;

        foreach (Mailbox::query()->where('driver', Mailbox::DRIVER_GMAIL)->syncable()->get() as $mailbox) {
            $delta = $client->fetchNew($mailbox, new SyncCursor, 500);
            $email = collect($delta->messages)->firstWhere('gmailMessageId', $this->argument('gmail_message_id'));

            if ($email !== null) {
                break;
            }
        }

        if ($email === null) {
            $this->error('Messaggio non trovato negli ultimi giorni indicati.');

            return self::FAILURE;
        }

        ProcessInboundMessageJob::dispatchSync($store->stash($email));
        $this->info('Messaggio rielaborato.');

        return self::SUCCESS;
    }
}
