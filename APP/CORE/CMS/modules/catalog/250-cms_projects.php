<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_PROJECTS', DB::$database_home.'.def_projects');

class cms_projects extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_projects');
		static::$parent_label = getLabel('ttl_content');
	}
	
	public function contents() {
	
		$return = '<div class="section"><h1 id="x:cms_projects:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup project_add" value="add" /></h1>
		<div class="cms_projects">';
		
			$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_PROJECTS')." AS pr ORDER BY date DESC");
		
			if ($res->getRowCount() == 0) {
				$return .= '<section class="info">'.getLabel('msg_no_projects').'</section>';
			} else {
		
				$return .= '<table class="list">
					<thead>
						<tr>
							<th></th>
							<th>Name</th>
							<th>Description</th>
							<th>Link</th>
							<th></th>
						</tr>
					</thead>
					<tbody>';
						while($row = $res->fetchAssoc()) {	
								
							$return .= '<tr id="x:cms_projects:project_id-'.$row['id'].'">
								<td><img src="'.$row['img'].'"></td>
								<td>'.$row['name'].'</td>	
								<td>'.$row['description'].'</td>	
								<td><a href="'.$row['url'].'" target="_blank" title="'.$row['url'].'"><span class="icon">'.getIcon('link').'</span></a></td>
								<td><input type="button" class="data edit popup project_edit" value="edit" /><input type="button" class="data del msg project_del" value="del" /></td>
							</lr>';
						}
					$return .= '</tbody>
				</table>';
			}
		
		$return .= '</div></div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.cms_projects .list tr > :first-child { padding: 1px 4px 1px 4px; }
			.cms_projects .list tr > :first-child img { max-width: 100px; max-height: 30px; vertical-align: middle; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// POPUP
		
		if ($method == "project_edit" || $method == "project_add") {
		
			if(!empty($id)) {
				$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_PROJECTS')." AS pr WHERE id = '".DBFunctions::strEscape($id)."'");
				$row = $res->fetchAssoc();
				
				$mode = "project_update";
			} else {
				$mode = "project_insert";
			}
								
			$this->html = '<form id="frm-project" class="'.$mode.'">
				<table>
					<tr>
						<td>Name</td>
						<td><input type="text" name="name" value="'.strEscapeHTML($row['name']).'"></td>
					</tr>
					<tr>
						<td>Description</td>
						<td><textarea name="description">'.strEscapeHTML($row['description']).'</textarea></td>
					</tr>
					<tr>
						<td>Image</td>
						<td><input type="hidden" name="img" id="y:cms_media:media_popup-0" value="'.$row['img'].'" /><img class="select" src="'.$row['img'].'" /></td>
					</tr>
					<tr>
						<td>URL</td>
						<td><input type="text" name="url" value="'.$row['url'].'" /></td>
					</tr>
					<tr>
						<td>Datum</td>
						<td>'.cms_general::createDefineDate($row["date"]).'</td>
					</tr>
				</table>
				</form>';
			
			$this->validate = ['name' => 'required', 'img' => 'required'];
		}
				
		// QUERY
						
		if ($method == "project_insert") {
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_PROJECTS')."
				(name, description, img, url, date)
					VALUES
				('".DBFunctions::strEscape($_POST['name'])."', '".DBFunctions::strEscape($_POST['description'])."', '".DBFunctions::strEscape($_POST['img'])."', '".DBFunctions::strEscape($_POST['url'])."', '".date("Y-m-d H:i:s", strtotime($_POST['date'].' '.$_POST['date_t']))."')");
			
			$this->refresh = true;
			$this->msg = true;
		}
		
		if ($method == "project_update" && $id) {
			
			$res = DB::query("UPDATE ".DB::getTable('TABLE_PROJECTS')." SET
					name = '".DBFunctions::strEscape($_POST['name'])."',
					description = '".DBFunctions::strEscape($_POST['description'])."',
					img = '".DBFunctions::strEscape($_POST['img'])."',
					url = '".DBFunctions::strEscape($_POST['url'])."',
					date = '".date("Y-m-d H:i:s", strtotime($_POST['date'].' '.$_POST['date_t']))."'
				WHERE id = ".(int)$id."
			");
			
			$this->refresh = true;
			$this->msg = true;
		}
		
		if ($method == "project_del" && $id){
			
			$res = DB::query("DELETE FROM ".DB::getTable('TABLE_PROJECTS')." WHERE id = ".(int)$id."' LIMIT 1");
			
			$this->msg = true;
		}
	}
	
	public static function getProjects($project_id = 0, $limit = 0) {
	
		$arr = [];

		if ($project) {
			
			$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_PROJECTS')." AS pr WHERE id = ".(int)$project_id."");
						
			$arr = $res->fetchAssoc();			
		} else {
			
			$res = DB::query("SELECT *
					FROM ".DB::getTable('TABLE_PROJECTS')." AS pr
				ORDER BY date DESC
				".($limit ? "LIMIT ".$limit : "")
			);
								
			while ($row = $res->fetchAssoc()) {
				
				$arr[] = $row;
			}
		}		
		return $arr;
	}
}
