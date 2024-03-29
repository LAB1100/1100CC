<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class contact_map extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_contact').' '.getLabel('lbl_map');
		static::$parent_label = getLabel('lbl_communication');
	}
	
	public function contents() {
		
		$return = '<div data-address="'.getLabel('address', 'D').' '.getLabel('address_nr', 'D').'" data-city="'.getLabel('city', 'D').'" data-country="'.getLabel('country', 'D').'"></div>';
		
		SiteEndEnvironment::addScript('http://maps.google.com/maps/api/js?sensor=false', true);
				
		return $return;
	}
	
	public static function css() {
	
		$return = '.contact_map > div { width: 100%; height: 500px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.contact_map', function(elm_scripter) {
		
			elm_scripter.children('div').each(function() {
				var cur = $(this);
				cur.goMap({
						zoom: 10,
						maptype: 'ROADMAP',
						markers: [{
								address: cur.attr('data-address')+','+cur.attr('data-city')+','+cur.attr('data-country'),
								html: { 
									content: cur.attr('data-address')+'<br />'+cur.attr('data-city')+'<br />'+cur.attr('data-country'), 
									popup: true 
								} 
						}]
				});
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY
		
	}
}
