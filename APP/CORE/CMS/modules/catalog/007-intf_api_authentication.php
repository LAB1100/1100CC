<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class intf_api_authentication extends apis {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_api').' '.getLabel('lbl_authentication');
		static::$parent_label = '';
	}

	public function contents() {
		
		$return = '<div class="section"><h1>'.self::$label.'</h1>
		<div>';
		
			$arr_apis = self::getAPIs();

			if (!$arr_apis) {

				Labels::setVariable('name', getLabel('lbl_apis'));
				
				$return .= '<section class="info">'.getLabel('msg_no', 'L', true).'</section>';
			} else {
				
				$return .= '<div class="tabs">
					<ul>
						<li><a href="#">'.getLabel('lbl_api_clients').'</a><span><input type="button" id="y:intf_api_authentication:add_client-0" class="data add popup" value="add" /></span></li>
						<li><a href="#">'.getLabel('lbl_users').'</a></li>
					</ul>
					
					<div>
					
						<form class="options filter">
							<label>'.getLabel('lbl_filter').':</label><div class="input">'.cms_general::createSelector($arr_apis, 'apis', 'all').'</div>
						</form>
						<table class="display" id="d:intf_api_authentication:data_clients-0">
							<thead> 
								<tr>
									<th title="'.getLabel('lbl_enabled').'"><span>E</span></th>
									<th data-sort="asc-0"><span>'.getLabel('lbl_api').'</span></th>
									<th><span>'.getLabel('lbl_user').'</span></th>
									<th class="max"><span>'.getLabel('lbl_name').'</span></th>
									<th class="limit"><span>'.getLabel('lbl_identifier').'</span></th>
									<th class="limit"><span>'.getLabel('lbl_passkey').' (secret)</span></th>
									<th class="disable-sort"><span>'.getLabel('lbl_users').'</span></th>
									<th class="disable-sort menu" id="x:intf_api_authentication:client_id-0" title="'.getLabel('lbl_multi_select').'">'
										.'<input type="button" class="data del msg del_client" value="del" />'
										.'<input type="checkbox" class="multi all" value="" />'
									.'</th>
								</tr> 
							</thead>
							<tbody>
								<tr>
									<td colspan="8" class="empty">'.getLabel('msg_loading_server_data').'</td>
								</tr>
							</tbody>
						</table>
						
					</div><div>
					
						<form class="options filter">
							<label>'.getLabel('lbl_filter').':</label><div class="input">'.cms_general::createSelector($arr_apis, 'apis', 'all').'</div>
						</form>
						<table class="display" id="d:intf_api_authentication:data_client_users-0">
							<thead> 
								<tr>
									<th title="'.getLabel('lbl_enabled').'"><span>E</span></th>
									<th data-sort="asc-0"><span>'.getLabel('lbl_api').'</span></th>
									<th><span>'.getLabel('lbl_api_client').' - '.getLabel('lbl_user').'</span></th>
									<th class="max"><span>'.getLabel('lbl_api_client').' - '.getLabel('lbl_name').'</span></th>
									<th><span>'.getLabel('lbl_user').'</span></th>
									<th class="limit"><span>'.getLabel('lbl_passkey').' (token)</span></th>
									<th class="limit"><span>'.getLabel('lbl_date_start').'</span></th>
									<th class="limit"><span>'.getLabel('lbl_date_end').'</span></th>
									<th class="disable-sort menu" id="x:intf_api_authentication:client_user_id-0" title="'.getLabel('lbl_multi_select').'">'
										.'<input type="button" class="data del msg del_client_user" value="del" />'
										.'<input type="checkbox" class="multi all" value="" />'
									.'</th>
								</tr> 
							</thead>
							<tbody>
								<tr>
									<td colspan="9" class="empty">'.getLabel('msg_loading_server_data').'</td>
								</tr>
							</tbody>
						</table>
						
					</div>
				</div>';
			}
			
		$return .= '</div></div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '#frm-api_client input[name*=time_amount],
					#frm-api_client_user input[name*=time_amount] { width: 40px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-intf_api_authentication', function(elm_scripter) {
		
			elm_scripter.on('change', 'form input', function() {
			
				var elm_form = $(this).closest('form');
				var elm_target = elm_form.closest('div').find('table[id^=d\\\:intf_api_authentication\\\:data_client]');
				
				COMMANDS.setData(elm_target[0], {filter: serializeArrayByName(elm_form)});
				
				elm_target.dataTable('refresh');
			});
		});
		
		SCRIPTER.dynamic('#frm-api_client', function(elm_scripter) {
			
			elm_scripter.on('change', '[name=api_id]', function() {
			
				var elm_target = elm_scripter.find('[id^=y\\\:intf_api_authentication\\\:lookup_user_client]');
				elm_target.autocomplete('clear');
				
				COMMANDS.setData(elm_target[0], this.value);
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "edit_client" || $method == "add_client") {
		
			if ($id) {		
						
				$arr_client = self::getClient($id);
				
				$mode = "update_client";
			} else {
				$mode = "insert_client";
			}
			
			$arr_apis = self::getAPIs();
			$api_id = ($arr_client['api_id'] ?: key($arr_apis));
			
			$return = '<form id="frm-api_client" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label></label>
						<div>'.cms_general::createSelectorRadio([['id' => '1', 'name' => getLabel('lbl_active')], ['id' => '0', 'name' => getLabel('lbl_inactive')]], 'enabled', ($mode == 'insert_client' || $arr_client['enabled'])).'</div>
					</li>
				</ul>
				<hr />
				<ul>
					<li>
						<label>'.getLabel('lbl_api').'</label>
						<div><select name="api_id">'.cms_general::createDropdown($arr_apis, $api_id, false).'</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_user').'</label>
						<div><input type="hidden" name="user_id" value="'.$arr_client['user_id'].'" /><input type="text" id="y:intf_api_authentication:lookup_user_client-'.$api_id.'" class="autocomplete" value="'.$arr_client['user_name'].'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($arr_client['name']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_request_limit').'</label>
						<div>'.cms_general::createSelectorRadio([['id' => '0', 'name' => getLabel('lbl_yes')], ['id' => '1', 'name' => getLabel('lbl_no')]], 'request_limit_disable', ($arr_client['request_limit_disable'] ? '1' : '0')).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_valid_period').'</label>
						<div><input type="number" name="time_amount" value="'.(int)$arr_client['time_amount'].'"><select name="time_unit">'.cms_general::createDropdown(cms_general::getTimeUnits(), $arr_client['time_unit'], true).'</select></div>
					</li>';
					if ($mode == 'update_client') {
						$return .= '</ul>
						<hr />
						<ul>
							<li>
								<label>'.getLabel('lbl_passkey_regenerate').' (secret)</label>
								<div><input type="checkbox" name="regenerate" value="1" /></div>
							</li>';
					}
				$return .= '</ul></fieldset>
			</form>';
			
			$this->html = $return;
			
			$this->validate = ['name' => 'required'];
		}
		
		if (($method == "edit_client_user" || $method == "add_client_user") && $id) {
		
			if ($method == "edit_client_user") {
				
				$arr_id = explode('_', $id);
				$client_id = $arr_id[0];
				$user_id = $arr_id[1];
						
				$arr_client_user = self::getClientUser($client_id, $user_id);
				
				$mode = "update_client_user";
			} else {
				
				$client_id = $id;
				
				$mode = "insert_client_user";
			}
			
			$arr_client = self::getClient($client_id);
			
			$return = '<form id="frm-api_client_user" data-method="'.$mode.'">
			
				<fieldset><ul>
					<li>
						<label></label>
						<div>'.cms_general::createSelectorRadio([['id' => '1', 'name' => getLabel('lbl_active')], ['id' => '0', 'name' => getLabel('lbl_inactive')]], 'enabled', ($mode == 'insert_client_user' || $arr_client_user['enabled'])).'</div>
					</li>
				</ul></fieldset>
				
				<hr />
			
				<div class="record"><dl>
					<li>
						<dt>'.getLabel('lbl_api').'</dt>
						<dd>'.strEscapeHTML($arr_client['api_name']).'</dd>
					</li>
					<li>
						<dt>'.getLabel('lbl_api_client').' - '.getLabel('lbl_user').'</dt>
						<dd>'.strEscapeHTML($arr_client['user_name']).'</dd>
					</li>
					<li>
						<dt>'.getLabel('lbl_api_client').' - '.getLabel('lbl_name').'</dt>
						<dd>'.strEscapeHTML($arr_client['name']).'</dd>
					</li>
				</dl></div>
				
				<hr />
			
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_user').'</label>
						<div><input type="hidden" name="user_id" value="'.$arr_client_user['user_id'].'" /><input type="text" id="y:intf_api_authentication:lookup_user_client_user-'.$arr_client['api_id'].'" class="autocomplete" value="'.$arr_client_user['user_name'].'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_valid_period').'</label>
						<div>';
							if ($mode == 'update_client_user' && $arr_client_user['date_valid']) {
								$return .= cms_general::createDefineDate($arr_client_user['date_valid'], 'date_valid', false);
							} else {
								$return .= '<input type="number" name="time_amount" value="'.(int)$arr_client['time_amount'].'"><select name="time_unit">'.cms_general::createDropdown(cms_general::getTimeUnits(), $arr_client['time_unit'], true).'</select>';
							}
						$return .= '</div>
					</li>';
					if ($mode == 'update_client_user') {
						$return .= '</ul>
						<hr />
						<ul>
							<li>
								<label>'.getLabel('lbl_passkey_regenerate').' (token)</label>
								<div><input type="checkbox" name="regenerate" value="1" /></div>
							</li>';
					}
				$return .= '</ul></fieldset>
			</form>';
			
			$this->html = $return;
			
			$this->validate = ['name' => 'required'];
		}
		
		if ($method == "view_client") {
		
			$arr_client = self::getClient($id);		
			
			if ($arr_client['time_amount']) {
				
				$arr_time_unit = cms_general::getTimeUnits($arr_client['time_unit']);
				$str_time_amount = $arr_client['time_amount'].' '.$arr_time_unit['name'];	
			} else {
				$str_time_amount = '∞';	
			}	
			
			$this->html = '<div class="record"><dl>
				<li>
					<dt>'.getLabel('lbl_api').'</dt>
					<dd>'.strEscapeHTML($arr_client['api_name']).'</dd>
				</li>
				<li>
					<dt>'.getLabel('lbl_user').'</dt>
					<dd>'.strEscapeHTML($arr_client['user_name']).'</dd>
				</li>
				<li>
					<dt>'.getLabel('lbl_name').'</dt>
					<dd>'.strEscapeHTML($arr_client['name']).'</dd>
				</li>
				<li>
					<dt>'.getLabel('lbl_request_limit').'</dt>
					<dd>'.($arr_client['request_limit_disable'] ? getLabel('lbl_no') : getLabel('lbl_yes')).'</dd>
				</li>
				<li>
					<dt>'.getLabel('lbl_valid_period').'</dt>
					<dd>'.$str_time_amount.'</dd>
				</li>
			</dl>
			<hr />
			<dl>
				<li>
					<dt>'.getLabel('lbl_identifier').'</dt>
					<dd><pre>'.strEscapeHTML($arr_client['id']).'</pre></dd>
				</li>
				<li>
					<dt>'.getLabel('lbl_passkey').' (secret)</dt>
					<dd><pre>'.strEscapeHTML($arr_client['secret']).'</pre></dd>
				</li>
			</dl></div>';
		}
		
		if ($method == "view_client_user") {
			
			$arr_id = explode('_', $id);
		
			$arr_client_user = self::getClientUser($arr_id[0], $arr_id[1]);				
			
			$this->html = '<div class="record"><dl>
				<li>
					<dt>'.getLabel('lbl_api').'</dt>
					<dd>'.strEscapeHTML($arr_client_user['api_name']).'</dd>
				</li>
				<li>
					<dt>'.getLabel('lbl_api_client').' - '.getLabel('lbl_user').'</dt>
					<dd>'.strEscapeHTML($arr_client_user['client_user_name']).'</dd>
				</li>
				<li>
					<dt>'.getLabel('lbl_api_client').' - '.getLabel('lbl_name').'</dt>
					<dd>'.strEscapeHTML($arr_client_user['client_name']).'</dd>
				</li>
			</dl>
			<hr />
			<dl>
				<li>
					<dt>'.getLabel('lbl_user').'</dt>
					<dd>'.strEscapeHTML($arr_client_user['user_name']).'</dd>
				</li>
				<li>
					<dt>'.getLabel('lbl_date_start').'</dt>
					<dd>'.date('d-m-Y H:i:s', strtotime($arr_client_user['date'])).'</dd>
				</li>
				<li>
					<dt>'.getLabel('lbl_date_end').'</dt>
					<dd>'.($arr_client_user['date_valid'] ? date('d-m-Y H:i:s', strtotime($arr_client_user['date_valid'])) : '∞').'</dd>
				</li>
			</dl>
			<hr />
			<dl>
				<li>
					<dt>'.getLabel('lbl_passkey').' (token)</dt>
					<dd><pre>'.strEscapeHTML($arr_client_user['token']).'</pre></dd>
				</li>
			</dl></div>';
		}
		
		// POPUP INTERACTION
		
		if ($method == "lookup_user_client" || $method == "lookup_user_client_user") {
			
			if (is_array($value)) {
				$api_id = $value[0];
				$name = $value['value_element'];
			} else {
				$api_id = $id;
				$name = $value;
			}
			
			$arr_api = self::getAPIs($api_id);
			$arr_settings = [];
			$arr_users = [];
			
			if ($method == 'lookup_user_client') {
				
				if ($arr_api['clients_user_group_id'] || (!$arr_api['clients_user_group_id'] && !$arr_api['client_users_user_group_id'])) {
					
					if ($arr_api['clients_user_group_id']) {
						$arr_settings['group_id'] = $arr_api['clients_user_group_id'];
					}
					
					$arr_users = user_management::filterUsers($name, $arr_settings);
				}
			} else {
				
				if ($arr_api['client_users_user_group_id']) { // Use specified user group
					
					$arr_settings['group_id'] = $arr_api['client_users_user_group_id'];
				} else if ($arr_api['clients_user_group_id']) { // Use user group descendants
					
					$arr_user_group_children = user_groups::getUserGroups(0, $arr_api['clients_user_group_id']);
					
					if ($arr_user_group_children) {
						$arr_settings['group_id'] = array_keys($arr_user_group_children);
					}
				}
				
				$arr_users = user_management::filterUsers($name, $arr_settings);
			}

			$arr = [];
			foreach ($arr_users as $row) {
				$arr[] = ['id' => $row['id'], 'label' => $row['name'], 'value' => $row['name']];
			}

			$this->html = $arr;
		}
		
		// DATA
		
		if ($method == "data_clients") {

			$arr_filter = ($value['filter'] ?: []);
			
			$arr_sql_columns = ['ac.enabled', 'a.name', 'ac_u.name', 'ac.name', 'ac.id', 'ac.secret'];	
			$arr_sql_columns_search = ['', 'a.name', 'ac_u.name', 'ac.name', 'ac.id', 'ac.secret'];
			$arr_sql_columns_as = ['ac.enabled', 'a.name AS api_name', 'ac_u.name AS user_name', 'ac.name', 'ac.id', 'ac.secret'];
			
			$sql_table = DB::getTable('SITE_API_CLIENTS')." ac
				JOIN ".DB::getTable('SITE_APIS')." a ON (a.id = ac.api_id)
				LEFT JOIN ".DB::getTable('TABLE_USERS')." ac_u ON (ac_u.id = ac.user_id)
			";

			$sql_index = 'ac.id';
			
			$sql_where = "(ac.user_id = 0 OR ac_u.id IS NOT NULL)";
						
			if ($arr_filter['apis']) {
				$sql_where .= " AND ac.api_id IN (".implode(',', $arr_filter['apis']).")";
			}
								 
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);

			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{

				$arr_data = [];
				
				$arr_data['id'] = 'x:intf_api_authentication:client_id-'.$arr_row['id']."";
				$arr_data['class'] = 'popup';
				$arr_data['attr']['data-method'] = 'view_client';
				
				$arr_data[] = '<span class="icon" data-category="status">'.getIcon(($arr_row['enabled'] ? 'tick' : 'min')).'</span>';
				$arr_data[] = $arr_row['api_name'];
				$arr_data[] = $arr_row['user_name'];
				$arr_data[] = $arr_row['name'];
				$arr_data[] = $arr_row['id'];
				$arr_data[] = $arr_row['secret'];
				$arr_data[] = '<input type="button" class="data add popup add_client_user" value="add" />';
				$arr_data[] = '<input type="button" class="data edit popup edit_client" value="edit" /><input type="button" class="data del msg del_client" value="del" /><input class="multi" value="'.$arr_row['id'].'" type="checkbox" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
		
		if ($method == "data_client_users") {
			
			$arr_filter = ($value['filter'] ?: []);

			$arr_sql_columns = ['acu.enabled', 'a.name', 'ac_u.name', 'ac.name', 'acu_u.name', 'acu.token', 'acu.date', 'acu.date_valid'];
			$arr_sql_columns_search = ['', 'a.name', 'ac_u.name', 'ac.name', 'acu_u.name', 'acu.token', 'acu.date', 'acu.date_valid', 'ac.id'];
			$arr_sql_columns_as = ['acu.enabled', 'a.name AS api_name', 'ac_u.name AS client_user_name', 'ac.name AS client_name', 'acu_u.name AS user_name', 'acu.token', 'acu.date', 'acu.date_valid', 'acu.client_id', 'acu.user_id'];
						
			$sql_table = DB::getTable('SITE_API_CLIENT_USERS')." acu
				JOIN ".DB::getTable('SITE_API_CLIENTS')." ac ON (ac.id = acu.client_id)
				JOIN ".DB::getTable('SITE_APIS')." a ON (a.id = ac.api_id)
				LEFT JOIN ".DB::getTable('TABLE_USERS')." ac_u ON (ac_u.id = ac.user_id)
				JOIN ".DB::getTable('TABLE_USERS')." acu_u ON (acu_u.id = acu.user_id)
			";

			$sql_index = 'acu.client_id, acu.user_id';
			
			$sql_where = "(ac.user_id = 0 OR ac_u.id IS NOT NULL)";
			
			if ($arr_filter['apis']) {
				$sql_where .= " AND ac.api_id IN (".implode(',', $arr_filter['apis']).")";
			}
								 
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);

			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
							
				$arr_data = [];
				
				$arr_data['id'] = 'x:intf_api_authentication:client_user_id-'.$arr_row['client_id'].'_'.$arr_row['user_id'];
				$arr_data['class'] = 'popup';
				$arr_data['attr']['data-method'] = 'view_client_user';
				
				$arr_data[] = '<span class="icon" data-category="status">'.getIcon(($arr_row['enabled'] ? 'tick' : 'min')).'</span>';
				$arr_data[] = $arr_row['api_name'];
				$arr_data[] = $arr_row['client_user_name'];
				$arr_data[] = $arr_row['client_name'];
				$arr_data[] = $arr_row['user_name'];
				$arr_data[] = $arr_row['token'];
				$arr_data[] = date('d-m-Y H:i:s', strtotime($arr_row['date']));
				$arr_data[] = ($arr_row['date_valid'] ? date('d-m-Y H:i:s', strtotime($arr_row['date_valid'])) : '∞');
				$arr_data[] = '<input type="button" class="data edit popup edit_client_user" value="edit" /><input type="button" class="data del msg del_client_user" value="del" /><input class="multi" value="'.$arr_row['client_id'].'_'.$arr_row['user_id'].'" type="checkbox" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
							
		// QUERY
		
		if ($method == "insert_client" || ($method == "update_client" && $id)) {
						
			if (!$_POST['api_id'] || !$_POST['name']) {
				error(getLabel('msg_missing_information'));
			}
			
			$arr_api = self::getAPIs($_POST['api_id']);
			
			if ($arr_api['clients_user_group_id'] || (!$arr_api['clients_user_group_id'] && !$arr_api['client_users_user_group_id'])) {
				if (!$_POST['user_id']) {
					error(getLabel('msg_missing_information'));
				}
			}
		}
		
		if ($method == "insert_client") {
						
			self::handleClient(false, $_POST['enabled'], $_POST);

			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "update_client" && $id) {
			
			self::handleClient($id, $_POST['enabled'], $_POST, $_POST['regenerate']);
			
			$this->refresh_table = true;
			$this->msg = true;
		}

		if ($method == "del_client" && $id) {
			
			self::delClients($id);

			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "insert_client_user" && $id) {
							
			$client_id = $id;
			
			if (!$_POST['user_id']) {
				error(getLabel('msg_missing_information'));
			}
			
			self::handleClientUser($client_id, false, $_POST['enabled'], $_POST);

			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "update_client_user" && $id) {
			
			$arr_id = explode('_', $id);
			$client_id = $arr_id[0];
			$user_id = $arr_id[1];
						
			self::handleClientUser($client_id, $user_id, $_POST['enabled'], $_POST, $_POST['regenerate']);
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del_client_user" && $id) {
			
			$arr_ids = [];
			if (is_array($id)) {
				foreach ($id as $cur_id) {
					$arr_id = explode('_', $cur_id);
					$arr_ids[] = [$arr_id[0], $arr_id[1]];
				}
			} else {
				$arr_id = explode('_', $id);
				$arr_ids[] = [$arr_id[0], $arr_id[1]];
			}
			
			self::delClientUsers($arr_ids);
			
			$this->refresh_table = true;
			$this->msg = true;
		}
	}
}
