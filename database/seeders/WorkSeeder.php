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
                'description' => 'Gli utenti non riescono ad accedere al sistema quando inseriscono credenziali valide. Il problema sembra essere legato alla validazione delle sessioni. Necessario verificare la configurazione del middleware di autenticazione e testare con diversi browser.',
                'type' => 'Bug',
                'assigned_user_id' => $users->where('email', 'developer@programmarti.com')->first()->id ?? $users->first()->id,
                'status' => 'Completato',
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(5),
            ],
            [
                'project_id' => $projects->first()->id,
                'name' => 'Implementazione sistema di notifiche',
                'description' => 'Sviluppare un sistema completo di notifiche in tempo reale per informare gli utenti di eventi importanti come nuovi messaggi, aggiornamenti di stato e scadenze. Utilizzare WebSocket per le notifiche push e implementare un sistema di preferenze utente.',
                'type' => 'Miglioramenti',
                'assigned_user_id' => $users->where('email', 'developer@programmarti.com')->first()->id ?? $users->first()->id,
                'status' => 'In Sospeso',
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(8),
            ],
            [
                'project_id' => $projects->skip(1)->first()->id ?? $projects->first()->id,
                'name' => 'Ottimizzazione performance database',
                'description' => 'Le query del database sono lente, specialmente quelle che coinvolgono tabelle con molti record. Analizzare gli indici esistenti, ottimizzare le query più frequenti e implementare strategie di caching per migliorare i tempi di risposta.',
                'type' => 'Miglioramenti',
                'assigned_user_id' => $users->where('email', 'developer@programmarti.com')->first()->id ?? $users->first()->id,
                'status' => 'In Sospeso',
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(6),
            ],
            [
                'project_id' => $projects->skip(1)->first()->id ?? $projects->first()->id,
                'name' => 'Aggiunta sezione FAQ',
                'description' => 'Creare una sezione FAQ completa per aiutare gli utenti a trovare risposte alle domande più comuni. La sezione deve essere facilmente navigabile, con funzionalità di ricerca e categorizzazione delle domande per argomento.',
                'type' => 'Da fare',
                'assigned_user_id' => $users->where('email', 'designer@programmarti.com')->first()->id ?? $users->last()->id,
                'status' => 'In Sospeso',
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],
            [
                'project_id' => $projects->skip(2)->first()->id ?? $projects->first()->id,
                'name' => 'Correzione layout responsive mobile',
                'description' => 'Il layout del sito non si adatta correttamente sui dispositivi mobili. Alcuni elementi si sovrappongono e i menu non sono accessibili. Necessario rivedere i CSS media queries e testare su diversi dispositivi.',
                'type' => 'Bug',
                'assigned_user_id' => $users->where('email', 'designer@programmarti.com')->first()->id ?? $users->last()->id,
                'status' => 'Completato',
                'created_at' => now()->subDays(12),
                'updated_at' => now()->subDays(7),
            ],
            [
                'project_id' => $projects->skip(2)->first()->id ?? $projects->first()->id,
                'name' => 'Integrazione sistema di pagamento',
                'description' => 'Implementare l\'integrazione con gateway di pagamento per permettere transazioni online sicure. Configurare Stripe/PayPal, gestire webhook per conferme di pagamento e implementare sistema di fatturazione automatica.',
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
