<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Client;
use App\Models\User;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();
        $users = User::all();

        if ($clients->isEmpty() || $users->isEmpty()) {
            return;
        }

        $projects = [
            [
                'name' => 'Sito Web Aziendale Tech Solutions',
                'description' => 'Sviluppo di un sito web moderno e responsive per Tech Solutions SRL',
                'project_type' => 'website',
                'client_id' => $clients->where('email', 'info@techsolutions.it')->first()->id,
                'assigned_user_id' => $users->where('email', 'developer@programmarti.com')->first()->id,
                'payment_type' => 'installments',
                'total_cost' => 3500.00,
                'has_down_payment' => true,
                'down_payment_amount' => 1000.00,
                'payment_frequency' => 'monthly',
                'installment_amount' => 500.00,
                'installment_count' => 5,
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(60),
                'status' => 'in_progress',
            ],
            [
                'name' => 'E-commerce Restaurant Da Mario',
                'description' => 'Piattaforma e-commerce per ordinazioni online del ristorante',
                'project_type' => 'ecommerce',
                'client_id' => $clients->where('email', 'info@damario.it')->first()->id,
                'assigned_user_id' => $users->where('email', 'developer@programmarti.com')->first()->id,
                'payment_type' => 'one_time',
                'total_cost' => 2800.00,
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(45),
                'status' => 'planning',
            ],
            [
                'name' => 'Campagna Social Media Marco Rossi',
                'description' => 'Gestione social media e campagne pubblicitarie per 3 mesi',
                'project_type' => 'social_media_management',
                'client_id' => $clients->where('email', 'marco.rossi@email.com')->first()->id,
                'assigned_user_id' => $users->where('email', 'social@programmarti.com')->first()->id,
                'payment_type' => 'installments',
                'total_cost' => 1500.00,
                'payment_frequency' => 'monthly',
                'installment_amount' => 500.00,
                'installment_count' => 3,
                'start_date' => now()->subDays(10),
                'end_date' => now()->addDays(80),
                'status' => 'in_progress',
            ],
            [
                'name' => 'Sistema Gestionale Anna Verdi',
                'description' => 'Sviluppo sistema gestionale personalizzato per attività commerciale',
                'project_type' => 'management_system',
                'client_id' => $clients->where('email', 'anna.verdi@email.com')->first()->id,
                'assigned_user_id' => $users->where('email', 'developer@programmarti.com')->first()->id,
                'payment_type' => 'installments',
                'total_cost' => 5000.00,
                'has_down_payment' => true,
                'down_payment_amount' => 1500.00,
                'payment_frequency' => 'monthly',
                'installment_amount' => 700.00,
                'installment_count' => 5,
                'start_date' => now()->addDays(5),
                'end_date' => now()->addDays(120),
                'status' => 'planning',
            ],
        ];

        foreach ($projects as $projectData) {
            $project = Project::create($projectData);

            // Generate payments for projects with installments
            if ($project->payment_type === 'installments') {
                $project->generatePayments();
            }
        }
    }
}
