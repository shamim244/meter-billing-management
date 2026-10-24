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
        Schema::table('field_desk_actions', function (Blueprint $table) {
            if (! Schema::hasColumn('field_desk_actions', 'mobile')) {
                $table->string('mobile', 20)->nullable()->after('ca_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('field_desk_actions', function (Blueprint $table) {
            if (Schema::hasColumn('field_desk_actions', 'mobile')) {
                $table->dropColumn('mobile');
            }
        });
    }
};
