<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class intf_user_groups extends user_groups {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_user_groups');
		static::$parent_label = '';
	}
		
	public function contents() {
	
		$return .= '<div class="section"><h1 id="x:intf_user_groups:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup user_groups_add" value="add" /></h1>
		<div class="user_groups">';
		
			$res = DB::query("SELECT
				g.*,
				pg.name AS parent_name,
				COUNT(DISTINCT u.id) AS user_count,
				".DBFunctions::sqlImplode('DISTINCT '.DBFunctions::castAs('d.id', DBFunctions::CAST_TYPE_STRING), ',')." AS directories 
					FROM ".DB::getTable('TABLE_USER_GROUPS')." g
					LEFT JOIN ".DB::getTable('TABLE_USER_GROUPS')." pg ON (pg.id = g.parent_id)
					LEFT JOIN ".DB::getTable('TABLE_DIRECTORIES')." d ON (d.user_group_id = g.id)
					LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.group_id = g.id)
				GROUP BY g.id, pg.id
			");
		
			if ($res->getRowCount() == 0) {
				
				$return .= '<section class="info">'.getLabel('msg_no_user_groups').'</section>';
			} else {
			
				$arr_database_locations = self::userDatabaseLocations();
		
				$return .= '<table class="list">
					<thead>
						<tr>
							<th class="max"><span>'.getLabel('lbl_user_group').'</span></th>
							<th><span>'.getLabel('lbl_parent').'</span></th>
							<th class="limit"><span>'.getLabel('lbl_directory').'</span></th>
							<th class="limit"><span>'.getLabel('lbl_data').'</span></th>
							<th class="limit"><span>'.getLabel('lbl_user').' #</span></th>
							<th></th>
						</tr>
					</thead>
					<tbody>';
						while($row = $res->fetchAssoc()) {
							
							$arr_directories = [];
							
							foreach (explode(',', $row['directories']) as $id) {
								
								$arr_dir = directories::getDirectories($id);
								
								if ($arr_dir['path']) {
									$arr_directories[] = $arr_dir['path'];
								}
							}
							$return .= '<tr id="x:intf_user_groups:users_id-'.$row['id'].'">
								<td>'.$row['name'].'</td>
								<td>'.($row['parent_name'] ?: getLabel('lbl_none')).'</td>
								<td><span class="info"><span class="icon" title="'.(count($arr_directories) ? implode('<br />', $arr_directories) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.count($arr_directories).'</span></span></td>
								<td>';
								
								$arr_locations = self::userLocationSearch($arr_database_locations, $row['id']);
								$arr_combine = [];
								
								foreach ($arr_locations as $value) {
									$arr_combine[] = '['.strtoupper($value[0]).'] '.$value[1];
								}
								
								$return .= '<span class="info"><span class="icon" title="'.(count($arr_locations) ? implode('<br />', $arr_combine) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.count($arr_locations).'</span></span>';
									
								$return .= '</td>
								<td>'.$row['user_count'].'</td>
								<td><input type="button" class="data edit popup user_groups_edit" value="edit" /><input type="button" class="data del msg user_groups_del" value="del" /></td>
							</tr>';
						}
					$return .= '</tbody>
				</table>';
			}
		
		$return .= '</div></div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '#frm-users-group #linking input[name="virtual_name[]"] { width: 100px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('#frm-users-group', function(elm_scripter) {
		
			var func_update_table_links = function() {
			
				var from = elm_scripter.find('select[name=\"from_table[]\"]');
				var to = elm_scripter.find('select[name=\"to_table[]\"]');
				var arr = ['".DB::getTableName('TABLE_USERS')."', '".DB::getTableName('VIEW_USER_PARENT')."'];
				for (var i = 0; i < from.length-1; i++) { // length -1 to prevent getting last added table
					if ($(from.get(i)).val() == '".DB::getTableName('TABLE_USERS')."' && $(to.get(i)).val() != 0) {
						arr.push($(to.get(i)).val());
					}
				}
				for (var ii = 0; ii < from.length; ii++) {
					$(from.get(ii)).children('option').addClass('hide');
					for (var j = 0; j < arr.length; j++) {
						$(from.get(ii)).children('option[value=\"'+arr[j]+'\"]').removeClass('hide');
					}
				}
				elm_scripter.find('#linking tbody tr').each(function() {
					$(this).find('select, input').prop('disabled', true);
					$(this).children('td:last').children('.del, .add').addClass('hide');
					
					if (elm_scripter.find('#linking tbody tr').length == 1) {
						$(this).find('select:gt(1), input').prop('disabled', false);
						$(this).children('td:last').children('.add').removeClass('hide');
					} else if ($(this).index() == elm_scripter.find('#linking tbody tr').length-1) {
						$(this).find('select, input').prop('disabled', false);
						$(this).children('td:last').children('.add, .del').removeClass('hide');
					}
				});
			};
		
			elm_scripter.on('ajaxloaded scripter', function() {
				func_update_table_links();
			}).on('change', 'select[name=\"from_table[]\"]', function() {
				$(this).quickCommand($(this).parent('td').next('td'));
			}).on('change', 'select[name=\"to_table[]\"]', function() {
				$(this).quickCommand([$(this).parent('td').next('td'), $(this).parent('td').next('td').next('td').next('td')]);
			}).on('click', '.add', function() {
				elm_scripter.find('#linking tbody tr:first').clone().appendTo($(this).closest('table'));
				var new_tr = $(this).closest('tr').next('tr');
				new_tr.find('select[name=\"from_table[]\"]').trigger('change');
				new_tr.find('select[name=\"to_table[]\"]').trigger('change');	
				
				func_update_table_links();
			}).on('click', '.del', function() {
				$(this).closest('tr').remove();
				func_update_table_links();
			}).on('ajaxsubmit', function() {
				elm_scripter.find('#linking').find('select, input').prop('disabled', false);
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// POPUP

		if ($method == "user_groups_edit" || $method == "user_groups_add") {
			
			if($id > 0) {
				
				$res = DB::query("SELECT *
						FROM ".DB::getTable('TABLE_USER_GROUPS')."
					WHERE id = ".(int)$id
				);
				
				$row = $res->fetchAssoc();
				
				$mode = 'user_groups_update';
			} else {
				$mode = 'user_groups_insert';
			}
			
			$arr_link_tables = self::getLinkTables();
						
			$this->html = '<form id="frm-users-group" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>Name</label>
						<div><input type="text" name="name" value="'.htmlspecialchars($row['name']).'"></div>
					</li>
					<li>
						<label>'.getLabel('lbl_parent').'</label>
						<div><select name="parent_id">'.cms_general::createDropdown(user_groups::getUserGroups(), $row['parent_id'], true).'</select></div>
					</li>
					<li>
						<label>Linking</label>
						<div>
							<table id="linking">
								<thead>
									<tr><th>From</th><th></th><th></th><th>To</th><th></th><th>Virtual</th><th>Get</th><th></th></tr>
								</thead>
								<tbody>';
								
									if ($mode == "user_groups_update") {
										
										$res_links = DB::query("SELECT *
												FROM ".DB::getTable('TABLE_USER_GROUP_LINK')."
											WHERE group_id = ".(int)$id." AND to_table != '".DB::getTableName('VIEW_USER_PARENT')."'
											ORDER BY sort
										");
										
										while ($arr_row_links = $res_links->fetchAssoc()) {
										
											$this->html .= '<tr><td><select name="from_table[]" id="y:intf_user_groups:get_from_columns-0">'.self::createLinkDropdown($arr_link_tables, $arr_row_links['from_table']).'</select></td>
												<td><select name="from_column[]">'.self::createLinkDropdown(self::getLinkColumns($arr_row_links['from_table']), $arr_row_links['from_column']).'</select></td>
												<td><span class="icon">'.getIcon('leftright-arrow-right').'</span></td>
												<td><select name="to_table[]" id="y:intf_user_groups:get_to_columns-1">'.self::createLinkDropdown($arr_link_tables, $arr_row_links['to_table']).'</select></td>
												<td><select name="to_column[]">'.self::createLinkDropdown(self::getLinkColumns($arr_row_links['to_table']), $arr_row_links['to_column']).'</select></td>
												<td><input type="text" name="virtual_name[]" value="'.$arr_row_links['virtual_name'].'" /></td>
												<td><select name="get_column[]">'.self::createLinkDropdown(self::getLinkColumns($arr_row_links['to_table']), $arr_row_links['get_column']).'</select></td>
												<td><input type="button" class="data add" value="add" /><input type="button" class="data del" value="del" /></td>
											</tr>';
											
											$existing = true;
										}
									} 
									if (!$existing) {
										
										$this->html .= '<tr><td><select name="from_table[]" id="y:intf_user_groups:get_from_columns-0">'.self::createLinkDropdown($arr_link_tables, DB::getTableName('TABLE_USERS')).'</select></td>
											<td><select name="from_column[]">'.self::createLinkDropdown(self::getLinkColumns(DB::getTableName('TABLE_USERS')), 'id').'</select></td>
											<td><span class="icon">'.getIcon('leftright-arrow-right').'</span></td>
											<td><select name="to_table[]" id="y:intf_user_groups:get_to_columns-1">'.self::createLinkDropdown($arr_link_tables, '0').'</select></td>
											<td><select name="to_column[]">'.self::createLinkDropdown([], '0').'</select></td>
											<td><input type="text" name="virtual_name[]" value="" /></td>
											<td><select name="get_column[]">'.self::createLinkDropdown([], '0').'</select></td>
											<td><input type="button" class="data add" value="add" /><input type="button" class="data del" value="del" /></td>
										</tr>';
									}
								$this->html .= '
								</tbody>
							</table>
						</div>
					</li>	
				</ul></fieldset>
			</form>';
			
			$this->validate = ['name' => 'required'];
		}
		
		// POPUP INTERACT
							
		if ($method == "get_from_columns") {
		
			$this->html = '<select name="from_column[]">'.self::createLinkDropdown(self::getLinkColumns($value), '0').'</select>';
		}
		
		if ($method == "get_to_columns") {
		
			$select = self::createLinkDropdown(self::getLinkColumns($value), '0');
			$this->html = ['<select name="to_column[]">'.$select.'</select>', '<select name="get_column[]">'.$select.'</select>'];
		}
				
		// QUERY
		
		if ($method == "user_groups_del" && (int)$id) {
			
			$res = DB::query("DELETE
					FROM ".DB::getTable('TABLE_USER_GROUPS')."
				WHERE id = ".(int)$id."
				LIMIT 1
			");
			
			$this->msg = true;
		}
		
		if ($method == "user_groups_update" && (int)$id) {

			$res = DB::query("UPDATE ".DB::getTable('TABLE_USER_GROUPS')." SET
					name = '".DBFunctions::strEscape($_POST['name'])."',
					parent_id = ".(int)$_POST['parent_id']."
				WHERE id = ".(int)$id);

			self::handleUserGroupLinks($id);
													
			$this->refresh = true;
			$this->msg = true;
		}
		
		if ($method == "user_groups_insert") {
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_USER_GROUPS')."
				(name, parent_id)
					VALUES
				('".DBFunctions::strEscape($_POST['name'])."', ".(int)$_POST['parent_id'].")
			");
													
			$id = DB::lastInsertID();
			
			self::handleUserGroupLinks($id);

			$this->refresh = true;
			$this->msg = true;
		}
	}
	
	protected function handleUserGroupLinks($user_group) {
	
		$res = DB::query("DELETE
				FROM ".DB::getTable('TABLE_USER_GROUP_LINK')."
			WHERE group_id = ".(int)$user_group."
		");
				
		$sort = 0;
		
		if ((int)$_POST['parent_id']) {
		
			self::insertUserGroupLink(['group_id' => $user_group,
				'from_table' => DB::getTableName('TABLE_USERS'),
				'from_column' => 'parent_id',
				'to_table' => DB::getTableName('VIEW_USER_PARENT'),
				'to_column' => 'id',
				'get_column' => 'parent_name',
				'view' => true
			], $sort);
			
			$sort++;
		}
														
		foreach ($_POST['from_table'] as $key => $value) {
		
			if ($_POST['from_table'][$key] && $_POST['from_column'][$key] && $_POST['to_table'][$key] && $_POST['to_column'][$key]) {
			
				// If source is INT and has a shared PRIMARY KEY, the source is a multi value table, use multiselect
									
				$res_source = DB::query(
					(DB::ENGINE_IS_POSTGRESQL ? "SELECT
						TRUE AS is_primary,
						(SELECT
							COUNT(*)
								FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu2
							WHERE kcu2.TABLE_CATALOG = kcu.TABLE_CATALOG
								AND kcu2.TABLE_SCHEMA = kcu.TABLE_SCHEMA
								AND kcu2.TABLE_NAME = kcu.TABLE_NAME
								AND kcu2.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
						) AS count_primary
							FROM INFORMATION_SCHEMA.TABLES t
							JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc ON (
								tc.TABLE_CATALOG = t.TABLE_CATALOG
								AND tc.TABLE_SCHEMA = t.TABLE_SCHEMA
								AND tc.TABLE_NAME = t.TABLE_NAME
								AND tc.constraint_type = 'PRIMARY KEY'
							)
							JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu ON (
								kcu.TABLE_CATALOG = tc.TABLE_CATALOG
								AND kcu.TABLE_SCHEMA = tc.TABLE_SCHEMA
								AND kcu.TABLE_NAME = tc.TABLE_NAME
								AND kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
							)
							JOIN INFORMATION_SCHEMA.COLUMNS c ON (
								c.TABLE_CATALOG = kcu.TABLE_CATALOG
								AND c.TABLE_SCHEMA = kcu.TABLE_SCHEMA
								AND c.TABLE_NAME = kcu.TABLE_NAME
								AND c.COLUMN_NAME = kcu.COLUMN_NAME
								AND c.COLUMN_NAME = '".DBFunctions::strEscape($_POST['from_column'][$key])."'
								AND (c.DATA_TYPE = 'integer' OR c.DATA_TYPE = 'bigint')
							)
						WHERE (t.TABLE_SCHEMA = '".DB::$database_home."' OR t.TABLE_SCHEMA = '".DB::$database_cms."')
							AND CONCAT(t.TABLE_SCHEMA, '.', t.TABLE_NAME) = '".DBFunctions::strEscape($_POST['from_table'][$key])."'
					" : "")
					.(DB::ENGINE_IS_MYSQL ? "SELECT
						TRUE AS is_primary,
						(SELECT
							COUNT(*)
								FROM INFORMATION_SCHEMA.COLUMNS c2
							WHERE c2.TABLE_SCHEMA = c.TABLE_SCHEMA
							AND c2.TABLE_NAME = c.TABLE_NAME
							AND c2.COLUMN_KEY = 'PRI'
						) AS count_primary
							FROM INFORMATION_SCHEMA.COLUMNS c
						WHERE CONCAT(c.TABLE_SCHEMA, '.', c.TABLE_NAME) = '".DBFunctions::strEscape($_POST['from_table'][$key])."'
							AND (c.TABLE_SCHEMA = '".DB::$database_home."' OR c.TABLE_SCHEMA = '".DB::$database_cms."')
							AND c.COLUMN_NAME = '".DBFunctions::strEscape($_POST['from_column'][$key])."'
							AND (c.DATA_TYPE = 'int' OR c.DATA_TYPE = 'bigint')
							AND c.COLUMN_KEY = 'PRI'
					" : "")
				);
				
				$row_source = $res_source->fetchAssoc();

				$is_multi_source = (DBFunctions::unescapeAs($row_source['is_primary'], DBFunctions::TYPE_BOOLEAN) && $row_source['count_primary'] > 1 ? true : false);
				
				// If target is INT and has a shared PRIMARY KEY, the target is a linking table, use multiselect
				// If target is INT and the table has PRIMARY KEY with AUTO_INCREMENT, the target is a multi value table, use multiview
				
				$res_target = DB::query(
					(DB::ENGINE_IS_POSTGRESQL ? "SELECT
						TRUE AS is_primary,
						(SELECT
							".DBFunctions::sqlImplode('kcu2.COLUMN_NAME', ',')."
								FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu2
							WHERE kcu2.TABLE_CATALOG = kcu.TABLE_CATALOG
								AND kcu2.TABLE_SCHEMA = kcu.TABLE_SCHEMA
								AND kcu2.TABLE_NAME = kcu.TABLE_NAME
								AND kcu2.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
						) AS columns_primary,
						(SELECT
							TRUE
								FROM INFORMATION_SCHEMA.COLUMNS c2
							WHERE c2.TABLE_CATALOG = c.TABLE_CATALOG
								AND c2.TABLE_SCHEMA = c.TABLE_SCHEMA
								AND c2.TABLE_NAME = c.TABLE_NAME
								AND c2.COLUMN_NAME = c.COLUMN_NAME
								AND c2.COLUMN_NAME LIKE 'nextval(%'
						) AS is_auto_increment
							FROM INFORMATION_SCHEMA.TABLES t
							JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc ON (
								tc.TABLE_CATALOG = t.TABLE_CATALOG
								AND tc.TABLE_SCHEMA = t.TABLE_SCHEMA
								AND tc.TABLE_NAME = t.TABLE_NAME
								AND tc.constraint_type = 'PRIMARY KEY'
							)
							JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu ON (
								kcu.TABLE_CATALOG = tc.TABLE_CATALOG
								AND kcu.TABLE_SCHEMA = tc.TABLE_SCHEMA
								AND kcu.TABLE_NAME = tc.TABLE_NAME
								AND kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
							)
							JOIN INFORMATION_SCHEMA.COLUMNS c ON (
								c.TABLE_CATALOG = kcu.TABLE_CATALOG
								AND c.TABLE_SCHEMA = kcu.TABLE_SCHEMA
								AND c.TABLE_NAME = kcu.TABLE_NAME
								AND c.COLUMN_NAME = kcu.COLUMN_NAME
								AND c.COLUMN_NAME = '".DBFunctions::strEscape($_POST['to_column'][$key])."'
								AND (c.DATA_TYPE = 'integer' OR c.DATA_TYPE = 'bigint')
							)
						WHERE (t.TABLE_SCHEMA = '".DB::$database_home."' OR t.TABLE_SCHEMA = '".DB::$database_cms."')
							AND CONCAT(t.TABLE_SCHEMA, '.', t.TABLE_NAME) = '".DBFunctions::strEscape($_POST['to_table'][$key])."'
					" : "")
					.(DB::ENGINE_IS_MYSQL ? "SELECT
						TRUE AS is_primary,
						(SELECT
							".DBFunctions::sqlImplode('c2.COLUMN_NAME', ',')."
								FROM INFORMATION_SCHEMA.COLUMNS c2
							WHERE c2.TABLE_SCHEMA = c.TABLE_SCHEMA
							AND c2.TABLE_NAME = c.TABLE_NAME
							AND c2.COLUMN_KEY = 'PRI'
						) AS columns_primary,
						(SELECT
							TRUE
								FROM INFORMATION_SCHEMA.COLUMNS c2
							WHERE c2.TABLE_SCHEMA = c.TABLE_SCHEMA
							AND c2.TABLE_NAME = c.TABLE_NAME
							AND c2.COLUMN_NAME = c.COLUMN_NAME
							AND c2.EXTRA = 'auto_increment'
						) AS is_auto_increment
							FROM INFORMATION_SCHEMA.COLUMNS c
						WHERE CONCAT(c.TABLE_SCHEMA, '.', c.TABLE_NAME) = '".DBFunctions::strEscape($_POST['to_table'][$key])."'
							AND (c.TABLE_SCHEMA = '".DB::$database_home."' OR c.TABLE_SCHEMA = '".DB::$database_cms."')
							AND c.COLUMN_NAME = '".DBFunctions::strEscape($_POST['to_column'][$key])."'
							AND (c.DATA_TYPE = 'int' OR c.DATA_TYPE = 'bigint')
							AND c.COLUMN_KEY = 'PRI'
					" : "")
				);
								
				$row_target = $res_target->fetchAssoc();
				
				$arr_columns_primary = explode(',', $row_target['columns_primary']);
				
				foreach ($arr_columns_primary as $column) {
					
					if ($column == $_POST['to_column'][$key]) {
						continue;
					}
					
					$get_column = $column;
					break;
				}
				
				$is_multi_target = (DBFunctions::unescapeAs($row_target['is_primary'], DBFunctions::TYPE_BOOLEAN) && count($arr_columns_primary) > 1 ? true : false);
				$is_multi_target_view = ($is_multi_target && DBFunctions::unescapeAs($row_target['is_auto_increment'], DBFunctions::TYPE_BOOLEAN) ? true : false);
				
				self::insertUserGroupLink(['group_id' => $user_group,
					'from_table' => $_POST['from_table'][$key],
					'from_column' => $_POST['from_column'][$key],
					'to_table' => $_POST['to_table'][$key],
					'to_column' => $_POST['to_column'][$key],
					'get_column' => ($is_multi_target ? $get_column : $_POST['get_column'][$key]),
					'virtual_name' => $_POST['virtual_name'][$key],
					'multi_source' => (int)($is_multi_source),
					'multi_target' => (int)($is_multi_target || $is_multi_target_view),
					'view' => (int)$is_multi_target_view
				], $sort);

				$sort++;
			} else {
				break;
			}
		}
	}
	
	protected function insertUserGroupLink($arr_link, $sort) {
		
		$res = DB::query("INSERT INTO ".DB::getTable('TABLE_USER_GROUP_LINK')."
			(group_id, from_table, from_column, to_table, to_column, get_column, virtual_name, multi_source, multi_target, view, sort)
				VALUES
			(
				".(int)$arr_link['group_id'].",
				'".DBFunctions::strEscape($arr_link['from_table'])."',
				'".DBFunctions::strEscape($arr_link['from_column'])."',
				'".DBFunctions::strEscape($arr_link['to_table'])."',
				'".DBFunctions::strEscape($arr_link['to_column'])."',
				'".DBFunctions::strEscape($arr_link['get_column'])."',
				'".DBFunctions::strEscape($arr_link['virtual_name'])."',
				".DBFunctions::escapeAs($arr_link['multi_source'], DBFunctions::TYPE_BOOLEAN).",
				".DBFunctions::escapeAs($arr_link['multi_target'], DBFunctions::TYPE_BOOLEAN).",
				".DBFunctions::escapeAs($arr_link['view'], DBFunctions::TYPE_BOOLEAN).",
				".$sort."
			)
		");
	}
}
