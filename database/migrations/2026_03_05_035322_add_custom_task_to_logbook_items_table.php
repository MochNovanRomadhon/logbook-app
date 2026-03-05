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
            $table->foreignId('task_id')->nullable()->change();
            $table->integer('current_progress')->nullable()->change();
            $table->string('custom_task_name')->nullable()->after('task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logbook_items', function (Blueprint $table) {
            $table->foreignId('task_id')->nullable(false)->change();
            $table->integer('current_progress')->nullable(false)->change();
            $table->dropColumn('custom_task_name');
        });
    }
};
