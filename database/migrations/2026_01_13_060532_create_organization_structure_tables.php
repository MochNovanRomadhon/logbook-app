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
    // Tabel Direktorat
    Schema::create('directorates', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    // Tabel Unit (Anak dari Direktorat)
    Schema::create('units', function (Blueprint $table) {
        $table->id();
        $table->foreignId('directorate_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    // Tabel Subunit (Anak dari Unit)
    Schema::create('subunits', function (Blueprint $table) {
        $table->id();
        $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_structure_tables');
    }
};
