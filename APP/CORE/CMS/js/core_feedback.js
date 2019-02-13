
/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

function Feedback() {
	
	var obj = this,
	arr_store = {},
	arr_listeners = [];
	
	this.start = function(elm) {
		
		var elm = getElement(elm);

		if (elm.loading) {
			return false;
		}
		
		LOADER.start(elm);
		
		return true;
	};
	
	this.stop = function(elm) {
	
		var elm = getElement(elm);
		
		if (elm.request) {
			elm.request.abort();
		}
		
		LOADER.stop(elm);
		
		if (elm.messagebox_identifiers) {

			MESSAGEBOX.clear({identifier: Object.values(elm.messagebox_identifiers)});
			
			elm.messagebox_identifiers = false;
		}
	};
	
	this.check = function(elm, json, callback) {
	
		var elm = getElement(elm);

		if (!onStage(elm) && !elm.keep_alive) {
			
			obj.stop(elm);
			return false;
		}
		
		var msg = json.msg;
		var msg_type = json.msg_type;
		
		var arr_msg_options = (json.msg_options ? json.msg_options : {});
		arr_msg_options.duration = (arr_msg_options.duration !== undefined ? arr_msg_options.duration : (msg_type == 'status' ? 3000 : 5000));
		arr_msg_options.identifier = (arr_msg_options.identifier !== undefined ? arr_msg_options.identifier : false);

		if (json.location) {
			
			if (json.location.reload) {
				
				LOCATION.reload(json.location.url, (msg ? arr_msg_options.duration : false));
			} else {
				
				if (json.location.replace) {
					
					LOCATION.replace(json.location.url, true);
				} else {
					
					LOCATION.push(json.location.url, true);
				}
			}
		}
		
		// Messages
		
		if (arr_msg_options.clear) {
			
			MESSAGEBOX.clear(arr_msg_options.clear);
		}
		
		if (msg_type == 'status') { // Indication there could be a longer running process: show the loader as soon as possible
			
			LOADER.show(elm);
		} else {
			
			LOADER.stop(elm);
		}
		
		if (msg) {
			
			MESSAGEBOX.add({msg: msg, type: msg_type, method: 'append', duration: arr_msg_options.duration, persist: arr_msg_options.persist, identifier: arr_msg_options.identifier});
			
			if (arr_msg_options.identifier) {
				
				if (!elm.messagebox_identifiers) {
					elm.messagebox_identifiers = {};
				}
				
				elm.messagebox_identifiers[arr_msg_options.identifier] = arr_msg_options.identifier;
			}
		}
		
		if (LOCATION.hasChanged() || msg_type == 'alert') {
			return false;
		}
		
		// Continue
		
		if (json.data_feedback) {
			
			if (PARSE) { // Document not officially loaded, wait for it
				
				$(document).one('documentloaded', function() {
					obj.setFeedback(json.data_feedback, elm);
				});
			} else {
				
				obj.setFeedback(json.data_feedback, elm);
			}
			
			if (LOCATION.hasChanged()) {
				return false;
			}
		}
		
		if (json.validate) {
			json.validate = loopJSONFunctionEval(json.validate);
		}
		
		if (msg_type == 'status') {
			return true;
		}
		
		if (typeof callback === 'function') {
			callback();
		} else {
			return true;
		}
	};
	
	this.listen = function(call, key) {
		
		if (key === 0 || key > 0) {
			
			arr_listeners[key] = call;
		} else {
			
			for (var i = 0, len = arr_listeners.length; i <= len; i++) {
				
				if (arr_listeners[i] === null || arr_listeners[i] === undefined) {
					
					var key = i;
					arr_listeners[key] = call;
					break;
				}
			}
		}

		return key;
	};
		
	this.setFeedback = function(data, elm) {
		
		if (data.store) {
			
			for (var key in data.store) {
				arr_store[key] = data.store[key];
			}
		}
		
		if (data.broadcast) {
			
			for (var i = 0, len = arr_listeners.length; i < len; i++) {
				
				if (!arr_listeners[i]) {
					continue;
				}
				
				arr_listeners[i](data.broadcast, elm);
			}
		}
	};
	
	this.getFeedback = function() {
		
		return arr_store;
	};
	
	var initValidator = function() {
			
		$.validator.classRuleSettings = []; // Ignore classes on elements.
		$.validator.setDefaults({
			onfocusout: false,
			onkeyup: false,
			onclick: false,
			ignore: '',
			errorPlacement: function(error, element) {},
			showErrors: function(errorMap, errorList) {},
			errorClass: 'input-error',
			highlight: function(element, errorClass) {
				element.classList.add(errorClass);
			},
			unhighlight: function(element, errorClass) {
				element.classList.remove(errorClass);
			},
			invalidHandler: function(e, validator) {
				
				obj.stop(this);
				
				var num_errors = validator.numberOfInvalids();
				
				if (!num_errors) {
					return;
				}
				
				if (num_errors == 1) {	
					var str_msg = 'The form you would like to submit contains an invalid or missing value.';
				} else {
					var str_msg = 'The form you would like to submit contains '+num_errors+' invalid or missing values.';
				}
				
				MESSAGEBOX.add({msg: '<ul><li><label></label><span>'+str_msg+'</span></li></ul>', type: 'attention', method: 'append', duration: 5000});

				validator.defaultShowErrors();
				
				var elm_error = validator.errorList[0].element;
				
				if (elm_error && elm_error.closest('.tabs')) {
					
					var elms_focus = $(elm_error).parents('.tabs > div');
					
					elms_focus.each(function() {
						
						var elm_content = this;
						var elm_tabs = this.parentNode;
							
						if (!elm_tabs.navigationtabs) {
							return;
						}
					
						elm_tabs.navigationtabs.focusContent(elm_content);
					});
				}
			}
		});
	};
	
	this.addValidatorMethod = function(identifier, func) {
			
		$.validator.addMethod(identifier, func);
	};
	
	initValidator();
}
var FEEDBACK = new Feedback();

