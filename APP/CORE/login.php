<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class HomeLogin {
	
	// Index
	
	private static $username = null;
	private static $password = null;
	
	private static $user_group = null;
	private static $arr_directory = null;
	
	public static function index() {
		
		unset($_SESSION['USER_GROUP'], $_SESSION['USER_ID'], $_SESSION['CUR_USER'], $_SESSION['IDENTIFIER']);
		
		static::$user_group = SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_USER_GROUP);
		static::$arr_directory = SiteStartEnvironment::getDirectory(false, SiteStartEnvironment::DIRECTORY_LOGIN);
		
		if (SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_PAGE_KIND) == '.l' && SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_PAGE_NAME) == 'logout') {
			
			self::doLogout();
			return;
		} else if (!empty($_SESSION['STORE_'.static::$user_group]['USER_ID']) && static::$username === null && static::$password === null) {
			
			self::updateLogin();
			return;
		} else if (static::$user_group && static::$username !== null && static::$password !== null) {
			
			self::checkLogin();
			return;
		}
		
		if (static::$user_group && static::$arr_directory['require_login']) {
			self::toLogin();
		}
	}
	
	public static function indexProposeUser(string $username, ?string $password, ?array $arr_directory = null, $no_password = false) {
		
		static::$username = Log::parseRequestIdentifier($username);
		static::$password = ($no_password && $password === null ? false : $password);
		
		if ($arr_directory) {
			
			SiteStartEnvironment::setContext(SiteStartEnvironment::CONTEXT_USER_GROUP, $arr_directory['user_group_id']);
			SiteStartEnvironment::setDirectory($arr_directory, SiteStartEnvironment::DIRECTORY_LOGIN);
		}

		static::index();
	}
	
	public static function indexSetDestination($str_url, $do_force = true) {
		
		if (!$do_force && isset($_SESSION['RETURN_TO'])) {
			return;
		}
		
		$_SESSION['RETURN_TO'] = $str_url;
	}

	private static function toLogin($is_error = false) {
		
		$arr_page = pages::getPages(static::$arr_directory['page_fallback_id']);

		if (!$is_error) {
		
			if (static::$arr_directory['page_fallback_id'] == SiteStartEnvironment::getPage('id')) {
				return;
			}
			
			if (static::$arr_directory['page_index_id'] != SiteStartEnvironment::getPage('id')) {
				self::indexSetDestination(SiteStartEnvironment::getRequestURL(), false);
			}
		}
		
		$str_path_directory = str_replace(' ', '', static::$arr_directory['path']);
		
		Response::location($str_path_directory.'/'.$arr_page['name'].'.p'.($is_error ? '/LOGIN_INCORRECT' : ''));
	}
		
	private static function updateLogin() {
		
		$_SESSION['CUR_USER'] = user_groups::getUserData($_SESSION['STORE_'.static::$user_group]['USER_ID'], true);
		
		if (!$_SESSION['CUR_USER']) {
			
			self::toLogin();
			return;
		}
		
		$arr_user = $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')];
		
		if (!$arr_user['enabled'] || $arr_user['login_identifier'] !== $_SESSION['STORE_'.static::$user_group]['IDENTIFIER']) {
			
			self::toLogin();
			return;
		}
			
		$_SESSION['USER_ID'] = $_SESSION['STORE_'.static::$user_group]['USER_ID'];
		$_SESSION['USER_GROUP'] = static::$user_group;
		$_SESSION['IDENTIFIER'] = $_SESSION['STORE_'.static::$user_group]['IDENTIFIER'];
		
		if (strtotime($arr_user['login_date']) < strtotime('-1 day')) {
			
			$arr_ip = Log::getIP();
			
			DB::setConnection(DB::CONNECT_CMS);
			
			$res = DB::query("UPDATE ".DB::getTable('TABLE_USERS')." SET 
					login_date = ".DBFunctions::dateTimeNow().",
					login_ip = ".($arr_ip ? DBFunctions::escapeAs(inet_pton($arr_ip[0]), DBFunctions::TYPE_BINARY) : "''").",
					login_ip_proxy = ".($arr_ip && $arr_ip[1] ? DBFunctions::escapeAs(inet_pton($arr_ip[1]), DBFunctions::TYPE_BINARY) : "''")."
				WHERE id = ".(int)$_SESSION['USER_ID']
			);
			
			DB::setConnection();
			
			message(user_management::getUserTag($_SESSION['USER_ID']), 'LOGIN+', LOG_SYSTEM);
		}
	}
		
	private static function checkLogin() {
	
		SiteStartEnvironment::checkCookieSupport();
		
		$check = Log::checkRequest('login_home', static::$username, 10, ['identifier' => 4, 'ip' => 5, 'ip_block' => 10, 'global' => 300]);
		
		if ($check !== true) {
			error(getLabel('msg_access_limit'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
						
		$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_USERS')."
			WHERE enabled = TRUE
				AND uname = '".DBFunctions::strEscape(static::$username)."'
				AND group_id = ".(int)static::$user_group."
		");
		
		$passhash = false;
		
		if ($res->getRowCount()) {
			
			$arr_user = $res->fetchAssoc();
			
			if (static::$password === false) {
				$passhash = $arr_user['passhash'];
			} else {
				$passhash = checkHash(static::$password, $arr_user['passhash']);
			}
		}
		
		if ($passhash !== false) {
			
			$_SESSION['CUR_USER'] = user_groups::getUserData($arr_user['id'], true);
			$_SESSION['USER_GROUP'] = static::$user_group;
			$_SESSION['USER_ID'] = (int)$arr_user['id'];
			$_SESSION['STORE_'.static::$user_group]['USER_ID'] = $_SESSION['USER_ID'];
			
			$_SESSION['IDENTIFIER'] = ($arr_user['login_identifier'] ?? generateRandomString(10));
			$_SESSION['STORE_'.static::$user_group]['IDENTIFIER'] = $_SESSION['IDENTIFIER'];
			$arr_ip = Log::getIP();
			
			DB::setConnection(DB::CONNECT_CMS);
			
			$res = DB::query("UPDATE ".DB::getTable('TABLE_USERS')." SET 
					login_date = ".DBFunctions::dateTimeNow().",
					login_identifier = '".DBFunctions::strEscape($_SESSION['IDENTIFIER'])."',
					login_ip = ".($arr_ip ? DBFunctions::escapeAs(inet_pton($arr_ip[0]), DBFunctions::TYPE_BINARY) : "''").",
					login_ip_proxy = ".($arr_ip && $arr_ip[1] ? DBFunctions::escapeAs(inet_pton($arr_ip[1]), DBFunctions::TYPE_BINARY) : "''")."
					".($passhash !== $arr_user['passhash'] ? ", passhash = '".DBFunctions::strEscape($passhash)."'" : "")."
				WHERE id = ".(int)$_SESSION['USER_ID']
			);
			
			DB::setConnection();
			
			message(user_management::getUserTag($_SESSION['USER_ID']), 'LOGIN', LOG_SYSTEM);
			
			if ($_SESSION['RETURN_TO']) {
				
				$str_url = $_SESSION['RETURN_TO'];
				unset($_SESSION['RETURN_TO']);
				
				Response::location($str_url);
			}
			
			return;
		}
		
		Log::logRequest('login_home', static::$username);
			
		self::toLogin(true);
	}
	
	private static function doLogout() {
		
		if (isset($_SESSION['STORE_'.static::$user_group]['USER_ID'])) {
			
			DB::setConnection(DB::CONNECT_CMS);
			
			$res = DB::query("UPDATE ".DB::getTable('TABLE_USERS')." SET 
					login_identifier = NULL
				WHERE id = ".(int)$_SESSION['STORE_'.static::$user_group]['USER_ID']
			);
			
			DB::setConnection();
		}
		
		//SiteStartEnvironment::terminateSession();
		unset($_SESSION['STORE_'.static::$user_group]);
		
		if ($_SESSION['RETURN_TO']) {
			
			$str_url = $_SESSION['RETURN_TO'];
			unset($_SESSION['RETURN_TO']);
		} else {
			
			$str_url = SiteStartEnvironment::getBasePath(1, false);
		}
		
		Response::location($str_url);
	}
	
	// API
	
	public static function API($str_token) {
		
		unset($_SESSION['USER_GROUP'], $_SESSION['USER_ID'], $_SESSION['CUR_USER'], $_SESSION['IDENTIFIER']);
		
		$str_token = Log::parseRequestIdentifier($str_token);
		
		$check = Log::checkRequest('login_api_home', $str_token, 10, ['identifier' => 4, 'ip' => 10, 'ip_block' => 20, 'global' => 300]);
		
		if ($check !== true) {
			error(getLabel('msg_access_limit'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
		
		$arr_api_client_user = apis::getClientUserByToken($str_token);
				
		if ($arr_api_client_user && $arr_api_client_user['api_id'] == SiteStartEnvironment::getAPI('id')) {
			
			// Regenerate token when provided unsecure
			if (SERVER_SCHEME != URI_SCHEME_HTTPS) {
				
				apis::handleClientUser($arr_api_client_user['client_id'], $arr_api_client_user['user_id'], $arr_api_client_user['enabled'], false, true);
				
				error(getLabel('msg_access_denied').' '.getLabel('msg_request_invalid_authentication_unsecure'), TROUBLE_INVALID_REQUEST, LOG_CLIENT);
			}

			if ($arr_api_client_user['client_enabled'] && $arr_api_client_user['enabled']) {
			
				if (!$arr_api_client_user['date_valid'] || strtotime($arr_api_client_user['date_valid']) > time()) {
				
					$is_enabled = self::checkUser($arr_api_client_user['user_id']);
					
					if (!$is_enabled) {
						error(getLabel('msg_access_denied'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
					}
					
					return $arr_api_client_user;
				} else {
				
					error(getLabel('msg_access_denied').' '.getLabel('msg_request_invalid_authentication_expired'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
				}
			} else {
				
				error(getLabel('msg_access_denied'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
			}
		} else {
			
			Log::logRequest('login_api_home', $str_token);
			
			error(getLabel('msg_access_denied'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
		
		return false;
	}
	
	private static function checkUser($user_id) {
	
		$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_USERS')."
			WHERE enabled = TRUE
				AND id = ".(int)$user_id."
		");

		if ($res->getRowCount()) {
			
			$arr_user = $res->fetchAssoc();
			
			$_SESSION['CUR_USER'] = user_groups::getUserData($arr_user['id'], true);
			$_SESSION['USER_GROUP'] = (int)$arr_user['group_id'];
			$_SESSION['USER_ID'] = (int)$arr_user['id'];
			$_SESSION['IDENTIFIER'] = $arr_user['login_identifier'];
			
			return true;
		}
			
		return false;
	}
}
