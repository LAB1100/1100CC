<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class login extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_login');
		static::$parent_label = getLabel('lbl_users');
	}
		
	public static function moduleVariables() {
		
		$str_html = '<select>'
			.directories::createDirectoriesDropdown(directories::getDirectories(), false, true)
		.'</select>';
		
		return $str_html;
	}

	public function contents() {
	
		SiteStartEnvironment::requestSecure();
		
		SiteEndEnvironment::setModuleVariables($this->mod_id);
		
		$method = $this->arr_query[0];
		
		$str_html = '';
				
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
				
				$str_html = '<h1>'.getLabel('lbl_login').'</h1>
				
				<section class="info alert">'.$str_message.'</section>';
				
				return $str_html;
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
				
			$str_html .= '<h1>'.getLabel('lbl_login_welcome').'</h1>
			
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
			
			$str_html .= '<h1>'.getLabel('lbl_login_recover').'</h1>
			
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

			$arr_request_vars = SiteStartEnvironment::getModuleVariables(0);
			
			$str_input_error = '';
			if ($arr_request_vars[0] == 'LOGIN_INCORRECT') {
				
				$str_input_error = 'input-error';
				SiteEndEnvironment::setModuleVariables(0, [0 => false], false);
			}
			
			Labels::setVariable('url_recover_password', SiteStartEnvironment::getModuleURL($this->mod_id).'recover');
				
			$str_html .= '<h1>'.getLabel('lbl_login').'</h1>
			
			'.($str_message ? '<section class="info attention">'.$str_message.'</section>' : '').'
			
			<form id="f:login:login-0" autocomplete="on">
				<fieldset><ul>		
					<li><label>'.getLabel('lbl_username').'</label><input name="login_user" type="text" class="'.$str_input_error.'" /></li>
					<li><label>'.getLabel('lbl_password').'</label><input name="login_password" type="password" class="'.$str_input_error.'" /></li>
					<li><label></label><p>'.getLabel('msg_login_recover_link').'</p></li>
					<li><label></label><input type="submit" value="'.getLabel('lbl_login').'" /></li>
				</ul></fieldset>
			</form>';
			
			$this->validate = ['login_user' => 'required', 'login_password' => 'required'];
		}

		return $str_html;
	}
	
	public static function css() {
	
		$str_html = '';
		
		return $str_html;
	}
	
	public static function js() {
	
		$str_html = "";
		
		return $str_html;
	}

	public function commands($method, $id, $value = "") {
		
		// INTERACT
		
		if ($method == 'login') {
			
			if (empty($_POST['login_user']) || empty($_POST['login_password']) || !is_string($_POST['login_user']) || !is_string($_POST['login_password'])) {
				error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
			}
			
			if ($this->arr_variables) {
				
				$arr_directory = directories::getDirectories($this->arr_variables);
				$str_url = str_replace(' ', '', $arr_directory['path']).'/';
			} else {
				
				$arr_directory = null;
				$str_url = SiteStartEnvironment::getBasePath();
			}
			
			HomeLogin::indexProposeUser($_POST['login_user'], $_POST['login_password'], $arr_directory);
			
			Response::location($str_url);
		}
		
		if ($method == 'welcome' || $method == 'recover_confirm') {
			
			$arr_confirm = SiteStartEnvironment::getFeedback('confirm');
			
			if (!$arr_confirm || !$_POST['password'] || $_POST['password'] != $_POST['password_confirm'] || !is_string($_POST['password'])) {
				error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
			}
			
			account::checkPasswordStrength($_POST['password']);
			
			$user_id = (int)$arr_confirm['user_id'];
			$key = $arr_confirm['key'];
			
			if ($method == 'welcome') {
				user_management::confirmUser('welcome', $user_id, $key);
			} else {
				user_management::confirmUser('recover', $user_id, $key);
			}
			
			SiteStartEnvironment::setFeedback('confirm', null, true);
			
			user_management::updateUser($user_id, true, false, $_POST['password']);
						
			$str_url = SiteStartEnvironment::getModuleURL($this->mod_id, false, 0, false);
			$str_url .= ($method == 'welcome' ? 'welcome_confirmed' : 'recover_confirmed');
			
			Response::location($str_url);
		}
		
		if ($method == 'recover') {

			if (empty($_POST['recover_user']) || !is_string($_POST['recover_user'])) {
				error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
			}
			
			if ($this->arr_variables) {
				
				$arr_directory = directories::getDirectories($this->arr_variables);
				$user_group_id = $arr_directory['user_group_id'];
			} else {
				
				$user_group_id = SiteStartEnvironment::getDirectory('user_group_id');
			}
			
			if (!$user_group_id) {
				error(getLabel('msg_missing_information'));
			}
			
			$check = Log::checkRequest('login_recover_home', null, 10, ['ip' => 5, 'ip_block' => 10, 'global' => 100]);
		
			if ($check !== true) {
				error(getLabel('msg_access_limit'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
			}
			
			Log::logRequest('login_recover_home');

			$str_url = SiteStartEnvironment::getModuleURL($this->mod_id, false, 0, false).'recover_confirm/';
						
			user_management::recoverUser($_POST['recover_user'], $user_group_id, $str_url);
			
			$this->reset_form = true;
			$this->message = true;
		}
	}
}
