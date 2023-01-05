<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class images extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_images');
		static::$parent_label = getLabel('lbl_modules');
	}
	
	public static function moduleVariables() {
		
		$return = '<select name="tag_id">';
		$return .= cms_general::createDropdown(cms_media::getMediaTags());
		$return .= '</select>';
		$return .= '<select name="columns">';
		for ($i = 1; $i <= 15; $i++) {
			$return .= '<option value="'.$i.'">'.$i.'</option>';
		}
		$return .= '</select>';
		
		return $return;
	}
	
	public function contents() {
		
		$arr_media = cms_media::getMedia(false, $this->arr_variables['tag_id']);
		
		$return = '<div class="album" data-lazyload="1"><table>';
			
			$count = 0;
			$num_columns = ($this->arr_variables['columns'] ?? 1);
			
			foreach ($arr_media as $arr_element) {
				
				if (!($count % $num_columns)) {
					$return .= '<tr>';
				}
				
				$return .= '<td><figure>'
					.'<img src="/'.DIR_CSS.'images/blank.png" data-original="'.SiteStartVars::getCacheUrl('img', [350, 300], '/'.DIR_CMS.DIR_UPLOAD.$arr_element['directory'].$arr_element['filename']).'" />'
					.($arr_element['description'] ? '<figurecaption>'.parseBody($arr_element['description']).'</figurecaption>' : '')
				.'</figure></td>';

				$count++;
				
				if (!($count % $num_columns)) {
					
					$return .= '</tr>';
				}
			}
			
			if (($count % $num_columns)) {
				$return .= '</tr>';
			}
			
		$return .= '</table></div>';
		
		return $return;
	}
	
	public static function css() {
		
		$return = '.images { }
			.images > .album > table { width: 100%; border-spacing: 10px 10px; }
			.images > .album > table td { vertical-align: top; text-align: center; }
			.images > .album > table td img[data-original] { background-color: #f5f5f5; width: 100%; height: 150px; }
			.images > .album > table td figurecaption { display: none; }
		';
		
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
