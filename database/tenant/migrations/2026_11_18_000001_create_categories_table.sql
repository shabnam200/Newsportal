-- One `categories` table replaces `category_info` + `sub_category_info`.
-- Hierarchy is stored with parent_id (NULL = top level) and `level` (1, 2 or 3 – max 3 layers).
-- Runs once per tenant database. Existing rows are copied, then the two old tables are dropped.

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) UNSIGNED DEFAULT NULL,
  `level` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `title` varchar(100) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `en_name` varchar(100) DEFAULT NULL,
  `key_name` varchar(100) NOT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `creator` int(5) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `legacy_sub_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categories_parent_id_index` (`parent_id`),
  KEY `categories_level_index` (`level`),
  KEY `categories_key_name_index` (`key_name`),
  CONSTRAINT `categories_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Level 1: old categories keep their ids (news / ads that point to a category keep working).
INSERT INTO `categories` (`id`, `parent_id`, `level`, `title`, `name`, `en_name`, `key_name`, `keywords`, `description`, `status`, `creator`, `created_at`, `updated_at`)
SELECT c.`cat_id`, NULL, 1, c.`title`, c.`cat_name`, c.`cat_en_name`, c.`cat_key_name`, c.`keyword`, c.`description`, c.`cat_status`, c.`cat_creator`, c.`cat_doc`, c.`cat_dom`
FROM `category_info` c
ORDER BY c.`cat_id`;

-- Level 2: old sub-categories get new ids (they would clash with category ids); the old id is kept
-- in legacy_sub_id only until the references below are updated.
INSERT INTO `categories` (`parent_id`, `level`, `title`, `name`, `en_name`, `key_name`, `keywords`, `description`, `status`, `creator`, `created_at`, `updated_at`, `legacy_sub_id`)
SELECT s.`category_id`, 2, s.`title`, s.`sub_cat_name`, s.`sub_cat_en_name`, s.`sub_cat_key_name`, s.`keywords`, s.`description`, s.`sub_cat_status`, s.`creator`,
       IF(s.`doc` IS NULL OR s.`doc` < '1970-01-02', CURRENT_TIMESTAMP, s.`doc`),
       COALESCE(s.`dom`, CURRENT_TIMESTAMP),
       s.`sub_category_id`
FROM `sub_category_info` s
WHERE s.`category_id` IN (SELECT `cat_id` FROM `category_info`)
ORDER BY s.`sub_category_id`;

-- Point existing news at the new sub-category ids.
UPDATE `news_common_info` n
JOIN `categories` c ON c.`legacy_sub_id` = n.`sub_cat_id`
SET n.`sub_cat_id` = c.`id`
WHERE n.`sub_cat_id` > 0;

UPDATE `temp_news_info` t
JOIN `categories` c ON c.`legacy_sub_id` = t.`sub_category`
SET t.`sub_category` = c.`id`
WHERE t.`sub_category` > 0;

ALTER TABLE `categories` DROP COLUMN `legacy_sub_id`;

DROP TABLE IF EXISTS `sub_category_info`;
DROP TABLE IF EXISTS `category_info`;
