<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// 1100CC Framework:

	ini_set('display_errors', 0);
	ini_set('error_reporting', E_ALL);
	
	require('core_operations.php');
	
	$run_module = false;
	$run_method = false;
	$arr_run_options = [];
	
	if (isset($_SERVER['argv'])) { // External call, i.e. exec(): index.php 'SITE_NAME' 'SERVER_NAME_1100CC' 'SERVER_NAME_CUSTOM' 'SERVER_NAME_SUB' 'STATE' 'module' 'method'
		
		$arr_signature = str2Array($_SERVER['argv'][1], ';');
		$run_module = $_SERVER['argv'][2];
		$run_method = $_SERVER['argv'][3];
		if (!empty($_SERVER['argv'][4])) {
			$arr_run_options = JSON2Value($_SERVER['argv'][4]);
		}

		$_SERVER['SITE_NAME'] = $arr_signature[0];
		$_SERVER['SERVER_NAME_1100CC'] = ($arr_signature[1] ?? '');
		$_SERVER['SERVER_NAME_SITE_NAME'] = ($arr_signature[6] ?? $_SERVER['SERVER_NAME_1100CC']);
		$_SERVER['SERVER_NAME_MODIFIER'] = ($arr_signature[7] ?? '');
		$_SERVER['SERVER_NAME_CUSTOM'] = ($arr_signature[2] ?? '');
		$_SERVER['SERVER_NAME_SUB'] = ($arr_signature[3] ?? '');
		$_SERVER['SERVER_SCHEME'] = ($arr_signature[4] ?? null);
		$_SERVER['STATE'] = ($arr_signature[5] ?? null);

		$_SERVER['PATH_INFO'] = '/';
		$_SERVER['PATH_CMS'] = true;
		
	} else if (!isset($_SERVER['SITE_NAME'])) { // Cleanup server variables when applicable, depends on host
		
		foreach($_SERVER as $key => $value) {
			$_SERVER[str_replace('REDIRECT_', '', $key)] = $value;
		}
	}
	
	$_SERVER['DIR_INDEX'] = dirname(__FILE__);
	
	require('core_settings.php');
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		
		if (!$_POST) { // Posted data in JSON format
			
			$json = file_get_contents('php://input');
			
			if ($json) {
				
				$json = JSON2Value($json);
				
				if (is_array($json)) {
					$_POST = $json;
				}
			}
			unset($json);
		} else if (!empty($_POST['json'])) { // Posted data in serialised format, check for JSON data
			
			$json = JSON2Value($_POST['json']);
			unset($_POST['json']);
			
			if (is_array($json)) {
				
				foreach ($json as $key => $value) {
					$_POST[$key] = $value;
				}
			}
			unset($json);
		}
	}
	
	Response::setFormat((SiteStartEnvironment::getRequestState() == SiteStartEnvironment::REQUEST_INDEX ? Response::OUTPUT_XML : Response::OUTPUT_JSON) | Response::RENDER_HTML);
	
	if (!strIsValidEncoding($_SERVER['PATH_INFO']) || !arrHasValidStringEncoding($_GET)) {
		error(Labels::getSystemLabel('msg_request_invalid_encoding'), TROUBLE_INVALID_REQUEST, LOG_CLIENT);
	}
	
	require('login.php');
	
	$arr_path_info = [];

	if ($_SERVER['PATH_INFO'] != '' && $_SERVER['PATH_INFO'] != '/') {
		$arr_path_info = explode('/', $_SERVER['PATH_INFO']);
	}
	
	$str_path_start = ($arr_path_info[1] ?? '');
	
	if ($str_path_start == 'robots.txt' || $str_path_start == 'humans.txt' || $str_path_start == 'version.txt') {
	
		header('Content-Type: text/plain; charset=utf-8');
		
		if ($str_path_start == 'robots.txt') {
			echo 'User-agent: *'.EOL_1100CC;
			echo 'Disallow: /'.EOL_1100CC;
		} else if ($str_path_start == 'version.txt') {
			echo Labels::getServerVariable('version');
		} else {
			echo Labels::getServerVariable('humans');
		}
		
		exit;
	}
	
	// Prepare
	SiteStartEnvironment::setRequestVariables($arr_path_info);
	SiteStartEnvironment::setModules(getModules());
	
	DB::setConnection(DB::CONNECT_HOME, DB::MODE_CONNECT_DEFAULT_DATABASE);
	
	Labels::setSystemLabels();
	
	// Language system
	SiteStartEnvironment::checkLanguage();
	
	// Async		
	if ($run_module && $run_method) {
		
		DB::setConnection(DB::CONNECT_CMS, DB::MODE_CONNECT_SET_LEVEL | DB::MODE_CONNECT_DEFAULT_DATABASE);
		
		Response::setFormat(Response::OUTPUT_TEXT);
		
		Mediator::runModuleMethod($run_module, $run_method, $arr_run_options);
		
		exit;
	}
	
	SiteStartEnvironment::checkRequestOptions();
	
	if ($str_path_start == 'combine') {

		require('core_combine.php');
		SiteStartEnvironment::setMaterial();
		
		$type = ($arr_path_info[2] ?? '');
		
		if ($type != SiteStartEnvironment::MATERIAL_JS && $type != SiteStartEnvironment::MATERIAL_CSS) {
			pages::noPage(true);
		}
		
		$arr_modules = SiteStartEnvironment::getModules();
		$ie_tag = ($arr_path_info[3] ?? '');

		CombineJSCSS::combine(SiteStartEnvironment::getMaterial($type), $arr_modules, $type, $ie_tag);
		
		exit;
	}
	
	if ($str_path_start == 'cache') {
				
		$cache = new FileCache($arr_path_info[2], $arr_path_info[3], implode('/', array_slice($arr_path_info, 4)));
		$cache->cache();
		$cache->read();
		
		exit;
	} 
	
	SiteStartEnvironment::requestSecure();
	
	// Special page
	if ($str_path_start == 'script') {
					
		if ($str_path_start == 'script') {
			
			Log::setHeader(getLabel('msg_no_script_support'));
			message(getLabel('msg_enable_script'), 'SORRY', LOG_CLIENT);
		}
				
		$obj = Log::addToObject(Response::getObject());
		
		$page = new ExitPage($obj->message, $str_path_start, $str_path_start);
		
		Response::stop($page->getPage(), '');
	}
	
	// Session
	SiteStartEnvironment::startSession();
	
	// Virtual path
	if ($str_path_start == 'commands') {
		
		$str_module = ($_POST['module'] ?? $_POST['multi'][0]['module'] ?? '');
		$str_module = (is_string($str_module) ? $str_module : 'INVALID');
		
		$_SERVER['PATH_VIRTUAL'] = '/'.$str_module.'/';
	}
	
	// Login
	if ($str_path_start != 'login') {
		CMSLogin::index();
	}
	
	DB::setConnection(DB::CONNECT_CMS, DB::MODE_CONNECT_SET_LEVEL | DB::MODE_CONNECT_DEFAULT_DATABASE);

	// Language
	SiteStartEnvironment::checkLanguageSession();
	
	// Return page
	$str_path_last = ($arr_path_info[count($arr_path_info)-1] ?? '');
	
	if ($str_path_start == 'commands') {
		
		// Feedback
		if ($_POST['feedback']) {
			SiteStartEnvironment::setFeedback($_POST['feedback']);
		}
		
		require('commands.php');
		
		exit;
	}
	
	if ($str_path_last == 'manifest') {

		if ($str_path_start == 'login' || $str_path_start == 'manifest') {
			
			$str_url = '/';
		} else {
			
			// Remove manifest from url
			$arr_url = $arr_path_info;
			unset($arr_url[count($arr_url)-1]);
			$str_url = implode('/', $arr_url);
		}
		
		$str_title = getLabel('title', 'D').' | 1100CC';
		$str_image = SiteEndEnvironment::getImage();
		$arr_theme = SiteEndEnvironment::getTheme();
							
		$arr_images = [];

		foreach ([64, 96, 128, 192, 256, 512] as $nr_size) {
			
			$arr_images[] = [
				'src' => SiteStartEnvironment::getCacheURL('img', [$nr_size, $nr_size], $str_image, DIR_CMS),
				'type' => 'image/png',
				'sizes' => $nr_size.'x'.$nr_size
			];
		}

		$arr_manifest = [
			'short_name' => getLabel('title', 'D'),
			'name' => $str_title,
			'start_url' => $str_url,
			'description' => getLabel('title', 'D').' powered by 1100CC',
			'icons' => $arr_images,
			'theme_color' => $arr_theme['theme_color'],
			'background_color' => $arr_theme['background_color'],
			'display' => 'standalone'
		];
		
		Response::setFormat(Response::OUTPUT_JSON);
				
		$json = Response::parse($arr_manifest);
		$json = Response::output($json);
			
		Response::addHeaders('Content-Type: application/manifest+json;charset=utf-8');
		Response::sendHeaders();
		
		echo $json;
		
		exit;
	}

	if (!isset($_SESSION['LANDING_URL'])) {
		$_SESSION['LANDING_URL'] = SiteStartEnvironment::getRequestURL(false);
	}
	
	if ($str_path_start == 'login') {
		
		if (!empty($_POST['login_user']) && !empty($_POST['login_password']) && is_string($_POST['login_user']) && is_string($_POST['login_password'])) {
			
			CMSLogin::indexProposeUser($_POST['login_user'], $_POST['login_password']);
			
			Response::location('/');
		}
		
		SiteEndEnvironment::checkServerName();
		
		require('core_combine.php');
		SiteStartEnvironment::setMaterial();
				
		$html_body = '<div id="cms-login">
		
			<form method="post" action="/login/" autocomplete="on">			
				<label>'.getLabel('lbl_username').'</label>
				<input name="login_user" type="text"'.(($arr_path_info[2] ?? '') == 'LOGIN_INCORRECT' ? ' class="input-error"' : '').' />
				<label>'.getLabel('lbl_password').'</label>
				<input name="login_password" type="password"'.(($arr_path_info[2] ?? '') == 'LOGIN_INCORRECT' ? ' class="input-error"' : '').' />
				<menu><input type="submit" value="Login" /></menu>
			</form>
			
			<div id="lab1100"><span><strong>1100CC</strong> is developed by</span><a href="https://lab1100.com" target="_blank"></a></div>
		
		</div>';
		
		$str_url = '/login/';
	} else {
		
		SiteStartEnvironment::preloadModules();
		
		$str_module = (SiteStartEnvironment::getRequestVariables(1) ?? '');
		
		if (!$str_module || !SiteStartEnvironment::getModules($str_module)) {
			
			$str_module = 'cms_dashboard';

			SiteEndEnvironment::setRequestVariables($str_module, 1);
		}
		
		$str_mod_identifier = 'mod-'.$str_module;
		
		$JSON = Response::getObject();
		
		$class = new $str_module;
		
		$html_content = $class->contents();
		
		if (isset($class->validate)) {
			$JSON->validate[$str_mod_identifier] = $class->validate;
		}
		
		SiteEndEnvironment::checkServerName();

		$JSON->data_feedback = SiteEndEnvironment::getFeedback();
		$JSON = Log::addToObject($JSON);
		if (Settings::get('timing') === true) {
			$JSON->timing = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
		}
		SiteEndEnvironment::addScript("PARSE = function() {"
			."return JSON.parse(".value2JSON(value2JSON($JSON)).");"
		."};");
		
		require('core_combine.php');
		SiteStartEnvironment::setMaterial();
		
		$html_body = '<div id="cms-menu" class="section">
			<h1>'.getLabel('lbl_modules').'</h1>
			'.cms_general::selectModuleList(SiteStartEnvironment::getModules()).'
			<div id="lab1100">'
				.'<span><strong>1100CC</strong> is developed by</span>'
				.'<a href="https://lab1100.com" target="_blank"></a>'
			.'</div>
			<div><span>version</span><span>'.Labels::getServerVariable('version').'</span></div>
		</div>
		<div class="cms-content" id="'.$str_mod_identifier.'">';
		
			$html_body .= $html_content;
		
		$html_body .= '</div>';
		
		$str_url = implode('/', $arr_path_info);
	}
	
	$str_url_manifest = $str_url.'manifest';
	
	$arr_theme = SiteEndEnvironment::getTheme();
	$str_image = SiteEndEnvironment::getImage();
	$str_url_image = URL_BASE.ltrim($str_image, '/');
	$html_icons = SiteEndEnvironment::getIcons();

	$html = '<!DOCTYPE html>'.EOL_1100CC
	.'<html lang="'.SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE).'">'.EOL_1100CC
		.'<head>'.EOL_1100CC
			.'<title>'.getLabel('title', 'D').' | 1100CC</title>'
			.$html_icons
			.'<meta property="og:image" content="'.$str_url_image.'" />'
			.'<meta name="theme-color" content="'.$arr_theme['theme_color'].'">'
			.'<link rel="manifest" href="'.$str_url_manifest.'" crossOrigin="use-credentials" />';
			
			$version = CombineJSCSS::getVersion(SiteStartEnvironment::getMaterial(SiteStartEnvironment::MATERIAL_CSS), SiteStartEnvironment::getModules());
			$html .= '<link href="/combine/css/'.$version.'" rel="stylesheet" type="text/css" />';
			$version = CombineJSCSS::getVersion(SiteStartEnvironment::getMaterial(SiteStartEnvironment::MATERIAL_JS), SiteStartEnvironment::getModules());
			$html .= '<script type="text/javascript" src="/combine/js/'.$version.'"></script>';
			
			if ($str_path_start != 'login') {
				
				$html .= SiteEndEnvironment::getHeadTags();
				$html .= '<noscript>'
					.'<meta http-equiv="refresh" content="0;url=/script" />'
				.'</noscript>';
			}
	
		$html .= EOL_1100CC.'</head>'.EOL_1100CC
		.'<body>'.EOL_1100CC
			.'<div id="cms-header">';
			
				if ($str_path_start != 'login') {
					
					$html .= '<span id="welcome"><strong>'.getLabel('lbl_welcome').': </strong>'.$_SESSION['CUR_USER']['name'].'</span>';
				}
				
				$html .= '<div id="plate">'
					.'<span></span>'
					.'<span id="lab1100cc" title="1100CC"><a href="/"></a></span>'
					.'<span id="site"><a href="'.URL_BASE_HOME.'" target="_blank">'.getLabel('title', 'D').'</a></span>'
				.'</div>';
				
				if ($str_path_start != 'login') {
					
					$html .= '<nav><ul>'
						.'<li><span><a href="'.URL_BASE_HOME.'" title="'.getLabel('inf_new_window').'" target="_blank">'.getLabel('lbl_open_site').'</a></li>'
						.'<li><span class="a" id="y:cms_users:my_edit-0">'.getLabel('lbl_account').'</span></li>'
						.'<li><a href="/logout">'.getLabel('lbl_logout').'</a></span></li>'
					.'</ul></nav>';
				}
				
			$html .= '</div>';
			
			$html .= '<div id="cms-body">'
				.$html_body
			.'</div>';
			
		$html .= EOL_1100CC.'</body>'.EOL_1100CC
	.'</html>';
	
	if ($str_path_start != 'login') {
		SiteStartEnvironment::cooldownModules();
	}

	Response::stop($html, false);
