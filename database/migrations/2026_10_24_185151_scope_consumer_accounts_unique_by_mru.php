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
        Schema::table('consumer_accounts', function (Blueprint $table) {
            // Drop old user-only unique constraint to allow a consumer to belong to multiple MRUs for the same user
            $table->dropUnique(['user_id', 'ca_number']);

            // Scope uniqueness to user + mru + ca_number
            $table->unique(['user_id', 'mru_id', 'ca_number'], 'consumer_accounts_user_mru_ca_unique');

            // Maintain non-unique index on [user_id, ca_number] for fast lookup across MRUs
            $table->index(['user_id', 'ca_number'], 'consumer_accounts_user_ca_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumer_accounts', function (Blueprint $table) {
            $table->dropUnique('consumer_accounts_user_mru_ca_unique');
            $table->dropIndex('consumer_accounts_user_ca_index');
            $table->unique(['user_id', 'ca_number'], 'consumer_accounts_user_id_ca_number_unique');
        });
    }
};
