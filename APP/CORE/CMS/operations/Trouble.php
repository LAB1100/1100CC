<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class RealTrouble extends Exception {
	
	public function setMessage($str) {
		
		$this->message = $str;
	}
	
	public function setCode($num) {
		
		$this->code = $num;
	}
	
	public function getTroubleMessage() {
		
		$arr_message = Trouble::parseMessage($this);
		
		return $arr_message['message'];
	}
	
	public function getTroubleDebug() {
		
		$arr_message = Trouble::parseMessage($this);
		
		return $arr_message['debug'];
	}
	
	public function getTroubleCode() {
		
		$arr_code = Trouble::parseCode($this);
		
		return $arr_code['code'];
	}
	
	public function getTroubleSuppress() {
		
		$arr_code = Trouble::parseCode($this);
		
		return $arr_code['suppress'];
	}
	
	public function getTroubleTraceDebug() {
		
		$str_trace_debug = Trouble::strTraceDebug($this);
		
		return $str_trace_debug;
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
		
	public static function fling($str_message = '', $mode_code = TROUBLE_ERROR, $mode_suppress = LOG_BOTH, $str_debug = null, $exception = null) {

		switch ($mode_code) {
			case TROUBLE_NOTICE:
			case TROUBLE_UNKNOWN:
				
				// Create notice/message
				
				$e = self::create($str_message, $mode_code, $mode_suppress, $str_debug, $exception);
				
				$mode_suppress = ($mode_suppress ?: (STATE == STATE_PRODUCTION && (!DB::isActive() || !getLabel('show_system_errors', 'D', true)) ? LOG_SYSTEM : LOG_BOTH));
				$arr_message = self::parseMessage($e);
				$str_trace_debug = self::strTraceDebug($e);
				
				message($arr_message['message'], self::label($mode_code), $mode_suppress, $str_trace_debug, self::type($mode_code), null, $e);
				break;
			default:
			
				// Create exception
			
				throw self::create($str_message, $mode_code, $mode_suppress, $str_debug, $exception);
				break;
		}
	}
	
	public static function create($str_message = '', $mode_code = TROUBLE_ERROR, $mode_suppress = LOG_BOTH, $str_debug = null, $exception = null) {
		
		$str_message = ($str_message ?? '');
		$mode_code = ($mode_suppress ? $mode_code + ($mode_suppress * 1000) : $mode_code); // Combine suppression parameter with error code

		if ($str_debug) {
			$str_message = $str_message.PHP_EOL.self::strMessageSeparator().PHP_EOL.$str_debug;
		}
		
		$class = (self::$arr_trouble[$mode_code][2] ?? 'RealTrouble');
		
		return new $class($str_message, $mode_code, $exception);
	}

	public static function label($mode_code) {
		
		return (isset(self::$arr_trouble[$mode_code]) ? self::$arr_trouble[$mode_code][0] : self::$arr_trouble[0][0]);
	}
	
	public static function type($mode_code) {
		
		return (isset(self::$arr_trouble[$mode_code]) ? self::$arr_trouble[$mode_code][1] : self::$arr_trouble[0][1]);
	}
	
	public static function core($mode_code, $str_message, $file, $line, $exception = null) {
	
		$str_message_system = $str_message.'. Error on line '.$line.' in file '.$file;
		
		switch ($mode_code) {
			case E_WARNING:
			
				self::fling($str_message_system, TROUBLE_WARNING, LOG_BOTH, null, $exception);
				break;
			case E_NOTICE:
			
				self::fling($str_message_system, TROUBLE_NOTICE, LOG_BOTH, null, $exception);
				break;
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_CORE_WARNING:
			case E_COMPILE_ERROR:
			case E_COMPILE_WARNING:
			case E_RECOVERABLE_ERROR:
		
				$str_message_user = null;
					
				if (strpos($str_message, 'Maximum execution time of') !== false) {
					
					preg_match("/^Maximum execution time of (\d+)/", $str_message, $amount);
					$amount = $amount[1];
					
					Labels::setVariable('amount', $amount);
					
					$str_message_user = Labels::getSystemLabel('msg_error_time_limit');
				} else if (strpos($str_message, 'Allowed memory size of') !== false) {
					
					preg_match("/^Allowed memory size of (\d+)/", $str_message, $amount);
					$amount = $amount[1];
					$amount = bytes2String($amount);
					
					Labels::setVariable('amount', $amount);
					
					$str_message_user = Labels::getSystemLabel('msg_error_memory_limit');
				}
				
				if ($str_message_user) {
					
					message($str_message_user, self::label(TROUBLE_FATAL), LOG_CLIENT, null, self::type(TROUBLE_FATAL), 5000, $exception);
					
					self::fling($str_message_system, TROUBLE_FATAL, LOG_SYSTEM, null, $exception);
				} else {
					
					self::fling($str_message_system, TROUBLE_FATAL, LOG_BOTH, null, $exception);
				}
				break;
			default:
			
				self::fling($str_message_system, TROUBLE_UNKNOWN, LOG_BOTH, null, $exception);
				break;
		}
	}
	
	public static function catchError($exception) {
		
		if (!($exception instanceof RealTrouble)) {
		
			$str_message = $exception->getMessage();
			$file = $exception->getFile();
			$line = $exception->getLine();
			
			if ($exception instanceof ParseError) {
				$mode_code = E_PARSE;
			} else {
				$mode_code = E_ERROR;
			}
			
			try {
				self::core($mode_code, $str_message, $file, $line, $exception);
			} catch (Exception $e_converted) {
				$exception = $e_converted;
			}
		}
		
		$arr_message = self::parseMessage($exception);
		$str_message = $arr_message['message'];
		$arr_code = self::parseCode($exception);
		$str_trace_debug = self::strTraceDebug($exception);
		
		if ($arr_code['code'] == TROUBLE_ERROR) {
			$bit_state = Mediator::SHUTDOWN_INIT_SCRIPT;
		} else {
			$bit_state = Mediator::SHUTDOWN_INIT_SYSTEM;
		}
		Mediator::setShutdown((Mediator::getShutdown() & ~Mediator::SHUTDOWN_INIT_UNDETERMINED) | $bit_state);
		
		if (!Mediator::inCleanup()) {
			
			$str_message_log = Labels::getSystemLabel('msg_error');
		
			Log::setHeader($str_message_log);
			message($str_message, self::label($arr_code['code']), $arr_code['suppress'], $str_trace_debug, self::type($arr_code['code']));
			
			if (!headers_sent()) {
				header($_SERVER['SERVER_PROTOCOL'].' 300 Multiple Choices');
			}
			
			try {
				
				Response::stop(function() {
					
						$obj = Log::addToObject(Response::getObject());
						
						$page = new ExitPage($obj->message, 'error', 'error');
						
						if (isset($obj->system_message)) {
							$page->setSystem($obj->system_message, 'important');
						}
						
						return $page->getPage();
					}, Log::addToObject(Response::getObject())
				);
			} catch (Exception $e) {
				
				Mediator::setCleanup();
				self::catchError($e);
			}
		} else {
			
			message($str_message, self::label($arr_code['code']), $arr_code['suppress'], $str_trace_debug, self::type($arr_code['code']));
		}
	}
	
	public static function parseCode($exception) {
		
		$mode_code = $exception->getCode();
		
		if ($mode_code >= 1000) { // Suppression parameter is combined with error code
			
			$mode_suppress = floor($mode_code/1000); // Division
			$mode_code = ($mode_code % 1000); // Remainder
		} else {
			
			$mode_suppress = ($mode_code >= TROUBLE_FATAL && $mode_code <= TROUBLE_DATABASE && STATE == STATE_PRODUCTION && (!DB::isActive() || !getLabel('show_system_errors', 'D', true)) ? LOG_SYSTEM : LOG_BOTH);
		}
		
		return ['code' => $mode_code, 'suppress' => $mode_suppress];
	}
	
	public static function parseMessage($exception) {
		
		$str_message = $exception->getMessage();
		$arr_message = explode(self::strMessageSeparator(), $str_message);
		
		$str_message = trim($arr_message[0]); // Get first message
		$str_debug = (isset($arr_message[1]) ? trim($arr_message[1]) : null); // Get debug message
		
		return ['message' => $str_message, 'debug' => $str_debug];
	}
	
	public static function strMessageSeparator() {
		
		return str_pad('', 4, '#');
	}

	public static function strTraceDebug($exception, $arr_seen = null) {
		
		// http://www.php.net/manual/en/exception.gettraceasstring.php#114980
		
		$arr_result = [];

		$str_starter = $arr_seen ? PHP_EOL.'Caused by: ' : '';
		if (!$arr_seen) {
			$arr_seen = [];
		}

		$str_message = $exception->getMessage();
		$str_message = str_replace(self::strMessageSeparator(), '', $str_message);
		$str_message = strIndent($str_message, ' ');
		
		$arr_result[] = $str_starter.get_class($exception).':'
			.PHP_EOL.' '.str_pad('', 20, '-')
			.PHP_EOL.$str_message
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
			$arr_result .= PHP_EOL.self::strTraceDebug($prev, $arr_seen);
		}
		
		return $arr_result;
	}
	
	public static function uncaught($exception) {
	
		// Thrown error handler
		
		Mediator::setShutdown(MEDIATOR::SHUTDOWN_SOFT | Mediator::SHUTDOWN_INIT_UNDETERMINED); // End of script, we're dealing with uncaught errors
		
		$obj = Response::getObject();
		unset($obj->data, $obj->html, $obj->data_feedback);
		
		try { // Reset connection to default and clean it
			
			DB::setConnection(null, DB::MODE_CONNECT_DEFAULT_DATABASE);
			DB::rollbackTransaction(false);
		} catch (Exception $e) { }

		self::catchError($exception);
	}
	
	public static function runtime($mode_code, $str_message, $file, $line) {
		
		// Runtime errors

		// Return false to execute the PHP internal error handler
		/*if (!(error_reporting() & $mode_code)) { // if error was suppressed with the @-operator, error_reporting() does not report error flags
			return false;
		}*/
		
		// Coding style: Ignore undefined array indexes. Make sure to poperly check essential arrays, but ignore possible subsequent exceptions.
		if (strStartsWith($str_message, 'Undefined array key') || strStartsWith($str_message, 'Trying to access array offset on')) {
			$mode_code = E_NOTICE;
		}
		if (strStartsWith($str_message, 'Undefined index:') || strStartsWith($str_message, 'Undefined offset:')) { // PHP 7.x
			$mode_code = E_NOTICE;
		}
		// Code has to be fixed
		if (strStartsWith($str_message, 'Undefined variable $')) {
			$mode_code = E_NOTICE;
		}

		//if (($mode_code == E_NOTICE || $mode_code == E_DEPRECATED) && STATE == STATE_PRODUCTION) {
		if ($mode_code == E_NOTICE || ($mode_code == E_DEPRECATED && STATE == STATE_PRODUCTION)) {
			return true;
		}

		self::core($mode_code, $str_message, $file, $line); // Depending on the $mode_code, it might or might not throw an error

		return true;
	}
	
	public static function system($mode_code, $str_message, $file, $line) {
	
		switch ($mode_code) {
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
					self::core($mode_code, $str_message, $file, $line);
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
