<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class WebServiceUser extends WebSocketUser {

	private $arr_service_options = [];
	private static $arr_service_users = [];

	public function addService($service, $arr_options) {
		
		$this->arr_service_options[$service] = ($arr_options ?: []);
		
		self::$arr_service_users[$service][$this->id] = $this;
	}
	
	public function getServiceOptions($service = false) {
		
		if ($service) {
			
			return ($this->arr_service_options[$service] ?: []);
		} else {
		
			return $this->arr_service_options;
		}
	}
	
	public static function getServiceUsers($service) {
		
		return (self::$arr_service_users[$service] ?: []);
	}
	
	public function remove() {
				
		foreach ($this->arr_service_options as $service => $arr_options) {
			
			unset(self::$arr_service_users[$service][$this->id]);
		}
		
		parent::remove();
	}
}
