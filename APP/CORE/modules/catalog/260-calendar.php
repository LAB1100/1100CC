<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class calendar extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_calendar');
		static::$parent_label = getLabel('lbl_communication');
	}
	
	public static function moduleVariables() {
		
		$return = '<select name="date_before_amount">';
		for ($i = 0; $i <= 10; $i++) {
			$return .= '<option value="'.$i.'">'.$i.'</option>';
		}
		$return .= '</select>';
		$return .= '<select name="date_before_unit"><option value="year">year</option><option value="month">month</option><option value="week">week</option><option value="day">day</option></select>';
		
		$return .= '<select name="date_range_amount">';
		for ($i = 0; $i <= 10; $i++) {
			$return .= '<option value="'.$i.'">'.$i.'</option>';
		}
		$return .= '</select>';
		$return .= '<select name="date_range_unit"><option value="year">year</option><option value="month">month</option><option value="week">week</option><option value="day">day</option></select>';
				
		$return .= '<select name="date_after_amount">';
		for ($i = 0; $i <= 10; $i++) {
			$return .= '<option value="'.$i.'">'.$i.'</option>';
		}
		$return .= '</select>';
		$return .= '<select name="date_after_unit"><option value="year">year</option><option value="month">month</option><option value="week">week</option><option value="day">day</option></select>';
		
		return $return;
	}
	
	public static function searchProperties() {
	
		return [
			'trigger' => [DB::getTable('TABLE_CALENDAR_EVENTS'), DB::getTable('TABLE_CALENDAR_EVENTS').'.description'],
			'title' => [DB::getTable('TABLE_CALENDAR_EVENTS'), DB::getTable('TABLE_CALENDAR_EVENTS').'.name'],
			'extra_values' => [
				[DB::getTable('TABLE_CALENDAR_EVENTS'), DB::getTable('TABLE_CALENDAR_EVENTS').'.date', 'date']
			],
			'search_var' => false,
			'module_link' => [
				[DB::getTable('TABLE_CALENDAR_EVENTS'), DB::getTable('TABLE_CALENDAR_EVENTS').'.id']
			],
			'module_var' => false,
			'module_query' => function($arr_result) {
				return 'day/'.date('d-m-Y', strtotime($arr_result['extra_values'][DB::getTable('TABLE_CALENDAR_EVENTS')]['date'])).'#'.$arr_result['object_link'].'-'.str2URL($arr_result['title']);
			}
		];
	}
		
	public function contents($date = false, $direction = "next_range", $view = "range") {
	
		$date = ($date ?: time());
		if ($this->arr_query[0] == "day" && $this->arr_query[1]) {
			$date = strtotime($this->arr_query[1].' 12:00:00');
			$view = "day";
		}
	
		$date_start = new DateTime(($date ? '@'.$date : false));
		$date_start->setTime(0, 0, 0);
		if ($direction == "prev_range" || $direction == "next_day") {
			$date_start->modify(-($this->arr_variables['date_range_amount']).' '.$this->arr_variables['date_range_unit']);
		}
		if ($direction == "prev_day") {
			$date_start->modify('-1 day');
		} else if ($direction == "next_day") {
			$date_start->modify('+1 day');
		}
		$date_start->modify(-($this->arr_variables['date_before_amount']).' '.$this->arr_variables['date_before_unit']);
		$date_end = new DateTime(($date ? '@'.$date : false));
		$date_end->setTime(23, 59, 59);
		if ($direction == "next_range" || $direction == "prev_day") {
			$date_end->modify(+($this->arr_variables['date_range_amount']).' '.$this->arr_variables['date_range_unit']);
		}
		if ($direction == "prev_day") {
			$date_end->modify('-1 day');
		} else if ($direction == "next_day") {
			$date_end->modify('+1 day');
		}
		$date_end->modify(+($this->arr_variables['date_after_amount']).' '.$this->arr_variables['date_after_unit']);
		
		$date_range_start = new DateTime(($date ? '@'.$date : false));
		if ($direction == "prev_range" || $direction == "next_day") {
			$date_range_start->modify(-($this->arr_variables['date_range_amount']).' '.$this->arr_variables['date_range_unit']);
		}
		if ($direction == "prev_day") {
			$date_range_start->modify('-1 day');
		} else if ($direction == "next_day") {
			$date_range_start->modify('+1 day');
		}
		$date_range_start->setTime(0, 0, 0);
		$date_range_end = new DateTime(($date ? '@'.$date : false));
		$date_range_end->setTime(23, 59, 59);
		if ($direction == "next_range" || $direction == "prev_day") {
			$date_range_end->modify(+($this->arr_variables['date_range_amount']).' '.$this->arr_variables['date_range_unit']);
		}
		if ($direction == "prev_day") {
			$date_range_end->modify('-1 day');
		} else if ($direction == "next_day") {
			$date_range_end->modify('+1 day');
		}

		$interval = DateInterval::createFromDateString('1 day');
		$period = new DatePeriod($date_start, $interval, $date_end);
		
		$date_today = date("d-m-Y");
		$arr_events = cms_calendar::getEvents($date_start->getTimestamp(), $date_end->getTimestamp());
		
		$return .= '<section>';

			foreach ($period as $cur_date) {
				if ($cur_date->format("j") == '1') {
					$return .= '<figure class="month">
						<figcaption><span>'.getLabel("unit_month_".$cur_date->format("F")).'</span></figcaption>
						'.($cur_date >= $date_range_start && $cur_date <= $date_range_end ? '<span class="range"></span>' : '').'
					</figure>';
				}
				if ($cur_date->format("w") == '0') {
					$return .= '<figure class="week">
						<figcaption><span>'.$cur_date->format("W").'</span></figcaption>
						'.($cur_date >= $date_range_start && $cur_date <= $date_range_end ? '<span class="range"></span>' : '').'
					</figure>';
				}
				$return .= '<figure class="day">
					<figcaption><span>'.$cur_date->format("d-m-Y").'</span></figcaption>
					'.($cur_date->format("d-m-Y") == $date_today ? '<span class="today"></span>' : '').'
					<div>';
						if ($arr_events[$cur_date->format("Y-m-d")]) {
							$height = (100/count($arr_events[$cur_date->format("Y-m-d")]));
							foreach ($arr_events[$cur_date->format("Y-m-d")] as $value) {
								$return .= '<span style="background-color: #'.$value["color"].'; height: '.$height.'%;" title="'.strEscapeHTML($value["name"]).'"></span>';
							}
						}
					$return .= '</div>
					'.($cur_date >= $date_range_start && $cur_date <= $date_range_end ? '<span class="range"></span>' : '').'
				</figure>';
			}
			
			$return .= '<menu id="x:calendar:scroll-'.$date_range_start->getTimestamp().'_'.$date_range_end->getTimestamp().'">'
				.'<button type="button" class="prev_range" value=""><span class="icon" data-category="increase">'.getIcon('prev').getIcon('prev').'</span></button>'
				.'<button type="button" class="prev_day" value=""><span class="icon">'.getIcon('prev').'</span></button>'
				.'<span>'.$date_range_start->format('d-m-Y').'</span>'
				.'<button type="button" class="'.($view == 'day' ? 'view_day' : 'view_range').'" value="">'.($view == 'day' ? getLabel('lbl_calendar_range') : getLabel('lbl_calendar_day')).'</button>'
				.'<span>'.$date_range_end->format('d-m-Y').'</span>'
				.'<button type="button" class="next_day" value=""><span class="icon">'.getIcon('next').'</span></button>'
				.'<button type="button" class="next_range" value=""><span class="icon" data-category="increase">'.getIcon('next').getIcon('next').'</span></button>'
			.'</menu>';
		
		$return .= '</section>';
		
		if ($view == 'day') {
			
			$return .= '<dl class="day">';
			
			foreach (($arr_events[$date_range_start->format('Y-m-d')] ?: []) as $value) {
				$return .= '<div>
					<dt id="'.$value['id'].'-'.str2URL($value['name']).'">'.createDate($date_range_start->getTimestamp()).createTime($value["date"]).createTime($value["date_end"]).'</dt>
					<dd>
						<h1><span style="background-color: #'.$value['color'].';"></span>'.strEscapeHTML($value['name']).'</h1>
						<div class="body">'.parseBody($value['description']).'</div>
					</dd>
				</div>';
			}
			
			$return .= '</dl>';
		} else {
			$return .= '<dl class="range">';
			
				$period = new DatePeriod($date_range_start, $interval, $date_range_end);

				foreach ($period as $cur_date) {
					
					if ($arr_events[$cur_date->format("Y-m-d")]) {
						
						$return .= '<div>
							<dt>'.createDate($cur_date->getTimestamp()).'</dt>
							<div>';
							
								foreach ($arr_events[$cur_date->format("Y-m-d")] as $value) {
									$return .= '<dd><span style="background-color: #'.$value["color"].';"></span><span>'.date("H:i", strtotime($value["date"])).' - '.date("H:i", strtotime($value["date_end"])).'</span><a href="'.SiteStartVars::getModuleURL($this->mod_id).'day/'.$cur_date->format("d-m-Y").'#'.$value["id"].'-'.str2URL($value["name"]).'">'.strEscapeHTML($value["name"]).'</a></dd>';
								}
							$return .= '</div>
						</div>';
					}
				}
		
			$return .= '</dl>';
		}
										
		return $return;
	}
	
	public static function css() {
	
		$return = '.calendar { text-align: center; }
					.calendar > * { text-align: left; }
					.calendar > section { display: inline-block; }
					.calendar > section > figure { display: inline-block; vertical-align: bottom; position: relative; margin-left: 1px; margin-top: 18px; margin-bottom: 18px; }
					.calendar > section > figure.month > figcaption,
					.calendar > section > figure.week > figcaption,
					.calendar > section > figure.day > figcaption { position: absolute; top: -18px; left: 50%; text-align: center; }
					.calendar > section > figure.month > figcaption > span,
					.calendar > section > figure.week > figcaption > span,
					.calendar > section > figure.day > figcaption > span { position: absolute; left: -50%; width: 100%; font-weight: bold; }
					.calendar > section > figure > span.range { position: absolute; bottom: -14px; width: 100%; height: 8px; background-color: var(--back-super); }
					
					.calendar > section > figure.month { width: 8px; background-color: var(--back-super); height: 140px; }
					.calendar > section > figure.month > figcaption { width: 60px; }
					
					.calendar > section > figure.week { width: 6px; background-color: #e1e1e1; height: 115px; }
					.calendar > section > figure.week > figcaption { width: 20px; }
					
					.calendar > section > figure.day { width: 8px; height: 100px; }
					.calendar > section > figure.day > figcaption { display: none; top: -8px; width: 80px; z-index: 1; }
					.calendar > section > figure.day > figcaption > span { padding: 2px 0px; background-color: var(--back-super); color: #ffffff; }
					.calendar > section > figure.day > span.today { position: absolute; top: -9px; width: 100%; height: 8px; background-color: var(--back-super); }
					.calendar > section > figure.day:hover > figcaption { display: block; }
					.calendar > section > figure.day > div { height: 100%; background-color: var(--back); }
					.calendar > section > figure.day > div > span { display: block; width: 100%; border-top: 1px solid #ffffff; box-sizing:border-box; -moz-box-sizing:border-box; -webkit-box-sizing:border-box; }
					.calendar > section > figure.day > div > span:first-child { border-top-width: 0px; }
					
					.calendar > section > menu { text-align: center; }
					.calendar > section > menu > .prev_day { margin-right: 10px; }
					.calendar > section > menu > .prev_day + span + button { margin: 0px 10px; }
					.calendar > section > menu > .next_day { margin-left: 10px; }

					.calendar > section:after { content: "."; display: block; height: 0; clear: both; visibility: hidden; font-size: 0px; }
					
					.calendar > dl.range { display: table; margin: -10px 0px; border-collapse: separate; border-spacing: 0px 10px; }
					.calendar > dl.day { display: table; margin: -15px 0px; border-collapse: separate; border-spacing: 0px 15px; }
					.calendar > dl:empty { display: block; margin: 0px; }
					.calendar > dl > div { display: table-row; }
					.calendar > dl > div:first-child { padding-top: 0px; }
					.calendar > dl dt,
					.calendar > dl.range dt + div,
					.calendar > dl.day dt + dd { display: table-cell; vertical-align: top; }
					.calendar > dl dt > .date { font-size: 0.8em; }
					.calendar > dl dt > .time { font-size: 0.7em; }
					.calendar > dl.range dt + div,
					.calendar > dl.day dt + dd { padding-left: 10px; }
					
					.calendar > dl.range dd { margin-top: 2px; }
					.calendar > dl.range dd:first-child { margin-top: 0px; }
					.calendar > dl.range dd > span,
					.calendar > dl.range dd > a { display: inline-block; vertical-align: middle; margin-left: 10px; }
					.calendar > dl.range dd > span:first-child { display: inline-block; width: 20px; height: 1.26em; margin-left: 0px; }
					
					.calendar > dl.day dt > .date:first-child + .time { margin-top: 4px; }
					.calendar > dl.day dt > .time { margin-top: 2px; color: var(--text); }
					.calendar > dl.day dd > h1 { margin-top: 0px; }
					.calendar > dl.day dd > h1 > span { display: inline-block; vertical-align: text-top; margin-right: 8px; width: 20px; height: 1.26em; }';
					
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.calendar', function(elm_scripter) {
		
			elm_scripter.on('click', 'menu > .prev_day, menu > .prev_range, menu > .next_day, menu > .next_range, menu > .view_day, menu > .view_range', function() {
				$(this).closest('[id^=x\\\:calendar\\\:scroll-]').data('value', {'view': elm_scripter.find('.view_day, .view_range').attr('class')});
				$(this).quickCommand(elm_scripter);
			}).on('click', 'a', function() {
				if (this.hash) {
					window.location.reload(true);
				}
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// INTERACT
		
		if (($method == "prev_range" || $method == "next_range" || $method == "prev_day" || $method == "next_day") && $id) {
			
			$id = explode("_", $id);

			$this->html = self::contents((int)$id[($method == "next_day" || $method == "next_range" ? 1 : 0)], $method, ($value["view"] == "view_range" ? "range" : "day"));
		}
		
		if (($method == "view_range" || $method == "view_day") && $id) {
				
			$id = explode("_", $id);
				
			$this->html = self::contents((int)$id[0], "next_range", ($value["view"] == "view_range" ? "day" : "range"));
		}
		// QUERY
		
	}
}
