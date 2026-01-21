
/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

var PARSE = false;
var MESSAGEBOX = false;

document.addEventListener("DOMContentLoaded", function(e) {
	
	MESSAGEBOX = new MessageBox(document.body);
	
	if (PARSE) {
		
		const obj = PARSE();
		
		FEEDBACK.check(document.body, obj);
		
		if (obj.validate) {
			
			for (const mod_id in obj.validate) {
				
				const elm_mod = document.querySelector('#'+mod_id+' form');
				
				if (elm_mod) {
					setElementData(elm_mod, 'rules', obj.validate[mod_id]);
				}
			}
		}
		
		PARSE = false;
	}
	
	SCRIPTER.runStatic();
	
	SCRIPTER.triggerEvent(document, 'documentloaded', {elm: $(document.body)});
	window.addEventListener('load', function() {
		SCRIPTER.triggerEvent(window, 'windowloaded');
	});
	
	SCRIPTER.triggerEvent(window, 'resize');
	
	document.body.setAttribute('tabindex', '0');
	
	// Autoplay video
	window.addEventListener('touchstart', function startVideo() {
		
		const elm_video = document.querySelectorAll('video[autoplay]');
		
		for (let i = 0; i < elm_video.length; i++) {
			elm_video[i].play();
		}
		
		this.removeEventListener('touchstart', startVideo, {passive: true});
	}, {passive: true});
	
	// Resize internal elements
	
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
			
			const elms_table = elm.querySelectorAll('table');
					
			for (let i = 0; i < elms_table.length; i++) {
			
				resizeDataTable(elms_table[i]);
			}
		}, 300);
	});
});

const EOL_1100CC = "\n";
const EOL_EXCHANGE = "\r\n";

function getElement(elm) {
	
	if (elm === null || elm === false) {
		return null;
	}
	
	if (elm instanceof Element) {
		return elm;
	} else if (elm instanceof Object) {
		return elm[0];
	} else if (elm instanceof ShadowRoot) {
		return elm;
	} else if (typeof elm.ownerDocument !== 'undefined' && elm instanceof elm.ownerDocument.defaultView.Element) { // Owned by an other document/iframe
		return elm;
	}
	
	return null;
}

function runElementSelectorFunction(elm, selector, callback, selector_negative) {
	
	var elm = getElement(elm);
	
	if (elm.matches(selector) && (!selector_negative || !elm.matches(selector_negative))) {
		callback(elm);
	}
	
	const elms = elm.querySelectorAll(selector);

	for (let i = 0; i < elms.length; i++) {
		
		if (selector_negative && elms[i].matches(selector_negative)) {
			continue;
		}
		
		callback(elms[i]);
	}
}

function runElementsSelectorFunction(elms, selector, callback, selector_negative) {
	
	for (let i = 0; i < elms.length; i++) {
		runElementSelectorFunction(elms[i], selector, callback, selector_negative);
	}
}

function getElementSelector(elm, selector, do_hosts) {
		
	var elm = getElement(elm);
	
	if (elm.nodeType != Node.ELEMENT_NODE) {
		return false;
	}
	
	let elms_new = null;
	
	if (elm.matches(selector)) {
		
		elms_new = [elm];
	} else {
	
		const elms_find = elm.querySelectorAll(selector);
		
		if (elms_find.length) {
			elms_new = elms_find;
		}
	}
	
	if (do_hosts && selector.includes('.host')) {

		elms_new = (elms_new ? Array.from(elms_new) : []); // Make sure we have an array
		const arr_selectors = selector.split(',');
		
		for (let i = 0; i < arr_selectors.length; i++) {
			
			const str_selector = arr_selectors[i];
			const arr_selector = str_selector.split('.host');
			
			if (!arr_selector[1]) {
				continue;
			}
			
			const elms_host = elm.querySelectorAll(arr_selector[0]+'.host');
			
			for (let j = 0; j < elms_host.length; j++) {
				
				const elm_root = elms_host[j].shadowRoot;
				
				if (!elm_root) {
					continue;
				}
				
				const elms_find = elm_root.querySelectorAll(arr_selector[1]);
				
				if (!elms_find.length) {
					continue;
				}
				
				elms_new.push(...elms_find);
			}
		}
		
		if (!elms_new.length) {
			elms_new = null;
		}
	}
	
	return elms_new;
}

function getElementsSelector(elms, selector, do_hosts) {
	
	let elms_new = null;
	
	for (let i = 0; i < elms.length; i++) {
		
		const elms_found = getElementSelector(elms[i], selector, do_hosts);
		
		if (!elms_found) {
			continue;
		}
		
		if (elms_new === null) {
			elms_new = [];
		}
		
		elms_new.push(...elms_found);
	}
	
	return elms_new;
}

