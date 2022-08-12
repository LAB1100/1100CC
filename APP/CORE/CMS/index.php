<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// 1100CC Framework:

	ini_set('display_errors', 0);
	ini_set('error_reporting', E_ALL);
	
	$run_module = false;
	$run_method = false;
	
	if (isset($_SERVER['argv'])) { // External call, i.e. exec(): index.php 'SITE_NAME' 'SERVER_NAME_1100CC' 'SERVER_NAME_CUSTOM' 'SERVER_NAME_SUB' 'STATE' 'module' 'method'
		
		$_SERVER['SITE_NAME'] = $_SERVER['argv'][1];
		$_SERVER['SERVER_NAME_1100CC'] = $_SERVER['argv'][2];
		$_SERVER['SERVER_NAME_CUSTOM'] = $_SERVER['argv'][3];
		$_SERVER['SERVER_NAME_SUB'] = $_SERVER['argv'][4];
		$arr_state = explode(';', $_SERVER['argv'][5]);
		$_SERVER['STATE'] = $arr_state[0];
		$_SERVER['HTTPS'] = ($arr_state[1] == 'https' ? true : false);
		$_SERVER['PATH_INFO'] = '/';
		$_SERVER['IF_CMS_PATH'] = true;
		$run_module = $_SERVER['argv'][6];
		$run_method = $_SERVER['argv'][7];
		$arr_run_options = ($_SERVER['argv'][8] ? json_decode($_SERVER['argv'][8], true) : []);
		
	} else if (!isset($_SERVER['SITE_NAME'])) { // Cleanup server variables when applicable, depends on host
		
		foreach($_SERVER as $key => $value) {
			$_SERVER[str_replace('REDIRECT_', '', $key)] = $value;
		}
	}
	
	$_SERVER['DIR_INDEX'] = dirname(__FILE__);
	
	require('core_operations.php');
	require('core_settings.php');
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		
		if (!$_POST) { // Posted data in JSON format
			
			$json = file_get_contents('php://input');
			
			if ($json) {
				$arr = json_decode($json, true);
				$_POST = $arr;
			}
		} else if (!empty($_POST['json'])) { // Posted data in serialized format, check for JSON data
			
			$arr = json_decode($_POST['json'], true);
			unset($_POST['json']);
			
			foreach ($arr as $key => $value) {
				$_POST[$key] = $value;
			}
		}
	}
	
	Response::setFormat((SiteStartVars::getRequestState() == SiteStartVars::REQUEST_INDEX ? Response::OUTPUT_XML : Response::OUTPUT_JSON) | Response::RENDER_HTML);
	
	require('login.php');
	
	$arr_path_info = [];

	if ($_SERVER['PATH_INFO'] != '' && $_SERVER['PATH_INFO'] != '/') {
		$arr_path_info = explode('/', $_SERVER['PATH_INFO']);
	}
	
	$str_path_start = ($arr_path_info[1] ?? '');
	
	if ($str_path_start == 'robots.txt' || $str_path_start == 'humans.txt' || $str_path_start == 'version.txt') {
	
		header('Content-Type: text/plain; charset=utf-8');
		
		if ($str_path_start == 'robots.txt') {
			echo 'User-agent: *'.PHP_EOL;
			echo 'Disallow: /'.PHP_EOL;
		} else if ($str_path_start == 'version.txt') {
			echo Labels::getServerVariable('version');
		} else {
			echo Labels::getServerVariable('humans');
		}
		
		exit;
	}
	
	// Prepare
	SiteStartVars::$arr_cms_vars = $arr_path_info;
	
	SiteStartVars::$arr_modules = getModules();
	
	DB::setConnection(DB::CONNECT_HOME);
	
	Labels::setSystemLabels();
	
	// Sytem Language
	$arr_lang_default = cms_language::getDefaultLanguage();
	SiteStartVars::$language = $arr_lang_default['lang_code'];
	
	// Async		
	if ($run_module && $run_method) {
		
		DB::setConnection(DB::CONNECT_CMS, true);
		
		Mediator::runModuleMethod($run_module, $run_method, $arr_run_options);
		exit;
	}
	
	SiteStartVars::checkRequestOptions();
	
	if ($str_path_start == 'combine') {

		require('core_combine.php');
		SiteStartVars::setJSCSS();
		
		$type = ($arr_path_info[2] ?? '');
		
		if ($type != 'js' && $type != 'css') {
			pages::noPage(true);
		}
		
		$arr_modules = SiteStartVars::$arr_modules;
		$ie_tag = ($arr_path_info[3] ?? '');

		CombineJSCSS::combine(SiteStartVars::$js_css[$type], $arr_modules, $type, $ie_tag);
		
		exit;
	} else if ($str_path_start == 'cache') {
				
		$cache = new FileCache($arr_path_info[2], $arr_path_info[3], implode('/', array_slice($arr_path_info, 4)));
		$cache->cache();
		$cache->read();
		
		exit;
	} 
	
	SiteStartVars::requestSecure();
	
	// Special page
	if ($str_path_start == 'script') {
					
		if ($str_path_start == 'script') {
			
			Log::setMsg(getLabel('msg_no_script_support'));
			msg(getLabel('msg_enable_script'), 'SORRY', LOG_CLIENT);
		}
				
		$obj = Log::addToObj(Response::getObject());
		
		$page = new ExitPage($obj->msg, $str_path_start, $str_path_start);
		
		Response::stop($page->getPage(), '');
	}
	
	// Session
	SiteStartVars::startSession();
	
	// Virtual path
	if ($str_path_start == 'commands') {
		$_SERVER['PATH_VIRTUAL'] = '/'.$_POST['module'].'/';
	}
	
	// Login
	if ($str_path_start != 'login') {
		CMSLogin::index();
	}
	
	DB::setConnection(DB::CONNECT_CMS, true);

	// Language
	if (!empty($_SESSION['LANGUAGE_SYSTEM'])) {
		SiteStartVars::$language = $_SESSION['LANGUAGE_SYSTEM'];
	} else if (!empty($_SESSION['CUR_USER']['lang_code'])) {
		SiteStartVars::$language = $_SESSION['CUR_USER']['lang_code'];
	}
	
	// Return page
	$str_path_last = ($arr_path_info[count($arr_path_info)-1] ?? '');
	
	if ($str_path_start == 'commands') {
	
		$_SERVER['REQUEST_COMMANDS'] = true;
		
		// Feedback
		if ($_POST['feedback']) {
			SiteStartVars::setFeedback($_POST['feedback']);
		}
		
		require('commands.php');
	} else if ($str_path_last == 'manifest') {

		if ($str_path_start == 'login' || $str_path_start == 'manifest') {
			
			$str_url = '/';
		} else {
			
			// Remove manifest from url
			$arr_url = $arr_path_info;
			unset($arr_url[count($arr_url)-1]);
			$str_url = implode('/', $arr_url);
		}
		
		$str_title = getLabel('title', 'D').' | 1100CC';
		$str_image = SiteEndVars::getImage();
		$arr_theme = SiteEndVars::getTheme();
							
		$arr_images = [];

		foreach ([64, 96, 128, 192, 256, 512] as $nr_size) {
			
			$arr_images[] = [
				'src' => SiteStartVars::getCacheUrl('img', [$nr_size, $nr_size], $str_image, DIR_CMS),
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
			
		header('Content-Type: application/manifest+json;charset=utf-8');
		
		echo $json;
	} else {

		if (!isset($_SESSION['PAGE_LOADED'])) {
			$_SESSION['PAGE_LOADED'] = 0;
		}
		$_SESSION['PAGE_LOADED']++;
		
		if ($str_path_start == 'login') {
			
			SiteEndVars::checkServerName();
			
			require('core_combine.php');
			SiteStartVars::setJSCSS();
					
			$html_body = '<div id="cms-login">
			
				<form method="post" action="/">			
					<label>'.getLabel('lbl_username').'</label>
					<input name="login_user" type="text"'.(($arr_path_info[2] ?? '') == 'LOGIN_INCORRECT' ? ' class="input-error"' : '').' />
					<label>'.getLabel('lbl_password').'</label>
					<input name="login_ww" type="password"'.(($arr_path_info[2] ?? '') == 'LOGIN_INCORRECT' ? ' class="input-error"' : '').' />
					<menu><input type="submit" value="Login" /></menu>
				</form>
				
				<div id="lab1100"><span><strong>1100CC</strong> is developed by</span><a href="https://lab1100.com" target="_blank"></a></div>
			
			</div>';
			
			$str_url = '/login/';
		} else {
			
			SiteStartVars::preloadModules();
			
			$str_module = (SiteStartVars::$arr_cms_vars[1] ?? '');
			
			if (!SiteStartVars::$arr_modules[$str_module]) {
				
				$str_module = 'cms_dashboard';
				SiteStartVars::$arr_cms_vars[1] = $str_module;
			}
			
			$class = new $str_module;
			
			$html_content = $class->contents();
			
			SiteEndVars::checkServerName();
			
			$JSON = Response::getObject();
			$JSON->data_feedback = SiteEndVars::getFeedback();
			$JSON = Log::addToObj($JSON);
			if (Settings::get('timing') === true) {
				$JSON->timing = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
			}
			SiteEndVars::addScript("PARSE = function() {"
				."var obj = JSON.parse(".value2JSON(value2JSON($JSON)).");"
				."FEEDBACK.check(document.body, obj);"
			."};");
			
			require('core_combine.php');
			SiteStartVars::setJSCSS();
			
			$html_body = '<div id="cms-menu" class="section">
				<h1>'.getLabel('lbl_modules').'</h1>
				'.cms_general::selectModuleList(SiteStartVars::$arr_modules).'
				<div id="lab1100">'
					.'<span><strong>1100CC</strong> is developed by</span>'
					.'<a href="https://lab1100.com" target="_blank"></a>'
				.'</div>
				<div><span>version</span><span>'.Labels::getServerVariable('version').'</span></div>
			</div>
			<div class="cms-content" id="mod-'.$str_module.'">';
			
				$html_body .= $html_content;
			
			$html_body .= '</div>';
			
			$str_url = implode('/', $arr_path_info);
		}
		
		$str_url_manifest = $str_url.'manifest';
		
		$arr_theme = SiteEndVars::getTheme();
		$str_image = SiteEndVars::getImage();
		$str_url_image = URL_BASE.ltrim($str_image, '/');
		$html_icons = SiteEndVars::getIcons();

		$html = '<!DOCTYPE html>'.PHP_EOL
		.'<html lang="en">'.PHP_EOL
			.'<head>'.PHP_EOL
				.'<title>'.getLabel('title', 'D').' | 1100CC</title>'
				.$html_icons
				.'<meta property="og:image" content="'.$str_url_image.'" />'
				.'<meta name="theme-color" content="'.$arr_theme['theme_color'].'">'
				.'<link rel="manifest" href="'.$str_url_manifest.'" crossOrigin="use-credentials" />';
				
				$version = CombineJSCSS::getVersion(SiteStartVars::$js_css['css'], SiteStartVars::$arr_modules);
				$html .= '<link href="/combine/css/'.$version.'" rel="stylesheet" type="text/css" />';
				$version = CombineJSCSS::getVersion(SiteStartVars::$js_css['js'], SiteStartVars::$arr_modules);
				$html .= '<script type="text/javascript" src="/combine/js/'.$version.'"></script>';
				
				if ($str_path_start != 'login') {
					
					$html .= SiteEndVars::getHeadTags();
					$html .= '<noscript>'
						.'<meta http-equiv="refresh" content="0;url=/script" />'
					.'</noscript>';
				}
	
			$html .= PHP_EOL.'</head>'.PHP_EOL
			.'<body>'.PHP_EOL
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
				
			$html .= PHP_EOL.'</body>'.PHP_EOL
		.'</html>';
		
		if ($str_path_start != 'login') {
			SiteStartVars::cooldownModules();
		}

		Response::stop($html, false);
	}
