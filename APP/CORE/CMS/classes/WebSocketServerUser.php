<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class WebSocketServerUser {

	public $socket;
	public $ip;
	public $id;
	public $arr_headers = [];
	public $path = '';
	
	public $handshake = false;
	
	public $passkey = false;
	public $passkey_user_id = false;
	
	public $has_received_partial_packet = false;
	public $buffer_partial_packet = '';
	public $buffer_partial_frame = '';
	
	public $is_sending_continuous = false;
	public $arr_send_messages = [];
	
	public static $timeout_passkey = 120; // Seconds (2 minutes)
	public static $timeout_alive = 300; // Seconds (5 minutes)
	public $time_alive = false;
	public $time_passkey = false;
	public $has_sent_close = false;
	
	function __construct($id, $socket) {
		
		$this->id = $id;
		$this->socket = $socket;
		
		$this->ip = stream_socket_get_name($socket, true);
		
		$this->alive();
	}
	
	public function checkHandshake($str) {
		
		return false;
	}
	
	public function remove() {
		
	}
		
	public function alive() {
		
		$this->time_alive = time();
		
		if ($this->time_passkey === false) {
			$this->time_passkey = $this->time_alive;
		}
		
		if (($this->time_alive - $this->time_passkey) > (static::$timeout_passkey / 2)) {
			
			static::updatePasskey($this->passkey_user_id, $this->passkey);
			
			$this->time_passkey = $this->time_alive;
		}
	}
	
	public function isDead($time) {
		
		if (($time - $this->time_alive) > static::$timeout_alive) {
			return true;
		}
		
		return false;
	}

	public function checkPasskey($user_id, $key) {
		
		if (!(int)$user_id || !$key) {
			return false;
		}
		
		$res = DB::query("SELECT
			TRUE
				FROM ".DB::getTable('TABLE_USER_WEBSERVICE_KEY')."
			WHERE user_id = ".(int)$user_id." AND passkey = '".DBFunctions::strEscape($key)."'
				AND (date_active IS NULL OR date_active >= (NOW() - ".DBFunctions::interval(static::$timeout_passkey, 'SECOND')."))
		");
		
		$is_valid = ($res->getRowCount() ? true : false);
		
		if ($is_valid) {
			
			$this->passkey = $key;
			$this->passkey_user_id = $user_id;
		}
		
		return $is_valid;
	}
	
	public static function getPasskey($user_id) {
		
		$res = DB::query("SELECT
			passkey, date_active
				FROM ".DB::getTable('TABLE_USER_WEBSERVICE_KEY')."
			WHERE user_id = ".(int)$user_id."
				AND (date_active IS NULL OR date_active >= (NOW() - ".DBFunctions::interval(static::$timeout_passkey, 'SECOND')."))
				
		");
		
		$arr = $res->fetchAssoc();
		
		return $arr;
	}
	
	public static function usePasskey($user_id) {
		
		$arr_passkey = static::getPasskey($user_id);
		
		if ($arr_passkey) {
			
			SiteStartEnvironment::setCookie('webservice_user_id', $user_id, true);
			SiteStartEnvironment::setCookie('webservice_passkey', $arr_passkey['passkey'], true);
			
			return $arr_passkey['passkey'];
		} else {
			
			return false;
		}
	}
	
	public static function setPasskey($user_id, $key, $no_timeout = false) {
		
		DB::setConnection(DB::CONNECT_CMS);
		
		$res = DB::query("INSERT INTO ".DB::getTable('TABLE_USER_WEBSERVICE_KEY')."
			(user_id, passkey, date_active)
				VALUES
			(".(int)$user_id.", '".DBFunctions::strEscape($key)."', ".($no_timeout ? "NULL" : "NOW()").")
			".DBFunctions::onConflict('user_id', ['passkey', 'date_active'])."
		");
			
		DB::setConnection();
	}
	
	public static function updatePasskey($user_id, $key) {
		
		DB::setConnection(DB::CONNECT_CMS);
		
		$res = DB::query("UPDATE ".DB::getTable('TABLE_USER_WEBSERVICE_KEY')." SET
				date_active = NOW()
			WHERE user_id = ".(int)$user_id." AND passkey = '".DBFunctions::strEscape($key)."'
				AND date_active IS NOT NULL
		");
		
		DB::setConnection();
	}
		
	public static function deletePasskey($user_id, $key) {
		
		DB::setConnection(DB::CONNECT_CMS);
		
		$res = DB::query("DELETE FROM ".DB::getTable('TABLE_USER_WEBSERVICE_KEY')."
			WHERE user_id = ".(int)$user_id."
				AND passkey = '".DBFunctions::strEscape($key)."'
		");
		
		DB::setConnection();
	}
}
