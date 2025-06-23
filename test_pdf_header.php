<?php

// Simple test script to verify PDF header functionality
require_once 'vendor/autoload.php';

use App\Models\Preventivo;
use Barryvdh\DomPDF\Facade\Pdf;

// Test if logo file exists
$logoPath = public_path('img/logo/LOGO.jpg');
echo "Logo path: " . $logoPath . "\n";
echo "Logo exists: " . (file_exists($logoPath) ? 'YES' : 'NO') . "\n";

// Test if we can load a preventivo
try {
    $preventivo = Preventivo::with(['client', 'project', 'items'])->first();
    if ($preventivo) {
        echo "Preventivo loaded: " . $preventivo->quote_number . "\n";
        echo "Client: " . $preventivo->client->full_name . "\n";
        echo "Items count: " . $preventivo->items->count() . "\n";
    } else {
        echo "No preventivo found\n";
    }
} catch (Exception $e) {
    echo "Error loading preventivo: " . $e->getMessage() . "\n";
}
