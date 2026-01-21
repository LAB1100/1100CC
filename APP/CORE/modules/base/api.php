<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

abstract class api extends base_module {
	
	public static function moduleProperties() {
		static::$parent_label = getLabel('lbl_api');
	}
	
	const CLIENT_USERS_MODE_ROOT_CHILD = 1;
	const CLIENT_USERS_MODE_CHILD_CHILD = 2;
	const CLIENT_USERS_MODE_USER_USER = 3;
	const CLIENT_USERS_MODE_USER_CHILD = 4;
	const CLIENT_USERS_MODE_PARENT_PARENT = 5;
	const CLIENT_USERS_MODE_PARENT_CHILD = 6;
	const CLIENT_USERS_MODE_PARENT_USER = 7;
	
	protected $arr_api = [];
	protected $mode = null;
	abstract protected function contentsFormConfiguration();
	abstract protected function processFormConfiguration();
	
	public static function moduleVariables() {
		
		$return = '<select name="api_id">'.cms_general::createDropdown(apis::getAPIs()).'</select>';
		
		return $return;
	}

	public function contents() {
		
		$this->setConfig();
				
		$str_html_form = $this->contentsForm();
		
		if ($str_html_form) {
			
			$return = '<div class="tabs">
				<ul>
					<li><a href="#">'.getLabel('lbl_api').'</a></li>
					<li><a href="#">'.getLabel('lbl_authentication').'</a></li>
				</ul>
				
				<div>
					'.$str_html_form.'					
				</div>
				
				<div>
					'.$this->contentsClients().'
				</div>
			</div>';
		} else {
			
			$return = $this->contentsClients();
		}
				
		return $return;
	}
	
	public function contentsForm() {
		
		$str_html_configuration = $this->contentsFormConfiguration();
		
		if (!$str_html_configuration) {
			return false;
		}
		
		$return = '<form id="f:'.static::class.':configuration-0">
			'.$str_html_configuration.'
			<menu class="options">
				<input type="submit" value="'.getLabel('lbl_save').'" />
			</menu>
		</form>';
		
		return $return;
	}
	
	public function contentsClients() {
		
		$return = '<div class="tabs">
			<ul>
				<li><a href="#">'.getLabel('lbl_api_clients').'</a></li>
				<li><a href="#">'.getLabel('lbl_users').'</a></li>
			</ul>
			
			<div>
			
				'.self::createAddClient().'
				
				<table class="display" id="d:'.static::class.':data_clients-0">
					<thead> 
						<tr>
							<th title="'.getLabel('lbl_enabled').'">E</th>
							'.($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD ? '<th>'.getLabel('lbl_user').'</th>' : '').'
							<th class="max" data-sort="asc-0">'.getLabel('lbl_name').'</th>
							<th class="limit">'.getLabel('lbl_identifier').'</th>
							<th class="limit">'.getLabel('lbl_passkey').' (secret)</th>
							<th class="disable-sort">'.getLabel('lbl_users').'</th>
							<th class="disable-sort menu" id="x:'.static::class.':client_id-0" title="'.getLabel('lbl_multi_select').'"><input type="button" class="data del msg del_client" value="del" /><input type="checkbox" class="multi all" value="" /></th>
						</tr> 
					</thead>
					<tbody>
						<tr>
							<td colspan="'.(7 - ($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD ? 1 : 0)).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>
				
			</div><div>
			
				<div class="dynamic"></div>
			
				<table class="display" id="d:'.static::class.':data_client_users-0">
					<thead> 
						<tr>
							<th title="'.getLabel('lbl_enabled').'">E</th>
							'.($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD ? '<th>'.getLabel('lbl_api_client').' - '.getLabel('lbl_user').'</th>' : '').'
							<th class="max" data-sort="asc-0">'.($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD ? getLabel('lbl_api_client').' - '.getLabel('lbl_name') : getLabel('lbl_api_client')).'</th>
							'.(variableHasValue($this->mode, static::CLIENT_USERS_MODE_CHILD_CHILD, static::CLIENT_USERS_MODE_PARENT_CHILD, static::CLIENT_USERS_MODE_ROOT_CHILD) ? '<th>'.getLabel('lbl_user').'</th>' : '').'
							<th class="limit">'.getLabel('lbl_passkey').' (token)</th>
							<th>'.getLabel('lbl_date_start').'</th>
							<th>'.getLabel('lbl_date_end').'</th>
							<th class="disable-sort menu" id="x:'.static::class.':client_user_id-0" title="'.getLabel('lbl_multi_select').'"><input type="button" class="data del msg del_client_user" value="del" /><input type="checkbox" class="multi all" value="" /></th>
						</tr> 
					</thead>
					<tbody>
						<tr>
							<td colspan="'.(8 - ($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD ? 1 : 0) - (variableHasValue($this->mode, static::CLIENT_USERS_MODE_CHILD_CHILD, static::CLIENT_USERS_MODE_PARENT_CHILD, static::CLIENT_USERS_MODE_ROOT_CHILD) ? 1 : 0)).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>
				
			</div>
		</div>';
		
		return $return;
	}
	
