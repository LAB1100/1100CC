<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class navigation extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_navigation');
		static::$parent_label = getLabel('ttl_site');
	}
	
	public static function moduleVariables() {
		
		$str_html = '<select name="directory_id" title="Directory">'
			.directories::createDirectoriesDropdown(directories::getDirectories(), false, true)
		.'</select>';
		
		return $str_html;
	}
	
	public function contents() {
		
		$str_html = '';
		
		if (SiteStartEnvironment::getDirectory('require_login') && SiteStartEnvironment::getDirectory('user_group_id') && (empty($_SESSION['USER_GROUP']) ||SiteStartEnvironment::getDirectory('user_group_id') != $_SESSION['USER_GROUP'])) {
			
			$str_html .= '<ul><li class="active"><a href="'.SiteStartEnvironment::getBasePath().SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_PAGE_NAME).'">'.strEscapeHTML(Labels::parseTextVariables(SiteStartEnvironment::getPage('title'))).'</a></li></ul>';
		} else {
			
			$directory_id = ($this->arr_variables['directory_id'] ?: SiteStartEnvironment::getDirectory('id'));
			$arr_main_directory = SiteStartEnvironment::getDirectory();
			
			$root_directory_id = directories::getClosestRootedDirectory($directory_id);
			$num_distance_offset = 0;
			
			if ($directory_id != SiteStartEnvironment::getDirectory('id')) {
				
				$arr_main_directory = directories::getDirectories($directory_id);
				
				$arr_root_directory = directories::getDirectories($root_directory_id);
				$num_distance_offset = (SiteStartEnvironment::getDirectory('path_length') - $arr_root_directory['path_length']);
			}

			$arr_pages = pages::getPagesByScope($root_directory_id, $directory_id, false, 0, true);
			$arr_pages = pages::filterClearance($arr_pages, ($_SESSION['USER_GROUP'] ?? null), ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')] ?? null));
			
			foreach ($arr_pages as $arr_page) {
				$arr_active[$arr_page['directory_id']] = true;
			}
			
			$arr_directories = directories::getDirectoriesLimited($root_directory_id, $directory_id, true);
			$num_path_length = -1;
			
			foreach ($arr_directories as $arr_directory) {
				
				$arr_pages_check = pages::getDirectoryPages($arr_directory['id'], false);
				$has_clearance_any = pages::filterClearance($arr_pages_check, ($_SESSION['USER_GROUP'] ?? null), ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')] ?? null), true);
				
				if (!$has_clearance_any) {
					continue;
				}
				
				$num_distance = ($arr_directory['path_length'] + $num_distance_offset);
				$str_base_url = SiteStartEnvironment::getBasePath($num_distance);
				
				if ($num_path_length != $arr_directory['path_length']) {
					
					$str_html .= ($num_path_length != -1 ? '</ul>' : '').'<ul>';
					
					foreach ($arr_pages as $arr_page) {
						
						if ($arr_page['directory_id'] == $arr_directory['ancestor_id']) {
							
							$str_html .= '<li'.($arr_directory['path_length'] == 0 && $arr_page['id'] == SiteStartEnvironment::getPage('id') ? ' class="active"' : '').'><a href="'.($arr_page['url'] ? strEscapeHTML($arr_page['url']) : $str_base_url.$arr_page['name']).'">'.strEscapeHTML(Labels::parseTextVariables($arr_page['title'])).'</a></li>';
							array_shift($arr_pages);
						} else {
							break;
						}
					}
				}
				
				$str_html .= '<li'.($arr_active[$arr_directory['id']] || $arr_directory['id'] == SiteStartEnvironment::getDirectory('id') ? ' class="active"' : '').'><a href="'.$str_base_url.$arr_directory['name'].'/">'.strEscapeHTML(Labels::parseTextVariables($arr_directory['title'])).'</a></li>';
				
				$num_path_length = $arr_directory['path_length'];
			}
			
			$str_html .= ($num_path_length != -1 ? '</ul>' : '');
		
			if (count($arr_pages)) {
				
				$num_distance = (SiteStartEnvironment::getDirectory('path_length') - $arr_main_directory['path_length']);
				$str_base_url = SiteStartEnvironment::getBasePath($num_distance);
				
				$str_html_pages = '';
				
				foreach ($arr_pages as $arr_page) {
					$str_html_pages .= '<li'.($arr_page['id'] == SiteStartEnvironment::getPage('id') ? ' class="active"' : '').'><a href="'.($arr_page['url'] ? strEscapeHTML($arr_page['url']) : $str_base_url.$arr_page['name']).'">'.strEscapeHTML(Labels::parseTextVariables($arr_page['title'])).'</a></li>';
				}
				
				if ($str_html_pages) {
					$str_html .= '<ul>'.$str_html_pages.'</ul>';
				}
			}
		}
		
		$str_html = ($str_html ? '<nav'.(!SiteStartEnvironment::getPage('publish') ? ' class="no-active-page"' : '').'>'.$str_html.'</nav>' : '');
		
		return $str_html;
	}
	
	public static function css() {
	
		$str_return = '';
		
		return $str_return;
	}
	
	public static function js() {
	
		$str_return = "";
		
		return $str_return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY

	}
}
