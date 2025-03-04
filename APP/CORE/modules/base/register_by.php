<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

abstract class register_by extends base_module {

	public static function moduleProperties() {
		static::$parent_label = getLabel('lbl_users');
	}
	
	protected $sql_main_table = false; // User details table
	protected $arr_columns = []; // name => array("table" => name, "column" => name/array(name, name))
	protected $arr_user = [];
	abstract protected function contentsForm();
	abstract protected function processForm();
	abstract protected function doubleCheckAuthorisedUserId($user_id);
	
	public static function moduleVariables() {
		
		$return = '<select name="user_group_id">'
			.user_groups::createUserGroupsDropdown(user_groups::getUserGroups())
		.'</select>'
		.'<input type="checkbox" name="allow_uname" value="1" title="'.getLabel('lbl_allow').' '.getLabel('lbl_uname').'" />';
		
		return $return;
	}

	public function contents() {
	
		$return = $this->createAddUser();

		$return .= '<table class="display" id="d:'.static::class.':data-0">
				<thead>
					<tr>
						<th><span title="'.getLabel('lbl_enabled').'">E</span></th>
						<th class="max limit" data-sort="asc-0">'.getLabel('lbl_name_display').'</th>
						<th class="max limit">'.getLabel('lbl_username').'</th>';
						foreach ($this->arr_columns as $arr_column) {
							$return .= '<th>'.$arr_column['label'].'</th>';
						}
						$return .= '<th class="disable-sort"></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="'.(count($this->arr_columns)+3).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
					</tr>
				</tbody>
			</table>';

		return $return;
	}
	
	private function createAddUser() {
	
		$return = '<form id="f:'.static::class.':add-0" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.getLabel('lbl_user').'" />
			</menu>
		</form>';
		
		return $return;
	}

	private function createForm($id = false) {
		
		$str_url_account = false;
		
		if ($id) {
			
			$this->checkAuthorisedUserId($id);
		
			$this->arr_user = user_groups::getUserData($id);
			
			$arr_user_account = user_management::getUserAccount($id);
						
			if ($arr_user_account['passkey']) {
				
				$arr_mod = pages::getClosestModule('login', 0, 0, $arr_user_account['group_id']);
				
				$str_url_account = pages::getModuleURL($arr_mod).'welcome/'.$id.'/'.$arr_user_account['passkey'];
			}
		}
		
		$return = '<h1>'.($id ? getLabel('lbl_user').': '.strEscapeHTML($this->arr_user[DB::getTableName('TABLE_USERS')]['name']) : getLabel('lbl_user')).'</h1>
		
		<div class="tabs">
			<ul>
				<li><a href="#">'.getLabel('lbl_user').'</a></li>
				'.(isset($_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]) ? '<li><a href="#">'.getLabel('lbl_page_clearance').'</a></li>' : '').'
			</ul>
				
			<div>
				<div class="options fieldsets"><div>';
							
					$return .= '<fieldset><legend>'.getLabel('lbl_account').'</legend><ul>';
						if ($id) {
							
							$return .= '<li>
								<label>'.getLabel('lbl_active').'</label>
								<span>'.cms_general::createSelectorRadio([['id' => '1', 'name' => getLabel('lbl_active')], ['id' => '0', 'name' => getLabel('lbl_inactive')]], 'enabled', (int)$this->arr_user[DB::getTableName('TABLE_USERS')]['enabled']).'</span>
							</li>';
						}
						$return .= '<li>
							<label>'.getLabel('lbl_name_display').'</label>
							<input name="name" type="text" value="'.strEscapeHTML($this->arr_user[DB::getTableName('TABLE_USERS')]['name']).'" />
						</li>
						<li>
							<label>'.getLabel('lbl_username').'</label>
							'.($this->arr_variables['allow_uname'] ? '<input name="uname" type="text" value="'.strEscapeHTML($this->arr_user[DB::getTableName('TABLE_USERS')]['uname']).'" placeholder="'.getLabel('lbl_email').'" />' : '<span>'.($this->arr_user[DB::getTableName('TABLE_USERS')]['uname'] ?: getLabel('lbl_email')).'</span>').'
						</li>
						<li>
							<label>'.getLabel('lbl_email').'</label>
							<input name="email" type="text" value="'.strEscapeHTML($this->arr_user[DB::getTableName('TABLE_USERS')]['email']).'" />
						</li>';
						if ($str_url_account) {
						
							$return .= '<li>
								<label>'.getLabel('lbl_account').' '.getLabel('lbl_url').'</label>
								<div>
									<div class="hide-edit hide">'
										.'<div class="password-url" title="'.getLabel('inf_copy_click').'">'.$str_url_account.'</div>'
									.'</div>'
									.'<input type="button" class="data neutral" value="show" />
								</div>
							</li>';
						}
						$return .= '<li>
							<label>'.getLabel('lbl_send').'</label>
							<div>';
								if ($id) {
									
									$arr_email_options = [['id' => '', 'name' => getLabel('lbl_no').' '.getLabel('lbl_email')], ['id' => user_management::MAIL_ACCOUNT, 'name' => getLabel('lbl_send_account'), 'title' => getLabel('inf_send_account_confirmation')], ['id' => user_management::MAIL_ACCOUNT_PASSWORD, 'name' => getLabel('lbl_send_account_password'), 'title' => getLabel('inf_send_account_confirmation')]];
									
									$return .= cms_general::createSelectorRadioList($arr_email_options, 'send_mail');
								} else {
									
									$return .= '<label title="'.getLabel('inf_send_account_confirmation').'"><input type="checkbox" name="send_mail" value="'.user_management::MAIL_ACCOUNT.'" checked="checked" /><span>'.getLabel('lbl_send_account').'</span></label>';
								}
							$return .= '</div>
						</li>
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
						<div><fieldset>
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
						</fieldset></div>';
						
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
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_user').'" /><input type="submit" name="do_discard" value="'.getLabel('lbl_cancel').'" />
		</menu>';
		
		$this->validate = array_merge($this->validate ?: [], ['name' => 'required', 'email' => 'required']);
		
		return $return;
	}

