<?php

namespace App\Ticketing\Support;

use App\Models\Mailbox;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;

/**
 * Snapshot of the module's health, for the admin page and `ticketing:health`.
 */
class TicketingHealth
{
    /**
     * @return list<array{key: string, label: string, ok: bool, detail: string}>
     */
    public function checks(): array
    {
        $checks = [];
        $mailboxes = Mailbox::query()->where('active', true)->orderBy('id')->get();
        $stale = (int) config('ticketing.alerts.sync_stale_minutes');
        $limit = (int) config('ticketing.mailbox.daily_send_limit');

        if ($mailboxes->isEmpty()) {
            $checks[] = $this->check('mailbox', 'Casella collegata', false, 'Nessuna casella collegata.');
        }

        foreach ($mailboxes as $mailbox) {
            $key = "mailbox-{$mailbox->id}";

            $checks[] = $this->check($key, "Casella {$mailbox->address}", ! $mailbox->isDisconnected(),
                $mailbox->isDisconnected() ? 'Disconnessa: '.$mailbox->last_error : 'Collegata');

            $checks[] = $this->check("{$key}-sync", "Ultima sincronizzazione {$mailbox->address}", $mailbox->last_synced_at !== null && $mailbox->last_synced_at->gt(now()->subMinutes($stale)),
                $mailbox->last_synced_at ? $mailbox->last_synced_at->diffForHumans() : 'Mai eseguita.');

            $checks[] = $this->check("{$key}-failures", "Errori consecutivi di sync {$mailbox->address}", $mailbox->consecutive_failures < 5,
                (string) $mailbox->consecutive_failures.($mailbox->last_error ? " — {$mailbox->last_error}" : ''));

            $sent = $mailbox->sent_today_date?->isToday() ? $mailbox->sent_today : 0;
            $checks[] = $this->check("{$key}-send", "Invii di oggi da {$mailbox->address}", $sent < $limit * 0.8, "{$sent}/{$limit}");
        }

        $failed = DB::table('failed_jobs')->count();
        $checks[] = $this->check('failed_jobs', 'Job falliti', $failed === 0, (string) $failed);

        $oldest = SupportTicket::query()->whereNull('support_ticket_type_id')->oldest()->first();
        $minutes = (int) config('ticketing.alerts.unclassified_minutes');
        $checks[] = $this->check('unclassified', 'Ticket "da classificare" più vecchio', $oldest === null || $oldest->created_at->gt(now()->subMinutes($minutes)),
            $oldest ? $oldest->public_id.' — '.$oldest->created_at->diffForHumans() : 'nessuno');

        $orphans = SupportTicket::query()->unassigned()->notClosed()
            ->whereHas('type', fn ($q) => $q->where('assignable', true))
            ->where('created_at', '<', now()->subMinutes((int) config('ticketing.alerts.unassigned_minutes')))->count();
        $checks[] = $this->check('unassigned', 'Ticket assegnabili senza assegnatario da oltre 1h', $orphans === 0, (string) $orphans);

        return $checks;
    }

    public function healthy(): bool
    {
        return collect($this->checks())->every(fn (array $check) => $check['ok']);
    }

    /**
     * @return array{key: string, label: string, ok: bool, detail: string}
     */
    private function check(string $key, string $label, bool $ok, string $detail): array
    {
        return compact('key', 'label', 'ok', 'detail');
    }
}
