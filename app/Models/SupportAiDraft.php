<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportAiDraft extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['used' => 'boolean', 'pregenerated' => 'boolean', 'generated_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }
}
