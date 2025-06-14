<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Preventivo;
use App\Models\PreventivoItem;
use App\Models\Client;
use App\Models\Project;

class TestPreventivo extends Command
{
    protected $signature = 'test:preventivo';
    protected $description = 'Test preventivo creation';

    public function handle()
    {
        $client = Client::first();
        $project = Project::first();

        if (!$client || !$project) {
            $this->error('No clients or projects found');
            return;
        }

        try {
            $preventivo = Preventivo::create([
                'quote_number' => Preventivo::generateQuoteNumber(),
                'client_id' => $client->id,
                'project_id' => $project->id,
                'description' => 'Test preventivo',
                'total_amount' => 1000.00,
                'status' => 'draft',
            ]);

            $this->info("Preventivo created: {$preventivo->quote_number}");

            $item = PreventivoItem::create([
                'preventivo_id' => $preventivo->id,
                'description' => 'Test item',
                'cost' => 1000.00,
            ]);

            $this->info("Item created: {$item->description}");
            $this->info('Test completed successfully!');

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