	public static function css() {
	
		$class = static::class;
	
		$return = '
			.mod.'.$class.' {  }
			.mod.'.$class.' fieldset > ul > li > label:first-child + * input[name=address] { width: 115px; }
			.mod.'.$class.' fieldset > ul > li > label:first-child + * input[name=address_nr] { width: 30px; }
			.mod.'.$class.' fieldset > ul > li > label:first-child + * input[name=zipcode] { width: 60px; }
			.mod.'.$class.' fieldset > ul > li > label:first-child + * input[name=zipcode_l] { width: 30px; }
			.mod.'.$class.' fieldset > ul > li > label:first-child + * .password-url { font-family: var(--font-mono); cursor: pointer; }
			.mod.'.$class.' fieldset > ul > li > label:first-child + * .password-url.pulse { background-color: transparent; color: var(--highlight); }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$class = static::class;
	
		$return = "SCRIPTER.static('.".$class."', function(elm_scripter) {
		
			elm_scripter.on('click', '.edit', function() {
				$(this).quickCommand(elm_scripter.children('form'), {html: 'replace'});
			}).on('click', '.password-url', function() {
				
				navigator.clipboard.writeText(this.textContent);
				new Pulse(this, {duration: 500});
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
			$arr_sql_columns_as = $arr_sql_columns;
			$arr_sql_columns_as[] = 'u.id';

			if ($this->sql_main_table) {
				
				foreach ($this->arr_columns as $key => $arr) {
					
					if (is_array($arr['column'])) {
						
						foreach ($arr['column'] as $column) {
							
							$sql_column = $arr['table'].'.'.$column;
						
							$arr_sql_columns[] = $sql_column;
							$arr_sql_columns_as[] = $sql_column;
						}
					} else {
						
						$sql_column = $arr['table'].'.'.$arr['column'];
						
						$arr_sql_columns[] = $sql_column;
						
						if ($arr['column_as']) {
							$arr_sql_columns_as[] = $arr['column_as'].' AS '.$arr['column'];
						} else {
							$arr_sql_columns_as[] = $sql_column;
						}
					}
				}
			}

			$sql_index = 'u.id';
			$sql_index_body = $sql_index;
			$sql_table = DB::getTable('TABLE_USERS').' u';
			
			if ($this->sql_main_table) {
				
				$sql_index_body .= ', '.$this->sql_main_table.'.user_id';
				$sql_table .= "
					LEFT JOIN ".$this->sql_main_table." ON (".$this->sql_main_table.".user_id = ".$sql_index.")
				";
			}

			if ($this->arr_variables['user_group_id'] && $this->arr_variables['user_group_id'] != $_SESSION['USER_GROUP']) {
				$sql_where = "u.parent_id = ".$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id'];
			} else if ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id']) {
				$sql_where = "u.parent_id = ".$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'];
			} else {
				$sql_where = "u.group_id = ".$_SESSION['USER_GROUP'];
			}
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, false, $arr_sql_columns_as, $sql_table, $sql_index, '', $sql_index_body, $sql_where);
			
			$class = static::class;
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{

				$arr_data = [];
				
				$arr_data['id'] = 'x:'.$class.':user_id-'.$arr_row['id'].'';
				$arr_data[] = '<span class="icon" data-category="status">'.getIcon(($arr_row['enabled'] ? 'tick' : 'min')).'</span>';
				$arr_data[] = $arr_row['name'];
				$arr_data[] = $arr_row['uname'];
				
				foreach ($this->arr_columns as $key => $arr) {
					
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
		
		if (($method == "insert" || $method == "update") && $this->is_discard) {
							
			$this->html = $this->createAddUser();
			return;
		}
		
		if ($method == "insert") {

			if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				error(getLabel('msg_error_email_format'));
			}
			
			$this->checkAuthorisedUserId();

			$parent_id = 0;
			if ($this->arr_variables['user_group_id'] && $this->arr_variables['user_group_id'] != $_SESSION['USER_GROUP']) {
				$parent_id = $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id'];
			} else if ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id']) {
				$parent_id = $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'];
			}
			
			$arr_update = ['name' => $_POST['name'], 'uname' => $_POST['email'], 'email' => $_POST['email'], 'group_id' => $this->arr_variables['user_group_id'], 'parent_id' => $parent_id];
			
			if ($this->arr_variables['allow_uname'] && $_POST['uname']) {
				$arr_update['uname'] = $_POST['uname'];
			}

			$arr_user = user_management::addUser(true, $arr_update, false, (bool)$_POST['send_mail']);
			
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

			$this->checkAuthorisedUserId($id);
			
			$user_id = $id;
			$str_password = ((int)$_POST['send_mail'] == user_management::MAIL_ACCOUNT_PASSWORD ? generateRandomString(10) : false); // Reset the password, force old password invalid
			
			$this->arr_user = user_groups::getUserData($id);
						
			$arr_update = ['name' => $_POST['name'], 'email' => $_POST['email']];
			
			if ($this->arr_variables['allow_uname'] && $_POST['uname']) {
				$arr_update['uname'] = $_POST['uname'];
			}

			user_management::updateUser($user_id, $_POST['enabled'], $arr_update, $str_password, (int)$_POST['send_mail']);
			
			$user_data = $this->process();
			$user_data += $this->processForm();
			
			user_management::updateUserLinkedData($user_id, $user_data);
			
			$this->html = $this->createAddUser();
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del" && (int)$id) {
			
			$this->checkAuthorisedUserId($id);
			
			user_management::delUser($id);
			
			$this->msg = true;
		}
		
		if ($method == "active" && (int)$id) {
			
			$this->checkAuthorisedUserId($id);
		
			$user = user_management::updateUser($id, 1);
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		if ($method == "inactive" && (int)$id) {
			
			$this->checkAuthorisedUserId($id);
		
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
		
	private function checkAuthorisedUserId($id = false) {
		
		$this->doubleCheckAuthorisedUserId($id);
		
		if (!$id) {
			return;
		}

		if ($this->arr_variables['user_group_id'] && $this->arr_variables['user_group_id'] != $_SESSION['USER_GROUP']) {
			
			$is_valid = user_management::checkUserIds($id, $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id'], 'parent');
		} else if ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id']) {
			
			$is_valid = user_management::checkUserIds($id, $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'], 'parent');
		} else {
			
			$is_valid = user_management::checkUserIds($id, $_SESSION['USER_GROUP'], 'group');
		}
	
		if (!$is_valid) {
			error(getLabel('msg_not_allowed'));
		}
	}
}
