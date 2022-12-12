# UPDATE

To get the version for your current 1100CC installation, view `./APP/CORE/CMS/info/version.txt`

Follow each section subsequently that comes after your current version:

## VERSION 10.1

Initial release.

## VERSION 10.2

Run SQL queries in database ?SITE?_cms:

```sql
ALTER TABLE `site_log_users` ADD `user_class` TINYINT NOT NULL AFTER `user_id`;

UPDATE site_log_users SET user_class = 3 WHERE user_id != 0;
UPDATE site_log_users SET user_class = 1, user_id = cms_user_id WHERE user_id = 0 AND cms_user_id >= 1000;
UPDATE site_log_users SET user_class = 2, user_id = cms_user_id WHERE user_id = 0 AND cms_user_id > 0 AND cms_user_id < 1000;

ALTER TABLE `site_log_users` DROP `cms_user_id`;

ALTER TABLE `site_log_users` DROP INDEX `user_id`, ADD INDEX `user_id` (`user_id`, `user_class`) USING BTREE;
```

---

Run SQL queries in database ?SITE?_cms:

```sql
ALTER TABLE `cms_language` DROP `flag`;
```

---

Run SQL queries in database 1100CC:

```sql
ALTER TABLE `core_language` DROP `flag`;
```

## VERSION 10.3

Update 1100CC [1100CC.core_labels.en.sql](/setup/1100CC.core_labels.en.sql).

---

Run SQL queries in database ?SITE?_cms:

```sql
ALTER TABLE `site_jobs` CHANGE `minutes` `seconds` INT(11) NOT NULL;

UPDATE site_jobs SET seconds = seconds * 60 WHERE seconds > 0;
```

## VERSION 10.4

Update 1100CC [1100CC.core_labels.en.sql](/setup/1100CC.core_labels.en.sql).

Install the following PHP modules: intl, bcmath.

Create the directory `./SAFE/` (restrictive clearance) and folders for every `./SAFE/?SITE?`.

---

Run SQL queries in database ?SITE?_cms:

```sql
ALTER TABLE `site_details` ADD `throttle` BOOLEAN NOT NULL AFTER `logging`;

ALTER TABLE `site_api_clients` ADD `request_limit_disable` BOOLEAN NOT NULL AFTER `name`;
```

---

Run SQL queries in database ?SITE?_home:

```sql
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

ALTER TABLE `def_documentations`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_documentation_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documentation_id` (`documentation_id`);

ALTER TABLE `def_documentations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_documentation_sections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

RENAME TABLE `site_log_requests` TO `site_log_requests_access`;

CREATE TABLE `site_log_requests_throttle` (
  `ip` varbinary(16) NOT NULL,
  `date` datetime(3) NOT NULL,
  `heat` float NOT NULL,
  `state` tinyint NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `site_log_requests_throttle`
  ADD PRIMARY KEY (`ip`);
```

## VERSION 10.4*

Run SQL queries in database ?SITE?_cms:

```sql
ALTER TABLE `cms_language` CHANGE `user` `is_user_selectable` TINYINT(1) NOT NULL;
```
