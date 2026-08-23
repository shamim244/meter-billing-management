<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ca_number', 50);
            $table->unsignedTinyInteger('billing_month');
            $table->unsignedSmallInteger('billing_year');
            $table->string('status', 30)->default('pending'); // pending, submitted, critical, doubt
            $table->timestamps();

            // Unique: one status per CA per billing period per user
            $table->unique(
                ['user_id', 'ca_number', 'billing_month', 'billing_year'],
                'bill_statuses_user_ca_period_unique'
            );
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_statuses');
    }
};
