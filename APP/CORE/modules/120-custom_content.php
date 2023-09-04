<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class custom_content extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_custom_content');
		static::$parent_label = getLabel('ttl_site');
	}
	
	public static function moduleVariables() {
		$return .= '<select>';
		$return .= cms_general::createDropdown(cms_custom_content::getCustomContent(), 0, true);
		$return .= '</select>';
		
		return $return;
	}
	
	public static function searchProperties() {
	
		return [
			'trigger' => [DB::getTable('TABLE_CUSTOM_CONTENT'), DB::getTable('TABLE_CUSTOM_CONTENT').'.body'],
			'title' => [DB::getTable('TABLE_PAGES'), DB::getTable('TABLE_PAGES').'.title'],
			'search_var' => [DB::getTable('TABLE_CUSTOM_CONTENT'), DB::getTable('TABLE_CUSTOM_CONTENT').'.id'],
			'module_link' => [
				[DB::getTable('TABLE_CUSTOM_CONTENT'), DB::getTable('TABLE_CUSTOM_CONTENT').'.id'],
				[DB::getTable('TABLE_PAGE_MODULES'), DBFunctions::castAs(DB::getTable('TABLE_PAGE_MODULES').'.var', DBFunctions::CAST_TYPE_INTEGER), 'AND '.DB::getTable('TABLE_PAGE_MODULES').".module = 'custom_content'"],
				[DB::getTable('TABLE_PAGE_MODULES'), DB::getTable('TABLE_PAGE_MODULES').'.page_id'],
				[DB::getTable('TABLE_PAGES'), DB::getTable('TABLE_PAGES').'.id']
			],
			'module_var' => false,
			'module_query' => function($arr_result) {
				return false;
			}
		];
	}
	
	public function contents() {
	
		if (!$this->arr_variables) {
			return false;
		}
		
		$arr = cms_custom_content::getCustomContent((int)$this->arr_variables);
		
		// Prepare module variables for special purposes
		
		$str_embed_url = false;
		
		if ($this->arr_query) {
			
			if ($this->arr_query['embed']) { // Customised location/embed solution
				$str_embed_url = arr2String($this->arr_query['embed'], '/');
			}
		} else {
			
			if (SiteStartVars::getRequestVariables('embed')) { // Default location/embed solution
				$str_embed_url = arr2String(SiteStartVars::getRequestVariables('embed'), '/');
			}
		}
		
		if ($str_embed_url) {
			
			$str_embed_url = str_replace(['||', '|', '#=#'], ['#=#', '/', '|'], $str_embed_url); // '|' are the URL path separators, place back '/'
			$str_embed_url = strEscapeHTML($str_embed_url).'#'; // Add URI fragment for 'commenting out' possible trailing path info
			Labels::setVariable('embed', '/'.$str_embed_url);
		} else {
			Labels::setVariable('embed', '/');
		}
		
		// Parse code
		
		if ($arr['script']) {
			
			$p = new ParsePHPString();
			$arr['script'] = trim($p->parse($arr['script']));
			
			if ($arr['script']) {
				SiteEndVars::addScript($arr['script']);
			}
		}
		
		// Process content
		
		$arr['body'] = parseBody($arr['body']);
		
		if ($arr['description']) {
			SiteEndVars::addDescription(strEscapeHTML($arr['description']));
		}
		if ($arr['tags']) {
			SiteEndVars::addKeywords(explode(',', $arr['tags']));
		}
		
		$arr['style'] = (!$arr['style'] || $arr['style'] == 'default' ? 'body' : $arr['style']);
		$this->style = $arr['style'];
		
		$return = $arr['body'];
								
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
