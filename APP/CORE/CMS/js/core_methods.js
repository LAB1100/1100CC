
/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// x: commands fired from an elements containing method in classname and other info in parent element
// y: commands fired from elements containing both method and info
// f: commands fired from form elements
// d: commands fired from table elements

function Commands() {
	
	const SELF = this;
	
	this.getContext = function(elm) {
		
		var elm = $(elm);
		
		var elm_context = elm.closest('[id^="x:"], [id^="y:"], [id^="f:"], [id^="d:"]');
		
		if (elm_context.length) {
			
			return elm_context[0];
		}
		
		var elm_overlay = elm.closest('.overlay');
		
		if (elm_overlay.length) {
			
			elm_context = elm_overlay[0].context[0];
			return elm_context;
		}
		
		return false;
	};

	this.setData = function(elm, arr, overwrite) {
		
		return setElementData(elm, 'value', arr, overwrite);
	};
	this.getData = function(elm) {
		
		return getElementData(elm, 'value');
	};

	this.setOptions = function(elm, arr, extend) {
		
		return setElementData(elm, 'options', arr, !extend);
	};
	this.getOptions = function(elm) {
		
		return getElementData(elm, 'options');
	};

	this.setTarget = function(elm, value) {
		
		return setElementData(elm, 'target', value, true);
	};
	this.getTarget = function(elm) {
		
		return getElementData(elm, 'target');
	};
	
	this.setModule = function(elm) {
		
		return setElementData(elm, 'module', module, true);
	};
	this.getModule = function(elm) {
		
		var elm = getElement(elm);
		
		var module = getElementData(elm, 'module');
		
		if (!module) {
			var arr_match = elm.getAttribute('id').match(/([dfxy]):([^:]*):([^-]*)-(.*)/);
			module = arr_match[2];
		}
		
		return module;
	};
	
	this.setID = function(elm, identifier) {
		
		return setElementData(elm, 'command_id', identifier, true);
	};
	this.getID = function(elm, do_parse) {
		
		var elm = getElement(elm);
		
		var command_id = getElementData(elm, 'command_id');
		if (command_id === undefined || command_id === ''|| command_id === null) {
			command_id = false;
		}
		
		if (command_id === false && do_parse) {
			var arr_match = elm.getAttribute('id').match(/([dfxy]):([^:]*):([^-]*)-(.*)/);
			command_id = arr_match[4];
		}
		
		return command_id;
	};
	
	this.setAbort = function(elm, do_abort) {
				
		return setElementData(elm, 'abort', do_abort);
	};
	this.isAborted = function(elm) {
		
		return getElementData(elm, 'abort');
	};

	this.setMessage = function(elm, label) {
		
		return setElementData(elm, 'msg', label, true);
	};
	this.getMessage = function(elm) {
		
		return getElementData(elm, 'msg');
	};
	
	// COMMANDS
	
	this.popupCommand = function(elm, arr_options) {
  
		var arr_options = $.extend({
		}, arr_options || {});

		var cur = $(elm);
		
		SCRIPTER.triggerEvent(cur, 'command');
		
		var elm = cur.closest('[id^="x:"], [id^="y:"]');
		
		if (COMMANDS.isAborted(elm)) {
			return;
		}

		var match = elm.attr('id').match(/([xy]):([^:]*):([^-]*)-(.*)/);

		if (cur.data('method')) {
			var method = cur.data('method');
		} else if (match[1] == 'y') {
			var method = match[3];
		} else if (match[1] == 'x') {
			var method = cur.attr('class').split(' ').slice(-1)[0]; // Last class name
		}
		if (cur.data('module')) {
			var module = cur.data('module');
		} else {
			var module = match[2];
		}
		var command_id = (COMMANDS.getID(elm) !== false ? COMMANDS.getID(elm) : match[4]);
		var mod_id = getModID(elm);
		
		if (cur.is('input:not([type=submit], [type=button]), select, textarea')) {
			var value = (cur.is('input[type=checkbox], input[type=radio]') ? cur.filter(':checked').val() : cur.val());
			if (elm.data('value')) {
				value = $.extend({'value_element': value}, (typeof elm.data('value') === 'object' ? elm.data('value') : [elm.data('value')]));
			}
		} else {
			var value = elm.data('value');
		}
		if (!value) { // Allow for object manupulation further down the road
			value = {};
			elm.data('value', value);
		}
		
		var target = (elm.data('target') ? elm.data('target') : (elm.data('target') !== false ? 'module' : false));
		if (target instanceof Array) {
			var settings = [];
			for (var i in target) {
				settings[i] = $.extend({}, arr_options, (target[i].data('options') ? target[i].data('options') : {}));
			}
		} else {
			var settings = $.extend(arr_options, (elm.data('options') ? elm.data('options') : {}));
		}
						
		if (!FEEDBACK.start(cur)) {
			return;
		}
		
		var arr_request = SELF.prepare(cur, {mod: mod_id, method: method, id: command_id, module: module, value: value});
		
		FEEDBACK.request(cur, elm, {
			type: 'POST',
			contentType: arr_request.contentType,
			dataType: 'json',
			url: LOCATION.getURL('command'),
			data: arr_request.data,
			processData: false,
			context: cur,
			uploadProgress: function(event, position, total, percent) {
				
				//elm_popup.find('progress').removeClass('hide').attr('value', percent);
			},
			success: function(json) {
				
				FEEDBACK.check(cur, json, function() {
				
					var elm_popup = $('<div class="popup"></div>');
					
					if (!elm.closest('.mod').length) {
						settings.overlay = 'document';
					}
					if (settings.overlay == 'document') {
						var elm_overlay_target = document.body;
					} else {
						var elm_overlay_target = (settings.overlay ? settings.overlay : elm.closest('.mod'));
					}
					var obj_overlay = new Overlay(elm_overlay_target, elm_popup, {
						call_close: function() {
							FEEDBACK.stopContext(elm_popup);
						},
						elm_prevnext: (cur.attr('data-method') ? cur : {}),
						sizing: (settings.overlay_settings ? settings.overlay_settings.sizing : undefined)
					});
					
					var elm_overlay = obj_overlay.getOverlay();
					
					elm_overlay.context = elm;
					if (settings.overlay == 'document') {
						setElementData(elm_overlay, 'mod', mod_id);
					}
				
					var call_popup = function(json) {
						
						var elm_html = $(json.html);
						let arr_rules = (json.validate ? json.validate : {});
						var name_submit = false;
						
						elm_popup.html(elm_html);
						
						var elm_form = elm_popup.children('form').last();
						
						if (elm_form.length) {
							
							var elm_menu = $('<menu></menu>').appendTo(elm_popup);
							var elm_buttons = elm_html.children('*[type=button], *[type=submit]');
							if (elm_buttons.length) {
								elm_buttons.appendTo(elm_menu);
							}
							
							if (!elm_buttons.filter('[type=submit][name=""], [type=submit]:not([name]), [type=submit].save').length) {
								$('<input type="submit" value="Save" />').appendTo(elm_menu);
							}
							
							setElementData(elm_form, 'rules', arr_rules);
							
							elm_menu.children('*[type=submit]').on('click', function() {
								
								name_submit = (this.getAttribute('name') ? this.getAttribute('name') : false);
								
								arr_rules = getElementData(elm_form, 'rules'); // The form's rules could have been updated
								
								if (name_submit != 'do_discard') {
			
									if (!elm_form.data('validator')) {
											
										elm_form.validate({
											rules: arr_rules,
											submitHandler: function() {
												
												this.resetForm();
												call_submit();
											}
										});
									} else {
									
										$.extend(elm_form.validate().settings, {rules: arr_rules});
									}
									
									SCRIPTER.triggerEvent(elm_form, 'submit');
								} else {
									
									if (name_submit == 'do_discard') {
										FEEDBACK.stopContext(elm_popup);
									}
									
									call_submit();
								}
							});
						}
						
						var elm_location = elm_popup.children('[data-location]').first();
						
						if (elm_location.length) {
							obj_overlay.location(elm_location[0].dataset.location);
							delete elm_location[0].dataset.location;
						}
						
						SCRIPTER.runDynamic(elm_html);
						SCRIPTER.triggerEvent(elm, 'ajaxloaded', {elm: elm_html});
						
						if (elm_form.length) {
							
							SCRIPTER.triggerEvent(cur, 'commandintermediate', {elm: elm_popup});
							
							var call_submit = function() {
								
								SCRIPTER.triggerEvent(elm_form, 'ajaxsubmithandler');
								
								var new_method = (elm_form.data('method') ? elm_form.data('method') : elm_form.attr('class').split(' ').slice(-1)[0]); // Last class name
								
								if (!FEEDBACK.start(elm_popup)) {
									return;
								}
								
								SCRIPTER.triggerEvent(elm_form, 'ajaxsubmit');
								
								var arr_request = {mod: mod_id, method: new_method, id: command_id, module: module, value: value};
								
								if (name_submit) {
									
									if (name_submit == 'do_discard') {
										arr_request.is_discard = 1;
									} else {
										arr_request[name_submit] = 1;
									}
								}
								
								arr_request = SELF.prepare(elm_form, arr_request);
								var call = false;
								
								FEEDBACK.request(elm_popup, elm_popup, {
									type: 'POST',
									contentType: arr_request.contentType,
									dataType: 'json',
									url: LOCATION.getURL('command'),
									data: arr_request.data,
									processData: false,
									context: elm_popup,
									beforeSend: function(xhr, settings) {
										call = settings;
									},
									uploadProgress: function(event, position, total, percent) {
										elm_popup.find('progress').removeClass('hide').attr('value', percent);
									},
									error: function() {
										SCRIPTER.triggerEvent(elm_form, 'ajaxsubmitted');
									},
									success: function(json) {

										SCRIPTER.triggerEvent(elm_form, 'ajaxsubmitted');
										
										FEEDBACK.check(elm_popup, json, function() {
											
											var elm_html = false;
											try {
												elm_html = $(json.html);
											} catch(e) { }
											
											if (elm_html && elm_html.is('form:not([id^=f\\\:])')) {
												
												call_popup(json);
											} else {
												
												SELF.parse(json, target, elm, settings, call, function() {
													
													LOCATION.unlock(elm_popup[0]);
													obj_overlay.close();
													SCRIPTER.triggerEvent(cur, 'commandfinished');
												});
											}
										});
									}											
								});
							}
						} else {
							
							SCRIPTER.triggerEvent(cur, 'commandfinished');
						}
					};
					
					call_popup(json);
				});
			}
		});
	};
	
	this.messageCommand = function(elm, arr_options) {
		
		var arr_options = $.extend({
			remove: false
		}, arr_options || {});
		
		var cur = $(elm);

		SCRIPTER.triggerEvent(cur, 'command');

		var elm = cur.closest('[id^="x:"], [id^="y:"]');
		
		if (COMMANDS.isAborted(elm)) {
			return;
		}

		var match = elm.attr('id').match(/([xy]):([^:]*):([^-]*)-(.*)/);

		if (cur.data('method')) {
			var method = cur.data('method');
		} else if (match[1] == 'y') {
			var method = match[3];
		} else if (match[1] == 'x') {
			var method = cur.attr('class').split(' ').slice(-1)[0]; // Last class name
		}
		if (cur.data('module')) {
			var module = cur.data('module');
		} else {
			var module = match[2];
		}
		var command_id = (COMMANDS.getID(elm) !== false ? COMMANDS.getID(elm) : match[4]);
		var mod_id = getModID(elm);
		
		if (cur.is('input:not([type=submit], [type=button]), select, textarea')) {
			var value = (cur.is('input[type=checkbox], input[type=radio]') ? cur.filter(':checked').val() : cur.val());
			if (elm.data('value')) {
				value = $.extend({'value_element': value}, (typeof elm.data('value') === 'object' ? elm.data('value') : [elm.data('value')]));
			}
		} else {
			var value = elm.data('value');
		}
		var target = (elm.data('target') ? elm.data('target') : (elm.data('target') !== false ? 'module' : false));
		if (target instanceof Array) {
			var settings = [];
			for (var i in target) {
				settings[i] = $.extend({}, arr_options, (target[i].data('options') ? target[i].data('options') : {}));
			}
		} else {
			var settings = $.extend(arr_options, (elm.data('options') ? elm.data('options') : {}));
		}
		var msg = (COMMANDS.getMessage(cur) ? COMMANDS.getMessage(cur) : 'conf_general');
		
		if (!FEEDBACK.start(cur)) {
			return;
		}
		
		var arr_request = SELF.prepare(cur, {mod: mod_id, module: 'cms_general', method: 'get_label', id: msg});

		FEEDBACK.request(cur, elm, {
			type: 'POST',
			contentType: arr_request.contentType,
			dataType: 'json',
			url: LOCATION.getURL('command'),
			data: arr_request.data,
			processData: false,
			context: cur,
			success: function(json) {
				
				FEEDBACK.check(cur, json, function() {
					
					var elm_html = false;
					try {
						elm_html = $(json.html);
					} catch (e) {};
					
					if (!elm.closest('.mod').length) {
						settings.overlay = 'document';
					}
					if (settings.overlay == 'document') {
						var elm_overlay_target = document.body;
					} else {
						var elm_overlay_target = (settings.overlay ? settings.overlay : elm.closest('.mod'));
					}
					
					var elm_popup = $('<div class="popup message"><span class="icon"></span><div>'+(elm_html ? elm_html : json.html)+'</div></div>');
					
					ASSETS.getIcons(cur, ['attention'], function(data) {
						elm_popup[0].children[0].innerHTML = data.attention;
					});

					var elm_menu = $('<menu></menu>').appendTo(elm_popup);
					
					$('<input type="button" value="Ok" />').appendTo(elm_menu).on('click', function() {
						
						if (!FEEDBACK.start(elm_popup)) {
							return;
						}

						var arr_request = SELF.prepare(false, {mod: mod_id, method: method, id: command_id, module: module, value: value});
						var call = false;
						
						FEEDBACK.request(elm_popup, elm_popup, {
							type: 'POST',
							contentType: arr_request.contentType,
							dataType: 'json',
							url: LOCATION.getURL('command'),
							data: arr_request.data,
							processData: false,
							context: elm_popup,
							beforeSend: function(xhr, settings) {
								call = settings;
							},
							success: function(json) {
								FEEDBACK.check(elm_popup, json, function() {
									
									SELF.parse(json, target, elm, settings, call, function() {
										
										obj_overlay.close();
										SCRIPTER.triggerEvent(cur, 'commandfinished');
									});
								});
							}
						});
					});
					$('<input type="button" value="Cancel" />').appendTo(elm_menu).on('click', function() {
						obj_overlay.close();
					});
					
					var obj_overlay = new Overlay(elm_overlay_target, elm_popup, {
						position: 'middle',
						call_close: function() {
							FEEDBACK.stop(elm_popup);
						},
						button_close: false,
						sizing: (settings.overlay_settings ? settings.overlay_settings.sizing : undefined)
					});
					
					var elm_overlay = obj_overlay.getOverlay();
					elm_overlay.context = elm;
					
					if (elm_html) {
						SCRIPTER.runDynamic(elm_html);
					}
					SCRIPTER.triggerEvent(elm, 'ajaxloaded', {elm: elm_html});
					SCRIPTER.triggerEvent(cur, 'commandintermediate', {elm: elm_popup});
				});
			}
		});
	};
	
	this.quickCommand = function(elm, target, arr_options) {
	
		var arr_options = $.extend({
		}, arr_options || {});
		
		var cur = $(elm);

		SCRIPTER.triggerEvent(cur, 'command');
		
		var elm = cur.closest('[id^="x:"], [id^="y:"]');
		
		if (COMMANDS.isAborted(elm)) {
			return;
		}

		var match = elm.attr('id').match(/([xy]):([^:]*):([^-]*)-(.*)/);

		if (cur.data('method')) {
			var method = cur.data('method');
		} else if (match[1] == 'y') {
			var method = match[3];
		} else if (match[1] == 'x') {
			var method = cur.attr('class').split(' ').slice(-1)[0]; // Last class name
		}
		if (cur.data('module')) {
			var module = cur.data('module');
		} else {
			var module = match[2];
		}
		var command_id = (COMMANDS.getID(elm) !== false ? COMMANDS.getID(elm) : match[4]);
		
		if (cur.is('input:not([type=submit], [type=button]), select, textarea')) {
			var value = (cur.is('input[type=checkbox], input[type=radio]') ? cur.filter(':checked').val() : cur.val());
			if (elm.data('value')) {
				value = $.extend({'value_element': value}, (typeof elm.data('value') === 'object' ? elm.data('value') : [elm.data('value')]));
			}
		} else {
			var value = elm.data('value');
		}
		var target = (target ? target : (target !== false ? (elm.data('target') ? elm.data('target') : (elm.data('target') !== false ? 'module' : false)) : false));
		if (target instanceof Array) {
			var settings = [];
			for (var i in target) {
				settings[i] = $.extend({}, arr_options, (target[i].data('options') ? target[i].data('options') : {}));
			}
		} else {
			var settings = $.extend(arr_options, (elm.data('options') ? elm.data('options') : {}));
		}

		if (!FEEDBACK.start(cur)) {
			return;
		}

		var arr_request = SELF.prepare(cur, {mod: getModID(elm), method: method, id: command_id, module: module, value: value});
		var call = false;
		
		FEEDBACK.request(cur, elm, {
			type: 'POST',
			contentType: arr_request.contentType,
			dataType: 'json',
			url: LOCATION.getURL('command'),
			data: arr_request.data,
			processData: false,
			context: cur,
			beforeSend: function(xhr, settings) {
				call = settings;
			},
			uploadProgress: function(event, position, total, percent) {
				
				//elm_popup.find('progress').removeClass('hide').attr('value', percent);
			},
			success: function(json) {
				FEEDBACK.check(cur, json, function() {
					
					SELF.parse(json, target, elm, settings, call, function() {
						
						SCRIPTER.triggerEvent(cur, 'commandfinished');
					});
				});
			}
		});
	};
	
	this.formCommand = function(elm, arr_options, e) {
	
		var arr_options = $.extend({
			html: 'replace'
		}, arr_options || {});
		
		var cur = $(elm);
		
		SCRIPTER.triggerEvent(cur, 'command');
		
		var elm = cur.closest('[id^="f:"]');
			
		if (COMMANDS.isAborted(elm)) {
			
			if (e) {
				e.preventDefault();
			}
			
			return;
		}
		
		var match = elm.attr('id').match(/f:([^:]*):([^-]*)-(.*)/);
		
		if (cur.data('method')) {
			var method = cur.data('method');
		} else {
			var method = match[2];
		}
		if (cur.data('module')) {
			var module = cur.data('module');
		} else {
			var module = match[1];
		}
		var command_id = match[3];
		
		var target = (elm.data('target') ? elm.data('target') : (elm.data('target') !== false ? elm : false));
		if (target instanceof Array) {
			var settings = [];
			for (var i in target) {
				settings[i] = $.extend({}, arr_options, (target[i].data('options') ? target[i].data('options') : {}));
			}
		} else {
			var settings = $.extend(arr_options, (elm.data('options') ? elm.data('options') : {}));
		}
		var value = elm.data('value');
		
		var name_submit = (e && $(e.target).is(':submit') && e.target.getAttribute('name') ? e.target.getAttribute('name') : false);

		var call_submit = function() {

			SCRIPTER.triggerEvent(elm, 'ajaxsubmithandler');
			
			if (!FEEDBACK.start(cur)) {
				return;
			}

			SCRIPTER.triggerEvent(elm, 'ajaxsubmit');
			
			if (name_submit == 'do_discard') {
				elm.find('input, select, textarea').prop('disabled', true);
			}
			
			var arr_request = {mod: getModID(elm), method: method, id: command_id, module: module, value: value};
			
			if (name_submit) {
				
				if (name_submit == 'do_discard') {
					arr_request.is_discard = 1;
				} else {
					arr_request[name_submit] = 1;
				}
			}
			
			arr_request = SELF.prepare(elm, arr_request);
			var call = false;
			
			FEEDBACK.request(cur, elm, {
				type: 'POST',
				contentType: arr_request.contentType,
				dataType: 'json',
				url: LOCATION.getURL('command'),
				data: arr_request.data,
				processData: false,
				context: cur,
				beforeSend: function(xhr, settings) {
					call = settings;
				},
				uploadProgress: function(event, position, total, percent) {
					elm.find('progress').removeClass('hide').attr('value', percent);
				},
				error: function() {
					SCRIPTER.triggerEvent(elm, 'ajaxsubmitted');
				},
				success: function(json) {
					
					SCRIPTER.triggerEvent(elm, 'ajaxsubmitted');
					
					FEEDBACK.check(cur, json, function() {
						
						var elm_parent = elm.parent();
						
						SELF.parse(json, target, elm, settings, call, function() {
						
							if (!onStage(cur[0])) {
	
								if (cur[0] != elm[0] && onStage(elm[0])) {
									var elm_trigger = elm;
								} else if (onStage(elm_parent[0])) {
									var elm_trigger = elm_parent;
								} else {
									var elm_trigger = document;
								}
								SCRIPTER.triggerEvent(elm_trigger, 'closed', {elm: elm});
							}
							
							SCRIPTER.triggerEvent(cur, 'commandfinished');
						});
					});
				}
			});
		};

		if (name_submit != 'do_discard') {
			
			let arr_rules = getElementData(elm, 'rules'); // Always check, the form's rules could have been updated
			arr_rules = (arr_rules ? arr_rules : {});
			
			if (!elm.data('validator')) {
					
				elm.validate({
					rules: arr_rules,
					submitHandler: function(elm) {
						
						this.resetForm();
						call_submit();
					}
				});
			} else {
			
				$.extend(elm.validate().settings, {rules: arr_rules});
			}
			
			if (!e) {
				SCRIPTER.triggerEvent(elm, 'submit');
			}
		} else {
			
			if (e) {
				e.preventDefault();
			}
			
			FEEDBACK.stopContext(elm);
									
			call_submit();
		}
	};
		
	this.dataTableContinue = function(elm, state) {
		
		var elm = getElement(elm);
		
		if (state) {
			
			elm.dataset.pause = 1;
			return;
		}
		
		if (elm.dataset.pause == 1 && !state) {
			
			elm.dataset.pause = 0;
			
			if (elm.datatable) {
				
				elm.datatable.reload();
			}
		}
	};
	this.dataTableRefresh = function(elm) {
		
		var elm = getElement(elm);
		
		elm.datatable.refresh();
	};
	this.dataTableReload = function(elm) {
		
		var elm = getElement(elm);
		
		elm.datatable.reload();
	};
	
	// HELPERS
	
	this.prepare = function(elm, arr_request) {
		
		var elm = $(elm);
		
		const is_form = (elm && elm.is('form') ? true : false);
		const has_object = (typeof arr_request.value === 'object' && arr_request.value !== null);
		const has_form = (has_object && arr_request.value.forms);
		
		let contentType = null;
		let data = null;
			
		if (is_form || has_form) {
			
			let form_collect = null;
					
			if (is_form) {
				
				const elms_ignore = [];
				
				runElementSelectorFunction(elm[0], '[type=submit][name]:enabled', function(elm_found) { // Make sure FormData procedures really disregard buttons (i.e. polyfill FormData issue)
					elms_ignore.push(elm_found);
				});
				runElementSelectorFunction(elm[0], 'input[type=file]:not([disabled])', function(elm_found) { // A Safari 11.1/webkit bug on empty file inputs
					if (elm_found.files.length > 0) {
						return;
					}
					elms_ignore.push(elm_found);
				});

				for (let i = 0, len = elms_ignore.length; i < len; i++) {
					elms_ignore[i].setAttribute('disabled', '');
				}
					
				form_collect = new FormData(elm[0]);
				
				for (let i = 0, len = elms_ignore.length; i < len; i++) {
					elms_ignore[i].removeAttribute('disabled');
				}
				
				if (FEEDBACK.getRequestElementName('check') !== 'check') { // When the request wants to change the naming, update existing form element names
					
					const form_append = form_collect;
					form_collect = new FormData();
					
					for (const arr_entry of form_append.entries()) {
						
						const str_name = arr_entry[0];
						
						if (arr_entry[2]) {
							form_collect.append(FEEDBACK.getRequestElementName(str_name), arr_entry[1], arr_entry[2]);
						} else {
							form_collect.append(FEEDBACK.getRequestElementName(str_name), arr_entry[1]);
						}
					}
				}
			} else {
				
				form_collect = new FormData();
			}
			
			if (has_form) {
					
				for (let i = 0, len = arr_request.value.forms.length; i < len; i++) {
					
					const elm_form_append = $(arr_request.value.forms[i]);
					
					const elms_ignore = [];
			
					runElementSelectorFunction(elm_form_append[0], '[type=submit][name]:enabled', function(elm_found) { // Make sure FormData procedures really disregard buttons (i.e. polyfill FormData issue)
						elms_ignore.push(elm_found);
					});
					runElementSelectorFunction(elm_form_append[0], 'input[type=file]:not([disabled])', function(elm_found) { // A Safari 11.1/webkit bug on empty file inputs
						if (elm_found.files.length > 0) {
							return;
						}
						elms_ignore.push(elm_found);
					});
					
					for (let j = 0, len_j = elms_ignore.length; j < len_j; j++) {
						elms_ignore[j].setAttribute('disabled', '');
					}
					
					const form_append = new FormData(elm_form_append[0]);
					
					for (let j = 0, len_j = elms_ignore.length; j < len_j; j++) {
						elms_ignore[j].removeAttribute('disabled');
					}
					
					for (const arr_entry of form_append.entries()) {
						
						const str_name = arr_entry[0];
						
						if (arr_entry[2]) {
							form_collect.append(FEEDBACK.getRequestElementName(str_name), arr_entry[1], arr_entry[2]);
						} else {
							form_collect.append(FEEDBACK.getRequestElementName(str_name), arr_entry[1]);
						}
					}
				}
			}
			
			if (has_object) {
				
				const arr_value = {};
				
				for (const key in arr_request.value) {
					
					if (key == 'forms') {
						continue;
					}
					
					arr_value[key] = arr_request.value[key];
				}
				
				arr_request.json = {value: arr_value};
			} else {
				
				arr_request.json = {value: arr_request.value};
			}
					
			for (const key in arr_request) {
				
				if (key == 'value' || key == 'json') {
					continue;
				}
				
				if (typeof arr_request[key] === 'object') {
					arr_request.json[key] = arr_request[key];
				} else {
					form_collect.append(FEEDBACK.getRequestElementName(key), arr_request[key]);
				}
			}
			
			form_collect.append(FEEDBACK.getRequestElementName('json'), JSON.stringify(arr_request.json)); // Finally append the json package
			
			contentType = FEEDBACK.CONTENT_TYPE_FORM;
			data = form_collect;
		} else {
			
			contentType = FEEDBACK.CONTENT_TYPE_JSON;
			data = arr_request;
		}
		
		return {contentType: contentType, data: data};
	};
	
	this.parse = function(json, target, elm, arr_settings, call, callback) {
		
		if (arr_settings.elm_container) {
			var elm_container = arr_settings.elm_container;
		} else {
			var elm_container = getContainer(elm); // Originating element container
		}
		
		if (call.is_discard) { // Cancel call to new parse from old parse
			
			delete call.is_discard;
			return false;
		}
		
		if (json.do_confirm) {
			
			if (!elm.closest('.mod').length) {
				arr_settings.overlay = 'document';
			}
			if (arr_settings.overlay == 'document') {
				var elm_overlay_target = document.body;
			} else {
				var elm_overlay_target = (arr_settings.overlay ? arr_settings.overlay : elm.closest('.mod'));
			}

			var elm_popup = $('<div class="popup confirm"><span class="icon"></span><div>'+json.html+'</div></div>');
			
			ASSETS.getIcons(elm, ['attention'], function(data) {
				elm_popup[0].children[0].innerHTML = data.attention;
			});

			var elm_menu = $('<menu></menu>').appendTo(elm_popup);
			
			const func_confirm = function(is_confirm) {
				
				if (!FEEDBACK.start(call.context)) {
					return;
				}
				
				try {
					var arr_data = JSON.parse(call.data);
				} catch (e) {};
				
				if (arr_data) { // JSON
					
					arr_data.is_confirm = is_confirm;
					arr_data.is_discard = !is_confirm;
					
					if (typeof json.do_confirm === 'object') {
						
						$.extend(arr_data, json.do_confirm);
					}
					
					call.data = JSON.stringify(arr_data);
				} else { // FormData
					
					call.data.append('is_confirm', (is_confirm ? 1 : 0));
					call.data.append('is_discard', (is_confirm ? 0 : 1));
					
					if (typeof json.do_confirm === 'object') {
						
						for (const key in json.do_confirm) {
							
							call.data.delete(key);
							
							const value = json.do_confirm[key];
							
							if (typeof value === 'object') {
								call.data.append('json['+key+']', JSON.stringify(value));
							} else {
								call.data.append(key, value);
							}
						}
					}
				}
				
				call.is_discard = !is_confirm;

				$.ajax(call);
			};
			
			$('<input type="button" value="Ok" />').appendTo(elm_menu).on('click', function() {
				
				func_confirm(true);
				
				obj_overlay.close();
			});
			$('<input type="button" value="Cancel" />').appendTo(elm_menu).on('click', function() {
				
				FEEDBACK.stopContext(call.context);
				
				func_confirm(false);
				
				obj_overlay.close();
			});
			
			var obj_overlay = new Overlay(elm_overlay_target, elm_popup, {
				position: 'middle',
				button_close: false,
				sizing: (arr_settings.overlay_settings ? arr_settings.overlay_settings.sizing : undefined)
			});
			
			var elm_overlay = obj_overlay.getOverlay();
			
			if (arr_settings.overlay == 'document') {
				setElementData(elm_overlay, 'mod', getModID(elm));
			}
				
			return false;
		} else if (json.do_download) {
			
			const elm_form = $('<form action="'+call.url+'" method="post"></form>');
			
			const elm_download = $('<input type="hidden" name="is_download" value="1" />');			
			
			elm_form.append(elm_download);
			
			try {
				var arr_data = JSON.parse(call.data);
			} catch (e) {};
			
			if (arr_data) { // JSON
				
				if (typeof json.do_download === 'object') {
						
					$.extend(arr_data, json.do_download);
					
					call.data = JSON.stringify(arr_data);
				}
				
				const elm_new = $('<input type="hidden" name="json" value="" />');
				elm_new.val(call.data);
				
				elm_form.append(elm_new);
			} else { // FormData
				
				const form_collect = new FormData(elm_form[0]);

				if (typeof json.do_download === 'object') {
					
					for (const key in json.do_download) {
						
						call.data.delete(key);
						
						const value = json.do_download[key];
						
						if (typeof value === 'object') {
							
							// Append to JSON in FormData
							
							let arr_json = {};
							if (call.data.has('json')) {
								arr_json = JSON.parse(call.data.get('json'));
							}
							arr_json[key] = value;
							
							call.data.append('json', JSON.stringify(arr_json));
						} else {
							call.data.append(key, value);
						}
					}
				}

				for (const arr_entry of call.data.entries()) {

					let elm_new = null;
					
					if (arr_entry[2]) { // File
						
						elm_new = $('<input type="file" name="'+arr_entry[0]+'" value="" />');
						elm_new[0].files(arr_entry[1]);
						elm_new.val(arr_entry[2]);
					} else {
						
						elm_new = $('<input type="hidden" name="'+arr_entry[0]+'" value="" />');
						elm_new.val(arr_entry[1]);
					}
					
					elm_form.append(elm_new);
				}
			}
					
			var elm_iframe = $('<iframe src="about:blank"></iframe>')
				.hide()
				.appendTo('body');
				
			var interval = setInterval(function() {
				
				if (elm_iframe.contents()[0].readyState != 'complete') {
					return;
				}
				clearInterval(interval);
				
				SCRIPTER.triggerEvent(elm_form.appendTo(elm_iframe.contents().find('body')), 'submit', false, {do_native: true});
				
				elm_iframe.on('load', function() { // Only tiggers when regular content is returned (i.e. not when a file download is presented)
					
					try {
						var json = JSON.parse(elm_iframe.contents().find('textarea').val());
					} catch(e) {}
					
					elm_iframe.remove();
					
					if (json) {
						
						FEEDBACK.check(call.context, json, function() {
							
							if (callback) {
								callback();
							}
						});
					}
				});
			}, 20);
			
			return false;
		} else if ((json.html || json.html === "") && target) {
			
			var elm_result = false;
			
			if (typeof target === 'function') {
				
				var html = json.html;
				var elm_html = false;
				
				if (html && typeof html === 'string' && html.indexOf('<') >= 0 && html.indexOf('>') >= 1) {
					
					try {
						elm_html = $(html).filter('*'); // Only keep elements, not text nodes
					} catch (e) {};
					
					if (!elm_html || !elm_html.length) { // If no nodes found, return to text
						elm_html = false;
					}
				}
				
				if (json.validate && elm_html) {
					setElementData(elm_html, 'rules', json.validate, true);
				}
				
				elm_result = target.apply(elm[0], [(elm_html ? elm_html : html)]);
				if (!elm_result) {
					elm_result = elm_html;
				}
				
				if (getElement(elm_result)) { // Could also be raw data

					if (json.validate) {
						
						var elm_check_form = getElement(elm_result);
						
						if (elm_check_form.matches('form')) {
							if (elm_check_form == getElement(elm_html)) { // Already applied
								elm_check_form = false;
							}
						} else {
							elm_check_form = elm_check_form.closest('form');
						}
						
						if (elm_check_form) {
							setElementData(elm_check_form, 'rules', json.validate);
						}
					}
				}
			} else {
				
				const func_process_target = function(arr_target_use, arr_settings_use, html_use, arr_validate_use) {
					
					const elm_target = $(arr_target_use);
				
					if (elm_target.is("input:not(:button, :submit), textarea")) {
						
						elm_target.val(html_use);
						SCRIPTER.triggerEvent(elm_target, 'change');
						
						return elm_target;
					} else if (elm_target.is("select") && !arr_settings_use.html) {
											
						elm_target.html(html_use);
						SCRIPTER.triggerEvent(elm_target, 'change');
						
						return elm_target;
					} else {
						
						const elm_html = $(html_use).filter('*'); // Only keep elements, not text nodes

						if (arr_target_use == 'module') {
							
							if (IS_CMS) {
								$(($('[id^=mod-] .tabs > div:visible').length ? '[id^=mod-] .tabs > div:visible' : '[id^=mod-]')).html(elm_html);
							} else {
								elm.closest('.mod').html(elm_html);
							}
						} else {
							if (arr_settings_use.style == 'fade') {
								elm_html.hide();
							}
							if (arr_settings_use.html == 'replace') {
								elm_target.replaceWith(elm_html);
							} else if (arr_settings_use.html == 'prepend') {
								elm_target.prepend(elm_html);
							} else if (arr_settings_use.html == 'append') {
								elm_target.append(elm_html);
							} else if (arr_settings_use.html == 'before') {
								elm_target.before(elm_html);
							} else if (arr_settings_use.html == 'after') {
								elm_target.after(elm_html);
							} else {
								elm_target.html(elm_html);
							}
							if (arr_settings_use.style == 'fade') {
								elm_html.fadeIn();
							}
						}
						
						if (elm_html.length) {
								
							if (arr_validate_use) {
								
								setElementData(elm_html, 'rules', arr_validate_use, true);
								
								const elm_form = elm_html.closest('form');
								
								if (elm_form.length) {
									setElementData(elm_form, 'rules', arr_validate_use);
								}
							}
							
							return elm_html;
						}
					}
				};
				
				if (target instanceof Array && json.html instanceof Array) {
					
					elm_result = $();
				
					for (let i = 0; i < target.length; i++) {
						
						const elm_target = func_process_target(target[i], arr_settings[i], json.html[i], (json.validate && json.validate[i] ? json.validate[i] : null));
						
						if (elm_target) {
							elm_result = elm_result.add(elm_target);
						}
					}
				} else {
					
					const elm_target = func_process_target(target, arr_settings, json.html, json.validate);
						
					if (elm_target) {
						elm_result = elm_target;
					}
				}
			}
			
			if (!elm_result) {
				elm_result = $();
			}
			
			if (onStage(elm[0])) {
				
				SCRIPTER.runDynamic(elm_result); // Applied to first element
				SCRIPTER.triggerEvent(elm, 'ajaxloaded', {elm: elm_result});
			} else if (elm_container[0] && onStage(elm_container[0])) {
				
				SCRIPTER.runDynamic(elm_result); // Applied to first element
				SCRIPTER.triggerEvent(elm_container, 'ajaxloaded', {elm: elm_result});
			} else {
				
				SCRIPTER.triggerEvent(document, 'ajaxloaded', {elm: elm_result});
			}
		} else if (typeof target === "function") {
			
			target.apply(elm[0]);
		}
		if (arr_settings.remove && target != false) {
			
			elm.fadeOut(function() {
				$(this).remove();
			});
		}
		if (arr_settings.hide && target != false) {
			
			elm.hide();
		}
		if (json.refresh_table) {

			var elms_datatable = elm_container.find('table[id^="d:"]').filter(function () {
				return !hasElement(call.context[0], this); // Do not target tables inside method's context (e.g. popup)
			});
			
			elms_datatable.each(function() {
				
				if (!this.datatable) {
					return;
				}
				
				this.datatable.refresh();
			});
		}
		if (json.reset_form) {
			
			if (elm.is('form')) {
				SCRIPTER.triggerEvent(elm, 'reset', false, {do_native: true});
			} else {
				elm.val('');
			}

			LOCATION.updateLocked(elm[0]);
		}
		
		if (callback) {
			callback();
		}
		
		return true;
	}
	
	// EXTENSIONS
	
	this.Cacher = function(elm) {
		
		const PARENT = SELF;
		const SELF_CACHER = this;
		
		var elm = getElement(elm);
		
		elm.command_cacher = this;
		
		var do_preload = false;
		var arr_preload_call = [];
		var callback_preload = false;
		
		this.obj_cache = {};
				
		this.preload = function(arr_call, callback) {
			
			arr_preload_call = arr_call;
			SELF_CACHER.setPreloadCallback(callback);
			
			do_preload = true;
			
			preloadNext(true);
		};
		
		this.setPreloadCallback = function(callback) {
			
			callback_preload = (callback ? callback : false);
		};
		
		var preloadNext = function(start) {
					
			if (arr_preload_call.length && !start) {
				
				if (arr_preload_call[0][1]) {
					
					var identifier = elm.getAttribute('data-cache');
					arr_preload_call[0][1](SELF_CACHER.obj_cache[identifier]); // Callback
				}
				arr_preload_call.shift();
			}
			
			if (arr_preload_call.length) {
				
				arr_preload_call[0][0](); // Launch
			} else {
				
				do_preload = false;
				
				if (callback_preload) {
					callback_preload();
				}
			}
		};
		
		this.run = function(command, func, callback_preloading) {
			
			var identifier = elm.getAttribute('data-cache');
			
			if (!SELF_CACHER.obj_cache[identifier]) {
				
				PARENT.setTarget(elm, function(data) {
					
					SELF_CACHER.obj_cache[identifier] = data;
					
					if (do_preload) {
						
						preloadNext();
						
						if (callback_preloading) {
							callback_preloading();
						}
					} else {
						
						func.call(elm, data);
					}
				});

				if (command == 'quick') {
					SELF_CACHER.quickCommand(elm);
				} else if (command == 'popup') {
					SELF_CACHER.popupCommand(elm);
				} else if (command == 'message') {
					SELF_CACHER.messageCommand(elm);
				} else if (command == 'form') {
					SELF_CACHER.formCommand(elm);
				}
			} else {
			
				if (!do_preload) {
					func.call(elm, SELF_CACHER.obj_cache[identifier]);
				}
			}
		};	
	}

	this.checkCacher = function(elm, command, func, callback_preloading) {
		
		var elm = getElement(elm);
		
		var obj_cacher = elm.command_cacher;
		
		if (!obj_cacher) {
			
			if (elm.hasAttribute('data-cache')) {
				
				obj_cacher = new SELF.Cacher(elm);
			} else {
				
				SELF.setTarget(elm, func);

				if (command == 'quick') {
					SELF.quickCommand(elm);
				} else if (command == 'popup') {
					SELF.popupCommand(elm);
				} else if (command == 'message') {
					SELF.messageCommand(elm);
				} else if (command == 'form') {
					SELF.formCommand(elm);
				}
				
				return;
			}
		}
		
		obj_cacher.run(command, func, callback_preloading);
	};
}
var COMMANDS = new Commands();

