<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Assistenza email: richiede il cron "schedule:run" ogni minuto e un worker della coda (QUEUE_CONNECTION=database)
// ogni 5 minuti: meno login IMAP, meno esposti ai controlli di sicurezza dei provider
Schedule::command('ticketing:sync-mailbox')->everyFiveMinutes()->withoutOverlapping(10);
Schedule::command('ticketing:retry-pending')->everyTenMinutes()->withoutOverlapping();
Schedule::command('ticketing:auto-close')->hourly()->withoutOverlapping();
Schedule::command('ticketing:health --alert')->everyFifteenMinutes();
Schedule::command('ticketing:purge')->dailyAt('03:30');
Schedule::command('ticketing:trash-closed-threads')->hourly()->withoutOverlapping();
