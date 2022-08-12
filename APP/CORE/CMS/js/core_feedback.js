
/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
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
			
			if (elm.request.obj_request) {
				elm.request.obj_request.abort();
			}
			
			var elm_context = elm.request.elm_context;
			
			if (elm_context) {
				
				var pos = elm_context.arr_requests.indexOf(elm);
				elm_context.arr_requests.splice(pos, 1);
			}
		}
		
		LOADER.stop(elm);
		
		if (elm.messagebox_identifiers) {

			MESSAGEBOX.clear({identifier: Object.values(elm.messagebox_identifiers)});
			
			elm.messagebox_identifiers = false;
		}
	};
	
	this.stopContext = function(elm_context) {
		
		var elm_context = getElement(elm_context);
		
		if (!elm_context.arr_requests) {
			return;
		}
		
		var arr_elms = [];
		
		for (var i = 0, len = elm_context.arr_requests.length; i < len; i++) {
			arr_elms.push(elm_context.arr_requests[i]);
		}
		
		for (var i = 0, len = arr_elms.length; i < len; i++) {
			obj.stop(arr_elms[i]);
		}
	}
	
	this.request = function(elm, elm_context, obj_request) {
		
		var elm = getElement(elm);
		var elm_context = getElement(elm_context);
		
		elm.request = {obj_request: obj_request, elm_context: elm_context};
		
		if (elm_context) {
			
			if (!elm_context.arr_requests) {
				elm_context.arr_requests = [];
			}
			
			if (elm_context.arr_requests.indexOf(elm) == -1) {
				elm_context.arr_requests.push(elm);
			}
		}
	}
	
	this.check = function(elm, json, callback) {
	
		var elm = getElement(elm);

		if (!onStage(elm) && !elm.keep_alive) {
			
			obj.stop(elm);
			return false;
		}
		
		if (json.timing) {
			console.log('1100CC server-side execution time: '+json.timing+' seconds.');
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
					
					LOCATION.replace(json.location.url, json.location.url_canonical, true);
				} else {
					
					LOCATION.push(json.location.url, json.location.url_canonical, true);
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
			
			obj.setFeedback(json.data_feedback, elm);

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
			
			if (PARSE) { // Document not officially loaded, wait before broadcast
				
				$(document).one('documentloaded', function() {
					obj.setFeedback({broadcast: data.broadcast}, elm);
				});
			} else {
				
				for (var i = 0, len = arr_listeners.length; i < len; i++) {
				
					if (!arr_listeners[i]) {
						continue;
					}
					
					arr_listeners[i](data.broadcast, elm);
				}
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
	
	this.addValidatorClassRules = function(str_class, arr_rules) {
		
		$.validator.addClassRules(str_class, arr_rules);
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

function WebService(host, port) {
	
	var SELF = this;
	
	this.host = host;
	this.port = port;

	var active = false;
	var socket = false;
	var keep_connection = false;
	var timeout_keep_connection = false;
	var func_keep_connection = function(state) {
		
		if (state === true || state === false) {
			
			keep_connection = state;
			clearTimeout(timeout_keep_connection);
			timeout_keep_connection = false;
			
			return;
		}
			
		if (!keep_connection || (keep_connection && timeout_keep_connection)) {
			return;
		}
		
		timeout_keep_connection = setTimeout(function() {
			SELF.listen();
			timeout_keep_connection = false;
		}, 2000);
	};
	var interval_keep_alive = false;
	var func_keep_alive = function(state) {
		
		if (state === false) {
			
			clearInterval(interval_keep_alive);
			interval_keep_alive = false;
		}
		
		if (interval_keep_alive) {
			return;
		}
		
		interval_keep_alive = setInterval(function() {
			SELF.send({alive: true});
		}, 60000);		
	};
				
	this.listen = function() {
		
		if (socket || !SELF.host) {
			return;
		}
						
		var host = (window.location.protocol == 'https:' ? 'wss:' : 'ws:')+'//'+SELF.host+(SELF.port ? ':'+SELF.port : '')+'/';

		socket = new WebSocket(host);
		
		active = false;
		func_keep_connection(true);
		
		SELF.opening();
			
		socket.onopen = function(e) {
		
			SELF.log('Connected.');
			
			active = true;
			SELF.opened();
			
			func_keep_alive(true);
		};
		
		socket.onmessage = function(e) {
								
			var data = JSON.parse(e.data);
			
			SELF.receive(data);
		};
		
		socket.onclose = function(e) {
		
			SELF.log('Disconnected.');

			socket = false;
			func_keep_alive(false);
			SELF.closed();
			
			func_keep_connection();
		};
		
		socket.onerror = function(e) {
			
		};
	};

	window.onbeforeunload = function() {
	
		if (!socket) {
			return;
		}
		
		socket.onclose = function () {}; // Disable onclose handler first
		socket.close();
	};
	
	this.isConnected = function() {
		
		if (socket && socket.readyState === socket.OPEN) {
			return true;
		}
		
		return false;
	};
		
	this.stop = function() {
	
		func_keep_connection(false);
		
		if (socket) {
		
			socket.close();
			socket = false;
		}
	};
	
	this.send = function(data) {
		
		if (!socket || !active) {
			return false;
		}
		
		socket.send(JSON.stringify(data));
		
		return true;
	};
	
	this.opening = function() {};
	this.opened = function() {};
	this.receive = function(data) {};
	this.closed = function() {};
	
	this.log = function(msg) {
		
		console.log('1100CC Webservice: '+msg);
	};
}

function WebServices() {
	
	var SELF = this;
	
	var obj_webservices = {};
	var obj_webservice_tasks = {};
	var obj_tasks_callbacks = {};
	
	this.register = function(host, port, task, arr_callbacks) {
		
		const webservice = getWebService(host, port, task);
		
		obj_tasks_callbacks[task] = arr_callbacks;
				
		obj_tasks_callbacks[task].send = function(data) {
			
			const arr_data = {arr_tasks: {}};
			arr_data.arr_tasks[task] = data;
			
			return webservice.send(arr_data);
		};
		
		if (!webservice.isConnected()) {
			
			webservice.listen();
		} else {
			
			registerTask(task);
		}
		
		return obj_tasks_callbacks[task].send;
	};
	
	this.unregister = function(host, port, task) {
		
		const str_identifier = host+'_'+port;
		
		const webservice = obj_webservices[str_identifier];
		
		if (webservice === undefined) {
			return;
		}
						
		if (obj_tasks_callbacks[task].closed) {
			obj_tasks_callbacks[task].closed();
		}
		
		delete obj_tasks_callbacks[task];
		delete obj_webservice_tasks[str_identifier][task];
				
		if (Object.keys(obj_webservice_tasks[str_identifier]).length === 0) {
			
			webservice.stop();
			delete obj_webservices[str_identifier];
		}
	}
	
	var registerTask = function(task) {
				
		var arr_task = obj_tasks_callbacks[task];
				
		arr_task.send({arr_options: {}}); // Register task at the server
					
		if (arr_task.opened) {
			arr_task.opened();
		}
	};
	
	var getWebService = function(host, port, task) {
		
		var str_identifier = host+'_'+port;
		var webservice = obj_webservices[str_identifier];
				
		if (webservice !== undefined) {
			
			obj_webservice_tasks[str_identifier][task] = true;
			
			return webservice;
		}

		webservice = new WebService(host, port);
		
		webservice.opening = function() {
						
			for (const task in obj_tasks_callbacks) {
				
				const callback = obj_tasks_callbacks[task].opening;
				
				if (callback) {
					callback();
				}
			}
		};
		
		webservice.opened = function() {
						
			for (const task in obj_tasks_callbacks) {
				
				registerTask(task);				
			}
		};
		
		webservice.receive = function(data) {
						
			for (const task in data) {
				
				const callback = obj_tasks_callbacks[task].receive;
				
				if (callback) {
					callback(data[task]);
				}
			}
		};
		
		webservice.closed = function() {
						
			for (const task in obj_tasks_callbacks) {
				
				const callback = obj_tasks_callbacks[task].closed;
				
				if (callback) {
					callback();
				}
			}			
		};
		
		obj_webservices[str_identifier] = webservice;
		
		obj_webservice_tasks[str_identifier] = {};
		obj_webservice_tasks[str_identifier][task] = true;
		
		return webservice;
	};
}
var WEBSERVICES = new WebServices();

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
