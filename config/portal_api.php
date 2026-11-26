<?php

return [
    // Random characters after the "npk_" prefix of a NEW token (minimum 16). 20 chars = about 119 bits, far beyond guessing.
    'token_length' => (int) env('PORTAL_API_TOKEN_LENGTH', 20),

    // Requests per minute allowed for one token.
    'rate_limit_per_minute' => (int) env('PORTAL_API_RATE_LIMIT', 120),

    // Wrong / unknown tokens allowed per minute from one IP address before it is blocked for a minute.
    'failed_attempts_per_minute' => (int) env('PORTAL_API_FAILED_PER_MINUTE', 20),

    'default_per_page' => 50,
    'max_per_page' => 200,

    // Tenant tables that are NEVER served, even with a valid token (logins, sessions, secrets, private contacts).
    // A hidden table answers 404, exactly like a table that does not exist.
    'hidden_tables' => [
        'users',
        'user_profiles',
        'user_autologin',
        'login_attempts',
        'ci_sessions',
        'app_setting_info',      // app_token / app_key / subscription_token
        'app_user_info',         // device push ids + emails
        'email_subscriber_info', // subscriber emails
        'subscription_tbl',      // subscriber emails
    ],

    // Columns removed from every table (not returned, not filterable, not sortable).
    'hidden_columns' => [
        'password', 'new_password_key', 'new_email_key', 'new_email', 'ban_reason',
        'app_token', 'app_key', 'subscription_token', 'gcmID', 'last_ip',
        'member_email', 'member_phone',
    ],
];
