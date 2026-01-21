<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('CMS_DASHBOARD_WIDGETS', DB::$database_cms.'.cms_dashboard_widgets');

class cms_dashboard extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
		
	public function contents() {
		
		$arr_modules = SiteStartEnvironment::getModules();
		$arr_module_widget_properties = self::getModuleWidgetProperties($arr_modules);
		$arr_widgets_user = self::getWidgetsUser($_SESSION['USER_ID']);

		$arr_columns = [];
		
		foreach ($arr_module_widget_properties as $module => $module_widget) {
			
			foreach ($module_widget as $method => $arr_widget) {
			
				if (isset($arr_widgets_user[$module][$method])) {
					
					$arr_user = $arr_widgets_user[$module][$method];
					
					$arr_columns['user'][$arr_user['x']][$arr_user['y']] = self::createWidget($module, $method, $arr_widget, $arr_user);
				} else {
					
					$arr_columns['none'][] = self::createWidget($module, $method, $arr_widget);
				}
			}
		}
		if (isset($arr_columns['none'])) {
			$arr_columns['none'] = arrChuckPartition($arr_columns['none'], 3);
		}
		
		$return = '<div>
			<div id="y:cms_dashboard:update-0">';

				for ($i = 0; $i < 3; $i++) {
					
					if (isset($arr_columns['user'][$i])) {
						ksort($arr_columns['user'][$i]);
					}
					
					$return .= '<ul>'.(isset($arr_columns['user'][$i]) ? implode('', $arr_columns['user'][$i]) : '').(isset($arr_columns['none'][$i]) ? implode('', $arr_columns['none'][$i]) : '').'</ul>';
				}
				
			$return .= '</div>
		</div>';

		return $return;
	}
		
	public static function css() {
	
		$return = '#mod-cms_dashboard > div { }
				#mod-cms_dashboard > div > div { display: flex; flex-flow: row nowrap; min-height: 100px; }
				#mod-cms_dashboard > div > div > ul { flex: 1 1 100%; vertical-align: top; overflow: hidden; }
				#mod-cms_dashboard > div > div > ul + ul { margin-left: 12px; }
				#mod-cms_dashboard > div > div > ul > li { display: block; margin-top: 10px; box-sizing: border-box; }
				#mod-cms_dashboard > div > div > ul > li:first-child { margin-top: 0px; }
				#mod-cms_dashboard > div > div > ul > li > h2 > span { cursor: move; }
				#mod-cms_dashboard > div > div > ul > li > h2 > ul { float: right; }
				#mod-cms_dashboard > div > div > ul > li > h2 > ul > li { cursor: pointer; display: inline-block; vertical-align: middle; margin-left: 4px; }
				#mod-cms_dashboard > div > div > ul > li > h2 > ul > li.size { width: 14px; height: 14px; background: url(/css/images/widget_buttons.png) no-repeat 0px 0px; }
				#mod-cms_dashboard > div > div > ul > li.min > h2 > ul > li.size { background-position: -14px 0px; }
				#mod-cms_dashboard > div > div > ul > li.min > h2 + div { display: none; }
				
				#mod-cms_dashboard > div > div > ul > li > div > table.list { width: 100%; }
				
				#mod-cms_dashboard .widget table th,
				#mod-cms_dashboard .widget table td { padding-right: 10px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-cms_dashboard', function(elm_scripter) {
					
					var elm_sortsorter = elm_scripter.find('> div > div');
					
					new SortSorter(elm_sortsorter, {
						container: '#mod-cms_dashboard > div > div > ul',
						handle: 'h2 > span',
						items: '> li',
						call_update: function() {
							func_update_position();
						}
					});
					
					var func_update_position = function(elm) {
						var arr = elm_scripter.find('> div > div > ul > .widget').map(function() {
							return {widget_id: $(this).attr('id'), x: $(this).closest('ul').index(), y: $(this).index()};
						}).get();
						var elm_target = $('[id^=y\\\:cms_dashboard\\\:update]');
						COMMANDS.setData(elm_target[0], arr);
						elm_target.quickCommand(false);
					};
					var func_update_attribute = function(elm, attr, val) {
						var elm_target = elm.closest('[id^=y\\\:cms_dashboard\\\:update]');
						COMMANDS.setData(elm_target[0], {widget_id: elm.attr('id'), attr: attr, val: val, x: elm.closest('ul').index(), y: elm.index()});
						elm_target.quickCommand(false);
					};
					
					elm_scripter.find('.widget > h2 .size').on('click', function() {
						var target = $(this).closest('.widget');
						if (target.hasClass('min')) {
							target.children('div').slideDown('fast', function() {
								target.removeClass('min');
								func_update_attribute(target, 'min', 0);
							});
						} else {
							target.children('div').slideUp('fast', function() {
								target.addClass('min');
								func_update_attribute(target, 'min', 1);
							});
						}
					});
				});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
		
		if ($method = "update") {
				
			if ($value['attr']) {
			
				$widget_id = DBFunctions::arrEscape(explode(':', $value['widget_id']));
				
				switch ($value['attr']) {
					
					case 'min':
					default:
						$sql_column = 'min';
						$sql_value = DBFunctions::escapeAs($value['val'], DBFunctions::TYPE_BOOLEAN);
						break;
				}
			
				$res = DB::query("INSERT INTO ".DB::getTable('CMS_DASHBOARD_WIDGETS')."
					(user_id, module, method, x, y, ".$sql_column.")
						VALUES
					(
						".$_SESSION['USER_ID'].",
						'".$widget_id[0]."',
						'".$widget_id[1]."',
						".(int)$value['x'].",
						".(int)$value['y'].",
						".$sql_value."
					)
					".DBFunctions::onConflict('user_id, module, method', ['x', 'y', $sql_column])."
				");
			} else {
				
				$arr_ids = [];
				
				foreach ($value as $arr_widget) {
				
					$widget_id = DBFunctions::arrEscape(explode(":", $arr_widget['widget_id']));

					$res = DB::query("INSERT INTO ".DB::getTable('CMS_DASHBOARD_WIDGETS')."
						(user_id, module, method, x, y)
							VALUES
						(
							".$_SESSION['USER_ID'].",
							'".$widget_id[0]."',
							'".$widget_id[1]."',
							".(int)$arr_widget['x'].",
							".(int)$arr_widget['y']."
						)
						".DBFunctions::onConflict('user_id, module, method', ['x', 'y'])."
					");
					
					$arr_ids[] = $widget_id[0].'.'.$widget_id[1];
				}
				
				$res = DB::query("DELETE FROM ".DB::getTable('CMS_DASHBOARD_WIDGETS')."
					WHERE user_id = ".$_SESSION['USER_ID']."
						AND CONCAT(module, '.', method) NOT IN ('".implode("','", $arr_ids)."')
				");
			}
		}
		
		// QUERY
		
	}
	
	private static function createWidget($module, $method, $arr_widget, $arr_user = []) {
			
		$mod = new $module;
		
		$min = (($arr_user['min'] === null && $arr_widget['min']) || $arr_user['min'] ? ' min' : '');

		$return = '<li id="'.$module.':'.$method.'" class="widget section '.$module.$min.'">
			<h2><span>'.$arr_widget['label'].'</span><ul><li class="size" title="Toggle"></li></ul></h2>
			<div>'.$mod->$method().'</div>
		</li>';
		
		return $return;
	}
	
	private static function getModuleWidgetProperties($modules) {

		$arr_widget_properties = [];

		foreach ($modules as $module => $value) {
			
			if (method_exists($module, 'widgetProperties')) {
				$arr_widget_properties[$module] = $module::widgetProperties();
			}
		}

		return $arr_widget_properties;
	}
	
	private static function getWidgetsUser($user_id) {

		$arr = [];

		$res = DB::query("SELECT w.*
				FROM ".DB::getTable('CMS_DASHBOARD_WIDGETS')." w
			WHERE w.user_id = ".(int)$user_id."
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['min'] = DBFunctions::unescapeAs($arr_row['min'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[$arr_row['module']][$arr_row['method']] = $arr_row;
		}

		return $arr;
	}
}
