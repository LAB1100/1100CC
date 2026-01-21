
/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
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
		
		let elm_context = elm.closest('[id^="x:"], [id^="y:"], [id^="f:"], [id^="d:"]');
		
		if (elm_context.length) {
			return elm_context[0];
		}
		
		const elm_overlay = elm.closest('.overlay');
		
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
		
		let module = getElementData(elm, 'module');
		
		if (!module) {
			
			const arr_match = elm.getAttribute('id').match(/([dfxy]):([^:]*):([^-]*)-(.*)/);
			module = arr_match[2];
		}
		
		return module;
	};
	
	this.setID = function(elm, identifier) {
		
		return setElementData(elm, 'command_id', identifier, true);
	};
	this.getID = function(elm, do_parse) {
		
		var elm = getElement(elm);
		
		let command_id = getElementData(elm, 'command_id');
		
		if (command_id === undefined || command_id === ''|| command_id === null) {
			command_id = false;
		}
		
		if (command_id === false && do_parse) {
			
			const arr_match = elm.getAttribute('id').match(/([dfxy]):([^:]*):([^-]*)-(.*)/);
			command_id = arr_match[4];
		}
		
		return command_id;
	};
	
	this.setAbort = function(elm, do_abort) {
				
		return setElementData(elm, 'abort', do_abort);
	};
	this.isAborted = function(elm, elm_command) {
		
		if (getElementData(elm, 'abort')) {
			return true;
		}
		if (elm_command && getElementData(elm_command, 'abort')) {
			return true;
		}
		
		return false;
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

		const elm_action = $(elm);
		
		SCRIPTER.triggerEvent(elm_action, 'command');
		
		const elm_context = elm_action.closest('[id^="x:"], [id^="y:"]');
		
		if (COMMANDS.isAborted(elm_context, elm_action)) {
			return;
		}

		var match = elm_context.attr('id').match(/([xy]):([^:]*):([^-]*)-(.*)/);

		if (elm_action.data('method')) {
			var method = elm_action.data('method');
		} else if (match[1] == 'y') {
			var method = match[3];
		} else if (match[1] == 'x') {
			var method = elm_action.attr('class').split(' ').slice(-1)[0]; // Last class name
		}
		if (elm_action.data('module')) {
			var module = elm_action.data('module');
		} else {
			var module = match[2];
		}
		var command_id = (COMMANDS.getID(elm_context) !== false ? COMMANDS.getID(elm_context) : match[4]);
		var mod_id = getModID(elm_context);
		
		if (elm_action.is('input:not([type=submit], [type=button]), select, textarea')) {
			var value = (elm_action.is('input[type=checkbox], input[type=radio]') ? elm_action.filter(':checked').val() : elm_action.val());
			if (elm_context.data('value')) {
				value = $.extend({'value_element': value}, (typeof elm_context.data('value') === 'object' ? elm_context.data('value') : [elm_context.data('value')]));
			}
		} else {
			var value = elm_context.data('value');
		}
		if (!value) { // Allow for object manupulation further down the road
			value = {};
			elm_context.data('value', value);
		}
		
		var target = (elm_context.data('target') ? elm_context.data('target') : (elm_context.data('target') !== false ? 'module' : false));
		if (target instanceof Array) {
			var settings = [];
			for (var i in target) {
				settings[i] = $.extend({}, arr_options, (target[i].data('options') ? target[i].data('options') : {}));
			}
		} else {
			var settings = $.extend(arr_options, (elm_context.data('options') ? elm_context.data('options') : {}));
		}
						
		if (!FEEDBACK.start(elm_action)) {
			return;
		}
		
		var arr_request = SELF.prepare(elm_action, {mod: mod_id, method: method, id: command_id, module: module, value: value});
		let call = null;
		
		FEEDBACK.request(elm_action, elm_context, {
			type: 'POST',
			contentType: arr_request.contentType,
			dataType: 'json',
			url: LOCATION.getURL('command'),
			data: arr_request.data,
			processData: false,
			context: elm_action,
			beforeSend: function(xhr, settings) {
				call = settings;
			},
			uploadProgress: function(event, position, total, percent) {
				//elm_context.find('progress').removeClass('hide').attr('value', percent);
			},
			success: function(json) {
				
				FEEDBACK.check(elm_action, json, function() {
					
					if (json.do_download) {
						
						SELF.parse(json, target, elm_context, settings, call, function() {

							SCRIPTER.triggerEvent(elm_action, 'commandfinished');
						});
						
						return;
					}
				
					const elm_popup = $('<div class="popup"></div>');
					
					if (!elm_context.closest('.mod').length) {
						settings.overlay = 'document';
					}
					if (settings.overlay == 'document') {
						var elm_overlay_target = document.body;
					} else {
						var elm_overlay_target = (settings.overlay ? settings.overlay : elm_context.closest('.mod'));
					}
					var obj_overlay = new Overlay(elm_overlay_target, elm_popup, {
						call_close: function() {
							FEEDBACK.stopContext(elm_popup);
						},
						elm_prevnext: (elm_action.attr('data-method') ? elm_action : {}),
						sizing: (settings.overlay_settings ? settings.overlay_settings.sizing : undefined)
					});
					
					var elm_overlay = obj_overlay.getOverlay();
					
					elm_overlay.context = elm_context;
					if (settings.overlay == 'document') {
						setElementData(elm_overlay, 'mod', mod_id);
					}
				
					var call_popup = function(json) {
						
						const elm_html = $(json.html);
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
						
						const elm_location = elm_popup.children('[data-location]').first();
						
						if (elm_location.length) {
							obj_overlay.location(elm_location[0].dataset.location);
							delete elm_location[0].dataset.location;
						}
						
						SCRIPTER.runDynamic(elm_html);
						SCRIPTER.triggerEvent(elm_context, 'ajaxloaded', {elm: elm_html});
						
						if (elm_form.length) {
							
							SCRIPTER.triggerEvent(elm_action, 'commandintermediate', {elm: elm_popup});
							
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
								let call = null;
								
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
											
											let elm_html_form = null;
											try {
												elm_html_form = $(json.html);
											} catch(e) { }
											
											if (elm_html_form && elm_html_form.is('form:not([id^=f\\\:])')) {
												
												call_popup(json);
											} else {
												
												SELF.parse(json, target, elm_context, settings, call, function() {
													
													LOCATION.unlock(elm_popup[0]);
													obj_overlay.close();
													SCRIPTER.triggerEvent(elm_action, 'commandfinished');
												});
											}
										});
									}											
								});
							}
						} else {
							
							SCRIPTER.triggerEvent(elm_action, 'commandfinished');
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
		
		const elm_action = $(elm);

		SCRIPTER.triggerEvent(elm_action, 'command');

		const elm_context = elm_action.closest('[id^="x:"], [id^="y:"]');
		
		if (COMMANDS.isAborted(elm_context, elm_action)) {
			return;
		}

		var match = elm_context.attr('id').match(/([xy]):([^:]*):([^-]*)-(.*)/);

		if (elm_action.data('method')) {
			var method = elm_action.data('method');
		} else if (match[1] == 'y') {
			var method = match[3];
		} else if (match[1] == 'x') {
			var method = elm_action.attr('class').split(' ').slice(-1)[0]; // Last class name
		}
		if (elm_action.data('module')) {
			var module = elm_action.data('module');
		} else {
			var module = match[2];
		}
		var command_id = (COMMANDS.getID(elm_context) !== false ? COMMANDS.getID(elm_context) : match[4]);
		var mod_id = getModID(elm_context);
		
		if (elm_action.is('input:not([type=submit], [type=button]), select, textarea')) {
			var value = (elm_action.is('input[type=checkbox], input[type=radio]') ? elm_action.filter(':checked').val() : elm_action.val());
			if (elm_context.data('value')) {
				value = $.extend({'value_element': value}, (typeof elm_context.data('value') === 'object' ? elm_context.data('value') : [elm_context.data('value')]));
			}
		} else {
			var value = elm_context.data('value');
		}
		var target = (elm_context.data('target') ? elm_context.data('target') : (elm_context.data('target') !== false ? 'module' : false));
		if (target instanceof Array) {
			var settings = [];
			for (var i in target) {
				settings[i] = $.extend({}, arr_options, (target[i].data('options') ? target[i].data('options') : {}));
			}
		} else {
			var settings = $.extend(arr_options, (elm_context.data('options') ? elm_context.data('options') : {}));
		}
		var msg = (COMMANDS.getMessage(elm_action) ? COMMANDS.getMessage(elm_action) : 'conf_delete');
		
		if (!FEEDBACK.start(elm_action)) {
			return;
		}
		
		var arr_request = SELF.prepare(elm_action, {mod: mod_id, module: 'cms_general', method: 'get_label', id: msg});

		FEEDBACK.request(elm_action, elm_context, {
			type: 'POST',
			contentType: arr_request.contentType,
			dataType: 'json',
			url: LOCATION.getURL('command'),
			data: arr_request.data,
			processData: false,
			context: elm_action,
			success: function(json) {
				
				FEEDBACK.check(elm_action, json, function() {
					
					let elm_html = null;
					try {
						elm_html = $(json.html);
					} catch (e) {};
					
					if (!elm_context.closest('.mod').length) {
						settings.overlay = 'document';
					}
					if (settings.overlay == 'document') {
						var elm_overlay_target = document.body;
					} else {
						var elm_overlay_target = (settings.overlay ? settings.overlay : elm_context.closest('.mod'));
					}
					
					var elm_popup = $('<div class="popup message"><span class="icon"></span><div>'+(elm_html ? elm_html : json.html)+'</div></div>');
					
					ASSETS.getIcons(elm_action, ['attention'], function(data) {
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
									
									SELF.parse(json, target, elm_context, settings, call, function() {
										
										obj_overlay.close();
										SCRIPTER.triggerEvent(elm_action, 'commandfinished');
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
					elm_overlay.context = elm_context;
					
					if (elm_html) {
						SCRIPTER.runDynamic(elm_html);
					}
					SCRIPTER.triggerEvent(elm_context, 'ajaxloaded', {elm: elm_html});
					SCRIPTER.triggerEvent(elm_action, 'commandintermediate', {elm: elm_popup});
				});
			}
		});
	};
	
	this.quickCommand = function(elm, target, arr_options) {
	
		var arr_options = $.extend({
		}, arr_options || {});
		
		const elm_action = $(elm);

		SCRIPTER.triggerEvent(elm_action, 'command');
		
		const elm_context = elm_action.closest('[id^="x:"], [id^="y:"]');
		
		if (COMMANDS.isAborted(elm_context, elm_action)) {
			return;
		}

		var match = elm_context.attr('id').match(/([xy]):([^:]*):([^-]*)-(.*)/);

		if (elm_action.data('method')) {
			var method = elm_action.data('method');
		} else if (match[1] == 'y') {
			var method = match[3];
		} else if (match[1] == 'x') {
			var method = elm_action.attr('class').split(' ').slice(-1)[0]; // Last class name
		}
		if (elm_action.data('module')) {
			var module = elm_action.data('module');
		} else {
			var module = match[2];
		}
		var command_id = (COMMANDS.getID(elm_context) !== false ? COMMANDS.getID(elm_context) : match[4]);
		
		if (elm_action.is('input:not([type=submit], [type=button]), select, textarea')) {
			var value = (elm_action.is('input[type=checkbox], input[type=radio]') ? elm_action.filter(':checked').val() : elm_action.val());
			if (elm_context.data('value')) {
				value = $.extend({'value_element': value}, (typeof elm_context.data('value') === 'object' ? elm_context.data('value') : [elm_context.data('value')]));
			}
		} else {
			var value = elm_context.data('value');
		}
		var target = (target ? target : (target !== false ? (elm_context.data('target') ? elm_context.data('target') : (elm_context.data('target') !== false ? 'module' : false)) : false));
		if (target instanceof Array) {
			var settings = [];
			for (var i in target) {
				settings[i] = $.extend({}, arr_options, (target[i].data('options') ? target[i].data('options') : {}));
			}
		} else {
			var settings = $.extend(arr_options, (elm_context.data('options') ? elm_context.data('options') : {}));
		}

		if (!FEEDBACK.start(elm_action)) {
			return;
		}

		var arr_request = SELF.prepare(elm_action, {mod: getModID(elm_context), method: method, id: command_id, module: module, value: value});
		var call = false;
		
		FEEDBACK.request(elm_action, elm_context, {
			type: 'POST',
			contentType: arr_request.contentType,
			dataType: 'json',
			url: LOCATION.getURL('command'),
			data: arr_request.data,
			processData: false,
			context: elm_action,
			beforeSend: function(xhr, settings) {
				call = settings;
			},
			uploadProgress: function(event, position, total, percent) {
				//elm_context.find('progress').removeClass('hide').attr('value', percent);
			},
			success: function(json) {
				FEEDBACK.check(elm_action, json, function() {
					
					SELF.parse(json, target, elm_context, settings, call, function() {
						
						SCRIPTER.triggerEvent(elm_action, 'commandfinished');
					});
				});
			}
		});
	};
	
	this.formCommand = function(elm, arr_options, e) {
	
		var arr_options = $.extend({
			html: 'replace'
		}, arr_options || {});
		
		const elm_action = $(elm);
		
		SCRIPTER.triggerEvent(elm_action, 'command');
		
		const elm_context = elm_action.closest('[id^="f:"]');
			
		if (COMMANDS.isAborted(elm_context, elm_action)) {
			
			if (e) {
				e.preventDefault();
			}
			
			return;
		}
		
		var match = elm_context.attr('id').match(/f:([^:]*):([^-]*)-(.*)/);
		
		if (elm_action.data('method')) {
			var method = elm_action.data('method');
		} else {
			var method = match[2];
		}
		if (elm_action.data('module')) {
			var module = elm_action.data('module');
		} else {
			var module = match[1];
		}
		var command_id = match[3];
		
		var target = (elm_context.data('target') ? elm_context.data('target') : (elm_context.data('target') !== false ? elm_context : false));
		if (target instanceof Array) {
			var settings = [];
			for (var i in target) {
				settings[i] = $.extend({}, arr_options, (target[i].data('options') ? target[i].data('options') : {}));
			}
		} else {
			var settings = $.extend(arr_options, (elm_context.data('options') ? elm_context.data('options') : {}));
		}
		var value = elm_context.data('value');
		
		var name_submit = (e && $(e.target).is(':submit') && e.target.getAttribute('name') ? e.target.getAttribute('name') : false);

		var call_submit = function() {

			SCRIPTER.triggerEvent(elm_context, 'ajaxsubmithandler');
			
			if (!FEEDBACK.start(elm_action)) {
				return;
			}

			SCRIPTER.triggerEvent(elm_context, 'ajaxsubmit');
			
			if (name_submit == 'do_discard') {
				elm_context.find('input, select, textarea').prop('disabled', true);
			}
			
			var arr_request = {mod: getModID(elm_context), method: method, id: command_id, module: module, value: value};
			
			if (name_submit) {
				
				if (name_submit == 'do_discard') {
					arr_request.is_discard = 1;
				} else {
					arr_request[name_submit] = 1;
				}
			}
			
			arr_request = SELF.prepare(elm_context, arr_request);
			var call = false;
			
			FEEDBACK.request(elm_action, elm_context, {
				type: 'POST',
				contentType: arr_request.contentType,
				dataType: 'json',
				url: LOCATION.getURL('command'),
				data: arr_request.data,
				processData: false,
				context: elm_action,
				beforeSend: function(xhr, settings) {
					call = settings;
				},
				uploadProgress: function(event, position, total, percent) {
					elm_context.find('progress').removeClass('hide').attr('value', percent);
				},
				error: function() {
					SCRIPTER.triggerEvent(elm_context, 'ajaxsubmitted');
				},
				success: function(json) {
					
					SCRIPTER.triggerEvent(elm_context, 'ajaxsubmitted');
					
					FEEDBACK.check(elm_action, json, function() {
						
						var elm_parent = elm_context.parent();
						
						SELF.parse(json, target, elm_context, settings, call, function() {
						
							if (!onStage(elm_action[0])) {
	
								if (elm_action[0] != elm_context[0] && onStage(elm_context[0])) {
									var elm_trigger = elm_context;
								} else if (onStage(elm_parent[0])) {
									var elm_trigger = elm_parent;
								} else {
									var elm_trigger = document;
								}
								SCRIPTER.triggerEvent(elm_trigger, 'closed', {elm: elm_context});
							}
							
							SCRIPTER.triggerEvent(elm_action, 'commandfinished');
						});
					});
				}
			});
		};

		if (name_submit != 'do_discard') {
			
			let arr_rules = getElementData(elm_context, 'rules'); // Always check, the form's rules could have been updated
			arr_rules = (arr_rules ? arr_rules : {});
			
			if (!elm_context.data('validator')) {
					
				elm_context.validate({
					rules: arr_rules,
					submitHandler: function(elm) {
						
						this.resetForm();
						call_submit();
					}
				});
			} else {
			
				$.extend(elm_context.validate().settings, {rules: arr_rules});
			}
			
			if (!e) {
				SCRIPTER.triggerEvent(elm_context, 'submit');
			}
		} else {
			
			if (e) {
				e.preventDefault();
			}
			
			FEEDBACK.stopContext(elm_context);
			
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
	
	this.prepare = function(elm_data, arr_request) {
		
		var elm_data = $(elm_data);
		
		const is_form = (elm_data && elm_data.is('form') ? true : false);
		const has_object = (typeof arr_request.value === 'object' && arr_request.value !== null);
		const has_form = (has_object && arr_request.value.forms);
		
		let contentType = null;
		let data = null;
			
		if (is_form || has_form) {
			
			let form_collect = null;
					
			if (is_form) {
				
				const elms_ignore = [];
				
				runElementSelectorFunction(elm_data[0], '[type=submit][name]:enabled', function(elm_found) { // Make sure FormData procedures really disregard buttons (i.e. polyfill FormData issue)
					elms_ignore.push(elm_found);
				});
				runElementSelectorFunction(elm_data[0], 'input[type=file]:not([disabled])', function(elm_found) { // A Safari 11.1/webkit bug on empty file inputs
					if (elm_found.files.length > 0) {
						return;
					}
					elms_ignore.push(elm_found);
				});

				for (let i = 0, len = elms_ignore.length; i < len; i++) {
					elms_ignore[i].setAttribute('disabled', '');
				}
					
				form_collect = new FormData(elm_data[0]);
				
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
	
	this.parse = function(json, target, elm_context, arr_settings, call, callback) {
		
		let elm_container = null;
		if (arr_settings.elm_container) {
			elm_container = arr_settings.elm_container;
		} else {
			elm_container = getContainer(elm_context); // Originating element container
		}
		
		if (call.is_discard) { // Cancel call to new parse from old parse
			
			delete call.is_discard;
			return false;
		}
		
		const elm_action = call.context;
		
		if (json.do_confirm) {
			
			if (!elm_context.closest('.mod').length) {
				arr_settings.overlay = 'document';
			}
			
			let elm_overlay_target = null;
			if (arr_settings.overlay == 'document') {
				elm_overlay_target = document.body;
			} else {
				elm_overlay_target = (arr_settings.overlay ? arr_settings.overlay : elm_context.closest('.mod'));
			}

			const elm_popup = $('<div class="popup confirm"><span class="icon"></span><div>'+json.html+'</div></div>');
			
			ASSETS.getIcons(elm_context, ['attention'], function(data) {
				elm_popup[0].children[0].innerHTML = data.attention;
			});

			const elm_menu = $('<menu></menu>').appendTo(elm_popup);
			
			const func_confirm = function(is_confirm) {

				if (!FEEDBACK.start(elm_action)) {
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
			
			let obj_overlay = null;
			
			$('<input type="button" value="Ok" />').appendTo(elm_menu).on('click', function() {
				
				func_confirm(true);
				
				obj_overlay.close();
			});
			$('<input type="button" value="Cancel" />').appendTo(elm_menu).on('click', function() {
				
				FEEDBACK.stopContext(elm_context);
				
				func_confirm(false);
				
				obj_overlay.close();
			});
			
			obj_overlay = new Overlay(elm_overlay_target, elm_popup, {
				position: 'middle',
				button_close: false,
				sizing: (arr_settings.overlay_settings ? arr_settings.overlay_settings.sizing : undefined)
			});
			
			const elm_overlay = obj_overlay.getOverlay();
			
			if (arr_settings.overlay == 'document') {
				setElementData(elm_overlay, 'mod', getModID(elm_context));
			}
				
			return false;
		} else if (json.do_download) {

			let arr_data = null;
			
			try {
				arr_data = JSON.parse(call.data);
			} catch (e) {};
			
			if (arr_data) { // JSON
				
				if (typeof json.do_download === 'object') {
					$.extend(arr_data, json.do_download);
				}
				
				arr_data.is_download = true;
				
				call.data = JSON.stringify(arr_data);
			} else { // FormData
				
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
				
				call.data.append('is_download', 1);
			}
			
			if (!FEEDBACK.start(elm_action)) {
				return;
			}
			
			LOADER.progress(elm_action, 0);

			const xhr = new XMLHttpRequest();
			xhr.responseType = 'blob';
			xhr.onprogress = function(e) {
				
				if (!e.lengthComputable) {
					return;
				}
				
				LOADER.progress(elm_action, parseInt((e.loaded / e.total) * 100));
			};
			xhr.onload = async function(e) {
				
				const blob = xhr.response;
				
				if (blob.type == 'text/plain') {
					
					let json = null;
					
					try {
						
						const str_text = await blob.text();
						json = JSON.parse(str_text);
					} catch(e) {}
					
					if (json) {
						
						FEEDBACK.check(elm_action, json, function() {
							
							if (callback) {
								callback();
							}
						});
						
						return;
					}
				}

				let str_filename = xhr.getResponseHeader('Content-Disposition');
				
				if (!str_filename) {
					return;
				}
				
				str_filename = str_filename.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)[1]; // https://stackoverflow.com/a/23054920/

				const elm_a = document.createElement('a');
				elm_a.href = window.URL.createObjectURL(blob);
				elm_a.download = str_filename;
				
				SCRIPTER.triggerEvent(elm_a, 'click', false, {do_native: true});
				
				FEEDBACK.stop(elm_action);
			};

			FEEDBACK.request(elm_action, elm_context, {
				xhr: xhr,
				url: call.url,
				data: call.data
			});
			
			return false;
		} else if ((json.html || json.html === "") && target) {
			
			let elm_result = false;
			
			if (typeof target === 'function') {
				
				const html = json.html;
				let elm_html = null;
				
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
				
				elm_result = target.apply(elm_context[0], [(elm_html ? elm_html : html)]);
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
								elm_context.closest('.mod').html(elm_html);
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
			
			if (onStage(elm_context[0])) {
				
				SCRIPTER.runDynamic(elm_result); // Applied to first element
				SCRIPTER.triggerEvent(elm_context, 'ajaxloaded', {elm: elm_result});
			} else if (elm_container[0] && onStage(elm_container[0])) {
				
				SCRIPTER.runDynamic(elm_result); // Applied to first element
				SCRIPTER.triggerEvent(elm_container, 'ajaxloaded', {elm: elm_result});
			} else {
				
				SCRIPTER.triggerEvent(document, 'ajaxloaded', {elm: elm_result});
			}
		} else if (typeof target === "function") {
			
			target.apply(elm_context[0]);
		}
		
		if (json.refresh_table) {

			let elms_datatable = elm_context.closest('table[id^="d:"]');
			
			if (!elms_datatable.length) {
				elms_datatable = elm_container.find('table[id^="d:"]');
			}
			
			elms_datatable = elms_datatable.filter(function () {
				return !hasElement(elm_action[0], this); // Do not target tables inside method's context (e.g. popup)
			});
			
			elms_datatable.each(function() {
				
				if (!this.datatable) {
					return;
				}
				
				this.datatable.refresh();
			});
		}
		if (json.reset_form) {
			
			if (elm_context.is('form')) {
				SCRIPTER.triggerEvent(elm_context, 'reset', false, {do_native: true});
			} else {
				elm_context.val('');
			}

			LOCATION.updateLocked(elm_context[0]);
		}
		
		if (arr_settings.remove && target != false) {
			
			elm_context.fadeOut(function() {
				$(this).remove();
			});
		}
		if (arr_settings.hide && target != false) {
			
			elm_context.hide();
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
							
	const elm_action = $(elm);
	
	if (elm_action[0].datatable) {
		return;
	}
	elm_action[0].datatable = this;

	SCRIPTER.triggerEvent(elm_action, 'command');
	
	if (COMMANDS.isAborted(elm_action)) {
		return;
	}
	
	var match = elm_action.attr('id').match(/d:([^:]*):([^-]*)-(.*)/);
	
	if (elm_action.data('method')) {
		var method = elm_action.data('method');
	} else {
		var method = match[2];
	}
	if (elm_action.data('module')) {
		var module = elm_action.data('module');
	} else {
		var module = match[1];
	}
	var command_id = match[3];
	
	var has_search = (elm_action.data('search') != undefined ? elm_action.data('search') : true);
	var value_search = (elm_action.data('search_settings') ? elm_action.data('search_settings') : '');
	var command_filter = elm_action.data('filter');
	var settings_filter = elm_action.data('filter_settings');
	var command_order = elm_action.data('order');
	
	var value_filter = COMMANDS.getData(elm_action);
	if (!value_filter) {
		value_filter = {};
		COMMANDS.setData(elm_action, value_filter);
	}
	if (settings_filter && typeof value_filter === 'object') {
		$.extend(value_filter, settings_filter);
	}
	
	var delay = (elm_action.data('delay') ? elm_action.data('delay') : false);
	
	// Build
					
	var elm_container = $('<div class="datatable"></div>').insertBefore(elm_action);
	
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
	
	ASSETS.getIcons(elm_action, ['prev', 'next', 'updown', 'updown-up', 'updown-down'], function(data) {
		
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
			
			resizeDataTable(elm_action);
			
			func_column_icons = false;
		};
		
		func_column_icons();
	});
	
	elm_action.appendTo(elm_container);
	
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
		ASSETS.getIcons(elm_action, ['filter'], function(data) {
			elm_filter[0].children[0].innerHTML = data.filter;
		});
		if (settings_filter) {
			elm_filter.addClass('active');
			SCRIPTER.triggerEvent(elm_filter, 'filteranimate');
		}
		elm_filter.insertAfter(elm_search);
		
		elm_filter.on('command', function() {
			
			COMMANDS.setData(elm_filter, COMMANDS.getData(elm_action), true);
		});
		COMMANDS.setTarget(elm_filter, function(data) {
			
			COMMANDS.setData(elm_action, (data.filter ? data.filter : {}), false);
			
			SELF.refresh();
			
			elm_filter.toggleClass('active', (data.active ? true : false));
			SCRIPTER.triggerEvent(elm_filter, 'filteranimate');
			
			//moveScroll(elm_action, {if_visible: true});
		});
	}
	
	var elm_order = null;
		
	if (command_order) {
		
		elm_order = $('<button type="button" title="Set Ordering" id="'+command_order+'" class="popup" value=""><span class="icon"></span></button>');
		
		ASSETS.getIcons(elm_action, ['updown'], function(data) {
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
	
	var elm_header = elm_action.children('thead').children('tr');
	var elms_column = elm_header.children('th');
	var elm_body = elm_action.children('tbody');
	
	tweakDataTable(elm_action);
	
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
		
		elm_search.on('input', function() {
			
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
		
		if (this.classList.contains('disable-sort') || elm_action[0].loading) {
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
		
		const arr = {};
		
		elms_column = elm_header.children('th');
		
		for (let i = 0; i < arr_settings.num_columns; i++) {
			
			const elm_column = elms_column[i];
			
			const arr_column = {disable_sort: false, text: '', title: ''};
			
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
			
		if (elm_action[0].dataset.pause == 1) {
			return;
		}
	
		var func_call = function() {
		
			if (timer_delay) {
				
				elm_search.removeClass('waiting');
				timer_delay = false;
				str_delay = false;
			}

			const do_abort = (what === 'search' ? true : false);

			if (do_abort) {
				FEEDBACK.stop(elm_action);
			}
			if (!FEEDBACK.start(elm_action)) {
				return;
			}
			
			// Copy settings to formalise the new state when everything has gone according to plan
			
			const arr_settings_new = {};
		
			for (const key in arr_settings) {
				arr_settings_new[key] = arr_settings[key];
			}
			
			let num_start = 0;

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
				
				for (let i = 0; i < arr_settings.num_columns; i++) {
					
					const elm_column = elms_column[i];
					
					arr_settings_new['sort_column_'+i] = (elm_column.classList.contains('disable-sort') ? false: true);
					arr_settings_new['search_column_'+i] = true;
					
					const str_sort = elm_column.dataset.sort_new;
					
					if (str_sort) {
						
						const arr_sort = str_sort.split('-');
						const index = arr_sort[1];
						const direction = arr_sort[0];
						
						if (index == 0) {
							
							// For easy access to first sorted column
							
							arr_settings_new['sorting_column_0'] = i;
							arr_settings_new['sorting_direction_0'] = direction;
						}
						
						arr_settings_new.arr_order_column[index] = [i, direction];
					}
				}
			}
			
			const arr_request = COMMANDS.prepare(elm_action, $.extend({mod: getModID(elm_action), method: arr_command.method, id: arr_command.command_id, module: arr_command.module, value: arr_command.value}, arr_settings_new));
			
			FEEDBACK.request(elm_action, elm_action, {
				type: 'POST',
				contentType: arr_request.contentType,
				dataType: 'json',
				url: LOCATION.getURL('command'),
				data: arr_request.data,
				processData: false,
				context: elm_action,
				error: function() {
					
					do_sort = false;							
				},
				success: function(json) {

					FEEDBACK.check(elm_action, json, function() {
						
						arr_settings = arr_settings_new;

						if (do_sort) {
							
							for (let i = 0; i < arr_settings.num_columns; i++) {
					
								const elm_column = elms_column[i];
								
								if (elm_column.dataset.sort_new) {
									
									elm_column.dataset.sort = elm_column.dataset.sort_new;
									delete elm_column.dataset.sort_new;
								} else if (elm_column.dataset.sort) {
									
									delete elm_column.dataset.sort;
								}
							}

							do_sort = false;
						}
						
						const data = json.data;
						
						if (data) {
						
							func_draw(data);
							
							SCRIPTER.runDynamic(elm_action);
							SCRIPTER.triggerEvent(elm_action, 'ajaxloaded', {elm: elm_action});
							SCRIPTER.triggerEvent(elm_action, 'commandfinished');
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
		
		const num_records = parseInt(data.total_records);
		const num_records_filtered = parseInt(data.total_records_filtered);
		
		const num_records_end = ((arr_settings.num_records_start + arr_settings.num_records_length) > num_records_filtered || arr_settings.num_records_length == -1 ? num_records_filtered : (arr_settings.num_records_start + arr_settings.num_records_length));
		
		arr_settings.num_records = num_records_filtered;
		
		const num_pages = (arr_settings.num_records_length != -1 ? Math.ceil(num_records_filtered / arr_settings.num_records_length) : 1);
		const num_page_active = (arr_settings.num_records_start / arr_settings.num_records_length) + 1;
		
		const str_thousands = ' '; // MEDIUM MATHEMATICAL SPACE
		let str_count = '';
		
		if (!num_records_filtered) {
			if (num_records) {
				str_count = '<span>No results</span><span class="count-from"> from '+formatNumber(num_records , 0, str_thousands, false)+(data.total_records_info ? data.total_records_info : '')+'</span>';
			} else {
				str_count = '<span>None</span>';
			}
		} else {
			str_count = formatNumber((arr_settings.num_records_start + 1), 0, str_thousands, false)+' - '+formatNumber(num_records_end , 0, str_thousands, false);
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
		
		const mode_buttons = (num_pages <= num_buttons_total || num_page_active <= (num_buttons_total - 2) ? 'start' : (num_page_active > num_pages - (num_buttons_total - 2) ? 'end' : 'middle'));
		const num_buttons_max = (mode_buttons == 'middle' ? (num_buttons_middle + 2) : num_buttons_total);
		const num_buttons = (num_pages > num_buttons_max ? num_buttons_max : num_pages);
		
		elm_paginate[0].appendChild(elm_paginate_previous[0]);
					
		for (let i = 1; i <= num_buttons; i++) {
			
			if (i == num_buttons_max && num_pages > num_buttons_total && mode_buttons != 'end') {
				
				const elm_ellipsis = $('<span></span>')[0];
				elm_paginate[0].appendChild(elm_ellipsis);
			}
			
			let num_page = 1;
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
				
			const elm_button = $('<button type="button" value="'+num_page+'"'+(num_page_active == num_page ? ' class="selected"' : '')+'>'+formatNumber(num_page, 0, str_thousands, false)+'</button>')[0];
			elm_paginate[0].appendChild(elm_button);
			
			if (i == 1 && mode_buttons != 'start') {
				
				const elm_ellipsis = $('<span></span>')[0];
				elm_paginate[0].appendChild(elm_ellipsis);
			}
		}
		
		elm_paginate[0].appendChild(elm_paginate_next[0]);
		
		const elm_body_new = $('<tbody></tbody>');
		
		const arr_data = data.data;
		
		if (arr_data.length) {
									
			for (let i = 0, len = arr_data.length; i < len; i++) {

				const arr_row = arr_data[i];
				
				const elm_tr = $('<tr></tr>')[0];
				
				if (arr_row.id) {
					elm_tr.setAttribute('id', arr_row.id);
				}
				if (arr_row.class) {
					elm_tr.setAttribute('class', arr_row.class);
				}
				if (arr_row.attr) {
					
					const arr_row_attr = arr_row.attr;
					
					for (const key in arr_row_attr) {
						elm_tr.setAttribute(key, arr_row_attr[key]);
					}
				}
				
				for (let j = 0, len_j = arr_settings.num_columns; j < len_j; j++) {
					
					const elm_td = $('<td></td>')[0];
					
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
			
			const elm_tr = $('<tr><td colspan="'+arr_settings.num_columns+'" class="empty">No results.</td></tr>');

			elm_body_new[0].appendChild(elm_tr[0]);
		}
		
		elm_action[0].removeChild(elm_body[0]);
		elm_body = elm_body_new;
		
		elm_action[0].appendChild(elm_body[0]);
		
		tweakDataTable(elm_action);
		SCRIPTER.triggerEvent(elm_action, 'newpage');
	};

	func_load();
	
	elm_action.on('next', function() {
		func_load('next');
	}).on('prev', function() {
		func_load('previous');
	}).on('pause', function() {
		elm_action[0].dataset.pause = 1;
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
		
		const elm_header = elm_action.children('thead').children('tr');
		const elm_target = elm_header.children(selector);
		let num_index = null;
		
		if (content == false) {
			
			if (!elm_target.length) {
				return;
			}
			
			num_index = elm_target.index();
			arr_settings.num_columns--;
			
			elm_target.remove();
		} else {
			
			const elm_content = $(content);
			
			ASSETS.getIcons(elm_action, ['updown', 'updown-up', 'updown-down'], function(data) {
				
				elm_content[0].innerHTML += '<span class="icon"></span>';
				
				const elms_icon = elm_content[0].getElementsByClassName('icon');
				elms_icon[elms_icon.length-1].innerHTML = data.updown+data['updown-up']+data['updown-down'];
				
				resizeDataTable(elm_action);
			});
			
			if (elm_target.length) {
				
				if (elm_target[0].dataset.sort && !elm_content[0].dataset.sort) {
					elm_content[0].dataset.sort = elm_target[0].dataset.sort;
				}
				
				elm_target.replaceWith(elm_content); // Update column header
			} else {
				
				const elm_before = elm_header.children(selector_before);
				
				num_index = elm_before.index();
				arr_settings.num_columns++;
				
				elm_before.before(elm_content);
			}
		}

		// Fill or remove for visual feedback
		
		if (content == false || !elm_target.length) {
				
			const elms_tr = elm_action.children('tbody').children('tr');
			
			for (let i = 0, len = elms_tr.length; i < len; i++) {
			
				const elm_tr = elms_tr[i];
				
				if (content == false) {
					
					elm_tr.removeChild(elm_tr.childNodes[num_index]);
				} else {
					
					const elm_td = $('<td></td>')[0];
					
					elm_tr.insertBefore(elm_td, elm_tr.childNodes[num_index]);
				}
			}
		}
		
		resizeDataTable(elm_action);
		
		// Reset sorting
		
		if (num_index != null) {
			
			const elm_columns = elm_header.children('th');
			
			for (let i = num_index; i < arr_settings.num_columns; i++) {
				
				const elm_column = elm_columns[i];
				
				if (!elm_column.dataset.sort) {
					continue;
				}
				
				arr_settings.sort = true;
				elm_column.dataset.sort_new = elm_column.dataset.sort;
			}
		}
	};
	
	this.resetSort = function() {

		func_order({});
	};
	
	this.refresh = function() {
									
		SCRIPTER.triggerEvent(elm_action, 'command');
		
		if (COMMANDS.isAborted(elm_action)) {
			return;
		}
		
		elm_action[0].dataset.pause = 0;
		
		const match = elm_action.attr('id').match(/d:([^:]*):([^-]*)-(.*)/);
		
		let method = match[2];
		if (elm_action.data('method')) {
			method = elm_action.data('method');
		}
		let module = match[1];
		if (elm_action.data('module')) {
			module = elm_action.data('module');
		}
		const command_id = match[3];
		const value = (COMMANDS.getData(elm_action) ? COMMANDS.getData(elm_action) : '');
						
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
	
	const elm_target = $(e.target);
	
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
	
	const elm = $(this);
	const arr_options = {};
	
	if (elm.hasClass('del')) {
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
