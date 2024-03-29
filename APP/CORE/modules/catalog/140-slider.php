<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class slider extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_slider');
		static::$parent_label = getLabel('ttl_site');
	}
	
	public static function moduleVariables() {
		
		$return = '<select>';
		$return .= cms_general::createDropdown(cms_sliders::getSliders());
		$return .= '</select>';
		
		return $return;
	}
	
	public function contents() {
		
		$arr_slider = cms_sliders::getSliderSet($this->arr_variables);

		$return = '<button type="button" class="prev"><span class="icon">'.getIcon('prev').'</span></button>
			<div data-speed="'.$arr_slider['slider']['speed'].'" data-timeout="'.$arr_slider['slider']['timeout'].'" data-effect="'.$arr_slider['slider']['effect'].'">';
			
			foreach ($arr_slider['slides'] as $arr_slide) {

				if ($arr_slide['media_internal_tag_id']) {
					
					$arr_media = cms_media::getMedia(false, $arr_slide['media_internal_tag_id']);
					
					foreach ($arr_media as $arr_file) {
						$return .= '<div><img src="/'.DIR_CMS.DIR_UPLOAD.$arr_file['directory'].$arr_file['filename'].'" /></div>';
					}
				} else {
					
					$html_body = parseBody($arr_slide['body']);
					$return .= '<div class="body">'.$html_body.'</div>';
				}
			}
			
			$return .= '</div>
			<button type="button" class="next"><span class="icon">'.getIcon('next').'</span></button>
			<nav class="pager"></nav>';
		
		return $return;
	}
	
	public static function css() {
		
		$return = '
			.slider { overflow: hidden; }
			.slider > div:first-of-type { position: relative; z-index: 1; width: 100%; height: 100%; overflow: hidden; }
			.slider > div:first-of-type > div { display: none; text-align: center; width: 100%; height: 100%: }
			.slider > div:first-of-type > div:first-child { display: block; }
			.slider > div:first-of-type > div > img { max-width: 100%; vertical-align: middle; }
			.slider button.prev,
			.slider button.next,
			.slider .pager { display: none; }
			
			.slider > div:first-of-type[data-effect=flow] { cursor: ew-resize; }
			.slider > div:first-of-type[data-effect=flow] > div { display: block; position: absolute; }
			
			.slider > div:first-of-type[data-effect=scroll] { overflow: visible; --slider-image-one: none; --slider-image-two: none; --slider-opacity: 0; }
			.slider > div:first-of-type[data-effect=scroll] > .flip { display: block; position: -webkit-sticky; position: sticky; z-index: 0; top: 0px; width: 100%; height: 100vh; }
			.slider > div:first-of-type[data-effect=scroll] > .flip:before,
			.slider > div:first-of-type[data-effect=scroll] > .flip:after { content: ""; position: absolute; top: 0; bottom: 0; left: 0; right: 0; background-position: center; background-repeat: no-repeat; background-size: cover; }
			.slider > div:first-of-type[data-effect=scroll] > .flip:before { background-image: var(--slider-image-one); }
			.slider > div:first-of-type[data-effect=scroll] > .flip:after { background-image: var(--slider-image-two); opacity: var(--slider-opacity); transition: opacity 1s ease-in-out; }
			.slider > div:first-of-type[data-effect=scroll] > div:not(.flip) { display: block; position: absolute; top: 0; left: 0; right: 0; height: 100vh; z-index: 1; }
			.slider > div:first-of-type[data-effect=scroll] > div:not(.flip) > p,
			.slider > div:first-of-type[data-effect=scroll] > div:not(.flip) > figure > figurecaption { position: absolute; bottom: 20%; left: 20%; right: 20%; margin: 0px; padding: 2em; background-color: #ffffff; }
			
			.slider button { position: absolute; width: 2rem; top: 25px; height: 2.5rem; z-index: 2; cursor: pointer; text-align: center; color: #ffffff; background-color: #000000; margin: 0px; padding: 0px; border: 0px; border-radius: 0px; }
			.slider button > .icon svg { height: 55%; }
			.slider button.prev { left: 0px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; }
			.slider button.next { right: 0px; border-radius: border-top-left-radius: 2px; border-bottom-left-radius: 2px; }
			.slider button:hover { color: #000000; background-color: #ffffff; }

			.slider .pager { position: absolute; z-index: 2; bottom: 0px; padding: 0px 2px; height: 20px; line-height: 20px; margin-left: 50%; background-color: #000000; text-align: center; border-top-left-radius: 2px; border-top-right-radius: 2px; }
			.slider .pager a { display: inline-block; height: 10px; width: 10px; margin: 0px 1px; border-radius: 5px; background-color: #000000; border: 1px solid #ffffff; font-size: 0px; text-decoration: none; }
			
			.slider .pager a.active,
			.slider .pager a:hover { background-color: #ffffff; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return .= "SCRIPTER.static('.slider', function(elm_scripter) {
			
			const elm_show = elm_scripter.children('div');
			
			new ContentSlider(elm_scripter, {
				effect: elm_show[0].dataset.effect,
				timeout: elm_show[0].dataset.timeout,
				speed: elm_show[0].dataset.speed
			});
		});
		
		function ContentSlider(elm, arr_options) {
		
			const SELF = this;
			
			if (SELF.slider) {
				return;
			}
			elm[0].slider = SELF;
			
			var arr_options = $.extend({
				effect: false,
				timeout: false,
				speed: false
			}, arr_options || {});
		
			const elm_show = elm.children('div');
			const elm_pager = elm_show.siblings('.pager');
			
			const elms_target = elm_show.children('div');
			
			if (!elms_target.length) {
				return;
			}
			
			if (arr_options.effect == 'flow') {
			
				var num_size_view_width = false;
				var num_size_difference = false;
				
				var is_loaded = false;
				var num_percentage = false;
			
				this.update = function(num_percentage_new) {
				
					if (!is_loaded) {
						return false;
					}
					
					num_percentage = num_percentage_new;
					let num_change = (num_size_difference * (num_percentage / 100));
					
					for (let i = 0, len = elms_target.length; i < len; i++) {
					
						const elm_slide = elms_target[i];
						
						const num_speed = elm_slide.dataset.speed;
						let num_offset = Math.round(num_change * num_speed);
						num_offset = -num_offset;
						
						elm_slide.style.transform = 'translateX('+num_offset+'px)';
					}
				};
				
				var func_draw = function() {
				
					num_size_view_width = elm[0].clientWidth;
					var num_size_view_percentage = (elm_show[0].dataset.view / 100); // View relative to stage
					var num_size_stage_width = (num_size_view_width / num_size_view_percentage);
					var num_size_stage_percentage = (num_size_stage_width / num_size_view_width); // Stage relative to view
					num_size_difference = (num_size_stage_width - num_size_view_width);

					var num_size_stage_height = 0;
					
					for (let i = 0, len = elms_target.length; i < len; i++) {
					
						const elm_slide = elms_target[i];
						const is_odd = (i % 2);
						
						let num_width_percentage = elm_slide.getAttribute('width');
						num_width_percentage = (!num_width_percentage ? (elm_slide.offsetWidth / num_size_stage_width) : num_width_percentage / 100);
						
						let num_left_percentage = elm_slide.dataset.left;
						if (num_left_percentage == undefined) {
							num_left_percentage = ((i * (1 / len)) * 100);
							elm_slide.dataset.left = num_left_percentage;
						}
						num_left_percentage = (num_left_percentage / 100);
						
						let num_top_percentage = elm_slide.dataset.top;
						if (num_top_percentage == undefined) {
							num_top_percentage = ((is_odd ? 0.7 : 0.2) * 100);
							elm_slide.dataset.top = num_top_percentage;
						}
						num_top_percentage = (num_top_percentage / 100);
						
						let num_index = elm_slide.dataset.index;
						if (num_index == undefined) {
							num_index = (is_odd ? 2 : 1);
							elm_slide.dataset.index = num_index;
						}
						
						let num_speed = elm_slide.dataset.speed;
						if (num_speed == undefined) {
							elm_slide.dataset.speed = num_index;
						}
						
						elm_slide.style.width = (num_width_percentage * 100)+'%';
						elm_slide.style.left = (num_left_percentage * 100)+'%';
						elm_slide.style.top = (num_top_percentage * 100)+'%';
						elm_slide.style.zIndex = num_index;
					}
					
					elm_show[0].style.width = num_size_stage_width+'px';
					
					for (let i = 0, len = elms_target.length; i < len; i++) {
						
						const elm_slide = elms_target[i];
						
						let num_height = elm_slide.offsetHeight;
						let num_top_percentage = (elm_slide.dataset.top / 100);
					
						elm_slide.style.marginTop = -(num_height * num_top_percentage)+'px'; // Put/anchor element to its nagative height

						let num_top = (num_height * num_top_percentage);
						
						if ((num_top + num_height) > num_size_stage_height) {
							num_size_stage_height = num_height + num_top;
						}
					}
					
					elm_show[0].style.height = num_size_stage_height+'px';
				};
				
				var cur_pos_mouse = false;
				var key_animate_idle = false;
				var time_animate_idle = 0;
				var num_animate_min = -100;
				var num_animate_max = 0;
				var arr_animate_from = {percentage: -100};
				var arr_animate_to = {percentage: 0};

				var func_move = function(e) {
					
					const pos_mouse = POSITION.getMouseToElement(e, elm_show[0]);
					
					if (e.type == 'touchstart' || e.type == 'mouseover') {
			
						cur_pos_mouse = pos_mouse;
						
						if (key_animate_idle) {
						
							ANIMATOR.animate(false, key_animate_idle);
							
							elm[0].addEventListener('mouseout', func_idle);
							elm[0].addEventListener('touchend', func_idle);
						}
						
						return;
					}
					
					//let num_percentage_new = ((pos_mouse.x / num_size_view_width) * 100); // View width is 100%
					
					let num_percentage_new = ((pos_mouse.x - cur_pos_mouse.x) / 6); // 6 pixels is 1%
					num_percentage_new = num_percentage + num_percentage_new;
					num_percentage_new = (num_percentage_new > 100 ? 100 : (num_percentage_new < 0 ? 0 : num_percentage_new));
					
					cur_pos_mouse = pos_mouse;

					SELF.update(num_percentage_new);
				};
				
				var func_idle = function(e) {
					
					if (!key_animate_idle || (e.relatedTarget && hasElement(elm[0], e.relatedTarget))) {
						return;
					}
					
					arr_animate_from.percentage = num_percentage;
					time_animate_idle = 0;
					
					func_animate_idle();
					
					elm[0].removeEventListener('mouseout', func_idle);
					elm[0].removeEventListener('touchend', func_idle);
				};
				
				var func_animate_idle = function() {
					
					key_animate_idle = ANIMATOR.animate(function(time) {
					
						if (!time_animate_idle) {
							time_animate_idle = time;
						}
					
						const time_diff = time - time_animate_idle;
						time_animate_idle = time;
						let num_percentage_new = (time_diff / 1000) * 2; // 2 percent per second
						
						if (arr_animate_to.percentage == num_animate_min) {
						
							arr_animate_from.percentage -= num_percentage_new;
							
							if (arr_animate_from.percentage < num_animate_min) {
								arr_animate_from.percentage = arr_animate_to.percentage + (arr_animate_to.percentage - arr_animate_from.percentage);
								arr_animate_to.percentage = num_animate_max;
							}
						} else {
						
							arr_animate_from.percentage += num_percentage_new;
							
							if (arr_animate_from.percentage > num_animate_max) {
								arr_animate_from.percentage = arr_animate_to.percentage - (arr_animate_from.percentage - arr_animate_to.percentage);
								arr_animate_to.percentage = num_animate_min;
							}
						}
						
						SELF.update(arr_animate_from.percentage);
						
						return true;
					}, key_animate_idle);
				};
				
				var func_animate = function() {

					new TWEEN.Tween(arr_animate_from)
						.to(arr_animate_to, 2500)
						.easing(TWEEN.Easing.Sinusoidal.InOut)
						.onUpdate(function() {
							SELF.update(arr_animate_from.percentage);
						})
						.onComplete(function() {
							
							num_animate_min = 0;
							num_animate_max = 100;
							arr_animate_from.percentage = 0;
							arr_animate_to.percentage = 100;
							time_animate_idle = 0;
							
							func_animate_idle();
						})
					.start();
					
					ANIMATOR.trigger();
				};
				
				var func_animate_check = function() {
				
					var pos = {top: 0, left: 0, width: window.innerWidth, height: window.innerHeight};
					var pos_bottom = pos.top + pos.height;
					var pos_right = pos.left + pos.width;

					var pos_slider = elm[0].getBoundingClientRect();
					var pos_slider_bottom = (pos_slider.top + (pos_slider.height * 0.6));
					var pos_slider_right = (pos_slider.left + (pos_slider.width * 0.6));
					
					if (
						(((pos_slider_bottom >= pos.top && pos_slider_bottom <= pos_bottom) && (pos_slider.top >= pos.top && pos_slider.top <= pos_bottom)) || (pos_slider_bottom >= pos_bottom && pos_slider.top <= pos.top))
						&& 
						(((pos_slider_right >= pos.left && pos_slider_right <= pos_right) && (pos_slider.left >= pos.left && pos_slider.left <= pos_right)) || (pos_slider_right >= pos_right && pos_slider.left <= pos.left))
					) { // Trigger on 60% of the element in view (= top + 60% of height)

						func_animate();
						
						window.removeEventListener('scroll', func_animate_check)
					}
				};
								
				new ImagesLoaded(elm_show, function() {
				
					func_draw();
					new ResizeSensor(elm[0], func_draw);
					
					elm[0].addEventListener('mouseover', func_move);
					elm[0].addEventListener('touchstart', func_move);
					elm[0].addEventListener('mousemove', func_move);
					elm[0].addEventListener('touchmove', func_move);
					
					window.addEventListener('scroll', func_animate_check);
					
					is_loaded = true;
					
					SELF.update(-999); // Place everything outside frame
					func_animate_check();					
				});
			} else if (arr_options.effect == 'scroll') {
			
				var arr_slide_settings = [];
			
				var func_draw = function() {
					
					elm[0].style.overflow = 'visible';
					elm_show[0].style.height = (elms_target.length * 100)+'vh';
					
					$('<div class=\"flip\"></div>').prependTo(elm_show);
									
					for (let i = 0, len = elms_target.length; i < len; i++) {
					
						const elm_slide = elms_target[i];
						
						const elm_image = $(elm_slide).find('img').first();
						const str_url = elm_image[0].src;
						
						arr_slide_settings[i] = {url: str_url};
						
						elm_image[0].classList.add('hide');
						
						elm_slide.style.top = (i * 100)+'vh';
					}
				};
				
				var func_scroll_check = function() {
				
					var pos = {top: 0, left: 0, width: window.innerWidth, height: window.innerHeight};
					var pos_bottom = pos.top + pos.height;
					var pos_right = pos.left + pos.width;
					
					for (let i = 0, len = elms_target.length; i < len; i++) {
						
						const elm_slide = elms_target[i];
						
						var pos_slider = elm_slide.getBoundingClientRect();
						var pos_slider_bottom = (pos_slider.top + (pos_slider.height * 0.6));
						var pos_slider_right = (pos_slider.left + (pos_slider.width * 0.6));
						
						if (
							((pos_slider_bottom >= pos.top && pos_slider_bottom <= pos_bottom) || (pos_slider_bottom >= pos_bottom && pos_slider.top <= pos.top))
							&& 
							((pos_slider_right >= pos.left && pos_slider_right <= pos_right) || (pos_slider_right >= pos_right && pos_slider.left <= pos.left))
						) { // Trigger when the bottom (= 60% of height) is in view

							func_flip(i);
							break;
						}
					}
				};
				
				var is_flipped = false;
				var num_slide_active = false;
				
				var func_flip = function(num_slide) {
				
					if (num_slide_active === num_slide) {
						return;
					}

					const str_url = arr_slide_settings[num_slide].url;
					
					if (is_flipped) {
						elm_show[0].style.setProperty('--slider-image-two', 'url(\''+str_url+'\')');
						elm_show[0].style.setProperty('--slider-opacity', 1);
					} else {
						elm_show[0].style.setProperty('--slider-image-one', 'url(\''+str_url+'\')');
						elm_show[0].style.setProperty('--slider-opacity', 0);
					}
					
					is_flipped = (is_flipped ? false : true);
					num_slide_active = num_slide;
				};
			
				func_draw();
				func_flip(0); // Prepare first slide
				
				window.addEventListener('scroll', func_scroll_check);
				
				func_scroll_check();
			} else {
			
				const elm_target = elms_target.first();
				
				new ImagesLoaded(elm_target, function() {
		
					elm_show.cycle({
						fx: arr_options.effect,
						timeout: arr_options.timeout,
						speed: arr_options.speed,
						pager: elm_pager,
						activePagerClass: 'active',
						before: function(elm_cur, elm_next) {
							const num_height = $(elm_next).height();
							const num_height_parent = elm_show.height();
							if (num_height && num_height > num_height_parent) {
								elm_show.height(num_height);
							}
						}
					});
					elm.on('mouseenter.slider', function() {
						$(this).children('div').cycle('pause');
						$(this).children('.next, .prev, .pager').stop(true, true).fadeIn('fast');					
						$(this).children('.pager').css('left', '-'+($(this).children('.pager').width()/2)+'px');	
					}).on('mouseleave.slider', function() {
						$(this).children('div').cycle('resume');
						$(this).children('.next, .prev, .pager').stop(true, true).fadeOut('fast');				
					}).on('click.slider', '.prev', function() {
						$(this).siblings('div').cycle('prev');
					}).on('click.slider', '.next', function() {
						$(this).siblings('div').cycle('next');
					});
				});
			}
		}";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {	
		// QUERY
	}
}
