<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class general extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	public function createLabel($str, $label_default = '') {
		
		$arr_language = cms_language::getLanguage();
		$user_id = ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'] ?: $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id']);
		
		$arr_label = [];
		preg_match('/^\[L\]\[(.+)\]$/', $str, $arr_match);
		
		if ($arr_match[1]) {
			$label = str2Label($arr_match[1]);
			$arr_label = cms_labels::getLabel($label, 'user', $user_id);
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
				<div class="options">
					<fieldset><ul>
						<li>
							<label>Identifier</label>
							<div><input type="text" name="identifier" value="'.$label.'" /></div>
						</li>';
							
						foreach($arr_language as $lang_code => $arr_row) {
							
							$return .= '<li>
								<label><span>'.$arr_row['label'].'</span></label>
								<div><textarea name="lang_code['.$lang_code.']">'.strEscapeHTML($arr_label[$lang_code]).'</textarea></div>
							</li>';
						}
						
					$return .= '</ul></fieldset>
				</div>
			</div>
			
			<div>
				
				<div class="options">
					<menu><input type="button" id="y:general:cleanup_labels-0" class="msg clean" data-msg="conf_cleanup_labels" value="'.getLabel('lbl_cleanup_labels').'" /></menu>
				</div>
				
				<table class="display" id="d:general:data_labels-select">
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
			.label-popup .options fieldset > ul > li > label:first-child + * textarea { width: 400px; height: 100px; }
			.label-popup menu { text-align: center; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "
				var IS_CMS = ".(int)IS_CMS.",
				DIR_CMS = '".DIR_CMS."',
				URL_BASE_HOME = '".URL_BASE_HOME."';";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		if ($method == "popup_labels") {
							
			$this->html = '<form class="label-popup" data-method="select_label">
				'.$this->createLabel($value['selected'], 'auto_'.($value['module'] ?: 'x').'_'.$value['name'].'_'.uniqid()).'
				<input class="hide" type="submit" name="" value="" />
				<input type="submit" data-tab="save" name="save" value="'.getLabel('lbl_save').'" />
				<input type="submit" data-tab="select" name="select" value="'.getLabel('lbl_select').'" />
			</form>';
		}
		
		if ($method == "select_label") {
			
			if ($_POST['select'] && $_POST['label_item']) {
				
				$this->html = '[L]['.$_POST['label_item'].']';	
			} else if ($_POST['save'] && $_POST['identifier']) {
										
				if (array_filter($_POST['lang_code'])) {
					
					$user_id = ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'] ?: $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id']);
				
					cms_labels::delLabel($_POST['identifier'], 'user', $user_id);
					cms_labels::addLabel($_POST['identifier'], $_POST['lang_code'], 'user', $user_id);

					$this->msg = true;
				}
				
				$this->html = '[L]['.str2Label($_POST['identifier']).']';
			} else {
				
				$this->html = false;
			}
		}
		
		if ($method == "cleanup_labels") {
			
			$user_id = ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'] ?: $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id']);
			
			cms_labels::cleanupLabels('user', $user_id);
							
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "data_labels") {
			
			$user_id = ($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'] ?: $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id']);
			
			$arr_sql_columns = ['i.identifier'];
			
			$sql_table = DB::getTable('TABLE_SITE_USER_LABELS')." i";
			
			$sql_index = 'i.identifier';
			
			$arr_language = cms_language::getLanguage();
			
			foreach ($arr_language as $lang_code => $arr_row) {
				
				$arr_sql_columns[] = $lang_code.'.label';
				
				$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_SITE_USER_LABELS')." ".$lang_code." ON (".$lang_code.".identifier = i.identifier AND ".$lang_code.".lang_code = '".$lang_code."' AND ".$lang_code.".user_id = ".(int)$user_id.")";
			}

			$sql_where = "i.user_id = ".(int)$user_id;
								 
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, false, false, $sql_table, $sql_index, '', '', $sql_where);

			while ($arr_row = $arr_datatable['result']->fetchRow())	{
							
				$arr_data = [];

				$arr_data = [];
				$arr_data['id'] = 'x:general:label_id-'.$arr_row[0];
				$arr_data[] = '<input name="label_item" value="'.$arr_row[0].'" type="radio" />';
				$arr_data[] = $arr_row[0];
				
				for ($i = 1; $i < count($arr_sql_columns); $i++) {
					
					$arr_data[] = strEscapeHTML($arr_row[$i]);
				}
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
	}
}
