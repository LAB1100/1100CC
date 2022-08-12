<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class projects extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_projects');
		static::$parent_label = getLabel('lbl_communication');
	}
	
	public static function moduleVariables() {
		
		$return = '<select>';
		for ($i = 0; $i <= 20; $i++) {
			$return .= '<option value="'.$i.'">'.$i.'</option>';
		}
		$return .= '</select>';
		
		return $return;
	}
	
	public function contents() {
	
		$arr_projects = cms_projects::getProjects(0, $this->arr_variables);
		
		$return = '';
		
		if ($arr_projects) {
			
			$return .= '<h1>Related</h1>';
			
			foreach ($arr_projects as $row) {
				$return .= '<div class="project"><a href="'.$row["url"].'" target="_blank"><img src="'.$row["img"].'" title="'.strEscapeHTML($row["description"]).'" alt="" /><span>'.strEscapeHTML($row["name"]).'</span></a></div>';
			}
		}
								
		return $return;
	}
	
	public static function css() {
	
		$return = '.projects .project {position: relative; margin: 10px 0px 0px 0px; width: 100%; height: 50px; line-height: 50px; overflow: hidden; background-color: #e1e1e1; }
				.projects h1 + .project {margin-top: 0px;}
				.projects .project a { display: block; text-decoration: none; }
				.projects .project a img {float: left;}
				.projects .project a span {color: #000000; margin: 0px 0px 0px 6px; font-size: 14px; font-weight: bold;}
				.projects .project a:hover {text-decoration: none;}
				.projects .project a:hover span {color: #666666;}
				.project > a img {display: block;}';
		
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
