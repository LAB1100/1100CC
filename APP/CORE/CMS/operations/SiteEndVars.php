<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class SiteEndVars {
	
	protected static $arr_head_tags = [];
	protected static $arr_titles = [];
	protected static $arr_descriptions = [];
	protected static $arr_keywords = [];
	protected static $arr_content_identifiers = [];
	protected static $str_type = 'website';
	protected static $str_image = false;
	protected static $arr_theme = [];
	
	protected static $arr_request_vars = [];
	protected static $arr_server_name_custom = [];
	protected static $arr_feedback = [];
	
	protected static $shortcut_mod_id = false;
	protected static $str_shortcut_name = false;
	protected static $is_root_shortcut = false;
	
	const LOCATION_REAL = 0;
	const LOCATION_CANONICAL_NATIVE = 1; // System-directed use
	const LOCATION_CANONICAL_PUBLIC = 2; // Public purposes

	public static function addHeadTag($tag) {
	
		self::$arr_head_tags[] = $tag;
	}
	
	public static function addScript($script, $is_url = false) {
		
		if ($is_url) {
			self::$arr_head_tags[] = '<script type="text/javascript" src="'.$script.'"></script>';
		} else {
			self::$arr_head_tags[] = '<script type="text/javascript">'.$script.'</script>';
		}
	}
	
	public static function addStyle($style, $is_url = false) {
		
		if ($is_url) {
			self::$arr_head_tags[] = '<link href="'.$style.'" rel="stylesheet" type="text/css" />';
		} else {
			self::$arr_head_tags[] = '<style>'.$style.'</style>';
		}
	}
		
	public static function getHeadTags() {
		
		$return = '';
	
		foreach (self::$arr_head_tags as $tag) {
			$return .= $tag;
		}
		if (!IS_CMS) {
			$return .= getLabel('head_tags', 'D');
		}
		
		return $return;
	}
	
	public static function addTitle($title) {
	
		self::$arr_titles[] = $title;
	}
	
	public static function getTitle() {

		array_unshift(self::$arr_titles, getLabel('title', 'D'), (SiteStartVars::getDirectory() ? SiteStartVars::getDirectory('title') : ''), (SiteStartVars::getContext(SiteStartVars::CONTEXT_PAGE_NAME) ? SiteStartVars::getPage('title') : ''));
		self::$arr_titles = array_filter(self::$arr_titles);
		
		return Labels::parseTextVariables(implode(' | ', self::$arr_titles));
	}
	
	public static function addDescription($arr_descriptions) {
	
		self::$arr_descriptions[] = $arr_descriptions;
	}
	
	public static function getDescription() {
				
		self::$arr_descriptions = (self::$arr_descriptions ? Labels::parseTextVariables(implode(' ', array_unique(self::$arr_descriptions))) : getLabel('description', 'D'));
		
		return self::$arr_descriptions;
	}
	
	public static function addKeywords($keywords) {
		
		if (is_array($keywords)) {
			self::$arr_keywords = array_merge(self::$arr_keywords, $keywords);
		} else {
			self::$arr_keywords[] = $keywords;
		}
	}
	
	public static function getKeywords() {
		
		if (!self::$arr_keywords) {
			return;
		}
		
		return implode(',', array_unique(self::$arr_keywords));
	}
	
	public static function addContentIdentifier($content, $identifier) {
		
		self::$arr_content_identifiers[$content][$identifier] = $identifier;
	}
	
	public static function getContentIdentifier() {
		
		if (!self::$arr_content_identifiers) {
			return;
		}
		
		return createContentIdentifier(self::$arr_content_identifiers);
	}
	
	public static function setType($str_type) {
	
		self::$str_type = $str_type;
	}
	
	public static function getType() {
	
		return self::$str_type;
	}
		
	public static function setImage($str_image) {
	
		self::$str_image = $str_image;
	}
	
	public static function getImage() {
		
		$str_image = (self::$str_image ?: '/'.DIR_CSS.'image.png');
		
		return $str_image;
	}
	
	public static function getIcons() {
		
		$str_image = static::getImage();

		$html = '';
		
		foreach ([16, 32, 96, 128, 196] as $num_size) {
			$html .= '<link rel="icon" type="image/png" href="'.SiteStartVars::getCacheURL('img', [$num_size, $num_size], $str_image, (IS_CMS ? DIR_CMS : false)).'" sizes="'.$num_size.'x'.$num_size.'" />';
		}
		foreach ([57, 60, 72, 76, 114, 120, 144, 152] as $num_size) {
			$html .= '<link rel="apple-touch-icon" href="'.SiteStartVars::getCacheURL('img', [$num_size, $num_size], $str_image, (IS_CMS ? DIR_CMS : false)).'" sizes="'.$num_size.'x'.$num_size.'" />';
		}
		
		return $html;
	}
	
	public static function setTheme($arr_theme) {
	
		self::$arr_theme += $arr_theme;
	}
	
	public static function getTheme() {
		
		if (self::$arr_theme) {
			
			return self::$arr_theme;
		}
		
		// Fallback to default theme
		$path = (isPath(DIR_SITE.DIR_CSS.'theme.json') ? DIR_SITE : DIR_CORE).DIR_CSS.'theme.json';
		$json = file_get_contents($path);
		$arr_theme = json_decode($json, true);
			
		return $arr_theme;
	}
	
	public static function setFeedback($str_variable, $data, $store = false) {
		
		if ($store) {
			self::$arr_feedback['store'][$str_variable] = $data;
			SiteStartVars::setFeedback($data, $str_variable); // Also update the possible original value
		} else {
			self::$arr_feedback['broadcast'][$str_variable] = $data;
		}
	}
	
	public static function getFeedback() {

		return self::$arr_feedback;
	}
	
	public static function setShortcut($mod_id, $str_name, $is_root) {
		
		static::$shortcut_mod_id = $mod_id;
		static::$str_shortcut_name = $str_name;
		static::$is_root_shortcut = $is_root;
	}
	
	public static function setRequestVariables($arr_variables, $num_index = false) {
		
		if ($num_index !== false) {
			
			if ($arr_variables === false) {
				unset(self::$arr_request_vars[$num_index]);
			} else {
				self::$arr_request_vars[$num_index] = $arr_variables;
			}
			return;
		}
		
		self::$arr_request_vars = $arr_variables;
	}
	
	public static function setModuleVariables($mod_id = false, $arr_var_names = [], $do_overwrite = true) {
		
		if ($do_overwrite) {
			self::$arr_request_vars[$mod_id] = [];
		}

		foreach ($arr_var_names as $var_name => $var) {
							
			if (!is_numeric($var_name)) {
				if ($var === false) {
					unset(self::$arr_request_vars[$mod_id][$var_name]);
				} else {
					self::$arr_request_vars[$mod_id][$var_name] = array_merge((array)self::$arr_request_vars[$mod_id][$var_name], (array)$var);
				}
			} else {
				self::$arr_request_vars[$mod_id][] = $var;
			}
		}
	}
	
	public static function getLocationVariables($mode_location = self::LOCATION_CANONICAL_NATIVE) {
		
		$arr = [];
		
		if (!self::$arr_request_vars) {
			return $arr;
		}
		
		if (IS_CMS) {
			return array_filter(self::$arr_request_vars);
		}
				
		ksort(self::$arr_request_vars);
		
		if (static::$shortcut_mod_id && isset(self::$arr_request_vars[static::$shortcut_mod_id]) && $mode_location == static::LOCATION_REAL) { // Start with the shortcut variables
			
			$arr_var_names = self::$arr_request_vars[static::$shortcut_mod_id];
			unset(self::$arr_request_vars[static::$shortcut_mod_id]);
			
			self::$arr_request_vars = [static::$shortcut_mod_id => $arr_var_names] + self::$arr_request_vars;
		}
		
		foreach (self::$arr_request_vars as $mod_id => $arr_var_names) {
			
			if (!$arr_var_names) {
				continue;
			}
			
			if ($mod_id && (!static::$shortcut_mod_id || static::$shortcut_mod_id != $mod_id || $mode_location != static::LOCATION_REAL)) {
				$arr[] = $mod_id.'.m';
			}
			
			$cur_var_name = false;
			
			foreach ((array)$arr_var_names as $var_name => $var) {

				if (!is_numeric($var_name)) {
					
					if ($cur_var_name !== $var_name) {
						$arr[] = $var_name.'.v';
					}
					
					foreach ($var as $value) {

						$arr[] = $value;
					}
				} else {
					
					$arr[] = $var;
				}
				
				$cur_var_name = $var_name;
			}
		}

		return $arr;
	}
	
	public static function getLocation($is_relative = true, $mode_location = self::LOCATION_REAL) {

		$arr_location_vars = self::getLocationVariables($mode_location);
		
		if (IS_CMS) {
			return (!$is_relative ? URL_BASE_HOME : '/').implode('/', $arr_location_vars);
		}
		
		$str_location = SiteStartVars::getBasePath(0, $is_relative);
		
		if ($arr_location_vars) {
			
			if (static::$shortcut_mod_id && $mode_location == static::LOCATION_REAL) {
				
				if (static::$is_root_shortcut) {
					$str_location = (!$is_relative ? URL_BASE_HOME : '/').static::$str_shortcut_name.'.s/'.implode('/', $arr_location_vars);
				} else {
					$str_location .= static::$str_shortcut_name.'.s/'.implode('/', $arr_location_vars);
				}
			} else {
			
				$str_location .= SiteStartVars::getPage('name').'.p/'.implode('/', $arr_location_vars);
			}
		} else {

			if (!(SiteStartVars::getDirectory('root') && SiteStartVars::getDirectory('page_index_id') == SiteStartVars::getPage('id')) || ($mode_location != static::LOCATION_REAL || (SiteStartVars::getContext(SiteStartVars::CONTEXT_PAGE_NAME) && SiteStartVars::getContext(SiteStartVars::CONTEXT_PAGE_KIND) == '.c' && SiteStartVars::getContext(SiteStartVars::CONTEXT_PAGE_NAME) != 'commands'))) { // Show page name when it is not the root page, or when it is explicitly requested (canonical or in command)
				$str_location .= SiteStartVars::getPage('name');
			}
		}
		
		if (($mode_location == static::LOCATION_REAL || $mode_location == static::LOCATION_CANONICAL_PUBLIC) && SiteStartVars::getURITranslator() && bitHasMode(SiteStartVars::getURITranslator('mode'), uris::MODE_OUT)) {
			
			$str_location_use = substr($str_location, (!$is_relative ? strlen(URL_BASE_HOME) : 1)); // Identifier does not contain any leading information
			$arr_uri = uris::getURI(SiteStartVars::getURITranslator('id'), uris::MODE_OUT, $str_location_use);
			
			if ($arr_uri) {
				$str_location = (!$is_relative ? URL_BASE_HOME : '/').substr($arr_uri['url'], 1); // Restore leading information
			}
		}
		
		return $str_location;
	}
	
	public static function getModuleLocation($mod_id, $arr_var_names = [], $do_overwrite = true, $is_relative = true, $do_attach = false) {
		
		return Response::addParseDelay(false, function() use ($mod_id, $arr_var_names, $do_overwrite, $is_relative, $do_attach) {
		
			$arr_cur_page_module_vars = self::$arr_request_vars[$mod_id];
			
			self::setModuleVariables($mod_id, $arr_var_names, $do_overwrite);
			
			$str_location = self::getLocation($is_relative);
			
			if ($do_attach) { // Include both real and canonical URLs
				
				$arr_location = ['real' => $str_location];
				
				$str_location_canonical = self::getLocation($is_relative, static::LOCATION_CANONICAL_NATIVE);
				
				if ($str_location_canonical != $str_location) {
					$arr_location['canonical'] = $str_location_canonical;
				}
				
				$str_location = value2JSON($arr_location);
			}
			
			self::$arr_request_vars[$mod_id] = $arr_cur_page_module_vars;
			
			return $str_location;
		});
	}
	
	public static function addServerNameCustom($value, $pos = false) {
		
		if ($pos !== false) {
			self::$arr_server_name_custom[$pos] = $value;
		} else {
			self::$arr_server_name_custom[] = $value;
		}
	}
	
	public static function getServerNameCustom($to_string = false) {
		
		if (!self::$arr_server_name_custom) {
			return '';
		}
		
		if ($to_string) {
			return implode('.', self::$arr_server_name_custom).'.';
		}
		
		return self::$arr_server_name_custom;
	}
	
	public static function checkServerName() {
		
		$server_name_custom = self::getServerNameCustom(true);
		$server_protocol = (SiteStartVars::useHTTPS() && SERVER_SCHEME != URI_SCHEME_HTTPS ? URI_SCHEME_HTTPS : SERVER_SCHEME);
		
		if (SERVER_NAME_CUSTOM != $server_name_custom || $server_protocol != SERVER_SCHEME) {
			
			Response::location($server_protocol.SERVER_NAME_SUB.$server_name_custom.SERVER_NAME_1100CC.self::getLocation());
		}
	}
	
	public static function checkRequestPolicy() {
		
		if (SiteStartVars::inSecureContext() && SiteStartVars::getRequestState() == SiteStartVars::REQUEST_INDEX) {
			
			Response::addHeaders('Content-Security-Policy: frame-ancestors \'self\'');
		}
	}
}
