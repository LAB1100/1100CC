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
	
	$is_multi = isset($_POST['multi']);
	$arr_commands = [];
	
	if ($is_multi) {
		
		$arr_commands = (array)$_POST['multi'];
		unset($_POST['multi']);
	} else {
		
		$arr_commands[] = $_POST;
	}
	
	foreach ($arr_commands as $arr_command) {
		
		if ($is_multi) {
			
			$_POST = [];
			
			foreach ($arr_command as $key => $value) {
				
				if ($key == 'json') {
					continue;
				}
				
				$_POST[$key] = $value;
			}
			
			if (!empty($arr_command['json'])) { // Posted data in serialized format, check for JSON data
			
				$arr = JSON2Value($arr_command['json']);
				unset($arr_command['json']);
				
				foreach ($arr as $key => $value) {
					$_POST[$key] = $value;
				}
				unset($arr);
			}
			
			$JSON_command = (object)[];
			$JSON->multi[] =& $JSON_command;
		} else {
			
			$JSON_command =& $JSON;
		}

		if (!empty($arr_command['module'])) {

			$class = new $arr_command['module'];
			$method = $arr_command['method'];
			
			if ($method) {
				
				if (isset($arr_command['is_confirm'])) {
					$class->is_confirm = (bool)$arr_command['is_confirm'];
				}
				if (isset($arr_command['is_download'])) {
					$class->is_download = (bool)$arr_command['is_download'];
				}
				if (isset($arr_command['is_discard'])) {
					$class->is_discard = (bool)$arr_command['is_discard'];
				}
			}
			
			$JSON_command->html =& $class->html;
			
			$class->commands($method, $arr_command['id'], $arr_command['value']);
			
			if ($class->refresh) {
				
				$JSON_command->html = $class->contents();
			}
			
			SiteEndVars::checkServerName();
			
			$JSON_command->do_confirm = $class->do_confirm;
			$JSON_command->do_download = $class->do_download;
			$JSON_command->validate = $class->validate;
			$JSON_command->data = $class->data;
			$JSON_command->refresh_table = $class->refresh_table;
			$JSON_command->reset_form = $class->reset_form;
			
			if ($class->msg) {
				if ($class->msg !== true) {
					Log::setMsg($class->msg);
				} else {
					Log::setMsg(getLabel('msg_success'));
				}
			}
		}
		
		unset($JSON_command);
	}
	
	SiteStartVars::cooldownModules();
	
	$JSON->data_feedback = SiteEndVars::getFeedback();
	
	$JSON = Log::addToObj($JSON);
	$JSON->timestamp = date('c');
	if (Settings::get('timing') === true) {
		$JSON->timing = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
	}
	
	Response::stop('', $JSON);
