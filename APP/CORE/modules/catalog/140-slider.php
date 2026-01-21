<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
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

		$return = '<div class="carousel" data-speed="'.$arr_slider['slider']['speed'].'" data-timeout="'.$arr_slider['slider']['timeout'].'" data-effect="'.$arr_slider['slider']['effect'].'">';
					
			foreach ($arr_slider['slides'] as $arr_slide) {

				if ($arr_slide['media_internal_tag_id']) {
					
					$arr_media = cms_media::getMedia(false, $arr_slide['media_internal_tag_id']);
					
					foreach ($arr_media as $arr_file) {
						$return .= '<div><img src="/'.DIR_CMS.DIR_UPLOAD.$arr_file['directory'].$arr_file['filename'].'" /></div>';
					}
				} else {
					
					$str_html_body = parseBody($arr_slide['body']);
					
					$return .= '<div class="body">'.$str_html_body.'</div>';
				}
			}
			
		$return .= '</div>
		<nav class="album-items"><button type="button" class="prev"><span class="icon">'.getIcon('prev').'</span></button><button type="button" class="next"><span class="icon">'.getIcon('next').'</span></button></nav>
		<nav class="pager"></nav>';
		
		return $return;
	}
	
	public static function css() {
		
		$return = '
			.slider { overflow: hidden; }
			.slider > div { position: relative; z-index: 1; width: 100%; height: 100%; overflow: hidden; }
			.slider > div > div { text-align: center; width: 100%; height: 100%: }
			.slider > div > div > img { max-width: 100%; vertical-align: middle; }
			
			.slider > div[data-effect=flow] { cursor: ew-resize; }
			.slider > div[data-effect=flow] > div { display: block; position: absolute; }
			
			.slider > div[data-effect=scroll] { overflow: visible; --slider-image-one: none; --slider-image-two: none; --slider-opacity: 0; }
			.slider > div[data-effect=scroll] > .flip { display: block; position: -webkit-sticky; position: sticky; z-index: 0; top: 0px; width: 100%; height: 100vh; }
			.slider > div[data-effect=scroll] > .flip:before,
			.slider > div[data-effect=scroll] > .flip:after { content: ""; position: absolute; top: 0; bottom: 0; left: 0; right: 0; background-position: center; background-repeat: no-repeat; background-size: cover; }
			.slider > div[data-effect=scroll] > .flip:before { background-image: var(--slider-image-one); }
			.slider > div[data-effect=scroll] > .flip:after { background-image: var(--slider-image-two); opacity: var(--slider-opacity); transition: opacity 1s ease-in-out; }
			.slider > div[data-effect=scroll] > div:not(.flip) { display: block; position: absolute; top: 0; left: 0; right: 0; height: 100vh; z-index: 1; }
			.slider > div[data-effect=scroll] > div:not(.flip) > p,
			.slider > div[data-effect=scroll] > div:not(.flip) > figure > figurecaption { position: absolute; bottom: 20%; left: 20%; right: 20%; margin: 0px; padding: 2em; background-color: #ffffff; }
			
			.slider > nav.album-items button { z-index: 2; color: #ffffff; background-color: #000000; }
			.slider > nav.album-items button:hover { color: #000000; background-color: #ffffff; }
						
			.slider > nav.pager { position: absolute; z-index: 2; bottom: 20px; left: 50%; transform: translateX(-50%); line-height: 1; font-size: 0px; text-align: center; pointer-events: none; }
			.slider > nav.pager a { display: inline-block; height: 10px; width: 10px; margin: 0px 5px; border-radius: 50%; background-color: #000000; border: 2px solid #ffffff; text-decoration: none; pointer-events: auto; }
			
			.slider > nav.pager a.active,
			.slider > nav.pager a:hover { background-color: #ffffff; }

			.slider > nav.album-items button.prev,
			.slider > nav.album-items button.next,
			.slider > nav.pager { opacity: 0; transition: opacity 0.4s; }
			
			.slider:hover > nav.album-items button.prev,
			.slider:hover > nav.album-items button.next,
			.slider:hover > nav.pager { opacity: 1; }
			
			.slider > .carousel { display: flex; flex-direction: row; align-items: normal; --slider-speed: 1000ms; --slider-index: 0; }
			.slider > .carousel > div { position: relative; flex: 0 0 100%; overflow: hidden; }
			.slider > .carousel > div:not(.body) { display: flex; justify-content: center; align-items: center; }
			
			.slider > .carousel[data-effect=vertical] { flex-direction: column; }
			.slider > .carousel[data-effect=vertical].no-height { height: 100vh; }
			
			.slider > .carousel[data-effect=fade] > div { left: calc(-100% * var(--slider-index)); opacity: 0; transition: opacity var(--slider-speed) ease-in-out; }
			.slider > .carousel[data-effect=fade] > div.active,
			.slider > .carousel[data-effect=fade]:not(:has( > div.active)) > div:first-child { opacity: 1; }
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
			
			const elms_slide = elm_show.children('div');
			
			if (!elms_slide.length) {
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
					
					for (let i = 0, len = elms_slide.length; i < len; i++) {
					
						const elm_slide = elms_slide[i];
						
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
					
					for (let i = 0, len = elms_slide.length; i < len; i++) {
					
						const elm_slide = elms_slide[i];
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
					
					for (let i = 0, len = elms_slide.length; i < len; i++) {
						
						const elm_slide = elms_slide[i];
						
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
					elm_show[0].style.height = (elms_slide.length * 100)+'vh';
					
					$('<div class=\"flip\"></div>').prependTo(elm_show);
									
					for (let i = 0, len = elms_slide.length; i < len; i++) {
					
						const elm_slide = elms_slide[i];
						
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
					
					for (let i = 0, len = elms_slide.length; i < len; i++) {
						
						const elm_slide = elms_slide[i];
						
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
			
				const elm_prev = elm.find('nav .prev');
				const elm_next = elm.find('nav .next');
				
				const num_duration = parseInt(arr_options.effect != 'none' ? arr_options.speed : 0);
				const num_timeout = (num_duration + parseInt(arr_options.timeout));
				
				elm_show[0].style.setProperty('--slider-speed', num_duration+'ms');

				for (let i = 0, len = elms_slide.length; i < len; i++) {
				
					const elm_slide = elms_slide[i];
					elm_slide.style.setProperty('--slider-index', i);
				
					const elm_link = $('<a href=\"#\">'+(i + 1)+'</a>');
					elm_pager.append(elm_link);
					
					elm_slide.num_slide = i;
					elm_link[0].num_slide = i;
				}
				
				const elms_link = elm_pager.children();
				let num_slide_active = 0;
				
				const func_slide = function(num_slide) {
					
					num_slide_active = num_slide;
					
					if (num_slide_active < 0) {
						num_slide_active = (elms_slide.length - 1);
					} else if (num_slide_active >= elms_slide.length) {
						num_slide_active = 0;
					}

					for (let i = 0, len = elms_link.length; i < len; i++) {
						
						if (i == num_slide_active) {
							elms_link[i].classList.add('active');
							elms_slide[i].classList.add('active');
						} else {
							elms_link[i].classList.remove('active');
							elms_slide[i].classList.remove('active');
						}
					}
					
					if (arr_options.effect == 'fade') {
						return;
					}
					
					const elm_slide = elms_slide[num_slide_active];
					
					moveScroll(elm_slide, {elm_container: elm_show, duration: num_duration});
				};
				
				// Interaction
				
				elm_pager[0].addEventListener('click', function(e) {
					
					if (!e.target.matches('a')) {
						return;
					}
					
					e.preventDefault();

					func_slide(e.target.num_slide);
				});
				elm_prev[0].addEventListener('click', function(e) {
				
					func_slide(num_slide_active-1);
				});
				elm_next[0].addEventListener('click', function(e) {
				
					func_slide(num_slide_active+1);
				});
				
				// Idle
				
				let timer_idle = false;
				
				var func_hold = function() {
				
					if (timer_idle) {
						clearInterval(timer_idle);
						timer_idle = false;
					}
				
					elm[0].addEventListener('mouseout', func_idle);
					elm[0].addEventListener('touchend', func_idle);
				};
				var func_idle = function() {
					
					if (timer_idle) {
						clearInterval(timer_idle);
						timer_idle = false;
					}
					
					timer_idle = setInterval(function() {
						func_slide(num_slide_active+1);
					}, num_timeout);
					
					elm[0].removeEventListener('mouseout', func_idle);
					elm[0].removeEventListener('touchend', func_idle);
				};
				
				elm[0].addEventListener('mouseover', func_hold);
				elm[0].addEventListener('touchstart', func_hold);
				
				const elm_target = elms_slide.first();
				
				new ImagesLoaded(elm_target, function() {
					
					if (arr_options.effect == 'vertical') { // Vertical flow needs a height set on the parent
						
						if (elm_show[0].scrollHeight <= elm[0].clientHeight) {
							elm_show[0].classList.add('no-height');
						}
					}
					
					func_slide(num_slide_active); // Reset position
					
					func_idle();
				});
			}
		}";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {	
		// QUERY
	}
}
