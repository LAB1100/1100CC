<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_CORE_LABELS', DB::$database_core.'.core_labels');
DB::setTable('TABLE_CMS_LABELS', DB::$database_cms.'.cms_labels');
DB::setTable('TABLE_SITE_USER_LABELS', DB::$database_home.'.site_user_labels');

class cms_labels extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_labels');
		static::$parent_label = getLabel('ttl_settings');
	}
	
	private static $arr_database_locations = [];
	
	public function contents() {
	
		$return = '<div class="section"><h1>'.self::$label.'</h1>
		<div class="labels">';

			$return .= '<div id="tabs-labels">
			<ul>
				<li id="x:cms_labels:new-cms"><a href="#tab-labels-cms">'.getLabel('lbl_site').'</a><input type="button" class="data add popup add_label" value="add" /></li>
				<li id="x:cms_labels:new-core"><a href="#tab-labels-core">'.getLabel('lbl_shared').'</a>'.($_SESSION['CORE'] ? '<input type="button" class="data add popup add_label" value="add" />' : '').'</li>
			</ul>
			
			<div id="tab-labels-cms">
			
				<table class="display" id="d:cms_labels:data_labels-cms">
					<thead>
						<tr>
							<th class="max"><span>Identifier</span></th>';
							
							$arr_language = cms_language::getLanguage();
							
							foreach($arr_language as $lang_code => $arr_row) {
								
								$return .= '<th class="max limit"><span>'.$arr_row['label'].'</span></th>';
							}
							
							$return .= '<th class="disable-sort"></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="'.(count($arr_language)+2).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>
				
				<menu><input type="button" id="y:cms_labels:export-cms" class="popup" value="'.getLabel('lbl_export').'" /><input type="button" id="y:cms_labels:import-cms" class="popup" value="'.getLabel('lbl_import').'" /></menu>
				
			</div>
			<div id="tab-labels-core">
			
				<table class="display" id="d:cms_labels:data_labels-core">
					<thead>
						<tr>
							<th class="max"><span>Identifier</span></th>';
							
							$arr_language = cms_language::getLanguage(false, 'core');
							
							foreach($arr_language as $lang_code => $arr_row) {
								
								$return .= '<th class="max limit"><span>'.$arr_row['label'].'</span></th>';
							}
							
							$return .= ($_SESSION['CORE'] ? '<th class="disable-sort"></th>' : '').'
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="'.(count($arr_language)+2).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>
				
				<menu><input type="button" id="y:cms_labels:export-core" class="popup" value="'.getLabel('lbl_export').'" />'.($_SESSION['CORE'] ? '<input type="button" id="y:cms_labels:import-core" class="popup" value="'.getLabel('lbl_import').'" />' : '').'</menu>

			</div>
		</div>';
		
		$return .= '</div></div>';
		
		return $return;
	}
	
	public function createLabel($str, $type, $label_default = '') {
		
		$arr_language = cms_language::getLanguage();
		
		$arr_label = [];
		preg_match('/^\[L\]\[(.+)\]$/', $str, $arr_match);
		
		if ($arr_match[1]) {
			$label = str2Label($arr_match[1]);
			$arr_label = self::getLabel($label);
		} else {
			$label = $label_default;
			$arr_label[SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE)] = $str;
		}
		
		$return = '<div class="tabs">
			<ul>
				<li><a href="#save">'.getLabel('lbl_label').'</a></li>
				<li><a href="#select">'.getLabel('lbl_select').'</a></li>
			</ul>
			<div>
				<table>
					<tr>
						<td>Identifier</td>
						<td><input type="text" name="identifier" value="'.$label.'" /></td>
					</tr>';
					
					if ($type == 'body') {
						
						$return .= '<tr>
							<td>'.getLabel('lbl_body').'</td>
							<td>
							<div class="tabs bodies">
								<ul>';
								
									foreach ($arr_language as $lang_code => $arr_row) {
										
										$return .= '<li><a href="#">'.$arr_row['label'].'</a></li>';
									}
									
								$return .= '</ul>';
															
								foreach ($arr_language as $lang_code => $arr_row) {
									
									$return .= '<div>
										<textarea name="lang_code['.$lang_code.']" class="body-content '.$lang_code.'">'.strEscapeHTML($arr_label[$lang_code]).'</textarea><span id="y:cms_general:preview-'.$lang_code.'" class="icon" title="'.getLabel('inf_preview').'">'.getIcon('view').'</span>
									</div>';
								}
								
							$return .= '</div>
							</td>
						</tr>';
					} else {
						
						foreach($arr_language as $lang_code => $arr_row) {
							
							$return .= '<tr>
								<td><span>'.$arr_row['label'].'</span></td>
								<td><textarea name="lang_code['.$lang_code.']">'.strEscapeHTML($arr_label[$lang_code]).'</textarea></td>
							</tr>';
						}
					}
					
				$return .= '</table>
			</div><div>
				<table class="display" id="d:cms_labels:data_labels-select">
					<thead>
						<tr>
							<th class="disable-sort"></th>
							<th class="max limit">Identifier</th>';
							
							foreach($arr_language as $lang_code => $arr_row) {
								
								$return .= '<th class="max limit">'.$arr_row['label'].'</th>';
							}
							
						$return .= '</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="'.(count($arr_language)+2).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>';
		
		return $return;
	}
	
	public function createLabelExport($id, $str = '') {
		
		$return = '<h1>'.getLabel('lbl_export').' - '.($id == 'cms' ? getLabel('lbl_site') : getLabel('lbl_shared')).'</h1>
		
		<div class="options">
			<textarea name="csv">'.$str.'</textarea>
		</div>
		';
		
		return $return;
	}
	
	public function createLabelImport($id) {
		
		$return = '<h1>'.getLabel('lbl_import').' - '.($id == 'cms' ? getLabel('lbl_site') : getLabel('lbl_shared')).'</h1>
		
		<div class="options">
			<textarea name="csv"></textarea>
		</div>
		<hr />
		<fieldset><ul>
			<li>
				<label>'.getLabel('lbl_clear').'</label>
				<div><input type="checkbox" name="truncate" value="1" /></div>
			</li>
		</ul><fieldset>
		';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '
			.label-popup textarea { width: 500px; height: 200px; }
			.label-popup li:first-child ~ li > label { vertical-align: middle; }
			.label-popup .tabs.bodies > ul > li img { display: inline-block; vertical-align: middle; height: 16px; }
			.label-import-export textarea { width: 800px; height: 500px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "
		SCRIPTER.static('#mod-cms_labels', function(elm_scripter) {
						
			elm_scripter.on('command', '[id^=y\\\:cms_labels\\\:export-]', function() {
				
				var datatable = getElement($(this).closest('div').find('[id^=d\\\:cms_labels\\\:data_labels-]')).datatable;
				
				COMMANDS.setData(this, {search: datatable.getSearch()});
			});
		});
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP

		if ($method == "popup_labels") {
							
			$this->html = '<form class="label-popup" data-method="select_label">
				'.$this->createLabel($value['selected'], $value['type'], 'auto_'.($value['module'] ?: 'x').'_'.$value['name'].'_'.uniqid()).'
				<input class="hide" type="submit" value="" />
				<input type="submit" data-tab="save" name="save" value="'.getLabel('lbl_save').'" />
				<input type="submit" data-tab="select" name="select" value="'.getLabel('lbl_select').'" />
			</form>';
		}
		
		if ($method == "edit_label_cms" || $method == "edit_label_core" || $method == "add_label") {
							
			if (($method == 'edit_label_cms' || $method == 'edit_label_core') && $id) {
								
				$arr_label = self::getLabel($id, ($method == 'edit_label_cms' ? 'cms' : 'core'));
				
				$mode = ($method == 'edit_label_cms' ? 'update_label_cms' : 'update_label_core');
			} else if ($method == 'add_label' && $id) {
				$mode = ($id == 'cms' ? 'insert_label_cms' : 'insert_label_core');
			}
			
			$arr_lang = cms_language::getLanguage(false, ($mode == 'insert_label_core' || $mode == 'update_label_core' ? 'core' : 'cms'));
								
			$this->html = '<form class="label-popup" data-method="'.$mode.'">
				
				<h1>'.($id == 'cms' ? getLabel('lbl_site') : getLabel('lbl_shared')).'</h1>
				
				<fieldset><ul>
					<li>
						<label>Identifier</label>
						<div><input type="text" name="identifier" value="'.strEscapeHTML($arr_label['identifier']).'"></div>
					</li>';

					foreach ($arr_lang as $lang_code => $arr_value) {
						
						$this->html .= '<li>
							<label><span>'.$arr_value['label'].'</span></label>
							<div><textarea name="lang_code['.$lang_code.']">'.strEscapeHTML($arr_label[$lang_code]).'</textarea></div>
						</li>';
					}
					
				$this->html .= '</ul></fieldset>		
			</form>';
			
			$this->validate = ['identifier' => 'required'];
		}
		
		if ($method == "export") {
			
			$arr_language = cms_language::getLanguage(false, $id);
			
			$str_search = ($value['search'] ?? false);
			
			$arr_labels = static::getLabelList($str_search, $id);
			
			$resource = getStreamMemory();
			
			$arr_headers = ['identifier'];
			
			foreach ($arr_language as $lang_code => $arr_value) {
					
				$arr_headers[] = $lang_code;
			}
				
			fputcsv($resource, $arr_headers, ',', '"', CSV_ESCAPE);				
			
			foreach ($arr_labels as $str_identifier => $arr_label) {
				
				$arr_row = [$str_identifier];
				
				foreach ($arr_language as $lang_code => $arr_value) {
					
					$arr_row[] = ($arr_label[$lang_code] ?? '');
				}
				
				fputcsv($resource, $arr_row, ',', '"', CSV_ESCAPE);
			}
			
			rewind($resource);
			$str = read($resource);
			fclose($resource);
			
			$this->html = '<form class="label-import-export">'
				.static::createLabelExport($id, $str)
				.'<input type="submit" value="'.getLabel('lbl_close').'" />'
			.'</form>';
		}
		
		if ($method == "import") {
									
			$this->html = '<form class="label-import-export" data-method="process_import">'.static::createLabelImport($id).'</form>';
		}
		
		if ($method == "process_import") {
			
			if ($id == 'core' && !$_SESSION['CORE']) {
				error(getLabel('msg_not_allowed'));
			}
			
			if (!$_POST['csv']) {
				return;
			}
			
			$arr_language = cms_language::getLanguage(false, $id);
						
			$resource = getStreamMemory();
			fwrite($resource, $_POST['csv']);
			rewind($resource);
			
			$arr_headers = fgetcsv($resource, 0, ',', '"', CSV_ESCAPE);
			$num_identifier = null;
			
			foreach ($arr_headers as $key => &$str_header) {
				
				if ($str_header == 'identifier') {
					
					$str_header = false;
					$num_identifier = $key;
					continue;
				}
				
				if (!isset($arr_language[$str_header])) {
					$str_header = false;
				}
			}
			unset($str_header);
			
			$arr_labels = [];
		
			while (($arr_line = fgetcsv($resource, 0, ',', '"', CSV_ESCAPE)) !== false) {
				
				$str_identifier = $arr_line[$num_identifier];
				
				foreach ($arr_headers as $key => $str_lang_code) {
					
					if (!$str_lang_code) {
						continue;
					}
					
					$arr_labels[$str_identifier][$str_lang_code] = $arr_line[$key];
				}
			}
			
			fclose($resource);
			
			if (!$arr_labels) {
				return;
			}
			
			if ($_POST['truncate']) {

				static::clearLabelList(($id == 'core' ? 'core' : 'cms'));
			}
			
			static::addLabelList($arr_labels, ($id == 'core' ? 'core' : 'cms'));

			$this->refresh_table = true;
			$this->msg = true;
		}
		
		// DATATABLE
					
		if ($method == "data_labels") {
			
			$arr_language = cms_language::getLanguage(false, ($id == 'cms' || $id == 'select' ? 'cms' : 'core'));
			
			$arr_sql_columns_search = false;
			$arr_sql_columns_as = false;
			
			if ($id == 'select') {
				
				$arr_sql_columns = ['', 'i.identifier'];
				$arr_sql_columns_union = ['i.identifier'];
				
				$sql_index = 'i.identifier';
				$sql_index_body = 'i.identifier';
				$sql_index_union = 'i.identifier';

				foreach ($arr_language as $str_lang_code => $arr_language_settings) {
					
					$arr_sql_columns[] = 'label_'.$str_lang_code;
					$arr_sql_columns_union[] = $str_lang_code.'.label AS label_'.$str_lang_code;
					
					$sql_index_union .= ', '.$str_lang_code.'.identifier, '.$str_lang_code.'.lang_code';
					$sql_index_body .= ', label_'.$str_lang_code;
				}
								
				$sql_table = "(
					(
						SELECT ".implode(',', $arr_sql_columns_union)."
							FROM ".DB::getTable('TABLE_CMS_LABELS')." i";
							
							foreach ($arr_language as $str_lang_code => $arr_language_settings) {
								
								$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_CMS_LABELS')." ".$str_lang_code." ON (".$str_lang_code.".identifier = i.identifier AND ".$str_lang_code.".lang_code = '".$str_lang_code."')";
							}
							
						$sql_table .= " GROUP BY ".$sql_index_union."
					)
					UNION ALL
					(
						SELECT ".implode(',', $arr_sql_columns_union)."
							FROM ".DB::getTable('TABLE_CORE_LABELS')." i";
							
							foreach ($arr_language as $str_lang_code => $arr_language_settings) {
								$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_CORE_LABELS')." ".$str_lang_code." ON (".$str_lang_code.".identifier = i.identifier AND ".$str_lang_code.".lang_code = '".$str_lang_code."')";
							}
							
						$sql_table .= " WHERE NOT EXISTS (SELECT TRUE
							FROM ".DB::getTable('TABLE_CMS_LABELS')." icms
							WHERE icms.identifier = i.identifier
						)
						GROUP BY ".$sql_index_union."
					)
				) AS i";
			} else {
				
				$arr_sql_columns = ['i.identifier'];
				
				$sql_index = 'i.identifier';
				$sql_index_body = 'i.identifier';

				foreach ($arr_language as $str_lang_code => $arr_language_settings) {
					
					$arr_sql_columns[] = $str_lang_code.'.label';
					
					$sql_index_body .= ', '.$str_lang_code.'.identifier, '.$str_lang_code.'.lang_code';
				}
				
				$sql_table = DB::getTable($id == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS')." i";
				
				foreach ($arr_language as $str_lang_code => $arr_language_settings) {
					
					$sql_table .= " LEFT JOIN ".DB::getTable($id == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS')." ".$str_lang_code." ON (".$str_lang_code.".identifier = i.identifier AND ".$str_lang_code.".lang_code = '".$str_lang_code."')";
				}
			}
			
			$num_columns = count($arr_sql_columns);

			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', $sql_index_body);
			
			while ($arr_row = $arr_datatable['result']->fetchRow()) {
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:cms_labels:label_id-'.$arr_row[0];
			
				if ($id == 'select') {
					$arr_data[] = '<input name="label_item" value="'.$arr_row[0].'" type="radio" />';
				}
				
				$arr_data[] = $arr_row[0];
				
				for ($i = 1; $i < $num_columns; $i++) {
					
					$arr_data[] = strEscapeHTML($arr_row[$i]);
				}
				
				if (($id != 'select' && $id != 'core') || ($id == 'core' && $_SESSION['CORE'])) {
					
					$arr_data[] = '<input type="button" class="data edit popup edit_label_'.$id.'" value="edit" /><input type="button" class="data del msg del_label_'.$id.'" value="del" />';
				}
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}

		// QUERY

		if (($method == "insert_label_cms" || $method == "insert_label_core") && $_POST['identifier']) {
		
			if ($method == 'insert_label_core' && !$_SESSION['CORE']) {
				error(getLabel('msg_not_allowed'));
			}

			self::addLabel($_POST['identifier'], $_POST['lang_code'], ($method == 'insert_label_cms' ? 'cms' : 'core'));

			$this->refresh_table = true;
			$this->msg = true;
		}

		if (($method == "update_label_cms" || $method == "update_label_core") && $id && $_POST['identifier']) {
		
			if ($method == 'update_label_core' && !$_SESSION['CORE']) {
				error(getLabel('msg_not_allowed'));
			}
								
			self::delLabel($id, ($method == 'update_label_cms' ? 'cms' : 'core'));
			
			self::addLabel($_POST['identifier'], $_POST['lang_code'], ($method == 'update_label_cms' ? 'cms' : 'core'));
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "select_label") {
			
			if ($_POST['select'] && $_POST['label_item']) {
				
				$this->html = '[L]['.$_POST['label_item'].']';	
			} else if ($_POST['save'] && $_POST['identifier']) {
										
				if (array_filter($_POST['lang_code'])) {
					
					self::delLabel($_POST['identifier']);
					self::addLabel($_POST['identifier'], $_POST['lang_code']);

					$this->msg = true;
				}
				
				$this->html = '[L]['.str2Label($_POST['identifier']).']';
			} else {
				
				$this->html = false;
			}
		}
		
		if (($method == "del_label_cms" || $method == "del_label_core") && $id) {
		
			if ($method == 'del_label_core' && !$_SESSION['CORE']) {
				error(getLabel('msg_not_allowed'));
			}
					
			self::delLabel($id, ($method == 'del_label_cms' ? 'cms' : 'core'));

			$this->msg = true;
		}
	}
	
	public static function getLabel($str_identifier, $table = 'cms', $user_id = false) {
						
		$arr = [];
		$table_name =  ($table == 'user' ? 'TABLE_SITE_USER_LABELS' : ($table == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS'));
		
		$res = DB::query("SELECT *
				FROM ".DB::getTable($table_name)."
			WHERE identifier = '".DBFunctions::strEscape($str_identifier)."'
				".($table == 'user' ? " AND user_id = ".(int)$user_id : "")
		);
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr['identifier'] = $arr_row['identifier'];
			$arr[$arr_row['lang_code']] = $arr_row['label'];
		}
		
		return $arr;
	}
	
	public static function getLabelList($str_search = false, $table = 'cms', $user_id = false) {
						
		$arr = [];
		$table_name = ($table == 'user' ? 'TABLE_SITE_USER_LABELS' : ($table == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS'));
		
		$res = DB::query("SELECT *
				FROM ".DB::getTable($table_name)."
			WHERE TRUE
				".($str_search ? "AND (identifier LIKE '%".DBFunctions::strEscape($str_search)."%' OR label LIKE '%".DBFunctions::strEscape($str_search)."%')" : "")."
				".($table == 'user' ? " AND user_id = ".(int)$user_id : "")
		);
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr[$arr_row['identifier']][$arr_row['lang_code']] = $arr_row['label'];
		}
		
		return $arr;
	}
	
	public static function addLabel($str_identifier, $arr_values, $table = 'cms', $user_id = false) {
				
		$arr_language = cms_language::getLanguage(false, ($table == 'cms' || $table == 'user' ? 'cms' : 'core'));
		$table_name = ($table == 'user' ? 'TABLE_SITE_USER_LABELS' : ($table == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS'));
		
		$str_identifier = str2Label($str_identifier);
		
		if ($str_identifier === '') {
			return false;
		}
		
		foreach ($arr_language as $str_lang_code => $arr_language_settings) {
			
			if ($arr_values[$str_lang_code]) {
				
				$res = DB::query("INSERT INTO ".DB::getTable($table_name)."
					(identifier, lang_code, label".($table == 'user' ? ", user_id" : "").")
						VALUES
					('".DBFunctions::strEscape($str_identifier)."', '".DBFunctions::strEscape($str_lang_code)."', '".DBFunctions::strEscape($arr_values[$str_lang_code])."'".($table == 'user' ? ", ".(int)$user_id : "").")");
			}
		}
	}
	
	public static function addLabelList($arr_labels, $table = 'cms', $user_id = false) {
				
		$arr_language = cms_language::getLanguage(false, ($table == 'cms' || $table == 'user' ? 'cms' : 'core'));
		$table_name = ($table == 'user' ? 'TABLE_SITE_USER_LABELS' : ($table == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS'));
		
		$arr_sql = [];
		
		foreach ($arr_labels as $str_identifier => $arr_label) {
			
			$str_identifier = str2Label($str_identifier);
			
			if ($str_identifier === '') {
				continue;
			}
			
			$arr_row = [];
			
			foreach ($arr_label as $str_lang_code => $str_label) {
				
				if ($str_label === '' || !isset($arr_language[$str_lang_code])) {
					continue;
				}
				
				$arr_sql[] = "('".DBFunctions::strEscape($str_identifier)."', '".DBFunctions::strEscape($str_lang_code)."', '".DBFunctions::strEscape($str_label)."'".($table == 'user' ? ", ".(int)$user_id : "").")";
			}
		}
		
		if ($arr_sql) {
			
			$res = DB::query("INSERT INTO ".DB::getTable($table_name)."
				(identifier, lang_code, label".($table == 'user' ? ", user_id" : "").")
					VALUES
				".implode(',', $arr_sql)."
				".DBFunctions::onConflict('identifier, lang_code', ['label'])."
			");
		}
	}

	public static function delLabel($str_identifier, $table = 'cms', $user_id = false) {
		
		$table_name =  ($table == 'user' ? 'TABLE_SITE_USER_LABELS' : ($table == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS'));
		
		$res = DB::query("DELETE FROM ".DB::getTable($table_name)."
			WHERE identifier = '".DBFunctions::strEscape($str_identifier)."'
				".($table == 'user' ? "AND user_id = ".(int)$user_id : "")
		);
	}

	public static function clearLabelList($table = 'cms', $user_id = false) {
		
		$table_name = ($table == 'user' ? 'TABLE_SITE_USER_LABELS' : ($table == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS'));
		
		$res = DB::query("DELETE FROM ".DB::getTable($table_name)."
			".($user_id ? "WHERE user_id = ".(int)$user_id : "")."
		");
	}
	
	public static function cleanupLabels($table = "cms", $user_id = false) {
		
		$table_name = ($table == 'user' ? 'TABLE_SITE_USER_LABELS' : ($table == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS'));
		
		$res = DB::query("SELECT identifier
				FROM ".DB::getTable($table_name)." 
			".($table == 'user' ? "WHERE user_id = ".(int)$user_id : "")."
		");
		
		while ($row = $res->fetchAssoc()) {
			
			$arr_locations = self::searchLabelLocations($row['identifier']);
		
			if (!$arr_locations) {
				self::delLabel($row['identifier'], $table, $user_id);
			}
		}
	}
	
	public static function getLabelDatabaseLocations() {
		
		if (self::$arr_database_locations) {
			return self::$arr_database_locations;
		}
		
		$arr_database_tables = DB::getDatabaseTables();
		$arr_databases = array_keys($arr_database_tables);
		
		$res = DB::query("SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE COLUMN_NAME IN ('name', 'label', 'value', 'html', 'body', 'text', 'description', 'information', 'value_text')
			AND TABLE_SCHEMA IN ('".implode("','", $arr_databases)."')
		");
		
		while ($row = $res->fetchAssoc()) {
			
			self::$arr_database_locations[$row["TABLE_SCHEMA"]][] = [$row["TABLE_NAME"], $row["COLUMN_NAME"]];
		}
		
		return self::$arr_database_locations;
	}
	
	public static function searchLabelLocations($str_identifier) {
		
		$arr = [];
				
		foreach (self::getLabelDatabaseLocations() as $database => $arr_database) {
			
			DB::setDatabase($database);
			
			$arr_sql_query = [];
			
			foreach ($arr_database as $value) {
				
				$arr_sql_query[] = "SELECT '".$value[0]."' AS table_name, '".$value[1]."' AS column_name FROM ".$value[0]." WHERE ".$value[1]." LIKE '%[L][".DBFunctions::strEscape($str_identifier)."]%'";
			}
			
			$res = DB::query("".implode(" UNION ALL ", $arr_sql_query)."");
			
			while ($row = $res->fetchAssoc()) {
				
				$arr[$database][$row['table_name'].$row['column_name']] = ['table' => $row['table_name'], 'column' => $row['column_name'], 'count' => $arr[$database][$row['table_name'].$row['column_name']]['count']+1];
			}
		}
		
		DB::setDatabase();

		return $arr;
	}
	
	/*
	public static function getLabels($labels) {
		
		$arr = array();
		
		if (is_string($labels)) {
		
			$res = DB::query("(SELECT identifier, label
				FROM ".DB::getTable("TABLE_CMS_LABELS")."
				WHERE lang_code = '".SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE)."'
				AND identifier = '".DBFunctions::strEscape($labels)."'
			) UNION ALL (
				SELECT identifier, label
					FROM ".DB::getTable("TABLE_CORE_LABELS")."
				WHERE lang_code = '".SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE)."'
					AND identifier = '".DBFunctions::strEscape($labels)."'
					AND NOT EXISTS (SELECT identifier 
						FROM ".DB::getTable("TABLE_CMS_LABELS")."
						WHERE identifier = '".DBFunctions::strEscape($labels)."'
				)
			)");
		
			$arr = $res->fetchAssoc();

		} else if (is_array($labels)) {

			$str = implode("','", $labels);
		
			$res = DB::query("(SELECT identifier, label
				FROM ".DB::getTable("TABLE_CMS_LABELS")."
				WHERE lang_code = '".SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE)."'
				AND identifier IN ('".$str."')
			) UNION ALL (
				SELECT identifier, label
					FROM ".DB::getTable("TABLE_CORE_LABELS")."
				WHERE lang_code = '".SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE)."'
					AND identifier IN ('".$str."')
					AND identifier NOT IN (SELECT identifier 
						FROM ".DB::getTable("TABLE_CMS_LABELS")."
						WHERE identifier IN ('".$str."')
				)
			)");
				
			while($row = $res->fetchAssoc()) {
				$arr[] = $row;
			}
		}
		return $arr;
	}
	*/
	
	public static function getLabels($labels) {
		
		$arr = [];
		$str_language_default = cms_language::getDefaultLanguage();
		$str_language_default = $str_language_default['lang_code'];
		
		if (is_string($labels)) {

			$arr_labels[$labels] = DBFunctions::strEscape($labels);
		} else {
			
			$arr_labels = array_combine($labels, DBFunctions::arrEscape($labels));
		}
		
		// System labels
		
		$sql_labels = implode("','", $arr_labels);
		
		if (SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE)) {
				
			$res = DB::query("SELECT lang_code, identifier, label
							FROM ".DB::getTable('TABLE_CMS_LABELS')."
						WHERE lang_code = '".SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE)."' AND identifier IN ('".$sql_labels."')");

			while ($row = $res->fetchAssoc()) {
				
				$arr[$row['identifier']] = $row;
				unset($arr_labels[$row['identifier']]);
			}

			if ($arr_labels) {
				
				$sql_labels = implode("','", $arr_labels);
				
				$res = DB::query("SELECT lang_code, identifier, label
								FROM ".DB::getTable('TABLE_CORE_LABELS')."
							WHERE lang_code = '".SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE)."' AND identifier IN ('".$sql_labels."')");

				while($row = $res->fetchAssoc()) {
					
					$arr[$row['identifier']] = $row;
					unset($arr_labels[$row['identifier']]);
				}
			}
		}
		
		if (SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE) != $str_language_default && $arr_labels) {
			
			$sql_labels = implode("','", $arr_labels);
				
			$res = DB::query("SELECT lang_code, identifier, label
						FROM ".DB::getTable('TABLE_CMS_LABELS')."
					WHERE lang_code = '".$str_language_default."' AND identifier IN ('".$sql_labels."')");
			
			while ($row = $res->fetchAssoc()) {
			
				$arr[$row['identifier']] = $row;
				unset($arr_labels[$row['identifier']]);
			}
			
			if ($arr_labels) {
			
				$sql_labels = implode("','", $arr_labels);
				
				$res = DB::query("SELECT lang_code, identifier, label
						FROM ".DB::getTable('TABLE_CORE_LABELS')."
					WHERE lang_code = '".$str_language_default."' AND identifier IN ('".$sql_labels."')
				");
				
				while ($row = $res->fetchAssoc()) {
				
					$arr[$row['identifier']] = $row;
					unset($arr_labels[$row['identifier']]);
				}
			}
		}
		
		// User labels
		
		if (!IS_CMS && !empty($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id']) && $arr_labels) {
			
			$user_id = ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'] ?: $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id']);
			
			$sql_labels = implode("','", $arr_labels);
			
			if (SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE)) {
					
				$res = DB::query("SELECT lang_code, identifier, label
						FROM ".DB::getTable('TABLE_SITE_USER_LABELS')."
					WHERE lang_code = '".SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE)."' AND identifier IN ('".$sql_labels."') AND user_id = ".(int)$user_id
				);

				while ($row = $res->fetchAssoc()) {
					
					$arr[$row['identifier']] = $row;
					unset($arr_labels[$row['identifier']]);
				}
			}

			if (SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE) != $str_language_default && $arr_labels) {
		
				$sql_labels = implode("','", $arr_labels);
				
				$res = DB::query("SELECT lang_code, identifier, label
							FROM ".DB::getTable('TABLE_SITE_USER_LABELS')."
						WHERE lang_code = '".$str_language_default."' AND identifier IN ('".$sql_labels."') AND user_id = ".(int)$user_id);
						
				while ($row = $res->fetchAssoc()) {
				
					$arr[$row['identifier']] = $row;
					unset($arr_labels[$row['identifier']]);
				}
			}
		}
		
		if (is_string($labels)) {
			$arr = reset($arr);
		}
		
		return $arr;
	}
	
	public static function searchLabels($search) {
	
		$arr_identifiers = [];
		
		$arr_strings = (is_array($search) ? $search : [$search]);
		
		foreach ($arr_strings as $string) {
			$arr_identifiers[$string] = [];
		}

		$func_search_labels = function($arr_strings, $is_tags = true) use (&$arr_identifiers, &$func_search_labels) {
		
			$arr_query_search = [];
			
			foreach ($arr_strings as $string) {

				$arr_query_search[] = "label LIKE '%".($is_tags ? "[L][".DBFunctions::strEscape($string)."]" : DBFunctions::strEscape($string))."%'";
			}
			
			$sql = "SELECT identifier, label
					FROM ".DB::getTable('TABLE_CMS_LABELS')."
						WHERE lang_code = '".SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_LANGUAGE)."'
						AND (".implode(" OR ", $arr_query_search).")
			";
			
			$res = DB::query($sql);

			if ($res->getRowCount()) {
				
				$arr_new_strings = [];
				
				while($row = $res->fetchAssoc()) {
					
					$match = false;
					
					foreach ($arr_strings as $string) {
						
						// If a plain text search (not tags), only match string when it is not followed by a closing tag (]), keeping out text searches on tags
						$match = ($is_tags ? strpos($row['label'], "[L][".$string."]") : (preg_match("/".$string."(?!\w*\])/i", $row['label']) ? true : false));
						
						if ($match !== false) {
							
							if ($is_tags) {
								
								foreach ($arr_identifiers as $string_identifier => $arr_values) {
									
									$lookup = array_search($string, $arr_values);
									
									if ($lookup !== false) {
										$arr_identifiers[$string_identifier][] = $row['identifier'];
									}
								}
							} else {
								
								$arr_identifiers[$string][] = $row['identifier'];
							}
							
							$arr_new_strings[] = $row['identifier'];
						}
					}
				}
				
				if ($arr_new_strings) {
					
					$func_search_labels($arr_new_strings);
				}
			}
		};
		
		$func_search_labels($arr_strings, false);

		return $arr_identifiers;
	}
}
