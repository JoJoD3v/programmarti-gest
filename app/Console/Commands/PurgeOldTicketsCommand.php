<?php

namespace App\Console\Commands;

use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Ticketing\Support\InboundEmailStore;
use Illuminate\Console\Command;

/**
 * Optional retention: anonymizes bodies and deletes attachments of tickets closed for more than N months.
 * Disabled unless TICKETING_RETENTION_MONTHS is set.
 */
class PurgeOldTicketsCommand extends Command
{
    protected $signature = 'ticketing:purge';

    protected $description = 'Anonimizza i ticket chiusi da oltre il periodo di conservazione';

    public function handle(): int
    {
        $months = config('ticketing.retention_months');

        if ($months === null) {
            $this->info('Conservazione illimitata: nessuna azione.');

            return self::SUCCESS;
        }

        $count = 0;

        SupportTicket::query()
            ->where('status', 'closed')
            ->where('closed_at', '<', now()->subMonths((int) $months))
            ->whereNull('anonymized_at')
            ->each(function (SupportTicket $ticket) use (&$count) {
                foreach ($ticket->messages()->with('attachments')->get() as $message) {
                    $message->attachments->each(function (SupportTicketAttachment $attachment) {
                        if ($attachment->path) {
                            InboundEmailStore::disk()->delete($attachment->path);
                        }
                        $attachment->delete();
                    });

                    $message->forceFill(['body_text' => '[contenuto eliminato]', 'body_html_sanitized' => null, 'from_name' => null])->save();
                }

                $ticket->forceFill([
                    'customer_name' => null,
                    'customer_email' => 'anonimo-'.$ticket->id.'@anonimizzato.invalid',
                    'client_id' => null,
                    'ai_summary' => null,
                    'anonymized_at' => now(),
                ])->save();
                $count++;
            });

        $this->info("Ticket anonimizzati: {$count}");

        return self::SUCCESS;
    }
}
