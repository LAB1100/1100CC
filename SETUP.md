# SETUP

Make sure to have a server running with the software requirements as outlined in the [README](README.md#requirements). The following parts outline how to configure 1100CC:

* [1100CC](SETUP.md#1100cc): install and configure 1100CC.
* [Server](SETUP.md#server): configure the LAMP software packages for the server.
* [Programs](SETUP.md#programs): create and compile 1100CC programs, optional.

## 1100CC

Create a directory on your server where you put all files from the 1100CC folder, we will use `/var/1100CC`.

Next, configure the LAMP software packages for the [server](SETUP.md#server).

To create a new site/application in the 1100CC installation, do the following:
1. The directory `./APP/SITE` contains the default files and directories for a new site/application, copy and rename this folder to the name of your site/application.
1. Copy and rename the directory `./APP/SETTINGS/SITE` to your new site/application name.
1. Copy and rename the directory `./APP/STORAGE/SITE` to your new site/application name.
1. Copy and rename the directory `./SAFE/SITE` to your new site/application name.
1. From now on ?SITE? refers to your site/application's name.
1. Add an alias (or multiple aliases) to `./APP/alias` that links a host to your site/application: e.g. `yourhost.com ?SITE?` and/or `sub.yourhost.com ?SITE?`
1. Add the appropriate databases for both the CMS and HOME of your new ?SITE?, see [database SITE](SETUP.md#database-site).
1. Add the database passwords for the CMS and HOME users to `./SAFE/?SITE?/database_cms.pass` and `./SAFE/?SITE?/database_home.pass` respectively.
1. Edit the `./APP/SETTINGS/?SITE?/settings.php` to configure or override any default settings if required.
1. Go to the CMS by adding cms. to your SITE's host e.g. `cms.yourhost.com`, and start building! See the [1100CC Guides](https://lab1100.com/1100cc/guides) on how to get started.
 
## Server

### Apache webserver

Make sure the following Apache modules are enabled:
* rewrite
* headers
* deflate
* proxy_fcgi (optional)
* ssl (optional)

Include and adjust the following VirtualHost configuration to your Apache config to direct all relevant traffic (hosts and HTTP/80 and or HTTPS/443) to the 1100CC `./APP` root directory:

```apache
<VirtualHost *:80>
	ServerAlias *
	ServerName 1100cc.youserver.com:80

	# Get the server name from the Host: header
	UseCanonicalName Off

	# Remove line breaks
	RewriteEngine On
	RewriteRule ^(.*)(?:\r?\n|\r)(.*)? $1$2 [N]

	# Set document root
	DocumentRoot /var/1100CC/APP

	# 1100CC mapping
	RewriteMap alias txt:/var/1100CC/APP/alias

	<Directory /var/1100CC/APP>
		#SSLRequireSSL
		Options -Indexes +FollowSymLinks
		AllowOverride All
		Require all granted
	</Directory>
</VirtualHost>
```

When using PHP-FPM, make sure the connection with the server is flushed:

```apache
<IfModule proxy_fcgi_module>
	<Proxy fcgi://localhost>
		ProxySet flushpackets=on
	</Proxy>
</IfModule>
```

When your Linux distrubiton applies SELinux:
 - Apache needs write access (i.e. httpd_sys_rw_content_t) to the `./APP/CACHE` and `./APP/STORAGE` directories.
 - Apache needs access to run programs independently (i.e. httpd_execmem)
 - Make sure to allow Apache to use the mailserver (i.e. httpd_can_network_connect, httpd_can_sendmail).

### PHP

1100CC uses the following modules, check your distribution (e.g. Debian- or Red Hat-based distributions) to see which ones are installed by default or your need to add them:
* xml
* gd
* mbstring
* mysql
* pgsql
* curl
* zip
* xmlrpc
* fpm or libapache2-mod
* intl
* bcmath
* yaml

### Database

Indicate which database to use by creating a file `./APP/SETTINGS/?SITE?/database` with either 'mysql' / 'mariadb' / 'postgresql' as its contents. 

#### MySQL

##### Database CORE

Create 2 MySQL users with a password of your choice:

```sql
CREATE USER 1100CC_cms@localhost IDENTIFIED BY '?PASSWORD?';
CREATE USER 1100CC_home@localhost IDENTIFIED BY '?PASSWORD?';
```

Create the 1100CC CORE database:

```sql
CREATE DATABASE 1100CC CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
Grant the MySQL users their 1100CC CORE privileges:

```sql
GRANT SELECT, INSERT, UPDATE, DELETE ON 1100CC.* TO 1100CC_cms@localhost;
GRANT SELECT ON 1100CC.* TO 1100CC_home@localhost;
```

##### Database SITE

Create a HOME and a CMS database for each SITE (replace ?SITE? with your site/application's name) you want hosted by 1100CC:

```sql
CREATE DATABASE ?SITE?_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE ?SITE?_home CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Grant the MySQL users their HOME and a CMS privileges:

```sql
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES, SHOW VIEW, EXECUTE ON ?SITE?_home.* TO 1100CC_cms@localhost;
GRANT SELECT, INSERT, UPDATE, DELETE ON ?SITE?_home.* TO 1100CC_home@localhost;

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES, SHOW VIEW, EXECUTE ON ?SITE?_cms.* TO 1100CC_cms@localhost;
GRANT SELECT ON ?SITE?_cms.* TO 1100CC_home@localhost;
```

Import the following SQL to their respective databases:
* [1100CC.sql](setup/1100CC.sql) to the 1100CC CORE database.
* [1100CC.core_language.sql](setup/1100CC.core_language.sql) to the 1100CC CORE database.
* [1100CC.core_labels.en.sql](setup/1100CC.core_labels.en.sql) to the 1100CC CORE database, and also other languages by their code ('nl'/'de') if needed.
* [SITE_cms.sql](setup/SITE_cms.sql) to the SITE CMS database.
* [SITE_cms.default.sql](setup/SITE_cms.default.sql) to the SITE CMS database.
* [SITE_home.sql](setup/SITE_home.sql) to the SITE HOME database.

#### PostgreSQL

Currently MySQL works as a master for development, you can use [PGLoader](https://pgloader.io/) to transfer the MySQL structure to PostgreSQL and work from there, e.g. use a PGLoader script with:

```sql
LOAD DATABASE
	FROM mysql://root:?PASSWORD?@127.0.0.1/1100CC
	INTO postgresql://postgres:?PASSWORD?@127.0.0.1/CC1100
	
	CAST
		type int with extra auto_increment to serial drop typemod keep default keep not null,
		type int to int drop typemod keep default keep not null,
		type bigint to bigint drop typemod keep default keep not null
;
```

```sql
LOAD DATABASE
	FROM mysql://root:?PASSWORD?@127.0.0.1/?SITE?_home
	INTO postgresql://postgres:?PASSWORD?@127.0.0.1/CC1100
	
	CAST
		type int with extra auto_increment to serial drop typemod keep default keep not null,
		type int to int drop typemod keep default keep not null,
		type bigint to bigint drop typemod keep default keep not null
;

LOAD DATABASE
	FROM mysql://root:?PASSWORD?@127.0.0.1/?SITE?_cms
	INTO postgresql://postgres:?PASSWORD?@127.0.0.1/CC1100
	
	CAST
		type int with extra auto_increment to serial drop typemod keep default keep not null,
		type int to int drop typemod keep default keep not null,
		type bigint to bigint drop typemod keep default keep not null

	AFTER LOAD DO

		$$ CREATE VIEW ?SITE?_cms.view_user_parent AS SELECT u.id AS id, u.name AS parent_name, u.group_id AS parent_group_id FROM ?SITE?_cms.users AS u; $$
;
```

Add a file called 'database' with the contents 'postgresql' in the SITE's `./APP/SETTINGS/?SITE?` directory.

## Programs

The `./PROGRAMS` directory provides an environment to create and build services using C++. The `creation_station.sh` script found in `./PROGRAMS/LIBRARIES` helps you to build and link programs to `./PROGRAMS/RUN`.

### Libraries

Libraries needed for inclusion:
* RapidJSON ([download](https://github.com/Tencent/rapidjson))

Libraries that need to be compiled:
* CMake 3.12+ ([download](https://cmake.org/download/))
* Boost 1.67+ ([download](https://www.boost.org/users/download/))
 
Libraries that first need to be complied can be stored in the `./PROGRAMS/LIBRARIES/BUILD` directory (e.g. `./PROGRAMS/LIBRARIES/BUILD/boost`). Libraries that only need inclusion can directly be stored in the `./PROGRAMS/LIBRARIES` directory (e.g. `./PROGRAMS/LIBRARIES/rapidjson`).
 
Make sure you have the necessary build tools available, e.g. on a Debian-based distribution:

```bash
sudo apt-get install build-essential
```

#### CMake

CMake can probably be installed from your distribution's repository, however often the version available is dated. To install CMake manually and system-wide:

```bash
cd /var/1100CC/PROGRAMS/LIBRARIES/BUILD/cmake

./bootstrap
make -j4
sudo make install

make clean
rm ./CMakeCache.txt
```

#### Boost

The main 1100CC libraries make use of Boost functions, and is very handy to have anyway.

```bash
cd /var/1100CC/PROGRAMS/LIBRARIES/BUILD/boost

./bootstrap.sh --prefix=/var/1100CC/PROGRAMS/LIBRARIES/boost/
./b2 install --build-dir=/tmp/build-boost

make clean
```

### Creation Station

Use to `creation_station.sh` script to build and link any program needed.

```bash
cd /var/1100CC/PROGRAMS
./creation_station.sh
```

When a program is compiled successfully, the path to the program in `/var/1100CC/PROGRAMS/RUN` is returned and is ready to be used by 1100CC.
