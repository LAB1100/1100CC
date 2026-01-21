<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// 1100CC Framework:

	/*
		Naming conventions:
		${data_type}_{action}_{object} => VARIABLE date FOR start OF object
		SELECT {object}_{data_type}_{action} AND array[{object}_{data_type}_{action}] => SELECT object date FOR start
		{do}{object}{data_type}{action}() AND $method = {do}_{object}_{data_type}_{action} => getObjectDateStart()
	*/
	
	ini_set('default_charset', 'UTF-8');
	date_default_timezone_set('UTC');
	
	const TROUBLE_ERROR = 0;
	const TROUBLE_FATAL = 1;
	const TROUBLE_WARNING = 2;
	const TROUBLE_NOTICE = 3;
	const TROUBLE_UNKNOWN = 4;
	const TROUBLE_DATABASE = 5;
	
	const TROUBLE_ACCESS_DENIED = 6;
	const TROUBLE_INVALID_REQUEST = 7;
	const TROUBLE_REQUEST_LIMIT = 8;
	const TROUBLE_UNAUTHORIZED_CLIENT = 9;
	
	const LOG_BOTH = 0;
	const LOG_SYSTEM = 1;
	const LOG_CLIENT = 2;
	
	const BYTE_MULTIPLIER = 1024; // Kibibyte vs kilobyte
	const EOL_1100CC = PHP_EOL;
	const EOL_EXCHANGE = "\r\n";
	const CSV_ESCAPE = "\0"; // Empty '' for PHP 7.4+
	const SYMBOL_SPACE_TEXT = ' '; // U+2002 - EN SPACE
	const SYMBOL_SPACE_MATHEMATICAL = ' '; // U+205F - MEDIUM MATHEMATICAL SPACE
	
	const TYPE_INTEGER = 'integer';
	const TYPE_FLOAT = 'float';
	const TYPE_BOOLEAN = 'boolean';
	const TYPE_STRING = 'string';
	const TYPE_TEXT = 'text';
	
	const MATCH_FULL = 0;
	const MATCH_START = 1;
	const MATCH_END = 2;
	const MATCH_ANY = 3;
	
	const REGEX_NOFLAG = '-';
	const REGEX_CASE_INSENSITIVE = 'i';
	const REGEX_DOT_SPECIAL = 'd';
	const REGEX_LINE = 'l';
	
	const URI_SCHEME_HTTP = 'http://';
	const URI_SCHEME_HTTPS = 'https://';
	
	require('operations/Trouble.php');
	require('operations/Log.php');
	require('operations/Response.php');
	require('operations/DB.php');
	require('operations/Mediator.php');
	require('operations/Settings.php');
	require('operations/Labels.php');
	require('operations/SiteStartEnvironment.php');
	require('operations/SiteEndEnvironment.php');
	
	function error($message = '', $mode_code = TROUBLE_ERROR, $mode_suppress = LOG_BOTH, $debug = null, $exception = null) {
		
		if (is_array($message)) {
			$message = print_r($message, true);
		}
		if (is_array($debug)) {
			$debug = print_r($debug, true);
		}
		
		Trouble::fling($message, $mode_code, $mode_suppress, $debug, $exception);
	}
	
	function message($message = '', $str_label = null, $mode_suppress = LOG_BOTH, $debug = null, $str_type = null, $arr_options = null, $identifier = null) {
		
		if (is_array($message)) {
			$message = print_r($message, true);
		}
		if (is_array($debug)) {
			$debug = print_r($debug, true);
		}
		$str_label = ($str_label ?: 'LOG');
		$str_type = ($str_type ?: Trouble::type(TROUBLE_NOTICE));
		
		Log::addMessage($message, $str_label, $mode_suppress, $debug, $str_type, $arr_options, $identifier);
	}
	
	function status($message = '', $str_label = null, $str_header = null, $arr_options = null) {
		
		if ($message === false) {
			
			$str = false;
		} else {
			
			if (is_array($message)) {
				$message = print_r($message, true);
			}
			$str_label = ($str_label ?: 'UPDATE');
			$str_header = ($str_header ?: getLabel('lbl_status'));
						
			$str = '<ul><li><label></label><div>'.$str_header.'</div></li><li><label>'.$str_label.'</label><div>'.$message.'</div></li></ul>';
		}
		
		if ($arr_options !== null && !is_array($arr_options)) {
			$arr_options = ['duration' => (int)$arr_options];
		} else {
			$arr_options = ($arr_options ?: []);
		}
		
		if (!isset($arr_options['identifier'])) {
			$arr_options['identifier'] = SiteStartEnvironment::getSessionID(true);
		}
		
		$arr_status = ['message' => $str, 'message_type' => 'status', 'message_options' => $arr_options];
			
		Response::update($arr_status);
	}
	
	function err(...$args) { error(...$args); }
	function msg(...$args) { message(...$args); }
	function sts(...$args) { status(...$args); }
	
	function clearStatus($identifier, $timeout = null) {
		
		status(false, false, false, ['clear' => ['identifier' => $identifier, 'timeout' => $timeout]]);
	}
	
	function getLabel($str_identifier, $str_type = null, $go_now = false) {
		
		return Labels::getLabel($str_identifier, $str_type, $go_now);
	}
	
	function getModules($level = false) {
	
		switch ($level) {
			case DIR_HOME:
				$directory = DIR_MODULES;
				break;
			case DIR_CMS:
				$directory = DIR_CMS.DIR_MODULES;
				break;
			default:
				$directory = (IS_CMS ? DIR_CMS.DIR_MODULES : DIR_MODULES);
		}
		
		$arr_modules = [];
		$arr_links = [];
		
		$autoload_abstract = function($class) use ($directory) {

			$c = $directory.DIR_MODULES_ABSTRACT.$class.'.php';

			if (isPath(DIR_ROOT_SITE.$c)) {
				$f = DIR_ROOT_SITE.$c;
			} else if (isPath(DIR_ROOT_CORE.$c)) {
				$f = DIR_ROOT_CORE.$c;
			}
			if ($f) {
				require($f);
			}
		};
		
		$arr_it_directory = ['site' => new DirectoryIterator(DIR_ROOT_SITE.$directory), 'core' => new DirectoryIterator(DIR_ROOT_CORE.$directory), 'catalog' => new DirectoryIterator(DIR_ROOT_CORE.$directory.DIR_MODULES_CATALOG)];
		
		foreach ($arr_it_directory as $directory_type => $it_directory) {
			
			if ($directory_type != 'catalog' || ($directory_type == 'catalog' && $arr_links)) {
				
				foreach ($it_directory as $file) {
					
					if($file->isFile()) {
						
						if ($directory_type != 'catalog' && ($file->getExtension() == 'mlnk')) {
							
							$class = $file->getBasename('.mlnk');
							$arr_links[$class] = true;
						} else {
							
							$filename = $file->getFilename();
							preg_match('/^[0-9]*-(.*)\.php/', $filename, $match);
							$class = $match[1];
							
							if (($directory_type != 'catalog' && !isset($arr_modules[$class])) || ($directory_type == 'catalog' && !empty($arr_links[$class]))) {
								
								$path_file = $file->getPath().'/';
								$arr_modules[$class] = ['file' => $filename, 'path' => $path_file, 'time' => $file->getMTime()];
							}
						}
					}
				}
			}
		}
		
		uasort($arr_modules, function($a, $b) {
			
			if ($a['file'] === $b['file']) { // Should not happen
				return 0;
			}
			return ($a['file'] < $b['file'] ? -1 : 1);
		});
		
		spl_autoload_register($autoload_abstract, true, true);
		
		foreach ($arr_modules as $class => $arr) {
			require($arr['path'].$arr['file']);
		}
		
		spl_autoload_unregister($autoload_abstract);
		
		return $arr_modules;
	}
	
	function getModuleConfiguration($method, $do_call = true, $level = false, $module = false) {
		
		if ($module) {
			
			$value = false;

			if (method_exists($module, $method)) {
				
				if ($do_call) {
					
					$res = $module::$method();
					
					if ($res !== null) {
						$value = $res;
					}
				} else {
				
					$value = $method;
				}
			}
			
			return $value;
		}
		
		$arr = [];
			
		switch ($level) {
			case DIR_HOME:
				$arr_modules = (IS_CMS ? getModules(DIR_HOME) : SiteStartEnvironment::getModules(false, DIR_HOME));
				break;
			case DIR_CMS:
				$arr_modules = SiteStartEnvironment::getModules(false, DIR_CMS);
				break;
			default:
				$arr_modules = SiteStartEnvironment::getModules();
		}

		foreach ($arr_modules as $module => $value) {
			
			if (!method_exists($module, $method)) {
				continue;
			}
			
			if ($do_call) {
				
				$res = $module::$method();
				
				if ($res === null) {
					continue;
				}
				
				$arr[$module] = $res;
			} else {
			
				$arr[$module] = $method;
			}
		}
		
		return $arr;
	}
	
	function findFilePath($str_filename, $str_path_file, $str_path_check) {
		
		static $arr_path_files = [];
		
		if (!isPath($str_path_check)) {
			return false;
		}

		if (!isset($arr_path_files[$str_path_check])) {
			
			$it_directory = new RecursiveDirectoryIterator($str_path_check, RecursiveDirectoryIterator::SKIP_DOTS);
			$it_files = new RecursiveIteratorIterator($it_directory, RecursiveIteratorIterator::LEAVES_ONLY); // LEAVES_ONLY, only files

			foreach ($it_files as $file) {
				
				$str_path_found = $file->getPath();
				$str_filename_found = $file->getFilename();
				
				$arr_path_file =& $arr_path_files[$str_path_check][$str_filename_found];
				
				if (isset($arr_path_file)) {
					
					if (!is_array($arr_path_file)) {
						$arr_path_file = [$arr_path_file];
					}
					
					$arr_path_file[] = $str_path_found;
				} else {
					
					$arr_path_file = $str_path_found;
				}
			}
			
			unset($arr_path_file);
		}
		
		$arr_path_file = ($arr_path_files[$str_path_check][$str_filename] ?? null);
				
		if (!$arr_path_file) {
			return false;
		}

		if (is_array($arr_path_file)) {
			
			$str_path = false;
			
			foreach ($arr_path_file as $str_path_found) {
				
				if (!strEndsWith($str_path_found, $str_path_file)) {
					continue;
				}
				
				$str_path_match = $str_path_found.'/'.$str_filename;
				
				if (!$str_path || strlen($str_path_match) < strlen($str_path)) { // Use the best match
					$str_path = $str_path_match;
				}
			}
			
			return $str_path;
		}
		
		$str_path = $arr_path_file;
			
		return $str_path.'/'.$str_filename;
	}
	
	function autoLoadClass($class) {
		
		$str_filename = $class.'.php';
		$str_path_file = '';
		
		if (strpos($str_filename, '\\') !== false) {
			
			$arr_path = explode('\\', $str_filename);
			
			$str_filename = array_pop($arr_path);
			$str_path_file = implode('\\', $arr_path);
		}

		$arr_paths = [
			DIR_SITE.DIR_CLASSES,
			DIR_SITE.DIR_CMS.DIR_CLASSES,
			DIR_CORE.DIR_CLASSES,
			DIR_CORE.DIR_CMS.DIR_CLASSES
		];
		
		foreach ($arr_paths as $str_path_check) {
			
			$str_path = findFilePath($str_filename, $str_path_file, $str_path_check);
			
			if (!$str_path) {
				continue;
			}
			
			require($str_path);
			
			return true;
		}
		
		return false;
	}
		
	function getIcon($id = '') {
		
		static $arr_icons = [];

		if (isset($arr_icons[$id])) {
			return $arr_icons[$id];
		}
			
		$path = DIR_CMS.DIR_CSS.'images/icons/'.$id.'.svg';
		$path = (isPath(DIR_ROOT_SITE.$path) ? DIR_ROOT_SITE.$path : (isPath(DIR_ROOT_CORE.$path) ? DIR_ROOT_CORE.$path : false));
		
		$svg = '';
		
		if ($path) {
			$svg = readSVG($path);
		}
		
		$arr_icons[$id] = $svg;
		
		return $arr_icons[$id];
	}
	
	function memoryBoost($num_mb = 1000, $do_add = false) {
		
		if ($num_mb === false) {
			
			ini_set('memory_limit', -1);
			return;
		}
		
		if ($do_add) {
			
			$num_add = str2Bytes(ini_get('memory_limit'));
			$num_mb += ($num_add / BYTE_MULTIPLIER / BYTE_MULTIPLIER); // To MegaBytes
		}
		
		ini_set('memory_limit', $num_mb.'M');
	}
	
	function timeLimit($num_seconds = true) {
		
		if ($num_seconds === false) {
			$num_seconds = 0;
		} else if ($num_seconds === true) {
			$num_seconds = 60;
		}
		
		set_time_limit($num_seconds);
	}
		
	function getExecutionTime($reset = false) {
		
		static $microtime_start = null;
		
		if ($reset) {
			$microtime_start = null;
		}
		
		if ($microtime_start === null) {
			$microtime_start = microtime(true);
			return 0.0;
		}
		
		return microtime(true) - $microtime_start;
	}
	
	function getExecutionMemory($reset = false) {
		
		static $memory_start = null;
		
		if ($reset) {
			$memory_start = null;
		}
		if ($memory_start === null) {
			$memory_start = memory_get_usage();
			return 0;
		}
		
		return (memory_get_usage() - $memory_start);
	}

	function onUserPoll($func_poll, $func_abort) {
		
		SiteStartEnvironment::checkCookieSupport();
						
		SiteStartEnvironment::stopSession();
		
		$num_count = 0;
		
		while (true) {
			
			$is_alive = Mediator::checkState(); // Check connection
			
			if (!$is_alive) {
				
				$func_abort();
				exit;
			} else { // Check if the session is still current (more foolproof)
				
				// Update session variables
				SiteStartEnvironment::startSession();
				SiteStartEnvironment::stopSession();
				
				// Check if session has loaded elsewhere
				if (!SiteStartEnvironment::checkSession()) {
					$func_abort();
					exit;
				}
			}
			
			// If polling function finishes, continue
			if ($func_poll()) {
				break;
			}
			
			usleep(($num_count < 10 ? 20000 : 100000)); // 100ms, first 10 loops 20ms
			$num_count++;
		}
				
		// Not aborted, continue
		SiteStartEnvironment::startSession();
	}
	
	function onUserPollContinuous($func_poll, $func_abort) { // Keep polling continuously, without sleep, but do keep track of checking state 
		
		SiteStartEnvironment::checkCookieSupport();

		SiteStartEnvironment::stopSession();
		
		$num_time = microtime(true);
		$num_count = 0;
		
		while (true) {
			
			$num_cur_time = microtime(true);
			
			if (($num_cur_time - $num_time) > ($num_count < 10 ? 0.2 : 0.1)) { // 100ms, first 10 loops 20ms
			
				$is_alive = Mediator::checkState(); // Check connection
			
				if (!$is_alive) {
					
					$func_abort();
					exit;
				} else { // Check if the session is still current (more foolproof)
					
					// Update session variables
					SiteStartEnvironment::startSession();
					SiteStartEnvironment::stopSession();
					
					// Check if session has loaded elsewhere
					if (!SiteStartEnvironment::checkSession()) {
						$func_abort();
						exit;
					}
				}
				
				$num_time = $num_cur_time;
				$num_count++;
			}
			
			// If polling function finishes, continue
			if ($func_poll()) {
				break;
			}			
		}
				
		// Not aborted, continue
		SiteStartEnvironment::startSession();
	}
	
	function variableHasValue($variable, ...$values) {
		
		foreach ($values as $value) {
			
			if ($variable === $value) {
				return true;
			}
		}
		
		return false;
	}
	
	const BIT_MODE_ADD = 1;
	const BIT_MODE_SUBTRACT = 2;
	
	function bitHasMode($bit, ...$bit_flags) {
		
		foreach ($bit_flags as $bit_flag) {
			
			if (($bit & $bit_flag) === $bit_flag) {
				return true;
			}
		}
		
		return false;
	}
	
	function bitUpdateMode($bit, $mode, ...$bit_flags) {
		
		foreach ($bit_flags as $bit_flag) {
			
			if ($mode === BIT_MODE_ADD) {
				$bit |= $bit_flag;
			} else if ($mode === BIT_MODE_SUBTRACT) {
				$bit &= ~$bit_flag;
			}
		}
		
		return $bit;
	}
		
	function isPath($str_path) {
	
		return ($str_path ? file_exists($str_path) : false);
	}
	function isResource($file) {
	
		return is_resource($file);
	}
	
	function read($file, $do_output = false) {
		
		if (is_resource($file)) {
			if (!$do_output) {
				return stream_get_contents($file);
			} else {
				return fpassthru($file);
			}
		} else {
			if (!$do_output) {
				return file_get_contents($file);
			} else {
				return readfile($file);
			}
		}
	}
	
	function readText($str_path) {
	
		return rtrim(read($str_path));
	}
	
	function readSVG($str_path) {
		
		$svg = read($str_path);
		$svg = str_replace(["\n", "\t"], ['', ' '], $svg);
		$svg = trim($svg);
	
		return $svg;
	}
	
	function resourceSkipBOM($file) {
		
		$arr_BOM = ["\xEF\xBB\xBF"];
		$str_test = fgets($file, 4+1); // BOM can be 4 bytes long
		$num_offset = 0;
		$str_found = false;
		
		foreach ($arr_BOM as $str_BOM) {
			
			if (!strStartsWith($str_test, $str_BOM)) {
				continue;
			}
			
			$num_offset = strlen($str_BOM);
			$str_found = $str_BOM;
		}
		
		fseek($file, ($num_offset-4), SEEK_CUR); // Do not rewind
		
		return $str_found;
	}
	
	function getPathTemporary($str_class = false, $is_directory = false, $str_path = false) {
		
		if ($str_path) {
			
			if (!isPath($str_path)) {
				mkdir($str_path, 00700, true);
			}
		} else {
			
			$str_path = Settings::get('path_temporary');
		}
		
		$str_temporary = tempnam($str_path, ($is_directory ? '_' : '').($str_class ?: '1100CC'));
		
		if ($is_directory) { // Switch temporary file to a directory
			
			unlink($str_temporary);
			mkdir($str_temporary, 00700);
			$str_temporary = $str_temporary.'/';
		}
		
		return $str_temporary;
	}
	
	function getStreamMemory($do_read = true, $num_size = 100) {
		
		return fopen('php://temp/maxmemory:'.($num_size * BYTE_MULTIPLIER * BYTE_MULTIPLIER), (!$do_read ? 'w' : 'w+')); // Keep resource in memory until it reaches a certain MB-size, otherwise create a temporary file
	}

	function generateHash($password) {
		
		$hash = password_hash($password, PASSWORD_DEFAULT);

		return $hash;
	}
	
	function checkHash($password, $hash) {
		
		if (substr($hash, 0, 3) == '---') { // Legacy
			
			$arr_hash = explode('---', $hash);
			
			if ($arr_hash[1] !== sha1($password.$arr_hash[2].$arr_hash[3])) {
				return false;
			}
			
			$do_rehash = true;
		} else {
					
			if (!password_verify($password, $hash)) {
				return false;
			}
			
			$do_rehash = password_needs_rehash($hash, PASSWORD_DEFAULT);
		}

		if ($do_rehash) {
			$hash = password_hash($password, PASSWORD_DEFAULT);
		}

		return $hash;
	}
	
	function generateRandomString($num_length, $str_characters = null) {
		
		$str_secret = '';
		$str_characters = ($str_characters ? $str_characters : 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ023456789');
		
		for ($i = 0; $i < $num_length; $i++) {
			
			$num_generate = rand(0, strlen($str_characters));
			$str_append = substr($str_characters, $num_generate, 1);
			$str_secret .= $str_append;
		}
		
		return $str_secret;
	}
	
	function generateSecret($num_length, $str_charset = 'unicode') {
		
		$str_secret = '';
		
		for ($i = 0; $i < $num_length; $i++) {
			
			if ($str_charset == 'ascii') {
				$str_secret .= chr(mt_rand(33, 255)); // ASCII
			} else {
				$str_secret .= mb_convert_encoding('&#'.mt_rand(33, 1000).';', 'UTF-8', 'HTML-ENTITIES'); // Unicode
			}
		}
		
		return $str_secret;
	}
	
	function checkPasswordStrength($str_password) {
		
		// Accounts for multi-script/unicode, https://www.php.net/manual/en/regexp.reference.unicode.php

		if (mb_strlen($str_password) < 8) {
			return 0;
		}
		
		$num_strength = 0;
		
		if (preg_match('/\p{Latin}/u', $str_password)) { // Alphabetic latin
			$num_strength += 1;
		}
		
		if (preg_match('/(?=\p{L})(?!\p{Latin})/u', $str_password)) { // Alphabetic non-latin
			$num_strength += 1;
		}
		
		/*if (preg_match('/(?:\p{Ll}.*\p{Lu}|(?:.\p{Lu}.*\p{Ll})|\p{Lt}|\p{Lm})/u', $str_password)) { // Mixed cases, title case ('ǲ' vs 'ǳ|Ǳ'), or modifier
			$num_strength += 1;
		}*/
		
		if (preg_match('/[\p{Nl}\p{No}]/u', $str_password)) { // Number-letter or other (e.g. roman numerals)
			$num_strength += 1;
		}
		
		if (preg_match('/\p{Nd}/u', $str_password)) { // Number-digit (any script)
			$num_strength += 1;
		}
			
		if (preg_match('/[\p{P}\p{S}]/u', $str_password)) { // Punctuation or symbol characters
			$num_strength += 1;
		}

		return ($num_strength >= 3);
	}

	function value2HashExchange($value) { // Calculable/exchangeable externally (e.g. database, ProcessProgram)
		
		$value = (is_array($value) || is_object($value) ? json_encode($value) : $value);
		
		return hash('md5', $value);
	}
	
	function value2Hash($value) {
		
		$value = (is_array($value) || is_object($value) ? serialize($value) : $value);

		return hash('md5', $value);
	}
	
	function value2JSON($value, $flags = 0, $do_default = true) {
		
		$flags = ($do_default ? (JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR | $flags) : $flags);
		
		return json_encode($value, $flags);
	}
	
	function JSON2Value($json, $flags = 0, $do_default = true) {
				
		$flags = ($do_default ? (JSON_OBJECT_AS_ARRAY | $flags) : $flags);
		
		return json_decode($json, null, 512, $flags);
	}
	
	function value2YAML($value, $arr_callbacks = []) {
				
		return yaml_emit($value, YAML_ANY_ENCODING, YAML_ANY_BREAK, $arr_callbacks);
	}
	
	function YAML2Value($yaml, $arr_callbacks = []) {
				
		return yaml_parse($yaml, 0, $num_docs, $arr_callbacks);
	}
	
	function strSerial2Value($str, &$str_leftover = null, $func_callback = null) {
		
		if (!$str) {
			return null;
		}
		
		$str_serial = null;
		$func_parse = null;
		
		static $arr_serials = [['YAML2Value', '---', '...', false], ['JSON2Value', '{', '}', true], ['JSON2Value', '[', ']', true]];
		
		foreach ($arr_serials as $arr_serial) {
			
			$num_pos_start = strpos($str, $arr_serial[1]);
			
			if ($num_pos_start === false) {
				continue;
			}
			
			$num_pos_end = strpos($str, $arr_serial[2], $num_pos_start);
		
			if ($num_pos_end === false && $arr_serials[4] === true) { // [4] = end is required
				continue;
			}
			
			$num_length = ($num_pos_end === false ? null : ($num_pos_end + strlen($arr_serial[2])) - $num_pos_start);
			$str_serial = substr($str, $num_pos_start, $num_length);
			$func_parse = $arr_serial[0];
			
			break;
		}
				
		if ($str_serial === null) {
			return null;
		}
		
		if (isset($func_callback)) {
			$value = $func_callback($func_parse, $str_serial);
		} else {
			$value = $func_parse($str_serial);
		}
		
		$str_leftover = trim(str_replace($str_serial, '', $str));
		
		return $value;
	}

	function str2Array($str, $separator = '_') {
		
		if (!$str) {
			return [];
		}
		
		return explode($separator, $str);
	}
	
	function arr2String($arr, $separator = '_') {
		
		return implode($separator, $arr);
	}
	
	function arr2StringRecursive($arr, $separator = '_') {
		
		$str_path = '';
		
		$func_walk = function($v) use (&$str_path, $separator) {
			$str_path .= ($str_path !== '' ? $separator : '').$v;
		};
		
		array_walk_recursive($arr, $func_walk);
		
		return $str_path;
	}
				
	function bytes2String($num_bytes) {
		
		$arr_ext = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
		$count = 0;
		
		for(; $num_bytes > BYTE_MULTIPLIER; $count++) {
			$num_bytes /= BYTE_MULTIPLIER;
		}
		
		return round($num_bytes, 2).' '.$arr_ext[$count];
	}
	
	function str2Bytes($str) {
		
		if (!$str) {
			return 0;
		}
		
		preg_match('/^(\d*\.?\d+)([a-z]*)$/i', trim($str), $arr_str);
		$num = (float)$arr_str[1];
		$ext = strtolower($arr_str[2]);
		
		$arr_ext = ['' => 1, 'b' => 1, 'kb' => BYTE_MULTIPLIER, 'k' => BYTE_MULTIPLIER, 'mb' => BYTE_MULTIPLIER**2, 'm' => BYTE_MULTIPLIER**2, 'gb' => BYTE_MULTIPLIER**3, 'g' => BYTE_MULTIPLIER**3, 'tb' => BYTE_MULTIPLIER**4];
		
		return ($num * (int)$arr_ext[$ext]);
	}
	
	function num2String($nr, $decimals = 0) {
		
		return number_format($nr, $decimals, '.', SYMBOL_SPACE_MATHEMATICAL);
	}
	
	function numRoundBetter($number, $precision = 0, $mode = PHP_ROUND_HALF_UP, $direction = null) {
		
		if (!isset($direction)) {
			
			return round($number, $precision, $mode);
		} else {
			
			$factor = pow(10, -1 * $precision);

			return ($direction === true
				? floor($number / $factor) * $factor
				: ceil($number / $factor) * $factor);
		}
	}
	function numRoundBetterUp($number, $precision = 0, $mode = PHP_ROUND_HALF_UP) {
		return numRoundBetter($number, $precision, $mode, true);
	}
	function numRoundBetterDown($number, $precision = 0, $mode = PHP_ROUND_HALF_UP) {
		return numRoundBetter($number, $precision, $mode, false);
	}
		
	function ip2Hex($ip) {
	
		$ip = explode('.', $ip);
		$hex = '';
		for ($i = 0; $i < count($ip); $i++) {
			$hex .= str_pad(dechex($ip[$i]), 2, '0', STR_PAD_LEFT);
		}
		
		return $hex;
	}
	
	function hex2Ip($hex) {
		
		for ($i = 0; $i < strlen($hex)-1; $i += 2) {
			$ip[] = hexdec($hex[$i].$hex[$i+1]);
		}
		
		return implode('.', $ip);
	}
	
	function filename2Name($str) {
		
		$arr_info = pathinfo($str);
		$str = basename($str, '.'.$arr_info['extension']);
		
		return $str;
	}
	
	function str2Clean($str) {
		
		if ($str === null) {
			return '';
		}
		
		$str = preg_replace('/[^\PC\s]/', '', $str); // Remove non-printable characters: not, not-control characters or whitespace characters ('\PC' is inverse form of '\p{C}')
		
		return $str;
	}

	function str2Name($str, $str_keep = false, $str_replace = '') {
		
		if (!$str) {
			return (string)$str;
		}
		
		return strtolower(preg_replace('/[^a-z0-9'.($str_keep ? preg_quote($str_keep, '/') : '').']/i', $str_replace, $str));
	}
	
	function str2Label($str, $str_keep = false, $str_replace = '', $str_whitespace = '_') {
		
		if (!$str) {
			return (string)$str;
		}
		
		return strtolower(preg_replace('/[^a-z0-9-_'.($str_keep ? preg_quote($str_keep, '/') : '').']/i', $str_replace, str_replace(' ', $str_whitespace, $str)));
	}
	
	function str2URL($str, $str_keep = false) {
		
		return str2Label($str, $str_keep, '', '-');
	}
	
	function str2Color($str, $code = 'hex') {
		
		if (!$str) {
			return false;
		}
		
		if ($code == 'hex') {
			return '#'.strtolower(substr(preg_replace('/[^a-f0-9]/i', '', $str), 0, 8));
		}
	}
	
	function strShift($str, $n = 13) {
		
		static $letters = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz';
		static $numbers = '0123456789';
		
		$n_letters = (int)$n % 26;
		if ($n_letters < 0) {
			$n_letters += 26;
		}
		
		$n_numbers = (int)$n % 10;
		if ($n_numbers < 0) {
			$n_numbers += 10;
		}
		
		if (!$n_letters && !$n_numbers) {
			return $str;
		}
		
		if ($n_letters) {
			$letters_replace = substr($letters, $n_letters * 2) . substr($letters, 0, $n_letters * 2);
		} else {
			$letters_replace = $letters;
		}
		if ($n_numbers) {
			$numbers_replace = substr($numbers, $n_numbers) . substr($numbers, 0, $n_numbers);
		} else {
			$numbers_replace = $numbers;
		}
		
		return strtr($str, $letters.$numbers, $letters_replace.$numbers_replace);
	}
	
	function strIndent($str, $str_indent = "\t") {
		
		return $str_indent.str_replace("\n", "\n".$str_indent, $str);
	}
	
	function strWrap($string, $width = 75, $break = PHP_EOL, $cut = true) {
	
		if ($cut) {
			// Match anything 1 to $width chars long followed by whitespace or EOS,
			// otherwise match anything $width chars long
			$search = '/(.{1,'.$width.'})(?:\s|$)|(.{'.$width.'})/uS';
			$replace = '$1$2'.$break;
		} else {
			// Anchor the beginning of the pattern with a lookahead
			// to avoid crazy backtracking when words are longer than $width
			$search = '/(?=\s)(.{1,'.$width.'})(?:\s|$)/uS';
			$replace = '$1'.$break;
		}
		
		return preg_replace($search, $replace, $string);
	}
		
	function strStartsWith($str, $str_test) {
				
		return (strncmp($str, $str_test, strlen($str_test)) === 0);
	}
	
	function strEndsWith($str, $str_test) {
		
		$num_str = strlen($str);
		$num_str_test = strlen($str_test);
		
		if ($num_str_test > $num_str) {
			return false;
		}
		
		return (substr_compare($str, $str_test, $num_str - $num_str_test, $num_str_test) === 0);
	}
	
	function strMatchesWith($str, $str_test, $mode = MATCH_FULL) {

		return preg_match('/'.($mode == MATCH_FULL || $mode == MATCH_START ? '^' : '').str2Search($str_test).($mode == MATCH_FULL || $mode == MATCH_END ? '$' : '').'/', $str);
	}

	function str2Search($str) {
		
		static $arr_replace = null;
		if ($arr_replace === null) {
			$arr_replace = [preg_quote('[*]', '/') => '.*', preg_quote('[*1]', '/') => '.', preg_quote('[*2]', '/') => '..', preg_quote('[*3]', '/') => '...'];
		}
		
		$str = preg_quote($str, '/');
		$str = strtr($str, $arr_replace);
		
		return $str;
	}
	
	function parseRegularExpression($str_pattern, $str_flags, $str_template = null, $do_parse_self = false) {
		
		$str_pattern = trim($str_pattern);
		
		$str_flags_test = '';

		if ($str_flags) {
			
			$str_flags = preg_replace('/[^'.REGEX_CASE_INSENSITIVE.REGEX_LINE.REGEX_DOT_SPECIAL.']*/', '', $str_flags);
			$str_flags_test = parseRegularExpressionFlags($str_flags, [REGEX_CASE_INSENSITIVE => 'i', REGEX_LINE => 'm', REGEX_NOFLAG.REGEX_DOT_SPECIAL => 's']);
		}
		
		$str_template = $str_template; // Can be empty to replace with an empty string
		
		if ($do_parse_self) { // Use for strRegularExpression
			
			$str_flags = $str_flags_test;
			
			if ($str_template !== null) {
				$str_template = stripcslashes($str_template);
			}
		}

		if (!$str_pattern) {
			return false;
		}
		
		// Make sure the pattern is not erroneous
		try {
			if ($str_template !== null) {
				preg_replace('/'.$str_pattern.'/'.$str_flags_test, $str_template, 'TEST');
			} else {
				preg_match('/'.$str_pattern.'/'.$str_flags_test, 'TEST');
			}
		} catch (Exception $e) {
			$str_pattern = preg_quote($str_pattern, '/');
		}
		
		return ['pattern' => $str_pattern, 'flags' => $str_flags, 'template' => $str_template];
	}
	
	function parseRegularExpressionFlags($str_flags, $arr_translate) {
		
		$str_flags_translate = '';
		$func_get_translate = fn($mode) => ($arr_translate[$mode] ?? '');
		
		if ($str_flags) {
			
			if (strpos($str_flags, REGEX_DOT_SPECIAL) !== false) { // Dot does not match newlines
				$str_flags_translate .= $func_get_translate(REGEX_DOT_SPECIAL);
			} else {
				$str_flags_translate .= $func_get_translate(REGEX_NOFLAG.REGEX_DOT_SPECIAL);
			}
			if (strpos($str_flags, REGEX_LINE) !== false) { // Start and end of line ($^) matches newlines
				$str_flags_translate .= $func_get_translate(REGEX_LINE);
			} else {
				$str_flags_translate .= $func_get_translate(REGEX_NOFLAG.REGEX_LINE);
			}
			if (strpos($str_flags, REGEX_CASE_INSENSITIVE) !== false) { // Case-insensitive
				$str_flags_translate .= $func_get_translate(REGEX_CASE_INSENSITIVE);
			} else { // Case-sensitive
				$str_flags_translate .= $func_get_translate(REGEX_NOFLAG.REGEX_CASE_INSENSITIVE);
			}
		} else {
			
			$str_flags_translate = $func_get_translate(REGEX_NOFLAG.REGEX_CASE_INSENSITIVE).$func_get_translate(REGEX_NOFLAG.REGEX_LINE).$func_get_translate(REGEX_NOFLAG.REGEX_DOT_SPECIAL);
		}
		
		return $str_flags_translate;
	}
	
	function strRegularExpression($str, $str_pattern, $str_flags, $str_template) {
		
		return preg_replace('/'.$str_pattern.'/u'.$str_flags, $str_template, $str); // Enable unicode support 'u'
	}
	
	function parseValue($value, $what, $keep_null = false) {
		
		if ($keep_null && $value === null) {
			return $value;
		}
		
		switch ($what) {
			
			case TYPE_INTEGER:
				$value = (int)$value;
				break;
			case TYPE_FLOAT:
				$value = (float)$value;
				break;
			case TYPE_BOOLEAN:
				$value = (bool)$value;
				break;
			case TYPE_STRING:
				$value = (string)$value;
				if ($value !== '') {
					$value = trim($value, " \x00..\x1F\x7F"); // Also remove control characters
					$value = str_replace(["\r\n", "\n"], ' ', $value); // Clear linebreaks
				}
				break;
			case TYPE_TEXT:
				$value = (string)$value;
				if ($value !== '') {
					$value = trim($value, " \x00..\x1F\x7F"); // Also remove control characters
				}
				break;
			default:
				$value = $what($value);
		}
		
		return $value;
	}
	
	function arrParseRecursive($arr, $what = TYPE_INTEGER, $arr_keys = null, $keys_include = true) {
		
		if (!is_array($arr)) {
			return parseValue($arr, $what);
		}
		
		if ($arr_keys !== null && !is_array($arr_keys)) {
			$arr_keys = [$arr_keys => true];
		}
		
		foreach ($arr as $key => &$value) {
			
			if ($arr_keys !== null) {
				
				if ($keys_include == true && empty($arr_keys[$key]) || $keys_include == false && !empty($arr_keys[$key])) {
					continue;
				}
			}
			
			if (is_array($value)) { // Recursive
				$value = arrParseRecursive($value, $what, $arr_keys, $keys_include);
			} else {
				$value = parseValue($value, $what);
			}
		}
		
		return $arr;
	}
	
	function arrFilterRecursive($arr, $func_check = null, $flag = 0) {
		
		foreach ($arr as &$value) {
			
			if (is_array($value)) {
				$value = arrFilterRecursive($value, $func_check); 
			} 
		} 
		
		if ($func_check !== null) {
			return array_filter($arr, $func_check, $flag);
		} else {
			return array_filter($arr);
		}
	}
		
	function arrValuesRecursive($arr_keys, $arr, $do_positive = true, &$arr_flat = [], $do_unique = false, $parse_what = null) {
		
		if ($arr_keys !== null && !is_array($arr_keys)) {
			$arr_keys = [$arr_keys => true];
		}
	
		foreach ($arr as $k => $v) {
		
			if ($v === null || ($do_positive && !$v)) {
				continue;
			}
			
			if (isset($arr_keys[$k])) {
				
				$v_use = ($parse_what !== null ? parseValue($v, $parse_what) : $v);
				
				if ($do_unique) {
					$arr_flat[$v_use] = $v_use;
				} else {
					$arr_flat[] = $v_use;
				}
			}
			
			if (is_array($v)) { // Recursive
				
				arrValuesRecursive($arr_keys, $v, $do_positive, $arr_flat, $do_unique);
				continue;
			}
			
			if ($arr_keys === null) {
				
				$v_use = ($parse_what !== null ? parseValue($v, $parse_what) : $v);
				
				if ($do_unique) {
					$arr_flat[$v_use] = $v_use;
				} else {
					$arr_flat[] = $v_use;
				}
			}
		}
		
		return $arr_flat;
	}
	
	function arrValuesRecursiveParse($arr_keys, $arr, $parse_what, $do_positive = true, &$arr_flat = []) {
		
		if (!is_array($arr)) {
			return parseValue($arr, $parse_what);
		}
		
		return arrValuesRecursive($arr_keys, $arr, $do_positive, $arr_flat, false, $parse_what);
	}
	
	function arrValuesRecursiveUniqueParse($arr_keys, $arr, $parse_what, $do_positive = true, &$arr_flat = []) {
		
		if (!is_array($arr)) {
			return parseValue($arr, $parse_what);
		}
		
		return arrValuesRecursive($arr_keys, $arr, $do_positive, $arr_flat, true, $parse_what);
	}
	
	function arrValuesRecursiveUnique($arr_keys, $arr, $do_positive = true, &$arr_flat = []) {
		
		return arrValuesRecursive($arr_keys, $arr, $do_positive, $arr_flat, true);
	}
	
	function arrHasValuesRecursive($key, $arr_values, $arr) {
		
		if (!is_array($arr_values)) {
			$arr_values = [$arr_values => true];
		}
	
		foreach ($arr as $k => $v) {
			
			$is_array_v = is_array($v);
			
			if (($k === $key || $key === null) && !$is_array_v && isset($arr_values[$v])) {
				return $v;
			}
			
			if ($v && $is_array_v) { // Recursive

				$value_found = arrHasValuesRecursive($key, $arr_values, $v);
				
				if ($value_found !== null) {
					return $value_found;
				}
			}
		}
		
		return null;
	}
	
	function arrHasKeysRecursive($arr_keys, $arr, $do_positive = false) {
		
		if (!is_array($arr_keys)) {
			$arr_keys = [$arr_keys => true];
		}
	
		foreach ($arr as $k => $v) {
			
			if ($v === null) {
				continue;
			}
			
			if (!empty($arr_keys[$k])) {
				
				if (!$do_positive) { // Any key (with or without value)
					return $k;
				} else if ($v) { // Only keys with a positive value
					return $k;
				}
			}
			
			if ($v && is_array($v)) { // Recursive
				
				$key_found = arrHasKeysRecursive($arr_keys, $v, $do_positive);
				
				if ($key_found !== null) {
					return $key_found;
				}
			}
		}
		
		return null;
	}
	
	function arrFlattenKeysRecursive($arr) {
		
		$arr_collect = [];
		
		foreach ($arr as $key => $value) {
			
			if (is_array($value)) {
				
				$arr_result = arrFlattenKeysRecursive($value);
				
				if ($key === 0) {
					
					foreach ($arr_result as $k => $v) {
						$arr_collect[] = ['[]' => $v];
					}
				}
				
				foreach ($arr_result as $k => $v) {
					$arr_collect[] = [$key => $v];
				}
			} else {
				
				if ($key === 0) {
					$arr_collect[] = ['[]' => ''];
				}
				
				$arr_collect[] = [$key => ''];
			}
		}
		
		return $arr_collect;
	}
	
	function arrSortKeysRecursive($arr, $flag_sort = SORT_STRING) {
		
		 // $flag_sort = SORT_STRING, compare as strings by default, because array keys could be of mixed types

		if (!is_array($arr)) {
			return $arr;
		}
			
		ksort($arr, $flag_sort);
			
		foreach ($arr as &$v) {
			$v = arrSortKeysRecursive($v, $flag_sort); // Recursive
		}
		
		return $arr;
	}
	
	function arrSortByArray($arr, $arr_sort) {
		
		$arr_sorted = [];
		
		foreach ($arr_sort as $key) {
			
			if (!isset($arr[$key])) { // Or array_key_exists for all keys
				continue;
			}
			
			$arr_sorted[$key] = $arr[$key];
			unset($arr[$key]);
		}
		
		return $arr_sorted + $arr;
	}
	
	function arrInsert(&$arr, $pos, $arr_insert, $before = false) {
		
		if (is_integer($pos)) {
			
			array_splice($arr, $pos+1, 0, $arr_insert);
		} else {
			
			$pos = array_search($pos, array_keys($arr))+(!$before ? 1 : 0);
				
			if (!$before && $pos == count($arr)) {
				
				$arr += $arr_insert;
			} else {
				
				$arr = array_merge(
					array_slice($arr, 0, $pos),
					$arr_insert,
					array_slice($arr, $pos)
				);
			}
		}
	}
	
	function arrMerge(...$arrs) { // Merge arrays: string keys will be overwitten
		
		if (!isset($arrs[1])) {
			$arrs = current($arrs);
		}
		
		return array_merge(...$arrs);
	}
	
	function arrMergeKeys(...$arrs) { // Merge arrays: all keys will be overwitten
		
		if (!isset($arrs[1])) {
			$arrs = current($arrs);
		}
		
		$arr_collect = [];
		
		foreach ($arrs as $arr) {
			$arr_collect += $arr;
		}
		
		return $arr_collect;
	}
	
	function arrMergeValues(...$arrs) { // Merge arrays: values will be overwitten
		
		if (!isset($arrs[1])) {
			$arrs = current($arrs);
		}
		
		$arr_buffer = [];
		
		foreach ($arrs as $arr) {
			foreach ($arr as $v) {
				$arr_buffer[$v] = true;
			}
		}
		
		return array_keys($arr_buffer);
	}
	
	function arrIsAssociative($arr) {
		
		return (bool)count(array_filter(array_keys($arr), 'is_string'));
	}
	
	function arrIsEqual(...$arrs) { // Check if transported arrays (i.e. former JSON object) are equal to active ones

		static $func_parse = null;
		
		if ($func_parse === null) {
			
			$func_parse = function($value) use (&$func_parse) {
					
				if (!is_array($value)) {
					
					if (is_numeric($value)) { // Account for difference between a floaty '1' and an integer '1'
						$value = (string)$value;
					}
					
					return $value;
				}
					
				ksort($value, SORT_STRING);
					
				foreach ($value as &$v) {
					$v = $func_parse($v);
				}
				
				return $value;
			};
		}
		
		$arr_first = null;
		
		foreach ($arrs as $arr) {
			
			if ($arr_first === null) {
				
				$arr_first = $func_parse($arr);
				continue;
			}
			
			if ($arr_first !== $func_parse($arr)) {
				return false;
			}
		}
		
		return true;
	}
	
	function arrRearrangeKeysValues($arr) { // $arr[key][0] => $arr[0][key]
		
		$arr_new = [];
		
		foreach ($arr as $key => $arr_all){
			foreach ($arr_all as $i => $value) {
				$arr_new[$i][$key] = $value;   
			}   
		}
		
		return $arr_new;
	}
	
	function arrChuckPartition($arr, $size) {
		
		$listlen = count($arr);
		$partlen = floor($listlen / $size);
		$partrem = $listlen % $size;
		$arr_partition = [];
		$mark = 0;
		for ($px = 0; $px < $size; $px++) {
			$incr = ($px < $partrem ? $partlen + 1 : $partlen);
			$arr_partition[$px] = array_slice($arr, $mark, $incr);
			$mark += $incr;
		}
		return $arr_partition;
	}
	
	function &arrByPath(&$arr, $str_path, $str_delimiter = '/') {
	
		$arr_walk = explode($str_delimiter, trim($str_path));
		
		$arr_current =& $arr;
	
		foreach ($arr_walk as $key) {
	
			if (!is_array($arr_current) || !array_key_exists($key, $arr_current)) {
				error();
			}
			
			$arr_current =& $arr_current[$key];
		}
	
		return $arr_current;
	}
	
	function keyIsUncontested($key, $arr) {
		
		return (!array_key_exists($key, $arr) || $arr[$key]);
	}
	
	function doNotXMLParse($s) {
		
		if (SiteStartEnvironment::getRequestState() == SiteStartEnvironment::REQUEST_INDEX) {
		
			$s = str_replace('<![CDATA[', '', $s); // Remove existing CDATA tags
			$s = str_replace(']]>', '', $s);
			
			return '<![CDATA['.$s.']]>';
		} else {
			
			return $s;
		}
	}
	
	function strIsValidEncoding($str) {
		
		if (!$str) {
			return true;
		}
		
		return mb_check_encoding($str); // Also does arrays
	}
	
	function arrHasValidStringEncoding($arr) {
		
		return strIsValidEncoding($arr);
	}
	
	function strFixEncoding($str) {
		
		if (!$str) {
			return $str;
		}
		
		return mb_convert_encoding($str, 'UTF-8'); // Also does arrays
	}
	
	function strEscapeXML($str_xml) {
		
		if (!$str_xml) {
			return (string)$str_xml;
		}
		
		return htmlspecialchars($str_xml, ENT_QUOTES | ENT_XML1);
	}
	
	function strEscapeXMLEntities($str_xml) {
		
		if (!$str_xml) {
			return (string)$str_xml;
		}
		
		return preg_replace('/&(?!#?[a-zA-Z0-9]+;)/', '&amp;', $str_xml);
	}
	
	function strEscapeHTML($str_html) {
		
		if (!$str_html) {
			return (string)$str_html;
		}
		
		return htmlspecialchars($str_html);
	}
	
	function strUnescapeHTML($str_html) {
		
		if (!$str_html) {
			return (string)$str_html;
		}
		
		return htmlspecialchars_decode($str_html);
	}
	
	function strParsePassthrough($str) {
		
		if (Settings::get('input_passthrough') !== false || !$str) {
			return $str;
		}
		
		$str_base = trim($str);
		
		if (!preg_match('/^[A-Za-z0-9+\/]+=*$/', $str_base)) {
			return $str;
		}
		
		return base64_decode($str_base);
	}
		
	function createContentIdentifier($arr) {
		
		$str_content_identifier = '';
		
		foreach ($arr as $str_content => $arr_identifiers) {
			
			foreach ($arr_identifiers as $str_identifier) {
				
				$str_content_identifier .= '|'.$str_content.':'.$str_identifier;
			}
		}
		
		return $str_content_identifier;
	}
	
	function createDate($date) {
		
		return '<time class="date"><span>'.implode('</span><span>', explode(',', date('d,M,Y', (is_integer($date) ? $date : strtotime($date))))).'</span></time>';
	}
	
	function createTime($date) {
		
		return '<time class="time"><span>'.implode('</span><span>', explode(',', date('H,:,i', (is_integer($date) ? $date : strtotime($date))))).'</span></time>';
	}

	function parseBody($body, $arr_options = []) {
	
		// $arr_options = array("extract" => number of paragraphs, "sanitise" => boolean, "append" => string, "function" => function);
	
		if (!$body) {
			return (string)$body;
		}
		
		$body = Labels::parseTextVariables($body);
		
		$label = Response::addParseDelay($body, function ($body) use ($arr_options) {
				
			if (!$body) {
				return;
			}
		
			$body = FormatTags::parse($body);
			
			$format = new FormatHTML($body, ($arr_options['newlines'] ?? true));
			
			if ($arr_options['sanitise']) {
				if (is_array($arr_options['sanitise'])) {
					$format->addSanitationValues($arr_options['sanitise']);
				}
				$format->sanitise();
			}
			
			if ($arr_options['extract']) {
				$format->extractParagraphs($arr_options['extract']);
				if ($arr_options['append'] && $format->count_elements_removed) {
					$format->addToLastParagraph($arr_options['append']);
				}
			}
			$format->cacheImages();
			
			if (Response::getFormat() & Response::RENDER_XML) {
				$body = $format->getXHTML();
			} else {
				$body = $format->getHTML();
			}
			
			if ($arr_options['function']) {
				$body = $arr_options['function']($body);
			}
			
			$body = Labels::printLabels($body);
			
			return $body;
		}, true);
		
		return $label;
	}
	
	abstract class base_module {

		public static $label;
		public static $parent_label;

		protected static $arr_cache = [];
		
		public $html;
		public $data;
		public $validate = [];
		public $do_confirm = false;
		public $do_download = false;
		public $refresh = false;
		public $refresh_table = false;
		public $reset_form = false;	
		public $style = false;
		public $message = false;
		
		public $is_confirm = null;
		public $is_download = null;
		public $is_discard = null;
		
		protected $arr_access = [];
		
		protected $mod_id;
		protected $arr_mod;
		protected $arr_variables;
		protected $arr_query;
		
		public static function moduleProperties() {
			
			static::$label = 'unknown';
			static::$parent_label = 'unknown';
		}
		
		//public static function moduleVariables() {}

		public static function setCache($key, $value = null) {
		
			self::$arr_cache[static::class][$key] = $value;
		}
		
		public static function getCache($key) {
			
			return (self::$arr_cache[static::class][$key] ?? null);
		}
		
		function __construct() {
			
			static::moduleProperties();
		}
		
		public function getExternalModule($module) {
			
			return $this->arr_access[$module];
		}
				
		public function setMod($arr_mod, $mod_id) {
				
			$this->arr_mod = $arr_mod;
			$this->mod_id = $mod_id;
		}
		
		public function setModQuery($arr_query) {
			
			$this->arr_query = $arr_query;
		}
		
		public function setModVariables($arr_variables) {
			
			if ($arr_variables === null || $arr_variables === '') {
				
				$this->arr_variables = [];
				return;
			}
			
			if (is_string($arr_variables) && substr($arr_variables, 0, 1) == '{') {
				
				$arr_variables = json_decode($arr_variables, true);
			}
			
			$this->arr_variables = $arr_variables;
		}
		
		//public function contents() {}
		//public function js() {}
		//public function css() {}
	}
	
	trait ClassAnalyse {
		
		public $byte_block;
		
		public function initialiseMemoryBlock() {
			
			$this->byte_block = str_repeat('*', 1024 * 1024 * 5);
		}
		
		public function getMemoryUsage($do_static = true) {
			
			$num_bytes = strlen(print_r((array)$this, true));
			
			if ($do_static) {
				$num_bytes += static::getMemoryUsageStatic();
			}
			
			return $num_bytes;
		}
		
		static public function getMemoryUsageStatic() {
			
			$class = get_called_class();
			$arr = [];
			
			$arr_variables = get_class_vars($class);
			$num_size = 0;
			
			foreach ($arr_variables as $name => $default) {
				
				if (!isset($class::$$name)) {
					continue;
				}
					
				$arr[$name] = $class::$$name;
			}
			
			$num_bytes = strlen(print_r($arr, true));
			
			return $num_bytes;
		}
	}
