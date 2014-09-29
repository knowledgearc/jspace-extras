CREATE TABLE `jos_assets` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `parent_id` INTEGER NOT NULL DEFAULT '0',
  `lft` INTEGER NOT NULL DEFAULT '0',
  `rgt` INTEGER NOT NULL DEFAULT '0',
  `level` INTEGER NOT NULL,
  `name` TEXT NOT NULL DEFAULT '',
  `title` TEXT NOT NULL DEFAULT '',
  `rules` TEXT NOT NULL DEFAULT '',
  CONSTRAINT `idx_assets_name` UNIQUE (`name`)
);

CREATE INDEX `idx_assets_left_right` ON `jos_assets` (`lft`,`rgt`);
CREATE INDEX `idx_assets_parent_id` ON `jos_assets` (`parent_id`);

CREATE TABLE `jos_categories` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `asset_id` INTEGER NOT NULL DEFAULT '0',
  `parent_id` INTEGER NOT NULL DEFAULT '0',
  `lft` INTEGER NOT NULL DEFAULT '0',
  `rgt` INTEGER NOT NULL DEFAULT '0',
  `level` INTEGER NOT NULL DEFAULT '0',
  `path` TEXT NOT NULL DEFAULT '',
  `extension` TEXT NOT NULL DEFAULT '',
  `title` TEXT NOT NULL DEFAULT '',
  `alias` TEXT NOT NULL DEFAULT '',
  `note` TEXT NOT NULL DEFAULT '',
  `description` TEXT NOT NULL DEFAULT '',
  `published` INTEGER NOT NULL DEFAULT '0',
  `checked_out` INTEGER NOT NULL DEFAULT '0',
  `checked_out_time` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access` INTEGER NOT NULL DEFAULT '0',
  `params` TEXT NOT NULL DEFAULT '',
  `metadesc` TEXT NOT NULL DEFAULT '',
  `metakey` TEXT NOT NULL DEFAULT '',
  `metadata` TEXT NOT NULL DEFAULT '',
  `created_user_id` INTEGER NOT NULL DEFAULT '0',
  `created_time` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_user_id` INTEGER NOT NULL DEFAULT '0',
  `modified_time` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hits` INTEGER NOT NULL DEFAULT '0',
  `language` TEXT NOT NULL DEFAULT '',
	`version` INTEGER NOT NULL DEFAULT '1'
);

CREATE INDEX `idx_categories_lookup` ON `jos_categories` (`extension`,`published`,`access`);
CREATE INDEX `idx_categories_access` ON `jos_categories` (`access`);
CREATE INDEX `idx_categories_checkout` ON `jos_categories` (`checked_out`);
CREATE INDEX `idx_categories_path` ON `jos_categories` (`path`);
CREATE INDEX `idx_categories_left_right` ON `jos_categories` (`lft`,`rgt`);
CREATE INDEX `idx_categories_alias` ON `jos_categories` (`alias`);
CREATE INDEX `idx_categories_language` ON `jos_categories` (`language`);

CREATE TABLE `jos_usergroups` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `parent_id` INTEGER NOT NULL DEFAULT '0',
  `lft` INTEGER NOT NULL DEFAULT '0',
  `rgt` INTEGER NOT NULL DEFAULT '0',
  `title` TEXT NOT NULL DEFAULT '',
  CONSTRAINT `idx_usergroups_parent_title_lookup` UNIQUE (`parent_id`,`title`)
);

CREATE INDEX `idx_usergroups_title_lookup` ON `jos_usergroups` (`title`);
CREATE INDEX `idx_usergroups_adjacency_lookup` ON `jos_usergroups` (`parent_id`);
CREATE INDEX `idx_usergroups_nested_set_lookup` ON `jos_usergroups` (`lft`,`rgt`);

CREATE TABLE `jos_viewlevels` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `title` TEXT NOT NULL DEFAULT '',
  `ordering` INTEGER NOT NULL DEFAULT '0',
  `rules` TEXT NOT NULL DEFAULT '',
  CONSTRAINT `idx_viewlevels_title` UNIQUE (`title`)
);

CREATE TABLE `jos_extensions` (
  `extension_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL DEFAULT '',
  `type` TEXT NOT NULL DEFAULT '',
  `element` TEXT NOT NULL DEFAULT '',
  `folder` TEXT NOT NULL DEFAULT '',
  `client_id` INTEGER NOT NULL,
  `enabled` INTEGER NOT NULL DEFAULT '1',
  `access` INTEGER NOT NULL DEFAULT '1',
  `protected` INTEGER NOT NULL DEFAULT '0',
  `manifest_cache` TEXT NOT NULL DEFAULT '',
  `params` TEXT NOT NULL DEFAULT '',
  `custom_data` TEXT NOT NULL DEFAULT '',
  `system_data` TEXT NOT NULL DEFAULT '',
  `checked_out` INTEGER NOT NULL DEFAULT '0',
  `checked_out_time` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering` INTEGER DEFAULT '0',
  `state` INTEGER DEFAULT '0'
);

CREATE INDEX `idx_extensions_client_id` ON `jos_extensions` (`element`,`client_id`);
CREATE INDEX `idx_extensions_folder_client_id` ON `jos_extensions` (`element`,`folder`,`client_id`);
CREATE INDEX `idx_extensions_lookup` ON `jos_extensions` (`type`,`element`,`folder`,`client_id`);

CREATE TABLE `jos_jspace_records` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `asset_id` INTEGER NOT NULL DEFAULT 0,
    `title` VARCHAR(1024) NOT NULL,
    `alias` VARCHAR(255) NOT NULL DEFAULT '',
    `published` TINYINT NOT NULL DEFAULT 0,
    `language` CHAR(7) NOT NULL,
    `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `created_by` INTEGER NOT NULL DEFAULT 0,
    `modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by` INTEGER NOT NULL DEFAULT 0,
    `checked_out` INTEGER NOT NULL DEFAULT 0,
    `checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `publish_up` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `publish_down` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `metadata` TEXT NOT NULL,
    `level` INTEGER NOT NULL DEFAULT 0,
    `path` VARCHAR(1024) NOT NULL,
    `lft` INTEGER NOT NULL DEFAULT 0,
    `rgt` INTEGER NOT NULL DEFAULT 0,
    `schema` VARCHAR(255) NOT NULL DEFAULT '[No Schema]',
    `parent_id` INTEGER NOT NULL DEFAULT 0,
    `ordering` INTEGER NOT NULL DEFAULT 0,
    `version` INTEGER NOT NULL DEFAULT 1,
    `access` INTEGER NOT NULL DEFAULT 0,
    `catid` INTEGER NOT NULL
);

CREATE INDEX `idx_jspace_records_access` ON `jos_jspace_records` (`access`);
CREATE INDEX `idx_jspace_records_checkout` ON `jos_jspace_records` (`checked_out`);
CREATE INDEX `idx_jspace_records_published` ON `jos_jspace_records` (`published`);
CREATE INDEX `idx_jspace_records_parent_id` ON `jos_jspace_records` (`parent_id`);
CREATE INDEX `idx_jspace_records_schema` ON `jos_jspace_records` (`schema`);
CREATE INDEX `idx_jspace_records_createdby` ON `jos_jspace_records` (`created_by`);
CREATE INDEX `idx_jspace_records_language` ON `jos_jspace_records` (`language`);

CREATE TABLE `jos_jspace_assets` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `contentType` VARCHAR(255) NOT NULL,
    `contentLength` INTEGER NOT NULL,
    `hash` VARCHAR(255) NOT NULL,
    `metadata` TEXT NOT NULL,
    `derivative` VARCHAR(255) NOT NULL,
    `record_id` INTEGER NOT NULL
);

CREATE INDEX `idx_jspace_assets_hash` ON `jos_jspace_assets` (`hash`);
CREATE INDEX `idx_jspace_assets_record_id` ON `jos_jspace_assets` (`record_id`);


CREATE TABLE `jos_jspace_harvests` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `originating_url` VARCHAR(255) NOT NULL,
    `harvester` VARCHAR(255) NOT NULL,
    `frequency` INTEGER NOT NULL DEFAULT 0,
    `total` INTEGER NOT NULL DEFAULT 0,
    `harvested` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `params` TEXT NOT NULL,
    `state` TINYINT NOT NULL DEFAULT 0,
    `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `created_by` INTEGER NOT NULL DEFAULT 0,
    `modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by` INTEGER NOT NULL DEFAULT 0,
    `checked_out` INTEGER NOT NULL DEFAULT 0,
    `checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `catid` INTEGER NOT NULL DEFAULT '0'
);

CREATE INDEX `idx_jspace_harvests_checkout` ON `jos_jspace_harvests` (`checked_out`);
CREATE INDEX `idx_jspace_harvests_state` ON `jos_jspace_harvests` (`state`);
CREATE INDEX `idx_jspace_harvests_createdby` ON `jos_jspace_harvests` (`created_by`);
CREATE INDEX `idx_jspace_record_categories_catid` ON `jos_jspace_harvests` (`catid`);

-- A holding table for records harvested.

CREATE TABLE `jos_jspace_cache` (
    `id` VARCHAR(255) NOT NULL,
    `data` TEXT NULL,
    `harvest_id` INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY(`id`, `harvest_id`)
);

CREATE INDEX `idx_jspace_cache_harvest_id` ON `jos_jspace_cache` (`harvest_id`);

CREATE TABLE `jos_content_types` (
  `type_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `type_title` TEXT NOT NULL DEFAULT '',
  `type_alias` TEXT NOT NULL DEFAULT '',
  `table` TEXT NOT NULL DEFAULT '',
  `rules` TEXT NOT NULL DEFAULT '',
  `field_mappings` TEXT NOT NULL DEFAULT '',
  `router` TEXT NOT NULL DEFAULT '',
  `content_history_options` varchar(5120)
  );

