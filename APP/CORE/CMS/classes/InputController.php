<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class InputController {
	
	public $name = 'input_controller';
	
	protected $arr_data = [];
	protected $cur_passkey = 0;
	
	public function __construct() {
        
	}

    public function init() {
		
		
    }
    
    public function setOptions($passkey, $arr) {
		
		$this->cur_passkey = $passkey;
    }
    
    public function check() {
		
		return ($this->arr_data ? true : false);
	}
	
	public function reset() {
		
		$this->arr_data = [];
    }
    
    public function set($passkey, $arr) {
		
		$this->arr_data[$passkey][] = $arr;
    }
    
    public function ready() {
				
		if (!$this->arr_data[$this->cur_passkey] || isset($this->arr_data[$this->cur_passkey]['ready'])) {
			return;
		}
			
		$arr = [];
		
		foreach ($this->arr_data[$this->cur_passkey] as $arr_input) {
			foreach ($arr_input as $key => $value) {
				
				$arr[$key][] = $value;
			}
		}
		
		$this->arr_data[$this->cur_passkey] = ['ready' => $arr];
    }
        
    public function get() {
		
		return $this->arr_data[$this->cur_passkey]['ready'];
    }
}
