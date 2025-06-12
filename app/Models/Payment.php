<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'client_id',
        'assigned_user_id',
        'amount',
        'due_date',
        'paid_date',
        'status',
        'payment_type',
        'installment_number',
        'notes',
        'invoice_number',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Status options
     */
    public static function getStatuses(): array
    {
        return [
            'pending' => 'In Attesa',
            'overdue' => 'Scaduto',
            'completed' => 'Completato',
        ];
    }

    /**
     * Payment type options
     */
    public static function getPaymentTypes(): array
    {
        return [
            'down_payment' => 'Acconto',
            'installment' => 'Rata',
            'one_time' => 'Pagamento Unico',
        ];
    }

    /**
     * Check if payment is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' &&
               $this->due_date->addDays(20)->isPast();
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'paid_date' => now(),
        ]);
    }

    /**
     * Generate invoice number
     */
    public function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');

        // Find the highest sequence number for the current month/year
        $lastInvoice = Payment::whereYear('created_at', $year)
                             ->whereMonth('created_at', now()->month)
                             ->whereNotNull('invoice_number')
                             ->orderBy('invoice_number', 'desc')
                             ->first();

        $sequence = 1;
        if ($lastInvoice && $lastInvoice->invoice_number) {
            // Extract sequence number from last invoice (format: INV-YYYYMM-XXXX)
            $parts = explode('-', $lastInvoice->invoice_number);
            if (count($parts) === 3) {
                $sequence = intval($parts[2]) + 1;
            }
        }

        return "INV-{$year}{$month}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                    ->where('due_date', '<', now()->subDays(20));
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('due_date', $year)
                    ->whereMonth('due_date', $month);
    }


}
