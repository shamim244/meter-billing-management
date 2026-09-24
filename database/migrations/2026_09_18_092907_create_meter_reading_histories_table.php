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
        Schema::create('meter_reading_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mru_id')->nullable()->constrained('mrus')->nullOnDelete();
            $table->foreignId('consumer_id')->nullable()->constrained('consumer_accounts')->nullOnDelete();
            $table->foreignId('bill_record_id')->nullable()->constrained('bill_records')->nullOnDelete();
            $table->string('ca_number', 50)->index();
            $table->unsignedTinyInteger('billing_month');
            $table->unsignedSmallInteger('billing_year');
            $table->string('previous_reading', 50)->nullable();
            $table->string('current_reading', 50)->nullable();
            $table->string('working_reading', 50)->nullable();
            $table->integer('units_consumed')->nullable();
            $table->string('billing_basis', 20)->default('OK');
            $table->string('reading_source', 30)->default('working'); // 'working', 'pdf', 'migrated'
            $table->boolean('is_closed')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'ca_number', 'billing_month', 'billing_year', 'reading_source'], 'mrh_user_ca_period_source_unique');
            $table->index(['user_id', 'ca_number', 'billing_year', 'billing_month'], 'mrh_user_ca_date_idx');
            $table->index(['user_id', 'reading_source', 'is_closed'], 'mrh_user_source_closed_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_reading_histories');
    }
};
