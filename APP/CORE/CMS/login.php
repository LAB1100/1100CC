<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class CMSLogin {
	
	private static $username = null;
	private static $password = null;
	
	public static function index() {
		
		unset($_SESSION['CUR_USER']);
		
		$str_page = (SiteStartEnvironment::getRequestVariables(1) ?: '');
		
		if ($str_page == 'logout') {
			
			self::doLogout();
			return;
		} else if (!empty($_SESSION['USER_ID']) && static::$username === null && static::$password === null) {

			self::updateLogin();
			return;
		} else if (static::$username !== null && static::$password !== null) {		
		
			self::checkLogin();
			return;
		}
		
		self::toLogin();
	}
	
	public static function indexProposeUser(string $username, string $password) {
		
		static::$username = Log::parseRequestIdentifier($username);
		static::$password = $password;
		
		static::index();
	}
	
	public static function indexSetDestination($str_url, $do_force = true) {
		
		if (!$do_force && isset($_SESSION['RETURN_TO'])) {
			return;
		}
		
		$_SESSION['RETURN_TO'] = $str_url;
	}
		
	private static function toLogin($is_error = false) {
		
		unset($_SESSION['USER_ID'], $_SESSION['CORE'], $_SESSION['IDENTIFIER']);
	
		if (!$is_error) {
			
			if (SiteStartEnvironment::getRequestURL() != '/' && SiteStartEnvironment::getRequestURL() != '/login/') {
				self::indexSetDestination(SiteStartEnvironment::getRequestURL(), false);
			}
		}
		
		Response::location('/login/'.($is_error ? 'LOGIN_INCORRECT' : ''));
	}
	
	private static function updateLogin() {
		
		$arr_user = cms_users::getCMSUsers($_SESSION['USER_ID'], $_SESSION['CORE']);
		
		if (!$arr_user || $arr_user['login_identifier'] !== $_SESSION['IDENTIFIER']) {
			
			self::toLogin();
			return;
		}
		
		$_SESSION['CUR_USER'] = $arr_user;
		
		if (strtotime($_SESSION['CUR_USER']['login_date']) < strtotime('-1 day')) {
				
			$arr_ip = Log::getIP();
			
			DB::setConnection(DB::CONNECT_CMS);

			$res = DB::query("UPDATE ".DB::getTable(($_SESSION['CORE'] ? 'TABLE_CORE_USERS' : 'TABLE_CMS_USERS'))." SET 
					login_date = ".DBFunctions::dateTimeNow().",
					login_ip = ".($arr_ip ? DBFunctions::escapeAs(inet_pton($arr_ip[0]), DBFunctions::TYPE_BINARY) : "''").",
					login_ip_proxy = ".($arr_ip && $arr_ip[1] ? DBFunctions::escapeAs(inet_pton($arr_ip[1]), DBFunctions::TYPE_BINARY) : "''")."
				WHERE id = ".(int)$_SESSION['USER_ID']
			);
			
			DB::setConnection();
		}
	}
	
	private static function checkLogin() {
	
		SiteStartEnvironment::checkCookieSupport();
		
		$check = Log::checkRequest('login_cms', static::$username, 10, ['identifier' => 2, 'ip' => 2, 'ip_block' => 2, 'global' => 50]);
		
		if ($check !== true) {
			error(getLabel('msg_access_limit'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
		
		if (Settings::get('setup_core_user')) {
			
			$res = DB::query("SELECT TRUE FROM ".DB::getTable('TABLE_CORE_USERS')." LIMIT 1");
			
			if (!$res->getRowCount() && static::$username && static::$password) {
				
				DB::setConnection(DB::CONNECT_CMS);
				
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_CORE_USERS')."
					(name, uname, lang_code, email, img, biography, passhash, labeler)
						VALUES
					('CORE Admin', '".DBFunctions::strEscape(static::$username)."', '', '', '', '', '".DBFunctions::strEscape(generateHash(static::$password))."', TRUE)
				");
				
				DB::setConnection();
			}
		}
				
		$arr_user = cms_users::getCMSUserByUsername(static::$username);
		
		if (!$arr_user) {
			$arr_user = cms_users::getCMSUserByUsername(static::$username, true);
		}
		
		$passhash = false;
		
		if ($arr_user) {
			$passhash = checkHash(static::$password, $arr_user['passhash']);
		}
		
		if ($passhash !== false) {
			
			$_SESSION['CUR_USER'] = $arr_user;
			$_SESSION['USER_ID'] = (int)$_SESSION['CUR_USER']['id'];
			$_SESSION['CORE'] = $arr_user['core'];
			
			$_SESSION['IDENTIFIER'] = ($arr_user['login_identifier'] ?? generateRandomString(10));
			$arr_ip = Log::getIP();
			
			DB::setConnection(DB::CONNECT_CMS);

			$res = DB::query("UPDATE ".DB::getTable(($_SESSION['CORE'] ? 'TABLE_CORE_USERS' : 'TABLE_CMS_USERS'))." SET 
					login_date = ".DBFunctions::dateTimeNow().",
					login_identifier = '".DBFunctions::strEscape($_SESSION['IDENTIFIER'])."',
					login_ip = ".($arr_ip ? DBFunctions::escapeAs(inet_pton($arr_ip[0]), DBFunctions::TYPE_BINARY) : "''").",
					login_ip_proxy = ".($arr_ip && $arr_ip[1] ? DBFunctions::escapeAs(inet_pton($arr_ip[1]), DBFunctions::TYPE_BINARY) : "''")."
					".($passhash !== $arr_user['passhash'] ? ", passhash = '".DBFunctions::strEscape($passhash)."'" : "")."
				WHERE id = ".(int)$_SESSION['USER_ID']
			);
			
			DB::setConnection();
			
			if (isset($_SESSION['RETURN_TO'])) {
				
				$str_url = $_SESSION['RETURN_TO'];
				unset($_SESSION['RETURN_TO']);
				
				Response::location($str_url);
			}
			
			return;
		}
		
		Log::logRequest('login_cms', static::$username);
		
		self::toLogin(true);
	}
	
	private static function doLogout() {
		
		if (isset($_SESSION['USER_ID'])) {
			
			DB::setConnection(DB::CONNECT_CMS);

			$res = DB::query("UPDATE ".DB::getTable(($_SESSION['CORE'] ? 'TABLE_CORE_USERS' : 'TABLE_CMS_USERS'))." SET 
					login_identifier = NULL
				WHERE id = ".(int)$_SESSION['USER_ID']
			);
			
			DB::setConnection();
		}
		
		SiteStartEnvironment::terminateSession(true);
		
		if (isset($_SESSION['RETURN_TO'])) {
			
			$str_url = $_SESSION['RETURN_TO'];
			unset($_SESSION['RETURN_TO']);
			
			Response::location($str_url);
		} else {
			
			$str_url = URL_BASE.'/';
		}
		
		Response::location($str_url);
	}
}
