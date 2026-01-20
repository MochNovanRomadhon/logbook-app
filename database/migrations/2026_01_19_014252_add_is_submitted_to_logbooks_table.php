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
    Schema::table('logbooks', function (Blueprint $table) {
        $table->boolean('is_submitted')->default(false); // 0 = Draft, 1 = Final
    });
}

public function down(): void
{
    Schema::table('logbooks', function (Blueprint $table) {
        $table->dropColumn('is_submitted');
    });
}
};
