<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// 1100CC Framework:

	$arr_uri_translator = SiteStartVars::$uri_translator;
	$arr_request_vars = SiteStartVars::getModVariables(0);
	
	$str_identifier = implode('/', $arr_request_vars);
	
	$arr_uri = uris::getURI($arr_uri_translator['id'], $str_identifier);
	
	if ($arr_uri) {

		$url = uris::getURL($arr_uri['url'], $arr_uri_translator['host_name']);
		
		if ($arr_uri_translator['delay']) {
			
			Log::setMsg(getLabel('msg_redirect'));
			
			if ($arr_uri_translator['show_remark'] && $arr_uri['remark']) {
				msg(parseBody($arr_uri['remark']), 'DESCRIPTION', LOG_CLIENT);
			}
			
			msg('<a href="'.$url.'">'.$url.'</a>', 'URL', LOG_CLIENT);
			
			$obj = Log::addToObj(Response::getObject());
			
			$page = new ExitPage($obj->msg, 'redirect...', 'redirect');
			
			$page->setTitle(getLabel('name', 'D').' | '.$arr_uri['identifier']);
			
			$page->addScript("
				window.setTimeout(function() {
						window.location.href='".$url."'
					}, ".$arr_uri_translator['delay']."
				);
			");
			
			$style = Settings::get('exit_page_css_redirect');
			
			if ($style) {
				$page->addStyle($style);
			}
			
			$page->addHeadtag('<noscript>
				<meta http-equiv="refresh" content="'.($arr_uri_translator['delay']/1000).';URL=\''.$url.'/\'" />
			</noscript>');
			
			Response::stop($page->getPage(), '');
		} else {
			
			Response::location($url);
		}
	}
