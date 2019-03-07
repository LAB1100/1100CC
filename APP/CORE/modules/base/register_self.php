<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

abstract class register_self extends base_module {

	public static function moduleProperties() {
		static::$parent_label = getLabel('lbl_users');
	}
	
	protected $arr_validate_extra = [];
	abstract protected function extraFields($arr_fields);
	abstract protected function processForm();
		
	public static function moduleVariables() {
		
		$return .= '<select name="user_group_id">'
			.user_groups::createUserGroupsDropdown(user_groups::getUserGroups(), false, true)
		.'</select>'
		.'<input type="checkbox" name="email_as_username" value="1" title="'.getLabel('inf_email_as_username').'" />';

		return $return;
	}
	
	public function contents() {

		enableHTTPS();

		if ($this->arr_query[0] == 'confirm' && (int)$this->arr_query[1] && $this->arr_query[2]) {

			user_management::confirmUser('account', $this->arr_query[1], $this->arr_query[2]);
			
			$return .= '<h1>'.self::$label.'</h1>';
			
			$return .= '<p>'.getLabel('msg_registration_confirmed').'</p>';
				
		} else {
			
			$return .= '<h1>'.self::$label.'</h1>
			
			<form id="f:'.static::class.':user_add-0">
				<fieldset><ul>
					<li><label>'.getLabel('lbl_name_display').'</label><input name="name" type="text" /></li>
					'.(!$this->arr_variables['email_as_username'] ? '<li><label>'.getLabel('lbl_username').'</label><input name="uname" type="text" /></li>' : '').'
					<li><label>'.getLabel('lbl_email').'</label><input name="email" type="text" /></li>
					<li><label>'.getLabel('lbl_password').'</label><input name="password" id="password" type="password" /></li>
					<li><label>'.getLabel('lbl_confirmation').'</label><input name="password_confirm" type="password" /></li>
					'.$this->extraFields($arr_fields).'
					<li><label></label><input type="submit" value="'.getLabel('lbl_register').'" /></li>
				</ul></fieldset>
			</form>';
			
			$arr_validate = ['name' => 'required', 'email' => 'required', 'password' => 'required', 'password_confirm' => ['required' => true, 'equalTo' => '#password']];
			if (!$this->arr_variables['email_as_username']) {
				$arr_validate['uname'] = 'required';
			}
			
			SiteEndVars::addScript("$(document).on('ready', function() {
				$('#f\\\:".static::class."\\\:user_add-0').data('rules', ".json_encode(array_merge($arr_validate, $this->arr_validate_extra)).");
			});");
		}
		
		return $return;
	}
	
	public static function css() {
	
		$return = '';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY
		
		if ($method == "user_add") {
		
			if (!$_POST['password'] || $_POST['password'] != $_POST['password_confirm']) {
				
				error(getLabel('msg_missing_information'));
			}
			
			$user_data = $this->processForm();
			
			$url = SiteStartVars::getModUrl($this->mod_id, false, 0, false).'confirm/';
		
			$user = user_management::addUser(false, ['name' => $_POST['name'], 'uname' => ($this->arr_variables['email_as_username'] ? $_POST['email'] : $_POST['uname']), 'group_id' => ($this->arr_variables['user_group_id'] ?: SiteStartVars::$user_group), 'parent_id' => (int)$user_data['parent_id'], 'email' => $_POST['email']], $_POST['password'], $url);
			
			user_management::updateUserLinkedData($user['id'], $user_data);
			
			$this->reset_form = true;
			$this->msg = true;
		}
	}
}

