
/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

var PARSE = false;
var MESSAGEBOX = false;

document.addEventListener("DOMContentLoaded", function(e) {
	
	MESSAGEBOX = new MessageBox(document.body);
	
	if (PARSE) {
		PARSE();
		PARSE = false;
	}
	
	SCRIPTER.runStatic();
	
	SCRIPTER.triggerEvent(document, 'documentloaded', {elm: $(document.body)});
	SCRIPTER.triggerEvent(window, 'resize');
	
	document.body.setAttribute('tabindex', '0');
	
	// Autoplay video
	window.addEventListener('touchstart', function videoStart() {
		
		var elm_video = document.querySelectorAll('video[autoplay]');
		
		for (var i = 0, len = elm_video.length; i < len; i++) {
			elm_video[i].play();
		}
		
		this.removeEventListener('touchstart', videoStart);
	});
	
	var timer_resize = false;
	
	window.addEventListener('resize', function() {
		
		if (timer_resize) {
			return;
		}
		
		timer_resize = setTimeout(function() {
			
			timer_resize = false;
			
			// Overlay has its own resize functionalities
			if (IS_CMS) {
				var elm =  document.querySelector('#cms-body');
			} else {
				var elm =  document.querySelector('.container');
			}
			
			var elms_table = elm.querySelectorAll('table');
					
			for (var i = 0, len = elms_table.length; i < len; i++) {
			
				resizeDataTable(elms_table[i]);
			}
		}, 300);
	});
});

function getElement(elm) {
	
	if (elm instanceof Element) {
		return elm;
	} else if (elm instanceof Object) {
		return elm[0];
	}
	
	return false;
}

function runElementSelectorFunction(elm, selector, callback) {
	
	var elm = getElement(elm);
	
	if (elm.matches(selector)) {
		callback(elm);
	}
	
	var elms = elm.querySelectorAll(selector);

	for (var i = 0, len = elms.length; i < len; i++) {
		callback(elms[i]);
	}
}

function setElementData(elm, key, arr, overwrite) {
	
	var elm = getElement(elm);
	
	if (overwrite || !(arr instanceof Object)) {
		
		var arr_data = arr;
		
		$.data(elm, key, arr_data);
	} else {
		
		var arr_data = $.data(elm, key);
		
		if (!arr_data) {
			$.data(elm, key, {});
			arr_data = $.data(elm, key);
		}
		
		if (arr) {
			arr_data = $.extend(arr_data, arr);
		}
	}
	
	return arr_data;
}

function getElementData(elm, key) {
	
	var elm = getElement(elm);
	
	return $.data(elm, key);
}

function onStage(elm) {
	
	var elm = getElement(elm);
	
	return document.body.contains(elm);
}
function isHidden(elm) {
	
	var elm = getElement(elm);
	
	return elm.offsetParent === null;
}

function hasElement(con, elm, self) {
	
	var elm = getElement(elm);
	var con = getElement(con);
	
	var has = false;
	
	if (self) {
		has = (con === elm);
	}
	
	if (!has) {
		has = con.contains(elm);
	}
	
	return has;
}

function getContainer(elm) {
			
	var elm = $(elm).closest((IS_CMS ? '[id^="mod-"]' : '.mod')+', .overlay');
	
	if (elm.hasClass('overlay')) {
		var elm_target = elm.children('.dialog').children('.content');
	} else {
		var elm_target = elm;
	}
	
	return elm_target;
}
	
function getContainerToolbox(elm, create) {
	
	var elm = getContainer(elm);
	
	var elm_toolbox = elm.children('.toolbox');
	
	if ((create == undefined || create) && !elm_toolbox.length) {
						
		elm_toolbox = $('<div class="toolbox"></div>').prependTo(elm);
	}
	
	return elm_toolbox;
}

function getModID(elm) {
	
	var elm = getElement(elm);
	
	var elm_mod = elm.closest('div[id^="mod-"]');
	
	if (elm_mod) {
		
		var str_mod = elm_mod.getAttribute('id').split('-')[1];
		
		if (!IS_CMS) {
			str_mod = document.body.getAttribute('id').split('-')[1]+'-'+str_mod;
		}
	} else {
		
		var str_mod = getElementData(elm, 'mod');
			
		if (!str_mod) {
			
			var elm_overlay = $(elm).parentsUntil('body').last();
			
			if (elm_overlay.length) {
				str_mod = getElementData(elm_overlay, 'mod');
			}
		}
	}
	
	return str_mod;
}

function ElementObjectByParameters(object_class, object_target, arr_arguments) {
	
	var method_or_options = arr_arguments[0];
	
	if (method_or_options) {
		var arr_arguments_new = Array.prototype.slice.call(arr_arguments, 1);
	}
	
	this.run = function() {
		
		var obj_self = this[object_target];
		
		if (obj_self) {
			
			if (method_or_options && typeof method_or_options === 'string' && obj_self[method_or_options]) {
		
				var func_method = obj_self[method_or_options];
				
				func_method(...arr_arguments_new);
			}
			
			return;
		}
		
		new object_class(this, ...arr_arguments);
	};
}

String.prototype.hashCode = function() {
	
	var hash = 0, i, chr, len;
	
	if (this.length === 0) {
		return hash;
	}
	
	for (i = 0, len = this.length; i < len; i++) {
		chr = this.charCodeAt(i);
		hash = ((hash << 5) - hash) + chr;
		hash |= 0; // Convert to 32bit integer
	}
	
	return hash;
};

function serializeArrayByName(elms) {
	
	if (elms[0] == undefined) {
		var elms = [elms];
	}
	
	var elms_name = [];
	
	for (var count_i = 0, len_i = elms.length; count_i < len_i; count_i++) {
		
		var elm = elms[count_i];
		
		if (elm.matches('[type="button"]')) {
			
			elms_name.push({name: elm.name, value: elm.value, disabled: elm.disabled, type: ''});
		} else {

			if (elm.matches('[name]')) {
				elms_name.push(elm);
			}
			
			var elms_found = elm.querySelectorAll('[name]');
			
			for (var count_j = 0, len_j = elms_found.length; count_j < len_j; count_j++) {
				elms_name.push(elms_found[count_j]);
			}
		}
		var arr_output = {};
		var arr_count_key = {};

		for (var count_j = 0, len_j = elms_name.length; count_j < len_j; count_j++) {
			
			var elm_name = elms_name[count_j];
			
			if (!elm_name.name || elm_name.disabled || ((elm_name.type == 'checkbox' || elm_name.type == 'radio') && !elm_name.checked) || elm_name.type == 'button' || elm_name.type == 'submit') { // Only keep relevant input elements
				continue;
			}

			// Split up the names into array tiers
			var arr_parts_all = elm_name.name.split(/\]|\[/);
			
			// We need to remove any blank parts returned by the regex.
			var arr_parts = [];
			
			for (var count_k = 0, len_k = arr_parts_all.length; count_k < len_k; count_k++) {
				
				var key = arr_parts_all[count_k];
				
				if (key != '' || (key == '' && (arr_parts_all[count_k-1] == '' || count_k-1 == 0) && arr_parts_all[count_k+1] == '')) { // Preserve key[]... and ...[key][][key]
					arr_parts.push(key);
				}			
			}

			// Start ref out at the root of the output object
			var ref = arr_output;

			for (var count_k = 0, len_k = arr_parts.length; count_k < len_k; count_k++) {
				
				var key = arr_parts[count_k]; // Set key for ease of use.
				var is_value = (count_k == (len_k-1)); // If we're at the last part, the value comes from the element.

				if (is_value) {
					
					if (elm_name.nodeName == 'SELECT') {
						
						var index = elm_name.selectedIndex;
						var is_array = (elm_name.type == 'select-multiple');
						var value = (is_array ? [] : '');
						
						if (index >= 0) {
							
							for (var count_l = index, len_l = elm_name.options.length; count_l < len_l; count_l++) {

								var elm_option = elm_name.options[count_l];
								
								if (elm_option.selected && !elm_option.disabled && (elm_option.parentNode.nodeName != 'OPTGROUP' || !elm_option.parentNode.disabled)) {
									
									if (is_array) {
										value.push(elm_option.value);
									} else {
										value = elm_option.value;
										break;
									}
								}
							}
						}
					} else {
						
						var value = elm_name.value;
					}
				} else {
					
					var value = {};
				}

				if (!key) { // key[]... and ...[key][][key]
					
					var identifier = arr_parts.slice(0, count_k).join('|'); // Also account for [yada][ya][da][][yada][hmm]
					
					if (arr_count_key[identifier] >= 0) {
						arr_count_key[identifier]++;
					} else {
						arr_count_key[identifier] = 0;
					}
					
					key = arr_count_key[identifier];
				}

				// Extend output with our temp object at the depth specified by ref.
				if (!ref[key] || is_value) {
					ref[key] = value;
				}

				// Reassign ref to point to this tier, so the next loop can extend it.
				ref = ref[key];
			}
		}
	}

	return arr_output;
}

var counter_array_names = 0;

function replaceArrayInName(elms, num_limit) {
	
	if (typeof elms[0] == undefined) {
		var elms = [elms];
	}

	var codes = [];

	for (var count_i = 0, len_i = elms.length; count_i < len_i; count_i++) {
		
		var elm = elms[count_i];
		
		elms_name = [];
		
		if (elm.matches('[name]')) {
			elms_name.push(elm);
		}
		
		var elms_found = elm.querySelectorAll('[name]');
		
		for (var count_j = 0, len_j = elms_found.length; count_j < len_j; count_j++) {
			elms_name.push(elms_found[count_j]);
		}
		
		for (var count_j = 0, len_j = elms_name.length; count_j < len_j; count_j++) {
		
			var elm_name = elms_name[count_j];

			var name = elm_name.getAttribute('name');
			
			var num_found = name.match(/\[array_([^\]]*)\]/g);
			num_found = (num_found ? num_found.length : 0);
			
			if (!num_found) {
				return;
			}
			
			if (num_found && /\[\]$/.test(name)) { // Account for a final []
				num_found++;
			}

			var count = 0;
			
			name = name.replace(/\[array_([^\]]*)\]/g, function(match, m1) {

				if (num_limit && num_limit < (num_found - count)) {
					
					count++;
					return match;
				}

				if (!codes[m1]) {
					counter_array_names++;
					var code = 'js_'+counter_array_names;
					codes[m1] = code;
				}
				
				count++;
				return '[array_'+codes[m1]+']';
			});

			elm_name.setAttribute('name', name);
		}
	};
};

// GENERAL DOM

$(document).on('documentloaded ajaxloaded', function(e) {

	if (!getElement(e.detail.elm)) {
		return;
	}
	
	for (var i = 0, len = e.detail.elm.length; i < len; i++) {
			
		var elm = $(e.detail.elm[i]);
		
		runElementSelectorFunction(elm, '[data-hold="1"]', function(elm_found) {
			elm_found.removeAttribute('data-hold');
			SCRIPTER.triggerEvent(elm_found, 'contenthold');
		});

		runElementSelectorFunction(elm, 'form, [data-form="1"]', function(elm_form) {
			
			if (elm_form.dataset.form) {
				elm_form = elm_form.elm_form;
			}
			
			new FormManager(elm_form);
		});
		
		runElementSelectorFunction(elm, '.sorter', function(elm_found) {
			new Sorter(elm_found, {
				prepend : elm_found.classList.contains('reverse'),
				auto_add : elm_found.dataset.sorter_auto_add
			});
		});

		runElementSelectorFunction(elm, '.tabs', function(elm_found) {
			new NavigationTabs(elm_found);
		});
		
		runElementSelectorFunction(elm, 'input[type="range"]', function(elm_found) {

			if (elm_found.input_range) {
				return;
			}
			elm_found.input_range = true;
			
			var cur = $(elm_found);
			var elm_value = cur.next('input');
			cur.val(elm_value.val());
			cur.on('input', function() {
				elm_value[0].value = this.value;
			});
			elm_value.on('input', function() {
				cur[0].value = this.value;
			});
		});
		
		runElementSelectorFunction(elm, 'input.autocomplete', function(elm_found) {
			new AutoCompleter(elm_found, {
				multi: elm_found.classList.contains('multi'),
				delay: elm_found.dataset.delay
			});
		});
		runElementSelectorFunction(elm, '.filebrowse', function(elm_found) {
			new FileBrowse(elm_found);
		});
		
		runElementSelectorFunction(elm, 'iframe[src="javascript:\'\';"]', function(elm_found) {
			new IframeDynamic(elm_found);
		});
		
		runElementSelectorFunction(elm, '.album', function(elm_found) {
			new Album(elm_found, {lazyload: (elm_found.dataset.lazyload == '1' ? true : false)});
		});

		elm.find('input.multi.all').addBack('input.multi.all').closest('[id^=x\\\:]').data('options', {'remove': false});
		elm.find('input[type=button].data:not([tabindex])').addBack('input[type=button].data:not([tabindex])').attr('tabindex', '-1');
		elm.find('input[type=text].date, input[type=text].datepicker').addBack('input[type=text].date, input[type=text].datepicker').attr('placeholder', 'd-m-y');
		
		runElementSelectorFunction(elm, '[id^="d:"].display', function(elm_found) {
			new DataTable(elm_found);
		});
		runElementSelectorFunction(elm, 'table.list', function(elm_found) {
			tweakDataTable(elm_found);
		});
		
		runElementSelectorFunction(elm, 'form, [data-form="1"]', function(elm_form) {
			
			runElementSelectorFunction(elm_form, '[data-lock="1"]', function(elm_found) {

				LOCATION.lock(elm_found);
			});
		});
	}
});

$(document).on('click', '.hide-edit.hide + *', function() {
	
	var cur = $(this);
	var elm_target = cur.prev();
	
	elm_target.children().insertAfter(elm_target);
	elm_target.remove();
	cur.remove();
});

$(document).on('click', 'input[type=checkbox].multi.all', function() {
	
	var cur = $(this);
	var target = cur.closest('table').find('td:nth-child('+(cur.parent().index()+1)+')');
	var elm_multi = target.children('input[type=checkbox].multi');
	elm_multi.prop('checked', cur.is(':checked'));
	SCRIPTER.triggerEvent(elm_multi, 'change');
}).on('click', 'input[type=checkbox].multi', function() {
	
	var cur = $(this);
	var elm_command = cur.closest('table').find('th').eq(cur.parent().index());
	var elm_checked = cur.closest('table').find('input[type=checkbox].multi:checked').not('.all');
	
	elm_command[0].elm_checked = elm_checked;
	var multi_id = $.map(elm_checked, function(val, i) {
		return val.value;
	});
	COMMANDS.setID(elm_command, (multi_id.length ? multi_id : false));
}).on('newpage', 'table.display', function(e) {
	
	var cur = $(this);
	var target = cur.find('input[type=checkbox].multi.all');
	var elm_command = target.closest('[id^=x\\\:]');
	
	if (elm_command.length) {
		if (target.prop('checked')) {
			target.prop('checked', false);
			SCRIPTER.triggerEvent(target, 'change');
		}
		COMMANDS.setID(elm_command, false);
		var elm_checked = elm_command[0].elm_checked;
		if (!cur.is('[id^=d\\\:]') && elm_checked) {
			elm_checked.prop('checked', false);
			SCRIPTER.triggerEvent(elm_checked, 'change');
			elm_command[0].elm_checked = false;
		}
	}
}).on('click', 'input[data-href], button[data-href]', function(e) {
	
	var cur = $(this);
	var url = cur.attr('data-href');
	
	window.open(url, '_blank');
}).on('command', '[id^=y\\\:cms_general\\\:preview-]', function() {
	
	var elm_target = $(this).parent('menu').parent().find('textarea.editor');
	COMMANDS.setData(this, {html: elm_target[0].value, external: elm_target[0].classList.contains('external'), style: elm_target[0].getAttribute('data-style')});
});

// FUNCTIONALITY

function Scripter() {
	
	var obj = this;
	var observing = false;

	var obj_scripts_static = {};
	var obj_scripts_static_identifier = {};
	var obj_scripts_dynamic = {};
	var obj_scripts_dynamic_identifier = {};

	var obj_observer_settings = {childList: true, subtree: true};
	
	var add = function(obj_scripts, selector, call, options) {
		
		var arr_options = (options ? options : {});
		
		if (isIdentifier(selector)) {
			obj_scripts = (obj_scripts === obj_scripts_static_identifier ? obj_scripts_static_identifier : obj_scripts_dynamic_identifier);
		}
		
		if (!obj_scripts[selector]) {
			obj_scripts[selector] = [];
		}
		
		obj_scripts[selector].push({call: call, options: arr_options});
	};
	
	this.static = function(selector, call, arr_options) {
		
		add(obj_scripts_static, selector, call, arr_options);		
	};
	
	this.dynamic = function(selector, call, arr_options) {
		
		add(obj_scripts_dynamic, selector, call, arr_options);	
	};
	
	var isIdentifier = function(selector) {
		
		var char = selector.charAt(0);
				
		return !(char == '.' || char == '[' || char == '#' || selector.substring(0, 3) == 'div' || selector.substring(0, 4) == 'form');
	};
	
	this.runStatic = function() {
		
		for (var selector in obj_scripts_static) {
			
			var elm_find = document.querySelectorAll(selector);
			
			for (var i = 0, len = elm_find.length; i < len; i++) {
			
				var elm = elm_find[i];
				
				attachScripts(elm, selector, obj_scripts_static);
			}
		}
	};
	
	this.runDynamic = function(elm) {
		
		var elm_attach = elm;
		elm = elm[0];
		
		if (!elm || elm.scripter) {
			return;
		}
		
		elm.scripter = true;
		
		for (var selector in obj_scripts_dynamic) {
			
			if (!elm.matches(selector)) {
				continue;
			}
			
			attachScripts(elm, selector, obj_scripts_dynamic, elm_attach);
		}
		
		obj.triggerEvent(elm_attach, 'scripter', {elm: elm_attach}, {bubbles: false});
	};
		
	var attachScripts = function(elm, selector, obj_scripts, elm_attach) {
		
		var arr_selectors = obj_scripts[selector];
		
		if (!arr_selectors) {
			return;
		}
		
		for (var i = 0, len = arr_selectors.length; i < len; i++) {
			
			var obj_call = arr_selectors[i];
			
			if (!elm_attach) {
				var elm_attach = $(elm);
			}
			
			if (typeof obj_call.call == 'string') {
				
				var use_selector = obj_call.call;
				
				if (isIdentifier(use_selector)) {
					var use_obj_scripts = (obj_scripts === obj_scripts_static_identifier ? obj_scripts_static_identifier : obj_scripts_dynamic_identifier);
				} else {
					var use_obj_scripts = obj_scripts;
				}
								
				attachScripts(elm, use_selector, use_obj_scripts);
			} else {
				
				obj_call.call(elm_attach);
			}
		}
	};
	
	var arr_events_native = {click: true, focus: true, blur: true, submit: false, reset: false};
	var arr_events_trigger = {mouseup: MouseEvent, mousedown: MouseEvent, mousemove: MouseEvent, click: MouseEvent, keydown: KeyboardEvent, keyup: KeyboardEvent, keypress: KeyboardEvent};

	this.triggerEvent = function(elm, type, data, obj_event, event_class) {
		
		if (elm !== document && elm !== window) {
			
			var elm = getElement(elm);
			
			if (!elm) {
				return false;
			}
		}
		
		if (!event_class) {
			
			if ((arr_events_native[type] || (arr_events_native[type] === false && obj_event && obj_event.do_native)) && elm[type] != undefined) { // Do native action (if possible)
				
				elm[type]();
				return true;
			}
			
			var event_class = arr_events_trigger[type];
			
			if (!event_class) {
				if (data) {
					var event_class = CustomEvent;
				} else {
					var event_class = Event;
				}
			}
		}
		
		var obj_event = $.extend({
			bubbles: true,
			cancelable: true
		}, obj_event || {});
		
		if (data) {
			obj_event.detail = data;
		}

		var event = new event_class(type, obj_event);
		
		elm.dispatchEvent(event);
		
		return event;
	};
}
var SCRIPTER = new Scripter();

