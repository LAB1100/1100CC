<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
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
	
		$return .= '<div class="section"><h1>'.self::$label.'</h1>
		<div class="labels">';

			$return .= '<div id="tabs-labels">
			<ul>
				<li id="x:cms_labels:new-cms"><a href="#tab-labels-cms">Site</a><input type="button" class="data add popup label_add" value="add" /></li>
				<li id="x:cms_labels:new-core"><a href="#tab-labels-core">Shared</a>'.($_SESSION['CORE'] ? '<input type="button" class="data add popup label_add" value="add" />' : '').'</li>
			</ul>
			
			<div id="tab-labels-cms">';
			
				$return .= '<table class="display" id="d:cms_labels:labels_data-cms">
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
			
			</div>
			<div id="tab-labels-core">
			
				<table class="display" id="d:cms_labels:labels_data-core">
					<thead>
						<tr>
							<th class="max"><span>Identifier</span></th>';
							
							$arr_language = cms_language::getLanguage('core');
							
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
				</table>';
			
			$return .= '</div>
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
			$arr_label[SiteStartVars::$language] = $str;
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
										
										$return .= '<li><a href="#"><img src="/'.DIR_FLAGS.$arr_row['flag'].'" title="'.htmlspecialchars($arr_row['label']).'" /></a></li>';
									}
									
								$return .= '</ul>';
															
								foreach ($arr_language as $lang_code => $arr_row) {
									
									$return .= '<div>
										<textarea name="lang_code['.$lang_code.']" class="body-content '.$lang_code.'">'.htmlspecialchars($arr_label[$lang_code]).'</textarea><span id="y:cms_general:preview-'.$lang_code.'" class="icon" title="'.getLabel('inf_preview').'">'.getIcon('view').'</span>
									</div>';
								}
								
							$return .= '</div>
							</td>
						</tr>';
					} else {
						
						foreach($arr_language as $lang_code => $arr_row) {
							
							$return .= '<tr>
								<td><img src="/'.DIR_FLAGS.$arr_row['flag'].'" title="'.htmlspecialchars($arr_row['label']).'" /></td>
								<td><textarea name="lang_code['.$lang_code.']">'.htmlspecialchars($arr_label[$lang_code]).'</textarea></td>
							</tr>';
						}
					}
					
				$return .= '</table>
			</div><div>
				<table class="display" id="d:cms_labels:labels_data-select">
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
	
	public static function css() {
	
		$return = '
				.label-popup textarea { width: 500px; height: 200px; }
				.label-popup li:first-child ~ li > label { vertical-align: middle; }
				.label-popup .tabs.bodies > ul > li img { display: inline-block; vertical-align: middle; height: 16px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP

		if ($method == "label_popup") {
							
			$this->html = '<form class="label-popup" data-method="label_select">
				'.$this->createLabel($value['selected'], $value['type'], 'auto_'.($value['module'] ?: 'x').'_'.$value['name'].'_'.uniqid()).'
				<input class="hide" type="submit" name="" value="" />
				<input type="submit" data-tab="save" name="save" value="'.getLabel('lbl_save').'" />
				<input type="submit" data-tab="select" name="select" value="'.getLabel('lbl_select').'" />
			</form>';
		}
		
		if ($method == "label_cms_edit" || $method == "label_core_edit" || $method == "label_add") {
							
			if (($method == 'label_cms_edit' || $method == 'label_core_edit') && $id) {
								
				$arr_label = self::getLabel($id, ($method == 'label_cms_edit' ? 'cms' : 'core'));
				
				$mode = ($method == 'label_cms_edit' ? 'label_cms_update' : 'label_core_update');
			} else if ($method == 'label_add' && $id) {
				$mode = ($id == 'cms' ? 'label_cms_insert' : 'label_core_insert');
			}
			
			$arr_lang = cms_language::getLanguage(($mode == 'label_core_insert' || $mode == 'label_core_update' ? 'core' : 'cms'));
								
			$this->html = '<form class="label-popup" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>Identifier</label>
						<div><input type="text" name="identifier" value="'.htmlspecialchars($arr_label['identifier']).'"></div>
					</li>';

					foreach ($arr_lang as $lang_code => $row) {
						
						$this->html .= '<li>
							<label><img src="/'.DIR_FLAGS.$row['flag'].'" title="'.htmlspecialchars($row['label']).'" /></label>
							<div><textarea name="lang_code['.$lang_code.']">'.htmlspecialchars($arr_label[$lang_code]).'</textarea></div>
						</li>';
					}
				$this->html .= '</ul></fieldset>		
			</form>';
			
			$this->validate = ['identifier' => 'required'];
		}
		
		// DATATABLE
					
		if ($method == "labels_data") {
			
			$arr_language = cms_language::getLanguage(($id == 'cms' || $id == 'select' ? 'cms' : 'core'));
			
			$arr_sql_columns_search = false;
			$arr_sql_columns_as = false;
			
			if ($id == 'select') {
				
				$arr_sql_columns = ['', 'i.identifier'];
				$arr_sql_columns_union = ['i.identifier'];
				
				$sql_index = 'i.identifier';
				$sql_index_body = 'i.identifier';
				$sql_index_union = 'i.identifier';

				foreach ($arr_language as $lang_code => $row) {
					
					$arr_sql_columns[] = 'label_'.$lang_code;
					$arr_sql_columns_union[] = $lang_code.'.label AS label_'.$lang_code;
					
					$sql_index_union .= ', '.$lang_code.'.identifier, '.$lang_code.'.lang_code';
					$sql_index_body .= ', label_'.$lang_code;
				}
								
				$sql_table = "(
					(
						SELECT ".implode(',', $arr_sql_columns_union)."
							FROM ".DB::getTable('TABLE_CMS_LABELS')." i";
							
							foreach ($arr_language as $lang_code => $row) {
								
								$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_CMS_LABELS')." ".$lang_code." ON (".$lang_code.".identifier = i.identifier AND ".$lang_code.".lang_code = '".$lang_code."')";
							}
							
						$sql_table .= " GROUP BY ".$sql_index_union."
					)
					UNION ALL
					(
						SELECT ".implode(',', $arr_sql_columns_union)."
							FROM ".DB::getTable('TABLE_CORE_LABELS')." i";
							
							foreach ($arr_language as $lang_code => $row) {
								$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_CORE_LABELS')." ".$lang_code." ON (".$lang_code.".identifier = i.identifier AND ".$lang_code.".lang_code = '".$lang_code."')";
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

				foreach ($arr_language as $lang_code => $row) {
					
					$arr_sql_columns[] = $lang_code.'.label';
					
					$sql_index_body .= ', '.$lang_code.'.identifier, '.$lang_code.'.lang_code';
				}
				
				$sql_table = DB::getTable($id == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS')." i";
				
				foreach ($arr_language as $lang_code => $row) {
					
					$sql_table .= " LEFT JOIN ".DB::getTable($id == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS')." ".$lang_code." ON (".$lang_code.".identifier = i.identifier AND ".$lang_code.".lang_code = '".$lang_code."')";
				}
			}
			
			$nr_columns = count($arr_sql_columns);

			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', $sql_index_body);
			
			while ($arr_row = $arr_datatable['result']->fetchRow()) {
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:cms_labels:label_id-'.$arr_row[0];
			
				if ($id == 'select') {
					$arr_data[] = '<input name="label_item" value="'.$arr_row[0].'" type="radio" />';
				}
				
				$arr_data[] = $arr_row[0];
				
				for ($i = 1; $i < $nr_columns; $i++) {
					
					$arr_data[] = htmlspecialchars($arr_row[$i]);
				}
				
				if (($id != 'select' && $id != 'core') || ($id == 'core' && $_SESSION['CORE'])) {
					
					$arr_data[] = '<input type="button" class="data edit popup label_'.$id.'_edit" value="edit" /><input type="button" class="data del msg label_'.$id.'_del" value="del" />';
				}
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}

		// QUERY

		if (($method == "label_cms_insert" || $method == "label_core_insert") && $_POST["identifier"]) {
		
			if ($method == 'label_core_insert' && !$_SESSION['CORE']) {
				error(getLabel('msg_not_allowed'));
			}

			self::addLabel($_POST['identifier'], $_POST['lang_code'], ($method == "label_cms_insert" ? "cms" : "core"));

			$this->refresh_table = true;
			$this->msg = true;
		}

		if (($method == "label_cms_update" || $method == "label_core_update") && $id && $_POST["identifier"]) {
		
			if ($method == 'label_core_update' && !$_SESSION['CORE']) {
				error(getLabel('msg_not_allowed'));
			}
								
			self::delLabel($id, ($method == "label_cms_update" ? "cms" : "core"));
			
			self::addLabel($_POST['identifier'], $_POST['lang_code'], ($method == 'label_cms_update' ? 'cms' : 'core'));
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "label_select") {
			
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
		
		if (($method == "label_cms_del" || $method == "label_core_del") && $id) {
		
			if ($method == 'label_core_del' && !$_SESSION['CORE']) {
				error(getLabel('msg_not_allowed'));
			}
					
			self::delLabel($id, ($method == 'label_cms_del' ? 'cms' : 'core'));

			$this->msg = true;
		}
	}
	
	public static function addLabel($identifier, $arr_values, $table = "cms", $user_id = false) {
				
		$arr_lang = cms_language::getLanguage(($table == 'cms' || $table == 'user' ? 'cms' : 'core'));
		$identifier = str2Label($identifier);
		$table_name = ($table == 'user' ? 'TABLE_SITE_USER_LABELS' : ($table == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS'));
		
		foreach ($arr_lang as $lang_code => $lang) {
			
			if ($arr_values[$lang_code]) {
				
				$res = DB::query("INSERT INTO ".DB::getTable($table_name)."
					(identifier, lang_code, label".($table == 'user' ? ", user_id" : "").")
						VALUES
					('".DBFunctions::strEscape($identifier)."', '".DBFunctions::strEscape($lang_code)."', '".DBFunctions::strEscape($arr_values[$lang_code])."'".($table == 'user' ? ", ".(int)$user_id : "").")");
			}
		}
	}
	
	public static function delLabel($identifier, $table = "cms", $user_id = false) {
		
		$table_name =  ($table == 'user' ? 'TABLE_SITE_USER_LABELS' : ($table == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS'));
		
		$res = DB::query("DELETE FROM ".DB::getTable($table_name)." WHERE identifier = '".DBFunctions::strEscape($identifier)."'".($table == "user" ? " AND user_id = ".(int)$user_id : ""));
	}
	
	public static function getLabel($identifier, $table = 'cms', $user_id = false) {
						
		$arr = [];
		$table_name =  ($table == 'user' ? 'TABLE_SITE_USER_LABELS' : ($table == 'cms' ? 'TABLE_CMS_LABELS' : 'TABLE_CORE_LABELS'));
		
		$res = DB::query("SELECT * FROM ".DB::getTable($table_name)." WHERE identifier = '".DBFunctions::strEscape($identifier)."'".($table == 'user' ? " AND user_id = ".(int)$user_id : ""));
		
		while ($row = $res->fetchAssoc()) {
			
			$arr['identifier'] = $row['identifier'];
			$arr[$row['lang_code']] = $row['label'];
		}
		
		return $arr;
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
	
	public static function searchLabelLocations($identifier) {
		
		$arr = [];
				
		foreach (self::getLabelDatabaseLocations() as $database => $arr_database) {
			
			DB::setDatabase($database);
			
			$arr_sql_query = [];
			
			foreach ($arr_database as $value) {
				
				$arr_sql_query[] = "SELECT '".$value[0]."' AS table_name, '".$value[1]."' AS column_name FROM ".$value[0]." WHERE ".$value[1]." LIKE '%[L][".DBFunctions::strEscape($identifier)."]%'";
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
				WHERE lang_code = '".SiteStartVars::$language."'
				AND identifier = '".DBFunctions::strEscape($labels)."'
			) UNION ALL (
				SELECT identifier, label
					FROM ".DB::getTable("TABLE_CORE_LABELS")."
				WHERE lang_code = '".SiteStartVars::$language."'
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
				WHERE lang_code = '".SiteStartVars::$language."'
				AND identifier IN ('".$str."')
			) UNION ALL (
				SELECT identifier, label
					FROM ".DB::getTable("TABLE_CORE_LABELS")."
				WHERE lang_code = '".SiteStartVars::$language."'
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
		$lang_default = cms_language::getDefaultLanguage();
		$lang_default = $lang_default['lang_code'];
		
		if (is_string($labels)) {

			$arr_labels[$labels] = DBFunctions::strEscape($labels);
		} else {
			
			$arr_labels = array_combine($labels, DBFunctions::arrEscape($labels));
		}
		
		// System labels
		
		$sql_labels = implode("','", $arr_labels);
		
		if (SiteStartVars::$language) {
				
			$res = DB::query("SELECT lang_code, identifier, label
							FROM ".DB::getTable('TABLE_CMS_LABELS')."
						WHERE lang_code = '".SiteStartVars::$language."' AND identifier IN ('".$sql_labels."')");

			while ($row = $res->fetchAssoc()) {
				
				$arr[$row['identifier']] = $row;
				unset($arr_labels[$row['identifier']]);
			}

			if ($arr_labels) {
				
				$sql_labels = implode("','", $arr_labels);
				
				$res = DB::query("SELECT lang_code, identifier, label
								FROM ".DB::getTable('TABLE_CORE_LABELS')."
							WHERE lang_code = '".SiteStartVars::$language."' AND identifier IN ('".$sql_labels."')");

				while($row = $res->fetchAssoc()) {
					
					$arr[$row['identifier']] = $row;
					unset($arr_labels[$row['identifier']]);
				}
			}
		}
		
		if (SiteStartVars::$language != $lang_default && $arr_labels) {
			
			$sql_labels = implode("','", $arr_labels);
				
			$res = DB::query("SELECT lang_code, identifier, label
						FROM ".DB::getTable('TABLE_CMS_LABELS')."
					WHERE lang_code = '".$lang_default."' AND identifier IN ('".$sql_labels."')");
			
			while ($row = $res->fetchAssoc()) {
			
				$arr[$row['identifier']] = $row;
				unset($arr_labels[$row['identifier']]);
			}
			
			if ($arr_labels) {
			
				$sql_labels = implode("','", $arr_labels);
				
				$res = DB::query("SELECT lang_code, identifier, label
						FROM ".DB::getTable('TABLE_CORE_LABELS')."
					WHERE lang_code = '".$lang_default."' AND identifier IN ('".$sql_labels."')
				");
				
				while ($row = $res->fetchAssoc()) {
				
					$arr[$row['identifier']] = $row;
					unset($arr_labels[$row['identifier']]);
				}
			}
		}
		
		// User labels
		
		if (!IS_CMS && $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id'] && $arr_labels) {
			
			$user_id = ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'] ?: $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id']);
			
			$sql_labels = implode("','", $arr_labels);
			
			if (SiteStartVars::$language) {
					
				$res = DB::query("SELECT lang_code, identifier, label
						FROM ".DB::getTable('TABLE_SITE_USER_LABELS')."
					WHERE lang_code = '".SiteStartVars::$language."' AND identifier IN ('".$sql_labels."') AND user_id = ".(int)$user_id
				);

				while ($row = $res->fetchAssoc()) {
					
					$arr[$row['identifier']] = $row;
					unset($arr_labels[$row['identifier']]);
				}
			}

			if (SiteStartVars::$language != $lang_default && $arr_labels) {
		
				$sql_labels = implode("','", $arr_labels);
				
				$res = DB::query("SELECT lang_code, identifier, label
							FROM ".DB::getTable('TABLE_SITE_USER_LABELS')."
						WHERE lang_code = '".$lang_default."' AND identifier IN ('".$sql_labels."') AND user_id = ".(int)$user_id);
						
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
						WHERE lang_code = '".SiteStartVars::$language."'
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
