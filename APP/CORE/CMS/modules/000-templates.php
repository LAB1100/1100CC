<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_PAGE_TEMPLATES', DB::$database_cms.'.site_page_templates');

class templates extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	public static function css() {
	
		$return = '.template-preview { background-color: #f5f5f5; width: 75px; height: 75px; }
				.template-preview table { background-color: transparent; width: 100%; height: 100%; }
				.template-preview table td { border: 3px solid #ffffff; padding: 0px; }
				.template-preview td[colspan] { background-color: black; }
				.template-preview td.master { background-color: #c0c0c0; }
				.template-preview td.set { background-color: #fd5c4d; }
				
				.template-select { text-align: left; }
				.template-select .template-option { text-align: right; display: inline-block; padding: 3px; background-color: #f5f5f5; }
				.template-select .template-option:hover { background-color: #e7f2f9; }
				.template-select .template-option .template-preview { height: 60px; width: 60px; }';
		
		return $return;
	}
	
	public static function getTemplates($template = 0, $active = false) {
	
		$arr = [];
		
		if ($template) {
			
			$res = DB::query("SELECT pt.*
					FROM ".DB::getTable('TABLE_PAGE_TEMPLATES')." pt
					".($active ? "JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.template_id = pt.id)" : "")."
				WHERE pt.id = ".(int)$template."
				GROUP BY pt.id
			");
								
			$arr = $res->fetchAssoc();			
		} else {
			
			$res = DB::query("SELECT pt.*
					FROM ".DB::getTable('TABLE_PAGE_TEMPLATES')." pt
					".($active ? "JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.template_id = pt.id)" : "")."
				GROUP BY pt.id
				ORDER BY pt.id
			");
			
			while($arr_row = $res->fetchAssoc()) {
				
				$arr[$arr_row['id']] = $arr_row;
			}
		}		
		return $arr;
	}
			
	public static function createTemplatesMenu($arr, $selected = 0) {

		if (!count($arr)) {
			
			$return .= 'No templates available.';
		} else {

			$return .= '<div class="template-select">';
				
				foreach($arr as $path => $arr_row) {
					
					$return .= '<div class="template-option"><input type="radio" name="template_select" value="'.$arr_row['id'].'"'.($arr_row['id'] == $selected ? ' checked="checked"' : '').' /><div class="template-preview"'.($arr_row['name'] ? ' title="'.$arr_row['name'].'"': '').'>'.$arr_row['preview'].'</div></div>';
				}
			
			$return .= '</div>';
		}
		
		return $return;
	}
	
	public static function writeTemplateSheet() {
	
		$arr_templates = self::getTemplates(0, true);
		
		foreach ($arr_templates as $arr_row) {
			
			$css .= $arr_row['css'];
		}
		
		$path = DIR_SITE_STORAGE.'css/templates.css';
		
		FileStore::deleteFile($path);
		FileStore::storeFile($path, $css);
	}
}
