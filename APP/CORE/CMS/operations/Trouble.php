<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class RealTrouble extends Exception {
	
	public function setMessage($str) {
		
		$this->message = $str;
	}
}
class RealTroubleThrown extends RealTrouble {
		
}
class RealTroubleDB extends RealTrouble {
		
}

class Trouble {

	private static $arr_trouble = [

		TROUBLE_ERROR => [['STATUS', 'error'], 'alert', 'RealTroubleThrown'], // Default self-thrown error
		TROUBLE_FATAL => [['FATAL', 'server_error'], 'alert'], // Fatal code-base error
		TROUBLE_WARNING => [['WARNING', 'warning'], 'alert'], // Runtime error
		TROUBLE_NOTICE => [['NOTICE', 'notice'], 'attention'], // Runtime notice
		TROUBLE_UNKNOWN => [['UNKNOWN', 'unknown'], 'attention'], // Unknown notice
		TROUBLE_DATABASE => [['DATABASE', 'server_error'], 'alert', 'RealTroubleDB'], // Database error

		TROUBLE_ACCESS_DENIED => [['REQUEST', 'access_denied'], 'alert', 'RealTroubleThrown'],
		TROUBLE_INVALID_REQUEST => [['REQUEST', 'invalid_request'], 'alert', 'RealTroubleThrown'],
		TROUBLE_REQUEST_LIMIT => [['REQUEST', 'request_limit'], 'alert', 'RealTroubleThrown'],
		TROUBLE_UNAUTHORIZED_CLIENT => [['REQUEST', 'unauthorized_client'], 'alert', 'RealTroubleThrown']
	];
		
	public static function fling($msg = '', $code = TROUBLE_ERROR, $suppress = LOG_BOTH, $debug = false, $exception = null) {

		switch ($code) {
			case TROUBLE_NOTICE:
			case TROUBLE_UNKNOWN:
				
				// Create notice/message
				
				$e = self::create($msg, $code, $suppress, $debug, $exception);
				
				$suppress = ($suppress ?: (STATE == STATE_PRODUCTION && (!DB::isActive() || !getLabel('show_system_errors', 'D', true)) ? LOG_SYSTEM : LOG_BOTH));
				$msg = self::strMsg($e);
				$debug = self::strDebug($e);
				
				msg($msg, self::label($code), $suppress, $debug, self::type($code));
				break;
			default:
			
				// Create exception
			
				throw self::create($msg, $code, $suppress, $debug, $exception);
				break;
		}
	}
	
	public static function create($msg = '', $code = TROUBLE_ERROR, $suppress = LOG_BOTH, $debug = false, $exception = null) {
		
		$msg = ($msg ?? '');
		$code = ($suppress ? $code + ($suppress * 1000) : $code); // Combine suppression parameter with error code

		if ($debug) {
			$msg = $msg.PHP_EOL.self::strMsgSeparator().PHP_EOL.$debug;
		}
		
		$class = (self::$arr_trouble[$code][2] ?? 'RealTrouble');
		
		return new $class($msg, $code, $exception);
	}

	public static function label($code) {
		
		return (isset(self::$arr_trouble[$code]) ? self::$arr_trouble[$code][0] : self::$arr_trouble[0][0]);
	}
	
	public static function type($code) {
		
		return (isset(self::$arr_trouble[$code]) ? self::$arr_trouble[$code][1] : self::$arr_trouble[0][1]);
	}
	
	public static function core($code, $msg, $file, $line, $exception = null) {
	
		$msg_system = $msg.'. Error on line '.$line.' in file '.$file;
		
		switch ($code) {
			case E_WARNING:
			
				self::fling($msg_system, TROUBLE_WARNING, LOG_BOTH, false, $exception);
				break;
			case E_NOTICE:
			
				self::fling($msg_system, TROUBLE_NOTICE, LOG_BOTH, false, $exception);
				break;
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_CORE_WARNING:
			case E_COMPILE_ERROR:
			case E_COMPILE_WARNING:
			case E_RECOVERABLE_ERROR:
		
				$msg_user = false;
					
				if (strpos($msg, 'Maximum execution time of') !== false) {
					
					preg_match("/^Maximum execution time of (\d+)/", $msg, $amount);
					$amount = $amount[1];
					
					Labels::setVariable('amount', $amount);
					
					$msg_user = Labels::getSystemLabel('msg_error_time_limit');
				} else if (strpos($msg, 'Allowed memory size of') !== false) {
					
					preg_match("/^Allowed memory size of (\d+)/", $msg, $amount);
					$amount = $amount[1];
					$amount = bytes2String($amount);
					
					Labels::setVariable('amount', $amount);
					
					$msg_user = Labels::getSystemLabel('msg_error_memory_limit');
				}
				
				if ($msg_user) {
					
					msg($msg_user, self::label(TROUBLE_FATAL), LOG_CLIENT, false, self::type(TROUBLE_FATAL), 5000);
					
					self::fling($msg_system, TROUBLE_FATAL, LOG_SYSTEM, false, $exception);
				} else {
					
					self::fling($msg_system, TROUBLE_FATAL, LOG_BOTH, false, $exception);
				}
				break;
			default:
			
				self::fling($msg_system, TROUBLE_UNKNOWN, LOG_BOTH, false, $exception);
				break;
		}
	}
	
