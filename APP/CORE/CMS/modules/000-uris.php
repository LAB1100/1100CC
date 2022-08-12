<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('SITE_URIS', DB::$database_cms.'.site_uris');
DB::setTable('SITE_URI_TRANSLATORS', DB::$database_cms.'.site_uri_translators');
DB::setTable('SITE_URI_TRANSLATOR_HOSTS', DB::$database_cms.'.site_uri_translator_hosts');

class uris extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}

	public static function getURITranslators($uri_translator_id = 0) {
		
		$arr = [];

		$res = DB::query("SELECT ut.id, ut.name, ut.host_name, ut.delay, ut.show_remark
							FROM ".DB::getTable('SITE_URI_TRANSLATORS')." ut
						".($uri_translator_id ? "WHERE ut.id = ".(int)$uri_translator_id."" : "")."
		");
		
		while ($arr_uri_translator = $res->fetchAssoc()) {
			
			$arr[$arr_uri_translator['id']] = $arr_uri_translator;
		}
	
		return ($uri_translator_id ? current($arr) : $arr);
	}
	
	public static function getURITranslatorHosts($host_name = '') {
		
		$arr = [];

		$res = DB::query("SELECT
			ut.id, ut.name,  ut.host_name, ut.delay, ut.show_remark,
			uth.host_name AS translator_host_name
				FROM ".DB::getTable('SITE_URI_TRANSLATOR_HOSTS')." uth
				JOIN ".DB::getTable('SITE_URI_TRANSLATORS')." ut ON (ut.id = uth.uri_translator_id)
			".($host_name ? "WHERE
				(
					uth.host_name = '".DBFunctions::strEscape($host_name)."'
				) OR (
					uth.host_name LIKE ':%'
					AND ".DBFunctions::regexpMatch("'".DBFunctions::strEscape($host_name)."'", "SUBSTRING(uth.host_name FROM 2)")."
				)
			" : "")."
		");
							
		while ($arr_host = $res->fetchAssoc()) {
			
			$arr[$arr_host['translator_host_name']] = $arr_host;
		}
	
		return ($host_name ? current($arr) : $arr);
	}
	
	public static function getURI($uri_translator_id, $str_identifier, $do_literal = false) {
				
		if ($str_identifier == '') {
			
			$sql_where = "
				u.uri_translator_id = ".(int)$uri_translator_id."
				AND u.identifier = ''
			";
		} else {
			
			$sql_where = "(
				u.uri_translator_id = ".(int)$uri_translator_id."
				AND u.identifier = '".DBFunctions::strEscape($str_identifier)."'
			) OR (
				u.uri_translator_id = ".(int)$uri_translator_id."
				AND u.identifier LIKE ':%'
				AND ".DBFunctions::regexpMatch("'".DBFunctions::strEscape($str_identifier)."'", "SUBSTRING(u.identifier FROM 2)")."
			)";
		}
		
		$res = DB::query("SELECT
			u.uri_translator_id, u.identifier, u.url, u.remark, u.service
				FROM ".DB::getTable('SITE_URIS')." u
				LEFT JOIN ".DB::getTable('SITE_URI_TRANSLATORS')." ut ON (ut.id = u.uri_translator_id)
			WHERE ".$sql_where."
			LIMIT 1
		");

		$arr = $res->fetchAssoc();
		
		if (!$arr) {
			return [];
		}
		
		$is_regex = (substr($arr['identifier'], 0, 1) == ':');
		
		if ($is_regex) {
			
			if (!$do_literal) {
				$arr['url'] = preg_replace('<'.substr($arr['identifier'], 1).'>', $arr['url'], $str_identifier); // '<>' as delimiter, as they are not normally used in URLs
			}
		}
	
		return $arr;
	}
	
	public static function getURL($str, $host_name = false) {
		
		if (substr($str, 0, 1) == '/') {
			
			if (substr($str, 0, 2) == '//') {
				
				$url = SERVER_PROTOCOL.ltrim($str, '/');
			} else {
				
				$url = SERVER_PROTOCOL.($host_name ?: SERVER_NAME_SITE_NAME).$str;
			}
		} else {
			
			$url = $str;
		}
		
		return $url;
	}
}