function Position() {
	
	var obj = this;
	
	this.mouse = {x: 0, y: 0};
	this.is_touch = false;
	this.event = false;
	
	this.getElementToDocument = function(elm) {

		var pos_elm = elm.getBoundingClientRect();
		pos_elm = {x: pos_elm.left + obj.scrollLeft(), y: pos_elm.top + obj.scrollTop()};
		
		return pos_elm;
	};
	
	this.getMouseToElement = function(e, elm) {
		
		var event = (e ? e : obj.event);
		var pos_elm = obj.getElementToDocument(elm);

		pos_elm.x = obj.getMouseX(event) - pos_elm.x;
		pos_elm.y = obj.getMouseY(event) - pos_elm.y;
		
		return pos_elm;
	};
	this.testMouseOnElement = function(e, elm) {
		
		var event = (e ? e : obj.event);
	
		return hasElement(elm, event.target, true);
	};

	this.getMouseX = function(e) {
		
		var event = (e ? e : obj.event);
		
		var cx = (event.pageX != undefined ? event.pageX : event.touches[0].pageX);

		return cx;
	};
	this.getMouseY = function(e) {
		
		var event = (e ? e : obj.event);
		
		var cy = (event.pageY != undefined ? event.pageY : event.touches[0].pageY);
		
		return cy;
	};
	this.getMouseXY = function(e) {
		
		var event = (e ? e : obj.event);
		
		return {x: obj.getMouseX(event), y: obj.getMouseY(event)}; 
	};
	this.isTouch = function() {
		
		return obj.is_touch;
	};
	this.getPinch = function(e) {
		
		if (e.touches == undefined || e.touches.length !== 2) {
			return false;
		}
		
		return Math.hypot(e.touches[0].pageX - e.touches[1].pageX, e.touches[0].pageY - e.touches[1].pageY);
	};
	
	this.scrollLeft = function() {
		
		return (document.documentElement.scrollLeft || document.body.scrollLeft);
	};
	this.scrollTop = function() {
		
		return (document.documentElement.scrollTop || document.body.scrollTop);
	};
	
	var in_touch = false;
	
	var func_move = function(e) {
		
		obj.event = e;

		obj.mouse.x = obj.getMouseX();
		obj.mouse.y = obj.getMouseY();
		
		if (e.type == 'touchstart' || e.type == 'touchmove') { // Touch events happen before mouse events; they can be caught
			in_touch = true;
			obj.is_touch = true;
		} else { // mousedown or mousemove
			if (!in_touch) {
				obj.is_touch = false;
			}
		}
	};

	var func_end = function(e) {

		in_touch = false;
	};
	
	var func_init = function(e) {
		
		func_move(e);
		
		document.removeEventListener('mouseover', func_init, true);
		document.removeEventListener('touchstart', func_init, true);
	};

	document.addEventListener('mouseover', func_init, true);
	document.addEventListener('touchstart', func_init, true);
	
	document.addEventListener('mousedown', func_move, true);
	document.addEventListener('mousemove', func_move, true);
	document.addEventListener('touchstart', func_move, true);
	document.addEventListener('touchmove', func_move, true);
	
	document.addEventListener('touchend', func_end, true);
	
	// Make sure there is at least one mouse event on page load
	
	document.addEventListener('eventinit', function initEvent(e) {
		
		func_move(e);
		
		document.removeEventListener('eventinit', initEvent, true);
	}, true);
	
	SCRIPTER.triggerEvent(document, 'eventinit', false, {
		bubbles: false,
		cancelable: false,
		view: window
	}, MouseEvent);
}
var POSITION = new Position();

function Tooltip() {
	
	var obj = this;
	var selector = '*[title]:not(iframe)';
	var elm_tooltip = $('<div class="tooltip mouse"></div>')[0];
	
	var pos_tooltip = false;
	var pos_source = false;
	
	var elm = false;
	var title = false;
	var is_static = false;
	var func_move = false;
	
	this.remove = function() {
		
		if (!elm) {
			return;
		}
		
		var cur_title = elm.getAttribute('title');
		
		if (!cur_title && cur_title != undefined) {
			elm.setAttribute('title', title);
		}
		
		if (!is_static) {
			elm.removeEventListener('mousemove', func_move, true);
			elm.removeEventListener('touchmove', func_move, true);
		}
		elm.removeEventListener('touchend', func_end, true);
		elm.removeEventListener('mouseout', func_end, true);
		
		elm = false;
			
		document.body.removeChild(elm_tooltip);
	};
	
	this.check = function() { // Check the current position/validity of the element
		
		var elm_hover = func_new();
		
		if (!elm_hover && !elm) {
			return;
		}
		
		if (elm_hover && elm === elm_hover) {
			
			if (is_static) { // Repostion, possibly moved
				
				pos_source = POSITION.getElementToDocument(elm);

				elm_tooltip.style.top = (pos_source.y - 4 - elm_tooltip.offsetHeight)+'px';
				elm_tooltip.style.left = pos_source.x+'px';
			}
			
			return;
		}
		
		obj.remove();
	};
	
	this.update = function() { // Check for a newly added title, or for changes to the title
		
		if (!elm) {
			
			elm = func_new();
			
			if (elm) {
				func_create();
			}
			
			return;
		}
		
		if (!elm.hasAttribute('title')) {

			obj.remove();
		} else {
			
			var cur_title = elm.getAttribute('title');
		
			if (cur_title) {
				
				title = cur_title;
				
				elm_tooltip.innerHTML = title;
				elm.setAttribute('title', '');
			}
		}
	};
		
	this.checkElement = function(elm_check, selector_check, func) {

		if (elm_check.tooltip_func_element) {
			
			elm_check.removeEventListener('mouseover', elm_check.tooltip_func_element, true);
		}
		
		elm_check.tooltip_func_element = function(e) {
			
			var elm_target = e.target;
			
			if (selector_check) {
				
				if (!elm_target.matches(selector_check)) {
					return;
				}
			}
			
			if (elm_target.tooltip_element_is_checked) {
				return;
			}
			
			elm_target.tooltip_element_is_checked = true;
			
			var title = func(elm_target);
			
			if (title === false) {
				return;
			}
									
			if (!title){
				elm_target.removeAttribute('title');
			} else {
				elm_target.setAttribute('title', title);
			}

			func_over(e); // Run the event over the tooltip listener itself
		};
		
		elm_check.addEventListener('mouseover', elm_check.tooltip_func_element, true);
	};
	
	this.recheckElement = function(elm_check, selector_check) {
	
		if (selector_check) {
			
			var elms_found = elm_check.querySelectorAll(selector_check);
		
			for (var i = 0, len = elms_found.length; i < len; i++) {
				
				elms_found[i].tooltip_element_is_checked = false;
			}
		} else {
			
			elm_check.tooltip_element_is_checked = false;
		}
	};
	
	var func_new = function(elm_use) {
		
		if (!elm_use) {
			
			var elm_use = document.elementFromPoint(POSITION.mouse.x - POSITION.scrollLeft(), POSITION.mouse.y - POSITION.scrollTop());
		}
		
		if (!elm_use || elm_use.nodeType != Node.ELEMENT_NODE) {
			return false;
		}
		
		var elm_hover = false;
		
		if (elm_use.matches(selector)) {
			
			elm_hover = elm_use;
		} else {
		
			elm_use = elm_use.closest(selector);
			
			if (elm_use) {
				elm_hover = elm_use;
			}
		}
		
		return elm_hover;
	};
	
	var func_create = function() {
				
		if (!elm) {
			return;
		}

		title = elm.getAttribute('title');
		is_static = elm.getAttribute('data-title_static');
		
		elm.setAttribute('title', '');
		
		elm_tooltip.innerHTML = title;
		document.body.appendChild(elm_tooltip);
		
		pos_tooltip = elm_tooltip.getBoundingClientRect();

		if (!is_static) {
			pos_source = POSITION.mouse;
		} else {
			pos_source = POSITION.getElementToDocument(elm);
		}
		
		if (is_static) {
			
			elm_tooltip.style.top = (pos_source.y - 4 - pos_tooltip.height)+'px';
			elm_tooltip.style.left = pos_source.x+'px';
		} else {
			
			var height = pos_source.y + 24 + pos_tooltip.height;
			var height_max = POSITION.scrollTop() + document.documentElement.clientHeight;
			var offset_y = (height > height_max ? -(height-height_max) : 0);
			
			elm_tooltip.style.top = (pos_source.y + 24 + offset_y)+'px';
			
			var offset_x = (pos_tooltip.width + pos_source.x + 15 > POSITION.scrollLeft() + document.documentElement.clientWidth ? -pos_tooltip.width - 10 : 15);

			elm_tooltip.style.left = (pos_source.x + offset_x)+'px';
		}

		if (!is_static) {
			
			func_move = function(e) {
				
				pos_source = POSITION.mouse;

				height = pos_source.y + 24 + pos_tooltip.height;
				height_max = POSITION.scrollTop() + document.documentElement.clientHeight;
				offset_y = (height > height_max ? -(height-height_max) : 0);
				
				elm_tooltip.style.top = (pos_source.y + 24 + offset_y)+'px';

				offset_x = (pos_tooltip.width + pos_source.x + 15 > POSITION.scrollLeft() + document.documentElement.clientWidth ? -pos_tooltip.width - 10 : 15);
				
				elm_tooltip.style.left = (pos_source.x + offset_x)+'px';
			};
				
			elm.addEventListener('mousemove', func_move, true);
			elm.addEventListener('touchmove', func_move, true);
		}
		
		elm.addEventListener('touchend', func_end, true);
		elm.addEventListener('mouseout', func_end, true);
	}

	var func_over = function(e) {
		
		if (POSITION.is_touch && e.type == 'mouseover') { // Prevent touch and triggered mouse events mixup
			return;
		}
				
		var elm_hover = func_new(e.target);
		
		if ((!elm_hover && !elm) || (elm_hover && elm === elm_hover)) {
			return;
		}
		
		obj.remove();
		
		if (elm_hover) {
				
			elm = elm_hover;
			
			func_create();
		}
	};
	
	var func_end = function(e) {
		
		if (e.type == 'mouseout' && e.relatedTarget) { // Check when it's a mouse event whether it has left the document (empty e.relatedTarget)
			return;
		}
		
		obj.remove();
	}
	
	document.addEventListener('mouseover', func_over, true);
	document.addEventListener('touchstart', func_over, true);
}
var TOOLTIP = new Tooltip();

function Keys() {
	
	var obj = this;
	var arr = [37,39,38,40]; // left, right, up, down
	var selector = 'input:not([type="hidden"]):not([type="radio"]):not([type="checkbox"]):not([tabindex="-1"]), textarea, .body-content';
	
	var elm = false;
	var key = false;
	var is_input = false;
	var value = '';
		
	var func_down = function(e) {
		
		key = e.keyCode;
		elm = e.target;
		if (elm.matches('.body-content')) {
			value = elm.textContent;
		} else {
			value = elm.value;
		}
		is_input = elm.matches('input:not([type="radio"]):not([type="checkbox"]), textarea, .body-content');
		
		if (key == 8 && !is_input) { // Prevent page back on backspace on non text inputs
			e.preventDefault();
		}
		
		if (!(elm.matches(selector) && arr.indexOf(key) != -1)) {
			
			elm = false;
			key = false;
			
			return;
		}
		
		if (
			((key == 38 || key == 40) && elm.matches('input')) // Prevent default input actions on fields like number or date (up/down)
		) {
			e.preventDefault();
		}
	};
	
	var func_up = function(e) {
		
		if (!elm || elm != e.target || key != e.keyCode) { // Could have been stoppropagated earlier
			return;
		}
		
		if (key == 37 || key == 39) { // Key left/right
			
			if (elm.matches('input:not([type="checkbox"]):not([type="radio"]), textarea, .body-content') && value) {
				return;
			}
		} else if (key == 38 || key == 40) { // Key up/down
			
			if ((elm.matches('input.autocomplete, textarea, .body-content') && value)) {
				return;
			}
		}
		
		var elm_con = $(elm).closest('ul > li');
		var elm_find = elm_con.parent('ul').parent('li');
		if (elm_find.parent().hasClass('sorter')) {
			elm_con = elm_find;
		}
		
		var func_filter = function() {
			
			var cur = this;
			
			if (!(cur.offsetWidth || cur.offsetHeight || cur.getClientRects().length)) { // Check visibility
				return false;
			}
			
			if (!cur.matches('*:enabled, *[contenteditable="true"]')) { // Check enabledness
				return false;
			}
			
			if ((cur.matches('textarea') && cur.value) || (cur.matches('.body-content') && cur.textContent)) { // Check if textarea/contenteditable has content
				return false;
			}
			
			return true;
		};
			
		if (key == 37 || key == 39) { // Key left/right

			var elm_focus = elm_con.find(selector).filter(func_filter);
			var index = elm_focus.index(elm)+(key == 37 ? -1 : 1);

			if (index < 0 || index > (elm_focus.length-1)) {
				return;
			}
			
			elm_focus = elm_focus.eq(index);
			
		} else if (key == 38 || key == 40) { // Key up/down

			var elm_con_focus = (key == 38 ? elm_con.prev() : elm_con.next());
			var elm_focus = elm_con_focus.find(selector).eq(elm_con.find(selector).index(elm)).filter(func_filter);			
			
			if (!elm_focus.length) {
				elm_con_focus = (key == 38 ? elm_con.prevAll() : elm_con.nextAll());
				elm_focus = elm_con_focus.find(selector).filter(func_filter);
				elm_focus = (key == 38 ? elm_focus.last() : elm_focus.first());
			}
			
			if (!elm_focus.length) {
				return;
			}
		}
		
		SCRIPTER.triggerEvent(elm_focus, 'focus');
	};
	
	document.addEventListener('keydown', func_down, true);
	document.addEventListener('keyup', func_up, true);
};
var KEYS = new Keys();

function Location() {
	
	var obj = this;
	
	var str_location_full = window.location.toString();
	var str_host = window.location.protocol+'//'+window.location.hostname;
	var str_location = str_location_full.replace(str_host, ''); // Make relative
	
	var cur_location = str_location;
	var changed = false;
	
	this.getUrl = function(purpose) {
	
		var purpose = (purpose ? purpose : false);
		
		if (IS_CMS) {
			
			if (purpose == 'command') {
				var url = '/commands';
			}		
		} else {
			
			var arr_path = cur_location.split('/');
			var arr = [];
			var page_name = false;
			
			for (var i = 0; i < arr_path.length; i++) {
				
				var page = arr_path[i].slice(-2);
				
				if (page == '.p' || page == '.l') { // Request originates from page
					page_name = arr_path[i].slice(0, -2);
					arr.push(page_name+(purpose == 'command' ? '.c' : '.p'));
				} else if (i == arr_path.length-1 && arr_path[i] && !page_name) { // Request originates from page (at url end)
					page_name = arr_path[i];
					arr.push(page_name+(purpose == 'command' ? '.c' : '.p'));
				} else {
					arr.push(arr_path[i]);
				}
			}
			
			var url = arr.join('/');
			
			if (purpose == 'command' && !page_name) { // Request originates from directory
				url = url+'commands.c';
			}
		}

		return url;
	};
	
	this.getHost = function() {
		
		return str_host;
	};
	
	this.getOriginalUrl = function(str_url) { // Parse a chached URL and return the original URL
	
		var match = str_url.match(/(.*)\/cache\/(?:[^\/]*\/){2}(.*)/i); // Match host and src
		
		if (match && match[2]) {
			return (match[1] ? match[1] : '')+Base64.decode(match[2]);
		} else {
			return str_url;
		}
	};
	
	this.reload = function(value, timeout) {
		
		obj.unlock(document.body);
		
		var elm_disable = document.querySelectorAll('input, select, textarea');

		for (var i = 0, len = elm_disable.length; i < len; i++) {
			elm_disable[i].disabled = 'disabled';
		}
		
		changed = true;
		
		var func = function() {
				
			if (value) {
				window.location.replace(value);
			} else {
				window.location.reload(false);
			}
		}
		
		if (timeout) {
			setTimeout(func, timeout);
		} else {
			func();
		}
	};
	
	this.push = function(value, persist) {
		
		if (value != cur_location) {
			
			window.history.pushState('', '', value);
			
			cur_location = value;
			if (persist) {
				str_location = cur_location;
			}
		}
	};
	
	this.replace = function(value, persist) {
		
		if (value != cur_location) {
			
			window.history.replaceState('', '', value);
			
			cur_location = value;
			if (persist) {
				str_location = cur_location;
			}
		}
	};
	
	this.open = function(value) {
		
		window.open(value, '_blank');
	};
	
	this.hasChanged = function() {
		
		return changed;
	};
	
	var elm_active = false;
	
	this.attach = function(elm, value, focus) {
		
		if (value) {
			elm.dataset.location = value;
			elm.setAttribute('tabindex', '0');
		} else {
			delete elm.dataset.location;
			elm.removeAttribute('tabindex');
		}
		
		if (focus) {
			
			target = (value ? elm : elm.closest('form, [tabindex]'));
			
			if (target) {
				if (document.activeElement == target) {
					elm_active = false;
					func_check();
				} else {
					SCRIPTER.triggerEvent(target, 'focus');
				}
			}
		}
	};

	var func_check = function(e) {

		if (document.activeElement && (document.activeElement !== elm_active)) {
			
			var elm_match = document.activeElement;
			elm_match = (elm_match.matches('[data-location]') ? elm_match : null) || elm_match.closest('[data-location]');

			if (elm_match) {
				
				if (elm_match !== elm_active) {
					
					elm_active = elm_match;
					
					obj.push(elm_match.dataset.location);
				}
			} else if (elm_active) {
					
				obj.push(str_location);
				elm_active = false;
			}
		}
	};
	
	document.addEventListener('focus', func_check, true);
	
	// Locking
	
	var locked_active = false;
	var str_locked_page = '';
	var str_locked_content = '';
	
	var func_locked = function (e) {
		
		if (!obj.checkLocked(document.body)) {
			return;
		}
		
		e.returnValue = str_locked_page;
		
		return str_locked_page;
	};
	
	var func_locked_leave = function (e) {
		
		func_leave(document.body, false);
	};
	
	var func_reload = function(e) {
		
		var elm_target = e.target;
		var is_mouse_primary = (e.which == 1); // Left mouse button
		
		if (!locked_active || elm_target.nodeName != 'A' || !is_mouse_primary) {
			return;
		}
		
		var url = elm_target.getAttribute('href');
		
		if (!url || url.charAt(0) == '#' || elm_target.target == '_blank') {
			return;
		}

		var locked = obj.checkLocked(document.body, function(locked) {
			
			if (locked == null) {
				return;
			}
			
			obj.unlock(document.body);
			obj.reload(url);
		});
		
		if (locked) {
			
			e.preventDefault();
		}
	};
	
	document.addEventListener('click', func_reload, true);
	
	this.lock = function(elm) {
		
		delete elm.dataset.lock;
		elm.dataset.locked = 1;
		elm.locked_identifier = JSON.stringify(serializeArrayByName(elm));
		
		if (!locked_active) {
			
			ASSETS.getLabels($(elm),
				['conf_locked_page', 'conf_locked_content'],
				function(data) {
					
					str_locked_page = data.conf_locked_page;
					str_locked_content = data.conf_locked_content;
					window.addEventListener('beforeunload', func_locked);
					window.addEventListener('unload', func_locked_leave);
				}
			);
			
			locked_active = true;
;		}
	};

	this.unlock = function(elm) {
		
		if (!locked_active) {
			return;
		}
		
		if (elm.matches('[data-locked]')) {
			
			delete elm.dataset.locked;
		}
		
		var elms_locked = elm.querySelectorAll('[data-locked]');
		
		for (var i = 0, len = elms_locked.length; i < len; i++) {

			delete elms_locked[i].dataset.locked;
		}
	};
	
	this.updateLocked = function(elm) {
		
		if (!locked_active) {
			return;
		}
		
		if (elm.matches('[data-locked]')) {
			
			elm.locked_identifier = JSON.stringify(serializeArrayByName(elm));
		}
		
		var elms_locked = elm.querySelectorAll('[data-locked]');
		
		for (var i = 0, len = elms_locked.length; i < len; i++) {

			var elm_locked = elms_locked[i];
			elm_locked.locked_identifier = JSON.stringify(serializeArrayByName(elm_locked));
		}
	};
	
	this.onLeave = function(elm, func, state) {
		
		// state: any, locked (false), unlocked (null)
		
		var state = (state ? state : 'any');
		
		if (!elm.arr_leave) {
			elm.arr_leave = [];
		}
		
		elm.arr_leave.push({func: func, state: state});
	};
	
	var arr_leave_run = [];
	
	var func_leave = function(elm, locked, callback) {
		
		arr_leave_run = [];
		
		if (elm.matches('[data-locked]')) {
			
			func_check_leave(elm, locked);
		}
		
		var elms_locked = elm.querySelectorAll('[data-locked]');
		
		for (var i = 0, len = elms_locked.length; i < len; i++) {
			
			func_check_leave(elms_locked[i], locked);
		}
		
		func_run_leave(elm, locked, callback);
	};
	
	var func_check_leave = function(elm, locked) {
		
		var arr_leave = elm.arr_leave;
		
		if (!arr_leave) {
			return;
		}

		for (var i = 0, len = arr_leave.length; i < len; i++) {
			
			var state = arr_leave[i].state;
			
			if (state == 'any' || (locked == null && state == 'unlocked') || (locked == false && state == 'locked')) {
				
				arr_leave_run.push(arr_leave[i].func);
			}
		}
	};
	
	var func_run_leave = function(elm, locked, callback) {
		
		var len = arr_leave_run.length;
		var count = 0;
		
		if (!len) {
			
			if (callback) {
				callback();
			}
			return;
		}
		
		if (callback) {
			
			var func_done = function() {
				
				count++;
				
				if (count == len) {
					callback();
				}
			};
		} else {
			
			var func_done = function() {};
		}
		
		for (var i = 0; i < len; i++) {
			
			arr_leave_run[i](func_done);
		}
	};
	
	this.checkLocked = function(elm, callback, force, wait) {
		
		var locked = null; // null (no lock), true (locked), false (lock removed after user confirmation)
		
		if (!locked_active) {
						
			if (callback) {
				callback(locked);
			}
			return locked;
		}
		
		var is_page = (elm === document.body);
		
		if (elm.matches('[data-locked]')) {
			
			var identifier = JSON.stringify(serializeArrayByName(elm));
			
			if (identifier != elm.locked_identifier) {
				locked = true;
			}
		}
		
		var elms_locked = elm.querySelectorAll('[data-locked]');
		
		for (var i = 0, len = elms_locked.length; i < len; i++) {
			
			var elm_locked = elms_locked[i];
			var identifier = JSON.stringify(serializeArrayByName(elm_locked));
			
			if (identifier != elm_locked.locked_identifier) {
				locked = true;
				break;
			}
		}
		
		if (callback) {
			
			if (locked == null) {
				
				if (wait) {
					func_leave(elm, locked, function() {
						callback(locked);
					});
				} else {
					func_leave(elm, locked);
					callback(locked);
				}
			} else {
								
				var elm_popup = $('<div class="popup message"><span class="icon"></span><div>'+(is_page ? str_locked_page : str_locked_content)+'</div></div>');
						
				ASSETS.getIcons(elm, ['attention'], function(data) {
					elm_popup[0].children[0].innerHTML = data.attention;
				});
				
				var elm_overlay_target = $(elm).closest('.mod');
				if (!elm_overlay_target.length) {
					elm_overlay_target = $('body');
				}

				var obj_overlay = new Overlay(elm_overlay_target, elm_popup, {
					position: 'middle',
					call_close: function() {
						if (force) {
							callback(true);
						}
					},
					button_close: false
				});
				
				var elm_menu = $('<menu></menu>').appendTo(elm_popup);
				$('<input type="button" value="Ok" />').appendTo(elm_menu).on('click', function() {
					obj_overlay.close();
					if (wait) {
						func_leave(elm, false, function() {
							callback(false);
						});
					} else {
						func_leave(elm, false);
						callback(false);
					}					
				});
				$('<input type="button" value="Cancel" />').appendTo(elm_menu).on('click', function() {
					obj_overlay.close();
					if (force) {
						callback(true);
					}
				});
			}
		}
		
		return locked;
	};
}
var LOCATION = new Location();

