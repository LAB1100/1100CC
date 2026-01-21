<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class WebServiceUser extends WebSocketServerUser {
	
	public $is_owner = false;
	public $arr_data = false;
	
	private $arr_task_options = [];
	private static $arr_task_users = [];
	private static $arr_task_owner_users = [];
	private static $arr_task_passkey_owner_users = [];
		
	public function checkHandshake($str) {
		
		if ((strpos($this->ip, '127.0.0.1') === 0 || strpos($this->ip, '::1') === 0) && trim($str) === 'owner') {
			
			$this->is_owner = true;
			$this->handshake = true;
			
			$this->arr_data = [];
			
			return 'welcome';
		}
		
		return false;
	}

	public function addTaskOptions($str_task, $arr_options) {
		
		$this->arr_task_options[$str_task] = ($arr_options ?: []);
		
		if ($this->is_owner) {
			
			$this->passkey = $arr_options['passkey'];
			
			static::$arr_task_owner_users[$str_task][$this->id] = $this;
			
			$s_arr = &static::$arr_task_passkey_owner_users[$str_task][$this->passkey];
			if (!$s_arr) {
				$s_arr = $this;
			}
		} else {
			
			static::$arr_task_users[$str_task][$this->id] = $this;
		}
	}
	
	public function getTaskOptions($str_task = false) {
		
		if ($str_task) {
			
			return ($this->arr_task_options[$str_task] ?? []);
		} else {
		
			return $this->arr_task_options;
		}
	}
		
	public function remove() {
				
		foreach ($this->arr_task_options as $str_task => $arr_options) {
			
			if ($this->is_owner) {
				
				unset(static::$arr_task_owner_users[$str_task][$this->id]);
				
				if (static::$arr_task_passkey_owner_users[$str_task][$this->passkey] == $this) {
					unset(static::$arr_task_passkey_owner_users[$str_task][$this->passkey]);
				}
			} else {
				
				unset(static::$arr_task_users[$str_task][$this->id]);
			}
		}
		
		parent::remove();
	}
	
	public static function getTaskUsers($str_task) {
		
		return (static::$arr_task_users[$str_task] ?? []);
	}
	
	public static function getTaskOwnerUsers($str_task) {
		
		return (static::$arr_task_owner_users[$str_task] ?? []);
	}
	
	public static function hasTaskOwnerUsers($str_task) {
		
		return (!empty(static::$arr_task_owner_users[$str_task]) ? true : false);
	}
	
	public static function getTaskOwnerUserByPasskey($str_task, $passkey) {
		
		return static::$arr_task_passkey_owner_users[$str_task][$passkey];
	}
}
