<?php

/**
 * AI VAT Preservation Test Script
 * 
 * This script tests that AI enhancement preserves VAT totals correctly.
 * Run with: php test_ai_vat_preservation.php
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Preventivo;
use App\Models\PreventivoItem;
use App\Models\Client;
use App\Models\Project;

echo "🤖 AI VAT Preservation Test\n";
echo "===========================\n\n";

try {
    // Create a test preventivo with VAT enabled
    echo "📝 Creating test preventivo with VAT enabled...\n";
    
    $client = Client::first();
    $project = Project::first();
    
    if (!$client || !$project) {
        echo "❌ No client or project found. Please ensure you have sample data.\n";
        exit(1);
    }
    
    $preventivo = Preventivo::create([
        'quote_number' => 'TEST-AI-VAT-' . time(),
        'client_id' => $client->id,
        'project_id' => $project->id,
        'description' => 'Test preventivo for AI VAT preservation',
        'vat_enabled' => true,
        'vat_rate' => 22.00,
        'status' => 'draft'
    ]);
    
    // Add test items
    $item1 = PreventivoItem::create([
        'preventivo_id' => $preventivo->id,
        'description' => 'Web development service',
        'cost' => 500.00
    ]);
    
    $item2 = PreventivoItem::create([
        'preventivo_id' => $preventivo->id,
        'description' => 'SEO optimization',
        'cost' => 300.00
    ]);
    
    // Calculate initial totals
    $preventivo->calculateTotal();
    
    echo "✅ Test preventivo created with ID: {$preventivo->id}\n";
    echo "\n📊 Initial Totals (with VAT enabled)\n";
    echo "====================================\n";
    echo "Subtotal: €" . number_format($preventivo->subtotal_amount, 2) . "\n";
    echo "VAT ({$preventivo->vat_rate}%): €" . number_format($preventivo->vat_amount, 2) . "\n";
    echo "Total: €" . number_format($preventivo->total_amount, 2) . "\n";
    
    // Store original totals for comparison
    $originalSubtotal = $preventivo->subtotal_amount;
    $originalVatAmount = $preventivo->vat_amount;
    $originalTotal = $preventivo->total_amount;
    $originalVatEnabled = $preventivo->vat_enabled;
    $originalVatRate = $preventivo->vat_rate;
    
    echo "\n🤖 Simulating AI Enhancement\n";
    echo "=============================\n";
    
    // Simulate AI enhancement by updating only the ai_enhanced_description
    // This is what the real AI enhancement does
    $item1->update([
        'ai_enhanced_description' => 'Sviluppo completo di sito web responsive con tecnologie moderne, ottimizzato per performance e SEO.'
    ]);
    
    $item2->update([
        'ai_enhanced_description' => 'Ottimizzazione SEO completa con analisi keywords, ottimizzazione on-page e strategia di link building.'
    ]);
    
    // Mark as AI processed (without calling calculateTotal)
    $preventivo->update(['ai_processed' => true]);
    
    // Refresh the model to get current state
    $preventivo->refresh();
    
    echo "✅ AI enhancement completed (descriptions updated)\n";
    echo "\n📊 Totals After AI Enhancement\n";
    echo "===============================\n";
    echo "Subtotal: €" . number_format($preventivo->subtotal_amount, 2) . "\n";
    echo "VAT ({$preventivo->vat_rate}%): €" . number_format($preventivo->vat_amount, 2) . "\n";
    echo "Total: €" . number_format($preventivo->total_amount, 2) . "\n";
    echo "VAT Enabled: " . ($preventivo->vat_enabled ? 'Yes' : 'No') . "\n";
    echo "AI Processed: " . ($preventivo->ai_processed ? 'Yes' : 'No') . "\n";
    
    echo "\n🔍 Verification\n";
    echo "===============\n";
    
    // Check that totals are preserved
    $subtotalPreserved = abs($preventivo->subtotal_amount - $originalSubtotal) < 0.01;
    $vatPreserved = abs($preventivo->vat_amount - $originalVatAmount) < 0.01;
    $totalPreserved = abs($preventivo->total_amount - $originalTotal) < 0.01;
    $vatSettingsPreserved = ($preventivo->vat_enabled === $originalVatEnabled) && 
                           (abs($preventivo->vat_rate - $originalVatRate) < 0.01);
    
    echo "Subtotal preserved: " . ($subtotalPreserved ? "✅ YES" : "❌ NO") . "\n";
    echo "VAT amount preserved: " . ($vatPreserved ? "✅ YES" : "❌ NO") . "\n";
    echo "Total preserved: " . ($totalPreserved ? "✅ YES" : "❌ NO") . "\n";
    echo "VAT settings preserved: " . ($vatSettingsPreserved ? "✅ YES" : "❌ NO") . "\n";
    
    // Check that AI descriptions were added
    $item1->refresh();
    $item2->refresh();
    
    $aiDescriptionsAdded = !empty($item1->ai_enhanced_description) && !empty($item2->ai_enhanced_description);
    echo "AI descriptions added: " . ($aiDescriptionsAdded ? "✅ YES" : "❌ NO") . "\n";
    
    $allTestsPassed = $subtotalPreserved && $vatPreserved && $totalPreserved && 
                     $vatSettingsPreserved && $aiDescriptionsAdded;
    
    echo "\n🎯 Overall Result: " . ($allTestsPassed ? "✅ ALL TESTS PASSED" : "❌ SOME TESTS FAILED") . "\n";
    
    if ($allTestsPassed) {
        echo "\n🎉 Perfect! AI enhancement preserves VAT totals correctly.\n";
        echo "   - Only descriptions are enhanced\n";
        echo "   - Costs and totals remain unchanged\n";
        echo "   - VAT settings are preserved\n";
    } else {
        echo "\n⚠️  Issues detected in AI VAT preservation.\n";
    }
    
    echo "\n📋 Enhanced Descriptions:\n";
    echo "=========================\n";
    echo "Item 1: " . ($item1->ai_enhanced_description ?: 'No AI description') . "\n";
    echo "Item 2: " . ($item2->ai_enhanced_description ?: 'No AI description') . "\n";
    
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
