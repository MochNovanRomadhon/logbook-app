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
            // Ubah task_id menjadi nullable agar bisa menyimpan "Pekerjaan Lainnya"
            $table->foreignId('task_id')->nullable()->change();
            // Ubah current_progress menjadi nullable
            $table->integer('current_progress')->nullable()->change();
            // Tambahkan kolom untuk judul pekerjaan lainnya (jika belum ada)
            if (!Schema::hasColumn('logbook_items', 'custom_task_name')) {
                $table->string('custom_task_name')->nullable()->after('task_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logbook_items', function (Blueprint $table) {
            $table->dropColumn('custom_task_name');
            $table->foreignId('task_id')->nullable(false)->change();
            $table->integer('current_progress')->nullable(false)->change();
        });
    }
};
