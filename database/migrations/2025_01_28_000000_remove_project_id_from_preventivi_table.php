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
            // Drop foreign key constraint first
            $table->dropForeign(['project_id']);
            // Then drop the column
            $table->dropColumn('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preventivi', function (Blueprint $table) {
            // Re-add the column
            $table->foreignId('project_id')->after('client_id')->constrained()->onDelete('cascade');
        });
    }
};

