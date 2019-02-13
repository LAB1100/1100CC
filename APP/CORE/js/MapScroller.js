
/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

function MapScroller(element, options) {

	var elm = $(element),
	obj = this,
	settings = $.extend({
		default_zoom: 4,
		default_center: {x: 0.5, y: 0.5}, // xy percentage or latlong
		origin: {x: 0, y: 0, latitude: 0, longitude: 0}, // xy coorditnates or latlong
		center_pointer: true,
		arr_levels: [],
		pos_view_frame: {top: 0, right: 0, bottom: 0, left: 0}, // pixels or percentage
		centered_level_tiles: true,
		show_zoom_levels: true,
		attribution: '',
		tile_path: '{s}.domain.tld/tile-{z}-{x}-{y}.png',
		tile_subdomain_range: [1,2,3], // e.g. a,b,c - 1,2,3
		background_path: '',
		background_color: false,
		allow_sizing: false
	}, options || {});
	
	var cur_zoom = false,
	elm_con = false,
	elm_background = false,
	elm_map = false,
	elm_paint = false,
	elm_controls = false,
	elm_zoomer = false,
	level_settings = [],
	pos_elm = {width: 0, height: 0, x: 0, y: 0, frame: {width: 0, height: 0, top: 0, right: 0, bottom: 0, left: 0}},
	pos_offset = {},
	pos_center = {},
	pos_zoom = false,
	pos_view_frame = {},
	obj_elm_map_content = {},
	obj_tiles = {},
	interval_tiles = false,
	timer_tiles = false,
	interval_tiling = false,
	timeout_tile_animation = 100,
	arr_move = [],
	resize_sensor = false;
			
	this.init = function() {
		
		if (!elm.hasClass('mapscroller')) {
			elm.addClass('mapscroller');
		}

		drawContainer();
					
		drawControls();
					
		for (var i = 0; i < settings.arr_levels.length; i++) {
			obj.addZoom(settings.arr_levels[i]);
		}
					
		obj.setPosition(0, 0);
		
		obj.setZoom(settings.default_zoom, settings.default_center, settings.pos_view_frame);
		
		addListeners();
	};
	
	this.close = function() {
		
		elm.off();
		elm.empty();
		
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
		
		if (settings.attribution) {
			$('<span class="attribution body">'+settings.attribution+'</span>').appendTo(elm_controls);
		}
		
		elm_zoomer = $('<div class="zoomer"><span class="plus"></span><span class="min"></span></div>').appendTo(elm_controls);
		elm_zoomer.children('span:first').on('click', function() {
			obj.setZoom(cur_zoom+1);
		});
		elm_zoomer.children('span:last').on('click', function() {
			obj.setZoom(cur_zoom-1);
		});
	};
	
	var addListeners = function() {
		
		var in_touch = false;
		var pinch = false;
		var has_moved = false;
		
		elm_con.on('mousedown.scroller touchstart.scroller', function(e) {

			if (POSITION.isTouch()) {
				
				pinch = POSITION.getPinch(e);
				
				if (in_touch) { // Touchstart can be triggered multiple times
					return;
				}
				
				in_touch = true;
			}
			
			e.preventDefault(); // Prevent document drag, and on touchstart do not trigger mousedown and zoom
			
			elm[0].closest('[tabindex]').focus();
		
			var elm_target = $(e.target);
			var pos_mouse = POSITION.getMouseXY(e.originalEvent);
			has_moved = false;

			startTiling();
			doMove(true);

			$(document).on('mousemove.scroller touchmove.scroller', function(e2) {
				
				e2.preventDefault(); // Prevent document drag, and on touchstart do not trigger mousemove and zoom
				
				if (pinch !== false) {
					
					var pinch2 = POSITION.getPinch(e2.originalEvent);
					var difference = pinch2 - pinch;

					var pos_xy = obj.getMousePositionToCenter();
					
					if (difference > 100) {
						
						obj.setZoom(cur_zoom+1, pos_xy);
						pinch = pinch2;
					} else if (difference < -100) {
						
						obj.setZoom(cur_zoom-1, pos_xy);
						pinch = pinch2;
					}
				}
				
				var cur_pos_mouse = POSITION.getMouseXY(e2.originalEvent);
				
				if (!has_moved && (cur_pos_mouse.x != pos_mouse.x || cur_pos_mouse.y != pos_mouse.y)) { // Check for real movement because of chrome 'always trigger move'-bug
					
					SCRIPTER.triggerEvent(elm, 'movingstart');
					elm.addClass('moving');
					has_moved = true;
				}
				
				obj.setPosition((cur_pos_mouse.x - pos_mouse.x), (cur_pos_mouse.y - pos_mouse.y), true);
				
				pos_mouse.x = cur_pos_mouse.x;
				pos_mouse.y = cur_pos_mouse.y;
			}).one('mouseup.scroller touchend.scroller', function(e2) {
				
				if (has_moved) {
					SCRIPTER.triggerEvent(elm, 'movingstop');
					elm.removeClass('moving');
				} else {
					if (in_touch) {
						SCRIPTER.triggerEvent(elm_target, 'click');
					}
				}
				
				doMove(false);
				stopTiling();
				
				in_touch = false;

				$(document).off('mousemove.scroller touchmove.scroller mouseup.scroller touchend.scroller');
			});
		})
		.on('click', function(e) {
			
			if (has_moved) { // Allow click or not
				e.stopPropagation();
			}
		})
		.on('mousewheel', function(e, d) {
			
			if (settings.center_pointer) {

				var pos_xy = obj.getMousePositionToCenter();
			} else {
				
				var pos_xy = false;
			}
			
			if (d > 0) {
				obj.setZoom(cur_zoom+1, pos_xy);
			} else {
				obj.setZoom(cur_zoom-1, pos_xy);
			}
			
			e.stopPropagation();
			e.preventDefault();
			
			elm[0].closest('[tabindex]').focus();
		})
		.on('dblclick', function(e) {
			
			var pos_xy = obj.getMousePositionToCenter();
			
			obj.setZoom(cur_zoom+1, pos_xy);
		});
		
		resize_sensor = new ResizeSensor(elm[0], function() {
			
			obj.setZoom(cur_zoom, false, pos_view_frame);
		});
	};
	
	var setWindow = function() {
		
		pos_elm.width = elm[0].clientWidth;
		pos_elm.height = elm[0].clientHeight;
		
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
			
	var addZoomerLevel = function() {
		
		$('<span />').insertAfter(elm_zoomer.children('.plus'))
		.on('click', function() {
			obj.setZoom(settings.arr_levels.length-$(this).index()+1);
		});
	};
	
	var setZoomerLevel = function() {
	
		elm_zoomer.children().removeClass('active');
		elm_zoomer.children('span:eq('+(level_settings.length - cur_zoom+1)+')').addClass('active');
	};
					
	this.addZoom = function(obj_level) {
	
		level_settings.push(obj_level);
		
		if (settings.show_zoom_levels) {
			addZoomerLevel();
		}
	};
	
	this.setZoom = function (zoom_new, pos_zoom_new, pos_view_frame_new) {
		
		if (pos_view_frame_new) {
			
			pos_view_frame = pos_view_frame_new;
		
			setWindow();
		}
		
		if (typeof zoom_new == 'object') {
			
			if (zoom_new.scale) {
				
				var pixels = (40075000 / zoom_new.scale); // In meters
				var zoom_new = 1;
				var cur_diff = Math.abs(pixels - level_settings[0].width);
				
				for (var i = 0; i < level_settings.length; i++) {
					var diff = Math.abs(pixels - level_settings[i].width);
					if (diff < cur_diff) {
						cur_diff = diff;
						zoom_new = i+1;
					}
				}
			} else {
				
				var zoom_check = obj.getBoundsZoom(zoom_new);
				
				if (zoom_new.min && zoom_check < zoom_new.min) {
					zoom_check = zoom_new.min;
				} else if (zoom_new.max && zoom_check > zoom_new.max) {
					zoom_check = zoom_new.max;
				}
				
				var zoom_new = zoom_check;
			}
		}

		if (zoom_new < 1 || zoom_new > level_settings.length) { // Zoom has reached maximum zoom, nothing to do
			
			return false;
		}

		pos_zoom = (pos_zoom_new ? pos_zoom_new : pos_zoom); // Reuse a possible earlier relative zoom position (in case of resizes)

		if (pos_zoom) {
			
			if (pos_zoom.latitude) {
				
				pos_center = obj.plotPoint(pos_zoom.latitude, pos_zoom.longitude, zoom_new);
			} else if (pos_zoom.north_east) {
				
				var xy_north_east = obj.plotPoint(pos_zoom.north_east.latitude, pos_zoom.north_east.longitude, zoom_new);
				var xy_south_west = obj.plotPoint(pos_zoom.south_west.latitude, pos_zoom.south_west.longitude, zoom_new);
				
				pos_center = {x: xy_south_west.x+((xy_north_east.x-xy_south_west.x)/2), y: xy_north_east.y+((xy_south_west.y-xy_north_east.y)/2)};
			} else {
				
				pos_center = {x: pos_zoom.x*obj.levelVars(zoom_new).width, y: pos_zoom.y*obj.levelVars(zoom_new).height};
			}
		} else {
			
			pos_center = {x: -pos_elm.x / obj.levelVars().width * obj.levelVars(zoom_new).width, y: -pos_elm.y / obj.levelVars().height * obj.levelVars(zoom_new).height};
		}
		
		if (pos_view_frame_new) {
			
			pos_center.x -= (pos_elm.frame.left - pos_elm.frame.right)/2;
			pos_center.y -= (pos_elm.frame.top - pos_elm.frame.bottom)/2;
		}
		
		var calc_zoom = (zoom_new > cur_zoom ? '+1' : (zoom_new < cur_zoom ? '-1' : false));
		cur_zoom = zoom_new;
		
		pos_offset = {x: false, y: false, width: false, height: false, sizing: {}};
		
		obj.setSize(obj.levelVars().width, obj.levelVars().height);
					
		obj.setPosition(-pos_center.x, -pos_center.y, false, calc_zoom);
		
		if (settings.tile_path) {	
			renewMap();
		}
		
		SCRIPTER.triggerEvent(elm, 'zoom', [zoom_new, calc_zoom]);		
		setZoomerLevel();
	};
	
	this.setSize = function(width, height) {
					
		if (settings.allow_sizing) {
			
			pos_offset.sizing.width = pos_elm.width*4;
			pos_offset.sizing.height = pos_elm.height*4;
			
			pos_offset.width = width - pos_offset.sizing.width;
			var width = pos_offset.sizing.width;

			pos_offset.height = height - pos_offset.sizing.height;
			var height = pos_offset.sizing.height;
		}
		
		elm_background[0].style.width = width+'px';
		elm_background[0].style.height = height+'px';
		elm_map[0].style.width = width+'px';
		elm_map[0].style.height = height+'px';
		elm_paint[0].style.width = width+'px';
		elm_paint[0].style.height = height+'px';
	};
	
	this.setPosition = function(x, y, relative, calc_zoom) {
		
		var x = Math.floor(x);
		var y = Math.floor(y);
		
		var obj_level = obj.levelVars();
		var width_view = Math.ceil(pos_elm.width/2);
		var height_view = Math.ceil(pos_elm.height/2);
		
		if (relative) {
			
			x = pos_elm.x + x;
			y = pos_elm.y + y;
		}
		
		if (cur_zoom && !obj_level.auto) { // Keep map in container
			
			if (obj_level.width > pos_elm.width) {
				if (x > -width_view) {
					x = -width_view;
				}
				if (x < -(obj_level.width - width_view)) {
					x = -(obj_level.width - width_view);
				}
			} else {
				if (x < -width_view) {
					x = -width_view;
				}
				if (x > (width_view - obj_level.width)) {
					x = (width_view - obj_level.width);
				}
			}
			if (obj_level.height > pos_elm.height) {
				if (y > -height_view) {
					y = -height_view;
				}
				if (y < -(obj_level.height - height_view)) {
					y = -(obj_level.height - height_view);
				}
			} else {
				if (y < -height_view) {
					y = -height_view;
				}
				if (y > (height_view - obj_level.height)) {
					y = (height_view - obj_level.height);
				}
			}
		}
		
		pos_elm.x = x;
		pos_elm.y = y;
		
		if (relative) {
			
			pos_zoom = false;
			
			if (pos_offset.x) {
				
				x = x + pos_offset.x;
			}
			if (pos_offset.y) {
				
				y = y + pos_offset.y;
			}
		} else {
			
			if (pos_offset.width) {
				
				var offset = Math.floor((obj_level.width - pos_offset.width)/2);
				pos_offset.x = -x - offset;
				x = -offset;
			}
			if (pos_offset.height) {
				
				var offset = Math.floor((obj_level.height - pos_offset.height)/2);
				pos_offset.y = -y - offset;
				y = -offset;
			}
		}
		
		doMove(null, calc_zoom);
		
		if (cur_zoom && !obj_level.auto) {
			
			x = x + width_view;
			y = y + height_view;
			
			var str = 'translate('+x+'px, '+y+'px)';
			
			elm_background[0].style.transform = elm_background[0].style.webkitTransform = str;
			elm_map[0].style.transform = elm_map[0].style.webkitTransform = str;
			elm_paint[0].style.transform = elm_paint[0].style.webkitTransform = str;
		}
	};
	
	this.getPosition = function() {
		
		return {x: pos_elm.x, y: pos_elm.y, origin: settings.origin, view: {width: pos_elm.width, height: pos_elm.height}, size: {width: (pos_offset.sizing.width ? pos_offset.sizing.width : obj.levelVars().width), height: (pos_offset.sizing.height ? pos_offset.sizing.height : obj.levelVars().height)}, offset: {x: (pos_offset.x ? pos_offset.x : 0), y: (pos_offset.y ? pos_offset.y : 0)}};
	};
	
	this.getMousePosition = function(test) {
		
		var test = (test == undefined || test ? true : false);
		
		if (test && !POSITION.testMouseOnElement(false, elm_paint[0])) {
			return false;
		}
		
		var pos_map = POSITION.getElementToDocument(elm_map[0]);
		
		return {x: (-pos_map.x + POSITION.mouse.x + (pos_offset.x ? pos_offset.x : 0)), y: (-pos_map.y + POSITION.mouse.y + (pos_offset.y ? pos_offset.y : 0))};
	};
	
	this.getMousePositionToCenter = function() {
		
		var pos_mouse = obj.getMousePosition(false);
		
		return {x: (pos_mouse.x - ((pos_mouse.x + pos_elm.x)/2)) / obj.levelVars().width, y: (pos_mouse.y - ((pos_mouse.y + pos_elm.y)/2)) / obj.levelVars().height};
	};
	
	this.move = function(call, key) {
		
		if (key === 0 || key > 0) {
			
			arr_move[key] = call;
		} else {
			
			for (var i = 0, len = arr_move.length; i <= len; i++) {
				
				if (arr_move[i] === null || arr_move[i] === undefined) {
					
					var key = i;
					arr_move[key] = call;
					break;
				}
			}
		}
		
		if (call) {
			call(false, obj.getPosition(), cur_zoom, cur_zoom);
		}
		
		return key;
	};
	
	var doMove = function(move, calc_zoom) {
				
		var len = arr_move.length;
		
		if (!len) {
			return;
		}
		
		var obj_move = obj.getPosition();
		
		for (var i = 0; i < len; i++) {
			if (arr_move[i]) {
				arr_move[i](move, obj_move, cur_zoom, calc_zoom);
			}
		}
	};
	
	var renewMap = function() {
		
		var obj_level = obj.levelVars();
		
		// Clean all not fully loaded maps, keep loaded one, and add new
		
		if (obj_elm_map_content.elm) {
							
			var calc_ratio = (obj_level.width / obj.levelVars(obj_elm_map_content.zoom).width);
			var calc_left = (pos_offset.x ? ((obj_elm_map_content.pos_offset.x / obj.levelVars(obj_elm_map_content.zoom).width) - (pos_offset.x / obj_level.width)) * obj_level.width : 0);
			var calc_top = (pos_offset.y ? ((obj_elm_map_content.pos_offset.y / obj.levelVars(obj_elm_map_content.zoom).height) - (pos_offset.y / obj_level.height)) * obj_level.height : 0);
			
			obj_elm_map_content.elm[0].style.transformOrigin = obj_elm_map_content.elm[0].style.webkitTransformOrigin = '0% 0% 0';
			obj_elm_map_content.elm[0].style.transform = obj_elm_map_content.elm[0].style.webkitTransform = 'translate('+calc_left+'px, '+calc_top+'px) scale('+calc_ratio+')';
		} else {
			
			obj_elm_map_content.elm = elm_map.children().first();
			obj_elm_map_content.elm = (obj_elm_map_content.elm.length ? obj_elm_map_content.elm : false);
			obj_elm_map_content.pos_offset = pos_offset;
			obj_elm_map_content.zoom = cur_zoom;
		}
		
		if (obj_elm_map_content.elm) {
			
			var elm_remove = obj_elm_map_content.elm.next()[0];
			if (elm_remove) {
				elm_map[0].removeChild(elm_remove);
			}
		}
		
		obj_elm_map_content.elm_loading = $('<div />').appendTo(elm_map); // New map content
					
		obj_tiles = {};
		obj_tiles.drawn = {};
		obj_tiles.total = false;
		obj_tiles.count_loading = false;
		obj_tiles.extra = 1;
		obj_tiles.wr = obj_level.width/obj_level.tile_width; // Width range (full)
		if (settings.centered_level_tiles) {
			obj_tiles.w = Math.ceil(obj_tiles.wr / 2) * 2; // Width range (rounded)
			obj_tiles.wd =  obj_level.tile_width-Math.round(((obj_tiles.w * obj_level.tile_width) - obj_level.width) / 2); // Width range (leftover)
		} else {
			obj_tiles.w = Math.ceil(obj_tiles.wr); // Width range (rounded)
			obj_tiles.wd =  obj_level.tile_width-Math.round((obj_tiles.w * obj_level.tile_width) - obj_level.width); // Width range (leftover)
		}
		
		obj_tiles.hr = obj_level.height/obj_level.tile_height;
		if (settings.centered_level_tiles) {
			obj_tiles.h = Math.ceil(obj_tiles.hr / 2) * 2;
			obj_tiles.hd =  obj_level.tile_height - Math.round(((obj_tiles.h * obj_level.tile_height) - obj_level.height) / 2);
		} else {
			obj_tiles.h = Math.ceil(obj_tiles.hr);
			obj_tiles.hd =  obj_level.tile_height - Math.round((obj_tiles.h * obj_level.tile_height) - obj_level.height);
		}
		
		var count_sub_domain = 0;
		var length_sub_domain = settings.tile_subdomain_range.length;
		
		obj_tiles.getSubDomain = function(x) {
			
			for (var i = 2; i <= length_sub_domain; i++) { // Start with second array value, because division by one is always true
			
				if (x % i == 0) {
					return i-1;
				}
			}
			
			return 1-1;
		};
		
		// Remove previous map when enough tiles are loaded
		
		if (timer_tiles) {
			clearTimeout(timer_tiles);
			timer_tiles = false;
		}
		if (interval_tiles) {
			clearInterval(interval_tiles);
			interval_tiles = false;
		}
		
		interval_tiles = setInterval(function() {
			
			if (obj_tiles.count_loading !== false && (obj_tiles.count_loading / obj_tiles.total) <= 0.1) {
				
				timer_tiles = setTimeout(function() {
					
					if (obj_elm_map_content.elm) {
						elm_map[0].removeChild(obj_elm_map_content.elm[0]);
					}
					obj_elm_map_content.elm = obj_elm_map_content.elm_loading;
					
					obj_elm_map_content.pos_offset = pos_offset;
					obj_elm_map_content.zoom = cur_zoom;
				}, timeout_tile_animation);
				
				clearInterval(interval_tiles);
				interval_tiles = false;
			}
		}, 200);
					
		drawTiles();
	};
	
	var startTiling = function() {
		
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
		
		var obj_level = obj.levelVars();
		
		var pos_x_elm = pos_elm.x + (pos_elm.width/2); // Correct x from center to left
		var pos_y_elm = pos_elm.y + (pos_elm.height/2); // Correct y from center to top
					
		if (settings.centered_level_tiles) {
			var y_start = Math.max(Math.floor((-pos_y_elm-obj_tiles.hd)/obj_level.tile_height)+1-obj_tiles.extra, 0);
			var height_start = (((y_start > 1 ? (y_start-1)*obj_level.tile_height : 0)+(y_start > 0 ? obj_tiles.hd : 0))/obj_level.height)*100;
			var y_end = Math.min(Math.ceil((-pos_y_elm-obj_tiles.hd+pos_elm.height)/obj_level.tile_height)+1+obj_tiles.extra, obj_tiles.h);
			var x_start = Math.max(Math.floor((-pos_x_elm-obj_tiles.wd)/obj_level.tile_width)+1-obj_tiles.extra, 0);
			var width_start = (((x_start > 1 ? (x_start-1)*obj_level.tile_width : 0)+(x_start > 0 ? obj_tiles.wd : 0))/obj_level.width)*100;
			var x_end = Math.min(Math.ceil((-pos_x_elm-obj_tiles.wd+pos_elm.width)/obj_level.tile_width)+1+obj_tiles.extra, obj_tiles.w);
		} else {
			var y_start = Math.max(Math.floor((-pos_y_elm)/obj_level.tile_height)-obj_tiles.extra, 0);
			var height_start = ((y_start*obj_level.tile_height)/obj_level.height)*100;
			var y_end = Math.min(Math.ceil((-pos_y_elm+pos_elm.height)/obj_level.tile_height)+obj_tiles.extra, obj_tiles.h);
			var x_start = Math.max(Math.floor(-pos_x_elm/obj_level.tile_width)-obj_tiles.extra, 0);
			var width_start = ((x_start*obj_level.tile_width)/obj_level.width)*100;
			var x_end = Math.min(Math.ceil(-pos_x_elm+pos_elm.width/obj_level.tile_width)+obj_tiles.extra, obj_tiles.w);
		}
		
		var offset_width = (pos_offset.width ? 1+(pos_offset.width/(obj_level.width-pos_offset.width)) : 1);
		var offset_height = (pos_offset.height ? 1+(pos_offset.height/(obj_level.height-pos_offset.height)) : 1);
		var offset_x = (pos_offset.x ? (pos_offset.x/obj_level.width)*100 : 0);
		var offset_y = (pos_offset.y ? (pos_offset.y/obj_level.height)*100 : 0);
		var size_width = (pos_offset.sizing.width ? pos_offset.sizing.width : obj_level.width);
		var size_height = (pos_offset.sizing.height ? pos_offset.sizing.height : obj_level.height);

		var cur_map = obj_elm_map_content.elm_loading;
		var arr_tiles_active = {};
		var cur_height = height_start;
		
		if (obj_tiles.total === false) {
			obj_tiles.total = (x_start-x_end)*(y_start-y_end);
			obj_tiles.count_loading = obj_tiles.total;
		}

		for (var y = y_start; y < y_end; y++) {
			
			if ((settings.centered_level_tiles && (y == 0 || y == obj_tiles.h-1)) || y == obj_tiles.h-1) {
				var height = (obj_tiles.hd/obj_level.height)*100;
			} else {
				var height = ((obj_level.tile_height)/obj_level.height)*100;
			}
			
			var cur_width = width_start;
						
			for (var x = x_start; x < x_end; x++) {
				
				if ((settings.centered_level_tiles && (x == 0 || x == obj_tiles.w-1)) || x == obj_tiles.w-1) {
					var width = (obj_tiles.wd/obj_level.width)*100;
				} else {
					var width = (obj_level.tile_width/obj_level.width)*100;
				}
				
				arr_tiles_active[x+'_'+y] = true;	
				if (!obj_tiles.drawn[x+'_'+y]) {
				
					var elm = $('<img />');
					obj_tiles.drawn[x+'_'+y] = elm;
					
					var tile_path = settings.tile_path.replace('{z}', cur_zoom).replace('{x}', x).replace('{y}', y).replace('{s}', settings.tile_subdomain_range[obj_tiles.getSubDomain(x)]);
					elm[0].setAttribute('src', tile_path);
					elm[0].setAttribute('style', 'opacity: 0; width:'+(width*offset_width)/100*size_width+'px; height:'+(height*offset_height)/100*size_height+'px; left: '+((cur_width-offset_x)*offset_width)/100*size_width+'px; top: '+((cur_height-offset_y)*offset_height)/100*size_height+'px;');
					
					elm.on('load', function(e) {
						
						if (cur_map[0] == obj_elm_map_content.elm_loading[0]) { // Check if tile is still part of the active map content
							
							var elm = this;
							
							new TWEEN.Tween({opacity: 0})
								.to({opacity: 1}, timeout_tile_animation)
								.easing(TWEEN.Easing.Cubic.Out)
								.onUpdate(function(arr) {
									elm.style.opacity = arr.opacity;
								})
							.start();
							
							ANIMATOR.trigger();
							
							obj_tiles.count_loading--;
						}
					});
					
					cur_map[0].appendChild(elm[0]);
				}
				
				cur_width = cur_width+width;
			}
			cur_height = cur_height+height;
		}
		
		for (var key in obj_tiles.drawn) {
			
			if (!arr_tiles_active[key] && obj_tiles.drawn[key]) {
				
				cur_map[0].removeChild(obj_tiles.drawn[key][0]);
				obj_tiles.drawn[key] = false;
				obj_tiles.count_loading--;
			}
		}
	}
	
	this.plotPoint = function(latitude, longitude, zoom) {
		
		// Mercator projection
		
		// Scale and shift from center earth
		var x = (obj.levelVars(zoom).width * (180 + parseFloat(longitude)) / 360);
		
		if (x > obj.levelVars(zoom).width) {
			x = x % obj.levelVars(zoom).width;
		} else if (x < 0) {
			x = obj.levelVars(zoom).width - (x % obj.levelVars(zoom).width);
		}
		
		var max_latitude = 85.0511287798;
		var latitude = Math.max(Math.min(max_latitude, latitude), -max_latitude);

		latitude = parseFloat(latitude) * Math.PI / 180; // Convert from degrees to radians

		var y = Math.log(Math.tan((latitude/2) + (Math.PI/4))); // Do the Mercator projection (w/ equator of 2pi units)

		y = (obj.levelVars(zoom).height / 2) - (obj.levelVars(zoom).height * y / (2 * Math.PI)); // Fit it to our map

		return {x: x, y: y};
	};
	
	this.getPoint = function(x, y, zoom) {

		var longitude = (360*x-180*obj.levelVars(zoom).width)/obj.levelVars(zoom).width;

		var latitude = -(2*Math.PI*y -Math.PI*obj.levelVars(zoom).height)/obj.levelVars(zoom).height;

		latitude = (4*Math.atan(Math.pow(Math.E,latitude))-Math.PI)/2;
		
		latitude = 180*latitude/Math.PI;

		return {latitude: latitude, longitude: longitude};
	};
	
	this.getBoundsZoom = function(bounds) {

		var zoom_found = false;
		var zoom = settings.arr_levels.length;

		while (!zoom_found && zoom > 1) {
		
			if (bounds.north_east) {
				
				var xy_north_east = this.plotPoint(bounds.north_east.latitude, bounds.north_east.longitude, zoom);
				var xy_south_west = this.plotPoint(bounds.south_west.latitude, bounds.south_west.longitude, zoom);
				var wh = {width: Math.abs(xy_north_east.x - xy_south_west.x), height: Math.abs(xy_south_west.y - xy_north_east.y)};
			} else {
				
				var wh = {width: Math.abs(bounds.top_right.x - bounds.bottom_left.x)*obj.levelVars(zoom).width, height: Math.abs(bounds.bottom_left.y - bounds.top_right.y)*obj.levelVars(zoom).height};
			}
			
			if (wh.width <= pos_elm.frame.width && wh.height <= pos_elm.frame.height) {
				zoom_found = true;
			} else {
				zoom--;
			}
		};

		return zoom;
	};
							
	this.levelVars = function(zoom) {
		
		var level = (typeof zoom != 'undefined' ? zoom : cur_zoom)-1;
		var obj_level = level_settings[level];
		
		if (obj_level && obj_level.auto) {
			
			obj_level.width = pos_elm.width;
			obj_level.height = pos_elm.height;
		}
		
		return obj_level;
	};
	
	this.getZoom = function() {
		return cur_zoom;
	};
	
	this.getPaint = function() {
		return elm_paint;
	};
	
}
