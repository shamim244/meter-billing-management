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
            if (! Schema::hasColumn('consumer_accounts', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('address');
            }
            if (! Schema::hasColumn('consumer_accounts', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('consumer_accounts', 'location_accuracy')) {
                $table->float('location_accuracy')->nullable()->after('longitude');
            }
            if (! Schema::hasColumn('consumer_accounts', 'location_updated_at')) {
                $table->timestamp('location_updated_at')->nullable()->after('location_accuracy');
            }
        });

        Schema::table('field_desk_actions', function (Blueprint $table) {
            if (! Schema::hasColumn('field_desk_actions', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('private_note');
            }
            if (! Schema::hasColumn('field_desk_actions', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('field_desk_actions', 'location_accuracy')) {
                $table->float('location_accuracy')->nullable()->after('longitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumer_accounts', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_accuracy', 'location_updated_at']);
        });

        Schema::table('field_desk_actions', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_accuracy']);
        });
    }
};
