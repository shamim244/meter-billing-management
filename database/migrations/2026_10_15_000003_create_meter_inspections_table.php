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
        if (! Schema::hasTable('meter_inspections')) {
            Schema::create('meter_inspections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('consumer_account_id')->nullable();
                $table->string('ca_number', 30)->index();
                $table->string('physical_reading', 50);
                $table->string('posted_reading_at_inspection', 50)->nullable();
                $table->integer('gap_at_inspection')->default(0);
                $table->string('photo_path', 255)->nullable();
                $table->string('notes', 255)->nullable();
                $table->timestamp('inspected_at');
                $table->timestamps();

                $table->index(['user_id', 'ca_number']);
                $table->index(['ca_number', 'inspected_at']);

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('consumer_account_id')->references('id')->on('consumer_accounts')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_inspections');
    }
};
