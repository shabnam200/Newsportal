-- Reporters are stored by name: one news_writer_info row per reporter per news, in `author_name`.
-- The news-entry reporter dropdown lists the distinct names found here.
SET @nw_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'news_writer_info' AND column_name = 'reporter_ids') > 0,
  'ALTER TABLE `news_writer_info` CHANGE `reporter_ids` `author_name` varchar(255) DEFAULT NULL',
  'SELECT 1 INTO @nw_noop'
);
PREPARE nw_stmt FROM @nw_sql;
EXECUTE nw_stmt;
DEALLOCATE PREPARE nw_stmt;

SET @nw_sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'news_writer_info' AND column_name = 'author_name' AND seq_in_index = 1) > 0,
  'SELECT 1 INTO @nw_noop',
  'CREATE INDEX `news_writer_info_author_name_idx` ON `news_writer_info` (`author_name`)'
);
PREPARE nw_stmt FROM @nw_sql;
EXECUTE nw_stmt;
DEALLOCATE PREPARE nw_stmt;
