<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_DASHBOARDS', DB::$database_home.'.def_dashboards');
DB::setTable('TABLE_DASHBOARD_WIDGETS', DB::$database_home.'.def_dashboard_widgets');
DB::setTable('TABLE_DASHBOARD_WIDGET_DATA', DB::$database_home.'.data_dashboard_widgets');

class cms_dashboards extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_dashboards');
		static::$parent_label = getLabel('ttl_site');
	}

	public function contents() {
		
		$return = '<div class="section">
			<h1 id="x:cms_dashboards:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup dashboard_add" value="add" /></h1>
			<div>';

				$res = DB::query("SELECT db.*,
						(SELECT
							COUNT(*)
								FROM ".DB::getTable('TABLE_DASHBOARD_WIDGETS')." dw
							WHERE dw.dashboard_id = db.id
						) AS widget_count,
						(SELECT ".DBFunctions::group2String("CONCAT(wm.module, ' (', dw.method, ')')", '<br />')."
								FROM ".DB::getTable('TABLE_DASHBOARD_WIDGETS')." dw
								LEFT JOIN ".DB::getTable('TABLE_PAGE_MODULES')." wm ON (wm.id = dw.module_id)
							WHERE dw.dashboard_id = db.id
						) AS widgets,
						".DBFunctions::group2String(DBFunctions::castAs('d.id', DBFunctions::CAST_TYPE_STRING), ',', 'ORDER BY p.id')." AS directories,
						".DBFunctions::group2String('p.name', ',', 'ORDER BY p.id')." AS pages
					FROM ".DB::getTable('TABLE_DASHBOARDS')." db
					LEFT JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (".DBFunctions::castAs('m.var', DBFunctions::CAST_TYPE_INTEGER)." = db.id AND m.module = 'dashboard')
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
					LEFT JOIN ".DB::getTable('TABLE_DIRECTORIES')." d ON (d.id = p.directory_id)
					GROUP BY db.id
				");
			
				if ($res->getRowCount() == 0) {
					$return .= '<section class="info">'.getLabel('msg_no_dashboards').'</section>';
				} else {
			
					$return .= '<table class="list">
						<thead>
							<tr>
								<th>'.getLabel('lbl_name').'</th>
								<th>'.getLabel('lbl_path').'</th>
								<th>'.getLabel('lbl_columns').'</th>
								<th>'.getLabel('lbl_widgets').'</th>
							</tr>
						</thead>
						<tbody>';
							while ($arr_row = $res->fetchAssoc()) {
								
								$arr_pages = str2Array($arr_row['pages'], ',');
								$arr_directories = array_filter(str2Array($arr_row['directories'], ','));
								$arr_paths = [];
								
								for ($i = 0; $i < count($arr_directories); $i++) {
									$arr_dir = directories::getDirectories($arr_directories[$i]);
									if ($arr_dir['id']) {
										$arr_paths[] = $arr_dir['path'].' / '.$arr_pages[$i];
									}
								}
								
								$arr_paths = array_unique($arr_paths);

								$return .= '<tr id="x:cms_dashboards:dashboard_id-'.$arr_row['id'].'">
									<td>'.$arr_row['name'].'</td>
									<td><span class="info"><span class="icon" title="'.($arr_paths ? implode('<br />', $arr_paths) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.count($arr_paths).'</span></span></td>
									<td>'.$arr_row['columns'].'</td>
									<td><span class="info"><span class="icon" title="'.($arr_row['widgets'] ?: getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)$arr_row['widget_count'].'</span></span></td>
									<td><input type="button" class="data edit popup dashboard_edit" value="edit" /><input type="button" class="data del msg dashboard_del" value="del" /></td>
								</tr>';
							}
						$return .= '</tbody>
					</table>';
				}
							
			$return .= '</div>
		</div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '#frm-dashboard input[name=columns] { width: 25px; }
				#frm-dashboard .dashboard { white-space: nowrap; }
				#frm-dashboard .dashboard > div { white-space: normal; }
				#frm-dashboard .dashboard > div,
				#frm-dashboard .dashboard > div > ul > li { box-sizing: border-box; }
				#frm-dashboard .dashboard > div > ul { padding-bottom: 70px; }
				#frm-dashboard .dashboard > div > ul > li { position: relative; margin-top: 10px; width: 180px; height: 90px; border-radius: 2px; }
				#frm-dashboard .dashboard > div > ul > li:hover { background-color: #e7f2f9; }
				#frm-dashboard .dashboard > div > ul > li ul { text-align: left; margin: 5px 15px 0px 5px; }
				#frm-dashboard .dashboard > div > ul > li ul li:first-child + li + li { margin-top: 4px; }
				#frm-dashboard .dashboard > div > ul > li ul li:first-child { font-weight: bold; }
				#frm-dashboard .dashboard > div > ul > li ul li > span { white-space: nowrap; }
				#frm-dashboard .dashboard > div { width: 180px; margin-left: 10px; display: inline-block; vertical-align: top; }
				#frm-dashboard .dashboard > div.widgets { margin-left: 0px; }
				#frm-dashboard .dashboard h3 { margin: 0px; padding: 5px 5px; font-size: 14px; font-weight: bold; }
				#frm-dashboard .dashboard > div.widgets > h3 { background-color: #ffdcdc; }
				#frm-dashboard .dashboard > div.column > h3 { background-color: #d6fdd3; }
				';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('#frm-dashboard', function(elm_scripter) {
		
			elm_dashboard = elm_scripter.find('.dashboard');
		
			var func_bind_sorter = function() {
				
				new SortSorter(elm_dashboard, {
					container: 'div > ul',
					items: '> li',
					handle: '.handle'
				});
			};
			var func_update_columns = function() {
				
				elm_scripter.find('input[name=columns]').val(elm_dashboard.find('.column').length);
			};

			func_bind_sorter();
			elm_dashboard.find('.widget ul li > span').each(function() {
				fitText(this);
			});

			elm_scripter.on('ajaxsubmit', function() {

				elm_dashboard.find('.widgets > ul > .widget').each(function() {
					var cur = $(this);
					cur.children('input.pos').val('');
				});
				elm_dashboard.find('.column > ul > .widget').each(function() {
					var cur = $(this);
					cur.children('input.pos').val(cur.closest('.column').index()-1+'_'+cur.index());
				});
				
				elm_scripter.find('input[name=columns]').prop('disabled', false);
			}).on('change', 'select[name=directory]', function() {
				elm_dashboard.find('.widgets > ul, .column > ul').empty();
				$(this).quickCommand(elm_dashboard.find('.widgets > ul'));
			}).on('click', 'input[name=columns] ~ .add', function() {
				var new_column = elm_dashboard.find('.column:first').clone();
				new_column.children('ul').empty();
				new_column.appendTo(elm_dashboard);
				func_update_columns();
				func_bind_sorter();
			}).on('click', 'input[name=columns] ~ .del', function() {
				var target_column = elm_dashboard.find('.column:last');
				if (!target_column.prev().is('.widgets') && target_column.children('ul').is(':empty')) {
					target_column.remove();
					func_update_columns();
					func_bind_sorter();
				}
			});
		})";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "dashboard_edit" || $method == "dashboard_add") {
		
			if ($id) {
				
				$res = DB::query("SELECT db.*
									FROM ".DB::getTable('TABLE_DASHBOARDS')." db
									WHERE db.id = ".(int)$id."
				");
				
				$row = $res->fetchAssoc();
																
				$mode = "dashboard_update";
			} else {
				$mode = "dashboard_insert";
			}
																	
			$this->html = '<form id="frm-dashboard" data-method="'.$mode.'">
				
				<fieldset><ul>
					<li>
						<label>Name</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($row['name']).'"></div>
					</li>
					<li>
						<label>Directory</label>
						<div><select name="directory" id="y:cms_dashboards:directory_select-0">'.directories::createDirectoriesDropdown(directories::getDirectories(), $row['directory_id']).'</select></div>
					</li>
					<li>
						<label>Columns</label>
						<div><input type="text" name="columns" value="'.($row['columns'] ?: 2).'" disabled="disabled" /><input type="button" class="data add" value="add" /><input type="button" class="data del" value="del" /></div>
					</li>
					<li>
						<label>Dashboard</label>
						<div>'.self::createDashboardEditor(self::createWidgets($row['directory_id'], ($mode == 'dashboard_update' ? self::getWidgets($row['id'], 0, true) : [])), ($row['columns'] ?: 2)).'</div>
					</li>
				</ul></fieldset>
					
			</form>';
			
			$this->validate = ['name' => 'required'];
		}
		
		// POPUP INTERACT
		
		if ($method == "directory_select") {
			
			$this->html = implode('', arrValuesRecursive('html', self::createWidgets((int)$value)));
		}
							
		// QUERY
	
		if ($method == "dashboard_insert") {
								
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_DASHBOARDS')."
				(name, directory_id, columns)
					VALUES
				('".DBFunctions::strEscape($_POST['name'])."', ".(int)$_POST['directory'].", ".(int)$_POST['columns'].")
			");

			$id = DB::lastInsertID();
						
			self::updateDashboardWidgets($id);
			
			$this->refresh = true;
			$this->msg = true;
		}
		
		if ($method == "dashboard_update" && (int)$id) {
						
			$res = DB::query("UPDATE ".DB::getTable('TABLE_DASHBOARDS')." SET
					name = '".DBFunctions::strEscape($_POST['name'])."',
					directory_id = ".(int)$_POST['directory'].",
					columns = ".(int)$_POST['columns']."
				WHERE id = ".(int)$id."
			");
						
			self::updateDashboardWidgets($id);
			
			$this->refresh = true;
			$this->msg = true;
		}
			
		if ($method == "dashboard_del" && (int)$id) {
		
			$res = DB::queryMulti("
				DELETE FROM ".DB::getTable('TABLE_DASHBOARDS')."
					WHERE id = ".(int)$id."
				;
					
				DELETE FROM ".DB::getTable('TABLE_DASHBOARD_WIDGETS')."
					WHERE dashboard_id = ".(int)$id."
				;
			");
			
			$this->msg = true;
		}
	}
	
	private static function updateDashboardWidgets($id) {

		if ($_POST['widgets']) {
			
			$arr_ids = [];
			
			foreach ($_POST['widgets'] as $key => $arr_methods) {
				
				foreach ($arr_methods as $method => $value) {
					
					if (!$value['pos']) {
						continue;
					}
					
					$pos_xy = explode('_', $value['pos']);
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_DASHBOARD_WIDGETS')."
						(dashboard_id, module_id, method, x, y, min, locked, linked)
							VALUES
						(".(int)$id.", ".(int)$key.", '".DBFunctions::strEscape($method)."', ".(int)$pos_xy[0].", ".(int)$pos_xy[1].", ".DBFunctions::escapeAs($value['min'], DBFunctions::TYPE_BOOLEAN).", ".DBFunctions::escapeAs($value['locked'], DBFunctions::TYPE_BOOLEAN).", ".DBFunctions::escapeAs($value['linked'], DBFunctions::TYPE_BOOLEAN).")
						".DBFunctions::onConflict('dashboard_id, module_id, method', ['x', 'y', 'min', 'locked', 'linked'])."
					");
					
					$arr_ids[] = "(dashboard_id = ".(int)$id." AND module_id = ".(int)$key." AND method = '".DBFunctions::strEscape($method)."')";
				}
			}
						
			$res = DB::query("DELETE FROM ".DB::getTable('TABLE_DASHBOARD_WIDGETS')."
				WHERE NOT ".implode(" AND NOT ", $arr_ids)
			);
		}
	}
	
	private static function createWidgets($directory_id = 0, $arr_widgets = []) {
	
		$directory_id = ($directory_id ?: directories::getRootDirectory());
		
		$arr_modules = getModules(DIR_HOME);
		$arr_module_widget_properties = self::getModuleWidgetProperties($arr_modules);
		$arr_dir_modules = pages::getDirectoryModules($directory_id);

		$arr = [];
		
		foreach ($arr_dir_modules as $row) {
			
			$mod_id = $row['id'];
			
			if ($arr_module_widget_properties[$row['module']]) {
				
				$module = $row['module'];
				
				$module::moduleProperties();
				$module_label = $module::$label;
				
				$arr_directory = directories::getDirectories($row['directory_id']);
				
				foreach (($arr_module_widget_properties[$module] ?: []) as $method => $arr_method_properties) {
					
					$widget = '<li class="widget object"><div class="handle"><span class="icon">'.getIcon('handle-grid').'</span></div>
						<input type="hidden" class="pos" name="widgets['.$mod_id.']['.$method.'][pos]" value="" />
						<input type="hidden" class="method" name="widgets['.$mod_id.']['.$method.'][method]" value="'.$method.'" />
						<ul class="title">
							<li><span>'.$module_label.'</span></li>
							<li><span>'.$arr_method_properties['label'].'</span></li>
							<li><span>'.$arr_directory['path'].' / '.$row['page_name'].'</span></li>
						</ul>
						<div class="object-info">'
							.'<label><input type="checkbox" value="1" name="widgets['.$mod_id.']['.$method.'][locked]"'.($arr_widgets[$mod_id][$method]['locked'] ? ' checked="checked"' : '').' /><span class="icon" title="Toggle: Allow widget to be moved">'.getIcon('locked').'</span></label>'
							.'<label><input type="checkbox" value="1" name="widgets['.$mod_id.']['.$method.'][linked]"'.($arr_widgets[$mod_id][$method]['linked'] ? ' checked="checked"' : '').' /><span class="icon" title="Toggle: Link to module">'.getIcon('linked').'</span></label>'
							.'<label><input type="checkbox" value="1" name="widgets['.$mod_id.']['.$method.'][min]"'.($arr_widgets[$mod_id][$method]['min'] ? ' checked="checked"' : '').' /><span class="icon" title="Toggle: Start minimized">'.getIcon('minimize').'</span></label>
						</div>
					</li>';
					
					$arr[] = ['x' => $arr_widgets[$mod_id][$method]['x'], 'y' => $arr_widgets[$mod_id][$method]['y'], 'html' => $widget];
				}
			}
		}
		
		if ($arr_widgets) {
			
			usort($arr, function($a, $b) {
				return $a['x'].'_'.$a['y'] <=> $b['x'].'_'.$b['y'];
			});
		}
		
		return $arr;
	}
	
	private static function createDashboardEditor($widgets, $columns) {

		$arr_columns = [];
		foreach ($widgets as $value) {
			$arr_columns[$value['x']] .= $value['html'];
		}
		
		$return = '<div class="dashboard">
			<div class="widgets">
				<h3>Inactive</h3>
				<ul>'.($arr_columns[''] ? $arr_columns[''] : '').'</ul>
			</div>';
		
		for ($i = 0; $i < $columns; $i++) {
			$return .= '<div class="column">
					<h3>Column</h3>
					<ul>'.($arr_columns[$i] ? $arr_columns[$i] : '').'</ul>
				</div>';
		}
		
		$return .= '</div>';
		
		return $return;
	}
	
	private static function getModuleWidgetProperties($modules, $module = false) {

		if ($module) {
			
			if (property_exists($module, 'widgets')) {
				return $module::$widgets;
			}
		} else {
			
			$arr_widget_properties = [];

			foreach ($modules as $module => $value) {
				if (method_exists($module, 'widgetProperties')) {
					$arr_widget_properties[$module] = $module::widgetProperties();
				}
			}

			return $arr_widget_properties;
		}
	}
	
	public static function getWidgets($dashboard_id, $user_id = 0, $by_module = false) {

		$arr = [];

		if ($user_id) {
			
			$res = DB::query("SELECT
				w.*,
				CASE
					WHEN u.dashboard_id != 0 THEN TRUE
					ELSE FALSE
				END AS override,
				u.x AS user_x, u.y AS user_y, u.min AS user_min
					FROM ".DB::getTable('TABLE_DASHBOARDS')." db
					JOIN ".DB::getTable('TABLE_DASHBOARD_WIDGETS')." w ON (w.dashboard_id = db.id)
					LEFT JOIN ".DB::getTable('TABLE_DASHBOARD_WIDGET_DATA')." u ON (u.dashboard_id = w.dashboard_id AND u.module_id = w.module_id AND u.method = w.method AND u.user_id = ".(int)$user_id.")
				WHERE db.id = ".(int)$dashboard_id."
				ORDER BY override DESC, user_x, user_y, w.x, w.y
			");
		} else {
			
			$res = DB::query("SELECT w.*
					FROM ".DB::getTable('TABLE_DASHBOARDS')." db
					JOIN ".DB::getTable('TABLE_DASHBOARD_WIDGETS')." w ON (w.dashboard_id = db.id)
				WHERE db.id = ".(int)$dashboard_id."
				ORDER BY x, y
			");
		}
		
		if ($by_module) {
			
			while($arr_row = $res->fetchAssoc()) {
				
				$arr_row['min'] = DBFunctions::unescapeAs($arr_row['min'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['locked'] = DBFunctions::unescapeAs($arr_row['locked'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['linked'] = DBFunctions::unescapeAs($arr_row['linked'], DBFunctions::TYPE_BOOLEAN);
				if ($user_id) {
					$arr_row['override'] = DBFunctions::unescapeAs($arr_row['override'], DBFunctions::TYPE_BOOLEAN);
					$arr_row['user_min'] = DBFunctions::unescapeAs($arr_row['user_min'], DBFunctions::TYPE_BOOLEAN);
				}
				
				$arr[$arr_row['module_id']][$arr_row['method']] = $arr_row;
			}
		} else {
			
			while($arr_row = $res->fetchAssoc()) {
				
				$arr_row['min'] = DBFunctions::unescapeAs($arr_row['min'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['locked'] = DBFunctions::unescapeAs($arr_row['locked'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['linked'] = DBFunctions::unescapeAs($arr_row['linked'], DBFunctions::TYPE_BOOLEAN);
				if ($user_id) {
					$arr_row['override'] = DBFunctions::unescapeAs($arr_row['override'], DBFunctions::TYPE_BOOLEAN);
					$arr_row['user_min'] = DBFunctions::unescapeAs($arr_row['user_min'], DBFunctions::TYPE_BOOLEAN);
				}
				
				$arr[$arr_row['module_id'].'_'.$arr_row['method']] = $arr_row;
			}
		}

		return $arr;
	}
	
	public static function getDashboards($dashboard_id = 0) {
	
		$arr = [];

		if ($dashboard_id) {
			
			$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_DASHBOARDS')." WHERE id = ".(int)$dashboard_id."");
			
			$arr = $res->fetchAssoc();			
		} else {
			
			$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_DASHBOARDS')." ORDER BY id");
		
			while($arr_row = $res->fetchAssoc()) {
				
				$arr[$arr_row['id']] = $arr_row;
			}
		}
		
		return $arr;
	}
}
