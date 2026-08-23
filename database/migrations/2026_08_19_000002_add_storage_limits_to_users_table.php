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
            if (!Schema::hasColumn('users', 'storage_limit_mb')) {
                $table->unsignedInteger('storage_limit_mb')->default(100)->after('shortcuts');
            }
            if (!Schema::hasColumn('users', 'plan_tier')) {
                $table->string('plan_tier')->default('free')->after('storage_limit_mb');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'storage_limit_mb')) {
                $table->dropColumn('storage_limit_mb');
            }
            if (Schema::hasColumn('users', 'plan_tier')) {
                $table->dropColumn('plan_tier');
            }
        });
    }
};
