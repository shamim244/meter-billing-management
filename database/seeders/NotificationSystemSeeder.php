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

        // 2. Seed default Hostinger Mail & SMTP email provider instances if empty
        if (EmailProviderInstance::where('driver_type', 'hostinger')->count() === 0) {
            EmailProviderInstance::create([
                'driver_type' => 'hostinger',
                'label' => 'Hostinger Mail API (Official)',
                'config' => [
                    'api_key' => env('HOSTINGER_MAIL_API_TOKEN', '9c16a97538050456cbe2aa3549cd9861949867070bfdd7872f125044e712e322'),
                    'from_address' => env('HOSTINGER_MAIL_FROM_ADDRESS', 'agent@nexgenhub.site'),
                    'from_name' => env('HOSTINGER_MAIL_FROM_NAME', 'NBPDCL SaaS Billing'),
                ],
                'priority' => 1,
                'is_enabled' => true,
            ]);
        }

        if (EmailProviderInstance::where('driver_type', 'smtp')->count() === 0) {
            EmailProviderInstance::create([
                'driver_type' => 'smtp',
                'label' => 'Secondary SMTP Failover',
                'config' => [
                    'host' => env('MAIL_HOST', 'smtp.hostinger.com'),
                    'port' => (int) env('MAIL_PORT', 587),
                    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                    'username' => env('MAIL_USERNAME', 'agent@nexgenhub.site'),
                    'password' => env('MAIL_PASSWORD', ''),
                    'from_address' => env('MAIL_FROM_ADDRESS', 'agent@nexgenhub.site'),
                    'from_name' => env('MAIL_FROM_NAME', 'NBPDCL Billing Platform'),
                ],
                'priority' => 2,
                'is_enabled' => true,
            ]);
        }
    }
}
