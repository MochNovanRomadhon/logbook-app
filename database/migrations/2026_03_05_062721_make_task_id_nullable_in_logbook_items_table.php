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
        Schema::table('logbook_items', function (Blueprint $table) {
            // Drop the existing foreign key constraint first
            $table->dropForeign(['task_id']);
            
            // Make the column nullable
            $table->foreignId('task_id')->nullable()->change();
            
            // Re-add the foreign key constraint with nullable support
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logbook_items', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->foreignId('task_id')->nullable(false)->change();
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
        });
    }
};
