<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class login extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_login');
		static::$parent_label = getLabel('lbl_users');
	}
		
	public static function moduleVariables() {
		
		$return = '<select>';
		$return .= directories::createDirectoriesDropdown(directories::getDirectories(), false, true);
		$return .= '</select>';
		
		return $return;
	}

	public function contents() {
	
		SiteStartEnvironment::requestSecure();
		
		SiteEndEnvironment::setModuleVariables($this->mod_id);
		
		$method = $this->arr_query[0];
		
		$return = '';
				
		if ($method == 'welcome' || $method == 'recover_confirm') {
			
			$user_id = (int)$this->arr_query[1];
			$key = $this->arr_query[2];
			$arr_confirm = SiteStartEnvironment::getFeedback('confirm');
			
			if (!$arr_confirm || $user_id != $arr_confirm['user_id'] || $key != $arr_confirm['key']) {

				$arr_confirm = ['user_id' => $user_id, 'key' => $key];
				SiteEndEnvironment::setFeedback('confirm', $arr_confirm, true);
			}
			
			$user_id = (int)$arr_confirm['user_id'];
			$key = $arr_confirm['key'];
			
			try {
				
				user_management::confirmUser('check', $user_id, $key);				
			} catch (Exception $e) {
				
				$str_message = getLabel('msg_login_confirm_incorrect');
				
				$return = '<h1>'.getLabel('lbl_login').'</h1>
				
				<section class="info alert">'.$str_message.'</section>';
				
				return $return;
			}
			
			SiteEndEnvironment::setModuleVariables($this->mod_id, [$method, $user_id, $key]);
			
			$arr_user = user_groups::getUserData($user_id, true);
			Labels::setVariable('name', $arr_user[DB::getTableName('TABLE_USERS')]['name']);
			Labels::setVariable('user_name', $arr_user[DB::getTableName('TABLE_USERS')]['uname']);
			Labels::setVariable('user_email', $arr_user[DB::getTableName('TABLE_USERS')]['email']);
			$domain = strEscapeHTML(($arr_user[DB::getTableName('VIEW_USER_PARENT')]['parent_name'] ?: $arr_user[DB::getTableName('TABLE_USER_GROUPS')]['name']));
			Labels::setVariable('domain', $domain);
			
			if ($method == 'welcome') {
				$str_message = getLabel('msg_login_welcome');
			} else {
				$str_message = getLabel('msg_login_recover_confirm');
			}
				
			$return .= '<h1>'.getLabel('lbl_login_welcome').'</h1>
			
			<section class="info attention">'.$str_message.'</section>
			
			<form id="f:login:'.$method.'-0">
				<fieldset><ul>
					<li><label>'.getLabel('lbl_password').'</label><input name="password" id="password" type="password" autocomplete="off" /></li>
					<li><label>'.getLabel('lbl_confirmation').'</label><input name="password_confirm" type="password" /></li>
					<li><label></label><input type="submit" value="'.getLabel('lbl_send').'" /></li>
				</ul></fieldset>
			</form>';
			
			$this->validate = ['password' => 'required', 'password_confirm' => ['equalTo' => '#password']];
		} else if ($method == 'recover') {
			
			SiteEndEnvironment::setModuleVariables($this->mod_id, [$method]);
		
			Labels::setVariable('url_login', SiteStartEnvironment::getPageURL());
			
			$return .= '<h1>'.getLabel('lbl_login_recover').'</h1>
			
			<form id="f:login:recover-0" autocomplete="on">
				<fieldset><ul>
					<li><label>'.getLabel('lbl_username').'</label><input name="recover_user" type="text" /></li>
					<li><label></label><p>'.getLabel('msg_login_recover_link_return').'</p></li>
					<li><label></label><input type="submit" value="'.getLabel('lbl_send').'" /></li>
				</ul></fieldset>
			</form>';
			
			$this->validate = ['recover_user' => 'required'];
		} else {
			
			$str_message = false;
			if ($method == 'welcome_confirmed') {
				$str_message = getLabel('msg_login_welcome_confirmed');
			} else if ($method == 'recover_confirmed') {
				$str_message = getLabel('msg_login_recover_confirmed');
			} else {
				$method = false;
			}
			
			if ($method) {
				SiteEndEnvironment::setModuleVariables($this->mod_id, [$method]);
			}
		
			if ($this->arr_variables) {
				$arr_dir = directories::getDirectories($this->arr_variables);
				$str_path = str_replace(' ', '', $arr_dir['path']).'/';
			} else {
				$str_path = SiteStartEnvironment::getBasePath();
			}
			
			$arr_request_vars = SiteStartEnvironment::getModuleVariables(0);
			
			$str_input_error = '';
			if ($arr_request_vars[0] == 'LOGIN_INCORRECT') {
				
				$str_input_error = 'input-error';
				SiteEndEnvironment::setModuleVariables(0, [0 => false], false);
			}
			
			Labels::setVariable('url_recover_password', SiteStartEnvironment::getModuleURL($this->mod_id).'recover/');
				
			$return .= '<h1>'.getLabel('lbl_login').'</h1>
			
			'.($str_message ? '<section class="info attention">'.$str_message.'</section>' : '').'
			
			<form method="post" action="'.$str_path.'" autocomplete="on">
				<fieldset><ul>		
					<li><label>'.getLabel('lbl_username').'</label><input name="login_user" type="text" class="'.$str_input_error.'" /></li>
					<li><label>'.getLabel('lbl_password').'</label><input name="login_ww" type="password" class="'.$str_input_error.'" /></li>
					<li><label></label><p>'.getLabel('msg_login_recover_link').'</p></li>
					<li><label></label><input type="submit" value="'.getLabel('lbl_login').'" /></li>
				</ul></fieldset>
			</form>';
			
			$this->validate = ['login_user' => 'required', 'login_ww' => 'required'];
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
		
		// INTERACT
		
		if ($method == "welcome" || $method == "recover_confirm") {
			
			$arr_confirm = SiteStartEnvironment::getFeedback('confirm');
			
			if (!$arr_confirm || !$_POST['password'] || $_POST['password'] != $_POST['password_confirm']) {
				error(getLabel('msg_missing_information'));
			}
			
			$user_id = (int)$arr_confirm['user_id'];
			$key = $arr_confirm['key'];
			
			if ($method == 'welcome') {
				user_management::confirmUser('welcome', $user_id, $key);
			} else {
				user_management::confirmUser('recover', $user_id, $key);
			}
			
			SiteStartEnvironment::setFeedback('confirm', null, true);
			
			user_management::updateUser($user_id, true, false, $_POST['password']);
						
			$url = SiteStartEnvironment::getModuleURL($this->mod_id, false, 0, false);
			$url .= ($method == 'welcome' ? 'welcome_confirmed/' : 'recover_confirmed/');
			
			Response::location($url);
		}
		
		if ($method == "recover") {

			if ($this->arr_variables) {
				$dir = directories::getDirectories($this->arr_variables);
				$user_group_id = $dir['user_group_id'];
			} else {
				$user_group_id = SiteStartEnvironment::getDirectory('user_group_id');
			}
		
			if (!$_POST['recover_user'] || !$user_group_id) {
				error(getLabel('msg_missing_information'));
			}
			
			$url = SiteStartEnvironment::getModuleURL($this->mod_id, false, 0, false).'recover_confirm/';
						
			user_management::recoverUser($_POST['recover_user'], $user_group_id, $url);
			
			$this->reset_form = true;
			$this->msg = true;
		}
	}
}
