<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

abstract class register_by extends base_module {

	public static function moduleProperties() {
		static::$parent_label = getLabel('lbl_users');
	}
	
	protected $main_table = ''; // User details table
	protected $columns = []; // name => array("table" => name, "column" => name/array(name, name))
	protected $arr_user = [];
	abstract protected function contentsForm();
	abstract protected function processForm();
	abstract protected function doubleCheckValidUserId($update);
	
	public static function moduleVar() {
		$return .= '<select>';
		$return .= user_groups::createUserGroupsDropdown(user_groups::getUserGroups());
		$return .= '</select>';
		
		return $return;
	}

	public function contents() {
	
		$return .= $this->createAddUser();

		$return .= '<table class="display" id="d:'.static::class.':data-0">
				<thead>
					<tr>
						<th><span title="'.getLabel('lbl_enabled').'">E</span></th>
						<th class="max limit">'.getLabel('lbl_name_display').'</th>
						<th class="max limit">'.getLabel('lbl_username').'</th>';
						foreach ($this->columns as $key => $value) {
							$return .= '<th>'.$key.'</th>';
						}
						$return .= '<th class="disable-sort"></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="'.(count($this->columns)+3).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
					</tr>
				</tbody>
			</table>';

		return $return;
	}
	
	private function createAddUser() {
	
		$return .= '<form id="f:'.static::class.':add-0" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.getLabel('lbl_user').'" />
			</menu>
		</form>';
		
		return $return;
	}

