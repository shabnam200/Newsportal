-- =====================================================================
-- MAIN DATABASE ADDITIONS (MySQL / MariaDB)
-- Import this ONCE into the main database (the one imported from sample.sql)
-- with phpMyAdmin > Import. It only CREATES the 11 tables below.
-- It does not touch, alter or drop any table that already exists in sample.sql.
-- Do NOT run `php artisan migrate`: that would add Laravel's own `migrations` table.
-- =====================================================================
SET NAMES utf8mb4;

-- 1) portal accounts (one row per signup)
CREATE TABLE IF NOT EXISTS `portals` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `portal_name` varchar(100) NOT NULL,
  `portal_reg_no` varchar(100) DEFAULT NULL,
  `editor_name` varchar(100) DEFAULT NULL,
  `editor_email` varchar(150) DEFAULT NULL,
  `editor_mobile` varchar(20) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `database_name` varchar(64) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `provisioned_at` timestamp NULL DEFAULT NULL,
  `provision_error` text DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `portals_portal_name_editor_mobile_unique` (`portal_name`,`editor_mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) super admins of the SaaS panel
CREATE TABLE IF NOT EXISTS `admins` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) packages
CREATE TABLE IF NOT EXISTS `packages` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `duration_days` int(10) UNSIGNED NOT NULL,
  `grace_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `grace_window_days` smallint(5) UNSIGNED NOT NULL DEFAULT 7,
  `is_trial` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `term_months` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `packages_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) subscriptions (which portal has which package, and until when)
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `portal_id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `starts_at` date NOT NULL,
  `ends_at` date NOT NULL,
  `grace_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `term_months` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `paid_months` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `period_days` int(10) UNSIGNED NOT NULL DEFAULT 30,
  PRIMARY KEY (`id`),
  KEY `subscriptions_portal_id_ends_at_index` (`portal_id`,`ends_at`),
  KEY `subscriptions_package_id_foreign` (`package_id`),
  CONSTRAINT `subscriptions_portal_id_foreign` FOREIGN KEY (`portal_id`) REFERENCES `portals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) payments received
CREATE TABLE IF NOT EXISTS `payments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `portal_id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subscription_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(20) NOT NULL,
  `trx_id` varchar(100) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `months` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `payments_portal_id_foreign` (`portal_id`),
  KEY `payments_package_id_foreign` (`package_id`),
  KEY `payments_subscription_id_foreign` (`subscription_id`),
  CONSTRAINT `payments_portal_id_foreign` FOREIGN KEY (`portal_id`) REFERENCES `portals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) extra charges per portal
CREATE TABLE IF NOT EXISTS `portal_charges` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `portal_id` bigint(20) UNSIGNED NOT NULL,
  `purpose` varchar(150) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` varchar(20) NOT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `portal_charges_portal_id_foreign` (`portal_id`),
  CONSTRAINT `portal_charges_portal_id_foreign` FOREIGN KEY (`portal_id`) REFERENCES `portals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7) request / login activity per portal
CREATE TABLE IF NOT EXISTS `portal_activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `portal_id` bigint(20) UNSIGNED NOT NULL,
  `event` varchar(20) NOT NULL DEFAULT 'request',
  `method` varchar(10) DEFAULT NULL,
  `path` varchar(500) DEFAULT NULL,
  `route_name` varchar(100) DEFAULT NULL,
  `status` smallint(5) UNSIGNED DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `duration_ms` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `db_queries` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `db_writes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `portal_activity_logs_created_at_index` (`created_at`),
  KEY `portal_activity_logs_portal_id_created_at_index` (`portal_id`,`created_at`),
  CONSTRAINT `portal_activity_logs_portal_id_foreign` FOREIGN KEY (`portal_id`) REFERENCES `portals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8) grace period requests
CREATE TABLE IF NOT EXISTS `grace_requests` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `portal_id` bigint(20) UNSIGNED NOT NULL,
  `until_date` date NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `admin_note` varchar(255) DEFAULT NULL,
  `approved_until` date DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `decided_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grace_requests_portal_id_status_index` (`portal_id`,`status`),
  CONSTRAINT `grace_requests_portal_id_foreign` FOREIGN KEY (`portal_id`) REFERENCES `portals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9) purchase / renew requests
CREATE TABLE IF NOT EXISTS `purchase_requests` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `portal_id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `months` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(20) NOT NULL,
  `trx_id` varchar(100) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `admin_note` varchar(255) DEFAULT NULL,
  `payment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `decided_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_requests_portal_id_status_index` (`portal_id`,`status`),
  KEY `purchase_requests_package_id_foreign` (`package_id`),
  CONSTRAINT `purchase_requests_portal_id_foreign` FOREIGN KEY (`portal_id`) REFERENCES `portals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_requests_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10) admin "support mode" sessions (audit)
CREATE TABLE IF NOT EXISTS `portal_support_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_name` varchar(100) NOT NULL,
  `portal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `portal_name` varchar(100) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `portal_support_sessions_last_activity_at_index` (`last_activity_at`),
  KEY `portal_support_sessions_ended_at_index` (`ended_at`),
  KEY `portal_support_sessions_portal_id_started_at_index` (`portal_id`,`started_at`),
  KEY `portal_support_sessions_admin_id_foreign` (`admin_id`),
  CONSTRAINT `portal_support_sessions_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `portal_support_sessions_portal_id_foreign` FOREIGN KEY (`portal_id`) REFERENCES `portals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11) what the admin did inside a support session
CREATE TABLE IF NOT EXISTS `portal_support_events` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `support_session_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(20) NOT NULL,
  `path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `portal_support_events_created_at_index` (`created_at`),
  KEY `portal_support_events_support_session_id_created_at_index` (`support_session_id`,`created_at`),
  CONSTRAINT `portal_support_events_support_session_id_foreign` FOREIGN KEY (`support_session_id`) REFERENCES `portal_support_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
