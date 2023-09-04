<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class documentation extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_documentation');
		static::$parent_label = getLabel('lbl_modules');
	}
		
	public static function moduleVariables() {
		
		$return = '<select name="id">';
		$return .= cms_general::createDropdown(cms_documentations::getDocumentations());
		$return .= '</select>';
		
		return $return;
	}
	
	public static function searchProperties() {
		
		return [
			'trigger' => [DB::getTable('TABLE_DOCUMENTATION_SECTIONS'), DB::getTable('TABLE_DOCUMENTATION_SECTIONS').'.body', 'AND '.DB::getTable('TABLE_DOCUMENTATION_SECTIONS').'.publish = TRUE'],
			'title' => [DB::getTable('TABLE_DOCUMENTATION_SECTIONS'), DB::getTable('TABLE_DOCUMENTATION_SECTIONS').'.title'],
			'extra_values' => [
				[DB::getTable('TABLE_DOCUMENTATION_SECTIONS'), DB::getTable('TABLE_DOCUMENTATION_SECTIONS').'.id', 'documentation_section_id'],
				[DB::getTable('TABLE_DOCUMENTATION_SECTIONS'), DB::getTable('TABLE_DOCUMENTATION_SECTIONS').'.name', 'documentation_section_name']
			],
			'search_var' => [DB::getTable('TABLE_DOCUMENTATIONS'), DB::getTable('TABLE_DOCUMENTATIONS').'.id'],
			'module_link' => [
				[DB::getTable('TABLE_DOCUMENTATION_SECTIONS'), DB::getTable('TABLE_DOCUMENTATION_SECTIONS').'.documentation_id'],
				[DB::getTable('TABLE_DOCUMENTATIONS'), DB::getTable('TABLE_DOCUMENTATIONS').'.id']
			],
			'module_var' => 'id',
			'module_query' => function($arr_result) {
				return $arr_result['extra_values'][DB::getTable('TABLE_DOCUMENTATION_SECTIONS')]['documentation_section_id'].'/'.$arr_result['extra_values'][DB::getTable('TABLE_DOCUMENTATION_SECTIONS')]['documentation_section_name'];
			}
		];
	}
	
	public function contents() {

		$documentation_id = $this->arr_variables['id'];
		$documentation_section_id = $this->arr_query[0];
		
		$return = '';
	
		if ((int)$documentation_section_id) {
			
			$arr_documentation_section = cms_documentation_sections::getDocumentationSections($documentation_id, $documentation_section_id);
			
			if (!$arr_documentation_section) {
				
				$arr_documentation_overview_vars = documentation_overview::findMainDocumentationOverview($documentation_id);
				
				Response::location(SiteStartVars::getPageURL($arr_documentation_overview_vars['page_name'], $arr_documentation_overview_vars['sub_dir']));
			}
			
			$return = $this->createDocumentationSection($arr_documentation_section);		
				
		} else if ((int)$documentation_id) {
		
			$arr_documentation = cms_documentations::getDocumentations((int)$documentation_id);
			$description = parseBody($arr_documentation['description']);
			
			$return = '<h1>'.$arr_documentation['name'].'</h1>'
				.'<section class="body">'.$description.'</section>';
		}
										
		return $return;
	}
	
	public function createDocumentationSection($arr_documentation_section) {
		
		$documentation_id = $arr_documentation_section['documentation_id'];
		$documentation_section_id = $arr_documentation_section['id'];

		$arr_documentation = cms_documentations::getDocumentations($documentation_id);
		$arr_documentation_sections = cms_documentation_sections::getDocumentationSections($documentation_id, false, true, ['meta_data' => true]);
		
		$arr_documentation_section_ids = array_keys($arr_documentation_sections);
		
		$num_key = array_search($documentation_section_id, $arr_documentation_section_ids);
		$prev_id = $arr_documentation_section_ids[$num_key - 1];
		$next_id = $arr_documentation_section_ids[$num_key + 1];
		
		$str_title = strEscapeHTML(Labels::parseTextVariables($arr_documentation_section['title']));
		SiteEndVars::addTitle($str_title);
		SiteEndVars::setType('article');
		
		$arr_documentation_overview_vars = documentation_overview::findMainDocumentationOverview($documentation_id);
		
		$str_url_documentation = SiteStartVars::getShortestModuleURL($this->mod_id, false, $this->arr_mod['shortcut'], $this->arr_mod['shortcut_root'], 0, true);
		$str_url_section = $str_url_documentation.$arr_documentation_section['id'].'/'.$arr_documentation_section['name'];
		
		cms_documentation_sections::loadTextTags($documentation_id, $documentation_section_id, $this->arr_mod);
				
		$body = parseBody($arr_documentation_section['body']);
		
		$return = '<nav class="breadcrumbs">'
			.'<a href="'.SiteStartVars::getPageURL($arr_documentation_overview_vars['page_name'], $arr_documentation_overview_vars['sub_dir']).'"><span>'.strEscapeHTML(Labels::parseTextVariables($arr_documentation['name'])).'</span></a>'
			.$this->createDocumentationSectionBreadcrumb($documentation_section_id, $arr_documentation_sections)
			.'<a href="'.$str_url_section.'"><span class="icon">'.getIcon('next').'</span><span>'.$str_title.'</span></a>'
		.'</nav>'
		.'<article>'
			.'<h1>'.$str_title.'</h1>'
			.'<time>'.getLabel('lbl_created').': '.date('d-m-Y', strtotime($arr_documentation_section['date_created'])).'.</time><time>'.getLabel('lbl_last_update').': '.date('d-m-Y', strtotime($arr_documentation_section['date_updated'])).'.</time>'
			.'<section class="body">'.$body.'</section>'
		.'</article>'
		.'<nav class="nextprev">'
			.($prev_id ? $this->createDocumentationSectionLink($arr_documentation_sections[$prev_id], 'prev') : '<span></span>')
			.($next_id ? $this->createDocumentationSectionLink($arr_documentation_sections[$next_id], false, 'next') : '<span></span>')
		.'</nav>';
		
		if ($this->arr_mod['shortcut']) {
			
			SiteEndVars::setShortcut($this->mod_id, $this->arr_mod['shortcut'], $this->arr_mod['shortcut_root']);
		}
		
		return $return;
	}
	
	public function createDocumentationSectionBreadcrumb($documentation_section_id, $arr_documentation_sections, $html_breadcrumbs = '') {
		
		$parent_documentation_section_id = $arr_documentation_sections[$documentation_section_id]['parent_section_id'];
		
		if ($parent_documentation_section_id) {
			
			$arr_parent_documentation_section = $arr_documentation_sections[$parent_documentation_section_id];
			$html_breadcrumbs = $this->createDocumentationSectionLink($arr_parent_documentation_section, 'next').$html_breadcrumbs;
			
			return $this->createDocumentationSectionBreadcrumb($parent_documentation_section_id, $arr_documentation_sections, $html_breadcrumbs);
		} else {
			
			return $html_breadcrumbs;
		}
	}
	
	private function createDocumentationSectionLink($arr_documentation_section, $icon_left = false, $icon_right = false) {
		
		$url_documentation = SiteStartVars::getModuleURL($this->mod_id, false, 0, true);
		$url_section = $url_documentation.$arr_documentation_section['id'].'/'.$arr_documentation_section['name'];
		
		$html = '<a href="'.$url_section.'">'
			.($icon_left ? '<span class="icon">'.getIcon($icon_left).'</span>' : '')
			.'<span>'.strEscapeHTML(Labels::parseTextVariables($arr_documentation_section['title'])).'</span>'
			.($icon_right ? '<span class="icon">'.getIcon($icon_right).'</span>' : '')
		.'</a>';
		
		return $html;
	}
		
	public static function css() {
	
		$return = '
				.documentation > nav.breadcrumbs > a > span.icon { padding: 0 5px;  }
				.documentation > nav.breadcrumbs > a > span.icon > svg { height: 10px; }
				.documentation > nav.breadcrumbs > a > span:not(.icon) { vertical-align: middle; }
				.documentation > nav.breadcrumbs > a:hover { text-decoration: none; }
				.documentation > nav.breadcrumbs > a:hover > span:not(.icon) { text-decoration: underline; }
				.documentation > nav.nextprev { display: flex; justify-content: space-between; margin-top: 40px; }
				.documentation > nav.nextprev a > span:not(.icon) { vertical-align: middle; }
				.documentation > nav.nextprev a > span.icon svg { height: 0.8em; }
				.documentation > nav.nextprev a > span + span { margin-left: 10px; }
			';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {

	}
		
	public static function findMainDocumentation($id = 0) {
		
		if ($id) {
			return pages::getClosestModule('documentation', 0, 0, 0, $id, 'id');
		} else {
			return pages::getClosestModule('documentation', SiteStartVars::getDirectory('id'), SiteStartVars::getPage('id'), 0, $id, 'id');
		}
	}
}
