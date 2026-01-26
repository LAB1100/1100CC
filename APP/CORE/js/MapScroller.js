
/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

function MapScroller(element, options) {

	var elm = $(element),
	SELF = this;
	
	var settings = $.extend({
		default_zoom: false,
		default_center: {x: 0.5, y: 0.5}, // xy percentage or latlong
		origin: {x: 0.5, y: 0.5, latitude: 0, longitude: 0}, // xy percentage or latlong coordinates
		center_pointer: true,
		arr_levels: [],
		pos_view_frame: {top: 0, right: 0, bottom: 0, left: 0}, // pixels or percentage
		show_zoom_levels: true,
		attribution: '', // String or {base: '', source: ''}
		arr_layers: false, // [{url: '{s}.domain.tld/tile-{z}-{x}-{y}.png', opacity: 1, attribution: string or {name: '', source: ''}}]
		background_path: '',
		background_color: false,
		allow_sizing: false,
		svg: false
	}, options || {});
	
	var cur_zoom = false,
	elm_con = false,
	elm_background = false,
	elm_map = false,
	elm_paint = false,
	elm_controls = false,
	elm_zoomer = false,
	elm_attribution = false,
	elm_layers = false,
	arr_levels = [],
	arr_layers = [],
	pos_elm = {width: 0, height: 0, x: 0, y: 0, frame: {width: 0, height: 0, top: 0, right: 0, bottom: 0, left: 0}},
	pos_offset = {},
	pos_center = {},
	pos_origin = {},
	pos_zoom = false,
	pos_view_frame = {},
	pos_render = {},
	arr_layer_level = false,
	arr_layers_manager = [],
	arr_tiles_manager = {},
	interval_layers = false,
	timer_tiles = false,
	interval_tiling = false,
	timeout_tile_animation = 100,
	arr_move = [],
	resize_sensor = false,
	do_svg = false;
	
	const stage_ns = 'http://www.w3.org/2000/svg',
	num_alpha_blending = 1; // Trigger overlapping alpha-blending that happens when you have polygons that share an edge
			
	this.init = function() {
		
		if (!elm.hasClass('mapscroller')) {
			elm.addClass('mapscroller');
		}
		
		do_svg = settings.svg;
		
		drawContainer();	
		drawControls();
					
		for (let i = 0; i < settings.arr_levels.length; i++) {
			
			const arr_level = settings.arr_levels[i];
			
			if (arr_level.level == null) {
				arr_level.level = (i + 1);
			}
			
			SELF.addZoom(arr_level);
		}
		
		if (settings.arr_layers) {
				
			for (let i = 0; i < settings.arr_layers.length; i++) {
				
				const arr_layer = settings.arr_layers[i];
				
				SELF.addLayer(arr_layer);
			}
		}
			
		SELF.setOrigin(settings.origin);
		SELF.setPosition(0, 0);
		
		SELF.setZoom(settings.default_zoom, settings.default_center, settings.pos_view_frame);
		
		addListeners();
	};
	
	this.close = function() {
		
		elm.off();
		elm.empty();
		
		if (removeListeners) {
			removeListeners();
		}
		
		resize_sensor.detach();
	};
			
	var drawContainer = function() {
		
		elm_con = $('<div class="draw" />').appendTo(elm);
		elm_background = $('<div class="background"></div>').appendTo(elm_con);
		if (settings.background_color) {
			elm_background.css('background-color', settings.background_color);
		}
		if (settings.background_path) {
			$('<img />').attr('src', settings.background_path).appendTo(elm_background);
		}
		elm_map = $('<div class="map"></div>').appendTo(elm_con);
		elm_paint = $('<div class="paint"></div>').appendTo(elm_con);
	};
			
	var drawControls = function() {
		
		elm_controls = $('<div class="controls" />').appendTo(elm);
				
		let str_attribution = '';
		
		if (settings.attribution) {
			
			if (settings.attribution instanceof Object) {
				str_attribution = (settings.attribution.source ? '<li class="source">'+settings.attribution.source+'</li>' : '')+(settings.attribution.base ? '<li class="base">'+settings.attribution.base+'</li>' : '');
			} else {
				str_attribution = '<li class="source">'+settings.attribution+'</li>';
			}
		}
		
		elm_attribution = $('<div class="attribution"><ul class="body">'+str_attribution+'</ul></div>').appendTo(elm_controls);
		elm_attribution = elm_attribution.children('ul');
		
		const elm_controls_interact = $('<div class="main" />').appendTo(elm_controls);
		
		elm_zoomer = $('<div class="zoomer hide"><button type="button" class="plus"><span class="icon"></span></button><button type="button" class="min"><span class="icon"></span></button></div>').appendTo(elm_controls_interact);
		const elm_zoomer_plus = elm_zoomer.children('button.plus');
		elm_zoomer_plus.on('click', function() {
			SELF.setZoom(cur_zoom+1);
		});
		const elm_zoomer_min = elm_zoomer.children('button.min');
		elm_zoomer_min.on('click', function() {
			SELF.setZoom(cur_zoom-1);
		});
		
		elm_layers = $('<div class="layers hide"><div><label title="Layers"><span class="icon" data-category="full"></span><input type="checkbox" value="1" class="hide" /></label></div><div><ul></ul></div></div>').appendTo(elm_controls_interact);
		
		ASSETS.getIcons(elm, ['marker-map-location', 'plus', 'min'], function(data) {
			elm_layers[0].children[0].children[0].children[0].innerHTML = data['marker-map-location'];
			elm_zoomer_plus[0].children[0].innerHTML = data['plus'];
			elm_zoomer_min[0].children[0].innerHTML = data['min'];
		});
	};
	
	this.onInteractDown = null;
	this.onInteractMove = null;
	this.onInteractUp = null;
	
	var addListeners = function() {
		
		var in_event = false;
		var num_pinch = false;
		var has_moved = false;
				
		const func_mouse_down = function(e) {
			
			let func_prevent_drag = false;
			
			if (POSITION.isTouch()) {
				
				if (e.type == 'mousedown') {
					return;
				}
				
				num_pinch = POSITION.getPinch(e);
				
				if (in_event) { // Touchstart can be triggered multiple times, prevent additional touches
					
					e.preventDefault();
					return;
				}
				// Allow click/click-select

				func_prevent_drag = function(e2) {
					e2.preventDefault(); // Prevent document drag/drag-select
				};
				
				document.addEventListener('touchmove', func_prevent_drag, {passive: false});
			} else {

				e.preventDefault(); // Prevent document drag-select
				
				if (in_event) {
					return;
				}
			}
			
			in_event = true;
			
			SCRIPTER.triggerEvent(elm[0].closest('[tabindex]'), 'focus');
		
			const pos_mouse = POSITION.getMouseXY(e);
			has_moved = false;
			
			if (SELF.onInteractDown) { // Do custom
				
				SELF.onInteractDown(e);
			} else { // Do scroller
				
				startTiling();
				doMove(true);
			}
			
			const func_mouse_move = function(e2) {
				
				const cur_pos_mouse = POSITION.getMouseXY(e2);
				let has_moved_trigger = false;
				
				if (!has_moved && (cur_pos_mouse.x != pos_mouse.x || cur_pos_mouse.y != pos_mouse.y)) { // Check for real movement because of chrome 'always trigger move'-bug

					has_moved = true;
					has_moved_trigger = true;
				}
				
				if (SELF.onInteractMove) { // Do custom
					
					SELF.onInteractMove(e2);
					return;
				}
				
				// Do scroller

				if (has_moved_trigger) {
					
					SCRIPTER.triggerEvent(elm, 'movingstart');
					elm.addClass('moving');
				}

				SELF.setPosition((cur_pos_mouse.x - pos_mouse.x), (cur_pos_mouse.y - pos_mouse.y), true);
				
				pos_mouse.x = cur_pos_mouse.x;
				pos_mouse.y = cur_pos_mouse.y;
				
				if (num_pinch !== false) {
					
					const num_pinch2 = POSITION.getPinch(e2);
					const num_difference = num_pinch2 - num_pinch;

					const pos_xy = SELF.getMousePositionToCenter();
					
					if (num_difference > 100) {
						
						SELF.setZoom(cur_zoom+1, pos_xy);
						num_pinch = num_pinch2;
					} else if (num_difference < -100) {
						
						SELF.setZoom(cur_zoom-1, pos_xy);
						num_pinch = num_pinch2;
					}
				}
			};
			
			const func_mouse_up = function(e2) {
				
				if (SELF.onInteractUp) { // Do custom
					
					SELF.onInteractUp(e2);
				} else { // Do scroller
				
					if (has_moved) {
						SCRIPTER.triggerEvent(elm, 'movingstop');
						elm.removeClass('moving');
					}
					
					doMove(false);
					stopTiling();
				}
					
				in_event = false;
				
				removeListeners();
			};
			
			document.addEventListener('mousemove', func_mouse_move, {passive: true});
			document.addEventListener('touchmove', func_mouse_move, {passive: true});
			document.addEventListener('mouseup', func_mouse_up, {passive: true});
			document.addEventListener('touchend', func_mouse_up, {passive: true});
			
			if (removeListeners) {
				removeListeners();
			}
			removeListeners = function() {
				
				if (POSITION.isTouch()) {
					document.removeEventListener('touchmove', func_prevent_drag, {passive: false});
				}
				
				document.removeEventListener('mousemove', func_mouse_move, {passive: true});
				document.removeEventListener('touchmove', func_mouse_move, {passive: true});
				document.removeEventListener('mouseup', func_mouse_up, {passive: true});
				document.removeEventListener('touchend', func_mouse_up, {passive: true});
				
				removeListeners = null;
			};
		};
		
		elm_con[0].addEventListener('mousedown', func_mouse_down, {passive: false});
		elm_con[0].addEventListener('touchstart', func_mouse_down, {passive: false});
		
		elm_con[0].addEventListener('click', function(e) {
			
			if (has_moved) { // Allow click or not
				e.stopPropagation();
			}
		});
		
		elm_con[0].addEventListener('dblclick', function(e) {
			
			const pos_xy = SELF.getMousePositionToCenter();
			
			SELF.setZoom(cur_zoom+1, pos_xy);
		});
		
		let timer_delta = null;
		let in_zoom_in_not_out = null;
		
		elm_con[0].addEventListener('wheel', function(e) {
			
			e.stopPropagation();
			e.preventDefault();
			
			const num_delta = e.deltaY;
			const is_in_not_out = (num_delta < 0); // Only in or out, not interested in the actual 'speed' of the delta value
			
			if (is_in_not_out != in_zoom_in_not_out && timer_delta) {
				
				clearTimeout(timer_delta);
				timer_delta = false;
			}
			
			in_zoom_in_not_out = is_in_not_out;
			
			if (timer_delta) {
				return;
			}
			
			timer_delta = setTimeout(function() {
				timer_delta = false;
			}, 100);

			let pos_xy = false;
			
			if (settings.center_pointer) {
				pos_xy = SELF.getMousePositionToCenter();
			}

			if (in_zoom_in_not_out) {
				SELF.setZoom(cur_zoom+1, pos_xy);
			} else {
				SELF.setZoom(cur_zoom-1, pos_xy);
			}

			SCRIPTER.triggerEvent(elm[0].closest('[tabindex]'), 'focus');
		}, {passive: false});
		
		resize_sensor = new ResizeSensor(elm[0], function() {
			
			SELF.setZoom(cur_zoom, false, pos_view_frame);
		});
	};
	
	var removeListeners = null;
	
	var setWindow = function() {
		
		pos_elm.width = elm[0].clientWidth;
		pos_elm.height = elm[0].clientHeight;
		
		pos_render.width = pos_elm.width;
		pos_render.height = pos_elm.height;
		pos_render.resolution = (window.devicePixelRatio || 1);
		if (elm[0].dataset.resolution) {
			pos_render.width = Math.ceil(elm[0].dataset.width * (elm[0].dataset.resolution / 2.54)); // Convert resolution's 1 inch to 2.54 centimeter
			pos_render.height = Math.ceil(elm[0].dataset.height * (elm[0].dataset.resolution / 2.54));
			pos_render.resolution = (pos_render.width / pos_elm.width);
		}
		
		pos_elm.frame.width = pos_elm.width;
		pos_elm.frame.height = pos_elm.height;
		
		if (pos_view_frame.left || pos_view_frame.right) {
			pos_elm.frame.left = parseInt(pos_view_frame.left);
			if (pos_view_frame.left && pos_view_frame.left.substr(-1) == '%') {
				pos_elm.frame.left = (pos_elm.frame.left / 100) * pos_elm.frame.width;
			}
			pos_elm.frame.right = parseInt(pos_view_frame.right);
			if (pos_view_frame.right && pos_view_frame.right.substr(-1) == '%') {
				pos_elm.frame.right = (pos_elm.frame.right / 100) * pos_elm.frame.width;
			}
			pos_elm.frame.width -= (pos_elm.frame.left + pos_elm.frame.right);
		}
		if (pos_view_frame.top || pos_view_frame.bottom) {
			pos_elm.frame.top = parseInt(pos_view_frame.top);
			if (pos_view_frame.top && pos_view_frame.top.substr(-1) == '%') {
				pos_elm.frame.top = (pos_elm.frame.top / 100) * pos_elm.frame.height;
			}
			pos_elm.frame.bottom = parseInt(pos_view_frame.bottom);
			if (pos_view_frame.bottom && pos_view_frame.bottom.substr(-1) == '%') {
				pos_elm.frame.bottom = (pos_elm.frame.bottom / 100) * pos_elm.frame.height;
			}
			pos_elm.frame.height -= (pos_elm.frame.top + pos_elm.frame.bottom);
		}
	};
	
	this.setOrigin = function(pos_origin_new) {
		
		if (pos_origin_new.north_east) {
			pos_origin = {latitude: 0, longitude: pos_origin_new.north_east.longitude - pos_origin_new.south_west.longitude};
		} else if (pos_origin_new.longitude != null) {
			pos_origin = {latitude: 0, longitude: pos_origin_new.longitude};
		} else {
			pos_origin = {x: pos_origin_new.x, y: pos_origin_new.y};
		}
	};
			
	var addZoomerLevel = function() {
		
		$('<button type="button" />').insertAfter(elm_zoomer.children('.plus'))
			.on('click', function() {
				SELF.setZoom(settings.arr_levels.length-$(this).index()+1);
			});
	};
	
	var setZoomerLevel = function() {
	
		elm_zoomer.children().removeClass('active');
		elm_zoomer.children('*:nth-child('+(1 + (arr_levels.length - cur_zoom) + 1)+')').addClass('active');
	};
					
	this.addZoom = function(arr_level) {
	
		const num_length = arr_levels.push(arr_level);
		
		if (num_length == 2) {
			elm_zoomer[0].classList.remove('hide');
		}
		
		if (settings.show_zoom_levels) {
			addZoomerLevel();
		}
	};
	
	this.setZoom = function (zoom_new, pos_zoom_new, pos_view_frame_new) {
		
		if (pos_view_frame_new) {
			
			pos_view_frame = pos_view_frame_new;
			setWindow();
		}
		
		if (zoom_new === false) {
			
			var zoom_new = Math.ceil(arr_levels.length / 2);
		} else if (typeof zoom_new == 'object') {
			
			if (zoom_new.scale) {
				
				var pixels = (40075000 / zoom_new.scale); // In meters
				var zoom_new = 1;
				var cur_diff = Math.abs(pixels - arr_levels[0].width);
				
				for (var i = 0; i < arr_levels.length; i++) {
					var diff = Math.abs(pixels - arr_levels[i].width);
					if (diff < cur_diff) {
						cur_diff = diff;
						zoom_new = i+1;
					}
				}
			} else if (zoom_new.level) { // Percentage
				
				var zoom_new = Math.max(1, Math.round(zoom_new.level / 100 * arr_levels.length));
			} else {
				
				var zoom_check = SELF.getBoundsZoom(zoom_new);
				
				if (zoom_new.min && zoom_check < zoom_new.min) {
					zoom_check = zoom_new.min;
				} else if (zoom_new.max && zoom_check > zoom_new.max) {
					zoom_check = zoom_new.max;
				}
				
				var zoom_new = zoom_check;
			}
		}

		if (zoom_new < 1 || zoom_new > arr_levels.length) { // Zoom has reached maximum zoom, nothing to do
			return false;
		}

		pos_zoom = (pos_zoom_new ? pos_zoom_new : pos_zoom); // Reuse a possible earlier relative zoom position (in case of resizes)
		
		if (pos_zoom) {
			
			if (pos_zoom.latitude != null) {
				
				pos_center = SELF.plotPoint(pos_zoom.latitude, pos_zoom.longitude, zoom_new);
			} else if (pos_zoom.north_east) {
				
				var xy_north_east = SELF.plotPoint(pos_zoom.north_east.latitude, pos_zoom.north_east.longitude, zoom_new);
				var xy_south_west = SELF.plotPoint(pos_zoom.south_west.latitude, pos_zoom.south_west.longitude, zoom_new);
				
				pos_center = {x: xy_south_west.x+((xy_north_east.x-xy_south_west.x)/2), y: xy_north_east.y+((xy_south_west.y-xy_north_east.y)/2)};
			} else {
				
				pos_center = {x: pos_zoom.x*SELF.levelVars(zoom_new).width, y: pos_zoom.y*SELF.levelVars(zoom_new).height};
			}
		} else {
			
			pos_center = {x: -pos_elm.x / SELF.levelVars().width * SELF.levelVars(zoom_new).width, y: -pos_elm.y / SELF.levelVars().height * SELF.levelVars(zoom_new).height};
		}
		
		if (pos_view_frame_new) {
			
			pos_center.x -= (pos_elm.frame.left - pos_elm.frame.right)/2;
			pos_center.y -= (pos_elm.frame.top - pos_elm.frame.bottom)/2;
		}

		const str_calc_zoom = (zoom_new > cur_zoom ? '+'+(zoom_new - cur_zoom) : (zoom_new < cur_zoom ? '-'+(cur_zoom - zoom_new) : '+0'));
		cur_zoom = zoom_new;
		
		pos_offset = {x: false, y: false, width: false, height: false, sizing: {}};
		
		SELF.setSize(SELF.levelVars().width, SELF.levelVars().height);
					
		SELF.setPosition(-pos_center.x, -pos_center.y, false, str_calc_zoom);
		
		renewLayers();
		
		SCRIPTER.triggerEvent(elm, 'zoom', [zoom_new, str_calc_zoom]);		
		setZoomerLevel();
	};
	
	this.setSize = function(num_width, num_height) {
					
		if (settings.allow_sizing) {
			
			pos_offset.sizing.width = pos_elm.width*4;
			pos_offset.sizing.height = pos_elm.height*4;
			
			pos_offset.width = num_width - pos_offset.sizing.width;
			var num_width = pos_offset.sizing.width;

			pos_offset.height = num_height - pos_offset.sizing.height;
			var num_height = pos_offset.sizing.height;
		}
		
		elm_map[0].style.width = num_width+'px';
		elm_map[0].style.height = num_height+'px';
		elm_paint[0].style.width = num_width+'px';
		elm_paint[0].style.height = num_height+'px';
	};
	
	this.setPosition = function(num_x, num_y, is_relative, str_calc_zoom) {
		
		var num_x = Math.floor(num_x);
		var num_y = Math.floor(num_y);
		
		const arr_level = SELF.levelVars();
		const num_width_view = Math.ceil(pos_elm.width/2);
		const num_height_view = Math.ceil(pos_elm.height/2);
		
		if (is_relative) {
			
			num_x = pos_elm.x + num_x;
			num_y = pos_elm.y + num_y;
		}
		
		if (cur_zoom && !arr_level.auto) { // Keep map in container
			
			if (arr_level.width > pos_elm.width) {
				if (num_x > -num_width_view) {
					num_x = -num_width_view;
				}
				if (num_x < -(arr_level.width - num_width_view)) {
					num_x = -(arr_level.width - num_width_view);
				}
			} else {
				if (num_x < -num_width_view) {
					num_x = -num_width_view;
				}
				if (num_x > (num_width_view - arr_level.width)) {
					num_x = (num_width_view - arr_level.width);
				}
			}
			if (arr_level.height > pos_elm.height) {
				if (num_y > -num_height_view) {
					num_y = -num_height_view;
				}
				if (num_y < -(arr_level.height - num_height_view)) {
					num_y = -(arr_level.height - num_height_view);
				}
			} else {
				if (num_y < -num_height_view) {
					num_y = -num_height_view;
				}
				if (num_y > (num_height_view - arr_level.height)) {
					num_y = (num_height_view - arr_level.height);
				}
			}
		}
		
		pos_elm.x = num_x;
		pos_elm.y = num_y;
		
		if (is_relative) {
			
			pos_zoom = false;
			
			if (pos_offset.x) {
				num_x = num_x + pos_offset.x;
			}
			if (pos_offset.y) {
				num_y = num_y + pos_offset.y;
			}
		} else {
			
			if (pos_offset.width) {
				
				const num_offset = Math.floor((arr_level.width - pos_offset.width)/2);
				pos_offset.x = -num_x - num_offset;
				num_x = -num_offset;
			}
			if (pos_offset.height) {
				
				const num_offset = Math.floor((arr_level.height - pos_offset.height)/2);
				pos_offset.y = -num_y - num_offset;
				num_y = -num_offset;
			}
		}
		
		doMove(null, str_calc_zoom);
		
		if (cur_zoom && !arr_level.auto) {
			
			num_x = num_x + num_width_view;
			num_y = num_y + num_height_view;
			
			const str = 'translate('+num_x+'px, '+num_y+'px)';
			
			elm_map[0].style.transform = elm_map[0].style.webkitTransform = str;
			elm_paint[0].style.transform = elm_paint[0].style.webkitTransform = str;
		}
	};
	
	this.getPosition = function() {
		
		const arr_level = SELF.levelVars();
		
		const arr = {
			x: pos_elm.x, y: pos_elm.y, origin: pos_origin, level: arr_level.level,
			view: {width: pos_elm.width, height: pos_elm.height},
			size: {width: (pos_offset.sizing.width ? pos_offset.sizing.width : arr_level.width), height: (pos_offset.sizing.height ? pos_offset.sizing.height : arr_level.height)},
			offset: {x: (pos_offset.x ? pos_offset.x : 0), y: (pos_offset.y ? pos_offset.y : 0)},
			render: {width: pos_render.width, height: pos_render.height, resolution: pos_render.resolution}
		};
		
		return arr;
	};
	
	this.getMousePosition = function(do_test) {
		
		var do_test = (do_test == undefined || do_test ? true : false);
		
		if (do_test && !POSITION.testMouseOnElement(false, elm_paint[0])) {
			return false;
		}
		
		const pos_map = POSITION.getElementToDocument(elm_map[0]);
		
		return {x: (-pos_map.x + POSITION.mouse.x + (pos_offset.x ? pos_offset.x : 0)), y: (-pos_map.y + POSITION.mouse.y + (pos_offset.y ? pos_offset.y : 0))};
	};
	
	this.getMousePositionToCenter = function() {
		
		const pos_mouse = SELF.getMousePosition(false);
		
		return {x: (pos_mouse.x - ((pos_mouse.x + pos_elm.x)/2)) / SELF.levelVars().width, y: (pos_mouse.y - ((pos_mouse.y + pos_elm.y)/2)) / SELF.levelVars().height};
	};
	
	this.move = function(call, key, do_call) {
		
		if (key === 0 || key > 0) {
			
			arr_move[key] = call;
		} else {
			
			for (let i = 0, len = arr_move.length; i <= len; i++) {
				
				if (arr_move[i] === null || arr_move[i] === undefined) {
					
					var key = i;
					arr_move[key] = call;
					
					break;
				}
			}
		}
		
		if (call && do_call !== false) {
			call(false, SELF.getPosition(), cur_zoom, cur_zoom); // Initialise move/position/zoom positions
		}
		
		return key;
	};
	
	var doMove = function(move, str_calc_zoom) {
				
		const num_length = arr_move.length;
		
		if (!num_length) {
			return;
		}
		
		const arr_position = SELF.getPosition();
		
		for (let i = 0; i < num_length; i++) {
			
			if (!arr_move[i]) {
				continue;
			}
			
			arr_move[i](move, arr_position, cur_zoom, str_calc_zoom);
		}
	};
	
	this.addLayer = function(arr_layer) {
		
		const num_length = arr_layers.push(arr_layer);
		
		if (num_length == 1) {
			elm_layers[0].classList.remove('hide');
		}

		// Attribution
		
		let elm_attribution_source = false;
		let elm_attribution_name = false;
		
		if (arr_layer.attribution) {
			
			let str_attribution_source = '';
			let str_attribution_name = '';
			
			if (arr_layer.attribution instanceof Object) {
				str_attribution_source = (arr_layer.attribution.source ? arr_layer.attribution.source : false);
				str_attribution_name = (arr_layer.attribution.name ? arr_layer.attribution.name : false);
			} else {
				str_attribution_source = arr_layer.attribution;
				str_attribution_name = arr_layer.attribution;
			}
			
			if (str_attribution_source) {
				
				elm_attribution_source = $('<li class="layer">'+str_attribution_source+'</li>');
				
				const elm_source = elm_attribution.children('.source');
				if (elm_source.length) {
					elm_attribution_source.insertAfter(elm_source);
				} else {
					elm_attribution_source.prependTo(elm_attribution);
				}
			}
			
			if (str_attribution_name) {
				elm_attribution_name = $('<div>'+str_attribution_name+'</div>')[0];
			}
		}
		
		// Container / map

		const elm_container = $('<div />')[0];
		elm_map[0].prepend(elm_container);

		arr_layers_manager[num_length-1] = {elm_container: elm_container, offset: null, zoom: null, elm: null, elm_loading: null, tiles: null};
				
		const func_update_visibility = function(num_opacity) {
			
			const do_revisit = (arr_layer.opacity == 0 && num_opacity > 0);
			
			arr_layer.opacity = num_opacity;
			elm_container.style.opacity = num_opacity;
			
			if (do_revisit) {
				drawTiles();
			}
			
			if (elm_attribution_source) {
				
				if (do_revisit) {
					elm_attribution_source[0].classList.remove('hide');
				} else if (!num_opacity) {
					elm_attribution_source[0].classList.add('hide');
				}
			}
		};
		
		if (arr_layer.opacity == null) {
			arr_layer.opacity = 1;
		} else if (arr_layer.opacity < 1) {
			func_update_visibility(arr_layer.opacity);
		}
		
		// Interact
		
		const elm_layer = $('<li><div><input type="range" min="0" max="1" step="0.01" value="'+arr_layer.opacity+'" /></div></li>').appendTo(elm_layers.find('ul'));
		
		if (elm_attribution_name) {
			elm_layer[0].append(elm_attribution_name);
		}
		
		const elm_layer_opacity = elm_layer.find('input[type=range]');
		elm_layer_opacity.on('input', function() {
			func_update_visibility(parseFloat(this.value));
		});
	};
	
	var renewLayers = function() {
		
		if (!arr_layers.length) {
			return;
		}
		
		const arr_position = SELF.getPosition();
		arr_layer_level = SELF.levelVars();
		let num_layer_resolution = 1;
		
		const num_find_width = (arr_layer_level.width * arr_position.render.resolution);
		
		if (do_svg && arr_layer_level.width != num_find_width) {
			
			for (let i = 0, len_i = arr_levels.length; i < len_i; i++) {
				
				if (arr_levels[i].width < num_find_width) {
					continue;
				}
				
				num_layer_resolution = (arr_levels[i].width / arr_layer_level.width);
				arr_layer_level = arr_levels[i];
				break;
			}			
		}
		
		arr_layer_level.resolution = num_layer_resolution;
		arr_layer_level.offset = {x: (arr_position.offset.x * num_layer_resolution), y: (arr_position.offset.y * num_layer_resolution)};
		
		arr_tiles_manager = {};
		arr_tiles_manager.total = false;
		arr_tiles_manager.extra = 1;
		
		arr_tiles_manager.count_width_range = Math.ceil((arr_layer_level.width / arr_layer_level.tile_width) / 2) * 2; // Width range (rounded)
		let xy_origin = false;
		if (pos_origin.longitude != null) {
			xy_origin = SELF.plotPoint(0, SELF.parseLongitude(pos_origin.longitude - 180, true), null, true);
		} else if (pos_origin.x != null) {
			xy_origin = pos_origin.origin;
		}
		if (xy_origin && xy_origin.x > 0) {
			
			arr_tiles_manager.count_width_range_origin = Math.floor((xy_origin.x * num_layer_resolution) / arr_layer_level.tile_width);
			arr_tiles_manager.width_range_surplus = Math.floor((xy_origin.x * num_layer_resolution) % arr_layer_level.tile_width); // Width range (leftover)
			arr_tiles_manager.count_width_range++; // Add extra divided tile
		} else {
			
			arr_tiles_manager.count_width_range_origin = false;
			arr_tiles_manager.width_range_surplus = Math.floor(arr_layer_level.width % arr_layer_level.tile_width); // Width range (leftover)
		}
		
		arr_tiles_manager.count_height_range = Math.ceil((arr_layer_level.height / arr_layer_level.tile_height) / 2) * 2;
		arr_tiles_manager.height_range_surplus = Math.floor(arr_layer_level.height % arr_layer_level.tile_height); // Height range (leftover)
		
		for (let num_layer = 0, num_length_layers = arr_layers.length; num_layer < num_length_layers; num_layer++) {
			
			const arr_layer = arr_layers[num_layer];
			const arr_layer_manager = arr_layers_manager[num_layer];
			
			// Clean all not fully loaded maps, keep loaded one, and add new
		
			if (!arr_layer_manager.elm) {

				arr_layer_manager.offset = arr_position.offset;
				arr_layer_manager.zoom = cur_zoom;
				
				if (arr_layer_manager.elm_loading) {
					arr_layer_manager.elm = arr_layer_manager.elm_loading;
				}
			} else {
				
				const arr_zoom_level = SELF.levelVars();		
				const arr_zoomed_level = SELF.levelVars(arr_layer_manager.zoom);				
				const num_calc_ratio = (arr_zoom_level.width / arr_zoomed_level.width);
				const num_calc_left = (arr_position.offset.x ? ((arr_layer_manager.offset.x / arr_zoomed_level.width) - (arr_position.offset.x / arr_zoom_level.width)) * arr_zoom_level.width : 0);
				const num_calc_top = (arr_position.offset.y ? ((arr_layer_manager.offset.y / arr_zoomed_level.height) - (arr_position.offset.y / arr_zoom_level.height)) * arr_zoom_level.height : 0);
				
				arr_layer_manager.elm.style.transformOrigin = arr_layer_manager.elm.style.webkitTransformOrigin = '0% 0% 0';
				arr_layer_manager.elm.style.transform = arr_layer_manager.elm.style.webkitTransform = 'translate('+num_calc_left+'px, '+num_calc_top+'px) scale('+num_calc_ratio+')';
				
				if (arr_layer_manager.elm_loading != arr_layer_manager.elm) {
					arr_layer_manager.elm_container.removeChild(arr_layer_manager.elm_loading);
				}
			}
			
			if (do_svg) {
				
				arr_layer_manager.elm_loading = document.createElementNS(stage_ns, 'svg');
				arr_layer_manager.elm_loading.setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', stage_ns);
				arr_layer_manager.elm_loading.style.width = arr_position.size.width+'px';
				arr_layer_manager.elm_loading.style.height = arr_position.size.height+'px';
				arr_layer_manager.elm_loading.setAttribute('viewBox', '0 0 '+(arr_position.size.width * num_layer_resolution)+' '+(arr_position.size.height * num_layer_resolution));
				
				const node_style = document.createTextNode(`
					svg { }
				`);
				const elm_style = document.createElementNS(stage_ns, 'style');
				elm_style.appendChild(node_style);
				
				arr_layer_manager.elm_loading.appendChild(elm_style);
			} else {
			
				arr_layer_manager.elm_loading = $('<div />')[0]; // New map content
			}

			arr_layer_manager.elm_container.appendChild(arr_layer_manager.elm_loading);
			
			if (!arr_layer_manager.tiles) {
				arr_layer_manager.tiles = {timer: false, loaded: {}, count_loading: false, url: '', func_get_subdomain: false, cache: {}};
			}
			
			if (arr_layer_manager.tiles.timer) {
				clearTimeout(arr_layer_manager.tiles.timer);
			}
			arr_layer_manager.tiles.timer = false;
			arr_layer_manager.tiles.loaded = {};
			arr_layer_manager.tiles.count_loading = false;
			arr_layer_manager.tiles.url = arr_layer.url;
			arr_layer_manager.tiles.func_get_subdomain = false;
			if (!arr_layer_manager.tiles.cache[arr_layer_level.level]) {
				arr_layer_manager.tiles.cache[arr_layer_level.level] = {};
			}

			const arr_layer_subdomains = arr_layer_manager.tiles.url.match(/\{s(?:=([^\}]*))?\}/);
			
			if (arr_layer_subdomains) {
				
				let arr_subdomains = [1,2,3];
				
				if (arr_layer_subdomains[1]) {
					
					arr_subdomains = arr_layer_subdomains[1].split(',');
					arr_layer_manager.tiles.url = arr_layer_manager.tiles.url.replace(arr_layer_subdomains[0], '{s}');
				}
			
				const num_length_subdomain = arr_subdomains.length;
				
				arr_layer_manager.tiles.func_get_subdomain = function(num_subdomain) {
					
					for (let i = 2; i <= num_length_subdomain; i++) { // Start with second array value, because division by one is always true
					
						if (num_subdomain % i == 0) {
							return arr_subdomains[i-1];
						}
					}
					
					return arr_subdomains[1-1];
				};
			}
		}
		
		// Remove previous map when enough tiles are loaded

		if (interval_layers) {
			clearInterval(interval_layers);
			interval_layers = false;
		}
		
		interval_layers = setInterval(function() {
			
			let is_done = true;
			
			for (let num_layer = 0, num_length_layers = arr_layers.length; num_layer < num_length_layers; num_layer++) {
				
				const arr_layer_manager = arr_layers_manager[num_layer];
				
				if (arr_layer_manager.tiles.timer) {
					continue;
				}
				
				if (arr_layer_manager.tiles.count_loading === false || (arr_layer_manager.tiles.count_loading / arr_tiles_manager.total) > 0.1) {
					
					is_done = false;
					continue;
				}

				arr_layer_manager.tiles.timer = setTimeout(function() {
					
					if (arr_layer_manager.elm) {
						arr_layer_manager.elm_container.removeChild(arr_layer_manager.elm);
					}
					arr_layer_manager.elm = arr_layer_manager.elm_loading;
					
					arr_layer_manager.offset = arr_position.offset;
					arr_layer_manager.zoom = cur_zoom;
				}, timeout_tile_animation);
			}
			
			if (!is_done) {
				return;
			}
			
			clearInterval(interval_layers);
			interval_layers = false;
		}, 200);
					
		drawTiles();
	};
	
	var startTiling = function() {
		
		if (!arr_layers.length) {
			return;
		}
		
		stopTiling();
		
		interval_tiling = setInterval(function() {
			drawTiles();
		}, 300);
	};
	
	var stopTiling = function() {
		
		if (interval_tiling) {
			
			drawTiles();
			clearInterval(interval_tiling);
			interval_tiling = false;
		}
	};
		
	var drawTiles = function() {
		
		const arr_position = SELF.getPosition();
		
		const num_pos_x_elm = ((arr_position.x + (arr_position.view.width / 2)) * arr_layer_level.resolution); // Correct x from center to left
		const num_pos_y_elm = ((arr_position.y + (arr_position.view.height / 2)) * arr_layer_level.resolution); // Correct y from center to top
					
		const num_y_start = Math.max(Math.floor(-num_pos_y_elm / arr_layer_level.tile_height) + 1-arr_tiles_manager.extra, 0);
		let num_height_start = 0;
		if (num_y_start > 0) {
			num_height_start = (arr_tiles_manager.height_range_surplus ? (arr_layer_level.tile_height - arr_tiles_manager.height_range_surplus) : arr_layer_level.tile_height);
			if (num_y_start > 1) {
				num_height_start += ((num_y_start-1) * arr_layer_level.tile_height);
			}
		}
		const num_y_end = Math.min(Math.ceil((-num_pos_y_elm + (arr_position.view.height * arr_layer_level.resolution)) / arr_layer_level.tile_height) + 1+arr_tiles_manager.extra, arr_tiles_manager.count_height_range);
		const num_y_max = (arr_tiles_manager.count_height_range - 1);
		
		const num_x_start = Math.max(Math.floor(-num_pos_x_elm / arr_layer_level.tile_width) + 1-arr_tiles_manager.extra, 0);
		let num_width_start = 0;
		if (num_x_start > 0) {
			num_width_start = (arr_tiles_manager.width_range_surplus ? (arr_layer_level.tile_width - arr_tiles_manager.width_range_surplus) : arr_layer_level.tile_width);
			if (num_x_start > 1) {
				num_width_start += ((num_x_start-1) * arr_layer_level.tile_width);
			}
		}
		const num_x_end = Math.min(Math.ceil((-num_pos_x_elm + (arr_position.view.width * arr_layer_level.resolution)) / arr_layer_level.tile_width) + 1+arr_tiles_manager.extra, arr_tiles_manager.count_width_range);

		const num_offset_x = arr_layer_level.offset.x;
		const num_offset_y = arr_layer_level.offset.y;

		if (arr_tiles_manager.total === false) {
			arr_tiles_manager.total = (num_x_start-num_x_end) * (num_y_start-num_y_end);
		}
		
		const num_level = arr_layer_level.level;

		for (let num_layer = 0, num_length_layers = arr_layers.length; num_layer < num_length_layers; num_layer++) {
			
			const arr_layer = arr_layers[num_layer];
			
			if (!arr_layer.opacity) {
				continue;
			}
			
			const arr_layer_manager = arr_layers_manager[num_layer];
			const elm_layer = arr_layer_manager.elm_loading;

			if (arr_layer_manager.tiles.count_loading === false) {
				arr_layer_manager.tiles.count_loading = arr_tiles_manager.total;
			}
			
			const str_layer_url = arr_layer_manager.tiles.url
				.replace('{width}', arr_layer_level.tile_width)
				.replace('{height}', arr_layer_level.tile_height);
			const has_layer_url_bbox = str_layer_url.includes('{bbox}');
			
			const arr_tiles_active = {};
			const fragment = document.createDocumentFragment();
						
			let num_cur_height = num_height_start;

			for (let num_y = num_y_start; num_y < num_y_end; num_y++) {
				
				let num_height = null;
				if (arr_tiles_manager.height_range_surplus && (num_y == 0 || num_y == num_y_max)) {
					num_height = (x == 0 ? arr_layer_level.tile_height - arr_tiles_manager.height_range_surplus : arr_tiles_manager.height_range_surplus);
				} else {
					num_height = arr_layer_level.tile_height;
				}
				
				let num_cur_width = num_width_start;
							
				for (let num_x = num_x_start; num_x < num_x_end; num_x++) {
					
					let num_width = null;
					let num_width_mask = null;
					let num_offset_x_extra = 0;
					if (arr_tiles_manager.width_range_surplus && (num_x == 0 || num_x == arr_tiles_manager.count_width_range-1)) {
						num_width = (num_x == 0 ? arr_layer_level.tile_width - arr_tiles_manager.width_range_surplus : arr_tiles_manager.width_range_surplus);
						num_width_mask = arr_layer_level.tile_width - num_width;
					} else {
						num_width = arr_layer_level.tile_width;
						num_width_mask = 0;
					}
					
					if (do_svg && num_width_mask && num_x == 0) { // Add extra offset for clipped svg images
						num_offset_x_extra = -num_width_mask;
					}
					
					const str_identifier = num_x+'_'+num_y;
					arr_tiles_active[str_identifier] = true;
					
					if (!arr_layer_manager.tiles.loaded[str_identifier]) {
						
						let elm_container_image = arr_layer_manager.tiles.cache[num_level][str_identifier];
						
						if (!elm_container_image) {

							let num_x_get = num_x;
							if (arr_tiles_manager.count_width_range_origin !== false) {
								
								num_x_get = num_x_get + arr_tiles_manager.count_width_range_origin;
								const num_count_width_range_real = arr_tiles_manager.count_width_range - 1;
								
								if (num_x_get >= num_count_width_range_real) {
									num_x_get = num_x_get - num_count_width_range_real;
								} else if (num_x_get < 0) {
									num_x_get = num_x_get + num_count_width_range_real;
								}
							}
							
							let str_tile_url = str_layer_url
								.replace('{z}', num_level)
								.replace('{x}', num_x_get)
								.replace('{y}', num_y)
								.replace('{-y}', (num_y_max - num_y));
							
							if (arr_layer_manager.tiles.func_get_subdomain) {
								str_tile_url = str_tile_url.replace('{s}', arr_layer_manager.tiles.func_get_subdomain(num_x));
							}
							if (has_layer_url_bbox) {
								str_tile_url = str_tile_url.replace('{bbox}', getTileBBox(num_x_get, num_y).join(','));
							}
							
							elm_container_image = null;
							let elm_image = null;
							
							if (num_width_mask) {
								
								if (do_svg) {
									
									elm_container_image = document.createElementNS(stage_ns, 'g');
									elm_container_image.classList.add('img');
									elm_image = document.createElementNS(stage_ns, 'image');
									elm_container_image.appendChild(elm_image);
							
									elm_image.setAttribute('width', (num_width + num_width_mask + num_alpha_blending));
									elm_image.setAttribute('height', (num_height + num_alpha_blending));
									
									if (num_x == 0) {
										elm_container_image.style['clip-path'] = 'rect(0 '+(num_width + num_width_mask + num_alpha_blending)+'px '+(num_height + num_alpha_blending)+'px '+(num_width_mask + num_alpha_blending)+'px)';
									} else {
										elm_container_image.style['clip-path'] = 'rect(0 '+(num_width + num_alpha_blending)+'px '+(num_height + num_alpha_blending)+'px 0)';
									}
								} else {

									elm_container_image = document.createElement('div');
									elm_container_image.classList.add('img');
									elm_image = document.createElement('img');
									elm_container_image.appendChild(elm_image);
									
									elm_image.style.width = (num_width + num_width_mask)+'px';
									elm_image.style.height = num_height+'px';
									if (num_x == 0) {
										elm_image.style.right = '0px';
									} else {
										elm_image.style.left = '0px';
									}
									
									elm_container_image.style.width = num_width+'px';
									elm_container_image.style.height = num_height+'px';
								}
							} else {
								
								if (do_svg) {
									
									elm_container_image = document.createElementNS(stage_ns, 'image');
									
									elm_container_image.setAttribute('width', (num_width + num_alpha_blending));
									elm_container_image.setAttribute('height', (num_height + num_alpha_blending));
								} else {
									
									elm_container_image = document.createElement('img');
									
									elm_container_image.style.width = num_width+'px';
									elm_container_image.style.height = num_height+'px';
								}
								
								elm_image = elm_container_image;
							}

							elm_container_image.style.opacity = 0;
							elm_container_image.style.transform = 'translate('+(num_cur_width - num_offset_x + num_offset_x_extra)+'px, '+(num_cur_height - num_offset_y)+'px)';
							
							if (do_svg) {
								elm_image.setAttribute('href', str_tile_url);
							} else {
								elm_image.src = str_tile_url;
								elm_image.referrerPolicy = 'no-referrer';
							}

							let func_ready_image = null;
							
							const func_load_image = function() {
								
								const do_continue = func_ready_image(elm_container_image);
								
								if (!do_continue) {
									return;
								}
								
								new TWEEN.Tween({opacity: 0})
									.to({opacity: 1}, timeout_tile_animation)
									.easing(TWEEN.Easing.Cubic.Out)
									.onUpdate(function(arr) {
										elm_container_image.style.opacity = arr.opacity;
									})
								.start();
								
								ANIMATOR.trigger();
							};
							const func_error_image = function() {
								
								const do_continue = func_ready_image(true); // No valid tile could be loaded
								
								if (elm_container_image.parentNode) { // Could have been removed or re-appended to a new layer
									elm_container_image.parentNode.removeChild(elm_container_image);
								}
								
								if (!do_continue) {
									return;
								}
								
								arr_layer_manager.tiles.loaded[str_identifier] = false;
							};
							
							func_ready_image = function(cache) {
								
								elm_image.removeEventListener('load', func_load_image);
								elm_image.removeEventListener('error', func_error_image);

								arr_layer_manager.tiles.cache[num_level][str_identifier] = cache;
								
								if (elm_layer != arr_layer_manager.elm_loading) { // Check if tile is still part of the active map content
									return false;
								}
								
								arr_layer_manager.tiles.count_loading--;
								
								return true;
							};
							
							elm_image.addEventListener('load', func_load_image);
							elm_image.addEventListener('error', func_error_image);						
							
							fragment.appendChild(elm_container_image);
						} else {
							
							if (elm_container_image !== true) {
		
								elm_container_image.style.opacity = 1;
								elm_container_image.style.transform = 'translate('+(num_cur_width - num_offset_x + num_offset_x_extra)+'px, '+(num_cur_height - num_offset_y)+'px)';
								
								fragment.appendChild(elm_container_image);
							}

							arr_layer_manager.tiles.count_loading--;
						}
						
						arr_layer_manager.tiles.loaded[str_identifier] = elm_container_image;
					}
					
					num_cur_width = num_cur_width + num_width;
				}
				
				num_cur_height = num_cur_height + num_height;
			}
		
			for (const str_identifier in arr_layer_manager.tiles.loaded) {
				
				const elm_container_image = arr_layer_manager.tiles.loaded[str_identifier];
				
				if (arr_tiles_active[str_identifier] === true || elm_container_image === false) {
					continue;
				}
				
				if (elm_container_image !== true) {
					elm_layer.removeChild(elm_container_image);
				}
				arr_layer_manager.tiles.loaded[str_identifier] = false;
				arr_layer_manager.tiles.count_loading--;
			}
			
			elm_layer.appendChild(fragment);
		}
	}
	
	var getTileBBox = function(x, y) {
				
		const pos_start = SELF.convertPointToEPSG3857(SELF.getPoint(arr_layer_level.tile_width * x, arr_layer_level.tile_height * y, arr_layer_level.level, true));
		const pos_end = SELF.convertPointToEPSG3857(SELF.getPoint(arr_layer_level.tile_width * (x + 1), arr_layer_level.tile_height * (y + 1), arr_layer_level.level, true));
		
		return [
			pos_start[0],
			pos_end[1],
			pos_end[0],
			pos_start[1]
		];
	}
	
	this.plotPoint = function(latitude, longitude, zoom, has_origin) {
		
		// Mercator projection
		
		var longitude = (!has_origin ? SELF.parseLongitude(longitude) : longitude);
		
		var max_latitude = 85.0511287798;
		var latitude = Math.max(Math.min(max_latitude, (!has_origin ? SELF.parseLatitude(latitude) : latitude)), -max_latitude);
		latitude = latitude * Math.PI / 180; // Convert from degrees to radians
		
		// Scale and shift from center earth
		var x = (SELF.levelVars(zoom).width * (180 + longitude) / 360);

		var y = Math.log(Math.tan((latitude/2) + (Math.PI/4))); // Do the Mercator projection (w/ equator of 2pi units)

		y = (SELF.levelVars(zoom).height / 2) - (SELF.levelVars(zoom).height * y / (2 * Math.PI)); // Fit it to our map

		return {x: x, y: y};
	};

	this.parseLatitude = function(latitude, has_origin) {
		
		var latitude = (!has_origin ? parseFloat(latitude) - pos_origin.latitude : latitude);
		latitude = (latitude > 90 ? latitude - 180 : (latitude < -90 ? latitude + 180 : latitude));
		
		return latitude;
	};
	
	this.parseLongitude = function(longitude, has_origin) {
		
		var longitude = (!has_origin ? parseFloat(longitude) - pos_origin.longitude : longitude);
		longitude = (longitude > 180 ? longitude - 360 : (longitude < -180 ? longitude + 360 : longitude));
		
		return longitude;
	};
	
	this.getPoint = function(x, y, zoom, has_origin) {

		const longitude = ((360*x-180*SELF.levelVars(zoom).width) / SELF.levelVars(zoom).width) + (!has_origin ? pos_origin.longitude : 0);

		let latitude = -(2*Math.PI*y -Math.PI*SELF.levelVars(zoom).height)/SELF.levelVars(zoom).height;
		latitude = (4*Math.atan(Math.pow(Math.E,latitude))-Math.PI)/2;
		latitude = 180*latitude/Math.PI;

		return {latitude: latitude, longitude: longitude};
	};
	
	this.convertPointToEPSG3857 = function(pos_point) { // WGS84/EPSG:4326 GPS/pixels to meters EPSG:3857
				
		const x = (pos_point.longitude * 20037508.34) / 180;
		
		let y = Math.log(Math.tan(((90 + pos_point.latitude) * Math.PI) / 360)) / (Math.PI / 180);
		y = (y * 20037508.34) / 180;
		
		return [x, y];
	};
	
	this.getBoundsZoom = function(bounds) {

		var zoom_found = false;
		var zoom = settings.arr_levels.length;

		while (!zoom_found && zoom > 1) {
		
			if (bounds.north_east) {
				
				var xy_north_east = SELF.plotPoint(bounds.north_east.latitude, bounds.north_east.longitude, zoom);
				var xy_south_west = SELF.plotPoint(bounds.south_west.latitude, bounds.south_west.longitude, zoom);
				var wh = {width: Math.abs(xy_north_east.x - xy_south_west.x), height: Math.abs(xy_south_west.y - xy_north_east.y)};
			} else {
				
				var wh = {width: Math.abs(bounds.top_right.x - bounds.bottom_left.x)*SELF.levelVars(zoom).width, height: Math.abs(bounds.bottom_left.y - bounds.top_right.y)*SELF.levelVars(zoom).height};
			}
			
			if (wh.width <= pos_elm.frame.width && wh.height <= pos_elm.frame.height) {
				zoom_found = true;
			} else {
				zoom--;
			}
		};

		return zoom;
	};
	
	this.levelVars = function(num_zoom) {
		
		const num_index = (num_zoom != null ? num_zoom : cur_zoom)-1;
		const arr_level = arr_levels[num_index];
		
		if (arr_level !== undefined && arr_level.auto) {
			
			arr_level.width = pos_elm.width;
			arr_level.height = pos_elm.height;
		}
		
		return arr_level;
	};
	
	this.getZoom = function() {
		return cur_zoom;
	};
	
	this.getPaint = function() {
		return elm_paint;
	};
}
