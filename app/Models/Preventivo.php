<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Preventivo extends Model
{
    use HasFactory;

    protected $table = 'preventivi';

    protected $fillable = [
        'quote_number',
        'client_id',
        'project_id',
        'description',
        'total_amount',
        'status',
        'ai_processed',
        'pdf_path',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'ai_processed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'ai_processed' => false,
        'total_amount' => 0,
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Status options for forms
     */
    public static function getStatuses(): array
    {
        return [
            'draft' => 'Bozza',
            'sent' => 'Inviato',
            'accepted' => 'Accettato',
            'rejected' => 'Rifiutato',
        ];
    }

    /**
     * Get status label in Italian
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? (string) $this->status ?? 'Sconosciuto';
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-gray-100 text-gray-800',
            'sent' => 'bg-blue-100 text-blue-800',
            'accepted' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Generate unique quote number
     */
    public static function generateQuoteNumber(): string
    {
        $year = date('Y');
        $lastQuote = self::where('quote_number', 'like', "PREV-{$year}-%")
                        ->orderBy('quote_number', 'desc')
                        ->first();

        if ($lastQuote) {
            $lastNumber = (int) substr($lastQuote->quote_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('PREV-%s-%04d', $year, $newNumber);
    }

    /**
     * Calculate total amount from items
     */
    public function calculateTotal(): void
    {
        $this->total_amount = $this->items()->sum('cost');
        $this->save();
    }

    /**
     * Relationships
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function items()
    {
        return $this->hasMany(PreventivoItem::class, 'preventivo_id');
    }
}