	private function createAddClient() {
	
		$return = '<form id="f:'.static::class.':add_client-0" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.getLabel('lbl_api_client').'" />
			</menu>
		</form>';
		
		return $return;
	}
		
	private function createFormClient($client_id = 0) {
		
		$arr_client = [];
		
		if ($client_id) {
			
			$this->checkValidClientId($client_id);
					
			$arr_client = apis::getClient($client_id);
		}
						
		$return = '<div class="options fieldsets"><div>
			<fieldset><legend>'.getLabel('lbl_api_client').'</legend><ul>
				<li>
					<label>'.getLabel('lbl_active').'</label>
					<div>'.cms_general::createSelectorRadio([['id' => '1', 'name' => getLabel('lbl_active')], ['id' => '0', 'name' => getLabel('lbl_inactive')]], 'enabled', (!$client_id || $arr_client['enabled'])).'</div>
				</li>';
				if ($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD) {
					$return .= '<li>
						<label>'.getLabel('lbl_user').'</label>
						<div><input type="hidden" name="user_id" value="'.$arr_client['user_id'].'" /><input type="text" id="y:'.static::class.':lookup_user_client-0" class="autocomplete" value="'.$arr_client['user_name'].'" /></div>
					</li>';
				}
				$return .= '<li>
					<label>'.getLabel('lbl_name').'</label>
					<div><input type="text" name="name" value="'.strEscapeHTML($arr_client['name']).'" /></div>
				</li>
				<li>
					<label>'.getLabel('lbl_valid_period').'</label>
					<div><input type="number" name="time_amount" value="'.(int)$arr_client['time_amount'].'"><select name="time_unit">'.cms_general::createDropdown(cms_general::getTimeUnits(), $arr_client['time_unit'], true).'</select></div>
				</li>';
				if ($client_id) {
					$return .= '<li>
						<label>'.getLabel('lbl_passkey_regenerate').' (secret)</label>
						<div><input type="checkbox" name="regenerate" value="1" /></div>
					</li>';
				}
			$return .= '</ul></fieldset>
		</div></div>
		
		<menu class="options">
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_api_client').'" /><input type="submit" name="do_discard" value="'.getLabel('lbl_cancel').'" />
		</menu>';
		
		return $return;
	}
	
	private function createViewClient($client_id) {
		
		$this->checkValidClientId($client_id);
		
		$arr_client = apis::getClient($client_id);
		
		if ($arr_client['time_amount']) {
			
			$arr_time_unit = cms_general::getTimeUnits($arr_client['time_unit']);
			$str_time_amount = $arr_client['time_amount'].' '.$arr_time_unit['name'];	
		} else {
			$str_time_amount = '∞';	
		}	
		
		$return = '<h2>'.($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD ? strEscapeHTML($arr_client['user_name']).' - ' : '').strEscapeHTML($arr_client['name']).'</h2>';

		$return .= '<div class="record"><dl>
			<div>
				<dt>'.getLabel('lbl_valid_period').'</dt>
				<dd>'.$str_time_amount.'</dd>
			</div>
			<div>
				<dt>'.getLabel('lbl_identifier').'</dt>
				<dd><pre>'.strEscapeHTML($arr_client['id']).'</pre></dd>
			</div>
			<div>
				<dt>'.getLabel('lbl_passkey').' (secret)</dt>
				<dd><pre>'.strEscapeHTML($arr_client['secret']).'</pre></dd>
			</div>
		</dl></div>';
		
		return $return;
	}
	
