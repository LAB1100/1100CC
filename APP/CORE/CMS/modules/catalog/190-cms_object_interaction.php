<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('DEF_OBJECT_INTERACTION_OBJECTS', DB::$database_home.'.def_object_interaction_objects');
DB::setTable('DEF_OBJECT_INTERACTION_STAGES', DB::$database_home.'.def_object_interaction_stages');
DB::setTable('DEF_OBJECT_INTERACTION_OBJECT_LINK', DB::$database_home.'.def_object_interaction_object_link');

Settings::set('object_interaction_stage_tiles_path', DIR_SITE_STORAGE.DIR_UPLOAD.'object_interaction/');
Settings::set('arr_object_interaction_stage_object_classes', [['id' => 'example', 'label' => 'example']]);

class cms_object_interaction extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_object_interaction');
		static::$parent_label = getLabel('ttl_site');
	}
	
	public function contents() {
		
		$return = '<div class="section"><h1><span>'.self::$label.'</span><input type="button" class="data add popup" id="y:cms_object_interaction:add_stage-0" value="add" /></h1>
		<div class="object_interaction">';
		
		$return .= '<div class="tabs">
				<ul>
					<li><a href="#tab-stages">'.getLabel('lbl_object_interaction_stages').'</a></li>
					<li id="y:cms_object_interaction:add_object-0"><a href="#tab-objects">'.getLabel("lbl_object_interaction_objects").'</a><input type="button" class="data add popup" value="add" /></li>
				</ul>
				<div id="tab-stages">

					'.self::contentTabStages().'
				
				</div><div id="tab-objects">
				
					'.self::contentTabObjects().'
										
				</div>					
			</div></div>';
		
		return $return;
	}
	
	private static function contentTabStages() {
		
		$res = DB::query("SELECT oi_s.*,
								sub_oi_s.count_stage_objects,
								sub_oi_s.stage_objects,
								".DBFunctions::group2String(DBFunctions::castAs('d.id', DBFunctions::CAST_TYPE_STRING), ',', 'ORDER BY p.id')." AS directories,
								".DBFunctions::group2String('p.name', ',', 'ORDER BY p.id')." AS pages
						FROM ".DB::getTable('DEF_OBJECT_INTERACTION_STAGES')." oi_s
						LEFT JOIN (SELECT
							oi_ol.object_interaction_stage_id,
							COUNT(oi_o.id) AS count_stage_objects,
							".DBFunctions::group2String("oi_o.name", '<br />', "ORDER BY oi_o.name")." AS stage_objects
								FROM ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECT_LINK")." oi_ol
								LEFT JOIN ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECTS")." oi_o ON (oi_o.id = oi_ol.object_interaction_object_id)
							GROUP BY oi_ol.object_interaction_stage_id) sub_oi_s ON (sub_oi_s.object_interaction_stage_id = oi_s.id)
						LEFT JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.var = oi_s.id AND m.module = 'object_interaction')
						LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
						LEFT JOIN ".DB::getTable('TABLE_DIRECTORIES')." d ON (d.id = p.directory_id)
						GROUP BY oi_s.id");
			
		if ($res->getRowCount() == 0) {
			$return .= '<p class="info">'.getLabel("msg_no_object_interaction_stages").'</p>';
		} else {
			
			$return .= '<table class="datatable display">
				<thead>
					<tr>
						<th class="max" data-sort="asc-0">'.getlabel("lbl_name").'</th>
						<th>'.getlabel("lbl_path").'</th>
						<th>'.getlabel("lbl_object_interaction_objects").'</th>
						<th class="disable-sort"></th>
					</tr>
				</thead>
				<tbody>';
					while ($row = $res->fetchAssoc()) {
						$pages = explode(",", $row['pages']);
						$directories = array_filter(explode(",", $row['directories']));
						$paths = [];
						for ($i = 0; $i < count($directories); $i++) {
							$dir = directories::getDirectories($directories[$i]);
							if ($dir["id"]) {
								$paths[] = $dir["path"]." / ".$pages[$i];
							}
						}
						$paths = array_unique($paths);
						$return .= '<tr id="x:cms_object_interaction:stage_id-'.$row['id'].'">
							<td>'.$row['name'].'</td>
							<td><span class="info"><span class="icon" title="'.($paths ? implode("<br />", $paths) : getLabel("inf_none")).'">'.getIcon('info').'</span><span>'.count($paths).'</span></span></td>
							<td><span class="info"><span class="icon" title="'.strEscapeHTML($row['stage_objects'] ?: getLabel("inf_none")).'">'.getIcon('info').'</span><span>'.(int)$row['count_stage_objects'].'</span></span></td>
							<td><input type="button" class="data edit popup edit_stage" value="edit" /><input type="button" class="data del msg del_stage" value="del" /></td>
						</tr>';
					}
				$return .= '</tbody>
			</table>';
		}
			
		return $return;
	}
	
	private static function contentTabObjects() {
		
		$res = DB::query("SELECT oi_o.*,
							sub_oi_o.stages,
							sub_oi_o.count_stages
						FROM ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECTS")." oi_o
						LEFT JOIN (SELECT
							DISTINCT oi_ol.object_interaction_object_id,
							".DBFunctions::group2String("oi_s.name", '<br />', "ORDER BY oi_s.name")." AS stages,
							COUNT(oi_s.id) AS count_stages
								FROM ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECT_LINK")." oi_ol
								LEFT JOIN ".DB::getTable("DEF_OBJECT_INTERACTION_STAGES")." oi_s ON (oi_s.id = oi_ol.object_interaction_stage_id)
							GROUP BY oi_ol.object_interaction_object_id) sub_oi_o ON ((sub_oi_o.object_interaction_object_id = oi_o.id))
						GROUP BY oi_o.id");
			
		if ($res->getRowCount() == 0) {
			$return .= '<p class="info">'.getLabel("msg_no_object_interaction_objects").'</p>';
		} else {
		
			$return .= '<table class="datatable display">
				<thead>
					<tr>
						<th class="max" data-sort="asc-0">'.getlabel("lbl_name").'</th>
						<th>'.getLabel('lbl_object_interaction_stages').'</th>
						<th class="disable-sort"></th>
					</tr>
				</thead>
				<tbody>';
					while ($row = $res->fetchAssoc()) {
						$return .= '<tr id="x:cms_object_interaction:object_id-'.$row['id'].'">
							<td>'.$row['name'].'</td>
							<td><span class="info"><span class="icon" title="'.strEscapeHTML($row['stages']).'">'.getIcon('info').'</span><span>'.(int)$row['count_stages'].'</span></span></td>
							<td><input type="button" class="data edit popup edit_object" value="edit" /><input type="button" class="data del msg del_object" value="del" /></td>
						</tr>';
					}
				$return .= '</tbody>
			</table>';
		}
			
		return $return;
	}
	
	private static function createStageObject($arr_stage_object, $arr_object) {
		
		$str_json = value2JSON($arr_stage_object);
		
		if ($arr_object['img']) {
			$value_hotspot = '<img class="hotspot" src="'.$arr_object['img'].'" title="'.$arr_object['name'].'" />';
		} else {
			$value_hotspot = '<div class="hotspot'.($arr_object['shape'] ? ' '.$arr_object['shape'] : '').'" title="'.$arr_object['name'].'"></div>';
		}
		
		return '<div class="object" id="x:cms_object_interaction:stage_object-0">
			<div class="handle"></div>
			<input type="hidden" value="'.strEscapeHTML($str_json).'" name="objects[details][]" />
			<input type="hidden" value="'.$arr_object['width'].'" name="objects[width][]" />
			<input type="hidden" value="'.($arr_object['pos_x'] ? $arr_object['pos_x'].'_'.$arr_object['pos_y'] : '').'" name="objects[pos][]" />
			'.$value_hotspot.'
			<div class="object-info"><input type="button" class="data del msg del_stage_object" value="del" /><input type="button" class="data edit popup edit_stage_object" value="edit" /></div>
		</div>';
	}
			
	public static function css() {
	
		$return = '#frm-object-interaction-stage img.select { max-width: 50px; max-height: 50px; }
				#frm-object-interaction-stage .stage { position: relative; display: none; }
				#frm-object-interaction-stage .stage > img { max-width: 1000px; }
				#frm-object-interaction-stage .stage > div[id] { position: absolute; top: 0px; left: 0px; width: 100%; height: 100%; }
				#frm-object-interaction-stage .stage > .point { position: absolute; top: 0px; left: 0px; display: block; cursor: pointer; width: 10px; height: 10px; background-color: #009604; -webkit-border-radius: 50%; -moz-border-radius: 50%; border-radius: 50%; }
				#frm-object-interaction-stage .stage > div > .object { position: absolute; border: 1px solid #000000; padding: 5px; padding-top: 16px; padding-bottom: 47px; width: 140px; height: 100px; background-color: transparent; box-sizing: content-box; }
				#frm-object-interaction-stage .stage > div > .object > .hotspot { max-width: 100%; max-height: 100%; margin: 0px auto; display: block; }
				#frm-object-interaction-stage .stage > div > .object > div.hotspot { background-color: #000000; opacity: 0.1; height: 100%; margin: 0px 14%; } /* margin left and right relative to width and height of .object */
				#frm-object-interaction-stage .stage > div > .object > div.hotspot.circle,
				#frm-object-interaction-stage .stage > div > .object > div.hotspot.square { opacity: 0.4; }
				#frm-object-interaction-stage .stage > div > .object > div.hotspot.circle { -webkit-border-radius: 50%; -moz-border-radius: 50%; border-radius: 50%;  }
				#frm-object-interaction-stage .stage > div > .object > .object-info { left: 6px; bottom: 15px; right: auto; white-space: nowrap;}
				#frm-object-interaction-stage .script textarea {width: 100%; height: 400px;}
				#frm-object-interaction-object {  }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "$(document).on('documentloaded ajaxloaded', function() {
		
					var target_stage = $('#frm-object-interaction-stage').find('.stage');
					
					/*
					target_stage.find('.object')
						.resizable({
							aspectRatio: true,
							stop: function() {
								$(this).trigger('update');
							}
						})
						.draggable({
							handle: '.handle',
							stop: function() {
								$(this).trigger('update');
							}
						});
					target_stage.children('.point')
						.draggable({
							containment: 'parent',
							stop: function() {
								var cur = $(this);
								var elm_stage = cur.closest('.stage');
								elm_stage.children('input[name=view]').val((((cur.position().left+(cur.width()/2))/elm_stage.width())*100).toFixed(4)+'_'+(((cur.position().top+(cur.height()/2))/elm_stage.height())*100).toFixed(4));
							}
						});

					target_stage.show();
					*/
				}).on('click', '#frm-object-interaction-stage .object .handle', function() {
					$(this).closest('.object').appendTo($(this).closest('#frm-object-interaction-stage .stage > div'));
				}).on('change', '#frm-object-interaction-stage [name=img]', function() {
					var target = $('#frm-object-interaction-stage').find('.stage > img');
					target.attr('src', target.attr('data-prefix')+$(this).val().replace(/^\//, ''));
				}).on('click', '[id^=y\\\:cms_object_interaction\\\:add_stage_object-]', function(e) {
					if (e.target == this) {
						$(this).data('target', function(html) {
							var elm_new = $(html);
							var parent = $(this);
							elm_new.appendTo(parent)
								.css({top: Math.floor((((e.pageY - parent.offset().top)-(elm_new.outerHeight()/2))/parent.height())*100)+'%', left: Math.floor((((e.pageX - parent.offset().left)-(elm_new.outerWidth()/2))/parent.width())*100)+'%'})
								.trigger('update');
						}).popupCommand();
					}
				}).on('mousedown', '[id=x\\\:cms_object_interaction\\\:stage_object-0] .edit_stage_object', function(e) {
					var target = $(this).closest('.object');
					$(this).closest('[id=x\\\:cms_object_interaction\\\:stage_object-0]').data({value: target.find('[name^=\"objects[details]\"]').val(), target: function(html) {
						var elm_new = $(html);
						target.find('[name^=\"objects[details]\"]').replaceWith(elm_new.find('[name^=\"objects[details]\"]'));
						target.find('.hotspot').replaceWith(elm_new.find('.hotspot'));
						target.trigger('update');
					}});
				}).on('mouseup', '[id=x\\\:cms_object_interaction\\\:stage_object-0] .edit_stage_object', function(e) {
					$(this).popupCommand();
				}).on('update', '#frm-object-interaction-stage .stage > div > .object', function(e) {
					var cur = $(this);
					var elm_stage = cur.closest('.stage');
					var elm_hotspot = cur.find('.hotspot');

					cur.find('[name^=\"objects[width]\"]').val(((elm_hotspot.width()/elm_stage.width())*100).toFixed(4));
					cur.find('[name^=\"objects[pos]\"]').val((((cur.position().left+elm_hotspot.position().left+parseFloat(elm_hotspot.css('margin-left')))/elm_stage.width())*100).toFixed(4)+'_'+(((cur.position().top+elm_hotspot.position().top+parseFloat(elm_hotspot.css('margin-top')))/elm_stage.height())*100).toFixed(4));
				}).on('ajaxloaded', function(e) {
				
					if (!getElement(e.detail.elm)) {
						return;
					}
				
					if (e.detail.elm.children('form').is('#frm-object-interaction-stage')) {
						
						var elm_stage = e.detail.elm.find('.stage');
						
						new ImagesLoaded(elm_stage, function() {
						
							elm_stage.find('.object').each(function() {
								var cur = $(this);
								var elm_hotspot = cur.find('.hotspot');
								
								var width = (parseFloat(cur.find('[name^=\"objects[width]\"]').val())/100)*elm_stage.width();
								var ratio = width/elm_hotspot.width();
								cur.width(cur.width()*ratio).height(cur.height()*ratio);
								
								var pos_xy = cur.find('[name^=\"objects[pos]\"]').val().split('_');
								var pos_x = ((parseFloat(pos_xy[0])/100)*elm_stage.width())-elm_hotspot.position().left-parseFloat(elm_hotspot.css('margin-left'));
								var pos_y = ((parseFloat(pos_xy[1])/100)*elm_stage.height())-elm_hotspot.position().top-parseFloat(elm_hotspot.css('margin-top'));
								cur.css({left: pos_x, top: pos_y});
							});
							var elm_point = elm_stage.children('.point');
							var pos_xy = elm_stage.children('[name=view]').val().split('_');
							var pos_x = ((parseFloat(pos_xy[0])/100)*elm_stage.width())-(elm_point.width()/2);
							var pos_y = ((parseFloat(pos_xy[1])/100)*elm_stage.height())-(elm_point.height()/2);
							elm_point.css({left: pos_x, top: pos_y});
						});
					}
				});
				
				$(document).on('ajaxloaded', function() {
					$('#frm-object-interaction-object [name=hotspot]').trigger('change');
				}).on('change', '#frm-object-interaction-object [name=hotspot]', function() {
					var value = $(this).siblings('[name=hotspot]').addBack().filter(':checked').val();
					var target = $(this).closest('table');
					if (value == 'image') {
						target.find('[name=img]').closest('tr').show();
						target.find('[name=shape]').closest('tr').hide();
					} else if (value == 'shape') {
						target.find('[name=shape]').closest('tr').show();
						target.find('[name=img]').closest('tr').hide();
					} else {
						target.find('[name=img], [name=shape]').closest('tr').hide();
					}
				});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
						
		if ($method == "edit_stage" || $method == "add_stage") {

			if ((int)$id) {
				
				$arr = self::getStageSet($id);
				
				$str_objects = '';
				foreach ((array)$arr['objects'] as $arr_object) {
					$str_objects .= self::createStageObject(
						[
							'object_id' => $arr_object['object_id'],
							'effect' => $arr_object['object_effect'],
							'effect_hover' => $arr_object['object_effect_hover'],
							'script' => $arr_object['object_script'],
							'script_hover' => $arr_object['object_script_hover'],
							'style' => $arr_object['object_style'],
							'style_hover' => $arr_object['object_style_hover']
						], [
							'name' => $arr_object['object_name'],
							'img' => $arr_object['object_img'],
							'shape' => $arr_object['object_shape'],
							'class' => $arr_object['object_class'],
							'body' => $arr_object['object_body'],
							'redirect_page_id' => $arr_object['object_redirect_page_id'],
							'width' => $arr_object['object_width'],
							'pos_x' => $arr_object['object_pos_x'],
							'pos_y' => $arr_object['object_pos_y']
						]
					);
				}
												
				$mode = "update_stage";
			} else {
									
				$mode = "insert_stage";
			}
						
			$this->html = '<form id="frm-object-interaction-stage" class="'.$mode.'">
				<table>
					<tr>
						<td>'.getLabel("lbl_name").'</td>
						<td><input type="text" name="name" value="'.strEscapeHTML($arr['stage']['name']).'"></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_description").'</td>
						<td><textarea name="description">'.strEscapeHTML($arr['stage']['description']).'</textarea></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_image").'</td>
						<td>'.cms_general::createImageSelector($arr['stage']["img"]).'</td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_object_interaction_stage").'</td>
						<td><div class="stage">
							<img src="'.SiteStartEnvironment::getCacheURL('img', [1000, 0], $arr['stage']["img"]).'" data-prefix="'.SiteStartEnvironment::getCacheURL('img', [1000, 0], '').'" />
							<div id="y:cms_object_interaction:add_stage_object-'.(int)$id.'">'.$str_objects.'</div>
							<div class="point" title="'.getLabel('inf_view_position_mark').'"></div>
							<input type="hidden" name="view" value="'.$arr['stage']["view_x"].'_'.$arr['stage']["view_y"].'" />
						</div></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_zoom_minimum").'</td>
						<td><input type="range" min="1" max="100" step="1" /><input type="number" name="zoom_min" value="'.($arr['stage']["zoom_min"] ?: 50).'" /><label>%</label></td>						
					</tr>
					<tr>
						<td>'.getLabel("lbl_zoom_maximum").'</td>
						<td><input type="range" min="1" max="100" step="1" /><input type="number" name="zoom_max" value="'.($arr['stage']["zoom_max"] ?: 100).'" /><label>%</label></td>						
					</tr>
					<tr>
						<td>'.getLabel("lbl_zoom_levels").'</td>
						<td><input type="range" min="1" max="10" step="1" /><input type="number" name="zoom_levels" value="'.($arr['stage']["zoom_levels"] ?: 4).'" /></td>						
					</tr>
					<tr>
						<td>'.getLabel("lbl_zoom_default").'</td>
						<td><input type="range" min="1" max="10" step="1" /><input type="number" name="zoom_level_default" value="'.($arr['stage']["zoom_level_default"] ?: 4).'" />
							<label title="'.getLabel('inf_height_full').'"><input type="checkbox" name="height_full" value="1"'.($arr['stage']["height_full"] ? ' checked="checked"' : '').' />'.getLabel('lbl_height_full').'</label>
							<label title="'.getLabel('inf_zoom_auto').'"><input type="checkbox" name="zoom_auto" value="1"'.($arr['stage']["zoom_auto"] ? ' checked="checked"' : '').' />'.getLabel('lbl_zoom_auto').'</label>
						</td>						
					</tr>
					<tr>
						<td>'.getLabel("lbl_script").' <span class="icon" title="'.getLabel("inf_object_interaction_script").'">'.getIcon('info').'</span></td>
						<td><div class="hide-edit'.(!$arr['stage']['script'] ? ' hide' : '').'"><textarea name="script">'.$arr['stage']['script'].'</textarea></div><span class="icon" title="'.getLabel('inf_edit_script').'">'.getIcon('script').'</span></td>
					</tr>
				</table>
				</form>';
			
			$this->validate = ['name' => 'required', 'img' => 'required'];
		}
		
		if ($method == "add_object" || $method == "edit_object") {
		
			if ((int)$id) {
				
				$row = self::getObjects($id);
												
				$mode = "update_object";
			} else {
									
				$mode = "insert_object";
			}
				
			$this->html = '<form id="frm-object-interaction-object" class="'.$mode.'">
				<table>
					<tr>
						<td>'.getLabel("lbl_name").'</td>
						<td><input type="text" name="name" value="'.strEscapeHTML($row["name"]).'"></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_hotspot").'</td>
						<td><span class="radios">'.cms_general::createSelectorRadio([["id" => "none", "label" => getLabel("lbl_none")], ["id" => "image", "label" => getLabel("lbl_image")], ["id" => "shape", "label" => getLabel("lbl_shape")]], 'hotspot', ($row["img"] ? "image" : ($row["shape"] ? 'shape' : 'none')), 'label').'</span></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_shape").'</td>
						<td><select name="shape">'.cms_general::createDropdown(self::getObjectShapes(), $row["shape"], false, 'label').'</select></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_image").'</td>
						<td><input type="hidden" name="img" id="y:cms_media:media_popup-0" value="'.$row["img"].'" /><img class="select" src="'.$row["img"].'" /></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_class").'</td>
						<td><select name="class">'.cms_general::createDropdown(Settings::get('arr_object_interaction_stage_object_classes'), $row["class"], true, 'label').'</select></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_redirect_page").'</td>
						<td><select name="redirect_page_id">'.cms_general::createDropdown(pages::getPageNameList(pages::getPages()), $row['redirect_page_id'], true).'</select></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_content").'</td>
						<td>'.cms_general::editBody($row['body']).'</td>
					</tr>
				</table>
				</form>';
				
			$this->validate = ['name' => 'required'];
		}
		
		// POPUP INTERACT
		
		if ($method == "add_stage_object" || $method == "edit_stage_object") {
						
			if ($method == "edit_stage_object") {
				
				$arr = json_decode($value, true);
			}

			$this->html = '<form id="frm-object-interaction-stage-object" class="return_stage_object">
				<table>
					<tr>
						<td>'.getLabel("lbl_object_interaction_object").'</td>
						<td><select name="object[object_id]">'.cms_general::createDropdown(self::getObjects(), $arr['object_id']).'</select></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_effect_default").'</td>
						<td><select name="object[effect][]" multiple="multiple">'.cms_general::createDropdown(self::getObjectEffects(), $arr['effect'], true, 'label').'</select></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_script").'</td>
						<td><div class="hide-edit'.(!$arr['script'] ? ' hide' : '').'"><textarea name="object[script]">'.$arr['script'].'</textarea></div><span class="icon" title="'.getLabel('inf_edit_script').'">'.getIcon('script').'</span></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_style").'</td>
						<td><div class="hide-edit'.(!$arr['style'] ? ' hide' : '').'"><textarea name="object[style]">'.$arr['style'].'</textarea></div><span class="icon" title="'.getLabel('inf_edit_style').'">'.getIcon('equalizer').'</span></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_effect_hover").'</td>
						<td><select name="object[effect_hover][]" multiple="multiple">'.cms_general::createDropdown(self::getObjectEffects(true), $arr['effect_hover'], true, 'label').'</select></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_script_hover").'</td>
						<td><div class="hide-edit'.(!$arr['script_hover'] ? ' hide' : '').'"><textarea name="object[script_hover]">'.$arr['script_hover'].'</textarea></div><span class="icon" title="'.getLabel('inf_edit_script').'">'.getIcon('script').'</span></td>
					</tr>
					<tr>
						<td>'.getLabel("lbl_style_hover").'</td>
						<td><div class="hide-edit'.(!$arr['style_hover'] ? ' hide' : '').'"><textarea name="object[style_hover]">'.$arr['style_hover'].'</textarea></div><span class="icon" title="'.getLabel('inf_edit_style').'">'.getIcon('equalizer').'</span></td>
					</tr>
				</table>
				</form>';
		}
							
		// QUERY
		
		if ($method == "return_stage_object") {
			
			$arr_object = self::getObjects($_POST['object']['object_id']);
			
			$this->html = self::createStageObject($_POST['object'], [
				'name' => $arr_object['name'],
				'img' => $arr_object['img'],
				'shape' => $arr_object['shape'],
				'class' => $arr_object['class']]
			);

		}
		
		if ($method == "insert_stage") {
			
			$arr_view_xy = explode('_', $_POST['view']);
						
			$res = DB::query("INSERT INTO ".DB::getTable("DEF_OBJECT_INTERACTION_STAGES")." (name, description, img, view_x, view_y, height_full, zoom_auto, zoom_min, zoom_max, zoom_levels, zoom_level_default) VALUES (
				'".DBFunctions::strEscape($_POST["name"])."',
				'".DBFunctions::strEscape($_POST["description"])."',
				'".DBFunctions::strEscape($_POST["img"])."',
				".(float)$arr_view_xy[0].",
				".(float)$arr_view_xy[1].",
				".(int)$_POST['height_full'].",
				".(int)$_POST['zoom_auto'].",
				".(int)$_POST['zoom_min'].",
				".(int)$_POST['zoom_max'].",
				".(int)$_POST['zoom_levels'].",
				".(int)$_POST['zoom_level_default']."
			)");
			$stage_id = DB::lastInsertID();
			
			self::handleStageObjects($stage_id, $_POST['objects']);
			self::handleStageLevels($stage_id);
						
			$this->html = self::contentTabStages();
			$this->msg = true;
		}
		
		if ($method == "update_stage" && (int)$id) {
			
			$arr = self::getStageSet($id);
			
			$arr_view_xy = explode('_', $_POST['view']);
											
			$res = DB::query("UPDATE ".DB::getTable("DEF_OBJECT_INTERACTION_STAGES")." SET
					name = '".DBFunctions::strEscape($_POST["name"])."',
					description = '".DBFunctions::strEscape($_POST["description"])."',
					img = '".DBFunctions::strEscape($_POST["img"])."',
					view_x = ".(float)$arr_view_xy[0].",
					view_y = ".(float)$arr_view_xy[1].",
					height_full = ".(int)$_POST['height_full'].",
					zoom_auto = ".(int)$_POST['zoom_auto'].",
					zoom_min = ".(int)$_POST['zoom_min'].",
					zoom_max = ".(int)$_POST['zoom_max'].",
					zoom_levels = ".(int)$_POST['zoom_levels'].",
					zoom_level_default = ".(int)$_POST['zoom_level_default'].",
					script = '".DBFunctions::strEscape($_POST["script"])."'
				WHERE id = ".$id."");
				
			self::handleStageObjects($id, $_POST['objects']);
			
			if ($_POST["img"] != $arr['stage']['img'] || $_POST['zoom_min'] != $arr['stage']['zoom_min'] || $_POST['zoom_max'] != $arr['stage']['zoom_max'] || $_POST['zoom_levels'] != $arr['stage']['zoom_levels']) {
				self::handleStageLevels($id);
			}
				
			$this->html = self::contentTabStages();
			$this->msg = true;
		}
		
		if ($method == "del_stage" && (int)$id) {
		
			$res = DB::query("DELETE oi_s, oi_ol FROM ".DB::getTable("DEF_OBJECT_INTERACTION_STAGES")." oi_s
								LEFT JOIN ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECT_LINK")." oi_ol ON (oi_ol.object_interaction_stage_id = oi_s.id)
									WHERE oi_s.id=".(int)$id."");
									
			$path = Settings::get('object_interaction_stage_tiles_path').'stage_'.$id.'/';

			if (is_dir($path)) {
				FileStore::deleteDirectoryTree($path);
			}
		
			$this->msg = true;
		}
		
							
		if ($method == "insert_object") {
			
			if ($_POST['hotspot'] == 'image') {
				$value_img = DBFunctions::strEscape($_POST["img"]);
			} else if ($_POST['hotspot'] == 'shape') {
				$value_shape = DBFunctions::strEscape($_POST["shape"]);
			}
						
			$res = DB::query("INSERT INTO ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECTS")." (name, img, shape, class, redirect_page_id, body) VALUES ('".DBFunctions::strEscape($_POST["name"])."', '".$value_img."', '".$value_shape."', '".DBFunctions::strEscape($_POST["class"])."', ".(int)$_POST["redirect_page_id"].", '".DBFunctions::strEscape($_POST["body"])."')");
												
			$this->html = self::contentTabObjects();
			$this->msg = true;
		}
		
		if ($method == "update_object" && (int)$id) {
			
			if ($_POST['hotspot'] == 'image') {
				$value_img = DBFunctions::strEscape($_POST["img"]);
			} else if ($_POST['hotspot'] == 'shape') {
				$value_shape = DBFunctions::strEscape($_POST["shape"]);
			}
											
			$res = DB::query("UPDATE ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECTS")." SET
								name = '".DBFunctions::strEscape($_POST["name"])."',
								img = '".$value_img."',
								shape = '".$value_shape."',
								class = '".DBFunctions::strEscape($_POST["class"])."',
								redirect_page_id = ".(int)$_POST["redirect_page_id"].",
								body = '".DBFunctions::strEscape($_POST["body"])."'
							WHERE id = ".$id."");
				
			$this->html = self::contentTabObjects();
			$this->msg = true;
		}
		
		if ($method == "del_object" && (int)$id) {
		
			$res = DB::query("DELETE oi_o, oi_ol FROM ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECTS")." oi_o
								LEFT JOIN ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECT_LINK")." oi_ol ON (oi_ol.object_interaction_object_id = oi_o.id)
									WHERE oi_o.id=".(int)$id."");
			$this->msg = true;
		}
	}
	
	private static function handleStageObjects($stage_id, $arr_objects) {
		
		$res = DB::query("DELETE FROM ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECT_LINK")."
							WHERE object_interaction_stage_id = ".$stage_id."");
							
		set_time_limit(0);
		
		$sort = 0;
		foreach ((array)$arr_objects['details'] as $key => $value) {
			
			$value = json_decode($value, true);
			$value_pos = explode('_', $arr_objects['pos'][$key]);
			$value_width = $arr_objects['width'][$key];
			
			$res = DB::query("INSERT INTO ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECT_LINK")." (object_interaction_object_id, object_interaction_stage_id, pos_x, pos_y, sort, width, effect, effect_hover, script, script_hover, style, style_hover) VALUES (
				".(int)$value["object_id"].",
				".(int)$stage_id.",
				".(float)$value_pos[0].",
				".(float)$value_pos[1].",
				".$sort.",
				".(float)$value_width.",
				'".DBFunctions::strEscape(($value['effect'] ? value2JSON($value['effect']) : ''))."',
				'".DBFunctions::strEscape(($value['effect_hover'] ? value2JSON($value['effect_hover']) : ''))."',
				'".DBFunctions::strEscape($value['script'])."',
				'".DBFunctions::strEscape($value['script_hover'])."',
				'".DBFunctions::strEscape($value['style'])."',
				'".DBFunctions::strEscape($value['style_hover'])."'
			)");
			
			$sort++;
		}
	}
	
	private static function handleStageLevels($stage_id) {
		
		$arr = self::getStageSet($stage_id);
				
		$path = Settings::get('object_interaction_stage_tiles_path').'stage_'.$stage_id.'/';

		if (is_dir($path)) {
			FileStore::deleteDirectoryTree($path);
		}
		mkdir($path, 0777, true);

		$res = imagecreatefromstring(file_get_contents(DIR_ROOT_STORAGE.SITE_NAME.$arr['stage']['img']));
		$w = imagesx($res);
		$h = imagesy($res);
		
		$ratio_min = ($arr['stage']['zoom_min']/100);
		$ratio_max = ($arr['stage']['zoom_max']/100);
		$ratio_step = ($ratio_max-$ratio_min)/$arr['stage']['zoom_levels'];
		
		for ($i = 1; $i <= $arr['stage']['zoom_levels']; $i++) {
			$splitter = new ImageSplitter($res);
			$splitter->force_tile_size = false;
			$splitter->output_type = IMAGETYPE_JPEG;
			$splitter->ratio = $ratio_min+(($i-1)*$ratio_step);
			$splitter->init();
			$splitter->getAllTiles($path, 'tile-'.$i.'-', '.jpg');
			
			status(getLabel('msg_object_interaction_tiles_level_done').': '.$i);
		}
		
		$resize = new ImageResize();
		$resize->resize(DIR_ROOT_STORAGE.SITE_NAME.$arr['stage']['img'], $path.'background.jpg', ($ratio_min*$w)/8, false);

		imagedestroy($res); 
	}
	
	private static function getObjectEffects($hover_effects = false) {

		$arr = [];
		
		$arr[] = ["id" => "shadow", "label" => getLabel("lbl_effect_shadow")];
		$arr[] = ["id" => "blur", "label" => getLabel("lbl_effect_blur")];
		$arr[] = ["id" => "tilt_left", "label" => getLabel("lbl_effect_tilt_left")];
		$arr[] = ["id" => "tilt_right", "label" => getLabel("lbl_effect_tilt_right")];
		if ($hover_effects) {
			$arr[] = ["id" => "enlarge", "label" => getLabel("lbl_effect_enlarge")];
			$arr[] = ["id" => "shake", "label" => getLabel("lbl_effect_shake")];
		}

		return $arr;
	}
	
	private static function getObjectShapes() {

		$arr = [];
		
		$arr[] = ["id" => "circle", "label" => getLabel("lbl_shape_circle")];
		$arr[] = ["id" => "square", "label" => getLabel("lbl_shape_square")];

		return $arr;
	}
		
	public static function getObjects($object_id = 0) {
	
		$arr = [];

		$res = DB::query("SELECT * FROM ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECTS")." oi_o
								".($object_id ? "WHERE id = ".(int)$object_id."" : "")."
								ORDER BY name");

		while($row = $res->fetchAssoc()) {
			$arr[$row["id"]] = $row;
		}
		
		return ((int)$object_id ? current($arr) : $arr);
	}
	
	public static function getStages($stage_id = 0) {
	
		$arr = [];
		
		$res = DB::query("SELECT oi_s.*
								FROM  ".DB::getTable("DEF_OBJECT_INTERACTION_STAGES")." AS oi_s
							".((int)$stage_id ? "WHERE oi_s.id = ".$stage_id."" : "")."
							ORDER BY name");
								
		while($row = $res->fetchAssoc()) {
			$arr[] = $row;
		}		

		return ((int)$stage_id ? current($arr) : $arr);
	}
	
	public static function getStageSet($stage_id) {
	
		$arr = [];

		$res = DB::query("SELECT oi_s.*, oi_ol.object_interaction_object_id AS object_id, oi_ol.effect AS object_effect, oi_ol.effect_hover AS object_effect_hover, oi_ol.script AS object_script, oi_ol.script_hover AS object_script_hover, oi_ol.style AS object_style, oi_ol.style_hover AS object_style_hover, oi_ol.width AS object_width, oi_ol.pos_x AS object_pos_x, oi_ol.pos_y AS object_pos_y,
								oi_o.name AS object_name, oi_o.img AS object_img, oi_o.shape AS object_shape, oi_o.class AS object_class, oi_o.body AS object_body, oi_o.redirect_page_id AS object_redirect_page_id
							FROM ".DB::getTable("DEF_OBJECT_INTERACTION_STAGES")." oi_s
							LEFT JOIN ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECT_LINK")." oi_ol ON (oi_ol.object_interaction_stage_id = oi_s.id)
							LEFT JOIN ".DB::getTable("DEF_OBJECT_INTERACTION_OBJECTS")." oi_o ON (oi_o.id = oi_ol.object_interaction_object_id)
								WHERE oi_s.id = ".(int)$stage_id."
							ORDER BY oi_ol.sort");

		while($row = $res->fetchAssoc()) {
			$arr['stage'] = $row;
			if ($row['object_id']) {
				$row['object_effect'] = (array)json_decode($row['object_effect'], true);
				$row['object_effect_hover'] = (array)json_decode($row['object_effect_hover'], true);
				$arr['objects'][] = $row;
			}
		}
		
		return $arr;
	}
}
