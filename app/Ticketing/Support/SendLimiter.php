<?php

namespace App\Ticketing\Support;

use App\Models\Mailbox;
use Illuminate\Support\Facades\DB;

/**
 * A personal Gmail account allows about 500 messages a day (hosting SMTP servers have similar caps): count them
 * per mailbox and stop before the provider blocks the account.
 */
class SendLimiter
{
    /**
     * Reserves one send for today. False when the daily budget is used up.
     */
    public function reserve(Mailbox $mailbox): bool
    {
        return DB::transaction(function () use ($mailbox) {
            $credential = Mailbox::query()->lockForUpdate()->find($mailbox->id);

            if ($credential === null) {
                return true; // unsaved mailbox: nothing to count against
            }

            if ($credential->sent_today_date?->toDateString() !== today()->toDateString()) {
                $credential->forceFill(['sent_today' => 0, 'sent_today_date' => today()])->save();
            }

            $limit = (int) config('ticketing.mailbox.daily_send_limit');

            if ($credential->sent_today >= $limit) {
                TicketNotifier::admins('send-limit', "Limite giornaliero di invio raggiunto per {$credential->address} ({$limit}): le risposte partiranno domani.", onceKey: "send-limit-{$credential->id}-".today()->toDateString());

                return false;
            }

            $credential->increment('sent_today');

            $warnAt = (int) floor($limit * (int) config('ticketing.alerts.send_limit_warning_percent') / 100);
            if ($credential->sent_today >= $warnAt) {
                TicketNotifier::admins('send-limit', "Invii di oggi da {$credential->address} all'80% del limite ({$credential->sent_today}/{$limit}).", onceKey: "send-warn-{$credential->id}-".today()->toDateString());
            }

            return true;
        });
    }
}
