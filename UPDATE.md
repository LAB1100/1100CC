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
