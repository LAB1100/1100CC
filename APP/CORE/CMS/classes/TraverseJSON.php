<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
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
	protected $arr_key_unique_parsed = [];
	protected $do_grouping = true;
	protected $in_grouping_check = false;
	
	protected $arr_return = [];
	protected $num_groups = 0;
	protected $do_group_sort = false;
	
	protected $arr_switch = [];
		
	protected static $arr_path_value_switches = [];
	
	const SWITCH_KEEP_EMPTY = 0;
	const SWITCH_KEY = 1;
	const SWITCH_JSON = 2;
	const SWITCH_UNIQUE = 3;
	const SWITCH_JOIN = 4;
	const SWITCH_SPLIT = 5;
	const SWITCH_REGEX = 6;
	const SWITCH_SORT = 7;

    public function __construct($arr_path, $do_grouping = true) {
		
		$this->arr_path = $this->preparePath($arr_path);
		
		if ($do_grouping === null) { // Assume grouping, but we're not sure
			
			$this->do_grouping = true;
			$this->in_grouping_check = true;
		} else {
		
			$this->do_grouping = $do_grouping;
		}		
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
			
			if (isset($this->arr_return[0])) {
				
				if ($this->do_group_sort && is_array($this->arr_return[0])) {
					ksort($this->arr_return[0], SORT_NATURAL);
				}
				
				return $this->arr_return[0];
			}
			
			return [];
		}
		
		if ($this->do_group_sort) {
			
			foreach ($this->arr_return as &$arr) {
				
				if (!is_array($arr)) {
					continue;
				}
				
				ksort($arr, SORT_NATURAL); // Always force natural order, as PROCESS_PATCH could have shifted things around
			}
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
		
		return ($this->arr_return && (!isset($this->arr_return[0]) || count($this->arr_return) > 1)); // Has grouping when there are multiple results in one query, which would start from 1, or a custom value.
	}
	
	protected function doWalk($arr_source, $arr_path, &$num_group = false, &$mode_process = false) {
			
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
					$str_path_key = ltrim($str_path_key, static::SYMBOL_ACTION_REVISIT.static::SYMBOL_ACTION_APPEND); // Get the key-value itself
					
					if ($str_extra == static::SYMBOL_ACTION_REVISIT) {
						
						$mode_process = static::PROCESS_PATCH;
						$this->do_group_sort = true;
					}
					
					$arr_path_key = explode(static::SYMBOL_ACTION_SEPARATOR_LIST, $str_path_key);
					$arr_identifier = [];
					
					foreach ($arr_path_key as $str_path_key) {
						
						$str_path_key_check = $str_path_key;
						$arr_check = $arr;
						$this->parsePathKey($str_path_key_check, $arr_check);
						
						$arr_identifier[] = $this->getValueByPathKeyPathValue($arr_check, [], $str_path_key_check, $str_path_value);
					}
					
					$num_group = '';
					
					foreach ($arr_identifier as $value_identifier) {
						
						if ($num_group !== '') {
							$num_group .= ',';
						}
						
						if (is_array($value_identifier)) {
							sort($value_identifier);
							$num_group .= arr2StringRecursive($value_identifier, '-');
						} else {
							$num_group .= $value_identifier;
						}
					}
					
					if ($str_path_value) {
						$num_group .= ($num_group !== '' ? '|' : '').$str_path_value; // Makes it possible to add additional identifier data using the path value
					}
				} else {
					
					if ($num_group === false && !$this->in_grouping_check) { // Incorrect
						continue;
					}
						
					$s_arr =& $this->arr_return[$num_group];

					if ($s_arr) {
						
						if (!is_array($s_arr)) {
							
							$this->arr_return[$num_group] = [$s_arr];
							$s_arr =& $this->arr_return[$num_group];
						}
						
						$mode_process_use = ($mode_process ?: self::PROCESS_APPEND);
						
						if ($str_path_key_identifier == static::SYMBOL_ACTION_PROCESS_BEFORE) {
													
							$arr_use = array_splice($s_arr, $num_offset_values);
							
							if ($do_offset_new && is_array($arr_use)) { // Revisiting immediate predecessor action SYMBOL_ACTION_PROCESS_BEFORE again
								$arr_use = current($arr_use);
							}
							$do_offset_new = true;

							$s_arr = $this->getValueByPathKeyPathValue($arr_use, $s_arr, $str_path_key_unique, $str_path_value, $mode_process_use);
						} else if ($str_path_key_identifier == static::SYMBOL_ACTION_APPEND) {
							
							$func_check_offset();
							
							$s_arr = $this->getValueFromPathKeyPathValue($s_arr, $str_path_key_unique, $str_path_value, $mode_process_use);
						} else {
							
							$func_check_offset();
							
							$s_arr = $this->getValueByPathKeyPathValue($arr, $s_arr, $str_path_key_unique, $str_path_value, $mode_process_use);
						}
					} else {
						
						$mode_process_use = ($mode_process ?: false);
						
						if ($str_path_key_identifier == static::SYMBOL_ACTION_PROCESS_BEFORE) {
							
							// Nothing
						} else if ($str_path_key_identifier == static::SYMBOL_ACTION_APPEND) {
							
							$s_arr = $this->getValueFromPathKeyPathValue($s_arr, $str_path_key_unique, $str_path_value, $mode_process_use);
						} else {
							
							$s_arr = $this->getValueByPathKeyPathValue($arr, $s_arr, $str_path_key_unique, $str_path_value, $mode_process_use);
						}
					}
				}
			}
		}
	}
	
	protected function parsePathKey(&$str_path_key, &$arr) {
		
		$str_path_key_unique = $str_path_key;
		
		$str_path_key = $this->translatePathKey($str_path_key_unique);
		$arr_path_key_parsed = $this->translatePathKeyParsed($str_path_key_unique);
		
		if ($arr_path_key_parsed === false) {
			
			return;
		} else if ($arr_path_key_parsed === null) {
			
			$iterator_token = null;
			$arr_find_settings = null;
			$do_find_recursive = false;
			
			if (strStartsWith($str_path_key, '[]') && $str_path_key !== '[]') {
				
				$iterator_token = static::tokenise(substr($str_path_key, 2));
			} else if (strStartsWith($str_path_key, '[*]')) {

				$iterator_token = static::tokenise(substr($str_path_key, 3));
				$do_find_recursive = true;
			}
			
			if ($iterator_token !== null) {
				
				foreach ($iterator_token as $str_check) {
					
					if ($arr_find_settings === null) {
						$arr_find_settings = [];
					}
					
					$arr_find_settings[] = $str_check;
				}
			}

			if ($arr_find_settings === null) {
				
				$this->arr_key_unique_parsed[$str_path_key_unique] = false;
				
				return;
			}
			
			$this->arr_key_unique_parsed[$str_path_key_unique] = [
				'find_recursive' => $do_find_recursive,
				'find_value' => null,
				'contains_key' => null,
				'contains_value' => null,
				'path_key' => '[]' // Interpret/continue with parsed results as plain array
			];
			
			foreach ($arr_find_settings as $str_setting) {
				
				if (strStartsWith($str_setting, 'containsKey:')) {
					$this->arr_key_unique_parsed[$str_path_key_unique]['contains_key'] = substr($str_setting, 12);
				} else if (strStartsWith($str_setting, 'containsValue:')) {
					$this->arr_key_unique_parsed[$str_path_key_unique]['contains_value'] = substr($str_setting, 14);
				} else {
					$this->arr_key_unique_parsed[$str_path_key_unique]['find_value'] = $str_setting;
				}
			}
		}
		
		list(
			'find_recursive' => $do_find_recursive,
			'find_value' => $str_find_value,
			'contains_key' => $str_contains_key,
			'contains_value' => $str_contains_value,
			'path_key' => $str_path_key // Continue with plain array
		) = $this->arr_key_unique_parsed[$str_path_key_unique];
		
		if ($do_find_recursive) {
			
			$arr = arrValuesRecursive((string)$str_find_value, $arr);
			$str_find_value = null;
		}

		foreach ($arr as $key => $value) {
			
			if ($str_find_value !== null && $key !== $str_find_value) {
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
				} else if (arrHasValuesRecursive(null, $str_contains_value, $value) === null) {
					unset($arr[$key]);
					continue;
				}
			}
		}
	}
	
	protected function getValueByPathKeyPathValue($arr_source, $arr_collect, $str_path_key_unique, $str_path_value, $mode_process = false) {
		
		$s_arr_switch =& static::$arr_path_value_switches[$str_path_value];
		
		if (!isset($s_arr_switch)) {

			$s_arr_switch = [static::SWITCH_KEEP_EMPTY => false, static::SWITCH_KEY => false, static::SWITCH_JSON => false, static::SWITCH_UNIQUE => false, static::SWITCH_JOIN => false, static::SWITCH_SPLIT => false, static::SWITCH_REGEX => false, static::SWITCH_SORT => false];
			
			if ($str_path_value) {
				
				$iterator_token = static::tokenise($str_path_value);

				foreach ($iterator_token as $str_check) {
					
					if ($str_check == 'empty') {
						$s_arr_switch[static::SWITCH_KEEP_EMPTY] = true;
					} else if ($str_check == 'key') {
						$s_arr_switch[static::SWITCH_KEY] = true;
					} else if ($str_check == 'json') {
						$s_arr_switch[static::SWITCH_JSON] = true;
					} else if ($str_check == 'unique') {
						$s_arr_switch[static::SWITCH_UNIQUE] = true;
					} else if (strStartsWith($str_check, 'join:')) {
						$s_arr_switch[static::SWITCH_JOIN] = substr($str_check, 5);
					} else if (strStartsWith($str_check, 'split:')) {
						$s_arr_switch[static::SWITCH_SPLIT] = substr($str_check, 6);
					} else if (strStartsWith($str_check, 'regex:')) { // regex:/pattern/flags:template
						
						$str_check = substr($str_check, 6);
						
						if (preg_match('/^\/([^\/]*)\/([a-zA-Z]*):(.*)$/s', $str_check, $arr_match)) {
							
							$arr_expression_settings = parseRegularExpression($arr_match[1], $arr_match[2], $arr_match[3], true);
							
							$s_arr_switch[static::SWITCH_REGEX] = fn($v) => strRegularExpression($v, $arr_expression_settings['pattern'], $arr_expression_settings['flags'], $arr_expression_settings['template']);
						}
					} else if (strStartsWith($str_check, 'sort:')) { // sort:direction,mode
						
						$str_check = substr($str_check, 5);
						
						$arr_sort_settings = explode(',', $str_check);

						$arr_sort_settings[0] = (($arr_sort_settings[0] ?? '') == 'd' ? 'd' : 'a');
						
						static $arr_sort_modes = ['regular' => SORT_REGULAR, 'numeric' => SORT_NUMERIC, 'string' => SORT_STRING, 'natural' => SORT_NATURAL];
						$arr_sort_settings[1] = ($arr_sort_modes[($arr_sort_settings[1] ?? 'regular')] ?? SORT_REGULAR);
						
						if ($arr_sort_settings[0] == 'd') {
							$s_arr_switch[static::SWITCH_SORT] = fn(&$v) => rsort($v, $arr_sort_settings[1]);
						} else {
							$s_arr_switch[static::SWITCH_SORT] = fn(&$v) => sort($v, $arr_sort_settings[1]);
						}
					}
				};
			}
		}
			
		$this->arr_switch = $s_arr_switch;
		
		$str_path_key = $this->translatePathKeyParsedInterpret($str_path_key_unique);

		if ($str_path_key === '[]' || $str_path_key === null) { // Grab all array values (as natural end of a path, or the raw/remainder of path)
			
			foreach ($arr_source as $key => $value) {
				$arr_collect = $this->getValue($arr_collect, $value, $str_path_key_unique, ($mode_process ?: static::PROCESS_APPEND));
			}
		} else {
			
			if (strStartsWith($str_path_key, static::SYMBOL_ACTION_PROCESS_BEFORE)) {
				
				if ($arr_source) {
				
					$str_extra = ltrim($str_path_key, static::SYMBOL_ACTION_PROCESS_BEFORE); // Target specific indices
					
					if ($str_extra !== '') {
						
						$arr_keys = explode(static::SYMBOL_ACTION_SEPARATOR_LIST, $str_extra);
						$arr_source_new = [];
						
						foreach ($arr_keys as $str_key) {
							
							if ((int)$str_key < 0) { // Start from the end
								$str_key = count($arr_source)+(int)$str_key;
							}
							
							if (!isset($arr_source[$str_key])) {
								continue;
							}
							
							$arr_source_new[] = $arr_source[$str_key];
						}
						
						$arr_source = $arr_source_new;
						unset($arr_source_new);
					}
				}
				
				$arr_collect = $this->getValue($arr_collect, $arr_source, $str_path_key_unique, $mode_process, true); // Always collect to a single element
			} else {
				
				$arr_collect = $this->getValue($arr_collect, $arr_source[$str_path_key], $str_path_key_unique, $mode_process);
			}
		}
				
		return $arr_collect;
	}
	
	protected function getValue($arr, $value, $str_key, $mode_process, $do_single_element = false) {
		
		if (!$this->arr_switch[static::SWITCH_KEEP_EMPTY] && ($value === false || $value === null || $value === '' || $value === [])) {
			return $arr;
		}
		
		$is_collected = false; // Value been travelled/collected (if applicable)
		$is_multi_element = false; // Value has to be handled as a newly made multi-value, where values are handled separately (access them later by their own indices, not collected in a single element)
		
		if ($this->arr_switch[static::SWITCH_JSON]) {
			
			$value = value2JSON($value);
			$is_collected = true;
		} else {
			
			if ($this->arr_switch[static::SWITCH_KEY]) {
				
				$value = $str_key;
				$is_collected = true;
			}
			if ($this->arr_switch[static::SWITCH_SPLIT] !== false) {

				$value = arrValuesRecursiveParse(null, $value, fn($v) => explode($this->arr_switch[static::SWITCH_SPLIT], $v));
				$is_multi_element = true;
				$is_collected = true;
			}
			if ($this->arr_switch[static::SWITCH_JOIN] !== false) {
				
				if (is_array($value)) {
					
					$value = arr2StringRecursive($value, $this->arr_switch[static::SWITCH_JOIN]);
					$is_multi_element = false;
					$is_collected = true;
				}
			}
			if ($this->arr_switch[static::SWITCH_REGEX] !== false) {
				
				$value = arrValuesRecursiveParse(null, $value, $this->arr_switch[static::SWITCH_REGEX]);
				$is_collected = true;
			}
		}
		
		if ($mode_process === static::PROCESS_APPEND) {
			
			if (is_array($value)) {
				
				if ($this->arr_switch[static::SWITCH_UNIQUE]) {
					$value = arrValuesRecursiveUnique(null, $value);
					$is_collected = true;
				}
				if ($this->arr_switch[static::SWITCH_SORT] !== false) {
					if (!$is_collected) {
						$value = arrValuesRecursive(null, $value);
					}
					$this->arr_switch[static::SWITCH_SORT]($value);
				}
			}
			
			if ($is_multi_element && !$do_single_element) {
				
				foreach ($value as $v) {
					$arr[] = $v;
				}
			} else {
				
				$arr[] = $value;
			}
		} else if ($mode_process === static::PROCESS_PATCH) {
			
			if ($is_multi_element && !$do_single_element) {
								
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
			
			if ($this->arr_switch[static::SWITCH_UNIQUE]) {
				$arr[$str_key] = arrValuesRecursiveUnique(null, $arr[$str_key]);
				$is_collected = true;
			}
			if ($this->arr_switch[static::SWITCH_SORT] !== false) {
				if (!$is_collected) {
					$arr[$str_key] = arrValuesRecursive(null, $arr[$str_key]);
				}
				$this->arr_switch[static::SWITCH_SORT]($arr[$str_key]);
			}
		} else {
			
			if (is_array($value)) {
				
				if ($this->arr_switch[static::SWITCH_UNIQUE]) {
					$value = arrValuesRecursiveUnique(null, $value);
					$is_collected = true;
				}
				if ($this->arr_switch[static::SWITCH_SORT] !== false) {
					if (!$is_collected) {
						$value = arrValuesRecursive(null, $value);
					}
					$this->arr_switch[static::SWITCH_SORT]($value);
				}
			}
			
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
			$str_key_unique = $num_count_keys.static::PATH_KEY_UNIQUE.$str_key; // Prepend unique number; make natural sorting possible
			
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
	
	protected function translatePathKeyParsed($str_key_unique) {
		
		return ($this->arr_key_unique_parsed[$str_key_unique] ?? null);
	}
	
	protected function translatePathKeyParsedInterpret($str_key_unique) {
		
		return ($this->arr_key_unique_parsed[$str_key_unique]['path_key'] ?? ($this->arr_key_unique[$str_key_unique] ?? $str_key_unique)); // First check parsed translation, otherwise the original translation
	}
	
	protected static function tokenise($str_value) {
		
		$str_check = '';
		$in_escape = false;
		$num_length_value = strlen($str_value);
		
		for ($i = 0; $i <= $num_length_value; $i++) {
			
			$str_character = ($str_value[$i] ?? '');
			
			if ($in_escape) {
				
				$in_escape = false;
			} else if ($str_character === '\\') { // Escape, skip next
				
				$in_escape = true;
				continue;
			} else if ($str_character === static::SYMBOL_ACTION_SEPARATOR) {
				
				$str_character_next = ($str_value[$i+1] ?? '');
				
				if ($str_character_next === static::SYMBOL_ACTION_SEPARATOR) { // Double colon escapes itself
					
					$in_escape = true;
					continue;
				}
				
				yield $str_check;
				$str_check = '';
				
				continue;
			} else if ($str_character === '') { // Separator or end
				
				yield $str_check;
				$str_check = '';

				continue;
			}
			
			$str_check .= $str_character;
		}
	}
}
