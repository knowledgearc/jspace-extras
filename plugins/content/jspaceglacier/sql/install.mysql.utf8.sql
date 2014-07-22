CREATE TABLE IF NOT EXISTS `#__jspaceglacier_archives` (
	`hash` VARCHAR(255) NOT NULL,
	`vault` VARCHAR(255) NOT NULL,
	`region` VARCHAR(255) NOT NULL,
	`valid` TINYINT NOT NULL DEFAULT 1,
	`job_id` INT NOT NULL,
	`jspaceasset_id` INT NOT NULL,
	PRIMARY KEY (`hash`, `jspaceasset_id`),
	KEY `idx_jspaceglacier_archives_hash` (`hash`),
	KEY `idx_jspaceglacier_archives_jspaceasset_id` (`jspaceasset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;