function getElementClosestSelector(elm, selector, do_hosts) {
		
	var elm = getElement(elm);
	
	if (elm.nodeType != Node.ELEMENT_NODE) {
		return false;
	}
	
	let elm_new = null;
	
	if (elm.matches(selector)) {
		
		elm_new = elm;
	} else {
	
		const elm_closest = elm.closest(selector);
		
		if (elm_closest) {
			elm_new = elm_closest;
		}
	}
	
	if (do_hosts && !elm_new && selector.includes('.host')) {
		
		const arr_selectors = selector.split(',');
		
		for (let i = 0; i < arr_selectors.length; i++) {
			
			const str_selector = arr_selectors[i];
			const arr_selector = str_selector.split('.host');
			
			if (!arr_selector[1]) {
				continue;
			}
			
			const elm_host = elm.closest(arr_selector[0]+'.host');
			
			if (!elm_host || !elm_host.shadowRoot) {
				continue;
			}

			const elm_closest = elm_host.closest(arr_selector[1]);
				
			if (elm_closest) {
				elm_new = elm_closest;
				break;
			}
		}
	}
	
	return elm_new;
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

function getElementRoot(elm) {
	
	var elm = getElement(elm);
	
	while (elm) {
		
		if (elm === document) {
			return document;
		} else if (elm instanceof ShadowRoot) {
			return elm;
		}
		
		elm = elm.parentNode;
	}
	
	return null;
}

function onStage(elm) {
	
	var elm = getElement(elm);
	
	while (elm && elm.parentNode) {
		
		if (elm.parentNode === document) {
			return true;
		} else if (elm.parentNode instanceof ShadowRoot) {
			elm = elm.parentNode.host;
		} else {
			elm = elm.parentNode;
		}
	}
	
	return false;
}

function isHidden(elm) {
	
	var elm = getElement(elm);
	
	return elm.offsetParent === null;
}

function hasElement(con, elm, self) {
	
	var elm = getElement(elm);
	var con = getElement(con);
		
	if (self && con === elm) {
		return true;
	}
	
	if (elm) {
		
		elm = elm.parentNode;
		
		while (elm) {
		
			if (elm === con) {
				return true;
			} else if (elm instanceof ShadowRoot) {
				elm = elm.host;
			} else {
				elm = elm.parentNode;
			}
		}
	}
	
	return false;
}

function getContainer(elm) {
			
	var elm = $(elm).closest((IS_CMS ? '[id^="mod-"]' : '.mod')+', .overlay');
	
	let elm_target = elm;
	
	if (elm.hasClass('overlay')) {
		elm_target = elm.children('.dialog').children('.content');
	}
	
	return elm_target;
}
	
function getContainerToolbox(elm, create) {
	
	var elm = getContainer(elm);
	
	let elm_toolbox = elm.children('.toolbox');
	
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
	
	const method_or_options = arr_arguments[0];
	
	let arr_arguments_new = null;
	if (method_or_options) {
		arr_arguments_new = Array.prototype.slice.call(arr_arguments, 1);
	}
	
	this.run = function() {
		
		const obj_self = this[object_target];
		let elm_return = null;
		
		if (obj_self) {
						
			if (method_or_options && typeof method_or_options === 'string' && obj_self[method_or_options]) {
		
				const func_method = obj_self[method_or_options];
				
				elm_return = func_method(...arr_arguments_new);
			}
			
			return (elm_return != undefined ? elm_return : this);
		}
		
		//new object_class(this, ...arr_arguments);
		
		const arr_arguments_all = Array.from(arr_arguments);
		arr_arguments_all.unshift(this);
		
		const instance = Object.create(object_class.prototype);
		instance.constructor = object_class;
		
		elm_return = object_class.apply(instance, arr_arguments_all);
		
		return (elm_return != undefined ? elm_return : this);
	};
}

function getElementContentSize(elm) {
	
	var elm = getElement(elm);

	const arr_style = window.getComputedStyle(elm);
	
	const num_width = elm.clientWidth - (parseInt(arr_style.paddingLeft) + parseInt(arr_style.paddingRight));
	const num_height = elm.clientHeight - (parseInt(arr_style.paddingTop) + parseInt(arr_style.paddingBottom));

	return {width: num_width, height: num_height};
}

String.prototype.hashCode = function() {
	
	let hash = 0;
	let i, len, chr;
	
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
	
	if (elms instanceof Element) {
		var elms = [elms];
	}
	
	const elms_name = [];
	
	for (let count_i = 0, len_i = elms.length; count_i < len_i; count_i++) {
		
		const elm = elms[count_i];
		
		if (elm.matches('[type="button"]')) {
			
			elms_name.push({name: elm.name, value: elm.value, disabled: elm.disabled, type: ''});
		} else {

			if (elm.matches('[name]')) {
				elms_name.push(elm);
			}
			
			const elms_found = elm.querySelectorAll('[name]');
			
			for (let count_j = 0, len_j = elms_found.length; count_j < len_j; count_j++) {
				elms_name.push(elms_found[count_j]);
			}
		}
	}
	
	if (!elms_name.length) {
		return null;
	}
		
	const arr_output = {};
	const arr_count_key = {};

	for (let count_j = 0, len_j = elms_name.length; count_j < len_j; count_j++) {
		
		const elm_name = elms_name[count_j];
		
		if (!elm_name.name || elm_name.disabled || ((elm_name.type == 'checkbox' || elm_name.type == 'radio') && !elm_name.checked) || elm_name.type == 'button' || elm_name.type == 'submit') { // Only keep relevant input elements
			continue;
		}

		// Split up the names into array tiers
		const arr_parts_all = elm_name.name.split(/\]|\[/);
		
		// We need to remove any blank parts returned by the regex.
		const arr_parts = [];
		
		for (let count_k = 0, len_k = arr_parts_all.length; count_k < len_k; count_k++) {
			
			const key = arr_parts_all[count_k];
			
			if (key != '' || (key == '' && (arr_parts_all[count_k-1] == '' || count_k-1 == 0) && arr_parts_all[count_k+1] == '')) { // Preserve key[]... and ...[key][][key]
				arr_parts.push(key);
			}			
		}

		// Start reference out at the root of the output object
		let arr_reference = arr_output;

		for (let count_k = 0, len_k = arr_parts.length; count_k < len_k; count_k++) {
			
			let key = arr_parts[count_k]; // Set key for ease of use.
			let value = null;
			const is_value = (count_k == (len_k-1)); // If we're at the last part, the value comes from the element.

			if (is_value) {
				
				if (elm_name.nodeName == 'SELECT') {
					
					const num_index = elm_name.selectedIndex;
					const is_array = (elm_name.type == 'select-multiple');
					value = (is_array ? [] : '');
					
					if (num_index >= 0) {
						
						for (let count_l = num_index, len_l = elm_name.options.length; count_l < len_l; count_l++) {

							const elm_option = elm_name.options[count_l];
							
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
					
					value = elm_name.value;
				}
			} else {
				
				value = {};
			}

			if (!key) { // key[]... and ...[key][][key]
				
				const identifier = arr_parts.slice(0, count_k).join('|'); // Also account for [yada][ya][da][][yada][hmm]
				
				if (arr_count_key[identifier] >= 0) {
					arr_count_key[identifier]++;
				} else {
					arr_count_key[identifier] = 0;
				}
				
				key = arr_count_key[identifier];
			}

			// Extend output with our temp object at the depth specified by reference.
			if (!arr_reference[key] || is_value) {
				arr_reference[key] = value;
			}

			// Reassign reference to point to this tier, so the next loop can extend it.
			arr_reference = arr_reference[key];
		}
	}

	return arr_output;
}

var num_iterate_name_groups = 0;

function replaceGroupIteratorInName(elms, num_or_arr_replace) {
	
	if (elms instanceof Element) {
		var elms = [elms];
	}
	
	const elms_name = [];
	const is_array = (num_or_arr_replace instanceof Array);

	for (let count_i = 0, len_i = elms.length; count_i < len_i; count_i++) {
		
		const elm = elms[count_i];

		if (elm.matches('[name]')) {
			elms_name.push(elm);
		}
		
		const elms_found = elm.querySelectorAll('[name]');
		
		for (let count_j = 0, len_j = elms_found.length; count_j < len_j; count_j++) {
			elms_name.push(elms_found[count_j]);
		}
	}
	
	if (!elms_name.length) {
		return;
	}
	
	const regex_iterate = /\[(iterate_(?:\d+_)?([^\]]*))\]/g;
	const arr_codes = [];
	
	for (let count_j = 0, len_j = elms_name.length; count_j < len_j; count_j++) {
	
		const elm_name = elms_name[count_j];

		let str_name = elm_name.getAttribute('name');
		
		let num_found = str_name.match(regex_iterate);
		num_found = (num_found ? num_found.length : 0);
		
		if (!num_found) {
			continue;
		}
		
		if (num_found && /\[\]$/.test(str_name)) { // Account for a final []
			num_found++;
		}

		let count = 0;
		
		str_name = str_name.replace(regex_iterate, function(match, m1, m2) {
			
			if (num_or_arr_replace) {
					
				if (is_array) {
					
					if (!num_or_arr_replace.includes(m1)) {
						
						count++;
						return match;
					}
				} else if (num_or_arr_replace < (num_found - count)) {
					
					count++;
					return match;
				}
			}

			if (!arr_codes[m2]) {
				
				num_iterate_name_groups++;
				const str_code = num_iterate_name_groups.toString()+'_'+m2;
				arr_codes[m2] = str_code;
			}
			
			count++;
			return '[iterate_'+arr_codes[m2]+']';
		});

		elm_name.setAttribute('name', str_name);
	}
}

function arrValueByKeyPath(str_path, arr, cur_path) {
	
	var cur_path = (cur_path == undefined ? '' : cur_path);

	for (var key in arr) {
	
		var str_test = (cur_path == '' ? key : cur_path+'['+key+']');
		
		if (str_test == str_path) {
			return arr[key];
		} else if (str_path.indexOf(str_test) === 0) {
			return arrValueByKeyPath(str_path, arr[key], str_test);
		}
	}
	
	return false;
}

function str2TypedValue(str) {

	// Parse a string to its interpreted typed value

	let str_out = str;
	
	if (str_out.charAt(0) == '"' && str.charAt(str_out.length - 1) == '"') { // If it's quoted, no need to parse because JSON parsing will remove the quotes, and it's a regular string anyhow
		return str_out;
	}

	try {
	
		const str_parsed = JSON.parse(str_out); // Succeeds if it's any of boolean/null/numeric/array/object
		
		str_out = str_parsed;
	} catch (e) { }
	
	return str_out; // Regular string
}

// GENERAL DOM

const func_content_loaded = function(e) {

	if (!getElement(e.detail.elm)) {
		return;
	}
	
	for (let i = 0, len = e.detail.elm.length; i < len; i++) {
			
		const elm = $(e.detail.elm[i]);
		
		runElementSelectorFunction(elm, '[data-hold="1"]', function(elm_found) {
			elm_found.removeAttribute('data-hold');
			SCRIPTER.triggerEvent(elm_found, 'contenthold');
		});
		
		FORMMANAGING.createManagers(elm);
		
		runElementSelectorFunction(elm, 'select', function(elm_found) {
			new DropDown(elm_found, {
				state_empty: elm_found.dataset.state_empty
			});
		});
		
		runElementSelectorFunction(elm, 'ul.sorter', function(elm_found) {
			
			const elm_menu = $(elm_found).closest('li').prev('li').find('menu.sorter');
			
			new Sorter(elm_found, {
				prepend: elm_found.classList.contains('reverse'),
				auto_add: elm_found.dataset.auto_add,
				auto_clean: elm_found.dataset.auto_clean,
				limit: elm_found.dataset.limit,
				elm_menu: (elm_menu.length ? elm_menu[0] : false)
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
			
			let elm_value = $(elm_found).nextAll('input:first');
			elm_value = elm_value[0];
			
			if (elm_found.name || !elm_value) {
				return;
			}
			
			elm_found.value = elm_value.value;
			
			if (!elm_found.step) {
				elm_found.min = elm_value.min;
				elm_found.max = elm_value.max;
				elm_found.step = elm_value.step;
			} else if (!elm_value.step) {
				elm_value.min = elm_found.min;
				elm_value.max = elm_found.max;
				elm_value.step = elm_found.step;
			}
			
			elm_found.addEventListener('input', function() {
				elm_value.value = this.value;
			}, true);
			elm_value.addEventListener('input', function() {
				elm_found.value = this.value;
			}, true);
		});
		
		runElementSelectorFunction(elm, 'input.autocomplete', function(elm_found) {
			new AutoCompleter(elm_found, {
				multi: elm_found.classList.contains('multi'),
				order: elm_found.dataset.order,
				delay: elm_found.dataset.delay
			});
		});
		runElementSelectorFunction(elm, '.filebrowse', function(elm_found) {
			new FileBrowse(elm_found);
		});
		runElementSelectorFunction(elm, '.regex', function(elm_found) {
			new RegularExpressionEditor(elm_found);
		});
		
		runElementSelectorFunction(elm, 'iframe[src="about:blank"]', function(elm_found) {
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
		
		FORMMANAGING.lockManagers(elm);
	}
};
document.addEventListener('documentloaded', func_content_loaded);
document.addEventListener('ajaxloaded', func_content_loaded);

function unloadClonedElements(elms) {
	
	// Unload elements (cloned) to prepare for new func_content_loaded run
	
	if (elms instanceof Element) {
		var elms = [elms];
	}
	
	for (let i = 0, len = elms.length; i < len; i++) {
		
		const elm = elms[i];
		
		runElementSelectorFunction(elm, 'input, select, textarea', function(elm_found) {
			
			elm_found.disabled = false;
			
			if (!elm_found.matches('[type=checkbox], [type=radio], select, [type=button], [type=submit]')) {
				elm_found.value = '';
			}
			
			if (elm_found.matches('[type=checkbox], [type=radio]')) {
				elm_found.checked = (elm_found.getAttribute('checked') == 'checked');
			}
			
			if (elm_found.matches('select')) {
				
				const elm_child = elm_found.querySelector(':scope > option[value=""][hidden=""]:last-child');
				if (elm_child) {
					elm_child.remove(); // Remove dynamic DropDown placeholder
				}
				
				elm_found.selectedIndex = 0;
			}
		});
		
		runElementSelectorFunction(elm, 'input.autocomplete', function(elm_found) {
			
			if (elm_found.placeholder) {
				elm_found.placeholder = '';
			}
			
			let elm_tags = elm_found.previousSibling;
			elm_tags = (elm_tags && elm_tags.matches('.tags') ? elm_tags.querySelector(':scope > ul') : null);
			
			if (elm_tags) {
			
				while (elm_tags.firstChild) {
					elm_tags.removeChild(elm_tags.firstChild);
				}
			}
		});
	}
}

$(document).on('click', '.hide-edit.hide + *', function() {
	
	const cur = $(this);
	const elm_target = cur.prev();
	
	elm_target.children().insertAfter(elm_target);
	elm_target.remove();
	cur.remove();
}).on('click', 'input[type=checkbox].multi.all', function() {
	
	const cur = $(this);
	const elms_target = cur.closest('table').find('td:nth-child('+(cur.parent().index()+1)+')');
	const elms_multi = elms_target.children('input[type=checkbox].multi');
	elms_multi.prop('checked', cur.is(':checked'));
	SCRIPTER.triggerEvent(elms_multi, 'change');
}).on('click', 'input[type=checkbox].multi', function() {
	
	const cur = $(this);
	const elm_command = cur.closest('table').find('th').eq(cur.parent().index());
	const elm_checked = cur.closest('table').find('input[type=checkbox].multi:checked').not('.all');
	
	elm_command[0].elm_checked = elm_checked;
	let arr_ids = $.map(elm_checked, function(val, i) {
		return val.value;
	});
	arr_ids = (arr_ids.length ? arr_ids : false);
	COMMANDS.setID(elm_command, arr_ids);
}).on('command', 'table th .msg:has(~ input[type=checkbox].multi.all)', function() {
	
	const elm_command = this.closest('th');
	COMMANDS.setAbort(this, (COMMANDS.getID(elm_command) ? false : true));
}).on('newpage', 'table.display', function(e) {
	
	const cur = $(this);
	const elm_target = cur.find('input[type=checkbox].multi.all');
	const elm_command = elm_target.closest('[id^=x\\\:]');
	
	if (elm_command.length) {
		if (elm_target.prop('checked')) {
			elm_target.prop('checked', false);
			SCRIPTER.triggerEvent(elm_target, 'change');
		}
		COMMANDS.setID(elm_command, false);
		const elm_checked = elm_command[0].elm_checked;
		if (!cur.is('[id^=d\\\:]') && elm_checked) {
			elm_checked.prop('checked', false);
			SCRIPTER.triggerEvent(elm_checked, 'change');
			elm_command[0].elm_checked = false;
		}
	}
}).on('click', 'input[data-href], button[data-href]', function(e) {
	
	const cur = $(this);
	const url = cur.attr('data-href');
	
	LOCATION.open(url);
}).on('command', '[id^=y\\\:cms_general\\\:preview-]', function() {
	
	const elm_target = $(this).parent('menu').parent().find('textarea.editor');
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
		
		for (const selector in obj_scripts_static) {
			
			const elms_find = document.querySelectorAll(selector);
			
			for (let i = 0, len = elms_find.length; i < len; i++) {
			
				const elm = elms_find[i];
				
				attachScripts(elm, selector, obj_scripts_static);
			}
		}
	};
	
	this.runDynamic = function(elm) {
		
		var elm = getElement(elm);

		if (!elm || elm.scripter) {
			return;
		}
		
		const elm_attach = $(elm);
		
		elm.scripter = true;

		for (const selector in obj_scripts_dynamic) {
			
			if (!elm.matches(selector)) {
				continue;
			}
			
			attachScripts(elm, selector, obj_scripts_dynamic, elm_attach);
		}
		
		obj.triggerEvent(elm_attach, 'scripter', {elm: elm_attach}, {bubbles: false});
	};
		
	var attachScripts = function(elm, selector, obj_scripts, elm_attach) {
		
		const arr_selectors = obj_scripts[selector];
		
		if (!arr_selectors) {
			return;
		}
		
		for (let i = 0, len = arr_selectors.length; i < len; i++) {
			
			const obj_call = arr_selectors[i];
			
			if (!elm_attach) {
				var elm_attach = $(elm);
			}
			
			if (typeof obj_call.call == 'string') {
				
				const use_selector = obj_call.call;
				let use_obj_scripts = null;
				
				if (isIdentifier(use_selector)) {
					use_obj_scripts = (obj_scripts === obj_scripts_static_identifier ? obj_scripts_static_identifier : obj_scripts_dynamic_identifier);
				} else {
					use_obj_scripts = obj_scripts;
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
					event_class = CustomEvent;
				} else {
					event_class = Event;
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

		const event = new event_class(type, obj_event);
		
		elm.dispatchEvent(event);
		
		return event;
	};
	
	this.isUserEvent = function(obj_event) {
		
		let is_trusted = (obj_event.originalEvent != undefined ? obj_event.originalEvent.isTrusted : obj_event.isTrusted); // isTrusted would mean the event was generated by a user action
		
		return is_trusted;
	};
}
var SCRIPTER = new Scripter();

function Position() {
	
	var obj = this;
	
	this.mouse = {x: 0, y: 0};
	this.is_touch = false;
	this.event = false;
	
	var has_touch = (('ontouchstart' in window) || (navigator.msMaxTouchPoints > 0));
	var in_touch = false;
	
	this.getElementToDocument = function(elm) {

		var pos_elm = elm.getBoundingClientRect();
		pos_elm = {x: pos_elm.left + obj.scrollLeft(), y: pos_elm.top + obj.scrollTop()};
		
		return pos_elm;
	};
	this.getElementFromMouse = function() {
		
		var elm = document.elementFromPoint(obj.mouse.x - obj.scrollLeft(), obj.mouse.y - obj.scrollTop());
		
		return elm;
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
	this.hasTouch = function() {
		
		return has_touch;
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
		document.removeEventListener('touchstart', func_init, {capture: true, passive: true});
	};

	document.addEventListener('mouseover', func_init, true);
	document.addEventListener('touchstart', func_init, {capture: true, passive: true});
	
	document.addEventListener('mousedown', func_move, true);
	document.addEventListener('mousemove', func_move, true);
	document.addEventListener('touchstart', func_move, {capture: true, passive: true});
	document.addEventListener('touchmove', func_move, {capture: true, passive: true});
	
	document.addEventListener('mouseup', func_end, true); // mouseup is also the last event triggered by a touch event
	
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
	
	const SELF = this;
	var selector = '*[title]:not(iframe)';
	var elm_tooltip = $('<div class="tooltip mouse"></div>')[0];
	
	var pos_tooltip = false;
	var pos_source = false;
	var do_update_size = false;
	
	var elm = false;
	var title = false;
	var is_static = false;
	var func_move = false;
	
	const num_offset_source_x = 12;
	const num_offset_source_y = 22;
	
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
			elm.removeEventListener('touchmove', func_move, {capture: true, passive: true});
		}
		elm.removeEventListener('touchend', func_end, true);
		elm.removeEventListener('mouseout', func_end, true);
		
		elm = false;
			
		document.body.removeChild(elm_tooltip);
	};
	
	this.check = function() { // Check the current position/validity of the element
		
		var elm_hover = POSITION.getElementFromMouse();
		elm_hover = (elm_hover ? getElementClosestSelector(elm_hover, selector) : false);
		
		if (!elm_hover && !elm) {
			return;
		}
		
		if (elm_hover && elm === elm_hover) {
			
			if (is_static) { // Reposition, possibly moved
				
				pos_source = POSITION.getElementToDocument(elm);

				elm_tooltip.style.top = (pos_source.y - 4 - elm_tooltip.offsetHeight)+'px';
				elm_tooltip.style.left = pos_source.x+'px';
			}
			
			return;
		}
		
		SELF.remove();
	};
	
	this.update = function() { // Check for a newly added title, or for changes to the title
		
		if (!elm) {
			
			elm = POSITION.getElementFromMouse();
			elm = (elm ? getElementClosestSelector(elm, selector) : false);
						
			if (elm) {
				func_create();
			}
			
			return;
		}
		
		if (!elm.hasAttribute('title')) {

			SELF.remove();
		} else {
			
			var cur_title = elm.getAttribute('title');
		
			if (cur_title) {
				
				title = cur_title;
				
				elm_tooltip.innerHTML = title;
				elm.setAttribute('title', '');
				
				if (!is_static) {
					
					do_update_size = true;
					func_move();
				}
			}
		}
	};
	
	this.getTitle = function(elm_check) {
		
		if (elm_check) {
			
			var elm_check = getElement(elm_check);
			
			if (elm_check !== elm) {
				return elm_check.getAttribute('title');
			}
		}
		
		if (!elm) {
			return false;
		}
		
		return title;
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
			
			var title_check = func(elm_target);
			
			if (title_check === false) {
				return;
			}
									
			if (!title_check){
				elm_target.removeAttribute('title');
			} else {
				elm_target.setAttribute('title', title_check);
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
	
	var func_create = function() {
				
		if (!elm) {
			return;
		}

		title = elm.getAttribute('title');
		is_static = elm.getAttribute('data-title_static');
		
		elm.setAttribute('title', '');
		
		elm_tooltip.innerHTML = title;
		document.body.appendChild(elm_tooltip);

		if (!is_static) {
			pos_source = POSITION.mouse;
		} else {
			pos_source = POSITION.getElementToDocument(elm);
		}
		
		elm_tooltip.style.left = '0px';
		elm_tooltip.style.maxWidth = 'none';
		pos_tooltip = elm_tooltip.getBoundingClientRect();
		
		let num_max_width = 0;
		
		if (is_static) {
						
			elm_tooltip.style.top = (pos_source.y - 4 - pos_tooltip.height)+'px';
			elm_tooltip.style.left = pos_source.x+'px';
		} else {

			const num_height = pos_source.y + num_offset_source_y + pos_tooltip.height;
			const num_height_max = POSITION.scrollTop() + document.documentElement.clientHeight;
			const num_offset_y = (num_height > num_height_max ? -(num_height-num_height_max) : 0);
			
			elm_tooltip.style.top = (pos_source.y + num_offset_source_y + num_offset_y)+'px';

			let num_offset_x = num_offset_source_x;
			
			if (pos_source.x < (document.documentElement.clientWidth / 2)) {
				
				num_max_width = document.documentElement.clientWidth - (pos_source.x + num_offset_source_x + num_offset_source_x);
			} else {
				
				num_max_width = (pos_source.x - num_offset_source_x - num_offset_source_x);
				
				if ((pos_source.x + num_offset_source_x + pos_tooltip.width + num_offset_source_x) > (POSITION.scrollLeft() + document.documentElement.clientWidth)) {
					num_offset_x = (-Math.min(pos_tooltip.width, num_max_width) - num_offset_source_x);
				}
			}

			elm_tooltip.style.left = (pos_source.x + num_offset_x)+'px';
			elm_tooltip.style.maxWidth = num_max_width+'px';
		}

		if (!is_static) {
			
			do_update_size = false;
			
			func_move = function(e) {
				
				pos_source = POSITION.mouse;

				if (do_update_size) {
					
					elm_tooltip.style.left = '0px';
					elm_tooltip.style.maxWidth = 'none';
					pos_tooltip = elm_tooltip.getBoundingClientRect();
				}
				do_update_size = false;

				const num_height = pos_source.y + num_offset_source_y + pos_tooltip.height;
				const num_height_max = POSITION.scrollTop() + document.documentElement.clientHeight;
				const num_offset_y = (num_height > num_height_max ? -(num_height-num_height_max) : 0);
				
				elm_tooltip.style.top = (pos_source.y + num_offset_source_y + num_offset_y)+'px';
				
				let num_offset_x = num_offset_source_x;
				
				if (pos_source.x < (document.documentElement.clientWidth / 2)) {
					
					num_max_width = document.documentElement.clientWidth - (pos_source.x + num_offset_source_x + num_offset_source_x);
				} else {
					
					num_max_width = (pos_source.x - num_offset_source_x - num_offset_source_x);
					
					if ((pos_source.x + num_offset_source_x + pos_tooltip.width + num_offset_source_x) > (POSITION.scrollLeft() + document.documentElement.clientWidth)) {
						num_offset_x = (-Math.min(pos_tooltip.width, num_max_width) - num_offset_source_x);
					}
				}
				
				elm_tooltip.style.left = (pos_source.x + num_offset_x)+'px';
				elm_tooltip.style.maxWidth = num_max_width+'px';
			};
				
			elm.addEventListener('mousemove', func_move, true);
			elm.addEventListener('touchmove', func_move, {capture: true, passive: true});
		}
		
		elm.addEventListener('touchend', func_end, true);
		elm.addEventListener('mouseout', func_end, true);
	}

	var func_over = function(e) {
		
		if (POSITION.isTouch() && e.type == 'mouseover') { // Prevent touch and triggered mouse events mixup
			return;
		}
				
		var elm_hover = getElementClosestSelector(e.target, selector);
		
		if ((!elm_hover && !elm) || (elm_hover && elm === elm_hover)) {
			return;
		}
		
		SELF.remove();
		
		if (elm_hover) {
				
			elm = elm_hover;
			
			func_create();
		}
	};
	
	var func_end = function(e) {
		
		if (e.type == 'mouseout' && e.relatedTarget) { // Check when it's a mouse event whether it has left the document (empty e.relatedTarget)
			return;
		}
		
		SELF.remove();
	}
	
	document.addEventListener('mouseover', func_over, true);
	document.addEventListener('touchstart', func_over, {capture: true, passive: true});
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
	
	var SELF = this;
		
	var str_host = window.location.origin;
	
	var str_location = window.location.pathname+window.location.search; // Only relative parth of the location and no client-side hash
	var str_location_canonical = null; // Holds the real 1100CC location path, when the location path itself could be a shortcut '.s'
	
	var str_location_active = str_location;
	var str_location_canonical_active = false;
	
	var has_changed = false;
	var str_location_target = null;
	
	this.getURL = function(mode_purpose) {
	
		var mode_purpose = (mode_purpose ? mode_purpose : false);
		let str_url = '';
		
		if (IS_CMS) {
			
			if (mode_purpose == 'command') {
				str_url = '/commands';
			} else {
				str_url = str_location_active;
			}
		} else {
			
			if (mode_purpose == 'client') {
				
				str_url = str_location_active;
			} else {
				
				const arr_url = new URL((str_location_canonical_active ? str_location_canonical_active : str_location_active), SELF.getHost());
				
				let arr_path = arr_url.pathname.split('/');
				let arr = [];
				let str_page_name = false;
				
				for (var i = 0; i < arr_path.length; i++) {
					
					var str_page = arr_path[i].slice(-2);
					
					if (str_page == '.p' || str_page == '.l') { // Request originates from page
						str_page_name = arr_path[i].slice(0, -2);
						arr.push(str_page_name+(mode_purpose == 'command' ? '.c' : '.p'));
					} else if (i == arr_path.length-1 && arr_path[i] && !str_page_name) { // Request originates from page (at url end)
						str_page_name = arr_path[i];
						arr.push(str_page_name+(mode_purpose == 'command' ? '.c' : '.p'));
					} else {
						arr.push(arr_path[i]);
					}
				}
				
				str_url = arr.join('/');
				
				if (mode_purpose == 'command' && !str_page_name) { // Request originates from directory
					str_url = str_url+'commands.c';
				}
				
				str_url = str_url+arr_url.search;
			}
		}

		return str_url;
	};
	
	this.getHost = function() {
		
		return str_host;
	};
	
	this.getOriginalURL = function(str_url) { // Parse a chached URL and return the original URL
	
		const arr_match = str_url.match(/(.*)\/cache\/(?:[^\/]*\/){2}(.*)/i); // Match host and src
		
		if (arr_match && arr_match[2]) {
			return (arr_match[1] ? arr_match[1] : '')+Base64.decode(arr_match[2]);
		} else {
			return str_url;
		}
	};
	
	this.reload = function(value, timeout) {
		
		SELF.unlock(document.body);
		
		const elms_disable = document.querySelectorAll('input, select, textarea');

		for (let i = 0, len = elms_disable.length; i < len; i++) {
			elms_disable[i].disabled = true;
		}
		
		has_changed = true;
		
		const func = function() {
			
			if (value) {
				str_location_target = value;
				window.location.replace(value);
			} else {
				str_location_target = window.location.href;
				window.location.reload(false);
			}
		}
		
		if (timeout) {
			setTimeout(func, timeout);
		} else {
			func();
		}
	};
	
	this.push = function(value, value_canonical, do_persist) {
		
		const is_new = (value != str_location_active);

		str_location_active = value;
		str_location_canonical_active = (value_canonical && value_canonical != value ? value_canonical : false);
		
		if (do_persist && (is_new || str_location_canonical === null)) {
			str_location = str_location_active;
			str_location_canonical = str_location_canonical_active;
		}
		
		if (!is_new) {
			return;
		}
		
		window.history.pushState('', '', str_location_active);
		func_trigger_location(str_location_active, str_location_canonical_active, false);
	};
	
	this.replace = function(value, value_canonical, do_persist) {
		
		const is_new = (value != str_location_active);

		str_location_active = value;
		str_location_canonical_active = (value_canonical && value_canonical != value ? value_canonical : false);
		
		if (do_persist && (is_new || str_location_canonical === null)) {
			str_location = str_location_active;
			str_location_canonical = str_location_canonical_active;
		}

		if (!is_new) {
			return;
		}
		
		const str_location_client = str_location_active + window.location.hash; // Include/retain a possible client-side hash
		
		window.history.replaceState('', '', str_location_client);
		func_trigger_location(str_location_active, str_location_canonical_active, true);
	};
	
	var func_trigger_location = function(str_client, str_canonical, do_replace) { // Pass any location as an event to be caught and sent by e.g. DocumentEmbedded 
		
		SCRIPTER.triggerEvent(window, 'location', {client: str_client, canonical: str_canonical, replace: do_replace});
	};
	
	window.addEventListener('unload', function() {
		
		let str_location_client = str_location_target;
		
		if (!str_location_target) {
			
			const elm_leave = SELF.getActiveElement();
			
			if (elm_leave && elm_leave.href) {
				str_location_client = elm_leave.href;
			} else {
				str_location_client = window.location.href;
			}
		}
		
		str_location_client = SELF.checkDestination(str_location_client);
		
		if (str_location_client === true) { // Going external
			str_location_client = '';
		}
		
		func_trigger_location(str_location_client, false, true);
	});

	this.open = function(value) {
		
		window.open(value, '_blank');
	};
	
	this.hasChanged = function() {
		
		return has_changed;
	};
	
	this.getActiveElement = function(elm_root) {
	
		const elm_target = (elm_root ? elm_root : document).activeElement;

		if (!elm_target) {
			return null;
		}
		
		if (elm_target.shadowRoot) {
			return SELF.getActiveElement(elm_target.shadowRoot);
		} else {
			return elm_target;
		}
	};
	
	this.checkDestination = function(str_url, elm_source) {
				
		if (!str_url || str_url.charAt(0) == '#' || (elm_source && elm_source.target == '_blank')) { // Not navigating away
			return false;
		}
		
		const arr_url = new URL(str_url, SELF.getHost());
		
		if (arr_url.host != window.location.host) { // Going external
			return true;
		}
		
		// Remain local
		 
		let str_location_client = arr_url.pathname+arr_url.search;
		
		if (str_location_client == '') {
			str_location_client = '/';
		}
		
		return str_location_client;
	};
	
	this.getHashParameter = function(str_parameter) {
		
		if (!window.location.hash) {
			return;
		}
		
		const obj_parameters = new URLSearchParams(window.location.hash.substring(1));
		
		if (str_parameter == null) { // Check anchor, could be first entry when valueless
			
			const arr_value = Array.from(obj_parameters.entries())[0];
			
			if (arr_value[1] === '') {
				return arr_value[0];
			}
			
			return;
		}
		
		return obj_parameters.get(str_parameter);
	};
	
	var func_check_hash = function() {
		
		const str_anchor = SELF.getHashParameter();
		
		if (!str_anchor) {
			return;
		}
		
		const str_selector = '#'+str_anchor+', .host #'+str_anchor;
		const elms_match = getElementSelector(document.body, str_selector, true);

		if (!elms_match) {
			return;
		}
		
		const elm_check = elms_match[0]; // Use the first found element
		const elm_root = getElementRoot(elm_check);
		
		if (elm_root !== document) { // The element is inside a ShadowRoot
			 elm_check.scrollIntoView();
		}
	};
	
	window.addEventListener('hashchange', func_check_hash);
	window.addEventListener('documentloaded', function handler(e) {
		
		func_check_hash();
		this.removeEventListener('documentloaded', handler);
	});
	
	var elm_active = false;
	
	this.attach = function(elm, arr_location_attach, do_focus) {
		
		if (arr_location_attach) {
			
			const str_location_attach = (typeof arr_location_attach == 'object' ? JSON.stringify(arr_location_attach) : arr_location_attach);
			
			elm.dataset.location = str_location_attach;
			elm.setAttribute('tabindex', '0');
		} else if (arr_location_attach === false) {
			
			delete elm.dataset.location;
			elm.removeAttribute('tabindex');
		}
		
		if (do_focus) {
			
			const elm_target = (arr_location_attach ? elm : elm.closest('form, [tabindex]'));
			
			if (elm_target) {
				
				if (SELF.getActiveElement() === elm_target) {
					elm_active = false;
					func_check();
				} else {
					SCRIPTER.triggerEvent(elm_target, 'focus');
				}
			}
		}
	};

	var func_check = function(e) {
		
		const elm_check = SELF.getActiveElement();

		if (!elm_check || elm_check === elm_active) {
			return;
		}
		
		let elm_match = elm_check;
		elm_match = (elm_match.matches('[data-location]') ? elm_match : null) || elm_match.closest('[data-location]');

		if (elm_match) {
			
			if (elm_match !== elm_active) {
				
				elm_active = elm_match;
				
				const arr_location = JSON.parse(elm_match.dataset.location);
				
				SELF.push(arr_location.real, arr_location.canonical);
			}
		} else if (elm_active) {
			
			SELF.push(str_location, str_location_canonical);
			elm_active = false;
		}
	};
	
	document.addEventListener('focus', func_check, true);
	
	// Locking
	
	var has_locked_active = false;
	var str_locked_page = '';
	var str_locked_content = '';
	
	var func_locked = function (e) {
		
		if (!SELF.checkLocked(document.body)) {
			return;
		}
		
		e.preventDefault();
		e.returnValue = str_locked_page; // Legacy
		
		return str_locked_page; // Legacy
	};
	
	var func_locked_leave = function (e) {
		
		func_leave(document.body, false);
	};
	
	var func_reload = function(e) {
		
		const elm_target = e.target;
		const is_mouse_primary = (e.which == 1); // Left mouse button
		
		if (!has_locked_active || elm_target.nodeName != 'A' || !is_mouse_primary) {
			return;
		}
		
		const str_url = elm_target.getAttribute('href');
		const is_navigating = SELF.checkDestination(str_url, elm_target);
		
		if (!is_navigating) {
			return;
		}

		const is_locked = SELF.checkLocked(document.body, function(is_locked) {
			
			if (is_locked == null) {
				return;
			}
			
			SELF.unlock(document.body);
			SELF.reload(str_url);
		});
		
		if (is_locked) {
			e.preventDefault();
		}
	};
	
	document.addEventListener('click', func_reload, true);
	
	this.lock = function(elm) {
		
		delete elm.dataset.lock;
		elm.dataset.locked = 1;
		elm.locked_identifier = func_get_lock_identifier(elm);
		
		if (!has_locked_active) {
			
			ASSETS.getLabels(elm,
				['conf_locked_page', 'conf_locked_content'],
				function(data) {
					
					str_locked_page = data.conf_locked_page;
					str_locked_content = data.conf_locked_content;
					window.addEventListener('beforeunload', func_locked);
					window.addEventListener('unload', func_locked_leave);
				}
			);
			
			has_locked_active = true;
		}
	};

	this.unlock = function(elm) {
		
		if (!has_locked_active) {
			return;
		}
		
		if (elm.matches('[data-locked]')) {
			
			delete elm.dataset.locked;
		}
		
		const elms_locked = elm.querySelectorAll('[data-locked]');
		
		for (var i = 0, len = elms_locked.length; i < len; i++) {

			delete elms_locked[i].dataset.locked;
		}
	};
	
	this.updateLocked = function(elm) {
		
		if (!has_locked_active) {
			return;
		}
		
		if (elm.matches('[data-locked]')) {
			
			elm.locked_identifier = func_get_lock_identifier(elm);
		}
		
		const elms_locked = elm.querySelectorAll('[data-locked]');
		
		for (let i = 0, len = elms_locked.length; i < len; i++) {

			const elm_locked = elms_locked[i];
			
			elm_locked.locked_identifier = func_get_lock_identifier(elm_locked);
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
		
		const elms_locked = elm.querySelectorAll('[data-locked]');
		
		for (let i = 0, len = elms_locked.length; i < len; i++) {
			
			func_check_leave(elms_locked[i], locked);
		}
		
		func_run_leave(elm, locked, callback);
	};
	
	var func_check_leave = function(elm, locked) {
		
		const arr_leave = elm.arr_leave;
		
		if (!arr_leave) {
			return;
		}

		for (let i = 0, len = arr_leave.length; i < len; i++) {
			
			const state = arr_leave[i].state;
			
			if (state == 'any' || (locked == null && state == 'unlocked') || (locked == false && state == 'locked')) {
				
				arr_leave_run.push(arr_leave[i].func);
			}
		}
	};
	
	var func_run_leave = function(elm, locked, callback) {
		
		let len = arr_leave_run.length;
		let count = 0;
		
		if (!len) {
			
			if (callback) {
				callback();
			}
			return;
		}
		
		let func_done = function() {};
		
		if (callback) {
			
			func_done = function() {
				
				count++;
				
				if (count == len) {
					callback();
				}
			};
		}
		
		for (let i = 0; i < len; i++) {
			
			arr_leave_run[i](func_done);
		}
	};
	
	this.checkLocked = function(elm, callback, do_force, do_wait) {
		
		var is_locked = null; // null (no lock), true (locked), false (lock removed after user confirmation)
		
		if (!has_locked_active) {
						
			if (callback) {
				callback(is_locked);
			}
			return is_locked;
		}
		
		const is_page = (elm === document.body);
		
		if (elm.matches('[data-locked]')) {
			
			const str_identifier = func_get_lock_identifier(elm);
			
			if (str_identifier != elm.locked_identifier) {
				is_locked = true;
			}
		}
		
		const elms_locked = elm.querySelectorAll('[data-locked]');
		
		for (let i = 0, len = elms_locked.length; i < len; i++) {
			
			const elm_locked = elms_locked[i];
			const str_identifier = func_get_lock_identifier(elm_locked);
			
			if (str_identifier != elm_locked.locked_identifier) {
				is_locked = true;
				break;
			}
		}
		
		if (callback) {
			
			if (is_locked == null) {
				
				if (do_wait) {
					func_leave(elm, is_locked, function() {
						callback(is_locked);
					});
				} else {
					func_leave(elm, is_locked);
					callback(is_locked);
				}
			} else {
				
				var popup = new MessagePopup(elm, (is_page ? str_locked_page : str_locked_content), function() {
					if (do_force) {
						callback(true);
					}
				});

				popup.addButton('Ok', function() {
					
					popup.close();
					
					if (do_wait) {
						func_leave(elm, false, function() {
							callback(false);
						});
					} else {
						func_leave(elm, false);
						callback(false);
					}					
				});
				popup.addButton('Cancel', function() {
					
					popup.close();
				});
			}
		}
		
		return is_locked;
	};
	
	var func_get_lock_identifier = function(elm) {
		
		const func_sort = function(obj_sort) { // Does not sort objects inside arrays
			
			if (typeof obj_sort != "object" || obj_sort instanceof Array) { // Not to sort the array
				return obj_sort;
			}
			const arr_keys = Object.keys(obj_sort);
			arr_keys.sort();
			
			const obj_sorted = {};
			
			for (let i = 0; i < arr_keys.length; i++) {
				obj_sorted[arr_keys[i]] = func_sort(obj_sort[arr_keys[i]]);
			}
			
			return obj_sorted;
		};
		
		const str_identifier = JSON.stringify(func_sort(serializeArrayByName(elm)));
		
		return str_identifier;
	};
}
var LOCATION = new Location();

function DocumentEmbedded() {
	
	// Communicate as embedded/embeddee 1100CC document, authorative class
	
	if (window.parent === window) {
		return;
	}
	
	const SELF = this;
	
	var is_embedded = false;
	var num_height_document = 0;

	window.addEventListener('message', function(e) { // Get size messages from parent document
				
		if (e.data.embedding) {
			
			if (!is_embedded || e.data.initialise) { // Initialise embedded state
				
				initialiseEmbedded();
				
				SELF.setEmbeddingSize(e.data.initialise);
			}
			
			if (e.data.embedding !== true) {
				
				if (e.data.embedding.height != undefined) {
					
					useEmbeddingSize(e.data.embedding.height);
				}
				
				if (e.data.embedding.location != undefined) {
					
					useEmbeddingLocation(e.data.embedding.location);
				}
			}
		}
		
		if (e.data.script === true) {

			const str_script = 'if (typeof DocumentEmbedding != \'function\') { var DocumentEmbedding = '+DocumentEmbedding.toString()+'; } new DocumentEmbedding();';
			
			window.parent.postMessage({script: str_script}, '*');
		}
	}, false);
	
	var requestEmbedding = function() {
		
		window.parent.postMessage({embedded: true, initialise: true}, '*');
	};
	
	var initialiseEmbedded = function() {
		
		if (is_embedded) {
			return;
		}
		
		is_embedded = true;
		document.body.classList.add('framed');
		
		new ResizeSensor(document.body, SELF.setEmbeddingSize);
		
		window.addEventListener('location', function(e) {
			SELF.setEmbeddingLocation(e.detail.client, e.detail.replace);
		});
	};
	
	this.setEmbeddingSize = function(do_initialise) { // Message size to external/parent document
		
		if (num_height_document == document.body.scrollHeight && !do_initialise) {
			return;
		}
		
		num_height_document = document.body.scrollHeight;
		
		window.parent.postMessage({embedded: {height: num_height_document}, initialise: do_initialise}, '*');
	};
	
	this.setEmbeddingLocation = function(str_location, do_replace) { // Message location to external/parent document
				
		window.parent.postMessage({embedded: {location: {client: str_location, replace: do_replace}}}, '*');
	};

	requestEmbedding();
	
	// Apply returned values
	
	var useEmbeddingSize = function(num_height) {
		
		const num_height_view = num_height;

		document.body.style.setProperty('--view-height', num_height_view+'px');
	};
	
	var useEmbeddingLocation = function(str_location) {

	};
}
var DOCUMENTEMBEDDED = new DocumentEmbedded();

function DocumentEmbedding() {
	
	// Communicate with embedded 1100CC documents
	// Only use vanilla JavaScript
	
	const SELF = this;
	
	let num_height_window = window.innerHeight;

	window.addEventListener('message', function(e) { // Get size messages from embedded document
		
		if (e.data.embedded) {
			
			const elms = document.body.querySelectorAll(DOCUMENTEMBEDDINGLISTENER.getSelector());

			for (let i = 0, len = elms.length; i < len; i++) {
				
				const elm_found = elms[i];
		
				if (e.source !== elm_found.contentWindow) { // Skip message in this event listener
					continue;
				}
				
				if (e.data.initialise) {
					initialiseEmbedded(elm_found);
				}
				
				if (e.data.embedded !== true) {
					
					if (e.data.embedded.height != undefined) {
						useEmbeddedSize(elm_found, e.data.embedded.height);
					}
					
					if (e.data.embedded.location != undefined) {
						useEmbeddedLocation(elm_found, e.data.embedded.location);
					}
				}
			}
		}
	}, false);
	
	var initialiseEmbedded = function(elm_frame) {
				
		if (!elm_frame.is_embedded) { // Do not redetermine default height if previously initialised
		
			let num_height = elm_frame.getAttribute('height');
			
			if (num_height && num_height != 'auto' && num_height != '100%') {
				
				const arr_style = window.getComputedStyle(elm_frame);
				num_height = parseInt(arr_style['height']);
			} else {
				
				num_height = false;
			}
			
			elm_frame.height_default = num_height;
		}
		
		elm_frame.is_embedded = true; // Embedded document is ready and send the parent's view size 
		
		SELF.setEmbeddedSize(elm_frame);
		SELF.setEmbeddedLocation(elm_frame);
	};
	
	this.setEmbeddedSize = function(elm_frame) {
		
		const num_height_view = (!elm_frame.height_default ? num_height_window : elm_frame.height_default);
				
		elm_frame.contentWindow.postMessage({embedding: {height: num_height_view}}, '*');
	};
	
	this.setEmbeddedLocation = function(elm_frame, str_location) {
		
		if (str_location == undefined) {
			var str_location = window.location.pathname+window.location.search; // Only relative parth of the location and no client-side hash
		}
						
		elm_frame.contentWindow.postMessage({embedding: {location: str_location}}, '*');
	};
	
	this.requestEmbedded = function(elm_frame) {
				
		elm_frame.contentWindow.postMessage({embedding: true, initialise: true}, '*');
	};
	
	this.requestEmbeddedAll = function() {
			
		const elms = document.body.querySelectorAll(DOCUMENTEMBEDDINGLISTENER.getSelector());

		for (let i = 0, len = elms.length; i < len; i++) {
			
			const elm_frame = elms[i];
			
			SELF.requestEmbedded(elm_frame);
		}
	};
	
	this.setEmbeddedAllSize = function() { // Message size to embedded documents
	
		if (num_height_window == window.innerHeight) {
			return;
		}
		
		num_height_window = window.innerHeight;
		
		const elms = document.body.querySelectorAll(DOCUMENTEMBEDDINGLISTENER.getSelector());

		for (let i = 0, len = elms.length; i < len; i++) {
			
			const elm_frame = elms[i];
			
			SELF.setEmbeddedSize(elm_frame);
		}
	};
	
	this.setEmbeddedAllLocation = function(str_location) { // Message location to embedded documents
				
		const elms = document.body.querySelectorAll(DOCUMENTEMBEDDINGLISTENER.getSelector());

		for (let i = 0, len = elms.length; i < len; i++) {
			
			const elm_frame = elms[i];
			
			SELF.setEmbeddedLocation(elm_frame, str_location);
		}
	};

	SELF.requestEmbeddedAll();
	
	window.addEventListener('resize', function(e) {
		SELF.setEmbeddedAllSize();
	});
	window.addEventListener('location', function(e) {
		SELF.setEmbeddedAllLocation(e.detail.client);
	});
	
	// Apply returned values
	
	var useEmbeddedSize = function(elm_frame, num_height) {
	
		const num_height_embedded = (!elm_frame.height_default || num_height > elm_frame.height_default ? num_height : elm_frame.height_default);
				
		elm_frame.style.height = num_height_embedded+'px';
	};
	
	var useEmbeddedLocation = function(elm_frame, arr_location) {
		
		if (!DOCUMENTEMBEDDINGLISTENER.checkLocation) {
			return;
		}
		
		const do_location = DOCUMENTEMBEDDINGLISTENER.checkLocation(arr_location, elm_frame);
		
		if (!do_location) {
			return;
		}
		
		const str_location = arr_location.client;
		
		if (arr_location.replace) {
			window.history.replaceState('', '', str_location);
		} else {
			window.history.pushState('', '', str_location);
		}
	};
}

//var DOCUMENTEMBEDDINGLISTENER = new DocumentEmbeddingListener(); // In separate file

function Assets() {
	
	const SELF = this;
	
	var obj_fetched = {font: {}, script: {}, media: {}};
	var obj_labels = {};
	var obj_icons = {};
	
	const arr_external_protocols = ['http', 'https', 'ftp'];
	
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
	
	this.fetch = function(elm, arr_options, callback) {
		
		let count_loaders = 0;
		const func_loaded = function() {
			
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
		if (arr_options.labels) {
			count_loaders++;
		}
		
		if (arr_options.script) {
			
			const call = function() {
			
				let num_count = arr_options.script.length;
				
				const func_asset = function(script, text) {
					
					num_count--;
					
					if (text != null) {
						obj_fetched.script[script] = text;
					}
					
					if (num_count) {
						return;
					}
					
					func_check();
				};
				
				const func_check = function() { // Check if scripts are loading from possible other call

					for (let i = 0, len = arr_options.script.length; i < len; i++) {
						
						const script = arr_options.script[i];
						const str_script = obj_fetched.script[script];
						
						if (str_script !== null && str_script !== '') {
							
							// Call in global scope
							(function() {
								eval.apply(this, arguments);
							}(str_script))

							obj_fetched.script[script] = '';
						} else if (str_script === null) { // Script is still loading in other call
							
							setTimeout(func_check, 50);
							return;
						}
					}
					
					func_loaded();
				};
				
				for (let i = 0, len = arr_options.script.length; i < len; i++) {

					const script = arr_options.script[i];
					
					if (obj_fetched.script[script] !== undefined) {

						func_asset(script);
						continue;
					}
					
					obj_fetched.script[script] = null; // Set as loading, not undefined anymore
					
					const call_script = function() {
						
						const cur_script = script;
						
						const xhr = new XMLHttpRequest();
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
			
			const call = function() {
				
				let num_count = arr_options.font.length;
				
				const func_asset = function() {
					
					num_count--;
					
					if (num_count) {
						return;
					}
					
					func_loaded();
				};
				
				for (let i = 0, len = arr_options.font.length; i < len; i++) {
					
					const font = arr_options.font[i];
					
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
			
			const call = function() {
				
				let num_count = arr_options.media.length;
				
				const func_process = function() {
			
					let timer_check = false;
					
					const func_check = function() {
						
						let do_wait = false;
						
						for (let i = 0, len = arr_options.media.length; i < len; i++) {

							const resource = arr_options.media[i];
							const arr_resource = obj_fetched.media[resource];
							
							if (!arr_resource.image) {
								
								// Something could be wrong, do not stall
							} else if (arr_resource.image.width || arr_resource.image.complete) {

								arr_resource.width = (arr_resource.image.width ? arr_resource.image.width : 100);
								arr_resource.height = (arr_resource.image.height ? arr_resource.image.height : 100);
							} else {
								
								do_wait = true;
							}
						}
						
						if (!do_wait) {
							
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

				const func_asset = function(resource, value) {
					
					num_count--;
					
					if (value != null) {

						const store = (window.URL || window.webkitURL).createObjectURL(value);
						
						const image = new Image();
						image.src = store;
						
						obj_fetched.media[resource] = {resource: store, image: image, width: null, height: null};
					}
					
					if (num_count) {
						return;
					}
					
					func_process();
				};
				
				for (let i = 0, len = arr_options.media.length; i < len; i++) {

					const resource = arr_options.media[i];
					
					if (obj_fetched.media[resource] !== undefined) {

						func_asset(resource);
						continue;
					}
					
					obj_fetched.media[resource] = null; // Set as loading, not undefined anymore
					
					const call_media = function() {
						
						const cur_resource = resource;
						
						const xhr = new XMLHttpRequest();
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
		
		if (arr_options.labels) {
			
			SELF.getLabels(elm, arr_options.labels, function(data) {
				func_loaded();
			});
		}
	};
	
	this.getMedia = function(resource) {
		
		var arr_resource = obj_fetched.media[resource];
		
		return arr_resource;
	};
	
	this.getFiles = function(elm, arr, callback, obj_store, str_type, str_url_prefix, str_url_affix) {

		let num_count = arr.length;
		
		var func_process = function() {
			
			var timer_check = false;
			
			var func_check = function() {
				
				var do_wait = false;
				
				for (var i = 0, len = arr.length; i < len; i++) {
					
					var file = arr[i];
					
					if (obj_store[file] === false) {
						do_wait = true;
					}						
				}
				
				if (!do_wait) {
					
					if (timer_check) {
						clearInterval(timer_check);
					}
					callback(obj_store);
					
					return;
				}

				if (!timer_check) {
					timer_check = setInterval(func_check, 10);
				}
			};
			func_check();
		};
		
		var func_asset = function(file, content) {
			
			num_count--;
			
			if (content != undefined) {
				
				obj_store[file] = content;
			}
			
			if (!num_count) {
				
				func_process();
			}
		};
				
		for (var i = 0, len = arr.length; i < len; i++) {

			var file = arr[i];
			
			if (obj_store[file] != undefined) {

				func_asset(file);
				continue;
			}
			
			obj_store[file] = false;
			
			var call_load = function() {
				
				const cur_file = file;
				let str_url = (str_url_prefix ? str_url_prefix : '')+cur_file+(str_url_affix ? str_url_affix : '');
				
				if (!SELF.getProtocolExternal(str_url)) {
					str_url = (str_host ? str_host : '')+str_url;
				}				
				
				const xhr = new XMLHttpRequest();
				xhr.onreadystatechange = function() {
					
					if (this.readyState == 4) {
						
						if (this.status == 200) {
							
							func_asset(cur_file, this.response);
							return;
						}
						func_asset(cur_file, '');
					}
				}
				xhr.open('GET', str_url, true);
				xhr.responseType = str_type;
				xhr.send(); 
			};
			call_load();
		}
	};
	
	this.getIcons = function(elm, arr, callback) {
		
		SELF.getFiles(elm, arr, callback, obj_icons, 'text', '/CMS/css/images/icons/', '.svg');
	};
	
	this.getIcon = function(icon) {
		
		const svg = obj_icons[icon];
		
		return svg;
	};
		
	this.getLabels = function(elm, arr, callback) {
		
		var elm = $(elm);
		
		const str_identifier_bulk = JSON.stringify(arr);
		
		if (obj_labels[str_identifier_bulk] !== undefined) {
			
			callback(obj_labels[str_identifier_bulk]);
			return;
		}
		
		let has_new = false;
		const arr_labels_collect = {};
		
		for (const str_identifier_label of arr) {
			
			if (obj_labels[str_identifier_label] === undefined) {
				
				has_new = true;
				break;
			}
			
			arr_labels_collect[str_identifier_label] = obj_labels[str_identifier_label];
		}
		
		if (!has_new) {
			
			callback(arr_labels_collect);
			return;
		}
		
		FEEDBACK.request(elm, elm, {
			type: 'POST',
			contentType: FEEDBACK.CONTENT_TYPE_JSON,
			dataType: 'json',
			url: LOCATION.getURL('command'),
			data: {mod: getModID(elm), module: 'cms_general', method: 'get_label', value: arr},
			includeFeedback: false,
			context: elm,
			async: true,
			success: function(json) {
				
				json.location = false; // Prevent async location changes
				
				FEEDBACK.check(elm, json, function() {

					SELF.setLabels(json.html, str_identifier_bulk);
					callback(json.html);
				});
			}
		});
	};
	
	this.setLabels = function(arr, str_identifier_bulk) {
		
		if (str_identifier_bulk) {
			obj_labels[str_identifier_bulk] = arr;
		}
		
		for (const str_identifier_label in arr) {
			obj_labels[str_identifier_label] = arr[str_identifier_label];
		}
	}
	
	this.createWorker = function(func, arr_scripts, func_before) {
		
		if (arr_scripts) {

			const str_host_use = (str_host ? str_host : LOCATION.getHost());
			
			for (let i = 0, len = arr_scripts.length; i < len; i++) {
				arr_scripts[i] = str_host_use+arr_scripts[i];
			}
		}
		
		const blob_url = URL.createObjectURL(new Blob([
				(func_before ? '('+((typeof func_before === 'string' || func_before instanceof String) ? func_before : func_before.toString())+')();' : ''),
				(arr_scripts ? 'importScripts(\''+arr_scripts.join('\',\'')+'\');' : ''),
				'('+((typeof func === 'string' || func instanceof String) ? func : func.toString())+')();'
			],
			{type: 'application/javascript'}
		));
		
		const worker = new Worker(blob_url);
		
		URL.revokeObjectURL(blob_url);
		
		return worker;
	};
	
	this.createDocumentHost = function(elm_host, str_class, arr_match_rules) {
		
		var elm_host = getElement(elm_host);
		let elm_root = false;

		const elm_host_context = getElementClosestSelector(elm_host, '[data-host]');
		
		if (elm_host.nodeName == 'TEMPLATE') {
			
			const elm_new = document.createElement('div');
			elm_host.parentNode.insertBefore(elm_new, elm_host);
			elm_root = elm_new.attachShadow({mode: 'open'});
			elm_root.appendChild(elm_host.content.cloneNode(true));
			
			elm_host.remove();
			elm_host = elm_new;
		} else {

			elm_root = elm_host.attachShadow({mode: 'open'});
		}
		
		elm_host.classList.add('host', str_class);
		
		if (!arr_match_rules) {
			var arr_match_rules = [];
		}
		arr_match_rules.push(':host(.host.'+str_class+')');
		
		if (elm_host_context && elm_host_context.dataset.host) {
			
			const str_class_context = elm_host_context.dataset.host;
			elm_host.classList.add(str_class_context);
			arr_match_rules.push(':host(.host.'+str_class+'.'+str_class_context+')');
		}
		
		const sheet = new CSSStyleSheet();
		
		for (arr_sheet of document.styleSheets) {
			
			let arr_rules = null;
			
			try {
				arr_rules = arr_sheet.cssRules; // Could throw DOMException when stylesheet was loaded through restrictive CORS
			} catch (e) {
				continue;
			}
			
			let in_block = false;
			
			for (let i = 0, len = arr_rules.length; i < len; i++) {
				
				const str_rule = arr_rules[i].cssText;
				
				if (str_rule.startsWith(':host(default)')) {
					
					in_block = (in_block ? false : true);
					continue;
				}
				if (in_block) {
					
					sheet.insertRule(str_rule);
					continue;
				}
					
				for (let j = 0, len_j = arr_match_rules.length; j < len_j; j++) {
					
					if (!str_rule.includes(arr_match_rules[j])) {
						continue;
					}
					
					sheet.insertRule(str_rule);
					break;
				}
			}
		}
		
		elm_root.adoptedStyleSheets = [sheet];
		
		return elm_root;
	};
		
	this.getProtocolExternal = function(str_url) {
		
		let str_protocol = false;
		
		if (str_url.substring(0, 2) == '//') {
			
			str_protocol = window.location.protocol;
		
			return str_protocol;
		}
		
		const arr_protocol_url = str_url.split('://');
		
		str_protocol = (typeof arr_protocol_url[1] != 'undefined' ? arr_protocol_url[0] : false);
		str_protocol = (str_protocol && arr_external_protocols.includes(str_protocol) ? str_protocol+':' : false);
		
		return str_protocol;
	};
}
var ASSETS = new Assets();

function Animator() {
	
	const SELF = this;
	
	var arr_animate = [];
	var key_tween = false;
	var is_animating = false;
	
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
			
			SELF.trigger();
		}
		
		return key;
	};
	
	this.trigger = function() {
		
		if (!is_animating) {
			
			is_animating = true;
			window.requestAnimatorFrame(doAnimate);
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
			window.requestAnimatorFrame(doAnimate);
		} else {
			is_animating = false;
		}
	};
	
	key_tween = SELF.animate(function(time) {

		var updated = TWEEN.update(time);
		
		return (updated ? true : false);
	});
}
var ANIMATOR = new Animator();
	
function MessageBox(elm) {
	
	const SELF = this;
	
	let num_count = 0;
	let is_hovering = false;
	let key_animate = false;
	let time_animate = false;
	let elm_con = $(elm).children('.result');
	
	if (!elm_con.length) {
		elm_con = $('<div class="result"></div>').appendTo(elm);
	}
	
	let elm_system = false;
	let str_system = false;
		
	this.add = function(options) {
		
		const arr_options = $.extend({
			message: '',
			type: 'attention',
			method: 'replace',
			identifier: false,
			counter: true,
			duration: 5000,
			persist: false,
			follow_click: false
		}, options || {});
		
		let elm_box = false;
				
		if (arr_options.follow_click) {
			elm_con.css({'position': 'absolute', 'z-index': '99999', 'top': POSITION.mouse.y, 'left': POSITION.mouse.x});
		}

		const elm_message = $(arr_options.message).clone().wrap('<div/>').parent(); // wrap to return own html
		
		if (arr_options.counter) {
			
			num_count++;
			elm_message.find('label:first').text("#"+num_count);
		}
		
		elm_box = $('<div class="'+arr_options.type+'"'+(arr_options.identifier ? ' data-identifier="'+arr_options.identifier+'"' : '')+'>'+elm_message.html()+'</div>');

		const obj_messagebox = {
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
	
	this.end = function(elm_box, do_quick) {
		
		var elm_box = getElement(elm_box);
		const obj_messagebox = elm_box.messagebox;
				
		obj_messagebox.active = false;
				
		if (do_quick) {
			
			if (obj_messagebox.tween) {
				obj_messagebox.tween.stop();
			}

			elm_box.remove();
		} else {
			
			if (obj_messagebox.tween) {
				return;
			}
			
			const arr_tween = {opacity: 1};
			
			obj_messagebox.tween = new TWEEN.Tween(arr_tween)
				.to({opacity: 0}, 500)
				.easing(TWEEN.Easing.Sinusoidal.InOut)
				.onUpdate(function() {
					
					elm_box.style.opacity = arr_tween.opacity;
				}).onComplete(function() {
					
					elm_box.remove();
				})
			.start();
		}
	};
	
	this.clear = function(arr_options) {
		
		// Identifiers are matched using a wildcard at the end to be able to easily match groups
		
		let elms_box = false;
		
		if (!arr_options.identifier) {
			
			elms_box = elm_con[0].children;
		} else {
				
			const arr_identifiers = [];

			if (typeof arr_options.identifier == 'object') {
				
				for (let i = 0, len = arr_options.identifier.length; i < len; i++) {
					arr_identifiers.push('[data-identifier^="'+arr_options.identifier[i]+'"]');
				}
			} else {
				
				arr_identifiers.push('[data-identifier^="'+arr_options.identifier+'"]');
			}

			if (arr_identifiers.length) {
				elms_box = elm_con[0].querySelectorAll(arr_identifiers.join(','));
			}
		}
		
		if (!elms_box) {
			return;
		}
		
		for (let i = 0, len = elms_box.length; i < len; i++) {
			
			const elm_box = elms_box[i];
			
			if (arr_options.timeout === 0) { // Clear
			
				SELF.end(elm_box, true);
			} else {
				
				const obj_messagebox = elm_box.messagebox;
				
				let time = arr_options.timeout;
				
				if (time == null) { // If no timeout is specified, use the originally defined timeout
					time = obj_messagebox.duration;
				}
				
				if (time) {
					obj_messagebox.time = time;
				} else {
					SELF.end(elm_box);
				}
			}
		}
		
		func_check_interact();
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
			
			const elms_box = elm_con[0].childNodes;
		
			if (!elms_box.length) {
				
				time_animate = false;
				ANIMATOR.animate(false, key_animate);
			
				return false;
			}
			
			if (!time_animate) {
				time_animate = time;
			}
			
			const time_difference = time - time_animate;
			
			for (let i = 0, len = elms_box.length; i < len; i++) {
				
				const elm_box = elms_box[i];
				const obj_messagebox = elm_box.messagebox;
				
				if (!obj_messagebox.active || obj_messagebox.time === false) {
					continue;
				}
								
				obj_messagebox.time -= time_difference;
				
				if (obj_messagebox.time > 0) {
					continue;
				}
				
				SELF.end(elm_box);
			}
				
			time_animate = time;
			
			return true;
		}, key_animate);
	};
	
	var func_check_pointer = function(elm_point) {
		
		if (!elm_point) {
			var elm_point = POSITION.getElementFromMouse();
		}
		
		if (elm_point) {
			
			if (!hasElement(elm_con, elm_point)) {
				elm_point = null;
			}
		}
		
		if (elm_point) { // Get and check the box
			
			const elm_box = getElementClosestSelector(elm_point, '.result > div');
			
			if (elm_box && !elm_box.messagebox.active) {
				elm_point = null;
			}
		}
		
		return elm_point;
	};
		
	var func_check_interact = function() {
		
		const elm_point = func_check_pointer();

		if (is_hovering && !elm_point) {
			func_end();
		} else if (!is_hovering && elm_point) {
			func_over();
		}
	};
	
	var func_over = function(e) {
		
		if (is_hovering) {
			return;
		}
		
		if (e != null) {
			
			if (e.type == 'mouseover' && POSITION.isTouch()) { // Prevent touch and triggered mouse events mixup
				return;
			}
			
			const elm_point = func_check_pointer(e.target);
			
			if (!elm_point) {
				return;
			}
		}
		
		is_hovering = true;

		elm_con[0].addEventListener('touchend', func_end, true);
		elm_con[0].addEventListener('mouseout', func_end, true);
	};
	
	var func_end = function(e) {
		
		if (e != null) {
			
			if (e.type == 'mouseout' && e.relatedTarget && hasElement(elm_con, e.relatedTarget)) { // Check when it's a mouse event whether it has not left the document (e.relatedTarget is not empty) and is still on the main element
				return;
			}
		}
	
		is_hovering = false;

		elm_con[0].removeEventListener('touchend', func_end, true);
		elm_con[0].removeEventListener('mouseout', func_end, true);
	};
		
	elm_con[0].addEventListener('mouseover', func_over, true);
	elm_con[0].addEventListener('touchstart', func_over, {capture: true, passive: true});
	
	this.checkSystem = function(str_html) {
	
		if (str_html) {
			
			if (!elm_system) {
				elm_system = $('<div class="system"></div>').prependTo(document.body);
			}
			
			if (str_html != str_system) {
				
				elm_system[0].innerHTML = str_html;
				str_system = str_html;
			}
		} else if (elm_system) {

			$(elm_system).remove();
			elm_system = false;
			str_system = false;
		}
	};
}

function MessagePopup(elm, str_message, call_close) {
	
	const SELF = this;
	
	var elm = getElement(elm);
	
	const elm_popup = $('<div class="popup message"><span class="icon"></span><div>'+(str_message ? str_message : '')+'</div></div>');
	
	const elm_icon = elm_popup[0].children[0];
	const elm_message = elm_popup[0].children[1];
			
	ASSETS.getIcons(elm, ['attention'], function(data) {
		elm_icon.innerHTML = data.attention;
	});
	
	let elm_overlay_target = $(elm).closest('.mod');
	if (!elm_overlay_target.length) {
		elm_overlay_target = $('body');
	}
	
	const elm_menu = $('<menu></menu>').appendTo(elm_popup);

	const obj_overlay = new Overlay(elm_overlay_target, elm_popup, {
		position: 'middle',
		call_close: function() {
			if (call_close) {
				call_close();
			}
		},
		button_close: false
	});
	
	this.setMessage = function(str) {
		
		elm_message.innerHTML = str;
	};
	
	this.addButton = function(str, call) {
		
		const elm_button = $('<input type="button" value="'+str+'" />').appendTo(elm_menu).on('click', call);
		
		return elm_button;
	};
	this.addButtonDefault = function() {
		
		SELF.addButton('Ok', function() {
			obj_overlay.close();
		});
	};
	
	this.close = function() {
		
		obj_overlay.close();
	};
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
		
		const timer_new = window.performance.now();
		
		time += timer_new - timer;
		timer = timer_new;
		
		return time;
	};
	
	this.log = function(name) {
		
		console.log((name ? name+' ' : '')+time);
	}
}

function tweakDataTable(elm) { // Make tables slick
			
	var elm = getElement(elm);
	
	const elm_heading = elm.querySelector('thead > tr');
	const elms_row = elm.querySelectorAll('tbody > tr');
	
	// Add classes
	
	const elms_columns = elm_heading.children;
	const arr_classes = ['max', 'limit', 'menu'];
	
	for (let i = 0, len = elms_columns.length; i < len; i++) {
		
		const elm_column = elms_columns[i];
		
		for (let j = 0, len_j = arr_classes.length; j < len_j; j++) {
			
			const str_class = arr_classes[j];
			
			if (!elm_column.classList.contains(str_class)) {
				continue;
			}
			
			for (let k = 0, len_k = elms_row.length; k < len_k; k++) {
				
				const elm_cell = elms_row[k].children[i];				
				
				if (elm_cell == undefined) {
					continue;
				}
				
				elm_cell.classList.add(str_class);
			}
		}		
	}

	resizeDataTable(elm);
	
	// Check loading elements
	
	const elms_image = elm.querySelectorAll('tbody > tr img');

	if (elms_image.length) {
				
		const func_check_images = function(has_loaded) {

			if (!onStage(elms_row[0])) { // Check if the table is still live
				return;
			}
						
			for (let i = 0, len_i = elms_image.length; i < len_i; i++) {
				
				const elm_img = elms_image[i];				
				
				if (elm_img.complete || elm_img.naturalWidth !== 0) {
					continue;
				}
				
				const func_check_image = function(event) {
					
					elm_img.removeEventListener('load', func_check_image);
					func_check_images(true);
				}
				
				elm_img.addEventListener('load', func_check_image);
				return;
			}
			
			if (has_loaded) {
				resizeDataTable(elm);
			}
		};

		func_check_images();
	}
	
	// Set interaction
	
	TOOLTIP.checkElement(elm_heading, 'th > span', function(elm_span) {

		if (elm_span.title) {
			return false;
		}
		
		const elm_column = elm_span.parentNode;
		
		if (elm_column.title) {
			return false;
		}
		
		const arr_size = getElementContentSize(elm_column);
		
		if (elm_span.scrollWidth > arr_size.width) {

			return elm_span.innerHTML;
		}
		
		return false;
	}, true);
}

function resizeDataTable(elm, force) { // Make tables fit
	
	var elm = getElement(elm);
	
	var func_calc = function() {

		const elms_column_all = elm.querySelectorAll('thead > tr > th');
		const elms_cell_all = elm.querySelectorAll('tbody > tr > td');
		
		elm.classList.remove('resized');
		
		for (let i = 0, len = elms_column_all.length; i < len; i++) {
			elms_column_all[i].style.maxWidth = '';
		}
		for (let i = 0, len = elms_cell_all.length; i < len; i++) {
			elms_cell_all[i].style.maxWidth = '';
		}
	
		const elm_target = elm.parentNode;
	
		const arr_style_target = window.getComputedStyle(elm_target);
		const num_padding = parseInt(arr_style_target['padding-left']) + parseInt(arr_style_target['padding-right']);
		
		let num_width_target_all = elm_target.clientWidth - num_padding;
		const num_width_real = elm.scrollWidth;
		
		const do_resize_container = elm.closest('.overlay');
		let do_resize = false;
		
		if (do_resize_container) { // If table is inside an overlay, do not try to resize the table but the overlay
			
			if (num_width_real > num_width_target_all) {
				
				elm.style.maxWidth = num_width_real+'px';
				
				// Check if the parent was able to resize to the table's size, if not, resize the table
				num_width_target_all = elm_target.clientWidth - num_padding;
				
				if (num_width_real > num_width_target_all) {
					do_resize = true;
				}
			} else {
			
				elm.style.maxWidth = num_width_target_all+'px';
			}
		} else {
			
			if (num_width_real > num_width_target_all) {
				do_resize = true;
			}
		}
		
		if (do_resize) {
	
			elm.classList.add('resized');

			let elms_column = elm.querySelectorAll('thead > tr > th.limit');
			elms_column = (elms_column.length ? elms_column : elms_column_all);
			let elms_cell = elm.querySelectorAll('tbody > tr > td.limit');
			elms_cell = (elms_cell.length ? elms_cell : elms_cell_all);
			
			let elms_column_no_limit = Array.prototype.filter.call(elms_column_all, function(n) {
				return Array.prototype.indexOf.call(elms_column, n) === -1;
			});
			
			let num_len_elms_column = elms_column.length;
			const elm_column_first = elms_column[0];
			let arr_style_columns = window.getComputedStyle(elm_column_first);
			
			let arr_columns_width = Array.prototype.map.call(elms_column, function(n) {
				return n.clientWidth;
			});
			let arr_columns_no_limit_width = Array.prototype.map.call(elms_column_no_limit, function(n) {
				return n.clientWidth;
			});

			let func_sum_arr = function(arr) {
				
				let total = 0;
				
				for (let i = 0, len = arr.length; i < len; i++) {
					total += arr[i];
				}
				
				return total;
			};
			
			let num_width_target = num_width_target_all - func_sum_arr(arr_columns_no_limit_width);
			let num_width_column_max = arr_style_columns['max-width'];
			
			if (num_width_column_max === 'none') { // Find current maximum requested width
				
				num_width_column_max = 0;
				
				for (let i = 0, len = elms_column_all.length; i < len; i++) {
					
					const num_width = elms_column_all[i].clientWidth;
					num_width_column_max = (num_width > num_width_column_max ? num_width : num_width_column_max);
				}
			}
			
			num_width_column_max = parseInt(num_width_column_max);
			let num_width_column_calc = num_width_column_max;

			for (num_width_column_calc--; func_sum_arr(arr_columns_width) > num_width_target; num_width_column_calc--) {
				
				if (num_width_column_calc == 0) {
					
					if (elms_column !== elms_column_all) { // Start over with all columns
						
						num_width_target = num_width_target_all;
						num_width_column_calc = num_width_column_max;
						elms_column = elms_column_all;
						elms_cell = elms_cell_all;
						num_len_elms_column = elms_column.length;
						
						arr_columns_width = Array.prototype.map.call(elms_column, function(n) {
							return n.clientWidth;
						});
					} else {
						
						break;
					}
				}
				
				for (let i = 0; i < num_len_elms_column; i++) {
					
					if (arr_columns_width[i] > num_width_column_calc) {
						arr_columns_width[i] = num_width_column_calc;
					}
				}
			}
			
			for (let i = 0, len = elms_cell.length; i < len; i++) {
				elms_cell[i].style.maxWidth = num_width_column_calc+'px';
			}
			for (let i = 0, len = elms_column.length; i < len; i++) {
				elms_column[i].style.maxWidth = num_width_column_calc+'px';
			}
			
			const elm_heading = elm.querySelector('thead > tr');
		
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
	
	var str = (str == null ? '' : ''+str);
	
	const arr_escape = {
		'&nbsp;': '\u00a0',
		'&amp;': '&',
		'&lt;': '<',
		'&gt;': '>',
		'&quot;': '"',
		'&apos;': "'", '&#039;': "'", '&#x27;': "'",
		'&#096;': '`', '&#x60;': '`'
	};
	
	const func_escape = function(match) {
		return arr_escape[match];
    };

    const str_regex_source = '(?:' + Object.keys(arr_escape).join('|') + ')';
    const regex_test = RegExp(str_regex_source);
    const regex_replace = RegExp(str_regex_source, 'g');
    
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

function uniqid(str_prefix = '', do_more_entropy = false) {

	const num_time = window.performance.timeOrigin + window.performance.now();
	const num_seconds = Math.floor(num_time / 1000);
	const str_part = num_seconds.toString(16).substring(0, 8);
	
	const num_microseconds = Math.floor((num_time - num_seconds * 1000) * 1000);
	const str_part2 = num_microseconds.toString(16).substring(0, 5).padStart(5, '0');
	
	return str_prefix + str_part + str_part2 + (do_more_entropy ? (Math.random() * 10).toFixed(8).toString() : '');
}

function parseCSSColor(input) {
	
	if (input[0] == '#') {
		
		let num_r, num_g, num_b = null;
		let num_a = 1;
		const num_length = input.length;
		
		if (num_length == 4 || num_length == 5) {
			num_r = +("0x" + input[1] + input[1]);
			num_g = +("0x" + input[2] + input[2]);
			num_b = +("0x" + input[3] + input[3]);
			if (num_length == 5) {
				num_a = (+("0x" + input[4] + input[4]) / 255);
			}
		} else if (num_length == 7 || num_length == 9) {
			num_r = +("0x" + input[1] + input[2]);
			num_g = +("0x" + input[3] + input[4]);
			num_b = +("0x" + input[5] + input[6]);
			if (num_length == 9) {
				num_a = (+("0x" + input[7] + input[8]) / 255);
			}
		}
		
		return {r: num_r, g: num_g, b: num_b, a: num_a};
	} else if (input.startsWith('rgb')) {
		
		const arr = input.match(/^rgb(?:a)?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:,\s*(\d+(?:\.\d+)?))?/i);
		
		if (arr) {
			
			const num_r = parseInt(arr[1]);
			const num_g = parseInt(arr[2]);
			const num_b = parseInt(arr[3]);
			const num_a = (typeof arr[4] != 'undefined' ? parseFloat(arr[4]) : 1);
			
			return {r: num_r, g: num_g, b: num_b, a: num_a};
		}
	} else if (input.startsWith('hsl')) {
		
		const arr = input.match(/^hsl(?:a)?\((\d+)[,\s]+(\d+%)[,\s]+(\d+%)(?:\s?[\/,]\s?(\d+(?:\.\d+)?))?/i);
		
		if (arr) {
			
			const num_h = parseInt(arr[1]); // 0-360
			const num_s = parseInt(arr[2]) / 100; // 0-1
			const num_l = parseInt(arr[3]) / 100; // 0-1
			const num_a = (typeof arr[4] != 'undefined' ? parseFloat(arr[4]) : 1); // 0-1
		
			const num_mix = num_s * Math.min(num_l, 1-num_l);
			const func_convert = function(num_n) {
				
				const num_k = (num_n + num_h/30) % 12;
				const num_c = num_l - num_mix * Math.max(Math.min(num_k-3, 9-num_k, 1), -1);
				
				return Math.round(num_c * 255);
			};
			
			return {r: func_convert(0), g: func_convert(8), b: func_convert(4), a: num_a};
		}
	}
	
	return {r: 255, g: 255, b: 255, a: 1};
}

function parseColorToHex(arr_color) {
	
	let hex = '0x'+(1 << 24 | (arr_color.r << 16) | (arr_color.g << 8) | arr_color.b).toString(16).slice(1);
	
	if (arr_color.a !== 1) {
		hex += (1 << 8 | Math.round(arr_color.a*255)).toString(16).slice(1);
	}
	
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
		elm_container: false
	}, arr_options || {});
		
	var elm = getElement(elm);
	const arr_pos = elm.getBoundingClientRect(); // Get position of element in relation to the client
	const is_document = (arr_options.elm_container == false);
	const elm_check = $('body [data-keep_focus=1]');
	
	if (elm_check.length && !hasElement(elm, elm_check[0], true)) {
		return;
	}
	
	let elm_container, arr_pos_container, arr_size_container, arr_pos_scroll, arr_pos_from;
	
	if (is_document) {
		
		arr_pos_container = {left: -POSITION.scrollLeft(), top: -POSITION.scrollTop()};
		arr_size_container = {width: window.innerWidth, height: window.innerHeight};
		arr_pos_scroll = {left: 0, top: 0};
		arr_pos_from = {x: POSITION.scrollLeft(), y: POSITION.scrollTop()};
	} else {
		
		elm_container = getElement(arr_options.elm_container);
		arr_pos_container = elm_container.getBoundingClientRect();
		arr_size_container = {width: elm_container.clientWidth, height: elm_container.clientHeight};
		arr_pos_scroll = {left: elm_container.scrollLeft, top: elm_container.scrollTop};
		arr_pos_from = {x: arr_pos_scroll.left, y: arr_pos_scroll.top};
	}
	
	const arr_pos_center = {x: (arr_size_container.width > elm.clientWidth ? (arr_size_container.width-elm.clientWidth)/2 : 0), y: (arr_size_container.height > elm.clientHeight ? (arr_size_container.height-elm.clientHeight)/2 : 0)};
	const arr_pos_to = {x: arr_pos_scroll.left + (arr_pos.left-arr_pos_container.left) - arr_pos_center.x, y: arr_pos_scroll.top + (arr_pos.top-arr_pos_container.top) - arr_pos_center.y};

	new TWEEN.Tween(arr_pos_from)
		.to(arr_pos_to, arr_options.duration)
		.easing(TWEEN.Easing.Sinusoidal.InOut)
		.onUpdate(function() {
			
			if (is_document) {
				
				window.scrollTo(arr_pos_from.x, arr_pos_from.y);
			} else {
				
				elm_container.scrollLeft = arr_pos_from.x;
				elm_container.scrollTop = arr_pos_from.y;
			}
		})
	.start();
	
	ANIMATOR.trigger();
}

// TOOLS

function Pulse(elm, arr_options) {
	
	const SELF = this;
	
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
		
		var arr_style = window.getComputedStyle(cur[0]);
		
		var arr_cur_css = {background_color: arr_style['background-color'], border_color: arr_style['border-top-color'], color: arr_style['color']};
		var str_style = cur[0].getAttribute('style');
		
		cur[0].removeAttribute('style');
		
		if (arr_options.to) {
			cur.addClass(arr_options.to);
		}
		if (arr_options.from) {
			cur.removeClass(arr_options.from);
		}
		
		arr_style = window.getComputedStyle(cur[0]);
		
		var arr_new_css = {background_color: arr_style['background-color'], border_color: arr_style['border-top-color'], color: arr_style['color']};
		
		cur[0].setAttribute('style', str_style);
		if (arr_options.to) {
			cur.removeClass(arr_options.to);
		}
		if (arr_options.from) {
			cur.addClass(arr_options.from);
		}
		
		if (arr_cur_css.color != arr_new_css.color) {
			
			var arr_color = parseCSSColor(arr_cur_css.color);
			arr_properties.from.colorRed = arr_color.r;
			arr_properties.from.colorGreen = arr_color.g;
			arr_properties.from.colorBlue = arr_color.b;
			arr_properties.from.colorAlpha = arr_color.a;
			var arr_color = parseCSSColor(arr_new_css.color);
			arr_properties.to.colorRed = arr_color.r;
			arr_properties.to.colorGreen = arr_color.g;
			arr_properties.to.colorBlue = arr_color.b;
			arr_properties.to.colorAlpha = arr_color.a;
		}
		if (arr_cur_css.background_color != arr_new_css.background_color) {
			
			var arr_color = parseCSSColor(arr_cur_css.background_color);
			arr_properties.from.backgroundColorRed = arr_color.r;
			arr_properties.from.backgroundColorGreen = arr_color.g;
			arr_properties.from.backgroundColorBlue = arr_color.b;
			arr_properties.from.backgroundColorAlpha = arr_color.a;
			var arr_color = parseCSSColor(arr_new_css.background_color);
			arr_properties.to.backgroundColorRed = arr_color.r;
			arr_properties.to.backgroundColorGreen = arr_color.g;
			arr_properties.to.backgroundColorBlue = arr_color.b;
			arr_properties.to.backgroundColorAlpha = arr_color.a;
		}
		if (arr_cur_css.border_color != arr_new_css.border_color) {
			
			var arr_color = parseCSSColor(arr_cur_css.border_color);
			arr_properties.from.borderColorRed = arr_color.r;
			arr_properties.from.borderColorGreen = arr_color.g;
			arr_properties.from.borderColorBlue = arr_color.b;
			arr_properties.from.borderColorAlpha = arr_color.a;
			var arr_color = parseCSSColor(arr_new_css.border_color);
			arr_properties.to.borderColorRed = arr_color.r;
			arr_properties.to.borderColorGreen = arr_color.g;
			arr_properties.to.borderColorBlue = arr_color.b;
			arr_properties.to.borderColorAlpha = arr_color.a;
		}
		
		var func_run = function() {
			
			let do_tween = true;
			
			if (arr_options.repeat) {
				
				const pos = cur[0].getBoundingClientRect(); // Get position in relation to the client of animating element
				const elm_hit = document.elementFromPoint(pos.left+(cur[0].clientWidth/2), pos.top+(cur[0].clientHeight/2)); // Do a hit-test with the coordinates, add 2 to make a hit more certain (IE)
			
				do_tween = (elm_hit && hasElement(cur[0], elm_hit, true));
			}
			
			if (do_tween) {

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
		capture: false,
		class: '',
		hash: false,
		timeout: 2000,
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
	var arr_style = window.getComputedStyle(elm_extras[0]);
	
	var arr_margin = {top: parseInt(arr_style['margin-top']), right: parseInt(arr_style['margin-right'])};
	elm_extras[0].style['margin-top'] = elm_extras[0].style['margin-right'] = 0;
	
	var func_position = function() {
		
		var pos_mod = elm_toolbox[0].getBoundingClientRect();
		var pos = elm[0].getBoundingClientRect();
		
		elm_extras[0].style.left = ((pos.left - pos_mod.left) + pos.width - (elm_extras[0].offsetWidth + arr_margin.right))+'px';
		elm_extras[0].style.top = ((pos.top - pos_mod.top) + arr_margin.top)+'px';
	};
	
	let arr_settings_client = [];
	
	if (arr_options.hash) {
		
		const str_settings = LOCATION.getHashParameter(arr_options.hash);
		arr_settings_client = (str_settings ? str_settings.split(',') : []);
	}
		
	if (arr_options.fullscreen) {
		
		const elm_button_fullscreen = $('<button type="button" class="fullscreen" title="Fullscreen"><span class="icon"></span><span class="icon"></span></button>').appendTo(elm_extras);
		
		ASSETS.getIcons(elm, ['maximize', 'minimize'], function(data) {
			
			elm_button_fullscreen[0].children[0].innerHTML = data.maximize;
			elm_button_fullscreen[0].children[1].innerHTML = data.minimize;
			
			func_position();
		});
		
		let func_fullscreen = null;
		
		if (arr_options.maximize && arr_options.maximize === 'fixed') {

			func_fullscreen = function(e) {
				
				if (elm_button_fullscreen.hasClass('minimize')) {
					
					elm.removeClass('tool-extras-big');
					$('body').removeClass('in-fullscreen');
				
					elm_button_fullscreen.removeClass('minimize');
				} else {
					
					elm.addClass('tool-extras-big');
					$('body').addClass('in-fullscreen');
				
					elm_button_fullscreen.addClass('minimize');
				}
				
				elm_extras.removeClass('hide');
				func_position();
			};
		} else {
			
			func_fullscreen = function(e) {

				elm_button_fullscreen.addClass('hide');
				elm_extras.addClass('hide');
				elm_placeholder[0].style.width = elm.outerWidth()+'px';
				elm_placeholder[0].style.height = elm.outerHeight()+'px';
				elm_placeholder.addClass('hide').insertAfter(elm);
				
				let elm_copy = elm;
				let elm_overlay_target = null;
				let mod_id = null;
				
				if (arr_options.maximize) {
					
					mod_id = getModID(elm);
					const classes = $('#mod-'+mod_id.split('-')[1]).attr('class');
					elm_copy = elm_copy.wrap('<div class="'+classes+'"></div>').parent();
					elm_overlay_target = $('body');
				} else {
					
					elm_overlay_target = (IS_CMS ? $('body') : getContainer(elm));
				}
				
				const elm_form = elm.closest('form');
				if (elm_form.length) { // Keep reference to form related elements (i.e. sorter)
					elm_copy[0].elm_form = elm_form;
					elm_copy[0].dataset.form = 1;
				}
				
				const elm_toolbox_track = elm_toolbox;
				
				const func_reflow = function() { // Reset/reflow listeners after element has been moved around
					
					runElementSelectorFunction(elm_copy, '.resize-sensor', function(elm_found) {
						elm_found.parentNode.resizesensor.reset();
					});
				};
				
				const obj_overlay = new Overlay(elm_overlay_target, elm_copy, {
					sizing: 'full-width',
					call_close: function() {
						elm_toolbox_track.append(elm_toolbox.children()); // Keep possible toolbox stuff working when switching
						elm_toolbox = elm_toolbox_track;
						
						elm.insertBefore(elm_placeholder).removeClass('tool-extras-big');
						func_reflow();
						
						elm_extras.addClass('hide');
						elm_button_fullscreen.removeClass('hide');
						elm_placeholder.remove();
					}
				});
				
				if (arr_options.maximize) {
					
					const elm_overlay = obj_overlay.getOverlay();
					setElementData(elm_overlay, 'mod', mod_id);
				}
				
				elm_placeholder.removeClass('hide');
				elm.addClass('tool-extras-big');

				elm_toolbox = getContainerToolbox(elm);
				elm_toolbox.append(elm_extras);
				
				func_reflow();
				
				SCRIPTER.runDynamic(elm);
			};
		}
		
		elm_button_fullscreen.on('click', func_fullscreen);
		
		if (arr_settings_client.includes('fullscreen')) {
			func_fullscreen();
		}
	}
	
	if (arr_options.tools) {
		
		const elm_button_tools = $('<button type="button" class="tools" title="Tools"><span class="icon"></span></button>').prependTo(elm_extras);
		
		ASSETS.getIcons(elm, ['tool'], function(data) {
			
			elm_button_tools[0].children[0].innerHTML = data.tool;
			
			func_position();
		});
		
		const func_tools = function() {
						
			if (elm[0].dataset.tools) {
				
				elm[0].dataset.tools = '';
				SCRIPTER.triggerEvent(elm, 'toolsdisable');
				elm_button_tools.addClass('active');
			} else {
				
				elm[0].dataset.tools = '1';
				SCRIPTER.triggerEvent(elm, 'toolsenable');
				elm_button_tools.removeClass('active');
			}			
		};
		
		elm[0].dataset.tools = '1';
		
		elm_button_tools.on('click', func_tools);

		if (arr_settings_client.includes('notools')) {
			func_tools();
		}
	}
	
	if (arr_options.capture) {
		
		arr_options.capture = (typeof arr_options.capture == 'object' ? arr_options.capture : {});
		const arr_selectors = (arr_options.capture.selectors ? arr_options.capture.selectors : {});
		
		const elm_button_capture = $('<button type="button" class="capture" title="Download"><span class="icon"></span></button>').prependTo(elm_extras);
		
		ASSETS.getIcons(elm, ['download'], function(data) {
			
			elm_button_capture[0].children[0].innerHTML = data.download;
			
			func_position();
		});
		
		const func_capture = function() {
			
			const elm_source = (arr_selectors.source ? elm.find(arr_selectors.source) : elm);
			
			const arr_settings_capture = {
				name: arr_options.capture.name,
				resolution: elm_source[0].dataset.resolution,
				width: elm_source[0].dataset.width,
				height: elm_source[0].dataset.height
			};
			
			const obj_capture = new CaptureElementImage(elm_source, arr_settings_capture);
			obj_capture.addLayers(arr_selectors.target);
			obj_capture.setBackgroundLayer(arr_selectors.background);
			obj_capture.download();
		};
		
		elm_button_capture.on('click', func_capture);
	}
	
	let is_listening = false;
	let timer_hide = false;
	let func_caller = null;
	const num_timeout_hide = arr_options.timeout;
	
	const func_hide = function() {
		
		elm_extras.addClass('hide');
		
		is_listening = false;
		
		if (!is_editable) {
			document.removeEventListener('mousemove', func_caller);
		} else {
			document.removeEventListener('mousedown', func_caller);
			document.removeEventListener('keydown', func_caller);
			document.removeEventListener('ajaxloaded', func_caller);
		}
		
		if (timer_hide) {
			clearInterval(timer_hide);
			timer_hide = false;
		}
	};

	elm[0].addEventListener((is_editable ? 'mouseup' : 'mouseenter'), function() {
		
		elm_extras.removeClass('hide');
		func_position();
		
		if (is_listening) {
			return;
		}
		
		if (!is_editable) {
			
			let time_check = false;
			
			const func_check = function() {

				if (!timer_hide) {
					
					time_check = window.performance.now();
					
					timer_hide = window.setInterval(function() {
				
						const time_now = window.performance.now();
						
						if (time_now - time_check >= num_timeout_hide) {
							
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
			
			func_caller = function(e) {
				
				if (elm[0] != e.target && !hasElement(elm[0], e.target) && !hasElement(elm_extras[0], e.target)) {
					func_hide();
				} else {
					func_check();
				}
			};
			
			document.addEventListener('mousemove', func_caller);
		} else {
			
			func_caller = function(e) {
				
				const is_extra = hasElement(elm_extras[0], e.target, true);
				const has_focus = hasElement(elm[0], e.target, true);
			
				if ((e.type != 'ajaxloaded' && !has_focus && !is_extra) || (e.type == 'ajaxloaded' && !is_extra)) {
					func_hide();
				}
			};
			
			document.addEventListener('mousedown', func_caller);
			document.addEventListener('keydown', func_caller);
			document.addEventListener('ajaxloaded', func_caller);
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
			return (n != '' && n.indexOf('iterate_') !== 0);
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
		}
		
		elm_button_container[0].classList.add('hide');
		
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
	
	const SELF = this;
	
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
	var elm_menu_existing = elm.next('menu'); // Append to new menu at the end
	
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
		
		CONTENT.input.setSelectionContent(elm, {replace: str_selected.replace(/\[.*?\]/g, '')});
				
		SCRIPTER.triggerEvent(elm, 'change');
	});
	
	SELF.addSeparator();
	
	var elm_button_preview = SELF.addButton({title: 'Preview', id: 'y:cms_general:preview-'+elm[0].name}, false, function() {
		
		COMMANDS.popupCommand(elm_button_preview);
	});
	
	if (elm_menu_existing.length) {
		
		SELF.addSeparator();
		
		elm_menu_existing.children().appendTo(elm_menu);
		elm_menu_existing.remove();
	}
	
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
		
		self.onmessage = function(event) {
			
			if (event.data.library) {
				obj_library = event.data.library;
			} else if (!obj_library) {
				return;
			}
			
			if (event.data.str) {
				
				var str_content = event.data.str;
				
				str_content = Prism.highlight(str_content, obj_library);
				
				self.postMessage({str: str_content});
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

FEEDBACK.addValidatorMethod('limit_filebrowse', function(value, elm) {
	
	var elm_filebrowse = $(elm).closest('.filebrowse');
	var elm_input_file = elm_filebrowse.find('.select input[type=file]');

	var arr_files = elm_input_file[0].files;
	
	if (!arr_files) {
		return true;
	}
	
	var num_size = elm_input_file[0].dataset.size;
	
	for (var i = 0; i < arr_files.length; i++) {
		
		if (num_size && arr_files[i].size > num_size) {
			
			elm_input_file[0].value = '';
			elm.value = '';
			elm_filebrowse.children('ul').empty();
			
			return false;
		}
	}

	return true;
});

FEEDBACK.addValidatorClassRules('filebrowse-path', {limit_filebrowse: true});
	
var counter_filebrowse = 0;
	
function FileBrowse(elm) {
	
	const SELF = this;
	
	var elm = $(elm);
	
	if (elm[0].filebrowse) {
		return;
	}
	elm[0].filebrowse = this;
	
	var elm_input_file = elm.find('.select > input[type=file]');
	var elm_path = elm.find('.select > label > input[type=text]');
	
	counter_filebrowse++;
	
	elm_input_file[0].setAttribute('id', 'filebrowse-'+counter_filebrowse);
	elm_input_file.next('label')[0].setAttribute('for', 'filebrowse-'+counter_filebrowse);
	
	elm_path[0].classList.add('filebrowse-path');
		
	var func_change = function() {
		
		var arr_files = (elm_input_file[0].files ? elm_input_file[0].files : false);
		var elm_files = elm.children('ul');
		
		elm_files.empty();
		elm_path.val('');
		elm_path[0].classList.remove('input-error');
		
		if (arr_files && arr_files.length > 1) {
			
			for (var i = 0; i < arr_files.length; i++) {
							
				elm_files.append('<li>'+arr_files[i].name+'</li>');
			}
			
			elm_path.val(arr_files.length+'x');
		} else {
						
			elm_path.val((arr_files ? arr_files[0].name : ''));
		}
	};
	
	elm_input_file[0].addEventListener('change', func_change);
}

function DropDown(elm, arr_options) {
	
	const SELF = this;
	
	var arr_options = $.extend({
		state_empty: false
	}, arr_options);
	
	var elm = getElement(elm);
	
	if (elm.dropdown || elm.multiple || elm.closest('li.source')) {
		return;
	}
	elm.dropdown = this;
		
	const str_placeholder = elm.getAttribute('placeholder');
	let elm_option_placeholder = false;
	
	if (str_placeholder) {
		elm_option_placeholder = $('<option value="" hidden>'+str_placeholder+'</option>')[0];
	}
	
	let is_empty = false;
	let has_input = false; // Check if an 'input' event has been fired before the 'change'
	let do_change = true; // Note if the 'change' event after the 'input' is actually needed
	
	const func_state = function(e) {
	
		// Manage placeholder
		
		if (elm_option_placeholder) {

			if (!onStage(elm_option_placeholder)) {
				const elm_selected = elm.selectedOptions[0]; // Keep original selection
				elm.append(elm_option_placeholder);
				if (elm_selected) {
					elm_selected.selected = true;
				}
			} else if (e && elm.selectedOptions[0].value == '' && is_empty) { // No change to the placeholder
				e.stopImmediatePropagation();
				elm_option_placeholder.selected = true;
				do_change = false;
				return;
			}
		}
		
		// Manage state
		
		is_empty = (elm.value == '' && !elm.matches(':empty'));
		
		if (arr_options.state_empty) {
			elm.classList.toggle('state-empty', is_empty);
		}
		
		if (elm_option_placeholder) {
			
			elm.classList.toggle('state-placeholder', is_empty);
			if (is_empty) {
				elm_option_placeholder.selected = true;
			}
		}
		
		do_change = true;
	};
	
	elm.addEventListener('input', function(e) {
		
		has_input = true;
		
		func_state(e);
	});
		
	elm.addEventListener('change', function(e) {
		
		if (!has_input) {
			func_state();
		}
		has_input = false;
		
		if (do_change) {
			return;
		}
		
		e.stopImmediatePropagation();
	});
	
	func_state();
}

function RegularExpressionEditor(elm) {
	
	const SELF = this;
		
	var elm = $(elm);
	
	if (elm[0].regex) {
		return;
	}
	elm[0].regex = this;
	
	const elm_enable = elm.find('input[name$=\"[enable]\"]');
	
	if (!elm_enable.length) {
		return;
	}
	
	const func_enable = function() {
		
		const elms_hide = elm.children('span, input[name$=\"[flags]\"]');
		
		if (elm_enable[0].checked) {
			elms_hide.removeClass('hide');
			elms_hide.filter('span').removeClass('input');
		} else {
			elms_hide.addClass('hide');
			elms_hide.filter('span').addClass('input'); // Make hidden spans pass on spacing
		}
	};
	
	func_enable();			
	elm_enable.on('change', func_enable);
}

function LazyLoad(elm, elm_scroll) {
	
	const SELF = this;
	
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
	
	const SELF = this;
	
	var arr_options = arr_options;
	
	var elm = getElement(elm);
	
	if (elm.formmanager) {
		return;
	}
	elm.formmanager = this;
	
	// Tweaking
	
	if (!elm.hasAttribute('autocomplete')) {
		elm.autocomplete = 'off';
	}
	
	// Listeners
			
	elm.addEventListener('ajaxsubmit', function(e) {
	
		runElementSelectorFunction(elm, 'ul.sorter', function(elm_found) {
			
			if (elm_found.matches('form ul.sorter ul.sorter')) { // Sorters could be nested, make sure only the top one is used
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
		
		runElementSelectorFunction(elm, 'ul.sorter', function(elm_found) {
			
			if (elm_found.matches('form ul.sorter ul.sorter')) { // Sorters could be nested, make sure only the top one is used
				return;
			}
			
			var elm_sorter = $(elm_found);
		
			elm_sorter.find('input[type=hidden].temp').remove();
			
			elm_sorter.find('[data-disabled]').prop('disabled', true).removeAttr('data-disabled');
		});
	});
	
	elm.addEventListener('reset', function(e) {
		
		runElementSelectorFunction(elm, 'ul.sorter', function(elm_found) {
			
			if (elm_found.matches('form ul.sorter ul.sorter')) { // Sorters could be nested, make sure only the top one is used
				return;
			}
			
			elm_found.sorter.reset();
		});
		
		runElementSelectorFunction(elm, 'input.autocomplete', function(elm_found) {
			
			elm_found.autocompleter.reset();
		});
		
		runElementSelectorFunction(elm, '.keep-state', function(elm_found) {
			
			if (elm_found.tagName.toLowerCase() == 'select') {
				
				let elms_option_clear = elm_found.querySelectorAll('option[selected]');
				let elms_option_keep = elm_found.querySelectorAll('option:checked');
				
				for (let i = 0, len = elms_option_clear.length; i < len; i++) {
					elms_option_clear[i].removeAttribute('selected');
				}
				for (let i = 0, len = elms_option_keep.length; i < len; i++) {
					elms_option_keep[i].setAttribute('selected', 'selected');
				}
			}
		});

		runElementSelectorFunction(elm, '.editor-content', function(elm_found) {
			
			setTimeout(function() { // Checking values after reset requires pushing code onto the event stack
				elm_found.edit_content.update();
			}, 0);
		});
	});
}

function FormManaging(arr_options) {
	
	var obj = this;
	
	this.createManagers = function(elm) {
		
		runElementSelectorFunction(elm, 'form, [data-form="1"]', function(elm_form) {
			
			if (elm_form.dataset.form) {
				elm_form = elm_form.elm_form;
			}
			
			new FormManager(elm_form);
		});
	};
	
	this.lockManagers = function(elm) {
		
		runElementSelectorFunction(elm, 'form, [data-form="1"]', function(elm_form) {
			
			runElementSelectorFunction(elm_form, '[data-lock="1"]', function(elm_found) {

				LOCATION.lock(elm_found);
			});
		});
	};
	
	this.getManager = function(elm) {
			
		var elm = getElement(elm);
		var elm_form = elm.closest('form, [data-form="1"]');
		
		if (elm_form.dataset.form) {
			elm_form = elm_form.elm_form;
		}
		
		return elm_form.formmanager;
	};
	
	this.getElementNameBase = function(elm) {
		
		var elm = getElement(elm);
		var elm_name = elm.closest('[data-form_name]');
		
		var str_name = (elm_name ? elm_name.dataset.form_name : '');	
		
		return str_name;
	};
	
	this.iterateElementsNames = function(elms, is_iterator) {
		
		if (elms instanceof Element) {
			var elms = [elms];
		}

		const elms_group_iterator = getElementsSelector(elms, '[data-group_iterator]');
		
		if (elms_group_iterator) {
			
			const arr_replace = [];
			
			for (let i = 0, len = elms_group_iterator.length; i < len; i++) {
				arr_replace.push(elms_group_iterator[i].dataset.group_iterator);
			}
			
			replaceGroupIteratorInName(elms, arr_replace);
		} else if (is_iterator) { // Is itself an iterator, start from self
			
			const elms_iterators = getElementSelector(elms, 'ul.sorter');
			const num_replace = ((elms_iterators ? elms_iterators.length : 0) + 1);
			
			replaceGroupIteratorInName(elms, num_replace);
		} else {
			
			replaceGroupIteratorInName(elms);
		}
	};
}
var FORMMANAGING = new FormManaging();

// AutoComplete

FEEDBACK.addValidatorMethod('required_autocomplete', function(value, elm) {
	
	var elms_value = $(elm).next('.autocomplete').children('ul').find('input');
	
	return (elms_value.length ? true : false);
});

function AutoCompleter(elm, arr_options) {
	
	const SELF = this;
	
	var arr_options = $.extend({
		multi: false,
		order: false,
		name: '',
		delay: 0,
		input_clear: true,
		call_request: null, // Custom data provider
		call_active: null,
		call_select: null
	}, arr_options);

	var elm = $(elm);
	
	if (elm[0].autocompleter) {
		return;
	}
	elm[0].autocompleter = this;
	
	elm[0].autocomplete = 'off'; // Disable browser property
	
	let arr_elm = {};
	let value_default = false;
	
	if (arr_options.multi) {
		
		const elm_tags = elm.prev('.autocomplete');
		
		arr_elm.values = elm_tags.find('ul');
		arr_elm.input_id = elm_tags.prev('input:hidden');
		arr_elm.input_id = (arr_elm.input_id.length ? arr_elm.input_id[0] : null);
		
		if (!arr_options.name && arr_elm.input_id) {
			arr_options.name = arr_elm.input_id.name;
		}
		
		ASSETS.getIcons(elm, ['min'], function(data) {
			
			elms_tags = arr_elm.values[0].getElementsByClassName('handler');
			
			for (let i = 0, len = elms_tags.length; i < len; i++) {
			
				elms_tags[i].innerHTML = '<span class="icon">'+data.min+'</span>';
			}
		});
		
		const elms_input = arr_elm.values[0].querySelectorAll('input');
		
		if (elms_input) {
			
			value_default = [];
		
			for (let i = 0, len = elms_input.length; i < len; i++) {
				
				let str_value = '';
				let elm_node = elms_input[i];
				
				while (elm_node = elm_node.nextSibling) {
					str_value += (elm_node.nodeType == 3 ? elm_node.textContent : elm_node.innerHTML);
				}
				
				value_default.push([str_value, elms_input[i].value]);
			}
		}
		
		if (arr_options.order) {
			
			elm_tags[0].classList.add('order');
			
			new SortSorter(arr_elm.values, {
				items: '> li',
				handle: '> li > span:first-child'
			});
		}
	} else {
		
		arr_elm.input_id = elm.prev('input:hidden');
		arr_elm.input_id = (arr_elm.input_id.length ? arr_elm.input_id[0] : null);
		
		if (arr_elm.input_id && arr_elm.input_id.value) {
			value_default = [elm[0].value, arr_elm.input_id.value];
		}
	}
	
	const value_initialise = elm[0].value;
	let value_input = value_initialise;
	let value_stored = value_initialise;
	
	const str_placeholder_default = (elm[0].placeholder ? elm[0].placeholder : '');
	if (value_initialise) {
		elm[0].placeholder = value_initialise;
	}
	
	let timer_delay = false;
	
	let elm_toolbox = getContainerToolbox(elm);
	const elm_popout = $('<dialog class="popout dropdown"></dialog>').appendTo(elm_toolbox);
	let popout_is_opening = false;
	let popout_is_open = false;
	
	const elm_dropdown = $('<ul></ul>').appendTo(elm_popout);
	
	var func_draw = function(arr) {
		
		SELF.position(true);
		
		if (!arr) {
			return;
		}

		for (let i = 0, len = arr.length; i < len; i++) {

			const arr_value = arr[i];
			
			const elm_item = $('<li>');
			const elm_label = $('<a tabindex="-1">'+arr_value.label+'</a>').appendTo(elm_item);
			if (arr_value.title) {
				elm_label.attr('title', arr_value.title);
			}
			const elm_target = elm_label.children();
			
			if (elm_target.length && elm_target.attr('id')) {
				
				COMMANDS.setTarget(elm_target, function(data) {
					
					if (data instanceof Array) {
						
						for (let j = 0; j < data.length; j++) {
							
							if (!data[j].id) {
								continue;
							}
							
							SELF.add(data[j].value, data[j].id);
						}
					} else if (data.id) {
						
						SELF.add(data.value, data.id);
					}
				});
				COMMANDS.setOptions(elm_target, {overlay: elm.closest('.mod')});
			}

			elm_label[0].autocomplete_value = arr_value;
			
			elm_item.appendTo(elm_dropdown);							
		}
	};

	var func_request = function() {
	
		FEEDBACK.stop(elm);
		COMMANDS.quickCommand(elm, func_draw);
	};
	
	var func_update = function() {
		
		popout_is_opening = true;
		
		var func_call = function() {
			
			if (timer_delay) {
				elm.removeClass('waiting');
				timer_delay = false;
			}
			
			if (arr_options.call_request) {
				arr_options.call_request(value_input, func_draw);
			} else {
				func_request();
			}
		};
		
		if (arr_options.delay) {
			
			if (timer_delay) {
				clearTimeout(timer_delay);
			} else {
				elm.addClass('waiting');
			}
			
			if (elm[0].value) { // Only delay when there is a value
				timer_delay = setTimeout(func_call, arr_options.delay * 1000);
				return;
			} else {
				timer_delay = true;
			}
		}
		
		func_call();
	};
	
	// Input interaction

	let is_input_keys = false;
	const func_check_input = function(do_force) {
		
		if (!do_force && value_input == elm[0].value) { // New input value
			return;
		}
		
		value_input = elm[0].value;
		func_update();
	};
	
	elm.on('input.autocomplete', function(e) {
		
		if (is_input_keys) {
			return;
		}
		
		func_check_input();
	}).on('keydown.autocomplete', function(e) {
		
		is_input_keys = true;
	}).on('keyup.autocomplete', function(e) {
		
		is_input_keys = false;
		
		if (e.which == 27 && value_input && (popout_is_open || popout_is_opening)) { // Key escape
			
			SELF.close();
			SCRIPTER.triggerEvent(elm, 'focus');
		} else if (e.which == 40 && value_input && popout_is_open) { // Key down
			
			SCRIPTER.triggerEvent(elm_dropdown.find('a:first'), 'focus');
			e.stopPropagation();
		} else if ((e.which == 8 || e.which == 46) && !value_input && !elm[0].value) { // Backspace or delete
			
			if (arr_elm.input_id) {
				arr_elm.input_id.value = '';
			}
			
			value_stored = '';
			value_input = '';
			elm[0].placeholder = str_placeholder_default;
		} else {
			
			func_check_input();
		}
	}).on('click.autocomplete focus.autocomplete', function(e) {
		
		if (!popout_is_open && !popout_is_opening) {
			
			if (arr_options.input_clear) {
				elm[0].value = '';
			}
			
			func_check_input(true);
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
		
		const elm_label = this;
		
		if (arr_options.call_active) {
			arr_options.call_active(elm_label, elm_label.autocomplete_value);
		}
		
		elm_dropdown.find('a').removeClass('active');
		elm_label.classList.add('active');
	}).on('click.autocomplete enter.autocomplete', '> li > a', function(e) {
		
		const elm_label = $(this);
		const elm_target = elm_label.children();
		
		if (elm_target.length && elm_target.is('[type=button]')) {
			
			if (elm_target[0] != e.target) {
				SCRIPTER.triggerEvent(elm_target, 'click');
			}
		} else {
			
			let arr_value = elm_label[0].autocomplete_value;
			
			if (arr_options.call_select) {
				arr_value = arr_options.call_select(elm_label[0], arr_value);
			}
			
			if (arr_value.value) {
				SELF.add(arr_value.value, arr_value.id);
			}

			if (e.type == 'enter') { // On keys interaction keep the dropdown active
				SELF.close();
				SCRIPTER.triggerEvent(elm, 'focus');
			} else if (arr_options.multi) { // On mouse with a multi selector, keep focus, but remove dropdown
				SCRIPTER.triggerEvent(elm, 'focus');
				SELF.close();
			} else { // On mouse with single select, no focus and no dropdown
				SELF.close();
				SCRIPTER.triggerEvent(elm, 'blur');
			}
		}
	}).on('keyup.autocomplete', '> li > a', function(e) {
		
		const elm_label = $(this);
		
		if (e.which == 27 || e.which == 8 || (e.which == 38 && elm_label.parent('li').is(':first-child'))) { // Key escape, backspace or up
			
			e.stopPropagation();
			SELF.close();
			SCRIPTER.triggerEvent(elm, 'focus');
		} else if (e.which == 38 || e.which == 40) { // Key up, down
			
			let elm_target = elm_label.closest('li');
			elm_target = (e.which == 38 ? elm_target.prev() : elm_target.next());
			elm_target = elm_target.children('a');
			
			if (elm_target.length) {
				SCRIPTER.triggerEvent(elm_target, 'focus');
			}
		} else if (e.which == 13) { // Key enter
			
			SCRIPTER.triggerEvent(elm_label, 'enter');
		} 
	});
	
	if (arr_options.multi) {
		
		arr_elm.values.on('click.autocomplete', 'li > span:last-child', function() {
			
			$(this).parent().remove();
		});
		
		TOOLTIP.checkElement(arr_elm.values[0], 'li > span:first-child', function(elm_target) {

			const arr_style = window.getComputedStyle(elm_target);
			
			const num_width_max = parseInt(arr_style['max-width']);
			
			if (num_width_max && parseInt(arr_style['width']) == num_width_max) {
				return elm_target.innerHTML;
			}
			
			return false;
		});
	} else {
		
		TOOLTIP.checkElement(elm[0], false, function(elm_target) {
			
			const num_width = getInputTextSize(elm_target);
			const arr_style = window.getComputedStyle(elm_target);
			
			if (num_width > parseInt(arr_style['width'])) {
				return elm_target.value;
			}
			
			return '';
		});
	}
	
	let func_remove_position = function() {};
	
	this.position = function(do_new) {
			
		const elm_toolbox_check = getContainerToolbox(elm);
		
		if (elm_toolbox_check[0] != elm_toolbox[0]) {
			elm_toolbox = elm_toolbox_check.append(elm_popout);
		}

		if (do_new) {
			elm_dropdown.empty();
		}
		elm_popout[0].open = true; // Native dialog setting
		
		const arr_pos_mod = elm_toolbox[0].getBoundingClientRect();
		const arr_pos = elm[0].getBoundingClientRect();
		elm_popout[0].style.minWidth = arr_pos.width+'px';
		elm_popout[0].style.left = (arr_pos.left - arr_pos_mod.left)+'px';
		elm_popout[0].style.top = ((arr_pos.top - arr_pos_mod.top) + arr_pos.height)+'px';
		
		if (!popout_is_open) {
			
			const func_check_position = function(e) {
				
				if (
					(
						// Source input is not our element, source input is not in focus
						elm[0] != e.target && !elm[0].matches(':focus') &&
						// Target element is not part of our popout
						!hasElement(elm_popout[0], e.target) &&
						// Target element is not part of directly related element, i.e. not a new related dialog that is overlaying
						!(e.type == 'focusin' && e.target.matches('.dialog') && !hasElement(e.target, elm[0]))
					) ||
					// Target element loads new data and is ours (close to renew)
					(e.type == 'ajaxloaded' && hasElement(elm_popout[0], e.target)) ||
					// There is no popout
					!popout_is_open
				) {
					
					if (onStage(elm[0])) {
						SELF.close();
					} else {
						func_remove_position();
					}
				}
			};
			
			document.addEventListener('mouseup', func_check_position);
			document.addEventListener('keyup', func_check_position);
			document.addEventListener('focusin', func_check_position);
			document.addEventListener('ajaxloaded', func_check_position);
			
			func_remove_position = function() {
				
				document.removeEventListener('mouseup', func_check_position);
				document.removeEventListener('keyup', func_check_position);
				document.removeEventListener('focusin', func_check_position);
				document.removeEventListener('ajaxloaded', func_check_position);
				
				func_remove_position = function() {};
			};
		}
		
		popout_is_open = true;
	};
	
	this.close = function() {
		
		if (arr_options.call_inactive) {
			arr_options.call_inactive();
		}
				
		FEEDBACK.stop(elm);
		popout_is_opening = false;
		
		elm_popout[0].open = null;
		elm_dropdown.empty();
		
		if (arr_options.input_clear) {
			
			elm[0].value = value_stored;
			value_input = value_stored;
		}

		func_remove_position();
		popout_is_open = false;
	};
	
	this.add = function(value, id, do_trigger) {

		let value_input_new = '';
		
		if (arr_options.multi) {
			
			const elm_tag = $('<li><span><input type="hidden" name="" value="" />'+value+'</span><span class="handler"></span></li>');
			const elm_tag_input = elm_tag[0].children[0].children[0];
			elm_tag_input.name = arr_options.name+'['+id+']';
			elm_tag_input.value = id;
			
			arr_elm.values.append(elm_tag);
			
			ASSETS.getIcons(elm, ['min'], function(data) {
				
				elm_tag[0].getElementsByClassName('handler')[0].innerHTML = '<span class="icon">'+data.min+'</span>';
			});

			elm[0].value = value_input_new;
		} else {
			
			value_input_new = decodeHTMLSpecialChars(value);
			elm[0].value = value_input_new;
			
			TOOLTIP.recheckElement(elm[0]);
		}

		value_input = value_input_new;
		value_stored = value_input_new;
		elm[0].placeholder = value_input_new;
		
		if (arr_elm.input_id) {
		
			arr_elm.input_id.value = id;
			
			if (do_trigger !== false) { 
				SCRIPTER.triggerEvent(arr_elm.input_id, 'change');
			}
		}
	};
	
	this.reset = function() {
		
		SELF.clear();
		
		if (value_default) {
			
			if (arr_options.multi) {
				for (let i = 0, len = value_default.length; i < len; i++) {
					SELF.add(value_default[i][0], value_default[i][1], false);
				}
			} else {
				SELF.add(value_default[0], value_default[1], false);
			}
		}
	};
	
	this.clear = function() {
		
		if (arr_options.multi) {
			
			while (arr_elm.values[0].firstChild) {
				arr_elm.values[0].removeChild(arr_elm.values[0].firstChild);
			}
		}
		
		elm[0].value = '';
		
		value_input = '';
		value_stored = '';
		elm[0].placeholder = str_placeholder_default;
		
		if (arr_elm.input_id) {
			arr_elm.input_id.value = '';
		}
	};
	
	this.getPopout = function() {
		
		return elm_popout[0];
	};
}

$.fn.autocomplete = function() {
	
	var obj = new ElementObjectByParameters(AutoCompleter, 'autocompleter', arguments);
	
	return this.each(obj.run);
};

function PickColor(elm, arr_options) {
	
	const SELF = this;
	
	var arr_options = $.extend({
		alpha: false
	}, arr_options);

	var elm = $(elm);
	
	if (elm[0].pickcolor) {
		return;
	}
	elm[0].pickcolor = this;
	
	const elm_input = elm.children('input');
	const value_initialise = elm_input[0].value;
	let value_input = value_initialise;
	
	const elm_color = $('<button type="button"></button>').appendTo(elm);
	
	const func_use_color = function() {
		elm_color[0].style.setProperty('--color', (value_input ? value_input : null));
	};
	func_use_color();
	
	let elm_toolbox = getContainerToolbox(elm);
	let elm_popout = null;
	let popout_is_opening = false;
	let popout_is_open = false;
	
	let colorpicker = null;
	
	const func_update_instance = function() {
		
		value_input = elm_input[0].value;
		func_use_color();
		
		if (colorpicker) {
			
			try {
				if (arr_options.alpha) {
					colorpicker.color.hex8String = value_input;
				} else {
					colorpicker.color.hexString = value_input;
				}
			} catch (e) { }
			
			return;
		}
		
		const arr_style = window.getComputedStyle(elm_popout[0]);
		
		const arr_colorpicker = {
			width: 200,
			margin: arr_style.paddingLeft,
			borderWidth: 0,
			layoutDirection: 'horizontal',
			layout: [
				{component: iro.ui.Box},
				{component: iro.ui.Slider, options: {sliderType: 'hue'}}
			],
			color: (value_input ? value_input : '#00ffff')
		};
		
		if (arr_options.alpha) {
			arr_colorpicker.layout.push({component: iro.ui.Slider, options: {sliderType: 'alpha'}});
		}
		
		colorpicker = new iro.ColorPicker(elm_popout[0], arr_colorpicker);
		
		colorpicker.on('color:change', function(color) {
			
			if (arr_options.alpha && color.alpha != 1) {
				value_input = color.hex8String;
			} else {
				value_input = color.hexString;
			}
			
			elm_input[0].value = value_input;
			func_use_color();
		});	
	};
	
	const func_update = function() {
		
		popout_is_opening = true;
		
		if (elm_popout === null) {
			elm_popout = $('<dialog class="popout pickcolor"></dialog>').appendTo(elm_toolbox);
		}

		ASSETS.fetch(false, {script: ['/CMS/js/support/iro.min.js']}, function() {
			
			func_update_instance();

			SELF.position();
		});
	};
	
	// Input interaction
	
	const func_input_change = function() {
		
		if (value_input != elm_input[0].value) {
			func_update();
		}
	};
	const func_input_focus = function() {
		
		if (!popout_is_open && !popout_is_opening) {
			func_update();
		}
	};
	
	elm_input[0].addEventListener('input', func_input_change);
	elm_input[0].addEventListener('focus', func_input_focus);
	
	elm_color[0].addEventListener('focus', function() {
		SCRIPTER.triggerEvent(elm_input[0], 'focus');
	});
	
	let func_remove_position = function() {};
	
	this.position = function() {
			
		const elm_toolbox_check = getContainerToolbox(elm);
		
		if (elm_toolbox_check[0] != elm_toolbox[0]) {
			elm_toolbox = elm_toolbox_check.append(elm_popout);
		}

		elm_popout[0].open = true; // Native dialog setting
		
		const arr_pos_mod = elm_toolbox[0].getBoundingClientRect();
		const arr_pos = elm_input[0].getBoundingClientRect();
		elm_popout[0].style.minWidth = arr_pos.width+'px';
		elm_popout[0].style.left = (arr_pos.left - arr_pos_mod.left)+'px';
		elm_popout[0].style.top = ((arr_pos.top - arr_pos_mod.top) + arr_pos.height)+'px';
		
		if (!popout_is_open) {
			
			const func_check_position = function(e) {
				
				if (
					// Source input is not our element, source input is not in focus, target element is not part of our popout, target element is not part of directly related element (i.e. not a new related dialog)
					(elm_input[0] != e.target && !elm_input[0].matches(':focus') && !hasElement(elm_popout[0], e.target) && !(e.type == 'focusin' && e.target.matches('.dialog'))) ||
					// Target element loads new data and is ours (close to renew)
					(e.type == 'ajaxloaded' && hasElement(elm_popout[0], e.target)) ||
					// There is no popout
					!popout_is_open
				) {
					
					if (onStage(elm[0])) {
						SELF.close();
					} else {
						func_remove_position();
					}
				}
			};
			
			document.addEventListener('mouseup', func_check_position);
			document.addEventListener('keyup', func_check_position);
			document.addEventListener('focusin', func_check_position);
			document.addEventListener('ajaxloaded', func_check_position);
			
			func_remove_position = function() {
				
				document.removeEventListener('mouseup', func_check_position);
				document.removeEventListener('keyup', func_check_position);
				document.removeEventListener('focusin', func_check_position);
				document.removeEventListener('ajaxloaded', func_check_position);
				
				func_remove_position = function() {};
			};
		}
		
		popout_is_open = true;
	};
	
	this.close = function() {
		
		popout_is_opening = false;
		
		elm_popout[0].open = null;
		
		func_remove_position();
		popout_is_open = false;
	};
	
	this.clear = function() {
		
		elm_input[0].value = '';
		value_input = '';
	};
}

// Sorter

function Sorter(elm, arr_options) {
	
	const SELF = this;
	
	var arr_options = $.extend({
		elm_menu: false,
		prepend: false,
		auto_add: false,
		auto_clean: false,
		state_empty: false,
		limit: 0
	}, arr_options || {});
	
	var elm = $(elm);
	
	if (elm[0].sorter) {
		return;
	}
	elm[0].sorter = this;
	
	var html_source = false;
	var is_copy = false;
	var arr_html_default = [];

	var elm_source = elm.children('li.source');
	
	if (elm_source.length) {
		
		elm_source.removeClass('source').remove();
		html_source = elm_source[0].outerHTML;
	}
	
	var elms_row = elm.children('li');

	if (!elm_source.length && !elms_row.length) {
		return;
	}
	
	var selector_handle = '.handle';

	if (!elm_source.length) {
		
		const elm_first = elms_row.first();
		
		html_source = elm_first[0].outerHTML;
		is_copy = true;
	}
	
	elms_row.each(function() {
		arr_html_default.push(this.outerHTML);
	});
	
	if (arr_options.elm_menu) {
		
		const elm_menu = getElement(arr_options.elm_menu);
		
		$(elm_menu).off('.sorter')
			.on('click.sorter', '.add', function() {
				SELF.addRow();
			}).on('click.sorter', '.del', function() {
				SELF.clean();
			}).on('click.sorter', '.order', function() {
				
				if (!this.nextElementSibling && this.previousElementSibling && this.previousElementSibling.classList.contains('split')) {
					elm_menu.removeChild(this.previousElementSibling);
				}
				elm_menu.removeChild(this);
				
				SELF.initSortSorter(true);
			});
	}
	
	var func_check_state = function() {
		
		const elms_children = SELF.getRows();
	
		elm[0].classList.remove('state-single', 'state-empty');
		
		if (elms_children.length == 1) {
			elm[0].classList.add('state-single');
			if (arr_options.state_empty && func_is_empty_row(elms_children[0])) {
				elm[0].classList.add('state-empty');
			}
		}
	};
	
	var func_auto_clean_row = function(elm_row) {
		
		if (!arr_options.auto_clean) {
			return;
		}
		
		if (elm_row.parentNode != elm[0]) {
			return;
		}
		
		if (SELF.getRows().length == 1) {
			return;
		}
		if (!func_is_empty_row(elm_row)) {
			return;
		}
		
		func_remove_rows(elm_row);
		if (arr_options.limit) {
			func_auto_add_row();
		}
		
		func_check_state();
		
		SCRIPTER.triggerEvent(elm[0], 'removed');
	};
	
	var func_auto_add_row = function(elm_row) {
		
		if (!arr_options.auto_add) {
			return;
		}
		
		if (!elm_row) {
			var elm_row = SELF.getRows(-1);
		}
		
		if (!elm_row.matches(':last-child') || elm_row.parentNode != elm[0]) {
			return;
		}
		
		if (arr_options.auto_clean && func_is_empty_row(elm_row)) {
			return;
		}
		
		SELF.addRow({focus: false});
	};
	
	elm[0].addEventListener('change', function(e) {
		
		const elm_row = e.target.closest('.sorter > li');
		
		func_auto_clean_row(elm_row);
		func_auto_add_row(elm_row);
	});
	
	var func_init = function() {
		
		SELF.initSortSorter();
		
		func_check_state();
	};
	
	this.initSortSorter = function(generate) {
		
		var func_html = function() {
			
			ASSETS.getIcons(elm, ['updown'], function(data) {
				
				let elms_row = $(SELF.getRows());
			
				const elm_source = $(html_source);
				elms_row = elms_row.add(elm_source);
				
				elms_row = elms_row.prepend('<span></span>');
				elms_row = elms_row.children('span:first-child');

				for (let i = 0, len = elms_row.length; i < len; i++) {
			
					elms_row[i].innerHTML = '<span class="icon">'+data.updown+'</span>';
				}
				
				html_source = elm_source[0].outerHTML;
				
				func_sort();
			});
		};
		
		var func_sort = function() {
			
			const elm_first = $(SELF.getRows(0));
			
			if (!elm_first.children('span').length && !elm_first.find('.handle').length) {
				return;
			}
						
			const elm_span = elm_first.children('span');
			if (elm_span.length) {
				selector_handle = (elm_span.first().is(':last-child') ? '> li > span:last-child' : '> li > span:first-child');
			}
			
			new SortSorter(elm, {
				items: '> li',
				handle: selector_handle
			});
		};
		
		if (generate) {
			func_html();
		} else {
			func_sort();
		}
	};

	this.getSource = function() {
		
		return html_source;
	};
	this.setSource = function(html, is_copy) {
		
		html_source = html;
		is_copy = (is_copy ? is_copy : false);
	};
	
	this.getRows = function(num_row) {
		
		const elms_children = getElementSelector(elm[0], ':scope > li');
		
		if (num_row != null) {
			
			if (num_row < 0) {
				return elms_children[elms_children.length+num_row];
			}
			return elms_children[num_row];
		}
		
		return elms_children;
	};

	this.addRow = function(arr_options_row) {
		
		var arr_options_row = $.extend({
			focus: true,
			prepend: arr_options.prepend,
		}, arr_options_row || {});
		
		if (arr_options.limit && SELF.getRows().length >= arr_options.limit) {
			return;
		}
					
		const elm_target = $(html_source);
		
		if (is_copy) {
			SELF.resetRow(elm_target);
		}
		
		FORMMANAGING.iterateElementsNames(elm_target, true); // Manage dynamic group-iterator-in-name

		// Add new row
		
		if (arr_options_row.prepend) {
			elm_target.prependTo(elm);
		} else {
			elm_target.appendTo(elm);
		}
		
		if (arr_options_row.focus) {
			
			const elm_first = elm_target.find('input, select, textarea').first();
			
			SCRIPTER.triggerEvent(elm_first, 'focus');
		}
		SCRIPTER.triggerEvent(elm, 'ajaxloaded', {elm: elm_target});
		
		func_check_state();
		
		return elm_target[0];
	};
	
	this.resetRow = function(elm_row) {

		unloadClonedElements(elm_row);
	};
	
	var func_is_empty_row = function(elm_check) {
		
		return (!$(elm_check).find('input, select:has(option[value=""])').first().val());
	};
	
	var func_remove_rows = function(elms_row) {
		
		$(elms_row).remove();
	};
	
	this.clean = function() {
	
		const elms_row = $(SELF.getRows());
		
		let elms_remove = elms_row.filter(function() {
			return func_is_empty_row(this); // Empty means the first meaningful element (i.e. any input element or a select with the possibility for '') is empty
		});
		
		if (elms_row.length == elms_remove.length) { // Keep at least one item
			elms_remove = elms_remove.slice(1);
		}
		
		func_remove_rows(elms_remove);
		
		func_check_state();
	};
	
	this.clear = function() {
		
		const elms_row = SELF.getRows();

		func_remove_rows(elms_row);
		
		SELF.addRow();
	};
	
	this.reset = function() {
		
		const elms_row = SELF.getRows();

		func_remove_rows(elms_row);
		
		for (let i = 0, len = arr_html_default.length; i < len; i++) {
			
			const elm_target = $(arr_html_default[i]);
			
			elm.append(elm_target);

			SCRIPTER.triggerEvent(elm, 'ajaxloaded', {elm: elm_target});
		}
		
		func_check_state();
	};
	
	func_init();
}

$.fn.sorter = function() {
	
	var obj = new ElementObjectByParameters(Sorter, 'sorter', arguments);
	
	return this.map(obj.run);
};

// SortSorter
	
var counter_sortsorter = 0;

function SortSorter(elm, arr_options) {
	
	const SELF = this;
	
	var arr_options = $.extend({
		container: 'ul',
		items: '> li',
		handle: '> li > span:first-child',
		nested: false,
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
		
		let do_connect = false;
		let elm_target = null;
		let elm_container = null;
		
		if (arr_options.nested) {
			
			elm_target = e.target.closest(arr_options.container);
			elm_container = elm_target;
		} else {
			
			elm_target = elm;
			elm_container = elm;
			
			if (!elm_target.matches(arr_options.container)) {
				
				do_connect = true;
				elm_container = elm_target.querySelectorAll(arr_options.container);
			}
			
			elm.removeEventListener('mousedown', func_init, true);
		}
	
		if (elm_target.arr_sortsortables) {
			
			if (arr_options.nested) {
				return;
			}
			
			for (let i = 0; i < elm_target.arr_sortsortables.length; i++) { // Prepare to re-init the sortable
				elm_target.arr_sortsortables[i].destroy();
			}
		}
		elm_target.arr_sortsortables = [];
		
		const num_identifier = counter_sortsorter + 1;
		counter_sortsorter++;
		
		if (!do_connect) {
			elm_container = [elm_container];
		}
		
		for (let i = 0; i < elm_container.length; i++) {
			
			elm_container[i].setAttribute('data-sortable_identifier', num_identifier);
			
			const arr_settings = {
				animation: 150,
				draggable: arr_options.container+'[data-sortable_identifier="'+num_identifier+'"] '+arr_options.items,
				handle: arr_options.container+'[data-sortable_identifier="'+num_identifier+'"] '+arr_options.handle,
				group: 'connect_'+num_identifier,
				ghostClass: 'sortsorter-placeholder',
				chosenClass: 'sortsorter-dragging'
			}
			
			if (arr_options.call_start) {
				
				arr_settings.onStart = function(e2) {
					arr_options.call_start($(e2.item));
				};
			}											
			if (arr_options.call_stop) {
				
				arr_settings.onEnd = function(e2) {
					arr_options.call_stop($(e2.item));
				};
			}
			if (arr_options.call_update) {
				
				arr_settings.onSort = function(e2) {
					arr_options.call_update($(e2.item));
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
	
	const SELF = this;
	
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
			
			const arr_state = {
				index: false,
				elm_content: false
			};
			
			new SortSorter(elm_nav, {
				container: 'ul',
				handle: '> li > a',
				items: '> li:not([data-sortable="0"])',
				nested: true,
				call_start: function(elm) {
					
					elm.addClass('sorting');
					arr_state.elm_content = [];
					
					const str_hash = elm.children('a')[0].hash;
					
					if (str_hash) { // Try tab id
						arr_state.elm_content = cur.children(str_hash);
					}
					
					if (!arr_state.elm_content.length) { // Use tab index
					
						arr_state.index = elm.index() - (elm.prevAll('.no-tab, :hidden').length);
						arr_state.elm_content = cur.children('div').eq(arr_state.index);
					}
				},
				call_stop: function(elm) {
					
					elm.removeClass('sorting');
				},
				call_update: function(elm) {
					
					var elm_sibling = elm.next('li');
					var sibling = 'next';
					if (!elm_sibling.length) {
						elm_sibling = elm.prev('li');
						sibling = 'prev';
					}
					
					var elm_content_sibling = [];
					
					const str_hash = elm_sibling.children('a')[0].hash;
					
					if (str_hash) {
						elm_content_sibling = cur.children(str_hash);
					}
					
					if (!elm_content_sibling.length) {
					
						var index_sibling = elm_sibling.index() - (elm_sibling.prevAll('.no-tab').length);
						var elm_content_sibling = cur.children('div');
						if (sibling == 'next') {
							elm_content_sibling = elm_content_sibling.eq(index_sibling-(arr_state.index >= index_sibling ? 1 : 0));
						} else {
							elm_content_sibling = elm_content_sibling.eq(index_sibling+1);
						}
					}
					
					if (sibling == 'next') {
						arr_state.elm_content.insertBefore(elm_content_sibling);
					} else {
						arr_state.elm_content.insertAfter(elm_content_sibling);
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
		
		const elm_tab_active = elm_nav.find('.active')[0];
		
		SCRIPTER.triggerEvent(elm_content, 'open');
		arr_options.call_open.apply(elm_content);
		
		if (elm_tab_active && elm_tab_active.tabs_options_tab) { // Could be missing an actual tab
			elm_tab_active.tabs_options_tab.call_open.apply(elm_content);
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
	
	const SELF = this;
	
	var arr_options = $.extend({
		sizing: 'fit-width', // fit, fit-width, full, full-width, force-full-width
		size_retain: true,
		ratio: false,
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
				arr_icons.push('prev', 'next');
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
		elm_overlay_active[0].classList.add('active', arr_options_active.sizing);
		
		// Current overlay resize (width)
		
		var elm_dialog = elm_overlay_active.children('.dialog');
		
		if (arr_options_active.ratio) {
			
			elm_overlay_active[0].classList.add('ratio');
			elm_dialog[0].style.height = (elm_dialog[0].offsetWidth * (arr_options_active.ratio[1] / arr_options_active.ratio[0]))+'px';
		}
		
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
						elm_dialog[0].style['margin-left'] = -((width_needed - arr_options_self.org_width) / 2)+'px';
					} else {
						elm_dialog[0].style['margin-left'] = '';
					}
				}
				
				if (arr_options_active.size_retain) {
					
					var arr_style_dialog = window.getComputedStyle(elm_dialog[0]);
					
					if (width_elm_dialog > parseInt(arr_style_dialog['min-width'])) {
						elm_dialog[0].style['min-width'] = width_elm_dialog+'px';
					}
				}
				
				if (arr_options_active.ratio) {
					elm_dialog[0].style.height = (width_elm_dialog * (arr_options_active.ratio[1] / arr_options_active.ratio[0]))+'px';
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
			cur[0].style['min-height'] = height+'px';
		} else {
			cur[0].style['min-height'] = '';
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
				
			elm_overlay_active.addClass('close');	
			
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
	
	this.sizing = function(str_sizing) {
		
		elm_overlay_active[0].classList.remove(arr_options_active.sizing);
		
		arr_options_active.sizing = str_sizing;
		elm_overlay_active[0].classList.add(str_sizing);
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
	
	const SELF = this;
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
	
	const SELF = this;
	
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
		
		const cur = $(this);
		
		const elm_view = $('<div class="album-viewer"><nav class="album-items"></nav></div>');
		const elm_nav = elm_view.children('nav');
		
		let num_index = elm.find(selector).index(cur)-1;
		
		var elm_nav_nextprev = $('<button type="button" class="prev"><span class="icon"></span></button><button type="button" class="next"><span class="icon"></span></button>').on('click', function() {

			num_index = (num_index+(this.classList.contains('next') ? 1 : -1));
			
			var elms_album = elm[0].querySelectorAll(selector);
			
			if (num_index >= elms_album.length) {
				num_index = 0;
			} else if (num_index < 0) {
				num_index = elms_album.length-1;
			}
			
			elm_view[0].dataset.items = elms_album.length;
			elm_view[0].dataset.index = num_index;
			
			var elm_pick = elms_album[num_index];
			
			LOADER.start(elm_view);
			
			const elm_source = (selector == 'figure' ? elm_pick.querySelector('img, video') : elm_pick);
			let elm_target = null;
			
			if (elm_source) {
				
				elm_view.children('img, video').remove();
				
				if (elm_source.matches('video')) {
					
					elm_target = $(elm_source.outerHTML);

					elm_target[0].addEventListener('loadeddata', function() {
					
						LOADER.stop(elm_view);
					});
					
					elm_target.prependTo(elm_view);
				} else {
					
					elm_target = $('<img src="" />');
					const str_url = LOCATION.getOriginalURL(elm_source.getAttribute('src'));

					elm_target[0].addEventListener('load', function() {
					
						LOADER.stop(elm_view);
					});
					
					elm_target[0].setAttribute('src', str_url);
					
					elm_target.prependTo(elm_view);
				}
				
				if (elm_source.dataset.native_width) {
					elm_target[0].width = elm_source.dataset.native_width;
				}
				if (elm_source.dataset.native_height) {
					elm_target[0].height = elm_source.dataset.native_height;
				}
			}
			
			if (selector == 'figure') {
				
				elm_view.children('div').remove();
					
				const elm_caption = elm_pick.querySelector('figurecaption');
				
				if (elm_caption) {
					
					$('<div>'+elm_caption.innerHTML+'</div>').appendTo(elm_view);
				}
			}
		}).appendTo(elm_nav);
		
		ASSETS.getIcons(cur, ['prev', 'next'], function(data) {
		
			elm_nav_nextprev[0].children[0].innerHTML = data.prev;
			elm_nav_nextprev[1].children[0].innerHTML = data.next;
		});
				
		SCRIPTER.triggerEvent(elm_nav.children('.next'), 'click');
		
		const obj_overlay = new Overlay(document.body, elm_view, {sizing: 'fit-width', size_retain: false});
		
		let elm_overlay = obj_overlay.getOverlay();
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

function CaptureElementImage(elm, settings) {
	
	const SELF = this;
	
	var arr_settings = $.extend({
		name: '1100CC',
		resolution: false,
		width: 0,
		height: 0
	}, settings || {});
	
	var arr_layers = [];
	var str_background_color = '';
	var count_loading = 0;
	
	const elm_source = getElement(elm);
	const pos_source = POSITION.getElementToDocument(elm_source);
	
	const num_width_view = elm_source.clientWidth;
	let num_width_render = 0;
	if (arr_settings.resolution) {
		num_width_render = Math.ceil(arr_settings.width * (arr_settings.resolution / 2.54)); // CM to pixel
	}
	const num_width = (num_width_render ? num_width_render : num_width_view);
	
	const num_height_view = elm_source.clientHeight;
	let num_height_render = 0;
	if (arr_settings.resolution) {
		num_height_render = Math.ceil(arr_settings.height * (arr_settings.resolution / 2.54));
	}
	const num_height = (num_height_render ? num_height_render : num_height_view);
	
	const num_resolution = (num_width / num_width_view);
		
	this.addLayers = function(elms_or_selector) {
		
		let elms_target = null;
		
		if (typeof elms_or_selector == 'string') {
			elms_target = getElementSelector(elm_source, elms_or_selector, true);
		} else if (elms_or_selector) {
			elms_target = elms_or_selector;
		} else {
			elms_target = getElementSelector(elm_source, 'canvas, svg:not(svg svg)', true);
		}

		for (let i = 0, len = elms_target.length; i < len; i++) {
			
			let elm_layer = elms_target[i];
			
			let num_width_layer = elm_layer.clientWidth;
			let num_height_layer = elm_layer.clientHeight;
						
			if (!num_width_layer || !num_height_layer) {
				continue;
			}
			
			const pos_layer = POSITION.getElementToDocument(elm_layer);
			
			let num_width_source = null;
			let num_height_source = null;
			let num_offset_x = pos_source.x-pos_layer.x;
			let num_offset_y = pos_source.y-pos_layer.y;
			
			if (elm_layer.matches('svg')) {
				
				const elm_layer_svg = elm_layer;
				
				// Try to get original source size to perform the necessary scaling
				num_width_source = num_width_view;
				num_height_source = num_height_view;
				if (elm_layer.getAttribute('width')) {
					num_width_layer = elm_layer.getAttribute('width');
					num_height_layer = elm_layer.getAttribute('height');
					num_width_source = num_width_layer;
					num_height_source = num_height_layer;
				} else if (elm_layer.dataset.width) {
					const num_resolution_layer = (elm_layer.dataset.resolution ? (elm_layer.dataset.resolution / 2.54) : 1); // CM or already pixel
					num_width_layer = (elm_layer.dataset.width * num_resolution_layer);
					num_height_layer = (elm_layer.dataset.height * num_resolution_layer);
					num_width_source = num_width_layer;
					num_height_source = num_height_layer;
				}

				const has_size = elm_layer_svg.hasAttribute('width');
				
				if (!has_size) {
					elm_layer_svg.setAttribute('width', num_width_layer);
					elm_layer_svg.setAttribute('height', num_height_layer);
				}
				
				const elm_parent = (elm_layer_svg.parentNode instanceof ShadowRoot ? elm_layer_svg.parentNode.host : elm_layer_svg.parentNode);
				const has_parent_opacity = (elm_parent.style.opacity != '');
				
				if (has_parent_opacity) {
					
					const num_opacity = elm_parent.style.opacity;
					
					if (num_opacity == 0) {
						continue;
					}
					
					elm_layer_svg.style.opacity = num_opacity;
				}
				
				const elm_img = document.createElement('img');
				const str_svg = new XMLSerializer().serializeToString(elm_layer_svg);
				
				if (!has_size) {
					elm_layer_svg.removeAttribute('width');
					elm_layer_svg.removeAttribute('height');
				}
				if (has_parent_opacity) {
					elm_layer_svg.style.opacity = '';
				}
				
				count_loading--;
				
				func_load_svg(str_svg, function(str) {
				
					const blob_svg = new Blob([str], {type: "image/svg+xml;charset=utf-8"});
					
					elm_img.onload = function() {
						count_loading++;
					};
					elm_img.src = (window.URL || window.webkitURL).createObjectURL(blob_svg);
				});
					
				elm_layer = elm_img;
			} else {
				
				// Try to get the target size to map to the correct size
				num_width_source = num_width;
				num_height_source = num_height;
				if (elm_layer.getAttribute('width')) {
					num_width_layer = elm_layer.getAttribute('width');
					num_height_layer = elm_layer.getAttribute('height');
					num_width_source = num_width_layer;
					num_height_source = num_height_layer;
				}
				
				num_offset_x *= num_resolution;
				num_offset_y *= num_resolution;
			}
			
			elm_layer = [elm_layer, num_offset_x, num_offset_y, num_width_source, num_height_source];
			
			arr_layers.push(elm_layer);
		}
	};
	
	this.setBackgroundLayer = function(elm_or_selector) {
		
		let elm_target = false;
		
		if (typeof elm_or_selector == 'string') {
			elm_target = getElementSelector(elm_source, elm_or_selector, true);
			elm_target = getElement(elm_target);
		} else if (elm_or_selector) {
			elm_target = getElement(elm_or_selector);
		}
		
		SELF.setBackgroundColor((elm_target ? elm_target.style.backgroundColor : false));
	}
	this.setBackgroundColor = function(str) {
		
		str_background_color = (str ? str : '');
	}
	
	var func_download = function() {
		
		let elm_canvas = $('<canvas width="'+num_width+'" height="'+num_height+'"></canvas>');
		elm_canvas = elm_canvas[0];
		let context = elm_canvas.getContext('2d');
		
		if (str_background_color) {
			context.fillStyle = str_background_color;
			context.fillRect(0, 0, num_width, num_height);
		}
		
		for (let i = 0, len = arr_layers.length; i < len; i++) {
			
			const arr_layer = arr_layers[i];
			
			//context.imageSmoothingEnabled = false;
			context.drawImage(arr_layer[0], arr_layer[1], arr_layer[2], arr_layer[3], arr_layer[4], 0, 0, num_width, num_height);
		}
		
		var elm_a = document.createElement('a');
		elm_a.download = arr_settings.name+'.png';
		elm_a.href = elm_canvas.toDataURL('image/png');
		elm_a.click();
	};
		
	this.download = function() {
				
		if (count_loading < 0) {
			
			let interval = setInterval(function() {
				
				if (count_loading < 0) {
					return;
				}
				
				clearInterval(interval);
				
				func_download();
			}, 10);
			
			return;
		}
		
		func_download();
	};
	
	var func_load_svg = function(str_svg, func_callback) {
								
		func_load_style_import(str_svg, function(str) {
			
			func_load_urls(str, function(str) {
				
				func_callback(str);
			});
		});
	};
	
	var func_load_style_import = function(str, func_callback) {
		
		const regex_replace = new RegExp('@import\\W+url\\([\'"]?(.*?)[\'"]?\\);', 'ig');
		const arr_match_urls = {};
		
		str.replaceAll(regex_replace, function(str_match, str_url) {
			
			arr_match_urls[str_url] = str_match;
		});
		
		const arr_fetch = Object.keys(arr_match_urls);
		
		if (!arr_fetch.length) {
			
			func_callback(str);
			return;
		}
		
		let str_new = str;
		
		ASSETS.getFiles(elm, arr_fetch, function(arr_files) {
			
			for (const str_url in arr_files) {
			
				const str_css = arr_files[str_url];
				
				str_new = str_new.replaceAll(arr_match_urls[str_url], str_css);
			}

			func_load_style_import(str_new, func_callback); // Recursive
		}, {}, 'text');		
	};
	
	var func_load_urls = function(str, func_callback) {
		
		const arr_match_urls = {};
					
		const regex_replace_style_url = new RegExp('src:\\W+url\\([\'"]?(.*?)[\'"]?\\)', 'ig');
		const regex_replace_image_url = new RegExp('href=[\'"](.*?)[\'"]', 'ig');
		
		str.replaceAll(regex_replace_style_url, function(str_match, str_url) {
			
			if (str_url.startsWith('data:')) { // Not a Blob/URL
				return;
			}
			
			arr_match_urls[str_url] = [str_match, str_url];
		});
		str.replaceAll(regex_replace_image_url, function(str_match, str_url) {
			
			if (str_url.startsWith('data:') || str_url.startsWith('#')) { // Not a Blob/URL
				return;
			}
			
			const str_url_unescaped = decodeHTMLSpecialChars(str_url); // XMLSerializer escaped some characters
			
			arr_match_urls[str_url_unescaped] = [str_match, str_url];
		});
		
		const arr_fetch = Object.keys(arr_match_urls);
		
		if (!arr_fetch.length) {
			
			func_callback(str);
			return;
		}
		
		let str_new = str;
		
		ASSETS.getFiles(elm, arr_fetch, function(arr_files) {
			
			let num_files = Object.keys(arr_files).length;
			
			if (!num_files) {
				func_callback(str_new);
			}
			
			for (const str_url in arr_files) {
				
				const str_file = arr_files[str_url];
				
				if (!str_file) {
					
					num_files--;
					
					if (!num_files) {
						func_callback(str_new);
					}
				}
				
				const file = new FileReader();
				file.onload = function(e) {
					
					const str_url_new = e.target.result;
					const str_url_raw = arr_match_urls[str_url][1];

					str_new = str_new.replaceAll(str_url_raw, str_url_new);
					
					num_files--;
					
					if (!num_files) {
						func_callback(str_new);
					}
				};
				file.readAsDataURL(str_file);
			}
		}, {}, 'blob');
	};
}