function DataTable(elm, arr_options) {
	
	const SELF = this;
	
	var arr_options = $.extend({
		paginate: 6,
		paginate_middle: 3
	}, arr_options);
							
	var cur = $(elm);
	
	if (cur[0].datatable) {
		return;
	}
	cur[0].datatable = this;

	SCRIPTER.triggerEvent(cur, 'command');
	
	if (COMMANDS.isAborted(cur)) {
		return;
	}
	
	var match = cur.attr('id').match(/d:([^:]*):([^-]*)-(.*)/);
	
	if (cur.data('method')) {
		var method = cur.data('method');
	} else {
		var method = match[2];
	}
	if (cur.data('module')) {
		var module = cur.data('module');
	} else {
		var module = match[1];
	}
	var command_id = match[3];
	
	var has_search = (cur.data('search') != undefined ? cur.data('search') : true);
	var value_search = (cur.data('search_settings') ? cur.data('search_settings') : '');
	var command_filter = cur.data('filter');
	var settings_filter = cur.data('filter_settings');
	var command_order = cur.data('order');
	
	var value_filter = COMMANDS.getData(cur);
	if (!value_filter) {
		value_filter = {};
		COMMANDS.setData(cur, value_filter);
	}
	if (settings_filter && typeof value_filter === 'object') {
		$.extend(value_filter, settings_filter);
	}
	
	var delay = (cur.data('delay') ? cur.data('delay') : false);
	
	// Build
					
	var elm_container = $('<div class="datatable"></div>').insertBefore(cur);
	
	var elm_options = $('<div class="options"></div>').appendTo(elm_container);
	var elm_options_left = $('<div></div>').appendTo(elm_options);
	var elm_options_right = $('<div></div>').appendTo(elm_options);
	if (has_search) {
		var elm_search = $('<input type="search" title="Search" value="'+value_search+'" />').appendTo(elm_options_left);
	} else {
		var elm_search = $('<label class="hide"></label>').appendTo(elm_options_left);
	}
	var elm_results_per_page = $('<select class="results-per-page" title="Results Per Page"><option value="25">25</option><option value="50">50</option><option value="100">100</option><option value="150">150</option><option value="-1">All</option></select>').appendTo(elm_options_left);
	var elm_count = $('<span class="count"></span>').appendTo(elm_options_left);
	var elm_paginate = $('<menu class="paginate"></menu>').appendTo(elm_options_right);
	var elm_paginate_previous = $('<button type="button"><span class="icon"></span></button>').appendTo(elm_paginate);
	var elm_paginate_next = $('<button type="button"><span class="icon"></span></button>').appendTo(elm_paginate);

	var func_column_icons = false;
	
	ASSETS.getIcons(cur, ['prev', 'next', 'updown', 'updown-up', 'updown-down'], function(data) {
		
		elm_paginate_previous[0].getElementsByClassName('icon')[0].innerHTML = data.prev;
		elm_paginate_next[0].getElementsByClassName('icon')[0].innerHTML = data.next;
		
		func_column_icons = function() {
			
			if (!arr_settings) {
				return;
			}
			
			for (var i = 0; i < arr_settings.num_columns; i++) {
			
				var elm_column = elms_column[i];
				
				var elms_icon = elm_column.getElementsByClassName('icon');
				elms_icon[elms_icon.length-1].innerHTML = data.updown+data['updown-up']+data['updown-down'];							
			}
			
			resizeDataTable(cur);
			
			func_column_icons = false;
		};
		
		func_column_icons();
	});
	
	cur.appendTo(elm_container);
	
	var elm_filter = null;
	
	if (command_filter) {
		
		elm_filter = $('<button type="button" title="Open Filter" id="'+command_filter+'" class="filter popup" value=""><span class="icon"></span></button>').on('filteranimate', function() {
			
			if (elm_filter.hasClass('active')) {
				new Pulse(elm_filter, {duration: 800, delay_out: 300, repeat: true});
			} else {
				var obj_pulse = elm_filter[0].pulse;
				if (obj_pulse) {
					obj_pulse.abort();
				}				
			}
		});
		ASSETS.getIcons(cur, ['filter'], function(data) {
			elm_filter[0].children[0].innerHTML = data.filter;
		});
		if (settings_filter) {
			elm_filter.addClass('active');
			SCRIPTER.triggerEvent(elm_filter, 'filteranimate');
		}
		elm_filter.insertAfter(elm_search);
		
		elm_filter.on('command', function() {
			
			COMMANDS.setData(elm_filter, COMMANDS.getData(cur), true);
		});
		COMMANDS.setTarget(elm_filter, function(data) {
			
			COMMANDS.setData(cur, (data.filter ? data.filter : {}), false);
			
			SELF.refresh();
			
			elm_filter.toggleClass('active', (data.active ? true : false));
			SCRIPTER.triggerEvent(elm_filter, 'filteranimate');
			
			//moveScroll(cur, {if_visible: true});
		});
	}
	
	var elm_order = null;
		
	if (command_order) {
		
		elm_order = $('<button type="button" title="Set Ordering" id="'+command_order+'" class="popup" value=""><span class="icon"></span></button>');
		
		ASSETS.getIcons(cur, ['updown'], function(data) {
			elm_order[0].children[0].innerHTML = data.updown;
		});

		elm_order.insertBefore(elm_results_per_page);
		
		elm_order.on('command', function() {
			
			COMMANDS.setData(elm_order, {order: arr_settings.arr_order_column, columns: SELF.getColumnsInfo()}, true);
		});
		COMMANDS.setTarget(elm_order, function(data) {
			
			func_order(data);
			SELF.reload();		
		});
	}
	
	var elm_header = cur.children('thead').children('tr');
	var elms_column = elm_header.children('th');
	var elm_body = cur.children('tbody');
	
	tweakDataTable(cur);
	
	// State
	
	var arr_command = {method: method, module: module, command_id: command_id, value: value_filter};
	var arr_settings = {num_records: false, num_columns: elms_column.length, search: value_search, arr_order_column: {}, num_records_start: 0, num_records_length: 25};
	
	var do_sort = false;
	
	for (var i = 0; i < arr_settings.num_columns; i++) {
			
		var elm_column = elms_column[i];
		
		if (elm_column.childNodes.length && elm_column.childNodes[0].nodeType == Node.TEXT_NODE) {
			
			elm_column.innerHTML = '<span>'+elm_column.innerHTML+'</span>';
		}
		
		elm_column.innerHTML += '<span class="icon"></span>';
		
		var sort = elm_column.dataset.sort;
		
		if (elm_column.classList.contains('disable-sort') || !sort) {
			continue;
		}
		
		do_sort = true;
		
		elm_column.dataset.sort_new = sort;
	}
			
	if (func_column_icons) {
		func_column_icons();
	}
	
	// Attach

	elm_results_per_page.on('change', function() {
		
		if (parseInt(this.value) == arr_settings.num_records_length) {
			return;
		}
		
		arr_settings.num_records_length = parseInt(this.value);
		func_load();
	});
	if (has_search) {
		
		elm_search.on('change keyup', function() {
			
			if (this.value == arr_settings.search) {
				return;
			}
			
			arr_settings.search = this.value;
			func_load('search');
		});
	}
	elm_paginate.on('click', 'button', function() {
		
		if (this === elm_paginate_previous[0]) {
			func_load('previous');
		} else if (this === elm_paginate_next[0]) {
			func_load('next');
		} else {
			func_load(parseInt(this.value));
		}
	});
	elm_header.on('click', 'th', function() {
		
		if (this.classList.contains('disable-sort') || cur[0].loading) {
			return;
		}
		
		var nr_column = $(this).index();
		var elm_column = elms_column[nr_column];
		var sort = elm_column.dataset.sort;
		
		var arr_column_order = {};
		arr_column_order[nr_column] = [(!sort || sort != 'asc-0' ? 'asc' : 'desc'), 0];
		
		func_order(arr_column_order);
		SELF.reload();
	});

	// Use
	
	var func_order = function(arr_column_order) {

		elms_column = elm_header.children('th');
		
		for (var i = 0; i < arr_settings.num_columns; i++) {
			
			var elm_column = elms_column[i];
			
			if (elm_column.classList.contains('disable-sort')) {
				continue;
			}

			// Possible cleanup
			if (elm_column.dataset.sort_new) {
				delete elm_column.dataset.sort_new;
			}
			
			if (arr_column_order[i]) {
				elm_column.dataset.sort_new = arr_column_order[i][0]+'-'+arr_column_order[i][1];
			}
		}
		
		do_sort = true;
	};
	
	this.getColumnsInfo = function() {
		
		var arr = {};
		
		elms_column = elm_header.children('th');
		
		for (var i = 0; i < arr_settings.num_columns; i++) {
			
			var elm_column = elms_column[i];
			
			var arr_column = {disable_sort: false, text: '', title: ''};
			
			if (elm_column.classList.contains('disable-sort')) {
				arr_column.disable_sort = true;
			}
			
			arr_column.text = elm_column.innerText;
			arr_column.title = elm_column.getAttribute('title');
			
			arr[i] = arr_column;
		}
		
		return arr;
	};
	
	var str_delay = false;
	var timer_delay = false;
	
	var func_load = function(what) {
			
		if (cur[0].dataset.pause == 1) {
			return;
		}
	
		var func_call = function() {
		
			if (timer_delay) {
				
				elm_search.removeClass('waiting');
				timer_delay = false;
				str_delay = false;
			}

			var abort = (what === 'search' ? true : false);

			if (abort) {
				FEEDBACK.stop(cur);
			}
			if (!FEEDBACK.start(cur)) {
				return;
			}
			
			// Copy settings to formalise the new state when everything has gone according to plan
			
			var arr_settings_new = {};
		
			for (var key in arr_settings) {
				arr_settings_new[key] = arr_settings[key];
			}
			
			var num_start = 0;

			if (what === 'next') {
				num_start = arr_settings.num_records_start + arr_settings.num_records_length;
			} else if (what === 'previous') {
				num_start = arr_settings.num_records_start - arr_settings.num_records_length;
			} else if (what === 'current') {
				if (arr_settings.num_records_start) {
					num_start = arr_settings.num_records_start;
				}
			} else if (parseInt(what)) {
				num_start = ((what - 1) * arr_settings.num_records_length);
			}
			
			if (num_start < 0) { // First page
				num_start = 0;
			} else if (arr_settings.num_records && (num_start + arr_settings.num_records_length) > arr_settings.num_records) { // Last page
				num_start = Math.ceil((arr_settings.num_records - arr_settings.num_records_length) / arr_settings.num_records_length) * arr_settings.num_records_length;
			}
			
			arr_settings_new.num_records_start = num_start;
			
			elms_column = elm_header.children('th');
			
			if (do_sort) {
				
				arr_settings_new.arr_order_column = {};
				
				for (var i = 0; i < arr_settings.num_columns; i++) {
					
					var elm_column = elms_column[i];
					
					arr_settings_new['sort_column_'+i] = (elm_column.classList.contains('disable-sort') ? false: true);
					arr_settings_new['search_column_'+i] = true;
					
					var sort = elm_column.dataset.sort_new;
					
					if (sort) {
						
						var arr_sort = sort.split('-');
						var index = arr_sort[1];
						var direction = arr_sort[0];
						
						if (index == 0) {
							
							// For easy access to first sorted column
							
							arr_settings_new['sorting_column_0'] = i;
							arr_settings_new['sorting_direction_0'] = direction;
						}
						
						arr_settings_new.arr_order_column[index] = [i, direction];
					}
				}
			}
			
			var arr_request = COMMANDS.prepare(cur, $.extend({mod: getModID(cur), method: arr_command.method, id: arr_command.command_id, module: arr_command.module, value: arr_command.value}, arr_settings_new));
			
			FEEDBACK.request(cur, cur, {
				type: 'POST',
				contentType: arr_request.contentType,
				dataType: 'json',
				url: LOCATION.getURL('command'),
				data: arr_request.data,
				processData: false,
				context: cur,
				error: function() {
					
					do_sort = false;							
				},
				success: function(json) {

					FEEDBACK.check(cur, json, function() {
						
						arr_settings = arr_settings_new;

						if (do_sort) {
							
							for (var i = 0; i < arr_settings.num_columns; i++) {
					
								var elm_column = elms_column[i];
								
								if (elm_column.dataset.sort_new) {
									
									elm_column.dataset.sort = elm_column.dataset.sort_new;
									delete elm_column.dataset.sort_new;
								} else if (elm_column.dataset.sort) {
									
									delete elm_column.dataset.sort;
								}
							}

							do_sort = false;
						}
						
						var data = json.data;
						
						if (data) {
						
							func_draw(data);
							
							SCRIPTER.runDynamic(cur);
							SCRIPTER.triggerEvent(cur, 'ajaxloaded', {elm: cur});
							SCRIPTER.triggerEvent(cur, 'commandfinished');
						}
					});
				}
			});
		}
		
		if (delay) {
			
			if (!arr_settings.search || str_delay == arr_settings.search) { // Check for delay criteria
				
				if (timer_delay) {
					clearTimeout(timer_delay);
				}
			} else {
				
				str_delay = arr_settings.search; // Set delay criteria
				
				if (timer_delay) {
					clearTimeout(timer_delay);
				} else {
					elm_search.addClass('waiting');
				}
				
				if (str_delay) { // Only delay when there are delay criteria
					timer_delay = setTimeout(func_call, delay * 1000);
					return;
				} else {
					timer_delay = true;
				}
			}
		}
		
		func_call();
	};
	
	var func_draw = function(data) {
		
		var num_records = parseInt(data.total_records);
		var num_records_filtered = parseInt(data.total_records_filtered);
		
		var num_records_end = ((arr_settings.num_records_start + arr_settings.num_records_length) > num_records_filtered || arr_settings.num_records_length == -1 ? num_records_filtered : (arr_settings.num_records_start + arr_settings.num_records_length));
		
		arr_settings.num_records = num_records_filtered;
		
		var num_pages = (arr_settings.num_records_length != -1 ? Math.ceil(num_records_filtered / arr_settings.num_records_length) : 1);
		var num_page_active = (arr_settings.num_records_start / arr_settings.num_records_length) + 1;
		
		var str_thousands = ' '; // MEDIUM MATHEMATICAL SPACE
		
		if (!num_records_filtered) {
			if (num_records) {
				var str_count = '<span>No results</span><span class="count-from"> from '+formatNumber(num_records , 0, str_thousands, false)+(data.total_records_info ? data.total_records_info : '')+'</span>';
			} else {
				var str_count = '<span>None</span>';
			}
		} else {
			var str_count = formatNumber((arr_settings.num_records_start + 1), 0, str_thousands, false)+' - '+formatNumber(num_records_end , 0, str_thousands, false);
			if (num_records == num_records_filtered) {
				str_count = '<span>'+str_count+'</span><span class="count-of"> of <strong>'+formatNumber(num_records, 0, str_thousands, false)+'</strong>'+(data.total_records_info ? data.total_records_info : '')+'</span>';
			} else {
				str_count = '<span>'+str_count+'</span><span class="count-of"> of <strong>'+formatNumber(num_records_filtered, 0, str_thousands, false)+'</strong>'+(data.total_records_filtered_info ? data.total_records_filtered_info : '')+'</span><span class="count-from"> from '+formatNumber(num_records, 0, str_thousands, false)+(data.total_records_info ? data.total_records_info : '')+'</span>';
			}
		}
		
		while (elm_paginate[0].firstChild) {
			elm_paginate[0].removeChild(elm_paginate[0].firstChild);
		}
	
		elm_count[0].innerHTML = str_count;
		
		const num_buttons_total = arr_options.paginate;
		const num_buttons_middle = arr_options.paginate_middle;
		
		var mode_buttons = (num_pages <= num_buttons_total || num_page_active <= (num_buttons_total - 2) ? 'start' : (num_page_active > num_pages - (num_buttons_total - 2) ? 'end' : 'middle'));
		var num_buttons_max = (mode_buttons == 'middle' ? (num_buttons_middle + 2) : num_buttons_total);
		var num_buttons = (num_pages > num_buttons_max ? num_buttons_max : num_pages);
		
		elm_paginate[0].appendChild(elm_paginate_previous[0]);
					
		for (i = 1; i <= num_buttons; i++) {
			
			if (i == num_buttons_max && num_pages > num_buttons_total && mode_buttons != 'end') {
				var elm_ellipsis = $('<span></span>');
				elm_paginate[0].appendChild(elm_ellipsis[0]);
			}
			
			var num_page = 1;
			if (i == num_buttons_max) {
				num_page = num_pages;
			} else if (i == 1) {
				num_page = 1;
			} else {
				if (mode_buttons == 'start') {
					num_page = i;
				} else if (mode_buttons == 'middle') {
					num_page = Math.floor(num_page_active - (num_buttons_max / 2)) + i;
				} else if (mode_buttons == 'end') {
					num_page = (num_pages - num_buttons_max) + i;
				}
			}
				
			var elm_button = $('<button type="button" value="'+num_page+'"'+(num_page_active == num_page ? ' class="selected"' : '')+'>'+formatNumber(num_page, 0, str_thousands, false)+'</button>');
			elm_paginate[0].appendChild(elm_button[0]);
			
			if (i == 1 && mode_buttons != 'start') {
				var elm_ellipsis = $('<span></span>');
				elm_paginate[0].appendChild(elm_ellipsis[0]);
			}
		}
		
		elm_paginate[0].appendChild(elm_paginate_next[0]);
		
		var elm_body_new = $('<tbody></tbody>');
		
		var arr_data = data.data;
		
		if (arr_data.length) {
									
			for (var i = 0, len = arr_data.length; i < len; i++) {

				var arr_row = arr_data[i];
				
				var elm_tr = $('<tr></tr>');
				elm_tr = elm_tr[0];
				
				if (arr_row.id) {
					elm_tr.setAttribute('id', arr_row.id);
				}
				if (arr_row.class) {
					elm_tr.setAttribute('class', arr_row.class);
				}
				if (arr_row.attr) {
					
					var arr_row_attr = arr_row.attr;
					
					for (var key in arr_row_attr) {
						elm_tr.setAttribute(key, arr_row_attr[key]);
					}
				}
				
				for (var j = 0, len_j = arr_settings.num_columns; j < len_j; j++) {
					
					var elm_td = $('<td></td>');
					elm_td = elm_td[0];
					
					elm_td.innerHTML = (arr_row[j] ? arr_row[j] : '');

					if (arr_row.cell && arr_row.cell[j] && arr_row.cell[j].attr) {
						
						var arr_cell_attr = arr_row.cell[j].attr;
						
						for (var key in arr_cell_attr) {
							elm_td.setAttribute(key, arr_cell_attr[key]);
						}
					}
						
					elm_tr.appendChild(elm_td);
				}
				
				elm_body_new[0].appendChild(elm_tr);
			}
		} else {
			
			var elm_tr = $('<tr><td colspan="'+arr_settings.num_columns+'" class="empty">No results.</td></tr>');

			elm_body_new[0].appendChild(elm_tr[0]);
		}
		
		cur[0].removeChild(elm_body[0]);
		elm_body = elm_body_new;
		
		cur[0].appendChild(elm_body[0]);
		
		tweakDataTable(cur);
		SCRIPTER.triggerEvent(cur, 'newpage');
	};

	func_load();
	
	cur.on('next', function() {
		func_load('next');
	}).on('prev', function() {
		func_load('previous');
	}).on('pause', function() {
		cur[0].dataset.pause = 1;
	}).on('continue', function() {
		SELF.continue();
	}).on('reload', function() {
		SELF.reload();
	});
	
	this.getFilter = function() {
		
		return arr_command.value;
	};
	this.setFilter = function(filter, is_active) {
		
		if (!elm_filter) {
			return;
		}
		
		const func_filter = COMMANDS.getTarget(elm_filter);
								
		func_filter({filter: filter, active: is_active});
	};
	this.getSearch = function() {
		
		return arr_settings.search;
	};	

	this.handleColumn = function(selector, content, selector_before) {
		
		var elm_header = cur.children('thead').children('tr');
		var elm_target = elm_header.children(selector);

		if (content == false) {
			
			if (!elm_target.length) {
				return;
			}
			
			var index = elm_target.index();
			arr_settings.num_columns--;
			
			elm_target.remove();
		} else {
			
			var elm_content = $(content);
			
			ASSETS.getIcons(cur, ['updown', 'updown-up', 'updown-down'], function(data) {
				
				elm_content[0].innerHTML += '<span class="icon"></span>';
				
				var elms_icon = elm_content[0].getElementsByClassName('icon');
				elms_icon[elms_icon.length-1].innerHTML = data.updown+data['updown-up']+data['updown-down'];
				
				resizeDataTable(cur);
			});
			
			if (elm_target.length) {
				
				if (elm_target[0].dataset.sort && !elm_content[0].dataset.sort) {
					elm_content[0].dataset.sort = elm_target[0].dataset.sort;
				}
				
				elm_target.replaceWith(elm_content); // Update column header
			} else {
				
				var elm_before = elm_header.children(selector_before);
				
				var index = elm_before.index();
				arr_settings.num_columns++;
				
				elm_before.before(elm_content);
			}
		}

		// Fill or remove for visual feedback
		
		if (content == false || !elm_target.length) {
				
			var elm_trs = cur.children('tbody').children('tr');
			
			for (var i = 0, len = elm_trs.length; i < len; i++) {
			
				var elm_tr = elm_trs[i];
				
				if (content == false) {
					
					elm_tr.removeChild(elm_tr.childNodes[index]);
				} else {
					
					var elm_td = $('<td></td>');
					elm_td = elm_td[0];
						
					elm_tr.insertBefore(elm_td, elm_tr.childNodes[index]);
				}
			}
		}
		
		resizeDataTable(cur);
		
		// Reset sorting
		
		var elm_columns = elm_header.children('th');
			
		for (var i = index; i < arr_settings.num_columns; i++) {
			
			var elm_column = elm_columns[i];
			
			if (!elm_column.dataset.sort) {
				continue;
			}
			
			arr_settings.sort = true;
			elm_column.dataset.sort_new = elm_column.dataset.sort;
		}
	};
	
	this.resetSort = function() {

		func_order({});
	};
	
	this.refresh = function() {
									
		SCRIPTER.triggerEvent(cur, 'command');
		
		if (COMMANDS.isAborted(cur)) {
			return;
		}
		
		cur[0].dataset.pause = 0;
		
		var match = cur.attr('id').match(/d:([^:]*):([^-]*)-(.*)/);
		
		if (cur.data('method')) {
			var method = cur.data('method');
		} else {
			var method = match[2];
		}
		if (cur.data('module')) {
			var module = cur.data('module');
		} else {
			var module = match[1];
		}
		var command_id = match[3];
		var value = (COMMANDS.getData(cur) ? COMMANDS.getData(cur) : '');
						
		arr_command.method = method;
		arr_command.module = module;
		arr_command.command_id = command_id;
		arr_command.value = value;

		SELF.reload();
	};

	this.reload = function() {
		
		func_load('current');
	}		
}

