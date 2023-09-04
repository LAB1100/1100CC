<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class Log {
	
	const IP_STATE_CHECKED = 1;
	const IP_STATE_BLOCKED = 2;
	const IP_STATE_APPROVED = 3;
	
	private static $msg = false;
	private static $arr_msg = [];
	
	private static $ip = false;
	private static $ip_proxy = false;
	private static $ip_request = false;
	private static $ip_request_block = false;
	private static $num_request_heat = false;
	private static $num_request_state = 0;
	private static $log_user_id = false;
	private static $do_store_database = true;
	private static $do_store_file = true;

	public static function setMsg($msg) {
	
		self::$msg = $msg;
	}
	
	public static function addMsg($msg, $label, $suppress, $debug, $type, $arr_options = null) {

		self::$arr_msg[] = [$msg, $label, $suppress, $debug, $type, $arr_options];
		
		if (SiteStartVars::isProcess()) { // Store immediately, do not wait for exit (can be a long time, if ever!)
			self::addToDB();
		}
	}
			
	public static function addToObj($JSON) {
					
		$arr_msgs = [];
		
		if (SiteStartVars::getRequestState() == SiteStartVars::REQUEST_API) {
			
			foreach (self::$arr_msg as $value) {
				
				if ($value[2] != LOG_BOTH && $value[2] != LOG_CLIENT) { // Suppressed?
					continue;
				}
				
				$label = (is_array($value[1]) ? $value[1][1] : $value[1]);
									
				if ($value[4] == 'alert') {
					
					$JSON->error = $label;
					$JSON->error_description = $value[0];
				} else {
				
					$arr_msgs[] = ['label' => $label, 'description' => $value[0], 'type' => $value[4], 'options' => $value[5]];
				}
			}	
			
			if ($arr_msgs) {
				
				$JSON->msg = $arr_msgs;
			}			
		} else {
			
			$type = 'attention';
			$str = '';
			$arr_options = null;
			
			foreach (self::$arr_msg as $value) {
				
				$type = $value[4];
				$arr_options = $value[5];
				
				if ($value[2] != LOG_BOTH && $value[2] != LOG_CLIENT) { // Suppressed?
					continue;
				}
				
				$label = (is_array($value[1]) ? $value[1][0] : $value[1]);
				
				$arr_msgs[] = '<label>'.$label.'</label><div>'.$value[0].'</div>';
			}

			if ($arr_msgs || self::$msg) {
				
				$msg = '<label></label><div>'.(!self::$msg && $arr_msgs ? 'Report' : self::$msg).'</div>';
				$str = '<ul><li>'.$msg.'</li>'.($arr_msgs ? '<li>'.implode('</li><li>', $arr_msgs).'</li>' : '').'</ul>';
			}
			
			if ($str) {
				
				$JSON->msg = $str;
				$JSON->msg_type = $type;
			}
			
			if ($arr_options !== null && !is_array($arr_options)) {
				
				$arr_options = ['duration' => (int)$arr_options];
			} else {
				
				$arr_options = ($arr_options ?: []);
			}
			
			if (!isset($arr_options['clear'])) {
				$arr_options['clear'] = ['identifier' => SiteStartVars::getSessionId(true)];
			}
			
			$JSON->msg_options = $arr_options;
			
			if (MESSAGE !== null) {
				$JSON->system_msg = '<div class="important"><p><span class="icon">'.getIcon('attention').'</span><span>'.Labels::parseTextVariables(MESSAGE).'</span></p></div>';
			}
		}
		
		return $JSON;
	}
					
	public static function addToDB() {
		
		if (!self::$arr_msg) {
			return;
		}

		// Try logging to database when enabled
		
		if (static::$do_store_database) {
			
			try {
				
				if (DB::isActive()) {
					
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
		
		if (!self::$arr_msg) {
			return;
		}
			
		$arr_sql_insert = [];
		
		foreach (self::$arr_msg as $value) {
			
			if ($value[2] == LOG_BOTH || $value[2] == LOG_SYSTEM) { // Suppressed?
				
				$log_user_id = self::addToUserDB();
				$label = (is_array($value[1]) ? $value[1][0] : $value[1]);
				
				$arr_sql_insert[] = "(
					'".DBFunctions::strEscape($value[0])."',
					'".DBFunctions::strEscape($label)."',
					'".DBFunctions::strEscape($value[3])."',
					'".DBFunctions::strEscape($value[4])."',
					NOW(),
					".$log_user_id."
				)";
			}
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
		
		self::$arr_msg = [];
	}
	
	public static function addToDBFile() {
		
		if (!self::$arr_msg) {
			return;
		}
		
		$str_path = DIR_ROOT_STORAGE.DIR_HOME.DIR_CMS.DIR_PRIVATE;
		FileStore::makeDirectoryTree($str_path);
		$str_path .= 'log';
		
		$file = fopen($str_path, 'a');
		FileStore::setFilePermission($str_path);
		
		if (flock($file, LOCK_EX)) {
			
			foreach (self::$arr_msg as $arr_value) {
				
				if ($arr_value[2] == LOG_BOTH || $arr_value[2] == LOG_SYSTEM) { // Suppressed?
					
					$label = (is_array($arr_value[1]) ? $arr_value[1][0] : $arr_value[1]);
					
					fwrite($file, EOL_1100CC.EOL_1100CC
						.str_pad('', 6, '#')
						.EOL_1100CC.$label.': '.$arr_value[0]
						.EOL_1100CC.$arr_value[4].' at '.date('d-m-Y H:i:s').' by '.($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['name'] ?? '...')
						.($arr_value[3] ? EOL_1100CC.EOL_1100CC.$arr_value[3] : '')
					);
				}
			}
			
			flock($file, LOCK_UN);
		}
		
		fclose($file);
		
		self::$arr_msg = [];		
	}
	
	public static function addToUserDB() {
		
		if (!self::$log_user_id) {
			
			$sql_user_id = 0;
			$sql_user_class = 0;
		
			if (!empty($_SESSION['USER_ID'])) {
				
				$sql_user_id = $_SESSION['USER_ID'];
				$sql_user_class = ($_SESSION['USER_GROUP'] ? 3 : ($_SESSION['CORE'] ? 1 : 2));
			}
			
			if (SiteStartVars::isProcess()) {
				
				$arr_ip = false;
				$url = URL_BASE;
				$referral_url = '';
			} else {
				
				$arr_ip = self::getIP();
				$url = URL_BASE.ltrim(($_SERVER['PATH_VIRTUAL'] ? $_SERVER['PATH_VIRTUAL'].' (V)' : $_SERVER['PATH_INFO']), '/');
				$referral_url = ($_SESSION['REFERER_URL'] ?? '');
			}
			
			DB::setConnection(DB::CONNECT_CMS);
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_LOG_USERS')."
				(user_id, user_class, ip, ip_proxy, url, referral_url)
					VALUES
				(".(int)$sql_user_id.", ".(int)$sql_user_class.", ".($arr_ip ? DBFunctions::escapeAs(inet_pton($arr_ip[0]), DBFunctions::TYPE_BINARY) : "''").", ".($arr_ip && $arr_ip[1] ? DBFunctions::escapeAs(inet_pton($arr_ip[1]), DBFunctions::TYPE_BINARY) : "''").", '".DBFunctions::strEscape($url)."', '".DBFunctions::strEscape($referral_url)."')
			");
			
			self::$log_user_id = DB::lastInsertID();
			
			DB::setConnection();
		}
		
		return self::$log_user_id;
	}
	
	public static function getWhereUserDB() {
	
		if (!empty($_SESSION['USER_ID'])) {
			
			$where = "user_class = ".($_SESSION['USER_GROUP'] ? 3 : ($_SESSION['CORE'] ? 1 : 2));
		} else {
			
			$arr_ip = self::getIP();
			$where = "(ip = ".DBFunctions::escapeAs(inet_pton($arr_ip[0]), DBFunctions::TYPE_BINARY)."".($arr_ip[1] ? " OR ip_proxy = ".DBFunctions::escapeAs(inet_pton($arr_ip[1]), DBFunctions::TYPE_BINARY)."" : "").")";
		}
		
		return $where;
	}
	
	public static function getIP() {
		
		if (!self::$ip) {
				
			$ip = $_SERVER['REMOTE_ADDR'];
			$ip_proxy = false;
			
			if ($_SERVER['HTTP_CLIENT_IP'] && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
				$ip_proxy = $ip;
				$ip = trim($_SERVER['HTTP_CLIENT_IP']);
			} else if ($_SERVER['HTTP_X_FORWARDED_FOR']) {
				$ip_proxy = $ip;
				if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') > 0) {
					$arr_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
					$ip = trim($arr_ip[0]);
				} else {
					$ip = trim($_SERVER['HTTP_X_FORWARDED_FOR']);
				}
			}
			
			self::$ip = $ip;
			self::$ip_proxy = $ip_proxy;
		}
					
		return [self::$ip, self::$ip_proxy];
	}
	
	public static function parseIPRequest() {
		
		if (self::$ip_request) {
			return;
		}
		
		self::$ip_request = inet_pton($_SERVER['REMOTE_ADDR']); // inet_pton can handle both IPv4 and IPv6 addresses, treat IPv6 addresses as /64 or /56 blocks.
		
		if (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
			self::$ip_request_block = substr(self::$ip_request, 0, 8)."\x00\x00\x00\x00\x00\x00\x00\x00"; // Turn 128 bit IPv6 address into 64 bit
		} else {
			self::$ip_request_block = substr(self::$ip_request, 0, 3)."\x00";  // Turn 32 bit IPv4 address into 24 bit
		}
	}
	
	public static function checkRequestThrottle($num_window = 30 * 60, $num_per_second = 1) { // Seconds
		
		// Throttle using a rolling window, stored in milliseconds to calculate proper short-term 'heat'
		
		self::parseIPRequest();
		
		$sql_ip = DBFunctions::escapeAs(self::$ip_request, DBFunctions::TYPE_BINARY);
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
				
				$str_host = gethostbyaddr(inet_ntop(self::$ip_request));
				
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
		
		$sql_ip = DBFunctions::escapeAs(self::$ip_request, DBFunctions::TYPE_BINARY);
	
		$res = DB::query("
			UPDATE ".DB::getTable('TABLE_LOG_REQUESTS_THROTTLE')." SET
				state = ".$num_state."
			WHERE ip = ".$sql_ip."
		");
	}
	
	public static function checkRequest($type, $identifier, $num_interval, $arr_count) {
		
		self::parseIPRequest();
		
		$res = DB::query("SELECT
			COUNT(*) AS count_global,
			SUM(CASE WHEN lr.ip_block = ".DBFunctions::escapeAs(self::$ip_request_block, DBFunctions::TYPE_BINARY)." THEN 1 ELSE 0 END) AS count_ip_block,
			SUM(CASE WHEN lr.ip = ".DBFunctions::escapeAs(self::$ip_request, DBFunctions::TYPE_BINARY)." THEN 1 ELSE 0 END) AS count_ip
			".($identifier !== false ? ", (SELECT COUNT(DISTINCT lr_i.ip_block)
				FROM ".DB::getTable('TABLE_LOG_REQUESTS_ACCESS')." lr_i
				WHERE lr_i.type = '".$type."'
					AND lr_i.date >= (NOW() - ".DBFunctions::interval((int)$num_interval, 'SECOND').")
					AND lr_i.identifier = '".DBFunctions::strEscape($identifier)."'
			) AS count_identifier" : "")."
				FROM ".DB::getTable('TABLE_LOG_REQUESTS_ACCESS')." lr
			WHERE lr.type = '".$type."'
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
	
	public static function logRequest($type, $identifier = false) {
		
		self::parseIPRequest();
		
		$res = DB::query("INSERT INTO ".DB::getTable('TABLE_LOG_REQUESTS_ACCESS')."
			(type, identifier, ip, ip_block, date)
				VALUES
			('".$type."', ".($identifier !== false ? "'".DBFunctions::strEscape($identifier)."'" : "NULL").", ".DBFunctions::escapeAs(self::$ip_request, DBFunctions::TYPE_BINARY).", ".DBFunctions::escapeAs(self::$ip_request_block, DBFunctions::TYPE_BINARY).", NOW())
		");
	}
}
