<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumer_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ca_number', 50);
            $table->foreignId('mru_id')->nullable()->constrained('mrus')->onDelete('set null');
            $table->string('consumer_name', 255)->nullable();
            $table->string('status', 20)->default('active'); // active, inactive
            $table->timestamps();

            // A user cannot register the same CA number twice
            $table->unique(['user_id', 'ca_number']);
            $table->index('ca_number');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumer_accounts');
    }
};
