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
    Schema::table('users', function (Blueprint $table) {
        // Relasi ke Organisasi (Nullable karena Admin mungkin tidak punya unit spesifik)
        $table->foreignId('directorate_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('subunit_id')->nullable()->constrained()->nullOnDelete();
        
        // Status Akun
        $table->boolean('is_active')->default(true);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
