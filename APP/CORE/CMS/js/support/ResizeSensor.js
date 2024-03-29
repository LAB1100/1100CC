/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

/**
 * Copyright Marc J. Schmidt. See the LICENSE file at the top-level
 * directory of this distribution and at
 * https://github.com/marcj/css-element-queries/blob/master/LICENSE.
 */

function ResizeSensor(elm, callback, elm_insert) {
	
	function EventQueue() {
		
		var arr_q = [];
		
		this.add = function(call) {
			arr_q.push(call);
		};

		this.call = function() {
			
			for (let i = 0, j = arr_q.length; i < j; i++) {
				arr_q[i].call();
			}
		};

		this.remove = function(call) {
			
			const arr_q_new = [];
			
			for (let i = 0, j = arr_q.length; i < j; i++) {
				if (arr_q[i] !== call) {
					arr_q_new.push(arr_q[i]);
				}
			}
			
			arr_q = arr_q_new;
		};

		this.length = function() {
			return arr_q.length;
		};
	}

	function getComputedStyle(elm, str_property) {
		
		if (elm.currentStyle) {
			return elm.currentStyle[str_property];
		} else if (window.getComputedStyle) {
			return window.getComputedStyle(elm, null).getPropertyValue(str_property);
		} else {
			return elm.style[str_property];
		}
	}
	
	var elm = getElement(elm);
	
	const SELF = this;
	
	if (!elm.resizesensor) {
		
		elm.resizesensor = SELF;
		
		this.queue = new EventQueue();
		this.queue.add(callback);
	} else {
		
		elm.resizesensor.queue.add(callback);
		
		return elm.resizesensor;
	}

	this.element = document.createElement('div');
	this.element.className = 'resize-sensor';
	const str_style = 'position: absolute; left: 0; top: 0; right: 0; bottom: 0; overflow: hidden; z-index: -1; visibility: hidden;';
	const str_style_child = 'position: absolute; left: 0; top: 0; transition: 0s;';

	this.element.style.cssText = str_style;
	this.element.innerHTML =
		'<div class="resize-sensor-expand" style="'+str_style+'">'
			+'<div style="'+str_style_child+'"></div>'
		+'</div>'
		+'<div class="resize-sensor-shrink" style="' + str_style+'">'
			+'<div style="'+str_style_child+' width: 200%; height: 200%"></div>'
		+'</div>'
	;
	
	if (elm_insert) {
		elm.insertBefore(this.element, elm_insert);
	} else {
		elm.appendChild(this.element);
	}

	if (getComputedStyle(elm, 'position') == 'static') {
		elm.style.position = 'relative';
	}

	const elm_expand = this.element.childNodes[0];
	const elm_expand_child = elm_expand.childNodes[0];
	const elm_shrink = this.element.childNodes[1];
	
	var is_dirty = false;
	var frame_id = 0;
	var num_width_new = 0;
	var num_height_new = 0;
	var num_width = elm.offsetWidth;
	var num_height = elm.offsetHeight;

	this.reset = function() {
		
		elm_expand_child.style.width = '100000px';
		elm_expand_child.style.height = '100000px';

		elm_expand.scrollLeft = 100000;
		elm_expand.scrollTop = 100000;

		elm_shrink.scrollLeft = 100000;
		elm_shrink.scrollTop = 100000;
	};

	var func_resized = function() {
		
		frame_id = 0;

		if (!is_dirty) {
			return;
		}

		num_width = num_width_new;
		num_height = num_height_new;

		if (SELF.queue) {
		   SELF.queue.call();
		}
	};

	var func_scroll = function() {
		
		num_width_new = elm.offsetWidth;
		num_height_new = elm.offsetHeight;
		is_dirty = (num_width_new != num_width || num_height_new != num_height);

		if (is_dirty && !frame_id) {
			frame_id = window.requestAnimatorFrame(func_resized);
		}

		SELF.reset();
	};
	
	this.detach = function(func) {
		
		if (SELF.queue && typeof func == 'function') {
			
			SELF.queue.remove(func);
			
			if (SELF.queue.length()) {
				return;
			}
		}
		
		if (SELF.element.parentNode) {
			elm.removeChild(SELF.element);
		}
		
		delete elm.resizesensor;
	};
	
	SELF.reset();

	elm_expand.addEventListener('scroll', func_scroll);
	elm_shrink.addEventListener('scroll', func_scroll);
	
	return this;
}
