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
        Schema::create('issue_reports', function (Blueprint $table) {
            $table->id();
            $table->string('issue_code', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255);
            $table->text('description');
            $table->string('category', 50)->default('other'); // calculation, bill_download, mru_sync, ui_display, wallet_payment, other
            $table->string('severity', 20)->default('medium'); // low, medium, high, critical
            $table->string('status', 20)->default('pending'); // pending, verified, spam, in_progress, resolved, closed
            $table->string('page_url', 1000)->nullable();
            $table->string('route_name', 255)->nullable();
            $table->string('ca_number', 50)->nullable();
            $table->foreignId('mru_id')->nullable()->constrained('mrus')->nullOnDelete();
            $table->unsignedTinyInteger('billing_month')->nullable();
            $table->unsignedSmallInteger('billing_year')->nullable();
            $table->json('client_context')->nullable();
            $table->json('server_context')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('ai_resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'severity']);
            $table->index(['user_id', 'created_at']);
            $table->index(['category', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_reports');
    }
};