CREATE INDEX `idx_content_types_alias` ON `jos_content_types` (`type_alias`);

CREATE TABLE IF NOT EXISTS `jos_ucm_history` (
  `version_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `ucm_item_id` INTEGER NOT NULL,
  `ucm_type_id` INTEGER NOT NULL,
  `version_note` VARCHAR(255) NOT NULL DEFAULT '',
  `save_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `editor_user_id` INTEGER NOT NULL DEFAULT '0',
  `character_count` INTEGER NOT NULL DEFAULT '0',
  `sha1_hash` VARCHAR(50) NOT NULL DEFAULT '',
  `version_data` MEDIUMTEXT NOT NULL,
  `keep_forever` TINYINT NOT NULL DEFAULT '0'
);

CREATE INDEX `idx_ucm_item_id` ON `jos_ucm_history` (`ucm_type_id`,`ucm_item_id`);
CREATE INDEX `idx_save_date` ON `jos_ucm_history` (`save_date`);

CREATE TABLE `jos_tags` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `parent_id` INTEGER NOT NULL DEFAULT '0',
  `lft` INTEGER NOT NULL DEFAULT '0',
  `rgt` INTEGER NOT NULL DEFAULT '0',
  `level` INTEGER NOT NULL DEFAULT '0',
  `path` TEXT NOT NULL DEFAULT '',
  `extension` TEXT NOT NULL DEFAULT '',
  `title` TEXT NOT NULL DEFAULT '',
  `alias` TEXT NOT NULL DEFAULT '',
  `note` TEXT NOT NULL DEFAULT '',
  `description` TEXT NOT NULL DEFAULT '',
  `published` INTEGER NOT NULL DEFAULT '0',
  `checked_out` INTEGER NOT NULL DEFAULT '0',
  `checked_out_time` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access` INTEGER NOT NULL DEFAULT '0',
  `params` TEXT NOT NULL DEFAULT '',
  `metadesc` TEXT NOT NULL DEFAULT '',
  `metakey` TEXT NOT NULL DEFAULT '',
  `metadata` TEXT NOT NULL DEFAULT '',
  `created_user_id` INTEGER NOT NULL DEFAULT '0',
  `created_time` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by_alias` TEXT NOT NULL DEFAULT '',
  `modified_user_id` INTEGER NOT NULL DEFAULT '0',
  `modified_time` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `images` TEXT NOT NULL DEFAULT '',
  `urls` TEXT NOT NULL DEFAULT '',
  `hits` INTEGER NOT NULL DEFAULT '0',
  `language` TEXT NOT NULL DEFAULT '',
  `version` INTEGER NOT NULL DEFAULT '1',
  `publish_up` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00'
);

