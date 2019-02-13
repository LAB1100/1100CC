<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class Log {
	
	private static $msg = false;
	private static $arr_msg = [];
	
	private static $ip = false;
	private static $ip_proxy = false;
	private static $ip_request = false;
	private static $ip_request_block = false;
	private static $log_user_id = false;

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
		
		if (SiteStartVars::getRequestState() == 'api') {
			
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
			
			foreach (self::$arr_msg as $value) {
				
				$type = $value[4];
				$arr_options = $value[5];
				
				if ($value[2] != LOG_BOTH && $value[2] != LOG_CLIENT) { // Suppressed?
					continue;
				}
				
				$label = (is_array($value[1]) ? $value[1][0] : $value[1]);
				
				$arr_msgs[] = '<label>'.$label.'</label><span>'.$value[0].'</span>';
			}

			if ($arr_msgs || self::$msg) {
				
				$msg = '<label></label><span>'.(!self::$msg && $arr_msgs ? 'Report' : self::$msg).'</span>';
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
			
			if ($arr_options['clear'] === null) {
				$arr_options['clear'] = ['identifier' => SiteStartVars::getSessionId(true)];
			}
			
			$JSON->msg_options = $arr_options;
		}
		
		return $JSON;
	}
					
	public static function addToDB() {
		
		if (!self::$arr_msg) {
			return;
		}

		// Try logging to database when enabled
		try {
			
			if (DB::isActive()) {
				
				if (getLabel('logging', 'D', true)) {
				
					self::addToDBSQL();
				}
				
				return;
			}
		} catch (Exception $e) {
			
			Trouble::catchError($e);
		}
		
		// Try logging to disk when database is unavailable
		try {
			
			self::addToDBFile();
		} catch (Exception $e) {
			
			Trouble::catchError($e);
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
		
		$path = DIR_ROOT_STORAGE.DIR_HOME.DIR_CMS.DIR_PRIVATE;
		FileStore::makeDirectoryTree($path);
		$path .= 'log';
		
		$file = fopen($path, 'a');
		FileStore::setFilePermission($path);
		
		if (flock($file, LOCK_EX)) {
			
			foreach (self::$arr_msg as $value) {
				
				if ($value[2] == LOG_BOTH || $value[2] == LOG_SYSTEM) { // Suppressed?
					
					$label = (is_array($value[1]) ? $value[1][0] : $value[1]);
					
					fwrite($file, PHP_EOL.PHP_EOL
						.str_pad('', 6, '#')
						.PHP_EOL.$label.': '.$value[0]
						.PHP_EOL.$value[4].' at '.date('d-m-Y H:i:s').' by '.$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['name']
						.($value[3] ? PHP_EOL.PHP_EOL.$value[3] : '')
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
		
			if ($_SESSION['USER_ID']) {
				
				$sql_column_user = ($_SESSION['USER_GROUP'] ? 'user_id' : 'cms_user_id');
				$sql_user = $_SESSION['USER_ID'];
			}
			
			if (SiteStartVars::isProcess()) {
				
				$arr_ip = false;
				$url = BASE_URL;
				$referral_url = '';
			} else {
				
				$arr_ip = self::getIP();
				$url = BASE_URL.ltrim(($_SERVER['PATH_VIRTUAL'] ? $_SERVER['PATH_VIRTUAL'].' (V)' : $_SERVER['PATH_INFO']), '/');
				$referral_url = $_SESSION['REFERER_URL'];
			}
			
			DB::setConnection(DB::CONNECT_CMS);
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_LOG_USERS')."
				(".($sql_column_user ? $sql_column_user.", " : "")."ip, ip_proxy, url, referral_url)
					VALUES
				(".($sql_user ? $sql_user.", " : "")."".($arr_ip ? DBFunctions::escapeAs(inet_pton($arr_ip[0]), DBFunctions::TYPE_BINARY) : "''").", ".($arr_ip && $arr_ip[1] ? DBFunctions::escapeAs(inet_pton($arr_ip[1]), DBFunctions::TYPE_BINARY) : "''").", '".DBFunctions::strEscape($url)."', '".DBFunctions::strEscape($referral_url)."')
			");
			
			self::$log_user_id = DB::lastInsertID();
			
			DB::setConnection();
		}
		
		return self::$log_user_id;
	}
	
	public static function getWhereUserDB() {
	
		if ($_SESSION['USER_ID']) {
			
			$where = "user_id = ".($_SESSION['USER_GROUP'] ? 'user_id' : 'cms_user_id');
		} else {
			
			$arr_ip = self::getIP();
			$where = "(ip = ".DBFunctions::escapeAs(inet_pton($arr_ip[0]), DBFunctions::TYPE_BINARY)."".($arr_ip[1] ? " OR ip_proxy = ".DBFunctions::escapeAs(inet_pton($arr_ip[1]), DBFunctions::TYPE_BINARY)."" : "").")";
		}
		
		return $where;
	}
	
	public static function getIP() {
		
		if (!self::$ip) {
				
			$ip = $_SERVER['REMOTE_ADDR'];
			
			if ($_SERVER['HTTP_CLIENT_IP'] && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
				$proxy = $ip;
				$ip = trim($_SERVER['HTTP_CLIENT_IP']);
			} else if ($_SERVER['HTTP_X_FORWARDED_FOR']) {
				$proxy = $ip;
				if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') > 0) {
					$arr_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
					$ip = trim($arr_ip[0]);
				} else {
					$ip = trim($_SERVER['HTTP_X_FORWARDED_FOR']);
				}
			}
			
			self::$ip = $ip;
			self::$ip_proxy = $proxy;
		}
					
		return [self::$ip, self::$ip_proxy];
	}
	
	public static function getIPRequest() {
		
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
	
	public static function checkRequest($type, $identifier, $interval, $arr_count) {
		
		self::getIPRequest();
		
		$res = DB::query("SELECT
			COUNT(*) AS count_global,
			SUM(CASE WHEN lr.ip_block = ".DBFunctions::escapeAs(self::$ip_request_block, DBFunctions::TYPE_BINARY)." THEN 1 ELSE 0 END) AS count_ip_block,
			SUM(CASE WHEN lr.ip = ".DBFunctions::escapeAs(self::$ip_request, DBFunctions::TYPE_BINARY)." THEN 1 ELSE 0 END) AS count_ip
			".($identifier ? ", (SELECT COUNT(DISTINCT lr_i.ip_block)
				FROM ".DB::getTable('TABLE_LOG_REQUESTS')." lr_i
				WHERE lr_i.type = '".$type."'
					AND lr_i.date >= (NOW() - ".DBFunctions::interval((int)$interval, 'SECOND').")
					AND lr_i.identifier = '".DBFunctions::strEscape($identifier)."'
			) AS count_identifier" : "")."
				FROM ".DB::getTable('TABLE_LOG_REQUESTS')." lr
			WHERE lr.type = '".$type."'
				AND lr.date >= (NOW() - ".DBFunctions::interval((int)$interval, 'SECOND').")
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
		
		self::getIPRequest();
		
		$res = DB::query("INSERT INTO ".DB::getTable('TABLE_LOG_REQUESTS')."
			(type, identifier, ip, ip_block, date)
				VALUES
			('".$type."', ".($identifier ? "'".DBFunctions::strEscape($identifier)."'" : "NULL").", ".DBFunctions::escapeAs(self::$ip_request, DBFunctions::TYPE_BINARY).", ".DBFunctions::escapeAs(self::$ip_request_block, DBFunctions::TYPE_BINARY).", NOW())
		");
	}
}
