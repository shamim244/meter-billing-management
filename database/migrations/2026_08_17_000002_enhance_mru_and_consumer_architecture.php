<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mrus', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index(['user_id', 'code']);
        });

        Schema::table('consumer_accounts', function (Blueprint $table) {
            $table->string('meter_no', 50)->nullable()->after('consumer_name');
            $table->string('mobile', 20)->nullable()->after('meter_no');
            $table->text('address')->nullable()->after('mobile');
        });
    }

    public function down(): void
    {
        Schema::table('mrus', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('consumer_accounts', function (Blueprint $table) {
            $table->dropColumn(['meter_no', 'mobile', 'address']);
        });
    }
};
