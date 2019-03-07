<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class slider extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_slider');
		static::$parent_label = getLabel('ttl_site');
	}
	
	public static function moduleVariables() {
		
		$return .= '<select>';
		$return .= cms_general::createDropdown(cms_sliders::getSliders());
		$return .= '</select>';
		
		return $return;
	}
	
	public function contents() {
		
		$arr_slider = cms_sliders::getSliderSet($this->arr_variables);

		$return .= '<span class="icon prev" data-category="full">'.getIcon('prev').'</span>
			<div class="slideshow" data-speed="'.$arr_slider['slider']['speed'].'" data-timeout="'.$arr_slider['slider']['timeout'].'" data-effect="'.$arr_slider['slider']['effect'].'">';
			
			foreach ($arr_slider['slides'] as $slide) {

				if ($slide['media_internal_tag_id']) {
					
					foreach (cms_media::getMedia(false, $slide['media_internal_tag_id']) as $media) {
						$return .= '<div><img src="/'.DIR_CMS.DIR_UPLOAD.$media['directory'].$media['filename'].'" /></div>';
					}
				} else {
					$body = $slide['body'];
					$body = parseBody($body);
					$return .= '<div>'.$body.'</div>';
				}
			}
			
			$return .= '</div>
			<span class="icon next" data-category="full">'.getIcon('next').'</span>
			<div class="toolbar"></div>';
		
		return $return;
	}
	
	public static function css() {
		
		$return .= '
			.slider .slideshow { position: relative; z-index: 1; width: 100%; height: 100%; overflow: hidden; }
			.slider .slideshow > div { display: none; text-align: center; width: 100%; height: 100%: }
			.slider .slideshow > div:first-child { display: block; }
			.slider .slideshow > div > img { max-width: 100%; vertical-align: middle; }
			.slider .icon.prev,
			.slider .icon.next,
			.slider .toolbar { display: none; }
			.slider .slideshow > div > p:first-child { margin: 0px; padding: 0px; }
			
			.slider .icon.prev,
			.slider .icon.next { position: absolute; width: 20px; top: 25px; height: 25px; z-index: 2; cursor: pointer; text-align: center; background-color: #000000; color: #ffffff; }
			.slider .icon.prev svg,
			.slider .icon.next svg { height: 1.4rem; }
			.slider .icon.prev { left: 0px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; }
			.slider .icon.next { right: 0px; border-top-left-radius: 2px; border-bottom-left-radius: 2px; }
			.slider .icon.prev:hover,
			.slider .icon.next:hover { color: #000000; background-color: #ffffff; }

			.slider .toolbar { position: absolute; z-index: 2; bottom: 0px; padding: 0px 2px; height: 20px; line-height: 20px; margin-left: 50%; background-color: #000000; text-align: center; border-top-left-radius: 2px; border-top-right-radius: 2px; }
			.slider .toolbar a { display: inline-block; height: 10px; width: 10px; margin: 0px 1px; border-radius: 5px; background-color: #000000; border: 1px solid #ffffff; font-size: 0px; text-decoration: none; }
			
			.slider .toolbar a.active,
			.slider .toolbar a:hover { background-color: #ffffff; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return .= "SCRIPTER.static('.slider', function(elm_scripter) {
				
			var elm_slideshow = elm_scripter.children('.slideshow');
			
			var timeout = elm_slideshow.attr('data-timeout');
			var speed = elm_slideshow.attr('data-speed');
			var effect = elm_slideshow.attr('data-effect');			
			var elm_pager = elm_slideshow.siblings('.toolbar');
			
			var elm_target = elm_slideshow.children('div').first();
			
			new ImagesLoaded(elm_target, function() {
			
				elm_scripter.height(elm_scripter.height());
				elm_slideshow.cycle({
					fx: effect,
					timeout: timeout,
					speed: speed,
					pager: elm_pager,
					activePagerClass: 'active',
					before: function(cur_elm, next_elm) {
						var elm_height = $(next_elm).height();
						var parent_height = elm_scripter.height();
						if (elm_height > parent_height) {
							elm_scripter.height(elm_height);
						}
					}
				});
				elm_scripter.on('mouseenter.slider', function() {
					$(this).children('.slideshow').cycle('pause');
					$(this).children('.next, .prev, .toolbar').stop(true, true).fadeIn('fast');					
					$(this).children('.toolbar').css('left', '-'+($(this).children('.toolbar').width()/2)+'px');	
				}).on('mouseleave.slider', function() {
					$(this).children('.slideshow').cycle('resume');
					$(this).children('.next, .prev, .toolbar').stop(true, true).fadeOut('fast');				
				}).on('click.slider', '.prev', function() {
					$(this).siblings('.slideshow').cycle('prev');
				}).on('click.slider', '.next', function() {
					$(this).siblings('.slideshow').cycle('next');
				});
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {	
		// QUERY
	}
}
