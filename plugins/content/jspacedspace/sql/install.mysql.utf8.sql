CREATE TABLE IF NOT EXISTS `#__jspacedspace_records` (
	`record_id` INTEGER NOT NULL,
	`dspace_id` INTEGER NOT NULL,
	PRIMARY KEY (`record_id`, `dspace_id`),
	KEY `idx_jspacedspace_records_record_id` (`record_id`),
	KEY `idx_jspacedspace_records_dspace_id` (`dspace_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;