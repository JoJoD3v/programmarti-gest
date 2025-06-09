<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'user_id',
        'description',
        'expense_date',
        'category',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Expense categories
     */
    public static function getCategories(): array
    {
        return [
            'office_supplies' => 'Forniture Ufficio',
            'software' => 'Software',
            'hardware' => 'Hardware',
            'marketing' => 'Marketing',
            'travel' => 'Viaggi',
            'training' => 'Formazione',
            'utilities' => 'Utenze',
            'other' => 'Altro',
        ];
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('expense_date', $year)
                    ->whereMonth('expense_date', $month);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
