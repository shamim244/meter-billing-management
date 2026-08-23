<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrus', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('full_identifier', 500)->nullable();
            $table->string('status', 20)->default('active'); // active, inactive
            $table->timestamps();

            $table->index('code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrus');
    }
};
