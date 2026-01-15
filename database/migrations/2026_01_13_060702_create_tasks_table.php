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
    Schema::create('tasks', function (Blueprint $table) {
        $table->id();
        // Pemilik Task
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        
        // Pemberi Task (Jika null, berarti task inisiatif sendiri)
        $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
        
        $table->string('title');
        $table->text('description')->nullable();
        $table->text('notes')->nullable(); // Catatan harian user
        
        // Status & Urgensi
        $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
        $table->tinyInteger('urgency')->default(1); // Skala 1-5
        
        // Waktu
        $table->date('deadline')->nullable();
        $table->dateTime('completed_at')->nullable();
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
