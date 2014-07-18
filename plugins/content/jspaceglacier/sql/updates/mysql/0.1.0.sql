CREATE TABLE IF NOT EXISTS `#__jspaceglacier_archives` (
	`hash` varchar(255) NOT NULL,
	`vault` varchar(255) NOT NULL,
	`region` varchar(255) NOT NULL,
	`valid` tinyint NOT NULL DEFAULT 1,
	`asset_id` int(10) unsigned NOT NULL,
	PRIMARY KEY (`hash`, `asset_id`),
	KEY `idx_jspaceglacier_archives_hash` (`hash`)
	KEY `idx_jspaceglacier_archives_asset_id` (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;