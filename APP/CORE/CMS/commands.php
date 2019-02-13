<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// 1100CC Framework:

	SiteStartVars::preloadModules();
					
	$JSON = Response::getObject();

	if ($_POST['module']) {

		$class = new $_POST['module'];
		$method = ($_POST['method'] && $_POST['confirmed'] ? ['method' => $_POST['method'], 'confirmed' => true] : $_POST['method']);
		
		$JSON->html =& $class->html;
		
		$class->commands($method, $_POST['id'], $_POST['value']);
		
		if ($class->refresh) {
			
			$JSON->html = $class->contents();
		}
		
		SiteEndVars::checkServerName();
		
		$JSON->confirm = $class->confirm;
		$JSON->download = $class->download;
		$JSON->validate = ($class->validate && !is_array($class->validate) ? json_decode($class->validate) : $class->validate);
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
	
	Response::stop('', $JSON);