function Assets() {
	
	var obj = this;
	var obj_fetched = {font: {}, script: {}, media: {}};
	var obj_labels = {};
	var obj_icons = {};
	
	var str_host = $('script[src*="/combine/js/"]').attr('src'); // Check for asset source
	str_host = str_host.match(/(.+)\/combine\/js\//i);
	if (str_host) {
		str_host = str_host[1];
		if (str_host.substring(0, 2) == '//') {
			str_host = window.location.protocol+str_host; // Add the protocol for external purposes (i.e. webworker)
		}
	}
	
	this.getHost = function() {
		
		return str_host;
	};
	
	this.fetch = function(arr_options, callback) {
		
		var count_loaders = 0;
		var func_loaded = function() {
			
			count_loaders--;
		
			if (!count_loaders) {
				callback();
			}
		};
		if (arr_options.script) {
			count_loaders++;
		}
		if (arr_options.font) {
			count_loaders++;
		}
		if (arr_options.media) {
			count_loaders++;
		}
		
		if (arr_options.script) {
			
			var call = function() {
			
				var count = arr_options.script.length;
				
				var func_asset = function(script, text) {
					
					count--;
					
					if (text) {
						
						obj_fetched.script[script] = text;
					}
					
					if (!count) {
						
						for (var i = 0, len = arr_options.script.length; i < len; i++) {
							
							var script = arr_options.script[i];
							
							if (obj_fetched.script[script]) {
								
								eval(obj_fetched.script[script]);
								obj_fetched.script[script] = '';
							}
						}
						
						func_loaded();
					}
				};
				
				for (var i = 0, len = arr_options.script.length; i < len; i++) {

					var script = arr_options.script[i];
					
					if (obj_fetched.script[script] != undefined) {

						func_asset(script);
						continue;
					}
					
					obj_fetched.script[script] = '';
					
					var call_script = function() {
						
						var cur_script = script;
						
						var xhr = new XMLHttpRequest();
						xhr.onreadystatechange = function() {
							
							if (this.readyState == 4) {
								
								if (this.status == 200) {
									
									func_asset(cur_script, this.response);
									return;
								}
								func_asset(cur_script, '');
							}
						}
						xhr.open('GET', (str_host ? str_host : '')+cur_script, true);
						xhr.responseType = 'text';
						xhr.send(); 
					};
					call_script();
				}
			};
			call();
		}
		
		if (arr_options.font) {
			
			var call = function() {
				
				var count = arr_options.font.length;
				
				var func_asset = function() {
					
					count--;
					
					if (!count) {
						func_loaded();
					}
				};
				
				for (var i = 0, len = arr_options.font.length; i < len; i++) {
					
					var font = arr_options.font[i];
					
					if (obj_fetched.font[font]) {
						
						func_asset();
						continue;
					}
					
					obj_fetched.font[font] = true;
					
					FontDetect.onFontLoaded(font, func_asset, func_asset);
				}
			};
			call();
		}
		
		if (arr_options.media) {
			
			var call = function() {
				
				var count = arr_options.media.length;
				
				var func_process = function() {
			
					var timer_check = false;
					
					var func_check = function() {
						
						var wait = false;
						
						for (var i = 0, len = arr_options.media.length; i < len; i++) {

							var resource = arr_options.media[i];
							var arr_resource = obj_fetched.media[resource];
							
							if (!arr_resource.image) {
								
								// Something could be wrong, do not stall
							} else if (arr_resource.image.width) {

								arr_resource.width = arr_resource.image.width;
								arr_resource.height = arr_resource.image.height;
							} else {
								
								wait = true;
							}						
						}
						
						if (!wait) {
							
							if (timer_check) {
								clearInterval(timer_check);
							}
							func_loaded();
							
							return;
						}

						if (!timer_check) {
							
							timer_check = setInterval(func_check, 10);
						}
					};
					func_check();
				};

				var func_asset = function(resource, value) {
					
					count--;
					
					if (value != undefined) {

						var store = (window.URL || window.webkitURL).createObjectURL(value);
						
						var image = new Image();
						image.src = store;
						
						obj_fetched.media[resource] = {resource: store, image: image, width: null, height: null};
					}
					
					if (!count) {
						func_process();
					}
				};
				
				for (var i = 0, len = arr_options.media.length; i < len; i++) {

					var resource = arr_options.media[i];
					
					if (obj_fetched.media[resource] != undefined) {

						func_asset(resource);
						continue;
					}
					
					obj_fetched.media[resource] = false;
					
					var call_media = function() {
						
						var cur_resource = resource;
						
						var xhr = new XMLHttpRequest();
						xhr.onreadystatechange = function() {
							
							if (this.readyState == 4) {
								
								if (this.status == 200) {
									
									func_asset(cur_resource, this.response);
									return;
								}
								func_asset(cur_resource);
							}
						}
						xhr.open('GET', (str_host ? str_host : '')+cur_resource, true);
						xhr.responseType = 'blob';
						xhr.send(); 
					};
					call_media();
				}
			};
			call();
		}
	};
	
	this.getMedia = function(resource) {
		
		var arr_resource = obj_fetched.media[resource];
		
		return arr_resource;
	};
	
	this.getIcons = function(elm, arr, callback) {

		var count = arr.length;
		
		var func_process = function() {
			
			var timer_check = false;
			
			var func_check = function() {
				
				var wait = false;
				
				for (var i = 0, len = arr.length; i < len; i++) {
					
					var icon = arr[i];
					
					if (obj_icons[icon] === false) {
						
						wait = true;
					}						
				}
				
				if (!wait) {
					
					if (timer_check) {
						clearInterval(timer_check);
					}
					callback(obj_icons);
					
					return;
				}

				if (!timer_check) {
					
					timer_check = setInterval(func_check, 10);
				}
			};
			func_check();
		};
		
		var func_asset = function(icon, content) {
			
			count--;
			
			if (content != undefined) {
				
				obj_icons[icon] = content;
			}
			
			if (!count) {
				
				func_process();
			}
		};
				
		for (var i = 0, len = arr.length; i < len; i++) {

			var icon = arr[i];
			
			if (obj_icons[icon] != undefined) {

				func_asset(icon);
				continue;
			}
			
			obj_icons[icon] = false;
			
			var call_load = function() {
				
				var cur_icon = icon;
				
				var xhr = new XMLHttpRequest();
				xhr.onreadystatechange = function(){
					
					if (this.readyState == 4) {
						
						if (this.status == 200) {
							
							func_asset(cur_icon, this.response);
							return;
						}
						func_asset(cur_icon, '');
					}
				}
				xhr.open('GET', (str_host ? str_host : '')+'/CMS/css/images/icons/'+cur_icon+'.svg', true);
				xhr.responseType = 'text';
				xhr.send(); 
			};
			call_load();
		}
	};
	
	this.getIcon = function(icon) {
		
		var svg = obj_icons[icon];
		
		return svg;
	};
	
	this.getLabels = function(elm, arr, callback) {
		
		var identifier = JSON.stringify(arr);
		
		if (obj_labels[identifier]) {
			
			callback(obj_labels[identifier]);
		} else {
		
			elm[0].request = $.ajax({
				type: 'POST',
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				url: LOCATION.getUrl('command'),
				data: JSON.stringify({mod: getModID(elm), module: 'cms_general', method: 'get_label', value: arr}),
				context: elm,
				async: true,
				success: function(json) {
					
					json.location = false; // Prevent async location changes
					
					FEEDBACK.check(elm, json, function() {
						
						obj_labels[identifier] = json.html;
						callback(json.html);
					});
				}
			});
		}
	};
	
	this.createWorker = function(func, arr_scripts, func_before) {
		
		if (arr_scripts) {

			var str_host_use = (str_host ? str_host : LOCATION.getHost());
			
			for (var i = 0, len = arr_scripts.length; i < len; i++) {
				arr_scripts[i] = str_host_use+arr_scripts[i];
			}
		}
		
		var blob_url = URL.createObjectURL(new Blob([
				(func_before ? '('+func_before.toString()+')();' : ''),
				(arr_scripts ? 'importScripts(\''+arr_scripts.join('\',\'')+'\');' : ''),
				'('+func.toString()+')();'
			],
			{type: 'application/javascript'}
		));
		
		var worker = new Worker(blob_url);
		
		URL.revokeObjectURL(blob_url);
		
		return worker;
	};
}
var ASSETS = new Assets();

function Animator() {
	
	var obj = this,
	arr_animate = [],
	key_tween = false,
	animating = false;
	
	window.requestAnimatorFrame = function() {
		return (
			window.requestAnimationFrame || 
			window.webkitRequestAnimationFrame || 
			window.mozRequestAnimationFrame || 
			window.oRequestAnimationFrame || 
			window.msRequestAnimationFrame || 
			function (callback) {
				window.setTimeout(callback, 1000 / 60);
			}
		);
	}();
	
	this.animate = function(call, key) {
		
		if (key === 0 || key > 0) {
			
			arr_animate[key] = call;
		} else {
			
			for (var i = 0, len = arr_animate.length; i <= len; i++) {
				
				if (arr_animate[i] === null || arr_animate[i] === undefined) {
					
					var key = i;
					arr_animate[key] = call;
					break;
				}
			}
		}
		
		if (key > 0 && call) { // key 0 = key_tween
			
			obj.trigger();
		}
		
		return key;
	};
	
	this.trigger = function() {
		
		if (!animating) {
			
			animating = true;
			requestAnimatorFrame(doAnimate);
		}
	};
	
	var doAnimate = function(time) {
		
		var count = 0;
		var len = arr_animate.length;
		
		for (var i = 0; i < len; i++) {
			
			if (arr_animate[i]) {
				
				if (arr_animate[i](time)) {
					count++;
				}
				
				var time = window.performance.now(); // Update time to account for computing time between calls
			}
		}
		
		if (count) {
			requestAnimatorFrame(doAnimate);
		} else {
			animating = false;
		}
	};
	
	key_tween = obj.animate(function(time) {

		var updated = TWEEN.update(time);
		
		return (updated ? true : false);
	});
}
var ANIMATOR = new Animator();
	
function MessageBox(elm) {
	
	var obj = this;
	
	var count = 0;
	var is_hovering = false;
	var key_animate = false;
	var time_animate = false;
	var elm_con = $(elm).children('.result');
	
	if (!elm_con.length) {
		elm_con = $('<div class="result"></div>').appendTo(elm);
	}
		
	this.add = function(options) {
		
		var arr_options = $.extend({
			msg: '',
			type: 'attention',
			method: 'replace',
			identifier: false,
			counter: true,
			duration: 5000,
			persist: false,
			follow_click: false
		}, options || {});
		
		var elm_box = false;
				
		if (arr_options.follow_click) {
			elm_con.css({'position': 'absolute', 'z-index': '99999', 'top': POSITION.mouse.y, 'left': POSITION.mouse.x});
		}

		var elm_msg = $(arr_options.msg).clone().wrap('<div/>').parent(); // wrap to return own html
		if (arr_options.counter) {
			count++;
			elm_msg.find('label:first').text("#"+count);
		}
		
		elm_box = $('<div class="'+arr_options.type+'"'+(arr_options.identifier ? ' data-identifier="'+arr_options.identifier+'"' : '')+'>'+elm_msg.html()+'</div>');

		var obj_messagebox = {
			active: true,
			time: false,
			duration: arr_options.duration,
			tween: false
		};
			
		elm_box[0].messagebox = obj_messagebox;
		
		if (arr_options.method == 'replace') {
			elm_con.html(elm_box);
		} else if (arr_options.method == 'append') {
			elm_con.append(elm_box);
		} else if (arr_options.method == 'prepend') {
			elm_con.prepend(elm_box);
		}

		if (!arr_options.persist) {
			obj_messagebox.time = arr_options.duration;
		}

		func_run();
			
		return elm_box;
	};
	
	this.end = function(elm_box, quick) {
		
		var elm_box = getElement(elm_box);
		var obj_messagebox = elm_box.messagebox;
				
		obj_messagebox.active = false;
				
		if (quick) {
			
			if (obj_messagebox.tween) {
				obj_messagebox.tween.stop();
			}

			$(elm_box).remove();
		} else {
			
			if (obj_messagebox.tween) {
				return;
			}
			
			var arr_tween = {opacity: 1};
			
			obj_messagebox.tween = new TWEEN.Tween(arr_tween)
				.to({opacity: 0}, 500)
				.easing(TWEEN.Easing.Sinusoidal.InOut)
				.onUpdate(function() {
					
					elm_box.style.opacity = arr_tween.opacity;
				}).onComplete(function() {
					
					$(elm_box).remove();
				})
			.start();
		}
	};
	
	this.clear = function(arr_options) {
		
		var elms_box = false;
		
		if (!arr_options.identifier) {
			
			elms_box = elm_con[0].children;
		} else {
				
			var arr_identifiers = [];

			if (typeof arr_options.identifier == 'object') {
				
				for (var i = 0, len = arr_options.identifier.length; i < len; i++) {
					arr_identifiers.push('[data-identifier="'+arr_options.identifier[i]+'"]');
				}
			} else {
				
				arr_identifiers.push('[data-identifier="'+arr_options.identifier+'"]');
			}

			if (arr_identifiers.length) {
				
				elms_box = elm_con[0].querySelectorAll(arr_identifiers.join(','));
			}
		}
		
		if (!elms_box) {
			return;
		}
		
		for (var i = 0, len = elms_box.length; i < len; i++) {
			
			var elm_box = elms_box[i];
			
			if (arr_options.timeout === 0) { // Clear
			
				obj.end(elm_box, true);
			} else {
				
				var obj_messagebox = elm_box.messagebox;
				
				var time = arr_options.timeout;
				
				if (time === undefined) { // If no timeout is specified, use the originally defined timeout
					time = obj_messagebox.duration;
				}
				
				if (time) {
					obj_messagebox.time = time;
				} else {
					obj.end(elm_box);
				}
				
			}
		}
	};

	var func_run = function() {
		
		if (time_animate !== false) {
			return;
		}
		
		time_animate = 0;
		
		key_animate = ANIMATOR.animate(function(time) {
			
			if (is_hovering) {
				
				time_animate = 0;
				
				return true;
			}
			
			var elms_box = elm_con[0].childNodes;
		
			if (!elms_box.length) {
				
				time_animate = false;
				ANIMATOR.animate(false, key_animate);
			
				return false;
			}
			
			if (!time_animate) {
				time_animate = time;
			}
			
			var time_diff = time - time_animate;
			
			for (var i = 0, len = elms_box.length; i < len; i++) {
				
				var elm_box = elms_box[i];
				var obj_messagebox = elm_box.messagebox;
				
				if (!obj_messagebox.active || obj_messagebox.time === false) {
					continue;
				}
								
				obj_messagebox.time -= time_diff;
				
				if (obj_messagebox.time > 0) {
					continue;
				}
				
				obj.end(elm_box);
			}
				
			time_animate = time;
			
			return true;
		}, key_animate);
	};
	
	var func_over = function(e) {
		
		if (is_hovering) {
			return;
		}
		
		if (POSITION.is_touch && e.type == 'mouseover') { // Prevent touch and triggered mouse events mixup
			return;
		}
		
		is_hovering = true;

		elm_con[0].addEventListener('touchend', func_end, true);
		elm_con[0].addEventListener('mouseout', func_end, true);
	};
	
	var func_end = function(e) {
		
		if (e.type == 'mouseout' && e.relatedTarget && hasElement(elm_con, e.relatedTarget)) { // Check when it's a mouse event whether it has left the document (empty e.relatedTarget) and is still on the main element
			return;
		}
	
		is_hovering = false;

		elm_con[0].removeEventListener('touchend', func_end, true);
		elm_con[0].removeEventListener('mouseout', func_end, true);
	}
	
	elm_con[0].addEventListener('mouseover', func_over, true);
	elm_con[0].addEventListener('touchstart', func_over, true);
}

function PollingBuffer(timeout) {
	
	this.running = false;
	
	var obj = this;
	var timeout = (timeout ? timeout : 40);
	var timing = 0;
	var key_animator = false;
	var func_run = false;
	var continuous = false;

	this.run = function(func, persist) {
		
		func_run = func;
		continuous = (persist ? true : false);
		
		if (!obj.running) { // Already running
			
			obj.running = true;

			key_animator = ANIMATOR.animate(function(time) {
				
				var cur_timeout = (time - timing);
		
				if (cur_timeout > timeout) {
					
					timing = time;

					func_run();
					
					if (!continuous) {
						obj.stop();
						return false;
					}
				}
				
				return true;
			}, key_animator);
		}
	};
	
	this.stop = function() {
		
		obj.running = false;
		
		ANIMATOR.animate(false, key_animator);
		func_run = false;
	};
}

function ExecutionTimer() {
	
	var time = 0;
	var timer = false;
	
	this.start = function(reset) {
		
		if (reset) {
			time = 0;
		}
		
		timer = window.performance.now();
	};
	
	this.stop = function() {
		
		time += window.performance.now() - timer;
	};
	
	this.log = function(name) {
		
		console.log((name ? name+' ' : '')+time);
	}
}

function tweakDataTable(elm) { // Make tables slick
			
	var elm = getElement(elm);
	
	var elm_heading = elm.querySelector('thead > tr');
	var elms_row = elm.querySelectorAll('tbody > tr');
	
	var elms_columns = elm_heading.children;
	var arr_classes = ['max', 'limit', 'menu'];
	
	for (var i = 0, len = elms_columns.length; i < len; i++) {
		
		var elm_column = elms_columns[i];
		
		for (var j = 0, len_j = arr_classes.length; j < len_j; j++) {
			
			var str_class = arr_classes[j];
			
			if (!elm_column.classList.contains(str_class)) {
				continue;
			}
			
			for (var k = 0, len_k = elms_row.length; k < len_k; k++) {
				
				var elm_cell = elms_row[k].children[i];				
				
				if (elm_cell == undefined) {
					continue;
				}
				
				elm_cell.classList.add(str_class);
			}
		}		
	}

	resizeDataTable(elm);
	
	TOOLTIP.checkElement(elm_heading, 'th > span', function(elm_span) {

		if (elm_span.title) {
			return false;
		}
		
		var elm_column = elm_span.parentNode;
		
		if (elm_column.title) {
			return false;
		}
		
		var arr_style = window.getComputedStyle(elm_column);
		
		var int_padding = parseInt(arr_style['padding-left']) + parseInt(arr_style['padding-right']);

		if (elm_span.scrollWidth > (elm_column.clientWidth - int_padding)) {

			return elm_span.innerHTML;
		}
		
		return false;
	}, true);
}

function resizeDataTable(elm, force) { // Make tables fit
	
	var elm = getElement(elm);
	
	var func_calc = function() {

		var elms_column_all = elm.querySelectorAll('thead > tr > th');
		var elms_cell_all = elm.querySelectorAll('tbody > tr > td');
		
		elm.classList.remove('resized');
		
		for (var i = 0, len = elms_column_all.length; i < len; i++) {
			elms_column_all[i].style.maxWidth = '';
		}
		for (var i = 0, len = elms_cell_all.length; i < len; i++) {
			elms_cell_all[i].style.maxWidth = '';
		}
	
		var elm_target = elm.parentNode;
	
		var arr_style_target = window.getComputedStyle(elm_target);
		var padding = parseInt(arr_style_target['padding-left']) + parseInt(arr_style_target['padding-right']);
		
		var width_target = elm_target.clientWidth - padding;
		var width_real = elm.scrollWidth;
		
		var resize_container = elm.closest('.overlay');
		var resize = false;
		
		if (resize_container) { // If table is inside an overlay, do not try to resize the table but the overlay
			
			if (width_real > width_target) {
				
				elm.style.maxWidth = width_real+'px';
				
				// Check if the parent was able to resize to the table's size, if not, resize the table
				width_target = elm_target.clientWidth - padding;
				
				if (width_real > width_target) {
					resize = true;
				}
			} else {
			
				elm.style.maxWidth = width_target+'px';
			}
		} else {
			
			if (width_real > width_target) {
				resize = true;
			}
		}
		
		if (resize) {
	
			elm.classList.add('resized');

			var elms_column = elm.querySelectorAll('thead > tr > th.limit');
			elms_column = (elms_column.length ? elms_column : elms_column_all);
			var elms_cell = elm.querySelectorAll('tbody > tr > td.limit');
			elms_cell = (elms_cell.length ? elms_cell : elms_cell_all);
			
			var elms_column_no_limit = Array.prototype.filter.call(elms_column_all, function(n) {
				return Array.prototype.indexOf.call(elms_column, n) === -1;
			});
			
			var len_elms_column = elms_column.length;
			var elm_column_first = elms_column[0];
			var arr_style_columns = window.getComputedStyle(elm_column_first);
			
			var arr_columns_width = Array.prototype.map.call(elms_column, function(n) {
				return n.clientWidth;
			});
			var arr_columns_no_limit_width = Array.prototype.map.call(elms_column_no_limit, function(n) {
				return n.clientWidth;
			});

			var func_sum_arr = function(arr) {
				
				var total = 0;
				
				for (var i = 0, len = arr.length; i < len; i++) {
					total += arr[i];
				}
				
				return total;
			};
			
			width_target = width_target - func_sum_arr(arr_columns_no_limit_width);
			var width_max = arr_style_columns['max-width'];
			
			if (width_max === 'none') { // Find current maximum requested width
				
				width_max = 0;
				
				for (var i = 0, len = elms_column_all.length; i < len; i++) {
					
					var width = elms_column_all[i].clientWidth;
					width_max = (width > width_max ? width : width_max);
				}
			}
			
			width_max = parseInt(width_max);
			var width_max_calc = width_max;

			for (width_max_calc--; func_sum_arr(arr_columns_width) > width_target; width_max_calc--) {
				
				if (width_max_calc == 0) {
					
					if (elms_column !== elms_column_all) { // Start over with all columns
						
						width_max_calc = width_max;
						elms_column = elms_column_all;
						
						arr_columns_width = Array.prototype.map.call(elms_column, function(n) {
							return n.clientWidth;
						});
					} else {
						
						break;
					}
				}
				
				for (var i = 0; i < len_elms_column; i++) {
					
					if (arr_columns_width[i] > width_max_calc) {
						arr_columns_width[i] = width_max_calc;
					}
				}
			}
			
			for (var i = 0, len = elms_cell.length; i < len; i++) {
				elms_cell[i].style.maxWidth = width_max_calc+'px';
			}
			for (var i = 0, len = elms_column.length; i < len; i++) {
				elms_column[i].style.maxWidth = width_max_calc+'px';
			}
			
			var elm_heading = elm.querySelector('thead > tr');
		
			TOOLTIP.recheckElement(elm_heading, 'th > span');
		}
	};
	
	if (force) {
		
		func_calc();
	} else {

		if (!isHidden(elm)) {
			
			func_calc();
		} else {
			
			if (!elm.overlay_resize_table) {
				
				$(elm).closest('.tabs > div').one('open', function() {
					
					func_calc();
					elm.overlay_resize_table = false;
				});
				
				elm.overlay_resize_table = true;
			}
		}
	}
}

// HELPERS

function loopJSONFunctionEval(obj) {
	
	$.each(obj, function(key, value) {

		if (typeof value == "boolean") {

		} else if (typeof value == "object") {
			loopJSONFunctionEval(value);
		} else {
			if (value.indexOf('funct') == 0) {
				eval('temp='+value);
				obj[key] = temp;
			}
		}
	});
	
	return obj;
}

function decodeHTMLSpecialChars(str) {
	
	str = (str == null ? '' : ''+str);
	
	var arr_escape = {
		'&nbsp;': '\u00a0',
		'&amp;': '&',
		'&lt;': '<',
		'&gt;': '>',
		'&quot;': '"',
		'&#x27;': "'",
		'&#x60;': '`' 
	};
	
	var func_escape = function(match) {
		return arr_escape[match];
    };

    var source = '(?:' + Object.keys(arr_escape).join('|') + ')';
    var regex_test = RegExp(source);
    var regex_replace = RegExp(source, 'g');
    
	return (regex_test.test(str) ? str.replace(regex_replace, func_escape) : str);
}

function stripHTMLTags(str) {
	
	if (!str) {
		return '';
	}
	
	var div = document.createElement('div');
	div.innerHTML = str;
	
	var str = div.textContent;
	
	return str;
}

function formatNumber(number, decimal_places, thousands_separator, decimal_separator) {

	var decimal_places = isNaN(decimal_places = Math.abs(decimal_places)) ? 2 : decimal_places,
		decimal_separator = decimal_separator == undefined ? '.' : decimal_separator,
		thousands_separator = thousands_separator == undefined ? ',' : thousands_separator,
		sign = number < 0 ? '-' : '',
		i = parseInt(number = Math.abs(+number || 0).toFixed(decimal_places)) + '',
		j = (j = i.length) > 3 ? j % 3 : 0;

	return sign + (j ? i.substr(0, j) + thousands_separator : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, '$1' + thousands_separator) + (decimal_places ? decimal_separator + Math.abs(number - i).toFixed(decimal_places).slice(2) : '');
}

function arrUnique(arr) {
	
    return arr.reduce(function(p, c) {
        if (p.indexOf(c) < 0) p.push(c);
        return p;
    }, []);
}

function guid() {
	
	function s4() {
		return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
	}
	
	return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
}

function parseCssColor(input) {
	
	if (input[0] == '#') {
		
		var int = parseInt(input.substring(1), 16);
		var r = (int >> 16) & 255;
		var g = (int >> 8) & 255;
		var b = int & 255;

		return {r: r, g: g, b: b, a: 1};
	} else {
	
		var arr = input.match(/^rgb(?:a)?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:,\s*(\d+(?:\.\d+)))?.*/i);
		
		if (arr) {
			return {r: parseInt(arr[1]), g: parseInt(arr[2]), b: parseInt(arr[3]), a: (typeof arr[4] != 'undefined' ? parseFloat(arr[4]) : 1)};
		} else {
			return {r: 255, g: 255, b: 255, a: 1};
		}
	}
}

