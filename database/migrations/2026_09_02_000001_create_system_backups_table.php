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
        Schema::create('system_backups', function (Blueprint $table) {
            $table->id();
            $table->string('backup_code')->unique();
            $table->string('type', 32); // db_only, storage_only, full, agent_export
            $table->string('filename');
            $table->string('disk', 32)->default('local');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('sha256_hash', 64)->nullable();
            $table->decimal('duration_seconds', 8, 2)->default(0.00);
            $table->string('status', 32)->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_backups');
    }
};