function Loader() {
	
	var obj = this;
	
	this.start = function(elm) {
		
		var elm = getElement(elm);
		
		var obj_loader = elm.loader;
		
		if (!obj_loader) {
		
			obj_loader = {
				loading: false,
				elm: false,
				timeout: false,
				timer: false,
				updated: 0
			};
			
			elm.loader = obj_loader;
		}
		
		if (obj_loader.loading) {
			return;
		}
		
		obj_loader.loading = true;
		elm.loading = true;
	
		obj_loader.timeout = setTimeout(function() {
			obj.show(elm);
		}, 400);
		
		obj_loader.updated = 0;
		
		obj_loader.timer = setInterval(function() {
			
			if (!onStage(elm) && !elm.keep_alive) {
				
				FEEDBACK.stop(elm);
				return;
			}
			
			if (!elm.keep_alive) {
				
				obj_loader.updated += 1;
			}
			
			if (obj_loader.updated == 240) { // Timeout connection after x seconds
				
				FEEDBACK.stop(elm);
				FEEDBACK.check(elm, {msg_type: 'alert', msg: '<ul><li><label></label><span>Connection timed out.</span></li></ul>'});
			}
		}, 1000);
	};
	
	this.show = function(elm) {
		
		var elm = getElement(elm);
		
		var obj_loader = elm.loader;
		
		if (obj_loader.elm) {
			return;
		}
		
		if (obj_loader.timeout) {
			clearTimeout(obj_loader.timeout);			
		}
				
		obj_loader.elm = MESSAGEBOX.add({msg: '<span></span>', counter: false, type: 'loading', duration: 0, persist: true, method: 'prepend'});
	};
	
	this.stop = function(elm) {
		
		var elm = getElement(elm);
		
		var obj_loader = elm.loader;
		
		if (!obj_loader || !obj_loader.loading) {
			return;
		}
		
		obj_loader.loading = false;
		elm.loading = false;
		
		clearTimeout(obj_loader.timeout);
		clearInterval(obj_loader.timer);

		if (obj_loader.elm) {
			
			MESSAGEBOX.end(obj_loader.elm);
			
			obj_loader.elm = false;
		}
	};
	
	this.keepAlive = function(elm, keep_alive) {
		
		var elm = getElement(elm);
		
		elm.keep_alive = (keep_alive === false ? false : true);
	};
	
	this.reset = function(elm) {
		
		var elm = getElement(elm);
		
		var obj_loader = elm.loader;
		
		if (!obj_loader) {
			return;
		}
			
		obj_loader.updated = 0;
	};
}
var LOADER = new Loader();

