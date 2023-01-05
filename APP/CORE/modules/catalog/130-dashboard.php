<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class dashboard extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_dashboard');
		static::$parent_label = getLabel('ttl_site');
	}
		
	public static function moduleVariables() {
	
		$return = '<select>';
		$return .= cms_general::createDropdown(cms_dashboards::getDashboards());
		$return .= '</select>';
		
		return $return;
	}
	
	public function contents() {
	
		if ($this->arr_query[0] == 'jump') {
			
			Response::location(pages::getPageUrl(pages::getMods((int)$this->arr_query[1])));
			die;
		}	
			
		$dashboard_options = cms_dashboards::getDashboards($this->arr_variables);
		
		$arr_widgets = cms_dashboards::getWidgets($this->arr_variables, $_SESSION['USER_ID']);

		$arr_modules = pages::getMods(arrValuesRecursive('module_id', $arr_widgets));		
		$arr_modules = pages::filterClearance($arr_modules, $_SESSION['USER_GROUP'], $_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]);

		$arr_columns = [];
		$arr_gaps = [];
		$arr_last_y = [];
		
		foreach ($arr_widgets as $arr_widget) {
			
			if (!$arr_modules[$arr_widget['module_id']]) {
				continue;
			}
			
			$cur_x = (isset($arr_widget['user_x']) ? $arr_widget['user_x'] : $arr_widget['x']);

			if ($arr_widget['override']) { // First set overridden widget positions
				
				$cur_y = $arr_widget['user_y'];
				$last_y = $arr_last_y[$cur_x];
				if ($cur_y > 0 && !isset($arr_last_y[$cur_x])) { // Store gaps which default widgets can fill later on
					$arr_gaps[$cur_x][] = 0;
				}
				$gap = $cur_y - $last_y;
				if ($gap > 1) { // Store gaps which default widgets can fill later on
					for ($i = 1; $i < $gap; $i++) {
						$arr_gaps[$cur_x][] = $last_y+$i;
					}
				}
				$arr_last_y[$cur_x] = $cur_y;
			} else if ($arr_gaps[$cur_x]) { // If gap exists, use it for default widgets
				
				$cur_y = array_shift($arr_gaps[$cur_x]);
			} else if (isset($arr_last_y[$cur_x])) { // If no more gaps exist, use last overridden position to continue
				
				$cur_y = $arr_widget['y']+($arr_last_y[$cur_x]-$arr_widget['y'])+1;
			} else { // Otherwise just place default widget
				
				$cur_y = $arr_widget['y'];
			}
		
			$arr_columns[$cur_x][$cur_y] = $this->createWidget($arr_widget, $arr_modules[$arr_widget['module_id']]);
		}
		
		$return .= '<div'.($_SESSION['USER_ID'] ? ' id="y:dashboard:update-'.$dashboard_options['module_id'].'_'.$dashboard_options['method'].'"' : '').'>';

		for ($i = 0; $i < $dashboard_options['columns']; $i++) {
			
			if ($arr_columns[$i]) {
				ksort($arr_columns[$i]);
			}
			
			$return .= '<ul class="mod-spacing">'.($arr_columns[$i] ? implode('', $arr_columns[$i]) : '').'</ul>';
		}
		
		$return .= '</div>';

		return $return;
	}
	
	public function createWidget($arr_widget, $module_row) {

		$mod = new $module_row['module'];
		$mod->setMod($module_row, $module_row['id']);
		$mod->setModVariables($module_row['var']);
		
		$mod_widget_properties = $mod::widgetProperties();
		
		$method = $arr_widget['method'];
		
		$return = '<li id="widget-'.$arr_widget['module_id'].'_'.$arr_widget['method'].'" class="mod-spacing widget '.$module_row['module'].($arr_widget['locked'] ? ' locked' : '').((isset($arr_widget['user_min']) && $arr_widget['user_min']) || (!isset($arr_widget['user_min']) && $arr_widget['min']) ? ' min' : '').'">
			<h3><span>'.$mod_widget_properties[$arr_widget['method']]['label'].'</span><ul><li class="size" title="Toggle"></li>'.($arr_widget['linked'] ? '<li title="Go to module"><a href="'.SiteStartVars::getModUrl($this->mod_id).'jump/'.$arr_widget['module_id'].'" target="_blank"></a></li>' : '').'</ul></h3>
			<div>'.$mod->$method().'</div>
		</li>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '.dashboard > div { width: 100%; display: flex; flex-flow: row nowrap; min-height: 40px; }
				.dashboard > div > ul { flex: 1 1 100%; vertical-align: top; overflow: hidden; margin-top: 0px !important; margin-bottom: 0px !important; margin-right: 0px !important; }
				.dashboard > div > ul:first-child { margin-left: 0px !important; }
				.dashboard > div > ul > li { margin-left: 0px !important; margin-right: 0px !important; margin-bottom: 0px !important; }
				.dashboard > div > ul > li:first-child { margin-top: 0px !important; }
				.dashboard > div > ul > li h3 { font-size: 12px; margin: 0px; padding: 0px 4px; color: #ffffff; background-color: #00497e; line-height: 24px; }
				.dashboard > div > ul > li h3 > span { vertical-align: middle; }
				.dashboard > div > ul > li:not(.locked) h3 > span { cursor: move; }
				.dashboard > div > ul > li h3 > ul { float: right; }
				.dashboard > div > ul > li h3 > ul > li { cursor: pointer; display: inline-block; vertical-align: middle; margin-left: 4px; }
				.dashboard > div > ul > li h3 > ul > li.size { width: 14px; height: 14px; background: url(/css/images/widget_buttons_w.png) no-repeat 0px 0px; }
				.dashboard > div > ul > li.min h3 > ul > li.size { background-position: -14px 0px; }
				.dashboard > div > ul > li.min > div { display: none; }
				.dashboard > div > ul > li h3 > ul > li a { display: block; width: 14px; height: 14px; background: url(/css/images/widget_buttons_w.png) no-repeat -28px 0px; }
				.dashboard > div > ul > li > div { padding: 4px; padding-bottom: 0px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.dashboard', function(elm_scripter) {
			
			var elm_sortsorter = elm_scripter.children('div');
			
			new SortSorter(elm_sortsorter, {
				container: '.dashboard > div > ul',
				items: '> li:not(.locked)',
				handle: '> li > h3 > span',
				call_update: function(elm) {
					func_update_position(elm);
				}
			});
			
			var func_update_position = function(elm) {
			
				var id = elm.attr('id').split('-')[1];
				var x = elm.closest('ul').index();
				var y = elm.index();
				var elm_target = elm.closest('[id^=y\\\:dashboard\\\:update]');
				if (elm_target.length) {
					COMMANDS.setData(elm_target[0], {widget_id: id, x: x, y: y});
					elm_target.quickCommand(false);
				}
			};
			var func_update_atrribute = function(elm, attr, val) {
			
				var id = elm.attr('id').split('-')[1];
				var elm_target = elm.closest('[id^=y\\\:dashboard\\\:update]');
				if (elm_target.length) {
					COMMANDS.setData(elm_target[0], {widget_id: id, attr: attr, val: val});
					elm_target.quickCommand(false);
				}
			};
			
			elm_scripter.on('click', '.widget > h3 .size', function() {
			
				var elm_target = $(this).closest('.widget');
				
				if (elm_target.hasClass('min')) {
					elm_target.children('div').slideDown('fast', function() {
						elm_target.removeClass('min');
						func_update_atrribute(elm_target, 'min', 0);
					});
				} else {
					elm_target.children('div').slideUp('fast', function() {
						elm_target.addClass('min');
						func_update_atrribute(elm_target, 'min', 1);
					});
				}
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY
		
		if ($method = "update" && $_SESSION['USER_ID']) {
			
			$arr_id = explode('_', $value['widget_id']);
			$module_id = (int)$arr_id[0];
			$method = DBFunctions::strEscape($arr_id[1]);

			$res = DB::query("SELECT
				u.module_id, u.method, u.x AS user_x, u.y AS user_y, w.*
					FROM ".DB::getTable('TABLE_DASHBOARD_WIDGETS')." w
					LEFT JOIN ".DB::getTable('TABLE_DASHBOARD_WIDGET_DATA')." u ON (u.dashboard_id = w.dashboard_id AND u.module_id = w.module_id AND u.method = w.method AND u.user_id = ".$_SESSION['USER_ID'].")
				WHERE w.dashboard_id = ".$this->arr_variables." AND w.module_id = ".$module_id." AND w.method = '".$method."'
			");
			
			$arr_row = $res->fetchAssoc();
			
			if (isset($value['x'])) {
			
				$res = DB::queryMulti("
					".DBFunctions::updateWith(
						DB::getTable('TABLE_DASHBOARD_WIDGET_DATA'), 'u', ['dashboard_id', 'module_id', 'method'],
						"JOIN ".DB::getTable('TABLE_DASHBOARD_WIDGETS')." w ON (w.dashboard_id = ".$this->arr_variables." AND w.dashboard_id = u.dashboard_id AND w.module_id = u.module_id AND w.method = u.method)",
						['y' => 'u.y-1']
					)."
						AND u.y > ".(int)(isset($arr_row['user_y']) ? $arr_row['user_y'] : $arr_row['y'])."
						AND u.x = ".(int)(isset($arr_row['user_x']) ? $arr_row['user_x'] : $arr_row['x'])."
						AND u.user_id = ".$_SESSION['USER_ID']."
					;
					".DBFunctions::updateWith(
						DB::getTable('TABLE_DASHBOARD_WIDGET_DATA'), 'u', ['dashboard_id', 'module_id', 'method'],
						"JOIN ".DB::getTable('TABLE_DASHBOARD_WIDGETS')." w ON (w.dashboard_id = ".$this->arr_variables." AND w.dashboard_id = u.dashboard_id AND w.module_id = u.module_id AND w.method = u.method)",
						['y' => 'u.y+1']
					)."
						AND u.y >= ".(int)$value['y']."
						AND u.x = ".(int)$value['x']."
						AND u.user_id = ".$_SESSION['USER_ID']."
					;
				");
				
				if ((int)$value['x'] != $arr_row['x'] || (int)$value['y'] != $arr_row['y']) {
				
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_DASHBOARD_WIDGET_DATA')."
						(dashboard_id, module_id, method, user_id, x, y)
							VALUES
						(".$this->arr_variables.", ".$module_id.", '".$method."', ".$_SESSION['USER_ID'].", ".(int)$value['x'].", ".(int)$value['y'].")
						".DBFunctions::onConflict('dashboard_id, module_id, method, user_id', ['x', 'y'])."
					");
				} else if ($arr_row['module_id']) {
				
					$res = DB::query("UPDATE ".DB::getTable('TABLE_DASHBOARD_WIDGET_DATA')."
						SET x = NULL, y = NULL
						WHERE dashboard_id = ".$this->arr_variables." AND module_id = ".$module_id." AND method = '".$method."'
							AND user_id = ".$_SESSION['USER_ID']."
					");
					
					$del = true;
				}
				
			} else if ($value['attr']) {
			
				$attr = DBFunctions::strEscape($value['attr']);
			
				if ((bool)$value['val'] != DBFunctions::unescapeAs($arr_row[$value['attr']], DBFunctions::TYPE_BOOLEAN)) {
				
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_DASHBOARD_WIDGET_DATA')."
						(dashboard_id, module_id, method, user_id, ".$attr.")
							VALUES
						(".$this->arr_variables.", ".$module_id.", '".$method."', ".$_SESSION['USER_ID'].", ".DBFunctions::escapeAs($value['val'], DBFunctions::TYPE_BOOLEAN).")
						".DBFunctions::onConflict('dashboard_id, module_id, method, user_id', [$attr])."
					");
				} else if ($arr_row['module_id']) {
				
					$res = DB::query("UPDATE ".DB::getTable('TABLE_DASHBOARD_WIDGET_DATA')."
						SET ".$attr." = NULL
						WHERE dashboard_id = ".$this->arr_variables." AND module_id = ".$module_id." AND method = '".$method."'
							AND user_id = ".$_SESSION['USER_ID']."
					");
					
					$del = true;
				}
			}
				
			if ($del) {

				$res = DB::query("SELECT u.*
						FROM ".DB::getTable('TABLE_DASHBOARD_WIDGET_DATA')." u
					WHERE u.dashboard_id = ".$this->arr_variables." AND u.module_id = ".$module_id." AND u.method = '".$method."'
						AND u.user_id = ".$_SESSION['USER_ID']."
				");
				
				$arr_row = $res->fetchAssoc();
				
				$do_del = true;
				
				foreach (array_slice($arr_row, 2) as $column) {
					
					if (isset($column)) {
						$do_del = false;
					}
				}
				
				if ($do_del) {
					
					$res = DB::query("DELETE FROM ".DB::getTable('TABLE_DASHBOARD_WIDGET_DATA')."
						WHERE dashboard_id = ".$this->arr_variables." AND module_id = ".$module_id." AND method = '".$method."'
							AND user_id = ".$_SESSION['USER_ID']."
					");
				}
			}
		}
	}
}
