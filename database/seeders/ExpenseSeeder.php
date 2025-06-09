<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\User;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        $expenses = [
            [
                'amount' => 150.00,
                'user_id' => $users->where('email', 'developer@programmarti.com')->first()->id,
                'description' => 'Licenza software Adobe Creative Suite',
                'expense_date' => now()->subDays(5),
                'category' => 'software',
            ],
            [
                'amount' => 85.50,
                'user_id' => $users->where('email', 'admin@programmarti.com')->first()->id,
                'description' => 'Materiale per ufficio - carta, penne, cartucce',
                'expense_date' => now()->subDays(10),
                'category' => 'office_supplies',
            ],
            [
                'amount' => 45.00,
                'user_id' => $users->where('email', 'social@programmarti.com')->first()->id,
                'description' => 'Pranzo di lavoro con cliente',
                'expense_date' => now()->subDays(3),
                'category' => 'other',
            ],
            [
                'amount' => 299.99,
                'user_id' => $users->where('email', 'developer@programmarti.com')->first()->id,
                'description' => 'Corso online React Advanced',
                'expense_date' => now()->subDays(15),
                'category' => 'training',
            ],
            [
                'amount' => 120.00,
                'user_id' => $users->where('email', 'admin@programmarti.com')->first()->id,
                'description' => 'Bolletta elettricità ufficio',
                'expense_date' => now()->subDays(20),
                'category' => 'utilities',
            ],
        ];

        foreach ($expenses as $expenseData) {
            Expense::create($expenseData);
        }
    }
}
