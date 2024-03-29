<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_FEED_ENTRIES', DB::$database_home.'.def_feed_entries');
DB::setTable('TABLE_FEED_ENTRY_LINK', DB::$database_home.'.def_feed_entry_link');
DB::setTable('TABLE_FEED_ENTRY_TAGS', DB::$database_home.'.def_feed_entry_tags');

class cms_feed_entries extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_feed_entries');
		static::$parent_label = getLabel('ttl_content');
	}
	
	public static function mediaLocations() {
		return [
			'TABLE_FEED_ENTRIES' => [
				'body',
				'media'
			]
		];
	}

	public function contents() {
		
		$return = '<div class="section"><h1 id="x:cms_feed_entries:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup add_feed_entry" value="add" /></h1>
		<div class="feed_entries">';
			
			$arr_feeds = cms_feeds::getFeeds();
			$num_columns = 5;
			
			$return .= '<table class="display" id="d:cms_feed_entries:data_feed_entries-0">
				<thead> 
					<tr>
						<th class="max">'.getLabel('lbl_title').'</th>
						<th>'.getLabel('lbl_url').'</th>
						<th>'.getLabel('lbl_content').'</th>
						<th data-sort="desc-0">'.getLabel('lbl_date').'</th>';
						
						if (count($arr_feeds) > 1) {
							
							foreach ($arr_feeds as $arr_feed) {
								$return .= '<th>F: '.$arr_feed['name'].'</th>';
							}
							
							$num_columns += count($arr_feeds);
						}
						
						$return .= '<th class="disable-sort menu" id="x:cms_feed_entries:feed_entry_id-0" title="'.getLabel('lbl_multi_select').'">'
							.'<input type="button" class="data del msg del_feed_entry" value="d" title="'.getLabel('lbl_delete').'" />'
							.'<input type="checkbox" class="multi all" value="" />'
						.'</th>
					</tr> 
				</thead>
				<tbody>
					<tr>
						<td colspan="'.$num_columns.'" class="empty">'.getLabel('msg_loading_server_data').'</td>
					</tr>
				</tbody>
			</table>';
						
		$return .= '</div></div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '
			.feed_entries table td.no-title { font-style: italic; }
			#frm-feed_entry input[name=title] { width: 500px; }
			#frm-feed_entry .body-content { height: 250px; }
			#frm-feed_entry img.select { max-height: 120px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "edit_feed_entry" || $method == "add_feed_entry") {
		
			$arr_tags = [];
			$arr_feeds = cms_feeds::getFeeds();
			$arr_feed_entry = [];
		
			if ((int)$id) {
				
				$arr_feed_entry = self::getFeedEntries(false, $id);
				
				$arr_tags = cms_general::getTagsByObject(DB::getTable('TABLE_FEED_ENTRY_TAGS'), 'feed_entry_id', $arr_feed_entry['id']);
								
				$mode = "update_feed_entry";
			} else {
			
				if (!$arr_feeds) {
					
					Labels::setVariable('name', getLabel('lbl_feeds'));
			
					$this->html = '<section class="info">'.getLabel('msg_no', 'L', true).'</section>';
					return;
				}
						
				$mode = "insert_feed_entry";
			}
			
			$this->html = '<form id="frm-feed_entry" data-method="'.$mode.'" data-lock="1">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_feed').'</label>';
						
						if (count($arr_feeds) > 1) {
							
							$this->html .= '<div>'.cms_general::createSelector($arr_feeds, 'feed', ($mode == 'insert_feed_entry' ? 'all' : self::getFeedEntryLinks($arr_feed_entry['id']))).'</div>';
						} else if (count($arr_feeds) == 1) {
							
							$arr_feed = current($arr_feeds);
							
							$this->html .= '<div><input type="hidden" name="feed['.$arr_feed['id'].']" value="1" />'.$arr_feed['name'].'</div>';
						}
						
					$this->html .= '</li>
					<li>
						<label>'.getLabel('lbl_title').'</label>
						<div><input type="text" name="title" value="'.strEscapeHTML($arr_feed_entry['title']).'"></div>
					</li>
					<li>
						<label>'.getLabel('lbl_url').'</label>
						<div><input type="text" name="url" value="'.strEscapeHTML($arr_feed_entry['url']).'" /></div>
					</li>
					<li>
						<label></label>
						<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
					</li>
					<li>
						<label>'.getLabel('lbl_media').'</label>';
						
						$arr_sorter = [];
						
						$arr_media = ($arr_feed_entry['media'] ?: ['']);
						array_unshift($arr_media, ''); // Empty run for sorter source
						
						foreach ($arr_media as $key => $str_media) {
							
							$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => cms_general::createImageSelector($str_media, 'media[]')];
						}
						
						$this->html .= cms_general::createSorter($arr_sorter, true, false, ['limit' => 4])
					.'</li>
					<li>
						<label>'.getLabel('lbl_body').'</label>
						<div>'.cms_general::editBody($arr_feed_entry['body']).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_tags').'</label>
						<div>'.cms_general::createSelectTags($arr_tags, '', false).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_date').'</label>
						<div>'.cms_general::createDefineDate($arr_feed_entry['date'], '', false).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_order').'</label>
						<div>';
						
							$arr_order = static::getFeedEntries(false, false, 0, 0, false, true);
							
							if ($arr_feed_entry) {

								unset($arr_order[$arr_feed_entry['id']]);
							}
							
							foreach ($arr_order as &$value) {
								
								$str_entry_name = ($value['title'] ?: getLabel('lbl_no_title')).' ('.$value['sort'].') '.date('d-m-Y', strtotime($value['date']));
								
								$value['title'] = getLabel('lbl_after').cms_general::OPTION_GROUP_SEPARATOR.$str_entry_name;
							}
							unset($value);
							
							$arr_order_default = ['date' => ['id' => 'date', 'title' => getLabel('lbl_date')], 'first' => ['id' => 'first', 'title' => getLabel('lbl_first')]];
							$arr_order = $arr_order_default + $arr_order;
						
							$this->html .= '<select name="order">'.cms_general::createDropdown($arr_order, (!$id ? 'date' : false), (!$id ? false : true), 'title').'</select>
						</div>
					</li>
				</ul></fieldset>
			</form>';
			
			$this->validate = ['date' => 'required', 'feed' => 'required'];
		}
		
		// POPUP INTERACT
						
		// DATATABLE
					
		if ($method == "data_feed_entries") {
			
			$arr_sql_columns = ['title', 'url', 'body', 'date'];
			$arr_sql_columns_search = ['title', 'url', 'body', false, DBFunctions::castAs('date', DBFunctions::CAST_TYPE_STRING)];
			$arr_sql_columns_as = ['title', 'url', 'body', 'date', 'sort', 'fe.id'];
			
			$arr_feeds = cms_feeds::getFeeds();
			$arr_urls_base = [];
			
			foreach ($arr_feeds as $arr_feed) {
				
				$arr_link = cms_feeds::findMainFeed($arr_feed['id']);
				$arr_urls_base[$arr_feed['id']] = pages::getModuleURL($arr_link);

				$arr_sql_columns[] = 'feed_'.$arr_feed['id'].'.feed_id';
				$arr_sql_columns_as[] = 'feed_'.$arr_feed['id'].'.feed_id AS feed_'.$arr_feed['id'];
			}
			
			$sql_table = DB::getTable('TABLE_FEED_ENTRIES').' fe';

			$sql_index = 'fe.id';
			$sql_index_body = 'fe.id';
						
			foreach ($arr_feeds as $arr_feed) {
				
				$sql_index_body .= ', feed_'.$arr_feed['id'].'.feed_id';
				$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_FEED_ENTRY_LINK')." feed_".$arr_feed['id']." ON (feed_".$arr_feed['id'].".feed_entry_id = fe.id AND feed_".$arr_feed['id'].".feed_id = ".$arr_feed['id'].")";
			}
				 
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', $sql_index_body);
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:cms_feed_entries:feed_entry_id-'.$arr_row['id'].'';
				
				if (!$arr_row['title']) {
					$arr_data[] = getLabel('lbl_no_title');
					$arr_data['cell'][0]['attr']['class'] = 'no-title';
				} else {
					$arr_data[] = $arr_row['title'];
				}
				
				
				if ($arr_row['url']) {
					$arr_data[] = '<a href="'.uris::getURL($arr_row['url']).'" target="_blank"><span class="icon">'.getIcon('linked').'</span></a>';
				} else {
					$arr_data[] = '<span class="icon" data-category="status">'.getIcon('min').'</span>';
				}

				$str_body = $arr_row['body'];
				if (mb_strlen($str_body) > 200) {
					$str_body = mb_substr($str_body, 0, 200).' [...]';
				}
				$arr_data[] = strEscapeHTML(strip_tags($str_body));
										
				$arr_data[] = date('d-m-Y', strtotime($arr_row['date'])).($arr_row['sort'] ? ' <small class="identifier" title="'.getLabel('lbl_order').'">'.$arr_row['sort'].'</small>' : '');
				
				if (count($arr_feeds) > 1) {
					
					foreach ($arr_feeds as $arr_feed) {
						
						if ($arr_row['feed_'.$arr_feed['id']]) {
							
							$str_title_url = $arr_urls_base[$arr_feed['id']].$arr_row['id'].'/'.str2URL($arr_row['title']);
							
							$arr_data[] = '<a href="'.$str_title_url.'" target="_blank"><span class="icon">'.getIcon('tick').'</span></a>';
						} else {
							
							$arr_data[] = '';
						}
					}
				}

				$arr_data[] = '<input type="button" class="data edit popup edit_feed_entry" value="edit" />'
					.'<input type="button" class="data del msg del_feed_entry" value="del" />'
					.'<input class="multi" value="'.$arr_row['id'].'" type="checkbox" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
							
		// QUERY
	
		if ($method == "insert_feed_entry") {
			
			$arr_feed_entry = $_POST;
			$arr_feed_entry['date'] = $_POST['date'].' '.$_POST['date_t'];
			
			$id = static::handleFeedEntry(false, $arr_feed_entry, $_POST['feed'], $_POST['tags']);
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "update_feed_entry" && (int)$id) {
			
			$arr_feed_entry = $_POST;
			$arr_feed_entry['date'] = $_POST['date'].' '.$_POST['date_t'];
			
			static::handleFeedEntry($id, $arr_feed_entry, $_POST['feed'], $_POST['tags']);
			
			$this->refresh_table = true;
			$this->msg = true;
		}
			
		if ($method == "del_feed_entry" && $id) {
		
			static::deleteFeedEntry($id);
			
			$this->refresh_table = true;
			$this->msg = true;
		}
	}
	
	public static function handleFeedEntry($feed_entry_id = false, $arr_feed_entry = [], $arr_feeds = [], $arr_tags = []) {
		
		$is_new = true;
		$arr_feeds = ($arr_feeds && !is_array($arr_feeds) ? [$arr_feeds => true] : $arr_feeds);
		
		$str_url = (string)$arr_feed_entry['url'];
		if ($str_url && !FileGet::getProtocolExternal($str_url)) {
			
			if (substr($str_url, 0, 1) != '/') {
				$str_url = '/'.$str_url;
			}
		}
		
		$str_media = ($arr_feed_entry['media'] ?: '');
		if (is_array($str_media)) {
			$str_media = arr2String(array_filter(array_unique($str_media)), DBFunctions::SQL_VALUE_SEPERATOR);
		}
		$str_sql_date = ($arr_feed_entry['date'] ? date('Y-m-d H:i:s', strtotime($arr_feed_entry['date'])) : false);
		
		if ($feed_entry_id) {
			
			$res = DB::queryMulti("
				SELECT TRUE FROM ".DB::getTable('TABLE_FEED_ENTRIES')." WHERE id = ".(int)$feed_entry_id.";
				
				UPDATE ".DB::getTable('TABLE_FEED_ENTRIES')." SET
					title = '".DBFunctions::strEscape($arr_feed_entry['title'])."',
					url = '".DBFunctions::strEscape($str_url)."',
					media = '".DBFunctions::strEscape($str_media)."',
					body = '".DBFunctions::strEscape($arr_feed_entry['body'])."'
					".($str_sql_date ? ", date = '".$str_sql_date."'" : '')."
						WHERE id = ".(int)$feed_entry_id.";
			");
			
			if (!$res[0]->getRowCount()) {
				error(getLabel('msg_error_database_missing_record'));
			}
			
			$is_new = false;
		} else {
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_FEED_ENTRIES')."
				(title, url, media, body, date)
					VALUES
				(
					'".DBFunctions::strEscape($arr_feed_entry['title'])."',
					'".DBFunctions::strEscape($str_url)."',
					'".DBFunctions::strEscape($str_media)."',
					'".DBFunctions::strEscape($arr_feed_entry['body'])."',
					".($str_sql_date ? "'".$str_sql_date."'" : 'NOW()')."
				)
			");
			
			$feed_entry_id = DB::lastInsertID();
		}
		
		if ($arr_feed_entry['order'] && (!$is_new || $arr_feed_entry['order'] !== 'date')) {
		
			static::setFeedEntryOrder($feed_entry_id, $arr_feed_entry['order']);
		}
		
		if (!$is_new) {
				
			$res = DB::query("DELETE FROM ".DB::getTable('TABLE_FEED_ENTRY_LINK')."
				WHERE feed_entry_id = ".(int)$feed_entry_id."
			");
		}
		
		if ($arr_feeds) {

			foreach ($arr_feeds as $feed_id => $do_enable) {
				
				if (!$do_enable) {
					continue;
				}
				
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_FEED_ENTRY_LINK')."
					(feed_id, feed_entry_id)
						VALUES
					(".(int)$feed_id.", ".(int)$feed_entry_id.")
				");
			}
		}
					
		cms_general::handleTags(DB::getTable('TABLE_FEED_ENTRY_TAGS'), 'feed_entry_id', $feed_entry_id, $arr_tags);
		
		return $feed_entry_id;
	}
	
	public static function setFeedEntryOrder($feed_entry_id, $order_feed_entry_id = false) {

		$num_sort = 0;
		
		if ($order_feed_entry_id && $order_feed_entry_id !== 'date') {
			
			$num_position = 0;
			
			if ($order_feed_entry_id === 'first') {
				
				$num_position = 0;
			} else {
			
				$res = DB::query("SELECT
					sort
						FROM ".DB::getTable('TABLE_FEED_ENTRIES')."
					WHERE id = ".(int)$order_feed_entry_id."
				");
				
				$num_position = $res->fetchRow();
				$num_position = (int)$num_position[0];
			}
			
			$num_sort = ($num_position + 1);
		}
		
		// When the feed entry does not want to be ordered, put it in front of the ordered list, but ignore it in the final num_row result (-1)
		
		$res = DB::query("
			".DBFunctions::updateWith(
				DB::getTable('TABLE_FEED_ENTRIES'), 'fe', 'id',
				["JOIN (SELECT
					fe.id,
					ROW_NUMBER() OVER (ORDER BY CASE
						WHEN fe.id = ".(int)$feed_entry_id." THEN ".$num_sort."
						WHEN fe.sort > ".($num_sort > 0 ? $num_sort-1 : 0)." THEN fe.sort + 2
						ELSE fe.sort
					END ASC) AS num_row
						FROM ".DB::getTable('TABLE_FEED_ENTRIES')." AS fe
					WHERE fe.id = ".(int)$feed_entry_id."
						OR fe.sort > 0
				) AS table_order ON (table_order.id = fe.id)", 'num_row'],
				['sort' => ($num_sort == 0 ? 'CASE
					WHEN fe.id = '.(int)$feed_entry_id.' THEN 0
					ELSE num_row - 1
				END' : 'num_row')]
			)."
		");
		
		return $num_sort;
	}
	
	public static function deleteFeedEntry($feed_entry_id) {
		
		$feed_entry_id = arrParseRecursive($feed_entry_id, TYPE_INTEGER);
		$sql_ids = (is_array($feed_entry_id) ? 'IN ('.arr2String($feed_entry_id, ',').')' : '= '.$feed_entry_id);
		
		$res = DB::queryMulti("
			DELETE FROM ".DB::getTable('TABLE_FEED_ENTRY_TAGS')."
				WHERE feed_entry_id ".$sql_ids."
			;
			DELETE FROM ".DB::getTable('TABLE_FEED_ENTRY_LINK')."
				WHERE feed_entry_id ".$sql_ids."
			;
			DELETE FROM ".DB::getTable('TABLE_FEED_ENTRIES')."
				WHERE id ".$sql_ids."
			;
		");
	}
	
	public static function getFeedEntryPosition($feed_id, $feed_entry_id, $arr_tags = false) {
		
		$sql_tags = false;
		if ($arr_tags) {
			$sql_tags = (!is_array($arr_tags) ?  "= '".DBFunctions::strEscape($arr_tags)."'" : "IN ('".arr2String(DBFunctions::arrEscape($arr_tags), "','")."')");
		}
	
		$res = DB::query("SELECT
			num_row
				FROM (SELECT
					fe.id,
					ROW_NUMBER() OVER (ORDER BY fe.sort > 0 DESC, fe.sort ASC, fe.date DESC) AS num_row
						FROM ".DB::getTable('TABLE_FEED_ENTRIES')." fe
						JOIN ".DB::getTable('TABLE_FEED_ENTRY_LINK')." l ON (l.feed_entry_id = fe.id)
						".($sql_tags ? "
							JOIN ".DB::getTable('TABLE_FEED_ENTRY_TAGS')." ftsel ON (ftsel.feed_entry_id = fe.id)
							JOIN ".DB::getTable('TABLE_TAGS')." tsel ON (tsel.id = ftsel.tag_id AND tsel.name ".$sql_tags.")"
						: "")."
					WHERE l.feed_id = ".(int)$feed_id."
				) AS feo
			WHERE feo.id = ".(int)$feed_entry_id."
		");
		
		$num_order = $res->fetchRow();
		$num_order = (int)$num_order[0];
		$num_postion = ($num_order != 0 ? $num_order - 1 : 0);

		return $num_postion;
	}
	
	public static function getFeedEntryLinks($feed_entry_id) {
	
		$arr = [];

		$res = DB::query("SELECT feed_id FROM ".DB::getTable('TABLE_FEED_ENTRY_LINK')." WHERE feed_entry_id = ".(int)$feed_entry_id."");
		
		while ($arr_row = $res->fetchAssoc()) {
			$arr[] = $arr_row['feed_id'];
		}	

		return $arr;
	}
	
	public static function getFeedEntriesCount($feed_id = 0, $arr_tags = false) {
		
		$sql_tags = false;
		if ($arr_tags) {
			$sql_tags = (!is_array($arr_tags) ?  "= '".DBFunctions::strEscape($arr_tags)."'" : "IN ('".arr2String(DBFunctions::arrEscape($arr_tags), "','")."')");
		}
		
		$res = DB::query("SELECT
			COUNT(fe.id)
				FROM ".DB::getTable('TABLE_FEED_ENTRIES')." fe
				JOIN ".DB::getTable('TABLE_FEED_ENTRY_LINK')." l ON (l.feed_entry_id = fe.id)
				".($sql_tags ? "
					JOIN ".DB::getTable('TABLE_FEED_ENTRY_TAGS')." ftsel ON (ftsel.feed_entry_id = fe.id)
					JOIN ".DB::getTable('TABLE_TAGS')." tsel ON (tsel.id = ftsel.tag_id AND tsel.name ".$sql_tags.")"
				: "")."
			WHERE l.feed_id = ".(int)$feed_id."
		");
		
		$arr_row = $res->fetchRow();
		
		return $arr_row[0];
	}
	
	public static function getFeedEntries($feed_id = false, $feed_entry_id = false, $num_limit = 0, $num_start = 0, $arr_tags = false, $do_sorted_only = false) {
	
		$arr = [];
		
		$sql_tags = false;
		if ($arr_tags) {
			$sql_tags = (!is_array($arr_tags) ? "= '".DBFunctions::strEscape($arr_tags)."'" : "IN ('".arr2String(DBFunctions::arrEscape($arr_tags), "','")."')");
		}
				
		$res = DB::query("SELECT
			fe.*,
			".DBFunctions::sqlImplode('t.name', ',')." AS tags
				FROM ".DB::getTable('TABLE_FEED_ENTRIES')." fe
				".($feed_id ? "JOIN ".DB::getTable('TABLE_FEED_ENTRY_LINK')." l ON (l.feed_entry_id = fe.id AND l.feed_id = ".(int)$feed_id.")" : "")."
				LEFT JOIN ".DB::getTable('TABLE_FEED_ENTRY_TAGS')." ft ON (ft.feed_entry_id = fe.id)
				LEFT JOIN ".DB::getTable('TABLE_TAGS')." t ON (t.id = ft.tag_id)
				".($sql_tags ? "
					JOIN ".DB::getTable('TABLE_FEED_ENTRY_TAGS')." ftsel ON (ftsel.feed_entry_id = fe.id)
					JOIN ".DB::getTable('TABLE_TAGS')." tsel ON (tsel.id = ftsel.tag_id AND tsel.name ".$sql_tags.")"
				: "")."
			WHERE TRUE
				".($feed_entry_id ? "AND fe.id = ".(int)$feed_entry_id : '')."
				".($do_sorted_only ? "AND fe.sort > 0" : '')."
			GROUP BY fe.id
			ORDER BY fe.sort > 0 DESC, fe.sort ASC, fe.date DESC
			".($num_start || $num_limit ? "LIMIT ".(int)$num_limit." OFFSET ".(int)$num_start : "")."
		");
									
		while ($arr_row = $res->fetchAssoc()) {
						
			$arr_tags = $arr_row['tags'];
			
			if ($arr_tags) {
			
				$arr_tags = explode(',', $arr_tags);
				$arr_tags = array_combine($arr_tags, $arr_tags);
				ksort($arr_tags);
			}
			
			$arr_row['tags'] = ($arr_tags ?: []);
			$arr_row['media'] = str2Array($arr_row['media'], DBFunctions::SQL_VALUE_SEPERATOR);

			$arr[$arr_row['id']] = $arr_row;
		}
		
		return ($feed_entry_id ? current($arr) : $arr);
	}
}
