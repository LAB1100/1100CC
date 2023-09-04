<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_FEEDS', DB::$database_home.'.def_feeds');

class cms_feeds extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_feeds');
		static::$parent_label = getLabel('ttl_site');
	}
	
	public function contents() {
		
		$return = '<div class="section"><h1 id="x:cms_feeds:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup add_feed" value="add" /></h1>
		<div class="feeds">';

			$res = DB::query("SELECT
				f.*,
				(SELECT
					COUNT(*)
						FROM ".DB::getTable('TABLE_FEED_ENTRY_LINK')." fl
					WHERE fl.feed_id = f.id
				) AS feed_entry_count,
				(SELECT
					".DBFunctions::sqlImplode('fe.title', '<br />', 'ORDER BY fe.date DESC')."
						FROM ".DB::getTable('TABLE_FEED_ENTRY_LINK')." fl
						LEFT JOIN ".DB::getTable('TABLE_FEED_ENTRIES')." fe ON (fe.id = fl.feed_entry_id)
					WHERE fl.feed_id = f.id
				) AS feed_entries,
				".DBFunctions::sqlImplode(DBFunctions::castAs('d.id', DBFunctions::CAST_TYPE_STRING), ',', 'ORDER BY p.id')." AS directories,
				".DBFunctions::sqlImplode('p.name', ',', 'ORDER BY p.id')." AS pages
					FROM ".DB::getTable('TABLE_FEEDS')." f
					LEFT JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.var LIKE CONCAT('%\"id\":\"', f.id, '\"%') AND m.module = 'feed')
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
					LEFT JOIN ".DB::getTable('TABLE_DIRECTORIES')." d ON (d.id = p.directory_id)
				GROUP BY f.id
			");
		
			if ($res->getRowCount() == 0) {
				
				Labels::setVariable('name', getLabel('lbl_feeds'));
			
				$return .= '<section class="info">'.getLabel('msg_no', 'L', true).'</section>';
			} else {
		
				$return .= '<table class="list">
					<thead>
						<tr>
							<th>'.getLabel('lbl_name').'</th>
							<th>'.getLabel('lbl_path').'</th>
							<th>'.getLabel('lbl_feed_entries').'</th>
						</tr>
					</thead>
					<tbody>';
						while ($arr_row = $res->fetchAssoc()) {
							
							$arr_pages = str2Array($arr_row['pages'], ',');
							$arr_directories = array_filter(str2Array($arr_row['directories'], ','));
							$arr_paths = [];
							
							for ($i = 0; $i < count($arr_directories); $i++) {
								$arr_dir = directories::getDirectories($arr_directories[$i]);
								if ($arr_dir['id']) {
									$arr_paths[] = $arr_dir['path'].' / '.$arr_pages[$i];
								}
							}
							
							$arr_paths = array_unique($arr_paths);
							
							$return .= '<tr id="x:cms_feeds:feed_id-'.$arr_row['id'].'">
								<td>'.$arr_row['name'].'</td>
								<td><span class="info"><span class="icon" title="'.($arr_paths ? implode('<br />', $arr_paths) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.count($arr_paths).'</span></span></td>
								<td><span class="info"><span class="icon" title="'.(strEscapeHTML($arr_row['feed_entries']) ?: getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)$arr_row['feed_entry_count'].'</span></span></td>
								<td><input type="button" class="data edit popup edit_feed" value="edit" /><input type="button" class="data del msg del_feed" value="del" /></td>
							</tr>';
						}
					$return .= '</tbody>
				</table>';
			}
						
		$return .= '</div></div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "edit_feed" || $method == "add_feed") {
		
			if ((int)$id) {
				
				$res = DB::query("SELECT f.*
						FROM ".DB::getTable('TABLE_FEEDS')." f
					WHERE f.id = ".(int)$id."");
									
				$arr = $res->fetchAssoc();
				
				$mode = "update_feed";
			} else {
				$mode = "insert_feed";
			}
														
			$this->html = '<form id="frm-feeds" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($arr['name']).'"></div>
					</li>		
				</ul></fieldset>
			</form>';
			
			$this->validate = ['name' => 'required'];
		}
		
		// POPUP INTERACT
							
		// QUERY
	
		if ($method == "insert_feed") {
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_FEEDS')."
				(name)
					VALUES
				('".DBFunctions::strEscape($_POST['name'])."')
			");
						
			$this->refresh = true;
			$this->msg = true;
		}
		
		if ($method == "update_feed" && (int)$id) {
					
			$res = DB::query("UPDATE ".DB::getTable('TABLE_FEEDS')." SET
					name = '".DBFunctions::strEscape($_POST['name'])."'
				WHERE id = ".(int)$id."
			");
						
			$this->refresh = true;
			$this->msg = true;
		}
			
		if ($method == "del_feed" && (int)$id) {
		
			$res = DB::queryMulti("
				DELETE FROM ".DB::getTable('TABLE_FEED_ENTRY_LINK')."
					WHERE feed_id = ".(int)$id."
				;
				DELETE FROM ".DB::getTable('TABLE_FEEDS')."
					WHERE id = ".(int)$id."
				;
			");
				
			$this->msg = true;
		}
	}	
	
	public static function getFeeds($feed_id = 0) {
	
		$arr = [];

		if ($feed_id) {
			
			$res = DB::query("SELECT *
				FROM ".DB::getTable('TABLE_FEEDS')."
				WHERE id = ".(int)$feed_id."
			");
			
			$arr = ($res->fetchAssoc() ?: []);
		} else {
			
			$res = DB::query("SELECT *
				FROM ".DB::getTable('TABLE_FEEDS')."
				ORDER BY id
			");
			
			while ($arr_row = $res->fetchAssoc()) {
				$arr[$arr_row['id']] = $arr_row;
			}
		}
			
		return $arr;
	}
	
	public static function findMainFeed($feed_id) {

		return pages::getClosestModule('feed', 0, 0, 0, $feed_id, 'id');
	}
}
