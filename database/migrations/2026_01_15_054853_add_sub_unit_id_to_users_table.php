<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        // Only add the column if it doesn't exist
        if (!Schema::hasColumn('users', 'subunit_id')) {
            $table->unsignedBigInteger('subunit_id')->nullable(); // (Your original column definition)
        }
    });
}

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus foreign key dan kolomnya jika rollback
            $table->dropForeign(['subunit_id']);
            $table->dropColumn('subunit_id');
        });
    }
};