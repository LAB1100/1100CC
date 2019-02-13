<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_CORE_USERS', DB::$database_core.'.core_users');
DB::setTable('TABLE_CMS_USERS', DB::$database_cms.'.cms_users');

class cms_users extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_cms_users');
		static::$parent_label = getLabel('ttl_settings');
	}
	
	public function contents() {
	
		$return .= '<div class="section"><h1 id="x:cms_users:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup user_add" value="add" /></h1>
		<div class="section-body">';
		
			$return .= '<table class="list">
				<thead>
					<tr>
						<th class="max"><span>'.getLabel('lbl_name').'</span></th>
						<th><span>'.getLabel('lbl_username').'</span></th>
						<th><span>'.getLabel('lbl_email').'</span></th>
						<th></th>
					</tr>
				</thead>
				<tbody>';
					
					$arr_users = self::getCMSUsers();
				
					foreach ($arr_users as $user_id => $arr_user) {
						
						$return .= '<tr id="x:cms_users:user_id-'.$user_id.'">
							<td>'.$arr_user['name'].'</td>
							<td>'.$arr_user['uname'].'</td>
							<td>'.$arr_user['email'].'</td>
							<td><input type="button" class="data edit popup user_edit" value="edit" /><input type="button" class="data del msg user_del" value="del" /></td>
						</tr>';
					}
				$return .= '</tbody>
			</table>';
		
		$return .= '</div></div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '#frm-user .body-content { width: 600px; height: 175px; }
			#frm-user li.biography > label { vertical-align: middle; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "$(document).on('click', '[id^=y\\\:cms_users\\\:my_edit]', function() {
				COMMANDS.popupCommand(this);
			});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// POPUP
		
		if ($method == "user_edit" || $method == "user_add" || $method == "my_edit") {
			
			if ($method == 'my_edit') {
				
				$arr_user = self::getCMSUsers($_SESSION['USER_ID'], $_SESSION['CORE']);
				
				$mode = 'my_update';
			} else if ($id > 0) {
				
				$arr_user = self::getCMSUsers($id);

				$mode = 'user_update';
			} else {
				$mode = 'user_insert';
			}
			
			$default_lang = cms_language::getDefaultLanguage();
						
			$this->html = '<form id="frm-user" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.htmlspecialchars($arr_user['name']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_username').'</label>
						<div><input type="text" name="uname" value="'.htmlspecialchars($arr_user['uname']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_image').'</label>
						<div>'.cms_general::createImageSelector($arr_user['img']).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_language').'</label>
						<div><select name="lang_code">'.cms_general::createDropdown(cms_language::getLanguage(), ($arr_user['lang_code'] ?: $default_lang['lang_code']), false, 'label', 'lang_code').'</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_email').'</label>
						<div><input type="text" name="email" value="'.htmlspecialchars($arr_user['email']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_password').'</label>
						<div><input type="password" id="password" name="password" value="" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_confirmation').'</label>
						<div><input type="password" name="password_confirm" value="" /></div>
					</li>
				</ul>
				<hr />
				<ul>
					<li class="biography">
						<label>'.getLabel('lbl_biography').'</label>
						<div>'.cms_general::editBody($arr_user['biography'], 'biography').'</div>
					</li>
				</ul>
				<hr />
				<ul>
					<li>
						<label>'.getLabel('lbl_labeler').'</label>
						<div><input type="checkbox" name="labeler" value="1"'.($arr_user['labeler'] ? ' checked="checked"' : '').' /></div>
					</li>
				</ul></fieldset>
			</form>';
			
			$this->validate = ['name' => 'required', 'uname' => 'required', 'email' => 'required', 'password_confirm' => ['equalTo' => '#password']];
		}
				
		// QUERY
		
		if ($method == "user_del" && $id > 0) {
			
			$res = DB::query("DELETE FROM ".DB::getTable('TABLE_CMS_USERS')." WHERE id = ".(int)$id." LIMIT 1");
			
			$this->msg = true;
		}
		
		if (($method == "user_update" && $id > 0) || $method == "my_update") {
		
			if (!$_POST['name'] || !$_POST['uname'] || $_POST['password'] != $_POST['password_confirm']) {
				error('Missing information');
			}
			
			$id = ($id ?: $_SESSION['USER_ID']);
			
			$res = DB::query("UPDATE ".DB::getTable(($method == 'my_update' && $_SESSION['CORE'] ? 'TABLE_CORE_USERS' : 'TABLE_CMS_USERS'))." SET
					name = '".DBFunctions::strEscape($_POST['name'])."',
					uname = '".DBFunctions::strEscape($_POST['uname'])."',
					lang_code = '".DBFunctions::strEscape($_POST['lang_code'])."',
					email = '".DBFunctions::strEscape($_POST['email'])."',
					img = '".DBFunctions::strEscape($_POST['img'])."',
					biography = '".DBFunctions::strEscape($_POST['biography'])."',
					".($_POST['password'] ? "passhash = '".DBFunctions::strEscape(generateHash($_POST['password']))."'," : "")."
					labeler = ".DBFunctions::escapeAs($_POST['labeler'], DBFunctions::TYPE_BOOLEAN)."
				WHERE id = ".(int)$id);
				
			if ($method == "user_update") {
				$this->refresh = true;
			}
			
			$this->msg = true;
		}
		
		if ($method == "user_insert") {
		
			if (!$_POST['password'] || $_POST['password'] != $_POST['password_confirm'] || !$_POST['name'] || !$_POST['uname']) {
				error('Missing information');
			}
		
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_CMS_USERS')."
				(name, uname, lang_code, email, img, biography, passhash, labeler)
					VALUES
				('".DBFunctions::strEscape($_POST['name'])."', '".DBFunctions::strEscape($_POST['uname'])."', '".DBFunctions::strEscape($_POST['lang_code'])."', '".DBFunctions::strEscape($_POST['email'])."', '".DBFunctions::strEscape($_POST['img'])."', '".DBFunctions::strEscape($_POST['biography'])."', '".DBFunctions::strEscape(generateHash($_POST['password']))."', ".DBFunctions::escapeAs($_POST['labeler'], DBFunctions::TYPE_BOOLEAN).")
			");
		
			$this->refresh = true;
			$this->msg = true;
		}
	}
	
	public static function getCMSUsers($cms_user_id = 0, $core = false) {

		$arr = [];

		$res = DB::query("SELECT *
				FROM ".DB::getTable(($core ? 'TABLE_CORE_USERS' : 'TABLE_CMS_USERS'))."
			".($cms_user_id ? "WHERE id = ".(int)$cms_user_id."" : "ORDER BY id")
		);
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['labeler'] = DBFunctions::unescapeAs($arr_row['labeler'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[$arr_row['id']] = $arr_row;
		}
		
		return ($cms_user_id ? current($arr) : $arr);
	}
	
	public static function getCMSUserByUsername($username, $core = false) {
		
		$res = DB::query("SELECT *
				FROM ".DB::getTable(($core ? 'TABLE_CORE_USERS' : 'TABLE_CMS_USERS'))."
			WHERE uname = '".DBFunctions::strEscape($username)."'
		");
		
		$arr = $res->fetchAssoc();
		
		if ($arr) {
			
			$arr['labeler'] = DBFunctions::unescapeAs($arr['labeler'], DBFunctions::TYPE_BOOLEAN);
			$arr['core'] = $core;
		}
		
		return $arr;
	}
}
