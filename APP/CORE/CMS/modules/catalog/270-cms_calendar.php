<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_CALENDAR_EVENTS', DB::$database_home.'.def_calendar_events');
DB::setTable('TABLE_CALENDAR_EVENT_RELATIONS', DB::$database_home.'.def_calendar_event_relations');
DB::setTable('TABLE_CALENDAR_EVENT_REMINDERS', DB::$database_home.'.def_calendar_event_reminders');

class cms_calendar extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_calendar');
		static::$parent_label = getLabel('ttl_content');
	}
	
	public static function jobProperties() {
		return [
			'checkReminders' => ['label' => getLabel('lbl_send_calendar_event_reminders')]
		];
	}

	public function contents() {

		$return .= '<div class="section"><h1 id="x:cms_calendar:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup add" value="add" /></h1>
		<div class="calendar">';
				
		$return .= '<div class="options filter">
			'.self::createFilter(date('Y'), date('m')).'
		</div>';
		
		$return .= '<div class="dynamic">
				'.self::createMonth(date('Y'), date('m')).'
			</div>
		</div></div>';
		
		return $return;
	}
	
	private static function createFilter($year, $month) {
			
		$arr_years = [];
		
		for ($i = ((int)$year-5); $i <= ((int)$year+5); $i++) {
			$arr_years[] = ['id' => $i, 'name' => $i];
		}
		
		$return = '<label>'.getLabel('lbl_year').':</label><select name="year">'.cms_general::createDropdown($arr_years, $year).'</select><label>'.getLabel('lbl_month').':</label><select name="month">'.cms_general::createDropdown(cms_general::getMonths(), $month).'</select>'
			.'<button type="button" class="prev" value=""><span class="icon">'.getIcon('prev').'</span></button><button type="button" class="calendar" value=""><span class="icon">'.getIcon('date').'</span></button><button type="button" class="next" value=""><span class="icon">'.getIcon('next').'</span></button>'
			.'<input type="hidden" id="y:cms_calendar:view_month-0" value="" />';
	
		return $return;
	}
	
	private static function createMonth($year, $month) {
		
		$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$date_start = mktime(0, 0, 0, $month, 1, $year);
		$date_end = mktime(23, 59, 59, $month, $days_in_month, $year);
		$day_first_of_month = date('N', $date_start);
		$day_last_of_month = date('N', $date_end);
		$week_start = (int)date('W', $date_start);
		$week_end = (int)date('W', $date_end);
		$cur_day = 2-$day_first_of_month;
		$date_start_true = mktime(0, 0, 0, $month, $cur_day, $year);
		$date_end_true = mktime(0, 0, 0, $month, $days_in_month+(7-$day_last_of_month), $year);

		$arr_day_names = [getLabel('unit_week_monday'), getLabel('unit_week_tuesday'), getLabel('unit_week_wednesday'), getLabel('unit_week_thursday'), getLabel('unit_week_friday'), getLabel('unit_week_saturday'), getLabel('unit_week_sunday')];
		$arr_day_abbr_length = Labels::printLabels(getLabel('unit_week_day_abbr_length'));
		$arr_day_names = Labels::printLabels($arr_day_names);
		
		$arr_events = self::getEvents($date_start_true, $date_end_true);

		$return .= '<table class="month">
			<thead>
				<tr><th></th><th>'.substr($arr_day_names[0], 0, $arr_day_abbr_length).'</th><th>'.substr($arr_day_names[1], 0, $arr_day_abbr_length).'</th><th>'.substr($arr_day_names[2], 0, $arr_day_abbr_length).'</th><th>'.substr($arr_day_names[3], 0, $arr_day_abbr_length).'</th><th>'.substr($arr_day_names[4], 0, $arr_day_abbr_length).'</th><th>'.substr($arr_day_names[5], 0, $arr_day_abbr_length).'</th><th>'.substr($arr_day_names[6], 0, $arr_day_abbr_length).'</th></tr>
			</thead>
			<tbody>';
			
			$cur_week = $week_start;
			
			while (true) {
				
				$return .= '<tr>
					<td>'.str_pad($cur_week, 2, '0', STR_PAD_LEFT).'</td>';
					
					$day_first_of_week = $cur_day;
					
					for ($cur_day; $cur_day < ($day_first_of_week+7); $cur_day++) {
						
						$cur_time = mktime(0, 0, 0, $month, $cur_day, $year);
						$cur_date = date('Y-m-d', $cur_time);
						$str_colors = '';
						$arr_names = [];
						
						$arr_events_date = ($arr_events[$cur_date] ?: []);
						
						foreach ($arr_events_date as $value) {
							
							$str_colors .= '<li style="background-color: #'.$value['color'].'; width: '.(100/count($arr_events_date)).'%;"></li>';
							
							$arr_names[] = $value['name'];
						}

						$arr_classes = array_filter([(($cur_day < 1 || $cur_day > $days_in_month) ? 'day-outside-month' : ''), ($cur_date == date('Y-m-d') ? 'today active' : '')]);
						$return .= '<td'.($arr_names ? ' title="'.htmlspecialchars(implode('<br />', $arr_names)).'"' : '').($arr_classes ? ' class="'.implode(" ", $arr_classes).'"' : '').'><div class="indicator'.($arr_events_date ? ' active' : '').'">'.(count($arr_events_date) ?: '').'</div>'.($str_colors ? '<ul class="colors">'.$str_colors.'</ul>' : '').date("d", $cur_time).'<input type="hidden" id="y:cms_calendar:view_day-'.$cur_time.'" value="'.$cur_time.'" /></td>';
					}
								
				$return .= '</tr>';
				
				if ($cur_week == $week_end) {
					break;
				}
				
				$cur_week = (int)date('W', mktime(0, 0, 0, $month, $cur_day+1, $year));
			}
		
			$return .= '</tbody>
		</table>
		
		<div class="dynamic-view-day">'.self::createDay(time()).'</div>';
				
		return $return;
	}
	
	private static function createDay($time) {
	
		$arr_events = self::getEvents($time);

		$return .= '<h1>'.date('d-m-Y', $time).'</h1>';
		
		if (!$arr_events) {
			$return .= '<p class="info">'.getLabel('msg_no_calendar_events').'</p>';
			return $return;
		}

		$return .= '<table class="list">
			<thead>
				<thead>
				<tr>
					<th></th>
					<th>'.getLabel('lbl_time').'</th>
					<th>'.getLabel('lbl_name').'</th>
					<th></th>
				</tr>
			</thead><tbody>';
			foreach ($arr_events as $value) {
				$return .= '<tr id="x:cms_calendar:event-'.$value['id'].'"><td style="background-color: #'.$value['color'].';"></td><td>'.date('H:i', strtotime($value['date'])).' - '.date('H:i', strtotime($value['date_end'])).'</td><td>'.$value['name'].'</td><td><input type="button" class="data edit popup edit" value="edit" /><input type="button" class="data del msg del" value="del" /></td></tr>';
			}
			$return .= '</tbody>
		</table>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '.calendar table.month { display: inline-table; vertical-align: top; }
					.calendar table.month * { box-sizing: border-box; }
					.calendar table.month th { text-align: center; vertical-align: middle; padding: 5px; }
					.calendar table.month td { text-align: right; vertical-align: bottom; height: 50px; width: 50px; border: 1px solid #cccccc; padding: 5px; cursor: pointer; }
					.calendar table.month td:first-child { vertical-align: middle; border: 0px; width: auto; }
					.calendar table.month td.today { background-color: #f5f5f5; }
					.calendar table.month td.active { background-color: #e7f2f9; }
					.calendar table.month td > .indicator { position: absolute; margin-top: -29px; margin-left: 29px; width: 15px; height: 15px; line-height: 15px; text-align: center; font-size: 10px; background-color: #f5f5f5; }
					.calendar table.month td.today > .indicator { background-color: #e5e3e3; }
					.calendar table.month td > .indicator.active { background-color: #c0c0c0; color: #ffffff; }
					.calendar table.month td:hover > .indicator { background-color: #c0c0c0; }
					.calendar table.month td.active > .indicator { background-color: #009cff; color: #ffffff; }
					.calendar table.month td > ul.colors { position: absolute; margin-top: -29px; margin-left: -5px; width: 34px; display: table; }
					.calendar table.month td > ul.colors > li { height: 8px; display: table-cell; }
					.calendar .day-outside-month { color: #cccccc; vertical-align: top; }
					.calendar .dynamic-view-day { display: inline-block; margin-left: 10px; }
					.calendar .dynamic-view-day th:first-child,
					.calendar .dynamic-view-day td:first-child { width: 10px; padding: 0px; }
					.calendar .dynamic-view-day th:first-child + th,
					.calendar .dynamic-view-day td:first-child + td { padding-right: 30px; }
					
					#frm-calendar td > .date { display: inline-block; }
					#frm-calendar .body-content { height: 250px; }
					#frm-calendar input[name*=reminder_amount] { width: 40px; }
					#frm-calendar label > input[name=color] + span { width: 15px; height: 15px; }
					#frm-calendar input[name=color] + span > span { display: block; width: 100%; height: 100%; margin-left: 2px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-cms_calendar', function(elm_scripter) {
		
			elm_scripter.on('change', '.calendar > .options > select', function() {
				var elm_filter = $(this).parent('.filter');
				var elm_target = $(this).siblings('[id=y\\\:cms_calendar\\\:view_month-0]');
				COMMANDS.setData(elm_target, {year: elm_filter.children('[name=year]').val(), month: elm_filter.children('[name=month]').val()});
				elm_target.quickCommand([elm_scripter.find('.dynamic'), elm_filter]);
			}).on('click', '.calendar > .options > .prev, .calendar > .options > .next', function() {
				var elm_filter = $(this).parent('.filter');
				var elm_target = $(this).siblings('[id=y\\\:cms_calendar\\\:view_month-0]');
				var month = parseInt(elm_filter.children('[name=month]').val())+($(this).hasClass('next') ? +1 : -1);
				var year = parseInt(elm_filter.children('[name=year]').val());
				if (month == 0) {
					month = 12;
					year = year-1;
				} else if (month == 13) {
					month = 1;
					year = year+1;
				}
				COMMANDS.setData(elm_target, {year: year, month: month});
				elm_target.quickCommand([elm_scripter.find('.dynamic'), elm_filter]);
			}).on('click', '.calendar > .options > .calendar', function() {
				var elm_filter = $(this).parent('.filter');
				var elm_target = $(this).siblings('[id=y\\\:cms_calendar\\\:view_month-0]');
				var today = new Date();
				COMMANDS.setData(elm_target, {year: today.getFullYear(), month: today.getMonth()+1});
				elm_target.quickCommand([elm_scripter.find('.dynamic'), elm_filter]);
			}).on('click', '.calendar table.month td', function() {
				var cur = $(this);
				COMMANDS.setData($('[id=x\\\:cms_calendar\\\:new-0]'), {time: cur.find('[id^=y\\\:cms_calendar\\\:view_day-]').val()});
				cur.find('[id^=y\\\:cms_calendar\\\:view_day-]').quickCommand(function(html) {
					elm_scripter.find('.dynamic-view-day').html(html);
					elm_scripter.find('table.month td.active').removeClass('active');
					cur.addClass('active');
				});
			});
		});
				
		SCRIPTER.dynamic('#frm-calendar', function(elm_scripter) {
			
			elm_scripter.on('click', '.add', function() {
				elm_scripter.find('.sorter').sorter('addRow');
			}).on('click', '.del', function() {
				elm_scripter.find('.sorter').sorter('clean');
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
		
		if ($method == "view_month") {
		
			$this->html = [self::createMonth($value['year'], $value['month']), self::createFilter($value['year'], $value['month'])];
		}
		
		if ($method == "view_day") {
		
			$this->html = self::createDay((int)$id);
		}
		
		// POPUP
						
		if ($method == "edit" || $method == "add") {
		
			if ((int)$id) {
				
				$arr_event_set = self::getEventSet($id);
				
				$arr_event_set_relation_groups = ($arr_event_set['relation_user_groups'] ? array_keys($arr_event_set['relation_user_groups']) : []);
				
				if ($arr_event_set['relation_cms_group']) {
					$arr_event_set_relation_groups[] = 'cms';
				}
				
				foreach (($arr_event_set['relation_users'] ?: []) as $key => $arr_user) {
					$arr_event_set_relation_users[$key] = $arr_user['user_name'];
				}
												
				$mode = "update";
			} else {
									
				$mode = "insert";
			}
			
			$arr_color = ['ff4f4f', 'ff91fb', '5f6eff', '6db9ff', '6afdff', '6eff6a', 'c7ff50', 'fffd50', 'ffa24f'];
			$arr_relation_user_groups = array_merge([['id' => 'cms', 'name' => getLabel('ttl_cms')]], user_groups::getUserGroups());

			$this->html = '<form id="frm-calendar" data-method="'.$mode.'">
				<table>
					<tr>
						<td>'.getLabel('lbl_name').'</td>
						<td><input type="text" name="name" value="'.htmlspecialchars($arr_event_set['details']['name']).'"></td>
					</tr>
					<tr>
						<td>'.getLabel('lbl_date').'</td>
						<td>'.cms_general::createDefineDate(($arr_event_set['details']['date'] ?: ((int)$value['time'] ?: time())), '', false).' - '.cms_general::createDefineDate(($arr_event_set['details']['date_end'] ?: ((int)$value['time'] ? (int)$value['time']+3600 : time())), 'date_end', false).'</td>
					</tr>
					<tr>
						<td>'.getLabel('lbl_description').'</td>
						<td>'.cms_general::editBody($arr_event_set['details']['description'], 'description').'</td>
					</tr>
					<tr>
						<td>'.getLabel('lbl_color').'</td>
						<td>';
						foreach ($arr_color as $value) {
							$this->html .= '<label><input type="radio" name="color" value="'.$value.'"'.((($mode == 'insert' && $value == $arr_color[0]) || $arr_event_set['details']['color'] == $value) ? ' checked="checked"' : '').' /><span style="background-color: #'.$value.';"></span></label>';
						}
						$this->html .= '</td>
					</tr>
					<th colspan="2" class="split"><hr /></th>
					<tr>
						<td>'.getLabel('lbl_user_groups').'</td>
						<td><span>'.cms_general::createSelector($arr_relation_user_groups, 'relation_user_groups', ($arr_event_set_relation_groups ?: [])).'</span></td>
					</tr>
					<tr>
						<td>'.getLabel('lbl_users').'</td>
						<td>'.cms_general::createMultiSelect('relation_users', 'y:cms_calendar:lookup_user-0', ($arr_event_set_relation_users ?: [])).'<label><input type="checkbox" name="relation_user_children" value="1"'.($arr_event_set['details']['user_children'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_children').'</span></label></td>
					</tr>
					<th colspan="2" class="split"><hr /></th>
					<tr>
						<td></td>
						<td><input type="button" class="data del" title="'.getLabel('inf_remove_empty_fields').'" value="del" /><input type="button" class="data add" value="add" /></td>
					</tr>
					<tr>
						<td>'.getLabel('lbl_reminder').'</td>
						<td><ul class="sorter">';
						
						if ($arr_event_set['reminders']) {
							
							foreach($arr_event_set['reminders'] as $value) {
								$this->html .= '<li><div><input type="number" name="reminder_amount[]" value="'.(int)$value['reminder_amount'].'"><select name="reminder_unit[]">'.cms_general::createDropdown(cms_general::getTimeUnits(), $value['reminder_unit'], true).'</select></div></li>';
							}
						} else {
							
							$this->html .= '<li><div><input type="number" name="reminder_amount[]" value=""><select name="reminder_unit[]">'.cms_general::createDropdown(cms_general::getTimeUnits(), 0, true).'</select></div></li>';
						}
						$this->html .= '</ul></td>
					</tr>
				</table>
				</form>';
			
			$this->validate = '{"name": "required"}';
		}
				
		// POPUP INTERACT
		
		if ($method == "lookup_user") {
		
			$arr_users = user_management::filterUsers($value);
			
			$arr = [];
			foreach ($arr_users as $row) {
				$arr[] = ['id' => $row['id'], 'label' => $row['name'], 'value' => $row['name']];
			}

			$this->html = $arr;
		}
							
		// QUERY
	
		if ($method == "insert") {
								
			self::handleCalendarEvent($_POST);
												
			$this->refresh = true;
			$this->msg = true;
		}
		
		if ($method == "update" && (int)$id) {

			self::handleCalendarEvent($_POST, (int)$id);
							
			$this->refresh = true;
			$this->msg = true;
		}
			
		if ($method == "del" && (int)$id) {
		
			$res = DB::query("DELETE ae, aer, aerm
					FROM ".DB::getTable('TABLE_CALENDAR_EVENTS')." ae
					LEFT JOIN ".DB::getTable('TABLE_CALENDAR_EVENT_RELATIONS')." aer ON (aer.calendar_event_id = ae.id)
					LEFT JOIN ".DB::getTable('TABLE_CALENDAR_EVENT_REMINDERS')." aerm ON (aerm.calendar_event_id = ae.id)
				WHERE ae.id = ".(int)$id."
			");
			
			$this->msg = true;
		}		
	}

	public static function handleCalendarEvent($arr_event, $event_id = false) {
		
		$date = DBFunctions::str2Date($arr_event['date'].' '.$arr_event['date_t']);
		$date_end = DBFunctions::str2Date($arr_event['date_end'].' '.$arr_event['date_end_t']);
		if ($date >= $date_end) {
			error(getLabel('msg_date_incorrect'));
		}
	
		if ($event_id) {
			$res = DB::query("UPDATE ".DB::getTable('TABLE_CALENDAR_EVENTS')." SET date = '".$date."', date_end = '".$date_end."', name = '".DBFunctions::strEscape($arr_event['name'])."', description = '".DBFunctions::strEscape($arr_event['description'])."', color = '".DBFunctions::strEscape($arr_event['color'])."' WHERE id = ".$event_id."");
		} else {
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_CALENDAR_EVENTS')." (date, date_end, name, description, color) VALUES ('".$date."', '".$date_end."', '".DBFunctions::strEscape($arr_event['name'])."', '".DBFunctions::strEscape($arr_event['description'])."', '".DBFunctions::strEscape($arr_event['color'])."')");
			
			$event_id = DB::lastInsertID();
		}
		
		$res = DB::query("DELETE aer, aerm
				FROM ".DB::getTable('TABLE_CALENDAR_EVENTS')." ae
				LEFT JOIN ".DB::getTable('TABLE_CALENDAR_EVENT_RELATIONS')." aer ON (aer.calendar_event_id = ae.id)
				LEFT JOIN ".DB::getTable('TABLE_CALENDAR_EVENT_REMINDERS')." aerm ON (aerm.calendar_event_id = ae.id)
			WHERE ae.id = ".(int)$event_id."
		");
		
		if ($arr_event["relation_user_groups"]) {
			
			foreach ($arr_event["relation_user_groups"] as $key => $value) {
				
				if ($key == "cms") {
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_CALENDAR_EVENT_RELATIONS')." (calendar_event_id, user_id, user_children, user_group_id, cms_group) VALUES (".(int)$event_id.", 0, FALSE, 0, TRUE)");
				} else {
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_CALENDAR_EVENT_RELATIONS')." (calendar_event_id, user_id, user_children, user_group_id, cms_group) VALUES (".(int)$event_id.", 0, FALSE, ".(int)$key.", FALSE)");
				}
			}
		}
		
		$arr_relation_users = array_filter(explode(",", $arr_event["relation_users"]));
		if ($arr_relation_users) {
			
			foreach ($arr_relation_users as $value) {
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_CALENDAR_EVENT_RELATIONS')." (calendar_event_id, user_id, user_children, user_group_id, cms_group) VALUES (".(int)$event_id.", ".(int)$value.", ".DBFunctions::escapeAs($arr_event['relation_user_children'], DBFunctions::TYPE_BOOLEAN).", 0, FALSE)");
			}
		}
		
		if ($arr_event["reminder_amount"]) {
			
			foreach ($arr_event["reminder_amount"] as $key => $value) {
				
				if ((!(int)$arr_event["reminder_amount"][$key] || !(int)$arr_event['reminder_unit'][$key]) || (strtotime($arr_event['date'].' '.$arr_event['date_t'])-((int)$arr_event['reminder_amount'][$key]*(int)$arr_event['reminder_unit'][$key]*60) < time())) {
					continue;
				}
				
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_CALENDAR_EVENT_REMINDERS')." (calendar_event_id, amount, unit) VALUES (".(int)$event_id.", ".(int)$arr_event['reminder_amount'][$key].", ".(int)$arr_event['reminder_unit'][$key].")");
			}
		}
	}
	
	public static function getEventSet($event_id) {
	
		$arr = [];
			
		$res = DB::query("SELECT ae.*, aer.user_id, aer.user_children, u.name AS user_name, aer.user_group_id, aer.cms_group, aerm.amount AS reminder_amount, aerm.unit AS reminder_unit
				FROM ".DB::getTable('TABLE_CALENDAR_EVENTS')." ae
				LEFT JOIN ".DB::getTable('TABLE_CALENDAR_EVENT_RELATIONS')." aer ON (aer.calendar_event_id = ae.id)
				LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = aer.user_id)
				LEFT JOIN ".DB::getTable('TABLE_CALENDAR_EVENT_REMINDERS')." aerm ON (aerm.calendar_event_id = ae.id)
			WHERE ae.id = ".(int)$event_id."
		");
									
		while($arr_row = $res->fetchAssoc()) {
			
			$arr_row['user_children'] = DBFunctions::unescapeAs($arr_row['user_children'], DBFunctions::TYPE_BOOLEAN);
			
			$arr['details'] = $arr_row;
			$arr['reminders'][$arr_row['reminder_amount']*$arr_row['reminder_unit']] = $arr_row;
			
			if ($arr_row['user_id']) {
				$arr['relation_users'][$arr_row['user_id']] = $arr_row;
			} else if ($arr_row['user_group_id']) {
				$arr['relation_user_groups'][$arr_row['user_group_id']] = $arr_row;
			} else if ($arr_row['cms_group']) {
				$arr['relation_cms_group'] = 1;
			}
		}
		
		return $arr;
	}
	
	public static function getEvents($date, $date_end = false, $arr_user = 0) {
	
		$arr = [];
		
		$date = (is_string($date) ? strtotime($date) : $date);
		$date_range_start = date("Y-m-d 00:00:00", $date);
		$date_range_end = date("Y-m-d 23:59:59", ($date_end ? is_string($date_end) ? strtotime($date_end) : $date_end : $date));

		if ($arr_user) {
		
			$query = "SELECT ae.*
					FROM ".DB::getTable('TABLE_CALENDAR_EVENTS')." ae
					LEFT JOIN ".DB::getTable('TABLE_CALENDAR_EVENT_RELATIONS')." aer ON (aer.calendar_event_id = ae.id)
				WHERE (ae.date <= '".$date_range_start."' OR ae.date BETWEEN '".$date_range_start."' AND '".$date_range_end."') AND (ae.date_end >= '".$date_range_start."' OR ae.date_end BETWEEN '".$date_range_start."' AND '".$date_range_end."')
					AND (
						aer.user_id = ".(int)$arr_user['id']."
						OR aer.user_group_id = ".(int)$arr_user['group_id']."
						OR aer.user_id = CASE
							WHEN aer.user_children THEN ".(int)$arr_user['parent_id']."
							ELSE 0
						END
					)
				GROUP BY ae.id
				ORDER BY date
			";
		} else {
			
			$query = "SELECT ae.*
					FROM ".DB::getTable('TABLE_CALENDAR_EVENTS')." ae
				WHERE (ae.date <= '".$date_range_start."' OR ae.date BETWEEN '".$date_range_start."' AND '".$date_range_end."') AND (ae.date_end >= '".$date_range_start."' OR ae.date_end BETWEEN '".$date_range_start."' AND '".$date_range_end."')
				GROUP BY ae.id
				ORDER BY date
			";
		}

		$res = DB::query($query);
		
		while($row = $res->fetchAssoc()) {
			
			if ($date_end) {
				
				$row_date_range_start = new DateTime($row['date']);
				$row_date_range_start->setTime(0, 0, 0);
				$row_date_range_end = new DateTime($row['date_end']);
				$row_date_range_end->setTime(23, 59, 59);

				$interval = DateInterval::createFromDateString('1 day');
				$period = new DatePeriod($row_date_range_start, $interval, $row_date_range_end);

				foreach ($period as $cur_date) {
					if ($row_date_range_start->format('Y-m-d') < $cur_date->format('Y-m-d')) {
						$row['date'] = $cur_date->format('Y-m-d 00:00:00');
					}
					if ($row_date_range_end->format('Y-m-d') > $cur_date->format('Y-m-d')) {
						$row['date_end'] = $cur_date->format('Y-m-d 23:59:59');
					}
					$arr[$cur_date->format('Y-m-d')][$row['id']] = $row;
				}
			} else {
				
				if ($row['date'] < $date_range_start) {
					$row['date'] = $date_range_start;
				}
				if ($row['date_end'] > $date_range_end) {
					$row['date_end'] = $date_range_end;
				}
				$arr[$row['id']] = $row;
			}
		}
			
		return $arr;		
	}
	
	public static function checkReminders() {
				
		$res = DB::query("SELECT
			ae.*, aer.user_id, aer.user_children, u.name AS user_name, aer.user_group_id, aer.cms_group, aerm.amount AS reminder_amount, aerm.unit AS reminder_unit, u.email AS user_email
				FROM ".DB::getTable('TABLE_CALENDAR_EVENTS')." ae
				LEFT JOIN ".DB::getTable('TABLE_CALENDAR_EVENT_RELATIONS')." aer ON (aer.calendar_event_id = ae.id)
				LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = aer.user_id AND user_children = FALSE)
				LEFT JOIN ".DB::getTable('TABLE_CALENDAR_EVENT_REMINDERS')." aerm ON (aerm.calendar_event_id = ae.id)
			WHERE aerm.executed IS NULL AND UNIX_TIMESTAMP(ae.date)-((aerm.amount*aerm.unit)*60) <= UNIX_TIMESTAMP()
		");
		
		$cur_arr = [];
		$arr_to = [];
		$arr_time_units = cms_general::getTimeUnits();
		$func_send = function($arr_to, $arr_vars) use ($arr_time_units) {
			
			$arr_to = array_unique($arr_to);
			
			Labels::setVariable('name', $arr_vars['name']);
			Labels::setVariable('date', date('d-m-Y H:i', strtotime($arr_vars['date'])));
			Labels::setVariable('date_end', date('d-m-Y H:i', strtotime($arr_vars['date_end'])));
			Labels::setVariable('description', parseBody($arr_vars['description']));
			Labels::setVariable('reminder_amount', $arr_vars['reminder_amount']);
			Labels::setVariable('reminder_unit_name', $arr_time_units[$arr_vars['reminder_unit']]['name']);
			
			$mail = new Mail($arr_to, getLabel('name', 'D').' '.getLabel('lbl_calendar_event_reminder'), getLabel('msg_calendar_event_reminder_notification'));
			$mail->send();
		};
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['user_children'] = DBFunctions::unescapeAs($arr_row['user_children'], DBFunctions::TYPE_BOOLEAN);
			
			if ($cur_arr['id'] != $arr_row['id']) {
				
				if ($arr_to) {
					$func_send($arr_to, $cur_arr);
				}
				
				$cur_arr = $arr_row;
				$arr_to = [];
			}
			
			if ($arr_row['user_id'] && !$arr_row['user_children']) {
			
				$arr_to[] = $arr_row['user_email'];
			} else if ($arr_row['user_id'] && $arr_row['user_children']) {
			
				$arr_users = user_management::filterUsers(false,
					[
						'self_id' => $arr_row['user_id'],
						'children_id' => $arr_row['user_id']
					],
					false
				);
				
				foreach ($arr_users as $value) {
					$arr_to[] = $value['email'];
				}
			} else if ($arr_row['user_group_id']) {
			
				$arr_users = user_management::getUsersFromGroup($arr_row['user_group_id']);
				
				foreach ($arr_users as $value) {
					$arr_to[] = $value['email'];
				}
			} else if ($arr_row['cms_group']) {
			
				$arr_users = cms_users::getCMSUsers();
				
				foreach ($arr_users as $value) {
					$arr_to[] = $value['email'];
				}
			}
		}
		if ($arr_to) {
			$func_send($arr_to, $cur_arr);
		}
		
		$res = DB::query("UPDATE ".DB::getTable('TABLE_CALENDAR_EVENTS')." ae
				LEFT JOIN ".DB::getTable('TABLE_CALENDAR_EVENT_REMINDERS')." aerm ON (aerm.calendar_event_id = ae.id)
			SET executed = NOW()
			WHERE aerm.executed IS NULL AND UNIX_TIMESTAMP(ae.date)-((aerm.amount*aerm.unit)*60) <= UNIX_TIMESTAMP()
		");
	}
}
