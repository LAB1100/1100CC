<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class intf_apis extends apis {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_apis');
		static::$parent_label = '';
	}
	
	public function contents() {
		
		$arr_hosts = cms_details::getSiteDetailsHosts();
	
		$return .= '<div class="section"><h1>'.self::$label.'</h1>
		<div class="apis">';
		
			$return .= '<div class="tabs">
				<ul>
					<li><a href="#">Hosts</a></li>
					<li><a href="#">APIs</a><span><input type="button" id="y:intf_apis:add_api-0" class="data add popup" value="add" /></span></li>
				</ul>
				
				<div>
				
					'.$this->contentTabHosts().'
					
				</div><div>
				
					'.$this->contentTabAPIs().'
					
				</div>
			</div>';
				
		$return .= '</div></div>';
		
		return $return;
	}
	
	private function contentTabHosts() {
		
		$arr_hosts = cms_details::getSiteDetailsHosts();
		
		if (!$arr_hosts) {
			
			Labels::setVariable('name', getLabel('lbl_server_hosts'));
			$msg = getLabel('msg_no', 'L', true);
			
			$return .= '<section class="info">'.Labels::printLabels(Labels::parseTextVariables($msg)).'</section>';
		} else {
			
			$arr_apis = self::getAPIs();
			$arr_apis_hosts = self::getAPIHosts();

			$return .= '<form id="f:intf_apis:host_api-0">
				<table class="list">
					<thead>
						<tr>
							<th><span>Host</span></th>
							<th><span>API</span></th>
						</tr>
					</thead>
					<tbody>';
						foreach ($arr_hosts as $arr_host) {
							
							$return .= '<tr>
								<td>'.$arr_host['name'].'</td>
								<td><select name="host_name['.rawurlencode($arr_host['name']).']">'.cms_general::createDropdown($arr_apis, $arr_apis_hosts[$arr_host['name']], true).'</select></td>	
							</tr>';
						}
					$return .= '</tbody>
				</table>
			</form>';
		}

		return $return;
	}
	
	private function contentTabAPIs() {
					
		$arr_apis = self::getAPIs();
		
		if (!$arr_apis) {
			
			Labels::setVariable('name', getLabel('lbl_apis'));
			$msg = getLabel('msg_no', 'L', true);
			
			$return .= '<section class="info">'.Labels::printLabels(Labels::parseTextVariables($msg)).'</section>';
		} else {
		
			$return .= '<table class="list">
				<thead>
					<tr>
						<th class="max"><span>'.getLabel('lbl_name').'</span></th>
						<th><span>'.getLabel('lbl_limit').' - IP</span></th>
						<th><span>'.getLabel('lbl_limit').' - Global</span></th>
						<th><span>'.getLabel('lbl_documentation').'</span></th>
						<th></th>
					</tr>
				</thead>
				<tbody>';
				
					foreach ($arr_apis as $api_id => $arr_api) {
						
						$arr_unit = cms_general::getTimeUnits($arr_api['request_limit_unit']);
						$str_limit_time = $arr_api['request_limit_amount'].' '.$arr_unit['name'];
							
						$return .= '<tr id="x:intf_apis:api_id-'.$api_id.'">
							<td>'.$arr_api['name'].'</td>
							<td>'.$arr_api['request_limit_ip'].' / '.$str_limit_time.'</td>
							<td>'.$arr_api['request_limit_global'].' / '.$str_limit_time.'</td>
							<td>'.($arr_api['documentation_url'] ? '<a href="'.strEscapeHTML($arr_api['documentation_url']).'" target="_blank">'.strEscapeHTML($arr_api['documentation_url']).'</a>' : '<span class="icon" data-category="status">'.getIcon('min').'</span>').'</td>
							<td><input type="button" class="data edit popup edit_api" value="edit" /><input type="button" class="data del msg del_api" value="del" /></td>
						</tr>';
					}
				$return .= '</tbody>
			</table>';
		}
		
		return $return;
	}
	
	public static function css() {
	
		$return = '';
		
		return $return;
	}
	
	public static function js() {

		$return = "SCRIPTER.static('#mod-intf_apis', function(elm_scripter) {
		
			elm_scripter.on('change', '[id=f\\\:intf_apis\\\:host_api-0] [name^=host_name]', function() {
				$(this).formCommand();		
			});
		});
		
		SCRIPTER.dynamic('#frm-api', function(elm_scripter) {
			
			elm_scripter.on('change', '[id=y\\\:intf_apis\\\:lookup_user_groups-0]', function() {
				var elm_target = elm_scripter.find('[name=client_users_user_group_id]');
				$(this).quickCommand(elm_target);		
			});
		});
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// INTERACT
		
		if ($method == "host_api") {
			
			$res = DB::query("DELETE FROM ".DB::getTable('SITE_API_HOSTS'));
		
			foreach ($_POST['host_name'] as $host_name => $value) {
				
				if (!$value) {
					continue;
				}
				
				$host_name = rawurldecode($host_name);
				
				$res = DB::query("INSERT INTO ".DB::getTable('SITE_API_HOSTS')." 
					(host_name, api_id)
						VALUES
					('".DBFunctions::strEscape($host_name)."',
					".(int)$value.")
				");
			}
			
			$this->msg = true;
		}
	
		// POPUP
		
		if ($method == "edit_api" || $method == "add_api") {
			
			$arr_api = [];
			$mode = 'insert_api';
			
			$arr_clients_user_groups = user_groups::getUserGroups();
			$arr_client_users_user_groups = $arr_clients_user_groups;
		
			if ($method == 'edit_api' && $id) {
				
				$arr_api = self::getAPIs($id);
				$mode = 'update_api';

				if ($arr_api['clients_user_group_id']) {
					
					$arr_client_users_user_groups = self::getClientUsersUserGroups($arr_api['clients_user_group_id']);
					
					if (!$arr_api['client_users_user_group_id']) {
						$arr_api['client_users_user_group_id'] = 'children';
					}
				}
			}
			
			$arr_modules = getModuleConfiguration('api', false, DIR_HOME, false);
			$arr_select_modules = [];
			
			foreach ($arr_modules as $module => $method) {
				$arr_select_modules[] = ['id' => $module, 'name' => $module];
			}
								
			$this->html = '<form id="frm-api" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($arr_api['name']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_api_client').' - '.getLabel('lbl_user_group').'</label>
						<span><select name="clients_user_group_id" id="y:intf_apis:lookup_user_groups-0">'.cms_general::createDropdown($arr_clients_user_groups, $arr_api['clients_user_group_id'], true).'</select></span>
					</li>
					<li>
						<label>'.getLabel('lbl_api_client').' / '.getLabel('lbl_users').' - '.getLabel('lbl_user_group').'</label>
						<span><select name="client_users_user_group_id">'.cms_general::createDropdown($arr_client_users_user_groups, $arr_api['client_users_user_group_id'], ($arr_api['clients_user_group_id'] ? false : true)).'</select></span>
					</li>
					<li>
						<label>'.getLabel('lbl_module').'</label>
						<div><select name="1100cc_home_module">'.cms_general::createDropdown($arr_select_modules, $arr_api['module'], false).'</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_limit').'</label>
						<div><input type="number" name="request_limit_amount" value="'.(float)$arr_api['request_limit_amount'].'" /><select name="request_limit_unit">'.cms_general::createDropdown(cms_general::getTimeUnits(), $arr_api['request_limit_unit'], false).'</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_limit').' - IP</label>
						<div><input type="number" name="request_limit_ip" value="'.(int)$arr_api['request_limit_ip'].'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_limit').' - Global</label>
						<div><input type="number" name="request_limit_global" value="'.(int)$arr_api['request_limit_global'].'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_documentation').'</label>
						<div><input type="text" name="documentation_url" value="'.strEscapeHTML($arr_api['documentation_url']).'" /></div>
					</li>
				</ul></fieldset>
			</form>';
			
			$this->validate = ['name' => 'required'];
		}
		
		// POPUP INTERACTION
		
		if ($method == "lookup_user_groups") {
				
			$arr_user_groups = [];
			
			if ((int)$value) {
				
				$arr_user_groups = self::getClientUsersUserGroups($value);
				
				$return = cms_general::createDropdown($arr_user_groups, $value, false);
			} else {
				
				$arr_user_groups = user_groups::getUserGroups();
				
				$return = cms_general::createDropdown($arr_user_groups, $value, true);
			}

			$this->html = $return;
		}
				
		// QUERY
		
		if ($method == "insert_api") {
		
			$res = DB::query("INSERT INTO ".DB::getTable('SITE_APIS')."
				(name, clients_user_group_id, client_users_user_group_id, module, request_limit_amount, request_limit_unit, request_limit_ip, request_limit_global, documentation_url)
					VALUES
				(
					'".DBFunctions::strEscape($_POST['name'])."',
					".(int)$_POST['clients_user_group_id'].",
					".(int)($_POST['client_users_user_group_id'] == 'children' ? 0 : $_POST['client_users_user_group_id']).",
					'".DBFunctions::strEscape($_POST['1100cc_home_module'])."',
					".(float)$_POST['request_limit_amount'].",
					".(int)$_POST['request_limit_unit'].",
					".(int)$_POST['request_limit_ip'].",
					".(int)$_POST['request_limit_global'].",
					'".DBFunctions::strEscape($_POST['documentation_url'])."'
				)
			");
			
			$this->html = $this->contentTabAPIs();
			$this->msg = true;
		}
		
		if ($method == "update_api" && $id){
						
			$res = DB::query("UPDATE ".DB::getTable('SITE_APIS')." SET
					name = '".DBFunctions::strEscape($_POST['name'])."',
					clients_user_group_id = ".(int)$_POST['clients_user_group_id'].",
					client_users_user_group_id = ".(int)($_POST['client_users_user_group_id'] == 'children' ? 0 : $_POST['client_users_user_group_id']).",
					request_limit_amount = ".(float)$_POST['request_limit_amount'].",
					request_limit_unit = ".(int)$_POST['request_limit_unit'].",
					documentation_url = '".DBFunctions::strEscape($_POST['documentation_url'])."',
					module = '".DBFunctions::strEscape($_POST['1100cc_home_module'])."'
				WHERE id = ".(int)$id."");
								
			self::setAPILimits($id, $_POST['request_limit_ip'] ,$_POST['request_limit_global']);
			
			$this->html = $this->contentTabAPIs();
			$this->msg = true;
		}
		
		if ($method == "del_api" && $id) {
						
			$res = DB::query("DELETE FROM ".DB::getTable('SITE_APIS')."
								WHERE id = ".(int)$id."
			");
			
			$this->msg = true;
		}
	}
	
	private static function getClientUsersUserGroups($user_group_id) {
		
		$arr = [];
	
		$arr_user_group = user_groups::getUserGroups($user_group_id);
		
		$arr[$arr_user_group['id']] = $arr_user_group;
		
		$arr_user_group_children = user_groups::getUserGroups(0, $user_group_id);
		
		if ($arr_user_group_children) {
			
			$arr['children'] = ['id' => 'children', 'name' => $arr_user_group['name'].' - '.getLabel('lbl_children')];
			
			$arr = array_merge($arr, $arr_user_group_children);
		}
		
		return $arr;
	}
}
