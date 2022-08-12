<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class navigation extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_navigation');
		static::$parent_label = getLabel('ttl_site');
	}
	
	public static function moduleVariables() {
		
		$return = '<select name="directory_id" title="Directory">';
		$return .= directories::createDirectoriesDropdown(directories::getDirectories(), false, true);
		$return .= '</select>';
		
		return $return;
	}
	
	public function contents() {
		
		$return = '';
		
		if (SiteStartVars::$dir['require_login'] && SiteStartVars::$dir['user_group_id'] && (empty($_SESSION['USER_GROUP']) ||SiteStartVars::$dir['user_group_id'] != $_SESSION['USER_GROUP'])) {
			
			$return .= '<ul><li class="active"><a href="'.SiteStartVars::getBasePath().SiteStartVars::$page_name.'">'.strEscapeHTML(Labels::parseTextVariables(SiteStartVars::$page['title'])).'</a></li></ul>';
		} else {
			
			$directory_id = ($this->arr_variables['directory_id'] ?: SiteStartVars::$dir['id']);
			
			$root_directory_id = directories::getClosestRootedDirectory($directory_id);
			$arr_root_directory = directories::getDirectories($directory_id);
			
			$arr_pages = pages::getPagesLimited($root_directory_id, $directory_id, false, 0, true);
			$arr_pages = pages::filterClearance($arr_pages, ($_SESSION['USER_GROUP'] ?? null), ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')] ?? null));
			
			foreach ($arr_pages as $arr_page) {
				$arr_active[$arr_page['directory_id']] = true;
			}
			
			$arr_directories = directories::getDirectoriesLimited($root_directory_id, $directory_id, true);
			$cur_path_length = -1;
			$active_dir = 0;
			
			foreach ($arr_directories as $arr_directory) {
				
				$base_url = SiteStartVars::getBasePath($arr_directory['path_length']);
				
				if ($cur_path_length != $arr_directory['path_length']) {
					
					$return .= ($cur_path_length != -1 ? '</ul>' : '').'<ul>';
					
					foreach ($arr_pages as $arr_page) {
						
						if ($arr_page['directory_id'] == $arr_directory['ancestor_id']) {
							
							$return .= '<li'.($arr_directory['path_length'] == 0 && $arr_page['id'] == SiteStartVars::$page['id'] ? ' class="active"' : '').'><a href="'.($arr_page['url'] ? strEscapeHTML($arr_page['url']) : $base_url.$arr_page['name']).'">'.strEscapeHTML(Labels::parseTextVariables($arr_page['title'])).'</a></li>';
							array_shift($arr_pages);
						} else {
							break;
						}
					}
				}
				
				$return .= '<li'.($arr_active[$arr_directory['id']] || $arr_directory['id'] == SiteStartVars::$dir['id'] ? ' class="active"' : '').'><a href="'.$base_url.$arr_directory['name'].'/">'.strEscapeHTML(Labels::parseTextVariables($arr_directory['title'])).'</a></li>';
				
				$cur_path_length = $arr_directory['path_length'];
			}
			
			$return .= ($cur_path_length != -1 ? '</ul>' : '');
		
			if (count($arr_pages)) {
				
				$num_distance = (SiteStartVars::$dir['path_length'] - $arr_root_directory['path_length']);
				$base_url = SiteStartVars::getBasePath($num_distance);
				
				$cur_pages = '';
				
				foreach ($arr_pages as $arr_page) {
					
					$cur_pages .= '<li'.($arr_page['id'] == SiteStartVars::$page['id'] ? ' class="active"' : '').'><a href="'.($arr_page['url'] ? strEscapeHTML($arr_page['url']) : $base_url.$arr_page['name']).'">'.strEscapeHTML(Labels::parseTextVariables($arr_page['title'])).'</a></li>';
				}
				if ($cur_pages) {
					
					$return .= '<ul>'.$cur_pages.'</ul>';
				}
			}
		}
		
		$return = ($return ? '<nav'.(!SiteStartVars::$page['publish'] ? ' class="no-active-page"' : '').'>'.$return.'</nav>' : '');
		
		return $return;
	}
	
	public static function css() {
	
		$return = '';
		
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
