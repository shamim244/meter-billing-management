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
        Schema::create('field_desk_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ca_number', 50);
            $table->foreignId('consumer_account_id')->nullable()->constrained('consumer_accounts')->nullOnDelete();
            $table->foreignId('category_id')->constrained('field_desk_categories')->restrictOnDelete();
            $table->enum('priority', ['urgent', 'high', 'normal', 'low'])->default('normal');
            $table->date('target_date');
            $table->date('original_target_date');
            $table->decimal('target_amount', 12, 2)->nullable();
            $table->decimal('collected_amount', 12, 2)->default(0.00);
            $table->string('payment_mode', 50)->nullable();
            $table->text('private_note')->nullable();
            $table->enum('status', ['open', 'completed', 'rescheduled', 'cancelled'])->default('open');
            $table->unsignedInteger('reschedule_count')->default(0);
            $table->unsignedSmallInteger('billing_month')->nullable();
            $table->unsignedSmallInteger('billing_year')->nullable();
            $table->foreignId('mru_id')->nullable()->constrained('mrus')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_note', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'target_date'], 'idx_user_status_date');
            $table->index(['user_id', 'ca_number'], 'idx_user_ca');
            $table->index(['category_id', 'status'], 'idx_cat_status');
            $table->index('mru_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_desk_actions');
    }
};
