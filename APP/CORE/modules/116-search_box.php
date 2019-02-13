<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class search_box extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_search_box');
		static::$parent_label = getLabel('ttl_site');
	}
		
	public function contents() {
					
		$return .= '<form id="f:search_box:search-0">
				<input type="text" name="string" value="" placeholder="'.getLabel('inp_search').'" /><button type="submit" value=""><span class="icon">'.getIcon('search').'</span></button>
			</form>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.search_box { text-align: right; }
			.search_box form input { vertical-align: top; }
			.search_box form input[type=text] { width: 125px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		if ($method == "search") {
			
			Response::location(pages::getModUrl(self::findSearch()).$_POST['string']);
		}
	}
	
	public static function findSearch() {
	
		return pages::getClosestMod('search', SiteStartVars::$dir['id'], SiteStartVars::$page['id']);
	}
}
