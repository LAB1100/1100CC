<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// 1100CC Server Settings:
	
	define('SITE_NAME', strtolower($_SERVER['SITE_NAME'])); // Custom environment variable
	define('IS_CMS', ($_SERVER['PATH_CMS'] ? true : false)); // Custom environment variable
	define('STATE_PRODUCTION', 0);
	define('STATE_DEVELOPMENT', 1);
	define('STATE', (isset($_SERVER['STATE']) && ($_SERVER['STATE'] === 'development' || (int)$_SERVER['STATE'] === STATE_DEVELOPMENT) ? STATE_DEVELOPMENT : STATE_PRODUCTION)); // Custom environment variable
	define('MESSAGE', ($_SERVER['MESSAGE'] ?? null)); // Custom environment variable
	define('DIR_STORAGE', 'STORAGE/');
	define('DIR_CACHE', 'CACHE/');
	define('DIR_SETTINGS', 'SETTINGS/');

	define('DIR_CORE', $_SERVER['DIR_INDEX'].'/'); // Path to executing script (index)
	define('DIR_SITE', str_replace('CORE', SITE_NAME, DIR_CORE));
	define('DIR_SITE_STORAGE', str_replace('CORE', DIR_STORAGE.SITE_NAME, DIR_CORE));
	define('DIR_SITE_CACHE', str_replace('CORE', DIR_CACHE.SITE_NAME, DIR_CORE));
	define('DIR_SITE_SETTINGS', str_replace('CORE', DIR_SETTINGS.SITE_NAME, DIR_CORE));
	
	define('DIR_ROOT', str_replace(['CMS/', 'CORE/'], '', DIR_CORE));
	define('DIR_ROOT_CORE', DIR_ROOT.'CORE/');
	define('DIR_ROOT_SITE', DIR_ROOT.SITE_NAME.'/');
	define('DIR_ROOT_STORAGE', DIR_ROOT.DIR_STORAGE);
	define('DIR_ROOT_CACHE', DIR_ROOT.DIR_CACHE);
	define('DIR_ROOT_SETTINGS', DIR_ROOT.DIR_SETTINGS);
	if (empty($_SERVER['DOCUMENT_ROOT'])) {
		$_SERVER['DOCUMENT_ROOT'] = DIR_ROOT;
	}
	
	$arr_dir = explode('/', DIR_ROOT);
	$str_dir_alternative = $arr_dir[count($arr_dir)-2];
	$str_dir_alternative = ($str_dir_alternative != 'APP' ? substr($str_dir_alternative, 3) : '');
	$str_dir_parent = array_slice($arr_dir, 0, -2);
	$str_dir_parent = implode('/', $str_dir_parent).'/';
	
	define('DIR_SAFE', $str_dir_parent.'SAFE'.$str_dir_alternative.'/');
	define('DIR_SAFE_SITE', DIR_SAFE.SITE_NAME.'/');
	define('DIR_PROGRAMS', $str_dir_parent.'PROGRAMS/');
	define('DIR_PROGRAMS_RUN', DIR_PROGRAMS.'RUN/');

	define('DIR_HOME', SITE_NAME.'/');
	define('DIR_CMS', 'CMS/');
	define('DIR_MODULES', 'modules/');
	define('DIR_MODULES_CATALOG', 'catalog/');
	define('DIR_MODULES_ABSTRACT', 'base/');
	define('DIR_CLASSES', 'classes/');
	
	define('DIR_EXTERNAL', 'external/');
	define('DIR_BACKUP', 'backup/');
	define('DIR_UPLOAD', 'upload/');
	define('DIR_PRIVATE', 'PRIVATE/');
	define('DIR_CSS', 'css/');
	define('DIR_JS', 'js/');
	define('DIR_INFO', 'info/');
	
	spl_autoload_register('autoLoadClass');

	define('SERVER_NAME_SUB', strtolower($_SERVER['SERVER_NAME_SUB'])); // .cms|.s[0-9]
	define('SERVER_NAME_CUSTOM', strtolower($_SERVER['SERVER_NAME_CUSTOM'])); // more.custom.
	define('SERVER_NAME_1100CC', strtolower($_SERVER['SERVER_NAME_1100CC'])); // e.1100cc.xyz
	define('SERVER_NAME_MODIFIER', strtolower($_SERVER['SERVER_NAME_MODIFIER'])); // e.
	define('SERVER_NAME_SITE_NAME', strtolower($_SERVER['SERVER_NAME_SITE_NAME'])); // 1100cc.xyz
	
	define('SERVER_NAME_BASE', SERVER_NAME_CUSTOM.SERVER_NAME_1100CC);
	define('SERVER_NAME', SERVER_NAME_SUB.SERVER_NAME_BASE);
	
	if (IS_CMS) {
		define('SERVER_NAME_HOME', str_replace(['cms.', 'cms-'], '', SERVER_NAME));
		define('SERVER_NAME_CMS', SERVER_NAME);
	} else {
		define('SERVER_NAME_HOME', SERVER_NAME);
		define('SERVER_NAME_CMS', 'cms.'.SERVER_NAME);
	}

	$path_database = DIR_ROOT_SETTINGS.DIR_HOME.'database';
	$path_settings = DIR_ROOT_SETTINGS.DIR_HOME.'settings.php';

	$str_database = '';
	
	if (isPath($path_database)) {
		$str_database = readText($path_database);
	}
	
	if ($str_database == 'postgresql') {
		require('operations/DBPostgresql.php');
	} else if ($str_database == 'mysql') {
		require('operations/DBMysql.php');
	} else {
		$str_database = 'mariadb';
		require('operations/DBMariadb.php');
	}
	
	class_alias('DBBase\\'.$str_database.'\DB', 'DB');
	class_alias('DBBase\\'.$str_database.'\DBStatement', 'DBStatement');
	class_alias('DBBase\\'.$str_database.'\DBResult', 'DBResult');
	class_alias('DBBase\\'.$str_database.'\DBFunctions', 'DBFunctions');
	class_alias('DBBase\\'.$str_database.'\DBTrouble', 'DBTrouble');

	// CORE settings
	
	DB::$localhost = 'localhost';
	DB::$database_core = '1100CC'; // Root dir
	DB::$database_cms = SITE_NAME.'_cms';
	DB::$database_home = SITE_NAME.'_home';
	
	Settings::set('path_temporary', sys_get_temp_dir().'/1100CC'.$str_dir_alternative.'/'.DIR_HOME);
	Settings::set('chmod_file', 00660);
	Settings::set('chmod_directory', 02775);
	
	// SITE settings
	
	if (!isPath($path_settings)) {
		error('1100CC is missing settings.');
	}
	
	require($path_settings);
		
	// Applied settings
	
	if (!isset($_SERVER['SERVER_SCHEME'])) { // Custom environment variable
		$_SERVER['SERVER_SCHEME'] = (!empty($_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') ? URI_SCHEME_HTTPS : URI_SCHEME_HTTP);
	}
	define('SERVER_SCHEME', $_SERVER['SERVER_SCHEME']);
	
	define('URL_BASE', SERVER_SCHEME.SERVER_NAME.'/');
	define('URL_BASE_HOME', SERVER_SCHEME.SERVER_NAME_HOME.'/');
	define('URL_BASE_CMS', SERVER_SCHEME.SERVER_NAME_CMS.'/');
	
	Response::setOutputUpdates(!empty($_SERVER['HTTP_1100CC_STATUS']) ? true : null);

	FileStore::makeDirectoryTree(Settings::get('path_temporary'));
