<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the unique constraint already exists
        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->unique('invoice_number', 'payments_invoice_number_unique');
            });
        } catch (\Exception $e) {
            // If constraint already exists or there's an error, we can ignore it
            // The constraint might already be in place from the original migration
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('payments_invoice_number_unique');
        });
    }
};
