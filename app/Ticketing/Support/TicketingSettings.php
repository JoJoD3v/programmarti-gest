<?php

namespace App\Ticketing\Support;

use App\Models\TicketingSetting;
use App\Models\TicketingSettingLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Runtime switches stored in the database (editable from "Impostazioni assistenza", no deploy needed).
 */
class TicketingSettings
{
    public const DEFAULTS = [
        'personal_saved_replies_enabled' => true,
        'drafts_enabled' => false,
        'drafts_audience' => 'admins', // admins | selected_users | all
        'drafts_selected_user_ids' => [],
        'drafts_pregenerate' => false,
        'drafts_tone_rules' => '',
        'reply_signature' => '',
        'llm_classifier_enabled' => true,
        'assignment_shares' => [], // user id => percentage; empty = least loaded
        'assignment_shares_since' => null,
        'gmail_trash_after_hours' => null, // null = closed tickets' threads are never trashed in Gmail
    ];

    private const CACHE_KEY = 'ticketing.settings';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => TicketingSetting::query()->pluck('value', 'key')->all());

        return array_replace(self::DEFAULTS, $stored);
    }

    public function get(string $key): mixed
    {
        return $this->all()[$key] ?? null;
    }

    public function bool(string $key): bool
    {
        return (bool) $this->get($key);
    }

    public function set(string $key, mixed $value, ?User $by = null): void
    {
        abort_unless(array_key_exists($key, self::DEFAULTS), 422, "Impostazione sconosciuta: {$key}");

        $old = $this->get($key);

        if ($old === $value) {
            return;
        }

        TicketingSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        TicketingSettingLog::create(['key' => $key, 'old_value' => $old, 'new_value' => $value, 'user_id' => $by?->id]);
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values, ?User $by = null): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $by);
        }
    }
}
