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
    // Pastikan tabel logbooks induk sudah ada. Jika belum, buat juga.
    // Schema::create('logbooks', function (Blueprint $table) {
    //     $table->id();
    //     $table->foreignId('user_id');
    //     $table->date('date');
    //     $table->timestamps();
    // });

    Schema::create('logbook_items', function (Blueprint $table) {
        $table->id();
        // Menghubungkan ke logbook harian (Induk)
        $table->foreignId('logbook_id')->constrained('logbooks')->cascadeOnDelete();
        
        // Menghubungkan ke Task yang dikerjakan
        $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
        
        $table->integer('previous_progress')->default(0); // Progress sebelumnya
        $table->integer('current_progress'); // Progress hari ini
        $table->text('activity'); // Rincian apa yang dikerjakan
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logbook_items');
    }
};
