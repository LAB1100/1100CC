<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_DIRECTORIES', DB::$database_cms.'.site_directories');
DB::setTable('TABLE_DIRECTORY_CLOSURE', DB::$database_cms.'.site_directory_closure');

class directories extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
			
	public static function getRootDirectory() {
	
		$res = DB::query("SELECT id
				FROM ".DB::getTable('TABLE_DIRECTORIES')."
			WHERE root = TRUE
		");
		
		$arr_row = $res->fetchAssoc();
		
		return ($arr_row['id'] ?: 0);
	}
	
	public static function getClosestRootedDirectory($directory_id = 0) {
		
		$directory_id = ($directory_id ?: SiteStartVars::$dir['id']);
		
		$res = DB::query("SELECT ad.ancestor_id
				FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." ad
				LEFT JOIN ".DB::getTable('TABLE_DIRECTORIES')." ai ON (ai.id = ad.ancestor_id)
			WHERE ad.descendant_id = ".(int)$directory_id." AND (ai.publish = FALSE OR ai.root = TRUE)
			ORDER BY path_length ASC
			LIMIT 1
		");
							
		$arr_row = $res->fetchAssoc();
		
		return $arr_row['ancestor_id'];
	}
	
	public static function getParentDirectory($directory_id = 0) {
		
		$directory_id = ($directory_id ?: SiteStartVars::$dir['id']);
		
		$res = DB::query("SELECT ad.ancestor_id
				FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." ad
			WHERE ad.descendant_id = ".(int)$directory_id."
			ORDER BY path_length ASC
			LIMIT 1
		");
		
		$arr_row = $res->fetchAssoc();
		
		return $arr_row['ancestor_id'];
	}
	
	public static function traceDirectoryPath($path) {

		$res = DB::query("SELECT i.*,
			MAX(a.path_length) AS path_length,
			".DBFunctions::sqlImplode('n.name', '/', 'ORDER BY a.path_length DESC')." AS path,
			".DBFunctions::sqlImplode(DBFunctions::castAs('n.user_group_id', DBFunctions::CAST_TYPE_STRING), '/', 'ORDER BY a.path_length DESC')." AS user_group
				FROM ".DB::getTable('TABLE_DIRECTORIES')." i
				JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." a ON (a.descendant_id = i.id)
				JOIN ".DB::getTable('TABLE_DIRECTORIES')." n ON (n.id = a.ancestor_id)
			GROUP BY i.id
			HAVING ".DBFunctions::sqlImplode('n.name', '/', 'ORDER BY a.path_length DESC')." = '".DBFunctions::strEscape(implode('/', $path))."'
		");
		
		if (!$res->getRowCount()) {
			return false;
		}
		
		$arr = $res->fetchAssoc();
		
		if ($arr) {
			$arr['publish'] = DBFunctions::unescapeAs($arr['publish'], DBFunctions::TYPE_BOOLEAN);
			$arr['require_login'] = DBFunctions::unescapeAs($arr['require_login'], DBFunctions::TYPE_BOOLEAN);
		}
		
		return $arr;
	}
	
	public static function getDirectories($directory_id = 0, $selected = 0, $root = 0, $published = false) {
	
		$arr = [];

		if ($directory_id) {
			
			$res = DB::query("SELECT i.*,
				MAX(a.path_length) AS path_length,
				".DBFunctions::sqlImplode('n.name', ' / ', 'ORDER BY a.path_length DESC')." AS path,
				".DBFunctions::sqlImplode(DBFunctions::castAs('n.user_group_id', DBFunctions::CAST_TYPE_STRING), '/', 'ORDER BY a.path_length DESC')." AS user_group
					FROM ".DB::getTable('TABLE_DIRECTORIES')." i
					JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." a ON (a.descendant_id = i.id)
					JOIN ".DB::getTable('TABLE_DIRECTORIES')." n ON (n.id = a.ancestor_id)
				WHERE i.id = ".(int)$directory_id."
				GROUP BY i.id
			");

			$arr = $res->fetchAssoc();
			
			if ($arr) {
				$arr['publish'] = DBFunctions::unescapeAs($arr['publish'], DBFunctions::TYPE_BOOLEAN);
				$arr['require_login'] = DBFunctions::unescapeAs($arr['require_login'], DBFunctions::TYPE_BOOLEAN);
			}
		} else {
		
			$root = ($root ? (int)$root : "(SELECT id
					FROM ".DB::getTable('TABLE_DIRECTORIES')."
				WHERE root = TRUE
			)");
			
			$res = DB::query("SELECT i.*, d.path_length,
				".DBFunctions::sqlImplode('n.name', ' / ', 'ORDER BY a.path_length DESC')." AS path,
				".DBFunctions::sqlImplode(DBFunctions::castAs('n.user_group_id', DBFunctions::CAST_TYPE_STRING), '/', 'ORDER BY a.path_length DESC')." AS user_group,
				".DBFunctions::sqlImplode(DBFunctions::castAs('s.sort', 'CHAR(3)'), ' / ', 'ORDER BY a.path_length DESC, s.sort')." AS sorted
					FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." d
					JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." a ON (
						a.descendant_id = d.descendant_id
						".($selected ? " AND a.descendant_id NOT IN (
							SELECT descendant_id
								FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')."
								WHERE ancestor_id = ".(int)$selected."
						)" : "")."
					)
					JOIN ".DB::getTable('TABLE_DIRECTORIES')." i on (i.id = a.descendant_id)
					JOIN ".DB::getTable('TABLE_DIRECTORIES')." n ON (n.id = a.ancestor_id)
					JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." s ON (s.descendant_id = a.ancestor_id AND s.path_length = 0) 
				WHERE d.ancestor_id = ".$root."
					".($published ? "AND i.publish = TRUE" : "")."
				GROUP BY d.ancestor_id, d.descendant_id, i.id
				ORDER BY sorted
			");

			while ($arr_row = $res->fetchAssoc()) {
				
				$arr_row['publish'] = DBFunctions::unescapeAs($arr_row['publish'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['require_login'] = DBFunctions::unescapeAs($arr_row['require_login'], DBFunctions::TYPE_BOOLEAN);
				
				$arr[$arr_row['id']] = $arr_row;
			}
		}
		
		return $arr;
	}
	
	public static function getDirectoriesInRange($directory_id = 0) {

		$res = DB::query("SELECT i.*, d.path_length,
			".DBFunctions::sqlImplode('n.name', ' / ', 'ORDER BY a.path_length DESC')." AS path,
			".DBFunctions::sqlImplode(DBFunctions::castAs('n.user_group_id', DBFunctions::CAST_TYPE_STRING), '/', 'ORDER BY a.path_length DESC')." AS user_group
				FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." d
				JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." a ON (a.descendant_id = d.descendant_id)
				JOIN ".DB::getTable('TABLE_DIRECTORIES')." i on (i.id = a.descendant_id)
				JOIN ".DB::getTable('TABLE_DIRECTORIES')." n ON (n.id = a.ancestor_id)
				JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." s ON (s.descendant_id = d.descendant_id AND s.path_length = 0) 
			WHERE d.ancestor_id = (SELECT id FROM ".DB::getTable('TABLE_DIRECTORIES')." WHERE root = TRUE)
				AND d.descendant_id NOT IN (SELECT descendant_id
						FROM ".DB::getTable('TABLE_DIRECTORIES')." pd
						JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." pdc ON (pdc.ancestor_id = pd.id)
					WHERE pd.publish = FALSE AND pd.id != ".(int)$directory_id."
				)
			GROUP BY d.ancestor_id, d.descendant_id, s.ancestor_id, s.descendant_id, i.id
			ORDER BY d.path_length, s.sort
		");
		
		$arr = [];
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['publish'] = DBFunctions::unescapeAs($arr_row['publish'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['require_login'] = DBFunctions::unescapeAs($arr_row['require_login'], DBFunctions::TYPE_BOOLEAN);
				
			$arr[$arr_row['id']] = $arr_row;
		}
		
		return $arr;
	}
	
	public static function getDirectoriesLimited($root = 0, $directory_id = 0, $published = false) {
		
		$root = ($root ? (int)$root : "(SELECT id FROM ".DB::getTable('TABLE_DIRECTORIES')." WHERE root = TRUE)");
	
		$res = DB::query("SELECT
			i.*, cd.path_length, cd.ancestor_id
				FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." d
				JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." cd ON (cd.descendant_id = d.descendant_id)
				JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." cdr ON (cdr.ancestor_id = cd.ancestor_id)
				JOIN ".DB::getTable('TABLE_DIRECTORIES')." i ON (i.id = cdr.descendant_id)
				JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." s ON (s.descendant_id = cdr.descendant_id AND s.path_length = 0)
			WHERE 
				d.ancestor_id = ".$root."
				".($published ? "AND i.publish = TRUE" : "")."
				".($directory_id ? "AND cd.descendant_id = ".(int)$directory_id."" : "")."
				AND cdr.path_length = 1
				AND cd.path_length <= d.path_length
			ORDER BY cd.path_length DESC, s.sort
		");
		
		$arr = [];
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['publish'] = DBFunctions::unescapeAs($arr_row['publish'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['require_login'] = DBFunctions::unescapeAs($arr_row['require_login'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[$arr_row['id']] = $arr_row;
		}
		
		return $arr;
	}
	
	public static function getDirectoryTree($arr_dirs) {
		
		$arr_paths = [];
		
		foreach ($arr_dirs as $cur_dir) {
			
			$arr_path = arrParseRecursive(explode('/', $cur_dir['path']), 'trim');
			
			$cur_path =& $arr_paths;
			
			foreach ($arr_path as $dir_name) {
				
				if ($dir_name == $cur_dir['name']) {
					$cur_path[$dir_name]['arr_dir'] = $cur_dir;
				}
				
				$cur_path =& $cur_path[$dir_name]['subs'];
			}
		}
		
		return $arr_paths;
	}
			
	public static function createDirectoriesDropdown($arr, $selected = 0, $empty = false) {
		
		foreach ($arr as &$arr_directory) {
		
			if ($arr_directory['path'] == '') { // Root, add a nice slash
				$arr_directory['path'] = '/';
			}
		}
		unset($arr_directory);
		
		return cms_general::createDropdown($arr, $selected, $empty, 'path');
	}
}
