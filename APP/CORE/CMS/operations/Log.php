<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class Log {
	
	const IP_STATE_CHECKED = 1;
	const IP_STATE_BLOCKED = 2;
	const IP_STATE_APPROVED = 3;
	
	const MESSAGE_DESCRIPTION = 0; // Places in a message array
	const MESSAGE_LABEL = 1;
	const MESSAGE_SUPPRESS = 2;
	const MESSAGE_IDENTIFIER = 6;
	
	private static $str_header = false;
	private static $arr_messages = [];
	
	private static $str_ip = null;
	private static $str_ip_proxy = null;
	private static $bin_ip_request = null;
	private static $bin_ip_request_block = null;
	private static $num_request_heat = false;
	private static $num_request_state = 0;
	private static $log_user_id = false;
	private static $do_store_database = true;
	private static $do_store_file = true;

	public static function setHeader($str_header) {
	
		self::$str_header = $str_header;
	}
	
	public static function addMessage($str_description, $str_label, $mode_suppress, $str_debug, $str_type, $arr_options = null, $identifier = null) {
		
		self::$arr_messages[] = [$str_description, $str_label, $mode_suppress, $str_debug, $str_type, $arr_options, $identifier];
		
		if (SiteStartEnvironment::isProcess()) { // Store immediately, do not wait for exit (can be a long time, if ever!)
			self::addToDB();
		}
	}
	
	public static function getMessages($identifier, $mode_suppress = null) {
		
		$arr_messages = [];
		
		if ($identifier instanceof Exception) { // Retrieve and cascade all previous error related messages
			
			$e = $identifier;
			
			do {
				
				foreach (self::$arr_messages as $key => $arr_message) {
			
					if ($arr_message[static::MESSAGE_IDENTIFIER] === null || $arr_message[static::MESSAGE_IDENTIFIER] !== $e) {
						continue;
					}
					
					if ($mode_suppress !== null && $arr_message[static::MESSAGE_SUPPRESS] !== $mode_suppress) {
						continue;
					}
					
					$arr_messages[$key] = self::$arr_messages[$key];
				}
			} while ($e = $e->getPrevious());
			
			return $arr_messages;
		}
		
		foreach (self::$arr_messages as $key => $arr_message) {
			
			if ($arr_message[static::MESSAGE_IDENTIFIER] === null || $arr_message[static::MESSAGE_IDENTIFIER] !== $identifier) {
				continue;
			}
			
			if ($mode_suppress !== null && $arr_message[static::MESSAGE_SUPPRESS] !== $mode_suppress) {
				continue;
			}
			
			$arr_messages[$key] = self::$arr_messages[$key];
		}
		
		return $arr_messages;
	}
	
	public static function removeMessages($identifier, $mode_suppress = null) {
		
		$arr_messages = static::getMessages($identifier, $mode_suppress);
				
		foreach ($arr_messages as $key => $arr_message) {
			
			unset(self::$arr_messages[$key]);
		}
	}
				
	public static function addToObject($JSON) {
					
		$arr_messages = [];
		
		if (SiteStartEnvironment::getRequestState() == SiteStartEnvironment::REQUEST_API) {
			
			foreach (self::$arr_messages as $arr_value) {
				
				if ($arr_value[2] != LOG_BOTH && $arr_value[2] != LOG_CLIENT) { // Suppressed?
					continue;
				}
				
				$str_label = (is_array($arr_value[1]) ? $arr_value[1][1] : $arr_value[1]);
									
				if ($arr_value[4] == 'alert') {
					
					$JSON->error = $str_label;
					$JSON->error_description = $arr_value[0];
				} else {
				
					$arr_messages[] = ['label' => $str_label, 'description' => $arr_value[0], 'type' => $arr_value[4], 'options' => $arr_value[5]];
				}
			}	
			
			if ($arr_messages) {
				$JSON->message = $arr_messages;
			}
		} else {
			
			$str_type = 'attention';
			$str = '';
			$arr_options = null;
			
			foreach (self::$arr_messages as $arr_value) {

				// Get type and options even before possible suppression
				
				$str_type = $arr_value[4];
				$arr_options = $arr_value[5];
				
				if ($arr_value[2] != LOG_BOTH && $arr_value[2] != LOG_CLIENT) { // Suppressed?
					continue;
				}
				
				$str_label = (is_array($arr_value[1]) ? $arr_value[1][0] : $arr_value[1]);
				
				$arr_messages[] = '<label>'.$str_label.'</label><div>'.$arr_value[0].'</div>';
			}

			if ($arr_messages || self::$str_header) {
				
				$str_header = '<label></label><div>'.(!self::$str_header && $arr_messages ? 'Report' : self::$str_header).'</div>';
				$str = '<ul><li>'.$str_header.'</li>'.($arr_messages ? '<li>'.implode('</li><li>', $arr_messages).'</li>' : '').'</ul>';
			}
			
			if ($str) {
				
				$JSON->message = $str;
				$JSON->message_type = $str_type;
			}
			
			if ($arr_options !== null && !is_array($arr_options)) {
				$arr_options = ['duration' => (int)$arr_options];
			} else {
				$arr_options = ($arr_options ?: []);
			}
			
			if (!isset($arr_options['clear'])) {
				$arr_options['clear'] = ['identifier' => SiteStartEnvironment::getSessionID(true)];
			}
			
			$JSON->message_options = $arr_options;
			
			if (MESSAGE !== null) {
				$JSON->system_message = '<div class="important"><p><span class="icon">'.getIcon('attention').'</span><span>'.Labels::parseTextVariables(MESSAGE).'</span></p></div>';
			}
		}
		
		return $JSON;
	}
					
	public static function addToDB() {
		
		if (!self::$arr_messages) {
			return;
		}

		// Try logging to database when enabled
		
		if (static::$do_store_database) {
			
			try {
				
				if (Settings::isInitialised()) {
					
					if (getLabel('logging', 'D', true)) {
						self::addToDBSQL();
					}
					
					return;
				}
			} catch (Exception $e) {
				
				static::$do_store_database = false;
				Trouble::catchError($e);
			}
		}
		
		// Try logging to disk when database is unavailable
		
		if (static::$do_store_file) {
			
			try {
				
				self::addToDBFile();
			} catch (Exception $e) {
				
				static::$do_store_file = false;
				Trouble::catchError($e);
			}
		}
	}
	
	public static function addToDBSQL() {
		
		if (!self::$arr_messages) {
			return;
		}
			
		$arr_sql_insert = [];
		
		foreach (self::$arr_messages as $arr_value) {
			
			if ($arr_value[2] != LOG_BOTH && $arr_value[2] != LOG_SYSTEM) { // Suppressed?
				continue;
			}
				
			$log_user_id = self::addToUserDB();
			$str_message = (!strIsValidEncoding($arr_value[0]) ? strFixEncoding($arr_value[0]) : $arr_value[0]); // Make sure we're also able to store wrongly encoded messages
			$str_label = (is_array($arr_value[1]) ? $arr_value[1][0] : $arr_value[1]);
			$str_debug = (!strIsValidEncoding($arr_value[3]) ? strFixEncoding($arr_value[3]) : $arr_value[3]);
			
			$arr_sql_insert[] = "(
				'".DBFunctions::strEscape($str_message)."',
				'".DBFunctions::strEscape($str_label)."',
				'".DBFunctions::strEscape($str_debug)."',
				'".DBFunctions::strEscape($arr_value[4])."',
				NOW(),
				".$log_user_id."
			)";
		}

		if ($arr_sql_insert) {
			
			DB::setConnection(DB::CONNECT_CMS);
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_LOG')."
				(msg, label, debug, type, date, log_user_id)
					VALUES
				".implode(",", $arr_sql_insert)."
			");
			
			DB::setConnection();
		}
		
		self::$arr_messages = [];
	}
	
	public static function addToDBFile() {
		
		if (!self::$arr_messages) {
			return;
		}
		
		$str_path = DIR_ROOT_STORAGE.DIR_HOME.DIR_CMS.DIR_PRIVATE;
		FileStore::makeDirectoryTree($str_path);
		$str_path .= 'log';
		
		$file = fopen($str_path, 'a');
		FileStore::setFilePermission($str_path);
		
		if (flock($file, LOCK_EX)) {
			
			foreach (self::$arr_messages as $arr_value) {
				
				if ($arr_value[2] != LOG_BOTH && $arr_value[2] != LOG_SYSTEM) { // Suppressed?
					continue;
				}
					
				$str_label = (is_array($arr_value[1]) ? $arr_value[1][0] : $arr_value[1]);
				
				fwrite($file, EOL_1100CC.EOL_1100CC
					.str_pad('', 6, '#')
					.EOL_1100CC.$str_label.': '.$arr_value[0]
					.EOL_1100CC.$arr_value[4].' at '.date('d-m-Y H:i:s').' by '.($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['name'] ?? '...')
					.($arr_value[3] ? EOL_1100CC.EOL_1100CC.$arr_value[3] : '')
				);
			}
			
			flock($file, LOCK_UN);
		}
		
		fclose($file);
		
		self::$arr_messages = [];		
	}
	
	public static function addToUserDB() {
		
		if (self::$log_user_id) {
			return self::$log_user_id;
		}
		
		$num_user_id = 0;
		$num_user_class = 0;
		
		if (!empty($_SESSION['USER_ID'])) {
			
			$num_user_id = $_SESSION['USER_ID'];
			$num_user_class = ($_SESSION['USER_GROUP'] ? 3 : ($_SESSION['CORE'] ? 1 : 2));
		}
		
		if (SiteStartEnvironment::isProcess()) {
			
			$arr_ip = null;
			$str_url = URL_BASE;
			$str_url_referral = '';
		} else {
			
			$arr_ip = self::getIP();
			$str_url = SiteStartEnvironment::getRequestURL(false);
			$str_url_referral = ($_SESSION['REFERER_URL'] ?? '');
		}
		
		DB::setConnection(DB::CONNECT_CMS);
		
		$res = DB::query("INSERT INTO ".DB::getTable('TABLE_LOG_USERS')."
			(user_id, user_class, ip, ip_proxy, url, referral_url)
				VALUES
			(".(int)$num_user_id.", ".(int)$num_user_class.", ".($arr_ip ? DBFunctions::escapeAs(inet_pton($arr_ip[0]), DBFunctions::TYPE_BINARY) : "''").", ".($arr_ip && $arr_ip[1] ? DBFunctions::escapeAs(inet_pton($arr_ip[1]), DBFunctions::TYPE_BINARY) : "''").", '".DBFunctions::strEscape($str_url)."', '".DBFunctions::strEscape($str_url_referral)."')
		");
		
		self::$log_user_id = DB::lastInsertID();
		
		DB::setConnection();
		
		return self::$log_user_id;
	}
	
	public static function getWhereUserDB() {
	
		if (!empty($_SESSION['USER_ID'])) {
			
			$str_sql_where = "user_class = ".($_SESSION['USER_GROUP'] ? 3 : ($_SESSION['CORE'] ? 1 : 2));
		} else {
			
			$arr_ip = self::getIP();
			$str_sql_where = "(ip = ".DBFunctions::escapeAs(inet_pton($arr_ip[0]), DBFunctions::TYPE_BINARY)."".($arr_ip[1] ? " OR ip_proxy = ".DBFunctions::escapeAs(inet_pton($arr_ip[1]), DBFunctions::TYPE_BINARY)."" : "").")";
		}
		
		return $str_sql_where;
	}
	
	public static function getIP() {
		
		if (self::$str_ip === null) {
				
			$str_ip = (string)$_SERVER['REMOTE_ADDR'];
			$str_ip_proxy = null;
			
			if ($_SERVER['HTTP_CLIENT_IP'] && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
				$str_ip_proxy = $str_ip;
				$str_ip = trim($_SERVER['HTTP_CLIENT_IP']);
			} else if ($_SERVER['HTTP_X_FORWARDED_FOR']) {
				$str_ip_proxy = $str_ip;
				if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') > 0) {
					$arr_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
					$str_ip = trim($arr_ip[0]);
				} else {
					$str_ip = trim($_SERVER['HTTP_X_FORWARDED_FOR']);
				}
			}
			
			self::$str_ip = $str_ip;
			self::$str_ip_proxy = $str_ip_proxy;
		}
					
		return [self::$str_ip, self::$str_ip_proxy];
	}
	
	public static function parseIPRequest() {
		
		if (self::$bin_ip_request !== null) {
			return;
		}
		
		$str_ip = (string)$_SERVER['REMOTE_ADDR'];
		self::$bin_ip_request = inet_pton($str_ip); // inet_pton can handle both IPv4 and IPv6 addresses, treat IPv6 addresses as /64 or /56 blocks.
		
		if (filter_var($str_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
			self::$bin_ip_request_block = substr(self::$bin_ip_request, 0, 8)."\x00\x00\x00\x00\x00\x00\x00\x00"; // Turn 128 bit IPv6 address into 64 bit
		} else {
			self::$bin_ip_request_block = substr(self::$bin_ip_request, 0, 3)."\x00";  // Turn 32 bit IPv4 address into 24 bit
		}
	}
	
	public static function checkRequestThrottle($num_window = 30 * 60, $num_per_second = 1) { // Seconds
		
		// Throttle using a rolling window, stored in milliseconds to calculate proper short-term 'heat'
		
		self::parseIPRequest();
		
		$sql_ip = DBFunctions::escapeAs(self::$bin_ip_request, DBFunctions::TYPE_BINARY);
		$sql_interval = DBFunctions::interval($num_window, 'SECOND');
		$sql_time_difference = DBFunctions::timeDifference('MICROSECOND', 'date', 'NOW(3)');
		
		$arr_res = DB::queryMulti("
			INSERT INTO ".DB::getTable('TABLE_LOG_REQUESTS_THROTTLE')."
				(ip, date, heat, state)
					VALUES
				(".$sql_ip.", NOW(3), 1, 0)
				".DBFunctions::onConflict('ip', false, "
					heat = (
						CASE
							WHEN date < (NOW() - ".$sql_interval.") THEN
								0
							ELSE
								(1 - ((".$sql_time_difference." / 1000) / (".$num_window." * 1000))) * heat
						END # Lower heat based on time past in the window
						+ (1000 / ((".$sql_time_difference." / 1000) + 10)) # Simulate the total amount of requests in a second based on time past in the window, and add 10 millisecond as a 'softening' bonus.
						+ 1 # Add one for the request itself
					),
					date = NOW(3)
				")."
			;
			SELECT heat, state
					FROM ".DB::getTable('TABLE_LOG_REQUESTS_THROTTLE')."
				WHERE ip = ".$sql_ip."
			;
		");
		
		$arr_row = $arr_res[1]->fetchRow();
		
		$num_heat = $arr_row[0];
		$num_state = $arr_row[1];
		
		$num_threshold = ($num_window * $num_per_second);
		
		if (!$num_state && $num_heat > ($num_threshold * 0.01)) { // Check hostname after a certain threshold
			
			$num_state = static::IP_STATE_CHECKED;
			
			$arr_hosts_blocked = Settings::get('request_hosts_blocked');
			
			if ($arr_hosts_blocked) {
				
				$str_host = gethostbyaddr(inet_ntop(self::$bin_ip_request));
				
				foreach ($arr_hosts_blocked as $str_check) {
					
					if (strpos($str_host, $str_check) !== false) {
					
						$num_state = static::IP_STATE_BLOCKED;
						break;
					}
				}
			}

			static::updateRequestState($num_state);
		}
		
		static::$num_request_state = $num_state;
		static::$num_request_heat = $num_heat;
		
		if ($num_state != static::IP_STATE_APPROVED && ($num_heat > $num_threshold || $num_state == static::IP_STATE_BLOCKED)) {
			return true;
		}
		
		return false;
	}
	
	public static function updateRequestState($num_state) {
		
		if ($num_state == static::$num_request_state) {
			return;
		}
		
		self::parseIPRequest();
		
		$str_sql_ip = DBFunctions::escapeAs(self::$bin_ip_request, DBFunctions::TYPE_BINARY);
	
		$res = DB::query("
			UPDATE ".DB::getTable('TABLE_LOG_REQUESTS_THROTTLE')." SET
				state = ".$num_state."
			WHERE ip = ".$str_sql_ip."
		");
	}
	
	public static function checkRequest($str_type, $str_identifier, $num_interval, $arr_count) {
		
		self::parseIPRequest();
		
		$res = DB::query("SELECT
			COUNT(*) AS count_global,
			SUM(CASE WHEN lr.ip_block = ".DBFunctions::escapeAs(self::$bin_ip_request_block, DBFunctions::TYPE_BINARY)." THEN 1 ELSE 0 END) AS count_ip_block,
			SUM(CASE WHEN lr.ip = ".DBFunctions::escapeAs(self::$bin_ip_request, DBFunctions::TYPE_BINARY)." THEN 1 ELSE 0 END) AS count_ip
			".($str_identifier !== null ? ", (SELECT COUNT(DISTINCT lr_i.ip_block)
				FROM ".DB::getTable('TABLE_LOG_REQUESTS_ACCESS')." lr_i
				WHERE lr_i.type = '".$str_type."'
					AND lr_i.date >= (NOW() - ".DBFunctions::interval((int)$num_interval, 'SECOND').")
					AND lr_i.identifier = '".DBFunctions::strEscape($str_identifier)."'
			) AS count_identifier" : "")."
				FROM ".DB::getTable('TABLE_LOG_REQUESTS_ACCESS')." lr
			WHERE lr.type = '".$str_type."'
				AND lr.date >= (NOW() - ".DBFunctions::interval((int)$num_interval, 'SECOND').")
		");
		
		$arr_row = $res->fetchRow();
		
		if ($arr_count['global'] && (int)$arr_row[0] >= $arr_count['global']) {
			return 'global';
		} else if ($arr_count['ip_block'] && (int)$arr_row[1] >= $arr_count['ip_block']) {
			return 'ip_block';
		} else if ($arr_count['ip'] && (int)$arr_row[2] >= $arr_count['ip']) {
			return 'ip';
		} else if ($arr_count['identifier'] && (int)$arr_row[3] >= $arr_count['identifier']) {
			return 'identifier';
		}
		
		return true;
	}
	
	public static function logRequest($str_type, $str_identifier = null) {
		
		self::parseIPRequest();
		
		$res = DB::query("INSERT INTO ".DB::getTable('TABLE_LOG_REQUESTS_ACCESS')."
			(type, identifier, ip, ip_block, date)
				VALUES
			('".$str_type."', ".($str_identifier !== null ? "'".DBFunctions::strEscape($str_identifier)."'" : "NULL").", ".DBFunctions::escapeAs(self::$bin_ip_request, DBFunctions::TYPE_BINARY).", ".DBFunctions::escapeAs(self::$bin_ip_request_block, DBFunctions::TYPE_BINARY).", NOW())
		");
	}
	
	public static function parseRequestIdentifier($str_identifier) {
		
		// If value is wrong/malicious, we still want to log it
		
		if (strlen($str_identifier) > 100 || !strIsValidEncoding($str_identifier)) {
			
			$str_identifier = 'ERRONEOUS';
			//$str_identifier = strFixEncoding(substr($str_identifier, 0, 100));
		}
		
		return $str_identifier;
	}
}
