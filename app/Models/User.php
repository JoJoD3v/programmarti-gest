<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'profile_photo',
        'is_active',
        'can_manage_tickets',
        'ticket_available',
    ];

    /**
     * Same defaults as the database, so a freshly created model is already usable by the ticketing checks.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'can_manage_tickets' => false,
        'ticket_available' => true,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'can_manage_tickets' => 'boolean',
            'ticket_available' => 'boolean',
            'last_ticket_assigned_at' => 'datetime',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Display name (used by the ticketing pages).
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * Ticketing configuration (mailboxes, types, rules, assignments) is for whoever can manage users.
     */
    public function isTicketingAdmin(): bool
    {
        return $this->is_active && $this->can('manage users');
    }

    /**
     * Enabled to receive and answer support tickets.
     */
    public function isTicketAgent(): bool
    {
        return $this->is_active && $this->can_manage_tickets;
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Active users enabled to handle support tickets (receive and answer them).
     *
     * @param  Builder<User>  $query
     */
    public function scopeTicketingEnabled(Builder $query): void
    {
        $query->where('is_active', true)->where('can_manage_tickets', true);
    }

    /**
     * Enabled users who are not temporarily away (holidays, sick leave): candidates for automatic assignment.
     *
     * @param  Builder<User>  $query
     */
    public function scopeTicketingAvailable(Builder $query): void
    {
        $query->ticketingEnabled()->where('ticket_available', true);
    }

    /**
     * @return HasMany<SupportTicket, $this>
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_user_id');
    }

    /**
     * Get the user's profile photo URL.
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_photo
            ? asset('storage/'.$this->profile_photo)
            : asset('images/default-avatar.png');
    }

    /**
     * Relationships
     */
    public function projects()
    {
        return $this->hasMany(Project::class, 'assigned_user_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'assigned_user_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function works()
    {
        return $this->hasMany(Work::class, 'assigned_user_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
