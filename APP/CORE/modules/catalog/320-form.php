<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class form extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_form');
		static::$parent_label = getLabel('ttl_input');
	}
	
	public static function moduleVariables() {
		
		$return .= '<select>';
		$return .= cms_general::createDropdown(cms_forms::getForms());
		$return .= '</select>';
		
		return $return;
	}
	
	public function contents() {
	
		$arr_form_set = cms_forms::getFormSet($this->arr_variables);
		
		if (!$arr_form_set) {
			return;
		}
		
		$return .= '<h1>'.Labels::parseTextVariables($arr_form_set['details']['name']).'</h1>';
		
		$return .= '<form id="f:form:post-'.$arr_form_set['details']['id'].'">';
						
		$return .= $this->createForm();
		
		$return .= '</form>';
			
		SiteEndVars::addScript("$(document).ready(function() {
			$('[id=f\\\:form\\\:post-".$arr_form_set['details']['id']."]').data('rules', ".json_encode($this->validate).");	
		})");
									
		return $return;
	}
	
	public static function css() {
	
		$return = '.form form > div + fieldset { margin-top: 10px; }
					.form li.field_surname span.input-split > span:first-child { width: 25%; }
					.form li.field_surname span.input-split > span:first-child + span { width: 75%; }
					.form li.field_address span.input-split > span:first-child { width: 60%; }
					.form li.field_address span.input-split > span:first-child + span { width: 25%; }
					.form li.field_address span.input-split > span:first-child + span + span { width: 15%; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY
		if ($method == "post") {

			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_FORM_SUBMISSIONS')." (form_id, date, log_user_id) VALUES (".$this->arr_variables.", NOW(), ".Log::addToUserDB().")");
			$submission_id = DB::lastInsertID();
			
			cms_form_submissions::handleFormSubmission($submission_id, $_POST['field']);
			
			$arr_form = cms_forms::getForms($this->arr_variables);
			
			if ($arr_form['send_email']) {
				
				Labels::setVariable('url', BASE_URL_CMS.'cms_form_submissions/'.$submission_id);
				
				$mail = new Mail();
				$mail->to(($arr_form['email'] ?: getLabel('email', 'D')));
				$mail->subject(getLabel('name', 'D').' '.getLabel('lbl_form_submission').' - '.Labels::parseTextVariables($arr_form['name']));
				$mail->message(getLabel('mail_form_submitted'));
				
				$mail->send();
			}
			
			msg((Labels::parseTextVariables($arr_form['response']) ?: getLabel('msg_form_submitted')), 'FORM', LOG_BOTH, false, false, 3000);
			Log::setMsg(getLabel('msg_success'));
			
			if ($arr_form['redirect_page_id']) {
				$arr_page = pages::getPages($arr_form['redirect_page_id']);
				Response::location(pages::getPageUrl($arr_page));
			} else {
				$this->reset_form = true;
			}
		}
	}
	
	public function createForm() {
	
		$arr_form_set = cms_forms::getFormSet($this->arr_variables);
		
		$this->validate = [];
				
		if ($arr_form_set['details']['text']) {
			$return .= '<div class="body">'.parseBody($arr_form_set['details']['text']).'</div>';
		}
		if ($arr_form_set['details']['script']) {
			SiteEndVars::addScript($arr_form_set['details']['script']);
		}
		$return .= '<fieldset><ul>';
		
		foreach ((array)$arr_form_set['fields'] as $value) {
						
			$return .= '<li class="'.$value['field_details']['type'].'"><label>'.htmlspecialchars(Labels::parseTextVariables($value['field_details']['label'])).'</label>';
				
			if ($value['field_details']['type'] == 'check' || $value['field_details']['type'] == 'choice') {
				
				$return .= '<ul class="select">';
					foreach(($value['field_details']['field_sub_table'] ? cms_forms::getFieldSubTableValues($value['field_details']['field_sub_table']) : $value['field_subs']) as $value_sub) {
						$return .= '<li><label><input type="'.($value['field_details']['type'] == 'choice' ? 'radio' : 'checkbox').'" name="field['.$value['field_details']['field_id'].'][]" value="'.$value_sub['field_sub_id'].'" /><span>'.htmlspecialchars(Labels::parseTextVariables($value_sub['field_sub_label'])).'</span></label></li>';
					}
					$return .= '</ul>';
			} else if ($value['field_details']['type'] == 'choice_dropdown') {
				
				$return .= '<select name="field['.$value['field_details']['field_id'].'][]">';
					if (!$value['field_details']['required']) {
						$return .= '<option value=""></option>';
					}
					foreach(($value['field_details']['field_sub_table'] ? cms_forms::getFieldSubTableValues($value['field_details']['field_sub_table']) : $value['field_subs']) as $value_sub) {
						$return .= '<option value="'.$value_sub['field_sub_id'].'">'.htmlspecialchars(Labels::parseTextVariables($value_sub['field_sub_label'])).'</option>';
					}
					$return .= '</select>';
			} else if ($value['field_details']['type'] == 'text') {
												
				$return .= '<textarea name="field['.$value['field_details']['field_id'].'][]"></textarea>';
			} else if ($value['field_details']['type'] == 'field_date') {
												
				$return .= '<input type="text" class="datepicker" name="field['.$value['field_details']['field_id'].'][]" value="" />';
			} else if ($value['field_details']['type'] == 'field_surname') {
												
				$return .= '<span class="input-split"><span><input type="text" name="field['.$value['field_details']['field_id'].'][0]" title="'.getLabel('lbl_name_insertion').'" value="" /></span><span><input type="text" name="field['.$value['field_details']['field_id'].'][1]" value="" /></span></span>';
			} else if ($value['field_details']['type'] == 'field_address') {
												
				$return .= '<span class="input-split"><span><input type="text" name="field['.$value['field_details']['field_id'].'][0]" value="" /></span><span><input type="text" name="field['.$value['field_details']['field_id'].'][1]" title="'.getLabel('lbl_address_number').'" value="" /></span><span><input type="text" name="field['.$value['field_details']['field_id'].'][2]" title="'.getLabel('lbl_address_number_affix').'" value="" /></span></span>';
			} else {
				
				$return .= '<input type="text" name="field['.$value['field_details']['field_id'].'][]" value="" />';
			}
			$return .= '</li>';
			
			if ($value['field_details']['type'] == 'field_email') {
				$this->validate['field['.$value['field_details']['field_id'].'][]']['email'] = true;
			}
			if ($value['field_details']['type'] == 'field_address') {
				$this->validate['field['.$value['field_details']['field_id'].'][1]']['digits'] = true;
			}
			if ($value['field_details']['required']) {
				if ($value['field_details']['type'] == 'field_surname') {
					$this->validate['field['.$value['field_details']['field_id'].'][1]']['required'] = true;
				} else if ($value['field_details']['type'] == 'field_address') {
					$this->validate['field['.$value['field_details']['field_id'].'][0]']['required'] = true;
					$this->validate['field['.$value['field_details']['field_id'].'][1]']['required'] = true;
				} else {
					$this->validate['field['.$value['field_details']['field_id'].'][]']['required'] = true;
				}
			}
		}
		$return .= '<li><label></label><div><input type="submit" value="Ok" class="invalid" /><input type="submit" value="'.($value['field_details']['label_button'] ? htmlspecialchars(Labels::parseTextVariables($value['field_details']['label_button'])) : getLabel('lbl_send')).'" /><input type="submit" value="Ok" class="invalid" /></div></li>
			</ul></fieldset>';
					
		return $return;
	}
}
