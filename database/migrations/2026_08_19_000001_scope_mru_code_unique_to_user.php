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
        Schema::table('mrus', function (Blueprint $table) {
            // Drop the global unique constraint on MRU code
            $table->dropUnique('mrus_code_unique');

            // Add user-scoped composite unique constraint: (user_id, code)
            $table->unique(['user_id', 'code'], 'mrus_user_id_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mrus', function (Blueprint $table) {
            $table->dropUnique('mrus_user_id_code_unique');
            $table->unique('code', 'mrus_code_unique');
        });
    }
};
