<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportKnowledgeEntry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['ticket_type_ids' => 'array', 'active' => 'boolean'];
    }

    public function appliesTo(?int $ticketTypeId): bool
    {
        $ids = $this->ticket_type_ids ?? [];

        return $ids === [] || ($ticketTypeId !== null && in_array($ticketTypeId, array_map('intval', $ids), true));
    }
}
