<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class Settings {
		
	private static $arr = [];
	private static $arr_override = [];
	private static $arr_override_keys = [];
	private static $arr_add = [];
	
	public static $server_file_host_name = '';
	public static $arr_storage_paths = [DIR_CSS, DIR_JS, DIR_CMS, DIR_UPLOAD];
	public static $arr_storage_paths_cacheable = ['combine/', 'cache/'];
				
	public static function get($setting, $key = false) { // Get a setting following: set or override, and add
		
		$value = null;
		
		if ($key) {
			
			if (isset(self::$arr_override_keys[$setting][$key])) {
				$value = self::$arr_override_keys[$setting][$key];
			} else if (isset(self::$arr_override[$setting])) {
				$value = self::$arr_override[$setting][$key];
			} else {
				$value = (self::$arr[$setting][$key] ?? null);
			}
		} else {
			
			if ((isset(self::$arr_override[$setting]) && is_array(self::$arr_override[$setting])) || isset(self::$arr_override_keys[$setting])) {
				$arr_override = array_merge((isset(self::$arr_override[$setting]) && is_array(self::$arr_override[$setting]) ? self::$arr_override[$setting] : []), (self::$arr_override_keys[$setting] ?? []));
			} else if (isset(self::$arr_override[$setting])) {
				$arr_override = self::$arr_override[$setting];
			}
			
			$value = (isset($arr_override) ? $arr_override : (self::$arr[$setting] ?? null));
		}
		
		if (is_callable($value)) {
			$value = $value();
		}
		
		if (isset(self::$arr_add[$setting])) {
			
			foreach (self::$arr_add[$setting] as $value_add) {
				$value = array_merge($value, (is_callable($value_add) ? $value_add() : $value_add));
			}
		}
		
		return $value;
	}

	public static function set($setting, $value) {
	
		self::$arr[$setting] = $value;
	}

	public static function override($setting, $value, $key = false) {
		
		if ($key) {
			self::$arr_override_keys[$setting][$key] = $value;
		} else {
			unset(self::$arr_override_keys[$setting]);
			self::$arr_override[$setting] = $value;
		}
	}
	
	public static function add($setting, $value, $key = false) {
		
		if ($key) {
			self::$arr_add[$setting][$key] = $value;
		} else {
			self::$arr_add[$setting][] = $value;
		}
	}
	
	public static function setServerFileHostName($host_name) {
	
		self::$server_file_host_name = $host_name;
	}
	
	public static function addStoragePath($path) {
	
		self::$arr_storage_paths[] = $path;
	}
	
	public static function setShare($key, $value = false, $num_seconds = false) {
		
		$path = self::get('path_temporary').'share_'.$key;
		
		if (!$value) {
			FileStore::deleteFile($path);
		}
		
		$value = var_export($value, true);
		
		// Just cast classes as objects; plain object access
		$value = str_replace('stdClass::__set_state', '(object)', $value);
		
		$is_valid = ($num_seconds ? '(time() < '.(time()+$num_seconds).')' : 'true');
					
		// Write to temporary file first to ensure atomicity
		$path_temp = $path.uniqid('', true).'.tmp';
		
		file_put_contents($path_temp, '<?php $is_valid = '.$is_valid.'; $value = '.$value.';', LOCK_EX);
		
		rename($path_temp, $path);
		opcache_invalidate($path);
	}
	
	public static function getShare($key) {
		
		$path = self::get('path_temporary').'share_'.$key;
		
		$is_valid = null;
		
		try {
			include $path;
		} catch (Exception $e) {
			// Nothing
		}
		
		if (!$is_valid) {
			return false;
		}
		
		return $value;
	}
	
	public static function getSafeText($str) {
		
		if (isPath(DIR_SAFE_SITE.$str)) {
			
			return readText(DIR_SAFE_SITE.$str);
		}
		
		return $str;
	}
	
	public static function getUpdatePath($do_file = true) {
		
		//$str_path = DIR_ROOT_SETTINGS.DIR_HOME.'update/';
		$str_path = DIR_ROOT_STORAGE.DIR_HOME.DIR_CMS.DIR_PRIVATE.'update/';
		
		return $str_path.($do_file ? 'update.php' : '');
	}
}
