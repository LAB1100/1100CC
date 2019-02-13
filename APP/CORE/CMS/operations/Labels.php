<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class Labels {
			
	private static $arr_identifiers = [];
	private static $arr_labels = [];
	private static $arr_labels_override = [];
	private static $arr_labels_last = [];
	private static $arr_system_labels = [];
	private static $arr_vars = [];
	
	public static function getLabel($identifier, $type = 'L', $go_now = false) {
		
		$identifier = strtolower($identifier);
		$code = '['.$type.']('.$identifier.')';
		if (!self::$arr_labels[$code]) {
			self::$arr_identifiers[$type][$identifier] = $code;
		}
		self::$arr_labels_last[$code] = $code;
		
		if (DB::isActive()) {
			return ($go_now ? self::printLabels($code) : $code);
		} else {
			return ($go_now ? false : $code);
		}
	}
	
	public static function printLabels($value, $encode = false) {
	
		if (is_array($value) || is_object($value)) {
		
			array_walk_recursive($value, function(&$v, $k) use ($encode) {
				
				if (is_string($v)) {
					$v = Labels::doPrintLabels($v, $encode);
				}
			});
		} else {
			
			$value = self::doPrintLabels($value, $encode);
		}
		
		return $value;
	}
	
	public static function doPrintLabels($text, $encode = false) {

		if (self::$arr_labels || self::$arr_identifiers) {
			
			$arr_to_lookup = [];
			
			while (self::$arr_identifiers && DB::isActive()) {
				
				// Store identifiers and reset class identifiers to prevent loop
				$arr_identifiers = self::$arr_identifiers;
				self::$arr_identifiers = [];
				
				$arr = [];
				if ($arr_identifiers['L']) {
					$arr['L'] = cms_labels::getLabels(array_keys($arr_identifiers['L']));
				}
				if ($arr_identifiers['D']) {
					$arr['D'] = cms_details::getSiteDetails(array_keys($arr_identifiers['D']));
				}
				if ($arr_identifiers['C']) {
					$arr['C'] = cms_details::getSiteDetailsCustom(array_keys($arr_identifiers['C']));
				}

				foreach ($arr as $type => $arr_type) {
					
					foreach ((array)$arr_type as $value) {
						
						$code = $arr_identifiers[$type][strtolower($value['identifier'])];
						$label = (isset(self::$arr_labels_override[$code]) ? (is_callable(self::$arr_labels_override[$code]) ? self::$arr_labels_override[$code]() : self::$arr_labels_override[$code]) : $value['label']);
						
						// Save label and store its relation to newly found labels
						
						self::$arr_labels_last = [];
						
						self::$arr_labels[$code] = self::parseTextVariables($label);
						
						unset($arr_to_lookup[$code]);
						$arr_to_lookup[$code] = self::$arr_labels_last;
					}
				}
			}
			
			// Print looked up labels in their related labels, top down so last first
			foreach (array_reverse($arr_to_lookup) as $code => $arr_codes_print) {
				
				foreach ($arr_codes_print as $code_print) {
					
					self::$arr_labels[$code] = str_replace($code_print, self::$arr_labels[$code_print], self::$arr_labels[$code]);
				}
			}
			
			// Print
			
			$func_parse = function($arr_match) use ($encode) {
				
				if ($arr_match[1]) {
					$str = self::$arr_labels['['.$arr_match[1].']('.$arr_match[2].')'];
				} else {
					$str = self::$arr_labels[$arr_match[0]];
				}
				
				if (!$str && !isset($str)) { // Return original tag when not defined
					$str = $arr_match[0];
				}
				
				if ($encode) {
					
					$str = Response::encode($str);
				}
				
				return $str;
			};
			
			$text = preg_replace_callback('/%5B([A-Z])%5D%28((?:(?!%29).)+)%29/', $func_parse, $text); // Url encoded tags
			
			$text = preg_replace_callback('/\[[A-Z]\]\([^\)]*\)/i', $func_parse, $text);
		}
		
		return $text;
	}
	
	public static function override($identifier, $type, $value) {
	
		$code = '['.$type.']{'.$identifier.'}';
		
		self::$arr_labels_override[$code] = $value;
	}
	
	public static function setSystemLabels() {
		
		self::$arr_system_labels = Settings::getShare('system_labels');
		
		if (!self::$arr_system_labels) {
			
			self::$arr_system_labels = [
				'msg_error' => getLabel('msg_error'),
				'msg_error_time_limit' => getLabel('msg_error_time_limit'),
				'msg_error_memory_limit' => getLabel('msg_error_memory_limit'),
				'msg_missing_information' => getLabel('msg_missing_information'),
				'msg_api_limit' => getLabel('msg_api_limit'),
				'inf_api_welcome' => getLabel('inf_api_welcome')
			];
			
			self::$arr_system_labels = Labels::printLabels(self::$arr_system_labels);
			
			Settings::setShare('system_labels', self::$arr_system_labels, 3600);
		}
	}
	
	public static function getSystemLabel($value) {
		
		return self::parseTextVariables(self::$arr_system_labels[$value]);
	}
	
	public static function setVariable($var, $value) {
	
		self::$arr_vars[$var] = ($value !== false ? preg_replace('/\[([A-Z])\]\(([^\)]*)\)/', '[\1][\2]', $value) : false); // Turn locked tags {} to unlocked [] to be parsed
	}
			
	public static function parseTextVariables($str) {
		
		// Parse language blocks
		$str = self::parseLanguage($str);
		
		// Parse variables
		$func_parse = function($arr_match) {
			
			if ($arr_match[1] == 'L' || $arr_match[1] == 'D' || $arr_match[1] == 'C') {
				$str = self::getLabel($arr_match[2], $arr_match[1]);
			} else if ($arr_match[1] == 'V') {
				$str = self::parseTextVariables(self::$arr_vars[$arr_match[2]]);
			} else if ($arr_match[1] == 'S') {
				$str = self::getServerVariable($arr_match[2]);
			}
			
			$str = ($str != '' ? $str : $arr_match[0]);

			return $str;
		};
	
		$str = preg_replace_callback('/%5B([A-Z])%5D%5B((?:(?!%5B|%5D).)+)%5D/', $func_parse, $str); // Url encoded tags
		
		$str = preg_replace_callback('/\[([A-Z])\]\[([^\[\]]*)\]/i', $func_parse, $str); // [X][VALUE]
		
		return $str;
	}
	
	public static function parseLanguage($str) {
					
		$pos = 0;
		
		while (true) {
			
			$pos = strpos($str, '[[', $pos);
			
			if ($pos === false) {
				return $str;
			}
			
			$pos_end = strpos($str, ']]', $pos);
			
			if (!$pos_end) {
				return $str;
			}
			
			$str_check = substr($str, $pos+2 , $pos_end - ($pos+2));
			
			if ($str_check === strtoupper($str_check)) { // System tags are uppercase
				break;
			} else {
				$pos += 2;
			}
		}
		
		$language = strtoupper(SiteStartVars::$language);
		$len = strlen($language);
		
		/*
		preg_match('/\[\['.$language.'\]\](?:<br\s?\/?>)?(?:<\/p>)?(((?!(?:<p>)?\[\[).)*)/si', $str, $arr_match);
		
		if ($arr_match[0]) {
			
			$str = $arr_match[1];
		}
		*/
		
		$pos_start = strpos($str, '[['.$language.']]', $pos);
		
		if ($pos_start !== false) { // Language is present, extract the language-tagged text
			
			$pos_start = $pos_start+$len+4;
			
			$pos_start_clean = strpos($str, '</p>', $pos_start);
			
			if ($pos_start_clean) {
				$pos_start = $pos_start_clean+4;
			}
			
			$pos_end = strpos($str, '[[', $pos_start);
			
			if ($pos_end) {
				
				$str = substr($str, $pos_start, $pos_end - $pos_start);
				
				$pos_end_clean = strrpos($str, '<p>');
				
				if ($pos_end_clean) {
					$str = substr($str, 0, $pos_end_clean);
				}
			} else {
				
				$str = substr($str, $pos_start);
			}
		} else { // Language is not present, keep possible untagged text and remove other languages
			
			$str = substr($str, 0, $pos);
			
			$pos_end_clean = strrpos($str, '<p>');
				
			if ($pos_end_clean) {
				$str = substr($str, 0, $pos_end_clean);
			}
		}
		
		$str = trim($str); // Remove possible new line or other empty characters
		
		return $str;
	}
			
	public static function addLanguageTags($what = null) {
		
		if ($what === true) {
			return '[LANG]';
		} else if ($what === false) {
			return '[/LANG]';
		} else {
			return '[LANG]'.$what.'[/LANG]';
		}
	}

	public static function parseLanguageTags($str) {

		$pos_end = strpos($str, '[/LANG]'); // Find and move to first closing tag
		
		if ($pos_end === false) {
			return $str;
		}

		while ($pos_end !== false) {
			
			$len = strlen($str);
			
			$pos_start = strrpos($str, '[LANG]', $pos_end-$len); // Lookup first leading opening tag
			
			$str_parse = substr($str, $pos_start+6, $pos_end-($pos_start+6));
			
			$str_parse = self::parseLanguage($str_parse);
			
			$str = substr_replace($str, $str_parse, $pos_start, ($pos_end-$pos_start)+7);
			
			$pos_end = strpos($str, '[/LANG]', $pos_start);
		}
		
		return $str;
	}
	
	public static function getServerVariable($str) {
		
		switch ($str) {
			case 'version':
				
				$path_core = DIR_ROOT_CORE.DIR_CMS.DIR_INFO.'version.txt';
				$path_site = DIR_ROOT_SITE.DIR_CMS.DIR_INFO.'version.txt';
				
				if (IS_CMS) {
					$str = '1100CC '.trim(file_get_contents($path_core));
					if (isPath($path_site)) {
						$str = '<span>'.$str.'</span><span>'.SITE_NAME.' '.trim(file_get_contents($path_site)).'</span>';
					}
				} else {
					$str = trim((isPath($path_site) ? file_get_contents($path_site) : file_get_contents($path_core)));
				}
				
				break;
			case 'humans':
			
				$path_core = DIR_ROOT_CORE.DIR_CMS.DIR_INFO.'humans.txt';
				$path_site = DIR_ROOT_SITE.DIR_CMS.DIR_INFO.'humans.txt';
			
				if (IS_CMS) {
					$str = trim(file_get_contents($path_core));
					$str .= (isPath($path_site) ? PHP_EOL.PHP_EOL.trim(file_get_contents($path_site)) : '');
				} else {
					$str = trim((isPath($path_site) ? file_get_contents($path_site) : file_get_contents($path_core)));
				}
				
				break;
			default:
				$str = constant($str);
		}
		
		return $str;
	}
}