function parseCssColorToHex(str) {
	
	var arr_color = parseCssColor(str);
	var hex = '0x'+(0x1000000 + (arr_color.r << 16) + (arr_color.g << 8) + arr_color.b).toString(16).slice(1);

	return hex;
}

function getInputTextSize(elm) {
	
	var arr_set_styles = [
		'fontFamily', 'fontSize', 'fontWeight', 'fontVariant', 'fontStyle',
		'paddingLeft', 'paddingTop', 'paddingBottom', 'paddingRight',
		'boxSizing'
	];
	
	var elm_toolbox = getContainerToolbox($(elm));

	var elm_simulator = document.createElement('div');
	
	elm_simulator.style['position'] = 'absolute';
	elm_simulator.style['display'] = 'inline-block';
	elm_simulator.style['whiteSpace'] = 'pre';
	elm_simulator.style['visibility'] = 'hidden';
	
	elm_toolbox[0].appendChild(elm_simulator);
	
	var arr_style = window.getComputedStyle(elm);
		
	for (var i = 0, len = arr_set_styles.length; i < len; i++) {
		
		var style = arr_set_styles[i];
		
		elm_simulator.style[style] = arr_style[style];
	}
		
	elm_simulator.textContent = elm.value;
	
	var width = elm_simulator.clientWidth;
	
	if (arr_style['box-sizing'] == 'border-box') {
		width += parseInt(arr_style['border-left-width']);
		width += parseInt(arr_style['border-right-width']);
		
	} else {
		width -= parseInt(arr_style['padding-left']);
		width -= parseInt(arr_style['padding-right']);
	}
	
	elm_toolbox[0].removeChild(elm_simulator);
	
	return width;
}
	
function fitText(elm, size_use) {
	
	var elm = getElement(elm);
	
	var arr_style = window.getComputedStyle(elm);
	var elm_parent = elm.parentNode;
	var height_max = elm_parent.clientHeight;
	var width_max = elm_parent.clientWidth;
	var size = parseInt(arr_style['font-size'], 10);
	var size_max = (size_use > 0 ? parseInt(size_use, 10) : size);
	var multiplier = (width_max / elm.offsetWidth);
	var size_new = (size * multiplier);
	
	elm.style['font-size'] = ((size_max > 0 && size_new > size_max) ? size_max : size_new)+'px';
}

function moveScroll(elm, arr_options) {
	
	var arr_options = $.extend({
		duration: 800,
		elm_con: false
	}, arr_options || {});
		
	var elm = getElement(elm);
	var pos = elm.getBoundingClientRect(); // Get position of element in relation to the client
	var is_document = (arr_options.elm_con == false);
	
	var elm_check = $('[data-keep_focus=1]');
	if (elm_check.length && !hasElement(elm, elm_check[0], true)) {
		return;
	}
	
	if (is_document) {
		var pos_con = {left: -POSITION.scrollLeft(), top: -POSITION.scrollTop()};
		var size_con = {width: window.innerWidth, height: window.innerHeight};
		var pos_scroll = {left: 0, top: 0};
		var pos_from = {x: POSITION.scrollLeft(), y: POSITION.scrollTop()};
	} else {
		var elm_con = getElement(arr_options.elm_con);
		var pos_con = elm_con.getBoundingClientRect();
		var size_con = {width: elm_con.clientWidth, height: elm_con.clientHeight};
		var pos_scroll = {left: elm_con.scrollLeft, top: elm_con.scrollTop};
		var pos_from = {x: pos_scroll.left, y: pos_scroll.top};
	}
	
	var pos_center = {x: (size_con.width > elm.clientWidth ? (size_con.width-elm.clientWidth)/2 : 0), y: (size_con.height > elm.clientHeight ? (size_con.height-elm.clientHeight)/2 : 0)};

	var pos_to = {x: pos_scroll.left + (pos.left-pos_con.left) - pos_center.x, y: pos_scroll.top + (pos.top-pos_con.top) - pos_center.y};

	new TWEEN.Tween(pos_from)
		.to(pos_to, arr_options.duration)
		.easing(TWEEN.Easing.Sinusoidal.InOut)
		.onUpdate(function() {
			if (is_document) {
				window.scrollTo(pos_from.x, pos_from.y);
			} else {
				elm_con.scrollLeft = pos_from.x;
				elm_con.scrollTop = pos_from.y;
			}
		})
	.start();
	
	ANIMATOR.trigger();
}

// TOOLS

function Pulse(elm, arr_options) {
	
	var SELF = this;
	
	var elm = getElement(elm);

	var arr_options = $.extend({
		duration: 500,
		ease_in: TWEEN.Easing.Sinusoidal.InOut,
		ease_out: TWEEN.Easing.Sinusoidal.InOut,
		delay_in: 0,
		delay_out: 0,
		from: 'pulse-from',
		to: 'pulse',
		repeat: false
	}, arr_options || {});
	
	if (elm.pulse) {
		
		elm.pulse.update(arr_options);
		
		return elm.pulse;
	}
	elm.pulse = this;
	
	var cur = $(elm);

	var do_stop = (arr_options.repeat ? false : true);
	var do_reverse = true;
	var is_starting = true;
	var is_stopping = false;
	
	var timeout_inactive = false;
	var tween = false;
		
	var func_intermediary = function() {
		
		if (arr_options.to) {
			cur.addClass(arr_options.to);
		} 
		if (arr_options.from) {
			cur.removeClass(arr_options.from);
		}
		
		if (!is_stopping) {
			
			var ease_in = arr_options.ease_in;
			arr_options.ease_in = arr_options.ease_out;
			arr_options.ease_out = ease_in;
			var delay_in = arr_options.delay_in;
			arr_options.delay_in = arr_options.delay_out;
			arr_options.delay_out = delay_in;
			var from = arr_options.from;
			arr_options.from = arr_options.to;
			arr_options.to = from;
			
			do_reverse = (do_reverse ? false : true);
			
			func_animate();
		} else {
		
			SELF.destroy();
		}
	};
	
	var func_animate = function() {
		
		is_stopping = (!do_reverse && do_stop);
		is_starting = false;
		
		if (is_stopping) {
			arr_options.to = false;
		}

		var arr_properties = {from: {}, to: {}};
									
		var arr_cur_css = {background_color: cur.css('background-color'), border_color: cur.css('border-top-color'), color: cur.css('color')};
		var cur_style = cur[0].getAttribute('style');
		
		cur[0].removeAttribute('style');
		if (arr_options.to) {
			cur.addClass(arr_options.to);
		}
		if (arr_options.from) {
			cur.removeClass(arr_options.from);
		}
		
		var arr_new_css = {background_color: cur.css('background-color'), border_color: cur.css('border-top-color'), color: cur.css('color')};
		
		cur[0].setAttribute('style', cur_style);
		if (arr_options.to) {
			cur.removeClass(arr_options.to);
		}
		if (arr_options.from) {
			cur.addClass(arr_options.from);
		}
		
		if (arr_cur_css.color != arr_new_css.color) {
			var arr_color = parseCssColor(arr_cur_css.color);
			arr_properties.from.colorRed = arr_color.r;
			arr_properties.from.colorGreen = arr_color.g;
			arr_properties.from.colorBlue = arr_color.b;
			arr_properties.from.colorAlpha = arr_color.a;
			var arr_color = parseCssColor(arr_new_css.color);
			arr_properties.to.colorRed = arr_color.r;
			arr_properties.to.colorGreen = arr_color.g;
			arr_properties.to.colorBlue = arr_color.b;
			arr_properties.to.colorAlpha = arr_color.a;
		}
		if (arr_cur_css.background_color != arr_new_css.background_color) {
			var arr_color = parseCssColor(arr_cur_css.background_color);
			arr_properties.from.backgroundColorRed = arr_color.r;
			arr_properties.from.backgroundColorGreen = arr_color.g;
			arr_properties.from.backgroundColorBlue = arr_color.b;
			arr_properties.from.backgroundColorAlpha = arr_color.a;
			var arr_color = parseCssColor(arr_new_css.background_color);
			arr_properties.to.backgroundColorRed = arr_color.r;
			arr_properties.to.backgroundColorGreen = arr_color.g;
			arr_properties.to.backgroundColorBlue = arr_color.b;
			arr_properties.to.backgroundColorAlpha = arr_color.a;
		}
		if (arr_cur_css.border_color != arr_new_css.border_color) {
			var arr_color = parseCssColor(arr_cur_css.border_color);
			arr_properties.from.borderColorRed = arr_color.r;
			arr_properties.from.borderColorGreen = arr_color.g;
			arr_properties.from.borderColorBlue = arr_color.b;
			arr_properties.from.borderColorAlpha = arr_color.a;
			var arr_color = parseCssColor(arr_new_css.border_color);
			arr_properties.to.borderColorRed = arr_color.r;
			arr_properties.to.borderColorGreen = arr_color.g;
			arr_properties.to.borderColorBlue = arr_color.b;
			arr_properties.to.borderColorAlpha = arr_color.a;
		}
		
		var func_run = function() {
			
			var pos = cur[0].getBoundingClientRect(); // Get position in relation to the client of animating element
			var elm_hit = document.elementFromPoint(pos.left+(cur[0].clientWidth/2), pos.top+(cur[0].clientHeight/2)); // Do a hit-test with the coordinates, add 2 to make a hit more certain (IE)
			
			if (elm_hit && hasElement(cur[0], elm_hit, true)) {

				tween = new TWEEN.Tween(arr_properties.from)
					.to(arr_properties.to, arr_options.duration)
					.easing(arr_options.ease_in)
					.onUpdate(function(arr) {
						
						if (typeof arr.backgroundColorRed != 'undefined') {
							cur[0].style.backgroundColor = 'rgba('+parseInt(arr.backgroundColorRed)+','+parseInt(arr.backgroundColorGreen)+','+parseInt(arr.backgroundColorBlue)+','+arr.backgroundColorAlpha+')';
						}
						if (typeof arr.borderColorRed != 'undefined') {
							cur[0].style.borderColor = 'rgba('+parseInt(arr.borderColorRed)+','+parseInt(arr.borderColorGreen)+','+parseInt(arr.borderColorBlue)+','+arr.borderColorAlpha+')';
						}
						if (typeof arr.colorRed != 'undefined') {
							cur[0].style.color = 'rgba('+parseInt(arr.colorRed)+','+parseInt(arr.colorGreen)+','+parseInt(arr.colorBlue)+','+arr.colorAlpha+')';
						}
					}).onComplete(func_intermediary)
				.delay(arr_options.delay_in).start();
				
				ANIMATOR.trigger();
			} else {
				
				timeout_inactive = setTimeout(func_run, 500);
			}
		};
		
		func_run();
	};
	
	func_animate();
	
	var func_restart = function() {
		
		if (timeout_inactive) {
			clearTimeout(timeout_inactive);
		}
		if (tween) {
			tween.stop();
		}
		
		func_animate();
	};
	
	this.update = function(arr_options_new) {
		
		for (var key in arr_options_new) {
			arr_options[key] = arr_options_new[key];
		}
		
		do_stop = (arr_options.repeat ? false : true);
		do_reverse = true;
		is_starting = true;
		
		func_restart();
	};
	
	this.abort = function() {
								
		do_stop = true;
		do_reverse = false;
		
		arr_options.delay_in = 0;
		arr_options.ease_in = TWEEN.Easing.Linear.None;
		
		func_restart();
	};
	
	this.stop = function() {
					
		do_stop = true;
	};
	
	this.destroy = function() {
		
		cur[0].pulse = null;
	};
}

