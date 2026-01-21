<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_USER_PAGE_CLEARANCE', DB::$database_cms.'.user_page_clearance');
DB::setTable('TABLE_USER_ACCOUNT_KEY', DB::$database_cms.'.user_account_key');
DB::setTable('TABLE_USER_WEBSERVICE_KEY', DB::$database_cms.'.user_webservice_key');

class user_management extends base_module {
	
	const MAIL_ACCOUNT = 1;
	const MAIL_ACCOUNT_PASSWORD = 2;

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}

	public static function addUser($is_enabled, $arr_user, $str_password = false, $mail_account = false) {

		if (!$arr_user['name'] || !$arr_user['uname'] || !(int)$arr_user['group_id'] || !filter_var($arr_user['email'], FILTER_VALIDATE_EMAIL)) {
			error(getLabel('msg_missing_information'));
		}
	
		$str_password = ($str_password ?: generateRandomString(10));
		
		DB::setConnection(DB::CONNECT_CMS);
		
		try {
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_USERS')."
				(enabled, name, uname, passhash, group_id, email, parent_id)
					VALUES
				(".DBFunctions::escapeAs($is_enabled, DBFunctions::TYPE_BOOLEAN).", '".DBFunctions::strEscape($arr_user['name'])."', '".DBFunctions::strEscape($arr_user['uname'])."', '".DBFunctions::strEscape(generateHash($str_password))."', ".(int)$arr_user['group_id'].", '".DBFunctions::strEscape($arr_user['email'])."', ".(int)$arr_user['parent_id'].")
			");
		} catch (Exception $e) {
			
			error(getLabel('msg_duplicate_user'));
		}
		
		$id = DB::lastInsertID();
		
		$use_confirmation = ($mail_account && is_string($mail_account)); // Send a confirmation email with a specific confirmation link
		
		try {
			
			if ($mail_account) {
				
				$key = generateRandomString(20);
				
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_USER_ACCOUNT_KEY')."
					(user_id, passkey)
						VALUES
					(".(int)$id.", '".DBFunctions::strEscape($key)."')
				");
				
				if ($use_confirmation) {
					
					if (!$is_enabled) {
						
						self::sendMailConfirmation('account', $id, $mail_account);
					}
				} else if ($is_enabled) {
					
					self::sendMailAccount($id, true);
				}
			}
			
			$debug = print_r($arr_user, true);
			msg(self::getUserTag($id), 'USER NEW', LOG_SYSTEM, $debug);
			
		} catch (Exception $e) {
			
			$res = DB::query("DELETE
					FROM ".DB::getTable('TABLE_USERS')."
				WHERE id = ".(int)$id."
			");
			
			throw($e);
		}
		
		DB::setConnection();

		return ['id' => $id, 'password' => $str_password];
	}
	
	public static function updateUser($id, $is_enabled, $arr_user = false, $str_password = false, $mail_account = false) {
	
		if (!(int)$id || ($arr_user['email'] && !filter_var($arr_user['email'], FILTER_VALIDATE_EMAIL))) {
			error(getLabel('msg_missing_information'));
		}

		$res = DB::query("SELECT u.*
			FROM ".DB::getTable('TABLE_USERS')." u
			WHERE u.id = ".(int)$id."
		");
								
		if (!$res->getRowCount()) {
			error(getLabel('msg_missing_information'));
		}
		
		$arr_row = $res->fetchAssoc();
		
		$use_confirmation = ($mail_account && is_string($mail_account)); // Send a confirmation email with a specific confirmation link
		
		DB::setConnection(DB::CONNECT_CMS);
		
		try {
			
			$res = DB::query("UPDATE ".DB::getTable('TABLE_USERS')." SET
					enabled = ".DBFunctions::escapeAs($is_enabled, DBFunctions::TYPE_BOOLEAN)."
					".($arr_user['name'] ? ", name = '".DBFunctions::strEscape($arr_user['name'])."'" : "")."
					".($arr_user['uname'] ? ", uname = '".DBFunctions::strEscape($arr_user['uname'])."'" : "")."
					".($arr_user['email'] && !$use_confirmation ? ", email = '".DBFunctions::strEscape($arr_user['email'])."'" : "")."
					".($arr_user['parent_id'] ? ", parent_id = ".(int)$arr_user['parent_id']."" : "")."
					".($str_password ? ", passhash = '".DBFunctions::strEscape(generateHash($str_password))."'" : "")."
				WHERE id = ".(int)$id."
			");
		} catch (Exception $e) {
			
			error(getLabel('msg_duplicate_user'));
		}
		
		$debug = print_r($arr_user, true);
		msg(self::getUserTag($id), 'USER UPDATE', LOG_SYSTEM, $debug);
																
		if ($mail_account) {
			
			if ($use_confirmation) {
				
				if ($arr_user['email'] && $arr_row['email'] != $arr_user['email']) {
								
					$key = generateRandomString(20);
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_USER_ACCOUNT_KEY')."
						(user_id, passkey, email_new)
							VALUES
						(".(int)$id.", '".DBFunctions::strEscape($key)."', '".DBFunctions::strEscape($arr_user['email'])."')
						".DBFunctions::onConflict('user_id', ['passkey', 'email_new'])."
					");
														
					self::sendMailConfirmation('update', $id, $mail_account);
				}
			} else if ($is_enabled) {
				
				if ($mail_account == static::MAIL_ACCOUNT_PASSWORD) {
					
					$key = generateRandomString(20);
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_USER_ACCOUNT_KEY')."
						(user_id, passkey)
							VALUES
						(".(int)$id.", '".DBFunctions::strEscape($key)."')
						".DBFunctions::onConflict('user_id', ['passkey'])."
					");
				}
				
				self::sendMailAccount($id);
			}
		}
		
		DB::setConnection();
		
		return true;
	}
	
	public static function delUser($id) {
		
		$id = (int)$id;
		
		if (!$id) {
			error(getLabel('msg_missing_information'));
		}
		
		$msg = self::getUserTag($id);
		
		$arr_tables_info = user_groups::getUserGroupTables(false, $id, false);
		
		DB::setConnection(DB::CONNECT_CMS);

		foreach ($arr_tables_info as $table) {

			$del = DB::query("DELETE
					FROM ".$table['to_table']."
				WHERE ".$table['to_column']." = ".(int)$id."
			");
		}
		
		$res = DB::query("DELETE
				FROM ".DB::getTable('TABLE_USERS')."
			WHERE id = ".(int)$id."
		");
		
		DB::setConnection();
					
		msg($msg, 'USER DELETE', LOG_SYSTEM);
		
				
		return true;
	}
	
	public static function getUserTag($id) {
		
		$arr_user_data = user_groups::getUserData($id);
		
		$return = $arr_user_data[DB::getTableName('TABLE_USERS')]['uname'].' ('.$arr_user_data[DB::getTableName('TABLE_USER_GROUPS')]['name'].')';
		
		return $return;
	}
		
	public static function recoverUser($str_uname, $user_group_id, $str_recover_confirmation_mail_url) {

		$res = DB::query("SELECT
			u.*
				FROM ".DB::getTable('TABLE_USERS')." u
			WHERE u.uname = '".DBFunctions::strEscape($str_uname)."'
				AND u.group_id = ".(int)$user_group_id."
				AND u.enabled = TRUE
		");
								
		if (!$res->getRowCount()) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_BOTH, $str_uname);
		}
		
		$arr_row = $res->fetchAssoc();
		$user_id = $arr_row['id'];
	
		$key = generateRandomString(20);
		
		DB::setConnection(DB::CONNECT_CMS);
		
		$res = DB::query("INSERT INTO ".DB::getTable('TABLE_USER_ACCOUNT_KEY')."
			(user_id, passkey)
				VALUES
			(".(int)$user_id.", '".DBFunctions::strEscape($key)."')
			".DBFunctions::onConflict('user_id', ['passkey'])."
		");
		
		DB::setConnection();
							
		self::sendMailConfirmation('recover', $user_id, $str_recover_confirmation_mail_url);

		return true;
	}
	
	public static function confirmUser($type, $id, $key) {
			
		$res = DB::query("SELECT
			u.*, uk.email_new
				FROM ".DB::getTable('TABLE_USERS')." u
				JOIN ".DB::getTable('TABLE_USER_ACCOUNT_KEY')." uk ON (uk.user_id = u.id)
			WHERE uk.passkey = '".DBFunctions::strEscape($key)."'
				AND u.id = ".(int)$id."
				".($type != 'account' ? "AND u.enabled = TRUE" : "")."
		");

		if (!$res->getRowCount()) {
			error(getLabel('msg_missing_information'));
		}
		
		$arr_row = $res->fetchAssoc();
		
		switch ($type) {
			case 'check':
				return true;
			case 'account':
				self::updateUser($id, true);
				break;
			case 'update':
				self::updateUser($id, true, ['email' => $arr_row['email_new']]);
				break;
			case 'recover':
				break;
			case 'welcome':
				break;
		}
		
		DB::setConnection(DB::CONNECT_CMS);
		
		$res = DB::query("DELETE FROM ".DB::getTable('TABLE_USER_ACCOUNT_KEY')."
			WHERE user_id = ".(int)$id."
		");
		
		DB::setConnection();
		
		return true;
	}
	
	public static function updateUserData($id, $arr_update) {
	
		$arr_set = [];
		
		foreach ($arr_update as $col => $val) {
			
			$arr_set[] = DBFunctions::strEscape($col)." = '".DBFunctions::strEscape($val)."'";
		}
		
		DB::setConnection(DB::CONNECT_CMS);

		$res = DB::query("UPDATE ".DB::getTable('TABLE_USERS')." SET
				".implode(',', $arr_set)."
			WHERE id = ".(int)$id."");
		
		DB::setConnection();
		
		return true;
	}
	
	public static function updateUserLinkedData($id, $arr_columns_data) {
	
		if (!$arr_columns_data) {
			return;
		}
		
		$arr_columns_info = user_groups::getUserGroupColumns(false, $id);
		$arr_tables_info = user_groups::getUserGroupTables(false, $id, false);

		$arr_val = [];
		foreach ($arr_columns_data as $key => $value) {
			
			$arr_database_table_column = explode('.', $key);
			
			switch ($arr_columns_info[$key]['DATA_TYPE']) {
				case 'datetime':
					$value = date('Y-m-d h:i:s', strtotime($value));
					break;
				case 'date':
					$value = date('Y-m-d', strtotime($value));
					break;
				case 'int':
				case 'tinyint':
				case 'smallint':
				case 'mediumint':
				case 'integer':
					$value = (int)$value;
					break;
			}
			
			$arr_val[$arr_database_table_column[0].'.'.$arr_database_table_column[1]][$arr_database_table_column[2]] = $value;
		}
		
		DB::setConnection(DB::CONNECT_CMS);

		foreach ($arr_val as $table => $arr_columns) {

			if (!$arr_tables_info[$table]) {
				continue;
			}

			$arr_columns_sql = DBFunctions::arrEscape(array_keys($arr_columns));
			$arr_values = DBFunctions::arrEscape($arr_columns);
			
			$sql_to_column = $arr_tables_info[$table]['to_column'];
			$sql_get_column = $arr_tables_info[$table]['get_column'];
			$sql_to_table = $arr_tables_info[$table]['to_table'];
			
			if ($arr_tables_info[$table]['multi_target']) {

				$has_data = !empty($arr_values[$sql_get_column]);
				
				if ($has_data) {
				
					$arr_values = $arr_values[$sql_get_column];
					$arr_sql_values = [];
					
					foreach ($arr_values as $key => $value) {
						
						$arr_sql_values[$key] = '('.(int)$id.', '.$value.')';
					}
					
					$res = DB::query("INSERT INTO ".$sql_to_table."
						(".$sql_to_column.", ".$sql_get_column.")
							VALUES 
						".implode(',', $arr_sql_values)."
						".DBFunctions::onConflict($sql_to_column.', '.$sql_get_column, false)."
					");
				}
				
				$del = DB::query("DELETE
						FROM ".$sql_to_table."
					WHERE ".$sql_to_column." = ".(int)$id."
						".($has_data ? "AND ".$sql_get_column." NOT IN (".implode(',', $arr_values).")" : "")."
				");
			} else {

				$str_columns = '"'.implode('","', $arr_columns_sql).'"';
				$str_values = implode("','", $arr_values);
				
				$res = DB::query("INSERT INTO ".$sql_to_table."
					(".$sql_to_column.", ".$str_columns.")
						VALUES
					(".(int)$id.",
					'".$str_values."')
					".DBFunctions::onConflict($sql_to_column, $arr_columns_sql)."
				");
			}
		}
		
		DB::setConnection();
	}
	
	public static function getUser($user_id) {
	
		$res = DB::query("SELECT
				id, name, uname, email, lang_code, group_id
			FROM ".DB::getTable('TABLE_USERS')."
			WHERE id = ".(int)$user_id."	
		");
		
		$arr = $res->fetchAssoc();
		
		return $arr;
	}
	
	public static function getUsersFromGroup($user_group) {
	
		$arr = [];

		$res = DB::query("SELECT
				id, name, uname, email, lang_code, group_id
			FROM ".DB::getTable('TABLE_USERS')."
			WHERE group_id = ".(int)$user_group."
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr[$arr_row['id']] = $arr_row;
		}
		
		return $arr;
	}
	
	public static function getUserAccount($user_id) {
		
		$res = DB::query("SELECT
				u.name, u.uname, u.email, u.lang_code, u.group_id, CASE
					WHEN up.parent_name IS NOT NULL THEN up.parent_name
					ELSE ug.name
				END AS domain, uk.passkey, uk.email_new
			FROM ".DB::getTable('TABLE_USERS')." u
			LEFT JOIN ".DB::getTable('TABLE_USER_ACCOUNT_KEY')." uk ON (uk.user_id = u.id)
			LEFT JOIN ".DB::getTable('TABLE_USER_GROUPS')." ug ON (ug.id = u.group_id)
			LEFT JOIN ".DB::getTable('VIEW_USER_PARENT')." up ON (up.id = u.parent_id)
			WHERE u.id = ".(int)$user_id
		);
		
		if (!$res->getRowCount()) {
			
			error('Invalid user ID.');
		}
		
		$arr = $res->fetchAssoc();
		
		return $arr;
	}
	
	public static function filterUsers($value, $arr_options = [], $num_limit = 20) {
			
		$arr = [];
		
		$arr_limit = [];
		if ($arr_options['group_id']) {
			if (is_array($arr_options['group_id'])) {
				$arr_limit[] = "u.group_id IN (".implode(",", arrParseRecursive($arr_options['group_id'], TYPE_INTEGER)).")";
			} else {
				$arr_limit[] = "u.group_id = ".(int)$arr_options['group_id'];
			}
		}
		if ($arr_options['parent_id']) {
			$arr_limit[] = "u.id = ".(int)$arr_options['parent_id'];
		}
		if ($arr_options['siblings_parent_id']) {
			$arr_limit[] = "u.parent_id = ".(int)$arr_options['siblings_parent_id'];
		}
		if ($arr_options['children_id']) {
			$arr_limit[] = "u.parent_id = ".(int)$arr_options['children_id'];
		}
		if ($arr_options['self_id']) {
			$arr_limit[] = "u.id = ".(int)$arr_options['self_id'];
		}
		if (isset($arr_options['enabled'])) {
			$enabled = ($arr_options['enabled'] != 'all' ? "u.enabled = ".DBFunctions::escapeAs($arr_options['enabled'], DBFunctions::TYPE_BOOLEAN) : '');
		} else {
			$enabled = "u.enabled = TRUE";
		}
		$sql_operator = ($arr_options['reduce'] ? ' AND ' : ' OR ');
		
		if ($value) {
			if ($arr_options['exact']) {
				$sql_value = "(u.uname = '".DBFunctions::strEscape($value)."' OR u.name = '".DBFunctions::strEscape($value)."')";
			} else {
				$sql_value = "(".DBFunctions::searchMatch('u.uname', $value)." OR ".DBFunctions::searchMatch('u.name', $value).")";
			}
		}
		
		$res = DB::query("SELECT
			u.*
				FROM ".DB::getTable('TABLE_USERS')." u
			WHERE ".($enabled ?: "TRUE")."
				".($value ? "AND ".$sql_value : "")."
				".($arr_limit ? "AND (".implode($sql_operator, $arr_limit).")" : "")."
				".($arr_options['arr_filter'] ? "AND u.id IN (".implode(",", $arr_options['arr_filter']).")" : "")."
			ORDER BY u.name
			".($num_limit ? "LIMIT ".(int)$num_limit : "")
		);
	
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr[$arr_row['id']] = $arr_row;
		}

		return $arr;
	}
	
	public static function checkUserIds($user_id, $other_id, $parent_or_group) {
		
		$sql_user_id = (is_array($user_id) ? "IN (".arrParseRecursive($user_id, TYPE_INTEGER).")" : "= ".(int)$user_id);
		
		if ($parent_or_group == 'parent') {
			
			$query = "SELECT id
				FROM ".DB::getTable('TABLE_USERS')."
				WHERE id ".$sql_user_id." AND parent_id != ".(int)$other_id
			;
		} else if ($parent_or_group == 'group') {
			
			$query = "SELECT id
				FROM ".DB::getTable('TABLE_USERS')."
				WHERE id ".$sql_user_id." AND group_id != ".(int)$other_id."
			";
		}
	
		$res_check = DB::query($query);
		
		if ($res_check->getRowCount()) {
			return false;
		} else {
			return true;
		}
	}
	
	protected static function sendMailConfirmation($type, $id, $str_url_base) {
				
		$arr = static::getUserAccount($id);	
		
		$str_url = $str_url_base.$id.'/'.$arr['passkey'];
		
		Labels::setVariable('name', $arr['name']);
		Labels::setVariable('domain', $arr['domain']);
		Labels::setVariable('url', $str_url);
		
		switch ($type) {
			case 'account':
				$str_subject = getLabel('mail_confirm_account_title');
				$str_mail = getLabel('mail_confirm_account');
				$str_msg = getLabel('msg_confirm_account_mail_sent');
				$str_email = $arr['email'];
				break;
			case 'recover':
				$str_subject = getLabel('mail_confirm_account_recover_title');
				$str_mail = getLabel('mail_confirm_account_recover');
				$str_msg = getLabel('msg_confirm_account_recover_mail_sent');
				$str_email = $arr['email'];
				break;
			case 'update':
				$str_subject = getLabel('mail_confirm_account_update_title');
				$str_mail = getLabel('mail_confirm_account_update');
				$str_msg = getLabel('msg_confirm_account_update_mail_sent');
				$str_email = $arr['email_new'];
				break;
		}
		
		$mail = new Mail($str_email, $str_subject, $str_mail);
		$mail->send();
		
		msg($str_msg, 'MAIL');
	}
	
	protected static function sendMailAccount($id, $is_new = false) {
				
		$arr = static::getUserAccount($id);	
						
		$str_subject = getLabel(($is_new ? 'mail_account_new_title' : 'mail_account_changed_title'));
		
		Labels::setVariable('name', $arr['name']);
		Labels::setVariable('domain', $arr['domain']);
		Labels::setVariable('user_name', $arr['uname']);
		
		$str_msg = getLabel(($is_new ? 'mail_account_new' : 'mail_account_changed'));
		
		$arr_module = pages::getClosestModule('login', 0, 0, $arr['group_id']);
		
		if ($arr_module) {
				
			if ($arr['passkey']) { // Possibility to set password
				$str_url = pages::getModuleURL($arr_module).'welcome/'.$id.'/'.$arr['passkey'];
			} else {
				$str_url = pages::getPageURL($arr_module);
			}
			Labels::setVariable('url', $str_url);
			
			$str_msg .= getLabel('mail_account_login_url'.($arr['passkey'] ? '_expire' : ''));
		}
		
		$mail = new Mail($arr['email'], $str_subject, $str_msg);
		$mail->send();
	}
}
