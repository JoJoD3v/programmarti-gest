<?php

namespace App\Models;

use App\Ticketing\Enums\CloseReason;
use App\Ticketing\Enums\MessageDirection;
use App\Ticketing\Enums\MessageType;
use App\Ticketing\Enums\TicketStatus;
use App\Ticketing\Enums\TypeSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'type_source' => TypeSource::class,
            'close_reason' => CloseReason::class,
            'ai_confidence' => 'float',
            'first_response_at' => 'datetime',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
            'closed_at' => 'datetime',
            'gmail_trashed_at' => 'datetime',
            'assigned_at' => 'datetime',
        ];
    }

    /**
     * Allocates the row, then derives the public id (T-1001…) from the primary key: unique under concurrency.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function openNew(array $attributes): self
    {
        $ticket = static::create($attributes + ['public_id' => 'tmp-'.Str::random(12)]);
        $ticket->forceFill(['public_id' => 'T-'.(1000 + $ticket->id)])->save();

        return $ticket;
    }

    /**
     * @return BelongsTo<Mailbox, $this>
     */
    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The mailbox replies leave from: the one the customer wrote to, otherwise the first active one
     * (tickets opened by hand, or whose mailbox was removed).
     */
    public function sendingMailbox(): ?Mailbox
    {
        $mailbox = $this->mailbox;

        if ($mailbox !== null && $mailbox->active) {
            return $mailbox;
        }

        return Mailbox::query()->where('active', true)->orderBy('id')->first();
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
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function related(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_ticket_id');
    }

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function previous(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_ticket_id');
    }

    /**
     * @return HasMany<SupportTicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }

    /**
     * @return HasMany<SupportTicketAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(SupportTicketAssignment::class);
    }

    /**
     * @return HasMany<SupportTicketTypeChange, $this>
     */
    public function typeChanges(): HasMany
    {
        return $this->hasMany(SupportTicketTypeChange::class);
    }

    /**
     * @return HasMany<SupportAiDraft, $this>
     */
    public function aiDrafts(): HasMany
    {
        return $this->hasMany(SupportAiDraft::class);
    }

    /**
     * @param  Builder<SupportTicket>  $query
     */
    public function scopeNotClosed(Builder $query): void
    {
        $query->where('status', '!=', TicketStatus::Closed->value);
    }

    /**
     * @param  Builder<SupportTicket>  $query
     */
    public function scopeAssignedTo(Builder $query, User $user): void
    {
        $query->where('assigned_user_id', $user->id);
    }

    /**
     * @param  Builder<SupportTicket>  $query
     */
    public function scopeUnassigned(Builder $query): void
    {
        $query->whereNull('assigned_user_id');
    }

    /**
     * @param  Builder<SupportTicket>  $query
     */
    public function scopeOfType(Builder $query, string $slug): void
    {
        $query->whereHas('type', fn (Builder $type) => $type->where('slug', $slug));
    }

    public function isClosed(): bool
    {
        return $this->status === TicketStatus::Closed;
    }

    public function lastInboundMessage(): ?SupportTicketMessage
    {
        return $this->messages()
            ->where('direction', MessageDirection::In->value)
            ->where('type', '!=', MessageType::System->value)
            ->latest('id')
            ->first();
    }

    public function lastMessage(): ?SupportTicketMessage
    {
        return $this->messages()->latest('id')->first();
    }

    public function hasHumanReply(): bool
    {
        return $this->messages()
            ->where('direction', MessageDirection::Out->value)
            ->where('type', MessageType::Reply->value)
            ->exists();
    }

    public function customerFirstName(): ?string
    {
        $name = trim((string) ($this->customer_name ?: $this->client?->first_name));

        return $name === '' ? null : Str::before($name, ' ');
    }
}
