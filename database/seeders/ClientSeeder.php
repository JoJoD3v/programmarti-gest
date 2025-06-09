<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            [
                'first_name' => 'Marco',
                'last_name' => 'Rossi',
                'email' => 'marco.rossi@email.com',
                'phone' => '+39 333 1234567',
                'entity_type' => 'individual',
                'tax_code' => 'RSSMRC85M01H501Z',
                'address' => 'Via Roma 123, 00100 Roma (RM)',
            ],
            [
                'first_name' => 'Giulia',
                'last_name' => 'Bianchi',
                'email' => 'giulia.bianchi@email.com',
                'phone' => '+39 347 9876543',
                'entity_type' => 'individual',
                'tax_code' => 'BNCGLI90A41F205X',
                'address' => 'Corso Milano 45, 20100 Milano (MI)',
            ],
            [
                'first_name' => 'Tech',
                'last_name' => 'Solutions SRL',
                'email' => 'info@techsolutions.it',
                'phone' => '+39 02 12345678',
                'entity_type' => 'business',
                'vat_number' => '12345678901',
                'address' => 'Via Innovazione 10, 20100 Milano (MI)',
            ],
            [
                'first_name' => 'Restaurant',
                'last_name' => 'Da Mario',
                'email' => 'info@damario.it',
                'phone' => '+39 06 87654321',
                'entity_type' => 'business',
                'vat_number' => '98765432109',
                'address' => 'Piazza del Gusto 5, 00100 Roma (RM)',
            ],
            [
                'first_name' => 'Anna',
                'last_name' => 'Verdi',
                'email' => 'anna.verdi@email.com',
                'phone' => '+39 340 5555555',
                'entity_type' => 'individual',
                'tax_code' => 'VRDNNA88D52L219Y',
                'address' => 'Via Napoli 78, 80100 Napoli (NA)',
            ],
        ];

        foreach ($clients as $clientData) {
            Client::create($clientData);
        }
    }
}
