<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class intf_user_management extends user_management {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_user_management');
		static::$parent_label = '';
	}
	
	public function contents() {
		
		$return .= '<div class="section"><h1>'.self::$label.'</h1>
		<div class="user_management">';
					
			$res = DB::query("SELECT *
					FROM ".DB::getTable('TABLE_USER_GROUPS')."
			");
		
			if ($res->getRowCount() == 0) {
				
				$return .= '<section class="info">'.getLabel('msg_no_users').'</section>';
			} else {
			
				$return .= '<div id="tabs-user-groups">
				<ul>';
				
				while ($row = $res->fetchAssoc()) {
					$return .= '<li id="x:intf_user_management:new-'.$row['id'].'"><a href="#tab-user-groups-'.$row['id'].'">'.$row['name'].'</a><span><input type="button" class="data popup add" value="add" /></span></li>';
				}
				
				$return .= '</ul>';
		
				$res->seekRow(0);
				
				while ($row = $res->fetchAssoc()) {
				
					$return .= '<div id="tab-user-groups-'.$row['id'].'">
			
					<table class="display" id="d:intf_user_management:users_data-'.$row['id'].'">
						<thead> 
							<tr>';
							
								$arr_columns = user_groups::getUserGroupColumns($row['id']);
								
								foreach ($arr_columns as $key => $row) {
								
									if ($key == DB::getTableName('TABLE_USERS').'.enabled') {
										$return .= '<th title="'.getLabel('lbl_enabled').'"><span>E</span></th>';
									} else if ($key == DB::getTableName('TABLE_USERS').'.name') {
										$return .= '<th class="max"><span>'.getLabel('lbl_'.$row['COLUMN_NAME']).'</span></th>';
									} else {
										$return .= '<th class="limit"><span>'.getLabel('lbl_'.($row['VIRTUAL_NAME'] ? $row['VIRTUAL_NAME'] : $row['COLUMN_NAME'])).'</span></th>';
									}
								}
								$return .= '<th class="disable-sort menu" id="x:intf_user_management:user_id-0" title="'.getLabel('lbl_multi_select').'">'
									.'<input type="button" class="data msg del" value="del" />'
									.'<input type="checkbox" class="multi all" value="" />'
								.'</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td colspan="'.(count($arr_columns)+1).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
							</tr>
						</tbody>
					</table>
					
					</div>';
				}
			}
							
		$return .= '</div></div></div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '#frm-user_management input[name=password] { width: 130px; }
				#frm-user_management [name=password] + .icon { margin-left: 4px; cursor: pointer; }
				#frm-user_management input[name=street] { width: 115px; }
				#frm-user_management input[name=streetnr] { margin-left: 5px; width: 30px; }
				#frm-user_management .icon[id*=popup_user_clearance] { cursor: pointer; }
				#frm-user_clearance .object { text-align: left; margin: 5px; min-width: 140px; padding: 5px 10px; }
				#frm-user_clearance .node { text-align: center; display: inline-block; vertical-align: top; }
				#frm-user_clearance .object > fieldset { margin: 0px; }
				#frm-user_clearance .object ul.select > li > label { border-left: 4px solid #ff0000; padding-left: 4px; }
				#frm-user_clearance .object ul.select > li > label.allow { border-color: #00ff12; }
				';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('#frm-user_management', function(elm_scripter) {
		
			elm_scripter.on('click', '[name=password] + .icon', function() {
				$(this).quickCommand(elm_scripter.find('input[name=password]'));
			}).on('click', '[id^=y\\\:intf_user_management\\\:popup_user_clearance-]', function() {
				var elm_target = $(this).prev('input');
				COMMANDS.setData(this, {clearance: elm_target.val()});
				COMMANDS.setTarget(this, elm_target);
				$(this).popupCommand();
			})
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "edit" || $method == "add") {
		
			if ($method == "edit" && (int)$id) {
			
				$arr_row = user_groups::getUserData($id, true);
			
				$user_group_id = $arr_row[DB::getTableName('TABLE_USERS')]['group_id'];

				$mode = "update";
			} else if ($method == "add" && (int)$id) {
			
				$user_group_id = $id;
			
				$mode = "insert";
			} else {
				error('missing ID');
			}

			$arr_user_group = user_groups::getUserGroups($user_group_id);
			$arr_columns = user_groups::getUserGroupColumns($user_group_id);
			
			$this->html = '<form id="frm-user_management" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label></label>
						<div>'.cms_general::createSelectorRadio([['id' => '1', 'name' => getLabel('lbl_active')], ['id' => '0', 'name' => getLabel('lbl_inactive')]], 'enabled', ($mode == 'insert' || $arr_row[DB::getTableName('TABLE_USERS')]['enabled'])).'</div>
					</li>
				</ul>
				<hr />
				<ul>';
					if ($arr_row[DB::getTableName('TABLE_USER_PAGE_CLEARANCE')] !== null) {
						$this->html .= '<li>
							<label>'.getLabel('lbl_clearance').'</label>
							<div><input type="hidden" name="col['.DB::getTableName('TABLE_USER_PAGE_CLEARANCE').'.page_id]" value="'.htmlspecialchars(json_encode(array_keys($arr_row[DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]))).'" /><span id="y:intf_user_management:popup_user_clearance-'.$user_group_id.'" class="icon" title="'.getLabel('inf_set_user_clearance').'">'.getIcon('clearance').'</span></div>
						</li>';
					}
					$this->html .= '<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.htmlspecialchars($arr_row[DB::getTableName('TABLE_USERS')]['name']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_username').'</label>
						<div><input type="text" name="uname" value="'.htmlspecialchars($arr_row[DB::getTableName('TABLE_USERS')]['uname']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_password').'</label>
						<div><input type="text" name="password" value="" /><span id="y:intf_user_management:gen_password-0" class="icon" title="'.getLabel('inf_generate_password').'">'.getIcon('refresh').'</span></div>
					</li>
					<li>
						<label>'.getLabel('lbl_email').'</label>
						<div><input type="text" name="email" value="'.htmlspecialchars($arr_row[DB::getTableName('TABLE_USERS')]['email']).'" /></div>
					</li>';
					if ($arr_user_group['parent_id']) {
						
						$this->html .= '<li>
							<label>'.getLabel('lbl_parent').'</label>
							<div><input type="hidden" name="parent_id" value="'.$arr_row[DB::getTableName('TABLE_USERS')]['parent_id'].'" /><input type="text" id="y:intf_user_management:lookup_parent_user-'.$arr_user_group['parent_id'].'" class="autocomplete" value="'.$arr_row[DB::getTableName('VIEW_USER_PARENT')]['parent_name'].'" /></div>
						</li>';
					}
					$this->html .= '<li>
						<label></label>
						<div><label title="'.getLabel('inf_send_confirmation_email').'"><input type="checkbox" name="confirm_mail" value="1" /><span>'.getLabel('lbl_email').'</span></label></div>
					</li>
				</ul>
				<hr />
				<ul>';

					foreach ($arr_columns as $arr_column) {
						
						if ($arr_column['TABLE_NAME'] != DB::getTableName('TABLE_USERS') && !$arr_column['VIEW']) {
							
							$this->html .= '<li>
								<label>'.getLabel('lbl_'.($arr_column['VIRTUAL_NAME'] ? $arr_column['VIRTUAL_NAME'] : $arr_column['COLUMN_NAME'])).'</label>
								<div>';
								if ($arr_column['SOURCE_TABLE_NAME']) {
									if ($arr_column['MULTI_SOURCE']) {
										$arr_tags = [];
										if ($arr_row[$arr_column['TABLE_NAME']] && key($arr_row[$arr_column['TABLE_NAME']])) {
											foreach ($arr_row[$arr_column['TABLE_NAME']] as $key => $value) {
												$arr_tags[$key] = $value[$arr_column['COLUMN_NAME']];
											}
										}
										$this->html .= cms_general::createMultiSelect('col['.$arr_column['SOURCE_TABLE_NAME'].'.'.$arr_column['SOURCE_COLUMN_NAME'].']', 'y:intf_user_management:lookup_table-'.$arr_column['TABLE_NAME'].'.'.$arr_column['LINK_COLUMN_NAME'].'.'.$arr_column['COLUMN_NAME'], $arr_tags);
									} else {
										$this->html .= '<input type="hidden" name="col['.$arr_column['SOURCE_TABLE_NAME'].'.'.$arr_column['SOURCE_COLUMN_NAME'].']" value="'.htmlspecialchars($arr_row[$arr_column['SOURCE_TABLE_NAME']][$arr_column['SOURCE_COLUMN_NAME']]).'" /><input type="text" id="y:intf_user_management:lookup_table-'.$arr_column['TABLE_NAME'].'.'.$arr_column['LINK_COLUMN_NAME'].'.'.$arr_column['COLUMN_NAME'].'" class="autocomplete" value="'.htmlspecialchars($arr_row[$arr_column['TABLE_NAME']][$arr_column['COLUMN_NAME']]).'" />';
									}
								} else {
									$arr_class = [];
									switch ($arr_column['DATA_TYPE']) {
										case 'datetime':
										case 'date':
											$arr_class[] = 'datepicker';
											$value_column = date('d-m-Y'.($arr_column['DATA_TYPE'] == 'datetime' ? ' h:i:s' : ''), ($arr_row[$arr_column['TABLE_NAME']][$arr_column['COLUMN_NAME']] ? strtotime($arr_row[$arr_column['TABLE_NAME']][$arr_column['COLUMN_NAME']]) : time()));
											break;
										default:
											$value_column = htmlspecialchars($arr_row[$arr_column['TABLE_NAME']][$arr_column['COLUMN_NAME']]);
									}
									$this->html .= '<input type="text" '.($arr_class ? ' class="'.implode(" ", $arr_class).'"' : '').'name="col['.$arr_column['TABLE_NAME'].'.'.$arr_column['COLUMN_NAME'].']" value="'.$value_column.'" />';
								}
								$this->html .= '</div>
							</li>';
						}
					}

				$this->html .= '</ul></fieldset>
			</form>';
			
			$this->validate = ['name' => 'required', 'uname' => 'required', 'email' => 'required'];
			
			if ($arr_user_group['parent_id']) {
				$this->validate['parent_id'] = 'required';
			}
		}
		
		if ($method == "popup_user_clearance" && (int)$id) {
			
			$arr_dirs = directories::getDirectoryTree(directories::getDirectories());
			$arr_pages = pages::getPages();
			$user_group_id = $id;
			
			$arr_cur_clearance = [];
			
			if ($value['clearance']) {
				$arr_cur_clearance = json_decode($value['clearance'], true);
			}
			
			$func_directory = function($arr_path) use (&$func_directory, $arr_pages, $arr_cur_clearance, $user_group_id) {
				
				$cur_dir = $arr_path['arr_dir'];
				$user_group = array_filter(explode('/', $cur_dir['user_group']));
				$cur_user_group_id = end($user_group);
										
				$return .= '<div class="node">
				
					<div class="object'.($id == $cur_dir['user_group_id'] ? ' user' : ($cur_dir['user_group_id'] ? ' closed' : '')).'">
						<fieldset><legend>'.$cur_dir['title'].'</legend>
							<ul><li>';
						
								if ($arr_pages[$cur_dir['id']] && $user_group_id == $cur_user_group_id) {
									$return .= '<ul class="select">';
									foreach ($arr_pages[$cur_dir['id']] as $arr_page) {
										$return .= '<li><label'.($arr_page['clearance'] ? ' class="allow"' : '').'><input type="checkbox" name="pages['.$arr_page['id'].']" value="1"'.(in_array($arr_page['id'], $arr_cur_clearance) ? ' checked="checked"' : '').' /><span>'.$arr_page['title'].'</span></label></li>';
									}
									$return .= '</ul>';
								}		
											
							$return .= '</li></ul>
						</fieldset>
					</div>';
					
					if ($arr_path['subs']) {
						
						$return .= '<div>';
						
						foreach ($arr_path['subs'] as $sub_dir) {
							$return .= $func_directory($sub_dir);
						}
						
						$return .= '</div>';
					}
					
				$return .= '</div>';
				
				return $return;
			};
			
			$this->html = '<form id="frm-user_clearance" data-method="return_user_clearance">
				'.$func_directory($arr_dirs['']).'
			</form>';

		}
		
		// POPUP INTERACT
							
		if ($method == "gen_password") {
		
			$this->html = generateRandomString(10);
		}
		
		if ($method == "lookup_parent_user") {
			
			$arr_users = self::filterUsers($value, ['group_id' => $id]);

			$arr = [];
			
			foreach ($arr_users as $key => $row) {
				$arr[] = ['id' => $row['id'], 'label' => $row['name'], 'value' => $row['name']];
			}
		
			$this->html = $arr;
		}
		
		if ($method == "lookup_table") {
		
			$arr = [];
			$arr_database_table_column = explode('.', $id);

			$res = DB::query("SELECT
				".DBFunctions::strEscape($arr_database_table_column[2])." AS id, ".DBFunctions::strEscape($arr_database_table_column[3])." AS var
					FROM ".DBFunctions::strEscape($arr_database_table_column[0]).".".DBFunctions::strEscape($arr_database_table_column[1])." s
				".($value == "?" ? "" : "WHERE ".DBFunctions::strEscape($arr_database_table_column[3])." LIKE '%".$value."%'")."
			");
	
			while ($row = $res->fetchAssoc()) {
				$arr[] = (object)['id' => $row['id'], 'label' => $row['var'], 'value' => $row['var']];
			}
		
			$this->html = $arr;
		}
		
		if ($method == "return_user_clearance") {
			
			$this->html = ($_POST['pages'] ? json_encode(array_keys($_POST['pages'])) : '');
		}
		
		// DATATABLE
					
		if ($method == "users_data") {
			
			$arr_sql_columns = [];	
			$arr_sql_columns_as = [];
			
			$arr_columns = user_groups::getUserGroupColumns($id);
			$arr_tables = user_groups::getUserGroupTables($id);
			
			foreach ($arr_columns as $arr_column) {
				
				if ($arr_column['MULTI_SOURCE']) {
					$arr_sql_columns_as[] = DBFunctions::sqlImplode("DISTINCT ".$arr_column['TABLE_NAME'].".\"".$arr_column['COLUMN_NAME']."\"")." AS ".$arr_column['VIRTUAL_NAME'];
				} else {
					$arr_sql_columns_as[] = $arr_column['TABLE_NAME'].".\"".$arr_column['COLUMN_NAME']."\"";
				}
				
				$arr_sql_columns[] = $arr_column['TABLE_NAME'].".\"".$arr_column['COLUMN_NAME']."\"";
			}
			
			$sql_table = DB::getTable('TABLE_USERS');
			
			$sql_index = $sql_table.'.id';
			
			$arr_sql_columns_as[] = $sql_table.'.id';
						
			foreach ($arr_tables as $key => $value) {
				
				$sql_table .= " LEFT JOIN ".$key." ON (".$key.".".$value['to_column']." = ".$value['from_table'].".".$value['from_column'].") ";
			}
			
			$sql_where = "group_id = ".$id."";
								 
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, false, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);
			
			$nr_columns = count($arr_sql_columns);
			
			while ($arr_row = $arr_datatable['result']->fetchRow())	{

				$arr_data = [];
				
				$arr_data['id'] = 'x:intf_user_management:user_id-'.$arr_row[$nr_columns];
				
				for ($i = 0; $i < $nr_columns; $i++) {
					
					if ($i == 0) {
						
						$arr_data[] = '<span class="icon" data-category="status">'.getIcon((DBFunctions::unescapeAs($arr_row[$i], DBFunctions::TYPE_BOOLEAN) ? 'tick' : 'min')).'</span>';
					} else if ($arr_sql_columns[$i] != $arr_sql_columns_as[$i]) {
						
						// Multi value column
						if ($arr_row[$i]) {
							
							$arr = explode(', ', $arr_row[$i]);
							$count = count($arr);
							$arr_data[] = '<span class="info"><span class="icon" title="'.($count ? implode('<br />', $arr) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.$count.'</span></span>';
						} else {
							
							$arr_data[] = '';
						}
					} else if ($arr_sql_columns[$i] != ' ') {
						
						// General output
						$arr_data[] = $arr_row[$i];
					}
				}
				$arr_data[] = '<input type="button" class="data popup edit" value="edit" />'
					.'<input type="button" class="data msg del" value="del" /><input class="multi" value="'.$arr_row[$nr_columns].'" type="checkbox" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
							
		// QUERY
	
		if ($method == "insert") {
		
			$new_user = self::addUser($_POST['enabled'], ['name' => $_POST['name'], 'uname' => $_POST['uname'], 'group_id' => $id, 'email' => $_POST['email'], 'parent_id' => $_POST['parent_id']], $_POST['password'], (bool)$_POST['confirm_mail']);
			
			self::updateUserLinkedData($new_user['id'], $_POST['col']);
											
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "update" && (int)$id) {

			$update_user = self::updateUser($id, $_POST['enabled'], ['name' => $_POST['name'], 'uname' => $_POST['uname'], 'email' => $_POST['email'], 'parent_id' => $_POST['parent_id']], $_POST['password'], (bool)$_POST['confirm_mail']);
			
			$_POST['col'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE').'.page_id'] = ($_POST['col'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE').'.page_id'] ? array_filter(json_decode($_POST['col'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE').'.page_id'], true)) : []);
			
			self::updateUserLinkedData($id, $_POST['col']);

			$this->refresh_table = true;
			$this->msg = true;
		}

		if ($method == "del" && $id) {
			
			foreach ((is_array($id) ? $id : [$id]) as $id) {
				
				self::delUser($id);
			}
			
			$this->refresh_table = true;
			$this->msg = true;
		}
	}
}
