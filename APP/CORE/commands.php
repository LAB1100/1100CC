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
	
	if ($_POST['module'] == 'cms_general') {
	
		$general = new cms_general;
		$general->commands($_POST['method'], $_POST['id'], $_POST['value']);
		
		$JSON->html = $general->html;
	
	} else if ($_POST['module']) {
		
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
			
			error('Request originates from invalid path: '.SiteStartVars::$dir['path'].' => '.htmlspecialchars($module).':'.htmlspecialchars($method).' (using: '.str_replace(' ', '', $page_dir['path']).' '.htmlspecialchars($_POST['mod']).')');
		}

		$arr = $res->fetchAssoc();

		$mod = new $arr['module'];
		$mod->setMod($arr, $arr['module_id']);
		$mod->setModVariables($arr['var']);
		$mod->setModQuery(SiteStartVars::getModVariables($arr['module_id']));

		if ($module != 'this' && $module != $arr['module']) { // Targetting module other than source module
			
			$arr_mod_target = $mod->getExternalModule($module);
			$module_target = $module;
			
			if ($arr_mod_target === null) { // Disallow all
				
				$module = false;
			} else if ($arr_mod_target[$method] === null) { // Allow or disallow method by abstaining
					
				if ($arr_mod_target['*'] === false) {
				
					$module = false;
				} else {
					
					$module = $module;
				}
			} else if ($arr_mod_target[$method] === true) { // Allow specific method
				
				$module = $module;
			} else if ($arr_mod_target[$method] === false) { // Disallow specific method
				
				$module = false;
			} else if ($arr_mod_target[$method]) { // Override
				
				if ($arr_mod_target[$method]['module']) {
					$module = $arr_mod_target[$method]['module'];
				}
				if ($arr_mod_target[$method]['method']) {
					$method = $arr_mod_target[$method]['method'];
				}
			}
			
			if ($module == false) {
				
				error('Module '.$arr['module'].' does not allow relaying '.htmlspecialchars($module_target));
			} else if ($module != 'this' && $module != $arr['module']) {
				
				$mod_target = new $module;
				$mod_target->setModVariables($arr_mod_target['mod_var']);
				$mod_target->setModQuery($arr_mod_target['arr_query']);
				$mod = $mod_target;
			}
		}
		
		$JSON->html =& $mod->html;
		
		$method = ($method && $_POST['confirmed'] ? ['method' => $method, 'confirmed' => true] : $method);

		$mod->commands($method, $_POST['id'], $_POST['value']);
		
		if ($mod->refresh) {
			
			$JSON->html = $mod->contents();
		}
		
		SiteEndVars::checkServerName();
		
		$JSON->confirm = $mod->confirm;
		$JSON->download = $mod->download;
		$JSON->validate = ($mod->validate && !is_array($mod->validate) ? json_decode($mod->validate) : $mod->validate);
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
	
	Response::stop('', $JSON);
