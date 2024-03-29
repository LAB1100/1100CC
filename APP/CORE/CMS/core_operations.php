<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
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

	mb_internal_encoding('UTF-8');
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
	
	const TYPE_INTEGER = 'int';
	const TYPE_FLOAT = 'float';
	const TYPE_BOOLEAN = 'boolean';
	const TYPE_STRING = 'string';
	const TYPE_TEXT = 'text';
	
	const MATCH_FULL = 0;
	const MATCH_START = 1;
	const MATCH_END = 2;
	const MATCH_ANY = 3;
	
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
	
	function error($msg = '', $code = TROUBLE_ERROR, $suppress = LOG_BOTH, $debug = false, $exception = null) {
		
		if (is_array($msg)) {
			$msg = print_r($msg, true);
		}
		if (is_array($debug)) {
			$debug = print_r($debug, true);
		}
		
		Trouble::fling($msg, $code, $suppress, $debug, $exception);
	}
	
	function msg($msg = '', $label = false, $suppress = LOG_BOTH, $debug = false, $type = false, $arr_options = null) {
		
		if (is_array($msg)) {
			$msg = print_r($msg, true);
		}
		if (is_array($debug)) {
			$debug = print_r($debug, true);
		}
		$label = ($label ?: 'LOG');
		$type = ($type ?: 'attention');
		
		Log::addMsg($msg, $label, $suppress, $debug, $type, $arr_options);
	}
	
	function status($msg = '', $label = false, $header = false, $arr_options = null) {
		
		if ($msg === false) {
			
			$str = false;
		} else {
			
			if (is_array($msg)) {
				$msg = print_r($msg, true);
			}
			$label = ($label ?: 'UPDATE');
			$header = ($header ?: getLabel('lbl_status'));
						
			$str = '<ul><li><label></label><div>'.$header.'</div></li><li><label>'.$label.'</label><div>'.$msg.'</div></li></ul>';
		}
		
		if ($arr_options !== null && !is_array($arr_options)) {
			$arr_options = ['duration' => (int)$arr_options];
		} else {
			$arr_options = ($arr_options ?: []);
		}
		
		if (!isset($arr_options['identifier'])) {
			$arr_options['identifier'] = SiteStartEnvironment::getSessionId(true);
		}
		
		$arr_status = ['msg' => $str, 'msg_type' => 'status', 'msg_options' => $arr_options];
			
		Response::update($arr_status);
	}
	
	function clearStatus($identifier, $timeout = null) {
		
		status(false, false, false, ['clear' => ['identifier' => $identifier, 'timeout' => $timeout]]);
	}
	
	function getLabel($identifier, $type = 'L', $go_now = false) {
		
		return Labels::getLabel($identifier, $type, $go_now);
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
	
	function findFilePath($filename, $path = '') {
		
		static $arr_path_files = [];
		
		if (!isPath($path)) {
			return false;
		}

		if (isset($arr_path_files[$path])) {
			
			return ($arr_path_files[$path][$filename] ?? false);
		} else {
			
			$it_directory = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
			$it_files = new RecursiveIteratorIterator($it_directory, RecursiveIteratorIterator::LEAVES_ONLY); // LEAVES_ONLY, only files

			foreach ($it_files as $file) {
				
				$cur_path = $file->getPath();
				$cur_filename = $file->getFilename();
				
				$arr_path_files[$path][$cur_filename] = $cur_path;
			}
			
			return ($arr_path_files[$path][$filename] ?? false);
		}
	}
	
	function autoLoadClass($class) {
		
		$filename = $class.'.php';
		
		$arr_paths = [
			DIR_SITE.DIR_CLASSES,
			DIR_SITE.DIR_CMS.DIR_CLASSES,
			DIR_CORE.DIR_CLASSES,
			DIR_CORE.DIR_CMS.DIR_CLASSES
		];
		
		foreach ($arr_paths as $cur_path) {
			
			$path = findFilePath($filename, $cur_path);
			
			if (!$path) {
				continue;
			}
			
			require($path.'/'.$filename);
			
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
		
		$count = 0;
		
		while (true) {
			
			$alive = Mediator::checkState(); // Check connection
			
			if (!$alive) {
				
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
			
			usleep(($count < 10 ? 20000 : 100000)); // 100ms, first 10 loops 20ms
			$count++;
		}
				
		// Not aborted, continue
		SiteStartEnvironment::startSession();
	}
	
	function onUserPollContinuous($func_poll, $func_abort) { // Keep polling continuously, without sleep, but do keep track of checking state 
		
		SiteStartEnvironment::checkCookieSupport();

		SiteStartEnvironment::stopSession();
		
		$time = microtime(true);
		$count = 0;
		
		while (true) {
			
			$cur_time = microtime(true);
			
			if (($cur_time - $time) > ($count < 10 ? 0.2 : 0.1)) { // 100ms, first 10 loops 20ms
			
				$alive = Mediator::checkState(); // Check connection
			
				if (!$alive) {
					
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
				
				$time = $cur_time;
				$count++;
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
		
		$str_temporary = tempnam(($str_path ?: Settings::get('path_temporary')), ($is_directory ? '_' : '').($str_class ?: '1100CC'));
		
		if ($is_directory) {
			
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
	
	function generateSecret($length = 20, $char = 'unicode') {
		
		$ret = '';
		
		for ($i = 0; $i < $length; $i++) {
			if ($char == 'ascii') {
				$ret .= chr(mt_rand(33, 255)); // ASCII
			} else {
				$ret .= unichr(mt_rand(33, 1000)); // Unicode
			}
		}
		
		return $ret;
	}
	
	function unichr($u) {
		
		return mb_convert_encoding('&#'.intval($u).';', 'UTF-8', 'HTML-ENTITIES');
	}
	
	function generateRandomString($length, $char = false) {
		
		$pass = '';
		$char = ($char ? $char : "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ023456789");
		
		for ($i=0; $i <= $length; $i++) {
			
			$gen = rand(0, strlen($char));
			$single = substr($char, $gen, 1);
			$pass .= $single;
		}
		return $pass;
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

	function str2Name($str, $str_keep = false) {
		
		if (!$str) {
			return (string)$str;
		}
		
		return strtolower(preg_replace('/[^a-z0-9'.($str_keep ? preg_quote($str_keep, '/') : '').']/i', '', $str));
	}
	
	function str2Label($str, $str_keep = false) {
		
		if (!$str) {
			return (string)$str;
		}
		
		return strtolower(preg_replace('/[^a-z0-9-_'.($str_keep ? preg_quote($str_keep, '/') : '').']/i', '', str_replace(' ', '_', $str)));
	}
	
	function str2URL($str, $str_keep = false) {
		
		if (!$str) {
			return (string)$str;
		}
		
		return strtolower(preg_replace('/[^a-z0-9-_'.($str_keep ? preg_quote($str_keep, '/') : '').']/i', '', str_replace(' ', '-', $str)));
	}
	
	function str2Color($str, $code = 'hex') {
		
		if (!$str) {
			return false;
		}
		
		if ($code == 'hex') {
			return '#'.strtolower(substr(preg_replace('/[^a-f0-9]/i', '', $str), 0, 6));
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
		
		$num_length_test = strlen($str_test);
		
		return (strncmp($str, $str_test, $num_length_test) === 0);
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
	
	function parseRegularExpression($pattern, $flags, $template) {
		
		$pattern = trim($pattern);
		$flags = ($flags ? preg_replace('/[^imsxADU]*/', '', $flags) : '');
		$template = $template; // Can be empty to replace with an empty string
		
		if (!$pattern) {
			return false;
		}
		
		// Make sure the pattern is not erroneous
		try {
			preg_replace('/'.$pattern.'/'.$flags, $template, 'TEST');
		} catch (Exception $e) {
			$pattern = preg_quote($pattern, '/');
		}
		
		return ['pattern' => $pattern, 'flags' => $flags, 'template' => $template];
	}
	
	function strRegularExpression($str, $pattern, $flags, $template, $do_process_template = true) {
		
		if ($do_process_template) {
			$template = stripcslashes($template);
		}
		
		return preg_replace('/'.$pattern.'/'.$flags, $template, $str);		
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
	
	function arrUniqueValuesRecursive($arr_keys, $arr, &$arr_flat = []) {
		
		return arrValuesRecursive($arr_keys, $arr, $arr_flat, true);
	}
	
	function arrValuesRecursive($arr_keys, $arr, &$arr_flat = [], $do_unique = false) {
		
		if ($arr_keys !== false && !is_array($arr_keys)) {
			$arr_keys = [$arr_keys => true];
		}
	
		foreach ($arr as $k => $v) {
		
			if (!$v) {
				continue;
			}
			
			if ($arr_keys === false || isset($arr_keys[$k])) {
				if ($do_unique) {
					$arr_flat[$v] = $v;
				} else {
					$arr_flat[] = $v;
				}
			}
			
			if (is_array($v)) { // Recursive
				arrValuesRecursive($arr_keys, $v, $arr_flat, $do_unique);
			}
		}
		
		return $arr_flat;
	}
	
	function arrHasValuesRecursive($key, $arr_values, $arr) {
		
		if (!is_array($arr_values)) {
			$arr_values = [$arr_values => true];
		}
	
		foreach ($arr as $k => $v) {
			
			$is_array_v = is_array($v);
			
			if (($k === $key || $key === false) && !$is_array_v && isset($arr_values[$v])) {
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
	
	function arrHasKeysRecursive($arr_keys, $arr, $only_positive = false) {
		
		if (!is_array($arr_keys)) {
			$arr_keys = [$arr_keys => true];
		}
	
		foreach ($arr as $k => $v) {
			
			if (!empty($arr_keys[$k])) {
				if (!$only_positive) { // Any key (with or without value)
					return $k;
				} else if ($v) { // Only keys with a positive value
					return $k;
				}
			}
			
			if ($v && is_array($v)) { // Recursive
				
				$key_found = arrHasKeysRecursive($arr_keys, $v, $only_positive);
				
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
				
				if ($key == '0') {
					
					foreach ($arr_result as $k => $v) {
						$arr_collect[] = ['[]' => $v];
					}
				}
				
				foreach ($arr_result as $k => $v) {
					$arr_collect[] = [$key => $v];
				}
			} else {
				
				if ($key == '0') {
					$arr_collect[] = ['[]' => ''];
				}
				
				$arr_collect[] = [$key => ''];
			}
		}
		
		return $arr_collect;
	}
	
	function arrKsortRecursive($arr, $flag_sort = SORT_STRING) {
		
		 // $flag_sort = SORT_STRING, compare as strings by default, because array keys could be of mixed types

		if (is_array($arr)) {
			
			ksort($arr, $flag_sort);
			
			foreach ($arr as &$v) {
				$v = arrKsortRecursive($v, $flag_sort); // Recursive
			}
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
	
	function arrMerge(...$arrs) { // String keys will be overwitten
		
		if (!isset($arrs[1])) {
			$arrs = current($arrs);
		}
		
		return array_merge(...$arrs);
	}
	
	function arrMergeKeys(...$arrs) { // All keys will be overwitten
		
		if (!isset($arrs[1])) {
			$arrs = current($arrs);
		}
		
		$arr_collect = [];
		
		foreach ($arrs as $arr) {
			
			$arr_collect += $arr;
		}
		
		return $arr_collect;
	}
	
	function arrMergeValues(...$arrs) {  // Merge arrays: values will be overwitten
		
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
	
	function arrRearrangeParams($arr) { // $arr[param][0] => $arr[0][param]
		
		$arr_new = [];
		
		foreach($arr as $key => $all){
			foreach($all as $i => $val) {
				$arr_new[$i][$key] = $val;   
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
	
	function keyIsUncontested($key, $arr) {
		
		return (!array_key_exists($key, $arr) || $arr[$key]);
	}
	
	function doNotXMLParse($s) {
		
		if (!$_SERVER['PATH_VIRTUAL']) {
		
			$s = str_replace('<![CDATA[', '', $s); // Remove existing CDATA tags
			$s = str_replace(']]>', '', $s);
			
			return '<![CDATA['.$s.']]>';
		} else {
			return $s;
		}
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
			
			$format = new FormatHTML($body);
			
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
		public $msg = false;
		
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
