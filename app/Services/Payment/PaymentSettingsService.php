<?php

namespace App\Services\Payment;

use App\Enums\PaymentMode;
use App\Models\SystemSetting;

class PaymentSettingsService
{
    /**
     * Get all payment configuration settings.
     */
    public function getSettings(): array
    {
        return [
            'pg_enabled' => (bool) SystemSetting::get('payment_pg_enabled', true),
            'cashfree_enabled' => (bool) SystemSetting::get('payment_cashfree_enabled', true),
            'razorpay_enabled' => (bool) SystemSetting::get('payment_razorpay_enabled', true),
            'manual_upi_enabled' => (bool) SystemSetting::get('payment_manual_upi_enabled', true),
            'bank_transfer_enabled' => (bool) SystemSetting::get('payment_bank_transfer_enabled', true),
            'min_amount' => (float) SystemSetting::get('payment_min_amount', 100.0),
            'wallet_low_balance_threshold' => (float) SystemSetting::get('wallet_low_balance_threshold', config('wallet.low_balance_threshold', 200.00)),
            'active_pg_driver' => (string) SystemSetting::get('payment_active_pg_driver', 'cashfree'), // 'cashfree' or 'razorpay'
            
            // Cashfree PG Settings
            'cashfree_app_id' => (string) SystemSetting::get('payment_cashfree_app_id', config('services.cashfree.app_id', 'test_app_id')),
            'cashfree_secret_key' => (string) SystemSetting::get('payment_cashfree_secret_key', config('services.cashfree.secret_key', 'test_secret_key')),
            'cashfree_environment' => (string) SystemSetting::get('payment_cashfree_environment', config('services.cashfree.environment', 'sandbox')),
            'cashfree_api_version' => (string) SystemSetting::get('payment_cashfree_api_version', config('services.cashfree.api_version', '2023-08-01')),
            'cashfree_webhook_secret' => (string) SystemSetting::get('payment_cashfree_webhook_secret', config('services.cashfree.webhook_secret', 'test_webhook_secret')),

            // Razorpay PG Settings
            'razorpay_key_id' => (string) SystemSetting::get('payment_razorpay_key_id', config('services.razorpay.key_id', 'rzp_test_nbpdcl_saas')),
            'razorpay_key_secret' => (string) SystemSetting::get('payment_razorpay_key_secret', config('services.razorpay.key_secret', 'test_razorpay_secret')),
            'razorpay_webhook_secret' => (string) SystemSetting::get('payment_razorpay_webhook_secret', config('services.razorpay.webhook_secret', 'test_razorpay_webhook_secret')),

            // Manual UPI Settings
            'business_upi_id' => (string) SystemSetting::get('payment_business_upi_id', 'nbpdcl.billing@upi'),
            'business_upi_name' => (string) SystemSetting::get('payment_business_upi_name', 'NBPDCL SaaS Billing'),
            
            // Bank Transfer Settings
            'bank_account_name' => (string) SystemSetting::get('payment_bank_account_name', 'NBPDCL SaaS Billing Pvt Ltd'),
            'bank_account_number' => (string) SystemSetting::get('payment_bank_account_number', '918273645019'),
            'bank_ifsc' => (string) SystemSetting::get('payment_bank_ifsc', 'SBIN0001234'),
            'bank_name' => (string) SystemSetting::get('payment_bank_name', 'State Bank of India'),
        ];
    }

    /**
     * Update payment configuration settings.
     */
    public function updateSettings(array $data): void
    {
        if (array_key_exists('pg_enabled', $data)) {
            SystemSetting::set('payment_pg_enabled', (bool) $data['pg_enabled']);
        }
        if (array_key_exists('cashfree_enabled', $data)) {
            SystemSetting::set('payment_cashfree_enabled', (bool) $data['cashfree_enabled']);
        }
        if (array_key_exists('razorpay_enabled', $data)) {
            SystemSetting::set('payment_razorpay_enabled', (bool) $data['razorpay_enabled']);
        }
        if (array_key_exists('manual_upi_enabled', $data)) {
            SystemSetting::set('payment_manual_upi_enabled', (bool) $data['manual_upi_enabled']);
        }
        if (array_key_exists('bank_transfer_enabled', $data)) {
            SystemSetting::set('payment_bank_transfer_enabled', (bool) $data['bank_transfer_enabled']);
        }
        if (isset($data['min_amount'])) {
            SystemSetting::set('payment_min_amount', max(1.0, (float) $data['min_amount']));
        }
        if (isset($data['wallet_low_balance_threshold'])) {
            SystemSetting::set('wallet_low_balance_threshold', max(0.0, (float) $data['wallet_low_balance_threshold']));
        }
        if (isset($data['active_pg_driver'])) {
            SystemSetting::set('payment_active_pg_driver', in_array($data['active_pg_driver'], ['cashfree', 'razorpay'], true) ? $data['active_pg_driver'] : 'cashfree');
        }

        // Cashfree settings
        if (isset($data['cashfree_app_id'])) {
            SystemSetting::set('payment_cashfree_app_id', trim($data['cashfree_app_id']));
        }
        if (isset($data['cashfree_secret_key'])) {
            SystemSetting::set('payment_cashfree_secret_key', trim($data['cashfree_secret_key']));
        }
        if (isset($data['cashfree_environment'])) {
            SystemSetting::set('payment_cashfree_environment', in_array($data['cashfree_environment'], ['sandbox', 'production'], true) ? $data['cashfree_environment'] : 'sandbox');
        }
        if (isset($data['cashfree_webhook_secret'])) {
            SystemSetting::set('payment_cashfree_webhook_secret', trim($data['cashfree_webhook_secret']));
        }

        // Razorpay settings
        if (isset($data['razorpay_key_id'])) {
            SystemSetting::set('payment_razorpay_key_id', trim($data['razorpay_key_id']));
        }
        if (isset($data['razorpay_key_secret'])) {
            SystemSetting::set('payment_razorpay_key_secret', trim($data['razorpay_key_secret']));
        }
        if (isset($data['razorpay_webhook_secret'])) {
            SystemSetting::set('payment_razorpay_webhook_secret', trim($data['razorpay_webhook_secret']));
        }

        // Manual UPI settings
        if (isset($data['business_upi_id'])) {
            SystemSetting::set('payment_business_upi_id', trim($data['business_upi_id']));
        }
        if (isset($data['business_upi_name'])) {
            SystemSetting::set('payment_business_upi_name', trim($data['business_upi_name']));
        }

        // Bank settings
        if (isset($data['bank_account_name'])) {
            SystemSetting::set('payment_bank_account_name', trim($data['bank_account_name']));
        }
        if (isset($data['bank_account_number'])) {
            SystemSetting::set('payment_bank_account_number', trim($data['bank_account_number']));
        }
        if (isset($data['bank_ifsc'])) {
            SystemSetting::set('payment_bank_ifsc', trim($data['bank_ifsc']));
        }
        if (isset($data['bank_name'])) {
            SystemSetting::set('payment_bank_name', trim($data['bank_name']));
        }
    }

