<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('preventivi', function (Blueprint $table) {
            $table->boolean('vat_enabled')->default(false)->after('total_amount');
            $table->decimal('vat_rate', 5, 2)->default(22.00)->after('vat_enabled');
            $table->decimal('subtotal_amount', 10, 2)->default(0)->after('vat_rate');
            $table->decimal('vat_amount', 10, 2)->default(0)->after('subtotal_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preventivi', function (Blueprint $table) {
            $table->dropColumn(['vat_enabled', 'vat_rate', 'subtotal_amount', 'vat_amount']);
        });
    }
};
