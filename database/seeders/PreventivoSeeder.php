<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Preventivo;
use App\Models\PreventivoItem;
use App\Models\Client;
use App\Models\Project;

class PreventivoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();
        $projects = Project::all();

        if ($clients->isEmpty() || $projects->isEmpty()) {
            $this->command->warn('No clients or projects found. Please run ClientSeeder and ProjectSeeder first.');
            return;
        }

        // Create sample preventivi
        $preventivi = [
            [
                'client_id' => $clients->first()->id,
                'project_id' => $projects->first()->id,
                'description' => 'Sviluppo completo di un sito web aziendale con sistema di gestione contenuti, ottimizzazione SEO e integrazione social media.',
                'status' => 'draft',
                'items' => [
                    ['description' => 'Analisi e progettazione UX/UI', 'cost' => 800.00],
                    ['description' => 'Sviluppo frontend responsive', 'cost' => 1200.00],
                    ['description' => 'Sviluppo backend e CMS', 'cost' => 1500.00],
                    ['description' => 'Ottimizzazione SEO', 'cost' => 400.00],
                    ['description' => 'Testing e deployment', 'cost' => 300.00],
                ]
            ],
            [
                'client_id' => $clients->skip(1)->first()->id ?? $clients->first()->id,
                'project_id' => $projects->skip(1)->first()->id ?? $projects->first()->id,
                'description' => 'Creazione di un e-commerce completo con sistema di pagamento integrato, gestione inventario e dashboard amministrativa.',
                'status' => 'sent',
                'items' => [
                    ['description' => 'Setup piattaforma e-commerce', 'cost' => 1000.00],
                    ['description' => 'Integrazione gateway pagamenti', 'cost' => 600.00],
                    ['description' => 'Sistema gestione inventario', 'cost' => 800.00],
                    ['description' => 'Dashboard amministrativa', 'cost' => 700.00],
                    ['description' => 'Formazione e supporto', 'cost' => 200.00],
                ]
            ],
            [
                'client_id' => $clients->skip(2)->first()->id ?? $clients->first()->id,
                'project_id' => $projects->skip(2)->first()->id ?? $projects->first()->id,
                'description' => 'Sviluppo di un sistema gestionale personalizzato per la gestione clienti, progetti e fatturazione.',
                'status' => 'accepted',
                'items' => [
                    ['description' => 'Modulo gestione clienti', 'cost' => 900.00],
                    ['description' => 'Modulo gestione progetti', 'cost' => 1100.00],
                    ['description' => 'Sistema fatturazione automatica', 'cost' => 800.00],
                    ['description' => 'Dashboard e reportistica', 'cost' => 600.00],
                    ['description' => 'Migrazione dati esistenti', 'cost' => 400.00],
                ]
            ],
        ];

        foreach ($preventivi as $preventivoData) {
            $items = $preventivoData['items'];
            unset($preventivoData['items']);

            // Calculate total amount
            $totalAmount = collect($items)->sum('cost');

            $preventivo = Preventivo::create([
                'quote_number' => Preventivo::generateQuoteNumber(),
                'client_id' => $preventivoData['client_id'],
                'project_id' => $preventivoData['project_id'],
                'description' => $preventivoData['description'],
                'status' => $preventivoData['status'],
                'total_amount' => $totalAmount,
                'ai_processed' => false,
            ]);

            // Create items
            foreach ($items as $item) {
                PreventivoItem::create([
                    'preventivo_id' => $preventivo->id,
                    'description' => $item['description'],
                    'cost' => $item['cost'],
                ]);
            }

            $this->command->info("Created preventivo: {$preventivo->quote_number}");
        }

        $this->command->info('Preventivo seeding completed!');
    }
}
