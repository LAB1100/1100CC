
/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// READY

$(document).on('documentloaded ajaxloaded', function(e) {
	
	if (!getElement(e.detail.elm)) {
		return;
	}
	
	for (var i = 0, len = e.detail.elm.length; i < len; i++) {
			
		var elm = $(e.detail.elm[i]);
		
		runElementSelectorFunction(elm, '[id^=tabs]', function(elm_found) {
			elm_found.removeAttribute('id');
			elm_found.classList.add('tabs');
			new NavigationTabs(elm_found);
		});
		
		runElementSelectorFunction(elm, 'textarea.editor', function(elm_found) {
			new EditContent(elm_found, {
				inline: (elm_found.classList.contains('inline') ? true : false),
				external: (elm_found.classList.contains('external') ? true : false),
				class: 'body'
			});
		});
	}
}).on('editorloaded', function(e) {
	
	new ToolExtras(e.detail.editor, {
		fullscreen: true,
		tools: (e.detail.content.classList.contains('body-content') ? true : false),
		class: (e.detail.editor.classList.contains('inline') ? 'editor-inline' : 'editor')
	});
});
