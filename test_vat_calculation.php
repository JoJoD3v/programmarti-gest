<?php

/**
 * VAT Calculation Test Script
 * 
 * This script tests the VAT calculation functionality in the preventivo system.
 * Run with: php test_vat_calculation.php
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Preventivo;
use App\Models\PreventivoItem;
use App\Models\Client;
use App\Models\Project;

echo "🧪 VAT Calculation Test\n";
echo "======================\n\n";

try {
    // Find or create a test preventivo
    $preventivo = Preventivo::where('quote_number', 'like', 'TEST-VAT-%')->first();
    
    if (!$preventivo) {
        echo "📝 Creating test preventivo...\n";
        
        $client = Client::first();
        $project = Project::first();
        
        if (!$client || !$project) {
            echo "❌ No client or project found. Please ensure you have sample data.\n";
            exit(1);
        }
        
        $preventivo = Preventivo::create([
            'quote_number' => 'TEST-VAT-' . time(),
            'client_id' => $client->id,
            'project_id' => $project->id,
            'description' => 'Test preventivo for VAT calculation',
            'vat_enabled' => true,
            'vat_rate' => 22.00,
            'status' => 'draft'
        ]);
        
        // Add test items
        PreventivoItem::create([
            'preventivo_id' => $preventivo->id,
            'description' => 'Test service 1',
            'cost' => 100.00
        ]);
        
        PreventivoItem::create([
            'preventivo_id' => $preventivo->id,
            'description' => 'Test service 2',
            'cost' => 200.00
        ]);
        
        echo "✅ Test preventivo created with ID: {$preventivo->id}\n";
    } else {
        echo "📋 Using existing test preventivo ID: {$preventivo->id}\n";
    }
    
    echo "\n📊 Testing VAT Calculation\n";
    echo "==========================\n";
    
    // Test 1: VAT Enabled
    echo "\n🔵 Test 1: VAT Enabled (22%)\n";
    $preventivo->update([
        'vat_enabled' => true,
        'vat_rate' => 22.00
    ]);
    
    echo "Before calculateTotal():\n";
    echo "  - VAT Enabled: " . ($preventivo->vat_enabled ? 'Yes' : 'No') . "\n";
    echo "  - VAT Rate: {$preventivo->vat_rate}%\n";
    echo "  - Subtotal: €" . number_format($preventivo->subtotal_amount, 2) . "\n";
    echo "  - VAT Amount: €" . number_format($preventivo->vat_amount, 2) . "\n";
    echo "  - Total: €" . number_format($preventivo->total_amount, 2) . "\n";
    
    $preventivo->calculateTotal();
    
    echo "After calculateTotal():\n";
    echo "  - Subtotal: €" . number_format($preventivo->subtotal_amount, 2) . "\n";
    echo "  - VAT Amount: €" . number_format($preventivo->vat_amount, 2) . "\n";
    echo "  - Total: €" . number_format($preventivo->total_amount, 2) . "\n";
    
    // Verify calculation
    $expectedSubtotal = $preventivo->items->sum('cost');
    $expectedVat = $expectedSubtotal * 0.22;
    $expectedTotal = $expectedSubtotal + $expectedVat;
    
    echo "Expected values:\n";
    echo "  - Subtotal: €" . number_format($expectedSubtotal, 2) . "\n";
    echo "  - VAT Amount: €" . number_format($expectedVat, 2) . "\n";
    echo "  - Total: €" . number_format($expectedTotal, 2) . "\n";
    
    $subtotalCorrect = abs($preventivo->subtotal_amount - $expectedSubtotal) < 0.01;
    $vatCorrect = abs($preventivo->vat_amount - $expectedVat) < 0.01;
    $totalCorrect = abs($preventivo->total_amount - $expectedTotal) < 0.01;
    
    echo "Results: " . ($subtotalCorrect && $vatCorrect && $totalCorrect ? "✅ PASSED" : "❌ FAILED") . "\n";
    
    // Test 2: VAT Disabled
    echo "\n🔴 Test 2: VAT Disabled\n";
    $preventivo->update([
        'vat_enabled' => false,
        'vat_rate' => 22.00
    ]);
    
    $preventivo->calculateTotal();
    
    echo "After calculateTotal() with VAT disabled:\n";
    echo "  - Subtotal: €" . number_format($preventivo->subtotal_amount, 2) . "\n";
    echo "  - VAT Amount: €" . number_format($preventivo->vat_amount, 2) . "\n";
    echo "  - Total: €" . number_format($preventivo->total_amount, 2) . "\n";
    
    $expectedVatDisabled = 0;
    $expectedTotalDisabled = $expectedSubtotal;
    
    $vatDisabledCorrect = abs($preventivo->vat_amount - $expectedVatDisabled) < 0.01;
    $totalDisabledCorrect = abs($preventivo->total_amount - $expectedTotalDisabled) < 0.01;
    
    echo "Results: " . ($vatDisabledCorrect && $totalDisabledCorrect ? "✅ PASSED" : "❌ FAILED") . "\n";
    
    echo "\n🎉 VAT Calculation Test Completed!\n";
    
    // Clean up test data
    echo "\n🧹 Cleaning up test data...\n";
    $preventivo->items()->delete();
    $preventivo->delete();
    echo "✅ Test data cleaned up.\n";
    
} catch (Exception $e) {
    echo "❌ Error during test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
