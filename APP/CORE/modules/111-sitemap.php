<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class sitemap extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_sitemap');
		static::$parent_label = getLabel('ttl_site');
	}

	public function contents() {

		$directory_id = SiteStartVars::getDirectory('id');

		if (SiteStartVars::getDirectory('require_login') && SiteStartVars::getDirectory('user_group_id') && (empty($_SESSION['USER_GROUP']) || SiteStartVars::getDirectory('user_group_id') != $_SESSION['USER_GROUP'])) {

			$directory_id = directories::getParentDirectory();
		}

		$arr_directories = directories::getDirectoriesInRange($directory_id);
		
		$arr_directory_pages = pages::getPages(0, array_keys($arr_directories), false, 0, true);

		foreach ($arr_directories as $row_directory) {
			
			$base_url = pages::getBaseURL($row_directory);
			
			$return .= '<ul>
				<li>'.($row_directory['title'] ? '<a href="'.$base_url.'">'.strEscapeHTML(Labels::parseTextVariables($row_directory['title'])).'</a>' : '').'</li>';
				
				if ($arr_directory_pages[$row_directory['id']]) {
					
					$arr_directory_pages[$row_directory['id']] = pages::filterClearance($arr_directory_pages[$row_directory['id']], ($_SESSION['USER_GROUP'] ?? null), ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')] ?? null));
					foreach ($arr_directory_pages[$row_directory['id']] as $page) {
						$return .= '<li>'.($page['title'] ? '<a href="'.($page['url'] ? strEscapeHTML($page['url']) : $base_url.$page['name']).'">'.strEscapeHTML(Labels::parseTextVariables($page['title'])).'</a>' : '').'</li>';
					}
				}
			$return .= '</ul>';
		}
				
		return $return;
	}
	
	public static function css() {
	
		$return = '.sitemap > ul { display: inline-block; vertical-align: top; margin-right: 30px; }
					.sitemap > ul:last-child { margin-right: 0px; }
					.sitemap > ul > li:first-child { font-weight: bold; }
					.sitemap > ul > li:empty { display: none; }';
					
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
