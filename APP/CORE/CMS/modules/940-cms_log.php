<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_LOG', DB::$database_cms.'.site_log');
DB::setTable('TABLE_LOG_USERS', DB::$database_cms.'.site_log_users');
DB::setTable('TABLE_LOG_REQUESTS', DB::$database_home.'.site_log_requests');

class cms_log extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_log');
		static::$parent_label = getLabel('ttl_settings');
	}
	
	public static function jobProperties() {
		return [
			'cleanRequests' => [
				'label' => getLabel('lbl_cleanup_requests'),
				'options' => function($options) {
					return '<label>'.getLabel('lbl_age').'</label><input type="text" name="options[age_amount]" value="'.$options['age_amount'].'" /><select name="options[age_unit]">'.cms_general::createDropdown(cms_general::getTimeUnits(), $options['age_unit']).'</select>';
				},
			]
		];
	}
	
	public static function widgetProperties() {
		return [
			'widgetLog' => ['label' => getLabel('lbl_log')]
		];
	}
	
	public static function widgetLog() {
		
		$arr_log = self::getLogSet(10);
		
		$return = '<table class="list color">
			<thead>
				<tr>
					<th><span>'.getLabel('lbl_label').'</span></th>
					<th class="max"><span>'.getLabel('lbl_message').'</span></th>
					<th><span>'.getLabel('lbl_date').'</span></th>
					<th><span>'.getLabel('lbl_user').'</span></th>
				</tr>
			</thead>
			<tbody>';
				
				if (!$arr_log) {
					
					$return .= '<tr>
						<td colspan="4" class="empty">'.getLabel('msg_no_results').'</td>
					</tr>';
				} else {
						
					foreach ($arr_log as $arr_record) {
						
						$return .= '<tr id="x:cms_log:log_id-'.$arr_record['id'].'" class="type-'.$arr_record['type'].' popup" data-method="log_info">
							<td>'.$arr_record['label'].'</td>
							<td>'.htmlspecialchars($arr_record['msg']).'</td>
							<td>'.date('d-m-\'y H:i', strtotime($arr_record['date'])).'</td>
							<td>';
								if ($arr_record['user']) {
								
									$return .= $arr_record['user'];
								} else {
									
									if ($arr_record['ip']) {
										
										$bin_ip = DBFunctions::unescapeAs($arr_record['ip'], DBFunctions::TYPE_BINARY);
										
										$return .= ($bin_ip ? inet_ntop($bin_ip) : getLabel('lbl_not_available_abbr'));
									} else {
										
										$return .= 'System';
									}
								}
							$return .= '</td>
						</tr>';
					}
				}
			$return .= '</tbody>
		</table>';
			
		return $return;
	}
	
	public function contents() {

		$return .= '<div class="section"><h1>'.self::$label.'</h1>
			<div class="cms_log">';
		
			$return .= '<div class="options">
				<menu><input type="button" id="y:cms_log:empty-0" value="'.getLabel('lbl_clear').'" /></menu>
			</div>';
		
			$return .= '<table class="display color" id="d:cms_log:log_data-0">
					<thead> 
						<tr>
							<th><span>'.getLabel('lbl_label').'</span></th>
							<th class="max"><span>'.getLabel('lbl_message').'</span></th>
							<th data-sort="desc-0"><span>'.getLabel('lbl_date').'</span></th>
							<th><span>'.getLabel('lbl_user').'</span></th>
							<th class="disable-sort menu" id="x:cms_log:log_id-0" title="'.getLabel('lbl_multi_select').'">'
								.'<input type="button" class="data msg del" value="del" title="'.getLabel('lbl_delete').'" />'
								.'<input type="checkbox" class="multi all" value="" />'
							.'</th>
						</tr> 
					</thead>
					<tbody>
						<tr>
							<td colspan="5" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
					</table>';
		
			$return .= '</div>
		</div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.cms_log .display td:first-child + td { white-space: normal; }
				.cms_log table tbody tr.type-alert:not(:hover),
				.log-info h2.type-alert { background-color: #ffd9d9; }
				.cms_log table tbody tr.type-attention:not(:hover),
				.log-info h2.type-attention { background-color: #fffcca; }
				.log-info h2 { margin: 8px 0px; padding: 5px 10px; }
				.log-info dl pre { max-width: 1000px; overflow: auto; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-cms_log', function(elm_scripter) {
		
			elm_scripter.on('click', '[id^=y\\\:cms_log\\\:empty]', function() {
			
				$(this).data('msg', 'conf_truncate').messageCommand();
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "log_info" && (int)$id) {
		
			$res = DB::query("SELECT l.*, lu.url
					FROM ".DB::getTable('TABLE_LOG')." l
					LEFT JOIN ".DB::getTable('TABLE_LOG_USERS')." lu ON (lu.id = l.log_user_id)
				WHERE l.id = ".(int)$id
			);
										
			$row = $res->fetchAssoc();
			
			$this->html = '<div class="log-info">
				<h2 class="type-'.$row['type'].'">'.$row['label'].'</h2>
				<div class="record"><dl>
					<li>
						<dt>'.getLabel('lbl_date').'</dt>
						<dd>'.date('d-m-Y H:i:s', strtotime($row['date'])).'</dd>
					</li>
					<li>
						<dt>'.getLabel('lbl_message').'</dt>
						<dd><pre>'.htmlspecialchars($row['msg']).'</pre></dd>
					</li>
					'.($row['debug'] ? '<li>
						<dt>Debug</dt>
						<dd><pre>'.htmlspecialchars($row['debug']).'</pre></dd>
					</li>' : '').'
					'.($row['url'] ? '<li>
						<dt>URL</dt>
						<dd>'.$row['url'].'</dd>
					</li>' : '').'
					<li>
						<dt>'.getLabel('lbl_user').'</dt>
						<dd>'.self::getLoggedUser($row['log_user_id']).'</dd>
					</li>
				</dl></div>
			</div>';
		}
		
		// DATATABLE
					
		if ($method == "log_data") {
			
			$arr_sql_columns = ['l.label', 'l.msg', 'l.date', 'CASE WHEN lu.user_id != 0 THEN u.name WHEN lu.cms_user_id != 0 THEN \'CMS\' ELSE lu.ip END'];
			$arr_sql_columns_search = ['l.label', 'l.msg', DBFunctions::castAs('l.date', DBFunctions::CAST_TYPE_STRING), 'CASE WHEN lu.user_id > 0 THEN u.name WHEN lu.cms_user_id != 0 THEN \'CMS\' ELSE \'\' END'];
			$arr_sql_columns_as = ['l.label', 'l.msg', 'l.date', 'CASE WHEN lu.user_id != 0 THEN ug.name WHEN lu.cms_user_id != 0 THEN \'CMS\' ELSE \'\' END AS user', 'lu.ip', 'l.type', 'l.id'];

			$sql_index = 'l.id';
			$sql_index_body = 'l.id, lu.id, u.id, ug.id';
			
			$sql_table = DB::getTable('TABLE_LOG')." l
				LEFT JOIN ".DB::getTable('TABLE_LOG_USERS')." lu ON (lu.id = l.log_user_id)
				LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = lu.user_id)
				LEFT JOIN ".DB::getTable('TABLE_USER_GROUPS')." ug ON (ug.id = u.group_id)
			";
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', $sql_index_body);
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:cms_log:log_id-'.$arr_row['id'];
				$arr_data['class'] = 'type-'.$arr_row['type'].' popup';
				$arr_data['attr']['data-method'] = 'log_info';
				
				$arr_data[] = $arr_row['label'];
				$arr_data[] = '<pre>'.htmlspecialchars($arr_row['msg']).'</pre>';
				$arr_data[] = date('d-m-Y H:i:s', strtotime($arr_row['date']));
				if ($arr_row['ip']) {
					
					if (!$arr_row['user']) {
						
						$bin_ip = DBFunctions::unescapeAs($arr_row['ip'], DBFunctions::TYPE_BINARY);
						
						$arr_data[] = ($bin_ip ? inet_ntop($bin_ip) : getLabel('lbl_not_available_abbr'));
					} else {
					
						$arr_data[] = $arr_row['user'];
					}
				} else {
					$arr_data[] = 'System';
				}
				$arr_data[] = '<input type="button" class="data msg del" value="del" /><input class="multi" value="'.$arr_row['id'].'" type="checkbox" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
					
		// QUERY
		
		if ($method == "empty") {
					
			$res = DB::queryMulti("
				".DBFunctions::deleteWith(
					DB::getTable('TABLE_LOG_USERS'), 'lu', 'id',
					"JOIN ".DB::getTable('TABLE_LOG')." l ON (l.log_user_id = lu.id)"
				)."
				;
				DELETE FROM ".DB::getTable('TABLE_LOG')."
				;
			");
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del" && $id) {
			
			$id = arrParseRecursive($id, 'int');
					
			$res = DB::queryMulti("
				".DBFunctions::deleteWith(
					DB::getTable('TABLE_LOG_USERS'), 'lu', 'id',
					"JOIN ".DB::getTable('TABLE_LOG')." l ON (l.log_user_id = lu.id AND l.id IN (".(is_array($id) ? implode(',', $id) : $id)."))"
				)."
				;
				DELETE FROM ".DB::getTable('TABLE_LOG')."
					WHERE id IN (".(is_array($id) ? implode(',', $id) : $id).")
				;
			");
			
			$this->refresh_table = true;
			$this->msg = true;
		}
	}
	
	private static function getLogSet($limit = 10) {

		$arr = [];

		$res = DB::query("SELECT l.*,
					CASE
						WHEN lu.user_id != 0 THEN ug.name
						WHEN lu.cms_user_id != 0 THEN 'CMS'
						ELSE ''
					END AS user,
					lu.ip
				FROM ".DB::getTable('TABLE_LOG')." l
				LEFT JOIN ".DB::getTable('TABLE_LOG_USERS')." lu ON (lu.id = l.log_user_id)
				LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = lu.user_id)
				LEFT JOIN ".DB::getTable('TABLE_USER_GROUPS')." ug ON (ug.id = u.group_id)
			ORDER BY l.date DESC
			LIMIT ".(int)$limit." OFFSET 0
		");
					
		while ($row = $res->fetchAssoc()) {
			
			$arr[$row['id']] = $row;
		}

		return $arr;
	}
	
	public static function getLoggedUser($id) {
		
		$res = DB::query("SELECT
			lu.*, u.uname, u.name, ug.name AS group_name, cu.uname AS cms_uname, cu.name AS cms_name
				FROM ".DB::getTable('TABLE_LOG_USERS')." lu
				LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = lu.user_id)
				LEFT JOIN ".DB::getTable('TABLE_USER_GROUPS')." ug ON (ug.id = u.group_id)
				LEFT JOIN ".DB::getTable('TABLE_CMS_USERS')." cu ON (cu.id = lu.cms_user_id)
			WHERE lu.id = ".(int)$id
		);
		
		$arr_row = $res->fetchAssoc();
		
		if (!$arr_row['cms_name']) {
			
			$arr_cms_user = cms_users::getCMSUsers($arr_row['cms_user_id'], true);
		
			$arr_row['cms_name'] = $arr_cms_user['name'];
			$arr_row['cms_uname'] = $arr_cms_user['uname'];
		}
		
		$str_ip = '';
	
		if ($arr_row['ip']) {
			
			$bin_ip = DBFunctions::unescapeAs($arr_row['ip'], DBFunctions::TYPE_BINARY);
			
			$str_ip = ($bin_ip ? inet_ntop($bin_ip) : getLabel('lbl_not_available_abbr'));
		}
		
		return ($arr_row['user_id'] ? $arr_row['name'].' - '.$arr_row['uname'].' ('.$arr_row['group_name'].') - '.$str_ip : ($arr_row['cms_user_id'] ? $arr_row['cms_name'].' - '.$arr_row['cms_uname'].' (CMS)' : ($str_ip ? $str_ip : 'System')));
	}
	
	public static function cleanRequests($arr_options) {
		
		if (!$arr_options['age_amount'] || !$arr_options['age_unit']) {
			return;
		}

		$res = DB::query("DELETE FROM ".DB::getTable('TABLE_LOG_REQUESTS')."
			WHERE type != '' AND date < (NOW() - ".DBFunctions::interval(((int)$arr_options['age_amount'] * (int)$arr_options['age_unit']), 'MINUTE').")
		");
	}
}