	public static function catchError($exception) {
		
		if (!($exception instanceof RealTrouble)) {
		
			$msg = $exception->getMessage();
			$file = $exception->getFile();
			$line = $exception->getLine();
			
			if ($exception instanceof ParseError) {
				$code = E_PARSE;
			} else {
				$code = E_ERROR;
			}
			
			try {
				self::core($code, $msg, $file, $line, $exception);
			} catch (Exception $e_converted) {
				$exception = $e_converted;
			}
		}
		
		$msg = self::strMsg($exception);
		$arr_code = self::parseCode($exception);
		$debug = self::strDebug($exception);
		
		if ($arr_code['code'] == TROUBLE_ERROR) {
			$b_state = Mediator::SHUTDOWN_INIT_SCRIPT;
		} else {
			$b_state = Mediator::SHUTDOWN_INIT_SYSTEM;
		}
		Mediator::setShutdown((Mediator::getShutdown() & ~Mediator::SHUTDOWN_INIT_UNDETERMINED) | $b_state);
		
		if (!Mediator::inCleanup()) {
			
			$msg_log = Labels::getSystemLabel('msg_error');
		
			Log::setMsg($msg_log);
			msg($msg, self::label($arr_code['code']), $arr_code['suppress'], $debug, self::type($arr_code['code']));
			
			if (!headers_sent()) {
				header($_SERVER['SERVER_PROTOCOL'].' 300 Multiple Choices');
			}
			
			try {
				
				Response::stop(function() {
					
						$obj = Log::addToObj(Response::getObject());
						
						$page = new ExitPage($obj->msg, 'error', 'error');
						
						if (isset($obj->system_msg)) {
							$page->setSystem($obj->system_msg, 'important');
						}
						
						return $page->getPage();
					}, Log::addToObj(Response::getObject())
				);
			} catch (Exception $e) {
				
				Mediator::setCleanup();
				self::catchError($e);
			}
		} else {
			
			msg($msg, self::label($arr_code['code']), $arr_code['suppress'], $debug, self::type($arr_code['code']));
		}
	}
	
	public static function parseCode($exception) {
		
		$code = $exception->getCode();
		
		if ($code >= 1000) { // Suppression parameter is combined with error code
			
			$suppress = floor($code/1000); // Division
			$code = ($code % 1000); // Remainder
		} else {
			
			$suppress = ($code >= TROUBLE_FATAL && $code <= TROUBLE_DATABASE && STATE == STATE_PRODUCTION && (!DB::isActive() || !getLabel('show_system_errors', 'D', true)) ? LOG_SYSTEM : LOG_BOTH);
		}
		
		return ['code' => $code, 'suppress' => $suppress];
	}
	
	public static function strMsg($exception) {
		
		$msg = $exception->getMessage();
		$arr_msg = explode(self::strMsgSeparator(), $msg);
		$str = trim($arr_msg[0]); // Get first message
		
		return $str;
	}
	
	public static function strMsgSeparator() {
		
		return str_pad('', 4, '#');
	}

