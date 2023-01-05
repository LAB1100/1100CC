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
							$return .= '<div class="title"><h3><a target="_blank" href="'.pages::getPageUrl($arr_row).'">'.$arr_row['name'].'</a></h3></div>';
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
				
				#frm-page #mod-list { display: inline-block; vertical-align: top; margin-left: 25px; }
				#frm-page #mod-list .mod-list { display: inline-block; vertical-align: top; min-width: 140px; font-size: 12px; }
				#frm-page #mod-list .mod-list:first-child { margin-left: 0px; }
				#frm-page #mod-list .mod-list li,
				#frm-page #mod-list .mod-list > li > h1 { padding: 0px 20px 0px 10px; line-height: 25px; }
				#frm-page #mod-list .mod-list li.sub { padding: 0px; }
				#frm-page #mod-list .mod-list > li > ul > li { padding-left: 20px; }
				#frm-page #mod-list .mod-list li > span > span + span { margin-left: 5px; color: #000000; }';
		
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
					
					// POPUP INPUT
					
					var func_hide_templace_select = function() {
					
						if (!$('#frm-shortcuts-popup').length) {
							elm_scripter.find('#mod-list').hide();
						}
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
							elm_scripter.find('#mod-select').hide();
							$('#mod-list').hide();
						} else {
							elm_target.show();
							elm_scripter.find('#mod-select').show();
							func_hide_templace_select();
						}
					};
					
					func_hide_templace_select();
					func_hide_mode_select();
					
					elm_scripter.on('change', '[name=directory]', function() {
					
						$(this).quickCommand(function(html) {
							var elm_select_master_page = elm_scripter.find('select[name=master_page]');
							var master_page_value = elm_select_master_page.val();
							
							elm_select_master_page.html(html).val(master_page_value);
							
							if (master_page_value && !elm_select_master_page.val()) {
								elm_scripter.find('#mod-select > .template-preview').empty();
							}
						});
					}).on('keyup', 'input[name=url]', function() {
						func_hide_mode_select();
					}).on('change', 'select[name=master_page], [name=template_selected]', function() {
						$(this).quickCommand(elm_scripter.find('#mod-select > .template-preview'));
					}).on('change', 'select[name=master_page]', function() {
						func_hide_templace_select();
					}).on('change', '[name=template_select]', function() {
						elm_scripter.find('[name=template_selected]').val(elm_scripter.find('[name=template_select]:checked').val()).trigger('change');
					});
					
					// POPUP MOD CLICK
					
					elm_scripter.on('mouseup', '#mod-select .template-preview td[id^=mod]', function() {
					
						var cur = $(this);
						
						$(document).off('.modclick').on('mousedown.modclick', function(e) {
						
							if (!($(e.target).closest('#mod-list').length || $('#frm-shortcuts-popup').length || !$(e.target).closest(elm_scripter).length)) {
								$('#mod-select .template-preview td').removeClass('active');
								$('#mod-list li').removeClass('active');
								$('#mod-list li span#mod-option-shortcut').children('span + span').text('None');
								$(document).off('.modclick');
							}
						});
						
						elm_scripter.find('#mod-list').show();
						cur.addClass('active');
						
						if (cur.attr('mod')) {
						
							var cur_parent = elm_scripter.find('#mod-list li span#mod-'+cur.attr('mod')+'').parent('li');
							cur_parent.addClass('active');
							
							if (cur.attr('var')) {
								if (cur.attr('var').charAt(0) == '{') {
									var obj = JSON.parse(cur.attr('var'));
									for (var key in obj) {
										var target = cur_parent.find('[name=\"'+key+'\"]');
										if (target.is('[type=checkbox]')) {
											target.prop('checked', obj[key]);
										} else {
											target.val(obj[key]);
										}
										if (target.hasClass('unique')) {
											target.siblings('[data-group='+target.attr('data-group')+']').prop('disabled', obj[key]);
										}
									}
								} else {
									var target = cur_parent.find('input, select').first();
									if (target.is('[type=checkbox]')) {
										target.prop('checked', cur.attr('var'));
									} else {
										target.val(cur.attr('var'));
									}
								}
							}
							// Trigger change to save new state if there was no possible state before
							cur_parent.find('input, select').trigger('change');
							
							var text = (cur.attr('shortcut') ? cur.attr('shortcut')+(cur.attr('shortcut_root') ? ' - root' : '') : 'None');
							$('#mod-list li span#mod-option-shortcut').children('span + span').text(text);
						}
					});
					
					// POPUP MOD LIST CLICK
					
					var func_get_mod_active = function() {
						return elm_scripter.find('#mod-select .template-preview .active');
					};
					var func_update_mod_html = function() {
						elm_scripter.find('input[name=html_modules]').val(elm_scripter.find('#mod-select .template-preview').html());
					};
					elm_scripter.on('click', '#mod-list li > span:first-child', function() {
					
						var cur_parent = $(this).parent('li');
						var active_mod = func_get_mod_active();
						active_mod.attr('var', '');
						// Trigger change to save new state
						cur_parent.find('input, select').trigger('change');
						if ($(this).attr('id').split('-')[2] == 'del') {
							$('#mod-list li').removeClass('active');
							cur_parent.addClass('active');
							active_mod.attr({'mod': '', 'var': '', 'shortcut': '', 'shortcut_root': ''}).removeClass('set');
						} else if ($(this).attr('id').split('-')[2] == 'shortcut') {
							$('#mod-list li span[id^=mod-option-]').parent('li').removeClass('active');
							cur_parent.addClass('active');
							var cur_elm = $(this).children('.shortcut');
							cur_elm.attr('id', cur_elm.attr('id').split('D')[0]+'D'+elm_scripter.find('[name=directory]').val());
							cur_elm.data({value: {name: active_mod.attr('shortcut'), root: active_mod.attr('shortcut_root')}, target: function(data) {
								active_mod.attr({shortcut: data.name, shortcut_root: data.root});
								var text = (data.name ? data.name+(data.root ? ' - root' : '') : 'None');
								cur_elm.next('span').text(text);
								func_update_mod_html();
							}}).popupCommand();
						} else {
							$('#mod-list li').removeClass('active');
							cur_parent.addClass('active');
							active_mod.attr('mod', $(this).attr('id').split('-')[1]).addClass('set');
						}
						
						func_update_mod_html();
					}).on('keyup change', '#mod-list li input, #mod-list li select', function() {
						
						var cur = $(this);
						
						if (cur.hasClass('unique')) {
							cur.siblings('[data-group='+cur.attr('data-group')+']').prop('disabled', cur.prop('checked'));
						}
						
						if (!cur.is('[name]')) {
							if (cur.is('[type=checkbox]')) {
								var value = (cur.is(':checked') ? cur.val() : 0);
							} else {
								var value = cur.val();
							}
						} else {
							var arr = jQuery.map(cur.siblings().addBack().filter('input[type=text], input[type=checkbox]:checked, select').filter(':enabled').not('#var'), function(o) {
							  return '\"'+o.name+'\":\"'+o.value+'\"'; 
							});
							var value = '{'+arr.join(',')+'}';
						}

						var active_mod = func_get_mod_active();
						active_mod.attr('var', value);
						func_update_mod_html();
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
				
				$arr_tags = cms_general::getObjectTags(DB::getTable('TABLE_PAGE_INTERNAL_TAGS'), 'page_id', $id, true);
				
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
						<label>'.getLabel("lbl_title").'</label>
						<div><input type="text" name="title" value="'.strEscapeHTML($arr['title']).'" /></div>
					</li>
					<li>
						<label>'.getLabel("lbl_name").'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($arr['name']).'" /></div>
					</li>
					<li>
						<label>'.getLabel("lbl_directory").'</label>
						<div>'.(($id && ($arr_directory['page_index_id'] == $id || $arr_directory['page_fallback_id'] == $id)) ? '<span title="'.getLabel('inf_page_is_index_fallback').'">'.($arr_directory['path'] ?: '/').'</span><input type="hidden" name="directory" value="'.$directory_id.'" />' : '<select name="directory" id="y:intf_pages:directory_select-0">'.directories::createDirectoriesDropdown(directories::getDirectories(), $directory_id).'</select>').'</div>
					</li>
					<li>
						<label>URL</label>
						<div><input type="text" name="url" value="'.strEscapeHTML($arr['url']).'" /></div>
					</li>
					<li>
						<label>'.getLabel("lbl_master_page").'</label>
						<div><select name="master_page" id="y:intf_pages:mod_select-master">'.cms_general::createDropdown(self::getPageNameList(self::getPagesLimited(0, $directory_id, true, ($mode == "page_update" ? $id : 0)), true, true), $arr['master_id'], true).'</select></div>
					</li>
					<li>
						<label>'.getLabel("lbl_template").'</label>
						<div class="templates"><input id="y:intf_pages:mod_select-template" type="hidden" name="template_selected" value="" />'.templates::createTemplatesMenu(templates::getTemplates(), $arr["actual_template_id"]).'</div>
					</li>
					<li>
						<label>'.getLabel("lbl_html").'</label>
						<div><div class="hide-edit'.(!$arr['html'] ? ' hide' : '').'"><textarea name="html">'.$arr['html'].'</textarea></div><span class="icon" title="'.getLabel('inf_edit_html').'">'.getIcon('html').'</span></div>
					</li>
					<li>
						<label>'.getLabel("lbl_script").'</label>
						<div><div class="hide-edit'.(!$arr['script'] ? ' hide' : '').'"><textarea name="script">'.$arr['script'].'</textarea></div><span class="icon" title="'.getLabel('inf_edit_script').'">'.getIcon('script').'</span></div>
					</li>
					<li>
						<label>'.getLabel("lbl_publish").'</label>
						<div><input type="checkbox" name="publish" value="1"'.($mode == "page_insert" || $arr["publish"] ? ' checked="checked"' : '').'></div>
					</li>
					<li>
						<label>'.getLabel("lbl_clearance").'</label>
						<div><input type="checkbox" name="clearance" value="1"'.($arr["clearance"] ? ' checked="checked"' : '').'></div>
					</li>
					<li>
						<label>'.getLabel("lbl_internal_tags").'</label>
						<div>'.cms_general::createSelectTags($arr_tags, '', !$arr_tags, true).'</div>
					</li>';
				$this->html .= '</ul></fieldset>';
				
				if ($mode == "page_update" && !$arr["url"]) {
					$modselector = self::getModSelector($arr['id'], "update");
				}
				$this->html .= '<div id="mod-select"><div class="template-preview">'.$modselector.'</div></div>';
				
				$arr_modules = ['option-del' => ['label' => '<span class="icon remove" title="Remove">'.getIcon('min').'</span><span>Remove</span>'], 'option-shortcut' => ['label' => '<span class="icon shortcut" id="y:intf_pages:popup_shortcuts-'.$id.'D0" title="Shortcut">'.getIcon('link').'</span><span>None</span>']]+getModules(DIR_HOME);
				$this->html .= '<div id="mod-list">'.cms_general::selectModuleList($arr_modules, true, false, 15).'</div>';
				
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
		
		if ($method == "popup_shortcuts") {
		
			$this->html .= '<form id="frm-shortcuts-popup" data-method="shortcut_get">
				<fieldset><ul>
					<li>
						<label>Name</label>
						<div><input type="text" name="name" value="'.$value["name"].'" /></div>
					</li>
					<li>
						<label>Root</label>
						<div><input type="checkbox" name="root" value="1"'.($value["root"] ? 'checked="checked"' : '').'></div>
					</li>
				</ul></fieldset>
			</form>';

		}
		
		// POPUP INTERACT
		
		if ($method == "directory_select") {
		
			$this->html = cms_general::createDropdown(self::getPageNameList(self::getPagesLimited(0, $value, true), true, true), 0, true);
		}
		
		if ($method == "mod_select") {

			$this->html = ((int)$value ? self::getModSelector($value, $id) : '');
		}
		
		if ($method == "shortcut_get") {
		
			$name = str2Name($_POST['name']);
			$root = ($name ? $_POST['root'] : 0);
			
			if ($name) {
				
				$ids = explode('D', $id);
				$page_id = $ids[0]; // if editing
				$directory_id = $ids[1];
				$root_id = directories::getRootDirectory();

				$res = DB::query("SELECT shortcut
						FROM ".DB::getTable('TABLE_PAGE_MODULES')." m
						LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
					WHERE m.shortcut = '".DBFunctions::strEscape($name)."'
					".($page_id ? "AND p.id != ".(int)$page_id : "")."
					AND (
						".($directory_id == directories::getRootDirectory() || $root ? 
							"m.shortcut_root = TRUE OR p.directory_id = ".(int)$root_id
								: 
							"m.shortcut_root = FALSE AND p.directory_id = ".(int)$directory_id
						)."
					)
				");
				
				if ($res->getRowCount()) {
					
					error('Shortcut already exists');
				}
			}

			$this->html = ['name' => $name, 'root' => $root];
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
			
			$id = self::updatePages(0, ['name' => $page_name, 'title' => $_POST['title'], 'directory_id' => $_POST['directory'], 'master_id' => $master_id, 'template_id' => $template_id, 'url' => $url, 'html' => $_POST['html'], 'script' => $_POST['script'], 'publish' => $_POST['publish'], 'clearance' => $_POST['clearance']]);
						
			if ($_POST['html_modules'] && !$url) {
				
				self::parseModuleTable($id, $_POST['html_modules']);
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
			
			self::updatePages($id, ['name' => $page_name, 'title' => $_POST['title'], 'directory_id' => $_POST['directory'], 'master_id' => $master_id, 'template_id' => $template_id, 'url' => $url, 'html' => $_POST['html'], 'script' => $_POST['script'], 'publish' => $_POST['publish'], 'clearance' => $_POST['clearance']]);
						
			if ($_POST['html_modules'] && !$url) {
				
				self::parseModuleTable($id, $_POST['html_modules']);
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
	
	private static function parseModuleTable($id, $html) {
	
		$doc = new DOMDocument();
		$doc->strictErrorChecking = false;
		$doc->loadHTML($html);
		# remove <!DOCTYPE 
		$doc->removeChild($doc->firstChild);
		# remove <html><body></body></html> 
		$doc->replaceChild($doc->firstChild->firstChild->firstChild, $doc->firstChild);
		
		$arr_ids = [];
		
		foreach ($doc->getElementsByTagName('td') as $td) {
			
			if (!$td->getAttribute('mod')) {
				continue;
			} 
			
			preg_match('/mod-(\d*)_(\d*)/', $td->getAttribute('id'), $xy);
			
			$var = ($td->getAttribute('var') ? DBFunctions::strEscape($td->getAttribute('var')) : '');
			$shortcut_name = ($td->getAttribute('shortcut') ? str2Name($td->getAttribute('shortcut')) : '');
			
			$arr_ids[] = pages::updateModule(['page_id' => $id, 'x' => $xy[1], 'y' => $xy[2], 'module' => $td->getAttribute('mod'), 'var' => $var, 'shortcut' => $shortcut_name, 'shortcut_root' => $td->getAttribute('shortcut_root')]);
		}
		
		pages::deleteNotModules($id, $arr_ids);
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
