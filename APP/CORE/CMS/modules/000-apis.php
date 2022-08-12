<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('SITE_APIS', DB::$database_cms.'.site_apis');
DB::setTable('SITE_API_HOSTS', DB::$database_cms.'.site_api_hosts');
DB::setTable('SITE_API_CLIENTS', DB::$database_cms.'.site_api_clients');
DB::setTable('SITE_API_CLIENT_USERS', DB::$database_cms.'.site_api_client_users');

class apis extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}

	public static function getAPIs($api_id = 0) {
		
		$arr = [];

		$res = DB::query("SELECT a.id, a.name, a.clients_user_group_id, a.client_users_user_group_id, a.module, a.request_limit_amount, a.request_limit_unit, a.request_limit_ip, a.request_limit_global, a.documentation_url
							FROM ".DB::getTable('SITE_APIS')." a
						".($api_id ? "WHERE a.id = ".(int)$api_id."" : "")."
		");
		
		while ($arr_api = $res->fetchAssoc()) {
			
			$arr[$arr_api['id']] = $arr_api;
		}
	
		return ($api_id ? current($arr) : $arr);
	}
	
	public static function getAPIHosts($host_name = '') {
		
		$arr = [];

		$res = DB::query("SELECT a.id, a.name, a.module, a.request_limit_amount, a.request_limit_unit, a.request_limit_ip, a.request_limit_global, a.documentation_url,
								ah.host_name
							FROM ".DB::getTable('SITE_API_HOSTS')." ah
							JOIN ".DB::getTable('SITE_APIS')." a ON (a.id = ah.api_id)
						".($host_name ? "WHERE
							(
								ah.host_name = '".DBFunctions::strEscape($host_name)."'
							) OR (
								ah.host_name LIKE ':%'
								AND ".DBFunctions::regexpMatch("'".DBFunctions::strEscape($host_name)."'", "SUBSTRING(ah.host_name FROM 2)")."
							)
						" : "")."
		");
							
		while ($arr_host = $res->fetchAssoc()) {
			
			$arr[$arr_host['host_name']] = $arr_host;
		}
	
		return ($host_name ? current($arr) : $arr);
	}
	
	public static function setAPILimits($id, $request_limit_ip, $request_limit_global) {
		
		$res = DB::query("UPDATE ".DB::getTable('SITE_APIS')." SET
								request_limit_ip = ".(int)$request_limit_ip.",
								request_limit_global = ".(int)$request_limit_global."
							WHERE id = ".(int)$id."
		");
	}
	
	public static function getClient($client_id) {	
		
		$arr = [];

		$res = DB::query("SELECT ac.api_id, ac.name, ac.id, ac.enabled AS enabled, ac.secret AS secret, ac.user_id, ac.time_amount, ac.time_unit, ac.request_limit_disable, a.name AS api_name, ac_u.name AS user_name
								FROM ".DB::getTable('SITE_API_CLIENTS')." ac
								JOIN ".DB::getTable('SITE_APIS')." a ON (a.id = ac.api_id)
								LEFT JOIN ".DB::getTable('TABLE_USERS')." ac_u ON (ac_u.id = ac.user_id)
							WHERE ac.id = '".DBFunctions::strEscape($client_id)."'
								AND (ac.user_id = 0 OR ac_u.id IS NOT NULL)
		");

		$arr = $res->fetchAssoc();
		
		if ($arr) {
			
			$arr['enabled'] = DBFunctions::unescapeAs($arr['enabled'], DBFunctions::TYPE_BOOLEAN);
			$arr['request_limit_disable'] = DBFunctions::unescapeAs($arr['request_limit_disable'], DBFunctions::TYPE_BOOLEAN);
		}

		return $arr;
	}
	
	public static function getClientUser($client_id, $client_user_id) {	
		
		$arr = [];

		$res = DB::query("SELECT ac.api_id, acu.client_id, acu.user_id, ac.enabled AS client_enabled, ac.name AS client_name, ac.request_limit_disable AS client_request_limit_disable, acu.enabled AS enabled, acu.token, acu.date, acu.date_valid, a.name AS api_name, ac_u.name AS client_user_name, acu_u.name AS user_name
								FROM ".DB::getTable('SITE_API_CLIENT_USERS')." acu
								JOIN ".DB::getTable('SITE_API_CLIENTS')." ac ON (ac.id = acu.client_id)
								JOIN ".DB::getTable('SITE_APIS')." a ON (a.id = ac.api_id)
								LEFT JOIN ".DB::getTable('TABLE_USERS')." ac_u ON (ac_u.id = ac.user_id)
								JOIN ".DB::getTable('TABLE_USERS')." acu_u ON (acu_u.id = acu.user_id)
							WHERE acu.client_id = '".DBFunctions::strEscape($client_id)."'
								AND acu.user_id = ".(int)$client_user_id."
								AND (ac.user_id = 0 OR ac_u.id IS NOT NULL)
		");

		$arr = $res->fetchAssoc();
		
		if ($arr) {
			
			$arr['enabled'] = DBFunctions::unescapeAs($arr['enabled'], DBFunctions::TYPE_BOOLEAN);
			$arr['client_enabled'] = DBFunctions::unescapeAs($arr['client_enabled'], DBFunctions::TYPE_BOOLEAN);
			$arr['client_request_limit_disable'] = DBFunctions::unescapeAs($arr['client_request_limit_disable'], DBFunctions::TYPE_BOOLEAN);
		}
		
		return $arr;
	}
	
	public static function getClientUserByToken($token) {	
		
		$arr = [];

		$res = DB::query("SELECT ac.api_id, acu.client_id, acu.user_id, ac.enabled AS client_enabled, ac.name AS client_name, ac.request_limit_disable AS client_request_limit_disable, acu.enabled AS enabled, acu.token, acu.date, acu.date_valid, a.name AS api_name
								FROM ".DB::getTable('SITE_API_CLIENT_USERS')." acu
								JOIN ".DB::getTable('SITE_API_CLIENTS')." ac ON (ac.id = acu.client_id)
								JOIN ".DB::getTable('SITE_APIS')." a ON (a.id = ac.api_id)
							WHERE acu.token = '".DBFunctions::strEscape($token)."'
		");

		$arr = $res->fetchAssoc();
		
		if ($arr) {
			
			$arr['enabled'] = DBFunctions::unescapeAs($arr['enabled'], DBFunctions::TYPE_BOOLEAN);
			$arr['client_enabled'] = DBFunctions::unescapeAs($arr['client_enabled'], DBFunctions::TYPE_BOOLEAN);
			$arr['client_request_limit_disable'] = DBFunctions::unescapeAs($arr['client_request_limit_disable'], DBFunctions::TYPE_BOOLEAN);
		}
		
		return $arr;
	}
		
	public static function handleClient($client_id, $enabled, $arr_client, $regenerate = false) {
		
		if (!$client_id) {
			
			$identifier = generateRandomString(50);
			$secret = generateRandomString(50);
			
			DB::setConnection(DB::CONNECT_CMS);
			
			$do_request_limit_disable = (IS_CMS ? $arr_client['request_limit_disable'] : false);
						
			$res = DB::query("INSERT INTO ".DB::getTable('SITE_API_CLIENTS')."
				(api_id, user_id, id, enabled, secret, name, request_limit_disable, time_amount, time_unit)
					VALUES
				(
					".(int)$arr_client['api_id'].",
					".(int)$arr_client['user_id'].",
					'".DBFunctions::strEscape($identifier)."',
					".DBFunctions::escapeAs($enabled, DBFunctions::TYPE_BOOLEAN).",
					'".DBFunctions::strEscape($secret)."',
					'".DBFunctions::strEscape($arr_client['name'])."',
					".DBFunctions::escapeAs($do_request_limit_disable, DBFunctions::TYPE_BOOLEAN).",
					".(int)$arr_client['time_amount'].",
					".(int)$arr_client['time_unit']."
				)
			");
			
			$client_id = $identifier;
		} else {
			
			if ($regenerate) {
				$secret = generateRandomString(50);
			}
			
			DB::setConnection(DB::CONNECT_CMS);
					
			if (!$arr_client) {
				
				$res = DB::query("UPDATE ".DB::getTable('SITE_API_CLIENTS')." SET
						enabled = ".DBFunctions::escapeAs($enabled, DBFunctions::TYPE_BOOLEAN)."
						".($regenerate ? "
							, secret = '".DBFunctions::strEscape($secret)."'
						" : "")."
					WHERE id = '".DBFunctions::strEscape($client_id)."'
				");
			} else {
								
				$res = DB::query("UPDATE ".DB::getTable('SITE_API_CLIENTS')." SET
						".($arr_client['api_id'] ? "api_id = ".(int)$arr_client['api_id']."," : "")."
						".($arr_client['user_id'] ? "user_id = ".(int)$arr_client['user_id']."," : "")."
						enabled = ".DBFunctions::escapeAs($enabled, DBFunctions::TYPE_BOOLEAN).",
						name = '".DBFunctions::strEscape($arr_client['name'])."',
						".(IS_CMS ? "request_limit_disable = ".DBFunctions::escapeAs($arr_client['request_limit_disable'], DBFunctions::TYPE_BOOLEAN)."," : "")."
						time_amount = ".(int)$arr_client['time_amount'].",
						time_unit = ".(int)$arr_client['time_unit']."
						".($regenerate ? "
							, secret = '".DBFunctions::strEscape($secret)."'
						" : "")."
					WHERE id = '".DBFunctions::strEscape($client_id)."'
				");
			}
		}
		
		DB::setConnection();
		
		return $client_id;
	}
	
	public static function handleClientUser($client_id, $user_id, $enabled, $arr_client_user, $regenerate = false) {
		
		$date_valid = false;
		
		if ($arr_client_user) {
			if ($arr_client_user['date_valid']) {
				$date_valid = DBFunctions::str2Date($arr_client_user['date_valid'].' '.$arr_client_user['date_valid_t']);
			} else if ($arr_client_user['time_amount'] && $arr_client_user['time_unit']) {
				$date_valid = DBFunctions::str2Date(time() + (($arr_client_user['time_amount'] * $arr_client_user['time_unit']) * 60));
			}
		}
		
		if (!$user_id) {
			
			$token = generateRandomString(50);
			
			DB::setConnection(DB::CONNECT_CMS);
			
			$res = DB::query("INSERT INTO ".DB::getTable('SITE_API_CLIENT_USERS')." (client_id, user_id, enabled, token, date, date_valid)
					VALUES
				('".DBFunctions::strEscape($client_id)."', ".(int)$arr_client_user['user_id'].", ".DBFunctions::escapeAs($enabled, DBFunctions::TYPE_BOOLEAN).", '".DBFunctions::strEscape($token)."', NOW(), ".($date_valid ? "'".$date_valid."'" : "NULL").")
			");
		} else {
			
			if ($regenerate) {
				$token = generateRandomString(50);
			}
			
			DB::setConnection(DB::CONNECT_CMS);
			
			if (!$arr_client_user) {
				
				$res = DB::query("UPDATE ".DB::getTable('SITE_API_CLIENT_USERS')." SET
											enabled = ".DBFunctions::escapeAs($enabled, DBFunctions::TYPE_BOOLEAN)."
											".($regenerate ? "
												, token = '".DBFunctions::strEscape($token)."'
											" : "")."
										WHERE client_id = '".DBFunctions::strEscape($client_id)."'
											AND user_id = ".(int)$user_id."
				");
			} else {
					
				$res = DB::query("UPDATE ".DB::getTable('SITE_API_CLIENT_USERS')." SET
											".($arr_client_user['user_id'] ? "user_id = ".(int)$arr_client_user['user_id']."," : "")."
											enabled = ".DBFunctions::escapeAs($enabled, DBFunctions::TYPE_BOOLEAN).",
											date_valid = ".($date_valid ? "'".$date_valid."'" : "NULL")."
											".($regenerate ? "
												, token = '".DBFunctions::strEscape($token)."'
											" : "")."
										WHERE client_id = '".DBFunctions::strEscape($client_id)."'
											AND user_id = ".(int)$user_id."
				");
			}
		}
		
		DB::setConnection();
		
		return $client_id;
	}
	
	public static function delClients($client_id) {
		
		$client_id = DBFunctions::arrEscape($client_id);
			
		$sql_in = "'".(is_array($client_id) ? implode("','", $client_id) : $client_id)."'";
		
		DB::setConnection(DB::CONNECT_CMS);
		
		$res = DB::queryMulti("
			DELETE FROM ".DB::getTable('SITE_API_CLIENTS')." WHERE id IN (".$sql_in.");
			DELETE FROM ".DB::getTable('SITE_API_CLIENT_USERS')." WHERE client_id IN (".$sql_in.");
		");
		
		DB::setConnection();
	}
	
	public static function delClientUsers($client_id, $user_id = 0) {
		
		if (is_array($client_id)) {
			
			$has_user = false;
			$arr_sql_in = [];
			
			foreach ($client_id as $arr_client_user) {
				
				if (is_array($arr_client_user)) {
					
					$has_user = true;
					$arr_sql_in[] = "'".DBFunctions::strEscape($arr_client_user[0])."_".(int)$arr_client_user[1]."'";
				} else {
					
					$arr_sql_in[] = "'".DBFunctions::strEscape($arr_client_user)."'";
				}
			}

			$sql = "DELETE FROM ".DB::getTable('SITE_API_CLIENT_USERS')."
				WHERE ".($has_user ?
					"CONCAT(client_id, '_', user_id) IN (".implode(",", $arr_sql_in).")"
						: 
					"client_id IN (".implode(",", $arr_sql_in).")")."
			";
		} else {
			
			$sql = "DELETE FROM ".DB::getTable('SITE_API_CLIENT_USERS')."
				WHERE client_id = '".DBFunctions::strEscape($client_id)."'
					".($user_id ? "AND user_id = ".(int)$user_id : "")."
			";
		}
		
		DB::setConnection(DB::CONNECT_CMS);
		
		$res = DB::query($sql);
		
		DB::setConnection();
	}
}
