<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class search extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_search');
		static::$parent_label = getLabel('ttl_site');
	}
		
	public function contents() {
	
		if ($this->arr_query[0] == 'jump') {
			
			if ($this->arr_query[2]) { // If a module query follows
				$location = pages::getModUrl(pages::getMods((int)$this->arr_query[1])).implode('/', array_slice($this->arr_query, 2));
			} else {
				$location = pages::getPageUrl(pages::getMods((int)$this->arr_query[1]));
			}
			Response::location($location);
			die;
		}	
				
		$return .= '<h1>'.getLabel('ttl_search').'</h1>
		<form id="f:search:search-0">
			<input type="search" name="string" value="'.htmlspecialchars($this->arr_query[0]).'" /><input type="submit" value="'.getLabel('lbl_search').'" />
		</form>
		<div class="result">';
			
			if ($this->arr_query[0]) {
				$return .= $this->doSearch($this->arr_query[0]);
			}
		
		$return .= '</div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.search form input { vertical-align: top; }
					.search dl {  }
					.search dl > dt { margin-top: 8px; }
					.search dl > dt > a { font-size: 14px; font-weight: bold; }
					.search dl > dt > span.hits { font-size: 10px; }
					.search dl > dt > span.link { display: block; }
					.search dl > dd { color: #666666; }
					.search dl > dd em { font-style: normal; font-weight: bold; color: #000000; }
					.search dl > dt > a em { font-style: normal; }';
		
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
	
	private function doSearch($string) {
	
		$arr_strings = self::getKeywords($string);
	
		if (!$arr_strings) {
			return '<p>'.getLabel('msg_search_too_short').'</p>';
		}
	
		$arr_search_properties = getModuleConfiguration('searchProperties');
		
		$arr_modules = pages::getMods(array_keys($arr_search_properties));
		$arr_modules = pages::filterClearance($arr_modules, $_SESSION['USER_GROUP'], $_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]);

		$arr_search_vars = [];
		
		foreach ($arr_modules as $row) {
			
			if ($arr_search_properties[$row['module']]['search_var'] && $row['var']) {
			
				if ($arr_search_properties[$row['module']]['module_var']) {
					
					$arr_var = json_decode($row['var'], true);
					$var = $arr_var[$arr_search_properties[$row['module']]['module_var']];
				} else {
					
					$var = $row['var'];
				}
				
				$arr_search_vars[$row['module']][$var] = $row['id'];
			} else if (!$arr_search_properties[$row['module']]['search_var']) {
				
				$arr_search_vars[$row['module']] = $row['id'];
			}
		}

		if ($arr_search_properties) {
							
			$arr_result = self::moduleSearchTriggers($arr_search_properties, $arr_search_vars, $arr_strings);
			
			$arr_bodies = [];
			foreach ($arr_result as $key => $row) {
				$arr_bodies[$key] = parseBody($row['value']);
			}
			$arr_bodies = Labels::printLabels($arr_bodies);
			
			foreach ($arr_result as $key => $row) {
				
				$module_id = ($row['search_var'] ? $arr_search_vars[$row['module']][$row['search_var']] : $arr_search_vars[$row['module']]);
				$title = htmlspecialchars(Labels::printLabels(Labels::parseTextVariables($row['title'])));
				$excerpt = htmlspecialchars(FormatExcerpt::parse($arr_bodies[$key], $arr_strings[0], false, 350, '... ', ' ...'));
				
				foreach ($arr_strings as $string) {
					
					$title_h = FormatExcerpt::performHighlight($title, $string);
					$title = $title_h['result'];
					$excerpt_h = FormatExcerpt::performHighlight($excerpt, $string);
					$excerpt = $excerpt_h['result'];
					$body_count = FormatExcerpt::countString($arr_bodies[$key], $string);
					$arr_result[$key]['count'] = ($title_h['count']+($body_count ?: $excerpt_h['count']));
				}
				
				$href = ''.SiteStartVars::getModUrl($this->mod_id).'jump/'.$module_id.($arr_search_properties[$row['module']]['module_query']($row) ?: '');
				$arr_result[$key]['html'] = '<dt>
					<a href="'.$href.'" target="_blank">'.$title.'</a>
					<span class="hits">'.$arr_result[$key]['count'].' hit'.($arr_result[$key]['count'] > 1 ? 's' : '').'</span>
					<span class="link">'.htmlspecialchars(Labels::parseTextVariables(($arr_modules[$module_id]['directory_title'] ? $arr_modules[$module_id]['directory_title'].' > ' : '> ').$arr_modules[$module_id]['page_title'])).'</span>
				</dt>
				<dd>'.$excerpt.'</dd>';
			}
			
			uasort($arr_result, function($a, $b) {
				return $a['count']<$b['count'];
			});
			
			$result = implode('', arrValuesRecursive('html', $arr_result));
		}

		if ($result) {
			$return .= '<h2>'.getLabel('ttl_result').'</h2>
			<dl>
			'.$result.'
			</dl>';
		} else {
			return '<p>'.getLabel('msg_search_no_result').'</p>';
		}
		
		return $return;
	}
			
	private static function moduleSearchTriggers($arr_search_properties, $arr_search_vars, $arr_strings) {
	
		$arr_identifiers = cms_Labels::searchLabels($arr_strings);
					
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
			
			if ($arr_search['extra_values']) {
				
				foreach ($arr_search['extra_values'] as $key => $value) {
					
					$extra_values_tc .= ','.$value[1].' AS extra_'.$key;
				}
			}
			reset($arr_search);
			
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
					
					$query .= " LEFT JOIN ".$arr_module_link_to[0]." ON (".$arr_module_link_to[1]." = ".$arr_module_link_from[1].($arr_module_link_to[2] ? " ".$arr_module_link_to[2] : '').")";
				}
			}

			$arr_query_search = [];
			foreach ($arr_identifiers as $string => $value) {
				$arr_query_search_or = [];
				$arr_query_search_or[] = ($title_tc ? "CONCAT(".$title_tc.", ".$trigger_tc.")" : $trigger_tc)." LIKE '%".DBFunctions::strEscape($string)."%'";
				foreach ($value as $identifier) {
					$arr_query_search_or[] = ($title_tc ? "CONCAT(".$title_tc.", ".$trigger_tc.")" : $trigger_tc)." LIKE '%[L][".DBFunctions::strEscape($identifier)."]%'";
				}
				$arr_query_search[] = implode(" OR ", $arr_query_search_or);
			}
			$query .= " WHERE (".implode(" AND ", $arr_query_search).")";
			if ($search_var_tc) {
				$query .= " AND ".$search_var_tc." IN (".implode(",", array_keys($arr_search_vars[$module])).")";
			}

			$arr_query[] = $query;
		}

		$arr = [];
		
		$res = DB::query(implode(" UNION ", $arr_query));
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$match_all = true;
			
			foreach ($arr_identifiers as $string => $value) { // All search strings or its identifiers have to be accounted for, meaning no overlap (i.e. "test" must not match [L][lbl_test]
				
				if (!preg_match("/".$string."(?!\w*[\]])/i", $arr_row['title'].$arr_row['value']) && (!$value || ($value && !preg_match("/(\\[L\\]\\[".implode("\\]|\\[L\\]\\[", $value)."\\])/", $arr_row['title'].$arr_row['value'])))) {
					
					$match_all = false;
					break;
				}
			}
			if ($match_all) {
				
				if ($arr_search['extra_values']) {
										
					foreach ($arr_search['extra_values'] as $key => $value) {
						
						$arr_row['extra_values'][$value[0]][$value[1]] = $arr_row['extra_'.$key];
					}
				}
				
				$arr[] = $arr_row;
			}
		}
		
		return $arr;
	}
	
	public static function getKeywords($string) {
	
		// Replace specific entities with plain spaces
		$string = str_replace(['.', ',', '_'], ' ', $string);
		
		// Remove non wordlike characters
		$string = preg_replace('/[^a-z0-9\s]/i', '', $string);
		
		// Remove small words of 3 chars
		$string = preg_replace('/(\b\w{1,3}\b)/', '', $string);
		
		// Collapse whitespace
		$string = preg_replace('/\s\s+/', ' ', $string);
		
		$string = trim($string);
		
		if (!$string) {
			return [];
		}

		// create unique keyword search array
		return array_unique(explode(" ", $string));
	
	}
}
