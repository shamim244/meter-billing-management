<?php

namespace Database\Seeders;

use App\Models\EmailProviderInstance;
use App\Services\Notifications\NotificationTemplateService;
use Illuminate\Database\Seeder;

class NotificationSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed all factory notification templates
        app(NotificationTemplateService::class)->resetToDefaults();

        // 2. Seed default SMTP email provider instance if empty
        if (EmailProviderInstance::count() === 0) {
            EmailProviderInstance::create([
                'driver_type' => 'smtp',
                'label' => 'Primary SMTP Server',
                'config' => [
                    'host' => env('MAIL_HOST', '127.0.0.1'),
                    'port' => (int) env('MAIL_PORT', 587),
                    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                    'username' => env('MAIL_USERNAME', ''),
                    'password' => env('MAIL_PASSWORD', ''),
                    'from_address' => env('MAIL_FROM_ADDRESS', 'notifications@nexgenhub.site'),
                    'from_name' => env('MAIL_FROM_NAME', 'NBPDCL Billing Platform'),
                ],
                'priority' => 1,
                'is_enabled' => true,
            ]);
        }
    }
}
