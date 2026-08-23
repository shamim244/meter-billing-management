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
        Schema::create('billing_basis_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mru_id')->nullable()->constrained('mrus')->nullOnDelete();
            $table->foreignId('consumer_id')->nullable()->constrained('consumer_accounts')->nullOnDelete();
            $table->string('ca_number', 64)->index();
            $table->foreignId('billing_cycle_id')->nullable()->constrained('billing_cycles')->nullOnDelete();
            $table->unsignedTinyInteger('billing_month');
            $table->unsignedSmallInteger('billing_year');
            $table->string('billing_basis', 20)->default('OK');
            $table->boolean('is_consecutive_alert')->default(false);
            $table->unsignedInteger('consecutive_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'billing_month', 'billing_year']);
            $table->index(['user_id', 'is_consecutive_alert']);
            $table->index(['user_id', 'ca_number']);
            $table->unique(['user_id', 'ca_number', 'billing_month', 'billing_year'], 'bbh_user_ca_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_basis_history');
    }
};
