<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Grace Period Days
    |--------------------------------------------------------------------------
    |
    | Platform-wide default number of days an Agent is granted after their
    | subscription expiration (RENEWAL_DUE) to renew before being moved to
    | SUSPENDED (read-only mode).
    |
    | Setting to 0 skips grace period entirely straight to SUSPENDED.
    | Can also be overridden per Plan in the plans table or via SystemSetting.
    |
    */
    'default_grace_period_days' => (int) env('BILLING_DEFAULT_GRACE_PERIOD_DAYS', 3),
];
