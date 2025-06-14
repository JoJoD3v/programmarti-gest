<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PreventivoItem extends Model
{
    use HasFactory;

    protected $table = 'preventivo_items';

    protected $fillable = [
        'preventivo_id',
        'description',
        'cost',
        'ai_enhanced_description',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function preventivo()
    {
        return $this->belongsTo(Preventivo::class, 'preventivo_id');
    }
}
