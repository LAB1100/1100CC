<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

abstract class WebServiceTask {
	
	public static $name;
	
	protected $user;
		
	protected $arr_passkeys_data_input = [];
	protected $arr_passkeys_data_output = [];
	
	public function __construct() {
        
	}

	public function init() {

	}
	
	public function setActiveUser($user) {
		
		$this->user = $user;
    }
	
	abstract function check();
	
	// User-Server, persistend, data stored with owner user
	
	public function setData($arr) {
			
		$this->user->arr_data = $arr;
	}
	
	public function getData() {
		
		$arr = $this->user->arr_data;
		
		return $arr;
	}
	
	// User-Client, reset after every iteration

	public function resetUserData() {
		
		$this->arr_passkeys_data_input = [];
		$this->arr_passkeys_data_output = [];
	}
    
	public function setUserData($arr) {
		
		foreach ($arr as $data) {
		
			$this->arr_passkeys_data_input[$this->user->passkey][] = $data;
		}
	}
    
	public function readyUserData() {
			
		$arr = $this->processUserData();
		
		$this->arr_passkeys_data_input[$this->user->passkey] = [];
		$this->arr_passkeys_data_output[$this->user->passkey] = $arr;
	}
    
	abstract protected function processUserData();

	public function getUserData() {
		
		return $this->arr_passkeys_data_output[$this->user->passkey];
	}
}
