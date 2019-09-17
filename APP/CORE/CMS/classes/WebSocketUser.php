<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class WebSocketUser {

	public $socket;
	public $ip;
	public $id;
	public $arr_headers = [];
	public $path = '';
	
	public $handshake = false;
	
	public $passkey = false;
	public $passkey_user_id = false;
	public $date_passkey_owner = false;
	
	public $has_received_partial_packet = false;
	public $received_buffer_partial = "";
	public $received_message_partial = "";
	
	public $is_sending_continuous = false;
	public $arr_send_messages = [];
	
	public $timeout_alive = 300; // Seconds (5 minutes)
	public $time_message = false;
	public $has_sent_close = false;
	
	function __construct($id, $socket) {
		
		$this->id = $id;
		$this->socket = $socket;
		
		$this->ip = stream_socket_get_name($socket, true);
		
		$this->alive();
	}
	
	public function remove() {
		
		if ($this->date_passkey_owner) {
			
			self::deletePasskey($this->passkey_user_id, $this->passkey, $this->date_passkey_owner);
		}
	}
	
	public function alive() {
		
		$this->time_message = time();
	}
	
	public function isDead($time) {
		
		if (($time - $this->time_message) > $this->timeout_alive) {
			return true;
		}
		
		return false;
	}
	
	public function checkPasskey($user_id, $key) {
		
		if (!(int)$user_id || !$key) {
			return false;
		}
		
		$res = DB::query("SELECT date_active FROM ".DB::getTable('TABLE_USER_WEBSERVICE_KEY')."
							WHERE user_id = ".(int)$user_id." AND passkey = '".DBFunctions::strEscape($key)."'
		");
		
		$pass = ($res->getRowCount() ? true : false);
		
		if ($pass) {
			
			$this->passkey = $key;
			$this->passkey_user_id = $user_id;
			
			$arr = $res->fetchAssoc();

			if (!$arr['date_active']) {
				
				$date = DBFunctions::str2Date(time());
				
				DB::setConnection(DB::CONNECT_CMS);
				
				$res = DB::query("UPDATE ".DB::getTable('TABLE_USER_WEBSERVICE_KEY')." SET
								date_active = '".$date."'
							WHERE user_id = ".(int)$user_id." AND passkey = '".DBFunctions::strEscape($key)."'
								AND date_active IS NULL
				");
				
				if ($res->getAffectedRowCount()) {
					
					$this->date_passkey_owner = $date;
				}
				
				DB::setConnection();
			}
		}
		
		return $pass;
	}
	
	public static function getPasskey($user_id) {
		
		$res = DB::query("SELECT passkey, date_active FROM ".DB::getTable('TABLE_USER_WEBSERVICE_KEY')."
							WHERE user_id = ".(int)$user_id."
		");
		
		$arr = ($res->fetchAssoc() ?: []);
		
		return $arr;
	}
	
	public static function usePasskey($user_id, $force = false) {
		
		$arr_passkey = self::getPasskey($user_id);
		
		if ($arr_passkey['date_active'] || $force) {
			
			SiteStartVars::setCookie('webservice_user_id', $user_id, true);
			SiteStartVars::setCookie('webservice_passkey', $arr_passkey['passkey'], true);
			
			return true;
		} else {
			
			return false;
		}
	}
	
	public static function setPasskey($user_id, $key) {
		
		DB::setConnection(DB::CONNECT_CMS);
		
		$res = DB::query("INSERT INTO ".DB::getTable('TABLE_USER_WEBSERVICE_KEY')."
			(user_id, passkey)
				VALUES
			(".(int)$user_id.", '".DBFunctions::strEscape($key)."')
			".DBFunctions::onConflict('user_id', ['passkey'], 'date_active = NULL')."
		");
			
		DB::setConnection();
	}
		
	public static function deletePasskey($user_id, $key, $date = false) {
		
		DB::setConnection(DB::CONNECT_CMS);
		
		$res = DB::query("DELETE FROM ".DB::getTable('TABLE_USER_WEBSERVICE_KEY')."
			WHERE user_id = ".(int)$user_id."
				AND passkey = '".DBFunctions::strEscape($key)."'
				".($date ? "AND date_active = '".$date."'" : "")."
		");
		
		DB::setConnection();
	}
}
