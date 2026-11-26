-- Add lookup indexes only when no existing index starts with news_id.
SET @news_entry_sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'news_tag_info' AND column_name = 'news_id' AND seq_in_index = 1) > 0,
  'SELECT 1 INTO @news_entry_noop',
  'CREATE INDEX `news_tag_info_news_id_idx` ON `news_tag_info` (`news_id`)'
);
PREPARE news_entry_stmt FROM @news_entry_sql;
EXECUTE news_entry_stmt;
DEALLOCATE PREPARE news_entry_stmt;

SET @news_entry_sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'news_seo_info' AND column_name = 'news_id' AND seq_in_index = 1) > 0,
  'SELECT 1 INTO @news_entry_noop',
  'CREATE INDEX `news_seo_info_news_id_idx` ON `news_seo_info` (`news_id`)'
);
PREPARE news_entry_stmt FROM @news_entry_sql;
EXECUTE news_entry_stmt;
DEALLOCATE PREPARE news_entry_stmt;

SET @news_entry_sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'news_writer_info' AND column_name = 'news_id' AND seq_in_index = 1) > 0,
  'SELECT 1 INTO @news_entry_noop',
  'CREATE INDEX `news_writer_info_news_id_idx` ON `news_writer_info` (`news_id`)'
);
PREPARE news_entry_stmt FROM @news_entry_sql;
EXECUTE news_entry_stmt;
DEALLOCATE PREPARE news_entry_stmt;

-- Add the cascade relationship only if this exact relationship is not already present.
SET @news_entry_sql = IF(
  (SELECT COUNT(*) FROM information_schema.key_column_usage WHERE table_schema = DATABASE() AND table_name = 'news_image_info' AND column_name = 'news_id' AND referenced_table_schema = DATABASE() AND referenced_table_name = 'news_common_info' AND referenced_column_name = 'news_id') > 0,
  'SELECT 1 INTO @news_entry_noop',
  'ALTER TABLE `news_image_info` ADD CONSTRAINT `news_image_info_news_id_fk` FOREIGN KEY (`news_id`) REFERENCES `news_common_info` (`news_id`) ON DELETE CASCADE'
);
PREPARE news_entry_stmt FROM @news_entry_sql;
EXECUTE news_entry_stmt;
DEALLOCATE PREPARE news_entry_stmt;
