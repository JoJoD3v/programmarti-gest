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
        Schema::create('preventivi', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number', 20)->unique()->comment('Formato PREV-YYYY-NNNN');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->text('description');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->boolean('vat_enabled')->default(false);
            $table->decimal('vat_rate', 5, 2)->default(22.00);
            $table->decimal('subtotal_amount', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected'])->default('draft');
            $table->boolean('ai_processed')->default(false);
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preventivi');
    }
};
