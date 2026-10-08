<?php

namespace Database\Seeders;

use App\Models\FieldDeskCategory;
use Illuminate\Database\Seeder;

class FieldDeskCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'code' => 'payment_promise',
                'name' => 'Payment Commitment',
                'icon' => '💳',
                'color' => '#10b981',
                'description' => 'Consumer payment commitment with target date and amount',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'issue_correction',
                'name' => 'Technical / Grievance',
                'icon' => '🔧',
                'color' => '#f59e0b',
                'description' => 'Meter defect or billing grievance resolution target',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'scheduled_visit',
                'name' => 'Scheduled Visit',
                'icon' => '🚶',
                'color' => '#6366f1',
                'description' => 'Scheduled field inspection or access visit',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'general_note',
                'name' => 'Field Dossier Note',
                'icon' => '📝',
                'color' => '#64748b',
                'description' => 'Field observation or consumer contact dossier note',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $cat) {
            FieldDeskCategory::updateOrCreate(
                ['code' => $cat['code']],
                $cat
            );
        }
    }
}