function ToolExtras(elm, options) {
		
	var arr_options = $.extend({
		fullscreen: false,
		maximize: false,
		tools: false,
		class: ''
	}, options);
		
	var elm = $(elm);

	if (elm[0].tool_extras) {
		return;
	}
	elm[0].tool_extras = true;
	
	var css_position = elm.css('position');
	if (css_position != 'absolute' && css_position != 'relative') {
		elm.css('position', 'relative');
	}
	
	var elm_toolbox = getContainerToolbox(elm);
	var is_editable = (elm.is('input, textarea, .input') ? true : false);
	
	var elm_placeholder = $('<div class="tool-extras-placeholder" />');
	var elm_extras = $('<div class="tool-extras'+(arr_options.class ? ' tool-extras-class-'+arr_options.class : '')+'"></div>').addClass('hide').appendTo(elm_toolbox);
	
	if (arr_options.fullscreen) {
		
		var elm_button_fullscreen = $('<span class="fullscreen" title="Fullscreen"><span class="icon"></span><span class="icon"></span></span>').appendTo(elm_extras);
		
		ASSETS.getIcons(elm, ['maximize', 'minimize'], function(data) {
			
			elm_button_fullscreen[0].children[0].innerHTML = data.maximize;
			elm_button_fullscreen[0].children[1].innerHTML = data.minimize;
		});
		
		if (arr_options.maximize && arr_options.maximize === 'fixed') {

			var func_fullscreen = function(e) {
				
				if (elm_button_fullscreen.hasClass('minimize')) {
					
					elm.removeClass('tool-extras-big');
					$('body').removeClass('in-fullscreen');
				
					elm_button_fullscreen.removeClass('minimize');
				} else {
					
					elm.addClass('tool-extras-big');
					$('body').addClass('in-fullscreen');
				
					elm_button_fullscreen.addClass('minimize');
				}
				
				func_position();
			};
		} else {
			
			var func_fullscreen = function(e) {

				elm_button_fullscreen.addClass('hide');
				elm_extras.addClass('hide');
				elm_placeholder[0].style.width = elm.outerWidth()+'px';
				elm_placeholder[0].style.height = elm.outerHeight()+'px';
				elm_placeholder.addClass('hide').insertAfter(elm);
				
				var elm_copy = elm;
				
				if (arr_options.maximize) {
					var mod_id = getModID(elm);
					var classes = $('#mod-'+mod_id.split('-')[1]).attr('class');
					elm_copy = elm_copy.wrap('<div class="'+classes+'"></div>').parent();
					var elm_overlay_target = $('body');
				} else {
					var elm_overlay_target = (IS_CMS ? $('body') : getContainer(elm));
				}
				
				var elm_form = elm.closest('form');
				if (elm_form.length) { // Keep reference to form related elements (i.e. sorter)
					elm_copy[0].elm_form = elm_form;
					elm_copy[0].dataset.form = 1;
				}
				
				var elm_toolbox_track = elm_toolbox;
				
				var obj_overlay = new Overlay(elm_overlay_target, elm_copy, {
					sizing: 'full-width',
					call_close: function() {
						elm_toolbox_track.append(elm_toolbox.children()); // Keep possible toolbox stuff working when switching
						elm_toolbox = elm_toolbox_track;
						elm.insertBefore(elm_placeholder).removeClass('tool-extras-big');
						elm_extras.addClass('hide');
						elm_button_fullscreen.removeClass('hide');
						elm_placeholder.remove();
					}
				});
				
				if (arr_options.maximize) {
					var elm_overlay = obj_overlay.getOverlay();
					setElementData(elm_overlay, 'mod', mod_id);
				}
				
				elm_placeholder.removeClass('hide');
				elm.addClass('tool-extras-big');

				elm_toolbox = getContainerToolbox(elm);
				elm_toolbox.append(elm_extras);
				
				SCRIPTER.runDynamic(elm);
			};
		}
		
		elm_button_fullscreen.on('click', func_fullscreen);
	}
	
	if (arr_options.tools) {
		
		var elm_tools = $('<span class="tools" title="Tools"><span class="icon"></span></span>').prependTo(elm_extras);
		
		ASSETS.getIcons(elm, ['tool'], function(data) {
			
			elm_tools[0].children[0].innerHTML = data.tool;
		});
		
		elm_tools.on('click', function(e) {
			
			var elm_button = $(this);
			
			if (elm[0].dataset.tools) {
				
				elm[0].dataset.tools = '';
				SCRIPTER.triggerEvent(elm, 'toolsdisable');
				elm_button.addClass('active');
			} else {
				
				elm[0].dataset.tools = 1;
				SCRIPTER.triggerEvent(elm, 'toolsenable');
				elm_button.removeClass('active');
			}			
		});
		
		elm[0].dataset.tools = 1;
	}
	
	var margin = {top: parseInt(elm_extras.css('margin-top')), right: parseInt(elm_extras.css('margin-right'))};
	
	var func_position = function() {
		
		var pos_mod = elm_toolbox[0].getBoundingClientRect();
		var pos = elm[0].getBoundingClientRect();
		
		elm_extras.removeClass('hide').css({left: (pos.left - pos_mod.left) + pos.width - (elm_extras[0].offsetWidth + margin.right), top: (pos.top - pos_mod.top) + margin.top});
	};
	
	var is_listening = false;
	var timer_hide = false;
	
	var func_hide = function() {
		
		elm_extras.addClass('hide');
		
		is_listening = false;
		
		if (timer_hide) {
			clearInterval(timer_hide);
			timer_hide = false;
		}
	};
	
	elm.on((is_editable ? 'mouseup.extras' : 'mouseenter.extras'), function() {
		
		func_position();
		
		if (is_listening) {
			return;
		}
		
		if (!is_editable) {
			
			var time_check = false;
			
			var func_check = function() {

				if (!timer_hide) {
					
					time_check = window.performance.now();
					
					timer_hide = window.setInterval(function() {
				
						var time_now = window.performance.now();
						
						if (time_now - time_check >= 2500) {
							
							elm_extras.addClass('hide');
							
							clearInterval(timer_hide);
							timer_hide = false;
						}
					}, (0.5 * 1000));
				} else {
					
					elm_extras.removeClass('hide');

					time_check = window.performance.now();
				}
			};
			
			func_check();
			
			$(document).on('mousemove.extras', function caller(e) {
				
				if (elm[0] != e.target && !hasElement(elm[0], e.target) && !hasElement(elm_extras[0], e.target)) {
					
					$(document).off('.extras', caller);
					func_hide();
				} else {
					
					func_check();
				}
			});
		} else {
			
			$(document).on('mousedown.extras keydown.extras ajaxloaded.extras', function caller(e) {
				
				var is_extra = hasElement(elm_extras[0], e.target, true);
				var has_focus = hasElement(elm[0], e.target, true);
			
				if ((e.type != 'ajaxloaded' && !has_focus && !is_extra) || (e.type == 'ajaxloaded' && !is_extra)) {
					
					$(document).off('.extras', caller);
					func_hide();
				}
			});
		}

		is_listening = true;
	});
};

var CONTENT = {
	getIdentifierModule: function(elm) {
		
		var elm = $(elm);
		
		var mod_id = getModID(elm);
		
		if (IS_CMS) {
			var module = mod_id;
		} else {
			var elm_mod = elm.closest('#mod-'+mod_id.split('-')[1]);
			if (elm_mod.length) {
				var classes = elm_mod.attr('class').split(' ');
				var index = classes.indexOf('mod');
				if (index > -1) {
					classes.splice(index, 1);
				}
				var module = classes.join(' ');
			} else {
				var module = elm.closest('[class]').attr('class');
			}
		}
		
		return module;
	},
	getIdentifierName: function(elm) {
		
		var elm = getElement(elm);
		
		var arr_parts = elm.getAttribute('name').split(/\]|\[/);
		arr_parts = $.grep(arr_parts, function(n, i) {
			return (n != '' && n.indexOf('js_') !== 0 && n.indexOf('array_') !== 0);
		});
		var name = arr_parts[arr_parts.length-1];
		
		return name;
	},
	input: {
		setSelectionRange: function(elm_input, pos_start, pos_end) {
			
			var elm_input = $(elm_input);
			
			elm_input.setSelection(pos_start, pos_end);
		},
		setSelectionContent: function(elm_input, obj) {
			
			var elm_input = $(elm_input);
			
			SCRIPTER.triggerEvent(elm_input, 'focus');
								
			var sel = elm_input.getSelection();

			if (obj.replace != undefined) {
				var str = obj.replace;
				elm_input.replaceSelectedText(str);
				sel.end = sel.start+str.length;
			}
			if (obj.before != undefined) {
				var str = obj.before;
				elm_input.insertText(str, sel.start);
				sel.end = sel.end + str.length;
			}
			if (obj.after != undefined) {
				var str = obj.after;
				elm_input.insertText(str, sel.end);
				sel.end = sel.end + str.length;
			}
			
			elm_input.setSelection(sel.start, sel.end);
		},
		getSelectionContent: function(elm_input) {
			
			var elm_input = $(elm_input);
			
			var sel = elm_input.getSelection();
			
			var str = sel.text;
			
			return str;
		},
		getSelectionPosition: function(elm_input) {
			
			var elm_input = $(elm_input);
			
			var sel = elm_input.getSelection();
			
			var arr = {start: sel.start, end: sel.end};
			
			return arr;
		},
		findTag: function(elm_input, str_tag) {
			
			var elm_input = getElement(elm_input);
			
			var patt_exact = new RegExp('\\['+str_tag+'\\]\\[[^\\]]*\\]$');
			var str_selection = CONTENT.input.getSelectionContent(elm_input);
			var arr_pos = CONTENT.input.getSelectionPosition(elm_input);
			
			var has_match_exact = patt_exact.test(str_selection);
			
			if (has_match_exact) {
				return arr_pos;
			}

			var str_text = elm_input.value;
			var str_start = str_text.substring(0, arr_pos.start);
			var str_end = str_text.substring(arr_pos.start);
			
			var patt_open = new RegExp("\\["+str_tag+"\\]\\[[^\\]]*$");
			var patt_close = new RegExp("^[^\\]]*\\]", 'g');
			
			var pos_start = str_start.search(patt_open);
			var pos_end = patt_close.test(str_end);
			pos_end = (pos_end === true ? patt_close.lastIndex : -1);

			if (pos_start >= 0 && pos_end >= 0) {
				return {start: pos_start, end: arr_pos.start + pos_end};
			} else {
				return false;
			}
		},
		findNestedTag: function(elm_input, str_tag) {
			
			var elm_input = $(elm_input);
			
			var patt_exact = new RegExp('\\['+str_tag+'=?[^\\]]*\\](?!\\[)([\\s\\S]*)\\[\/'+str_tag+'\\]$');
			var str_selection = CONTENT.input.getSelectionContent(elm_input);
			var arr_pos = CONTENT.input.getSelectionPosition(elm_input);
			
			var has_match_exact = patt_exact.test(str_selection);
			
			if (has_match_exact) {
				
				var arr_pos_found = CONTENT.input.findNestedTagPosition(elm_input, str_tag, arr_pos.start + str_tag.length + 1);
				
				if (arr_pos_found && str_selection.length == arr_pos_found.end - arr_pos_found.start) {
					
					return arr_pos_found;
				}
			}
			
			return CONTENT.input.findNestedTagPosition(elm_input, str_tag, arr_pos.start);
		},
		findNestedTagPosition: function(elm_input, str_tag, pos) {
			
			var elm_input = getElement(elm_input);
			
			var pos_start;
			var pos_end;

			var str_text = elm_input.value;
			
			var str_find = str_text.substring(0, pos);
			var patt_find = new RegExp("\\[(\/)?"+str_tag+"", 'g');
			var arr_match;
			var count = 0; // We start from the beginning, we're not yet in a tag
			
			var arr_count_pos = [];
			
			while (arr_match = patt_find.exec(str_find)) {
				
				if (arr_match[1] !== undefined) {
					count--;
				} else {
					count++;
					arr_count_pos[count] = patt_find.lastIndex;
				}			
			}
			
			if (count > 0) {
				
				pos_start = arr_count_pos[count] - str_tag.length - 1; // Subtract tag length and 1 for ']'
				
				str_find = str_text.substring(pos);
				patt_find = new RegExp("\\[(\/)?"+str_tag+"", 'g');
				count = 1; // We start from a specific position, asume we're in a tag
				
				while (arr_match = patt_find.exec(str_find)) {
					
					if (arr_match[1] !== undefined) {
						count--;
					} else {
						count++;
					}	

					if (count == 0) {
						
						pos_end = patt_find.lastIndex + 1; // Add 1 for closing ']'
						
						return {start: pos_start, end: pos + pos_end};
					}
				}
			}
			
			return false;
		}
	},
	selectors: {
		selectClosest: function(tag, sel, range, arr_options) {
			
			var elm = false;
			
			if (arr_options.force) { // Auto select when the focus node is the target
				
				elm = $(sel.focusNode);

				if (elm[0].nodeType == 3 || elm[0].tagName.toLowerCase() != tag) {
					elm = elm.closest(tag);
				}
				
				if (!elm.length) {
					elm = false;
				}
			}
			
			if (!arr_options.force || (arr_options.force && !elm.length)) {
				
				var stop = false;
				
				range.getNodes(false, function(node) {

					if (stop || (node != sel.focusNode && node.parentNode != sel.focusNode && node.parentNode != sel.focusNode.parentNode)) { // Only focus node itself, direct children, or siblings
						return;
					}
	
					if (!elm && node.nodeType == 1 && node.tagName.toLowerCase() == tag) {
						elm = node;
					} else if (node.nodeType == 3) { // Do not lose relevant selection on empty text nodes
						
						var text = node.textContent;
						
						if (node == sel.focusNode && (
								((range.startContainer != node || range.startOffset == 0) && range.endOffset == text.length) ||
								(range.endContainer != node && range.startOffset == 0)
							)
						) { // Full & extact text selection inside node, get the node
							elm = node.parentNode;
						} else if (!text) { // Empty, toss it
							elm = elm;
						} else if (node == range.startContainer && text.substring(range.startOffset).trim() == '') { // Spacing before node
							elm = elm;
						} else if (node == range.endContainer && text.substring(0, range.endOffset).trim() == '') { // Spacing after node
							elm = elm;
						} else {
							stop = true;
							elm = false;
						}
					} else {
						stop = true;
						elm = false;
					}
				});
				
				elm = (elm ? $(elm) : $());
			}
			
			if (elm.length) { // Select full node
				range.selectNode(elm[0]);
			} else if (sel.isCollapsed) { // No selection
				return false;
			}
			
			return elm;
		}
	}
};

function LabelOption(elm, arr_options) {
		
	var arr_options = $.extend({
		action: false,
		tag: 'V',
		type: 'label', // label (parse) or code (present) => [tag][id] or [tag=id]value[/tag]
		button: 'label',
		persistent: false,
		select_previous: false,
		select_using_content: false
	}, arr_options);

	var elm = $(elm);
	
	if (elm[0].labeler) {
		return;
	}
	elm[0].labeler = true;
	
	var elm_toolbox = getContainerToolbox(elm);
	var elm_editor = elm.parent('.body-content');
	
	var elm_button_container = $('<div/>')
	.addClass('hide labeler')
	.appendTo(elm_toolbox);
	
	var func_update_selection = function(html) {
	
		if (html) {
			
			CONTENT.input.setSelectionContent(elm, {replace: html});
			
			// Store last selected tag
			if (arr_options.type == 'label') {
				elm[0].labeler_last_tag = html;
			} else {
				var arr_match = html.match(new RegExp("\\["+arr_options.tag+"=?([^\\]]*)\\](?!\\[).*\\[\/"+arr_options.tag+"\\]"));
				elm[0].labeler_last_tag = arr_match[1];
			}						
		}
		
		SCRIPTER.triggerEvent(elm, 'mouseup');
		SCRIPTER.triggerEvent(elm, 'change');
	};
	var func_select_previous = function() {
	
		var str = CONTENT.input.getSelectionContent(elm);
		
		if (arr_options.type == 'label') {
			var str = elm[0].labeler_last_tag;
		} else {
			var str = '['+arr_options.tag+'='+elm[0].labeler_last_tag+']'+str+'[/'+arr_options.tag+']';
		}
		
		CONTENT.input.setSelectionContent(elm, {replace: str});
		
		SCRIPTER.triggerEvent(elm, 'mouseup');
		SCRIPTER.triggerEvent(elm, 'change');
	};
	var func_delete_tag = function() {
		
		var str = CONTENT.input.getSelectionContent(elm);
		
		var arr_match = str.match(new RegExp("\\["+arr_options.tag+"=?[^\\]]*\\](?!\\[)(.*)\\[\/"+arr_options.tag+"\\]$"));
		
		if (arr_match) {
			
			CONTENT.input.setSelectionContent(elm, {replace: arr_match[1]});
			
			SCRIPTER.triggerEvent(elm, 'mouseup');
		}
		
		SCRIPTER.triggerEvent(elm, 'change');
	};

	var elm_button_select = $('<input type="button" class="data add popup labeler" id="'+arr_options.action+'" value="'+arr_options.button+'" />')
	.appendTo(elm_button_container)
	.on('mousedown', function(e) {
						
		var str = CONTENT.input.getSelectionContent(elm);
		
		$(this).data({target: func_update_selection, value: {selected: str, content: elm.val(), name: CONTENT.getIdentifierName(elm), module: CONTENT.getIdentifierModule(elm), type: 'text'}});
	});
	
	if (arr_options.select_using_content) {
		
		var elm_button_select_using_content = $('<input type="button" class="data edit popup labeler" id="'+arr_options.action+'" value="&"  title="Select Tag" />')
		.appendTo(elm_button_container)
		.on('mousedown', function(e) {
			
			var str = CONTENT.input.getSelectionContent(elm);
			
			$(this).data({target: func_update_selection, value: {selected: str, content: elm.val(), select_using_content: true, name: CONTENT.getIdentifierName(elm), module: CONTENT.getIdentifierModule(elm), type: 'text'}});
		});
	}
	
	if (arr_options.select_previous) {
		
		var elm_button_select_last = $('<input type="button" class="data edit labeler" value="+" title="Select Previous Tag" />')
		.addClass('hide')
		.appendTo(elm_button_container)
		.on('mouseup', function(e) {
			
			func_select_previous();
		});
	}
	
	if (arr_options.type == 'code') {
		
		var elm_button_delete = $('<input type="button" class="data del labeler" value="x" title="Delete Tag" />')
		.addClass('hide')
		.appendTo(elm_button_container)
		.on('mouseup', function(e) {
			
			func_delete_tag();
		});
	}
	
	var func_listener_button = false;
				
	var func_check = function(e) {
		
		// Interaction
		
		var cancel = false;
		
		var elm_tools = elm.closest('[data-tools]');
		var has_tools = (!elm_tools.length || elm_tools[0].dataset.tools == '1' ? true : false);

		if (!has_tools) {
			cancel = true;
		}
		
		if (!cancel) {
			
			if (!arr_options.persistent) {
				
				var str_selection = CONTENT.input.getSelectionContent(elm);
			
				if (!str_selection.trim()) {
					cancel = true;
				}
			}
		}
		
		if (cancel) {
			
			if (func_listener_button) {
				
				elm_button_container.addClass('hide');
				
				$(document).off('.labeler', func_listener_button);
				func_listener_button = false;
			}
			
			return;
		}
		
		if (!func_listener_button) {
			
			func_listener_button = function(e2) {
		
				var is_button = hasElement(elm_button_container[0], e2.target, true);
				var has_focus = hasElement(elm[0], e2.target, true);
				
				if ((e2.type != 'ajaxloaded' && !has_focus && !is_button && !elm_button_select[0].loading && (!elm_button_select_using_content || !elm_button_select_using_content[0].loading)) || (e2.type == 'ajaxloaded' && is_button)) {
					
					elm_button_container.addClass('hide');

					$(document).off('.labeler', func_listener_button);
					func_listener_button = false;
				}
			}

			$(document).on('mousedown.labeler keyup.labeler ajaxloaded.labeler', func_listener_button);
		}
		
		// Focus 
		
		if (document.activeElement != elm[0]) {
			SCRIPTER.triggerEvent(elm, 'focus');
		}
		
		// Edit mode

		if (e.type == 'mouseup') {
				
			if (arr_options.type == 'label') { 
				
				var arr_pos = CONTENT.input.findTag(elm, arr_options.tag);
			} else {
				
				var arr_pos = CONTENT.input.findNestedTag(elm, arr_options.tag);
			}
			
			if (arr_pos) {
				
				CONTENT.input.setSelectionRange(elm, arr_pos.start, arr_pos.end);
				
				elm_button_select.addClass('edit');
				if (elm_button_delete) {
					elm_button_delete.removeClass('hide');
				}
			} else {
				
				elm_button_select.removeClass('edit');
				if (elm_button_delete) {
					elm_button_delete.addClass('hide');
				}
			}
		} else if (e.type == 'keyup') {
			
			elm_button_select.removeClass('edit');
			if (elm_button_delete) {
				elm_button_delete.addClass('hide');
			}
		}
		
		// Position
						
		var pos_caret = elm.getCaretPosition();
		var pos_mod = elm_toolbox[0].getBoundingClientRect();
		if (elm_editor.length) {
			var pos_scroll = elm_editor[0].getBoundingClientRect();
			pos_caret.top = pos_caret.top - elm_editor[0].scrollTop;
		} else {
			var pos_scroll = elm[0].getBoundingClientRect();
		}
			
		if (pos_caret.top > pos_scroll.height || pos_caret.top < 0) {
			elm_button_container.css({left: (pos_scroll.left - pos_mod.left) + pos_scroll.width + 3, top: (pos_scroll.top - pos_mod.top) + pos_scroll.height + 3});
		} else {
			elm_button_container.css({left: (pos_scroll.left - pos_mod.left) + pos_caret.left + 6, top: (pos_scroll.top - pos_mod.top) + pos_caret.top + 8});
		}
		
		elm_button_container.removeClass('hide');
		
		if (elm_button_select_last && elm[0].labeler_last_tag) {
			elm_button_select_last.removeClass('hide');	
		}
	};

	elm.on('keyup.labeler mouseup.labeler', function(e) {
		
		if (e.type == 'mouseup') { // Delay a fraction of time to get the latest selection possible
			setTimeout(func_check, 0, e);
		} else {
			func_check(e);
		}
	}).on('focus.labeler', function() {
	
		var elm_toolbox_check = getContainerToolbox(elm);
		if (elm_toolbox_check[0] != elm_toolbox[0]) {
			elm_toolbox = elm_toolbox_check.append(elm_button_container);
		}
	});
	
	var timer_scroll = false;

	var elm_scroll = (elm_editor.length ? elm_editor : elm);
	
	elm_scroll.on('scroll.labeler', function(e) {
		
		if (timer_scroll) {
			clearTimeout(timer_scroll);
		} else {				
			elm_button_container.addClass('hide');
		}
		
		timer_scroll = setTimeout(function() {
			
			timer_scroll = false;
			func_check(e);
		}, 400);
	});
	
	if (arr_options.type == 'label' && elm.is('input')) {
		
		var str_value = '';
		
		elm.on('mouseenter', function(e) {
			
			var str_value_check = elm.val();
			var patt = new RegExp("\\["+arr_options.tag+"\\]\\[");
			
			if (str_value == str_value_check || !patt.test(str_value_check)) {
				
				if (str_value != str_value_check) {
					
					elm.removeAttr('title');
					TOOLTIP.update();
				}
				
				return;
			}
			
			str_value = str_value_check;
			
			var elm_label = $('<input type="hidden" id="y:cms_general:get_text-0" value="" />').appendTo(elm_toolbox);
			elm_label.val(str_value);
			
			elm_label.quickCommand(function(data) {
				
				elm.attr('title', data);
				TOOLTIP.update();
				
				elm_label.remove();
			});
		});
	}
}

