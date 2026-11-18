<?php

namespace Database\Seeders;

use App\Models\SupportTicketType;
use Illuminate\Database\Seeder;

/**
 * Initial ticket types for a web agency. Idempotent: existing rows keep the name, description and flags the admin edited.
 * The slugs spam, notifica_automatica and altro are referenced by the code.
 */
class SupportTicketTypeSeeder extends Seeder
{
    /**
     * @return list<array{slug: string, name: string, color: string, assignable: bool, description: string}>
     */
    public static function types(): array
    {
        return [
            ['slug' => 'assistenza_tecnica', 'name' => 'Assistenza tecnica', 'color' => 'red', 'assignable' => true,
                'description' => 'Un sito, un gestionale o un\'app non funziona: errori, pagine bianche, malfunzionamenti, accessi bloccati.'],
            ['slug' => 'modifiche', 'name' => 'Modifiche e aggiornamenti', 'color' => 'blue', 'assignable' => true,
                'description' => 'Richieste di modifica su un progetto esistente: testi, immagini, nuove pagine o funzioni, aggiornamenti.'],
            ['slug' => 'preventivo', 'name' => 'Richiesta preventivo / nuovo progetto', 'color' => 'green', 'assignable' => true,
                'description' => 'Nuovi clienti o clienti esistenti che chiedono un preventivo o propongono un nuovo progetto.'],
            ['slug' => 'hosting_dominio', 'name' => 'Hosting, dominio ed email', 'color' => 'purple', 'assignable' => true,
                'description' => 'Rinnovi, DNS, caselle email, certificati SSL, spazio hosting, trasferimenti di dominio.'],
            ['slug' => 'social_marketing', 'name' => 'Social e marketing', 'color' => 'pink', 'assignable' => true,
                'description' => 'Gestione social, campagne pubblicitarie, contenuti, piani editoriali, statistiche.'],
            ['slug' => 'pagamento_fattura', 'name' => 'Pagamento / fattura', 'color' => 'indigo', 'assignable' => true,
                'description' => 'Pagamenti, rate, richieste di fattura o di ricevuta, solleciti.'],
            ['slug' => 'fornitore_commerciale', 'name' => 'Fornitori / proposte commerciali', 'color' => 'slate', 'assignable' => true,
                'description' => 'Fornitori, agenti o aziende che propongono prodotti, servizi o collaborazioni.'],
            ['slug' => 'notifica_automatica', 'name' => 'Notifica automatica / newsletter', 'color' => 'slate', 'assignable' => false,
                'description' => 'Messaggi generati da sistemi: newsletter, notifiche di servizi online, hosting, registrar, PayPal, avvisi automatici.'],
            ['slug' => 'spam', 'name' => 'Spam', 'color' => 'pink', 'assignable' => false,
                'description' => 'Pubblicità indesiderata, truffe, phishing e messaggi privi di senso.'],
            ['slug' => 'altro', 'name' => 'Altro', 'color' => 'slate', 'assignable' => true,
                'description' => 'Tutto ciò che non rientra nelle altre tipologie.'],
        ];
    }

    public function run(): void
    {
        foreach (self::types() as $sort => $type) {
            SupportTicketType::firstOrCreate(
                ['slug' => $type['slug']],
                $type + ['is_system' => true, 'active' => true, 'sort' => $sort],
            );
        }
    }
}
