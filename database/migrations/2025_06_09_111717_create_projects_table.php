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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('project_type', [
                'website',
                'ecommerce',
                'management_system',
                'marketing_campaign',
                'social_media_management',
                'nfc_accessories'
            ]);
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('payment_type', ['one_time', 'installments'])->default('one_time');
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->boolean('has_down_payment')->default(false);
            $table->decimal('down_payment_amount', 10, 2)->nullable();
            $table->enum('payment_frequency', ['monthly', 'quarterly', 'yearly'])->nullable();
            $table->decimal('installment_amount', 10, 2)->nullable();
            $table->integer('installment_count')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['planning', 'in_progress', 'completed', 'cancelled'])->default('planning');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
