<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class object_interaction {

	public static $label;
	public static $parent_label;
	public static function moduleProperties() {
		self::$label = getLabel('ttl_object_interaction');
		self::$parent_label = getLabel('ttl_object_interaction');
	}
	
	public static function moduleVariables() {
		$return .= '<select>';
		$return .= cms_general::createDropdown(cms_object_interaction::getStages());
		$return .= '</select>';
		
		return $return;
	}
	
	private $arr = [];
	private $stage_width = 0;
	private $stage_height = 0;
	
	public function contents() {
	
		if ($this->arr_query[0] == "jump") {
			
			Response::location(pages::getPageURL(pages::getPages((int)$this->arr_query[1])));
			die;
		}
		
		$this->arr = cms_object_interaction::getStageSet($this->arr_variables);
				
		$info = getimagesize(DIR_ROOT_STORAGE.SITE_NAME.$this->arr['stage']['img']);
		$this->stage_width = $info[0];
		$this->stage_height = $info[1];
		
		$ratio_min = ($this->arr['stage']['zoom_min']/100);
		$ratio_max = ($this->arr['stage']['zoom_max']/100);
		$ratio_step = ($ratio_max-$ratio_min)/$this->arr['stage']['zoom_levels'];
		
		$arr_layers = [];
		for ($i = 1; $i <= $this->arr['stage']['zoom_levels']; $i++) {
			$arr_layers[] = ['width' => round($this->stage_width*($ratio_min+(($i-1)*$ratio_step))), 'height' => round($this->stage_height*($ratio_min+(($i-1)*$ratio_step))), 'tile_width' => 256, 'tile_height' => 256];
		}
				
		$script = "var target = $('.object_interaction > #stage_".$this->arr['stage']['id']."');
		var height_parent = target.parent().height();";
		
		if ($this->arr['stage']['height_full']) {
			$script .= "var height_window = $(window).height()-60; // 30 px margin
			target.height(height_window);";
		} else {
			$script .= "if (!target.height()) {
				target.height(height_parent);
			}";
		}
			
		$script .= "var obj_mapscroller = new MapScroller(target, {
				arr_levels: ".value2JSON($arr_layers).",
				default_center: {x: ".($this->arr['stage']['view_x']/100).", y: ".($this->arr['stage']['view_y']/100)."},
				default_zoom: ".($this->arr['stage']['zoom_auto'] ? "{top_right: {x: 0, y: 0}, bottom_left: {x: 0, y: 1}}" : $this->arr['stage']['zoom_level_default']).",
				tile_path: '".str_replace('://', '://s{s}.', URL_BASE).DIR_CMS.DIR_UPLOAD."object_interaction/stage_".$this->arr['stage']['id']."/tile-{z}-{x}-{y}.jpg',
				background_path: '/".DIR_CMS.DIR_UPLOAD."object_interaction/stage_".$this->arr['stage']['id']."/background.jpg'
			});
			
			obj_mapscroller.init();
		";
			
		if ($this->arr['stage']['height_full']) {
			$script .= "var height_target = target.find('.draw > .map').height();
			target.height((height_target < height_window ? (height_parent > height_target ? height_parent : height_target) : height_window));";
		}
		
		if ($this->arr['objects']) {
			$script .= "var elm_paint = obj_mapscroller.getPaint();
				elm_paint.append(target.next('.objects'));";
		}
				
		$str_objects = '';
		$script_objects = '';
		$i = 0;
		foreach ((array)$this->arr['objects'] as $arr_object) {
			$str_objects .= $this->createStageObject(
				$i,
				[
					'object_id' => $arr_object['object_id'],
					'effect' => $arr_object['object_effect'],
					'effect_hover' => $arr_object['object_effect_hover']
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
			if ($arr_object['object_script']) {
				$script_objects .= "stage.find('#object_".$i."').each(function() {".$arr_object['object_script']."});";
			}
			if ($arr_object['object_script_hover']) {
				$script_objects .= "stage.find('#object_".$i."').on('mouseover', function() {".$arr_object['object_script_hover']."});";
			}
			if ($arr_object['object_style']) {
				$style_objects .= ".object_interaction .objects > #object_".$i." .hotspot {".$arr_object['object_style']."}";
			}
			if ($arr_object['object_style_hover']) {
				$style_objects .= ".object_interaction .objects > #object_".$i." .hotspot:hover {".$arr_object['object_style_hover']."}";
			}
			$i++;
		}
		
		if ($script_objects || $this->arr['stage']['script']) {
			
			$script_custom = "$(document).ready(function() {
				var stage = elm_paint;
				var objects = {};
				$(stage.find('.object')).each(function() {
					var cur = $(this);
					objects[cur.attr('data-name')] = (objects[cur.attr('data-name')] ? objects[cur.attr('data-name')].add(cur) : cur);
				});

				".$script_objects."
				".$this->arr['stage']['script']."
			});";

			$p = new ParsePHPString();
			$script_custom = $p->parse($script_custom);
		}
		
		SiteEndEnvironment::addScript("$(document).ready(function() {
			".$script.$script_custom."
		});");

		if ($style_objects) {
			SiteEndEnvironment::addStyle($style_objects);
		}
				
		$return .= '<div id="stage_'.$this->arr['stage']['id'].'"></div><div class="objects">'.$str_objects.'</div>';
		
		return $return;
	}
	
	private function createStageObject($id, $arr_stage_object, $arr_object) {
		
		$arr_style_extra = [];
		$arr_classes = [];
		
		$info = parseBody($arr_object['body'], ['function' => 'strEscapeHTML']);
		if ($arr_object['img']) {
						
			$img_width = ($arr_object['width']/100)*(($this->arr['stage']['zoom_max']/100)*$this->stage_width); // Image width * max zoomed stage width
			$arr_img_options = [];
			$arr_img_padding = $arr_img_hover_padding = [0];
			if (in_array('shadow', $arr_stage_object['effect'])) {
				$arr_img_options['shadow'] = ['color' => 'black', 'spread' => 8, 'x' => 5, 'y' => 5];
				$arr_img_padding[] = (2*8+5);
			}
			if (in_array('blur', $arr_stage_object['effect'])) {
				$arr_img_options['blur'] = ['amount' => 3];
				$arr_img_padding[] = 3*2;
			}
			$img_url = siteStartVars::getCacheURL("img", [$img_width, 9999, $arr_img_options], $arr_object['img']);
			
			if ($arr_stage_object['effect_hover']) {
				$img_hover_width = $img_width;
				$arr_img_hover_options = [];
				if (in_array('enlarge', $arr_stage_object['effect_hover'])) {
					$img_hover_width = $img_hover_width*1.1;
				}
				if (in_array('shadow', $arr_stage_object['effect_hover'])) {
					$arr_img_hover_options['shadow'] = ['color' => 'red', 'spread' => 8, 'x' => 5, 'y' => 5];
					$arr_img_hover_padding[] = (2*8+5);
				}
				if (in_array('blur', $arr_stage_object['effect_hover'])) {
					$arr_img_hover_options['blur'] = ['amount' => 3];
					$arr_img_hover_padding[] = 3*2;
				}
				$img_hover_url = siteStartVars::getCacheURL("img", [$img_hover_width, 9999, $arr_img_hover_options], $arr_object['img']);
			}
			
			$value_hotspot = '<div class="hotspot image-effect"'.($info ? ' data-popper="'.$info.'"' : '').'><img src="'.$img_url.'" data-width="'.$img_width.'" data-padding="'.max($arr_img_padding).'"'.($img_hover_url ? ' data-hover-src="'.$img_hover_url.'" data-hover-width="'.$img_hover_width.'" data-hover-padding="'.max($arr_img_hover_padding).'"' : '').' /></div>';
		} else {
			$value_hotspot = '<div class="hotspot shape'.($arr_object['shape'] ? ' '.$arr_object['shape'] : '').($arr_object['class'] ? ' '.$arr_object['class'] : '').'"'.($info ? ' data-popper="'.$info.'"' : '').'></div>';
			$arr_style_extra[] = 'height: '.(($this->stage_width/$this->stage_height)*$arr_object['width']).'%;';
		}
		
		foreach ($arr_stage_object['effect'] as $value) {
			$arr_classes[] = $value;
		};
		foreach ($arr_stage_object['effect_hover'] as $value) {
			$arr_classes[] = 'hover-'.$value;
		};
		
		$value_hotspot = ($arr_object['redirect_page_id'] ? '<a href="'.SiteStartEnvironment::getModuleURL($this->mod_id).'jump/'.$arr_object['redirect_page_id'].'">'.$value_hotspot.'</a>' : $value_hotspot);

		return '<div id="object_'.$id.'" class="object'.($arr_classes ? ' '.implode(' ', $arr_classes) : '').'" style="left: '.$arr_object['pos_x'].'%; top: '.$arr_object['pos_y'].'%; width: '.$arr_object['width'].'%; '.implode(' ', $arr_style_extra).'" data-name="'.$arr_object['name'].'">
			'.$value_hotspot.'
		</div>';
	}
	
	public static function css() {
		
		$return .= ".object_interaction .objects { width: 100%; height: 100%; }
					.object_interaction > .objects { display: none; }
					.object_interaction .objects > .object { position: absolute; }
					.object_interaction .objects > .object .hotspot { position: relative; z-index: 1; margin: 0px auto; max-width: 100%; max-height: 100%; display: block; -ms-box-sizing: border-box; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; }
					.object_interaction .objects > .object .hotspot.shape { background-color: #000000; opacity: 0; width: 99999px; height: 99999px; }
					.object_interaction .objects > .object .hotspot.shape.circle,
					.object_interaction .objects > .object .hotspot.shape.square { opacity: 0.3; }
					.object_interaction .objects > .object .hotspot.shape.circle { -webkit-border-radius: 50%; -moz-border-radius: 50%; border-radius: 50%; }
					
					.object_interaction .objects > .object.shadow .hotspot.shape { box-shadow: 5px 5px 8px 0px rgba(0, 0, 0, 80); -webkit-box-shadow: 5px 5px 8px 0px rgba(0, 0, 0, 80); }
					.object_interaction .objects > .object.hover-shadow:hover .hotspot.shape { box-shadow: 5px 5px 8px 0px rgba(255, 0, 0, 80); -webkit-box-shadow: 5px 5px 8px 0px rgba(255, 0, 0, 80); }";
		
		return $return;
	}
	
	public static function js() {
	
		$return .= "SCRIPTER.static('.object_interaction', function(elm_scripter) {
			
			elm_scripter.find('.object .hotspot').each(function() {
				var cur = $(this);
				var target = cur.closest('.object');
			
				if (target.hasClass('tilt_left') || target.hasClass('tilt_right')) {
					var cur_class = (cur.closest('.object').hasClass('tilt_right') ? 'tilt_right' : 'tilt_left');
					var css = {rotate: (cur_class == 'tilt_right' ? 0.2 : -0.2)};
					
					cur.css(css).data('rotation', cur.css('rotate'));
				}
			});
				
			elm_scripter.on('mouseover', '.object .hotspot', function(e) {
			
				var cur = $(this);
				var elm_stage = cur.closest('[id^=stage_]');
				
				cur.css('z-index', '+=1');
				elm_stage.on('movingstop.hotspot', function() {						
					cur.popper();
				});
				if (!elm_stage.hasClass('moving')) {
					SCRIPTER.triggerEvent(elm_stage, 'movingstop');
				}
				
				cur.one('mouseleave', function(e) {
					cur.css('z-index', '-=1');
					cur.popper('del');
					elm_stage.off('.hotspot');
				});
				elm_stage.on('movingstart.hotspot', function(e) {
					cur.popper('del');
				});
				
			}).on('mouseover', '.object .hotspot', function() {
									
				var cur = $(this);
				var target = cur.closest('.object');
				
				cur.one('mouseleave', function(e) {
					cur.stop(true); // Stop all animations
				});
				
				if (target.hasClass('hover-tilt_left') || target.hasClass('hover-tilt_right')) {
					var cur_class = (cur.closest('.object').hasClass('hover-tilt_right') ? 'tilt_right' : 'tilt_left');
					var css = {rotate: (cur_class == 'tilt_right' ? 0.2 : -0.2)};
					var default_rotation = cur.data('rotation');
					
					cur.one('mouseleave', function(e) {
						cur.animate({rotate: default_rotation}, {duration: 'fast', queue: false}, 'easeOutSine');
					});
					
					cur.animate(css, {duration: 'fast', queue: false}, 'easeOutSine', function() {
						cur.data('rotation', cur.css('rotate'));
					});
				}
				if (target.hasClass('hover-enlarge')) {
					var css = {maxWidth: '110%', maxHeight: '110%', margin: '-5%'};
					
					cur.one('mouseleave', function(e) {
						cur.css({maxWidth: '110%', maxHeight: '110%'})
							.animate({maxWidth: '100%', maxHeight: '100%', margin: 0}, {duration: 'fast', queue: false}, 'easeOutSine');
					});
					
					cur.css({maxWidth: '100%', maxHeight: '100%'})
						.animate(css, {duration: 'fast', queue: false}, 'easeOutSine');
				}
				if (target.hasClass('hover-shake')) {				
					cur.css({rotate: 0}, {duration: 50, queue: false}, 'easeOutSine');
					var i;
					
					cur.one('mouseleave', function(e) {
						i = false;
						cur.animate({rotate: cur.data('rotation')}, {duration: '50', queue: false}, 'easeOutSine');
					});
					
					for (i = 1; (i <= 2 && i !== false); i++) {
						cur.animate({rotate: -0.2}, 50, 'easeOutSine');
						cur.animate({rotate: 0.2}, 100, 'easeOutSine');
						cur.animate({rotate: 0}, 50, 'easeOutSine');
					}
					cur.animate({rotate: cur.data('rotation')}, {duration: 50}, 'easeOutSine');
				}
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {	
		// QUERY
	}
}
