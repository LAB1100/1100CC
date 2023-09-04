CREATE TABLE `data_blog_post_comments` (
  `id` int NOT NULL,
  `blog_post_id` int NOT NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `added` datetime NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `log_user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_blog_post_xrefs` (
  `id` int NOT NULL,
  `direction` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `added` datetime NOT NULL,
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `blog_post_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `excerpt` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_dashboard_widgets` (
  `dashboard_id` int NOT NULL,
  `module_id` int NOT NULL,
  `method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `x` tinyint DEFAULT NULL,
  `y` tinyint DEFAULT NULL,
  `min` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_email_addresses` (
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_email_addresses_bounces` (
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed` tinyint(1) NOT NULL,
  `count` tinyint NOT NULL,
  `date_postponed` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_email_addresses_opt_out` (
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_form_submissions` (
  `id` int NOT NULL,
  `form_id` int NOT NULL,
  `date` datetime NOT NULL,
  `log_user_id` int NOT NULL,
  `remark` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_form_submission_field_input` (
  `form_submission_id` int NOT NULL,
  `field_id` int NOT NULL,
  `value` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_form_submission_field_sub_input` (
  `form_submission_id` int NOT NULL,
  `field_id` int NOT NULL,
  `field_sub_id` int NOT NULL,
  `value` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_form_submission_internal_tags` (
  `form_submission_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_messaging_conversations` (
  `id` int NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_messaging_conversation_last_seen` (
  `conversation_id` int NOT NULL,
  `user_id` int NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_messaging_conversation_messages` (
  `id` int NOT NULL,
  `conversation_id` int NOT NULL,
  `user_id` int NOT NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_messaging_conversation_participants` (
  `conversation_id` int NOT NULL,
  `user_id` int NOT NULL DEFAULT '0',
  `user_group_id` int NOT NULL DEFAULT '0',
  `is_owner` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_poll_set_option_votes` (
  `poll_set_option_id` int NOT NULL,
  `log_user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_blogs` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_blog_posts` (
  `id` int NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cms_user_id` int NOT NULL,
  `abstract` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `para_preview` tinyint NOT NULL,
  `draft` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_blog_post_link` (
  `blog_id` int NOT NULL,
  `blog_post_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_blog_post_tags` (
  `blog_post_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_calendar_events` (
  `id` int NOT NULL,
  `date` datetime NOT NULL,
  `date_end` datetime NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_calendar_event_relations` (
  `calendar_event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `user_children` tinyint(1) NOT NULL,
  `user_group_id` int NOT NULL,
  `cms_group` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_calendar_event_reminders` (
  `calendar_event_id` int NOT NULL,
  `amount` int NOT NULL,
  `unit` int NOT NULL,
  `executed` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_custom_content` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `style` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `script` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_custom_content_tags` (
  `custom_content_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_dashboards` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `directory_id` int NOT NULL,
  `columns` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_dashboard_widgets` (
  `dashboard_id` int NOT NULL,
  `module_id` int NOT NULL,
  `method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `x` tinyint NOT NULL,
  `y` tinyint NOT NULL,
  `min` tinyint(1) NOT NULL,
  `locked` tinyint(1) NOT NULL,
  `linked` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_documentations` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_documentation_sections` (
  `id` int NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `documentation_id` int NOT NULL,
  `parent_section_id` int NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `publish` tinyint(1) NOT NULL,
  `sort` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_forms` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `script` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label_button` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `send_email` tinyint(1) NOT NULL,
  `response` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `redirect_page_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_form_fields` (
  `id` int NOT NULL,
  `form_id` int NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_sub_table` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `required` tinyint(1) NOT NULL,
  `sort` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_form_field_sub` (
  `id` int NOT NULL,
  `field_id` int NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_media` (
  `id` int NOT NULL,
  `label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `directory` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_media_internal_tags` (
  `media_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_newsletters` (
  `id` int NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `draft` tinyint(1) NOT NULL,
  `recipients` int NOT NULL,
  `bounces` int NOT NULL DEFAULT '0',
  `opt_out` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_newsletters_templates` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_object_interaction_objects` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shape` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `class` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `redirect_page_id` int NOT NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_object_interaction_object_link` (
  `object_interaction_object_id` int NOT NULL,
  `object_interaction_stage_id` int NOT NULL,
  `pos_y` decimal(7,4) NOT NULL,
  `pos_x` decimal(7,4) NOT NULL,
  `sort` tinyint NOT NULL,
  `width` decimal(7,4) NOT NULL,
  `effect` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `effect_hover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `script` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `script_hover` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `style` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `style_hover` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_object_interaction_stages` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `view_x` decimal(4,2) NOT NULL,
  `view_y` decimal(4,2) NOT NULL,
  `height_full` tinyint(1) NOT NULL,
  `zoom_auto` tinyint(1) NOT NULL,
  `zoom_min` tinyint NOT NULL,
  `zoom_max` tinyint NOT NULL,
  `zoom_levels` tinyint NOT NULL,
  `zoom_level_default` tinyint NOT NULL,
  `script` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_polls` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_poll_sets` (
  `id` int NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_poll_set_link` (
  `poll_id` int NOT NULL,
  `poll_set_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_poll_set_options` (
  `id` int NOT NULL,
  `poll_set_id` int NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_sliders` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `timeout` int NOT NULL,
  `speed` int NOT NULL,
  `effect` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_slider_slides` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_slider_slide_link` (
  `slider_id` int NOT NULL,
  `slider_slide_id` int NOT NULL,
  `media_internal_tag_id` int NOT NULL,
  `sort` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_tags` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_cache_files` (
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_log_requests_access` (
  `id` int NOT NULL,
  `type` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varbinary(16) NOT NULL,
  `ip_block` varbinary(16) NOT NULL,
  `date` datetime NOT NULL,
  `identifier` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_log_requests_throttle` (
  `ip` varbinary(16) NOT NULL,
  `date` datetime(3) NOT NULL,
  `heat` float NOT NULL,
  `state` tinyint NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_user_labels` (
  `identifier` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_details` (
  `user_id` int NOT NULL,
  `parent_id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `surname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `street` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `streetnr` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_preferences_messaging` (
  `user_id` int NOT NULL,
  `notify_email` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_feeds` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_feed_entries` (
  `id` int NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `media` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `sort` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_feed_entry_link` (
  `feed_id` int NOT NULL,
  `feed_entry_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_feed_entry_tags` (
  `feed_entry_id` int NOT NULL,
  `tag_id` int NOT NULL
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

ALTER TABLE `def_documentations`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_documentation_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documentation_id` (`documentation_id`);

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
  ADD UNIQUE KEY `directory` (`directory`,`filename`),
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

ALTER TABLE `site_log_requests_access`
  ADD PRIMARY KEY (`id`),
  ADD KEY `identifier` (`type`,`date`,`identifier`) USING BTREE,
  ADD KEY `ip` (`type`,`date`,`ip_block`,`ip`) USING BTREE;

ALTER TABLE `site_log_requests_throttle`
  ADD PRIMARY KEY (`ip`);

ALTER TABLE `site_user_labels`
  ADD PRIMARY KEY (`identifier`,`lang_code`,`user_id`);

ALTER TABLE `user_details`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

ALTER TABLE `user_preferences_messaging`
  ADD PRIMARY KEY (`user_id`);

ALTER TABLE `def_feeds`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_feed_entries`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_feed_entry_link`
  ADD PRIMARY KEY (`feed_id`,`feed_entry_id`);

ALTER TABLE `def_feed_entry_tags`
  ADD PRIMARY KEY (`feed_entry_id`,`tag_id`);


ALTER TABLE `data_blog_post_comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `data_blog_post_xrefs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `data_form_submissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `data_messaging_conversations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `data_messaging_conversation_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_blogs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_blog_posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_calendar_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_custom_content`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_dashboards`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_documentations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_documentation_sections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_forms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_form_fields`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_form_field_sub`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_newsletters`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_newsletters_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_object_interaction_objects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_object_interaction_stages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_polls`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_poll_sets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_poll_set_options`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_sliders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_slider_slides`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `site_log_requests_access`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_feeds`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_feed_entries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
