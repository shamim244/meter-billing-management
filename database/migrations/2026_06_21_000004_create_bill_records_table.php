<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ca_number', 50);
            $table->foreignId('mru_id')->nullable()->constrained('mrus')->onDelete('set null');

            // Billing period — the composite unique key
            $table->unsignedTinyInteger('billing_month'); // 1-12
            $table->unsignedSmallInteger('billing_year'); // e.g. 2026
            $table->string('bill_month_label', 50)->nullable(); // "JUN, 2026" (raw from PDF)

            // Extracted bill data
            $table->string('consumer_name', 255)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('current_reading', 20)->nullable();
            $table->string('previous_reading', 20)->nullable();
            $table->unsignedInteger('units_consumed')->nullable();
            $table->string('meter_no', 50)->nullable();
            $table->date('bill_date')->nullable();

            // File management
            $table->string('pdf_path', 500)->nullable(); // relative path in storage
            $table->string('pdf_filename', 255)->nullable(); // original filename

            // Processing status
            $table->string('download_status', 20)->default('pending'); // pending, downloaded, failed
            $table->string('parse_status', 20)->default('pending');    // pending, parsed, failed, skipped
            $table->timestamp('processing_date')->nullable();
            $table->text('error_message')->nullable(); // store failure reasons

            $table->timestamps();

            // Composite unique: same CA + same billing month/year for same user = one record
            $table->unique(['user_id', 'ca_number', 'billing_month', 'billing_year'], 'bill_records_user_ca_period_unique');

            // Performance indexes
            $table->index(['user_id', 'ca_number']);
            $table->index(['user_id', 'billing_month', 'billing_year']);
            $table->index(['user_id', 'mru_id']);
            $table->index(['user_id', 'download_status']);
            $table->index(['user_id', 'parse_status']);
            $table->index('ca_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_records');
    }
};
