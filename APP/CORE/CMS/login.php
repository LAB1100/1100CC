<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class CMSLogin {
	
	static public function index() {
		
		if (SiteStartVars::$cms_vars[1] == 'logout') {
			
			$_SESSION = [];
			session_destroy();
			
			Response::location(BASE_URL.'/');
		} else if ($_SESSION['USER_ID'] && !isset($_POST['login_ww']) && !isset($_POST['login_user'])) {

			self::updateLogin();
		} else if (isset($_POST['login_user']) && isset($_POST['login_ww'])) {		
		
			self::checkLogin($_POST['login_user'], $_POST['login_ww']);
		} else {
		
			self::toLogin();
		}
	}
		
	static private function toLogin($error = false) {
	
		if (!$error) {
			$_SESSION['RETURN_TO'] = ($_SERVER['PATH_VIRTUAL'] ?: $_SERVER['PATH_INFO']);
		}
		
		Response::location('/login/'.($error ? 'LOGIN_INCORRECT' : ''));
	}
	
	static private function updateLogin() {
		
		$arr_user = cms_users::getCMSUsers($_SESSION['USER_ID'], $_SESSION['CORE']);
		
		if ($arr_user) {
			$_SESSION['CUR_USER'] = $arr_user;
		} else {
			self::toLogin();
		}
	}
	
	static private function checkLogin($username, $password) {
	
		SiteStartVars::checkCookieSupport();
				
		$check = Log::checkRequest('login_cms', $username, 10, ['identifier' => 2, 'ip' => 4, 'ip_block' => 4, 'global' => 50]);
		
		if ($check !== true) {
			error(getLabel('msg_access_limit'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
		
		$res = DB::query("SELECT TRUE FROM ".DB::getTable('TABLE_CORE_USERS')." LIMIT 1");
		
		if (!$res->getRowCount() && $username && $password) {
						
			DB::setConnection(DB::CONNECT_CMS);
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_CORE_USERS')."
				(name, uname, lang_code, email, img, biography, passhash, labeler)
					VALUES
				('CORE Admin', '".DBFunctions::strEscape($username)."', '', '', '', '', '".DBFunctions::strEscape(generateHash($password))."', TRUE)
			");
			
			DB::setConnection();
		}
				
		$arr_user = cms_users::getCMSUserByUsername($username);
		
		if (!$arr_user) {
			
			$arr_user = cms_users::getCMSUserByUsername($username, true);
		}
		
		$passhash = false;
		
		if ($arr_user) {
			
			$passhash = checkHash($password, $arr_user['passhash']);
		}
		
		if ($passhash !== false) {
			
			$_SESSION['CUR_USER'] = $arr_user;
			$_SESSION['USER_ID'] = $_SESSION['CUR_USER']['id'];
			$_SESSION['CORE'] = $arr_user['core'];
			$arr_ip = Log::getIP();
			
			DB::setConnection(DB::CONNECT_CMS);

			$res = DB::query("UPDATE ".DB::getTable(($_SESSION['CORE'] ? 'TABLE_CORE_USERS' : 'TABLE_CMS_USERS'))." SET 
					last_login = NOW(),
					ip = ".($arr_ip ? DBFunctions::escapeAs(inet_pton($arr_ip[0]), DBFunctions::TYPE_BINARY) : "''").",
					ip_proxy = ".($arr_ip && $arr_ip[1] ? DBFunctions::escapeAs(inet_pton($arr_ip[1]), DBFunctions::TYPE_BINARY) : "''")."
					".($passhash !== $arr_user['passhash'] ? ", passhash = '".DBFunctions::strEscape($passhash)."'" : "")."
				WHERE id = ".(int)$_SESSION['USER_ID']
			);
			
			DB::setConnection();
			
			if ($_SESSION['RETURN_TO']) {
				
				$url = $_SESSION['RETURN_TO'];
				unset($_SESSION['RETURN_TO']);
				
				Response::location($url);
			}
		} else {
			
			Log::logRequest('login_cms', $username);
			
			self::toLogin(true);	
		}
	}
}
