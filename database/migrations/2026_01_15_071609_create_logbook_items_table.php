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
    Schema::create('logbook_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('logbook_id')->constrained()->cascadeOnDelete(); // Terhubung ke logbook harian
        $table->foreignId('task_id')->constrained()->cascadeOnDelete();    // Terhubung ke Task
        $table->integer('previous_progress')->default(0); // Persen sebelumnya
        $table->integer('current_progress')->default(0);  // Persen sekarang
        $table->text('activity'); // Aktivitas spesifik untuk task ini
        $table->timestamps();
    });

    // Opsional: Hapus kolom activity lama di tabel logbooks jika ingin bersih
    // Schema::table('logbooks', function (Blueprint $table) {
    //     $table->dropColumn('activity');
    // });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logbook_items');
    }
};
