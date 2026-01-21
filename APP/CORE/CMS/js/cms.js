
/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// READY

const func_content_loaded_cms = function(e) {
	
	if (!getElement(e.detail.elm)) {
		return;
	}
	
	for (let i = 0, len = e.detail.elm.length; i < len; i++) {
			
		const elm = $(e.detail.elm[i]);
		
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
};

document.addEventListener('documentloaded', func_content_loaded_cms);
document.addEventListener('ajaxloaded', func_content_loaded_cms);

document.addEventListener('editorloaded', function(e) {
	
	new ToolExtras(e.detail.editor, {
		fullscreen: true,
		tools: (e.detail.content.classList.contains('body-content') ? true : false),
		class: (e.detail.editor.classList.contains('inline') ? 'editor-inline' : 'editor')
	});
});
