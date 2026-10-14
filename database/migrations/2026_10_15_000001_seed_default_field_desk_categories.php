<?php

use App\Models\FieldDeskCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        FieldDeskCategory::ensureDefaultsExist();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // System categories are preserved.
    }
};
