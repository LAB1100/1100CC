<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_CUSTOM_CONTENT', DB::$database_home.'.def_custom_content');
DB::setTable('TABLE_CUSTOM_CONTENT_TAGS', DB::$database_home.'.def_custom_content_tags');

class cms_custom_content extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_custom_content');
		static::$parent_label = getLabel('ttl_site');
	}
	
	public static function mediaLocations() {
		return [
			'TABLE_CUSTOM_CONTENT' => [
				'body',
				'description'
			]
		];
	}
		
	public function contents() {
		
		$return = '<div class="section"><h1 id="x:cms_custom_content:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup add" value="add" /></h1>
		<div class="custom_content">
		
			<table class="display" id="d:cms_custom_content:data-0">
				<thead> 
					<tr>			
						<th class="max" data-sort="asc-0"><span>'.getLabel('lbl_name').'</span></th>
						<th class="limit"><span>'.getLabel('lbl_path').'</span></th>
						<th class="limit"><span>'.getLabel('lbl_page').' - '.getLabel('lbl_internal_tags').'</span></th>
						<th class="disable-sort"></th>
					</tr> 
				</thead>
				<tbody>
					<tr>
						<td colspan="4" class="empty">'.getLabel('msg_loading_server_data').'</td>
					</tr>
				</tbody>
			</table>
			
		</div></div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '#frm-custom_content textarea[name=description] { width: 500px; height: 80px; }
		#frm-custom_content li.content > label { vertical-align: middle; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('#frm-custom_content', function(elm_scripter) {
			
			var elm_editor = false;

			elm_scripter.on('update change', '[name=style]', function() {

				var value = $(this).val();
				value = (value == 'default' ? 'body' : value);
				
				if (elm_editor) {
					elm_editor.dataset.style = value;
				}
			}).on('editorloaded', function(e) {
				
				elm_editor = e.detail.source;
				elm_scripter.find('[name=style]:checked').trigger('update');
			})
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "edit" || $method == "add") {
		
			if ($id) {
				
				$arr_row = self::getCustomContent($id);
				
				$arr_tags = cms_general::getObjectTags(DB::getTable('TABLE_CUSTOM_CONTENT_TAGS'), 'custom_content_id', $id);
				
				$mode = "update";
			} else {
				
				$mode = "insert";
				
				$arr_tags = [];
			}
														
			$this->html = '<form id="frm-custom_content" data-method="'.$mode.'" data-lock="1">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($arr_row['name']).'"></div>
					</li>
					<li class="content">
						<label>'.getLabel('lbl_body').'</label>
						<div>'.cms_general::editBody($arr_row['body']).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_style').'</label>
						<div>'.cms_general::createSelectorRadio([['id' => 'none', 'name' => getLabel('lbl_none')], ['id' => 'default', 'name' => getLabel('lbl_default')]], 'style', ($arr_row['style'] ?: 'default')).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_script').'</label>
						<div><div class="hide-edit'.(!$arr_row['script'] ? ' hide' : '').'"><textarea name="script">'.$arr_row['script'].'</textarea></div><span class="icon" title="'.getLabel('inf_edit_script').'">'.getIcon('script').'</span></div>
					</li>
					<li>
						<label>'.getLabel('lbl_description').'</label>
						<div><textarea name="description">'.strEscapeHTML($arr_row['description']).'</textarea></div>
					</li>
					<li>
						<label>'.getLabel('lbl_tags').'</label>
						<div>'.cms_general::createSelectTags($arr_tags, '', false).'</div>
					</li>
				</ul></fieldset>
			</form>';
			
			$this->validate = ['name' => 'required'];
		}
		
		// POPUP INTERACT
		
		// DATATABLE
		
		if ($method == "data") {
			
			$sql_column_tags = "(SELECT
				".DBFunctions::sqlImplode('DISTINCT t.name')."
					FROM ".DB::getTable('TABLE_PAGE_MODULES')." m
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
					LEFT JOIN ".DB::getTable('TABLE_PAGE_INTERNAL_TAGS')." ot ON (ot.page_id = p.id)
					LEFT JOIN ".DB::getTable('TABLE_INTERNAL_TAGS')." t ON (t.id = ot.tag_id)
				WHERE m.var != '' AND ".DBFunctions::castAs('m.var', DBFunctions::CAST_TYPE_INTEGER)." = cc.id AND m.module = 'custom_content'
			)";
			
			$arr_sql_columns = ['cc.name', 'COUNT(d.id)', $sql_column_tags];
			$arr_sql_columns_search = ['cc.name', '', $sql_column_tags];
			$arr_sql_columns_as = ['cc.name', '', $sql_column_tags.' AS tags', 'cc.id', DBFunctions::sqlImplode(DBFunctions::castAs('d.id', DBFunctions::CAST_TYPE_STRING), ',', 'ORDER BY p.id').' AS directories', DBFunctions::sqlImplode('p.name', ',', 'ORDER BY p.id').' AS pages'];

			$sql_table = DB::getTable('TABLE_CUSTOM_CONTENT').' cc';

			$sql_index = 'cc.id';
			
			$sql_body = $sql_table."
					LEFT JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.var != '' AND ".DBFunctions::castAs('m.var', DBFunctions::CAST_TYPE_INTEGER)." = cc.id AND m.module = 'custom_content')
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
					LEFT JOIN ".DB::getTable('TABLE_DIRECTORIES')." d ON (d.id = p.directory_id)
			";
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, $sql_body);
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{

				$arr_data = [];
				$arr_data['id'] = 'x:cms_custom_content:id-'.$arr_row['id'];
				$arr_data[] = $arr_row['name'];
				
				$arr_pages = explode(',', $arr_row['pages']);
				$directories = array_filter(explode(',', $arr_row['directories']));
				$arr_paths = [];
				for ($i = 0; $i < count($directories); $i++) {
					
					$dir = directories::getDirectories($directories[$i]);
				
					if ($dir['id']) {
						$arr_paths[] = $dir['path'].' / '.$arr_pages[$i];
					}
				}
				
				$arr_paths = array_unique($arr_paths);
				$arr_data[] = '<span class="info"><span class="icon" title="'.(count($arr_paths) ? implode('<br />', $arr_paths) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.count($arr_paths).'</span></span>';
				$arr_data[] = $arr_row['tags'];
				$arr_data[] = '<input type="button" class="data edit popup edit" value="edit" /><input type="button" class="data del msg del" value="del" />';
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
							
		// QUERY
	
		if ($method == "insert") {
						
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_CUSTOM_CONTENT')."
				(name, body, style, script, description)
					VALUES
				('".DBFunctions::strEscape($_POST['name'])."', '".DBFunctions::strEscape($_POST['body'])."', '".DBFunctions::strEscape($_POST['style'])."', '".DBFunctions::strEscape($_POST['script'])."', '".DBFunctions::strEscape($_POST['description'])."')
			");

			$new_id = DB::lastInsertID();
			
			cms_general::handleTags(DB::getTable('TABLE_CUSTOM_CONTENT_TAGS'), "custom_content_id", $new_id, $_POST['tags']);
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "update" && (int)$id) {
						
			$res = DB::query("UPDATE ".DB::getTable('TABLE_CUSTOM_CONTENT')."
				SET
					name = '".DBFunctions::strEscape($_POST['name'])."',
					body = '".DBFunctions::strEscape($_POST['body'])."',
					style = '".DBFunctions::strEscape($_POST['style'])."',
					script = '".DBFunctions::strEscape($_POST['script'])."',
					description = '".DBFunctions::strEscape($_POST['description'])."'
				WHERE id = ".(int)$id."
			");
			
			cms_general::handleTags(DB::getTable('TABLE_CUSTOM_CONTENT_TAGS'), 'custom_content_id', $id, $_POST['tags']);
			
			$this->refresh_table = true;
			$this->msg = true;
		}
			
		if ($method == "del" && (int)$id) {
		
			$res = DB::query("DELETE FROM ".DB::getTable('TABLE_CUSTOM_CONTENT')." WHERE id = ".(int)$id."");
			
			$this->msg = true;
		}
	}
				
	public static function getCustomContent($custom_content_id = 0) {
	
		$arr = [];

		$res = DB::query("SELECT
			cc.*,
			".DBFunctions::sqlImplode('t.name')." AS tags
				FROM ".DB::getTable('TABLE_CUSTOM_CONTENT')." cc
				LEFT JOIN ".DB::getTable('TABLE_CUSTOM_CONTENT_TAGS')." ot ON (ot.custom_content_id = cc.id)
				LEFT JOIN ".DB::getTable('TABLE_TAGS')." t ON (t.id = ot.tag_id)
			".($custom_content_id ? "WHERE cc.id = ".(int)$custom_content_id : "")."
			GROUP BY cc.id
			ORDER BY cc.name
		");		

		while ($arr_row = $res->fetchAssoc()) {
			
			$arr[$arr_row['id']] = $arr_row;
		}
		
		return ($custom_content_id ? current($arr) : $arr);
	}
}
