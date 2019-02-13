<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class Mediator {
		
	public static $shutdown = false;
	public static $in_cleanup = false;
	
	private static $arr_events = [];
	private static $arr_processes = [];
	private static $arr_locks = [];
	
	private static $run_module = false;
	private static $run_method = false;
	private static $arr_run_options = false;
	
	public static function attach($event, $id, $function) {
		
		list($name, $space) = explode('.', $event);
		$space = ($space ?: 0);
		
		$s_arr =& self::$arr_events[$name];
		
		if ($id) {
			
			$s_arr[$space][$id] = $function;
		} else {
			
			if (!$s_arr[$space]) {
				$s_arr[$space] = [];
			}
			
			$id = array_push($s_arr[$space], $function);
			$id = $id - 1;
		}

		return $id;
	}
	
	public static function remove($event, $id = false) {
		
		list($name, $space) = explode('.', $event);
		$space = ($space ?: 0);
		
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
		
		list($name, $space) = explode('.', $event);
		$space = ($space ?: 0);
		
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
		
		list($name, $space) = explode('.', $event);
		$space = ($space ?: 0);
		
		if ($space) {
			
			foreach ((array)self::$arr_events[$name][$space] as $id => $function) {
				
				self::runListener($event, $id, $data);
			}
		} else {
			
			foreach ((array)self::$arr_events[$name] as $space => $arr_events) {
				
				foreach ($arr_events as $id => $function) {
				
					self::runListener($name.'.'.$space, $id, $data);
				}
			}
		}
	}
	
	public static function lock($identifier) {
		
		$path = Settings::get('path_temporary').'lock_'.FileStore::cleanFilename($identifier);
		
		$file = fopen($path, 'c');
		
		if (flock($file, LOCK_EX | LOCK_NB)) {
			
			self::$arr_locks[$identifier] = $file;
			
			return true;
		}
		
		return false;
	}
		
	public static function setLock($identifier, $code) {
		
		$path = Settings::get('path_temporary').'lock_'.FileStore::cleanFilename($identifier);
		$path_queue = $path.'_queue';
				
		// Update lock queue with latest code
		
		$file_queue = fopen($path_queue, 'w');
		flock($file_queue, LOCK_EX);
		fwrite($file_queue, $code);

		// Try to get the lock
		
		$file = fopen($path, 'c');
		
		if (flock($file, LOCK_EX | LOCK_NB)) { // Got lock
			
			ftruncate($file, 0);
			fwrite($file, $code);
			
			self::$arr_locks[$identifier] = $file;
			
			// Remove queue lock
			flock($file_queue, LOCK_UN);
			fclose($file_queue);
			
			return true;
		}
		
		fclose($file);
	
		// Need to wait for an active lock
			
		$file = fopen($path, 'r');
		$cur_code = fread($file, 1024);
		fclose($file);
		
		// Remove queue lock
		flock($file_queue, LOCK_UN);
		fclose($file_queue);

		$do_queue = ($cur_code != $code); // Our code seems to be different (newer) than the current one, so queue to get a lock
		
		while (true) {
			
			usleep(250000); // Wait 0.25 seconds
			
			$file_queue = fopen($path_queue, 'r');
			flock($file_queue, LOCK_SH);
							
			if ($do_queue) { // Queue until lock is released to attempt to get our own lock
				
				$queue_code = fread($file_queue, 1024);
				
				if ($queue_code != $code) { // Check if our queue still reigns authority because a newer thread might have swooped in with a newer code
				
					$do_queue = false; // No need to queue for authority anymore, we are outdated
				} else {
					
					// Try to get the lock
					$file = fopen($path, 'c');
				
					if (flock($file, LOCK_EX | LOCK_NB)) { // Got lock
						
						ftruncate($file, 0);
						fwrite($file, $code);
						
						self::$arr_locks[$identifier] = $file;
						
						// Remove queue lock
						flock($file_queue, LOCK_UN);
						fclose($file_queue);
			
						return true;
					}
					
					fclose($file);
				}
			}
			
			if (!$do_queue) {
			
				$file = fopen($path, 'r');
				$cur_code = fread($file, 1024);
				fclose($file);
				
				if ($cur_code != $code) { // The active lock is gone
					
					// Remove queue lock
					flock($file_queue, LOCK_UN);
					fclose($file_queue);
				
					return false;
				}
			}
			
			// Remove queue lock
			flock($file_queue, LOCK_UN);
			fclose($file_queue);
		}
	}
	
	public static function checkLock($identifier) {
		
		$path = Settings::get('path_temporary').'lock_'.FileStore::cleanFilename($identifier);
		
		$file = fopen($path, 'c');
		
		if (flock($file, LOCK_EX | LOCK_NB)) { // Got lock
			
			ftruncate($file, 0);
			
			self::$arr_locks[$identifier] = $file;
						
			return true;
		}
		
		return false;
	}
	
	public static function unsetLock($identifier) {
		
		$path = Settings::get('path_temporary').'lock_'.FileStore::cleanFilename($identifier);
		$path_queue = $path.'_queue';
		
		// Update lock queue with latest code and instance
		$file_queue = fopen($path_queue, 'w');
		flock($file_queue, LOCK_EX);
		
		ftruncate($file_queue, 0);
		
		flock($file_queue, LOCK_UN);
		fclose($file_queue);
	}
	
	public static function removeLock($identifier) {
		
		$file = self::$arr_locks[$identifier];
		
		ftruncate($file, 0);
		
		flock($file, LOCK_UN);
		fclose($file);
	}
	
	public static function runAsync($module, $method, $arr_options = []) {
		
		$state = STATE.';'.(SiteStartVars::useHTTPS(false) ? 'https' : 'http');
		
		$process = new Process("php -q ".DIR_ROOT_CORE.DIR_CMS."index.php '".SITE_NAME."' '".SERVER_NAME_1100CC."' '".SERVER_NAME_CUSTOM."' '".SERVER_NAME_SUB."' '".$state."' '".$module."' '".$method."' '".($arr_options ? json_encode($arr_options) : '')."'");
		$process_id = $process->getPid();
		
		self::$arr_processes[$module][$method][$process_id] = $process_id;
		
		return $process_id;
	}
	
	public static function stopAsync($module, $method, $process_id = false) {

		$arr = ($process_id ? [$process_id] : (self::$arr_processes[$module][$method] ?: []));
		$arr_stopped = [];
		
		foreach ($arr as $cur_process_id) {
		
			$process = new Process();
			$process->setPid($cur_process_id);
			
			$arr_stopped[$cur_process_id] = $process->stop();
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
	
	public static function terminate($signal) {
		
		// Call Mediator::checkState() to listen for signals, or see declare(ticks=x)

		 switch ($signal) {
			 case SIGTERM:
			 case SIGALRM:
				 // handle shutdown task
				 msg('Received signal '.$signal.'. Closing process:'.PHP_EOL
					.'	'.SITE_NAME.' '.STATE.' '.self::$run_module.' '.self::$run_method.' '.json_encode(self::$arr_run_options).'.',
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
	
	if (!Mediator::$shutdown) {

		Mediator::$shutdown = 'soft';
		
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
