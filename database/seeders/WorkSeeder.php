<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Work;
use App\Models\Project;
use App\Models\User;

class WorkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();
        $users = User::all();

        if ($projects->isEmpty() || $users->isEmpty()) {
            return;
        }

        $works = [
            [
                'project_id' => $projects->first()->id,
                'name' => 'Correzione bug nel modulo di login',
                'type' => 'Bug',
                'assigned_user_id' => $users->where('email', 'developer@programmarti.com')->first()->id ?? $users->first()->id,
                'status' => 'Completato',
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(5),
            ],
            [
                'project_id' => $projects->first()->id,
                'name' => 'Implementazione sistema di notifiche',
                'type' => 'Miglioramenti',
                'assigned_user_id' => $users->where('email', 'developer@programmarti.com')->first()->id ?? $users->first()->id,
                'status' => 'In Sospeso',
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(8),
            ],
            [
                'project_id' => $projects->skip(1)->first()->id ?? $projects->first()->id,
                'name' => 'Ottimizzazione performance database',
                'type' => 'Miglioramenti',
                'assigned_user_id' => $users->where('email', 'developer@programmarti.com')->first()->id ?? $users->first()->id,
                'status' => 'In Sospeso',
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(6),
            ],
            [
                'project_id' => $projects->skip(1)->first()->id ?? $projects->first()->id,
                'name' => 'Aggiunta sezione FAQ',
                'type' => 'Da fare',
                'assigned_user_id' => $users->where('email', 'designer@programmarti.com')->first()->id ?? $users->last()->id,
                'status' => 'In Sospeso',
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],
            [
                'project_id' => $projects->skip(2)->first()->id ?? $projects->first()->id,
                'name' => 'Correzione layout responsive mobile',
                'type' => 'Bug',
                'assigned_user_id' => $users->where('email', 'designer@programmarti.com')->first()->id ?? $users->last()->id,
                'status' => 'Completato',
                'created_at' => now()->subDays(12),
                'updated_at' => now()->subDays(7),
            ],
            [
                'project_id' => $projects->skip(2)->first()->id ?? $projects->first()->id,
                'name' => 'Integrazione sistema di pagamento',
                'type' => 'Da fare',
                'assigned_user_id' => $users->where('email', 'developer@programmarti.com')->first()->id ?? $users->first()->id,
                'status' => 'In Sospeso',
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
        ];

        foreach ($works as $workData) {
            Work::create($workData);
        }
    }
}
