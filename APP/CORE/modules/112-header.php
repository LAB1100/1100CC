<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class header extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_header');
		static::$parent_label = getLabel('ttl_site');
	}
	
	public static function moduleVariables() {
		
		$return = '<select name="directory_id" title="Directory">';
		$return .= directories::createDirectoriesDropdown(directories::getDirectories(), false, true);
		$return .= '</select>';
		
		return $return;
	}
	
	protected $arr_access = [
		'search_box' => []
	];
	
	public function contents() {
		
		$navigation = new navigation;
		
		if ($this->arr_variables['directory_id']) {
			$navigation->setModVariables(['directory_id' => $this->arr_variables['directory_id']]);
		}
		
		$navigation = $navigation->contents();
		
		$search_box = new search_box;
		$search_box = $search_box->contents();
		
		$logout = new logout;
		$logout = $logout->contents();
		
		$return .= '<a href="'.(SiteStartVars::$login_dir ? SiteStartVars::$login_dir['path'].'/' : '/').'" alt="'.getLabel('name', 'D').'"></a>';
		
		$return .= '<div class="search_box">'.$search_box.'</div>';
		
		$return .= '<div class="navigation">'
			.'<input id="toggle-navigation-'.$this->mod_id.'" type="checkbox" /><label for="toggle-navigation-'.$this->mod_id.'">☰</label>'
			.$navigation.($logout ? '<div class="logout">'.$logout.'</div>' : '')
		.'</div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.header { position: relative; }
			.header > .navigation input[type=checkbox],
			.header > .navigation input[type=checkbox] + label { display: none; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {

	}
}
