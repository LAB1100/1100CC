<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
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
	
	require('operations/Trouble.php');
	require('operations/Log.php');
	require('operations/Response.php');
	require('operations/DB.php');
	require('operations/Mediator.php');
	require('operations/Settings.php');
	require('operations/Labels.php');
	require('operations/SiteStartVars.php');
	require('operations/SiteEndVars.php');
	
	function error($msg = '', $code = 0, $suppress = LOG_BOTH, $debug = false, $exception = null) {
		
		if (is_array($msg)) {
			$msg = print_r($msg, true);
		}
		
		Trouble::fling($msg, $code, $suppress, $debug, $exception);
	}
	
	function msg($msg = '', $label = false, $suppress = LOG_BOTH, $debug = false, $type = false, $arr_options = null) {
		
		if (is_array($msg)) {
			$msg = print_r($msg, true);
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
						
			$str = '<ul><li><label></label><span>'.$header.'</span></li><li><label>'.$label.'</label><span>'.$msg.'</span></li></ul>';
		}
		
		if ($arr_options !== null && !is_array($arr_options)) {
			$arr_options = ['duration' => (int)$arr_options];
		} else {
			$arr_options = ($arr_options ?: []);
		}
		
		if ($arr_options['identifier'] === null) {
			$arr_options['identifier'] = SiteStartVars::getSessionId(true);
		}
		
		$arr_status = ['msg' => $str, 'msg_type' => 'status', 'msg_options' => $arr_options];
			
		Response::update($arr_status);
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
							$arr_links[$class] = 1;
						} else {
							
							$filename = $file->getFilename();
							preg_match('/^[0-9]*-(.*)\.php/', $filename, $match);
							$class = $match[1];
							
							if (($directory_type != 'catalog' && !$arr_modules[$class]) || ($directory_type == 'catalog' && $arr_links[$class])) {
								
								$path_file = $file->getPath().'/';
								$arr_modules[$class] = ['file' => $filename, 'path' => $path_file, 'time' => $file->getMTime()];
							}
						}
					}
				}
			}
		}
		
		uasort($arr_modules, function($a, $b) {
			return $a['file']>$b['file'];
		});
		
		spl_autoload_register($autoload_abstract, true, true);
		
		foreach ($arr_modules as $class => $arr) {
			require($arr['path'].$arr['file']);
		}
		
		spl_autoload_unregister($autoload_abstract);
		
		return $arr_modules;
	}
	
	function getModuleConfiguration($method, $call = true, $level = false, $module = false) {
		
		$arr = [];
		
		if ($module) {

			if (method_exists($module, $method)) {
				$arr = ($call ? $module::$method() : $method);
			}
		} else {
			
			switch ($level) {
				case DIR_HOME:
					$arr_modules = (IS_CMS ? getModules(DIR_HOME) : SiteStartVars::$modules);
					break;
				case DIR_CMS:
					$arr_modules = (IS_CMS ? SiteStartVars::$modules : SiteStartVars::$cms_modules);
					break;
				default:
					$arr_modules = SiteStartVars::$modules;
			}

			foreach ($arr_modules as $module => $value) {
				
				if (method_exists($module, $method)) {
					$arr[$module] = ($call ? $module::$method() : $method);
				}
			}
		}
		
		return $arr;
	}
	
	function findFilePath($filename, $path = '') {
		
		static $arr_path_files = [];
		
		if (!isPath($path)) {
			return false;
		}

		if ($arr_path_files[$path]) {
			
			return ($arr_path_files[$path][$filename] ?: false);
		} else {
			
			$it_directory = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
			$it_files = new RecursiveIteratorIterator($it_directory, RecursiveIteratorIterator::LEAVES_ONLY); // LEAVES_ONLY, only files

			foreach ($it_files as $file) {
				
				$cur_path = $file->getPath();
				$cur_filename = $file->getFilename();
				
				$arr_path_files[$path][$cur_filename] = $cur_path;
			}
			
			return ($arr_path_files[$path][$filename] ?: false);
		}
	}
	
	function __autoload($class) {
		
		$filename = $class.'.php';
		
		$arr_paths = [
			DIR_SITE.DIR_CLASSES,
			DIR_SITE.DIR_CMS.DIR_CLASSES,
			DIR_CLASSES,
			DIR_CMS.DIR_CLASSES
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
	
	spl_autoload_register('__autoload');
	
	function getIcon($id = '') {
		
		static $arr_icons = [];

		if ($arr_icons[$id] === null) {
			
			$path = DIR_CMS.DIR_CSS.'images/icons/'.$id.'.svg';
			$path = (isPath(DIR_ROOT_SITE.$path) ? DIR_ROOT_SITE.$path : (isPath(DIR_ROOT_CORE.$path) ? DIR_ROOT_CORE.$path : false));
			
			$svg = '';
			
			if ($path) {
				$svg = file_get_contents($path);
				$svg = str_replace(["\n", "\t"], ['', ' '], $svg);
				$svg = trim($svg);
			}
			
			$arr_icons[$id] = $svg;
		}
		
		return $arr_icons[$id];
	}
	
	function memoryBoost($amount = 1024, $add = false) {
		
		if ($add) {
			
			$str = trim(ini_get('memory_limit'));
			$char = strtolower($str[strlen($str)-1]);
			$nr = (int)$str;
			
			// To Bytes
			switch($char) {
				case 'g':
					$nr *= 1024;
				case 'm':
					$nr *= 1024;
				case 'k':
					$nr *= 1024;
			}
			
			$amount += ($nr / 1024 / 1024); // To MegaBytes
		}
			
		ini_set('memory_limit', $amount.'M');
	}
	
	function timeLimit($time = 60) {
			
		set_time_limit($time);
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
		
		return mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
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
				
	function roundBetter($number, $precision = 0, $mode = PHP_ROUND_HALF_UP, $direction = NULL) {
		
		if (!isset($direction) || is_null($direction)) {
			return round($number, $precision, $mode);
		}
	   
		else {
			$factor = pow(10, -1 * $precision);

			return strtolower(substr($direction, 0, 1)) == 'd'
				? floor($number / $factor) * $factor
				: ceil($number / $factor) * $factor;
		}
	}
	function roundBetterUp($number, $precision = 0, $mode = PHP_ROUND_HALF_UP) {
			return roundBetter($number, $precision, $mode, 'up');
	}
	function roundBetterDown($number, $precision = 0, $mode = PHP_ROUND_HALF_UP) {
			return roundBetter($number, $precision, $mode, 'down');
	}
			
	function bytes2String($bytes) {
		$ext = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
		$count = 0;
		for(; $bytes > 1024; $count++)
			$bytes /= 1024;
		return round($bytes,2)." ".$ext[$count];
	}
	
	function nr2Price($num) {
		return str_replace(".", ",", sprintf("%01.2f", $num));
	}
	
	function nr2String($nr, $decimals = 0) {
		return number_format($nr, $decimals, '.', ' '); // U+205F - MEDIUM MATHEMATICAL SPACE
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
		return memory_get_usage() - $memory_start;
	}
	
	function read($file) {
	
		return file_get_contents($file);
	}
	
	function ip2Hex($ip) {
	
		$ip = explode('.', $ip);
		for ($i = 0; $i < count($ip); $i++) {
			$hex .= str_pad(dechex($ip[$i]), 2, '0', STR_PAD_LEFT);
		}
		return $hex;
	}
	
	function hex2Ip($hex) {
		
		for ($i = 0; $i < strlen($hex)-1; $i += 2) {
			$ip[] = hexdec($hex[$i].$hex[$i+1]);
		}
		return implode(".", $ip);
	}
	
	function filename2Name($str) {
		$info = pathinfo($str);
		$name = basename($str, '.'.$info['extension']);
		return $name;
	}
	
	function xmlspecialchars($text) {
		return str_replace('&#039;', '&apos;', htmlspecialchars($text, ENT_QUOTES));
	}
	
	function str2Name($str) {
		return strtolower(preg_replace("/[^a-z0-9]/i", '', $str));
	}
	
	function str2Label($str) {
		return strtolower(preg_replace("/[^a-z0-9-_]/i", '', str_replace(' ', '_', $str)));
	}
	
	function str2URL($str) {
		return strtolower(preg_replace("/[^a-z0-9-_]/i", '', str_replace(' ', '-', $str)));
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
	
	function wordWrapMB($string, $width = 75, $break = "\n", $cut = true) {
	
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
	
	function getMaxUploadSize() {

		return min((int)(ini_get('upload_max_filesize')), (int)(ini_get('post_max_size')), (int)(ini_get('memory_limit')));
	}

	function onUserPoll($func_poll, $func_abort) {
		
		SiteStartVars::checkCookieSupport();
						
		SiteStartVars::stopSession();
		
		$count = 0;
		
		while (true) {
			
			$alive = Mediator::checkState(); // Check connection
			
			if (!$alive) {
				
				$func_abort();
				exit;
			} else { // Check if the session is still current (more foolproof)
				
				// Update session variables
				SiteStartVars::startSession();
				SiteStartVars::stopSession();
				
				// Check if session has loaded elsewhere
				if ($_SESSION['session'] != SiteStartVars::$session) {
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
		SiteStartVars::startSession();
	}
	
	function onUserPollContinuous($func_poll, $func_abort) {
		
		SiteStartVars::checkCookieSupport();

		SiteStartVars::stopSession();
		
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
					SiteStartVars::startSession();
					SiteStartVars::stopSession();
					
					// Check if session has loaded elsewhere
					if ($_SESSION['session'] != SiteStartVars::$session) {
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
		SiteStartVars::startSession();
	}
	
	function isPath($path) {
	
		return ($path ? file_exists($path) : false);
	}
	
	function parseValue($value, $what) {
		
		switch ($what) {
			
			case 'int':
				$value = (int)$value;
				break;
			case 'trim':
				if ($value !== null) {
					$value = trim($value, " \x00..\x1F\x7F"); // Also remove control characters
				}
				break;
			default:
				$value = $what($value);
		}
		
		return $value;
	}
	
	function arrParseRecursive($arr, $what = 'int', $arr_keys = null, $keys_include = true) {
		
		if (!is_array($arr)) {
			return parseValue($arr, $what);
		}
		
		if ($arr_keys !== null && !is_array($arr_keys)) {
			$arr_keys = [$arr_keys => true];
		}
		
		foreach ($arr as $key => &$value) {
			
			if ($arr_keys !== null) {
				if ($keys_include == true && !$arr_keys[$key] || $keys_include == false && $arr_keys[$key]) {
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
	
	function arrValuesRecursive($key, $arr, &$arr_flat = []) {
	
		foreach ($arr as $k => $v) {
		
			if (!$v) {
				continue;
			}
			
			if ($k === $key || $key === false) {
				$arr_flat[] = $v;
			}
			
			if (is_array($v)) { // Recursive
				arrValuesRecursive($key, $v, $arr_flat);
			}
		}
		
		return $arr_flat;
	}
	
	function arrHasValuesRecursive($key, $arr_values, $arr) {
		
		if (!is_array($arr_values)) {
			$arr_values = [$arr_values => true];
		}
	
		foreach ($arr as $k => $v) {
			
			if (($k === $key || $key === false) && $arr_values[$v]) {
				return $v;
			}
			
			if ($v && is_array($v)) { // Recursive

				$value_found = arrHasValuesRecursive($key, $arr_values, $v);
				
				if ($value_found !== false) {
					return $value_found;
				}
			}
		}
		
		return false;
	}
	
	function arrHasKeysRecursive($arr_keys, $arr, $only_positive = false) {
		
		if (!is_array($arr_keys)) {
			$arr_keys = [$arr_keys => true];
		}
	
		foreach ($arr as $k => $v) {
			
			if ($arr_keys[$k]) {
				if (!$only_positive) { // Any key (with or without value)
					return $k;
				} else if ($v) { // Only keys with a positive value
					return $k;
				}
			}
			
			if ($v && is_array($v)) { // Recursive
				
				$key_found = arrHasKeysRecursive($arr_keys, $v, $only_positive);
				
				if ($key_found !== false) {
					return $key_found;
				}
			}
		}
		
		return false;
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
	
	function arrKsortRecursive($arr) {

		if (is_array($arr)) {
			
			ksort($arr);
			
			foreach ($arr as &$v) {
				$v = arrKsortRecursive($v); // recursive
			}
		}
		
		return $arr;
	}
	
	function arrInsert(&$arr, $pos, $arr_insert, $before = false) {
		
		if (is_int($pos)) {
			
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
	
	function arrMergeValues($arrs) {
		
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
		
		return '<time class="date"><span>'.implode('</span><span>', explode(',', date('d,M,Y', (is_int($date) ? $date : strtotime($date))))).'</span></time>';
	}
	
	function createTime($date) {
		
		return '<time class="time"><span>'.implode('</span><span>', explode(',', date('H,:,i', (is_int($date) ? $date : strtotime($date))))).'</span></time>';
	}
	
	function parseBody($body, $arr_options = []) {
	
		// $arr_options = array("extract" => number of paragraphs, "append" => string, "function" => function);
	
		if (!$body) {
			return;
		}
		
		$body = Labels::parseTextVariables($body);
		
		$label = Response::addParseDelay($body, function ($body) use ($arr_options) {
				
			if (!$body) {
				return;
			}
		
			$body = FormatBBCode::parse($body);
			
			$format = new FormatHTML($body);
			
			if ($arr_options['extract']) {
				$format->extractParagraphs($arr_options['extract']);
				if ($arr_options['append'] && $format->count_elements_removed) {
					$format->addToLastParagraph($arr_options['append']);
				}
			}
			$format->cacheImages();
			
			$body = $format->getHTML();
			
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
			
			return self::$arr_cache[static::class][$key];
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
