<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class contact_info extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_contact').' '.getLabel('lbl_info');
		static::$parent_label = getLabel('lbl_communication');
	}
	
	public function contents() {
		
		$return = '<ul>
			<li><span class="icon">'.getIcon('home').'</span><span>'.getLabel('address', 'D').' '.getLabel('address_nr', 'D').'</span></li>
			<li><span></span><span>'.getLabel('zipcode', 'D').'</span> <span>'.getLabel('city', 'D').'</span></li>
			<li><span></span><span>'.getLabel('country', 'D').'</span></li>
			<li class="split"></li>
			'.(getLabel('email', 'D', true) ? '<li><span class="icon">'.getIcon('email').'</span><span>'.getLabel('email', 'D').'</span></li>' : '').'
			'.(getLabel('tel', 'D', true) ? '<li><span class="icon">'.getIcon('telephone').'</span><span>'.getLabel('tel', 'D').'</span></li>' : '').'
		</ul>';
				
		return $return;
	}
	
	public static function css() {
	
		$return = '.contact_info > ul { float: left; width: 225px; }
				.contact_info > ul li { line-height: 18px; }
				.contact_info > ul .split { height: 14px; }
				.contact_info > ul li > span { display: inline-block; vertical-align: middle; }
				.contact_info > ul li > span:first-child { width: 24px; text-align: left;}
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY

	}
}
