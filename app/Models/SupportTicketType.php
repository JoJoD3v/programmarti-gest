<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicketType extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'assignable' => 'boolean',
            'is_system' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<SupportTicketType>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * @return HasMany<SupportTicket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function badgeClass(): string
    {
        return match ($this->color) {
            'red' => 'bg-red-100 text-red-800',
            'amber' => 'bg-amber-100 text-amber-800',
            'green' => 'bg-green-100 text-green-800',
            'blue' => 'bg-blue-100 text-blue-800',
            'indigo' => 'bg-indigo-100 text-indigo-800',
            'purple' => 'bg-purple-100 text-purple-800',
            'pink' => 'bg-pink-100 text-pink-800',
            default => 'bg-slate-100 text-slate-700',
        };
    }
}
