<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class HomeLogin {
	
	public static function index() {
		
		unset($_SESSION['USER_GROUP'], $_SESSION['USER_ID'], $_SESSION['CUR_USER']);
		
		if (SiteStartVars::$page_kind == '.l' && SiteStartVars::$page_name == 'logout') {

			unset($_SESSION['STORE_'.SiteStartVars::$user_group]['USER_ID']);
			
			Response::location(SiteStartVars::getBasePath(1, false));
		} else if (!empty($_SESSION['STORE_'.SiteStartVars::$user_group]['USER_ID']) && !isset($_POST['login_user']) && !isset($_POST['login_ww'])) {
					
			self::updateLogin();
		} else if (SiteStartVars::$user_group && isset($_POST['login_user']) && isset($_POST['login_ww'])) {
		
			self::checkLogin($_POST['login_user'], $_POST['login_ww']);
		} else if (SiteStartVars::$user_group && SiteStartVars::$login_dir['require_login']) {
			
			self::toLogin();
		}
	}
	
	public static function API($token) {
		
		unset($_SESSION['USER_GROUP'], $_SESSION['USER_ID'], $_SESSION['CUR_USER']);
		
		$check = Log::checkRequest('login_api_home', $token, 10, ['identifier' => 4, 'ip' => 20, 'ip_block' => 40, 'global' => 300]);
		
		if ($check !== true) {
			error(getLabel('msg_access_limit'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
		
		$arr_api_client_user = apis::getClientUserByToken($token);
				
		if ($arr_api_client_user && $arr_api_client_user['api_id'] == SiteStartVars::$api['id']) {
			
			// Regenerate token when provided unsecure
			if (SERVER_SCHEME != 'https://') {
				
				apis::handleClientUser($arr_api_client_user['client_id'], $arr_api_client_user['user_id'], $arr_api_client_user['enabled'], false, true);
				
				error(getLabel('msg_access_denied').' The token was sent over an unencrypted connection. The token has been reset.', TROUBLE_INVALID_REQUEST, LOG_CLIENT);
			}

			if ($arr_api_client_user['client_enabled'] && $arr_api_client_user['enabled']) {
			
				if (!$arr_api_client_user['date_valid'] || strtotime($arr_api_client_user['date_valid']) > time()) {
				
					$user = self::checkUser($arr_api_client_user['user_id']);
					
					return $arr_api_client_user;
				} else {
				
					error(getLabel('msg_access_denied').' The token is expired.', TROUBLE_ACCESS_DENIED, LOG_CLIENT);
				}
			} else {
				
				error(getLabel('msg_access_denied'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
			}
		} else {
			
			Log::logRequest('login_api_home', $token);
			
			error(getLabel('msg_access_denied'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
		
		return false;
	}
			
	private static function toLogin($error = false) {
		
		$arr_page = pages::getPages(SiteStartVars::$login_dir['page_fallback_id']);

		if (SiteStartVars::$dir['path'] != SiteStartVars::$login_dir['path'] || SiteStartVars::$page['name'] != $arr_page['name']) {
		
			if (!$error) {
				$_SESSION['RETURN_TO'] = (!empty($_SERVER['PATH_VIRTUAL']) ? $_SERVER['PATH_VIRTUAL'] : $_SERVER['PATH_INFO']);
			}
			
			Response::location(URL_BASE.ltrim(SiteStartVars::$login_dir['path'], '/').(SiteStartVars::$login_dir['path'] ? '/' : '').$arr_page['name'].'.p'.($error ? '/LOGIN_INCORRECT' : ''));
		}
	}
		
	private static function updateLogin() {
		
		$_SESSION['CUR_USER'] = user_groups::getUserData($_SESSION['STORE_'.SiteStartVars::$user_group]['USER_ID'], true);
		
		if ($_SESSION['CUR_USER']) {
			
			$_SESSION['USER_ID'] = $_SESSION['STORE_'.SiteStartVars::$user_group]['USER_ID'];
			$_SESSION['USER_GROUP'] = SiteStartVars::$user_group;
			
			if (strtotime($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['last_login']) < strtotime('-1 day')) {
				
				$arr_ip = Log::getIP();
				
				DB::setConnection(DB::CONNECT_CMS);
				
				$res = DB::query("UPDATE ".DB::getTable('TABLE_USERS')."
				SET 
					last_login = NOW(),
						ip = ".($arr_ip ? DBFunctions::escapeAs(inet_pton($arr_ip[0]), DBFunctions::TYPE_BINARY) : "''").",
						ip_proxy = ".($arr_ip && $arr_ip[1] ? DBFunctions::escapeAs(inet_pton($arr_ip[1]), DBFunctions::TYPE_BINARY) : "''")."
					WHERE id = ".(int)$_SESSION['USER_ID']
				);
				
				DB::setConnection();
				
				msg(user_management::getUserTag($_SESSION['USER_ID']), 'LOGIN+', LOG_SYSTEM);
			}
		} else {
			
			self::toLogin();
		}
	}
		
	private static function checkLogin($username, $password) {
	
		SiteStartVars::checkCookieSupport();
		
		$username = (is_string($username) ? $username : '');
		$password = (is_string($password) ? $password : '');
		
		$check = Log::checkRequest('login_home', $username, 10, ['identifier' => 5, 'ip' => 25, 'ip_block' => 100, 'global' => 300]);
		
		if ($check !== true) {
			error(getLabel('msg_access_limit'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
						
		$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_USERS')."
			WHERE enabled = TRUE
				AND uname = '".DBFunctions::strEscape($username)."'
				AND group_id = ".SiteStartVars::$user_group."
		");
		
		$passhash = false;
		
		if ($res->getRowCount()) {
			
			$arr_user = $res->fetchAssoc();
			
			$passhash = checkHash($password, $arr_user['passhash']);
		}
		
		if ($passhash !== false) {
			
			$_SESSION['CUR_USER'] = user_groups::getUserData($arr_user['id'], true);
			$_SESSION['USER_GROUP'] = SiteStartVars::$user_group;
			$_SESSION['USER_ID'] = $arr_user['id'];
			$_SESSION['STORE_'.SiteStartVars::$user_group]['USER_ID'] = $_SESSION['USER_ID'];
			$arr_ip = Log::getIP();
			
			DB::setConnection(DB::CONNECT_CMS);
			
			$res = DB::query("UPDATE ".DB::getTable('TABLE_USERS')."
				SET 
					last_login = NOW(),
					ip = ".($arr_ip ? DBFunctions::escapeAs(inet_pton($arr_ip[0]), DBFunctions::TYPE_BINARY) : "''").",
					ip_proxy = ".($arr_ip && $arr_ip[1] ? DBFunctions::escapeAs(inet_pton($arr_ip[1]), DBFunctions::TYPE_BINARY) : "''")."
					".($passhash !== $arr_user['passhash'] ? ", passhash = '".DBFunctions::strEscape($passhash)."'" : "")."
				WHERE id = ".(int)$_SESSION['USER_ID']
			);
			
			DB::setConnection();
			
			msg(user_management::getUserTag($_SESSION['USER_ID']), 'LOGIN', LOG_SYSTEM);
			
			if ($_SESSION['RETURN_TO']) {
				
				$url = $_SESSION['RETURN_TO'];
				unset($_SESSION['RETURN_TO']);
				
				Response::location($url);
			}
		} else {
			
			Log::logRequest('login_home', $username);
			
			self::toLogin(true);	
		}
	}
	
	private static function checkUser($user_id) {
	
		$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_USERS')."
			WHERE enabled = TRUE
				AND id = ".(int)$user_id."
		");

		if ($res->getRowCount()) {
			
			$arr_user = $res->fetchAssoc();
			
			$_SESSION['CUR_USER'] = user_groups::getUserData($arr_user['id'], true);
			$_SESSION['USER_GROUP'] = $arr_user['group_id'];
			$_SESSION['USER_ID'] = $arr_user['id'];
			
			return true;
		} else {
			
			return false;
		}
	}
}
