CREATE TABLE `core_labels` (
  `identifier` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` longtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `core_language` (
  `lang_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `flag` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `label` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `core_users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `img` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `biography` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `passhash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `ip` varbinary(16) DEFAULT NULL,
  `ip_proxy` varbinary(16) DEFAULT NULL,
  `labeler` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_var_countries` (
  `id` int(11) NOT NULL,
  `code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `core_labels`
  ADD PRIMARY KEY (`identifier`,`lang_code`);

ALTER TABLE `core_language`
  ADD PRIMARY KEY (`lang_code`);

ALTER TABLE `core_users`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_var_countries`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `core_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_var_countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
