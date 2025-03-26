<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_CORE_LANGUAGE', DB::$database_core.'.core_language');
DB::setTable('TABLE_CMS_LANGUAGE', DB::$database_cms.'.cms_language');
DB::setTable('TABLE_CMS_LANGUAGE_HOSTS', DB::$database_cms.'.cms_language_hosts');

class cms_language extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	private static $arr_language_default = [];
	
	public static function getLanguage($str_lang_code = false, $str_table = 'cms') {
	
		$str_table = ($str_table == 'cms' ? 'TABLE_CMS_LANGUAGE' : 'TABLE_CORE_LANGUAGE'); 
	
		$arr = [];

		if ($str_lang_code) {
			
			$res = DB::query("SELECT * FROM ".DB::getTable($str_table)." AS language WHERE lang_code = '".DBFunctions::strEscape($str_lang_code)."'");			
			
			$arr = ($res->fetchAssoc() ?: []);
			
			if ($arr) {
				
				$arr['is_user_selectable'] = DBFunctions::unescapeAs($arr['is_user_selectable'], DBFunctions::TYPE_BOOLEAN);
				$arr['is_default'] = DBFunctions::unescapeAs($arr['is_default'], DBFunctions::TYPE_BOOLEAN);
			}
		} else {
			
			$res = DB::query("SELECT * FROM ".DB::getTable($str_table)." AS language ORDER BY".($str_table == 'TABLE_CMS_LANGUAGE' ? " is_default DESC," : "")." lang_code");
			
			while ($arr_row = $res->fetchAssoc()) {
				
				$arr_row['is_user_selectable'] = DBFunctions::unescapeAs($arr_row['is_user_selectable'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['is_default'] = DBFunctions::unescapeAs($arr_row['is_default'], DBFunctions::TYPE_BOOLEAN);
				
				$arr[$arr_row['lang_code']] = $arr_row;
			}
		}		
		
		return $arr;
	}
	
	public static function getLanguageSelectable($str_lang_code = false) {
	
		$arr_languages = static::getLanguage($str_lang_code);
		
		foreach ($arr_languages as $lang_code => $arr_language) {
			
			if (!$arr_language['is_user_selectable']) {
				unset($arr_languages[$lang_code]);
			}
		}
		
		return $arr_languages;
	}
	
	public static function getLanguageHosts() {
		
		$arr = [];

		$res = DB::query("SELECT
				lh.*
			FROM ".DB::getTable('TABLE_CMS_LANGUAGE_HOSTS')." lh
			JOIN ".DB::getTable('TABLE_CMS_LANGUAGE')." l ON (l.lang_code = lh.lang_code)
		");
		
		while($row = $res->fetchAssoc()){
			
			$arr[$row['host_name']] = $row;
		}
	
		return $arr;
	}
	
	public static function getDefaultLanguage($host_name = '') {
		
		if (!$host_name && self::$arr_language_default) {
			
			return self::$arr_language_default;
		}
	
		$res = DB::query("SELECT l.*
			FROM ".DB::getTable('TABLE_CMS_LANGUAGE')." l
			".($host_name ? "LEFT JOIN ".DB::getTable('TABLE_CMS_LANGUAGE_HOSTS')." lh ON (lh.lang_code = l.lang_code AND (
					(
						lh.host_name = '".DBFunctions::strEscape($host_name)."'
					) OR (
						lh.host_name LIKE ':%'
						AND ".DBFunctions::searchRegularExpression(DBFunctions::SQL_IS_LITERAL.DBFunctions::strEscape($host_name), "SUBSTRING(lh.host_name FROM 2)")."
					)
				)
			)
			" : "")."
			ORDER BY ".($host_name ? "CASE WHEN lh.host_name IS NOT NULL THEN 1 ELSE 0 END DESC, " : "")."is_default DESC
			LIMIT 1
		");
							
		$row = $res->fetchAssoc();
		
		if (!$host_name) {
			
			self::$arr_language_default = $row;
		}
		
		return $row;
	}
}
