<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_FORMS', DB::$database_home.'.def_forms');
DB::setTable('TABLE_FORM_FIELDS', DB::$database_home.'.def_form_fields');
DB::setTable('TABLE_FORM_FIELD_SUB', DB::$database_home.'.def_form_field_sub');
DB::setTable('VAR_COUNTRIES', DB::$database_core.'.def_var_countries');

class cms_forms extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_forms');
		static::$parent_label = getLabel('ttl_site');
	}
	
	private static $arr_field_sub_tables = [];
	
	public static function formFieldSubTable() {
		return [
			'VAR_COUNTRIES' => ['id' => 'VAR_COUNTRIES', 'name' => getLabel('lbl_country'), 'get_id' => 'id', 'get_label' => 'name', 'sort' => 'name']
		];
	}

	public function contents() {
		
		$return = '<div class="section"><h1 id="x:cms_forms:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup add" value="add" /></h1>
		<div class="forms">';

			$res = DB::query("SELECT f.*,
				(SELECT COUNT(fld.id)
					FROM ".DB::getTable('TABLE_FORM_FIELDS')." fld
					WHERE fld.form_id = f.id
				) AS count_fields,
				(SELECT ".DBFunctions::sqlImplode("CONCAT(fld.label, ' (', fld.type, ')')", '<br />')."
					FROM ".DB::getTable('TABLE_FORM_FIELDS')." fld
					WHERE fld.form_id = f.id
				) AS fields,
				".DBFunctions::sqlImplode(DBFunctions::castAs('d.id', DBFunctions::CAST_TYPE_STRING), ',', 'ORDER BY p.id')." AS directories,
				".DBFunctions::sqlImplode('p.name', ',', 'ORDER BY p.id')." AS pages
					FROM ".DB::getTable('TABLE_FORMS')." f					
					LEFT JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.var != '' AND ".DBFunctions::castAs('m.var', DBFunctions::CAST_TYPE_INTEGER)." = f.id AND m.module = 'form')
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
					LEFT JOIN ".DB::getTable('TABLE_DIRECTORIES')." d ON (d.id = p.directory_id)
				GROUP BY f.id
			");
		
			if ($res->getRowCount() == 0) {
				
				$return .= '<section class="info">'.getLabel('msg_no_forms').'</section>';
			} else {
		
				$return .= '<table class="list">
					<thead>
						<tr>
							<th class="max"><span>'.getLabel('lbl_name').'</span></th>
							<th><span>'.getLabel('lbl_email').'</span></th>
							<th class="limit"><span>'.getLabel('lbl_path').'</span></th>
							<th class="limit"><span>'.getLabel('lbl_fields').'</span></th>
							<th></th>
						</tr>
					</thead>
					<tbody>';
						while ($arr_row = $res->fetchAssoc()) {
							
							$arr_pages = str2Array($arr_row['pages'], ',');
							$arr_directories = array_filter(str2Array($arr_row['directories'], ','));
							$arr_paths = [];
							
							for ($i = 0; $i < count($arr_directories); $i++) {
								$arr_dir = directories::getDirectories($arr_directories[$i]);
								if ($arr_dir['id']) {
									$arr_paths[] = $arr_dir['path'].' / '.$arr_pages[$i];
								}
							}
							
							$arr_paths = array_unique($arr_paths);
							
							$return .= '<tr id="x:cms_forms:form_id-'.$arr_row['id'].'">
								<td>'.$arr_row['name'].'</td>
								<td>'.(DBFunctions::unescapeAs($arr_row['send_email'], DBFunctions::TYPE_BOOLEAN) ? ($arr_row['email'] ?: getLabel('email', 'D')) : getLabel('lbl_none')).'</td>
								<td><span class="info"><span class="icon" title="'.($arr_paths ? implode('<br />', $arr_paths) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.count($arr_paths).'</span></span></td>
								<td><span class="info"><span class="icon" title="'.(strEscapeHTML($arr_row['fields']) ?: getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)$arr_row['count_fields'].'</span></span></td>
								<td><input type="button" class="data edit popup edit" value="edit" /><input type="button" class="data del msg del" value="del" /></td>
							</tr>';
						}
					$return .= '</tbody>
				</table>';
			}
						
		$return .= '</div></div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '#frm-forms .sorter.sub { display: block; margin-left: 16px; }
					#frm-forms .sorter.main li.none > span { display: none; }
					#frm-forms .sorter.main select[name*=field_sub_table] { margin: 4px 0px 0px 16px; display: block; }
					#frm-forms .sorter.main .controls { margin: 4px 0px 4px 16px; display: block; }
					#frm-forms input[name=send_email],
					#frm-forms input[name=send_email] + label,
					#frm-forms input[name=email] { vertical-align: middle; }
					#frm-forms input[name=email] { display: none; }
					#frm-forms input[name=send_email]:checked + input[name=email],
					#frm-forms input[name=send_email]:checked + label + input[name=email] { display: inline; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('#frm-forms', function(elm_scripter) {
		
			elm_scripter.on('ajaxloaded scripter', function() {
				elm_scripter.find('.sorter.main select[name*=field_sub_table]').trigger('change');
			}).on('click', '[id^=y\\\:cms_forms\\\:get_field_option]', function() {
				var target = elm_scripter.find('.sorter.main');
				if (target.children('li.none').length) {
					target.empty();
				}
				$(this).quickCommand(target, {html: 'append'});
			}).on('click', '[id^=y\\\:cms_forms\\\:get_subfield_option]', function() {
				$(this).quickCommand($(this).parent('.controls').next('ul'), {html: 'append'});
			}).on('click', 'td > .del', function() {
				elm_scripter.find('.sorter.main').sorter('clean');
			}).on('click', '.sorter.main .del', function() {
				$(this).parent('.controls').next('.sorter.sub').sorter('clean');
			}).on('change', '.sorter.main select[name*=field_sub_table]', function() {
				var target = $(this).closest('li').find('.sorter.sub, .controls');
				if ($(this).val() == false) {
					target.show();
				} else {
					target.hide();
				}
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "edit" || $method == "add") {
		
			if ((int)$id) {
				
				$arr_form_set = self::getFormSet($id);

				$mode = "update";
			} else {
				$mode = "insert";
			}
								
			$this->html = '<form id="frm-forms" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($arr_form_set['details']['name']).'"></div>
					</li>
					<li>
						<label>'.getLabel('lbl_text').'</label>
						<div><textarea name="text">'.strEscapeHTML($arr_form_set['details']['text']).'</textarea></div>
					</li>
					<li>
						<label>'.getLabel('lbl_script').'</label>
						<div><div class="hide-edit'.(!$arr_form_set['details']['script'] ? ' hide' : '').'"><textarea name="script">'.$arr_form_set['details']['script'].'</textarea></div><span class="icon" title="'.getLabel('inf_edit_script').'">'.getIcon('script').'</span></div>
					</li>	
					<li>
						<label>'.getLabel('lbl_types').'</label>
						<div><input type="button" id="y:cms_forms:get_field_option-field" class="data add" value="'.getLabel('lbl_field').'" /><input type="button" id="y:cms_forms:get_field_option-text" class="data add" value="'.getLabel('lbl_text').'" /><input type="button" id="y:cms_forms:get_field_option-check" class="data add" value="'.getLabel("lbl_check").'" /><input type="button" id="y:cms_forms:get_field_option-choice" class="data add" value="'.getLabel("lbl_choice").'" /><input type="button" id="y:cms_forms:get_field_option-choice_dropdown" class="data add" value="'.getLabel("lbl_choice_dropdown").'" /><input type="button" id="y:cms_forms:get_field_option-field_date" class="data add" value="'.getLabel("lbl_field_date").'" /><input type="button" id="y:cms_forms:get_field_option-field_email" class="data add" value="'.getLabel("lbl_field_email").'" /><input type="button" id="y:cms_forms:get_field_option-field_address" class="data add" value="'.getLabel("lbl_field_address").'" /><input type="button" class="data del" title="'.getLabel("inf_remove_empty_fields").'" value="del" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_fields').'</label>
						<div><ul class="sorter main">';
						if ($mode == 'update') {
							foreach ($arr_form_set['fields'] as $value) {
								$this->html .= self::createFieldOption($value);
							}
						} else {
							$this->html .= '<li class="none"><span></span><div>'.getLabel('inf_none').'</div></li>';
						}
						$this->html .= '</ul></div>
					</li>
					<li>
						<label>'.getLabel('lbl_button').'</label>
						<div><input type="text" name="label_button" value="'.strEscapeHTML($arr_form_set['details']['label_button']).'" placeholder="'.getLabel('lbl_send').'"></div>
					</li>
					<li>
						<label>'.getLabel('lbl_response').'</label>
						<div><textarea name="response" placeholder="'.getLabel('msg_form_submitted').'">'.strEscapeHTML($arr_form_set['details']['response']).'</textarea></div>
					</li>
					<li>
						<label>'.getLabel('lbl_redirect_page').'</label>
						<div><select name="redirect_page_id">'.cms_general::createDropdown(pages::getPageNameList(pages::getPages()), $arr_form_set['details']['redirect_page_id'], true).'</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_email').'</label>
						<div><input type="checkbox" name="send_email" value="1"'.($arr_form_set['details']['send_email'] || $mode == 'insert' ? ' checked="checked"' : '').' /><input type="text" name="email" value="'.$arr_form_set['details']['email'].'" placeholder="'.getLabel("email", "D").'"></div>
					</li>
				</ul></fieldset>
			</form>';
			
			$this->validate = ['name' => 'required'];
		}
		
		// POPUP INTERACT
		
		if ($method == "get_field_option") {

			$this->html = self::createFieldOption(false, $id);
		}
		
		if ($method == "get_subfield_option") {

			$arr = explode(".", $id);
			$this->html = self::createSubfieldOption($arr[0], $arr[1]);
		}
				
		// QUERY
	
		if ($method == "insert") {
		
			if (!$_POST['field']) {
				error('Missing information');
			}
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_FORMS')."
				(name, text, script, label_button, response, send_email, email, redirect_page_id)
					VALUES
				('".DBFunctions::strEscape($_POST['name'])."', '".DBFunctions::strEscape($_POST['text'])."', '".DBFunctions::strEscape($_POST['script'])."', '".DBFunctions::strEscape($_POST['label_button'])."', '".DBFunctions::strEscape($_POST['response'])."', ".DBFunctions::escapeAs($_POST['send_email'], DBFunctions::TYPE_BOOLEAN).", '".DBFunctions::strEscape($_POST['email'])."', ".(int)$_POST['redirect_page_id'].")
			");
			
			$new_id = DB::lastInsertID();
			
			self::updateForm($new_id, $_POST['field']);
						
			$this->refresh = true;
			$this->msg = true;
		}
		
		if ($method == "update" && (int)$id) {
					
			$res = DB::query("UPDATE ".DB::getTable('TABLE_FORMS')." SET
					name = '".DBFunctions::strEscape($_POST['name'])."',
					text = '".DBFunctions::strEscape($_POST['text'])."',
					script = '".DBFunctions::strEscape($_POST['script'])."',
					label_button = '".DBFunctions::strEscape($_POST['label_button'])."',
					response = '".DBFunctions::strEscape($_POST['response'])."',
					send_email = ".DBFunctions::escapeAs($_POST['send_email'], DBFunctions::TYPE_BOOLEAN).",
					email = '".DBFunctions::strEscape($_POST['email'])."',
					redirect_page_id = ".(int)$_POST['redirect_page_id']."
				WHERE id = ".(int)$id."
			");
			
			self::updateForm($id, $_POST['field']);
			
			$this->refresh = true;
			$this->msg = true;
		}

		if ($method == "del" && (int)$id) {

			$res = DB::queryMulti("
				DELETE FROM ".DB::getTable('TABLE_FORM_FIELD_SUB')."
					WHERE EXISTS (SELECT TRUE
							FROM ".DB::getTable('TABLE_FORM_FIELDS')." fld
						WHERE fld.form_id = ".(int)$id."
							AND ".DB::getTable('TABLE_FORM_FIELD_SUB').".field_id = fld.id
					)
				;
				
				DELETE FROM ".DB::getTable('TABLE_FORM_FIELDS')."
					WHERE form_id = ".(int)$id."
				;
				
				DELETE FROM ".DB::getTable('TABLE_FORMS')."
					WHERE id = ".(int)$id."
				;
			");
			
			$this->msg = true;
		}
	}
	
	private static function createFieldOption($arr_field, $type = false) {
		
		$type = ($type ?: $arr_field['field_details']['type']);

		if ($type == 'check' || $type == 'choice' || $type == 'choice_dropdown') {
				
			$field_id = ($arr_field['field_details']['field_id'] ?: 'temp_'.(int)($_SESSION['temp_field_id']++));
			
			$return = '<li>'
				.'<span><span class="icon">'.getIcon('updown').'</span></span>'
				.'<div><input type="text" name="field['.$field_id.'][label]" value="'.$arr_field['field_details']['label'].'" /><label><input type="checkbox" name="field['.$field_id.'][required]" value="1"'.($arr_field['field_details']['required'] ? ' checked="checked"' : '').' title="'.getLabel('inf_form_field_required').'" /><span>'.getLabel('lbl_'.$type).'</span></label><input type="hidden" name="field['.$field_id.'][type]" value="'.$type.'" />'
					.'<select name="field['.$field_id.'][field_sub_table]" title="'.getLabel('inf_field_sub_table').'">'.cms_general::createDropdown(self::getModuleFormFieldSubTables(), $arr_field['field_details']['field_sub_table'], true).'</select>'
					.'<span class="controls"><input type="button" class="data add" value="add" id="y:cms_forms:get_subfield_option-'.$type.'.'.$field_id.'" /><input type="button" class="data del" title="'.getLabel('inf_remove_empty_fields').'" value="del" /></span>'
					.'<ul class="sorter sub">';
						
						if ($arr_field['field_subs'] && !$arr_field['field_details']['field_sub_table']) {
							
							foreach($arr_field['field_subs'] as $value_sub) {
								
								$return .= self::createSubfieldOption($type, $field_id, $value_sub);
							}
						} else {
							
							$return .= self::createSubfieldOption($type, $field_id);
						}
					$return .= '</ul>'
				.'</div>
			</li>';
		} else {
						
			$field_id = ($arr_field['field_details']['field_id'] ?: 'temp_'.(int)($_SESSION['temp_field_id']++));
			
			$return = '<li>'
				.'<span><span class="icon">'.getIcon('updown').'</span></span>'
				.'<div><input type="text" name="field['.$field_id.'][label]" value="'.$arr_field['field_details']['label'].'" /><label><input type="checkbox" name="field['.$field_id.'][required]" value="1"'.($arr_field['field_details']['required'] ? ' checked="checked"' : '').' title="'.getLabel('inf_form_field_required').'" /><span>'.getLabel('lbl_'.$type).'</span></label><input type="hidden" name="field['.$field_id.'][type]" value="'.$type.'" /></div>'
			.'</li>';
		}
		
		return $return;
	}
	
	private static function createSubfieldOption($type, $field_id, $arr_sub = []) {
		
		$subfield_id = ($arr_sub['field_sub_id'] ?: 'temp_'.(int)($_SESSION['temp_field_id']++));
		
		if ($type != '') {
			
			$return .= '<li>'
				.'<span><span class="icon">'.getIcon('updown').'</span></span>'
				.'<div><input type="text" name="field['.$field_id.'][sub]['.$subfield_id.'][label]" value="'.$arr_sub['field_sub_label'].'" /></div>'
			.'</li>';
		}
		
		return $return;
	}
	
	public static function getModuleFormFieldSubTables() {
	
		if (self::$arr_field_sub_tables) {
			return self::$arr_field_sub_tables;
		}

		foreach (SiteStartVars::getModules(false, DIR_CMS) as $module => $value) {
			
			if (!method_exists($module, 'formFieldSubTable')) {
				continue;
			}
			
			self::$arr_field_sub_tables = array_merge(self::$arr_field_sub_tables, $module::formFieldSubTable());
		}

		return self::$arr_field_sub_tables;
	}
	
	public static function getFieldSubTableValues($table) {
	
		$arr_field_sub_tables = self::getModuleFormFieldSubTables();

		$arr = [];

		$res = DB::query("SELECT ".$arr_field_sub_tables[$table]['get_id']." AS field_sub_id, ".$arr_field_sub_tables[$table]['get_label']." AS field_sub_label
								FROM ".DB::getTable($table)."
								ORDER BY ".$arr_field_sub_tables[$table]['sort']."");
								
		while($row = $res->fetchAssoc()) {
			
			$arr[$row['field_sub_id']] = $row;
		}		

		return $arr;
	}
	
	private static function updateForm($id, $arr_fields) {
	
		$sort = 0;
		$arr_field_id = [];
		
		foreach($arr_fields as $key => $value) {
		
			if (!$value['label'] || ($value['sub'] && !array_filter(arrValuesRecursive('label', $value['sub'])) && !$value['field_sub_table'])) { // Skip when no main label or, if applicable, no sub labels or sub table
				continue;
			}
		
			$key = explode('_', $key);
			
			if ($key[0] == 'temp') {
				
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_FORM_FIELDS')."
										(form_id, label, type, field_sub_table, required, sort) 
											VALUES
										(".(int)$id.", '".DBFunctions::strEscape($value['label'])."', '".DBFunctions::strEscape($value['type'])."', '".DBFunctions::strEscape($value['field_sub_table'])."', ".DBFunctions::escapeAs($value['required'], DBFunctions::TYPE_BOOLEAN).", ".$sort.")");
				
				$cur_field_id = DB::lastInsertID();
			} else {
				
				$res = DB::query("UPDATE ".DB::getTable('TABLE_FORM_FIELDS')." SET 
						label = '".DBFunctions::strEscape($value['label'])."', type = '".DBFunctions::strEscape($value['type'])."', field_sub_table = '".DBFunctions::strEscape($value['field_sub_table'])."', required = ".DBFunctions::escapeAs($value['required'], DBFunctions::TYPE_BOOLEAN).", sort = ".$sort."
					WHERE id = ".(int)$key[0]."
				");
				
				$cur_field_id = (int)$key[0];
			}
			
			$arr_field_id[] = $cur_field_id;
			$sort++;
			
			if ($value['sub'] && !$value['field_sub_table']) {
			
				$arr_subfield_id = [];
				
				foreach($value['sub'] as $sub_key => $value_sub) {
				
					if (!$value_sub['label']) {
						continue;
					}
					
					$sub_key = explode('_', $sub_key);
					
					if ($sub_key[0] == 'temp') {
						
						$res = DB::query("INSERT INTO ".DB::getTable('TABLE_FORM_FIELD_SUB')."
												(field_id, label, sort)
													VALUES
												(".$cur_field_id.", '".DBFunctions::strEscape($value_sub['label'])."', ".$sort.")
						");
						
						$cur_subfield_id = DB::lastInsertID();
					} else {
						
						$res = DB::query("UPDATE ".DB::getTable('TABLE_FORM_FIELD_SUB')." SET
								label = '".DBFunctions::strEscape($value_sub['label'])."', sort = ".$sort."
							WHERE id = ".(int)$sub_key[0]."
						");
						
						$cur_subfield_id = (int)$sub_key[0];
					}
					
					$arr_subfield_id[] = $cur_subfield_id;
					$sort++;
				}
				
				$res = DB::query("DELETE FROM ".DB::getTable('TABLE_FORM_FIELD_SUB')."
						WHERE field_id = ".$cur_field_id." AND id NOT IN (".(implode(',', $arr_subfield_id) ?: "''").")
				");
			}
		}
		
		$res = DB::queryMulti("
			".DBFunctions::deleteWith(
				DB::getTable('TABLE_FORM_FIELD_SUB'), 'sub', 'field_id',
				"JOIN ".DB::getTable('TABLE_FORM_FIELDS')." fld ON (fld.id = sub.field_id
					AND fld.form_id = ".(int)$id."
					AND fld.id NOT IN (".(implode(',', $arr_field_id) ?: "''").")
				)"
			)."
			;
			DELETE FROM ".DB::getTable('TABLE_FORM_FIELDS')."
				WHERE form_id = ".(int)$id." AND id NOT IN (".(implode(",", $arr_field_id) ?: "''").")
			;
		");
	}
	
	public static function getForms($form_id = 0) {
	
		$arr = [];

		$res = DB::query("SELECT *
			FROM ".DB::getTable('TABLE_FORMS')."
			".($form_id ? "WHERE id = ".(int)$form_id."" : "")."
			 ORDER BY id
		");
								 
		while($row = $res->fetchAssoc()) {
			
			$arr[$row['id']] = $row;
		}		

		return ((int)$form_id ? current($arr) : $arr);
	}
	
	public static function getFormSet($form_id) {
	
		$arr = [];

		$res = DB::query("SELECT
			f.*, fld.id AS field_id, fld.label, fld.type, fld.field_sub_table, fld.required, sub.id AS field_sub_id, sub.label AS field_sub_label
				FROM ".DB::getTable('TABLE_FORMS')." f
				LEFT JOIN ".DB::getTable('TABLE_FORM_FIELDS')." fld ON (fld.form_id = f.id)
				LEFT JOIN ".DB::getTable('TABLE_FORM_FIELD_SUB')." sub ON (sub.field_id = fld.id)
			WHERE f.id = ".(int)$form_id."
			ORDER BY f.id, fld.sort, sub.sort
		");

		while($arr_row = $res->fetchAssoc()) {
			
			if (!$arr['details']) {
				
				$arr_row['send_email'] = DBFunctions::unescapeAs($arr_row['send_email'], DBFunctions::TYPE_BOOLEAN);
				
				$arr['details'] = $arr_row;
			}
			
			$arr_row['required'] = DBFunctions::unescapeAs($arr_row['required'], DBFunctions::TYPE_BOOLEAN);
			
			$arr['fields'][$arr_row['field_id']]['field_details'] = $arr_row;
			$arr['fields'][$arr_row['field_id']]['field_subs'][$arr_row['field_sub_id']] = $arr_row;
		}

		return $arr;
	}
}
