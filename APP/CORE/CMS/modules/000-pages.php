<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_PAGES', DB::$database_cms.'.site_pages');
DB::setTable('TABLE_PAGE_MODULES', DB::$database_cms.'.site_page_modules');
DB::setTable('TABLE_PAGE_INTERNAL_TAGS', DB::$database_cms.'.site_page_internal_tags');

class pages extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	public static function webLocations() {
		
		return [
			'name' => 'pages',
			'entries' => function() {
				
				$arr_pages = static::getPagesByScope(false, false, false, false, null, false);
				
				foreach ($arr_pages as $arr_page) {
					
					if ($arr_page['require_login'] || $arr_page['clearance']) {
						continue;
					}
					
					$str_location = static::getPageURL($arr_page, true);
					
					yield $str_location;
				}
			}
		];
	}
	
	public static function js() {
		
		$return = "$(document).on('change', '[id=y\\\:pages\\\:directory_select-0]', function() {
		
			$(this).quickCommand($(this).closest('form').find('[name=page_id]'));
		});";
				
		return $return;
	}
	
	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "popup_link") {
				
			$return = '<form data-method="return_link">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_directory').'</label>
						<div><select name="directory_id" id="y:pages:directory_select-0">'.directories::createDirectoriesDropdown(directories::getDirectories()).'</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_page').'</label>
						<div><select name="page_id">'.cms_general::createDropdown(pages::getPages(0, directories::getRootDirectory())).'</select></div>
					</li>
				</ul></fieldset>
			</form>';
		
			$this->html = $return;
		}

		// POPUP INTERACT
		
		if ($method == "directory_select") {
		
			$this->html = cms_general::createDropdown(pages::getPages(0, $value));
		}
							
		// QUERY
		
		if ($method == "return_link") {
				
			$this->html = pages::getPageURL(pages::getPages($_POST['page_id']), true);
		}
	}
			
	public static function getPageModules($page_id) {
		
		$arr = [];
		$arr_sql_xy = [];
		
		$res = DB::query("SELECT
			m.*, 'set' AS class
				FROM ".DB::getTable('TABLE_PAGES')." p
				JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.page_id = p.id)
			WHERE p.id = ".(int)$page_id."
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['shortcut_root'] = DBFunctions::unescapeAs($arr_row['shortcut_root'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[$arr_row['id']] = $arr_row;
			
			$xy = '(m.x = '.$arr_row['x'].' AND m.y = '.$arr_row['y'].')';
			$arr_sql_xy[$xy] = $xy;
		}
		
		$res = DB::query("SELECT
			m.*, 'master' AS class
				FROM ".DB::getTable('TABLE_PAGES')." pm
				JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.page_id = pm.master_id)
			WHERE pm.id = ".(int)$page_id."
				".($arr_sql_xy ? "AND NOT ".implode(' AND NOT ', $arr_sql_xy) : "")."
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['shortcut_root'] = DBFunctions::unescapeAs($arr_row['shortcut_root'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[$arr_row['id']] = $arr_row;
		
			$xy = '(m.x = '.$arr_row['x'].' AND m.y = '.$arr_row['y'].')';
			$arr_sql_xy[$xy] = $xy;
		}
		
		$res = DB::query("SELECT
			m.*, 'master' AS class
				FROM ".DB::getTable('TABLE_PAGES')." pm
				JOIN ".DB::getTable('TABLE_PAGES')." pm2 ON (pm2.id = pm.master_id)
				JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.page_id = pm2.master_id)
			WHERE pm.id = ".(int)$page_id."
				".($arr_sql_xy ? "AND NOT ".implode(' AND NOT ', $arr_sql_xy) : "")."
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['shortcut_root'] = DBFunctions::unescapeAs($arr_row['shortcut_root'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[$arr_row['id']] = $arr_row;
			
			$xy = '(m.x = '.$arr_row['x'].' AND m.y = '.$arr_row['y'].')';
			$arr_sql_xy[$xy] = $xy;
		}

		return $arr;
	}
	
	public static function getDirectoryModules($directory_id, $directory_depth = false) {
		
		$arr = [];
		
		$res = DB::query("SELECT
			m.*, p.directory_id, p.name AS page_name, p.title AS page_title, p.master_id, p.clearance
				FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." d
				JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.directory_id = d.descendant_id)
				JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.page_id = p.id)
			WHERE d.ancestor_id = ".(int)$directory_id."
			".($directory_depth ? "AND d.path_length <= ".$directory_depth."" : "")."
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['shortcut_root'] = DBFunctions::unescapeAs($arr_row['shortcut_root'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['clearance'] = DBFunctions::unescapeAs($arr_row['clearance'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[$arr_row['id']] = $arr_row;
		}
		
		return $arr;
	}
	
	public static function getModulesByScope($root = 0, $directory_id = 0, $module = 0, $var = false, $var_name = false) {
		
		$root = ($root ?: directories::getRootDirectory());
		
		if ($module) {
			$is_num_module = (is_numeric($module) || (is_array($module) && is_numeric(current($module))));
			$is_arr_module = is_array($module);
		}
		
		$res = DB::query("SELECT
			m.*, p.directory_id, p.name AS page_name, p.title AS page_title, p.master_id, p.clearance, i.name AS directory_name, i.title AS directory_title, i.user_group_id, i.require_login
				FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." d
				JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." cd ON (cd.descendant_id = d.descendant_id)
				JOIN ".DB::getTable('TABLE_DIRECTORIES')." i ON (i.id = cd.ancestor_id)
				LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.directory_id = cd.ancestor_id)
				LEFT JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.page_id = p.id)
			WHERE 
				d.ancestor_id = ".$root."
				".($directory_id ? "AND cd.descendant_id = ".(int)$directory_id."" : "")."
				".($module ? "AND" : "")."
				".($module && $is_num_module && $is_arr_module ? "m.id IN (".implode(",", $module).")" : "")."
				".($module && $is_num_module && !$is_arr_module ? "m.id = ".$module : "")."
				".($module && !$is_num_module && $is_arr_module ? "m.module IN ('".implode("','", $module)."')" : "")."
				".($module && !$is_num_module && !$is_arr_module ? "m.module = '".$module."'" : "")."
				".($var !== false && $var_name ? "AND m.var LIKE '%\"".DBFunctions::strEscape($var_name)."\":\"".DBFunctions::strEscape($var)."\"%'" : "")."
				".($var !== false && !$var_name ? "AND m.var = '".DBFunctions::strEscape($var)."'" : "")."
				AND cd.path_length <= d.path_length
			ORDER BY cd.path_length DESC, p.sort
		");

		$arr = [];
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['shortcut_root'] = DBFunctions::unescapeAs($arr_row['shortcut_root'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['clearance'] = DBFunctions::unescapeAs($arr_row['clearance'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['require_login'] = DBFunctions::unescapeAs($arr_row['require_login'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[$arr_row['id']] = $arr_row;
		}
		
		return $arr;
	}
	
	public static function getModules($module, $var = false, $var_name = false) {
	
		$is_num_module = (is_numeric($module) || (is_array($module) && is_numeric(current($module))));
		$is_arr_module = is_array($module);

		$res = DB::query("SELECT
			m.*, p.directory_id, p.name AS page_name, p.title AS page_title, p.master_id, p.clearance, d.name AS directory_name, d.title AS directory_title, d.user_group_id, d.require_login
				FROM ".DB::getTable('TABLE_PAGE_MODULES')." m
				JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
				JOIN ".DB::getTable('TABLE_DIRECTORIES')." d ON (d.id = p.directory_id)
			WHERE
			".($is_num_module && $is_arr_module ? "m.id IN (".implode(",", $module).")" : "")."
			".($is_num_module && !$is_arr_module ? "m.id = ".$module : "")."
			".(!$is_num_module && $is_arr_module ? "m.module IN ('".implode("','", $module)."')" : "")."
			".(!$is_num_module && !$is_arr_module ? "m.module = '".$module."'" : "")."
			".($var !== false && $var_name ? "AND m.var LIKE '%\"".DBFunctions::strEscape($var_name)."\":\"".DBFunctions::strEscape($var)."\"%'" : "")."
			".($var !== false && !$var_name ? "AND m.var = '".DBFunctions::strEscape($var)."'" : "")."
		");
		
		if ($is_arr_module || !$is_num_module) {
			
			$arr = [];
			
			while ($arr_row = $res->fetchAssoc()) {
				
				$arr_row['shortcut_root'] = DBFunctions::unescapeAs($arr_row['shortcut_root'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['clearance'] = DBFunctions::unescapeAs($arr_row['clearance'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['require_login'] = DBFunctions::unescapeAs($arr_row['require_login'], DBFunctions::TYPE_BOOLEAN);
			
				$arr[$arr_row['id']] = $arr_row;
			}
		} else {
			
			$arr = $res->fetchAssoc();
			
			if ($arr) {
				$arr['shortcut_root'] = DBFunctions::unescapeAs($arr['shortcut_root'], DBFunctions::TYPE_BOOLEAN);
				$arr['clearance'] = DBFunctions::unescapeAs($arr['clearance'], DBFunctions::TYPE_BOOLEAN);
				$arr['require_login'] = DBFunctions::unescapeAs($arr['require_login'], DBFunctions::TYPE_BOOLEAN);
			}
		}

		return $arr;
	}
	
	public static function getPages($page = 0, $directory_id = 0, $do_limit_master = false, $selected_page_id = 0, $is_published = null, $is_clearance = null) {
	
		$arr = [];

		if ((int)$page && !$directory_id) {
			
			$res = DB::query("SELECT
				p.*,
				CASE
					WHEN pm.master_id != 0 THEN 2
					WHEN p.master_id != 0 THEN 1
					ELSE 0
				END AS master_level,
				CASE
					WHEN p.template_id != 0 THEN p.template_id
					WHEN pm.template_id != 0 THEN pm.template_id
					ELSE pm2.template_id
				END AS actual_template_id
					FROM ".DB::getTable('TABLE_PAGES')." p
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." pm ON (pm.id = p.master_id)
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." pm2 ON (pm2.id = pm.master_id)
				WHERE p.id = ".(int)$page."
			");
			
			$arr = $res->fetchAssoc();
			
			if ($arr) {
				$arr['publish'] = DBFunctions::unescapeAs($arr['publish'], DBFunctions::TYPE_BOOLEAN);
				$arr['clearance'] = DBFunctions::unescapeAs($arr['clearance'], DBFunctions::TYPE_BOOLEAN);
			}
		} else if ($page && $directory_id) {
			
			$res = DB::query("SELECT
				p.*,
				CASE
					WHEN pm.master_id != 0 THEN 2
					WHEN p.master_id != 0 THEN 1
					ELSE 0
				END AS master_level,
				CASE
					WHEN p.template_id != 0 THEN p.template_id
					WHEN pm.template_id != 0 THEN pm.template_id
					ELSE pm2.template_id
				END AS actual_template_id
					FROM ".DB::getTable('TABLE_PAGES')." p
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." pm ON (pm.id = p.master_id)
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." pm2 ON (pm2.id = pm.master_id)
				WHERE p.name = '".DBFunctions::strEscape($page)."'
					AND p.directory_id = ".(int)$directory_id."
			");
			
			$arr = $res->fetchAssoc();
			
			if ($arr) {
				$arr['publish'] = DBFunctions::unescapeAs($arr['publish'], DBFunctions::TYPE_BOOLEAN);
				$arr['clearance'] = DBFunctions::unescapeAs($arr['clearance'], DBFunctions::TYPE_BOOLEAN);
			}
		} else {
			
			$res = DB::query("SELECT
				p.*,
				CASE
					WHEN pm.master_id != 0 THEN 2
					WHEN p.master_id != 0 THEN 1
					ELSE 0
				END AS master_level,
				CASE
					WHEN p.template_id != 0 THEN p.template_id
					WHEN pm.template_id != 0 THEN pm.template_id
					ELSE pm2.template_id
				END AS actual_template_id
					FROM ".DB::getTable('TABLE_PAGES')." p
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." pm ON (pm.id = p.master_id)
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." pm2 ON (pm2.id = pm.master_id)
				WHERE p.id != ".(int)$selected_page_id."
					".($directory_id && is_array($directory_id) ? "AND p.directory_id IN (".implode(",", $directory_id).")": "")."
					".($directory_id && !is_array($directory_id) ? "AND p.directory_id = ".(int)$directory_id."": "")."
					".($do_limit_master ? "AND CASE
							WHEN pm.master_id != 0 THEN 2
							WHEN p.master_id != 0 THEN 1
							ELSE 0
						END < 2 AND p.url = ''
					" : "")."
					".($is_published !== null ? "AND p.publish = ".($is_published ? 'TRUE' : 'FALSE') : '')."
					".($is_clearance !== null ? "AND p.clearance = ".($is_clearance ? 'TRUE' : 'FALSE'): '')."
					ORDER BY p.sort
			");
										
			if (!$directory_id || is_array($directory_id)) {
				
				while ($arr_row = $res->fetchAssoc()) {
					
					$arr_row['publish'] = DBFunctions::unescapeAs($arr_row['publish'], DBFunctions::TYPE_BOOLEAN);
					$arr_row['clearance'] = DBFunctions::unescapeAs($arr_row['clearance'], DBFunctions::TYPE_BOOLEAN);
					
					$arr[$arr_row['directory_id']][$arr_row['id']] = $arr_row;
				}
			} else {
				
				while ($arr_row = $res->fetchAssoc()) {
					
					$arr_row['publish'] = DBFunctions::unescapeAs($arr_row['publish'], DBFunctions::TYPE_BOOLEAN);
					$arr_row['clearance'] = DBFunctions::unescapeAs($arr_row['clearance'], DBFunctions::TYPE_BOOLEAN);
					
					$arr[$arr_row['id']] = $arr_row;
				}
			}
		}
		
		return $arr;
	}
	
	public static function getPagesByScope($root = 0, $directory_id = 0, $do_limit_master = false, $selected_page_id = 0, $is_published = null, $is_clearance = null) {
		
		$root = ($root ?: directories::getRootDirectory());
		
		$res = DB::query("SELECT
			p.*, i.id AS directory_id, i.require_login, i.user_group_id,
			CASE
				WHEN pm.master_id != 0 THEN 2
				WHEN p.master_id != 0 THEN 1
				ELSE 0
			END AS master_level,
			CASE
				WHEN p.template_id != 0 THEN p.template_id
				WHEN pm.template_id != 0 THEN pm.template_id
				ELSE pm2.template_id
			END AS actual_template_id
				FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." d
				JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." cd ON (cd.descendant_id = d.descendant_id)
				JOIN ".DB::getTable('TABLE_DIRECTORIES')." i ON (i.id = cd.ancestor_id)
				LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.directory_id = cd.ancestor_id)
				LEFT JOIN ".DB::getTable('TABLE_PAGES')." pm ON (pm.id = p.master_id)
				LEFT JOIN ".DB::getTable('TABLE_PAGES')." pm2 ON (pm2.id = pm.master_id)
			WHERE p.id != ".(int)$selected_page_id."
				AND d.ancestor_id = ".$root."
				".($directory_id ? "AND cd.descendant_id = ".(int)$directory_id."" : "")."
				".($do_limit_master ? "AND CASE
						WHEN pm.master_id != 0 THEN 2
						WHEN p.master_id != 0 THEN 1
						ELSE 0
					END < 2 AND p.url = ''
				" : "")."
				".($is_published !== null ? "AND p.publish = ".($is_published ? 'TRUE' : 'FALSE') : '')."
				".($is_clearance !== null ? "AND p.clearance = ".($is_clearance ? 'TRUE' : 'FALSE'): '')."
				AND cd.path_length <= d.path_length
			ORDER BY cd.path_length DESC, p.sort
		");

		$arr = [];
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['publish'] = DBFunctions::unescapeAs($arr_row['publish'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['clearance'] = DBFunctions::unescapeAs($arr_row['clearance'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['require_login'] = DBFunctions::unescapeAs($arr_row['require_login'], DBFunctions::TYPE_BOOLEAN);
					
			$arr[$arr_row['id']] = $arr_row;
		}
		
		return $arr;
	}
	
	public static function getPageNameList($arr, $add_directory = true, $add_master_level = false) {
		
		if ($add_directory) {
			$arr_directories = directories::getDirectories();
		}
		
		// Check if array is split into directories or flat
		$is_directory_divided = true;
		if (!$arr || !is_array(current(current($arr)))) {
			$arr = [$arr];
			$is_directory_divided = false;
		}
		
		foreach($arr as &$arr_directory) {
			foreach($arr_directory as $page_id => &$arr_page) {
				$name = $arr_page['name'];
				if ($add_directory) {
					$name = $arr_directories[$arr_page['directory_id']]['path'].' / '.$name;
				}
				if ($add_master_level) {
					$name = $name.' ('.$arr_page['master_level'].')';
				}
				$arr_page['name'] = $name;
			}
		}
		unset($arr_directory);
		
		// Sort and flatten directory divided array
		if ($is_directory_divided) {
			$arr_sorted = [];
			foreach($arr_directories as $directory_id => $arr_directory) {
				if ($arr[$directory_id]) {
					$arr_sorted = $arr_sorted+$arr[$directory_id];
				}
			}
			$arr = $arr_sorted;
		}

		return (!$is_directory_divided ? $arr[0] : $arr);
	}
				
	public static function getClosestModule($module, $directory_id = 0, $page_id = 0, $group_id = 0, $var = false, $var_name = false) {
	
		$query = "SELECT
			m.*, p.directory_id, p.name AS page_name, p.title AS page_title, p.clearance, d.name AS directory_name, d.title AS directory_title, d.require_login, d.user_group_id, ".($directory_id ? "c.path_length" : "0")." AS sub_dir
				FROM ".DB::getTable('TABLE_PAGE_MODULES')." m
				JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
				JOIN ".DB::getTable('TABLE_DIRECTORIES')." d ON (d.id = p.directory_id)
				".($directory_id ? "JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." c ON (c.descendant_id = ".(int)$directory_id.")" : "")."
			WHERE m.module = '".DBFunctions::strEscape($module)."'
				".($var && $var_name ? "AND m.var LIKE '%\"".DBFunctions::strEscape($var_name)."\":\"".DBFunctions::strEscape($var)."\"%'" : "")."
				".($var && !$var_name ? "AND m.var = '".DBFunctions::strEscape($var)."'" : "")."
				".($directory_id && !$page_id ? "AND (
					p.directory_id = ".(int)$directory_id."
					OR c.path_length != 0
				)" : "")."
				".($page_id && !$directory_id ? "AND p.id = ".(int)$page_id : "")."
				".($directory_id && $page_id ? "AND (
					p.id = ".(int)$page_id."
					OR p.directory_id = ".(int)$directory_id."
					OR (c.ancestor_id = p.directory_id AND c.path_length != 0)
				)" : "")."
				".($group_id ? "AND d.user_group_id = ".(int)$group_id : "")."
				".($directory_id && !$page_id ? "ORDER BY c.path_length ASC" : "")."
				".($directory_id && $page_id ? "ORDER BY CASE
						WHEN p.id = ".(int)$page_id." THEN -1
						ELSE c.path_length
					END ASC, c.path_length ASC
				" : "")."
			LIMIT 1
		";

		$res = DB::query($query);
									
		$arr = ($res->fetchAssoc() ?: []);
		
		if ($arr) {
			
			$arr['shortcut_root'] = DBFunctions::unescapeAs($arr['shortcut_root'], DBFunctions::TYPE_BOOLEAN);
			$arr['clearance'] = DBFunctions::unescapeAs($arr['clearance'], DBFunctions::TYPE_BOOLEAN);
			$arr['require_login'] = DBFunctions::unescapeAs($arr['require_login'], DBFunctions::TYPE_BOOLEAN);
		}
		
		return $arr;
	}
	
	public static function updatePages($id, $arr_values) {
	
		if ((int)$id) {
	
			$res = DB::query("UPDATE ".DB::getTable('TABLE_PAGES')."
				SET
					name = '".DBFunctions::strEscape($arr_values['name'])."',
					title = '".DBFunctions::strEscape($arr_values['title'])."',
					directory_id = '".(int)$arr_values['directory_id']."',
					master_id = '".(int)$arr_values['master_id']."',
					template_id = '".(int)$arr_values['template_id']."',
					url = '".DBFunctions::strEscape($arr_values['url'])."',
					html = '".DBFunctions::strEscape($arr_values['html'])."',
					script = '".DBFunctions::strEscape($arr_values['script'])."',
					publish = ".DBFunctions::escapeAs($arr_values['publish'], DBFunctions::TYPE_BOOLEAN).",
					clearance = ".DBFunctions::escapeAs($arr_values['clearance'], DBFunctions::TYPE_BOOLEAN)."
				WHERE id = ".(int)$id."
			");
		} else {
		
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_PAGES')."
				(name, title, directory_id, master_id, template_id, url, html, script, publish, clearance)
					VALUES
				(
					'".DBFunctions::strEscape($arr_values['name'])."',
					'".DBFunctions::strEscape($arr_values['title'])."',
					'".(int)$arr_values['directory_id']."',
					'".(int)$arr_values['master_id']."',
					'".(int)$arr_values['template_id']."',
					'".DBFunctions::strEscape($arr_values['url'])."',
					'".DBFunctions::strEscape($arr_values['html'])."',
					'".DBFunctions::strEscape($arr_values['script'])."',
					".DBFunctions::escapeAs($arr_values['publish'], DBFunctions::TYPE_BOOLEAN).",
					".DBFunctions::escapeAs($arr_values['clearance'], DBFunctions::TYPE_BOOLEAN)."
				)
			");
									
			$id = DB::lastInsertID();
		}
		
		templates::writeTemplateSheet();

		return $id;
	}
	
	public static function updateModule($arr_values) {

		$res = DB::query("INSERT INTO ".DB::getTable('TABLE_PAGE_MODULES')."
			(page_id, x, y, module, var, shortcut, shortcut_root)
				VALUES
			(
				".(int)$arr_values['page_id'].",
				".(int)$arr_values['x'].",
				".(int)$arr_values['y'].",
				'".DBFunctions::strEscape($arr_values['module'])."',
				'".$arr_values['var']."',
				'".DBFunctions::strEscape($arr_values['shortcut'])."',
				".DBFunctions::escapeAs($arr_values['shortcut_root'], DBFunctions::TYPE_BOOLEAN)."
			)
			".DBFunctions::onConflict('page_id, x, y', ['module', 'var', 'shortcut', 'shortcut_root'])."
		");
		
		$res = DB::query("SELECT id
				FROM ".DB::getTable('TABLE_PAGE_MODULES')."
			WHERE page_id = ".(int)$arr_values['page_id']."
				AND x = ".(int)$arr_values['x']."
				AND y = ".(int)$arr_values['y']."
		");
											
		$arr_row = $res->fetchRow();
		$module_id = $arr_row[0];
		
		return $module_id;
	}
		
	public static function deleteNotModules($page_id, $arr_modules) {
	
		$arr_modules = (is_array($arr_modules) ? $arr_modules : [$arr_modules]);
	
		$res = DB::query("DELETE FROM ".DB::getTable('TABLE_PAGE_MODULES')."
			WHERE page_id = ".(int)$page_id."
				AND id NOT IN ('".implode("','", $arr_modules)."')
		");
	}
	
	public static function deletePage($page_id) {
	
		$res = DB::query("SELECT p.id
				FROM ".DB::getTable('TABLE_PAGES')." p
				JOIN ".DB::getTable('TABLE_DIRECTORIES')." d ON (d.page_fallback_id = p.id OR d.page_index_id = p.id)
			WHERE p.id = ".(int)$page_id."
		");
		
		if ($res->getRowCount()) {
			error(getLabel('msg_page_delete_is_index_fallback'));
		}
		
		$res = DB::queryMulti("
			DELETE FROM ".DB::getTable('TABLE_PAGE_MODULES')."
				WHERE page_id = ".(int)$page_id."
			;
			DELETE FROM ".DB::getTable('TABLE_PAGES')."
				WHERE id = ".(int)$page_id."
			;
		");
								
		templates::writeTemplateSheet();
	}
	
	public static function filterClearance($arr_pages_or_modules, $user_group_id, $arr_clearance) {
		
		foreach ($arr_pages_or_modules as $key => $arr_page_or_module) {
			
			$has_clearance = ($arr_clearance && !empty($arr_clearance[($arr_page_or_module['page_id'] ?: $arr_page_or_module['id'])]));
			
			if ((!empty($arr_page_or_module['require_login']) && $arr_page_or_module['user_group_id'] != $user_group_id) || (!$arr_page_or_module['clearance'] && $has_clearance) || ($arr_page_or_module['clearance'] && !$has_clearance)) {
				unset($arr_pages_or_modules[$key]);
			}
		}
		
		return $arr_pages_or_modules;
	}
	
	public static function getBaseURL($arr) {
			
		if ($arr) {
			
			if ($arr['path'] === null) {
				
				$directory = directories::getDirectories($arr['directory_id']);
				$arr['path'] = $directory['path'];
			}
			
			return URL_BASE_HOME.ltrim(str_replace(' ', '', $arr['path']).'/', '/');
		}
		
		return false;
	}
	
	public static function getPageURL($arr, $is_relative = false) {
			
		if ($arr) {
			
			if ($arr['path'] === null) {
				
				$directory = directories::getDirectories($arr['directory_id']);
				$arr['path'] = $directory['path'];
			}
			
			return (!$is_relative ? URL_BASE_HOME : '/').ltrim(str_replace(' ', '', $arr['path']).'/', '/').($arr['page_name'] ?: $arr['name']).'';
		}
		
		return false;
	}
	
	public static function getModuleURL($arr, $is_relative = false, $arr_vars_page = []) {
			
		if ($arr) {
			
			if ($arr['path'] === null) {
				
				$directory = directories::getDirectories($arr['directory_id']);
				$arr['path'] = $directory['path'];
			}
			
			return (!$is_relative ? URL_BASE_HOME : '/').ltrim(str_replace(' ', '', $arr['path']).'/', '/').$arr['page_name'].'.p/'.($arr_vars_page ? implode('/', $arr_vars_page).'/' : '').$arr['id'].'.m/';
		}
		
		return false;
	}
	
	public static function getShortcutURL($arr) {
			
		if ($arr) {

			if (!$root) {
				
				if ($arr['path'] === null) {
					
					$directory = directories::getDirectories($arr['directory_id']);
					$arr['path'] = $directory['path'];
				}
				
				return URL_BASE_HOME.ltrim(str_replace(' ', '', $arr['path']).'/', '/').$arr['shortcut'].'.s/';
			}
				
			return URL_BASE_HOME.$arr['shortcut'].'.s/';
		}
			
		return false;
	}
		
	public static function getShortcut() {
		
		$res = DB::query("SELECT
			m.id, m.page_id, p.name AS page_name, p.directory_id
				FROM ".DB::getTable('TABLE_PAGE_MODULES')." m
				JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
			WHERE m.shortcut = '".DBFunctions::strEscape(SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_PAGE_NAME))."'
				AND (".(SiteStartEnvironment::getDirectory('root') ?
					"m.shortcut_root = TRUE OR p.directory_id = ".(int)SiteStartEnvironment::getDirectory('id').""
					: 
					"m.shortcut_root = FALSE AND p.directory_id = ".(int)SiteStartEnvironment::getDirectory('id').""
				).")
		");
										
		if ($res->getRowCount()) {
		
			$arr = $res->fetchAssoc();
			
			$arr_directory = directories::getDirectories($arr['directory_id']);
			$arr['path'] = $arr_directory['path'];
			
			return $arr;
		}
		
		return false;
	}
	
	public static function noPage($do_show = false) {
		
		$num_dir_pop = (SiteStartEnvironment::getDirectory() && SiteStartEnvironment::getDirectory('page_index_id') && SiteStartEnvironment::getDirectory('page_index_id') != SiteStartEnvironment::getPage('id') ? 0 : 1);
	
		if (count(SiteStartEnvironment::getDirectoryClosure()) <= 10) { // Prevent an possible overload
			$str_url = SiteStartEnvironment::getBasePath($num_dir_pop);
		} else {
			$str_url = '/';
		}
		
		$do_show = ($do_show || getLabel('show_404', 'D', true) ? true : false);
		
		if ($do_show) {
			
			Labels::setVariable('url', $str_url);
			$str_msg = '<ul>
				<li><label></label><div>'.getLabel('msg_page_not_found').'</div></li>
				<li><label>SORRY</label><div>'.getLabel('msg_page_not_found_suggestion').'</div></li>
			</ul>';
					
			Response::addHeaders($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
					
			Response::stop(function() use($str_msg) {
				
					$page = new ExitPage($str_msg, '404', '404');
					
					return $page->getPage();
				}, (object)['msg' => $str_msg, 'msg_type' => 'alert']
			);
		} else {
			
			Response::addHeaders($_SERVER['SERVER_PROTOCOL'].' 301 Moved Permanently');
			
			Response::location($str_url);
		}
		
		exit;
	}
}
