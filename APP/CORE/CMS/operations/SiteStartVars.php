<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class SiteStartVars {

	public static $js_css = [];
	
	public static $cms_modules = [];
	public static $cms_vars = [];
	
	public static $arr_request_vars = [];
	public static $arr_feedback = [];

	public static $modules = [];
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
	public static $https = null;
	public static $https_requested = false;
	public static $session = false;
	public static $session_open = 0;
	
	public static function setPageVars($arr_vars) {
	
		$cur_mod = 0;
		$cur_var_name = false;

		foreach ($arr_vars as $var) {
			
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
		
		SiteEndVars::$arr_request_vars = self::$arr_request_vars;
	}
	
	public static function setRequestVars($arr_vars) {
	
		self::$arr_request_vars[0] = $arr_vars;
	}
	
	public static function getModuleVars($module_id) {
	
		return (self::$arr_request_vars[$module_id] ?: []);
	}
	
	public static function setFeedback($data) {
		
		self::$arr_feedback = $data;
	}
	
	public static function getFeedback($variable) {
		
		return self::$arr_feedback[$variable];
	}

	public static function preloadModules() {
			
		foreach (self::$modules as $class => $arr) {
			
			if (method_exists($class, 'modulePreload')) {
				$class::modulePreload();
			}
		}
		
		Mediator::runListeners('preload.modules');
	}
	
	public static function cooldownModules() {

		foreach (self::$modules as $class => $arr) {
			if (method_exists($class, 'moduleCooldown')) {
				$class::moduleCooldown();
			}
		}
		
		cms_jobs::callJobs();
	}

	public static function requestHTTPS() {
		
		self::$https_requested = true;
	}
	
	public static function useHTTPS($requested = true) {
		
		if (self::$https === null) {
			self::$https = (bool)getLabel('https', 'D', true);
		}
		
		return (self::$https && (!$requested || self::$https_requested));
	}
	
	public static function startSession() {
		
		if (self::$session_open != 0) {
			
			self::$session_open++;
			return;
		}
		
		if (self::$session) { // When reopening the session, do not send cookies

			$arr_session_options = ['use_only_cookies' => false, 'use_cookies' => false, 'use_trans_sid' => false, 'cache_limiter' => ''];
		} else {

			$arr_session_options = [];
			session_set_cookie_params(0, (IS_CMS ? ini_get('session.cookie_path') : '/'), ini_get('session.cookie_domain'), (SERVER_PROTOCOL == 'https://' ? true : false), true);
			
			self::$session = uniqid();
			session_name('1100CC_'.(SERVER_PROTOCOL == 'https://' ? 'secure' : 'open'));
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
		
		setcookie($name, $value, 0, (IS_CMS ? ini_get('session.cookie_path') : '/'), ($include_sub_domains ? SERVER_NAME : ini_get('session.cookie_domain')), (SERVER_PROTOCOL == 'https://' ? true : false), true);
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
		foreach ((array)$arr_core['js'] as $value) {
			self::$js_css['js'][$value] = $value;
		}
		foreach ((array)$arr_core['css'] as $value) {
			self::$js_css['css'][$value] = $value;
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
		foreach ((array)$arr_site_storage_self['js'] as $value) {
			self::$js_css['js'][$value] = DIR_SITE_STORAGE.$value;
		}
		foreach ((array)$arr_site_storage_self['css'] as $value) {
			self::$js_css['css'][$value] = DIR_SITE_STORAGE.$value;
		}
	}
	
	public static function getBasePath($pop_length = 0, $rel = true) {
	
		$arr_base = ($pop_length && count(self::$arr_dir) ? array_slice(self::$arr_dir, 0, -$pop_length) : self::$arr_dir);

		return (!$rel ? BASE_URL_HOME : '/').(count($arr_base) ? implode('/', $arr_base).'/' : '');
	}
	
	public static function getPageUrl($name = false, $sub_dir = 0, $rel = true) {
	
		return self::getBasePath($sub_dir, $rel).($name ? $name : self::$page['name']);
	}
			
	public static function getModUrl($id, $name = false, $sub_dir = 0, $rel = true, $arr_vars_page = []) {
	
		return self::getBasePath($sub_dir, $rel).($name ? $name : self::$page['name']).'.p/'.($arr_vars_page ? implode('/', $arr_vars_page).'/' : '').$id.'.m/';
	}
	
	public static function getShortcutUrl($name, $root = true, $sub_dir = 0, $rel = true) {
		
		if ($root) {
			return (!$rel ? BASE_URL_HOME : '/').$name.'.s/';
		} else {
			return self::getBasePath($sub_dir, $rel).$name.'.s/';
		}
	}
	
	public static function getCacheUrl($type, $arr_options, $url, $target = DIR_HOME) {
	
		$cache = new FileCache($type, (array)$arr_options, $url, $target);
		$cache->generate();			
		
		return ($target == DIR_HOME && IS_CMS ? BASE_URL_HOME : '/').'cache/'.$type.'/'.$cache->getStringOptions().'/'.$cache->getStringUrl();
	}
	
	public static function isProcess() {
		
		return (PHP_SAPI == 'cli');
	}
	
	public static function getRequestState() {

		if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') { // Ajax and ajax file uploads
			return 'command';
		} else if (SiteStartVars::$api) { // API calls
			return 'api';
		} else if ($_FILES || $_POST['get-download']) { // Plain file upload or download
			return 'iframe';
		} else { // Direct page request
			return 'index';
		}
	}
	
	public static function checkCookieSupport() {
		
		if (!$_SESSION['PAGE_LOADED'] && !self::isProcess()) { // Check for cookie support
			
			$url = '/'.str_replace(BASE_URL, '', $_SERVER['HTTP_REFERER']);
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
