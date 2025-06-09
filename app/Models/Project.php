<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'project_type',
        'client_id',
        'assigned_user_id',
        'payment_type',
        'total_cost',
        'has_down_payment',
        'down_payment_amount',
        'payment_frequency',
        'installment_amount',
        'installment_count',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_cost' => 'decimal:2',
        'down_payment_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'has_down_payment' => 'boolean',
    ];

    /**
     * Project type options for forms
     */
    public static function getProjectTypes(): array
    {
        return [
            'website' => 'Sito Web',
            'ecommerce' => 'E-commerce',
            'management_system' => 'Sistema Gestionale',
            'marketing_campaign' => 'Campagna Marketing',
            'social_media_management' => 'Gestione Social Media',
            'nfc_accessories' => 'Accessori NFC',
        ];
    }

    /**
     * Payment frequency options
     */
    public static function getPaymentFrequencies(): array
    {
        return [
            'monthly' => 'Mensile',
            'quarterly' => 'Trimestrale',
            'yearly' => 'Annuale',
        ];
    }

    /**
     * Status options
     */
    public static function getStatuses(): array
    {
        return [
            'planning' => 'Pianificazione',
            'in_progress' => 'In Corso',
            'completed' => 'Completato',
            'cancelled' => 'Annullato',
        ];
    }

    /**
     * Relationships
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Generate payments for this project
     */
    public function generatePayments()
    {
        // Clear existing payments
        $this->payments()->delete();

        if ($this->payment_type === 'one_time') {
            Payment::create([
                'project_id' => $this->id,
                'client_id' => $this->client_id,
                'assigned_user_id' => $this->assigned_user_id,
                'amount' => $this->total_cost,
                'due_date' => $this->start_date,
                'payment_type' => 'one_time',
            ]);
        } else {
            // Generate down payment if applicable
            if ($this->has_down_payment && $this->down_payment_amount > 0) {
                Payment::create([
                    'project_id' => $this->id,
                    'client_id' => $this->client_id,
                    'assigned_user_id' => $this->assigned_user_id,
                    'amount' => $this->down_payment_amount,
                    'due_date' => $this->start_date,
                    'payment_type' => 'down_payment',
                ]);
            }

            // Generate installments
            if ($this->installment_count > 0 && $this->installment_amount > 0) {
                $startDate = $this->start_date;
                if ($this->has_down_payment) {
                    $startDate = $this->getNextPaymentDate($startDate);
                }

                for ($i = 1; $i <= $this->installment_count; $i++) {
                    Payment::create([
                        'project_id' => $this->id,
                        'client_id' => $this->client_id,
                        'assigned_user_id' => $this->assigned_user_id,
                        'amount' => $this->installment_amount,
                        'due_date' => $startDate,
                        'payment_type' => 'installment',
                        'installment_number' => $i,
                    ]);

                    $startDate = $this->getNextPaymentDate($startDate);
                }
            }
        }
    }

    /**
     * Get next payment date based on frequency
     */
    private function getNextPaymentDate(Carbon $currentDate): Carbon
    {
        return match ($this->payment_frequency) {
            'monthly' => $currentDate->copy()->addMonth(),
            'quarterly' => $currentDate->copy()->addMonths(3),
            'yearly' => $currentDate->copy()->addYear(),
            default => $currentDate->copy()->addMonth(),
        };
    }
}
