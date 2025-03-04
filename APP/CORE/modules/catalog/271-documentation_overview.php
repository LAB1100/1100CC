<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class documentation_overview extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_documentation_overview');
		static::$parent_label = getLabel('lbl_modules');
	}
		
	public static function moduleVariables() {
		
		$return = '<select name="id">';
		$return .= cms_general::createDropdown(cms_documentations::getDocumentations());
		$return .= '</select>';
		
		return $return;
	}
	
	public function contents() {
			
		$documentation_id = ($this->arr_variables['id'] ?: 0);
		$arr_documentation = cms_documentations::getDocumentations($documentation_id);
		
		$arr_documentation_vars = documentation::findMainDocumentation($documentation_id);
		$arr_documentation_vars['mod_vars'] = SiteStartEnvironment::getModuleVariables($arr_documentation_vars['id']);
	
		$arr_documentation_sections = cms_documentation_sections::getDocumentationSections($documentation_id, false, true);
		$arr_documentation_section_ids = [];
		
		foreach ($arr_documentation_sections as $documentation_section_id => $arr_documentation_section) {
			
			if (!$arr_documentation_section['parent_section_id']) {
				$arr_documentation_section_ids[] = $documentation_section_id;
			}
		}
		
		$return = '<h1>'.strEscapeHTML(Labels::parseTextVariables($arr_documentation['name'])).'</h1>'
			.'<section class="body">'.parseBody($arr_documentation['description']).'</section>'
			.self::createDocumentationSectionList($arr_documentation_vars, $arr_documentation_section_ids, $arr_documentation_sections, 0);
										
		return $return;
	}
	
	private static function createDocumentationSectionList($arr_documentation_vars, $arr_documentation_section_ids, $arr_documentation_sections, $num_identation_level = 0) {

		if ($arr_documentation_vars['mod_vars'][0]) {
			$active_documentation_section_id = $arr_documentation_vars['mod_vars'][0];
		}
		
		$return = '<ul>';

		foreach ($arr_documentation_section_ids as $documentation_section_id) {
			
			$arr_documentation_section = $arr_documentation_sections[$documentation_section_id];
			$active = false;
			
			if ($active_documentation_section_id == $documentation_section_id) {
				$active = true;
			}
			
			$str_title = Labels::parseTextVariables($arr_documentation_section['title']);
			
			$return .= '<li>';
			
			$i = 1;
			$html_identation = '';
			while ($i <= $num_identation_level) {
				
				$i++;
				$html_identation .= '<span class="indentation"></span>';
			}
			
			$str_url_documentation = SiteStartEnvironment::getModuleURL($arr_documentation_vars['id'], $arr_documentation_vars['page_name'], $arr_documentation_vars['sub_dir'], true);
			$str_url_section = $str_url_documentation.$documentation_section_id.'/'.$arr_documentation_section['name'];
			
			$return .= '<a '.($active ? 'class="active"' : '').' href="'.$str_url_section.'">'
				.$html_identation
				.'<span class="title">'.strEscapeHTML($str_title).'</span>'
			.'</a>';
			
			if ($arr_documentation_section['child_section_ids']) {
				
				$return .= self::createDocumentationSectionList($arr_documentation_vars, $arr_documentation_section['child_section_ids'], $arr_documentation_sections, $num_identation_level + 1);
			}
			
			$return .= '</li>';
		}	
		
		$return .= '</ul>';	
		
		return $return;
	}
		
	public static function css() {
	
		$return = '
			.documentation_overview > section { margin-bottom: 20px;  }
			.documentation_overview ul li a span.indentation { width: 10px; display: inline-block;}
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {

	}
	
	public static function findMainDocumentationOverview($id = 0) {
		
		if ($id) {
			return pages::getClosestModule('documentation_overview', 0, 0, 0, $id, 'id');
		} else {
			return pages::getClosestModule('documentation_overview', SiteStartEnvironment::getDirectory('id'), SiteStartEnvironment::getPage('id'), 0, $id, 'id');
		}
	}
}