CREATE INDEX `idx_tags_lookup` ON `jos_tags` (`published`,`access`);
CREATE INDEX `idx_tags_access` ON `jos_tags` (`access`);
CREATE INDEX `idx_tags_checkout` ON `jos_tags` (`checked_out`);
CREATE INDEX `idx_tags_path` ON `jos_tags` (`path`);
CREATE INDEX `idx_tags_left_right` ON `jos_tags` (`lft`,`rgt`);
CREATE INDEX `idx_tags_alias` ON `jos_tags` (`alias`);
CREATE INDEX `idx_tags_language` ON `jos_tags` (`language`);

CREATE TABLE IF NOT EXISTS `jos_contentitem_tag_map` (
  `type_alias` TEXT NOT NULL DEFAULT '',
  `core_content_id` INTEGER NOT NULL,
  `content_item_id` INTEGER NOT NULL,
  `tag_id` INTEGER NOT NULL,
  `tag_date` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `type_id` INTEGER NOT NULL,
	CONSTRAINT `idx_contentitemtagmap_uc_ItemnameTagid` UNIQUE (`type_id`,`content_item_id`,`tag_id`)
);

CREATE INDEX `idx_contentitem_tag_map_tag_type` ON `jos_contentitem_tag_map` (`tag_id`,`type_id`);
CREATE INDEX `idx_contentitem_tag_map_date_id` ON `jos_contentitem_tag_map` (`tag_date`,`tag_id`);
CREATE INDEX `idx_contentitem_tag_map_tag` ON `jos_contentitem_tag_map` (`tag_id`);
CREATE INDEX `idx_contentitem_tag_map_type` ON `jos_contentitem_tag_map` (`type_id`);
CREATE INDEX `idx_contentitem_tag_map_core_content_id` ON `jos_contentitem_tag_map` (`core_content_id`);

