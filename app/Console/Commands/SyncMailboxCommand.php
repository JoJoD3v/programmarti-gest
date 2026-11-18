<?php

namespace App\Console\Commands;

use App\Models\Mailbox;
use App\Ticketing\Jobs\SyncMailboxJob;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SyncMailboxCommand extends Command
{
    protected $signature = 'ticketing:sync-mailbox
        {--mailbox= : Solo la casella con questo id}
        {--since= : Rilegge la casella dal giorno indicato (YYYY-MM-DD); i messaggi già importati vengono ignorati}';

    protected $description = 'Legge le nuove email dalle caselle collegate e le trasforma in ticket';

    public function handle(): int
    {
        $since = $this->option('since');

        if ($since !== null) {
            try {
                $days = (int) ceil(CarbonImmutable::parse($since)->startOfDay()->diffInDays(now(), true)) + 1;
            } catch (\Throwable) {
                $this->error('Data non valida: usa il formato YYYY-MM-DD.');

                return self::FAILURE;
            }

            config(['ticketing.mailbox.initial_days' => $days]);
        }

        $mailboxes = Mailbox::query()->syncable()
            ->when($this->option('mailbox'), fn ($query, $id) => $query->whereKey($id))
            ->pluck('id');

        // one mailbox failing never blocks the others: the job records the error on its row
        foreach ($mailboxes as $id) {
            SyncMailboxJob::dispatchSync($id, $since);
        }

        return self::SUCCESS;
    }
}
