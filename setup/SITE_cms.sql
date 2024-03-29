CREATE TABLE `cms_dashboard_widgets` (
  `user_id` int NOT NULL,
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `x` tinyint NOT NULL,
  `y` tinyint NOT NULL,
  `min` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cms_labels` (
  `identifier` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cms_language` (
  `lang_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `label` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `host_canonical` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_user_selectable` tinyint(1) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cms_language_hosts` (
  `host_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cms_users` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `uname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `biography` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `passhash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `ip` varbinary(16) DEFAULT NULL,
  `ip_proxy` varbinary(16) DEFAULT NULL,
  `labeler` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_apis` (
  `id` int NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `clients_user_group_id` int NOT NULL,
  `client_users_user_group_id` int NOT NULL,
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_limit_amount` float NOT NULL,
  `request_limit_unit` mediumint NOT NULL,
  `request_limit_ip` mediumint NOT NULL,
  `request_limit_global` mediumint NOT NULL,
  `documentation_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_api_clients` (
  `api_id` int NOT NULL,
  `user_id` int NOT NULL,
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_limit_disable` tinyint(1) NOT NULL,
  `time_amount` int NOT NULL,
  `time_unit` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_api_client_users` (
  `client_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `date_valid` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_api_hosts` (
  `api_id` int NOT NULL,
  `host_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_details` (
  `unique_row` tinyint(1) NOT NULL DEFAULT '1',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_nr` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `zipcode` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fax` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_nr` varchar(34) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `head_tags` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `html` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_header` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_footer` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `analytics_account` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `facebook` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `twitter` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `youtube` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_1100cc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_1100cc_host` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_1100cc_password` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `caching` tinyint(1) NOT NULL,
  `caching_external` tinyint(1) NOT NULL,
  `logging` tinyint(1) NOT NULL,
  `throttle` tinyint(1) NOT NULL,
  `https` tinyint(1) NOT NULL,
  `show_system_errors` tinyint(1) NOT NULL,
  `show_404` tinyint(1) NOT NULL,
  `use_servers` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_details_custom` (
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_details_hosts` (
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_details_servers` (
  `host_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `host_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `host_port` int NOT NULL,
  `login_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `login_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `passkey` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `extra` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_directories` (
  `id` int NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `root` tinyint(1) NOT NULL,
  `page_index_id` int DEFAULT NULL,
  `user_group_id` int NOT NULL,
  `require_login` tinyint(1) NOT NULL,
  `page_fallback_id` int DEFAULT NULL,
  `publish` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_directory_closure` (
  `ancestor_id` int NOT NULL,
  `descendant_id` int NOT NULL,
  `path_length` int NOT NULL,
  `sort` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_jobs` (
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `seconds` int NOT NULL,
  `date_executed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `running` tinyint(1) NOT NULL DEFAULT '0',
  `process_id` int DEFAULT NULL,
  `process_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_jobs_timer` (
  `unique_row` tinyint(1) NOT NULL DEFAULT '1',
  `date` datetime NOT NULL,
  `process_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_log` (
  `id` int NOT NULL,
  `label` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `msg` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `debug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `log_user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_log_users` (
  `id` int NOT NULL,
  `user_id` int NOT NULL DEFAULT '0',
  `user_class` tinyint NOT NULL,
  `ip` varbinary(16) NOT NULL,
  `ip_proxy` varbinary(16) NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `referral_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_pages` (
  `id` int NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `directory_id` int NOT NULL,
  `template_id` int NOT NULL,
  `master_id` int NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `html` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `script` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `publish` tinyint(1) NOT NULL,
  `clearance` tinyint(1) NOT NULL,
  `sort` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_page_internal_tags` (
  `page_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_page_modules` (
  `id` int NOT NULL,
  `page_id` int NOT NULL,
  `x` tinyint NOT NULL,
  `y` tinyint NOT NULL,
  `module` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `var` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shortcut` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shortcut_root` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_page_templates` (
  `id` int NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `html` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `html_raw` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `css` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `preview` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `column_count` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `row_count` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `margin_back` int NOT NULL,
  `margin_mod` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_tags` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_uris` (
  `uri_translator_id` int NOT NULL,
  `in_out` tinyint NOT NULL DEFAULT '1',
  `identifier` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remark` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `service` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_uri_translators` (
  `id` int NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode` tinyint NOT NULL DEFAULT '1',
  `host_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delay` int NOT NULL,
  `show_remark` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_uri_translator_hosts` (
  `uri_translator_id` int NOT NULL,
  `host_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_user_groups` (
  `id` int NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_user_group_link` (
  `group_id` int NOT NULL,
  `from_table` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_column` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_table` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_column` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `get_column` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `virtual_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `multi_source` tinyint(1) NOT NULL,
  `multi_target` tinyint(1) NOT NULL,
  `view` tinyint(1) NOT NULL,
  `sort` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `group_id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `uname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int NOT NULL,
  `passhash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `ip` varbinary(16) DEFAULT NULL,
  `ip_proxy` varbinary(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_account_key` (
  `user_id` int NOT NULL,
  `passkey` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_new` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_page_clearance` (
  `user_id` int NOT NULL,
  `page_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_webservice_key` (
  `user_id` int NOT NULL,
  `passkey` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_active` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `view_user_parent` (
`id` int
,`parent_group_id` int
,`parent_name` varchar(50)
);
DROP TABLE IF EXISTS `view_user_parent`;

CREATE VIEW `view_user_parent`  AS SELECT `u`.`id` AS `id`, `u`.`name` AS `parent_name`, `u`.`group_id` AS `parent_group_id` FROM `users` AS `u`  ;


ALTER TABLE `cms_dashboard_widgets`
  ADD PRIMARY KEY (`user_id`,`module`,`method`);

ALTER TABLE `cms_labels`
  ADD PRIMARY KEY (`identifier`,`lang_code`);

ALTER TABLE `cms_language`
  ADD PRIMARY KEY (`lang_code`);

ALTER TABLE `cms_language_hosts`
  ADD PRIMARY KEY (`host_name`,`lang_code`);

ALTER TABLE `cms_users`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `site_apis`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `site_api_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `api_id` (`api_id`,`user_id`);

ALTER TABLE `site_api_client_users`
  ADD PRIMARY KEY (`client_id`,`user_id`),
  ADD UNIQUE KEY `token` (`token`);

ALTER TABLE `site_api_hosts`
  ADD PRIMARY KEY (`host_name`,`api_id`) USING BTREE;

ALTER TABLE `site_details`
  ADD PRIMARY KEY (`unique_row`);

ALTER TABLE `site_details_custom`
  ADD PRIMARY KEY (`name`);

ALTER TABLE `site_details_hosts`
  ADD PRIMARY KEY (`name`);

ALTER TABLE `site_details_servers`
  ADD PRIMARY KEY (`host_name`,`host_type`),
  ADD KEY `status` (`status`);

ALTER TABLE `site_directories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `users_group_id` (`user_group_id`),
  ADD KEY `page_fallback_id` (`page_fallback_id`),
  ADD KEY `index_page` (`page_index_id`);

ALTER TABLE `site_directory_closure`
  ADD PRIMARY KEY (`ancestor_id`,`descendant_id`);

ALTER TABLE `site_jobs`
  ADD PRIMARY KEY (`module`,`method`),
  ADD KEY `running` (`running`),
  ADD KEY `seconds` (`seconds`,`date_executed`) USING BTREE,
  ADD KEY `process_id` (`process_id`) USING BTREE;

ALTER TABLE `site_jobs_timer`
  ADD PRIMARY KEY (`unique_row`);

ALTER TABLE `site_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `log_user_id` (`log_user_id`);

ALTER TABLE `site_log_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`user_class`) USING BTREE;

ALTER TABLE `site_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`,`directory_id`),
  ADD KEY `directory_id` (`directory_id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `master_id` (`master_id`);

ALTER TABLE `site_page_internal_tags`
  ADD PRIMARY KEY (`page_id`,`tag_id`);

ALTER TABLE `site_page_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_id` (`page_id`,`x`,`y`) USING BTREE;

ALTER TABLE `site_page_templates`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `site_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

ALTER TABLE `site_uris`
  ADD PRIMARY KEY (`uri_translator_id`,`in_out`,`identifier`),
  ADD KEY `service` (`service`);

ALTER TABLE `site_uri_translators`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `site_uri_translator_hosts`
  ADD PRIMARY KEY (`host_name`,`uri_translator_id`) USING BTREE;

ALTER TABLE `site_user_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

ALTER TABLE `site_user_group_link`
  ADD PRIMARY KEY (`group_id`,`from_table`,`from_column`,`to_table`,`to_column`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_id` (`group_id`,`uname`),
  ADD KEY `parent_id` (`parent_id`);

ALTER TABLE `user_account_key`
  ADD PRIMARY KEY (`user_id`);

ALTER TABLE `user_page_clearance`
  ADD PRIMARY KEY (`user_id`,`page_id`);

ALTER TABLE `user_webservice_key`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `passkey` (`passkey`);


ALTER TABLE `cms_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `site_apis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `site_directories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `site_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `site_log_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `site_pages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `site_page_modules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `site_page_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `site_tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `site_uri_translators`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `site_user_groups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
