<?php

namespace App\Models;

use App\Ticketing\Support\TicketNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

/**
 * A connected mailbox: credentials (encrypted) and IMAP sync state.
 * Driver "gmail" uses the X-GM-* extensions (threads, labels, trash) and optionally Google OAuth;
 * driver "imap" is any IMAP/SMTP server authenticated with username and password.
 */
class Mailbox extends Model
{
    public const DRIVER_GMAIL = 'gmail';

    public const DRIVER_IMAP = 'imap';

    protected $guarded = [];

    protected $hidden = ['password', 'oauth_refresh_token'];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'oauth_refresh_token' => 'encrypted',
            'oauth_connected_at' => 'datetime',
            'active' => 'boolean',
            'last_synced_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'last_verified_at' => 'datetime',
            'sent_today_date' => 'date',
        ];
    }

    /**
     * @param  Builder<Mailbox>  $query
     */
    public function scopeSyncable(Builder $query): void
    {
        $query->where('active', true)->whereNull('disconnected_at');
    }

    /**
     * @return HasMany<SupportTicket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function isGmail(): bool
    {
        return $this->driver === self::DRIVER_GMAIL;
    }

    public function isDisconnected(): bool
    {
        return $this->disconnected_at !== null;
    }

    /**
     * Rejected credentials: stop for good (repeated failed logins can get the account blocked).
     */
    public function disconnect(string $error): void
    {
        $this->forceFill(['disconnected_at' => now(), 'last_error' => $error])->save();

        TicketNotifier::admins(
            'mailbox',
            "La casella {$this->address} non è più collegata (credenziali rifiutate). Ricollegala da Assistenza email > Caselle.",
            route('support.mailboxes.edit', $this),
            onceKey: "mailbox-disconnected-{$this->id}",
        );
    }

    public function reconnect(): void
    {
        $this->forceFill(['disconnected_at' => null, 'consecutive_failures' => 0, 'last_error' => null])->save();

        TicketNotifier::clearAlert("mailbox-disconnected-{$this->id}");
    }

    /**
     * Google no longer accepts the link: forget it and carry on with the app password.
     */
    public function dropOAuth(string $reason): void
    {
        $this->forceFill(['oauth_refresh_token' => null, 'oauth_connected_at' => null])->save();

        TicketNotifier::admins(
            'mailbox',
            "Collegamento Google revocato per {$this->address}: la sincronizzazione è passata alla password per le app. Ricollega Google da Assistenza email > Caselle.",
            route('support.mailboxes.edit', $this),
            onceKey: "mailbox-oauth-revoked-{$this->id}",
        );

        Log::warning('Ticketing: collegamento Google revocato', ['mailbox' => $this->address, 'error' => $reason]);
    }

    /**
     * Google shows the app password in groups of four: strip whatever was pasted along.
     */
    public static function normalizePassword(string $password, string $driver): string
    {
        return $driver === self::DRIVER_GMAIL ? (preg_replace('/\s+/', '', $password) ?? $password) : $password;
    }
}
