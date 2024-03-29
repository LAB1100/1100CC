<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_POLLS', DB::$database_home.'.def_polls');

class cms_polls extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_polls');
		static::$parent_label = getLabel('ttl_site');
	}
	
	public function contents() {
		
		$return = '<div class="section"><h1 id="x:cms_polls:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup poll_add" value="add" /></h1>
		<div class="polls">';

			$res = DB::query("SELECT
				po.*,
				(SELECT 
					COUNT(pol.poll_set_id)
						FROM ".DB::getTable('TABLE_POLL_SET_LINK')." pol
					WHERE pol.poll_id = po.id
				) AS poll_set_count,
				(SELECT 
					".DBFunctions::sqlImplode('pos.label', '<br />', 'ORDER BY pos.date DESC')."
						FROM ".DB::getTable('TABLE_POLL_SET_LINK')." pol
						LEFT JOIN ".DB::getTable('TABLE_POLL_SETS')." pos ON (pos.id = pol.poll_set_id)
					WHERE pol.poll_id = po.id
				) AS poll_sets,
				".DBFunctions::sqlImplode(DBFunctions::castAs('d.id', DBFunctions::CAST_TYPE_STRING), ',', 'ORDER BY p.id')." AS directories,
				".DBFunctions::sqlImplode('p.name', ',', 'ORDER BY p.id')." AS pages
					FROM ".DB::getTable('TABLE_POLLS')." po
					LEFT JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.var != '' AND ".DBFunctions::castAs('m.var', DBFunctions::CAST_TYPE_INTEGER)." = po.id AND m.module = 'poll')
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
					LEFT JOIN ".DB::getTable('TABLE_DIRECTORIES')." d ON (d.id = p.directory_id)
				GROUP BY po.id
			");
		
			if ($res->getRowCount() == 0) {
				
				$return .= '<section class="info">'.getLabel('msg_no_polls').'</section>';
			} else {
		
				$return .= '<table class="list">
					<thead>
						<tr>
							<th class="max"><span>'.getLabel('lbl_name').'</span></th>
							<th><span>'.getLabel('lbl_path').'</span></th>
							<th><span>'.getLabel('lbl_poll_sets').'</span></th>
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
							
							$return .= '<tr id="x:cms_polls:poll_id-'.$arr_row['id'].'">
								<td>'.$arr_row['name'].'</td>
								<td><span class="info"><span class="icon" title="'.($arr_paths ? implode('<br />', $arr_paths) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.count($arr_paths).'</span></span></td>
								<td><span class="info"><span class="icon" title="'.(strEscapeHTML($arr_row['poll_sets']) ?: getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)$arr_row['poll_set_count'].'</span></span></td>
								<td><input type="button" class="data edit popup poll_edit" value="edit" /><input type="button" class="data del msg poll_del" value="del" /></td>
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
		
		if ($method == "poll_edit" || $method == "poll_add") {
		
			if ((int)$id) {
				
				$arr = self::getPolls($id);
				
				$mode = "poll_update";
			} else {
				
				$mode = "poll_insert";
			}
														
			$this->html = '<form id="frm-polls" data-method="'.$mode.'">
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
	
		if ($method == "poll_insert") {
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_POLLS')." (name) VALUES ('".DBFunctions::strEscape($_POST['name'])."')");
						
			$this->refresh = true;
			$this->msg = true;
		}
		
		if ($method == "poll_update" && $id) {
					
			$res = DB::query("UPDATE ".DB::getTable('TABLE_POLLS')." SET name = '".DBFunctions::strEscape($_POST['name'])."' WHERE id = ".(int)$id."");
						
			$this->refresh = true;
			$this->msg = true;
		}

		if ($method == "poll_del" && $id) {
				
			$res = DB::queryMulti("
				".DBFunctions::deleteWith(
					DB::getTable('TABLE_POLL_SET_OPTIONS'), 'so', 'poll_set_id',
					"JOIN ".DB::getTable('TABLE_POLL_SETS')." s ON (s.id = so.poll_set_id)
					JOIN ".DB::getTable('TABLE_POLL_SET_LINK')." l ON (l.poll_set_id = s.id AND l.poll_id = ".(int)$id.")"
				)."
				;
				".DBFunctions::deleteWith(
					DB::getTable('TABLE_POLL_SETS'), 's', 'id',
					"JOIN ".DB::getTable('TABLE_POLL_SET_LINK')." l ON (l.poll_set_id = s.id AND l.poll_id = ".(int)$id.")"
				)."
				;
				DELETE FROM ".DB::getTable('TABLE_POLL_SET_LINK')."
					WHERE poll_id = ".(int)$id."
				;
				DELETE FROM ".DB::getTable('TABLE_POLLS')."
					WHERE id = ".(int)$id."
				;
			");
		
			$this->msg = true;
		}
	}	
	
	public static function getPolls($poll_id = 0) {
	
		$arr = [];

		$res = DB::query("SELECT *
				FROM ".DB::getTable('TABLE_POLLS')."
			".($poll_id ? "WHERE id = ".(int)$poll_id : "")."
			 ORDER BY id
		");
		
		while($arr_row = $res->fetchAssoc()) {
			
			$arr[] = $arr_row;
		}		

		return ((int)$poll_id ? current($arr) : $arr);
	}
	
	public static function findMainPoll($poll_id) {

		return pages::getClosestModule('poll', 0, 0, 0, $poll_id);
	}
}
