<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// 1100CC Framework:

	$arr_uri_translator = SiteStartEnvironment::getURITranslator();
	
	$arr_request_variables = SiteStartEnvironment::getRequestVariables();
	$arr_request_variables = array_slice($arr_request_variables, 1); // Remove possible leading '/'
	$arr_modifier_variables = SiteStartEnvironment::getModifierVariables();
	
	$str_identifier = implode('/', $arr_request_variables);

	if ($arr_modifier_variables) {
		
		$str_query = '?'.rawurldecode(http_build_query($arr_modifier_variables));
				
		$str_identifier .= $str_query;
	}
	
	$arr_uri = uris::getURI($arr_uri_translator['id'], uris::MODE_IN, $str_identifier);
	
	if ($arr_uri) {

		$str_url = uris::getURL($arr_uri['url'], $arr_uri_translator['host_name']);
		
		if ($arr_uri_translator['delay']) {
			
			Log::setMsg(getLabel('msg_redirect'));
			
			if ($arr_uri_translator['show_remark'] && $arr_uri['remark']) {
				msg(parseBody($arr_uri['remark']), 'DESCRIPTION', LOG_CLIENT);
			}
			
			msg('<a href="'.$str_url.'">'.$str_url.'</a>', 'URL', LOG_CLIENT);
			
			$obj = Log::addToObj(Response::getObject());
			
			$page = new ExitPage($obj->msg, 'redirect...', 'redirect');
			
			$page->setTitle(getLabel('name', 'D').' | '.$arr_uri['identifier']);
			
			$page->addScript("
				window.setTimeout(function() {
						window.location.href='".$str_url."'
					}, ".$arr_uri_translator['delay']."
				);
			");
			
			$style = Settings::get('exit_page_css_redirect');
			
			if ($style) {
				$page->addStyle($style);
			}
			
			$page->addHeadtag('<noscript>
				<meta http-equiv="refresh" content="'.($arr_uri_translator['delay']/1000).';URL=\''.$str_url.'/\'" />
			</noscript>');
			
			Response::stop($page->getPage(), '');
			
		} else if (!uris::isURLInternal($arr_uri['url'], $arr_uri_translator['host_name'])) {
			
			Response::location($str_url);
		}
		
		$arr_parts = str2Array($arr_uri['url'], '?');
		
		$arr_query = [];
		
		if (!empty($arr_parts[1])) {
			parse_str($arr_parts[1], $arr_query);
		}
		
		SiteStartEnvironment::setModifierVariables($arr_query);
		
		$arr_path_info = [];
		
		if ($arr_parts[0] != '' && $arr_parts[0] != '/') {
			$arr_path_info = explode('/', $arr_parts[0]);
		}
		
		SiteStartEnvironment::setRequestVariables($arr_path_info);
	}
