<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// 1100CC Server Settings:
	
	define('SITE_NAME', strtolower($_SERVER['SITE_NAME'])); // Custom environment variable
	define('IS_CMS', ($_SERVER['IF_CMS_PATH'] ? 1 : 0)); // Custom environment variable
	define('STATE_PRODUCTION', 0);
	define('STATE_DEVELOPMENT', 1);
	define('STATE', (isset($_SERVER['STATE']) && ($_SERVER['STATE'] == 'development' || $_SERVER['STATE'] == STATE_DEVELOPMENT) ? STATE_DEVELOPMENT : STATE_PRODUCTION)); // Custom environment variable
	define('MESSAGE', ($_SERVER['MESSAGE'] ?? '')); // Custom environment variable
	define('DIR_STORAGE', 'STORAGE/');
	define('DIR_CACHE', 'CACHE/');
	define('DIR_SETTINGS', 'SETTINGS/');

	define('DIR_CORE', $_SERVER['DIR_INDEX'].'/'); // Path to executing script (index)
	define('DIR_SITE', str_replace('CORE', SITE_NAME, DIR_CORE));
	define('DIR_SITE_STORAGE', str_replace('CORE', DIR_STORAGE.SITE_NAME, DIR_CORE));
	define('DIR_SITE_CACHE', str_replace('CORE', DIR_CACHE.SITE_NAME, DIR_CORE));
	define('DIR_SITE_SETTINGS', str_replace('CORE', DIR_SETTINGS.SITE_NAME, DIR_CORE));
	
	define('DIR_ROOT', str_replace(['CMS/', 'CMS\\', 'CORE/', 'CORE\\'], '', DIR_CORE));
	define('DIR_ROOT_CORE', DIR_ROOT.'CORE/');
	define('DIR_ROOT_SITE', DIR_ROOT.SITE_NAME.'/');
	define('DIR_ROOT_STORAGE', DIR_ROOT.DIR_STORAGE);
	define('DIR_ROOT_CACHE', DIR_ROOT.DIR_CACHE);
	define('DIR_ROOT_SETTINGS', DIR_ROOT.DIR_SETTINGS);
	if (empty($_SERVER['DOCUMENT_ROOT'])) {
		$_SERVER['DOCUMENT_ROOT'] = DIR_ROOT;
	}
	
	$dir_parent = explode('/', DIR_ROOT);
	$dir_parent = array_slice($dir_parent, 0, -2);
	$dir_parent = implode('/', $dir_parent).'/';
	define('DIR_SAFE', $dir_parent.'SAFE/');
	define('DIR_SAFE_SITE', DIR_SAFE.SITE_NAME.'/');
	define('DIR_PROGRAMS', $dir_parent.'PROGRAMS/');
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

	define('SERVER_NAME_SUB', strtolower($_SERVER['SERVER_NAME_SUB'])); // Custom environment variable
	define('SERVER_NAME_CUSTOM', strtolower($_SERVER['SERVER_NAME_CUSTOM'])); // Custom environment variable
	define('SERVER_NAME_1100CC', strtolower($_SERVER['SERVER_NAME_1100CC'])); // Custom environment variable
	define('SERVER_NAME_MODIFIER', strtolower($_SERVER['SERVER_NAME_MODIFIER'])); // Custom environment variable
	define('SERVER_NAME_SITE_NAME', strtolower($_SERVER['SERVER_NAME_SITE_NAME'])); // Custom environment variable
	
	define('SERVER_NAME_BASE', SERVER_NAME_CUSTOM.SERVER_NAME_1100CC);
	define('SERVER_NAME', SERVER_NAME_SUB.SERVER_NAME_BASE);
	
	define('SERVER_NAME_HOME', str_replace('cms.', '', SERVER_NAME));
	define('SERVER_NAME_CMS', 'cms.'.SERVER_NAME_HOME);

	$path_database = DIR_ROOT_SETTINGS.DIR_HOME.'database';
	$path_settings = DIR_ROOT_SETTINGS.DIR_HOME.'settings.php';
	$path_various = DIR_ROOT_SETTINGS.DIR_HOME.'various.php';

	$str_database = 'mysql';
	
	if (isPath($path_database)) {
		$str_database = readText($path_database);
	}
	
	if ($str_database == 'postgresql') {
		
		require('operations/DBPostgresql.php');
	} else {
		
		require('operations/DBMysql.php');
	}

	// CORE settings
	DB::$localhost = 'localhost';
	DB::$database_core = '1100CC'; // Root dir
	DB::$database_cms = SITE_NAME.'_cms';
	DB::$database_home = SITE_NAME.'_home';
	
	Settings::set('path_temporary', sys_get_temp_dir().'/1100CC/'.DIR_HOME);
	Settings::set('chmod_file', 0660);
	Settings::set('chmod_directory', 02775);
	
	// SITE settings
	
	if (!isPath($path_settings)) {
		exit('1100CC');
	}
	
	require($path_settings);
	
	if (isPath($path_various)) {
		require($path_various);
	}
	
	// Applied settings
	
	define('SERVER_PROTOCOL', (!empty($_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') ? 'https' : 'http').'://'); // Custom environment variable
	
	define('URL_BASE', SERVER_PROTOCOL.SERVER_NAME.'/');
	define('URL_BASE_HOME', SERVER_PROTOCOL.SERVER_NAME_HOME.'/');
	define('URL_BASE_CMS', SERVER_PROTOCOL.SERVER_NAME_CMS.'/');
	
	Response::setOutputUpdates(!empty($_SERVER['HTTP_1100CC_STATUS']) ? true : null);

	FileStore::makeDirectoryTree(Settings::get('path_temporary'));
