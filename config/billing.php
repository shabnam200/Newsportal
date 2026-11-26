<?php

return [
    // Fallback for the "expiring soon" banner + Grace Period option: they appear when this many days (or
    // fewer) are left. Each package has its own value (Admin > Packages); this is used if a package has none.
    'warn_days' => (int) env('BILLING_WARN_DAYS', 7),

    // A portal can ask for its grace period to end at most this many days after the paid period ends.
    'max_grace_days' => (int) env('BILLING_MAX_GRACE_DAYS', 30),

    // Shown inside the Renew/Purchase popup so the portal knows where to send money
    // (e.g. "bKash/Nagad personal: 01XXXXXXXXX"). Leave empty to show a generic message.
    'payment_info' => env('BILLING_PAYMENT_INFO', ''),

    'methods' => [
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'rocket' => 'Rocket',
        'bank' => 'Bank',
        'cash' => 'Cash',
    ],
];
