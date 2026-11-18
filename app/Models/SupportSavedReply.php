<?php

namespace App\Models;

use App\Ticketing\Enums\SavedReplyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportSavedReply extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'scope' => SavedReplyScope::class,
            'active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SupportTicketType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(SupportTicketType::class, 'support_ticket_type_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Replies a user may insert: every global one, plus their own personal ones when the setting allows it.
     *
     * @param  Builder<SupportSavedReply>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user, bool $personalEnabled): void
    {
        $query->where('active', true)->where(function (Builder $sub) use ($user, $personalEnabled) {
            $sub->where('scope', SavedReplyScope::Global->value);

            if ($personalEnabled) {
                $sub->orWhere(fn (Builder $own) => $own->where('scope', SavedReplyScope::Personal->value)->where('owner_user_id', $user->id));
            }
        });
    }
}
