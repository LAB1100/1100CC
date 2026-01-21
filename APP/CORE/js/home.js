
/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// READY

const func_content_loaded_home = function(e) {

	if (!getElement(e.detail.elm)) {
		return;
	}
	
	for (let i = 0, len = e.detail.elm.length; i < len; i++) {
			
		const elm = $(e.detail.elm[i]);
		
		runElementSelectorFunction(elm[0], 'textarea.body-content', function(elm_found) {
			new EditContent(elm_found, {
				inline: (elm_found.classList.contains('inline') ? true : false),
				class: 'body'
			});
		});
		
		runElementSelectorFunction(elm[0], 'textarea:not(.editor)', function(elm_found) {
			new ToolExtras(elm_found, {
				fullscreen: true
			});
		});
		
		runElementSelectorFunction(elm[0], '.pickcolor', function(elm_found) {
			new PickColor(elm_found, {
				alpha: elm_found.dataset.alpha
			});
		});
		
		elm.find('.image-effect').imageEffects();
	}
};

document.addEventListener('documentloaded', func_content_loaded_home);
document.addEventListener('ajaxloaded', func_content_loaded_home);

document.addEventListener('editorloaded', function(e) {
	
	new ToolExtras(e.detail.editor, {
		fullscreen: true,
		tools: true,
		class: (e.detail.editor.classList.contains('inline') ? 'editor-inline' : 'editor')
	});
});

$(document).on('click', '[id^=y\\\:cms_general\\\:set_language-]', function() {
	
	$(this).quickCommand();
}).on('click', 'img.enlarge', function() {

	if (this.closest('.album')) {
		return false;
	}
	
	const elm_source = this;
	
	const str_url = LOCATION.getOriginalURL(elm_source.getAttribute('src'));
	const elm_img = document.createElement('img');

	elm_img.src = str_url;
	if (elm_source.dataset.native_width) {
		elm_img.width = elm_source.dataset.native_width;
	}
	if (elm_source.dataset.native_height) {
		elm_img.height = elm_source.dataset.native_height;
	}
	
	new Overlay(document.body, elm_img, {sizing: 'fit-width'});
}).on('click', '.icon.a.info[title]', function() {
	
	let html_value = TOOLTIP.getTitle(this);
	const html_icon = this.innerHTML;
	
	html_value = '<div class=\"popup message\"><span class=\"icon\">'+html_icon+'</span><div>'+html_value+'</div></div>';
	
	let elm_overlay_target = this.closest('.mod');
	elm_overlay_target = (elm_overlay_target ? elm_overlay_target : document.body);
	
	new Overlay(elm_overlay_target, html_value, {position: 'middle'});
	
	return false;
});

// METHODS

// TOOLS

(function($) {
	
	var elm_preloaded = false;

	$.fn.imageEffects = function() {
		
		if (!elm_preloaded) {
			elm_preloaded = $('<div/>').appendTo('body'); // Dump after container which is not visible
		}
		
		return this.each(function() {
			
			var cur = $(this);
		
			var elm_img = cur.children('img');

			var perc_padding = ((parseFloat(elm_img.attr('data-padding'))*2)/parseFloat(elm_img.attr('data-width')))*100;
			elm_img.css({'maxWidth': 100+perc_padding+'%', 'margin': '-'+perc_padding/2+'%'});
			
			if (elm_img.attr('data-hover-src')) {
				var elm_img_hover = $('<img/>').attr('src', elm_img.attr('data-hover-src')).appendTo(elm_preloaded);
				var perc_padding_hover = ((parseFloat(elm_img.attr('data-hover-padding'))*2)/parseFloat(elm_img.attr('data-hover-width')))*100;
				
				elm_img.on('mouseover mouseleave', function() {
					var src = elm_img.attr('src');
					elm_img.attr('src', elm_img_hover.attr('src'));
					elm_img_hover.attr('src', src);
				}).on('mouseover', function() {
					elm_img.css({'maxWidth': 100+perc_padding_hover+'%', 'margin': '-'+perc_padding_hover/2+'%'});
				}).on('mouseleave', function() {				
					elm_img.css({'maxWidth': 100+perc_padding+'%', 'margin': '-'+perc_padding/2+'%'});
				});
			}
		});
	};
})(jQuery);

(function($) {
		
	var methods = {
		init : function(options) {
		
			return this.each(function() {
				
				var cur = $(this);
				var val = cur.attr('data-popper');
				
				if (cur.data('elm') || !val) {
					return;
				}
				
				var elm_popper = $('<div class="body tooltip popper">'+val+'</div>').appendTo('body');
				cur.data('elm', elm_popper);
				
				var pos = cur[0].getBoundingClientRect();
				var pos_popper = elm_popper[0].getBoundingClientRect();
				
				var height = pos.top+pos_popper.height;
				var height_max = POSITION.scrollTop()+window.innerHeight;
				var offset_y = (height > height_max ? -(height-height_max) : 0);
				elm_popper.css('top', pos.top + POSITION.scrollTop() + offset_y);
				
				var offset_x = (pos.left+pos.width+15+pos_popper.width > POSITION.scrollLeft()+window.innerWidth ? -pos_popper.width-15 : pos.width+15);
				elm_popper.css('left', pos.left + POSITION.scrollLeft() + offset_x);
			});
		},
		del : function(options) {
		
			return this.each(function() {
				
				var cur = $(this);
				var elm_popper = cur.data('elm');
				
				if (!elm_popper) {
					return;
				}
				
				cur.data('elm', false);
				elm_popper.remove();
			});
		}
	};
	
	$.fn.popper = function(methodOrOptions) {
		if (methods[methodOrOptions]) {
			return methods[methodOrOptions].apply($(this), Array.prototype.slice.call(arguments, 1));
		} else {
			return methods.init.apply($(this), arguments);
		}
	};
	
})(jQuery);

(function($) {
		
	var methods = {
		init : function(options) {
			
			var arr_options = $.extend({
				elm_parent: false,
				perc: 0
			}, options || {});
		
			return this.each(function() {
				
				var cur = $(this);
				var height_window = window.innerHeight;
				
				if (arr_options.perc) {
					var height = (height_window*arr_options.perc);
					if (arr_options.elm_parent) {
						height = height + (cur[0].offsetHeight - arr_options.elm_parent[0].offsetHeight);
					}
				} else { // Fill up to the max, need .con
					var height = height_window - (cur.closest('.con')[0].offsetHeight - cur[0].offsetHeight);
				}
				height = height - parseInt(cur.css('padding-top')) - parseInt(cur.css('padding-bottom'));
				
				var height_min = parseInt(cur.css('min-height'));
				var height_max = parseInt(cur.css('max-height'));
				if (height_min && height < height_min) {
					height = height_min;
				}
				if (height_max && height > height_max) {
					height = height_max;
				}
				
				cur.height(height);
			});
		}
	};
	
	$.fn.sizeToWindow = function(methodOrOptions) {
		if (methods[methodOrOptions]) {
			return methods[methodOrOptions].apply($(this), Array.prototype.slice.call(arguments, 1));
		} else {
			return methods.init.apply($(this), arguments);
		}
	};
	
})(jQuery);
