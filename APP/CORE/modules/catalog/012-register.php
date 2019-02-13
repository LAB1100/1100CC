<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class register extends register_self {

	public static function moduleProperties() {
		parent::moduleProperties();
		self::$label = getLabel('ttl_register');
	}
	
	protected function extraFields($arr_fields) {
		
		$return = '';
		
		return $return;
		
		$this->arr_validate_extra = [];
	}
	
	protected function processForm() {
		
		$user_data = [];
		
		return $user_data;
	}
}
