<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class TraverseJSON {
	
	const SYMBOL_ACTION_PROCESS_BEFORE = '<';
	const SYMBOL_ACTION_PROCESS_AFTER = '>';
	const SYMBOL_ACTION_APPEND = '+';
	const SYMBOL_ACTION_REVISIT = '=';
	
	const SYMBOL_ACTION_SEPARATOR = ';';
	const SYMBOL_ACTION_SEPARATOR_LIST = ',';
	
	const PROCESS_APPEND = 1; // Append to collection
	const PROCESS_PATCH = 2; // Append to collection, grouped/revisit by key
	const PROCESS_UNIQUE = 3;
	
	const PATH_KEY_UNIQUE = '`';
	
	protected $arr_path = [];
	protected $arr_key_unique = [];
	protected $do_grouping = true;
	
	protected $arr_return = [];
	protected $num_groups = 0;
	
	protected $switch_do_keep_empty;	
	protected $switch_do_key;
	protected $switch_do_json;
	protected $switch_do_unique;
	protected $switch_str_join;
	protected $switch_str_split;

    public function __construct($arr_path, $do_grouping = true) {
		
		$this->arr_path = $this->preparePath($arr_path);

		$this->do_grouping = $do_grouping;
    }
    
    public function set($arr) {
		
		if (is_string($arr)) {
			$arr = JSON2Value($arr);
		}
				
		$this->num_groups = 0;
		$this->arr_return = [];
		$num_group_init = ($this->do_grouping ? false : 0); // Use grouping, or collect everything in one group '0'
		
		$this->doWalk($arr, $this->arr_path, $num_group_init);
	}
    	
	public function get($do_force_grouped = false) {

		if (!$this->do_grouping && !$do_force_grouped) {
			return ($this->arr_return[0] ?? []);
		}
		
		return $this->arr_return;
	}
	
	public function getString($do_force_grouped = false, $mode_process = false) {
		
		$arr = $this->get($do_force_grouped);
		
		if (!$this->do_grouping && !$do_force_grouped) {
			
			return $this->parseValueToString($arr, $mode_process);
		}
		
		foreach ($arr as $key => &$value) {
			
			$value = $this->parseValueToString($value, $mode_process);
		}
		
		return $arr;
	}
	
	protected function parseValueToString($arr, $mode = false) {
		
		if (!is_array($arr)) { // Single plain value
			return $arr;
		}
		
		if ($mode === static::PROCESS_UNIQUE) {
			
			$str = '';
			$arr_check = [];
			
			foreach ($arr as $value) {
				
				if (is_array($value)) {
					$value = arr2StringRecursive($value, ' ');
				}
				
				if (isset($arr_check[$value])) {
					continue;
				}
				
				$str .= ($str !== '' ? ' ' : '').$value;
				$arr_check[$value] = true;
			}

			return $str;
		}
		
		$str = arr2StringRecursive($arr, ' ');
		
		return $str;
	}
	
	public function hasGroups() {
		
		return (!isset($this->arr_return[0]) && $this->arr_return); // Has grouping when there are multiple results in one query, which would start from 1.
	}
	
	protected function doWalk($arr_source, $arr_path, $num_group = false, $mode_process = false) {
			
		if (!$arr_source) {
			
			if ($num_group !== false && !isset($this->arr_return[$num_group])) {
				$this->arr_return[$num_group] = [];
			}
			return;
		}
		
		$num_offset_values = 0;
		$do_offset_new = false;
		$func_check_offset = function() use (&$num_group, &$num_offset_values, &$do_offset_new) { // Before new values are appended, store the starting offset to make new values available to special operations (<)
			
			if (!$do_offset_new) {
				return;
			}

			$num_offset_values = (is_array($this->arr_return[$num_group]) ? count($this->arr_return[$num_group]) : 1);
			$do_offset_new = false;
		};
		
		$do_offset_new = ($num_group !== false && !empty($this->arr_return[$num_group]));
		
		foreach ($arr_path as $str_path_key_unique => $path_value) {
			
			$str_path_key = $str_path_key_unique;
			$arr = $arr_source;
			$this->parsePathKey($str_path_key, $arr);

			if (is_array($path_value)) { // We need to go deeper, and collect
				
				$func_check_offset();
				
				if ($str_path_key === '[]') {

					foreach ($arr as $key => $value) {
						
						if ($num_group === false) { // Start grouping for each row
							
							$this->num_groups++;
							$num_group_use = $this->num_groups;
						} else {
							
							$num_group_use = $num_group;
						}
						
						$this->doWalk($value, $path_value, $num_group_use, $mode_process);
					}
				} else {
					
					$this->doWalk($arr[$str_path_key], $path_value, $num_group, $mode_process);
				}
			} else if ($num_group === false && $str_path_key === '[]') { // We reached the end, we want groups, but do not have any groups yet
				
				foreach ($arr as $key => $value) {
					
					$this->num_groups++;
					$num_group_use = $this->num_groups;

					$this->doWalk([$key => $value], [$key => $path_value], $num_group_use, $mode_process);
				}
			} else { // We reached the end or a special operation, collect specific keys or the full array '[]'
				
				$str_path_value = (string)$path_value;
				$str_path_key_identifier = substr($str_path_key, 0, 1);
				
				if ($str_path_key_identifier == static::SYMBOL_ACTION_PROCESS_AFTER) {
					
					$mode_process = static::PROCESS_APPEND;
					$str_path_key = ltrim($str_path_key, static::SYMBOL_ACTION_PROCESS_AFTER); // Get the key-value itself
					$str_extra = substr($str_path_key, 0, 1);
					if ($str_extra == static::SYMBOL_ACTION_REVISIT) {
						$mode_process = static::PROCESS_PATCH;
					}
					$str_path_key = ltrim($str_path_key, static::SYMBOL_ACTION_REVISIT.static::SYMBOL_ACTION_APPEND); // Get the key-value itself
					
					$arr_path_key = explode(static::SYMBOL_ACTION_SEPARATOR_LIST, $str_path_key);
					$arr_identifier = [];
					
					foreach ($arr_path_key as $str_path_key) {
						
						$str_path_key_check = $str_path_key;
						$arr_check = $arr;
						$this->parsePathKey($str_path_key_check, $arr_check);
						
						$arr_identifier = $this->getValueByPathKeyPathValue($arr_check, $arr_identifier, $str_path_key_check, $str_path_value);
					}
					
					$num_group = (is_array($arr_identifier) ? arr2StringRecursive($arr_identifier, '-') : $arr_identifier);
				} else {
						
					$s_arr =& $this->arr_return[$num_group];

					if ($s_arr) {
						
						if (!is_array($s_arr)) {
							
							$this->arr_return[$num_group] = [$s_arr];
							$s_arr =& $this->arr_return[$num_group];
						}
						
						$mode_process = ($mode_process ?: self::PROCESS_APPEND);
						
						if ($str_path_key_identifier == static::SYMBOL_ACTION_PROCESS_BEFORE) {
													
							$arr_use = array_splice($s_arr, $num_offset_values);
							$do_offset_new = true;
							
							if ($arr_use) {
								
								$str_extra = ltrim($str_path_key, static::SYMBOL_ACTION_PROCESS_BEFORE); // Target specific indices
								
								if ($str_extra !== '') {
									
									$arr_keys = explode(static::SYMBOL_ACTION_SEPARATOR_LIST, $str_extra);
									$arr_use = array_filter($arr_use, function ($k) use ($arr_keys) {
										return in_array($k, $arr_keys);
									}, ARRAY_FILTER_USE_KEY);
								}
							}
							
							$s_arr = $this->getValueByPathKeyPathValue($arr_use, $s_arr, $str_path_key_unique, $str_path_value, $mode_process);
						} else if ($str_path_key_identifier == static::SYMBOL_ACTION_APPEND) {
							
							$func_check_offset();
							
							$s_arr = $this->getValueFromPathKeyPathValue($s_arr, $str_path_key_unique, $str_path_value, $mode_process);
						} else {
							
							$func_check_offset();
							
							$s_arr = $this->getValueByPathKeyPathValue($arr, $s_arr, $str_path_key_unique, $str_path_value, $mode_process);
						}
					} else {
						
						$mode_process = ($mode_process ?: false);
						
						if ($str_path_key_identifier == static::SYMBOL_ACTION_PROCESS_BEFORE) {
							
							// Nothing
						} else if ($str_path_key_identifier == static::SYMBOL_ACTION_APPEND) {
							
							$s_arr = $this->getValueFromPathKeyPathValue($s_arr, $str_path_key_unique, $str_path_value, $mode_process);
						} else {
														
							$s_arr = $this->getValueByPathKeyPathValue($arr, $s_arr, $str_path_key_unique, $str_path_value, $mode_process);
						}
					}
				}
			}
		}
	}
	
	protected function parsePathKey(&$str_path_key, &$arr) {
		
		$str_path_key = $this->translatePathKey($str_path_key);
		
		$arr_find_settings = null;
		$do_find_recursive = false;
			
		if (strStartsWith($str_path_key, '[]') && $str_path_key !== '[]') {
			
			$arr_find_settings = explode(static::SYMBOL_ACTION_SEPARATOR, substr($str_path_key, 2));
		} else if (strStartsWith($str_path_key, '[*]')) {
			
			$arr_find_settings = explode(static::SYMBOL_ACTION_SEPARATOR, substr($str_path_key, 3));
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
		
		$str_path_key = '[]'; // Continue with plain array
	}
	
	protected function getValueByPathKeyPathValue($arr_source, $arr_collect, $str_path_key_unique, $str_path_value, $mode_process = false) {
		
		$this->switch_do_keep_empty = false;	
		$this->switch_do_key = false;
		$this->switch_do_json = false;
		$this->switch_do_unique = false;
		$this->switch_str_join = false;
		$this->switch_str_split = false;
		
		if ($str_path_value) {
			
			$this->switch_do_keep_empty = (strpos($str_path_value, 'empty') !== false);
			$this->switch_do_key = (strpos($str_path_value, 'key') !== false);
			$this->switch_do_json = (strpos($str_path_value, 'json') !== false);
			$this->switch_do_unique = (strpos($str_path_value, 'unique') !== false);
			$num_pos_join = strpos($str_path_value, 'join:');
			if ($num_pos_join !== false) {
				$this->switch_str_join = substr($str_path_value, $num_pos_join + 5);
			}
			$num_pos_split = strpos($str_path_value, 'split:');
			if ($num_pos_split !== false) {
				$this->switch_str_split = substr($str_path_value, $num_pos_split + 6);
			}
		}
		
		$str_path_key = $this->translatePathKey($str_path_key_unique);

		if ($str_path_key === '[]' || $str_path_key === null) { // Grab all array values (as natural end of a path, or the raw/remainder of path)
			
			foreach ($arr_source as $key => $value) {
				
				$arr_collect = $this->getValue($arr_collect, $value, $key, ($mode_process ?: static::PROCESS_APPEND));
			}
		} else {
			
			if (strStartsWith($str_path_key, static::SYMBOL_ACTION_PROCESS_BEFORE)) {
				$value = $arr_source;
			} else {
				$value = $arr_source[$str_path_key];
			}
			
			$arr_collect = $this->getValue($arr_collect, $value, $str_path_key_unique, $mode_process);
		}
				
		return $arr_collect;
	}
	
	protected function getValue($arr, $value, $str_key, $mode_process) {
		
		if (!$this->switch_do_keep_empty && ($value === false || $value === null || $value === '' || $value === [])) {
			return $arr;
		}
		
		$is_array = false;
			
		if ($this->switch_do_json) {
			
			$value = value2JSON($value);
		} else {
			
			if ($this->switch_do_key) {
				$value = $str_key;
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
				
				if (is_array($value)) {
					
					$value = arr2StringRecursive($value, $this->switch_str_join);
					$is_array = false;
				}
			}
		}
		
		if ($mode_process === static::PROCESS_APPEND) {
			
			if ($this->switch_do_unique && is_array($value)) {
				$value = arrUniqueValuesRecursive(false, $value);
			}
			
			if ($is_array) {
				
				foreach ($value as $v) {
					$arr[] = $v;
				}
			} else {
				
				$arr[] = $value;
			}
		} else if ($mode_process === static::PROCESS_PATCH) {
			
			if ($is_array) {
								
				foreach ($value as $v) {
					
					if (isset($arr[$str_key])) {
						$arr[$str_key] = arrMerge((array)$arr[$str_key], (array)$v);
					} else {
						$arr[$str_key] = (array)$v;
					}
				}
			} else {
				
				if (isset($arr[$str_key])) {
					$arr[$str_key] = arrMerge((array)$arr[$str_key], (array)$value);
				} else {
					$arr[$str_key] = (array)$value;
				}
			}
			
			if ($this->switch_do_unique) {
				$arr[$str_key] = arrUniqueValuesRecursive(false, $arr[$str_key]);
			}
		} else {
			
			$arr = $value;
		}
		
		return $arr;
	}
	
	protected function getValueFromPathKeyPathValue($arr, $str_path_key_unique, $str_path_value, $mode_process = false) {
		
		$value = $str_path_value;
		
		if ($mode_process === static::PROCESS_APPEND) {
			$arr[] = $value;
		} else if ($mode_process === static::PROCESS_PATCH) {
			$arr[$str_path_key_unique] = $value;
		} else {
			$arr = $value;
		}
		
		return $arr;
	}
	
	protected function preparePath($str) {

		if (!is_string($str)) {
			return $str;
		}
		
		$num_count_keys = 1;
		$func_keys = function($arr_matches) use (&$num_count_keys) {
			
			$str_key = $arr_matches[2];
			$str_key_unique = $str_key.str_pad('', $num_count_keys, static::PATH_KEY_UNIQUE, STR_PAD_RIGHT);
			
			$this->arr_key_unique[$str_key_unique] = $str_key;
			$num_count_keys++;
				
			return $arr_matches[1].$str_key_unique.$arr_matches[3];
		};
		
		$arr_path = [];
		
		if (strStartsWith($str, '---')) {

			$str = preg_replace_callback('/^(\s*[\'"]?)(.*?)([\'"]?\s*:)/m', $func_keys, $str);
			
			$arr_path = YAML2Value($str);
		} else {
			
			$str = preg_replace_callback('/([\{,]\s*[\'"])(.*?)([\'"]\s*:)/', $func_keys, $str);
			
			$arr_path = JSON2Value($str);
		}
		
		return $arr_path;
	}
	
	protected function translatePathKey($str_key_unique) {
		
		return ($this->arr_key_unique[$str_key_unique] ?? $str_key_unique);
	}
}
