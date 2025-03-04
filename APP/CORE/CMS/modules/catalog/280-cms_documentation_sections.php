<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_DOCUMENTATION_SECTIONS', DB::$database_home.'.def_documentation_sections');

class cms_documentation_sections extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_documentation_sections');
		static::$parent_label = getLabel('ttl_content');
	}
	
	public static function mediaLocations() {
		
		return [
			'TABLE_DOCUMENTATION_SECTIONS' => [
				'body'
			]
		];
	}
	
	public static function webLocations() {
		
		return [
			'name' => 'documentations',
			'entries' => function() {
				
				$arr_documentations = cms_documentations::getDocumentations();

				foreach ($arr_documentations as $arr_documentation) {
					
					$arr_link = cms_documentations::findMainDocumentation($arr_documentation['id']);
					
					if (!$arr_link || $arr_link['require_login']) {
						continue;
					}
					
					$str_location_base = pages::getModuleURL($arr_link, true);
					
					$arr_documentation_sections = static::getDocumentationSections($arr_documentation['id'], false, true);
					
					foreach ($arr_documentation_sections as $arr_documentation_section) {
							
						$str_location = $str_location_base.$arr_documentation_section['id'].'/'.$arr_documentation_section['name'];
						
						yield $str_location;
					}
				}
			}
		];
	}
	
	const TAGCODE_HEADING_DOCUMENT_SECTION = '/\[hds=([1-9])\](.+?)\[\/hds\]/si';
	const TAGCODE_URL_DOCUMENT_SECTION = '/\[urlds=(?:([0-9]*?)_)?([0-9]*)(?::([^\s\'"<>]*?))?\](.+?)\[\/urlds\]/si';
	
	public function contents() {

		$return = '<div class="section"><h1>'.static::$label.'</h1>
		<div>';
		
			$arr_documentations = cms_documentations::getDocumentations();
			
			if (!$arr_documentations) {
			
				Labels::setVariable('name', getLabel('lbl_documentations'));
					
				$return .= '<section class="info">'.getLabel('msg_no', 'L', true).'</section>';
			} else {
			
				$return .= '<div id="tabs-documentations">
					<ul>';
			
						foreach ($arr_documentations as $arr_documentation) {
					
							$return .= '<li><a href="#tab-documentation-sections-'.$arr_documentation['id'].'">'.$arr_documentation['name'].'</a><span><input id="x:cms_documentation_sections:new_documentation_section-'.$arr_documentation['id'].'_0" type="button" class="data add popup add_documentation_section" value="add" /></span></li>';
						}
					
					$return .= '</ul>';
					
					foreach ($arr_documentations as $arr_documentation) {
					
						$return .= '<div id="tab-documentation-sections-'.$arr_documentation['id'].'">'.static::contentTabDocumentationSections($arr_documentation['id']).'</div>';
					}
				
				$return .= '</div>';
			}
		$return .= '</div></div>';
				
		return $return;
	}
		
	private static function contentTabDocumentationSections($documentation_id) {
		
		$arr_documentation_sections = static::getDocumentationSections($documentation_id);

		if (!$arr_documentation_sections) {
			
			Labels::setVariable('name', getLabel('lbl_documentation_sections'));

			return '<section class="info">'.getLabel('msg_no', 'L', true).'</section>';
		}
		
		$arr_top_level_documentation_section_ids = [];
		foreach ($arr_documentation_sections as $documentation_section_id => $arr_documentation_section) {
			
			if ($arr_documentation_section['parent_section_id']) {
				continue;
			}
			
			$arr_top_level_documentation_section_ids[] = $documentation_section_id;
		}
		
		$return = '<div class="options documentation-sections" id="y:cms_documentation_sections:get_documentation_sections-'.$documentation_id.'">
			<input type="hidden" id="y:cms_documentation_sections:sort_documentation_sections-'.$documentation_id.'" name="sort" value="" />'
			.static::createDocumentationSectionSorter($documentation_id, $arr_top_level_documentation_section_ids, $arr_documentation_sections)
		.'</div>';
		
		return $return;
	}

	private static function createDocumentationSectionSorter($documentation_id, $arr_documentation_section_ids, $arr_documentation_sections) {
		
		$elm_sorter = '<ul class="sorter">';
		
		foreach ($arr_documentation_section_ids as $documentation_section_id) {
			
			$arr_documentation_section = $arr_documentation_sections[$documentation_section_id];
			$elm_section = '<input type="text" value="'.$arr_documentation_section['title'].'" disabled /><input type="button" class="data edit popup edit_documentation_section" value="edit" /><input type="button" class="data del msg del_documentation_section" value="del" />';
						
			if ($arr_documentation_section['child_section_ids']) {
				
				$elm_section = '<ul>
					<li>'.$elm_section.'</li>
					<li>'.static::createDocumentationSectionSorter($documentation_id, $arr_documentation_section['child_section_ids'], $arr_documentation_sections).'</li>
				</ul>';
				
			} else {
				
				$elm_section = '<div>'.$elm_section.'</div>';
			}
			
			$elm_sorter .= '<li id="x:cms_documentation_sections:documentation_section_id-'.$documentation_id.'_'.$documentation_section_id.'" >'
				.'<span><span class="icon">'.getIcon('updown').'</span></span>'
				.$elm_section
			.'</li>';
		}
		
		$elm_sorter .= '</ul>';
		
		return '<fieldset><div>'.$elm_sorter.'</div></fieldset>';
	}	
		
	public static function css() {
	
		$return = '';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "
		SCRIPTER.static('#mod-cms_documentation_sections', function(elm_scripter) {

			elm_scripter.on('commandfinished', '.edit_documentation_section', function() {
				var elm_documentation = $(this).closest('[id^=y\\\:cms_documentation_sections\\\:get_documentation_sections]');
				elm_documentation.quickCommand();
			});
			
			elm_scripter.on('commandfinished', '.add_documentation_section', function() {

				var elm_documentation = elm_scripter.find('.tabs').children('div:not(.hide)').children('[id^=y\\\:cms_documentation_sections\\\:get_documentation_sections]');
				elm_documentation.quickCommand();
			});

			elm_scripter.find('.documentation-sections').each(function() {
				SCRIPTER.runDynamic(this);
			});
		});
		
		SCRIPTER.dynamic('.documentation-sections', function(elm_scripter) {
		
			var func_update_sort = function() {

				var arr_sort = {};
				elm_scripter.find('[id^=x\\\:cms_documentation_sections\\\:documentation_section_id]').each(function() {
					var id = $(this).attr('id').split('-')[1];
					arr_sort[id] = elm_scripter.find('[id^=x\\\:cms_documentation_sections\\\:documentation_section_id]').index(this);
				});

				elm_input = elm_scripter.children('input[name=sort]');
				elm_input.val($.param(arr_sort));
				COMMANDS.quickCommand(elm_input, elm_input, false);
			};
			
			elm_scripter.find('ul.sorter').each(function() {
				$(this).on('sort', function() {
					func_update_sort();
				});
			});		
		});
		
		SCRIPTER.dynamic('#frm-documentation_section', function(elm_scripter) {
		
			elm_scripter.on('editorloaded', 'textarea[name=body]', function(e) {
			
				var obj_editor = e.detail.editor.edit_content;
				
				obj_editor.addSeparator();
				
				var elm_button_heading_documentation_section = obj_editor.addButton({title: 'Documentation Section Heading', class: 'heading'}, '<span><span>Hds</span><span>1</span><span>2</span><span>3</span></span>', function() {
										
					CONTENT.input.setSelectionContent(e.detail.source, {before: '[hds=1]', after: '[/hds]'});
					SCRIPTER.triggerEvent(e.detail.source, 'change');
				});
				var elm_button_url_documentation_section = obj_editor.addButton({title: 'Documentation Section Link', id: 'y:cms_documentation_sections:popup_documentation_section_link-0'}, false, function() {
					
					var str_selected = CONTENT.input.getSelectionContent(e.detail.source);
					var elm_context = elm_scripter.closest('.overlay')[0].context;
					
					COMMANDS.setID(elm_button_url_documentation_section, COMMANDS.getID(elm_context, true));
					COMMANDS.setData(elm_button_url_documentation_section, {selected: str_selected});
					COMMANDS.setTarget(elm_button_url_documentation_section, function(str) {
					
						CONTENT.input.setSelectionContent(e.detail.source, {replace: str});
						SCRIPTER.triggerEvent(e.detail.source, 'change');
					});
					
					COMMANDS.popupCommand(elm_button_url_documentation_section);
				});
				
				ASSETS.getIcons(e.detail.source, ['pages'], function(data) {
		
					elm_button_url_documentation_section[0].children[0].innerHTML = data.pages;
				});
			});
		});
		SCRIPTER.dynamic('[data-method=return_documentation_section_link]', function(elm_scripter) {
			
			elm_scripter.on('change', '[id^=y\\\:cms_documentation_sections\\\:get_documentation_sections_list]', function(e) {
				
				let elm_target = elm_scripter.find('[name=documentation_section_id]');
				COMMANDS.setID(elm_target, this.value);
				
				COMMANDS.quickCommand(this, function(data) {
				
					elm_target.html(data);
					SCRIPTER.triggerEvent(elm_target, 'change');
				});
			}).on('change', '[id^=y\\\:cms_documentation_sections\\\:get_documentation_section_anchors]', function(e) {
				
				COMMANDS.quickCommand(this, elm_scripter.find('[name=documentation_section_anchor]'));
			});
		});
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		if ($method == "get_documentation_sections") {
			
			$this->html = static::contentTabDocumentationSections($id);
		}
		
		if ($method == "sort_documentation_sections") {
	
			parse_str($value, $arr_sort);

			foreach ($arr_sort as $key => $value) {
				
				$arr_id = explode('_', $key);
				$documentation_section_id = $arr_id[1];
 
				$res = DB::query("UPDATE
						".DB::getTable('TABLE_DOCUMENTATION_SECTIONS')."
					SET sort = ".(int)$value."
					WHERE id = ".(int)$documentation_section_id."
				");
			}
		}
				
		// POPUP
		
		if ($method == "edit_documentation_section" || $method == "add_documentation_section") {

			$arr_id = explode('_', $id);
			$documentation_id = $arr_id[0];
			$documentation_section_id = $arr_id[1];
			
			$arr_documentations = cms_documentations::getDocumentations();
			
			if ((int)$documentation_section_id) {
						
				$arr_documentation_section = static::getDocumentationSections($documentation_id, $documentation_section_id);
								
				$mode = "update_documentation_section";
			} else {
			
				if (!$arr_documentations) {
					
					$this->html = '<section class="info">'.getLabel('msg_no_documentation_sections').'</section>';
					return;
				}
						
				$mode = "insert_documentation_section";
			}
			
			$arr_documentation_sections = static::getDocumentationSections($documentation_id);
			$arr_documentation_section_candidates = static::parseDocumentationSectionsList($arr_documentation_sections, $documentation_section_id); // Doc Section cannot be set as child of own descendents.
													
			$this->html = '<form id="frm-documentation_section" data-method="'.$mode.'" data-lock="1">
				<fieldset><ul>
					<li>
						<label></label>
						<div>'.cms_general::createSelectorRadio([['id' => '1', 'name' => getLabel('lbl_publish')], ['id' => '0', 'name' => getLabel('lbl_draft')]], 'publish', ($arr_documentation_section['publish'] ? $arr_documentation_section['publish'] : 0)).'</div>
					</li>
				</ul>
				<hr />
				<ul>
					<li>
						<label>'.getLabel('lbl_title').'</label>
						<div><input type="text" name="title" value="'.strEscapeHTML($arr_documentation_section['title']).'"></div>
					</li>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($arr_documentation_section['name']).'"></div>
					</li>
					<li>
						<label>'.getLabel('lbl_parent').' '.getLabel('lbl_documentation_section').'</label>
						<div><select name="parent_section_id">'.cms_general::createDropdown($arr_documentation_section_candidates, $arr_documentation_section['parent_section_id'], true).'</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_body').'</label>
						<div>'.cms_general::editBody($arr_documentation_section['body']).'</div>
					</li>
				</ul></fieldset>
				</form>';
			
			$this->validate = ['title' => 'required', 'name' => 'required'];
		}
		
		if ($method == "popup_documentation_section_link") {
			
			$arr_id = explode('_', $id);
			$documentation_id = $arr_id[0];
			$documentation_section_id = $arr_id[1];
			
			$selected_documentation_section_id = false;
			$html_documentation_section_anchors = '';

			$has_match = preg_match(static::TAGCODE_URL_DOCUMENT_SECTION, $value['selected'], $arr_match);
			
			if ($has_match) {
				
				$documentation_id = ($arr_match[1] ?: $documentation_id);
				$selected_documentation_section_id = ($arr_match[2] ?: $documentation_section_id);
				
				$arr_documentation_section_anchors = static::getDocumentationSectionAnchors($documentation_id, $selected_documentation_section_id);
				$html_documentation_section_anchors = cms_general::createDropdown($arr_documentation_section_anchors, $arr_match[3], true);
			}
			
			$arr_documentations = cms_documentations::getDocumentations();
			$arr_documentation_sections = static::getDocumentationSections($documentation_id);
			$arr_documentation_sections_list = static::parseDocumentationSectionsList($arr_documentation_sections);

			$return = '<form data-method="return_documentation_section_link">
				<fieldset><ul>
					'.($arr_documentations > 1 ? '
						<li>
							<label>'.getLabel('lbl_documentations').'</label>
							<div><select name="documentation_id" id="y:cms_documentation_sections:get_documentation_sections_list-0">'.cms_general::createDropdown($arr_documentations, $documentation_id).'</select></div>
						</li>
					' : '').'
					<li>
						<label>'.getLabel('lbl_documentation_section').'</label>
						<div><select name="documentation_section_id" id="y:cms_documentation_sections:get_documentation_section_anchors-'.$documentation_id.'">'.cms_general::createDropdown($arr_documentation_sections_list, $selected_documentation_section_id, true).'</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_heading').'</label>
						<div><select name="documentation_section_anchor">'.$html_documentation_section_anchors.'</select></div>
					</li>
				</ul></fieldset>
			</form>';
		
			$this->html = $return;
		}
		
		if ($method == "return_documentation_section_link") {
			
			$arr_id = explode('_', $id);
			$documentation_id = $arr_id[0];
			$documentation_section_id = $arr_id[1];
			
			if ($_POST['documentation_id'] != $documentation_id) {
				$str_identifier = $_POST['documentation_id'].'_'.$_POST['documentation_section_id'];
			} else {
				$str_identifier = ($_POST['documentation_section_id'] == $documentation_section_id ? '' : $_POST['documentation_section_id']);
			}
			$str_identifier .= ($_POST['documentation_section_anchor'] ? ':'.$_POST['documentation_section_anchor'] : '');
			
			$str_selected = $value['selected'];
			
			$str_selected = preg_replace_callback(static::TAGCODE_URL_DOCUMENT_SECTION, function($arr_matches) use ($str_identifier) {
				
				return '[urlds='.$str_identifier.']'.$arr_matches[4].'[/urlds]';
			}, $str_selected, 1, $count);
			
			if (!$count) {
				$str_selected = '[urlds='.$str_identifier.']'.$str_selected.'[/urlds]';
			}

			$this->html = $str_selected;
		}
		
		if ($method == "get_documentation_sections_list") {
			
			$documentation_id = (int)$value;
			
			if (!$documentation_id) {
				return;
			}
			
			$arr_documentation_sections = static::getDocumentationSections($documentation_id);
			$arr_documentation_sections_list = static::parseDocumentationSectionsList($arr_documentation_sections);
					
			$this->html = cms_general::createDropdown($arr_documentation_sections_list, false, true);
		}
		
		if ($method == "get_documentation_section_anchors") {
			
			$documentation_id = $id;
			$documentation_section_id = $value;
			
			if (!$documentation_section_id) {
				
				$this->html = '';
				return;
			}
			
			$arr = static::getDocumentationSectionAnchors($documentation_id, $documentation_section_id);
			
			$this->html = cms_general::createDropdown($arr, false, true);
		}
		
		// DATATABLE
							
		// QUERY
	
		if ($method == "insert_documentation_section" && (int)$id) {
		
			$arr_id = explode('_', $id);
			$documentation_id = $arr_id[0];
			$body = $_POST['body'];
			
			$documentation_section_name = ($_POST['name'] ?: Labels::printLabels(Labels::parseTextVariables($_POST['title'])));
			$documentation_section_name = str2Name($documentation_section_name, '-');
							
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_DOCUMENTATION_SECTIONS')."
				(name, documentation_id, parent_section_id, title, body, date_created, date_updated, publish, sort)
					VALUES
				(
					'".DBFunctions::strEscape($documentation_section_name)."',
					".(int)$documentation_id.",
					".(int)$_POST['parent_section_id'].",
					'".DBFunctions::strEscape($_POST['title'])."',
					'".DBFunctions::strEscape($body)."',
					NOW(),
					NOW(),
					".DBFunctions::escapeAs($_POST['publish'], DBFunctions::TYPE_BOOLEAN).",
					0
				)
			");
						
			$this->msg = true;
		}
		
		if ($method == "update_documentation_section" && (int)$id) {
		
			$arr_id = explode('_', $id);
			$documentation_id = $arr_id[0];
			$documentation_section_id = $arr_id[1];
			$body = $_POST['body'];
			
			$documentation_section_name = ($_POST['name'] ?: Labels::printLabels(Labels::parseTextVariables($_POST['title'])));
			$documentation_section_name = str2Name($documentation_section_name, '-');
					
			$res = DB::query("UPDATE ".DB::getTable('TABLE_DOCUMENTATION_SECTIONS')." SET
				name = '".DBFunctions::strEscape($documentation_section_name)."',
				documentation_id = ".(int)$documentation_id.",
				parent_section_id = ".(int)$_POST['parent_section_id'].",
				title = '".DBFunctions::strEscape($_POST['title'])."',
				body = '".DBFunctions::strEscape($body)."',
				date_updated = NOW(),
				publish = ".DBFunctions::escapeAs($_POST['publish'], DBFunctions::TYPE_BOOLEAN)."
					WHERE id = ".(int)$documentation_section_id."
			");
									
			$this->msg = true;
		}
			
		if ($method == "del_documentation_section" && (int)$id) {

			$arr_id = explode('_', $id);
			$documentation_section_id = $arr_id[1];
					
			$res = DB::queryMulti("			
				DELETE FROM ".DB::getTable('TABLE_DOCUMENTATION_SECTIONS')."
					WHERE id = ".(int)$documentation_section_id."
					OR parent_section_id = ".(int)$documentation_section_id."
				;
			");
			
			$this->msg = true;
		}
	}
	
	public static function getDocumentationSections($documentation_id, $documentation_section_id = 0, $is_published = null, $arr_options = []) {
	
		$arr_documentation_sections = [];

		$res = DB::query("SELECT
			".($arr_options['meta_data'] ? "ds.id, ds.name, ds.documentation_id, ds.parent_section_id, ds.title, ds.publish" : "ds.*")."
				FROM ".DB::getTable('TABLE_DOCUMENTATION_SECTIONS')." ds
			WHERE 
				ds.documentation_id = ".(int)$documentation_id."
				".($documentation_section_id ? "AND ds.id = ".(int)$documentation_section_id : "")."
				".($is_published !== null ? "AND ds.publish = ".($is_published ? 'TRUE' : 'FALSE') : '')."
			ORDER BY ds.sort
		");
							
		while ($arr_row = $res->fetchAssoc()) {
				
			$arr_documentation_sections[$arr_row['id']] = $arr_row;
		}
		
		foreach ($arr_documentation_sections as $id => $arr_section) {
			
			if ($arr_section['parent_section_id']) {
				
				$arr_documentation_sections[$arr_section['parent_section_id']]['child_section_ids'][] = $id;
				
			}
		}
		
		return ($documentation_section_id ? $arr_documentation_sections[$documentation_section_id] : $arr_documentation_sections);
	}
	
	public static function parseDocumentationSectionsList($arr_documentation_sections, $check_documentation_section_id = false) {
		
		$arr_list = [];
		
		$func_make_list = function(&$arr_list, $arr_documentation_section_ids, $str_indent) use ($arr_documentation_sections, $check_documentation_section_id, &$func_make_list) {
			
			if (!$arr_documentation_section_ids) {
				return;
			}
			
			foreach ($arr_documentation_section_ids as $documentation_section_id) {
				
				if ($check_documentation_section_id && $documentation_section_id == $check_documentation_section_id) {
					continue;
				} 
					
				$arr_documentation_section = $arr_documentation_sections[$documentation_section_id];
				
				$arr_list[$documentation_section_id] = ['id' => $documentation_section_id, 'name' => $str_indent.$arr_documentation_section['title']];
				
				$func_make_list($arr_list, $arr_documentation_section['child_section_ids'], $str_indent.'― ');
			}
		};
		
		$arr_documentation_section_ids = [];
		
		foreach ($arr_documentation_sections as $documentation_section_id => $arr_documentation_section) {
			
			if ($arr_documentation_section['parent_section_id']) {
				continue;
			}
			
			$arr_documentation_section_ids[] = $documentation_section_id;
		}
		
		$func_make_list($arr_list, $arr_documentation_section_ids, '');

		return $arr_list;
	}
	
	public static function getDocumentationSectionAnchors($documentation_id, $documentation_section_id) {
		
		$arr = [];
		
		$arr_documentation_section = static::getDocumentationSections($documentation_id, $documentation_section_id);
		
		$str_value = $arr_documentation_section['body'];

		$has_match = preg_match_all(static::TAGCODE_HEADING_DOCUMENT_SECTION, $str_value, $arr_matches);
		
		if (!$has_match) {
			return $arr;
		}
		
		foreach ($arr_matches[2] as $str_heading) {
			
			$str_anchor = str2URL($str_heading);
			
			$arr[$str_anchor] = ['id' => $str_anchor, 'name' => $str_heading];
		}
		
		return $arr;
	}
	
	public static function loadTextTags($documentation_id, $documentation_section_id, $arr_mod) {
		
		$arr_documentation_sections = static::getDocumentationSections($documentation_id);
		$arr_documentation_section = $arr_documentation_sections[$documentation_section_id];
		
		$str_url_documentation = SiteStartEnvironment::getShortestModuleURL($arr_mod['id'], false, $arr_mod['shortcut'], $arr_mod['shortcut_root'], 0, true);
		
		$url_section = $str_url_documentation.$arr_documentation_section['id'].'/'.$arr_documentation_section['name'];
		Labels::setVariable('url_documentation', $str_url_documentation);
		Labels::setVariable('url_section', $url_section);
		
		FormatTags::addCode('header_documentation_section',
			static::TAGCODE_HEADING_DOCUMENT_SECTION,
			function($arr_match) use ($url_section) {
				
				$str_anchor = str2URL($arr_match[2]);
				
				return '<h'.$arr_match[1].' id="'.$str_anchor.'">'.$arr_match[2].' <a href="'.$url_section.'#'.$str_anchor.'"><span class="icon">'.getIcon('link').'</span></a></h'.$arr_match[1].'>';
			}
		);
		FormatTags::addCode('url_documentation_section',
			static::TAGCODE_URL_DOCUMENT_SECTION,
			function($arr_match) use ($arr_documentation_sections, $documentation_section_id, $arr_mod, $str_url_documentation) {
				
				$use_documentation_id = $arr_match[1];
				$use_documentation_section_id = ((int)$arr_match[2] ?: $documentation_section_id);
				
				if ($use_documentation_id) {

					$arr_mod_external = documentation::findMainDocumentation($use_documentation_id);
					$str_url_documentation = SiteStartEnvironment::getModuleURL($arr_mod_external['id'], $arr_mod_external['page_name'], $arr_mod_external['sub_dir'], true); // Make sure to not use shortcut URLs
					
					$arr_documentation_sections = static::getDocumentationSections($use_documentation_id);
				} else if ($use_documentation_section_id != $documentation_section_id) {
					
					$str_url_documentation = SiteStartEnvironment::getModuleURL($arr_mod['id'], false, 0, true); // Make sure to not use shortcut URLs
				}
				
				$arr_use_documentation_section = $arr_documentation_sections[$use_documentation_section_id];
				
				$url_section = $str_url_documentation.$arr_use_documentation_section['id'].'/'.$arr_use_documentation_section['name'].($arr_match[3] ? '#'.$arr_match[3] : '');
				
				return '<a href="'.$url_section.'">'.$arr_match[4].'</a>';
			}
		);
	}
}
