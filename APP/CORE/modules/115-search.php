<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class search extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_search');
		static::$parent_label = getLabel('ttl_site');
	}
		
	public function contents() {
			
		$str_search = static::decodeURLString($this->arr_query[0]);
				
		$return .= '<h1>'.getLabel('lbl_search').'</h1>
		<form id="f:search:search-0">
			<input type="search" name="string" value="'.strEscapeHTML($str_search).'" /><input type="submit" value="'.getLabel('lbl_search').'" />
		</form>
		<div class="result">';
			
			if ($this->arr_query[0]) {
				
				$return .= $this->doSearch($str_search);
				
				SiteEndVars::setModuleVariables($this->mod_id, [], true); // Clear the settings in the url
			}
		
		$return .= '</div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '
			.search form > input[type=search] { border-top-right-radius: 0px; border-bottom-right-radius: 0px; }
			.search form > input[type=submit] { margin-left: 0px; border-top-left-radius: 0px; border-bottom-left-radius: 0px; }
			.search dl { margin-top: 20px; }
			.search dl > dt { margin-top: 12px; }
			.search dl > dt > a { font-size: 1.4rem; font-weight: bold; }
			.search dl > dt > .hits { font-size: 1rem; display: inline-block; }
			.search dl > dt > .link { display: block; margin-top: 4px; }
			.search dl > dd { color: #666666; margin-top: 8px; }
			.search dl > dd em { font-style: normal; font-weight: bold; color: var(--text); background-color: #fffc5b; }
			.search dl > dt > a em { font-style: normal; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.search', function(elm_scripter) {
		
			elm_scripter.find('[id^=f\\\:search\\\:search]').data({options: {html: 'html', style: 'fade'}, target: $('.search .result')});
			updateSESearch();

			elm_scripter.on('ajaxsubmit', '[id^=f\\\:search\\\:search]', function() {
				updateSESearch();
			});
			
			function updateSESearch() {
				if (typeof _gaq != 'undefined') {
					_gaq.push(['_trackPageview', '/search?q='+elm_scripter.find('[id^=f\\\:search\\\:search]').children('input[name=string]').val()]);
				}
			}
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = '') {

		if ($method == 'search') {
				
			$this->html = $this->doSearch($_POST['string']);
		}
	}
	
	private function doSearch($str) {
	
		$arr_strings = self::getKeywords($str);
	
		if (!$arr_strings) {
			return '<p>'.getLabel('msg_search_too_short').'</p>';
		}
	
		$arr_search_properties = getModuleConfiguration('searchProperties');
		
		$arr_modules = pages::getModules(array_keys($arr_search_properties));
		$arr_modules = pages::filterClearance($arr_modules, ($_SESSION['USER_GROUP'] ?? null), ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')] ?? null));

		$arr_search_vars = [];
		
		foreach ($arr_modules as $arr_module) {
			
			if ($arr_search_properties[$arr_module['module']]['search_var'] && $arr_module['var']) {
			
				if ($arr_search_properties[$arr_module['module']]['module_var']) {
					
					$arr_var = json_decode($arr_module['var'], true);
					$var = $arr_var[$arr_search_properties[$arr_module['module']]['module_var']];
				} else {
					
					$var = $arr_module['var'];
				}
				
				$arr_search_vars[$arr_module['module']][$var] = $arr_module['id'];
			} else if (!$arr_search_properties[$arr_module['module']]['search_var']) {
				
				$arr_search_vars[$arr_module['module']] = $arr_module['id'];
			}
		}

		if ($arr_search_properties) {
							
			$arr_results = self::moduleSearchTriggers($arr_search_properties, $arr_search_vars, $arr_strings);
			
			$arr_bodies = [];
			
			foreach ($arr_results as $key => $arr_result) {
				
				$str_body = Labels::parseTextVariables($arr_result['value']);
				
				$str_body = FormatTags::strip($str_body); // Tags are not needed
				
				$format = new FormatHTML($str_body);
				
				if (Response::getFormat() & Response::RENDER_XML) {
					$str_body = $format->getXHTML();
				} else {
					$str_body = $format->getHTML();
				}
				
				$arr_bodies[$key] = Labels::printLabels($str_body);
			}
			
			$arr_cache_url = [];
						
			foreach ($arr_results as $key => $arr_result) {
				
				$module_id = (int)($arr_result['search_var'] ? $arr_search_vars[$arr_result['module']][$arr_result['search_var']] : $arr_search_vars[$arr_result['module']]);
				$str_title = strEscapeHTML(Labels::printLabels(Labels::parseTextVariables($arr_result['title'])));
				$str_excerpt = FormatExcerpt::parse($arr_bodies[$key], $arr_strings[0], false, 350, '... ', ' ...');
				
				foreach ($arr_strings as $str_search) {
					
					$str_title_highlight = FormatExcerpt::performHighlight($str_title, $str_search);
					$str_title = $str_title_highlight['result'];
					$str_excerpt_highlight = FormatExcerpt::performHighlight($str_excerpt, $str_search);
					$str_excerpt = $str_excerpt_highlight['result'];
					$body_count = FormatExcerpt::countString($arr_bodies[$key], $str_search);
					$arr_results[$key]['count'] = ($str_title_highlight['count']+($body_count ?: $str_excerpt_highlight['count']));
				}
				
				if (!$str_title) { // Get module name
					
					$arr_result['module']::moduleProperties();
					$str_title = $arr_result['module']::$label;
				}
				
				$str_url_extra = ($arr_search_properties[$arr_result['module']]['module_query']($arr_result) ?: '');

				if ($str_url_extra) { // If a module query follows
					
					if (!isset($arr_cache_url['module'][$module_id])) {
						$arr_cache_url['module'][$module_id] = pages::getModuleURL(pages::getModules($module_id));
					}
					$str_url = $arr_cache_url['module'][$module_id].$str_url_extra;
				} else {
					
					if (!isset($arr_cache_url['page'][$module_id])) {
						$arr_cache_url['page'][$module_id] = pages::getPageURL(pages::getModules($module_id));
					}
					$str_url = $arr_cache_url['page'][$module_id];
				}
				
				$arr_results[$key]['html'] = '<dt>
					<a href="'.$str_url.'" target="_blank">'.$str_title.'</a>
					<span class="hits">'.$arr_results[$key]['count'].' hit'.($arr_results[$key]['count'] > 1 ? 's' : '').'</span>
					<span class="link">'.strEscapeHTML(Labels::parseTextVariables(($arr_modules[$module_id]['directory_title'] ? $arr_modules[$module_id]['directory_title'].' > ' : '> ').$arr_modules[$module_id]['page_title'])).'</span>
				</dt>
				<dd>'.$str_excerpt.'</dd>';
			}
			
			uasort($arr_results, function($a, $b) {
				
				if ($a['count'] === $b['count']) {
					return 0;
				}
				return ($a['count'] > $b['count'] ? -1 : 1);
			});
			
			$result = implode('', arrValuesRecursive('html', $arr_results));
		}

		if ($result) {
			
			$return .= '<dl>
			'.$result.'
			</dl>';
		} else {
			
			return '<p>'.getLabel('msg_search_no_result').'</p>';
		}
		
		return $return;
	}
			
	private static function moduleSearchTriggers($arr_search_properties, $arr_search_vars, $arr_strings) {
	
		$arr_strings_identifiers = cms_Labels::searchLabels($arr_strings);
		
		$num_extra_values_total = 0;
		
		foreach ($arr_search_properties as $module => $arr_search) {
			
			if (!$arr_search_vars[$module] || !$arr_search['extra_values']) {
				continue;
			}
			
			$num_extra_values = count($arr_search['extra_values']);
			$num_extra_values_total = ($num_extra_values > $num_extra_values_total ? $num_extra_values : $num_extra_values_total);
		}
		
		$arr_query = [];
		
		foreach ($arr_search_properties as $module => $arr_search) {
			
			if (!$arr_search_vars[$module]) {
				continue;
			}
		
			$trigger_tc = $arr_search['trigger'][1];
			$title_tc = ($arr_search['title'] ? $arr_search['title'][1] : false);
			$object_link_tc = $arr_search['module_link'][0][1]; // First module_link is trigger id
			$search_var_tc = ($arr_search['search_var'] ? $arr_search['search_var'][1] : false); // Search var is used to lookup module variables
			$extra_values_tc = '';
			
			for ($i = 0; $i < $num_extra_values_total; $i++) {
				
				if (!isset($arr_search['extra_values'][$i])) {
					
					$extra_values_tc .= ',NULL AS extra_'.$i;
					continue;
				}
				
				$extra_values_tc .= ','.$arr_search['extra_values'][$i][1].' AS extra_'.$i;
			}
			
			$query = "SELECT
				".$trigger_tc." AS value
				, ".($title_tc ?: "0")." AS title
				, ".$object_link_tc." AS object_link
				, ".($search_var_tc ?: "''")." AS search_var
				, '".$module."' AS module
				".($extra_values_tc ?: '')."
					FROM ".$arr_search['trigger'][0]."
			";
					
			if (count($arr_search['module_link']) > 1) {
				
				for ($i = 0; $i < count($arr_search['module_link']); $i = $i+2) {
					
					$arr_module_link_from = $arr_search['module_link'][$i];
					$arr_module_link_to = $arr_search['module_link'][$i+1];
					
					$query .= " LEFT JOIN ".$arr_module_link_to[0]." ON (".$arr_module_link_to[1]." = ".$arr_module_link_from[1].(isset($arr_module_link_to[2]) ? " ".$arr_module_link_to[2] : '').")";
				}
			}

			$arr_query_search = [];
			
			foreach ($arr_strings_identifiers as $str => $arr_identifiers) {
				
				$arr_query_search_or = [];
				$arr_query_search_or[] = ($title_tc ? "CONCAT(".$title_tc.", ".$trigger_tc.")" : $trigger_tc)." LIKE '%".DBFunctions::strEscape($str)."%'";
				
				foreach ($arr_identifiers as $str_identifier) {
					$arr_query_search_or[] = ($title_tc ? "CONCAT(".$title_tc.", ".$trigger_tc.")" : $trigger_tc)." LIKE '%[L][".DBFunctions::strEscape($str_identifier)."]%'";
				}
				
				$arr_query_search[] = "(".implode(' OR ', $arr_query_search_or).")";
			}
			
			$query .= " WHERE (".implode(' AND ', $arr_query_search).")".(isset($arr_search['trigger'][2]) ? ' '.$arr_search['trigger'][2] : '');
			
			if ($search_var_tc) {
				$query .= " AND ".$search_var_tc." IN (".implode(',', array_keys($arr_search_vars[$module])).")";
			}

			$arr_query[] = $query;
		}

		$arr = [];
		
		$res = DB::query(implode(" UNION ", $arr_query));
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$match_all = true;
			
			foreach ($arr_strings_identifiers as $str => $arr_identifiers) { // All search strings or its identifiers have to be accounted for, meaning no overlap (i.e. "test" must not match [L][lbl_test]
				
				if (!preg_match("/".$str."(?!\w*[\]])/i", $arr_row['title'].$arr_row['value']) && (!$arr_identifiers || ($arr_identifiers && !preg_match("/(\\[L\\]\\[".implode("\\]|\\[L\\]\\[", $arr_identifiers)."\\])/", $arr_row['title'].$arr_row['value'])))) {
					
					$match_all = false;
					break;
				}
			}
			if ($match_all) {
				
				if ($arr_search['extra_values']) {
										
					foreach ($arr_search['extra_values'] as $key => $value) {
						
						$arr_row['extra_values'][$value[0]][($value[2] ?? $value[1])] = $arr_row['extra_'.$key];
					}
				}
				
				$arr[] = $arr_row;
			}
		}
		
		return $arr;
	}
	
	public static function encodeURLString($str) {
		
		$str = ($str ? str_replace(' ', '|', $str) : '');
		
		return $str;
	}
	
	public static function decodeURLString($str) {
		
		$str = ($str ? str_replace('|', ' ', $str) : '');
		
		return $str;
	}
	
	public static function getKeywords($str) {
	
		// Replace specific entities with plain spaces
		$str = str_replace(['.', ',', '_'], ' ', $str);
		
		// Remove non wordlike characters
		$str = preg_replace('/[^a-z0-9\s]/i', '', $str);
		
		// Remove small words of 2 chars
		$str = preg_replace('/(\b\w{1,2}\b)/', '', $str);
		
		// Collapse whitespace
		$str = preg_replace('/\s\s+/', ' ', $str);
		
		$str = trim($str);
		
		if (!$str) {
			return [];
		}

		// create unique keyword search array
		return array_unique(explode(' ', $str));
	
	}
}