function EditContent(elm, arr_options) {
	
	var SELF = this;
	
	var arr_options = $.extend({
		inline: false,
		external: false,
		class: ''
	}, arr_options);
	
	var elm = $(elm);
	
	if (elm[0].has_edit_content) {
		return;
	}
	elm[0].has_edit_content = true;
	
	var elm_toolbox = getContainerToolbox(elm);
	
	var elm_container = $('<div class="input editor-content"></div>').insertBefore(elm);
	elm_container[0].edit_content = this;
	
	var elm_menu = $('<menu class="editor"></menu>').appendTo(elm_container);
	
	this.addButton = function(arr, elm_content, call, elm_before) {
		
		var elm_button = $('<button type="button" title="'+arr.title+'"'+(arr.id ? ' id="'+arr.id+'"' : '')+(arr.class ? ' class="'+arr.class+'"' : '')+'>'+(elm_content ? elm_content : '<span class="icon"></span>')+'</button>');
		elm_button.on('click', call);
		
		if (elm_before) {
			elm_button.insertBefore(elm_before);
		} else {
			elm_button.appendTo(elm_menu);
		}
		
		return elm_button;
	};
	
	this.addSeparator = function(elm_before) {
		
		var elm_separator = $('<span class="split" />');
		
		if (elm_before) {
			elm_separator.insertBefore(elm_before);
		} else {
			elm_separator.appendTo(elm_menu);
		}
	};
	
	this.createTag = function(elm_button) {
		
		var str_selected = CONTENT.input.getSelectionContent(elm);
		
		COMMANDS.setData(elm_button, {selected: str_selected});
		
		COMMANDS.setTarget(elm_button, function(arr_data) {
			
			CONTENT.input.setSelectionContent(elm, {replace: arr_data.tag});
			
			SCRIPTER.triggerEvent(elm, 'change');
		});
		
		COMMANDS.popupCommand(elm_button);
	};
	
	var elm_button_header = SELF.addButton({title: 'Heading', class: 'heading'}, '<span><span>H</span><span>1</span><span>2</span><span>3</span></span>', function() {
		
		var str_selected = CONTENT.input.getSelectionContent(elm);
		
		CONTENT.input.setSelectionContent(elm, {before: '[h=1]', after: '[/h]'});
				
		SCRIPTER.triggerEvent(elm, 'change');
	});
	var elm_button_bold = SELF.addButton({title: 'Bold', class: 'bold'}, '<span>B</span>', function() {
		
		var str_selected = CONTENT.input.getSelectionContent(elm);
		
		CONTENT.input.setSelectionContent(elm, {before: '[b]', after: '[/b]'});
				
		SCRIPTER.triggerEvent(elm, 'change');
	});
	var elm_button_italic = SELF.addButton({title: 'Italic', class: 'italic'}, '<span>i</span>', function() {
		
		var str_selected = CONTENT.input.getSelectionContent(elm);
		
		CONTENT.input.setSelectionContent(elm, {before: '[i]', after: '[/i]'});
				
		SCRIPTER.triggerEvent(elm, 'change');
	});
	var elm_button_quote = SELF.addButton({title: 'Quote', id: 'y:cms_general:create_quote-0', class: 'quote'}, false, function() {
		SELF.createTag(elm_button_quote);
	});
	var elm_button_url = SELF.addButton({title: 'URL', id: 'y:cms_general:create_url-0'}, false, function() {
		SELF.createTag(elm_button_url);
	});
	
	if (IS_CMS) {
		
		var elm_button_pages = SELF.addButton({title: 'Page Link', id: 'y:pages:popup_link-0'}, false, function() {
			
			COMMANDS.setTarget(elm_button_pages, function(link) {
				
				CONTENT.input.setSelectionContent(elm, {before: '[url='+link+']', after: '[/url]'});
				
				SCRIPTER.triggerEvent(elm, 'change');
			});
			COMMANDS.popupCommand(elm_button_pages);				
		});
		var elm_button_image = SELF.addButton({title: 'Image', id: 'y:cms_media:media_popup-'+(arr_options.external ? 1 : 0)}, false, function() {
			
			COMMANDS.setTarget(elm_button_image, function(code) {
				
				CONTENT.input.setSelectionContent(elm, {replace: '[img]'+code+'[/img]'});
				
				SCRIPTER.triggerEvent(elm, 'change');
			});
			COMMANDS.popupCommand(elm_button_image);
		});
	}

	var elm_button_clean = SELF.addButton({title: 'Remove tags'}, false, function() {
		
		var str_selected = CONTENT.input.getSelectionContent(elm);
		
		CONTENT.input.setSelectionContent(elm, {replace: str_selected.replace(/\[(.*?)\]/g, '')});
				
		SCRIPTER.triggerEvent(elm, 'change');
	});
	
	SELF.addSeparator();
	
	var elm_button_preview = SELF.addButton({title: 'Preview', id: 'y:cms_general:preview-'+elm[0].name}, false, function() {
		
		COMMANDS.popupCommand(elm_button_preview);
	});
	
	ASSETS.getIcons(elm, ['link', 'quote-right', 'image', 'pages', 'cross', 'view'], function(data) {
		
		if (IS_CMS) {
			
			elm_button_image[0].children[0].innerHTML = data.image;
			elm_button_pages[0].children[0].innerHTML = data.pages;
		}
		
		elm_button_url[0].children[0].innerHTML = data.link;
		elm_button_quote[0].children[0].innerHTML = data['quote-right'];
		elm_button_clean[0].children[0].innerHTML = data.cross;
		elm_button_preview[0].children[0].innerHTML = data.view;
	});
		
	var elm_content = $('<div class="body-content"></div>').appendTo(elm_container);
	
	if (elm[0].classList.contains('inline')) {
		elm_container[0].classList.add('inline');
	}
	elm.removeClass('body-content');
	
	elm_content.append(elm);
	
	var elm_highlight = $('<pre><code></code></pre>').appendTo(elm_content);
	var elm_highlight_code = elm_highlight.children('code');
	
	var is_hidden = false;
	
	var func_set_textarea_height = function() {
		
		if (isHidden(elm[0])) {
			
			if (is_hidden) {
				return;
			}
			is_hidden = true;
				
			elm.closest('.tabs > div').one('open', function() {
				
				is_hidden = false;
				func_set_textarea_height();
			});
		}
		
		elm[0].style.height = 'auto'; // Reset height to check actual height
			
		var arr_style = window.getComputedStyle(elm[0]);
		var int_height_full = elm[0].scrollHeight + parseInt(arr_style['border-top-width']) + parseInt(arr_style['border-bottom-width']);
		var int_height_available = elm_content[0].clientHeight;

		if (int_height_available > int_height_full) {
			elm[0].style.height = int_height_available+'px';
		} else {
			elm[0].style.height = int_height_full+'px';
		}
	};
	
	var func_highlight = function() {

		var str_content = elm[0].value;
		
		if (has_tools) {
			
			str_content = Prism.highlight(str_content, Prism.languages.CC1100);
			
			elm_highlight_code[0].innerHTML = str_content;
			
			//worker.postMessage({str: str_content});
		} else {
			
			elm_highlight_code[0].innerHTML = func_escape_html(str_content);
		}
	};

	/*var func = function() {
		
		var obj_library = false;
		
		onmessage = function(event) {
			
			if (event.data.library) {
				obj_library = event.data.library;
			} else if (!obj_library) {
				return;
			}
			
			if (event.data.str) {
				
				var str_content = event.data.str;
				
				str_content = Prism.highlight(str_content, obj_library);
				
				postMessage({str: str_content});
			}
		};
	};
	
	var func_before = function() {
		
		self.Prism = {disableWorkerMessageHandler: true};
	}

	var worker = ASSETS.createWorker(func, ['/CMS/js/PrismJS.js'], func_before);
	
	worker.postMessage({library: Prism.languages.CC1100});
	
	worker.addEventListener('message', function(event) {
		
		var str_content = event.data.str;
		
		if (str_content == undefined) {
			return;
		}
		
		elm_highlight_code[0].innerHTML = str_content;
	});*/

	var func_escape_html = function(str_unsafe) {
		
		return str_unsafe
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#039;");
	};
	
	var elm_tools = elm.closest('[data-tools]');
	var has_tools = (!elm_tools.length || elm_tools[0].dataset.tools == '1' ? true : false);
	
	elm_container.on('toolsdisable', function() {
		has_tools = false;
		SELF.update();
	}).on('toolsenable', function() {
		has_tools = true;
		SELF.update();
	});

	this.update = function() {
		
		func_set_textarea_height();
		func_highlight();
	};
	
	new ResizeSensor(elm_content[0], function() {
		
		func_set_textarea_height();
	});
	
	elm[0].addEventListener('input', SELF.update, true); // User
	elm[0].addEventListener('change', SELF.update, true); // Program
	
	SELF.update();
	
	SCRIPTER.triggerEvent(elm, 'editorloaded', {source: elm[0], editor: elm_container[0], content: elm_content[0]});
}

Prism.languages.markup = {
	'comment': /<!--[\s\S]*?-->/,
	'prolog': /<\?[\s\S]+?\?>/,
	'doctype': /<!DOCTYPE[\s\S]+?>/i,
	'cdata': /<!\[CDATA\[[\s\S]*?]]>/i,
	'tag': {
		pattern: /<\/?(?!\d)[^\s>\/=$<%]+(?:\s+[^\s>\/=]+(?:=(?:("|')(?:\\[\s\S]|(?!\1)[^\\])*\1|[^\s'">=]+))?)*\s*\/?>/i,
		greedy: true,
		inside: {
			'tag': {
				pattern: /^<\/?[^\s>\/]+/i,
				inside: {
					'punctuation': /^<\/?/,
					'namespace': /^[^\s>\/:]+:/
				}
			},
			'attr-value': {
				pattern: /=(?:("|')(?:\\[\s\S]|(?!\1)[^\\])*\1|[^\s'">=]+)/i,
				inside: {
					'punctuation': [
						/^=/,
						{
							pattern: /(^|[^\\])["']/,
							lookbehind: true
						}
					]
				}
			},
			'punctuation': /\/?>/,
			'attr-name': {
				pattern: /[^\s>\/]+/,
				inside: {
					'namespace': /^[^\s>\/:]+:/
				}
			}

		}
	},
	'entity': /&#?[\da-z]{1,8};/i
};
Prism.languages.CC1100 = Prism.languages.extend('markup', {
	'cc1100tag': {
		pattern: /\[\/?[^\s\]\/=\[]+(?:=(?:(?:\\[\s\S]|(?!\1)[^\\])*\1|[^\]=]+)?)*\]/i,
		greedy: true,
		inside: {
			'cc1100tag': {
				pattern: /^\[\/?[^=\]\/]+/i,
				inside: {
					'cc1100punctuation': /^\[\/?/
				}
			},
			'cc1100tag-attribute': {
				pattern: /=(?:(?:\\[\s\S]|(?!\1)[^\\])*\1|[^\]=]+)/i,
				inside: {
					'cc1100punctuation': /^=/
				}
			},
			'cc1100punctuation': /\]/
		}
	},
	'cc1100flag': {
		pattern: /\[\[[a-z0-9]+\]\]/i,
		greedy: true,
		inside: {
			'cc1100punctuation': /[\[\]]/
		}
	},
	'cc1100variable': {
		pattern: /\[[a-z0-9]+\]\[[^\]]*\]/i,
		greedy: true,
		inside: {
			'cc1100variable': {
				pattern: /^\[[a-z0-9]+\]/i,
				inside: {
					'cc1100punctuation': /[\[\]]/
				}
			},
			'cc1100variable-value': {
				pattern: /\[[^\]]*\]$/i,
				inside: {
					'cc1100punctuation': /[\[\]]/
				}
			}
		}
	}
});
	
var counter_filebrowse = 0;
	
function FileBrowse(elm) {
	
	var SELF = this;
	
	var elm = $(elm);
	
	if (elm[0].filebrowse) {
		return;
	}
	elm[0].filebrowse = this;
	
	var elm_input_file = elm.find('.select > input[type=file]');
	var elm_path = elm.find('.select > label > input[type=text]');
	
	counter_filebrowse++;
		
	elm_input_file.attr('id', 'filebrowse-'+counter_filebrowse);
	elm_input_file.next('label').attr('for', 'filebrowse-'+counter_filebrowse);
	
	var func_change = function() {
		
		var arr_files = (elm_input_file[0].files ? elm_input_file[0].files : false);
		var elm_files = elm.children('ul');
		
		elm_files.empty();
		
		if (arr_files && arr_files.length > 1) {
			
			elm_path.val(arr_files.length+'x');
			
			for (var i = 0; i < arr_files.length; i++) {
				elm_files.append('<li>'+arr_files[i].name+'</li>');
			}
		} else {
			
			elm_path.val((arr_files ? arr_files[0].name : ''));
		}
	};
	
	elm_input_file[0].addEventListener('change', func_change);
}

function LazyLoad(elm, elm_scroll) {
	
	var SELF = this;
	
	var elm = $(elm);
	
	if (elm[0].lazyload) {
		return;
	}
	elm[0].lazyload = this;
	
	var is_document = false;
		
	if (elm_scroll) {
		var elm_scroll = getElement(elm_scroll);
	} else {
		var elm_scroll = window;
		is_document = true;
	}
	
	var elms_not_loaded = elm.find('img[data-original]');
	
	this.load = function() {

		// Define view frame
		var pos = (is_document ? {top: 0, left: 0, width: window.innerWidth, height: window.innerHeight} : elm_scroll.getBoundingClientRect());
		var pos_bottom = pos.top + pos.height;
		var pos_right = pos.left + pos.width;

		elms_not_loaded = elms_not_loaded.map(function() {

			var elm_image = $(this);

			var pos_img = elm_image[0].getBoundingClientRect();
			var pos_img_bottom = pos_img.top + pos_img.height;
			var pos_img_right = pos_img.left + pos_img.width;

			if (((pos_img_bottom >= pos.top && pos_img_bottom <= pos_bottom) || (pos_img.top >= pos.top && pos_img.top <= pos_bottom) || (pos_img_bottom >= pos_bottom && pos_img.top <= pos.top))
				&& 
				((pos_img_right >= pos.left && pos_img_right <= pos_right) || (pos_img.left >= pos.left && pos_img.left <= pos_right) || (pos_img_right >= pos_right && pos_img.left <= pos.left))) {

				elm_image
					.css('opacity', 0)
					.attr('src', elm_image.attr('data-original'))
					.on('load', function() {
						elm_image
							.removeAttr('data-original')
							.fadeTo('fast', 1);
					});
				
				return null; // Remove from elms_not_loaded
			}
			
			return this; // Keep in elms_not_loaded
		});
		
		if (!elms_not_loaded.length) {
			
			elm_scroll.removeEventListener('scroll', SELF.load);
			elm[0].lazyload = null;
		}
	};
	
	elm_scroll.addEventListener('scroll', SELF.load);
	
	SELF.load();
}

function ImagesLoaded(elm, callback) {
	
	var elm = getElement(elm);
	
	var elms_img = elm.querySelectorAll('img');
	var count = 0;
	
	if (!elms_img.length) {
		callback();
		return;
	}
	
	var func_check = function() {
		
		count++;
		
		if (count == elms_img.length) {
			callback();
		}
	};
	
	for (var i = 0, len = elms_img.length; i < len; i++) {
		
		var elm_img = elms_img[i];
		
		if (elm_img.complete) {
			func_check();
		} else {
			elm_img.addEventListener('load', func_check);
		}
	}
}

function FormManager(elm, arr_options) {
	
	var SELF = this;
	
	var arr_options = arr_options;
	
	var elm = getElement(elm);
	
	if (elm.formmanager) {
		return;
	}
	elm.formmanager = this;
					
	elm.addEventListener('ajaxsubmit', function(e) {
	
		runElementSelectorFunction(elm, '.sorter', function(elm_found) {
			
			if (elm_found.matches('form .sorter .sorter')) { // Sorters could be nested, make sure only the top one is used
				return;
			}
			
			var elm_sorter = $(elm_found);
			
			elm_sorter.find('input[type=checkbox]:not(:checked), select:empty').each(function() {
				
				var elm_input = $(this);
				elm_input.after('<input type="hidden" value="" class="temp" name="'+elm_input.attr('name')+'" />');
			});
			
			elm_sorter.find(':disabled').prop('disabled', false).attr('data-disabled', true);
		});
	});
	
	elm.addEventListener('ajaxsubmitted', function(e) {
		
		runElementSelectorFunction(elm, '.sorter', function(elm_found) {
			
			if (elm_found.matches('form .sorter .sorter')) { // Sorters could be nested, make sure only the top one is used
				return;
			}
			
			var elm_sorter = $(elm_found);
		
			elm_sorter.find('input[type=hidden].temp').remove();
			
			elm_sorter.find('[data-disabled]').prop('disabled', true).removeAttr('data-disabled');
		});
	});
	
	elm.addEventListener('reset', function(e) {
		
		runElementSelectorFunction(elm, '.sorter', function(elm_found) {
			
			if (elm_found.matches('form .sorter .sorter')) { // Sorters could be nested, make sure only the top one is used
				return;
			}
			
			elm_found.sorter.reset();
		});
		
		runElementSelectorFunction(elm, 'input.autocomplete', function(elm_found) {
			
			elm_found.autocompleter.reset();
		});
	});
}

// AutoComplete

FEEDBACK.addValidatorMethod('required_autocomplete', function(value, elm) {
	
	var elms_value = $(elm).next('.autocomplete').children('ul').find('input');
	
	return (elms_value.length ? true : false);
});

