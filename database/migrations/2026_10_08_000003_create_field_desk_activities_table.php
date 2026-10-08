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
        Schema::create('field_desk_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_id')->constrained('field_desk_actions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action_type', 50);
            $table->text('note')->nullable();
            $table->date('old_date')->nullable();
            $table->date('new_date')->nullable();
            $table->decimal('amount_recorded', 12, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action_id', 'created_at'], 'idx_action_timeline');
            $table->index('action_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_desk_activities');
    }
};