CREATE TABLE `jos_ucm_base` (
  `ucm_id` INTEGER PRIMARY KEY,
  `ucm_item_id` INTEGER NOT NULL DEFAULT '0',
  `ucm_type_id` INTEGER NOT NULL DEFAULT '0',
  `ucm_language_id` INTEGER NOT NULL DEFAULT '0'
);

CREATE INDEX `idx_ucm_base_ucm_item_id` ON `jos_ucm_base` (`ucm_item_id`);
CREATE INDEX `idx_ucm_base_ucm_type_id` ON `jos_ucm_base` (`ucm_type_id`);
CREATE INDEX `idx_ucm_base_ucm_language_id` ON `jos_ucm_base` (`ucm_language_id`);

CREATE TABLE `jos_jspacedspace_records` (
    `record_id` INTEGER NOT NULL,
    `dspace_id` INTEGER NOT NULL,
    PRIMARY KEY (`record_id`, `dspace_id`)
);

CREATE INDEX `idx_jspacedspace_records_record_id` ON `jos_jspacedspace_records` (`record_id`);
CREATE INDEX `idx_jspacedspace_records_dspace_id` ON `jos_jspacedspace_records` (`dspace_id`);

CREATE TABLE `jos_users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL DEFAULT '',
  `username` TEXT NOT NULL DEFAULT '',
  `email` TEXT NOT NULL DEFAULT '',
  `password` TEXT NOT NULL DEFAULT '',
  `block` INTEGER NOT NULL DEFAULT '0',
  `sendEmail` INTEGER DEFAULT '0',
  `registerDate` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastvisitDate` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `activation` TEXT NOT NULL DEFAULT '',
  `params` TEXT NOT NULL DEFAULT '',
    `lastResetTime` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
    `resetCount` INTEGER DEFAULT '0'
);

