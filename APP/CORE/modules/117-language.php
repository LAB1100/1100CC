<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class language extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_language');
		static::$parent_label = getLabel('ttl_site');
	}
		
	public function contents() {
		
		$arr_languages = cms_language::getLanguageSelectable();
		
		$return = '';

		if (count($arr_languages) > 1) {
			
			$str_html_language = '';
			$str_language_override = SiteEndEnvironment::getModifierVariables('language');
			
			foreach ($arr_languages as $str_lang_code => $arr_language) {
				
				$str_url = '';

				if ($arr_language['host_canonical']) {
					
					SiteEndEnvironment::setModifierVariable('language', null);
					$str_url = SERVER_SCHEME.SERVER_NAME_SUB.SERVER_NAME_CUSTOM.$arr_language['host_canonical'].SiteEndEnvironment::getLocation(true, SiteEndEnvironment::LOCATION_CANONICAL_PUBLIC);
				} else {
					
					SiteEndEnvironment::setModifierVariable('language', $str_lang_code);
					$str_url = SiteEndEnvironment::getLocation(true, SiteEndEnvironment::LOCATION_CANONICAL_PUBLIC);
				}
				
				$str_html_language .= '<li><a href="'.$str_url.'" title="'.strEscapeHTML($arr_language['label']).'">'.$str_lang_code.'</a></li>';
			}
			
			SiteEndEnvironment::setModifierVariable('language', $str_language_override);
			
			$return .= '<ul>'.$str_html_language.'</ul>';
		}
		
		return $return;
	}
	
	public static function css() {
	
		$return = '
			.language ul > li { display: inline-block; }
			.language ul > li:not(:first-child)::before { content: "/"; margin: 0px 0.15em; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = '') {
	
	}
}
