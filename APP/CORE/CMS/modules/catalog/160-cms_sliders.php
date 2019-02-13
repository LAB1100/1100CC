<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_SLIDERS', DB::$database_home.'.def_sliders');
DB::setTable('TABLE_SLIDER_SLIDE_LINK', DB::$database_home.'.def_slider_slide_link');
DB::setTable('TABLE_SLIDER_SLIDES', DB::$database_home.'.def_slider_slides');

class cms_sliders extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_sliders');
		static::$parent_label = getLabel('ttl_site');
	}

	public function contents() {
		
		$return .= '<div class="section"><h1 id="x:cms_sliders:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup add" value="add" /></h1>
		<div class="sliders">';
		
			$return .= '<div id="tabs-sliders">
				<ul>
					<li><a href="#tab-sliders">'.getLabel("ttl_sliders").'</a></li>
					<li><a href="#tab-slides">'.getLabel("ttl_slides").'</a><span><input id="x:cms_sliders:new-0" type="button" class="data add popup add_slide" value="add" /></span></li>
				</ul>
				<div id="tab-sliders">
				
					'.self::contentTabSliders().'
				
				</div>
				<div id="tab-slides">
				
					'.self::contentTabSlides().'
				
				</div>
			</div>
		</div></div>';
		
		return $return;
	}
	
	private static function contentTabSliders() {
		
		$res = DB::query("SELECT
			sldr.*,
			(SELECT 
				COUNT(*)
					FROM ".DB::getTable('TABLE_SLIDER_SLIDE_LINK')." sldl
					LEFT JOIN ".DB::getTable('TABLE_SLIDER_SLIDES')." slds ON (slds.id = sldl.slider_slide_id)
				WHERE sldl.slider_id = sldr.id
			) AS count_slider_slides,
			(SELECT 
				".DBFunctions::sqlImplode("CASE
					WHEN sldl.media_internal_tag_id > 0 THEN CONCAT(t.name, ' (".getLabel('lbl_media').")')
					ELSE CONCAT(slds.name, ' (".getLabel('lbl_slide').")')
				END", '<br />', 'ORDER BY sldl.sort')."
					FROM ".DB::getTable('TABLE_SLIDER_SLIDE_LINK')." sldl
					LEFT JOIN ".DB::getTable('TABLE_SLIDER_SLIDES')." slds ON (slds.id = sldl.slider_slide_id)
					LEFT JOIN ".DB::getTable('TABLE_INTERNAL_TAGS')." t ON (t.id = sldl.media_internal_tag_id)
				WHERE sldl.slider_id = sldr.id
			) AS slider_slides,
			".DBFunctions::sqlImplode(DBFunctions::castAs('d.id', DBFunctions::CAST_TYPE_STRING), ',', 'ORDER BY p.id')." AS directories,
			".DBFunctions::sqlImplode('p.name', ',', 'ORDER BY p.id')." AS pages
				FROM ".DB::getTable('TABLE_SLIDERS')." sldr
				LEFT JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.var != '' AND ".DBFunctions::castAs('m.var', DBFunctions::CAST_TYPE_INTEGER)." = sldr.id AND m.module = 'slider')
				LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
				LEFT JOIN ".DB::getTable('TABLE_DIRECTORIES')." d ON (d.id = p.directory_id)
			GROUP BY sldr.id
		");
			
		if ($res->getRowCount() == 0) {
			
			$return .= '<p class="info">'.getLabel('msg_no_sliders').'</p>';
		} else {
		
			$return .= '<table class="list">
				<thead>
					<tr>
						<th class="max"><span>'.getLabel('lbl_name').'</span></th>
						<th><span>'.getLabel('lbl_path').'</span></th>
						<th><span>'.getLabel('lbl_slides').'</span></th>
						<th><span>'.getLabel('lbl_duration').'</span></th>
						<th class="limit"><span>'.getLabel('lbl_transition_effect').'</span></th>
						<th class="limit"><span>'.getLabel('lbl_transition_speed').'</span></th>
					</tr>
				</thead>
				<tbody>';
					while ($arr_row = $res->fetchAssoc()) {
						
						$arr_pages = explode(',', $arr_row['pages']);
						$arr_directories = array_filter(explode(',', $arr_row['directories']));
						$arr_paths = [];
						
						for ($i = 0; $i < count($arr_directories); $i++) {
							$arr_dir = directories::getDirectories($arr_directories[$i]);
							if ($arr_dir['id']) {
								$arr_paths[] = $arr_dir['path'].' / '.$arr_pages[$i];
							}
						}
						
						$arr_paths = array_unique($arr_paths);
						
						$return .= '<tr id="x:cms_sliders:slider_id-'.$arr_row['id'].'">
							<td>'.$arr_row['name'].'</td>
							<td><span class="info"><span class="icon" title="'.($arr_paths ? implode('<br />', $arr_paths) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.count($arr_paths).'</span></span></td>
							<td><span class="info"><span class="icon" title="'.htmlspecialchars($arr_row['slider_slides'] ?: getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)$arr_row['count_slider_slides'].'</span></span></td>
							<td>'.($arr_row['timeout']/1000).' '.getLabel('unit_seconds').'</td>
							<td>'.self::getSliderEffects($arr_row['effect']).'</td>
							<td>'.($arr_row['speed']/1000).' '.getLabel('unit_seconds').'</td>
							<td><input type="button" class="data edit popup edit" value="edit" /><input type="button" class="data del msg del" value="del" /></td>
						</tr>';
					}
				$return .= '</tbody>
			</table>';
		}
			
		return $return;
	}
	
	private static function contentTabSlides() {
					
		$return .= '<table id="d:cms_sliders:data_slides-0" class="display">
			<thead>
				<tr>
					<th class="max" data-sort="asc-0"><span>'.getlabel('lbl_name').'</span></th>
					<th><span>'.getlabel('lbl_sliders').'</span></th>
					<th class="disable-sort"></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>';

		return $return;
	}
		
	public static function css() {
	
		$return = '#frm-slide li.content > label { vertical-align: middle; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return .= "SCRIPTER.dynamic('#frm-slider', function(elm_scripter) {
		
			elm_scripter.on('click', '[id^=y\\\:cms_sliders\\\:get_type_option]', function() {
				
				var elm_sorter = elm_scripter.find('.sorter');
				
				if (!elm_sorter.children('li').length) {
					elm_sorter.empty();
				}
				
				$(this).quickCommand(elm_sorter, {html: 'append'});
			}).on('click', '.del', function() {
			
				elm_scripter.find('.sorter').sorter('clean');
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "edit" || $method == "add") {
		
			if ((int)$id) {				
				$arr_slider_set = self::getSliderSet($id);				
				$mode = "update";
			} else {
				$mode = "insert";
			}
			
			$arr_effects = self::getSliderEffects();
			
			$this->html = '<form id="frm-slider" data-method="'.$mode.'">						
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.htmlspecialchars($arr_slider_set['slider']['name']).'"></div>
					</li>
					<li>
						<label>'.getLabel('lbl_duration').'</label>
						<div><input type="range" min="0.5" max="10" step="0.5" /><input type="number" name="timeout" value="'.(((int)$arr_slider_set['slider']['timeout']/1000) ?: 2).'" /><label>'.getLabel('unit_seconds').'</label></div>
					</li>
					<li>
						<label>'.getLabel('lbl_transition_effect').'</label>
						<div><select name="effect">'.cms_general::createDropdown($arr_effects, $arr_slider_set['slider']['effect']).'</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_transition_speed').'</label>
						<div><input type="range" min="0.2" max="4" step="0.2" /><input type="number" name="speed" value="'.(((int)$arr_slider_set['slider']['speed']/1000) ?: 1).'" /><label>'.getLabel('unit_seconds').'</label></div>						
					</li>
					<li>
						<label></label>
						<div><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input id="y:cms_sliders:get_type_option-slide" type="button" class="data add" value="'.getLabel('lbl_slide').'" /><input id="y:cms_sliders:get_type_option-media" type="button" class="data add" value="'.getLabel("lbl_media").'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_slides').'</label>
						<div><ul class="sorter">';
							
							if ($mode == 'update' && $arr_slider_set['slides']) {
								foreach ($arr_slider_set['slides'] as $value) {
									$this->html .= self::createTypeOption($value);
								}
							} else {
								for ($i = 0; $i < 3; $i++) {
									$this->html .= self::createTypeOption([], 'slide');
								}
							}
						
						$this->html .= '</ul></div>
					</li>
				</ul></fieldset>							
			</form>';
			
			$this->validate = '{"name": "required"}';
		}
		
		if ($method == "edit_slide" || $method == "add_slide") {
			
			if ($method == "edit_slide" && (int)$id) {
			
				$arr = self::getSlides($id);

				$mode = "update_slide";
			} else {
				$mode = "insert_slide";
			}
					
			$this->html = '<form id="frm-slide" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.htmlspecialchars($arr['name']).'"></div>
					</li>
					<li class="content">
						<label>'.getLabel('lbl_content').'</label>
						<div>'.cms_general::editBody($arr['body']).'</div>
					</li>			
				</ul></fieldset>
			</form>';
			
			$this->validate = '{"name": "required"}';
		}
		
		// POPUP INTERACT
		
		if ($method == "get_type_option") {

			$this->html = self::createTypeOption([], $id);
		}
		
		// DATATABLE
					
		if ($method == "data_slides") {
			
			$arr_sql_columns = ['slds.name', 'COUNT(sldr.id)'];
			$arr_sql_columns_search = ['slds.name', 'sldr.name'];
			$arr_sql_columns_as = ['slds.name', DBFunctions::sqlImplode('sldr.name', '<br />', 'ORDER BY sldr.id DESC').' AS sliders', 'COUNT(sldr.id) AS count_sliders', 'slds.id'];
			
			$sql_table = DB::getTable('TABLE_SLIDER_SLIDES')." slds
				LEFT JOIN ".DB::getTable('TABLE_SLIDER_SLIDE_LINK')." sldl ON (sldl.slider_slide_id = slds.id)
				LEFT JOIN ".DB::getTable('TABLE_SLIDERS')." sldr ON (sldr.id = sldl.slider_id)
			";

			$sql_index = 'slds.id';
							 
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index);

			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{

				$arr_data = [];
				
				$arr_data['id'] = 'x:cms_sliders:slide-'.$arr_row['id'];
				
				$arr_data[] = $arr_row['name'];
				$arr_data[] = '<span class="info"><span class="icon" title="'.htmlspecialchars($arr_row['sliders']).'">'.getIcon('info').'</span><span>'.$arr_row['count_sliders'].'</span></span>';
				$arr_data[] = '<input type="button" class="data edit popup edit_slide" value="edit" /><input type="button" class="data del msg del_slide" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
							
		// QUERY
	
		if ($method == "insert") {
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_SLIDERS')."
				(name, timeout, speed, effect)
					VALUES
				('".DBFunctions::strEscape($_POST['name'])."', ".(int)((float)$_POST['timeout']*1000).", ".(int)((float)$_POST['speed']*1000).", '".DBFunctions::strEscape($_POST['effect'])."')
			");
			
			$slider_id = DB::lastInsertID();
		}
		
		if ($method == "update" && (int)$id) {
					
			$res = DB::query("UPDATE ".DB::getTable('TABLE_SLIDERS')." SET
				name = '".DBFunctions::strEscape($_POST['name'])."',
				timeout = ".(int)((float)$_POST['timeout']*1000).",
				speed = ".(int)((float)$_POST['speed']*1000).",
				effect = '".DBFunctions::strEscape($_POST['effect'])."'
					WHERE id = ".(int)$id."
			");
										
			$res = DB::query("DELETE FROM ".DB::getTable('TABLE_SLIDER_SLIDE_LINK')." WHERE slider_id = ".(int)$id."");	

			$slider_id = $id;
		}
		
		if ($method == "insert" || ($method == "update" && (int)$id)) {
			
			if ($_POST['slide']) {
				
				$sort = 0;
				
				foreach ($_POST['slide'] as $key => $value) {
					
					if (!$value) {
						continue;
					}
										
					$sql_value = ($_POST['slide_type'][$key] == 'media' ? '0, '.(int)$value : (int)$value.', 0');
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_SLIDER_SLIDE_LINK')."
						(slider_id, slider_slide_id, media_internal_tag_id, sort)
							VALUES
						(".(int)$slider_id.", ".$sql_value.", ".$sort.")
					");
					
					$sort++;
				}
			}
			
			$this->html = self::contentTabSliders();
			$this->msg = true;
		}

		if ($method == "del" && (int)$id) {
		
			$res = DB::query("DELETE sldr.*, sldl.*
					FROM ".DB::getTable('TABLE_SLIDERS')." sldr
					LEFT JOIN ".DB::getTable('TABLE_SLIDER_SLIDE_LINK')." sldl ON (sldl.slider_id = sldr.id)
				WHERE sldr.id = ".(int)$id."
			");
			
			$this->msg = true;
		}
			
		if($method == "insert_slide") {
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_SLIDER_SLIDES')." (name, body)
											VALUES ('".DBFunctions::strEscape($_POST['name'])."', '".DBFunctions::strEscape($_POST['body'])."')");
				
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if($method == "update_slide" && (int)$id) {

			$res = DB::query("UPDATE ".DB::getTable('TABLE_SLIDER_SLIDES')." SET
					name = '".DBFunctions::strEscape($_POST['name'])."',
					body = '".DBFunctions::strEscape($_POST['body'])."'
				WHERE id = ".(int)$id."
			");
			
			$this->refresh_table = true;
			$this->msg = true;
		}

		if ($method == "del_slide" && (int)$id) {
		
			$res = DB::query("DELETE slds.*, sldl.*
					FROM ".DB::getTable('TABLE_SLIDER_SLIDES')." slds
					LEFT JOIN ".DB::getTable('TABLE_SLIDER_SLIDE_LINK')." sldl ON (sldl.slider_slide_id = slds.id)
				WHERE slds.id = ".(int)$id."
			");
			
			$this->msg = true;
		}
	}
	
	private static function getSliderEffects($effect = false) {
		
		$arr = [];
		$arr['scrollHorz'] = ['name' => getLabel('lbl_scroll_horizontal'), 'id' => 'scrollHorz'];
		$arr['scrollVert'] = ['name' => getLabel('lbl_scroll_vertical'), 'id' => 'scrollVert'];
		$arr['fade'] = ['name' => getLabel('lbl_fade'), 'id' => 'fade'];
		$arr['none'] = ['name' => getLabel('lbl_none'), 'id' => 'none'];
		
		return ($effect ? $arr[$effect]['name'] : $arr);
	}
	
	private static function createTypeOption($value = [], $type = false) {
		
		if ($type == "media" || $value["media_internal_tag_id"]) {
			
			$arr_tags = cms_media::getMediaTags();
			$return = '<li><span><span class="icon">'.getIcon('updown').'</span></span><div><select name="slide[]">'.cms_general::createDropdown($arr_tags, $value['media_internal_tag_id'], true).'</select><input type="hidden" name="slide_type[]" value="media" /><label>'.getLabel("lbl_media").'</label></div></li>';
		
		} else {
			
			$arr_slides = self::getSlides();
			$return = '<li><span><span class="icon">'.getIcon('updown').'</span></span><div><select name="slide[]">'.cms_general::createDropdown($arr_slides, $value['slider_slide_id'], true).'</select><input type="hidden" name="slide_type[]" value="slide" /><label>'.getLabel("lbl_slide").'</label></div></li>';
		}
		
		return $return;
	}
	
	public static function getSliders($slider = 0) {	
		
		$arr = [];

		$res = DB::query("SELECT sldr.*
				FROM ".DB::getTable("TABLE_SLIDERS")." sldr
			".($slider ? "WHERE sldr.id = ".(int)$slider."" : "")."");

		while($row = $res->fetchAssoc()) {
			$arr[] = $row;
		}

		return ((int)$slider ? current($arr) : $arr);
	}
	
	public static function getSlides($slide = 0) {
	
		$arr = [];
		
		$res = DB::query("SELECT slds.*
								FROM ".DB::getTable("TABLE_SLIDER_SLIDES")." AS slds
								".((int)$slide ? "WHERE slds.id = ".$slide."" : "")."
		");
								
		while($row = $res->fetchAssoc()) {
			$arr[] = $row;
		}		

		return ((int)$slide ? current($arr) : $arr);
	}
		
	public static function getSliderSet($slider) {	
	
		$arr = [];
		
		$res = DB::query("SELECT
			sldr.*, slds.id AS slide_id, slds.name AS slide_name, slds.body, sldl.slider_slide_id, sldl.media_internal_tag_id
				FROM ".DB::getTable("TABLE_SLIDERS")." sldr
				LEFT JOIN ".DB::getTable("TABLE_SLIDER_SLIDE_LINK")." sldl ON (sldl.slider_id = sldr.id)
				LEFT JOIN ".DB::getTable("TABLE_SLIDER_SLIDES")." slds ON (slds.id = sldl.slider_slide_id)
			WHERE sldr.id = ".(int)$slider."
			ORDER BY sldl.sort
		");
					
		while($row = $res->fetchAssoc()) {
			
			$arr['slider'] = $row;
			$arr['slides'][] = $row;
		}
		
		return $arr;
	}
}
