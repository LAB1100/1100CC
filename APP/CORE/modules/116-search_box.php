<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class search_box extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_search_box');
		static::$parent_label = getLabel('ttl_site');
	}
		
	public function contents() {
					
		$return = '<form id="f:search_box:search-0">
				<input type="search" name="string" value="" placeholder="'.getLabel('inp_search').'" /><button type="submit" value=""><span class="icon">'.getIcon('search').'</span></button>
			</form>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.search_box { text-align: right; }
			.search_box form > input[type=search] { width: 125px; border-top-right-radius: 0px; border-bottom-right-radius: 0px; }
			.search_box form > button[type=submit] { margin-left: 0px; border-top-left-radius: 0px; border-bottom-left-radius: 0px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = '') {
	
		if ($method == 'search') {
			
			$str_url = pages::getModuleURL(self::findSearch());
			
			if (!$str_url) {
				return;
			}
			
			$str_url .= search::encodeURLString(str2Clean($_POST['string']));
			
			Response::location($str_url);
		}
	}
	
	public static function findSearch() {
	
		return pages::getClosestModule('search', SiteStartEnvironment::getDirectory('id'), SiteStartEnvironment::getPage('id'));
	}
}