	private function createFormClientUser($client_id, $user_id = 0) {
		
		$this->checkValidClientId($client_id);
		
		$arr_client_user = [];
		
		if ($user_id) {
			
			$this->checkValidUserId(false, $user_id);
			$arr_client_user = apis::getClientUser($client_id, $user_id);
		}
			
		$arr_client = apis::getClient($client_id);
		
		$return = '<h2>'.getLabel('lbl_api_client').': '.($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD ? strEscapeHTML($arr_client['user_name']).' - ' : '').strEscapeHTML($arr_client['name']).'</h2>
		
		<div class="options">
			
			<div class="fieldsets"><div>
			
				<fieldset><legend>'.getLabel('lbl_user').'</legend><ul>
					<li>
						<label>'.getLabel('lbl_active').'</label>
						<div>'.cms_general::createSelectorRadio([['id' => '1', 'name' => getLabel('lbl_active')], ['id' => '0', 'name' => getLabel('lbl_inactive')]], 'enabled', (!$user_id || $arr_client_user['enabled'])).'</div>
					</li>';
					if (variableHasValue($this->mode, static::CLIENT_USERS_MODE_CHILD_CHILD, static::CLIENT_USERS_MODE_PARENT_CHILD, static::CLIENT_USERS_MODE_ROOT_CHILD)) {
						$return .= '<li>
							<label>'.getLabel('lbl_user').'</label>
							<div><input type="hidden" name="user_id" value="'.$arr_client_user['user_id'].'" /><input type="text" id="y:'.static::class.':lookup_user_client_user-0" class="autocomplete" value="'.$arr_client_user['user_name'].'" /></div>
						</li>';
					}
					$return .= '<li>
						<label>'.getLabel('lbl_valid_period').'</label>
						<div>';
							if ($user_id && $arr_client_user['date_valid']) {
								$return .= cms_general::createDefineDate($arr_client_user['date_valid'], 'date_valid', false);
							} else {
								$return .= '<input type="number" name="time_amount" value="'.(int)$arr_client['time_amount'].'"><select name="time_unit">'.cms_general::createDropdown(cms_general::getTimeUnits(), $arr_client['time_unit'], true).'</select>';
							}
						$return .= '</div>
					</li>';
					if ($user_id) {
						$return .= '<li>
							<label>'.getLabel('lbl_passkey_regenerate').' (token)</label>
							<div><input type="checkbox" name="regenerate" value="1" /></div>
						</li>';
					}
				$return .= '</ul></fieldset>
			</div></div>
		</div>
		
		<menu class="options">
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_user').'" /><input type="submit" name="do_discard" value="'.getLabel('lbl_cancel').'" />
		</menu>';

		return $return;
	}
	
	private function createViewClientUser($client_id, $user_id) {
		
		$this->checkValidClientId($client_id);
		$this->checkValidUserId(false, $user_id);
		
		$arr_client_user = apis::getClientUser($client_id, $user_id);
		
		$return = '<h2>'.getLabel('lbl_api_client').': '.($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD ? strEscapeHTML($arr_client_user['client_user_name']).' - ' : '').strEscapeHTML($arr_client_user['client_name']).'</h2>';
			
		$return .= '<div class="record"><dl>';
			if (variableHasValue($this->mode, static::CLIENT_USERS_MODE_CHILD_CHILD, static::CLIENT_USERS_MODE_PARENT_CHILD, static::CLIENT_USERS_MODE_ROOT_CHILD)) {
				$return .= '<div>
					<dt>'.getLabel('lbl_user').'</dt>
					<dd>'.strEscapeHTML($arr_client_user['user_name']).'</dd>
				</div>';
			}
			$return .= '<div>
				<dt>'.getLabel('lbl_date_start').'</dt>
				<dd>'.date('d-m-Y H:i:s', strtotime($arr_client_user['date'])).'</dd>
			</div>
			<div>
				<dt>'.getLabel('lbl_date_end').'</dt>
				<dd>'.($arr_client_user['date_valid'] ? date('d-m-Y H:i:s', strtotime($arr_client_user['date_valid'])) : '∞').'</dd>
			</div>
			<div>
				<dt>'.getLabel('lbl_passkey').' (token)</dt>
				<dd><pre>'.strEscapeHTML($arr_client_user['token']).'</pre></dd>
			</div>
		</dl></div>';
		
		return $return;
	}