(function($) {
	
	$(document).ajaxError(function(e, xhr, settings, exception) {
		
		if (exception == 'abort') { // Abort is invoked client side, already handled
			return;
		}
		
		var elm = settings.context;
		
		if (xhr.responseText) {
			
			try {
				var json = JSON.parse(xhr.responseText);
			} catch(e) { }
			if (json) {
				FEEDBACK.check(elm, json);
				return;
			}
		}
		
		if (exception) {
			var msg = '<ul><li><label></label><span>Connection error: '+exception+'.</span></li></ul>';
		} else {
			var msg = '<ul><li><label></label><span>Connection lost.</span></li></ul>';
		}
		
		FEEDBACK.check(elm, {msg_type: 'alert', msg: msg});
	}).ajaxSend(function(e, xhr, settings) {
		
		xhr.setRequestHeader('1100CC-Status', '1');
	});
	    
    var orgXHR = $.ajaxSettings.xhr;
    
    $.ajaxSetup({
		xhr: function() {
			
			// Patch ajax settings to call a progress callback
			
			var xhr = orgXHR();
			
			if (xhr instanceof window.XMLHttpRequest) {
				xhr.addEventListener('progress', this.progress, false);
			}
			
			if (xhr.upload) {
				xhr.upload.addEventListener('progress', this.uploadProgress, false);
			}
			
			return xhr;
		}
    });

	$.ajaxPrefilter(function(options, options_original, jqXHR) {
		
		var is_buffering = true;
		var arr_response = false;
		var str_response = '';
		var pos_processed = 0;
		var str_processed = '';
		
		var str2Bytes = function(str) {

			var bytes = 0;
			
			for (var i = 0, len = str.length; i < len; i++) {
				
				var c = str.charCodeAt(i);
				
				bytes += c < (1 <<  7) ? 1 :
						   c < (1 << 11) ? 2 :
						   c < (1 << 16) ? 3 :
						   c < (1 << 21) ? 4 :
						   c < (1 << 26) ? 5 :
						   c < (1 << 31) ? 6 : Number.NaN;
			}
			
			return bytes;
		};
		
		options.uploadProgress = function(e) {
			
			if (!options_original.uploadProgress) {
				return;
			}
			
			var position = e.loaded;
			var total = e.total;
			var percent = (position / total) * 100;
			
			options_original.uploadProgress(e, position, total, percent);
		};
		
		// Processing: XHR.readyState = 2/3 (parsing request) or 4 (full request)
		
		options.progress = function(e) {
			
			if (!(e.currentTarget instanceof window.XMLHttpRequest)) { // The send request progress
				return;
			}

			if (!e.currentTarget.responseText) {
				return;
			}
			
			arr_response = e.currentTarget;
			
			if (is_buffering) { // Make sure we have an fully processed first response and no buffer issues (IE)

				var str_check = arr_response.responseText;
				
				if (str_check.indexOf('[-PROCESS]') !== -1) {
					
					is_buffering = false;
				} else if (str_check.indexOf('{') !== -1) { 
					
					is_buffering = false;
				}

				if (is_buffering) {
					
					setTimeout(function() { // Wait a bit for the first response to get ready
						update();
					}, 50);
				} else {
					
					update();
				}						
			} else {
				
				update();
			}
		}
		
		var update = function() {
			
			var elm = options.context;
			
			LOADER.reset(elm);

			if (arr_response.responseText && (arr_response.responseText.length > str_response.length)) {
				
				str_response = arr_response.responseText;
				
				parse();
			}
		};
		
		var parse = function() {
			
			var arr_pos_delimiters = [];
			
			var pos_search = pos_processed;
			var pos_delimiter_found = str_response.indexOf('[-PROCESS]', pos_search);
			
			while (pos_delimiter_found !== -1) {
				
				var pos_delimiter_open = str_response.indexOf('[PROCESS]', pos_search);
				
				arr_pos_delimiters.push([pos_delimiter_open, pos_delimiter_found + 10]);
				
				pos_search = pos_delimiter_found + 10;
				pos_delimiter_found = str_response.indexOf('[-PROCESS]', pos_search);
			}
			
			for (var i = 0, len = arr_pos_delimiters.length; i < len; i++) {
				
				var arr_pos_delimiter = arr_pos_delimiters[i];
				
				var pos_start = arr_pos_delimiter[0];
				var pos_stop = arr_pos_delimiter[1];
				
				if (pos_start > pos_processed) {
					
					str_processed += str_response.substring(pos_processed, pos_start);
				}
				
				var str = str_response.substring(pos_start + 9, pos_stop - 10);
				
				pos_processed = pos_stop;
				
				try {
					
					var json = JSON.parse(str);
				} catch(e) { }
				
				if (json) {
					
					var elm = options.context;
					
					FEEDBACK.check(elm, json);
				}
			}
		}
		
		// Processed: XHR.readyState = 4
		
		options.dataFilter = function(data, type) {
			
			if (data) {
				
				str_response = data+'[PROCESS][-PROCESS]';

				parse();

				return str_processed;
			}
			
			return false;
		};
	});
})(jQuery);
