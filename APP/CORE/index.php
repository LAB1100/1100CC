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
	
	if (!isset($_SERVER['SITE_NAME'])) { // Cleanup server variables when applicable, depends on host
		
		foreach($_SERVER as $key => $value) {
			$_SERVER[str_replace('REDIRECT_', '', $key)] = $value;
		}
	}
	
	$_SERVER['DIR_INDEX'] = dirname(__FILE__);
	
	require('./CMS/core_operations.php');
	require('./CMS/core_settings.php');
	
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
	
	require('login.php');
	
	$arr_path_info = [];

	if ($_SERVER['PATH_INFO'] != '' && $_SERVER['PATH_INFO'] != '/') {
		$arr_path_info = explode('/', $_SERVER['PATH_INFO']);
	}
	
	$str_path_start = ($arr_path_info[1] ?? '');
	
	if ($str_path_start == 'robots.txt' || $str_path_start == 'humans.txt' || $str_path_start == 'version.txt') {
		
		header('Content-Type: text/plain;charset=utf-8');
		
		if ($str_path_start == 'robots.txt') {
			echo 'User-agent: *'.PHP_EOL;
			echo 'Disallow: '.(STATE == STATE_DEVELOPMENT ? '/' : '').PHP_EOL;
		} else if ($str_path_start == 'version.txt') {
			echo Labels::getServerVariable('version');
		} else {
			echo Labels::getServerVariable('humans');
		}
		
		exit;
	}
	
	// Prepare
	SiteStartVars::$arr_cms_modules = getModules(DIR_CMS);
	SiteStartVars::$arr_modules = getModules();
	
	DB::setConnection(DB::CONNECT_HOME);
	
	Labels::setSystemLabels();
		
	// Process request
	
	SiteStartVars::$uri_translator = (uris::getURITranslatorHosts(SERVER_NAME) ?: false);
	SiteStartVars::$api = (apis::getAPIHosts(SERVER_NAME) ?: false);
	
	SiteStartVars::checkRequestOptions();
		
	if (SiteStartVars::$uri_translator) {
				
		if ($str_path_start) {
			SiteStartVars::setRequestVariables(array_slice($arr_path_info, 1));
		}
		
		// Request
		require('uri.php');
		
		SiteStartVars::setRequestVariables();
	}
				
	if (SiteStartVars::$api) {
				
		Response::setFormat(Response::OUTPUT_JSON | Response::PARSE_PRETTY);
		
		$arr_api = SiteStartVars::$api;
		
		$JSON = Response::getObject();
	
		Labels::setVariable('api_name', $arr_api['name']);
		Labels::setVariable('api_url', $arr_api['documentation_url']);
		
		$JSON->info = Labels::getSystemLabel('inf_api_welcome');

		$JSON->timestamp = date('c');
		
		$str_request_identifier = 'api_home_'.$arr_api['id'];
		
		$check = Log::checkRequest($str_request_identifier, false, (($arr_api['request_limit_amount'] * $arr_api['request_limit_unit']) * 60), ['ip' => $arr_api['request_limit_ip'], 'ip_block' => ($arr_api['request_limit_ip'] * 4), 'global' => $arr_api['request_limit_global']]);
		
		if ($check !== true) {
			
			$arr_unit = cms_general::getTimeUnits($arr_api['request_limit_unit']);
			$str_limit_time = $arr_api['request_limit_amount'].' '.$arr_unit['name'];
			
			if ($check == 'ip') {
				$limit = $arr_api['request_limit_ip'].' x IP / '.$str_limit_time;
			} else if ($check == 'ip_block') {
				$limit = ($arr_api['request_limit_ip'] * 4).' x IP block / '.$str_limit_time;
			} else { 
				$limit = 'too many requests';
			}
			
			Labels::setVariable('limit', $limit);
			error(Labels::getSystemLabel('msg_api_limit'), TROUBLE_REQUEST_LIMIT, LOG_CLIENT);
		}
		
		// Authorization
		if ($str_path_start == 'authorization') {
			
		}
		
		// Authentication
		if ($_SERVER['HTTP_AUTHORIZATION']) {
			
			try {
				
				$arr_method_token = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);
			
				if ($arr_method_token[0] == 'Bearer' && $arr_method_token[1]) {
					
					$arr_api_client_user = HomeLogin::API($arr_method_token[1]);
					
					if (!$arr_api_client_user['client_request_limit_disable']) {
				
						Log::logRequest($str_request_identifier);
					}
				} else {
					
					error(Labels::getSystemLabel('msg_missing_information').' Invalid authentication method.', TROUBLE_INVALID_REQUEST);
				}
			} catch (Exception $e) {
				
				Log::logRequest($str_request_identifier);
				throw($e);
			}
		} else {
			
			Log::logRequest($str_request_identifier);
		}
		
		$JSON->authenticated = ($_SESSION['USER_ID'] ? true : false);
		
		SiteStartVars::setRequestVariables(($str_path_start ? array_slice($arr_path_info, 1) : []));

		// Request
		require('api.php');
				
	} else if ($str_path_start == 'combine') {
		
		require('./CMS/core_combine.php');
		SiteStartVars::setJSCSS();
		
		$type = ($arr_path_info[2] ?? '');
		
		if ($type != 'js' && $type != 'css') {
			pages::noPage(true);
		}
		
		$arr_modules = SiteStartVars::$arr_modules;
		$ie_tag = ($arr_path_info[3] ?? '');

		CombineJSCSS::combine(SiteStartVars::$js_css[$type], $arr_modules, $type, $ie_tag);
	
	} else if ($str_path_start == 'cache') {
				
		$cache = new FileCache($arr_path_info[2], $arr_path_info[3], implode('/', array_slice($arr_path_info, 4)));
		$cache->cache();
		$cache->read();
	
	} else {
		
		Response::setFormat((SiteStartVars::getRequestState() == SiteStartVars::REQUEST_INDEX ? Response::OUTPUT_XML : Response::OUTPUT_JSON) | Response::RENDER_HTML);
		
		if (getLabel('throttle', 'D', true)) {
			
			$num_window = (Settings::get('request_throttle_window') ?: 30 * 60);
			$is_heated = Log::checkRequestThrottle($num_window, (Settings::get('request_throttle_per_second') ?: 2));
			
			if ($is_heated) {
				
				Labels::setVariable('minutes', $num_window / 60);
				error(getLabel('msg_request_throttle_limit'), TROUBLE_REQUEST_LIMIT, LOG_CLIENT);
			}
		}
			
		if (getLabel('use_servers', 'D', true)) {
			cms_details::useServerFiles();
		}
	
		// URL
		$str_path_last = '';
		
		if ($arr_path_info) {
			
			$str_path_last = $arr_path_info[count($arr_path_info)-1];
			
			if ($str_path_last && !preg_grep("/\.(p|c|s|l|e|manifest)$/", $arr_path_info)) {
				
				$arr_path_info[count($arr_path_info)-1] = $str_path_last.'.p'; // If no / at the end, and no page kind, assume .p
			}
		}

		// Page raw
		$arr_page_raw = preg_grep("/.+\.(p|c|s|l|e|manifest)$/", $arr_path_info);
		$str_page = '';
		
		if ($arr_page_raw) {
			
			$str_page = reset($arr_page_raw);
			$num_page_key = key($arr_page_raw);
			
			preg_match("/(.+)\.(p|c|s|l|e|manifest)$/", $str_page, $arr_match);
			
			SiteStartVars::$page_name = $arr_match[1]; // Substring '.c.p.s.l.e.manifest'
			SiteStartVars::$page_kind = '.'.$arr_match[2]; // Store '.c.p.s.l.e.manifest'
		} else {
			
			SiteStartVars::$page_kind = '.p'; // Assume .p
		}
							
		// Directory
		if ($str_page) {
			
			$arr_directory = array_slice($arr_path_info, 0, $num_page_key);
			$arr_page_variables = array_slice($arr_path_info, $num_page_key+1);
		} else {
			
			$arr_directory = $arr_path_info;
			if (!$str_path_last) { // Remove empty / at the end
				array_pop($arr_directory);
			}
			
			$arr_page_variables = [];			
		}
		
		SiteStartVars::$arr_dir = array_slice($arr_directory, 1);
		SiteStartVars::$dir = directories::traceDirectoryPath($arr_directory);
		SiteStartVars::setPageVariables($arr_page_variables);
		
		if (!SiteStartVars::$dir) { // No directory
			
			pages::noPage();
		}
		
		// Special page
		if (SiteStartVars::$page_kind == '.e') {
					
			if (SiteStartVars::$page_name == 'script') {
				
				Log::setMsg(getLabel('msg_no_script_support'));
				msg(getLabel('msg_enable_script'), 'SORRY', LOG_CLIENT);
			}
				
			$obj = Log::addToObj(Response::getObject());
			
			$page = new ExitPage($obj->msg, SiteStartVars::$page_name, SiteStartVars::$page_name);

			Response::stop($page->getPage(), '');
		}
		
		// Shortcut
		if (SiteStartVars::$page_kind == '.s') {
			
			pages::doShortcut();
		}

		// Page
		if (SiteStartVars::$page_kind == '.c') {
			
			$page_mod = explode('-', $_POST['mod']);
			
			$page_id = (int)$page_mod[0];
			if ($page_id) {
				$arr_page = pages::getPages($page_id);
				if (SiteStartVars::$dir['id'] == $arr_page['directory_id']) {
					SiteStartVars::$page = $arr_page;
				}
			}
			
			$mod_xy = explode('_', $page_mod[1]);
			SiteStartVars::$page_mod_xy['x'] = (int)$mod_xy[0];
			SiteStartVars::$page_mod_xy['y'] = (int)$mod_xy[1];

			$_SERVER['PATH_VIRTUAL'] = SiteEndVars::getLocation(true, true);
		} else {
			
			if ($str_page) { // Page match
				SiteStartVars::$page = pages::getPages(SiteStartVars::$page_name, SiteStartVars::$dir['id']);
			} else if (SiteStartVars::$dir['page_index_id']) { // Try directory index
				SiteStartVars::$page = pages::getPages(SiteStartVars::$dir['page_index_id']);
			}
		}
		
		if (!SiteStartVars::$page && SiteStartVars::$page_kind != '.l') { // No page
		
			pages::noPage();
		}
		
		// Login prepare
		$user_groups = array_filter(explode('/', SiteStartVars::$dir['user_group']));

		if (count($user_groups)) {
			
			SiteStartVars::requestSecure();
			$dir_key = array_keys($user_groups);
			$dir_key = end($dir_key);
			$arr_login_directory = array_slice($arr_directory, 0, $dir_key+1);
			SiteStartVars::$user_group = (int)end($user_groups);
			SiteStartVars::$login_dir = directories::traceDirectoryPath($arr_login_directory);
		}
				
		// Session
		SiteStartVars::startSession();
				
		// Login
		HomeLogin::index();
		
		if (!empty($_SESSION['USER_ID'])) {
			Log::updateRequestState(Log::IP_STATE_APPROVED);
		}
		
		// Language
		if (!empty($_SESSION['LANGUAGE_SYSTEM'])) {
			SiteStartVars::$language = $_SESSION['LANGUAGE_SYSTEM'];
		} else if (!empty($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['lang_code'])) {
			SiteStartVars::$language = $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['lang_code'];
		} else {
			if (empty($_SESSION['LANGUAGE_DEFAULT'])) {
				$arr_lang_default = cms_language::getDefaultLanguage(SERVER_NAME_SITE_NAME);
				$_SESSION['LANGUAGE_DEFAULT'] = $arr_lang_default['lang_code'];
			}
			SiteStartVars::$language = $_SESSION['LANGUAGE_DEFAULT'];
		}
		
		// Clearance
		if (!pages::filterClearance([SiteStartVars::$page], ($_SESSION['USER_GROUP'] ?? null), ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')] ?? null))) { // User has no clearance for page
			pages::noPage(); // No clearance, no page
		}
		
		// Return page
		if (SiteStartVars::$page_kind == '.c') {
			
			// Feedback
			if ($_POST['feedback']) {
				SiteStartVars::setFeedback($_POST['feedback']);
			}
					
			require('commands.php');
			
		} else if (SiteStartVars::$page_kind == '.manifest') {
						
			$str_url = implode('/', $arr_path_info);
			$str_url = (substr($str_url, -9) === '.manifest' ? substr($str_url, 0, -9) : str_replace('.manifest', '.p', $str_url));
			$str_identifier = 'manifest_'.str2Label($str_url);
			
			$json = Settings::getShare($str_identifier);
				
			if (!$json) {
				
				SiteStartVars::preloadModules();
				
				$arr_modules = pages::getPageModules(SiteStartVars::$page['id']);

				foreach ($arr_modules as $arr_module) {
						
					$mod = new $arr_module['module'];
					$mod->setMod($arr_module, $arr_module['id']);
					$mod->setModVariables($arr_module['var']);
					$mod->setModQuery(SiteStartVars::getModVariables($arr_module['id']));

					$mod->contents();
				}
				
				SiteEndVars::checkServerName();
				
				$str_url = SiteEndVars::getLocation(true, true);
				$str_identifier = 'manifest_'.str2Label($str_url);
				
				$str_title = SiteEndVars::getTitle();
				$str_description = SiteEndVars::getDescription();
				$str_image = SiteEndVars::getImage();
				$arr_theme = SiteEndVars::getTheme();
									
				$arr_images = [];

				foreach ([64, 96, 128, 192, 256, 512] as $nr_size) {
					
					$arr_images[] = [
						'src' => SiteStartVars::getCacheUrl('img', [$nr_size, $nr_size], $str_image),
						'type' => 'image/png',
						'sizes' => $nr_size.'x'.$nr_size
					];
				}

				$arr_manifest = [
					'short_name' => getLabel('name', 'D'),
					'name' => $str_title,
					'start_url' => $str_url,
					'description' => $str_description,
					'icons' => $arr_images,
					'theme_color' => $arr_theme['theme_color'],
					'background_color' => $arr_theme['background_color'],
					'display' => 'standalone'
				];
				
				SiteStartVars::cooldownModules();
				
				Response::setFormat(Response::OUTPUT_JSON);
				
				$json = Response::parse($arr_manifest);
				$json = Response::output($json);
				
				Settings::setShare($str_identifier, $json, 60);
			}
			
			header('Content-Type: application/manifest+json;charset=utf-8');
			
			echo $json;
		} else if (SiteStartVars::$page_kind == '.p') {
		
			if (!isset($_SESSION['PAGE_LOADED'])) {
				$_SESSION['PAGE_LOADED'] = 0;
				$_SESSION['LANDING_PAGE'] = SiteStartVars::$page; // Store landing page
			}
			if ($_SERVER['HTTP_REFERER'] && strpos($_SERVER['HTTP_REFERER'], URL_BASE) === false) {
				$_SESSION['REFERER_URL'] = $_SERVER['HTTP_REFERER']; // Store referer url
			}
			$_SESSION['PAGE_LOADED']++;
		
			if (SiteStartVars::$page['url']) {
				
				$arr_location_vars = SiteEndVars::getLocationVariables();
				
				if ($arr_location_vars) {
					Response::location(rtrim(SiteStartVars::$page['url'], '/').'/'.implode('/', $arr_location_vars));
				} else {
					Response::location(SiteStartVars::$page['url']);
				}
			}
			
			SiteStartVars::preloadModules();

			$template = templates::getTemplates(SiteStartVars::$page['actual_template_id']);
										
			$doc = new DOMDocument();
			$doc->strictErrorChecking = false;
			$doc->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>'.$template['html'].'</body></html>');
			// Remove <!DOCTYPE 
			$doc->removeChild($doc->firstChild);
			// Remove <html><head></head><body></body></html> 
			$doc->replaceChild($doc->firstChild->lastChild->firstChild, $doc->firstChild);
			
			$domxpath = new DOMXPath($doc);
			
			$arr_modules = pages::getPageModules(SiteStartVars::$page['id']);

			foreach ($arr_modules as $arr_module) {
				
				$doc_select = $domxpath->query('//div[@id="mod-'.$arr_module['x'].'_'.$arr_module['y'].'"]');
				
				if ($doc_select->length) {
					
					$mod = new $arr_module['module'];
					$mod->setMod($arr_module, $arr_module['id']);
					$mod->setModVariables($arr_module['var']);
					$mod->setModQuery(SiteStartVars::getModVariables($arr_module['id']));

					$content = $mod->contents();
					
					$doc_select->item(0)->setAttribute('class', $doc_select->item(0)->getAttribute('class').' '.$arr_module['module'].($mod->style ? ' '.$mod->style : ''));					
					
					if ($content) {
						
						$frag = $doc->createDocumentFragment(); // create fragment
						$frag->appendXML($content); // insert arbitary html into the fragment
						$doc_select->item(0)->appendChild($frag);
					}
				}
			}
			
			SiteEndVars::checkServerName();

			$html_body = $doc->saveHTML();
			
			$JSON = Response::getObject();
			$JSON->data_feedback = SiteEndVars::getFeedback();
			$JSON->location = ['replace' => true, 'url' => SiteEndVars::getLocation(), 'url_canonical' => SiteEndVars::getLocation(true, true)]; // Make sure the resulting location is clean
			$JSON = Log::addToObj($JSON);
			if (Settings::get('timing') === true) {
				$JSON->timing = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
			}
			SiteEndVars::addScript("PARSE = function() {"
				."var obj = JSON.parse(".value2JSON(value2JSON($JSON)).");"
				."FEEDBACK.check(document.body, obj);"
			."};");

			if (SiteStartVars::$page['script']) {
				SiteEndVars::addScript(SiteStartVars::$page['script']);
			}
			
			SiteStartVars::setJSCSS();
			require('./CMS/core_combine.php');
										
			$str_title = SiteEndVars::getTitle();
			$str_description = SiteEndVars::getDescription();
			$arr_theme = SiteEndVars::getTheme();
			
			$str_url_real = SiteEndVars::getLocation(false, true);
			$str_url_manifest = (strpos($str_url_real, '.p') !== false ? str_replace('.p', '.manifest', $str_url_real) : $str_url_real.'.manifest');
			$str_url = (SiteEndVars::getUrl() ?: SiteEndVars::getLocation(false));
			
			$str_image = SiteEndVars::getImage();
			$str_url_image = URL_BASE.ltrim($str_image, '/');
			
			$str_identifier = 'html_icons_'.str2Label($str_image);
			$html_icons = Settings::getShare($str_identifier);
				
			if (!$html_icons) {

				$html_icons = SiteEndVars::getIcons();
				
				Settings::setShare($str_identifier, $html_icons, 60);
			}
			
			$html = '<!DOCTYPE html>'.PHP_EOL
			.'<html lang="en">'.PHP_EOL
				.'<head>'.PHP_EOL
					.'<title>'.$str_title.'</title>'
					// Description
					.'<meta name="application-name" content="'.getLabel('name', 'D').'">'
					.'<meta name="description" content="'.$str_description.'" />'
					.'<meta name="keywords" content="'.SiteEndVars::getKeywords().'" />'
					.'<meta property="og:title" content="'.$str_title.'" />'
					.'<meta property="og:site_name" content="'.getLabel('name', 'D').'" />'
					.'<meta property="og:description" content="'.$str_description.'" />'
					.'<meta property="og:type" content="'.SiteEndVars::getType().'" />'
					// Linking
					.'<link rel="canonical" href="'.$str_url_real.'" />'
					.($str_url != $str_url_real ? '<link rel="shortlink" href="'.$str_url.'" />' : '')
					.'<meta property="og:url" content="'.$str_url.'" />'
					// Icons
					.$html_icons
					.'<meta property="og:image" content="'.$str_url_image.'" />'
					// Theme
					.'<meta name="theme-color" content="'.$arr_theme['theme_color'].'">'
					.'<link rel="manifest" href="'.$str_url_manifest.'"'.(SiteStartVars::$user_group ? ' crossOrigin="use-credentials"' : '').' />'
					.'<meta name="apple-mobile-web-app-capable" content="yes">'
					.'<meta name="apple-mobile-web-app-status-bar-style" content="black">';
					// CSS & JS
					$version = CombineJSCSS::getVersion(SiteStartVars::$js_css['css'], SiteStartVars::$arr_modules);
					$html .= '<link href="/combine/css/'.$version.'" rel="stylesheet" type="text/css" />';
					$version = CombineJSCSS::getVersion(SiteStartVars::$js_css['js'], SiteStartVars::$arr_modules);
					$html .= '<script type="text/javascript" src="/combine/js/'.$version.'"></script>';
					// Other
					$html .= SiteEndVars::getHeadTags();
					
					if (getLabel('analytics_account', 'D', true)) {
						
						$html .= '<script type="text/javascript">'
							."(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){"
							."(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),"
							."m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)"
							."})(window,document,'script','//www.google-analytics.com/analytics.js','ga');"
							."ga('create', '".getLabel('analytics_account', 'D')."', 'auto');"
							."ga('send', 'pageview');"
						.'</script>';
					}

				$html .= PHP_EOL.'</head>'.PHP_EOL;
				
				$arr_dir_classes = [];
				foreach (explode('/', SiteStartVars::$dir['path']) as $value) {
					if ($value) {
						$arr_dir_classes[] = 'dir-'.$value;
					}
				}
				
				$str_content_identifier = SiteEndVars::getContentIdentifier();
				
				$html .= '<body id="page-'.SiteStartVars::$page['id'].'" class="page-'.SiteStartVars::$page['name'].($arr_dir_classes ? ' '.implode(' ', $arr_dir_classes) : '').'"'.($str_content_identifier ? ' data-content="'.$str_content_identifier.'"' : '').'>'.PHP_EOL
					.(MESSAGE ? '<div class="important"><p><span class="icon">'.getIcon('attention').'</span><span>'.Labels::parseTextVariables(MESSAGE).'</span></p></div>' : '')
					.$html_body
					.Labels::parseTextVariables(SiteStartVars::$page['html'])
					.getLabel('html', 'D')
				.PHP_EOL.'</body>'.PHP_EOL
			.'</html>';
			
			SiteStartVars::cooldownModules();
			
			Response::stop($html, false);
		} else {
		
			pages::noPage();
		}
	}
