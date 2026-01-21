<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_DOCUMENTATIONS', DB::$database_home.'.def_documentations');

class cms_documentations extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_documentations');
		static::$parent_label = getLabel('ttl_site');
	}
	
	public function contents() {
		
		$return = '<div class="section"><h1 id="x:cms_documentations:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup documentation_add" value="add" /></h1>
		<div class="documentations">';
			
			$res = DB::query("SELECT
				d.*,
				(SELECT
					COUNT(*)
						FROM ".DB::getTable('TABLE_DOCUMENTATION_SECTIONS')." ds
					WHERE ds.documentation_id = d.id 
				) AS documentation_section_count,
				(SELECT
					".DBFunctions::group2String('ds.title', '<br />', 'ORDER BY ds.date_updated DESC')."
						FROM ".DB::getTable('TABLE_DOCUMENTATION_SECTIONS')." ds
					WHERE ds.documentation_id = d.id
				) AS documentation_sections,
				".DBFunctions::group2String(DBFunctions::castAs('dir.id', DBFunctions::CAST_TYPE_STRING), ',', 'ORDER BY p.id')." AS directories,
				".DBFunctions::group2String('p.name', ',', 'ORDER BY p.id')." AS pages
					FROM ".DB::getTable('TABLE_DOCUMENTATIONS')." d
					LEFT JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.var LIKE CONCAT('%\"id\":\"', d.id, '\"%') AND m.module = 'documentation')
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
					LEFT JOIN ".DB::getTable('TABLE_DIRECTORIES')." dir ON (dir.id = p.directory_id)
				GROUP BY d.id
			");
		
			if ($res->getRowCount() == 0) {
				
				Labels::setVariable('name', getLabel('lbl_documentations'));
				
				$return .= '<section class="info">'.getLabel('msg_no', 'L', true).'</section>';
			} else {
		
				$return .= '<table class="list">
					<thead>
						<tr>
							<th>'.getLabel('lbl_name').'</th>
							<th>'.getLabel('lbl_path').'</th>
							<th>'.getLabel('lbl_documentation_sections').'</th>
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
							
							$return .= '<tr id="x:cms_documentations:documentation_id-'.$arr_row['id'].'">
								<td>'.$arr_row['name'].'</td>
								<td><span class="info"><span class="icon" title="'.($arr_paths ? implode('<br />', $arr_paths) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.count($arr_paths).'</span></span></td>
								<td><span class="info"><span class="icon" title="'.(strEscapeHTML($arr_row['documentation_sections']) ?: getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)$arr_row['documentation_section_count'].'</span></span></td>
								<td><input type="button" class="data edit popup documentation_edit" value="edit" /><input type="button" class="data del msg documentation_del" value="del" /></td>
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
		
		if ($method == "documentation_edit" || $method == "documentation_add") {
		
			if ((int)$id) {
				
				$res = DB::query("SELECT b.*
						FROM ".DB::getTable('TABLE_DOCUMENTATIONS')." b
					WHERE b.id = ".(int)$id."");
									
				$arr = $res->fetchAssoc();
				
				$mode = "documentation_update";
			} else {
				$mode = "documentation_insert";
			}
														
			$this->html = '<form id="frm-documentation" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($arr['name']).'"></div>
					</li>		
					<li>
						<label>'.getLabel('lbl_description').'</label>
						<div>'.cms_general::editBody($arr['description'], 'description').'</div>
					</li>
				</ul></fieldset>
			</form>';
			
			$this->validate = ['name' => 'required'];
		}
		
		// POPUP INTERACT
							
		// QUERY
	
		if ($method == "documentation_insert") {
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_DOCUMENTATIONS')."
				(name, description)
					VALUES
				('".DBFunctions::strEscape($_POST['name'])."', '".DBFunctions::strEscape($_POST['description'])."')
			");
						
			$this->refresh = true;
			$this->message = true;
		}
		
		if ($method == "documentation_update" && (int)$id) {
					
			$res = DB::query("UPDATE ".DB::getTable('TABLE_DOCUMENTATIONS')." SET
					name = '".DBFunctions::strEscape($_POST['name'])."',
					description = '".DBFunctions::strEscape($_POST['description'])."'
				WHERE id = ".(int)$id."
			");
						
			$this->refresh = true;
			$this->message = true;
		}
			
		if ($method == "documentation_del" && (int)$id) {
		
			$res = DB::queryMulti("
				DELETE FROM ".DB::getTable('TABLE_DOCUMENTATIONS')."
					WHERE id = ".(int)$id.";
				
				DELETE FROM ".DB::getTable('TABLE_DOCUMENTATION_SECTIONS')."
					WHERE documentation_id = ".(int)$id.";
			");
				
			$this->message = true;
		}
	}	

	public static function getDocumentations($documentation_id = 0) {
	
		$arr = [];

		if ($documentation_id) {
			
			$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_DOCUMENTATIONS')." WHERE id = ".(int)$documentation_id."");
			
			$arr = ($res->fetchAssoc() ?: []);		
		} else {
			
			$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_DOCUMENTATIONS')." ORDER BY id");
			
			while ($row = $res->fetchAssoc()) {
				$arr[] = $row;
			}
		}
			
		return $arr;
	}
	
	public static function findMainDocumentation($documentation_id) {

		return pages::getClosestModule('documentation', 0, 0, 0, $documentation_id, 'id');
	}
}
