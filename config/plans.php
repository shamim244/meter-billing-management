<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MRU Auto-Lock Decision Window Timeout (Hours)
    |--------------------------------------------------------------------------
    |
    | The number of hours an Agent has during subscription renewal to decide
    | whether to add extra MRUs to their renewal before the platform auto-locks
    | the most recently created over-quota MRU.
    |
    */
    'mru_autolock_timeout_hours' => (int) env('PLAN_MRU_AUTOLOCK_TIMEOUT_HOURS', 72),
];