function AutoCompleter(elm, arr_options) {
	
	var SELF = this;
	
	var arr_options = $.extend({
		multi: false,
		name: '',
		delay: 0
	}, arr_options);

	var cur = $(elm);
	
	if (cur[0].autocompleter) {
		return;
	}
	cur[0].autocompleter = this;
	
	cur[0].autocomplete = 'off'; // Disable browser property
	
	var elm_toolbox = getContainerToolbox(cur);
	var elm_dropdown = $('<ul class="dropdown hide"></ul>').appendTo(elm_toolbox);
	
	var arr_elm = {};
	var value_default = false;
	
	if (arr_options.multi) {
		
		arr_elm.values = cur.prev('.autocomplete').find('ul');
		arr_elm.input_id = cur.prev('.autocomplete').prev('input:hidden');
		if (!arr_options.name) {
			arr_options.name = arr_elm.input_id.attr('name');
		}
		
		ASSETS.getIcons(cur, ['min'], function(data) {
			
			elms_tags = arr_elm.values[0].getElementsByClassName('handler');
			
			for (var i = 0, len = elms_tags.length; i < len; i++) {
			
				elms_tags[i].innerHTML = '<span class="icon">'+data.min+'</span>';
			}
		});
		
		var elms_input = arr_elm.values[0].querySelectorAll('input');
		
		if (elms_input) {
			
			value_default = [];
		
			for (var i = 0, len = elms_input.length; i < len; i++) {
				
				var str_value = '';
				var elm_node = elms_input[i];
				
				while (elm_node = elm_node.nextSibling) {
					str_value += (elm_node.nodeType == 3 ? elm_node.textContent : elm_node.innerHTML);
				}
				
				value_default.push([str_value, elms_input[i].value]);
			}
		}
	} else {
		
		arr_elm.input_id = cur.prev('input:hidden');
		
		if (arr_elm.input_id.val()) {
			value_default = [cur.val(), arr_elm.input_id.val()];
		}
	}
	
	var input = cur.val();
	
	var value_input = input;
	var value_stored = input;
	cur.attr('placeholder', input);
	
	var timer_delay = false;
	
	var dropdown_is_opening = false;
	var dropdown_is_open = false;
	
	var func_update = function(e) {
		
		dropdown_is_opening = true;
		
		var func_call = function() {
			
			if (timer_delay) {
				cur.removeClass('waiting');
				timer_delay = false;
			}

			FEEDBACK.stop(cur);
			cur.quickCommand(function(arr) {
				
				SELF.position(true);
				
				if (!arr) {
					return;
				}

				for (var i = 0, len = arr.length; i < len; i++) {

					var cur_value = arr[i];
					
					var elm = $('<li>');
					var elm_label = $('<a tabindex="-1">'+cur_value.label+'</a>').appendTo(elm);
					if (cur_value.title) {
						elm_label.attr('title', cur_value.title);
					}
					var target = elm_label.children();
					
					if (target.length && target.attr('id')) {
						target.data({
							target: function(data) {
								if (data.id) {
									SELF.add(data.value, data.id);
								}
							},
							options: {overlay: cur.closest('.mod')}
						});
					}

					elm_label[0].autocomplete_value = cur_value;
					
					elm.appendTo(elm_dropdown);							
				}
			});
		};
		
		if (arr_options.delay) {
			
			if (timer_delay) {
				clearTimeout(timer_delay);
			} else {
				cur.addClass('waiting');
			}
			
			if (cur.val()) { // Only delay when there is a value
				timer_delay = setTimeout(func_call, arr_options.delay * 1000);
				return;
			} else {
				timer_delay = true;
			}
		}
		
		func_call();
	};
	
	// Input interaction
	
	cur.on('keyup.autocomplete', function(e) {
		
		if (e.which == 27 && value_input && (dropdown_is_open || dropdown_is_opening)) { // Key escape
			
			SELF.close();
			SCRIPTER.triggerEvent(cur, 'focus');
		} else if (e.which == 40 && value_input && dropdown_is_open) { // Key down
			
			SCRIPTER.triggerEvent(elm_dropdown.find('a:first'), 'focus');
			e.stopPropagation();
		} else if (value_input != cur.val()) { // New input value
			
			value_input = cur.val();
			func_update();
		} else if ((e.which == 8 || e.which == 46) && !value_input && !cur.val()) { // Backspace or delete
			
			arr_elm.input_id.val('');
			value_stored = '';
			value_input = '';
			cur.attr('placeholder', '');
		}
	}).on('click.autocomplete focus.autocomplete', function(e) {
		
		if (!dropdown_is_open && !dropdown_is_opening) {
			
			cur.val('');
			value_input = '';
			
			func_update();
		}
	});
	
	// Dropdown interaction
	
	elm_dropdown.on('mousedown', '> li > a', function(e) {
		
		e.preventDefault();
	}).on('keydown.autocomplete', function(e) {

		if (e.which == 37 || e.which == 39 || e.which == 38 || e.which == 40 || e.which == 13) { // Key left, right, up, down, enter
			e.preventDefault();
			e.stopPropagation();
		}
	}).on('mouseenter.autocomplete focus.autocomplete', '> li > a', function(e) {
		
		var elm = $(this);
		
		elm_dropdown.find('a').removeClass('active');
		elm.addClass('active');
	}).on('click.autocomplete enter.autocomplete', '> li > a', function(e) {
		
		var elm = $(this);
		var value = elm[0].autocomplete_value;
		var target = elm.children();
		
		if (target.length && target.is('[type=button]')) {
			
			if (target[0] != e.target) {
				SCRIPTER.triggerEvent(target, 'click');
			}
		} else {
			
			if (value.value) {
				SELF.add(value.value, value.id);
			}

			if (e.type == 'enter') { // On keys interaction keep the dropdown active
				SELF.close();
				SCRIPTER.triggerEvent(cur, 'focus');
			} else if (arr_options.multi) { // On mouse with a multi selector, keep focus, but remove dropdown
				SCRIPTER.triggerEvent(cur, 'focus');
				SELF.close();
			} else { // On mouse with single select, no focus and no dropdown
				SELF.close();
				SCRIPTER.triggerEvent(cur, 'blur');
			}
		}
	}).on('keyup.autocomplete', '> li > a', function(e) {
		
		var elm = $(this);
		
		if (e.which == 27 || e.which == 8 || (e.which == 38 && elm.parent('li').is(':first-child'))) { // Key escape, backspace or up
			
			e.stopPropagation();
			SELF.close();
			SCRIPTER.triggerEvent(cur, 'focus');
		} else if (e.which == 38 || e.which == 40) { // Key up, down
			
			var elm_target = elm.closest('li');
			elm_target = (e.which == 38 ? elm_target.prev() : elm_target.next());
			elm_target = elm_target.children('a');
			
			if (elm_target.length) {
				SCRIPTER.triggerEvent(elm_target, 'focus');
			}
		} else if (e.which == 13) { // Key enter
			
			SCRIPTER.triggerEvent(elm, 'enter');
		} 
	});
	
	if (arr_options.multi) {
		
		arr_elm.values.on('click.autocomplete', 'li > span:last-child', function() {
			
			$(this).parent().remove();
		});
		
		TOOLTIP.checkElement(arr_elm.values[0], 'li > span:first-child', function(elm) {

			var arr_style = window.getComputedStyle(elm);
			
			var width_max = parseInt(arr_style['max-width']);
			
			if (width_max && parseInt(arr_style['width']) == width_max) {
				return elm.innerHTML;
			}
			
			return false;
		});
	} else {
		
		TOOLTIP.checkElement(cur[0], false, function(elm) {
			
			var width = getInputTextSize(elm);
			var arr_style = window.getComputedStyle(elm);
			
			if (width > parseInt(arr_style['width'])) {
				return elm.value;
			}
			
			return '';
		});
	}
	
	var func_position = function() {};
	
	this.position = function(empty) {
			
		var elm_toolbox_check = getContainerToolbox(cur);
		
		if (elm_toolbox_check[0] != elm_toolbox[0]) {
			elm_toolbox = elm_toolbox_check.append(elm_dropdown);
		}

		if (empty) {
			elm_dropdown.empty().removeClass('hide');
		}
		
		var pos_mod = elm_toolbox[0].getBoundingClientRect();
		var pos = cur[0].getBoundingClientRect();
		elm_dropdown.css({'min-width': pos.width, left: (pos.left - pos_mod.left), top: (pos.top - pos_mod.top) + pos.height});
		
		if (!dropdown_is_open) {
			
			func_position = function(e) {
				
				if ((cur[0] != e.target && !cur.is(':focus') && !hasElement(elm_dropdown[0], e.target) && !(e.type == 'focusin' && $(e.target).is('.dialog'))) || (e.type == 'ajaxloaded' && hasElement(elm_dropdown[0], e.target)) || !dropdown_is_open) {
					if (onStage(cur[0])) {
						SELF.close()
					} else {
						$(document).off('.autocomplete', func_position);
					}
				}
			};
						
			$(document).on('mouseup.autocomplete keyup.autocomplete focusin.autocomplete ajaxloaded.autocomplete', func_position);
		}
		
		dropdown_is_open = true;
	};
	
	this.close = function() {
				
		FEEDBACK.stop(cur);
		dropdown_is_opening = false;
		
		elm_dropdown.addClass('hide').empty();

		cur.val(value_stored);
		value_input = value_stored;

		$(document).off('.autocomplete', func_position);
		dropdown_is_open = false;
	};
	
	this.add = function(value, id) {
		
		if (!arr_elm.input_id.length) {
			return;
		}
		
		if (arr_options.multi) {
			
			var elm_tag = $('<li><span><input type="hidden" name="'+arr_options.name+'['+id+']" value="'+id+'" />'+value+'</span><span class="handler"></span></li>');
			arr_elm.values.append(elm_tag);
			
			ASSETS.getIcons(cur, ['min'], function(data) {
				
				elm_tag[0].getElementsByClassName('handler')[0].innerHTML = '<span class="icon">'+data.min+'</span>';
			});

			var input = '';
			cur.val(input);
		} else {
			
			var input = decodeHTMLSpecialChars(value);
			cur.val(input);
			
			TOOLTIP.recheckElement(cur[0]);
		}

		value_input = input;
		value_stored = input;
		cur.attr('placeholder', input);
		
		arr_elm.input_id.val(id);
		
		SCRIPTER.triggerEvent(arr_elm.input_id, 'change');
	};
	
	this.reset = function() {
		
		SELF.clear();
		
		if (value_default) {
			
			if (arr_options.multi) {
				for (var i = 0, len = value_default.length; i < len; i++) {
					SELF.add(value_default[i][0], value_default[i][1]);
				}
			} else {
				SELF.add(value_default[0], value_default[1]);
			}
		}
	};
	
	this.clear = function() {
		
		if (arr_options.multi) {
			arr_elm.values.empty();
		}
		
		cur.val('');
		
		value_input = '';
		value_stored = '';
		cur.attr('placeholder', '');
		
		arr_elm.input_id.val('');
	};
}

$.fn.autocomplete = function() {
	
	var obj = new ElementObjectByParameters(AutoCompleter, 'autocompleter', arguments);
	
	return this.each(obj.run);
};

// sorter

function Sorter(elm, arr_options) {
	
	var SELF = this;
	
	var arr_options = $.extend({
		controls: false,
		prepend: false,
		auto_add: false
	}, arr_options || {});
	
	var cur = $(elm);
	
	if (cur[0].sorter) {
		return;
	}
	cur[0].sorter = this;
	
	var html_source = false;
	var arr_html_default = [];

	var elm_source = cur.children('li.source');
	
	if (elm_source.length) {
		elm_source.removeClass('source').remove();
		var html_source = elm_source[0].outerHTML;
	}
	
	var elms_row = cur.children('li');

	if (!elm_source.length && !elms_row.length) {
		return;
	}
	
	var elm_first = elms_row.first();
	
	if (!elm_source.length) {
		var html_source = elm_first[0].outerHTML;
		var is_copy = true;
	}
	
	elms_row.each(function() {
		arr_html_default.push(this.outerHTML);
	});
										
	if (elm_first.children('span').length || elm_first.find('.handle').length) {
		
		var selector_handle = '.handle';
		
		var elm_span = elm_first.children('span');
		if (elm_span.length) {
			selector_handle = (elm_span.first().is(':last-child') ? '> li > span:last-child' : '> li > span:first-child');
		}
		
		new SortSorter(cur, {
			items: '> li',
			handle: selector_handle
		});		
	}
	
	if (arr_options.controls) {
		
		arr_options.controls.off('.sorter').on('click.sorter', '.add', function() {
			SELF.addRow();
		}).on('click.sorter', '.del', function() {
			SELF.clean();
		});
	}
	if (arr_options.auto_add) {
		
		cur.on('focus.sorter click.sorter', '> li:last-child', function(e) {
			
			if ($(e.target).is('[type=button], [type=submit]')) {
				return;
			}
			
			SELF.addRow({focus: false});
		});
	}

	this.getSource = function() {
		
		return html_source;
	};
	this.setSource = function(html, is_copy) {
		
		html_source = html;
		is_copy = (is_copy ? is_copy : false);
	};

	this.addRow = function(arr_options_row) {
		
		var arr_options_row = $.extend({
			focus: true
		}, arr_options_row || {});
					
		var elm_target = $(html_source);
		
		if (is_copy) {
			SELF.resetRow(elm_target);
		}
		
		// Manage dynamic array-in-name setting
		
		var num_replace = (elm_target.find('.sorter').length + 1);
		var elm_targets = elm_target.find('input, select, textarea');
		
		replaceArrayInName(elm_targets, num_replace);
		
		// Add new row
		
		if (arr_options.prepend) {
			elm_target.prependTo(cur);
		} else {
			elm_target.appendTo(cur);
		}
		
		if (arr_options_row.focus) {
			SCRIPTER.triggerEvent(elm_targets.first(), 'focus');
		}
		SCRIPTER.triggerEvent(cur, 'ajaxloaded', {elm: elm_target});
	};
	
	this.resetRow = function(elm_row) {

		var elm_inputs = elm_row.find('input, select, textarea');

		elm_inputs.prop('disabled', false);
		elm_inputs.not('[type=checkbox], [type=radio], select, [type=button], [type=submit]').val('');
		elm_inputs.filter('[type=checkbox], [type=radio]').prop('checked', function () {
			return this.getAttribute('checked') == 'checked';
		});
		elm_inputs.filter('select').prop('selectedIndex', 0);
		elm_row.find('.autocomplete.tags > ul').empty();
	};
	
	this.clean = function() {
	
		var elms_target = cur.children('li');
		var elms_remove = elms_target.filter(function() {
			return (!$(this).find('input, select:has(option[value=""])').first().val()); // Empty means the first meaningful element (i.e. any input element or a select with the possibility for '') is empty
		});
		
		if (elms_target.length == elms_remove.length) { // Keep at least one item
			elms_remove = elms_remove.slice(1);
		}
		
		elms_remove.remove();
	};
	
	this.clear = function() {
		
		var elms_target = cur.children('li');

		elms_target.remove();
		
		SELF.addRow();
	};
	
	this.reset = function() {
		
		var elms_target = cur.children('li');

		elms_target.remove();
		
		for (var i = 0, len = arr_html_default.length; i < len; i++) {
			
			var elm_target = $(arr_html_default[i]);
			
			cur.append(elm_target);
			
			SCRIPTER.triggerEvent(cur, 'ajaxloaded', {elm: elm_target});
		}
	};
}

$.fn.sorter = function() {
	
	var obj = new ElementObjectByParameters(Sorter, 'sorter', arguments);
	
	return this.each(obj.run);
};

// SortSorter
	
var counter_sortsorter = 0;

function SortSorter(elm, arr_options) {
	
	var SELF = this;
	
	var arr_options = $.extend({
		container: 'ul',
		items: '> li',
		handle: '> li > span:first-child',
		nested: false,
		func_obj: false,
		placeholder_class: false,
		call_start: false,
		call_stop: false,
		call_update: false
	}, arr_options || {});
	
	var elm = getElement(elm);
	
	if (elm.sortsorter) {
		elm.sortsorter.destroy();
	}
	elm.sortsorter = this;
	
	var func_init = function(e) {
		
		var connect = false;
		
		if (arr_options.nested) {
			
			var elm_target = e.target.closest(arr_options.container);
			var elm_container = elm_target;
		} else {
			
			var elm_target = elm;
			elm_container = elm;
			
			if (!elm_target.matches(arr_options.container)) {
				connect = true;
				var elm_container = elm_target.querySelectorAll(arr_options.container);
			}
			
			elm.removeEventListener('mousedown', func_init, true);
		}
	
		if (elm_target.arr_sortsortables) {
			
			if (arr_options.nested) {
				return;
			}
			
			for (var i = 0; i < elm_target.arr_sortsortables.length; i++) { // Prepare to re-init the sortable
				elm_target.arr_sortsortables[i].destroy();
			}
		}
		elm_target.arr_sortsortables = [];
		
		var identifier = counter_sortsorter + 1;
		counter_sortsorter++;
	
		var obj_state = (arr_options.func_obj ? arr_options.func_obj() : false);
		
		if (!connect) {
			elm_container = [elm_container];
		}
		
		for (var i = 0; i < elm_container.length; i++) {
			
			elm_container[i].setAttribute('data-sortable_identifier', identifier);
			
			var arr_settings = {
				animation: 150,
				draggable: arr_options.container+'[data-sortable_identifier="'+identifier+'"] '+arr_options.items,
				handle: arr_options.container+'[data-sortable_identifier="'+identifier+'"] '+arr_options.handle,
				group: 'connect_'+identifier,
				ghostClass: 'sortsorter-placeholder',
				chosenClass: 'sortsorter-dragging'
			}
			
			if (arr_options.call_start) {
				
				arr_settings.onStart = function(e2) {
					arr_options.call_start($(e2.item), obj_state);
				};
			}											
			if (arr_options.call_stop) {
				
				arr_settings.onEnd = function(e2) {
					arr_options.call_stop($(e2.item), obj_state);
				};
			}
			if (arr_options.call_update) {
				
				arr_settings.onSort = function(e2) {
					arr_options.call_update($(e2.item), obj_state);
				};
			}
			
			elm_target.arr_sortsortables.push(new Sortable(elm_container[i], arr_settings));
		}
	};
	
	this.destroy = function() {
		
		elm.removeEventListener('mousedown', func_init, true);
	};
	
	elm.addEventListener('mousedown', func_init, true); // Set mousedown event to capture (tickle down) mode to be caught by the sortable handler.
}

// NavigationTabs

function NavigationTabs(elm, arr_options) {
	
	var SELF = this;
	
	var arr_options = $.extend({
		call_open: function() {},
		elm_open: '.open',
		sorting: false,
		big: false
	}, arr_options || {});
	
	var cur = $(elm);
	
	if (cur[0].navigationtabs) {
		return;
	}
	cur[0].navigationtabs = this;
					
	var elm_nav = cur.children('ul:first-child');
	
	var sorting = (arr_options.sorting || cur.attr('data-sorting') ? true : false);		
	var big = (arr_options.big || cur.attr('data-big') ? true : false);
		
	// Clean white spaces between tabs
	var elms_node = elm_nav[0].childNodes;
	for (var i = 0; i < elms_node.length; i++) {
		if (elms_node[i].nodeType == 3) {
			elm_nav[0].removeChild(elms_node[i]);
		}
	}
	
	var func_check = false;
	
	var func_init = function() {
	
		elm_nav.children('[data-parent_id]').each(function() {
			SELF.add({
				tab: $(this),
				content: cur.children($(this).children('a')[0].hash),
				open: false
			});
		});
		
		if (!big) {
			
			var is_big = false;
			
			// Check overflow
			func_check = function() {
				
				var elm_children = elm_nav.children('li');
				
				if (!elm_children.length) {
					return;
				}
				
				var pos_last = elm_children.last()[0].getBoundingClientRect();
				
				var make_big = false;
				
				if (elm_children.length > 1) {
				
					var pos_first = elm_children.first()[0].getBoundingClientRect();
					
					if (Math.floor(pos_last.bottom) > Math.ceil(pos_first.bottom)) { // Tabs align to bottom, so check bottom.
						make_big = true;
					} else {
						make_big = false;
					}
				}
				
				if (make_big && !is_big) {
					elm_nav.addClass('big');
				} else if (!make_big && is_big) {
					elm_nav.removeClass('big');
				}
				
				is_big = make_big;
			};
			
			new ResizeSensor(elm_nav[0], func_check);
			
			func_check();
		} else {
			
			var is_big = true;
			
			elm_nav.addClass('big');
		}
	
		if (sorting) {
			
			elm_nav.addClass('sorting');
				
			new SortSorter(elm_nav, {
				container: 'ul',
				handle: '> li > a',
				items: '> li:not([data-sortable="0"])',
				nested: true,
				func_obj: function() {
					return {
						index: false,
						elm_content: false
					};
				},
				call_start: function(elm, obj) {
					
					elm.addClass('sorting');
					obj.elm_content = [];
					
					var hash = elm.children('a')[0].hash;
					if (hash) { // Try tab id
			
						obj.elm_content = cur.children(hash);
					}
					if (!obj.elm_content.length) { // Use tab index
					
						obj.index = elm.index() - (elm.prevAll('.no-tab, :hidden').length);
						obj.elm_content = cur.children('div').eq(obj.index);
					}
				},
				call_stop: function(elm, obj) {
					
					elm.removeClass('sorting');
				},
				call_update: function(elm, obj) {
														
					var elm_sibling = elm.next('li');
					var sibling = 'next';
					if (!elm_sibling.length) {
						elm_sibling = elm.prev('li');
						sibling = 'prev';
					}
					
					var elm_content_sibling = [];
					
					var hash = elm_sibling.children('a')[0].hash;
					if (hash) {
			
						elm_content_sibling = cur.children(hash);
					}
					if (!elm_content_sibling.length) {
					
						var index_sibling = elm_sibling.index() - (elm_sibling.prevAll('.no-tab').length);
						var elm_content_sibling = cur.children('div');
						if (sibling == 'next') {
							elm_content_sibling = elm_content_sibling.eq(index_sibling-(obj.index >= index_sibling ? 1 : 0));
						} else {
							elm_content_sibling = elm_content_sibling.eq(index_sibling+1);
						}
					}
					
					if (sibling == 'next') {
						obj.elm_content.insertBefore(elm_content_sibling);
					} else {
						obj.elm_content.insertAfter(elm_content_sibling);
					}
				}
			});
		}
						
		elm_nav.on('click.tabs', 'li > a', function(e) {
		
			var cur_a = $(this);
			var elm_tab = cur_a.parent('li');
			var arr_options_tab = elm_tab[0].tabs_options_tab;
		
			if (elm_tab.hasClass('active')) {
				return false;
			}
			
			elm_nav.find('li').removeClass('selected active');
			elm_tab.addClass('selected active');
			elm_tab.parentsUntil('.tabs > ul').last().addClass('selected');
				
			var elm_content = $();
			if (this.hash) { // Try tab id
				
				elm_content = cur.children(this.hash);
				
				// Show and hide content related to tabs outside of navTabs 
				var elm_con = getContainer(cur);
				var elm_tab_targets = elm_con.find('[data-tab]').addClass('hide');
				elm_tab_targets.filter('[data-tab='+this.hash.substr(1)+']').removeClass('hide');
			}
			if (!elm_content.length) { // Use tab index
				
				var index = elm_tab.index() - (elm_tab.prevAll('.no-tab').length);
				elm_content = cur.children('div').eq(index);
			}
			if (elm_content.length) {
				
				cur.children('div').addClass('hide');
				elm_content.removeClass('hide');
				
				if (!isHidden(elm_content) || !cur.parent().closest('.tabs').length) {
					
					SELF.openContent(elm_content);
					
					runElementSelectorFunction(elm_content, '.tabs', function(elm_found) {

						if (!elm_found.navigationtabs) {
							return;
						}
						
						var elm_target = false;
						var elms_node = elm_found.children;
						
						for (var i = 0; i < elms_node.length; i++) {
							
							elm_target = elms_node[i];
							
							if (elm_target.matches('div') && !isHidden(elm_target)) {
								break;
							}
							
							elm_target = false;
						}
						
						if (!elm_target) {
							return;
						}
						
						elm_found.navigationtabs.openContent(elm_target);
					});
				}
			}
			
			return false;
		});
	
		if (!elm_nav.find('.active').length) {
							
			var elm_tab_open = elm_nav.find(arr_options.elm_open);
			
			if (!elm_tab_open.length) {
				elm_tab_open = elm_nav.children('li').children('a');
			}
			
			SCRIPTER.triggerEvent(elm_tab_open.first(), 'click');
		}
	};

	this.check = function() {
				
		if (!func_check) {
			return;
		};
		
		func_check();
	};
	
	this.openContent = function(elm_content) {
		
		var elm_content = $(elm_content);
		
		SELF.check();
		
		var arr_options_tab = elm_nav.find('.active')[0].tabs_options_tab;
		
		SCRIPTER.triggerEvent(elm_content, 'open');
		arr_options.call_open.apply(elm_content);
		if (arr_options_tab) {
			arr_options_tab.call_open.apply(elm_content);
		}
	};
	
	this.focusContent = function(elm_content) {
		
		var elm_content = $(elm_content);
		var elm_tab = $();
		
		if (elm_content[0].id) { // Try tab id
			
			elm_tab = elm_nav.find('a[href=#'+elm_content[0].id+']').parent('li');
		}
		
		if (!elm_tab.length) { // Use tab index
			
			var index = elm_content.index() - 1; // Subtract 1 for the elm_nav
			elm_tab = elm_nav.children('li:not(.no-tab)').eq(index);
		}
		
		if (elm_tab.length) {
			
			SCRIPTER.triggerEvent(elm_tab.children('a'), 'click');
		}
	};
	
	this.add = function(arr_options_tab) {
		
		var arr_options_tab = $.extend({
			tab: '',
			content: '',
			open: true,
			parent_id: false,
			call_open: function() {}
		}, arr_options_tab || {});

		var elm_content = $(arr_options_tab.content).appendTo(cur);
		var elm_tab = $(arr_options_tab.tab);
		
		var parent_id_tab = elm_tab.attr('data-parent_id');
		
		if (!arr_options_tab.parent_id && parent_id_tab) {
			arr_options_tab.parent_id = parent_id_tab;
		}
		
		arr_options_tab.parent_id = (arr_options_tab.parent_id ? arr_options_tab.parent_id.replace('#', '') : false);
		
		elm_tab[0].tabs_options_tab = arr_options_tab;
			
		var elm_target = elm_nav;
		if (arr_options_tab.parent_id) {
			elm_target = elm_nav.find('a[href="#'+arr_options_tab.parent_id+'"]').parent();
			elm_target = (elm_target.children('ul').length ? elm_target.children('ul') : $('<ul></ul>').appendTo(elm_target));
		}
		
		// Update the navigation with possible other elements in mide, like the ResizeSensor
		var elm_tab_last = elm_target.children('li').last();
		if (elm_tab_last.length) {
			elm_tab_last.after(elm_tab);
		} else {
			elm_target.prepend(elm_tab);
		}
		
		SELF.check();
		
		if (arr_options_tab.open) {
			SCRIPTER.triggerEvent(elm_tab.children('a'), 'click');
		}
	};
	
	this.del = function(arr_options_del) {
		
		var arr_options_del = $.extend({
			id: false
		}, arr_options_del || {});
		
		arr_options_del.id = (arr_options_del.id ? arr_options_del.id.replace('#', '') : false);
						
		var elm_target = elm_nav.find('a[href="#'+arr_options_del.id+'"]').parent('li');
		var elm_tab_open = elm_target.prev('li').add(elm_target.next('li')).add(elm_target.parent('ul').closest('li')).not('.no-tab').last();
		
		var arr_tab_children = elm_target.find('a').each(function() {
			cur.children(this.hash).remove();
		});
		
		elm_target.remove();
		
		SELF.check();
						
		SCRIPTER.triggerEvent(elm_tab_open.children('a'), 'click');
	};
	
	func_init();
}

