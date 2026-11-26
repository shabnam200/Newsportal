-- Views created in EVERY tenant database.
-- Uses CREATE OR REPLACE so it is safe to run again (new tenants, re-provision, existing tenants).
-- Add any new view below, each statement ending with a semicolon.

CREATE OR REPLACE VIEW `view_news_common_info` AS
SELECT *
FROM `news_common_info`
ORDER BY `news_id` DESC
LIMIT 1000;
