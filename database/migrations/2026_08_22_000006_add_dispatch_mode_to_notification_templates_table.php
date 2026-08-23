<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->enum('dispatch_mode', ['sync', 'queued'])->default('queued')->after('priority');
        });

        // Seed data migration: set dispatch_mode = 'sync' specifically for auth.welcome, auth.password_reset, payment.success
        DB::table('notification_templates')
            ->where('channel', 'email')
            ->whereIn('event_type', ['auth.welcome', 'auth.password_reset', 'payment.success'])
            ->update(['dispatch_mode' => 'sync']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropColumn('dispatch_mode');
        });
    }
};
