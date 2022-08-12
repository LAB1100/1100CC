<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
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
	
	public static $in_cleanup = false;

	protected static $shutdown = false;
	
	protected static $arr_events = [];
	protected static $arr_processes = [];
	protected static $arr_locks = [];
	
	protected static $run_module = false;
	protected static $run_method = false;
	protected static $arr_run_options = false;
	
	protected static $timeout_lock_sleep = 0.25; // Seconds
	protected static $timeout_lock_notify = 60; // Seconds
	
	const LOCK_POOL_SEPARATOR = '::';
	const LOCK_POOL_EMPTY = '::::';
	
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
			
			if (self::$in_cleanup) {
				
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
			
	public static function setLock($identifier, $code) {
		
		$path = Settings::get('path_temporary').'lock_'.FileStore::cleanFilename($identifier);
		$path_pool = $path.'_pool';
		$time_self = microtime(true);
		
		$file_pool = fopen($path_pool, 'c+');
		flock($file_pool, LOCK_EX);
		
		list($code_initial, $time_initial, $time_done) = explode(static::LOCK_POOL_SEPARATOR, (fread($file_pool, 1024) ?: static::LOCK_POOL_EMPTY));
		
		// Try to get the lock
		
		$file = fopen($path, 'c');
		
		if (flock($file, LOCK_EX | LOCK_NB)) { // Got lock

			fseek($file_pool, 0);
			ftruncate($file_pool, 0);
			fwrite($file_pool, $code.'::'.microtime(true).'::'.$time_initial);
			fflush($file_pool);
			
			self::$arr_locks[$identifier] = $file;
			
			flock($file_pool, LOCK_UN); // Remove pool lock
			fclose($file_pool);

			return true;
		}
		
		flock($file, LOCK_UN);
		fclose($file);
		
		flock($file_pool, LOCK_UN); // Remove pool lock
		fclose($file_pool);

		$do_pool = ($code_initial && $code_initial != $code); // Our code seems to be different (newer) than the current one, so join the pool to get a lock
		
		while (true) {

			usleep(static::$timeout_lock_sleep * 1000000); // Wait 0.25 seconds
			
			if ($do_pool) { // Add to the pool, exit when the pool date is initiated later and has finished (time_done), or when we get lock
				
				$file_pool = fopen($path_pool, 'c+');
				flock($file_pool, LOCK_EX);
			
				list($code_pool, $time_pool, $time_done) = explode(static::LOCK_POOL_SEPARATOR, (fread($file_pool, 1024) ?: static::LOCK_POOL_EMPTY));
				
				if (!$code_pool || (float)$time_done >= $time_self) { // There has been a process initialised and finished after us.
					
					flock($file_pool, LOCK_UN); // Remove pool lock
					fclose($file_pool);
				
					return false;
				}

				// Try to get the lock
				$file = fopen($path, 'c');
				
				if (flock($file, LOCK_EX | LOCK_NB)) { // Got lock
					
					fseek($file_pool, 0);
					ftruncate($file_pool, 0);
					fwrite($file_pool, $code.'::'.microtime(true).'::'.$time_pool);
					fflush($file_pool);
					
					self::$arr_locks[$identifier] = $file;
					
					flock($file_pool, LOCK_UN); // Remove pool lock
					fclose($file_pool);
			
					return true;
				}
				
				flock($file, LOCK_UN);
				fclose($file);
				
				flock($file_pool, LOCK_UN); // Remove pool lock
				fclose($file_pool);
			} else {
				
				$file_pool = fopen($path_pool, 'r');
				flock($file_pool, LOCK_EX);
				
				list($code_pool, $time_pool, $time_done) = explode(static::LOCK_POOL_SEPARATOR, (fread($file_pool, 1024) ?: static::LOCK_POOL_EMPTY));
			
				if (!$code_pool || $code_pool != $code_initial || $time_pool != $time_initial) { // The initial lock has changed
					
					flock($file_pool, LOCK_UN); // Remove pool lock
					fclose($file_pool);
					
					return false;
				}
				
				$file = fopen($path, 'r');

				if (flock($file, LOCK_EX | LOCK_NB)) { // Got lock, initial lock is obviously gone

					flock($file, LOCK_UN);
					fclose($file);
					
					flock($file_pool, LOCK_UN); // Remove pool lock
					fclose($file_pool);
					
					return false;
				}
				
				flock($file, LOCK_UN);
				fclose($file);
				
				flock($file_pool, LOCK_UN); // Remove pool lock
				fclose($file_pool);
			}
			
			$time_now = microtime(true);
			$time_notify = ($time_notify ?? $time_now);
			
			if (($time_now - $time_notify) > static::$timeout_lock_notify) {
				
				msg('Lock status:'.EOL_1100CC
					.'	'.$identifier.' = '.(int)($time_now - $time_self).' seconds.'
				, 'MEDIATOR', LOG_SYSTEM, 'Path lock: '.$path.EOL_1100CC.'Path pool: '.$path_pool);
				
				$time_notify = ($time_now - (($time_now - $time_notify) % static::$timeout_lock_notify));
			}
		}
	}
	
	public static function checkLock($identifier) {
		
		$path = Settings::get('path_temporary').'lock_'.FileStore::cleanFilename($identifier);
		
		$file = fopen($path, 'c');
		
		if (flock($file, LOCK_EX | LOCK_NB)) { // Got lock
						
			self::$arr_locks[$identifier] = $file;
						
			return true;
		}
		
		return false;
	}
	
	public static function unsetLock($identifier) {
		
		$path = Settings::get('path_temporary').'lock_'.FileStore::cleanFilename($identifier);
		$path_pool = $path.'_pool';
		
		// Clear pool file state
		$file_pool = fopen($path_pool, 'c');
		flock($file_pool, LOCK_EX);
		
		ftruncate($file_pool, 0);
		
		flock($file_pool, LOCK_UN);
		fclose($file_pool);
	}
	
	public static function removeLock($identifier) {
		
		$file = self::$arr_locks[$identifier];
		
		flock($file, LOCK_UN);
		fclose($file);
		
		unset(self::$arr_locks[$identifier]);
	}
	
	public static function runAsync($module, $method, $arr_options = []) {
		
		$state = STATE.';'.(SiteStartVars::useHTTPS(false) ? 'https' : 'http');
		
		$process = new Process("php -q ".DIR_ROOT_CORE.DIR_CMS."index.php '".SITE_NAME."' '".SERVER_NAME_1100CC."' '".SERVER_NAME_CUSTOM."' '".SERVER_NAME_SUB."' '".$state."' '".$module."' '".$method."' '".($arr_options ? value2JSON($arr_options) : '')."'");
		$process_id = $process->getPID();
		
		self::$arr_processes[$module][$method][$process_id] = $process_id;
		
		return $process_id;
	}
	
	public static function stopAsync($module, $method, $process_id = false) {

		$arr = ($process_id ? [$process_id] : (self::$arr_processes[$module][$method] ?: []));
		$arr_stopped = [];
		
		foreach ($arr as $cur_process_id) {
		
			$process = new Process();
			$process->setPID($cur_process_id);
			
			$arr_stopped[$cur_process_id] = $process->close();
		}
	
		return ($process_id ? current($arr_stopped) : $arr_stopped);
	}
	
	public static function runModuleMethod($module, $method, $arr_options = []) {
		
		self::$run_module = $module;
		self::$run_method = $method;
		self::$arr_run_options = $arr_options;
		
		timeLimit(0);
			
		$module::$method($arr_options);
	}
	
	public static function checkState() {
		
		if (SiteStartVars::isProcess()) {
			
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
	
	public static function setShutdown($shutdown) {
		
		self::$shutdown = $shutdown;
	}
	
	public static function getShutdown() {
		
		return self::$shutdown;
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
			
	Mediator::$in_cleanup = true;

	Mediator::runListeners('cleanup');

	Log::addToDB();
}

register_shutdown_function('shutdown');

if (function_exists('pcntl_signal')) {
	
	$func_terminate = function($signal) {
		
		Mediator::terminate($signal);
	};
	
	pcntl_signal(SIGTERM, $func_terminate);
	pcntl_signal(SIGALRM, $func_terminate);
	pcntl_signal(SIGHUP, $func_terminate);
	pcntl_signal(SIGUSR1, $func_terminate);
}
