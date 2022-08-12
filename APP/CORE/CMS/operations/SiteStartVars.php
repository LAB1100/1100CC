<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class SiteStartVars {
	
	const REQUEST_INDEX = 1;
	const REQUEST_COMMAND = 2;
	const REQUEST_DOWNLOAD = 3;
	const REQUEST_API = 4;

	public static $js_css = [];
	
	public static $arr_cms_modules = [];
	public static $arr_cms_vars = [];
	
	public static $arr_request_vars = [];
	public static $arr_feedback = [];

	public static $arr_modules = [];
	public static $dir = false;
	public static $arr_dir = [];
	public static $login_dir = false;
	public static $user_group = false;
	public static $page = false;
	public static $page_name = false;
	public static $page_kind = false;
	public static $page_mod_xy = [];
	
	public static $uri_translator = false;
	public static $api = false;
	
	public static $language = false;
	public static $do_https = null;
	public static $do_secure = false;
	public static $session = false;
	public static $session_open = 0;
		
	public static function setPageVariables($arr_variables = false) {
		
		if ($arr_variables === false) {
			
			self::$arr_request_vars = [];
		} else {
	
			$cur_mod = 0;
			$cur_var_name = false;

			foreach ($arr_variables as $var) {
				
				$var = str_replace(['<', '>', '\'', '"', ' ', "\n", "\r"], '', $var); // Cleanup
				preg_match('%^(\d+)\.m$%', $var, $match);
				
				if ($match[1]) {
					$cur_mod = $match[1];
					$cur_var_name = false;
				} else {
					if (substr($var, -2) == '.v') { // check for '.v'
						$cur_var_name = substr($var, 0, -2);
					} else {
						if ($cur_var_name) {
							self::$arr_request_vars[$cur_mod][$cur_var_name][] = $var;
						} else {
							self::$arr_request_vars[$cur_mod][] = $var;
						}
					}
				}
			}
		}
		
		SiteEndVars::$arr_request_vars = self::$arr_request_vars;
	}
	
	public static function setRequestVariables($arr_variables = false) {
		
		if ($arr_variables === false) {
			unset(self::$arr_request_vars[0]);
		} else {
			self::$arr_request_vars[0] = $arr_variables;
		}
	}
	
	public static function getModVariables($mod_id) {
	
		return (self::$arr_request_vars[$mod_id] ?? []);
	}
	
	public static function setFeedback($data) {
		
		self::$arr_feedback = $data;
	}
	
	public static function getFeedback($variable) {
		
		return (self::$arr_feedback[$variable] ?? null);
	}

	public static function preloadModules() {
			
		foreach (self::$arr_modules as $class => $arr) {
			
			if (method_exists($class, 'modulePreload')) {
				$class::modulePreload();
			}
		}
		
		Mediator::runListeners('preload.modules');
	}
	
	public static function cooldownModules() {

		foreach (self::$arr_modules as $class => $arr) {
			if (method_exists($class, 'moduleCooldown')) {
				$class::moduleCooldown();
			}
		}
		
		cms_jobs::callJobs();
	}

	public static function requestSecure() {
		
		self::$do_secure = true;
		
		if (static::getRequestState() == static::REQUEST_INDEX) {
			
			Response::addHeaders('Content-Security-Policy: frame-ancestors \'self\'');
		}
	}
	
	public static function useHTTPS($use_request = true) {
		
		if (self::$do_https === null) {
			self::$do_https = (bool)getLabel('https', 'D', true);
		}
		
		return (self::$do_https && (!$use_request || (!SERVER_NAME_CUSTOM || self::$do_secure))); // Use https when explicitly requested or when no variable sub-domains are part of the request
	}
	
	public static function startSession() {
		
		if (self::$session_open != 0) {
			
			self::$session_open++;
			return;
		}
		
		if (self::$session) { // When reopening the session, do not send cookies

			$arr_session_options = ['use_only_cookies' => false, 'use_cookies' => false, 'use_trans_sid' => false, 'cache_limiter' => ''];
		} else {
			
			$is_secure = (SERVER_PROTOCOL == 'https://' ? true : false);

			$arr_session_options = [];
			$arr_cookie_options = ['lifetime' => 0, 'path' => (IS_CMS ? ini_get('session.cookie_path') : '/'), 'domain' => ini_get('session.cookie_domain'), 'httponly' => true, 'secure' => $is_secure, 'samesite' => ($is_secure ? 'None' : null)];
			
			session_set_cookie_params($arr_cookie_options);	
			
			self::$session = uniqid('', true); // As unique as possible over multiple/parallel threads
			session_name('1100CC_'.($is_secure ? 'secure' : 'open'));
		}
		
		try {
			session_start($arr_session_options);
		} catch (Exception $e) {
			// Reopening sessions is not really possible at the moment after sending output
		}
		
		self::$session_open = 1;
		
		$_SESSION['session'] = self::$session; // Set identifier to indicate last session request
		
		ignore_user_abort(false);
	}
	
	public static function stopSession() {
		
		if (self::$session_open != 1) {
			
			self::$session_open--;
			return;
		}
		
		session_write_close();
		self::$session_open = 0;

		ignore_user_abort(true); // Ignore user abort so we can run our own stuff
	}
	
	public static function getSessionId($this_request = false) {
		
		if ($this_request) {
			return (self::$session ?: 0);
		} else {
			return (session_id() ?: 0);
		}
	}
	
	public static function setCookie($name, $value, $include_sub_domains = false) {
		
		if (Response::isSent()) {
			return;
		}
		
		$is_secure = (SERVER_PROTOCOL == 'https://' ? true : false);
		
		$arr_cookie_options = ['expires' => 0, 'path' => (IS_CMS ? ini_get('session.cookie_path') : '/'), 'domain' => ($include_sub_domains ? SERVER_NAME : ini_get('session.cookie_domain')), 'httponly' => true, 'secure' => $is_secure, 'samesite' => ($is_secure ? 'None' : null)];
		
		setcookie($name, $value, $arr_cookie_options);
	}

	public static function setJSCSS() {
		
		require('js_css.php');
		$arr_core_self = $arr;
		require(DIR_SITE.'js_css.php');
		$arr_site_self = $arr;
		
		foreach ($arr_core_self['js'] as $value) {
			self::$js_css['js'][$value] = $value;
		}
		foreach ($arr_core_self['css'] as $value) {
			self::$js_css['css'][$value] = $value;
		}
		if (isset($arr_core['js'])) {
			foreach ($arr_core['js'] as $value) {
				self::$js_css['js'][$value] = $value;
			}
		}
		if (isset($arr_core['css'])) {
			foreach ($arr_core['css'] as $value) {
				self::$js_css['css'][$value] = $value;
			}
		}
		
		self::$js_css['js']['modules'] = 'modules';
		self::$js_css['css']['modules'] = 'modules';
		
		foreach ($arr_site['js'] as $value) {
			self::$js_css['js'][$value] = DIR_SITE.$value;
		}
		foreach ($arr_site['css'] as $value) {
			self::$js_css['css'][$value] = DIR_SITE.$value;
		}
		foreach ($arr_site_self['js'] as $value) {
			self::$js_css['js'][$value] = DIR_SITE.$value;
		}
		foreach ($arr_site_self['css'] as $value) {
			self::$js_css['css'][$value] = DIR_SITE.$value;
		}
		
		foreach ($arr_site_storage['js'] as $value) {
			self::$js_css['js'][$value] = DIR_SITE_STORAGE.$value;
		}
		foreach ($arr_site_storage['css'] as $value) {
			self::$js_css['css'][$value] = DIR_SITE_STORAGE.$value;
		}
		if (isset($arr_site_storage_self['js'])) {
			foreach ($arr_site_storage_self['js'] as $value) {
				self::$js_css['js'][$value] = DIR_SITE_STORAGE.$value;
			}
		}
		if (isset($arr_site_storage_self['css'])) {
			foreach ($arr_site_storage_self['css'] as $value) {
				self::$js_css['css'][$value] = DIR_SITE_STORAGE.$value;
			}
		}
	}
	
	public static function getBasePath($num_pop_length = 0, $is_relative = true) {
	
		$arr_base = ($num_pop_length && count(self::$arr_dir) ? array_slice(self::$arr_dir, 0, -$num_pop_length) : self::$arr_dir);

		return (!$is_relative ? URL_BASE_HOME : '/').(count($arr_base) ? implode('/', $arr_base).'/' : '');
	}
	
	public static function getPageUrl($str_name = false, $num_sub_dir = 0, $is_relative = true) {
	
		return self::getBasePath($num_sub_dir, $is_relative).($str_name ? $str_name : self::$page['name']);
	}
			
	public static function getModUrl($id, $str_name = false, $num_sub_dir = 0, $is_relative = true, $arr_vars_page = []) {
	
		return self::getBasePath($num_sub_dir, $is_relative).($str_name ? $str_name : self::$page['name']).'.p/'.($arr_vars_page ? implode('/', $arr_vars_page).'/' : '').$id.'.m/';
	}
	
	public static function getShortcutUrl($str_name, $is_root = true, $num_sub_dir = 0, $is_relative = true, $arr_vars_page = []) {
		
		if ($is_root) {
			return (!$is_relative ? URL_BASE_HOME : '/').$str_name.'.s/'.($arr_vars_page ? implode('/', $arr_vars_page).'/' : '');
		} else {
			return self::getBasePath($num_sub_dir, $is_relative).$str_name.'.s/'.($arr_vars_page ? implode('/', $arr_vars_page).'/' : '');
		}
	}
	
	public static function getShortestModUrl($id, $str_name = false, $str_root_name = false, $is_root = null, $num_sub_dir = 0, $is_relative = true, $arr_vars_page = []) {
		
		if ($str_root_name) {
			return static::getShortcutUrl($str_root_name, $is_root, $num_sub_dir, $is_relative, $arr_vars_page);
		} else {
			return static::getModUrl($id, $str_name, $num_sub_dir, $is_relative, $arr_vars_page);
		}
	}
	
	public static function getCacheUrl($type, $arr_options, $str_url, $target = DIR_HOME) {
	
		$cache = new FileCache($type, (array)$arr_options, $str_url, $target);
		$cache->generate();			
		
		return ($target == DIR_HOME && IS_CMS ? URL_BASE_HOME : '/').'cache/'.$type.'/'.$cache->getStringOptions().'/'.$cache->getStringUrl();
	}
	
	public static function isProcess() {
		
		return (PHP_SAPI == 'cli');
	}
	
	public static function getRequestState() {

		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') { // Ajax and ajax file uploads
			return static::REQUEST_COMMAND;
		} else if (SiteStartVars::$api) { // API calls
			return static::REQUEST_API;
		} else if (!empty($_FILES) || !empty($_POST['get-download'])) { // Plain file upload or download
			return static::REQUEST_DOWNLOAD;
		} else { // Direct page request
			return static::REQUEST_INDEX;
		}
	}
	
	public static function checkRequestOptions() {
				
		if (isset($_SERVER['HTTP_ORIGIN']) && static::getRequestState() == static::REQUEST_API) {

			Response::addHeaders('Access-Control-Allow-Origin: *');
			
			if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
				
				Response::addHeaders([
					'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS',
					'Access-Control-Allow-Headers: *'
				]);
				
				header($_SERVER['SERVER_PROTOCOL'].' 204 No Content');
				
				Response::stop('', '');
			}
		}
	}
	
	public static function checkCookieSupport() {
		
		if (!isset($_SESSION['PAGE_LOADED']) && !self::isProcess()) { // Check for cookie support
			
			$url = '/'.str_replace(URL_BASE, '', $_SERVER['HTTP_REFERER']);
			Labels::setVariable('url', $url);
			
			Log::setMsg(getLabel('msg_no_cookie_support'));
			msg(getLabel('msg_enable_cookies'), 'SORRY', LOG_CLIENT, false, 'mediate', 20000);
			
			Response::stop(function() {
				
					$obj = Log::addToObj(Response::getObject());
					$page = new ExitPage($obj->msg, 'cookie', 'cookie');
					
					return $page->getPage();
				}, Log::addToObj(Response::getObject())
			);
		}
	}
		
	public static function getRequestOutputFormat($arr_formats) {
		
		$queue = new SplPriorityQueue();
		
		foreach (preg_split('#,\s*#', $_SERVER['HTTP_ACCEPT']) as $accept) {
			
			$arr_split = preg_split('#;\s*q=#', $accept, 2);
			
			$queue->insert($arr_split[0], isset($arr_split[1]) ? (float)$arr_split[1] : 1.0);
		}
		
		foreach ($queue as $mime) {
			
			if (in_array($mime, $arr_formats)) {
				return $mime;
			}
		}
		
		return false;
	}
}