	public static function strDebug($exception, $arr_seen = null) {
		
		// http://www.php.net/manual/en/exception.gettraceasstring.php#114980
		
		$arr_result = [];

		$str_starter = $arr_seen ? PHP_EOL.'Caused by: ' : '';
		if (!$arr_seen) {
			$arr_seen = [];
		}

		$str_msg = $exception->getMessage();
		$str_msg = str_replace(self::strMsgSeparator(), '', $str_msg);
		$str_msg = strIndent($str_msg, ' ');
		
		$arr_result[] = $str_starter.get_class($exception).':'
			.PHP_EOL.' '.str_pad('', 20, '-')
			.PHP_EOL.$str_msg
			.PHP_EOL.' '.str_pad('', 20, '-')
		;
		
		$trace = $exception->getTrace();
		$prev = $exception->getPrevious();
		$file = $exception->getFile();
		$line = $exception->getLine();
		
		while (true) {
		
			$current = $file.':'.$line;
			$len_trace = count($trace);
			
			if (is_array($arr_seen) && in_array($current, $arr_seen)) {
			
				$arr_result[] = sprintf(' ... %d more', $len_trace + 1);
				break;
			}
			
			$str_args = '';
			
			if ($len_trace && array_key_exists('args', $trace[0])) {
			
				$arr_args = [];
				
				foreach ($trace[0]['args'] as $arg) {
				
					if (is_string($arg)) {
						if (mb_strlen($arg) > 1000) {
							$arg = mb_substr($arg, 0, 1000).'...'; // Log a maximum of 1000 characters
						}
						$arg = str_replace(["\r", "\n", "\t"], ['', ' ', ''], $arg);
						$arr_args[] = "'".$arg."'";
					} elseif (is_array($arg)) {
						$arr_args[] = 'Array';
					} elseif (is_null($arg)) {
						$arr_args[] = 'NULL';
					} elseif (is_bool($arg)) {
						$arr_args[] = ($arg) ? 'true' : 'false';
					} elseif (is_object($arg)) {
						$arr_args[] = get_class($arg);
					} elseif (is_resource($arg)) {
						$arr_args[] = get_resource_type($arg);
					} else {
						$arr_args[] = $arg;
					}   
				}
				
				$str_args = implode(', ', $arr_args);
			}
			
			$arr_result[] = sprintf(
				' at %s%s%s: %s%s%s(%s)',
				$line === null ? $file : basename($file),
				$line === null ? '' : ':',
				$line === null ? '' : $line,
				$len_trace && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
				$len_trace && array_key_exists('class', $trace[0]) && array_key_exists(
					'function',
					$trace[0]
				) ? '.' : '',
				$len_trace && array_key_exists('function', $trace[0]) ? str_replace(
					'\\',
					'.',
					$trace[0]['function']
				) : '(main)',
				$str_args
			);
			
			if (is_array($arr_seen)) {
				$arr_seen[] = $file.':'.$line;
			}
			
			if (!$len_trace) {
				break;
			}
			
			$file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
			$line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
			
			array_shift($trace);
		}
		
		$arr_result = implode(PHP_EOL, $arr_result);
		if ($prev) {
			$arr_result .= PHP_EOL.self::strDebug($prev, $arr_seen);
		}
		
		return $arr_result;
	}
	
	public static function uncaught($exception) {
	
		// Thrown error handler
		
		Mediator::setShutdown(MEDIATOR::SHUTDOWN_SOFT | Mediator::SHUTDOWN_INIT_UNDETERMINED); // End of script, we're dealing with uncaught errors
		
		$obj = Response::getObject();
		unset($obj->data, $obj->html, $obj->data_feedback);
		
		// Reset connection to default and clean it
		try {
			
			DB::setConnection();
			DB::rollbackTransaction(false);
		} catch (Exception $e) { }

		self::catchError($exception);
	}
	
	public static function runtime($code, $msg, $file, $line) {
		
		// Runtime errors

		// Return false to execute the PHP internal error handler
		/*if (!(error_reporting() & $code)) { // if error was suppressed with the @-operator, error_reporting() does not report error flags
			return false;
		}*/
		
		// Coding style: Ignore undefined array indexes. Make sure to poperly check essential arrays, but ignore possible subsequent exceptions.
		if (strStartsWith($msg, 'Undefined array key') || strStartsWith($msg, 'Trying to access array offset on')) {
			$code = E_NOTICE;
		}
		if (strStartsWith($msg, 'Undefined index:') || strStartsWith($msg, 'Undefined offset:')) { // PHP 7.x
			$code = E_NOTICE;
		}
		// Code has to be fixed
		if (strStartsWith($msg, 'Undefined variable $')) {
			$code = E_NOTICE;
		}

		//if (($code == E_NOTICE || $code == E_DEPRECATED) && STATE == STATE_PRODUCTION) {
		if ($code == E_NOTICE || ($code == E_DEPRECATED && STATE == STATE_PRODUCTION)) {
			return true;
		}

		self::core($code, $msg, $file, $line); // Depending on the $code, it might or might not throw an error

		return true;
	}
	
	public static function system($code, $msg, $file, $line) {
	
		switch ($code) {
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_CORE_WARNING:
			case E_COMPILE_ERROR:
			case E_COMPILE_WARNING:
				
				Mediator::setShutdown(MEDIATOR::SHUTDOWN_HARD | Mediator::SHUTDOWN_INIT_SYSTEM);
				
				$obj = Response::getObject();
				unset($obj->data, $obj->html, $obj->data_feedback);
				
				try {
					self::core($code, $msg, $file, $line);
				} catch (Exception $e) {
					self::catchError($e);
				}
				
				// When a system overload (timeout/memory) error occurs it could happen mid-execution, clear anything that might be stuck
				DB::clearConnection();
				
				break;
		}
	}
}

set_exception_handler('Trouble::uncaught');

set_error_handler('Trouble::runtime');
