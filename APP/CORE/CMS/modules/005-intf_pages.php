<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class intf_pages extends pages {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_pages');
		static::$parent_label = '';
	}
	
	public static function webLocations() {}
		
	public function contents() {
	
		$directory_id = directories::getRootDirectory();
	
		$return = '<div class="section pages"><h1 id="x:intf_pages:page-0"><span>'.self::$label.'</span><input type="button" class="data add popup page_add" value="add" /></h1>
		<div>';
			
			$parent_options = directories::createDirectoriesDropdown(directories::getDirectories(), $directory_id);
			
			$return .= '<div class="options">'
				.'<label>'.getLabel('lbl_directory').':</label><select name="parent" id="y:intf_pages:get_directory-0">'.$parent_options.'</select>'
				.'<label>'.getLabel('lbl_internal_tag').':</label><select name="tag" id="y:intf_pages:get_tag-0">'.cms_general::createDropdown(cms_general::getTags(DB::getTable('TABLE_PAGES'), DB::getTable('TABLE_PAGE_INTERNAL_TAGS'), 'page_id', true), 0, true).'</select>
			</div>
			'.$this->createDirectoryPage($directory_id);
			
		$return .= '</div></div>';
		
		return $return;
	}
	
	private function createDirectoryPage($directory_id, $tag_id = false) {
	
			$res = DB::query("SELECT
				p.*,
				t.preview,
				COUNT(m.page_id) AS module_count,
				".DBFunctions::sqlImplode("CASE
					WHEN m.shortcut != '' THEN CONCAT(
						m.module, ' (shortcut: ', m.shortcut, ', root: ', CASE
							WHEN m.shortcut_root IS TRUE THEN 'yes'
							ELSE 'no'
						END, ')'
					)
					ELSE m.module
				END", '<br />')." AS installed,
				pm.name AS master_name,
				CASE
					WHEN f.page_fallback_id != 0 THEN TRUE
					ELSE FALSE
				END AS is_fallback,
				CASE
					WHEN i.page_index_id != 0 THEN TRUE
					ELSE FALSE
				END AS is_index
					FROM ".DB::getTable('TABLE_PAGES')." p
					LEFT JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.page_id = p.id)
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." pm ON (pm.id = p.master_id)
					".($tag_id ? "JOIN ".DB::getTable('TABLE_PAGE_INTERNAL_TAGS')." pt ON (pt.page_id = p.id)" : "")."
					LEFT JOIN ".DB::getTable('TABLE_PAGE_TEMPLATES')." t ON (t.id = CASE 
						WHEN p.template_id != 0 THEN p.template_id
						WHEN pm.template_id != 0 THEN pm.template_id
						ELSE (SELECT template_id FROM ".DB::getTable('TABLE_PAGES')." WHERE id = pm.master_id)
					END)
					LEFT JOIN ".DB::getTable('TABLE_DIRECTORIES')." f ON (f.page_fallback_id = p.id)
					LEFT JOIN ".DB::getTable('TABLE_DIRECTORIES')." i ON (i.page_index_id = p.id)
				WHERE p.directory_id = ".(int)$directory_id."
					".($tag_id ? "AND pt.tag_id = ".(int)$tag_id : "")."
				GROUP BY p.id, pm.id, t.id, f.page_fallback_id, i.page_index_id
				ORDER BY sort
			");
			
			$return = '<ul class="pages-overview">';
						
				if ($res->getRowCount() == 0) {
					
					$return .= '<section class="info">'.getLabel('msg_no_pages').'</section>';
				} else {

					$return .= '<input type="hidden" id="y:intf_pages:pages_sort-'.$directory_id.'" name="sort" value="" />';
														
					while ($arr_row = $res->fetchAssoc()) {
						
						$arr_row['publish'] = DBFunctions::unescapeAs($arr_row['publish'], DBFunctions::TYPE_BOOLEAN);
						$arr_row['clearance'] = DBFunctions::unescapeAs($arr_row['clearance'], DBFunctions::TYPE_BOOLEAN);
						$arr_row['is_fallback'] = DBFunctions::unescapeAs($arr_row['is_fallback'], DBFunctions::TYPE_BOOLEAN);
						$arr_row['is_index'] = DBFunctions::unescapeAs($arr_row['is_index'], DBFunctions::TYPE_BOOLEAN);
					
						$return .= '<li id="x:intf_pages:page_id-'.$arr_row['id'].'" class="object'.($arr_row['is_fallback'] ? ' fallback' : ($arr_row['is_index'] ? ' index' : '')).'">';
							if (!$tag_id) {
								$return .= '<div class="handle"><span class="icon">'.getIcon('handle-grid').'</span></div>';
							}
							$return .= '<div class="title"><h3><a target="_blank" href="'.pages::getPageURL($arr_row).'">'.$arr_row['name'].'</a></h3></div>';
							if ($arr_row['preview']) {
								$return .= '<div class="template-preview">'.$arr_row['preview'].'</div>';
							}
							$return .= '<div class="object-info">';
							$return .= '<span class="icon'.($arr_row['publish'] ? ' selected' : '').'" title="'.($arr_row['publish'] ? getLabel('inf_publish_in_header') : getLabel('inf_publish_in_header_not')).'">'.getIcon('globe').'</span>';
							$return .= '<span class="icon'.($arr_row['clearance'] ? ' selected' : '').'" title="'.($arr_row['clearance'] ? getLabel('inf_clearance_required') : getLabel('inf_clearance_required_not')).'">'.getIcon('clearance').'</span>';
							if ($arr_row['url']) {
								$return .= '<span class="icon selected" title="URL: '.$arr_row['url'].'">'.getIcon('link').'</span>';
							}
							$return .= '</div>';
							if (!$arr_row['url']) {
								$return .= '<div class="object-info vertical">';
								$return .= '<span class="icon'.($arr_row['master_name'] ? ' selected' : '').'" title="'.getLabel('lbl_master_page').': <br />'.($arr_row['master_name'] ?: getLabel('inf_none')).'">'.getIcon('pages').'</span>';
								$return .= '<span class="icon'.($arr_row['installed'] ? ' selected' : '').'" title="'.getLabel('lbl_modules_installed').': <br />'.($arr_row['installed'] ?: getLabel('inf_none')).'">'.getIcon('modules').'</span><span class="modules-count selected">'.$arr_row['module_count'].'</span>';
								$return .= '</div>';
							}
							$return .= '<div class="object-info del-edit">'.(!$arr_row['is_fallback'] && !$arr_row['is_index'] ? '<input type="button" class="data del msg page_del" value="del" />' : '').'<input type="button" class="data edit popup page_edit" value="edit" /></div>
						</li>';
					}
				}
			
			$return .= '</ul>';
			
			return $return;
	}
	
	public static function css() {
	
		$return = '
				#mod-intf_pages .pages-overview { margin: 7px; }
				#mod-intf_pages .pages-overview > .object { margin: 3px; width: 150px; height: 145px; }
				#mod-intf_pages .pages-overview > .object .title { margin: 10px 5px 0px 5px; text-align: center; }
				#mod-intf_pages .pages-overview > .object .title h3 { display: inline; font-weight: bold; font-size: 14px; line-height: 14px; vertical-align: middle; white-space: nowrap;}
				#mod-intf_pages .pages-overview > .object .title a { color: #000000; }
				#mod-intf_pages .pages-overview > .object .template-preview { display: inline-block; width: 75px; height: 75px; margin: 0px auto; margin-top: 5px; vertical-align: top; }
				#mod-intf_pages .pages-overview > .object .object-info.vertical { position: relative; display: inline-block; left: 8px; top: 5px; }
				#mod-intf_pages .pages-overview > .object .object-info.vertical .modules-count { font-size: 10px; line-height: 10px; height: 10px; padding: 0px; }
				#mod-intf_pages .pages-overview > .object .object-info.del-edit { left: 6px; right: auto; }
				
				#frm-page { white-space: nowrap; }
				#frm-page > * { white-space: normal; }
				#frm-page > fieldset { display: inline-block; }
				#frm-page > fieldset > ul .templates { max-width: 198px; }
				#frm-page > fieldset > ul textarea { width: 198px; }
				
				#frm-page #mod-select { display: inline-block; vertical-align: top; margin-left: 25px; padding: 10px; width: 250px; height: 250px; background-color: #f5f5f5; }
				#frm-page #mod-select .template-preview { width: inherit; height: inherit; }
				#frm-page #mod-select .template-preview td[colspan]:hover, .template-preview td.active { background-color: #4c8efa; }
				
				#frm-page #mod-list { display: inline-block; vertical-align: top; margin-left: 25px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-intf_pages', function(elm_scripter) {

					elm_scripter.on('change', 'select[name=parent]', function() {
						var cur = $(this);
						cur.data('value', {directory_id: cur.val(), tag: elm_scripter.find('select[name=tag]').val()});
						cur.quickCommand(elm_scripter.find('.pages-overview'), {html: 'replace'});
					})
					.on('change', 'select[name=tag]', function() {
						var cur = $(this);
						cur.data('value', {directory_id: elm_scripter.find('select[name=parent]').val(), tag: cur.val()});
						cur.quickCommand(elm_scripter.find('.pages-overview'), {html: 'replace'});
					})
					.on('mousedown', '[id^=\"x\\\:intf_pages\\\:new-\"]', function() {
						var cur = $(this);
						cur.attr('id', cur.attr('id').split('-')[0]+'-'+elm_scripter.find('select[name=parent]').val());
					})
					.on('command', '.page_add, .page_edit', function() {
						var elm_target = $(this).closest('[id^=x\\\:intf_pages\\\:page]');
						elm_target.data({value: {directory_id: elm_scripter.find('select[name=parent]').val(), tag: elm_scripter.find('select[name=tag]').val()}, target: elm_scripter.find('.pages-overview'), options: {html: 'replace'}});
					});
					
					SCRIPTER.runDynamic(elm_scripter.find('.pages-overview'));
				});
				
				SCRIPTER.dynamic('.section.pages', function(elm_scripter) {
				
					elm_scripter.data('is_dynamic', true);
					
					SCRIPTER.runDynamic(elm_scripter.find('.pages-overview'));
				});
				
				SCRIPTER.dynamic('.pages-overview', function(elm_scripter) {
				
					elm_scripter.find('> .object .title h3').each(function() {
						fitText(this);
					});
					
					new SortSorter(elm_scripter, {
						handle: '.handle',
						call_update: function (elm) {
							func_update_sort();
						}
					});
				
					var func_update_sort = function() {
					
						var arr_sort = {};
						elm_scripter.children('.object').each(function() {
							var id = $(this).attr('id').split('-')[1];
							arr_sort[id] = elm_scripter.children('.object').index(this);
						});
						elm_input = elm_scripter.children('input[name=sort]');
						elm_input.val($.param(arr_sort));
						elm_input.quickCommand(elm_input, false);
					};

					var elm_section = elm_scripter.closest('.section.pages');
					
					if (elm_section.data('is_dynamic')) {
						elm_section.data('is_dynamic', false);
						func_update_sort();
					}
				});
				
				SCRIPTER.dynamic('#frm-page', function(elm_scripter) {
					
					const elm_module_select = elm_scripter.find('#mod-select');
					const elm_module_list = elm_scripter.find('#mod-list');
					
					const elm_select = elm_module_list.find('fieldset > legend + ul select');
					const elm_shortcut = elm_module_list.find('fieldset > legend + ul + hr + ul');
					const elm_options = elm_module_list.find('fieldset > ul:last-child');
					const elm_apply = elm_module_list.find('fieldset + menu > [name=apply]');
					const elm_cancel = elm_module_list.find('fieldset + menu > [name=cancel]');
					
					var func_get_module_active = function() {
						
						return elm_module_select.find('.template-preview .active');
					};
					var func_close_module_active = function() {
						
						const elm_module_active = func_get_module_active();

						elm_module_active.removeClass('active');
						elm_module_list.hide();
					};
					
					// POPUP INPUT
					
					var func_hide_template_select = function() {
					
						func_close_module_active();

						var elm_master = elm_scripter.find('select[name=master_page]');
						var elm_target = elm_scripter.find('[name=template_select]').closest('li');
						if (elm_master.val()) {
							elm_target.hide();
							elm_scripter.find('[name=template_select]').prop('checked', false);
						} else {
							elm_target.show();
						}
					};
					var func_hide_mode_select = function() {
						
						var elm_url = elm_scripter.find('input[name=url]');
						var elm_target = elm_scripter.find('[name=template_select], select[name=master_page]').closest('li');
						
						if (elm_url.val() != '') {
							elm_target.hide();
							elm_module_select.hide();
							func_close_module_active();
						} else {
							elm_target.show();
							elm_module_select.show();
							func_hide_template_select();
						}
					};
					
					func_hide_template_select();
					func_hide_mode_select();
					
					elm_scripter.on('change', '[name=directory]', function() {
					
						$(this).quickCommand(function(html) {
							var elm_select_master_page = elm_scripter.find('select[name=master_page]');
							var master_page_value = elm_select_master_page.val();
							
							elm_select_master_page.html(html).val(master_page_value);
							
							if (master_page_value && !elm_select_master_page.val()) {
								elm_module_select.children('.template-preview').empty();
							}
						});
					}).on('keyup', 'input[name=url]', function() {
						func_hide_mode_select();
					}).on('change', 'select[name=master_page], [name=template_selected]', function() {
						$(this).quickCommand(elm_module_select.children('.template-preview'));
					}).on('change', 'select[name=master_page]', function() {
						func_hide_template_select();
					}).on('change', '[name=template_select]', function() {
						func_hide_template_select();
						elm_scripter.find('[name=template_selected]').val(elm_scripter.find('[name=template_select]:checked').val()).trigger('change');
					});
					
					// POPUP MOD CLICK
										
					elm_module_select.on('mouseup', '.template-preview td[id^=mod]', function() {
					
						var cur = $(this);

						elm_module_list.show();
						
						const elm_module_active = func_get_module_active();
						elm_module_active.removeClass('active');
						
						cur.addClass('active');

						if (cur.attr('mod')) {
						
							elm_select.val(cur.attr('mod'));
							SCRIPTER.triggerEvent(elm_select, 'change');
						
							var elm_target = elm_options.children('li[data-module=\"'+cur.attr('mod')+'\"]');
							const str_variable = cur.attr('var');
							
							if (str_variable) {
							
								if (str_variable.charAt(0) == '{') {
								
									var obj = JSON.parse(str_variable);
									for (var key in obj) {
										var elm_target_value = elm_target.find('[name=\"'+key+'\"]');
										if (elm_target_value.is('[type=checkbox]')) {
											elm_target_value.prop('checked', obj[key]);
										} else {
											elm_target_value.val(obj[key]);
										}
										if (elm_target_value.hasClass('unique')) {
											elm_target_value.siblings('[data-group='+elm_target_value.attr('data-group')+']').prop('disabled', obj[key]);
										}
									}
								} else {
								
									var elm_target_value = elm_target.find('input, select').first();
									if (elm_target_value.is('[type=checkbox]')) {
										elm_target_value.prop('checked', str_variable);
									} else {
										elm_target_value.val(str_variable);
									}
								}
							}
							
							// Trigger state change
							runElementSelectorFunction(elm_target, 'input, select', function(elm_found) {
								SCRIPTER.triggerEvent(elm_found, 'change');
							});
							
							elm_shortcut.find('[name=shortcut]')[0].value = (cur.attr('shortcut') ? cur.attr('shortcut') : '');
							elm_shortcut.find('[name=shortcut_root]')[0].checked = (cur.attr('shortcut_root') ? true : false);
						} else {
						
							elm_select.val('');
							SCRIPTER.triggerEvent(elm_select, 'change');
							
							elm_shortcut.find('[name=shortcut]')[0].value = '';
							elm_shortcut.find('[name=shortcut_root]')[0].checked = false;
						}
					});
					
					// POPUP MOD LIST CLICK

					var func_update_html_modules = function() {
						
						const elm_module_active = func_get_module_active();
						elm_module_active.removeClass('active');
						
						const str_html = elm_module_select.children('.template-preview').html();
						elm_scripter.find('input[name=html_modules]').val(str_html);
						
						elm_module_active.addClass('active'); // Restore
					};

					elm_select.on('change', function() {
						
						const elms_target = elm_select.closest('ul').nextAll('ul, hr');
						
						if (this.value) {
							
							elms_target.removeClass('hide');
							
							elm_options.children('li').addClass('hide');
							elm_options.children('li[data-module=\"'+this.value+'\"]').removeClass('hide');
						} else {
						
							elms_target.addClass('hide');
						}
					});
					
					elm_options.on('keyup change', 'input, select', function() {
						
						var cur = $(this);
						
						if (cur.hasClass('unique')) {
							cur.siblings('[data-group='+cur.attr('data-group')+']').prop('disabled', cur.prop('checked'));
						}
					});

					elm_apply.on('click', function() {
					
						const elm_module_active = func_get_module_active();
						const str_module = elm_select.val();

						if (!str_module) {
							
							elm_module_active.attr({'mod': '', 'var': '', 'shortcut': '', 'shortcut_root': ''}).removeClass('set');
						} else {
						
							const elm_options_active = elm_options.children(':not(.hide)');
							let str_module_variables = '';
							
							if (!elm_options_active.find('[name]').length) {
								
								const elm_target_value = elm_options_active.find('input, select');
								if (elm_target_value.is('[type=checkbox]')) {
									str_module_variables = (elm_target_value.is(':checked') ? elm_target_value.val() : 0);
								} else {
									str_module_variables = elm_target_value.val();
								}
							} else {
							
								var arr = jQuery.map(elm_options_active.find('input[type=text], input[type=number], input[type=checkbox]:checked, select').filter(':enabled').not('#var'), function(o) {
								  return '\"'+o.name+'\":\"'+o.value+'\"'; 
								});
								str_module_variables = '{'+arr.join(',')+'}';
							}

							elm_module_active.attr('var', str_module_variables);
							
							const str_shortcut = elm_shortcut.find('[name=shortcut]')[0].value;
							
							if (str_shortcut) {
								elm_module_active.attr({shortcut: str_shortcut, shortcut_root: (elm_shortcut.find('[name=shortcut_root]')[0].checked ? 1 : '')});
							} else {
								elm_module_active.attr({shortcut: '', shortcut_root: ''});
							}
														
							elm_module_active.attr('mod', str_module).addClass('set');
						}
						
						func_update_html_modules();
						
						func_close_module_active();
					});
					
					elm_cancel.on('click', function() {
						
						func_close_module_active();
					});
				});
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// MAIN PAGE
		
		if ($method == "get_directory" || $method == "get_tag") {
			
			$this->html .= $this->createDirectoryPage($value['directory_id'], $value['tag']);
		}
				
		if ($method == "pages_sort") {
			
			parse_str($value, $arr_sort);
			
			foreach ($arr_sort as $key => $value) {
				
				$res = DB::query("UPDATE
						".DB::getTable('TABLE_PAGES')."
					SET sort = ".(int)$value."
					WHERE id = ".(int)$key."
				");
			}
		}
	
		// POPUP
		
		if ($method == "page_edit" || $method == "page_add") {
		
			if ($method == "page_edit" && (int)$id) {
				
				$arr = self::getPages($id);
				$arr_directory = directories::getDirectories($arr['directory_id']);
								
				$directory_id = $arr['directory_id'];
				
				$arr_tags = cms_general::getTagsByObject(DB::getTable('TABLE_PAGE_INTERNAL_TAGS'), 'page_id', $id, true);
				
				$mode = "page_update";
			} else {
				$mode = "page_insert";
				
				$directory_id = $id;
				
				$arr_tags = [];
			}
			
			$directory_id = ($directory_id ? $directory_id : directories::getRootDirectory());
														
			$this->html = '<form id="frm-page" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_title').'</label>
						<div><input type="text" name="title" value="'.strEscapeHTML($arr['title']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($arr['name']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_directory').'</label>
						<div>'.(($id && ($arr_directory['page_index_id'] == $id || $arr_directory['page_fallback_id'] == $id)) ? '<span title="'.getLabel('inf_page_is_index_fallback').'">'.($arr_directory['path'] ?: '/').'</span><input type="hidden" name="directory" value="'.$directory_id.'" />' : '<select name="directory" id="y:intf_pages:directory_select-0">'.directories::createDirectoriesDropdown(directories::getDirectories(), $directory_id).'</select>').'</div>
					</li>
					<li>
						<label>URL</label>
						<div><input type="text" name="url" value="'.strEscapeHTML($arr['url']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_master_page').'</label>
						<div><select name="master_page" id="y:intf_pages:mod_select-master">'.cms_general::createDropdown(self::getPageNameList(self::getPagesByScope(0, $directory_id, true, ($mode == 'page_update' ? $id : 0)), true, true), $arr['master_id'], true).'</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_template').'</label>
						<div class="templates"><input id="y:intf_pages:mod_select-template" type="hidden" name="template_selected" value="" />'.templates::createTemplatesMenu(templates::getTemplates(), $arr['actual_template_id']).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_html').'</label>
						<div><div class="hide-edit'.(!$arr['html'] ? ' hide' : '').'"><textarea name="html">'.$arr['html'].'</textarea></div><span class="icon" title="'.getLabel('inf_edit_html').'">'.getIcon('html').'</span></div>
					</li>
					<li>
						<label>'.getLabel('lbl_script').'</label>
						<div><div class="hide-edit'.(!$arr['script'] ? ' hide' : '').'"><textarea name="script">'.$arr['script'].'</textarea></div><span class="icon" title="'.getLabel('inf_edit_script').'">'.getIcon('script').'</span></div>
					</li>
					<li>
						<label>'.getLabel('lbl_publish').'</label>
						<div><input type="checkbox" name="publish" value="1"'.($mode == 'page_insert' || $arr['publish'] ? ' checked="checked"' : '').'></div>
					</li>
					<li>
						<label>'.getLabel('lbl_clearance').'</label>
						<div><input type="checkbox" name="clearance" value="1"'.($arr['clearance'] ? ' checked="checked"' : '').'></div>
					</li>
					<li>
						<label>'.getLabel('lbl_internal_tags').'</label>
						<div>'.cms_general::createSelectTags($arr_tags, '', !$arr_tags, true).'</div>
					</li>';
				$this->html .= '</ul></fieldset>';
				
				if ($mode == 'page_update' && !$arr['url']) {
					
					$html_modselector = self::getModSelector($arr['id'], 'update');
				}
				$this->html .= '<div id="mod-select"><div class="template-preview">'.$html_modselector.'</div></div>';
				
				$arr_modules = getModules(DIR_HOME);
				
				$arr_modules_selector = [];
				$arr_modules_options = [];
				
				foreach ($arr_modules as $key => $value) {

					$key::moduleProperties();
					$str_label = $key::$label;
					$str_parent_label = $key::$parent_label;
					
					if (!$str_label) { // Skip non-interface modules
						continue;
					}
					
					$arr_modules_selector[] = ['id' => $key, 'name' => ($str_parent_label ? $str_parent_label.cms_general::OPTION_GROUP_SEPARATOR : '').$str_label];

					$arr_module_variables = (method_exists($key, 'moduleVariables') ? $key::moduleVariables() : '');
											
					if ($arr_module_variables && is_array($arr_module_variables)) {
						
						foreach ($arr_module_variables as $str_name => $html_module_variable) {
							
							$arr_modules_options[] = '<li data-module="'.$key.'">
								<label>'.$str_name.'</label>
								<div>'.$html_module_variable.'</div>
							</li>';
						}
					} else {
						
						$arr_modules_options[] = '<li data-module="'.$key.'">
							<label>'.getLabel('lbl_options').'</label>
							<div>'.($arr_module_variables ?: getLabel('lbl_none')).'</div>
						</li>';
					}
				}
				
				$this->html .= '<div id="mod-list">'
					.'<fieldset><legend>'.getLabel('lbl_module').'</legend>'
						.'<ul>
							<li>
								<label></label>
								<div><select>'.cms_general::createDropdown($arr_modules_selector, false, true).'</select></div>
							</li>
						</ul>'
						.'<hr />'
						.'<ul>
							<li>
								<label>Shortcut</label>
								<div><input type="text" name="shortcut" value="" /></div>
							</li>
							<li>
								<label>Root</label>
								<div><input type="checkbox" name="shortcut_root" value="1" /></div>
							</li>
						</ul>'
						.'<hr />'
						.'<ul>'.arr2String($arr_modules_options, '').'</ul>'
					.'</fieldset>'
					.'<menu><input name="apply" type="button" value="'.getLabel('lbl_apply').'" /><input name="cancel" type="button" value="'.getLabel('lbl_cancel').'" /></menu>'
				.'</div>';
				
				$this->html .= '<input name="html_modules" type="hidden" value="" />
			</form>';
			
			$this->validate = ['title' => 'required', 'directory' => 'required',
				'master_page' => ['required' => 'function() {'.
					'if (!$(\'[name="url"]\').val()) {'.
						'return $(\'[name="template_select"]:selected\').val() < 1'.
					'} else {'.
						'return false;'.
					'}}'
				],
				'template_select' => ['required' => 'function() {'.
					'if (!$(\'[name="url"]\').val()) {'.
						'return $(\'[name="master_page"]\').val() < 1'.
					'} else {'.
						'return false;'.
					'}}'
				]
			];
		}
		
		// POPUP INTERACT
		
		if ($method == "directory_select") {
		
			$this->html = cms_general::createDropdown(self::getPageNameList(self::getPagesByScope(0, $value, true), true, true), 0, true);
		}
		
		if ($method == "mod_select") {

			$this->html = ((int)$value ? self::getModSelector($value, $id) : '');
		}
							
		// QUERY
	
		if ($method == "page_insert") {
			
			if ((!(int)$_POST['master_page'] && !(int)$_POST['template_select'] && !$_POST['url']) || !(int)$_POST['directory']) {
				error('Missing Information');
			}

			$url = $_POST['url'];
			if ($url) {
				$template_id = $master_id = 0;
			} else {
				$master_id = (int)$_POST['master_page'];
				$template_id = ($master_id ? 0 : (int)$_POST['template_select']);
			}
			$page_name = ($_POST['name'] ?: Labels::printLabels(Labels::parseTextVariables($_POST['title'])));
			$page_name = str2Name($page_name);
			
			$directory_id = $_POST['directory'];
			
			$arr_modules = null;

			if ($_POST['html_modules'] && !$url) {
				
				$arr_modules = self::parseModuleTable($directory_id, false, $_POST['html_modules']);
			}
			
			$id = self::updatePages(false, ['name' => $page_name, 'title' => $_POST['title'], 'directory_id' => $directory_id, 'master_id' => $master_id, 'template_id' => $template_id, 'url' => $url, 'html' => $_POST['html'], 'script' => $_POST['script'], 'publish' => $_POST['publish'], 'clearance' => $_POST['clearance']]);
						
			if (isset($arr_modules)) {
								
				foreach ($arr_modules as &$arr_module) {
					
					$arr_module['page_id'] = $id;
					
					$arr_module = pages::updateModule($arr_module);
				}
				unset($arr_module);
				
				pages::deleteNotModules($id, $arr_modules);
			}
			
			cms_general::handleTags(DB::getTable('TABLE_PAGE_INTERNAL_TAGS'), 'page_id', $id, $_POST['tags'], true);
			
			$this->html = $this->createDirectoryPage($value['directory_id'], $value['tag']);
			$this->msg = true;
		}
		
		if ($method == "page_update" && (int)$id) {
		
			if ((!(int)$_POST['master_page'] && !(int)$_POST['template_select'] && !$_POST['url']) || !(int)$_POST['directory']) {
				error('Missing Information');
			}
			
			$url = $_POST['url'];
			if ($url) {
				$template_id = $master_id = 0;
			} else {
				$master_id = (int)$_POST['master_page'];
				$template_id = ($master_id ? 0 : (int)$_POST['template_select']);
			}
			$page_name = ($_POST['name'] ?: Labels::printLabels(Labels::parseTextVariables($_POST['title'])));
			$page_name = str2Name($page_name);
			
			$directory_id = $_POST['directory'];
			
			$arr_modules = null;

			if ($_POST['html_modules'] && !$url) {
				
				$arr_modules = self::parseModuleTable($directory_id, $id, $_POST['html_modules']);
			}
			
			self::updatePages($id, ['name' => $page_name, 'title' => $_POST['title'], 'directory_id' => $directory_id, 'master_id' => $master_id, 'template_id' => $template_id, 'url' => $url, 'html' => $_POST['html'], 'script' => $_POST['script'], 'publish' => $_POST['publish'], 'clearance' => $_POST['clearance']]);
						
			if (isset($arr_modules)) {
								
				foreach ($arr_modules as &$arr_module) {
					
					$arr_module['page_id'] = $id;
					
					$arr_module = pages::updateModule($arr_module);
				}
				unset($arr_module);
				
				pages::deleteNotModules($id, $arr_modules);
			}
			
			cms_general::handleTags(DB::getTable('TABLE_PAGE_INTERNAL_TAGS'), 'page_id', $id, $_POST['tags'], true);
			
			$this->html = $this->createDirectoryPage($value['directory_id'], $value['tag']);
			$this->msg = true;
		}
			
		if ($method == "page_del" && (int)$id) {
		
			self::deletePage($id);
			
			$this->msg = true;
		}
	}
	
	private static function checkModuleShortcut($directory_id, $page_id, $str_name, $is_root) {

		if (!$str_name) {
			return false;
		}
			
		$root_id = directories::getRootDirectory();

		$res = DB::query("SELECT shortcut
				FROM ".DB::getTable('TABLE_PAGE_MODULES')." m
				LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
			WHERE m.shortcut = '".DBFunctions::strEscape($str_name)."'
			".($page_id ? "AND p.id != ".(int)$page_id : "")."
			AND (
				".($directory_id == directories::getRootDirectory() || $is_root ? 
					"m.shortcut_root = TRUE OR p.directory_id = ".(int)$root_id
						: 
					"m.shortcut_root = FALSE AND p.directory_id = ".(int)$directory_id
				)."
			)
		");
		
		if ($res->getRowCount()) {
			
			return true;
		}
		
		return false;
	}
	
	private static function parseModuleTable($directory_id, $page_id, $html) {
	
		$doc = new DOMDocument();
		$doc->strictErrorChecking = false;
		$doc->loadHTML($html);
		# remove <!DOCTYPE 
		$doc->removeChild($doc->firstChild);
		# remove <html><body></body></html> 
		$doc->replaceChild($doc->firstChild->firstChild->firstChild, $doc->firstChild);
		
		$arr_modules = [];
		
		foreach ($doc->getElementsByTagName('td') as $td) {
			
			if (!$td->getAttribute('mod')) {
				continue;
			} 
			
			preg_match('/mod-(\d*)_(\d*)/', $td->getAttribute('id'), $xy);
			
			$var = ($td->getAttribute('var') ? DBFunctions::strEscape($td->getAttribute('var')) : '');
			$str_shortcut_name = ($td->getAttribute('shortcut') ? str2Name($td->getAttribute('shortcut')) : '');
			$is_shortcut_root = (bool)$td->getAttribute('shortcut_root');
			
			if (static::checkModuleShortcut($directory_id, $page_id, $str_shortcut_name, $is_shortcut_root)) {
					
				error('Shortcut already exists');
			}
			
			$arr_modules[] = ['x' => $xy[1], 'y' => $xy[2], 'module' => $td->getAttribute('mod'), 'var' => $var, 'shortcut' => $str_shortcut_name, 'shortcut_root' => $is_shortcut_root];
		}
		
		return $arr_modules;
	}
	
	private static function getModSelector($id, $kind) {
	
		if ($kind == 'template') {
			$template = templates::getTemplates($id);
			$preview = $template['preview'];
		} else {
			$page = self::getPages($id);
			$template = templates::getTemplates($page['actual_template_id']);
			$preview = ($template['preview'] ?: '<div></div>');
						
			$doc = new DOMDocument();
			$doc->strictErrorChecking = false;
			$doc->loadHTML($preview);
			# remove <!DOCTYPE 
			$doc->removeChild($doc->firstChild);
			# remove <html><body></body></html> 
			$doc->replaceChild($doc->firstChild->firstChild->firstChild, $doc->firstChild);
			
			$domxpath = new DOMXPath($doc);
			
			$arr_modules = self::getPageModules($id);

			foreach ($arr_modules as $row) {
				
				$doc_select = $domxpath->query('//td[@id="mod-'.$row['x'].'_'.$row['y'].'"]');
				
				if ($doc_select->length) {
					
					$class = ($row['class'] == 'master' || $kind == 'master' ? 'master' : 'set');
					$doc_select->item(0)->setAttribute('class', $class);
					
					if ($class == 'set') {
						
						$doc_select->item(0)->setAttribute('mod', $row['module']);
						
						if ($row['shortcut']) {
							
							$doc_select->item(0)->setAttribute('shortcut', $row['shortcut']);
							
							if ($row['shortcut_root']) {
								$doc_select->item(0)->setAttribute('shortcut_root', 1);
							}
						}
						
						if ($row['var']) {
							$doc_select->item(0)->setAttribute('var', $row['var']);
						}
					}
				}
			}

			$preview = $doc->saveHTML();
		}
			
		$return = $preview;

		return $return;
	}
}
