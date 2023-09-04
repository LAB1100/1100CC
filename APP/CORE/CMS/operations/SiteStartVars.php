<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class SiteStartVars {
	
	const REQUEST_INDEX = 1;
	const REQUEST_COMMAND = 2;
	const REQUEST_DOWNLOAD = 3;
	const REQUEST_API = 4;
	
	const DIRECTORY_LANDING = 0;
	const DIRECTORY_LOGIN = 1;
	
	const CONTEXT_USER_GROUP = 1;
	const CONTEXT_PAGE_NAME = 2;
	const CONTEXT_PAGE_KIND = 3;
	const CONTEXT_MODULE_X = 4;
	const CONTEXT_MODULE_Y = 5;
	const CONTEXT_LANGUAGE = 6;
	
	const MATERIAL_JS = 'js';
	const MATERIAL_CSS = 'css';
	
	public static $arr_material = [];
		
	protected static $arr_request_vars = [];
	protected static $arr_feedback = [];
	
	protected static $arr_cms_modules = [];
	protected static $arr_modules = [];
	protected static $arr_directory_landing = false;
	protected static $arr_directory_closure = [];
	protected static $arr_directory_login = false;
	protected static $num_user_group = false;
	protected static $arr_page = false;
	protected static $str_page_name = false;
	protected static $str_page_kind = false;
	protected static $num_page_module_x = false;
	protected static $num_page_module_y = false;
	protected static $str_language = false;
	
	protected static $uri_translator = false;
	protected static $api = false;
	
	protected static $do_https = null;
	protected static $do_secure = false;
	protected static $session = false;
	protected static $num_session_open = 0;
		
	public static function setPageVariables($arr_variables = false, $do_overwrite = true) {
		
		if ($do_overwrite) {
			self::$arr_request_vars = [];
		}
		
		if ($arr_variables) {
	
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
		
		SiteEndVars::setRequestVariables(self::$arr_request_vars);
	}
	
	public static function setRequestVariables($arr_variables = false) {
		
		if (IS_CMS) {
			
			self::$arr_request_vars = $arr_variables;
			SiteEndVars::setRequestVariables(self::$arr_request_vars);
			
			return;
		}
		
		if ($arr_variables === false) {
			unset(self::$arr_request_vars[0]);
		} else {
			self::$arr_request_vars[0] = $arr_variables;
		}
		SiteEndVars::setRequestVariables($arr_variables, 0);
	}
	
	public static function getRequestVariables($num_index = false) {
		
		if (IS_CMS) {
			
			$arr = self::$arr_request_vars;
		} else {
		
			$arr = self::getModuleVariables(0);
		}
		
		return ($num_index !== false ? ($arr[$num_index] ?? null) : $arr);
	}
	
	public static function getModuleVariables($mod_id) {
	
		return (self::$arr_request_vars[$mod_id] ?? []);
	}
	
	public static function setModules($arr_modules, $mode_target = false) {
		
		if (!$mode_target) {
			$mode_target = (IS_CMS ? DIR_CMS : DIR_HOME);
		}
		
		if ($mode_target == DIR_CMS) {
			
			self::$arr_cms_modules = $arr_modules;
			return;
		}
		
		self::$arr_modules = $arr_modules;
	}
	
	public static function getModules($str_module = false, $mode_target = false) {
		
		if (!$mode_target) {
			$mode_target = (IS_CMS ? DIR_CMS : DIR_HOME);
		}
		
		if ($mode_target == DIR_CMS) {
			
			return ($str_module ? (self::$arr_cms_modules[$str_module] ?? null) : self::$arr_cms_modules);
		}
		
		return ($str_module ? (self::$arr_modules[$str_module] ?? null) : self::$arr_modules);
	} 
	
	public static function setDirectory($arr_directory, $mode_target = self::DIRECTORY_LANDING) {
		
		if ($mode_target == self::DIRECTORY_LOGIN) {
			
			self::$arr_directory_login = $arr_directory;
			return;
		}
		
		self::$arr_directory_landing = $arr_directory;
	}
	
	public static function getDirectory($str_property = false, $mode_target = self::DIRECTORY_LANDING) {
		
		if ($mode_target == self::DIRECTORY_LOGIN) {
			return ($str_property ? (self::$arr_directory_login[$str_property] ?? null) : self::$arr_directory_login);
		}
		
		return ($str_property ? (self::$arr_directory_landing[$str_property] ?? null) : self::$arr_directory_landing);
	}
	
	public static function setDirectoryClosure($arr_directories) {
		
		self::$arr_directory_closure = $arr_directories;
	}

	public static function getDirectoryClosure() {
		
		return self::$arr_directory_closure;
	}
	
	public static function setPage($arr_page) {
		
		self::$arr_page = $arr_page;
	}
	
	public static function getPage($str_property = false) {
		
		return ($str_property ? (self::$arr_page[$str_property] ?? null) : self::$arr_page);
	}
	
	public static function setContext($mode_context, $value) {
		
		switch ($mode_context) {
			
			case self::CONTEXT_PAGE_NAME:
				self::$str_page_name = $value;
				break;
			case self::CONTEXT_PAGE_KIND:
				self::$str_page_kind = $value;
				break;
			case self::CONTEXT_MODULE_X:
				self::$num_page_module_x = (int)$value;
				break;
			case self::CONTEXT_MODULE_Y:
				self::$num_page_module_y = (int)$value;
				break;
			case self::CONTEXT_USER_GROUP:
				self::$num_user_group = (int)$value;
				break;
			case self::CONTEXT_LANGUAGE:
				self::$str_language = $value;
				break;
		}
	}
	
	public static function getContext($mode_context) {
		
		switch ($mode_context) {

			case self::CONTEXT_PAGE_NAME:
				return self::$str_page_name;
			case self::CONTEXT_PAGE_KIND:
				return self::$str_page_kind;
			case self::CONTEXT_MODULE_X:
				return self::$num_page_module_x;
			case self::CONTEXT_MODULE_Y:
				return self::$num_page_module_y;
			case self::CONTEXT_USER_GROUP:
				return self::$num_user_group;
			case self::CONTEXT_LANGUAGE:
				return self::$str_language;
		}
		
		return null;
	}
	
	public static function setAPI($arr_api) {
		
		self::$api = $arr_api;
	}
	
	public static function getAPI($str_property = false) {
		
		return ($str_property ? (self::$api[$str_property] ?? null) : self::$api);
	}
	
	public static function setURITranslator($arr_uri_translator) {
		
		self::$uri_translator = $arr_uri_translator;
	}
	
	public static function getURITranslator($str_property = false) {
		
		return ($str_property ? (self::$uri_translator[$str_property] ?? null) : self::$uri_translator);
	}
	
	public static function setFeedback($data, $str_variable = false) {
		
		if ($str_variable) { // Update/override specific value
			
			self::$arr_feedback[$str_variable] = $data;
			return;
		}
		
		self::$arr_feedback = $data;
	}
	
	public static function getFeedback($str_variable) {
		
		return (self::$arr_feedback[$str_variable] ?? null);
	}

	public static function preloadModules() {
			
		foreach (self::getModules() as $class => $arr) {
			
			if (method_exists($class, 'modulePreload')) {
				$class::modulePreload();
			}
		}
		
		Mediator::runListeners('preload.modules');
	}
	
	public static function cooldownModules() {

		foreach (self::getModules() as $class => $arr) {
			if (method_exists($class, 'moduleCooldown')) {
				$class::moduleCooldown();
			}
		}
		
		cms_jobs::callJobs();
	}

	public static function requestSecure() {
		
		self::$do_secure = true;
	}
	
	public static function inSecureContext() {
		
		return self::$do_secure;
	}
	
	public static function useHTTPS($use_request = true) {
		
		if (self::$do_https === null) {
			self::$do_https = (bool)getLabel('https', 'D', true);
		}
		
		return (self::$do_https && (!$use_request || (!SERVER_NAME_CUSTOM || self::inSecureContext()))); // Use https when explicitly requested or when no variable sub-domains are part of the request
	}
	
	public static function startSession() {
		
		if (self::$num_session_open != 0) {
			
			self::$num_session_open++;
			return;
		}
		
		if (self::$session) { // When reopening the session, do not send cookies

			$arr_session_options = ['use_only_cookies' => false, 'use_cookies' => false, 'use_trans_sid' => false, 'cache_limiter' => ''];
		} else {
			
			$is_secure = (SERVER_SCHEME == URI_SCHEME_HTTPS ? true : false);

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
		
		self::$num_session_open = 1;
		
		$_SESSION['session'] = self::$session; // Set identifier to indicate last session request
		
		ignore_user_abort(false);
	}
	
	public static function stopSession() {
		
		if (self::$num_session_open != 1) {
			
			self::$num_session_open--;
			return;
		}
		
		session_write_close();
		self::$num_session_open = 0;

		ignore_user_abort(true); // Ignore user abort so we can run our own stuff
	}
	
	public static function checkSession() {
		
		if (isset($_SESSION['session']) && $_SESSION['session'] == SiteStartVars::$session) {
			return true;
		}
		
		return false;
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
		
		$is_secure = (SERVER_SCHEME == URI_SCHEME_HTTPS ? true : false);
		
		$arr_cookie_options = ['expires' => 0, 'path' => (IS_CMS ? ini_get('session.cookie_path') : '/'), 'domain' => ($include_sub_domains ? SERVER_NAME : ini_get('session.cookie_domain')), 'httponly' => true, 'secure' => $is_secure, 'samesite' => ($is_secure ? 'None' : null)];
		
		setcookie($name, $value, $arr_cookie_options);
	}

	public static function setMaterial() {
		
		require('js_css.php');
		$arr_core_self = $arr;
		require(DIR_SITE.'js_css.php');
		$arr_site_self = $arr;
		
		foreach ($arr_core_self[self::MATERIAL_JS] as $value) {
			self::$arr_material[self::MATERIAL_JS][$value] = $value;
		}
		foreach ($arr_core_self[self::MATERIAL_CSS] as $value) {
			self::$arr_material[self::MATERIAL_CSS][$value] = $value;
		}
		if (isset($arr_core[self::MATERIAL_JS])) {
			foreach ($arr_core[self::MATERIAL_JS] as $value) {
				self::$arr_material[self::MATERIAL_JS][$value] = $value;
			}
		}
		if (isset($arr_core[self::MATERIAL_CSS])) {
			foreach ($arr_core[self::MATERIAL_CSS] as $value) {
				self::$arr_material[self::MATERIAL_CSS][$value] = $value;
			}
		}
		
		self::$arr_material[self::MATERIAL_JS]['modules'] = 'modules';
		self::$arr_material[self::MATERIAL_CSS]['modules'] = 'modules';
		
		foreach ($arr_site[self::MATERIAL_JS] as $value) {
			self::$arr_material[self::MATERIAL_JS][$value] = DIR_SITE.$value;
		}
		foreach ($arr_site[self::MATERIAL_CSS] as $value) {
			self::$arr_material[self::MATERIAL_CSS][$value] = DIR_SITE.$value;
		}
		foreach ($arr_site_self[self::MATERIAL_JS] as $value) {
			self::$arr_material[self::MATERIAL_JS][$value] = DIR_SITE.$value;
		}
		foreach ($arr_site_self[self::MATERIAL_CSS] as $value) {
			self::$arr_material[self::MATERIAL_CSS][$value] = DIR_SITE.$value;
		}
		
		foreach ($arr_site_storage[self::MATERIAL_JS] as $value) {
			self::$arr_material[self::MATERIAL_JS][$value] = DIR_SITE_STORAGE.$value;
		}
		foreach ($arr_site_storage[self::MATERIAL_CSS] as $value) {
			self::$arr_material[self::MATERIAL_CSS][$value] = DIR_SITE_STORAGE.$value;
		}
		if (isset($arr_site_storage_self[self::MATERIAL_JS])) {
			foreach ($arr_site_storage_self[self::MATERIAL_JS] as $value) {
				self::$arr_material[self::MATERIAL_JS][$value] = DIR_SITE_STORAGE.$value;
			}
		}
		if (isset($arr_site_storage_self[self::MATERIAL_CSS])) {
			foreach ($arr_site_storage_self[self::MATERIAL_CSS] as $value) {
				self::$arr_material[self::MATERIAL_CSS][$value] = DIR_SITE_STORAGE.$value;
			}
		}
		
		$arr_extra = Settings::get('material');
		
		if (isset($arr_extra)) {
			
			if (isset($arr_extra[self::MATERIAL_CSS])) {
				foreach ($arr_extra[self::MATERIAL_CSS] as $value) {
					self::$arr_material[self::MATERIAL_CSS][$value] = $value; // Here value includes path
				}
			}
			if (isset($arr_extra[self::MATERIAL_JS])) {
				foreach ($arr_extra[self::MATERIAL_JS] as $value) {
					self::$arr_material[self::MATERIAL_JS][$value] = $value; // Here value includes path
				}
			}
		}
	}
	
	public static function getMaterial($mode_material) {
		
		return self::$arr_material[$mode_material];
	}
	
	public static function getBasePath($num_pop_length = 0, $is_relative = true) {
	
		$arr_base = ($num_pop_length && count(self::getDirectoryClosure()) ? array_slice(self::getDirectoryClosure(), 0, -$num_pop_length) : self::getDirectoryClosure());

		return (!$is_relative ? URL_BASE_HOME : '/').(count($arr_base) ? implode('/', $arr_base).'/' : '');
	}
	
	public static function getPageURL($str_name = false, $num_sub_dir = 0, $is_relative = true) {
	
		return self::getBasePath($num_sub_dir, $is_relative).($str_name ? $str_name : self::getPage('name'));
	}
			
	public static function getModuleURL($id, $str_name = false, $num_sub_dir = 0, $is_relative = true, $arr_vars_page = []) {
	
		return self::getBasePath($num_sub_dir, $is_relative).($str_name ? $str_name : self::getPage('name')).'.p/'.($arr_vars_page ? implode('/', $arr_vars_page).'/' : '').$id.'.m/';
	}
	
	public static function getShortcutURL($str_name, $is_root = true, $num_sub_dir = 0, $is_relative = true, $arr_vars_page = []) {
		
		if ($is_root) {
			return (!$is_relative ? URL_BASE_HOME : '/').$str_name.'.s/'.($arr_vars_page ? implode('/', $arr_vars_page).'/' : '');
		} else {
			return self::getBasePath($num_sub_dir, $is_relative).$str_name.'.s/'.($arr_vars_page ? implode('/', $arr_vars_page).'/' : '');
		}
	}
	
	public static function getShortestModuleURL($id, $str_name = false, $str_root_name = false, $is_root = null, $num_sub_dir = 0, $is_relative = true, $arr_vars_page = []) {
		
		if ($str_root_name) {
			return static::getShortcutURL($str_root_name, $is_root, $num_sub_dir, $is_relative, $arr_vars_page);
		} else {
			return static::getModuleURL($id, $str_name, $num_sub_dir, $is_relative, $arr_vars_page);
		}
	}
	
	public static function getCacheURL($type, $arr_options, $str_url, $target = DIR_HOME) {
	
		$cache = new FileCache($type, (array)$arr_options, $str_url, $target);
		$cache->generate();			
		
		return ($target == DIR_HOME && IS_CMS ? URL_BASE_HOME : '/').'cache/'.$type.'/'.$cache->getOptionsString().'/'.$cache->getURLString();
	}
	
	public static function isProcess() {
		
		return (PHP_SAPI == 'cli');
	}
	
	public static function getRequestState() {

		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') { // Ajax and ajax file uploads
			return static::REQUEST_COMMAND;
		} else if (SiteStartVars::getAPI()) { // API calls
			return static::REQUEST_API;
		} else if (!empty($_FILES) || !empty($_POST['is_download'])) { // Plain file upload or download
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
