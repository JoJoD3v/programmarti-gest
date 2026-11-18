<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportSenderRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /**
     * @return BelongsTo<SupportTicketType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(SupportTicketType::class, 'support_ticket_type_id');
    }

    /**
     * @param  Builder<SupportSenderRule>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * Exact address (mario@x.it) or whole domain (@x.it).
     */
    public function matches(string $email): bool
    {
        $email = mb_strtolower(trim($email));
        $pattern = mb_strtolower(trim($this->pattern));

        if (str_starts_with($pattern, '@')) {
            return str_ends_with($email, $pattern);
        }

        return $email === $pattern;
    }
}
