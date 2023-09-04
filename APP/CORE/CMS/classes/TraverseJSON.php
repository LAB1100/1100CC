<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class TraverseJSON {
	
	protected $arr_path = [];
	protected $use_grouping = true;
	
	protected $arr_return = [];
	protected $num_groups = 0;
	
	protected $switch_do_keep_empty;	
	protected $switch_do_key;
	protected $switch_do_json;
	protected $switch_str_join;
	protected $switch_str_split;

    public function __construct($arr_path, $use_grouping = true) {
		
		if (is_string($arr_path)) {
			$this->arr_path = JSON2Value($arr_path);
		} else {
			$this->arr_path = $arr_path;
		}

		$this->use_grouping = $use_grouping;
    }
    	
	public function get($arr) {
		
		if (is_string($arr)) {
			$arr = JSON2Value($arr);
		}
		
		$this->num_groups = 0;
		$this->arr_return = [];
		$num_group_init = ($this->use_grouping ? false : 0); // Use grouping, or collect everything in one group '0'
		
		$this->doWalk($arr, $this->arr_path, $num_group_init);
		
		return $this->arr_return;
	}
	
	public function hasGroups() {
		
		return (!isset($this->arr_return[0])); // Has grouping when there are multiple results in one query, which would start from 1.
	}
	
	protected function doWalk($arr, $arr_path, $num_group = false) {
			
		if (!$arr) {
			
			if ($num_group !== false && !isset($this->arr_return[$num_group])) {
				$this->arr_return[$num_group] = [];
			}
			return;
		}
		
		$num_offset_values = 0;
		$do_offset_new = ($num_group !== false && !empty($this->arr_return[$num_group]));
		
		if ($do_offset_new) {
			$num_offset_values = (is_array($this->arr_return[$num_group]) ? count($this->arr_return[$num_group]) : 1);
			$do_offset_new = false;
		}
		
		foreach ($arr_path as $path_key => $path_value) {
			
			$this->parsePathKey($arr, $path_key);

			if (is_array($path_value)) { // We need to go deeper, and collect
				
				if ($path_key === '[]') {

					foreach ($arr as $key => $value) {
						
						if ($num_group === false) { // Start grouping for each row
							$this->num_groups++;
							$num_group_use = $this->num_groups;
						} else {
							$num_group_use = $num_group;
						}
						
						$this->doWalk($value, $path_value, $num_group_use);
					}
				} else {
					
					$this->doWalk($arr[$path_key], $path_value, $num_group);
				}
			} else if ($num_group === false && $path_key === '[]') { // We reached the end, we want groups, but do not have any groups yet
				
				foreach ($arr as $key => $value) {
					
					$this->num_groups++;
					$num_group_use = $this->num_groups;

					$this->doWalk([$key => $value], [$key => $path_value], $num_group_use);
				}
			} else { // We reached the end, collect specific keys or the full array '[]'
				
				$path_value = (string)$path_value;
				
				$s_arr =& $this->arr_return[$num_group];

				if ($s_arr) {
					
					if (!is_array($s_arr)) {
						
						$this->arr_return[$num_group] = [$s_arr];
						$s_arr =& $this->arr_return[$num_group];
					}
					
					if (strStartsWith($path_key, '+')) {
												
						$arr_use = array_splice($s_arr, $num_offset_values);
						$do_offset_new = true;
						
						if ($arr_use) {
							$path_key = ltrim($path_key, '+');
							if ($path_key !== '') {
								$arr_path_keys = explode(',', $path_key);
								$arr_use = array_filter($arr_use, function ($k) use ($arr_path_keys) { return in_array((int)$k, $arr_path_keys); }, ARRAY_FILTER_USE_KEY);
							}
						}
						
						$s_arr = $this->getValueByPathKeyPathValue($arr_use, $s_arr, '', $path_value, true);
					} else {
						
						if ($do_offset_new) { // Before new values are appended, store the starting offset to make new values available to special operations (+)
							$num_offset_values = count($s_arr);
							$do_offset_new = false;
						}
						
						$s_arr = $this->getValueByPathKeyPathValue($arr, $s_arr, $path_key, $path_value, true);
					}
				} else {
					
					if (strStartsWith($path_key, '+')) {
						
						// Nothing
					} else {
													
						$s_arr = $this->getValueByPathKeyPathValue($arr, $s_arr, $path_key, $path_value);
					}
				}
			}
		}
	}
	
	protected function parsePathKey(&$arr, &$path_key) {
		
		$arr_find_settings = null;
		$do_find_recursive = false;
			
		if (strStartsWith($path_key, '[]') && $path_key !== '[]') {
			
			$arr_find_settings = explode(';', substr($path_key, 2));
		} else if (strStartsWith($path_key, '[*]')) {
			
			$arr_find_settings = explode(';', substr($path_key, 3));
			$do_find_recursive = true;
		}

		if ($arr_find_settings === null) {
			return;
		}
		
		$str_find = null;
		$str_contains_key = null;
		$str_contains_value = null;
		
		foreach ($arr_find_settings as $str_setting) {
			
			if (strStartsWith($str_setting, 'containsKey:')) {
				$str_contains_key = substr($str_setting, 12);
			} else if (strStartsWith($str_setting, 'containsValue:')) {
				$str_contains_value = substr($str_setting, 14);
			} else {
				$str_find = $str_setting;
			}
		}
		
		if ($do_find_recursive) {
			
			$arr = arrValuesRecursive($str_find, $arr);
			$str_find = null;
		}

		foreach ($arr as $key => $value) {
			
			if ($str_find !== null && $key !== $str_find) {
				unset($arr[$key]);
				continue;
			}
			
			if ($str_contains_key !== null) {
				
				if (!is_array($value) || arrHasKeysRecursive($str_contains_key, $value) === null) {
					unset($arr[$key]);
					continue;
				}
			}
			if ($str_contains_value !== null) {
				
				if (!is_array($value)) {
					
					if ($str_contains_value !== $value) {
						unset($arr[$key]);
						continue;
					}
				} else if (arrHasValuesRecursive(false, $str_contains_value, $value) === null) {
					unset($arr[$key]);
					continue;
				}
			}
		}
		
		$path_key = '[]'; // Continue with plain array
	}
	
	protected function getValueByPathKeyPathValue($arr_source, $arr_collect, $path_key, $path_value, $to_existing = false) {
		
		$this->switch_do_keep_empty = false;	
		$this->switch_do_key = false;
		$this->switch_do_json = false;
		$this->switch_str_join = false;
		$this->switch_str_split = false;
		
		if ($path_value) {
			
			$this->switch_do_keep_empty = (strpos($path_value, 'empty') !== false);
			$this->switch_do_key = (strpos($path_value, 'key') !== false);
			$this->switch_do_json = (strpos($path_value, 'json') !== false);
			$num_pos_join = strpos($path_value, 'join:');
			if ($num_pos_join !== false) {
				$this->switch_str_join = substr($path_value, $num_pos_join + 5);
			}
			$num_pos_split = strpos($path_value, 'split:');
			if ($num_pos_split !== false) {
				$this->switch_str_split = substr($path_value, $num_pos_split + 6);
			}
		}

		if ($path_key === '[]' || $path_key === null) { // Grab all array values (as natural end of a path, or the raw/remainder of path)
										
			foreach ($arr_source as $key => $value) {
				
				$arr_collect = $this->getValue($arr_collect, $value, $key, true);
			}
		} else {
			
			if ($path_key === '') {
				$value = $arr_source;
			} else {
				$value = $arr_source[$path_key];
			}
			
			$arr_collect = $this->getValue($arr_collect, $value, $path_key, $to_existing);
		}
				
		return $arr_collect;
	}
	
	protected function getValue($arr, $value, $key, $to_existing) {
		
		if (!$this->switch_do_keep_empty && ($value === false || $value === null || $value === '' || $value === [])) {
			return $arr;
		}
		
		$is_array = false;
			
		if ($this->switch_do_json) {
			$value = value2JSON($value);
		} else {
			if ($this->switch_do_key) {
				$value = $key;
			}
			if ($this->switch_str_split !== false) {
				if (is_array($value)) {
					foreach ($value as &$v) {
						$v = explode($this->switch_str_split, $v);
					}
					unset($v);
					$value = array_merge(...$value);
				} else {
					$value = explode($this->switch_str_split, $value);
				}
				$is_array = true;
			}
			if ($this->switch_str_join !== false) {
				$value = implode($this->switch_str_join, $value);
			}
		}
		
		if ($to_existing) {
			if ($is_array) {
				foreach ($value as $v) {
					$arr[] = $v;
				}
			} else {
				$arr[] = $value;
			}
		} else {
			$arr = $value;
		}
		
		return $arr;
	}
}