CREATE INDEX `idx_users_name` ON `jos_users` (`name`);
CREATE INDEX `idx_users_block` ON `jos_users` (`block`);
CREATE INDEX `idx_users_username` ON `jos_users` (`username`);
CREATE INDEX `idx_users_email` ON `jos_users` (`email`);

CREATE TABLE `jos_user_usergroup_map` (
  `user_id` INTEGER NOT NULL DEFAULT '0',
  `group_id` INTEGER NOT NULL DEFAULT '0',
  CONSTRAINT `idx_user_usergroup_map` PRIMARY KEY (`user_id`,`group_id`)
);

CREATE TABLE `jos_session` (
  `session_id` TEXT NOT NULL DEFAULT '',
  `client_id` INTEGER NOT NULL DEFAULT '0',
  `guest` INTEGER DEFAULT '1',
  `time` TEXT DEFAULT '',
  `data` TEXT DEFAULT NULL,
  `userid` INTEGER DEFAULT '0',
  `username` TEXT DEFAULT '',
  CONSTRAINT `idx_session` PRIMARY KEY (`session_id`)
);

CREATE INDEX `idx_session_user` ON `jos_session` (`userid`);
CREATE INDEX `idx_session_time` ON `jos_session` (`time`);

CREATE TABLE IF NOT EXISTS `jos_jspace_record_identifiers` (
    `id` VARCHAR(255) NOT NULL,
    `record_id` INTEGER NOT NULL,
    PRIMARY KEY (`id`, `record_id`)
);

CREATE INDEX `idx_jspace_record_identifiers_id` ON `jos_jspace_record_identifiers` (`id`);
CREATE INDEX `idx_jspace_record_identifiers_record_id` ON `jos_jspace_record_identifiers` (`record_id`);

CREATE TABLE IF NOT EXISTS `jos_weblinks` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `catid` INTEGER NOT NULL DEFAULT '0',
  `sid` INTEGER NOT NULL DEFAULT '0',
  `title` VARCHAR(250) NOT NULL DEFAULT '',
  `alias` VARCHAR(255) NOT NULL DEFAULT '',
  `url` VARCHAR(250) NOT NULL DEFAULT '',
  `description` TEXT NOT NULL DEFAULT '',
  `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hits` INTEGER NOT NULL DEFAULT '0',
  `state` TINYINT NOT NULL DEFAULT '0',
  `checked_out` INTEGER NOT NULL DEFAULT '0',
  `checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering` INTEGER NOT NULL DEFAULT '0',
  `archived` TINYINT NOT NULL DEFAULT '0',
  `approved` TINYINT NOT NULL DEFAULT '1',
  `access` INTEGER NOT NULL DEFAULT '1',
  `params` TEXT NOT NULL DEFAULT '',
  `language` CHAR(7) NOT NULL DEFAULT '',
  `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` INTEGER NOT NULL DEFAULT '0',
  `created_by_alias` VARCHAR(255) NOT NULL DEFAULT '',
  `modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` INTEGER NOT NULL DEFAULT '0',
  `metakey` TEXT NOT NULL DEFAULT '',
  `metadesc` TEXT NOT NULL DEFAULT '',
  `metadata` TEXT NOT NULL DEFAULT '',
  `featured` TINYINT NOT NULL DEFAULT '0',
  `xreference` VARCHAR(50) NOT NULL DEFAULT '',
  `publish_up` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'
);

CREATE TABLE IF NOT EXISTS `jos_jspace_references` (
    `id` INTEGER NOT NULL,
    `context` VARCHAR(255) NOT NULL,
    `record_id` INTEGER NOT NULL,
    PRIMARY KEY (`id`, `context`)
);

