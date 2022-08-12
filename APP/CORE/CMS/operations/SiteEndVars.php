<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class SiteEndVars {
	
	private static $arr_head_tags = [];
	private static $arr_titles = [];
	private static $arr_descriptions = [];
	private static $arr_keywords = [];
	private static $arr_content_identifiers = [];
	private static $type = 'website';
	private static $url = '';
	private static $image = false;
	private static $arr_theme = [];
	
	public static $arr_request_vars = [];
	public static $arr_server_name_custom = [];
	public static $arr_feedback = [];
	
	protected static $shortcut_mod_id = false;
	protected static $str_shortcut_name = false;
	protected static $is_root_shortcut = false;

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

		array_unshift(self::$arr_titles, getLabel('title', 'D'), (SiteStartVars::$dir ? SiteStartVars::$dir['title'] : ''), (SiteStartVars::$page_name ? SiteStartVars::$page['title'] : ''));
		self::$arr_titles = array_filter(self::$arr_titles);
		
		return strEscapeHTML(Labels::parseTextVariables(implode(' | ', self::$arr_titles)));
	}
	
	public static function addDescription($arr_descriptions) {
	
		self::$arr_descriptions[] = $arr_descriptions;
	}
	
	public static function getDescription() {
				
		self::$arr_descriptions = (self::$arr_descriptions ? Labels::parseTextVariables(implode(' ', array_unique(self::$arr_descriptions))) : getLabel('description', 'D'));
		
		return strEscapeHTML(self::$arr_descriptions);
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
	
	public static function setType($type) {
	
		self::$type = $type;
	}
	
	public static function getType() {
	
		return self::$type;
	}
	
	public static function setUrl($url) {
	
		self::$url = $url;
	}
	
	public static function getUrl() {
	
		return self::$url;
	}
	
	public static function setImage($image) {
	
		self::$image = $image;
	}
	
	public static function getImage() {
		
		$image = (self::$image ?: '/'.DIR_CSS.'image.png');
		
		return $image;
	}
	
	public static function getIcons() {
		
		$str_image = static::getImage();

		$html = '';
		
		foreach ([16, 32, 96, 128, 196] as $num_size) {
			$html .= '<link rel="icon" type="image/png" href="'.SiteStartVars::getCacheUrl('img', [$num_size, $num_size], $str_image, (IS_CMS ? DIR_CMS : false)).'" sizes="'.$num_size.'x'.$num_size.'" />';
		}
		foreach ([57, 60, 72, 76, 114, 120, 144, 152] as $num_size) {
			$html .= '<link rel="apple-touch-icon" href="'.SiteStartVars::getCacheUrl('img', [$num_size, $num_size], $str_image, (IS_CMS ? DIR_CMS : false)).'" sizes="'.$num_size.'x'.$num_size.'" />';
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
	
	public static function setFeedback($variable, $data, $store = false) {
		
		if ($store) {
			self::$arr_feedback['store'][$variable] = $data;
			SiteStartVars::$arr_feedback[$variable] = $data; // Also update the possible original value
		} else {
			self::$arr_feedback['broadcast'][$variable] = $data;
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
	
	public static function setModVariables($mod_id = false, $arr_var_names = [], $overwrite = true) {
		
		if ($overwrite) {
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
	
	public static function getLocationVariables($is_canonical = true) {
		
		$arr = [];
		
		if (!self::$arr_request_vars) {
			return $arr;
		}
				
		ksort(self::$arr_request_vars);
		
		if (static::$shortcut_mod_id && isset(self::$arr_request_vars[static::$shortcut_mod_id]) && !$is_canonical) { // Start with the shortcut variables
			
			$arr_var_names = self::$arr_request_vars[static::$shortcut_mod_id];
			unset(self::$arr_request_vars[static::$shortcut_mod_id]);
			
			self::$arr_request_vars = [static::$shortcut_mod_id => $arr_var_names] + self::$arr_request_vars;
		}
		
		foreach (self::$arr_request_vars as $mod_id => $arr_var_names) {
			
			if (!$arr_var_names) {
				continue;
			}
			
			if ($mod_id && (!static::$shortcut_mod_id || static::$shortcut_mod_id != $mod_id || $is_canonical)) {
				$arr[] = $mod_id.'.m';
			}
			
			$cur_var_name = false;
			
			foreach ((array)$arr_var_names as $var_name => $var) {

				if (!is_numeric($var_name)) {
					
					if ($cur_var_name != $var_name) {
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
	
	public static function getLocation($rel = true, $is_canonical = false) {
		
		$location = SiteStartVars::getBasePath(0, $rel);
		
		$arr_location_vars = self::getLocationVariables($is_canonical);
		
		if ($arr_location_vars) {
			
			if (static::$shortcut_mod_id && !$is_canonical) {
				
				if (static::$is_root_shortcut) {
					$location = (!$rel ? URL_BASE_HOME : '/').static::$str_shortcut_name.'.s/'.implode('/', $arr_location_vars);
				} else {
					$location .= static::$str_shortcut_name.'.s/'.implode('/', $arr_location_vars);
				}
			} else {
			
				$location .= SiteStartVars::$page['name'].'.p/'.implode('/', $arr_location_vars);
			}
		} else {

			if (!(SiteStartVars::$dir['root'] && SiteStartVars::$dir['page_index_id'] == SiteStartVars::$page['id']) || ($is_canonical || (SiteStartVars::$page_name && SiteStartVars::$page_kind == '.c' && SiteStartVars::$page_name != 'commands'))) { // Show page name when it is not the root page, or when it is explicitly requested (canonical or in command)
				$location .= SiteStartVars::$page['name'];
			}
		}

		return $location;
	}
	
	public static function getModLocation($mod_id, $arr_var_names = [], $overwrite = true, $rel = true) {
		
		return Response::addParseDelay(false, function() use ($mod_id, $arr_var_names, $overwrite, $rel) {
		
			$arr_cur_page_module_vars = self::$arr_request_vars[$mod_id];
			
			self::setModVariables($mod_id, $arr_var_names, $overwrite);
			
			$location = self::getLocation($rel);
			
			self::$arr_request_vars[$mod_id] = $arr_cur_page_module_vars;
			
			return $location;
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
		$server_protocol = (SiteStartVars::useHTTPS() && SERVER_PROTOCOL != 'https://' ? 'https://' : SERVER_PROTOCOL);
		
		if (SERVER_NAME_CUSTOM != $server_name_custom || $server_protocol != SERVER_PROTOCOL) {
			
			Response::location($server_protocol.SERVER_NAME_SUB.$server_name_custom.SERVER_NAME_1100CC.self::getLocation());
		}
	}
}
