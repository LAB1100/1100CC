<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class Labels {
			
	protected static $arr_identifiers = [];
	protected static $arr_labels = [];
	protected static $arr_labels_override = [];
	protected static $arr_labels_last = [];
	protected static $arr_system_labels = [];
	protected static $arr_vars = [];
	
	const NAMESPACE_SEPARATOR = ':';
	
	public static function getLabel($identifier, $type = 'L', $go_now = false) {
		
		$identifier = strtolower($identifier);
		$code = '['.$type.']('.$identifier.')';
		if (!isset(self::$arr_labels[$code])) {
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
	
	public static function doPrintLabels($str_text, $encode = false) {

		if (self::$arr_labels || self::$arr_identifiers) {
			
			$arr_to_lookup = [];
			
			while (self::$arr_identifiers && DB::isActive()) {
				
				// Store identifiers and reset class identifiers to prevent loop
				$arr_identifiers = self::$arr_identifiers;
				self::$arr_identifiers = [];
				
				$arr = [];
				if (isset($arr_identifiers['L'])) {
					$arr['L'] = cms_labels::getLabels(array_keys($arr_identifiers['L']));
				}
				if (isset($arr_identifiers['D'])) {
					$arr['D'] = cms_details::getSiteDetails(array_keys($arr_identifiers['D']));
				}
				if (isset($arr_identifiers['C'])) {
					$arr['C'] = cms_details::getSiteDetailsCustom(array_keys($arr_identifiers['C']));
				}

				foreach ($arr as $type => $arr_type) {
					
					foreach ((array)$arr_type as $value) {
						
						$code = $arr_identifiers[$type][strtolower($value['identifier'])];
						$label = (isset(self::$arr_labels_override[$code]) ? (is_callable(self::$arr_labels_override[$code]) ? self::$arr_labels_override[$code]() : self::$arr_labels_override[$code]) : $value['label']);
						
						// Save label and capture/store its relation to newly found labels while parsing
						
						self::$arr_labels_last = [];
						
						self::$arr_labels[$code] = self::parseTextVariables($label, false, false); // Parse text but do not print and store found labels and variables (labels are printed after all are collected, and variables can change so do not print them here)
						
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

				$str = (self::$arr_labels['['.$arr_match[1].']('.$arr_match[2].')'] ?? null);
				
				if ($str === null) { // Return original but 'broken' tag when not defined
					$str = '['.$arr_match[1].']{'.$arr_match[2].'}';
				} else {
					
					if (strpos($str, '[V][') !== false) { // Parse text and print found labels and variables
						$str = self::parseTextVariables($str, true, true);
					}
				}
				
				if ($encode) {
					$str = Response::encode($str);
				}
				
				return $str;
			};
			
			//$str_text = preg_replace_callback('/%5B([LDC])%5D%28((?:[A-Za-z0-9_\-]|%20)+)%29/', $func_parse, $text); // URL encoded tags
			
			$str_text = preg_replace_callback('/\[([LDC])\]\(([A-Za-z0-9_\- ]+)\)/', $func_parse, $str_text);
		}
		
		$str_text = static::clearContainers($str_text);
		
		return $str_text;
	}
	
	public static function override($identifier, $type, $value) {
	
		$code = '['.$type.']('.$identifier.')';
		
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
		
		$str = (self::$arr_system_labels[$value] ?? $value);
		
		$str = preg_replace('/\[([A-Z])\]\{([A-Za-z0-9_\- ]+)\}/', '[\1][\2]', $str); // Turn 'broken' tags {} to unlocked [] to be parsed
		$str = self::parseTextVariables($str);
		
		return $str;
	}
	
	public static function setVariable($var, $value) {
	
		$str = $value;
		
		if ($value !== false && strpos($str, '](') !== false) {
			$str = preg_replace('/\[([A-Z])\]\(([A-Za-z0-9_\- ]+)\)/', '[\1][\2]', $str); // Turn locked tags () to unlocked [] to be parsed
		}
		
		self::$arr_vars[$var] = $str;
	}
	
	public static function getVariable($var) {
		
		return (self::$arr_vars[$var] ?? null);
	}
			
	public static function parseTextVariables($str, $print_labels = false, $print_variables = true) {
		
		if (!$str) {
			return (string)$str; // Possible '0'
		}
		
		// Parse blocks
		$str = self::parseContainers($str);

		// Parse variables
		$func_parse = function($arr_match) use ($print_labels, $print_variables) {
			
			if ($arr_match[1] == 'L' || $arr_match[1] == 'D' || $arr_match[1] == 'C') {
				
				$str = self::getLabel($arr_match[2], $arr_match[1], $print_labels);
			} else if ($arr_match[1] == 'V') {
				
				$str_parsed = (self::$arr_vars[$arr_match[2]] ?? '');
				
				if ($str_parsed === false) {
					
					$str = '['.$arr_match[1].']['.$arr_match[2].']'; // Keep tag for later parsing
				} else {
					
					$str_parsed = self::parseTextVariables($str_parsed, $print_labels, $print_variables);
					
					if ($print_variables) {
						$str = $str_parsed;
					} else {
						$str = '['.$arr_match[1].']['.$arr_match[2].']'; // Keep tag for later parsing
					}
				}
			} else if ($arr_match[1] == 'S') {
				
				$str = self::getServerVariable($arr_match[2]);
			}
			
			if ($str == '') {
				$str = '['.$arr_match[1].']{'.$arr_match[2].'}'; // Return 'broken' tag
			}
			
			return $str;
		};
	
		$str = preg_replace_callback('/%5B([A-Z])%5D%5B((?:[A-Za-z0-9_\-]|%20)+)%5D/', $func_parse, $str); // URL encoded tags
		
		$str = preg_replace_callback('/\[([A-Z])\]\[([A-Za-z0-9_\- ]+)\]/', $func_parse, $str); // [X][VALUE]
		
		return $str;
	}
	
	public static function parseContainers($str) {
		
		// Parse content that use single tags [[..]], e.g. language blocks

		$pos_end = strpos($str, '[/LABEL]'); // Find and move to first closing tag
		
		if ($pos_end === false) {
			
			$str = self::parseLanguage($str);
			
			return $str;
		}

		while ($pos_end !== false) {
			
			$len = strlen($str);
			
			$pos_start = strrpos($str, '[LABEL]', $pos_end-$len); // Lookup first leading opening tag
			
			$str_parse = substr($str, $pos_start+7, $pos_end-($pos_start+7));
			
			$str_parse = self::parseLanguage($str_parse);
			
			$str = substr_replace($str, $str_parse, $pos_start, ($pos_end-$pos_start)+7+1);
			
			$pos_end = strpos($str, '[/LABEL]', $pos_start);
		}
		
		return $str;
	}
	
	public static function parseLanguage($str) {
		
		if (!$str) {
			return $str;
		}
				
		$num_pos = static::checkLanguageTag($str);
		
		if ($num_pos === false) {
			return $str;
		}
						
		if (strpos($str, '</p>') !== false) { // Replace possible <p>[[XX]]</p>
			
			$str = preg_replace('/<p>\s*(\[\[[A-Z]+\]\])\s*<\/p>/', '$1', $str);
		}
				
		$str_language = strtoupper(SiteStartVars::getContext(SiteStartVars::CONTEXT_LANGUAGE));
		
		$num_pos_start = strpos($str, '[['.$str_language.']]', $num_pos);
		
		if ($num_pos_start !== false) { // Language is present, extract the language-tagged text
			
			$num_pos_start = $num_pos_start+strlen($str_language)+4;
						
			$num_pos_end = strpos($str, '[[', $num_pos_start);
			
			if ($num_pos_end) {
				
				$str = substr($str, $num_pos_start, $num_pos_end - $num_pos_start);
			} else {
				
				$str = substr($str, $num_pos_start);
			}
		} else { // Language is not present, remove all, keep default
			
			$str = substr($str, 0, $num_pos);
		}
		
		$str = trim($str); // Remove possible new line or other empty characters
		
		return $str;
	}
	
	public static function checkLanguageTag($str) {
		
		$num_pos = 0;
		
		while (true) {
		
			$num_pos = strpos($str, '[[', $num_pos);
			
			if ($num_pos === false) {
				return false;
			}
			
			$num_pos_end = strpos($str, ']]', $num_pos);
			
			if (!$num_pos_end) {
				return false;
			}
			
			$str_check = substr($str, $num_pos+2 , $num_pos_end - ($num_pos+2));
			
			if ($str_check === strtoupper($str_check)) { // System tags are uppercase
				break;
			} else {
				$num_pos += 2;
			}
		}
		
		return $num_pos;
	}
			
	public static function addContainer($str, $test = true) {
		
		// Non-parsed containers are removed at Response output

		if ($test) {
			
			$num_pos = strpos($str, '[[');
			
			if ($num_pos === false) {
				return $str;
			}

			$num_pos = strpos($str, ']]', $num_pos + 2);
			
			if ($num_pos === false) {
				return $str;
			}
		}
		
		return '[LABEL]'.$str.'[/LABEL]';
	}
	public static function addContainerOpen() {
		
		return '[LABEL]';
	}
	public static function addContainerClose() {
		
		return '[/LABEL]';
	}
	
	public static function clearContainers($str) {
		
		if (strpos($str, 'LABEL]') === false) {
			return $str;
		}
		
		return str_replace(['[LABEL]', '[/LABEL]', '[\/LABEL]'], '', $str);
	}
	
	public static function addNamespace($str, $str_namespace, $do_strict = false) {
		
		if ($do_strict) {
			$str_namespace = strtoupper($str_namespace);
		}
		
		return $str_namespace.static::NAMESPACE_SEPARATOR.$str;
	}
	
	public static function parseNamespace($str, $do_strict = false) {
		
		if (!$str) {
			return false;
		}
		
		$num_pos = strpos($str, static::NAMESPACE_SEPARATOR);
		
		if ($num_pos === false) {
			return false;
		}
		
		$str_namespace = substr($str, 0, $num_pos);
		
		if ($do_strict && $str_namespace != strtoupper($str_namespace)) {
			return false;
		}
		
		$str_namespace = strtoupper($str_namespace);		
		$str_other = ltrim(substr($str, $num_pos + strlen(static::NAMESPACE_SEPARATOR)));
		
		return ['namespace' => $str_namespace, 'label' => $str_other];
	}
	
	public static function getServerVariable($str) {
		
		switch ($str) {
			case 'version':
				
				$path_core = DIR_ROOT_CORE.DIR_CMS.DIR_INFO.'version.txt';
				$path_site = DIR_ROOT_SITE.DIR_CMS.DIR_INFO.'version.txt';
				
				if (IS_CMS) {
					
					if (Response::getFormat() & Response::RENDER_TEXT) {
						
						$str = '1100CC '.strip_tags(readText($path_core));
						$str .= PHP_EOL.SITE_NAME.(isPath($path_site) ? ' '.strip_tags(readText($path_site)) : '');
					} else {
						
						$str = '<span>1100CC '.readText($path_core).'</span>';
						$str .= '<span>'.SITE_NAME.(isPath($path_site) ? ' '.readText($path_site) : '').'</span>';
					}
				} else {
					
					$str = (isPath($path_site) ? readText($path_site) : readText($path_core));
					
					if (Response::getFormat() & Response::RENDER_TEXT) {
						$str = strip_tags($str);
					}
				}
				
				break;
			case 'humans':
			
				$path_core = DIR_ROOT_CORE.DIR_CMS.DIR_INFO.'humans.txt';
				$path_site = DIR_ROOT_SITE.DIR_CMS.DIR_INFO.'humans.txt';
			
				if (IS_CMS) {
					
					$str = readText($path_core);
					$str .= (isPath($path_site) ? PHP_EOL.PHP_EOL.readText($path_site) : '');
				} else {
					
					$str = (isPath($path_site) ? readText($path_site) : readText($path_core));
				}
				
				break;
			case 'user_agent':
				
				$path_core = DIR_ROOT_CORE.DIR_CMS.DIR_INFO.'version.txt';
				$path_site = DIR_ROOT_SITE.DIR_CMS.DIR_INFO.'version.txt';
				
				$str = '1100CC '.strip_tags(readText($path_core));
				$str .= (isPath($path_site) ? ' / '.SITE_NAME.' '.strip_tags(readText($path_site)) : '');
				
				break;
			case 'language':	
				$str = SiteStartVars::getContext(SiteStartVars::CONTEXT_LANGUAGE);
				break;
			case 'url_site':
				$str = URL_BASE;
				break;
			case 'scheme':
				$str = SERVER_SCHEME;
				break;
			case 'domain_site':
				$str = SERVER_NAME_SITE_NAME;
				break;
			case 'domain':
				$str = SERVER_NAME;
				break;
			default:
				$str = $str;
		}
		
		return $str;
	}
}
