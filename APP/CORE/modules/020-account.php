<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class account extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_account');
		static::$parent_label = getLabel('lbl_users');
	}
	
	public static function moduleVar() {
		
		$return .= '<input type="checkbox" name="allow_name" value="1" title="'.getLabel('lbl_allow').' '.getLabel('lbl_name').'" />';
		$return .= '<input type="checkbox" name="allow_uname" value="1" title="'.getLabel('lbl_allow').' '.getLabel('lbl_uname').'" />';
		
		return $return;
	}
	
	public static $arr_external_modules = [];
	
	public function getExternalModule($module) {
		
		getModuleConfiguration('accountSettings');
				
		return self::$arr_external_modules[$module];
	}
	
	public function contents() {
	
		if ($this->arr_query[0] == 'update') {
		
			user_management::confirmUser('update', $this->arr_query[1], $this->arr_query[2]);
					
			$return .= '<h1>'.getLabel('lbl_account').'</h1>';
			
			$return .= '<section class="info attention">'.getLabel('msg_account_update_confirmed').'</section>';
			
		} else {
			
			$arr_languages = cms_language::getLanguage();
			
			foreach ($arr_languages as $lang_code => $arr_language) {
				
				if (!$arr_language['user']) {
					unset($arr_languages[$lang_code]);
				}
			}
			
			$arr_settings = getModuleConfiguration('accountSettings');
				
			$return .= '<h1>Account Setup</h1>
				<form id="f:account:account_update-0">
					<fieldset><ul>
						<li><label>'.getLabel('lbl_name_display').'</label>'.($this->mod_var->allow_name ? '<input name="name" type="text" value="'.$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['name'].'" />' : '<span>'.$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['name'].'</span>').'</li>
						<li><label>'.getLabel('lbl_username').'</label>'.($this->mod_var->allow_uname ? '<input name="uname" type="text" value="'.$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['uname'].'" />' : '<span>'.$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['uname'].'</span>').'</li>
						<li><label>'.getLabel('lbl_email').'</label><input name="email" type="text" value="'.$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['email'].'" /></li>
						'.($arr_languages ? '<li><label>'.getLabel('lbl_language').'</label>'.self::createLanguageMenu($arr_languages, $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['lang_code']).'</li>' : '').'
						<li><label>'.getLabel('lbl_password').'</label><input name="password" id="password" type="password" autocomplete="off" /></li>
						<li><label>'.getLabel('lbl_confirmation').'</label><input name="password_confirm" type="password" /></li>';
					
						foreach ($arr_settings as $arr_setting) {
							foreach ($arr_setting['values']() as $label => $html) {
								$return .= '<li><label>'.$label.'</label><div>'.$html.'</div></li>';
							}
						}
						
						$return .= '<li><label></label><input type="submit" value="'.getLabel('lbl_update').'" /></li>
					</ul></fieldset>
				</form>';
				
		}
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.account .select input[name=lang_code] + span > img { margin-right: 4px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.account', function(elm_scripter) {
		
			elm_scripter.find('#f\\\:account\\\:account_update-0').data('rules', {'name': 'required', 'uname': 'required', 'email': 'required', 'password_confirm': {'equalTo': '#password'}});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY
		
		if ($method == "account_update") {
		
			if ($_POST['password'] && $_POST['password'] != $_POST['password_confirm']) {
				error('Missing information');
			}
						
			$arr_update = ['email' => $_POST['email']];
			if ($this->mod_var->allow_name) {
				$arr_update['name'] = $_POST['name'];
			}
			if ($this->mod_var->allow_uname) {
				$arr_update['uname'] = $_POST['uname'];
			}
			
			$url = SiteStartVars::getModUrl($this->mod_id, false, 0, false).'update/';
									
			$update_user = user_management::updateUser($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id'], true, $arr_update, $_POST['password'], $url);
			
			if ($_POST['lang_code']) {
				
				$arr_language = cms_language::getLanguage('cms', $_POST['lang_code']);
				
				if ($arr_language['user']) {
					
					user_management::updateUserVal(['lang_code' => $_POST['lang_code']], $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id']);
				}
			}
			
			foreach (getModuleConfiguration('accountSettings') as $arr_setting) {
				$arr_setting['update']($_POST);
			}
					
			$this->msg = true;
		}
	}
	
	public static function createLanguageMenu($arr_languages, $selected = "") {
	
		$return .= '<ul class="select">';
		
			foreach($arr_languages as $lang_code => $arr_language) {	
					
				$return .= '<li><label><input type="radio" name="lang_code" value="'.$arr_language['lang_code'].'"'.($lang_code == $selected ? ' checked="checked"' : '').' /><span><img src="/'.DIR_CMS.DIR_FLAGS.$arr_language['flag'].'" />'.$arr_language['label'].'</span></label></li>';
			}
			
		$return .= '</ul>';
		
		return $return;
	}
	
	public static function findAccount() {
	
		return pages::getClosestMod('account', SiteStartVars::$dir['id'], SiteStartVars::$page['id']);
	}
}
