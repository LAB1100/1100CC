<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_USER_GROUPS', DB::$database_cms.'.site_user_groups');
DB::setTable('TABLE_USER_GROUP_LINK', DB::$database_cms.'.site_user_group_link');
DB::setTable('TABLE_USERS', DB::$database_cms.'.users');
DB::setTable('VIEW_USER_PARENT', DB::$database_cms.'.view_user_parent');

class user_groups extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
		
	protected function getLinkTables() {
	
		$res = DB::query("SELECT
			DISTINCT CONCAT(TABLE_SCHEMA, '.', TABLE_NAME) AS \"TABLE_NAME\"
				FROM INFORMATION_SCHEMA.COLUMNS
			WHERE (TABLE_SCHEMA = '".DB::$database_home."' OR TABLE_SCHEMA = '".DB::$database_cms."')
		");
		
		$arr = [];
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr[] = $arr_row['TABLE_NAME'];
		}
		
		return $arr;
	}
	
	protected function getLinkColumns($table) {
	
		DB::setConnectionDatabase(false);
		
		$res = DB::query("SELECT
			COLUMN_NAME AS \"COLUMN_NAME\"
				FROM INFORMATION_SCHEMA.COLUMNS
			WHERE CONCAT(TABLE_SCHEMA, '.', TABLE_NAME) = '".DBFunctions::strEscape($table)."' AND (TABLE_SCHEMA = '".DB::$database_home."' OR TABLE_SCHEMA = '".DB::$database_cms."')
		");
		
		$arr = [];
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr[] = $arr_row['COLUMN_NAME'];
		}
		
		return $arr;
	}
	
	protected function createLinkDropdown($arr, $selected = 0) {

		$return = '<option value="0"></option>';

		foreach($arr as $value){
			
			$return .= '<option value="'.$value.'"'.($value == $selected ? ' selected="selected"' : '').'>'.$value.'</option>';
		}
		
		return $return;
	}
	
	public static function getUserGroupTables($user_group_id, $user_id = false, $do_indirect = true) {
	
		if ($user_group_id) {
			
			$res = DB::query("SELECT *
					FROM ".DB::getTable('TABLE_USER_GROUP_LINK')."
				WHERE group_id = ".(int)$user_group_id."
					".(!$do_indirect ? "AND (from_table = '".static::formatTableNameToTemplate(DB::getTableName('TABLE_USERS'))."' OR from_table = '".DB::getTableName('TABLE_USERS')."')" : "")."
				ORDER BY sort
			");
		} else {
			
			$res = DB::query("SELECT l.*
					FROM ".DB::getTable('TABLE_USERS')." u
					JOIN ".DB::getTable('TABLE_USER_GROUP_LINK')." l ON (l.group_id = u.group_id)
				WHERE u.id = ".(int)$user_id."
					".(!$do_indirect ? "AND (l.from_table = '".static::formatTableNameToTemplate(DB::getTableName('TABLE_USERS'))."' OR l.from_table = '".DB::getTableName('TABLE_USERS')."')" : "")."
				ORDER BY sort
			");
		}
			
		$arr = [];
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['multi_source'] = DBFunctions::unescapeAs($arr_row['multi_source'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['multi_target'] = DBFunctions::unescapeAs($arr_row['multi_target'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['view'] = DBFunctions::unescapeAs($arr_row['view'], DBFunctions::TYPE_BOOLEAN);
			
			$arr_row['from_table'] = static::formatTableNameFromTemplate($arr_row['from_table']);
			$arr_row['to_table'] = static::formatTableNameFromTemplate($arr_row['to_table']);
			
			$arr[$arr_row['to_table']] = $arr_row;
		}
		
		return $arr;
	}
		
	public static function getUserGroupColumns($user_group_id, $user_id = false, $do_indirect = true) {

		if ($user_group_id) {
			
			$res = DB::query("SELECT *
				FROM ".DB::getTable('TABLE_USER_GROUP_LINK')."
					WHERE group_id = ".(int)$user_group_id."
						".(!$do_indirect ? "AND (from_table = '".static::formatTableNameToTemplate(DB::getTableName('TABLE_USERS'))."' OR from_table = '".DB::getTableName('TABLE_USERS')."')" : "")."
					ORDER BY sort
			");
		} else {
			
			$res = DB::query("SELECT l.*
				FROM ".DB::getTable('TABLE_USERS')." u
				JOIN ".DB::getTable('TABLE_USER_GROUP_LINK')." l ON (l.group_id = u.group_id)
					WHERE u.id = ".(int)$user_id."
						".(!$do_indirect ? "AND (l.from_table = '".static::formatTableNameToTemplate(DB::getTableName('TABLE_USERS'))."' OR l.from_table = '".DB::getTableName('TABLE_USERS')."')" : "")."
					ORDER BY sort
			");
		}
		
		$arr_users = [];
		$arr_connect = [];
		$arr_multi_target = [];
		$arr_all = [];
		$arr_user_columns = ['enabled', 'name', 'uname', 'email'];
		$arr_order = ["'".DB::getTableName('TABLE_USERS')."'"];
		
		while ($arr_row = $res->fetchAssoc()) {

			$arr_row['multi_source'] = DBFunctions::unescapeAs($arr_row['multi_source'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['multi_target'] = DBFunctions::unescapeAs($arr_row['multi_target'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['view'] = DBFunctions::unescapeAs($arr_row['view'], DBFunctions::TYPE_BOOLEAN);
			
			$arr_row['from_table'] = static::formatTableNameFromTemplate($arr_row['from_table']);
			$arr_row['to_table'] = static::formatTableNameFromTemplate($arr_row['to_table']);
			
			if ($arr_row['from_table'] == DB::getTableName('TABLE_USERS')) { // Connection to a user related table
				
				if ($arr_row['from_column'] != 'id' && !$arr_row['view']) { // If column is referencing a non-user source, and not viewable, hide it from column overview
					continue;
				}
				
				if ($arr_row['get_column']) { // Connection to a single column (get) table
				
					if ($arr_row['from_column'] != 'id') { // Do not overwrite the primary id column
						$arr_user_columns[$arr_row['from_column']] = $arr_row['from_column'];
						$arr_connect[$arr_row['from_table'].'.'.$arr_row['from_column']] = $arr_row;
					}
				} else {
					
					$arr_users[$arr_row['to_table']] = $arr_row['to_table'].'.'.$arr_row['to_column'];
				}
			} else { // Connection to a non(direct)-user related table
				
				$arr_connect[$arr_row['from_table'].'.'.$arr_row['from_column']] = $arr_row;
			}
			
			if ($arr_row['multi_target']) {
				$arr_multi_target[$arr_row['to_table']] = $arr_row['to_table'].'.'.$arr_row['get_column'];
			}
			
			$arr_all[$arr_row['to_table']] = $arr_row;
			$arr_order[$arr_row['to_table']] = "'".$arr_row['to_table']."'";
		}

		// Get columns from linked tables
		$res = DB::query("SELECT CONCAT(TABLE_SCHEMA, '.', TABLE_NAME) AS \"TABLE_NAME\", COLUMN_NAME AS \"COLUMN_NAME\", DATA_TYPE AS \"DATA_TYPE\"
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE 
				CASE
				WHEN CONCAT(TABLE_SCHEMA, '.', TABLE_NAME) = '".DB::getTableName('TABLE_USERS')."'
					THEN COLUMN_NAME IN ('".implode("','", $arr_user_columns)."')
				WHEN CONCAT(TABLE_SCHEMA, '.', TABLE_NAME) IN ('".implode("','", array_keys($arr_multi_target))."') 
					THEN CONCAT(TABLE_SCHEMA, '.', TABLE_NAME, '.', COLUMN_NAME) IN ('".implode("','", $arr_multi_target)."')
				ELSE 
					CONCAT(TABLE_SCHEMA, '.', TABLE_NAME) IN ('".implode("','", array_keys($arr_users))."') AND CONCAT(TABLE_SCHEMA, '.', TABLE_NAME, '.', COLUMN_NAME) NOT IN ('".implode("','", $arr_users)."')
				END
			AND (TABLE_SCHEMA = '".DB::$database_home."' OR TABLE_SCHEMA = '".DB::$database_cms."')
			ORDER BY ".DBFunctions::fieldToPosition("CONCAT(TABLE_SCHEMA, '.', TABLE_NAME)", $arr_order).", ORDINAL_POSITION
		");

		$arr = [];
		
		while ($arr_row = $res->fetchAssoc()) {
			
			if ($arr_connect[$arr_row['TABLE_NAME'].'.'.$arr_row['COLUMN_NAME']]) { // If column overlaps with an non-user table or single column (get) table, update column with all linking info
				
				$arr_link = $arr_connect[$arr_row['TABLE_NAME'].'.'.$arr_row['COLUMN_NAME']];
				
				$arr_row['SOURCE_TABLE_NAME'] = $arr_row['TABLE_NAME'];
				$arr_row['SOURCE_COLUMN_NAME'] = $arr_row['COLUMN_NAME'];
				$arr_row['TABLE_NAME'] = $arr_link['to_table'];
				$arr_row['LINK_COLUMN_NAME'] = $arr_link['to_column'];
				$arr_row['COLUMN_NAME'] = ($arr_link['get_column'] ? $arr_link['get_column'] : $arr_link['to_column']);
				$arr_row['VIRTUAL_NAME'] = $arr_link['virtual_name'];
				$arr_row['MULTI_SOURCE'] = $arr_link['multi_source'];
			} else if ($arr_all[$arr_row['TABLE_NAME']]['view']) {
				
				$arr_link = $arr_all[$arr_row['TABLE_NAME']];
				
				$arr_row['SOURCE_TABLE_NAME'] = $arr_row['TABLE_NAME'];
				$arr_row['SOURCE_COLUMN_NAME'] = $arr_link['to_column'];
				$arr_row['VIRTUAL_NAME'] = $arr_link['virtual_name'];
				$arr_row['MULTI_SOURCE'] = true;
			} else if ($arr_multi_target[$arr_row['TABLE_NAME']] == $arr_row['TABLE_NAME'].'.'.$arr_row['COLUMN_NAME']) { // If a column belongs to a multi value table, and has no succeeding connection table and is not to be viewed, hide reference
				continue;
			}
			
			if ($arr_all[$arr_row['TABLE_NAME']]['view']) { // If column is only to be viewed, make notice
				$arr_row['VIEW'] = true;
			}

			$arr[$arr_row['TABLE_NAME'].'.'.$arr_row['COLUMN_NAME']] = $arr_row;
		}

		return $arr;
	}
	
	public static function formatTableNameToTemplate($str_table_name) {
		
		$str_table_name = str_replace(DB::$database_home, '[[home]]', $str_table_name);
		$str_table_name = str_replace(DB::$database_cms, '[[cms]]', $str_table_name);
		
		return $str_table_name;
	}
	
	public static function formatTableNameFromTemplate($str_table_name) {
		
		$str_table_name = str_replace('[[home]]', DB::$database_home, $str_table_name);
		$str_table_name = str_replace('[[cms]]', DB::$database_cms, $str_table_name);
		
		return $str_table_name;
	}
	
	public static function getUserData($user_id, $do_indirect = false) {
		
		if (is_array($user_id)) {
			
			$user_id = arrParseRecursive($user_id, TYPE_INTEGER);
			
			$arr_tables = self::getUserGroupTables(false, reset($user_id), $do_indirect); // Requires only one user ID
			$sql_user_id = 'IN ('.arr2String($user_id, ',').')';
		} else {
			
			$arr_tables = self::getUserGroupTables(false, $user_id, $do_indirect);
			$sql_user_id = '= '.(int)$user_id;
		}
		
		$arr_table_names = [];
		
		$sql_query = "SELECT ".DB::getTable('TABLE_USERS').".*, ".DB::getTable('TABLE_USER_GROUPS').".*
				".(count($arr_tables) ? ", ".implode(".*, ", array_keys($arr_tables)).".*" : "")."
			FROM ".DB::getTable('TABLE_USERS')."
			LEFT JOIN ".DB::getTable('TABLE_USER_GROUPS')." ON (".DB::getTable('TABLE_USER_GROUPS').".id = ".DB::getTable('TABLE_USERS').".group_id) ";	
		
		$arr_scheme_table = explode('.', DB::getTableName('TABLE_USERS'));
		$arr_table_names[$arr_scheme_table[1]] = DB::getTableName('TABLE_USERS');
		
		$arr_scheme_table = explode('.', DB::getTableName('TABLE_USER_GROUPS'));
		$arr_table_names[$arr_scheme_table[1]] = DB::getTableName('TABLE_USER_GROUPS');
		
		foreach ($arr_tables as $key => $value) {
			
			$sql_query .= "LEFT JOIN ".$key." ON (".$key.".".$value['to_column']." = ".$value['from_table'].".".$value['from_column'].") ";
			
			$arr_scheme_table = explode('.', $key);
			$arr_table_names[$arr_scheme_table[1]] = $key;
		}
		
		$sql_query .= "WHERE ".DB::getTable('TABLE_USERS').".id ".$sql_user_id;

		$res = DB::query($sql_query);

		$arr = [];
		
		$arr_fields_meta = [];
		$nr_fields = $res->getFieldCount();
		$i = 0;
		
		while ($i < $nr_fields) {
			
			$arr_fields_meta[$i] = $res->getFieldMeta($i);
			$arr_fields_meta[$i]['type'] = $res->getFieldDataType($i);
			
			$i++;
		}
		
		while ($arr_row = $res->fetchArray()) {
			
			$s_arr_user = &$arr[$arr_row[0]]; // User ID
			
			foreach ($arr_fields_meta as $i => $arr_field_meta) {
				
				$table_name = $arr_table_names[$arr_field_meta['table']];
				$arr_table_info = ($arr_tables[$table_name] ?? null);

				$s_arr_user[$table_name] = (!isset($s_arr_user[$table_name]) ? [] : $s_arr_user[$table_name]); // Make sure the table does exist

				if ($arr_table_info && $arr_table_info['multi_source']) { // If source has multiple rows create multidimensional array based on to_column
					
					if (!empty($arr_row[$arr_table_info['to_column']])) { // Do not store empty rows
						
						$s_arr_user[$table_name][$arr_row[$arr_table_info['to_column']]][$arr_field_meta['name']] = $arr_row[$i];
					}
				} else if ($arr_table_info && $arr_table_info['multi_target']) {  // If target has multiple rows create multidimensional array based on get_column
				
					if (!empty($arr_row[$arr_table_info['get_column']])) { // Do not store empty rows
						
						$s_arr_user[$table_name][$arr_row[$arr_table_info['get_column']]][$arr_field_meta['name']] = $arr_row[$i];
					}
				} else {
					if (!empty($arr_row[$i])) { // Do not store empty values
						
						if ($arr_field_meta['type'] == DBFunctions::TYPE_BOOLEAN) {
							
							$arr_row[$i] = DBFunctions::unescapeAs($arr_row[$i], DBFunctions::TYPE_BOOLEAN);
						}
						
						$s_arr_user[$table_name][$arr_field_meta['name']] = $arr_row[$i];
					}
				}
			}
		}

		return (is_array($user_id) ? $arr : current($arr));
	}
	
	public static function getUserGroups($user_group_id = 0, $parent_user_goup_id = 0) {
	
		$arr = [];
		
		$res = DB::query("SELECT *
			FROM ".DB::getTable('TABLE_USER_GROUPS')."
			WHERE TRUE
				".($user_group_id ? "AND id = '".(int)$user_group_id."'" : "")."
				".($parent_user_goup_id ? "AND parent_id = '".(int)$parent_user_goup_id."'" : "")."
			".($user_group_id ? "ORDER BY id" : "")."
		");
		
		while($arr_row = $res->fetchAssoc()) {
			
			$arr[$arr_row['id']] = $arr_row;
		}
		
		return ($user_group_id ? current($arr) : $arr);
	}
			
	public static function createUserGroupsDropdown($arr, $selected = 0, $empty = false) {

		$return .= ($empty ? '<option value="0"></option>' : '');

		foreach($arr as $path => $row){
			$return .= '<option value="'.$row['id'].'"'.($row['id'] == $selected ? ' selected="selected"' : '').'>'.$row['name'].'</option>';
		}
		
		return $return;
	}
	
	public static function userDatabaseLocations() {
		
		$res = DB::query("SELECT
			CONCAT(TABLE_SCHEMA, '.', TABLE_NAME) AS \"TABLE_NAME\", COLUMN_NAME AS \"COLUMN_NAME\"
				FROM INFORMATION_SCHEMA.COLUMNS
			WHERE COLUMN_NAME IN ('user_id', 'parent_user_id')
			AND TABLE_NAME != '".DB::getTableName('TABLE_USERS')."'
			AND (TABLE_SCHEMA = '".DB::$database_home."' OR TABLE_SCHEMA = '".DB::$database_cms."')
		");
		
		$arr = [];
		
		while ($arr_row = $res->fetchAssoc()) {
			$arr[] = [$arr_row['TABLE_NAME'], $arr_row['COLUMN_NAME']];
		}
		
		return $arr;
	}
	
	public static function userLocationSearch($arr_database, $search) {
		
		$arr_query = [];
		
		foreach ($arr_database as $value) {
			
			$arr_query[] = "SELECT
				'".$value[0]."' AS table_name, '".$value[1]."' AS column_name
					FROM ".$value[0]." t
					LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = t.".$value[1].")
				WHERE u.group_id = '".$search."'
			";
		}
		
		$arr = [];
		$res = DB::query("".implode(" UNION ", $arr_query)."");
		
		while ($arr_row = $res->fetchAssoc()) {
			$arr[] = [$arr_row['table_name'], $arr_row['column_name']];
		}
		
		return $arr;
	}
}
