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
        'description',
        'total_amount',
        'vat_enabled',
        'vat_rate',
        'subtotal_amount',
        'vat_amount',
        'status',
        'ai_processed',
        'pdf_path',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'vat_enabled' => 'boolean',
        'vat_rate' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'ai_processed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'ai_processed' => false,
        'total_amount' => 0,
        'vat_enabled' => false,
        'vat_rate' => 22.00,
        'subtotal_amount' => 0,
        'vat_amount' => 0,
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
     * Calculate total amount from items with VAT
     */
    public function calculateTotal(): void
    {
        // Get fresh data from database
        $this->refresh();

        $subtotal = $this->items()->sum('cost');
        $this->subtotal_amount = $subtotal;

        // Log calculation details for debugging
        \Log::info('Preventivo calculateTotal', [
            'preventivo_id' => $this->id,
            'subtotal' => $subtotal,
            'vat_enabled' => $this->vat_enabled,
            'vat_rate' => $this->vat_rate,
            'items_count' => $this->items()->count()
        ]);

        if ($this->vat_enabled) {
            $this->vat_amount = $subtotal * ($this->vat_rate / 100);
            $this->total_amount = $subtotal + $this->vat_amount;

            \Log::info('VAT calculation applied', [
                'preventivo_id' => $this->id,
                'subtotal' => $subtotal,
                'vat_rate' => $this->vat_rate,
                'vat_amount' => $this->vat_amount,
                'total_amount' => $this->total_amount
            ]);
        } else {
            $this->vat_amount = 0;
            $this->total_amount = $subtotal;

            \Log::info('No VAT applied', [
                'preventivo_id' => $this->id,
                'subtotal' => $subtotal,
                'total_amount' => $this->total_amount
            ]);
        }

        $this->save();
    }

    /**
     * Relationships
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(PreventivoItem::class, 'preventivo_id');
    }
}