	private function createForm($id = false) {
	
		if ($id) {
			
			$this->checkValidUserId($id);
		
			$this->arr_user = user_groups::getUserData($id);
		}
		
		$return .= '<div class="tabs">
			<ul>
				<li><a href="#">'.getLabel('lbl_user').'</a></li>
				'.(isset($_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]) ? '<li><a href="#">'.getLabel('lbl_page_clearance').'</a></li>' : '').'
			</ul>
				
			<div>
				<div class="options fieldsets"><div>';
							
					$return .= '<fieldset><legend>'.getLabel('lbl_account').'</legend><ul>
						'.($id ? '<li><label>'.getLabel('lbl_active').'</label><span>'.cms_general::createSelectorRadio([['id' => '1', 'name' => getLabel('lbl_active')], ['id' => '0', 'name' => getLabel('lbl_inactive')]], 'enabled', (int)$this->arr_user[DB::getTableName('TABLE_USERS')]['enabled']).'</span></li>' : '').'
						<li><label>'.getLabel('lbl_name_display').'</label><input name="name" type="text" value="'.$this->arr_user[DB::getTableName('TABLE_USERS')]['name'].'" /></li>
						<li><label>'.getLabel('lbl_email').'</label><input name="email" type="text" value="'.$this->arr_user[DB::getTableName('TABLE_USERS')]['email'].'" /></li>
						'.($id ? '<li><label>'.getLabel('lbl_password_reset').'</label><input name="reset_password" type="checkbox" value="1" /></li>' : '').'
						<li><label>'.getLabel('lbl_email_confirm').'</label><input name="send_email" type="checkbox" value="1"'.(!$id ? ' checked="checked"' : '').' /></li>
					</ul></fieldset>';
					
					$return .= $this->contentsForm();

				$return .= '</div></div>
			</div>';
			
			if (isset($_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')])) {
				
				$arr_dirs = directories::getDirectoryTree(directories::getDirectories());
				$arr_pages = pages::getPages();
				
				$arr_cur_clearance = ($this->arr_user ? array_keys($this->arr_user[DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]) : []);
				
				$func_directory = function($arr_path) use (&$func_directory, $arr_pages, $arr_cur_clearance) {
					
					$cur_dir = $arr_path['arr_dir'];
					$cur_user = $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')];
					$user_group = array_filter(explode('/', $cur_dir['user_group']));
					$cur_user_group_id = end($user_group);
					
					if ($cur_user_group_id && $cur_user['group_id'] != $cur_user_group_id) {
						
						return;
					} else if (!$cur_user_group_id) {
						
						if ($arr_path['subs']) {
							
							foreach ($arr_path['subs'] as $sub_dir) {
								$return .= $func_directory($sub_dir);
							}
						}
							
						return $return;
					}
											
					$return .= '<div class="node">
						<h4>'.($cur_dir['root'] ? getLabel('name', 'D') : $cur_dir['title']).'</h4>
						<fieldset>
							<ul><li>';
						
								$arr_pages_clearance = [];
								if ($arr_pages[$cur_dir['id']] && $cur_user['group_id'] == $cur_user_group_id) {
									
									foreach ($arr_pages[$cur_dir['id']] as $page_id => $arr_page) {
										
										if (!$arr_page['clearance'] || ($arr_page['clearance'] && !$_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')][$page_id])) {
											continue;
										}
										
										$arr_pages_clearance[$page_id] = $arr_page;
									}
								}
								
								$return .= cms_general::createSelectorList($arr_pages_clearance, 'pages_clearance', $arr_cur_clearance, 'title');
											
							$return .= '</li></ul>
						</fieldset>';
						
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
				
				$return .= '<div>
				
					<div class="options network"><div class="node">
						'.$func_directory($arr_dirs['']).'
					</div></div>
					
				</div>';
			}			
			
		$return .= '</div>
			
		<menu class="options">
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_user').'" /><input type="submit" name="discard" value="'.getLabel("lbl_cancel").'" />
		</menu>';
		
		$this->validate = array_merge($this->validate ?: [], ['name' => 'required', 'email' => 'required']);
		
		return $return;
	}

	public static function css() {
	
		$class = static::class;
	
		$return = '.mod.'.$class.' {  }
					.mod.'.$class.' fieldset > ul > li > label:first-child + * input[name=address] { width: 115px; }
					.mod.'.$class.' fieldset > ul > li > label:first-child + * input[name=address_nr] { width: 30px; }
					.mod.'.$class.' fieldset > ul > li > label:first-child + * input[name=zipcode] { width: 60px; }
					.mod.'.$class.' fieldset > ul > li > label:first-child + * input[name=zipcode_l] { width: 30px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$class = static::class;
	
		$return = "SCRIPTER.static('.".$class."', function(elm_scripter) {
		
			elm_scripter.on('click', '.edit', function() {
				$(this).quickCommand(elm_scripter.children('form'), {html: 'replace'});
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		if ($method == "add") {
			$this->html = '<form id="f:'.static::class.':insert-0">'.$this->createForm().'</form>';
		}
		if ($method == "edit") {
			$this->html = '<form id="f:'.static::class.':update-'.$id.'">'.$this->createForm($id).'</form>';
		}
	
		if ($method == "data") {
			
			$arr_sql_columns = ['u.enabled', 'u.name', 'u.uname'];

			if ($this->main_table) {
				
				foreach ($this->columns as $key => $arr) {
					
					if (is_array($arr['column'])) {
						
						foreach ($arr['column'] as $column) {
							
							$arr_sql_columns[] = $arr['table'].'.'.$column;
						}
					} else {
						
						$arr_sql_columns[] = $arr['table'].'.'.$arr['column'];
					}
				}
			}
			
			$arr_sql_columns_as = $arr_sql_columns;
			$arr_sql_columns_as[] = 'u.id';
			
			$sql_index = 'u.id';
			
			$sql_table = DB::getTable('TABLE_USERS')." u
				".($this->main_table ? "LEFT JOIN ".$this->main_table." ON (".$this->main_table.".user_id = ".$sql_index.")" : "")."
			";

			if ($this->mod_var && $this->mod_var != $_SESSION['USER_GROUP']) {
				$sql_where = "u.parent_id = ".$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id'];
			} else if ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id']) {
				$sql_where = "u.parent_id = ".$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'];
			} else {
				$sql_where = "u.group_id = ".$_SESSION['USER_GROUP'];
			}
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, false, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);
			
			$class = static::class;
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{

				$arr_data = [];
				
				$arr_data['id'] = 'x:'.$class.':user_id-'.$arr_row['id'].'';
				$arr_data[] = '<span class="icon" data-category="status">'.getIcon(($arr_row['enabled'] ? 'tick' : 'min')).'</span>';
				$arr_data[] = $arr_row['name'];
				$arr_data[] = $arr_row['uname'];
				foreach ($this->columns as $key => $arr) {
					if (is_array($arr['column'])) {
						$arr_combine = [];
						foreach ($arr['column'] as $column) {
							$arr_combine[] = $arr_row[$column];
						}
						$arr_data[] = implode(' ', $arr_combine);
					} else {
						$arr_data[] = $arr_row[$arr['column']];
					}
				}
				$arr_data[] = '<input type="button" class="data edit" value="edit" /><input type="button" class="data msg del" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
	
		// QUERY
		
		if (($method == "insert" || $method == "update") && $_POST['discard']) {
							
			$this->html = $this->createAddUser();
			return;
		}
		
		if ($method == "insert") {

			if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				error(getLabel('msg_error_email_format'));
			}
			
			$this->checkValidUserId();

			$parent_id = 0;
			if ($this->mod_var && $this->mod_var != $_SESSION['USER_GROUP']) {
				$parent_id = $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id'];
			} else if ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id']) {
				$parent_id = $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'];
			}

			$arr_user = user_management::addUser(true, ['name' => $_POST['name'], 'uname' => $_POST['email'], 'email' => $_POST['email'], 'group_id' => $this->mod_var, 'parent_id' => $parent_id], false, (bool)$_POST['send_email']);
			
			$this->arr_user = user_groups::getUserData($arr_user['id']);
			
			$user_data = $this->process();
			$user_data += $this->processForm();
			
			user_management::updateUserLinkedData($arr_user['id'], $user_data);

			$this->reset_form = true;
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "update" && (int)$id) {

			if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				error(getLabel('msg_error_email_format'));
			}

			$this->checkValidUserId($id);
			
			$user_id = $id;
			
			$this->arr_user = user_groups::getUserData($id);

			user_management::updateUser($user_id, $_POST['enabled'], ['name' => $_POST['name'], 'uname' => $this->arr_user[DB::getTableName('TABLE_USERS')]['uname'], 'email' => $_POST['email']], ((int)$_POST['reset_password'] ? generateRandomString(10) : false), (bool)$_POST['send_email']);
			
			$user_data = $this->process();
			$user_data += $this->processForm();
			
			user_management::updateUserLinkedData($user_id, $user_data);
			
			$this->html = $this->createAddUser();
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del" && (int)$id) {
			
			$this->checkValidUserId($id);
			
			user_management::delUser($id);
			
			$this->msg = true;
		}
		
		if ($method == "active" && (int)$id) {
			
			$this->checkValidUserId($id);
		
			$user = user_management::updateUser($id, 1);
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		if ($method == "inactive" && (int)$id) {
			
			$this->checkValidUserId($id);
		
			$update_user = user_management::updateUser($id, 0);
			
			$this->refresh_table = true;
			$this->msg = true;
		}
	}
	
	protected function process() {
		
		$arr = [];
		
		if (isset($this->arr_user[DB::getTableName('TABLE_USER_PAGE_CLEARANCE')])) {
			
			$arr_cur_page_ids = array_keys($this->arr_user[DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]);
			$arr[DB::getTableName('TABLE_USER_PAGE_CLEARANCE').'.page_id'] = array_combine($arr_cur_page_ids, $arr_cur_page_ids);
			
			foreach ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')] as $page_id => $value) {
				
				if ($_POST['pages_clearance'][$page_id]) {
					$arr[DB::getTableName('TABLE_USER_PAGE_CLEARANCE').'.page_id'][$page_id] = $page_id;
				} else {
					unset($arr[DB::getTableName('TABLE_USER_PAGE_CLEARANCE').'.page_id'][$page_id]);
				}
			}
		}
		
		return $arr;
	}
	
	private function checkValidUserId($id = false) {
		
		$this->doubleCheckValidUserId($id);
		
		if (!$id) {
			return;
		}

		if ($this->mod_var && $this->mod_var != $_SESSION['USER_GROUP']) {
			
			$check = user_management::checkUserIds($id, $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id'], 'parent');
		} else if ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id']) {
			
			$check = user_management::checkUserIds($id, $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'], 'parent');
		} else {
			
			$check = user_management::checkUserIds($id, $_SESSION['USER_GROUP'], 'group');
		}
	
		if (!$check) {
			error(getLabel('msg_not_allowed'));
		}
	}
}
