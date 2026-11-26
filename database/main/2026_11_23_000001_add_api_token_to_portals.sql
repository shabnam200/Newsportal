-- Public read-only API: one secret token per portal (tenant).
-- Import this ONCE into the main database (phpMyAdmin > Import). Safe to run again.
-- Only the SHA-256 hash of the token is stored, never the token itself.
-- Do NOT run `php artisan migrate` (see database/main/README.md).
SET NAMES utf8mb4;

SET @api_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'portals' AND column_name = 'api_token_hash') > 0,
  'SELECT 1 INTO @api_noop',
  'ALTER TABLE `portals`
     ADD COLUMN `api_token_hash` char(64) NULL DEFAULT NULL AFTER `password`,
     ADD COLUMN `api_token_last4` char(4) NULL DEFAULT NULL AFTER `api_token_hash`,
     ADD COLUMN `api_token_created_at` timestamp NULL DEFAULT NULL AFTER `api_token_last4`,
     ADD COLUMN `api_token_last_used_at` timestamp NULL DEFAULT NULL AFTER `api_token_created_at`,
     ADD UNIQUE KEY `portals_api_token_hash_unique` (`api_token_hash`)'
);
PREPARE api_stmt FROM @api_sql;
EXECUTE api_stmt;
DEALLOCATE PREPARE api_stmt;