    /**
     * Check if a specific payment mode is currently enabled.
     */
    public function isModeEnabled(PaymentMode $mode): bool
    {
        return match ($mode) {
            PaymentMode::PG => (bool) SystemSetting::get('payment_pg_enabled', true) && 
                              ((bool) SystemSetting::get('payment_cashfree_enabled', true) || (bool) SystemSetting::get('payment_razorpay_enabled', true)),
            PaymentMode::MANUAL_UPI => (bool) SystemSetting::get('payment_manual_upi_enabled', true),
            PaymentMode::BANK_TRANSFER => (bool) SystemSetting::get('payment_bank_transfer_enabled', true),
        };
    }

    /**
     * Check if Cashfree gateway is enabled.
     */
    public function isCashfreeEnabled(): bool
    {
        return (bool) SystemSetting::get('payment_pg_enabled', true) && (bool) SystemSetting::get('payment_cashfree_enabled', true);
    }

    /**
     * Check if Razorpay gateway is enabled.
     */
    public function isRazorpayEnabled(): bool
    {
        return (bool) SystemSetting::get('payment_pg_enabled', true) && (bool) SystemSetting::get('payment_razorpay_enabled', true);
    }

    /**
     * Get minimum payment amount allowed.
     */
    public function getMinAmount(): float
    {
        return (float) SystemSetting::get('payment_min_amount', 100.0);
    }

    /**
     * Get Active Online PG Driver ('cashfree' or 'razorpay').
     */
    public function getActivePgDriver(): string
    {
        $driver = (string) SystemSetting::get('payment_active_pg_driver', 'cashfree');
        if ($driver === 'razorpay' && !$this->isRazorpayEnabled() && $this->isCashfreeEnabled()) {
            return 'cashfree';
        }
        if ($driver === 'cashfree' && !$this->isCashfreeEnabled() && $this->isRazorpayEnabled()) {
            return 'razorpay';
        }
        return $driver;
    }

    /**
     * Get Cashfree Base API URL based on environment.
     */
    public function getCashfreeBaseUrl(): string
    {
        $env = (string) SystemSetting::get('payment_cashfree_environment', config('services.cashfree.environment', 'sandbox'));
        return $env === 'production'
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';
    }

    /**
     * Get Cashfree App ID.
     */
    public function getCashfreeAppId(): string
    {
        return (string) SystemSetting::get('payment_cashfree_app_id', config('services.cashfree.app_id', 'test_app_id'));
    }

    /**
     * Get Cashfree Secret Key.
     */
    public function getCashfreeSecretKey(): string
    {
        return (string) SystemSetting::get('payment_cashfree_secret_key', config('services.cashfree.secret_key', 'test_secret_key'));
    }

    /**
     * Get Cashfree Environment.
     */
    public function getCashfreeEnvironment(): string
    {
        return (string) SystemSetting::get('payment_cashfree_environment', config('services.cashfree.environment', 'sandbox'));
    }

    /**
     * Get Cashfree API Version.
     */
    public function getCashfreeApiVersion(): string
    {
        return (string) SystemSetting::get('payment_cashfree_api_version', config('services.cashfree.api_version', '2023-08-01'));
    }

    /**
     * Get Razorpay Key ID.
     */
    public function getRazorpayKeyId(): string
    {
        return (string) SystemSetting::get('payment_razorpay_key_id', config('services.razorpay.key_id', 'rzp_test_nbpdcl_saas'));
    }

    /**
     * Get Razorpay Key Secret.
     */
    public function getRazorpayKeySecret(): string
    {
        return (string) SystemSetting::get('payment_razorpay_key_secret', config('services.razorpay.key_secret', 'test_razorpay_secret'));
    }

    /**
     * Get Razorpay Webhook Secret.
     */
    public function getRazorpayWebhookSecret(): string
    {
        return (string) SystemSetting::get('payment_razorpay_webhook_secret', config('services.razorpay.webhook_secret', 'test_razorpay_webhook_secret'));
    }
}
