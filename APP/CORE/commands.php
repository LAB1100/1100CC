<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// 1100CC Framework:

	SiteStartEnvironment::preloadModules();
	
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

		if (empty($arr_command['module'])) {
			
			// Nothing
		} else if ($arr_command['module'] == 'cms_general') {
		
			$general = new cms_general;
			$general->commands($arr_command['method'], $arr_command['id'], $arr_command['value']);
			
			$JSON_command->html = $general->html;
		} else {
			
			$module = $arr_command['module'];
			$method = $arr_command['method'];
			$page_module = $arr_command['mod'];
			$is_valid = (is_string($module) && is_string($method) && is_string($page_module));
			
			if (!$is_valid || str2Name($module.$method, '-_') != $module.$method) {
				error('Request targets an invalid module or method.', TROUBLE_INVALID_REQUEST, LOG_CLIENT);
			}
			
			if ($is_multi) {
				
				$arr_page_module = explode('-', $page_module);
				$arr_module_xy = explode('_', ($arr_page_module[1] ?? ''));
				SiteStartEnvironment::setContext(SiteStartEnvironment::CONTEXT_MODULE_X, $arr_module_xy[0]);
				SiteStartEnvironment::setContext(SiteStartEnvironment::CONTEXT_MODULE_Y, $arr_module_xy[1]);
			}

			// Check if module command really originates from valid source directory and page
			
			$res = DB::query("SELECT
				d.id, m.id AS module_id, m.module, m.var,
				CASE 
					WHEN m.page_id = pm2.id THEN 2
					WHEN m.page_id = pm.id THEN 1
					ELSE 0
				END AS parent_level
					FROM ".DB::getTable('TABLE_PAGES')." p
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." pm ON (pm.id = p.master_id)
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." pm2 ON (pm2.id = pm.master_id)
					JOIN ".DB::getTable('TABLE_DIRECTORIES')." d ON (d.id = p.directory_id)
					JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.page_id = p.id OR m.page_id = pm.id OR m.page_id = pm2.id)
				WHERE p.id = ".(int)SiteStartEnvironment::getPage('id')."
					AND m.x = ".(int)SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_MODULE_X)."
					AND m.y = ".(int)SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_MODULE_Y)."
					AND d.id = ".(int)SiteStartEnvironment::getDirectory('id')."
				ORDER BY parent_level ASC
			");

			if (!$res->getRowCount()) {
				
				$arr_page_directory = directories::getDirectories(SiteStartEnvironment::getPage('directory_id'));
				
				error('Request originates from invalid path.', TROUBLE_INVALID_REQUEST,
					(STATE == STATE_DEVELOPMENT || getLabel('show_system_errors', 'D', true) ? LOG_BOTH : LOG_CLIENT),
					'Path '.SiteStartEnvironment::getDirectory('path').' targets '.strEscapeHTML($module).':'.strEscapeHTML($method).'. Using: '.str_replace(' ', '', $arr_page_directory['path']).' '.strEscapeHTML($page_module)
				);
			}

			$arr = $res->fetchAssoc();

			$mod = new $arr['module'];
			$mod->setMod($arr, $arr['module_id']);
			$mod->setModVariables($arr['var']);
			$mod->setModQuery(SiteStartEnvironment::getModuleVariables($arr['module_id']));

			if ($module != 'this' && $module != $arr['module']) { // Targetting module other than source module
				
				$module_target = $module;
				
				$arr_mod_target = $mod->getExternalModule($module);
				$arr_mod_target_method = ($arr_mod_target[$method] ?? null);
				
				if ($arr_mod_target === null) { // Disallow all
					
					$module = false;
				} else if ($arr_mod_target_method === true) { // Allow specific method
					
					$module = $module;
				} else if ($arr_mod_target_method === false) { // Disallow specific method
					
					$module = false;
				} else if ($arr_mod_target_method !== null) { // Override
					
					if ($arr_mod_target_method['module']) {
						$module = $arr_mod_target_method['module'];
					}
					if ($arr_mod_target_method['method']) {
						$method = $arr_mod_target_method['method'];
					}
				} else if (isset($arr_mod_target['*'])) { // Override any
					
					$arr_mod_target_method = $arr_mod_target['*'];
					
					if ($arr_mod_target_method === false) {
						
						$module = false;
					} else {
						
						if ($arr_mod_target_method['module']) {
							$module = $arr_mod_target_method['module'];
						}
						if ($arr_mod_target_method['method']) {
							$method = $arr_mod_target_method['method'];
						}
					}
				} else { // Allow by abstaining
					
					$module = $module;
				}
				
				if ($module == false) {
					
					error('Module does not allow the requested relay.', TROUBLE_INVALID_REQUEST,
						(STATE == STATE_DEVELOPMENT || getLabel('show_system_errors', 'D', true) ? LOG_BOTH : LOG_CLIENT),
						'Module '.$arr['module'].' does not allow relaying '.strEscapeHTML($module_target)
					);
				} else if ($module != 'this' && $module != $arr['module']) {
					
					$mod_target = new $module;
					$mod_target->setModVariables($arr_mod_target['mod_var']);
					$mod_target->setModQuery($arr_mod_target['arr_query']);
					$mod = $mod_target;
				}
			}

			if ($method) {
				
				if (isset($arr_command['is_confirm'])) {
					$mod->is_confirm = (bool)$arr_command['is_confirm'];
				}
				if (isset($arr_command['is_download'])) {
					$mod->is_download = (bool)$arr_command['is_download'];
				}
				if (isset($arr_command['is_discard'])) {
					$mod->is_discard = (bool)$arr_command['is_discard'];
				}
			}
			
			$JSON_command->html =& $mod->html;

			$mod->commands($method, $arr_command['id'], $arr_command['value']);
			
			if ($mod->refresh) {
				
				$JSON_command->html = $mod->contents();
			}
			
			SiteEndEnvironment::checkServerName();
			
			$JSON_command->do_confirm = $mod->do_confirm;
			$JSON_command->do_download = $mod->do_download;
			$JSON_command->validate = $mod->validate;
			$JSON_command->data = $mod->data;
			$JSON_command->refresh_table = $mod->refresh_table;
			$JSON_command->reset_form = $mod->reset_form;
			
			if ($mod->msg) {
				
				if ($mod->msg !== true) {
					Log::setMsg($mod->msg);
				} else {
					Log::setMsg(getLabel('msg_success'));
				}
			}
		}
		
		unset($JSON_command);
	}

	SiteStartEnvironment::cooldownModules();

	$JSON->data_feedback = SiteEndEnvironment::getFeedback();

	$JSON = Log::addToObj($JSON);
	$JSON->timestamp = date('c');
	if (Settings::get('timing') === true) {
		$JSON->timing = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
	}
	
	Response::location(['real' => SiteEndEnvironment::getLocation(), 'canonical' => SiteEndEnvironment::getLocation(true, SiteEndEnvironment::LOCATION_CANONICAL_NATIVE)]);
	
	Response::stop('', $JSON);
