<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class WebServiceTaskInputController Extends WebServiceTask {
	
	public static $name = 'input_controller';
	
	public function check() {
		
		return ($this->arr_passkeys_data_input ? true : false);
	}
    
    protected function processUserData() {
		
		if ($this->arr_passkeys_data_output[$this->user->passkey] || !$this->arr_passkeys_data_input[$this->user->passkey]) {
			return $this->arr_passkeys_data_output[$this->user->passkey];
		}
							
		$arr = [];
		
		foreach ($this->arr_passkeys_data_input[$this->user->passkey] as $arr_input) {
			foreach ($arr_input as $key => $value) {
				
				$arr[$key][] = $value;
			}
		}
		
		return $arr;
    }
}
