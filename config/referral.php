<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Refer & Earn System Defaults
    |--------------------------------------------------------------------------
    |
    | Default configuration for platform-wide referral settings.
    | Can be updated dynamically by Admin at /admin/referrals/settings.
    |
    */

    'is_enabled' => env('REFERRAL_SYSTEM_ENABLED', true),

    // 'subscription' (first subscription payment) or 'topup' (first wallet recharge)
    'reward_trigger' => env('REFERRAL_REWARD_TRIGGER', 'subscription'),

    // 'percentage' or 'flat'
    'reward_kind' => env('REFERRAL_REWARD_KIND', 'percentage'),

    // 10% or ₹10 flat
    'reward_value' => (float) env('REFERRAL_REWARD_VALUE', 10.0),

    // Minimum qualifying payment amount (₹100 default) to prevent trivial farming
    'minimum_qualifying_amount' => (float) env('REFERRAL_MIN_QUALIFYING_AMOUNT', 100.0),

    // Hold period before payout release (7 days default)
    'hold_period_days' => (int) env('REFERRAL_HOLD_PERIOD_DAYS', 7),

    // Optional referee discount at registration / first purchase
    'referee_discount_kind' => env('REFERRAL_REFEREE_DISCOUNT_KIND', null),
    'referee_discount_value' => env('REFERRAL_REFEREE_DISCOUNT_VALUE', null),
];
