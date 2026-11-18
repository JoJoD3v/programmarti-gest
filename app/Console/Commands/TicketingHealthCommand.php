<?php

namespace App\Console\Commands;

use App\Ticketing\Support\TicketingHealth;
use App\Ticketing\Support\TicketNotifier;
use Illuminate\Console\Command;

class TicketingHealthCommand extends Command
{
    protected $signature = 'ticketing:health {--alert : Avvisa gli admin (una volta per problema) se qualcosa non va}';

    protected $description = 'Controlla lo stato del modulo assistenza email';

    public function handle(TicketingHealth $health): int
    {
        $failing = 0;

        foreach ($health->checks() as $check) {
            $this->line(sprintf('[%s] %s: %s', $check['ok'] ? ' OK ' : 'KO', $check['label'], $check['detail']));

            if (! $check['ok']) {
                $failing++;

                if ($this->option('alert')) {
                    TicketNotifier::admins('health', "Assistenza email: {$check['label']} — {$check['detail']}", route('support.health'), onceKey: 'health-'.$check['key']);
                }
            } elseif ($this->option('alert')) {
                TicketNotifier::clearAlert('health-'.$check['key']);
            }
        }

        return $failing === 0 ? self::SUCCESS : self::FAILURE;
    }
}
