<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_FORM_SUBMISSIONS', DB::$database_home.'.data_form_submissions');
DB::setTable('TABLE_FORM_SUBMISSION_FIELD_INPUT', DB::$database_home.'.data_form_submission_field_input');
DB::setTable('TABLE_FORM_SUBMISSION_FIELD_SUB_INPUT', DB::$database_home.'.data_form_submission_field_sub_input');
DB::setTable('TABLE_FORM_SUBMISSION_INTERNAL_TAGS', DB::$database_home.'.data_form_submission_internal_tags');

class cms_form_submissions extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_form_submissions');
		static::$parent_label = getLabel('ttl_data');
	}
	
	public static function widgetProperties() {
		return [
			'widgetFormSubmissions' => ['label' => getLabel('lbl_form_submissions')]
		];
	}
	
	public static function logUserLocations() {
		return [
			'TABLE_FORM_SUBMISSIONS' => 'log_user_id'
		];
	}
	
	private static $nr_summary_fields = 4;
	
	public static function widgetFormSubmissions() {
		
		$arr_forms = cms_forms::getForms();
		
		if (!$arr_forms) {
			
			$return = '<section class="info">'.getLabel('msg_no_forms').'</section>';
		} else {
			
			$return = '';
			
			foreach ($arr_forms as $form_id => $arr_form) {
				
				$arr_form_set = cms_forms::getFormSet($form_id);
				$arr_form_summary = self::getFormSubmissionsSummary($form_id);
				
				$return .= '<h3>'.$arr_form_set['details']['name'].'</h3>';
					
				$return .= '<table class="list">
					<thead> 
						<tr>';
						
							$count = 1;
							
							foreach ($arr_form_set['fields'] as $field_id => $arr_field) {
								
								if ($count > static::$nr_summary_fields) {
									break;
								}
								
								$return .= '<th class="limit'.(!$count == 0 ? ' max' : '').'"><span>'.$arr_field['field_details']['label'].'</span></th>';
								
								$count++;
							}
							
							$return .= '<th data-sort="desc-0"><span>'.getLabel('lbl_date').'</span></th>
						</tr> 
					</thead>
					<tbody>';
					
						if (!$arr_form_summary) {
							
							$nr_fields = count($arr_form_set['fields']);
							
							$return .= '<tr><td colspan="'.(($nr_fields > static::$nr_summary_fields ? static::$nr_summary_fields : $nr_fields)+1).'" class="empty">'.getLabel('msg_no_data').'</td></tr>';
						} else {
								
							foreach ($arr_form_summary as $submission_id => $arr_submission) {
								
								$return .= '<tr id="x:cms_form_submissions:submission_id-'.$arr_submission['id'].'" class="popup" data-method="view_quick">';
									
									$count = 1;
											
									foreach ($arr_form_set['fields'] as $field_id => $arr_field) {
										
										if ($count > static::$nr_summary_fields) {
											break;
										}
								
										$return .= '<td>'.str_replace('$|$', ' ', $arr_submission['field_'.$field_id]).'</td>';
										
										$count++;
									}
										
									$return .= '<td>'.date('d-m-Y', strtotime($arr_submission['date'])).'</td>';
									
								$return .= '</tr>';
							}
						}
							
					$return .= '</tbody>
				</table>';
			}
		}
		
		return $return;
	}
	
	public function contents() {
		
		$arr_forms = cms_forms::getForms();
		$use_form_id = key($arr_forms);
		
		$form_submission_id = (int)(SiteStartVars::$arr_cms_vars[2] ?? null);
			
		if ($form_submission_id) {

			$arr_form_submission = self::getFormSubmissionSet($form_submission_id);
			
			if ($arr_form_submission) {
				
				$use_form_id = $arr_form_submission['submission']['form_id'];
								
				SiteEndVars::addScript("SCRIPTER.static('#mod-cms_form_submissions', function(elm_scripter) {
					
					var elm_toolbox = getContainerToolbox(elm_scripter);
					var elm_command = $('<div id=\"y:cms_form_submissions:view-".$form_submission_id." \"></div>').appendTo(elm_toolbox);
					
					elm_command.popupCommand();
				});");
			}
		}
		
		$return = '<div class="section"><h1 id="x:cms_forms:new-0"><span>'.self::$label.'</span></h1>
		<div>';

			if ($arr_forms) {
				
				$return .= '<form class="options filter">
					<label>'.getLabel('lbl_form').':</label><select id="y:cms_form_submissions:get_form_submissions-0">'.cms_general::createDropdown($arr_forms, $use_form_id).'</select>
					<label>'.getLabel('lbl_filter').':</label>'.self::createFilterColumns($use_form_id).'
				</form>';
				$return .= '<div class="dynamic-data">'.self::createDataTable($use_form_id).'</div>';
			} else {
				
				$return .= '<section class="info">'.getLabel('msg_no_forms').'</section>';
			}
						
		$return .= '</div></div>';
		
		return $return;
	}
	
	private static function createDataTable($form_id) {
	
		$arr_form_set = cms_forms::getFormSet($form_id);
		
		$arr_columns = (array)$_SESSION['cms_form_submissions'][$form_id]['arr_columns'];
		
		$return = '<table class="display" id="d:cms_form_submissions:data-'.$form_id.'">
		<thead> 
			<tr>';
			
				$max_used = false;
				
				foreach ($arr_form_set['fields'] as $arr_field) {
		
					if ($arr_columns && !in_array($arr_field['field_details']['field_id'], $arr_columns)) {
						continue;
					}
					
					$return .= '<th class="limit'.(!$max_used ? ' max' : '').'"><span>'.$arr_field['field_details']['label'].'</span></th>';
					$max_used = true;
				}
				
				$return .= '<th data-sort="desc-0"><span>'.getLabel('lbl_date').'</span></th>
				<th><span>'.getLabel('lbl_internal_tags').'</span></th>
				<th class="disable-sort"></th>
			</tr> 
		</thead>
		<tbody>
			<tr>
				<td colspan="'.((count($arr_columns) ?: count($arr_form_set['fields']))+3).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
			</tr>
		</tbody>
		</table>';
		
		return $return;
	}
	
	private static function createFilterColumns($form_id) {
	
		$arr_form_set = cms_forms::getFormSet($form_id);
		$arr_columns = $_SESSION['cms_form_submissions'][$form_id]['arr_columns'];
		
		$arr_select = [];
		
		foreach ($arr_form_set['fields'] as $arr_field) {
			
			$arr_select[] = $arr_field['field_details'];
		}
		
		$return = '<div class="input" id="y:cms_form_submissions:filter_form_columns-'.$form_id.'">'.cms_general::createSelector($arr_select, 'filter', ($arr_columns ?: []), 'label', 'field_id').'</div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '.widget.cms_form_submissions > div h3 { margin: 8px 0px; }
			.widget.cms_form_submissions > div h3:first-child { margin-top: 0px; }
			.widget.cms_form_submissions > div > ul > li { margin-top: 2px; }
			.widget.cms_form_submissions > div > ul > li:first-child { margin-top: 0px; }
			.widget.cms_form_submissions > div > ul > li > span { margin-left: 8px; }
			.widget.cms_form_submissions > div > ul > li > span:first-child { margin-left: 0px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-cms_form_submissions', function(elm_scripter) {
		
			elm_scripter.on('change', '[id=y\\\:cms_form_submissions\\\:get_form_submissions-0]', function() {
				var elm_target = elm_scripter.find('[id^=y\\\:cms_form_submissions\\\:filter_form_columns]');
				COMMANDS.setOptions(elm_target, {'html': 'replace'});
				$(this).quickCommand([elm_scripter.find('.dynamic-data'), elm_target]);
			}).on('change', '[id^=y\\\:cms_form_submissions\\\:filter_form_columns] input', function() {
				var elm_target = elm_scripter.find('[id^=y\\\:cms_form_submissions\\\:filter_form_columns]');
				COMMANDS.setData(elm_target, {columns: $.map(elm_target.find('input:checked'), function(n, i) {
					return n.value;
				})});
				elm_target.quickCommand(elm_scripter.find('.dynamic-data'));
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// MAIN PAGE
		
		if ($method == "get_form_submissions") {
		
			$this->html = [self::createDataTable($value), self::createFilterColumns($value)];
		}
		
		if ($method == "filter_form_columns") {
			
			$_SESSION['cms_form_submissions'][$id]['arr_columns'] = $value['columns'];
			
			$this->html = self::createDataTable($id);
		}
		
		// POPUP
		
		if ($method == "edit") {
		
			if ((int)$id) {

				$arr_form_submission = self::getFormSubmissionSet($id);

				$mode = 'update';
			} else {
				$mode = 'insert';
			}
			
			$arr_form_set = cms_forms::getFormSet($arr_form_submission['submission']['form_id']);
									
			$this->html = '<form id="frm-form-submission" data-method="'.$mode.'">
				<fieldset><ul>';
				
				foreach ($arr_form_set['fields'] as $field_id => $arr_field) {
					
					$this->html .= '<li>
						<label>'.Labels::parseTextVariables($arr_field['field_details']['label']).'</label>
						<div>';
					
					if ($arr_field['field_details']['type'] == 'check' || $arr_field['field_details']['type'] == 'choice') {
						
						$this->html .= '<ul class="select">';
							foreach(($arr_field['field_details']['field_sub_table'] ? cms_forms::getFieldSubTableValues($arr_field['field_details']['field_sub_table']) : $arr_field['field_subs']) as $arr_field_sub) {
								$this->html .= '<li><label><input type="'.($arr_field['field_details']['type'] == 'choice' ? 'radio' : 'checkbox').'" name="field['.$field_id.'][]" value="'.$arr_field_sub['field_sub_id'].'"'.($arr_form_submission['fields'][$field_id]['field_subs'][$arr_field_sub['field_sub_id']]['field_sub_value'] ? ' checked="checked"' : '').' /><span>'.Labels::parseTextVariables($arr_field_sub['field_sub_label']).'</span></label></li>';
							}
						$this->html .= '</ul>';
					} else if ($arr_field['field_details']['type'] == 'choice_dropdown') {
						
						$this->html .= '<select name="field['.$field_id.'][]">';
							foreach(($arr_field['field_details']['field_sub_table'] ? cms_forms::getFieldSubTableValues($arr_field['field_details']['field_sub_table']) : $arr_field['field_subs']) as $arr_field_sub) {
								$this->html .= '<option value="'.$arr_field_sub['field_sub_id'].'"'.($arr_form_submission['fields'][$field_id]['field_subs'][$arr_field_sub['field_sub_id']]['field_sub_value'] ? ' selected="selected"' : '').'>'.Labels::parseTextVariables($arr_field_sub['field_sub_label']).'</option>';
							}
						$this->html .= '</select>';
					} else if ($arr_field['field_details']['type'] == 'text') {

						$this->html .= '<textarea name="field['.$field_id.'][]">'.strEscapeHTML($arr_form_submission['fields'][$field_id]['field_input']['field_value']).'</textarea>';
					} else if ($arr_field['field_details']['type'] == 'field_date') {

						$this->html .= '<input type="text" class="datepicker" name="field['.$field_id.'][]" value="'.strEscapeHTML($arr_form_submission['fields'][$field_id]['field_input']['field_value']).'" />';
					} else if ($arr_field['field_details']['type'] == 'field_address') {
						
						$arr_split = explode('$|$', $arr_form_submission['fields'][$field_id]['field_input']['field_value']);
						$this->html .= '<input type="text" name="field['.$field_id.'][0]" value="'.strEscapeHTML($arr_split[0]).'" /><input type="text" name="field['.$field_id.'][1]" title="'.getLabel('lbl_address_number').'" value="'.strEscapeHTML($arr_split[1]).'" /><input type="text" name="field['.$field_id.'][2]" title="'.getLabel('lbl_address_number_affix').'" value="'.strEscapeHTML($arr_split[2]).'" />';
					} else {
						
						$this->html .= '<input type="text" name="field['.$field_id.'][]" value="'.strEscapeHTML($arr_form_submission['fields'][$field_id]['field_input']['field_value']).'" />';
					}
					
					$this->html .= '</div>
						</li>';
				}
				$this->html .= '</ul><fieldset>
			</form>';
		}
		
		if ($method == "view" || $method == "view_quick") {
		
			$arr_form_submission = self::getFormSubmissionSet($id);
			
			if (!$arr_form_submission) {
				
				$this->html = '<p class="info">'.getLabel('msg_not_found').'</p>';
				return;
			}
			
			$arr_form_set = cms_forms::getFormSet($arr_form_submission['submission']['form_id']);
			
			$arr_tags = cms_general::getObjectTags(DB::getTable('TABLE_FORM_SUBMISSION_INTERNAL_TAGS'), 'form_submission_id', $id, true);

			$return = '<div class="record"><dl>
				<li>
					<dt>'.getLabel('lbl_form').'</dt>
					<dd>'.strEscapeHTML($arr_form_set['details']['name']).'</dd>
				</li>
				<li>
					<dt></dt>
					<dd>
						<table><tr>';
			
					$arr_required = [];

					foreach ($arr_form_set['fields'] as $arr_field) {
												
						$return .= '<th>'.strEscapeHTML($arr_field['field_details']['label']).'</th>';
					}
					
					$return .= '</tr><tr>';
					
					foreach ($arr_form_set['fields'] as $field_id => $arr_field) {
					
						$return .= '<td>';
												
						if ($arr_field['field_details']['type'] == 'check' || $arr_field['field_details']['type'] == 'choice' || $arr_field['field_details']['type'] == 'choice_dropdown') {
							
							$return .= '<ul>';
								foreach(($arr_field['field_details']['field_sub_table'] ? cms_forms::getFieldSubTableValues($arr_field['field_details']['field_sub_table']) : $arr_field['field_subs']) as $arr_field_sub) {
									
									if ($arr_form_submission['fields'][$field_id]['field_subs'][$arr_field_sub['field_sub_id']]['field_sub_value']) {
										$return .= '<li>'.strEscapeHTML($arr_field_sub['field_sub_label']).'</li>';
									}
								}
							$return .= '</ul>';
						} else {
							
							$return .= strEscapeHTML(str_replace('$|$', ' ', $arr_form_submission['fields'][$field_id]['field_input']['field_value']));
						}
						
						$return .= '</td>';
					}
			
					$return .= '</tr></table>
					</dd>
				</li>
				<li>
					<dt>'.getLabel('lbl_date').'</dt>
					<dd>'.date('d-m-Y H:i:s', strtotime($arr_form_submission['submission']['date'])).'</dd>
				</li>';
				if ($arr_form_submission['submission']['referral_url']) {
					$return .= '<li>
						<dt>'.getLabel('lbl_referral_url').'</dt>
						<dd>'.$arr_form_submission['submission']['referral_url'].'</dd>
					</li>';
				}
				$return .= '<li>
					<dt>'.getLabel('lbl_url').'</dt>
					<dd>'.$arr_form_submission['submission']['url'].'</dd>
				</li>
				<li>
					<dt>'.getLabel('lbl_user').'</dt>
					<dd>'.cms_log::getLoggedUser($arr_form_submission['submission']['log_user_id']).'</dd>
				</li>
			</dl></div>';
			
			if ($method == "view") {
				
				$return = '<form id="frm-form-submission" data-method="remark">
					'. $return.'
					<hr />
					<fieldset><ul>
						<li>
							<label>'.getLabel('lbl_remark').'</label>
							<div><textarea name="remark">'.strEscapeHTML($arr_form_submission['submission']['remark']).'</textarea></div>
						</li>
						<li>
							<label>'.getLabel('lbl_internal_tags').'</label>
							<div>'.cms_general::createSelectTags($arr_tags, '', !$arr_tags, true).'</div>
						</li>
					</ul></fieldset>
				</form>';
			}
			
			$this->html = $return;
		}
		
		// POPUP INTERACT
				
							
		// QUERY
			
		if ($method == "update" && (int)$id) {
			
			$res = DB::queryMulti("
				DELETE FROM ".DB::getTable('TABLE_FORM_SUBMISSION_FIELD_SUB_INPUT')."
					WHERE form_submission_id = ".(int)$id."
				;
				DELETE FROM ".DB::getTable('TABLE_FORM_SUBMISSION_FIELD_INPUT')."
					WHERE form_submission_id = ".(int)$id."
				;
			");
		
			self::handleFormSubmission($id, $_POST['field']);
						
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "remark" && (int)$id) {
		
			$res = DB::query("UPDATE ".DB::getTable('TABLE_FORM_SUBMISSIONS')." fs SET
				remark = '".DBFunctions::strEscape($_POST['remark'])."'
				WHERE fs.id = ".(int)$id."
			");
								
			cms_general::handleTags(DB::getTable('TABLE_FORM_SUBMISSION_INTERNAL_TAGS'), 'form_submission_id', $id, $_POST['tags'], true);
		
			$this->refresh_table = true;
			$this->msg = true;
		}

		if ($method == "del" && (int)$id) {
			
			$res = DB::queryMulti("
				DELETE FROM ".DB::getTable('TABLE_FORM_SUBMISSION_FIELD_SUB_INPUT')."
					WHERE form_submission_id = ".(int)$id."
				;
				DELETE FROM ".DB::getTable('TABLE_FORM_SUBMISSION_FIELD_INPUT')."
					WHERE form_submission_id = ".(int)$id."
				;
				DELETE FROM ".DB::getTable('TABLE_FORM_SUBMISSIONS')."
					WHERE id = ".(int)$id."
				;
			");
			
			$this->msg = true;
		}
		
		// DATATABLE
					
		if ($method == "data") {
			
			$arr_form_set = cms_forms::getFormSet($id);
			$arr_field_sub_tables = cms_forms::getModuleFormFieldSubTables();
			$arr_columns_selected = $_SESSION['cms_form_submissions'][$id]['arr_columns'];			
			
			$arr_sql_columns = [];
			$arr_sql_columns_search = [];
			$arr_sql_columns_as = ['fs.id'];
			
			foreach ($arr_form_set['fields'] as $field_id => $arr_field) {
			
				if ($arr_columns_selected && !in_array($field_id, $arr_columns_selected)) {
					continue;
				}
				
				$get_label = ($arr_field['field_details']['field_sub_table'] ? $arr_field_sub_tables[$arr_field['field_details']['field_sub_table']]['get_label'] : 'label');
				
				if ($arr_field['field_details']['type'] == 'check') {
					$sql_value = "field_sub_".$field_id.".".$get_label;
				} else if ($arr_field['field_details']['type'] == 'choice' || $arr_field['field_details']['type'] == 'choice_dropdown') {
					$sql_value = "field_sub_".$field_id.".".$get_label;
				} else {
					$sql_value = "field_input_".$field_id.".value";
				}
				
				$arr_sql_columns[] = DBFunctions::sqlImplode('DISTINCT '.$sql_value);
				$arr_sql_columns_search[] = $sql_value;
				$arr_sql_columns_as['field_'.$field_id] = DBFunctions::sqlImplode('DISTINCT '.$sql_value)." AS field_".$field_id;
			}
			
			$arr_sql_columns[] = 'fs.date';
			$arr_sql_columns_search[] = 'fs.date';
			$arr_sql_columns_as[] = 'fs.date';
					
			$sql_column_tags = "(SELECT
				".DBFunctions::sqlImplode('DISTINCT t.name')."
					FROM ".DB::getTable('TABLE_FORM_SUBMISSION_INTERNAL_TAGS')." ot
					LEFT JOIN ".DB::getTable('TABLE_INTERNAL_TAGS')." t ON (t.id = ot.tag_id)
				WHERE ot.form_submission_id = fs.id
			)";
			
			$arr_sql_columns[] = $sql_column_tags;
			$arr_sql_columns_search[] = $sql_column_tags;
			$arr_sql_columns_as[] = $sql_column_tags.' AS tags';
		
			$sql_table = DB::getTable('TABLE_FORM_SUBMISSIONS').' fs';

			$sql_index = 'fs.id';
			
			$sql_where = 'fs.form_id = '.(int)$id;
			
			foreach ($arr_form_set['fields'] as $field_id => $arr_field) {

				if ($arr_field['field_details']['type'] == 'choice' || $arr_field['field_details']['type'] == 'check' || $arr_field['field_details']['type'] == 'choice_dropdown') {
					
					$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_FORM_SUBMISSION_FIELD_SUB_INPUT')." field_sub_input_".$field_id." ON (field_sub_input_".$field_id.".form_submission_id = fs.id AND field_sub_input_".$field_id.".field_id = ".$field_id.")";
				
					if ($arr_field['field_details']['field_sub_table']) {
						$sql_table .= " LEFT JOIN ".DB::getTable($arr_field['field_details']['field_sub_table'])." field_sub_".$field_id." ON (field_sub_".$field_id.".".$arr_field_sub_tables[$arr_field['field_details']['field_sub_table']]['get_id']." = field_sub_input_".$field_id.".field_sub_id)";
					} else {
						$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_FORM_FIELD_SUB')." field_sub_".$field_id." ON (field_sub_".$field_id.".id = field_sub_input_".$field_id.".field_sub_id)";
					}
				} else {
					
					$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_FORM_SUBMISSION_FIELD_INPUT')." field_input_".$field_id." ON (field_input_".$field_id.".form_submission_id = fs.id AND field_input_".$field_id.".field_id = ".$field_id.")";
				}
			}
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{

				$arr_data = [];
				
				$arr_data['id'] = 'x:cms_form_submissions:submission_id-'.$arr_row['id'];
				$arr_data['class'] = 'popup';
				$arr_data['attr']['data-method'] = 'view';
				
				foreach ($arr_form_set['fields'] as $field_id => $arr_field) {
					
					if (!$arr_sql_columns_as['field_'.$field_id]) {
						continue;
					}
					
					$arr_data[] = '<span class="limit">'.str_replace('$|$', ' ', strEscapeHTML($arr_row['field_'.$field_id])).'</span>';
				}
				
				$arr_data[] = date('d-m-Y', strtotime($arr_row['date']));
				$arr_data[] = $arr_row['tags'];
				$arr_data[] = '<input type="button" class="data edit popup edit" value="edit" /><input type="button" class="data del msg del" value="del" />';				
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
	}
	
	public static function handleFormSubmission($id, $arr_fields) {
		
		$arr_form_submission = self::getFormSubmissionSet($id);
		$row_submission = $arr_form_submission['submission'];
									
		$arr_form_set = cms_forms::getFormSet($row_submission['form_id']);
		
		$arr_fields = arrParseRecursive($arr_fields, 'trim');
		
		foreach ($arr_form_set['fields'] as $value) {
						
			if ($value['field_details']['type'] == 'check' || $value['field_details']['type'] == 'choice' || $value['field_details']['type'] == 'choice_dropdown') {
			
				foreach(($value['field_details']['field_sub_table'] ? cms_forms::getFieldSubTableValues($value['field_details']['field_sub_table']) : $value['field_subs']) as $value_sub) {
					
					if ($arr_fields[$value['field_details']['field_id']] && in_array($value_sub['field_sub_id'], $arr_fields[$value['field_details']['field_id']])) {
						
						$res = DB::query("INSERT INTO ".DB::getTable('TABLE_FORM_SUBMISSION_FIELD_SUB_INPUT')."
							(form_submission_id, field_id, field_sub_id, value)
								VALUES
							(".(int)$id.", ".(int)$value['field_details']['field_id'].", ".(int)$value_sub['field_sub_id'].", 1)
						");
					}
				}
			} else {
				
				$field_value = implode('$|$', $arr_fields[$value['field_details']['field_id']]);
				
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_FORM_SUBMISSION_FIELD_INPUT')."
					(form_submission_id, field_id, value)
						VALUES
					(".(int)$id.", ".(int)$value['field_details']['field_id'].", '".DBFunctions::strEscape($field_value)."')
				");
			}
		}
	}
		
	public static function getFormSubmissionSet($submission_id) {
	
		$arr = [];

		$res = DB::query("SELECT
			fs.*, fldi.field_id, subi.field_id AS parent_field_id, fldi.value AS field_value, subi.field_sub_id, subi.value AS field_sub_value, lu.url, lu.referral_url
				FROM ".DB::getTable('TABLE_FORM_SUBMISSIONS')." fs
				LEFT JOIN ".DB::getTable('TABLE_FORM_SUBMISSION_FIELD_INPUT')." fldi ON (fldi.form_submission_id = fs.id)
				LEFT JOIN ".DB::getTable('TABLE_FORM_SUBMISSION_FIELD_SUB_INPUT')." subi ON (subi.form_submission_id = fs.id)
				LEFT JOIN ".DB::getTable('TABLE_LOG_USERS')." lu ON (lu.id = fs.log_user_id)
			WHERE fs.id = ".(int)$submission_id."
		");

		while($row = $res->fetchAssoc()) {
			
			$arr['submission'] = $row;
			$arr['fields'][$row['field_id']]['field_input'] = $row;
			$arr['fields'][$row['parent_field_id']]['field_subs'][$row['field_sub_id']] = $row;
		}

		return $arr;
	}
	
	private static function getFormSubmissionsSummary($form_id, $limit = 3) {
	
		$arr = [];
		
		$arr_form_set = cms_forms::getFormSet($form_id);
		$arr_field_sub_tables = cms_forms::getModuleFormFieldSubTables();
					
		$arr_sql_columns = [];
		$sql_tables = '';
		$count = 1;
		
		foreach ($arr_form_set['fields'] as $field_id => $arr_field) {
			
			if ($count > static::$nr_summary_fields) {
				break;
			}
						
			$get_label = ($arr_field['field_details']['field_sub_table'] ? $arr_field_sub_tables[$arr_field['field_details']['field_sub_table']]['get_label'] : 'label');
			
			if ($arr_field['field_details']['type'] == 'check') {
				$sql_value = "field_sub_".$field_id.".".$get_label;
			} else if ($arr_field['field_details']['type'] == 'choice' || $arr_field['field_details']['type'] == 'choice_dropdown') {
				$sql_value = "field_sub_".$field_id.".".$get_label;
			} else {
				$sql_value = "field_input_".$field_id.".value";
			}
			
			$arr_sql_columns['field_'.$field_id] = DBFunctions::sqlImplode('DISTINCT '.$sql_value)." AS field_".$field_id;
			
			if ($arr_field['field_details']['type'] == 'choice' || $arr_field['field_details']['type'] == 'check' || $arr_field['field_details']['type'] == 'choice_dropdown') {
				
				$sql_tables .= " LEFT JOIN ".DB::getTable('TABLE_FORM_SUBMISSION_FIELD_SUB_INPUT')." field_sub_input_".$field_id." ON (field_sub_input_".$field_id.".form_submission_id = fs.id AND field_sub_input_".$field_id.".field_id = ".$field_id.")";
			
				if ($arr_field['field_details']['field_sub_table']) {
					$sql_tables .= " LEFT JOIN ".DB::getTable($arr_field['field_details']['field_sub_table'])." field_sub_".$field_id." ON (field_sub_".$field_id.".".$arr_field_sub_tables[$arr_field['field_details']['field_sub_table']]['get_id']." = field_sub_input_".$field_id.".field_sub_id)";
				} else {
					$sql_tables .= " LEFT JOIN ".DB::getTable('TABLE_FORM_FIELD_SUB')." field_sub_".$field_id." ON (field_sub_".$field_id.".id = field_sub_input_".$field_id.".field_sub_id)";
				}
			} else {
				
				$sql_tables .= " LEFT JOIN ".DB::getTable('TABLE_FORM_SUBMISSION_FIELD_INPUT')." field_input_".$field_id." ON (field_input_".$field_id.".form_submission_id = fs.id AND field_input_".$field_id.".field_id = ".$field_id.")";
			}
			
			$count++;
		}
		
		$res = DB::query("SELECT
			fs.id, fs.date,
			".implode(',', $arr_sql_columns)."
				FROM ".DB::getTable('TABLE_FORM_SUBMISSIONS')." fs
				".$sql_tables."
			WHERE fs.form_id = ".(int)$form_id."
			GROUP BY fs.id
			ORDER BY fs.date DESC
			LIMIT ".$limit." OFFSET 0
		");
		
		while ($arr_row = $res->fetchAssoc()) {
		
			$arr[$arr_row['id']] = $arr_row;
		}	
		
		return $arr;
	}
}
