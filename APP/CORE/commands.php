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
	
	if (empty($_POST['module'])) {
		
		// Nothing
	} else if ($_POST['module'] == 'cms_general') {
	
		$general = new cms_general;
		$general->commands($_POST['method'], $_POST['id'], $_POST['value']);
		
		$JSON->html = $general->html;
	
	} else {
		
		$module = $_POST['module'];
		$method = $_POST['method'];

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
			WHERE p.id = ".(int)SiteStartVars::$page['id']."
				AND m.x = ".(int)SiteStartVars::$page_mod_xy['x']."
				AND m.y = ".(int)SiteStartVars::$page_mod_xy['y']."
				AND d.id = ".(int)SiteStartVars::$dir['id']."
			ORDER BY parent_level ASC
		");

		if (!$res->getRowCount()) {
			
			$page_dir = directories::getDirectories(SiteStartVars::$page['directory_id']);
			
			error('Request originates from invalid path: '.SiteStartVars::$dir['path'].' => '.strEscapeHTML($module).':'.strEscapeHTML($method).' (using: '.str_replace(' ', '', $page_dir['path']).' '.strEscapeHTML($_POST['mod']).')');
		}

		$arr = $res->fetchAssoc();

		$mod = new $arr['module'];
		$mod->setMod($arr, $arr['module_id']);
		$mod->setModVariables($arr['var']);
		$mod->setModQuery(SiteStartVars::getModVariables($arr['module_id']));

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
				
				error('Module '.$arr['module'].' does not allow relaying '.strEscapeHTML($module_target));
			} else if ($module != 'this' && $module != $arr['module']) {
				
				$mod_target = new $module;
				$mod_target->setModVariables($arr_mod_target['mod_var']);
				$mod_target->setModQuery($arr_mod_target['arr_query']);
				$mod = $mod_target;
			}
		}

		if ($method) {
			
			if (isset($_POST['is_confirm'])) {
				$mod->is_confirm = (bool)$_POST['is_confirm'];
			}
			if (isset($_POST['is_download'])) {
				$mod->is_download = (bool)$_POST['is_download'];
			}
			if (isset($_POST['is_discard'])) {
				$mod->is_discard = (bool)$_POST['is_discard'];
			}
		}
		
		$JSON->html =& $mod->html;

		$mod->commands($method, $_POST['id'], $_POST['value']);
		
		if ($mod->refresh) {
			
			$JSON->html = $mod->contents();
		}
		
		SiteEndVars::checkServerName();
		
		$JSON->do_confirm = $mod->do_confirm;
		$JSON->do_download = $mod->do_download;
		$JSON->validate = $mod->validate;
		$JSON->data = $mod->data;
		$JSON->refresh_table = $mod->refresh_table;
		$JSON->reset_form = $mod->reset_form;
		
		if ($mod->msg) {
			if ($mod->msg !== true) {
				Log::setMsg($mod->msg);
			} else {
				Log::setMsg(getLabel('msg_success'));
			}
		}
	}

	SiteStartVars::cooldownModules();

	$JSON->location = ['url' => SiteEndVars::getLocation()];
	$JSON->data_feedback = SiteEndVars::getFeedback();

	$JSON = Log::addToObj($JSON);
	$JSON->timestamp = date('c');
	if (Settings::get('timing') === true) {
		$JSON->timing = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
	}
	
	Response::stop('', $JSON);
