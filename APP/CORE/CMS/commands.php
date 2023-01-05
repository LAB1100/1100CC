<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// 1100CC Framework:

	SiteStartVars::preloadModules();
					
	$JSON = Response::getObject();

	if (!empty($_POST['module'])) {

		$class = new $_POST['module'];
		$method = $_POST['method'];
		
		if ($method) {
			
			if (isset($_POST['is_confirm'])) {
				$class->is_confirm = (bool)$_POST['is_confirm'];
			}
			if (isset($_POST['is_download'])) {
				$class->is_download = (bool)$_POST['is_download'];
			}
			if (isset($_POST['is_discard'])) {
				$class->is_discard = (bool)$_POST['is_discard'];
			}
		}
		
		$JSON->html =& $class->html;
		
		$class->commands($method, $_POST['id'], $_POST['value']);
		
		if ($class->refresh) {
			
			$JSON->html = $class->contents();
		}
		
		SiteEndVars::checkServerName();
		
		$JSON->do_confirm = $class->do_confirm;
		$JSON->do_download = $class->do_download;
		$JSON->validate = $class->validate;
		$JSON->data = $class->data;
		$JSON->refresh_table = $class->refresh_table;
		$JSON->reset_form = $class->reset_form;
		
		if ($class->msg) {
			if ($class->msg !== true) {
				Log::setMsg($class->msg);
			} else {
				Log::setMsg(getLabel('msg_success'));
			}
		}
	}
	
	SiteStartVars::cooldownModules();
	
	$JSON->data_feedback = SiteEndVars::getFeedback();
	
	$JSON = Log::addToObj($JSON);
	$JSON->timestamp = date('c');
	if (Settings::get('timing') === true) {
		$JSON->timing = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
	}
	
	Response::stop('', $JSON);
