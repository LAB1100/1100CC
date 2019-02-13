CREATE TABLE `data_blog_post_comments` (
  `id` int(11) NOT NULL,
  `blog_post_id` int(11) NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `added` datetime NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `log_user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_blog_post_xrefs` (
  `id` int(11) NOT NULL,
  `direction` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `added` datetime NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `blog_post_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `excerpt` mediumtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_dashboard_widgets` (
  `dashboard_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `x` tinyint(4) DEFAULT NULL,
  `y` tinyint(4) DEFAULT NULL,
  `min` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_email_addresses` (
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_email_addresses_bounces` (
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed` tinyint(1) NOT NULL,
  `count` tinyint(4) NOT NULL,
  `date_postponed` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_email_addresses_opt_out` (
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_form_submissions` (
  `id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `log_user_id` int(11) NOT NULL,
  `remark` mediumtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_form_submission_field_input` (
  `form_submission_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `value` varchar(5000) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_form_submission_field_sub_input` (
  `form_submission_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_sub_id` int(11) NOT NULL,
  `value` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_form_submission_internal_tags` (
  `form_submission_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_messaging_conversations` (
  `id` int(11) NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_messaging_conversation_last_seen` (
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_messaging_conversation_messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_messaging_conversation_participants` (
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `user_group_id` int(11) NOT NULL DEFAULT '0',
  `is_owner` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_poll_set_option_votes` (
  `poll_set_option_id` int(11) NOT NULL,
  `log_user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_blogs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_blog_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `cms_user_id` int(11) NOT NULL,
  `abstract` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `para_preview` tinyint(4) NOT NULL,
  `draft` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_blog_post_link` (
  `blog_id` int(11) NOT NULL,
  `blog_post_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_blog_post_tags` (
  `blog_post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_calendar_events` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `date_end` datetime NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_calendar_event_relations` (
  `calendar_event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_children` tinyint(1) NOT NULL,
  `user_group_id` int(11) NOT NULL,
  `cms_group` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_calendar_event_reminders` (
  `calendar_event_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `unit` int(11) NOT NULL,
  `executed` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_custom_content` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `style` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `script` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_custom_content_tags` (
  `custom_content_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_dashboards` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `directory_id` int(11) NOT NULL,
  `columns` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_dashboard_widgets` (
  `dashboard_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `x` tinyint(4) NOT NULL,
  `y` tinyint(4) NOT NULL,
  `min` tinyint(1) NOT NULL,
  `locked` tinyint(1) NOT NULL,
  `linked` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_forms` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `script` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `label_button` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `send_email` tinyint(1) NOT NULL,
  `response` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `redirect_page_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_form_fields` (
  `id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_sub_table` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `required` tinyint(1) NOT NULL,
  `sort` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_form_field_sub` (
  `id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_media` (
  `id` int(11) NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `directory` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_media_internal_tags` (
  `media_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_newsletters` (
  `id` int(11) NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `draft` tinyint(1) NOT NULL,
  `recipients` int(11) NOT NULL,
  `bounces` int(11) NOT NULL DEFAULT '0',
  `opt_out` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_newsletters_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_object_interaction_objects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `img` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shape` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `redirect_page_id` int(11) NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_object_interaction_object_link` (
  `object_interaction_object_id` int(11) NOT NULL,
  `object_interaction_stage_id` int(11) NOT NULL,
  `pos_y` decimal(7,4) NOT NULL,
  `pos_x` decimal(7,4) NOT NULL,
  `sort` tinyint(4) NOT NULL,
  `width` decimal(7,4) NOT NULL,
  `effect` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `effect_hover` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `script` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `script_hover` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `style` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `style_hover` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_object_interaction_stages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `img` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `view_x` decimal(4,2) NOT NULL,
  `view_y` decimal(4,2) NOT NULL,
  `height_full` tinyint(1) NOT NULL,
  `zoom_auto` tinyint(1) NOT NULL,
  `zoom_min` tinyint(4) NOT NULL,
  `zoom_max` tinyint(4) NOT NULL,
  `zoom_levels` tinyint(4) NOT NULL,
  `zoom_level_default` tinyint(4) NOT NULL,
  `script` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_polls` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_poll_sets` (
  `id` int(11) NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_poll_set_link` (
  `poll_id` int(11) NOT NULL,
  `poll_set_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_poll_set_options` (
  `id` int(11) NOT NULL,
  `poll_set_id` int(11) NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_projects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `img` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_sliders` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timeout` int(11) NOT NULL,
  `speed` int(11) NOT NULL,
  `effect` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_slider_slides` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_slider_slide_link` (
  `slider_id` int(11) NOT NULL,
  `slider_slide_id` int(11) NOT NULL,
  `media_internal_tag_id` int(11) NOT NULL,
  `sort` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_tags` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_cache_files` (
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_log_requests` (
  `id` int(11) NOT NULL,
  `type` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varbinary(16) NOT NULL,
  `ip_block` varbinary(16) NOT NULL,
  `date` datetime NOT NULL,
  `identifier` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_user_labels` (
  `identifier` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` longtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_details` (
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `surname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `street` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `streetnr` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_preferences_messaging` (
  `user_id` int(11) NOT NULL,
  `notify_email` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `data_blog_post_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `blog_post_id` (`blog_post_id`),
  ADD KEY `log_user_id` (`log_user_id`);

ALTER TABLE `data_blog_post_xrefs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `blog_post_id` (`blog_post_id`);

ALTER TABLE `data_dashboard_widgets`
  ADD PRIMARY KEY (`dashboard_id`,`module_id`,`method`,`user_id`);

ALTER TABLE `data_email_addresses`
  ADD PRIMARY KEY (`email`);

ALTER TABLE `data_email_addresses_bounces`
  ADD PRIMARY KEY (`email`);

ALTER TABLE `data_email_addresses_opt_out`
  ADD PRIMARY KEY (`email`);

ALTER TABLE `data_form_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`),
  ADD KEY `log_user_id` (`log_user_id`);

ALTER TABLE `data_form_submission_field_input`
  ADD PRIMARY KEY (`form_submission_id`,`field_id`);

ALTER TABLE `data_form_submission_field_sub_input`
  ADD PRIMARY KEY (`form_submission_id`,`field_id`,`field_sub_id`);

ALTER TABLE `data_form_submission_internal_tags`
  ADD PRIMARY KEY (`form_submission_id`,`tag_id`);

ALTER TABLE `data_messaging_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

ALTER TABLE `data_messaging_conversation_last_seen`
  ADD PRIMARY KEY (`conversation_id`,`user_id`);

ALTER TABLE `data_messaging_conversation_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`,`user_id`);

ALTER TABLE `data_messaging_conversation_participants`
  ADD PRIMARY KEY (`conversation_id`,`user_id`,`user_group_id`);

ALTER TABLE `data_poll_set_option_votes`
  ADD PRIMARY KEY (`poll_set_option_id`,`log_user_id`);

ALTER TABLE `def_blogs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cms_user_id` (`cms_user_id`);

ALTER TABLE `def_blog_post_link`
  ADD PRIMARY KEY (`blog_id`,`blog_post_id`);

ALTER TABLE `def_blog_post_tags`
  ADD PRIMARY KEY (`blog_post_id`,`tag_id`);

ALTER TABLE `def_calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date` (`date`);

ALTER TABLE `def_calendar_event_relations`
  ADD PRIMARY KEY (`calendar_event_id`,`user_id`,`user_group_id`,`cms_group`);

ALTER TABLE `def_calendar_event_reminders`
  ADD PRIMARY KEY (`calendar_event_id`,`amount`,`unit`);

ALTER TABLE `def_custom_content`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_custom_content_tags`
  ADD PRIMARY KEY (`custom_content_id`,`tag_id`);

ALTER TABLE `def_dashboards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `directory_id` (`directory_id`);

ALTER TABLE `def_dashboard_widgets`
  ADD PRIMARY KEY (`dashboard_id`,`module_id`,`method`);

ALTER TABLE `def_forms`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_form_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`);

ALTER TABLE `def_form_field_sub`
  ADD PRIMARY KEY (`id`),
  ADD KEY `field_id` (`field_id`);

ALTER TABLE `def_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`);

ALTER TABLE `def_media_internal_tags`
  ADD PRIMARY KEY (`media_id`,`tag_id`);

ALTER TABLE `def_newsletters`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_newsletters_templates`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_object_interaction_objects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `redirect_page_id` (`redirect_page_id`);

ALTER TABLE `def_object_interaction_object_link`
  ADD PRIMARY KEY (`object_interaction_object_id`,`object_interaction_stage_id`,`pos_y`,`pos_x`);

ALTER TABLE `def_object_interaction_stages`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_polls`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_poll_sets`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_poll_set_link`
  ADD PRIMARY KEY (`poll_id`,`poll_set_id`);

ALTER TABLE `def_poll_set_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `poll_set_id` (`poll_set_id`);

ALTER TABLE `def_projects`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_sliders`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_slider_slides`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_slider_slide_link`
  ADD PRIMARY KEY (`slider_id`,`slider_slide_id`,`media_internal_tag_id`,`sort`);

ALTER TABLE `def_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

ALTER TABLE `site_cache_files`
  ADD PRIMARY KEY (`filename`);

ALTER TABLE `site_log_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `identifier` (`type`,`date`,`identifier`) USING BTREE,
  ADD KEY `ip` (`type`,`date`,`ip_block`,`ip`) USING BTREE;

ALTER TABLE `site_user_labels`
  ADD PRIMARY KEY (`identifier`,`lang_code`,`user_id`);

ALTER TABLE `user_details`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

ALTER TABLE `user_preferences_messaging`
  ADD PRIMARY KEY (`user_id`);


ALTER TABLE `data_blog_post_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `data_blog_post_xrefs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `data_form_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `data_messaging_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `data_messaging_conversation_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_blogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_custom_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_dashboards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_form_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_form_field_sub`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_newsletters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_newsletters_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_object_interaction_objects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_object_interaction_stages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_polls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_poll_sets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_poll_set_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_sliders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_slider_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `site_log_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