CREATE TABLE `jos_ucm_content` (
  `core_content_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `core_type_alias` TEXT NOT NULL DEFAULT '',
  `core_title` TEXT NOT NULL DEFAULT '',
  `core_alias` TEXT NOT NULL DEFAULT '',
  `core_body` TEXT NOT NULL DEFAULT '',
  `core_state` INTEGER NOT NULL DEFAULT '0',
  `core_checked_out_time` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `core_checked_out_user_id` INTEGER NOT NULL DEFAULT '0',
  `core_access` INTEGER NOT NULL DEFAULT '0',
  `core_params` TEXT NOT NULL DEFAULT '',
  `core_featured` INTEGER NOT NULL DEFAULT '0',
  `core_metadata` TEXT NOT NULL DEFAULT '',
  `core_created_user_id` INTEGER NOT NULL DEFAULT '0',
  `core_created_by_alias` TEXT NOT NULL DEFAULT '',
  `core_created_time` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `core_modified_user_id` INTEGER NOT NULL DEFAULT '0',
  `core_modified_time` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `core_language` TEXT NOT NULL DEFAULT '',
  `core_publish_up` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `core_publish_down` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `core_content_item_id` INTEGER NOT NULL DEFAULT '0',
  `asset_id` INTEGER NOT NULL DEFAULT '0',
  `core_images` TEXT NOT NULL DEFAULT '',
  `core_urls` TEXT NOT NULL DEFAULT '',
  `core_hits` INTEGER NOT NULL DEFAULT '0',
  `core_version` INTEGER NOT NULL DEFAULT '1',
  `core_ordering` INTEGER NOT NULL DEFAULT '0',
  `core_metakey` TEXT NOT NULL DEFAULT '',
  `core_metadesc` TEXT NOT NULL DEFAULT '',
  `core_catid` INTEGER NOT NULL DEFAULT '0',
  `core_xreference` TEXT NOT NULL DEFAULT '',
  `core_type_id` INTEGER NOT NULL DEFAULT '0'
);

CREATE INDEX `tag_idx` ON `jos_ucm_content` (`core_state`,`core_access`);
CREATE INDEX `idx_ucm_content_access` ON `jos_ucm_content` (`core_access`);
CREATE INDEX `idx_ucm_content_alias` ON `jos_ucm_content` (`core_alias`);
CREATE INDEX `idx_ucm_content_language` ON `jos_ucm_content` (`core_language`);
CREATE INDEX `idx_ucm_content_title` ON `jos_ucm_content` (`core_title`);
CREATE INDEX `idx_ucm_content_modified_time` ON `jos_ucm_content` (`core_modified_time`);
CREATE INDEX `idx_ucm_content_created_time` ON `jos_ucm_content` (`core_created_time`);
CREATE INDEX `idx_ucm_content_content_type` ON `jos_ucm_content` (`core_type_alias`);
CREATE INDEX `idx_ucm_content_core_modified_user_id` ON `jos_ucm_content` (`core_modified_user_id`);
CREATE INDEX `idx_ucm_content_core_checked_out_user_id` ON `jos_ucm_content` (`core_checked_out_user_id`);
CREATE INDEX `idx_ucm_content_core_created_user_id` ON `jos_ucm_content` (`core_created_user_id`);
CREATE INDEX `idx_ucm_content_core_type_id` ON `jos_ucm_content` (`core_type_id`);

CREATE TABLE `jos_languages` (
  `lang_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `lang_code` CHAR(7) NOT NULL DEFAULT '',
  `title` VARCHAR(50) NOT NULL DEFAULT '',
  `title_native` VARCHAR(50) NOT NULL DEFAULT '',
  `sef` VARCHAR(50) NOT NULL DEFAULT '',
  `image` VARCHAR(50) NOT NULL DEFAULT '',
  `description` VARCHAR(512) NOT NULL DEFAULT '',
  `metakey` TEXT NOT NULL DEFAULT '',
  `metadesc` TEXT NOT NULL DEFAULT '',
  `sitename` VARCHAR(1024) NOT NULL default '',
  `published` INTEGER NOT NULL DEFAULT '0',
  `access` INTEGER NOT NULL DEFAULT '1',
  `ordering` INTEGER NOT NULL default '0',
  CONSTRAINT `idx_languages_sef` UNIQUE (`sef`),
  CONSTRAINT `idx_languages_image` UNIQUE (`image`),
  CONSTRAINT `idx_languages_lang_code` UNIQUE (`lang_code`)
);

CREATE INDEX `idx_languages_ordering` ON `jos_languages` (`ordering`);