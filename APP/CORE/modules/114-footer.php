<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class footer extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_footer');
		static::$parent_label = getLabel('ttl_site');
	}
	
	public function contents() {
		
		$sitemap = new sitemap();
		
		$return .= '<div class="sitemap">'.$sitemap->contents().'</div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.footer { position: relative; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {

	}
}