$(document).on('click', '*[type=button].popup, .a.popup, tr.popup', function(e) {
	
	var elm_target = $(e.target);
	
	if (this.nodeName == 'TR' && (elm_target.is('input, button, a, .a, img.enlarge') || elm_target.closest('input, button, a, .a, img.enlarge').length)) {
		return;
	}
	
	e.stopPropagation();
	
	COMMANDS.popupCommand(this);
});

$.fn.popupCommand = function(arr_options) {
		
	return this.each(function() {
		
		COMMANDS.popupCommand(this, arr_options);
	});
};

$(document).on('click', '*[type=button].msg', function(e) {
	
	var cur = $(this);
	
	var arr_options = {};
	
	if (cur.hasClass('del')) {
		arr_options.remove = true;
	}
	
	e.stopPropagation();
	
	COMMANDS.messageCommand(this, arr_options);
});

$.fn.messageCommand = function(arr_options) {
	
	return this.each(function() {
		
		COMMANDS.messageCommand(this, arr_options);
	});
};

$(document).on('click', '*[type=button].quick, .a.quick', function(e) {
	
	e.stopPropagation();
	
	COMMANDS.quickCommand(this);
});

$.fn.quickCommand = function(target, arr_options) {
		
	return this.each(function() {
		
		COMMANDS.quickCommand(this, target, arr_options);
	});
};

$(document).on('click', '[id^=f\\:] *[type=submit]', function(e) {
	
	if (this.classList.contains('invalid')) { // spam protection, only allow direct clicks on form submit
		
		var elm_valid = $('[id^=f\\:] *[type=submit]:not(.invalid)');
		elm_valid.prop('disabled', true);
		
		if (this.form_timeout) {
			clearTimeout(this.form_timeout);
		}
		
		this.form_timeout = setTimeout(function() {
			elm_valid.prop('disabled', false);
		}, 200);
		
		return false;
	}
	
	COMMANDS.formCommand(this, false, e);
});

$.fn.formCommand = function(arr_options, e) {
			
	return this.each(function() {
		
		COMMANDS.formCommand(this, arr_options, e);
	});
};

$.fn.dataTable = function() {
	
	var obj = new ElementObjectByParameters(DataTable, 'datatable', arguments);
			
	return this.each(obj.run);
};
