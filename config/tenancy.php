<?php

return [
    // Tenant database server (supervisor will provide these values via .env)
    'connection' => [
        'driver' => env('TENANT_DB_DRIVER', 'mysql'),
        'host' => env('TENANT_DB_HOST', '127.0.0.1'),
        'port' => env('TENANT_DB_PORT', '3306'),
        'username' => env('TENANT_DB_USERNAME', 'root'),
        'password' => env('TENANT_DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_general_ci',
        'prefix' => '',
        'strict' => false,
        'engine' => null,
    ],

    // New tenant databases use <prefix><portal-name>_<portal id>, e.g. tenant_demo_portal_12.
    // Existing portals keep their assigned database_name so their tenant data is not moved.
    'database_prefix' => env('TENANT_DB_PREFIX', 'tenant_'),

    // Schema (and optionally sample data) imported into every new tenant DB
    'schema_file' => database_path('tenant/schema.sql'),
    // Views (re)created in every tenant DB on provision – idempotent (CREATE OR REPLACE VIEW)
    'views_file' => database_path('tenant/views.sql'),
    // Extra schema changes for tenant DBs: database/tenant/migrations/*.sql, applied once per
    // tenant, in file-name order (see database/tenant/migrations/README.md).
    'migrations_path' => database_path('tenant/migrations'),

    // Class (with a seed($connection, $portal) method) that fills a brand new tenant database.
    // Point this at your own class when this code is reused for another system.
    'seeder' => \App\Services\TenantSeeder::class,

    // Written into every tenant DB so a DB that belongs to ANOTHER installation (same
    // tenant_<id> name on a shared MySQL server) is refused instead of silently reused.
    // Defaults to a hash of APP_KEY. Better: also give each environment its own TENANT_DB_PREFIX.
    'installation_id' => env('TENANT_INSTALLATION_ID') ?: substr(hash('sha256', (string) env('APP_KEY')), 0, 16),

    'default_time_zone' => env('TENANT_DEFAULT_TIMEZONE', 'Asia/Dhaka'),

    // Optional override of the mobile-number rule (leave empty for Bangladeshi numbers).
    'mobile_regex' => env('PORTAL_MOBILE_REGEX'),

    'import_sample_data' => (bool) env('TENANT_IMPORT_SAMPLE_DATA', false),

    // Thresholds used to flag suspicious portals in the admin panel
    'monitor' => [
        'requests_per_15min' => (int) env('MONITOR_REQUESTS_15MIN', 300),
        'writes_per_15min' => (int) env('MONITOR_WRITES_15MIN', 1000),
        'errors_per_hour' => (int) env('MONITOR_ERRORS_HOUR', 30),
        'ips_per_hour' => (int) env('MONITOR_IPS_HOUR', 4),
        'blocked_logins_per_hour' => (int) env('MONITOR_BLOCKED_LOGINS_HOUR', 5),
    ],
];
