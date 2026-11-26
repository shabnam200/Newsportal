-- News Entry module. The delivered SQL file referenced by the feature spec was not present;
-- this migration implements the schema changes listed there against the frozen baseline.
ALTER TABLE `news_common_info`
  MODIFY `news_id` int(11) NOT NULL AUTO_INCREMENT,
  MODIFY `cat_id` int(11) NOT NULL,
  CHANGE `sub_cat_id` `cat_child_1` int(11) NOT NULL DEFAULT 0,
  ADD `cat_child_2` int(11) NOT NULL DEFAULT 0 AFTER `cat_child_1`;

ALTER TABLE `news_info` MODIFY `news_id` int(11) NOT NULL;

CREATE TABLE IF NOT EXISTS `news_image_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `news_id` int(11) NOT NULL,
  `section_no` int(3) NOT NULL,
  `media_type` tinyint(1) NOT NULL,
  `img_path` varchar(500) NOT NULL DEFAULT '',
  `video_link` varchar(255) NOT NULL DEFAULT '',
  `caption` varchar(300) NOT NULL DEFAULT '',
  `is_cover` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `news_image_info_news_id_index` (`news_id`),
  UNIQUE KEY `news_image_info_news_section_unique` (`news_id`, `section_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `news_type_info` (`news_type_id`, `type_name`, `slug_name`, `last_modify`, `creator`, `type_status`)
VALUES (1, 'Normal', 'normal', CURRENT_TIMESTAMP, 0, 1),
       (2, 'Photo', 'photo', CURRENT_TIMESTAMP, 0, 1),
       (3, 'Video', 'video', CURRENT_TIMESTAMP, 0, 1)
ON DUPLICATE KEY UPDATE `type_name` = VALUES(`type_name`), `slug_name` = VALUES(`slug_name`), `type_status` = 1;
