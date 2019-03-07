<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
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
	
	private static $lang_default = '';
	
	public static function getLanguage($table = 'cms', $lang = '') {
	
		$table = ($table == 'cms' ? 'TABLE_CMS_LANGUAGE' : 'TABLE_CORE_LANGUAGE'); 
	
		$arr = [];

		if (!empty($lang)) {
			
			$res = DB::query("SELECT * FROM ".DB::getTable($table)." AS language WHERE lang_code = '".DBFunctions::strEscape($lang)."'");			
			
			$arr = $res->fetchAssoc();			
		} else {
			
			$res = DB::query("SELECT * FROM ".DB::getTable($table)." AS language ORDER BY".($table == 'TABLE_CMS_LANGUAGE' ? " is_default DESC," : "")." lang_code");
			
			while ($row = $res->fetchAssoc()) {
				
				$arr[$row['lang_code']] = $row;
			}
		}		
		
		return $arr;
	}
	
	public static function getLanguageHosts() {
		
		$arr = [];

		$res = DB::query("SELECT lh.* FROM ".DB::getTable('TABLE_CMS_LANGUAGE_HOSTS')." lh
							JOIN ".DB::getTable('TABLE_CMS_LANGUAGE')." l ON (l.lang_code = lh.lang_code)
		");
		
		while($row = $res->fetchAssoc()){
			
			$arr[$row['host_name']] = $row;
		}
	
		return $arr;
	}
	
	public static function getDefaultLanguage($host_name = '') {
		
		if (!$host_name && self::$lang_default) {
			
			return self::$lang_default;
		}
	
		$res = DB::query("SELECT l.* FROM ".DB::getTable('TABLE_CMS_LANGUAGE')." l
			".($host_name ? "LEFT JOIN ".DB::getTable('TABLE_CMS_LANGUAGE_HOSTS')." lh ON (lh.lang_code = l.lang_code AND
				CASE
					WHEN LEFT(lh.host_name, 1) = ':' THEN ".DBFunctions::regexpMatch("'".DBFunctions::strEscape($host_name)."'", "SUBSTRING(lh.host_name FROM 2)")."
					ELSE lh.host_name = '".DBFunctions::strEscape($host_name)."'
				END
			)
			" : "")."
			ORDER BY ".($host_name ? "CASE WHEN lh.host_name IS NOT NULL THEN 1 ELSE 0 END DESC, " : "")."is_default DESC
			LIMIT 1
		");
							
		$row = $res->fetchAssoc();
		
		if (!$host_name) {
			
			self::$lang_default = $row;
		}
		
		return $row;
	}
}