	public static function css() {
	
		$class = static::class;
	
		$return = '
			.mod.'.$class.' input[name*=time_amount] { width: 60px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$class = static::class;
	
		$return = "SCRIPTER.static('.".$class."', function(elm_scripter) {
		
			elm_scripter.on('click', '[id=d\\\:".$class."\\\:data_clients-0] .edit', function() {
				var cur = $(this);
				cur.quickCommand(cur.closest('.tabs > div').children('form'), {html: 'replace'});
			}).on('click', '[id^=x\\\:".$class."\\\:client_id-] .add_client_user, [id=d\\\:".$class."\\\:data_client_users-0] .edit_client_user', function() {
				var cur = $(this);
				var elm_tabs = cur.closest('.tabs');
				var elm_target = elm_tabs.find('[id=d\\\:".$class."\\\:data_client_users-0]').parent('.datatable').prev('.dynamic');
				
				cur.quickCommand(function(html) {
				
					elm_target.html(html);
					
					if (cur.is('.add_client_user')) {
						var index = elm_target.closest('.tabs > div').index() - 1;
						elm_tabs.children('ul').children('li').eq(index).children('a').trigger('click');
					}
				});
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// INTERACT
	
		if ($method == "add_client") {
			
			$this->setConfig();
			
			$this->html = '<form id="f:'.static::class.':insert_client-0">'.$this->createFormClient().'</form>';
		}
		if ($method == "edit_client" && $id) {
			
			$this->setConfig();

			$this->html = '<form id="f:'.static::class.':update_client-'.$id.'">'.$this->createFormClient($id).'</form>';
		}
		
		if ($method == "view_client") {
			
			$this->setConfig();
			
			$this->html = $this->createViewClient($id);
		}
		
		if ($method == "add_client_user") {
			
			$this->setConfig();

			$this->html = '<form id="f:'.static::class.':insert_client_user-'.$id.'">'.$this->createFormClientUser($id).'</form>';
		}
		if ($method == "edit_client_user") {
			
			$this->setConfig();
			
			$arr_id = explode('_', $id);
			$client_id = $arr_id[0];
			$user_id = $arr_id[1];
			
			$this->html = '<form id="f:'.static::class.':update_client_user-'.$id.'">'.$this->createFormClientUser($client_id, $user_id).'</form>';
		}
		
		if ($method == "view_client_user") {
			
			$this->setConfig();
			
			$arr_id = explode('_', $id);
			$client_id = $arr_id[0];
			$user_id = $arr_id[1];

			$this->html = $this->createViewClientUser($client_id, $user_id);
		}
				
		if ($method == "lookup_user_client" || $method == "lookup_user_client_user") {
			
			$this->setConfig();
			
			$name = $value;

			$arr_settings = [];
			
			if ($method == 'lookup_user_client') {
				
				if (!($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD)) {
					return;
				}
				
				if ($this->arr_api['clients_user_group_id']) {
					$arr_settings['group_id'] = $this->arr_api['clients_user_group_id'];
				}
				
				$arr_settings['children_id'] = $_SESSION['USER_ID'];
			} else {
				
				if (!($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD || $this->mode == static::CLIENT_USERS_MODE_PARENT_CHILD || $this->mode == static::CLIENT_USERS_MODE_ROOT_CHILD)) {
					return;
				}
				
				if ($this->arr_api['client_users_user_group_id']) { // Use specified user group
					
					$arr_settings['group_id'] = $this->arr_api['client_users_user_group_id'];
				} else if ($this->arr_api['clients_user_group_id']) { // Use user group descendants
					
					$arr_user_group_children = user_groups::getUserGroups(0, $this->arr_api['clients_user_group_id']);
					
					if ($arr_user_group_children) {
						$arr_settings['group_id'] = array_keys($arr_user_group_children);
					}
				}
				
				if ($this->mode == static::CLIENT_USERS_MODE_PARENT_CHILD) {
					$arr_settings['children_id'] = $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'];
				} else if ($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD) {
					$arr_settings['children_id'] = $_SESSION['USER_ID'];
				}
			}
			
			$arr_settings['reduce'] = true;
			
			$arr_users = user_management::filterUsers($name, $arr_settings);
			
			$arr = [];
			foreach ($arr_users as $row) {
				$arr[] = ['id' => $row['id'], 'label' => $row['name'], 'value' => $row['name']];
			}

			$this->html = $arr;
		}
		
		// DATA
	
		if ($method == "data_clients") {
			
			$this->setConfig();
			
			$arr_sql_columns = ['ac.enabled', 'user_name' => 'ac_u.name', 'ac.name', 'ac.id', 'ac.secret'];
			$arr_sql_columns_search = ['', 'user_name' => 'ac_u.name', 'ac.name', 'ac.id', 'ac.secret'];
			$arr_sql_columns_as = ['ac.enabled', 'user_name' => 'ac_u.name AS user_name', 'ac.name', 'ac.id', 'ac.secret'];
			
			if (!($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD)) {
				unset($arr_sql_columns['user_name'], $arr_sql_columns_search['user_name'], $arr_sql_columns_as['user_name']);
			}
			
			$sql_table = DB::getTable('SITE_API_CLIENTS')." ac
				JOIN ".DB::getTable('SITE_APIS')." a ON (a.id = ac.api_id)
				LEFT JOIN ".DB::getTable('TABLE_USERS')." ac_u ON (ac_u.id = ac.user_id)
			";

			$sql_index = 'ac.id';
			
			$sql_where = "ac.api_id = ".(int)$this->arr_api['id'];
			
			if ($this->mode == static::CLIENT_USERS_MODE_PARENT_PARENT || $this->mode == static::CLIENT_USERS_MODE_PARENT_USER || $this->mode == static::CLIENT_USERS_MODE_PARENT_CHILD) {
				$sql_where .= " AND ac_u.id = ".$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'];
			} else if ($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD) {
				$sql_where .= " AND ac_u.parent_id = ".$_SESSION['USER_ID'];
			} else if ($this->mode == static::CLIENT_USERS_MODE_ROOT_CHILD) {
				$sql_where .= " AND ac_u.id IS NULL";
			} else {
				$sql_where .= " AND ac_u.id = ".$_SESSION['USER_ID'];
			}
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);
						
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:'.static::class.':client_id-'.$arr_row['id']."";
				$arr_data['class'] = 'popup';
				$arr_data['attr']['data-method'] = 'view_client';
				
				$arr_data[] = '<span class="icon" data-category="status">'.getIcon(($arr_row['enabled'] ? 'tick' : 'min')).'</span>';
				if ($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD) {
					$arr_data[] = $arr_row['user_name'];
				}
				$arr_data[] = $arr_row['name'];
				$arr_data[] = $arr_row['id'];
				$arr_data[] = $arr_row['secret'];
				$arr_data[] = '<input type="button" class="data add add_client_user" value="add" />';
				$arr_data[] = '<input type="button" class="data edit edit_client" value="edit" /><input type="button" class="data del msg del_client" value="del" /><input class="multi" value="'.$arr_row['id'].'" type="checkbox" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
		
		if ($method == "data_client_users") {
			
			$this->setConfig();
			
			$arr_sql_columns = ['acu.enabled', 'client_user_name' => 'ac_u.name', 'ac.name', 'user_name' => 'acu_u.name', 'acu.token', 'acu.date', 'acu.date_valid'];
			$arr_sql_columns_search = ['', 'client_user_name' => 'ac_u.name', 'ac.name', 'user_name' => 'acu_u.name', 'acu.token', 'acu.date', 'acu.date_valid', 'ac.id'];
			$arr_sql_columns_as = ['acu.enabled', 'client_user_name' => 'ac_u.name AS client_user_name', 'ac.name AS client_name', 'user_name' => 'acu_u.name AS user_name', 'acu.token', 'acu.date', 'acu.date_valid', 'acu.client_id', 'acu.user_id'];
			
			if (!($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD)) {
				unset($arr_sql_columns['client_user_name'], $arr_sql_columns_search['client_user_name'], $arr_sql_columns_as['client_user_name']);
			}
			if (!($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD || $this->mode == static::CLIENT_USERS_MODE_PARENT_CHILD || $this->mode == static::CLIENT_USERS_MODE_ROOT_CHILD)) {
				unset($arr_sql_columns['user_name'], $arr_sql_columns_search['user_name'], $arr_sql_columns_as['user_name']);
			}
			
			$sql_table = DB::getTable('SITE_API_CLIENT_USERS')." acu
				JOIN ".DB::getTable('SITE_API_CLIENTS')." ac ON (ac.id = acu.client_id)
				JOIN ".DB::getTable('SITE_APIS')." a ON (a.id = ac.api_id)
				LEFT JOIN ".DB::getTable('TABLE_USERS')." ac_u ON (ac_u.id = ac.user_id)
				JOIN ".DB::getTable('TABLE_USERS')." acu_u ON (acu_u.id = acu.user_id)
			";

			$sql_index = 'acu.client_id, acu.user_id';
			
			$sql_where = "ac.api_id = ".(int)$this->arr_api['id'];
			
			if ($this->mode == static::CLIENT_USERS_MODE_PARENT_PARENT || $this->mode == static::CLIENT_USERS_MODE_PARENT_USER || $this->mode == static::CLIENT_USERS_MODE_PARENT_CHILD) {
				$sql_where .= " AND ac_u.id = ".$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'];
			} else if ($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD) {
				$sql_where .= " AND ac_u.parent_id = ".$_SESSION['USER_ID'];
			} else if ($this->mode == static::CLIENT_USERS_MODE_ROOT_CHILD) {
				$sql_where .= " AND ac_u.id IS NULL";
			} else {
				$sql_where .= " AND ac_u.id = ".$_SESSION['USER_ID'];
			}
			
			if ($this->mode == static::CLIENT_USERS_MODE_PARENT_PARENT) {
				$sql_where .= " AND acu_u.id = ".$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'];
			} else if ($this->mode == static::CLIENT_USERS_MODE_USER_CHILD || $this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD) {
				$sql_where .= " AND acu_u.parent_id = ".$_SESSION['USER_ID'];
			} else if ($this->mode == static::CLIENT_USERS_MODE_PARENT_USER || $this->mode == static::CLIENT_USERS_MODE_USER_USER) {
				$sql_where .= " AND acu_u.id = ".$_SESSION['USER_ID'];
			}
			
			// There could be CMS-administered user-parent changes
			if ($this->mode == static::CLIENT_USERS_MODE_PARENT_USER || $this->mode == static::CLIENT_USERS_MODE_PARENT_CHILD) {
				$sql_where .= " AND acu_u.parent_id = ac_u.id";
			} else if ($this->mode == static::CLIENT_USERS_MODE_ROOT_CHILD) {
				$sql_where .= " AND acu_u.parent_id = 0";
			} else {
				$sql_where .= " AND acu_u.id = ac_u.id";
			}
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);

			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{

				$arr_data = [];
				
				$arr_data['id'] = 'x:'.static::class.':client_user_id-'.$arr_row['client_id'].'_'.$arr_row['user_id'];
				$arr_data['class'] = 'popup';
				$arr_data['attr']['data-method'] = 'view_client_user';
				
				$arr_data[] = '<span class="icon" data-category="status">'.getIcon(($arr_row['enabled'] ? 'tick' : 'min')).'</span>';
				if ($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD) {
					$arr_data[] = $arr_row['client_user_name'];
				}
				$arr_data[] = $arr_row['client_name'];
				if ($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD || $this->mode == static::CLIENT_USERS_MODE_PARENT_CHILD || $this->mode == static::CLIENT_USERS_MODE_ROOT_CHILD) {
					$arr_data[] = $arr_row['user_name'];
				}
				$arr_data[] = $arr_row['token'];
				$arr_data[] = date('d-m-Y H:i:s', strtotime($arr_row['date']));
				$arr_data[] = ($arr_row['date_valid'] ? date('d-m-Y H:i:s', strtotime($arr_row['date_valid'])) : '∞');
				$arr_data[] = '<input type="button" class="data edit edit_client_user" value="edit" /><input type="button" class="data del msg del_client_user" value="del" /><input class="multi" value="'.$arr_row['client_id'].'_'.$arr_row['user_id'].'" type="checkbox" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
	
		// QUERY
		
		if ($method == "configuration") {
			
			$this->setConfig();
			
			$this->processFormConfiguration();
			
			$str_html_form = $this->contentsForm();
		
			if ($str_html_form) {
				$this->html = $str_html_form;
			}
			$this->message = true;
		}
		
		if (($method == "insert_client" || $method == "update_client") && $this->is_discard) {
			
			$this->setConfig();
							
			$this->html = $this->createAddClient();
			return;
		}
		
		if ($method == "insert_client") {
			
			$this->setConfig();
			
			$arr_client = $_POST;
			
			if ($this->mode == static::CLIENT_USERS_MODE_PARENT_PARENT || $this->mode == static::CLIENT_USERS_MODE_PARENT_USER || $this->mode == static::CLIENT_USERS_MODE_PARENT_CHILD) {
				$arr_client['user_id'] = $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'];
			} else if ($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD) {
				if (!$arr_client['user_id']) {
					error(getLabel('msg_missing_information'));
				}
				$this->checkValidUserId($arr_client['user_id']);
			} else if ($this->mode == static::CLIENT_USERS_MODE_ROOT_CHILD) {
				$arr_client['user_id'] = 0;
			} else {
				$arr_client['user_id'] = $_SESSION['USER_ID'];
			}
			
			if (!$arr_client['name']) {
				error(getLabel('msg_missing_information'));
			}
			
			$arr_client['api_id'] = $this->arr_api['id'];
			
			apis::handleClient(false, $arr_client['enabled'], $arr_client);
			
			$this->reset_form = true;
			$this->refresh_table = true;
			$this->message = true;
		}
		
		if ($method == "update_client" && $id) {
			
			$this->setConfig();
			
			$this->checkValidClientId($id);
			
			$arr_client = $_POST;
			
			if ($this->mode == static::CLIENT_USERS_MODE_PARENT_PARENT || $this->mode == static::CLIENT_USERS_MODE_PARENT_USER || $this->mode == static::CLIENT_USERS_MODE_PARENT_CHILD) {
				$arr_client['user_id'] = $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'];
			} else if ($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD) {
				if (!$arr_client['user_id']) {
					error(getLabel('msg_missing_information'));
				}
				$this->checkValidUserId($arr_client['user_id']);
			} else if ($this->mode == static::CLIENT_USERS_MODE_ROOT_CHILD) {
				$arr_client['user_id'] = 0;
			} else {
				$arr_client['user_id'] = $_SESSION['USER_ID'];
			}

			if (!$arr_client['name']) {
				error(getLabel('msg_missing_information'));
			}
			
			apis::handleClient($id, $arr_client['enabled'], $arr_client, $arr_client['regenerate']);

			$this->html = $this->createAddClient();
			$this->refresh_table = true;
			$this->message = true;
		}
		
		if ($method == "del_client" && $id) {
			
			$this->setConfig();
			
			$this->checkValidClientId($id);
			
			apis::delClients($id);
			
			$this->message = true;
		}
		
		if (($method == "insert_client_user" || $method == "update_client_user") && $this->is_discard) {
							
			$this->html = '';
			return;
		}
		
		if ($method == "insert_client_user" && $id) {
			
			$this->setConfig();
							
			$client_id = $id;
			
			$this->checkValidClientId($client_id);
					
			$arr_client_user = $_POST;

			if ($this->mode == static::CLIENT_USERS_MODE_PARENT_PARENT) {
				$arr_client_user['user_id'] = $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'];
			} else if (variableHasValue($this->mode, static::CLIENT_USERS_MODE_CHILD_CHILD, static::CLIENT_USERS_MODE_PARENT_CHILD, static::CLIENT_USERS_MODE_ROOT_CHILD, static::CLIENT_USERS_MODE_USER_CHILD)) {
				if (!$arr_client_user['user_id']) {
					error(getLabel('msg_missing_information'));
				}
				$this->checkValidUserId(false, $arr_client_user['user_id']);
			} else {
				$arr_client_user['user_id'] = $_SESSION['USER_ID'];
			}

			apis::handleClientUser($client_id, false, $arr_client_user['enabled'], $arr_client_user);
			
			$this->reset_form = true;
			$this->refresh_table = true;
			$this->message = true;
		}
		
		if ($method == "update_client_user" && $id) {
			
			$this->setConfig();
			
			$arr_id = explode('_', $id);
			$client_id = $arr_id[0];
			$user_id = $arr_id[1];
			
			$this->checkValidClientId($client_id);
			$this->checkValidUserId(false, $user_id);
			
			$arr_client_user = $_POST;
			
			if ($this->mode == static::CLIENT_USERS_MODE_PARENT_PARENT) {
				$arr_client_user['user_id'] = $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'];
			} else if (variableHasValue($this->mode, static::CLIENT_USERS_MODE_CHILD_CHILD, static::CLIENT_USERS_MODE_PARENT_CHILD, static::CLIENT_USERS_MODE_ROOT_CHILD, static::CLIENT_USERS_MODE_USER_CHILD)) {
				if (!$arr_client_user['user_id']) {
					error(getLabel('msg_missing_information'));
				}
				$this->checkValidUserId(false, $arr_client_user['user_id']);
			} else {
				$arr_client_user['user_id'] = $_SESSION['USER_ID'];
			}
						
			apis::handleClientUser($client_id, $user_id, $arr_client_user['enabled'], $arr_client_user, $arr_client_user['regenerate']);
			
			$this->html = '';
			$this->refresh_table = true;
			$this->message = true;
		}
		
		if ($method == "del_client_user" && $id) {
			
			$this->setConfig();
			
			$arr_ids = [];
			$arr_client_ids = [];
			$arr_user_ids = [];
			if (is_array($id)) {
				foreach ($id as $cur_id) {
					$arr_id = explode('_', $cur_id);
					$arr_client_ids[] = $arr_id[0];
					$arr_user_ids[] = $arr_id[1];
					$arr_ids[] = [$arr_id[0], $arr_id[1]];
				}
			} else {
				$arr_id = explode('_', $id);
				$arr_client_ids[] = $arr_id[0];
				$arr_user_ids[] = $arr_id[1];
				$arr_ids[] = [$arr_id[0], $arr_id[1]];
			}
			
			$this->checkValidClientId($arr_client_ids);
			$this->checkValidUserId(false, $arr_user_ids);
			
			apis::delClientUsers($arr_ids);
			
			$this->message = true;
		}
	}
	
	protected function setConfig() {
		
		$this->arr_api = apis::getAPIs($this->arr_variables['api_id']);

		if (!$this->arr_api['clients_user_group_id']) {
			if ($this->arr_api['client_users_user_group_id']) {
				$this->mode = static::CLIENT_USERS_MODE_ROOT_CHILD;
			} else {
				$this->mode = static::CLIENT_USERS_MODE_USER_USER;
			}
		} else if ($_SESSION['CUR_USER'][DB::getTableName('VIEW_USER_PARENT')]['parent_group_id'] == $this->arr_api['clients_user_group_id']) {
			if ($this->arr_api['client_users_user_group_id'] && $_SESSION['CUR_USER'][DB::getTableName('VIEW_USER_PARENT')]['parent_group_id'] == $this->arr_api['client_users_user_group_id']) {
				$this->mode = static::CLIENT_USERS_MODE_PARENT_PARENT;
			} else {
				$this->mode = static::CLIENT_USERS_MODE_PARENT_CHILD;
			}
			//$this->mode = static::CLIENT_USERS_MODE_PARENT_USER;
		} else if ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['group_id'] == $this->arr_api['clients_user_group_id']) {
			if ($this->arr_api['client_users_user_group_id'] && $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['group_id'] != $this->arr_api['client_users_user_group_id']) {
				$this->mode = static::CLIENT_USERS_MODE_USER_CHILD;
			} else {
				$this->mode = static::CLIENT_USERS_MODE_USER_USER;
			}
		} else {
			$this->mode = static::CLIENT_USERS_MODE_USER_USER;
		}
		//$this->mode = static::CLIENT_USERS_MODE_CHILD_CHILD;
	}
	
	protected function checkValidClientId($client_id) {
		
		$client_id = DBFunctions::arrEscape($client_id);
			
		$sql_in = "'".(is_array($client_id) ? implode("','", $client_id) : $client_id)."'";
		
		if (variableHasValue($this->mode, static::CLIENT_USERS_MODE_PARENT_PARENT, static::CLIENT_USERS_MODE_PARENT_USER, static::CLIENT_USERS_MODE_PARENT_CHILD)) {
			$sql_user = "ac_u.id != ".$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'];
		} else if ($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD) {
			$sql_user = "ac_u.parent_id != ".$_SESSION['USER_ID'];
		} else if ($this->mode == static::CLIENT_USERS_MODE_ROOT_CHILD) {
			$sql_user = "ac_u.id IS NOT NULL";
		} else {
			$sql_user = "ac_u.id != ".$_SESSION['USER_ID'];
		}

		$res_check = DB::query("SELECT ac.id
				FROM ".DB::getTable('SITE_API_CLIENTS')." ac
				LEFT JOIN ".DB::getTable('TABLE_USERS')." ac_u ON (ac_u.id = ac.user_id)
			WHERE ac.id IN (".$sql_in.")
				AND (
					ac.api_id != ".(int)$this->arr_api['id']."
					OR
					".$sql_user."
				)
		");
		
		if ($res_check->getRowCount()) {
			error(getLabel('msg_not_allowed'));
		}	
	}
	
	protected function checkValidUserId($client_user_id, $client_user_user_id) {
		
		if ($client_user_id) {
			
			if (variableHasValue($this->mode, static::CLIENT_USERS_MODE_PARENT_PARENT, static::CLIENT_USERS_MODE_PARENT_USER, static::CLIENT_USERS_MODE_PARENT_CHILD)) {
				$is_valid = ($client_user_id == $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id']);
			} else if ($this->mode == static::CLIENT_USERS_MODE_ROOT_CHILD) {
				$is_valid = ($client_user_id == 0);
			} else if ($this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD) {
				$is_valid = user_management::checkUserIds($client_user_id, $_SESSION['USER_ID'], 'parent');
			} else {
				$is_valid = ($client_user_id == $_SESSION['USER_ID']);
			}
			
			if (!$is_valid) {
				error(getLabel('msg_not_allowed'));
			}
		} else {
			
			foreach ((array)$client_user_user_id as $user_id) {
				
				if ($this->mode == static::CLIENT_USERS_MODE_PARENT_PARENT) {
					$is_valid = ($user_id == $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id']);
				} else if ($this->mode == static::CLIENT_USERS_MODE_PARENT_CHILD) {
					$is_valid = user_management::checkUserIds($user_id, $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'], 'parent');
				} else if ($this->mode == static::CLIENT_USERS_MODE_ROOT_CHILD) {
					$is_valid = user_management::checkUserIds($user_id, 0, 'parent');
				} else if ($this->mode == static::CLIENT_USERS_MODE_USER_CHILD || $this->mode == static::CLIENT_USERS_MODE_CHILD_CHILD) {
					$is_valid = user_management::checkUserIds($user_id, $_SESSION['USER_ID'], 'parent');
				} else {
					$is_valid = ($user_id == $_SESSION['USER_ID']);
				}
				
				if (!$is_valid) {
					error(getLabel('msg_not_allowed'));
				}
			}
		}
	}
}