$.fn.navTabs = function() {
	
	var obj = new ElementObjectByParameters(NavigationTabs, 'navigationtabs', arguments);
	
	return this.each(obj.run);
};

// Overlay

function Overlay(elm, content, arr_options) {
	
	var SELF = this;
	
	var arr_options = $.extend({
		sizing: 'fit-width', // fit, fit-width, full, full-width
		size_retain: true,
		position: 'top', // top, middle
		overwrite: false,
		call_close: function() {},
		call_overwrite: function() {},
		call_focus: function() {},
		button_close: true,
		elm_prevnext: {},
		location: false
	}, arr_options || {});
	
	var cur = $(elm);
	
	if (cur[0].overlay) {
		
		cur[0].overlay.addOverlay(content, arr_options);
		
		return cur[0].overlay;
	}
	
	cur[0].overlay = this;
	
	var arr_overlays = [];
	var elm_overlay_active = false;
	var arr_options_active = false;
				
	var elm_overlays = cur.children('.overlay');
					
	var arr_style = window.getComputedStyle(cur[0]);
		
	var arr_options_self = {org_height: parseInt(arr_style['height']), org_width: parseInt(arr_style['width'])};

	var resize_sensor = false;
	
	var func_init = function() {
		
		var elm_toolbox = getContainerToolbox(cur);
		
		resize_sensor = new ResizeSensor(cur[0], SELF.check, elm_toolbox[0]);
		
		SELF.addOverlay(content, arr_options);
	};
	
	this.addOverlay = function(content, arr_options) {
		
		cur.addClass('overlaying');
		
		var do_add = (!arr_overlays.length || !arr_options.overwrite);
		
		if (do_add) {
			
			if (elm_overlay_active) {
				elm_overlay_active.removeClass('active');
			}

			var elm_overlay = $('<div class="overlay"><div></div><div class="dialog" tabindex="0"><div class="content"></div><nav>'+(arr_options.elm_prevnext.length ? '<button type="button" class="prev" value=""><span class="icon"></span></button><button type="button" class="next" value=""><span class="icon"></span></button>' : '')+(arr_options.button_close ? '<button type="button" class="close" value=""><span class="icon"></span></button>' : '')+'</nav></div></div>');
			
			var arr_icons = [];
			if (arr_options.elm_prevnext.length) {
				arr_icons.push('nextprev-prev', 'nextprev-next');
			}
			if (arr_options.button_close) {
				arr_icons.push('close');
			}
			ASSETS.getIcons(cur, arr_icons, function(data) {
				
				if (arr_options.elm_prevnext.length) {
					elm_overlay[0].querySelector('button.prev > .icon').innerHTML = data.prev;
					elm_overlay[0].querySelector('button.next > .icon').innerHTML = data.next;
				}
				if (arr_options.button_close) {
					elm_overlay[0].querySelector('button.close > .icon').innerHTML = data.close;
				}
			});
			
			if (cur.is('body')) {
				
				elm_overlay.appendTo(cur);
			} else {
				
				if (elm_overlay_active) {
					elm_overlay_active.after(elm_overlay);
				} else { // Use the toolbox for consistent targeting (i.e. css)
					var elm_toolbox = getContainerToolbox(cur);
					elm_toolbox.before(elm_overlay);
				}
			}
			
			elm_overlay.children('div:first-child').on('click', function () {
				SELF.close();
			});
			elm_overlay.on('click', '> .dialog > nav > .close', function () {
				SELF.close();
			}).on('keyup', function(e) {
				if (e.which == 27 && !e.originalEvent.closed) {
					SELF.close();
					e.originalEvent.closed = true;
				}
			}).on('click', '> .dialog > nav > .prev, .dialog > nav > .next', function(e) {
				
				var elm = $(this);
				
				if (elm.hasClass('prev')) {
					var elm_target = arr_options.elm_prevnext.closest('tr, li').prev();
					var next_prev = 'prev';
				} else if (elm.hasClass('next')) {
					var elm_target = arr_options.elm_prevnext.closest('tr, li').next();
					var next_prev = 'next';
				} else {
					return;
				}
				
				var call_view = function() {
					SELF.close();
					COMMANDS.popupCommand(elm_target);
				};
				
				if (elm_target.length) {
					
					call_view();
				} else {
					
					var elm_table = arr_options.elm_prevnext.closest('[id^="d:"], .datatable');
					
					if (elm_table.length) {
						
						SCRIPTER.triggerEvent(elm_table, next_prev);
						
						if (elm_table.is('[id^="d:"]')) {
							
							elm_table.one('commandfinished', function() {
								elm_target = elm_table.find('[data-method]');
								elm_target = (next_prev == 'prev' ? elm_target.last() : elm_target.first());
								call_view();
							});
						} else {
							
							elm_target = elm_table.find('[data-method]');
							elm_target = (next_prev == 'prev' ? elm_target.last() : elm_target.first());
							call_view();
						}
					}
				}
			});

			if (arr_options.elm_prevnext.length) {
				
				elm_overlay.on('keyup', function(e) {
					
					if ($(e.target).closest('input[type=text], textarea, .body-content').length) {
						return;
					}
					if (e.which == 37) {
						SCRIPTER.triggerEvent(elm_overlay.find('> .dialog > nav > .prev'), 'click');
					} else if (e.which == 39) {
						SCRIPTER.triggerEvent(elm_overlay.find('> .dialog > nav > .next'), 'click');
					} else {
						return;
					}
				});
			}
			
			arr_overlays.push({options: arr_options, elm: elm_overlay});
			elm_overlay_active = elm_overlay;
		} else {
			
			// Trigger and update the current overlay before overwriting
			
			arr_options_active.call_overwrite.apply(elm_overlay_active);
			SCRIPTER.triggerEvent(elm_overlay_active, 'overwrite');

			var arr_overlay = arr_overlays[arr_overlays.length-1];
			arr_overlay.options = arr_options;
		}
		
		arr_options_active = arr_options;
		elm_overlay_active.addClass('active').addClass(arr_options_active.sizing);
		
		// Current overlay resize (width)
		
		var elm_dialog = elm_overlay_active.children('.dialog');
		
		if (do_add) {
			
			var arr_style = window.getComputedStyle(elm_overlay_active[0]);
				
			new ResizeSensor(elm_dialog[0], function() {
				
				SELF.check();
				
				// Manage overlay width
				var width_elm_dialog = elm_dialog[0].offsetWidth;
				
				if (width_elm_dialog == elm_dialog[0].width_dialog) {
					return;
				}
				
				elm_dialog[0].width_dialog = width_elm_dialog;
				
				if (!cur.is('body')) {
					
					var width_needed = width_elm_dialog + parseInt(arr_style['padding-left']) + parseInt(arr_style['padding-right']);
					if (width_needed > arr_options_self.org_width) {
						elm_dialog.css('margin-left', -((width_needed - arr_options_self.org_width) / 2));
					} else {
						elm_dialog.css('margin-left', '');
					}
				}
				
				if (arr_options_active.size_retain) {
					
					var arr_style_dialog = window.getComputedStyle(elm_dialog[0]);
					
					if (width_elm_dialog > parseInt(arr_style_dialog['min-width'])) {
						elm_dialog.css('min-width', width_elm_dialog);
					}
				}
				
				// Resize tables
				elm_dialog.find('table.display').each(function() {
					
					var elm_table = $(this);
					
					if (!elm_table.find('> thead > tr > th.max').length) {
						return;
					}

					resizeDataTable(elm_table);
				});
			});
		}
		
		// Finish
		
		if (arr_options_active.location) {
			LOCATION.attach(elm_dialog[0], arr_options_active.location, true);
		}
		
		var elm_content = (typeof content === 'object' ? content : $(content));

		elm_dialog
			.children('.content')
			.empty()
			.append(elm_content);
			
		SELF.position();

		SCRIPTER.triggerEvent(elm_dialog, 'focus');
		arr_options_active.call_focus.apply(elm_overlay_active);

		SCRIPTER.runDynamic(elm_content);
	};
	
	this.getOverlay = function() {
														
		return elm_overlay_active[0];
	};
	
	this.check = function() { // Main overlayer resize (height)
			
		// Force a possible container height
		
		var arr_height = [];
		var arr_style = window.getComputedStyle(elm_overlay_active[0]);
		
		for (var i = 0, len = arr_overlays.length; i < len; i++) {
			
			var elm_overlay = arr_overlays[i].elm;
			var cur_dialog = elm_overlay.children('.dialog');
			var arr_style_dialog = window.getComputedStyle(cur_dialog[0]);
			
			var height = cur_dialog[0].offsetHeight+parseInt(arr_style_dialog['margin-top'])+parseInt(arr_style['padding-top'])+parseInt(arr_style['padding-bottom']);
		
			arr_height.push(height);
		}
		
		var height = Math.max.apply(Math, arr_height);
		
		if (height > arr_options_self.org_height) {
			cur.css('min-height', height);
		} else {
			cur.css('min-height', '');
		}
	};
	
	this.position = function() {
				
		var elm_dialog = elm_overlay_active.children('.dialog');
		
		var pos = POSITION.getElementToDocument(elm_overlay_active[0]);
		var arr_style = window.getComputedStyle(elm_overlay_active[0]);
		var y_dialog = POSITION.scrollTop();
		
		if (arr_options_active.position == 'middle') {
			y_dialog = y_dialog + (window.innerHeight/3) - elm_dialog[0].offsetHeight - parseInt(arr_style['padding-top']);
		}
		
		y_dialog = y_dialog - pos.y;
		y_dialog = (y_dialog > 0 ? y_dialog : 0);

		elm_dialog.css('margin-top', y_dialog);
	};
	
	this.close = function(all) {
				
		if (all) {
			var count = arr_overlays.length;
		} else {
			var count = 1;
		}
		
		var func_close = function() {
				
			arr_options_active.call_close.apply(elm_overlay_active);
			SCRIPTER.triggerEvent(elm_overlay_active, 'close');
			elm_overlay_active.remove();
			
			count--;
			
			arr_overlays.pop();
			
			if (arr_overlays.length) {
				
				var arr_overlay = arr_overlays[arr_overlays.length-1];
				elm_overlay_active = arr_overlay.elm;
				arr_options_active = arr_overlay.options;
			}
			
			if (count) {

				LOCATION.checkLocked(elm_overlay_active[0], func_close);
			} else {
				
				func_closed();
			}
		};

		var func_closed = function() {
											
			if (all || !arr_overlays.length) { // All overlays are closed
				
				SELF.destroy();

				if (!cur.is('body')) {
					
					var elm_focus = cur.closest('form, [tabindex]');
				} else {
					
					var elm_focus = cur;
				}
				
				SCRIPTER.triggerEvent(elm_focus, 'focus');
			}
			
			if (!all && (cur.is('body') || arr_overlays.length)) { // Check leftover overlays in the tree
				
				var elm_overlay = cur.find('.overlay').last(); // Could be any overlay in the document, even inside mods.
				
				if (!elm_overlay.length) {
					return;
				}
				
				var obj_overlay = elm_overlay.parent()[0].overlay;
				
				if (obj_overlay) {
					obj_overlay.focus();
				}
			}
		};
		
		LOCATION.checkLocked(elm_overlay_active[0], func_close);
	};
	
	this.focus = function() {
				
		elm_overlay_active.addClass('active');
		
		var elm_dialog = elm_overlay_active.children('.dialog');
		var has_focus = elm_dialog.find('iframe').filter(function() {
			return $(this).find(':focus').length;
		}).add(elm_dialog.find(':focus')).length;

		if (!has_focus) {
			SCRIPTER.triggerEvent(elm_dialog, 'focus');
		}
		
		SELF.check();
		arr_options_active.call_focus.apply(elm_overlay_active);
	};
	
	this.location = function(location) {
				
		var elm_dialog = elm_overlay_active.children('.dialog');
		
		LOCATION.attach(elm_dialog[0], location, true);
	};
	
	this.destroy = function() {
		
		cur.css('min-height', '');
		
		cur.removeClass('overlaying');
		
		resize_sensor.detach();
		cur[0].overlay = null;
	};
	
	func_init();
}

$.fn.overlay = function() {
	
	var obj = new ElementObjectByParameters(Overlay, 'overlay', arguments);
	
	return this.each(obj.run);
};

function IframeDynamic(elm) {
	
	var SELF = this;
	var elm_iframe = $(elm);
	
	var count = 0;
	var interval = setInterval(function() {
		
		if (elm_iframe.contents()[0].readyState != 'complete') {
			
			if (count == 10) {
				clearInterval(interval);
			}
			count++;
			
			return;
		}
		clearInterval(interval);
		
		var content = elm_iframe.contents();
		var elm_body = content.find('body');
		elm_body.css({margin: 0, border: 0, padding: 0, width: '100%', overflow: 'hidden'});
		
		if (elm_iframe.attr('data-head')) {
			content.find('head').html(elm_iframe.attr('data-head'));
		}
		
		if (elm_iframe.attr('data-body')) {
								
			var elm_content = $('<div/>').appendTo(elm_body);
			
			var func_resize = function() {
				elm_iframe[0].style.height = elm_content[0].scrollHeight+'px';
			};
			
			var elm_toolbox = $('<div class="toolbox"></div>').appendTo(elm_content);

			new ResizeSensor(elm_content[0], func_resize, elm_toolbox[0]);
								
			elm_content.append(elm_iframe.attr('data-body'));
			
			if (elm_iframe.attr('data-body-class')) {
				elm_body.addClass(elm_iframe.attr('data-body-class'));
			}
		}

		elm_iframe.removeAttr('data-head').removeAttr('data-body').removeAttr('data-body-class');
	}, 10);
}

function Album(elm, options) {
	
	var SELF = this;
	
	var arr_options = $.extend({
		lazyload: false
	}, options || {});
	
	var elm = $(elm);
	
	if (elm[0].album) {
		return;
	}
	elm[0].album = this;
	
	if (arr_options.lazyload) {
		
		new LazyLoad(elm);
	}
	
	var selector = 'img';
	
	if (elm[0].querySelector('figure')) {
		selector = 'figure';
	}

	elm.on('click', selector, function() {
		
		var cur = $(this);
		
		var elm_view = $('<div class=\"album-viewer\"><img src=\"\" /><nav></nav></div>');
		var elm_nav = elm_view.children('nav');
		
		var index = elm.find(selector).index(cur)-1;
		
		var elm_nav_nextprev = $('<span class=\"icon prev\" data-category=\"full\"></span><span class=\"icon next\" data-category=\"full\"></span>').on('click', function() {

			index = (index+(this.classList.contains('next') ? 1 : -1));
			
			var elms_album = elm[0].querySelectorAll(selector);
			
			if (index >= elms_album.length) {
				index = 0;
			} else if (index < 0) {
				index = elms_album.length-1;
			}
			var elm_pick = elms_album[index];
			
			LOADER.start(elm_view);
			
			var elm_img = (selector == 'figure' ? elm_pick.querySelector('img') : elm_pick);

			if (elm_img) {
				
				var str_url = LOCATION.getOriginalUrl(elm_img.getAttribute('src'));
				
				elm_view.children('img').off('load').on('load', function() {
				
					LOADER.stop(elm_view);
				}).attr('src', str_url);
			}
			
			if (selector == 'figure') {
				
				elm_view.children('div').remove();
					
				var elm_caption = elm_pick.querySelector('figurecaption');
				
				if (elm_caption) {
					
					$('<div>'+elm_caption.innerHTML+'</div>').appendTo(elm_view);
				}
			}
		}).appendTo(elm_nav);
		
		ASSETS.getIcons(cur, ['prev', 'next'], function(data) {
		
			elm_nav_nextprev[0].innerHTML = data.prev;
			elm_nav_nextprev[1].innerHTML = data.next;
		});
				
		SCRIPTER.triggerEvent(elm_nav.children('.next'), 'click');
		
		var obj_overlay = new Overlay(document.body, elm_view, {sizing: 'fit-width', size_retain: false});
		
		var elm_overlay = obj_overlay.getOverlay();
		elm_overlay = $(elm_overlay);
		
		elm_overlay.on('keyup', function(e) {
			
			if (e.which == 37) {
				SCRIPTER.triggerEvent(elm_nav.children('.prev'), 'click');
			} else if (e.which == 39) {
				SCRIPTER.triggerEvent(elm_nav.children('.next'), 'click');
			}
		});
	});
}
