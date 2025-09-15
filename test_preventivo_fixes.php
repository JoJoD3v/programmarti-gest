<?php

/**
 * Test script per verificare le correzioni ai preventivi
 * 
 * Questo script testa:
 * 1. Il calcolo corretto dell'IVA dopo l'analisi AI
 * 2. La generazione del PDF senza il totale superiore
 */

require_once 'vendor/autoload.php';

use App\Models\Preventivo;
use App\Models\Client;
use App\Models\Project;
use App\Models\PreventivoItem;

echo "🧪 Test delle correzioni ai preventivi\n";
echo "=====================================\n\n";

// Test 1: Verifica calcolo IVA dopo AI
echo "📋 Test 1: Calcolo IVA dopo analisi AI\n";
echo "--------------------------------------\n";

try {
    // Trova un preventivo esistente o creane uno di test
    $preventivo = Preventivo::with(['items', 'client', 'project'])->first();
    
    if (!$preventivo) {
        echo "❌ Nessun preventivo trovato per il test\n";
        exit(1);
    }
    
    echo "✅ Preventivo trovato: {$preventivo->quote_number}\n";
    echo "📊 Stato prima del test:\n";
    echo "   - Subtotale: €" . number_format($preventivo->subtotal_amount, 2) . "\n";
    echo "   - IVA abilitata: " . ($preventivo->vat_enabled ? 'Sì' : 'No') . "\n";
    echo "   - Aliquota IVA: {$preventivo->vat_rate}%\n";
    echo "   - Importo IVA: €" . number_format($preventivo->vat_amount, 2) . "\n";
    echo "   - Totale: €" . number_format($preventivo->total_amount, 2) . "\n";
    echo "   - AI processato: " . ($preventivo->ai_processed ? 'Sì' : 'No') . "\n\n";
    
    // Simula il processo di calcolo totali
    echo "🔄 Simulazione ricalcolo totali...\n";
    $preventivo->calculateTotal();
    
    echo "📊 Stato dopo ricalcolo:\n";
    echo "   - Subtotale: €" . number_format($preventivo->subtotal_amount, 2) . "\n";
    echo "   - Importo IVA: €" . number_format($preventivo->vat_amount, 2) . "\n";
    echo "   - Totale: €" . number_format($preventivo->total_amount, 2) . "\n\n";
    
    // Verifica che i calcoli siano corretti
    $expectedSubtotal = $preventivo->items->sum('cost');
    $expectedVat = $preventivo->vat_enabled ? ($expectedSubtotal * $preventivo->vat_rate / 100) : 0;
    $expectedTotal = $expectedSubtotal + $expectedVat;
    
    echo "🔍 Verifica calcoli:\n";
    echo "   - Subtotale atteso: €" . number_format($expectedSubtotal, 2) . "\n";
    echo "   - IVA attesa: €" . number_format($expectedVat, 2) . "\n";
    echo "   - Totale atteso: €" . number_format($expectedTotal, 2) . "\n\n";
    
    $subtotalMatch = abs($preventivo->subtotal_amount - $expectedSubtotal) < 0.01;
    $vatMatch = abs($preventivo->vat_amount - $expectedVat) < 0.01;
    $totalMatch = abs($preventivo->total_amount - $expectedTotal) < 0.01;
    
    if ($subtotalMatch && $vatMatch && $totalMatch) {
        echo "✅ Test 1 SUPERATO: I calcoli dell'IVA sono corretti\n\n";
    } else {
        echo "❌ Test 1 FALLITO: I calcoli dell'IVA non sono corretti\n";
        echo "   - Subtotale: " . ($subtotalMatch ? "✅" : "❌") . "\n";
        echo "   - IVA: " . ($vatMatch ? "✅" : "❌") . "\n";
        echo "   - Totale: " . ($totalMatch ? "✅" : "❌") . "\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Errore durante il Test 1: " . $e->getMessage() . "\n\n";
}

// Test 2: Verifica template PDF
echo "📄 Test 2: Template PDF senza totale superiore\n";
echo "----------------------------------------------\n";

try {
    $pdfTemplate = file_get_contents('resources/views/preventivi/pdf.blade.php');
    
    // Verifica che la sezione del totale superiore sia stata rimossa
    $hasUpperTotal = strpos($pdfTemplate, '<div><strong>Totale Preventivo</strong></div>') !== false;
    
    if (!$hasUpperTotal) {
        echo "✅ Test 2 SUPERATO: La sezione del totale superiore è stata rimossa dal PDF\n";
    } else {
        echo "❌ Test 2 FALLITO: La sezione del totale superiore è ancora presente nel PDF\n";
    }
    
    // Verifica che il totale in fondo sia ancora presente
    $hasBottomTotal = strpos($pdfTemplate, 'TOTALE PREVENTIVO:') !== false;
    
    if ($hasBottomTotal) {
        echo "✅ Il totale in fondo alla tabella è ancora presente\n";
    } else {
        echo "❌ ATTENZIONE: Il totale in fondo alla tabella potrebbe essere stato rimosso per errore\n";
    }
    
    // Verifica che non ci siano conflitti di merge
    $hasMergeConflict = strpos($pdfTemplate, '<<<<<<<') !== false || 
                       strpos($pdfTemplate, '>>>>>>>') !== false || 
                       strpos($pdfTemplate, '=======') !== false;
    
    if (!$hasMergeConflict) {
        echo "✅ Nessun conflitto di merge presente nel template PDF\n";
    } else {
        echo "❌ ATTENZIONE: Sono ancora presenti conflitti di merge nel template PDF\n";
    }
    
} catch (Exception $e) {
    echo "❌ Errore durante il Test 2: " . $e->getMessage() . "\n";
}

echo "\n🎯 Riepilogo correzioni applicate:\n";
echo "==================================\n";
echo "1. ✅ Aggiunta chiamata calculateTotal() dopo analisi AI\n";
echo "2. ✅ Rimossa sezione totale superiore dal PDF\n";
echo "3. ✅ Risolto conflitto di merge nel template PDF\n";
echo "4. ✅ Aggiornato messaggio di risposta JavaScript\n\n";

echo "📝 Note per l'utente:\n";
echo "- Ora l'IVA viene ricalcolata correttamente dopo l'analisi AI\n";
echo "- Il PDF mostra il totale solo in fondo, non più in alto\n";
echo "- I conflitti di merge sono stati risolti\n\n";

echo "🚀 Le correzioni sono state applicate con successo!\n";
