<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class Mediator {
	
	const SHUTDOWN_SILENT = 1;
	const SHUTDOWN_SOFT = 2;
	const SHUTDOWN_HARD = 4;
	
	const SHUTDOWN_INIT_UNDETERMINED = 8;
	const SHUTDOWN_INIT_SCRIPT = 16;
	const SHUTDOWN_INIT_SYSTEM = 32;
	
	protected static $shutdown = false;
	protected static $in_cleanup = false;
	protected static $memory = null;
	
	protected static $arr_events = [];
	protected static $arr_processes = [];
	protected static $arr_locks = [];
	protected static $arr_fallbacks = [];
	
	protected static $run_module = false;
	protected static $run_method = false;
	protected static $arr_run_options = false;
	
	protected static $timeout_lock_sleep = 0.25; // Seconds
	protected static $timeout_lock_notify = 60; // Seconds
	
	const LOCK_SEPARATOR = '::';
	const LOCK_POOL_EMPTY = '::::';
	const LOCK_POOL_NO_CODE = 'nocode';
	
	public static function attach($event, $id, $function) {
		
		$arr_event = explode('.', $event);
		$name = $arr_event[0];
		$space = ($arr_event[1] ?? 0);
		
		$s_arr =& self::$arr_events[$name];
		
		if ($id) {
			
			$s_arr[$space][$id] = $function;
		} else {
			
			if (!isset($s_arr[$space])) {
				$s_arr[$space] = [];
			}
			
			$id = array_push($s_arr[$space], $function);
			$id = $id - 1;
		}

		return $id;
	}
	
	public static function remove($event, $id = false) {
		
		$arr_event = explode('.', $event);
		$name = $arr_event[0];
		$space = ($arr_event[1] ?? 0);
		
		if ($id !== false) {

			self::$arr_events[$name][$space][$id] = false;
		} else {
			
			if ($space) {
				self::$arr_events[$name][$space] = false;
			} else {
				self::$arr_events[$name] = false;
			}
		}
	}
	
	public static function runListener($event, $id, $data = false) {
		
		$arr_event = explode('.', $event);
		$name = $arr_event[0];
		$space = ($arr_event[1] ?? 0);
		
		$function = self::$arr_events[$name][$space][$id];
					
		if (is_callable($function)) {
			
			if (self::inCleanup()) {
				
				try {
					$function($event, $id, $data);
				} catch (Exception $e) {
					Trouble::catchError($e);
				}
			} else {
				
				return $function($event, $id, $data);
			}
		} else {
			
			return false;
		}			
	}
	
	public static function runListeners($event, $data = false) {
		
		$arr_event = explode('.', $event);
		$name = $arr_event[0];
		$space = ($arr_event[1] ?? 0);
		
		if ($space) {
			
			if (isset(self::$arr_events[$name][$space])) {
				
				foreach (self::$arr_events[$name][$space] as $id => $function) {
					
					self::runListener($event, $id, $data);
				}
			}
		} else {
			
			if (isset(self::$arr_events[$name])) {
				
				foreach (self::$arr_events[$name] as $space => $arr_events) {
					
					foreach ($arr_events as $id => $function) {
					
						self::runListener($name.'.'.$space, $id, $data);
					}
				}
			}
		}
	}
			
	public static function attachLock($identifier, $code) {
		
		if (!$code) {
			$code = static::LOCK_POOL_NO_CODE;
		}
				
		$str_path = Settings::get('path_temporary').'lock_'.FileStore::cleanFilename($identifier);
		$str_path_pool = $str_path.'_pool';
		$num_time_self = (int)(microtime(true) * 1000); // Keep millisecond precision and as integer to preserve accuracy
		
		$file_pool = fopen($str_path_pool, 'c+');
		static::setFileLock($file_pool);
		
		list($code_initial, $num_time_initial, $num_time_done) = explode(static::LOCK_SEPARATOR, (fread($file_pool, 1024) ?: static::LOCK_POOL_EMPTY));
		
		// Try to get the lock
		
		$file = fopen($str_path, 'c');
		
		if (static::setFileLock($file, false)) { // Got lock

			fseek($file_pool, 0);
			ftruncate($file_pool, 0);
			fwrite($file_pool, $code.static::LOCK_SEPARATOR.((int)(microtime(true) * 1000)).static::LOCK_SEPARATOR.$num_time_initial);
			fflush($file_pool);
			
			self::$arr_locks[$identifier] = $file;
			
			static::removeFileLock($file_pool);

			return true;
		}
		
		static::removeFileLock($file);
		static::removeFileLock($file_pool);
		
		if ($code == static::LOCK_POOL_NO_CODE) {
			return false;
		}
		
		$do_pool = ($code_initial && $code_initial != $code); // Our code seems to be different (newer) than the current one, so join the pool to get a lock
		
		while (true) {

			usleep(static::$timeout_lock_sleep * 1000000); // Wait 0.25 seconds
			
			if ($do_pool) { // Add to the pool, exit when the pool date is initiated later and has finished (time_done), or when we get lock
				
				$file_pool = fopen($str_path_pool, 'c+');
				static::setFileLock($file_pool);
			
				list($code_pool, $num_time_pool, $num_time_done) = explode(static::LOCK_SEPARATOR, (fread($file_pool, 1024) ?: static::LOCK_POOL_EMPTY));
				
				if (!$code_pool || (int)$num_time_done >= $num_time_self) { // There has been a process initialised and finished after us.
					
					static::removeFileLock($file_pool);
				
					return false;
				}

				// Try to get the lock
				$file = fopen($str_path, 'c');
				
				if (static::setFileLock($file, false)) { // Got lock
					
					if ((int)$num_time_pool >= $num_time_self) { // There has been a process initialised and finished after us.
						
						static::removeFileLock($file);
						static::removeFileLock($file_pool);
						
						return false;
					}
					
					fseek($file_pool, 0);
					ftruncate($file_pool, 0);
					fwrite($file_pool, $code.static::LOCK_SEPARATOR.((int)(microtime(true) * 1000)).static::LOCK_SEPARATOR.$num_time_pool);
					fflush($file_pool);
					
					self::$arr_locks[$identifier] = $file;
					
					static::removeFileLock($file_pool);
			
					return true;
				}
				
				static::removeFileLock($file);
				static::removeFileLock($file_pool);
			} else {
				
				$file_pool = fopen($str_path_pool, 'r');
				static::setFileLock($file_pool);
				
				list($code_pool, $num_time_pool, $num_time_done) = explode(static::LOCK_SEPARATOR, (fread($file_pool, 1024) ?: static::LOCK_POOL_EMPTY));
			
				if (!$code_pool || $code_pool != $code_initial || $num_time_pool != $num_time_initial) { // The initial lock has changed
					
					static::removeFileLock($file_pool);
					
					return false;
				}
				
				$file = fopen($str_path, 'r');

				if (static::setFileLock($file, false)) { // Got lock, initial lock is obviously gone

					static::removeFileLock($file);
					static::removeFileLock($file_pool);
					
					return false;
				}
				
				static::removeFileLock($file);
				static::removeFileLock($file_pool);
			}
			
			$num_time_start = ($num_time_start ?? time());
			$num_time_now = time();
			$num_time_notify = ($num_time_notify ?? $num_time_now);
			
			if (($num_time_now - $num_time_notify) > static::$timeout_lock_notify) {
				
				msg('Lock status:'.EOL_1100CC
					.'	'.$identifier.' = '.($num_time_now - $num_time_start).' seconds.'
				, 'MEDIATOR', LOG_SYSTEM, 'Path lock: '.$str_path.EOL_1100CC.'Path pool: '.$str_path_pool);
				
				$num_time_notify = ($num_time_now - (($num_time_now - $num_time_notify) % static::$timeout_lock_notify));
			}
		}
	}
	
	public static function checkLock($identifier) {
		
		$str_path = Settings::get('path_temporary').'lock_'.FileStore::cleanFilename($identifier);
		
		$file = fopen($str_path, 'c');
		
		if (static::setFileLock($file, false)) { // Got lock
						
			self::$arr_locks[$identifier] = $file;
						
			return true;
		}
		
		return false;
	}
	
	public static function detachLock($identifier) {
		
		$str_path = Settings::get('path_temporary').'lock_'.FileStore::cleanFilename($identifier);
		$str_path_pool = $str_path.'_pool';
		
		// Clear pool file state
		$file_pool = fopen($str_path_pool, 'c');
		static::setFileLock($file_pool);
		
		ftruncate($file_pool, 0);
		
		static::removeFileLock($file_pool);
	}
	
	public static function removeLock($identifier) {
		
		$file = self::$arr_locks[$identifier];
		
		static::removeFileLock($file);
		
		unset(self::$arr_locks[$identifier]);
	}

	public static function attachFallback($str_module, $str_method, $arr_options = []) {
		
		$str_path = getPathTemporary(false, false, Settings::get('path_temporary').'fallback/');
		$num_time = (int)(microtime(true) * 1000); // Keep millisecond precision and as integer to preserve accuracy
		
		// Get the lock
		
		$file = fopen($str_path, 'w');
		
		if (!static::setFileLock($file, false)) { // Did not get lock, should not happen
			return false;
		}
		
		$str_options = value2JSON($arr_options);
		
		fwrite($file, $num_time.static::LOCK_SEPARATOR.$str_module.static::LOCK_SEPARATOR.$str_method.static::LOCK_SEPARATOR.$str_options);
		fflush($file);
		
		self::$arr_fallbacks[$str_path] = $file;
						
		return $str_path;
	}
	
	public static function removeFallback($str_path) {
		
		$file = self::$arr_fallbacks[$str_path];
		
		if (!$file) {
			return false;
		}
		
		static::removeFileLock($file);
		unlink($str_path);
		
		unset(self::$arr_fallbacks[$str_path]);
		
		return true;
	}
	
	public static function cleanFallbacks() {
		
		$str_directory = Settings::get('path_temporary').'fallback/';
		
		if (!isPath($str_directory)) {
			return;
		}
				
		$iterator_directory = new DirectoryIterator($str_directory);
		
		foreach ($iterator_directory as $file) {
			
			if (!$file->isFile()) {
				continue;
			}
			
			$str_path = $file->getPathname();
			$file = fopen($str_path, 'r');
		
			if (!static::setFileLock($file, false)) { // Did not get lock, great
				continue;
			}
			
			list($num_time, $str_module, $str_method, $str_options) = explode(static::LOCK_SEPARATOR, fread($file, 2048));
			
			static::removeFileLock($file);
			unlink($str_path);
						
			if (!method_exists($str_module, $str_method)) {
				continue;
			}
			
			$num_time = ($num_time / 1000);
			$arr_options = JSON2Value($str_options);
			
			$str_module::$str_method($arr_options, $num_time);
		}
	}
	
	public static function setFileLock($file, $do_wait = true) {
		
		return flock($file, ($do_wait ? LOCK_EX : (LOCK_EX | LOCK_NB)));
	}
	
	public static function removeFileLock($file) {
		
		flock($file, LOCK_UN);
		fclose($file);
	}
	
	public static function runAsync($str_module, $str_method, $arr_options = []) {
		
		$arr_signature = [
			SITE_NAME,
			SERVER_NAME_1100CC,
			SERVER_NAME_CUSTOM,
			SERVER_NAME_SUB,
			SERVER_SCHEME,
			STATE
		];
		
		if (SERVER_NAME_1100CC != SERVER_NAME_SITE_NAME) {
			$arr_signature[] = SERVER_NAME_SITE_NAME;
			$arr_signature[] = SERVER_NAME_MODIFIER;
		}
		
		$str_signature = arr2String($arr_signature, ';');
		
		$process = new Process("php -q ".DIR_ROOT_CORE.DIR_CMS."index.php '".$str_signature."' '".$str_module."' '".$str_method."'".($arr_options ? ' '.escapeshellarg(value2JSON($arr_options)) : ''));
		$process_id = $process->getPID();
		
		self::$arr_processes[$str_module][$str_method][$process_id] = $process_id;
		
		return $process_id;
	}
	
	public static function stopAsync($str_module, $str_method, $process_id = false) {

		$arr = ($process_id ? [$process_id] : (self::$arr_processes[$str_module][$str_method] ?? []));
		$arr_stopped = [];
		
		foreach ($arr as $cur_process_id) {
		
			$process = new Process();
			$process->setPID($cur_process_id);
			
			$arr_stopped[$cur_process_id] = $process->close();
		}
	
		return ($process_id ? current($arr_stopped) : $arr_stopped);
	}
	
	public static function runModuleMethod($str_module, $str_method, $arr_options = []) {
		
		self::$run_module = $str_module;
		self::$run_method = $str_method;
		self::$arr_run_options = $arr_options;
		
		timeLimit(false);
			
		$str_module::$str_method($arr_options);
	}
	
	public static function checkState() {
		
		if (SiteStartEnvironment::isProcess()) {
			
			if (function_exists('pcntl_signal_dispatch')) {
				
				pcntl_signal_dispatch();
			}
			
			return true;
		} else {
						
			Response::update();
		
			if (connection_aborted()) { // Check connection
				return false;
			} else {
				return true;
			}
		}
	}
	
	public static function allocateShutdownMemory() {

		static::$memory = str_repeat('*', 1024 * 1024); // 1MB
	}
	
	public static function setShutdown($shutdown) {
		
		static::$memory = null; // Release shutdown/cleanup memory
		
		self::$shutdown = $shutdown;
	}
	
	public static function getShutdown() {
		
		return self::$shutdown;
	}

	public static function setCleanup() {
		
		self::$in_cleanup = true;
	}
	
	public static function inCleanup() {
		
		return self::$in_cleanup;
	}
	
	public static function terminate($signal) {
		
		// Call Mediator::checkState() to listen for signals, or see declare(ticks=x)

		 switch ($signal) {
			 case SIGTERM:
			 case SIGALRM:
				 // handle shutdown task
				 msg('Received signal '.$signal.'. Closing process:'.PHP_EOL
					.'	'.SITE_NAME.' '.STATE.' '.self::$run_module.' '.self::$run_method.' '.value2JSON(self::$arr_run_options).'.',
				'SYSTEM');
				 exit;
				 break;
			 case SIGHUP:
				 // handle restart tasks
				 break;
			 case SIGUSR1:
				// Custom signals SIGUSR1, SIGUSR2, ...
				break;
			 default:
				 // handle all other signals
		 }
	}
}

function shutdown() {
		
	// Closing up
	
	if (!Mediator::getShutdown()) {

		Mediator::setShutdown(Mediator::SHUTDOWN_SILENT);
		
		// Check for core error
		
		$arr_error = error_get_last();
		
		if ($arr_error) {
			Trouble::system($arr_error['type'], $arr_error['message'], $arr_error['file'], $arr_error['line']);
		}
		
		// Reset connection to default and clean it
		try {
			
			DB::setConnection();
			DB::rollbackTransaction(false);
		} catch (Exception $e) { }
	}
	
	// Cleanup
			
	Mediator::setCleanup();

	Mediator::runListeners('cleanup');

	Log::addToDB();
}

register_shutdown_function('shutdown');

Mediator::allocateShutdownMemory();

if (function_exists('pcntl_signal')) {
	
	$func_terminate = function($signal) {
		
		Mediator::terminate($signal);
	};
	
	pcntl_signal(SIGTERM, $func_terminate);
	pcntl_signal(SIGALRM, $func_terminate);
	pcntl_signal(SIGHUP, $func_terminate);
	pcntl_signal(SIGUSR1, $func_terminate);
}
