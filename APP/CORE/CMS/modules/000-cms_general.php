<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_TAGS', DB::$database_home.'.def_tags');
DB::setTable('TABLE_INTERNAL_TAGS', DB::$database_cms.'.site_tags');

class cms_general extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	public static function modulePreload() {
		
		SiteEndVars::addScript("
			var labeler = ".(int)$_SESSION['CUR_USER']['labeler'].";
		");
	}
		
	public static function css() {
	
		$return = 'img.select { max-width: 200px; max-height: 200px; cursor: pointer; border: 1px dashed #bdbdbd; vertical-align: middle; }
				img.select[src=""] { display: block; width: 30px; height: 30px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "
				var labeler = false,
				IS_CMS = ".(int)IS_CMS.",
				DIR_CMS = '".DIR_CMS."',
				BASE_URL_HOME = '".BASE_URL_HOME."';
				
				$(document).on('documentloaded ajaxloaded', function() {
					if (labeler) {
					
						var elms_input = $('form textarea[name], form input[type=text][name]').not('.editor');
						
						elms_input.each(function() {
							new LabelOption(this, {action: 'y:cms_labels:label_popup-0', tag: 'L'});
						});
					}
				}).on('editorloaded', function(e) {
					if (labeler) {
						new LabelOption(e.detail.source, {action: 'y:cms_labels:label_popup-0', tag: 'L'});
					}
				}).on('click', 'img.select', function() {
					var input = $(this).prev('input');
					input.data('target', function(html) {
						$(this).val(html).trigger('change');
						var target = $(this).next('img');
						if (target.attr('data-prefix')) {
							var src = target.attr('data-prefix')+html.replace(/^\//, '');
						} else {
							var src = html;
						}
						target.attr('src', src);
					}).popupCommand();
				});
				";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
		
		// HOME & CMS
		
		if ($method == "get_label") {
			
			if ($id) {
				
				$this->html = getLabel($id);
			} else if ($value) {
				
				$arr = [];
				
				foreach ($value as $identifier) {
					$arr[$identifier] = getLabel($identifier);
				}
				
				$this->html = $arr;
			}
		}
		
		if ($method == "get_text") {
		
			$this->html = Labels::parseTextVariables($value);
		}
					
		if ($method == "set_language") {
		
			$language = ($value ?: $id);
			
			$cur_language = $_SESSION['LANGUAGE_SYSTEM'];
			
			if ($language != $cur_language) {
			
				$_SESSION['LANGUAGE_SYSTEM'] = $language;
				
				Response::location(SiteStartVars::getPageUrl());
			}
		}
		
		if ($method == "create_url") {
			
			$str_url = '';
			$str_text = '';
			
			if ($value && $value['selected']) {
				
				$arr_code = FormatBBCode::getCode('url_attr');
				preg_match($arr_code[0], $value['selected'], $arr_matches);
				
				if ($arr_matches) {
					
					$str_url = $arr_matches[1];
					$str_text = $arr_matches[2];
				} else {
					
					$arr_code = FormatBBCode::getCode('url');
					preg_match($arr_code[0], $value['selected'], $arr_matches);
					
					if ($arr_matches) {
						
						$str_url = $arr_matches[1];
						$str_text = $arr_matches[1];
					} else {
							
						$str_text = $value['selected'];
					}
				}
			}
				
			$return = '<form class="options" data-method="return_url">
				<fieldset><legend>'.getLabel('lbl_create').' Link</legend><ul>
					<li>
						<label>'.getLabel('lbl_url').'</label>
						<div><input type="text" name="url" value="'.htmlspecialchars($str_url).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_text').'</label>
						<div><input type="text" name="text" value="'.htmlspecialchars($str_text).'" /><label>'.getLabel('lbl_optional').'</label></div>
					</li>
				</ul></fieldset>
			</form>';
		
			$this->html = $return;
		}
		
		if ($method == "create_quote") {
			
			$str_cite = '';
			$str_text = '';
			
			if ($value && $value['selected']) {
				
				$arr_code = FormatBBCode::getCode('quote');
				preg_match($arr_code[0], $value['selected'], $arr_matches);
				
				if ($arr_matches) {
					
					$str_cite = $arr_matches[1];
					$str_text = $arr_matches[2];
				} else {
					$str_text = $value['selected'];
				}
			}
				
			$return = '<form class="options" data-method="return_quote">
				<fieldset><legend>'.getLabel('lbl_create').' Quote</legend><ul>
					<li>
						<label>'.getLabel('lbl_citation').'</label>
						<div><input type="text" name="cite" value="'.htmlspecialchars($str_cite).'" /><label>'.getLabel('lbl_optional').'</label></div>
					</li>
					<li>
						<label>'.getLabel('lbl_text').'</label>
						<div><input type="text" name="text" value="'.htmlspecialchars($str_text).'" /></div>
					</li>
				</ul></fieldset>
			</form>';
		
			$this->html = $return;
		}
		
		if ($method == "return_url") {
		
			$str_url = $_POST['url'];
			
			if ($str_url) {
				
				$str_protocol = FileGet::getExternalProtocol($str_url);
				
				if (!$str_protocol) {
				
					if (substr($str_url, 0, 1) != '/') {
						$str_url = '/'.$str_url;
					}
				}
			}
			
			if ($str_url && (!$_POST['text'] || $str_url == $_POST['text'])) {
				$str_tag = '[url]'.$str_url.'[/url]';
			} else {
				$str_tag = '[url='.$str_url.']'.$_POST['text'].'[/url]';
			}

			$this->html = ['url' => $str_url, 'text' => $_POST['text'], 'tag' => $str_tag];
		}
		
		if ($method == "return_quote") {
			
			$str_tag = '[quote'.($_POST['cite'] ? '='.$_POST['cite'] : '').']'.$_POST['text'].'[/quote]';
							
			$this->html = ['cite' => $_POST['cite'], 'text' => $_POST['text'], 'tag' => $str_tag];
		}

		// CMS
		
		if ($method == "lookup_tag" || $method == "lookup_internal_tag") {
		
			$arr = [];

			$res = DB::query("SELECT id, name
								FROM ".DB::getTable(($method == 'lookup_internal_tag' ? 'TABLE_INTERNAL_TAGS' :'TABLE_TAGS'))."
								WHERE name LIKE '%".DBFunctions::strEscape($value)."%'
								LIMIT 20
			");
		
			while ($row = $res->fetchAssoc()) {
				
				$arr[] = ['id' => 'id_'.$row['id'], 'label' => $row['name'], 'value' => $row['name']];
			}
			
			if (!$arr && $value != '?') {
				
				$value = trim(str_replace('+', ' ', $value)); // '+' character not allowed, exclusive for url related tasks
				array_unshift($arr, ['id' => $value, 'label' => $value, 'value' => $value]);
			}
		
			$this->html = $arr;
		}
		
		if ($method == "preview") {
			
			$style = (!$value['style'] ? 'body' : $value['style']);
			
			$this->html = self::createIframeDynamic($value['html'], (!$value['external'] ? '<link href="'.BASE_URL_HOME.'combine/css/" rel="stylesheet" type="text/css" />' : ''), 'back mod '.$style, 'preview');
		}
		
		if ($method == "editor") {
			
			$this->html = '<form class="return_editor">'.self::editBody($value['html'], 'body', ['external' => (bool)$value['external']]).'</form>';
		}
		
		if ($method == "return_editor") {
			
			$this->html = $_POST['body'];
		}
		
		if ($method == "order_datatable") {
					
			$arr_columns = (array)$value['columns'];
			$arr_order_column = (array)$value['order'];
			
			$arr_column_order = [];
			
			foreach ($arr_order_column as $nr_order => list($nr_column, $str_direction)) {
				$arr_column_order[$nr_column] = [$str_direction, $nr_order];
			}
			
			$arr_sort_options = [['id' => 'asc', 'name' => getLabel('lbl_ascending')], ['id' => 'desc', 'name' => getLabel('lbl_descending')]];
			
			$arr_sorter = [];
			
			foreach ($arr_columns as $nr_column => $arr_column_info) {
				
				if ($arr_column_info['disable_sort']) {
					continue;
				}

				$nr_order = 'b'.$nr_column;
				$str_direction = '';
				
				$arr_column = $arr_column_order[$nr_column];
				
				if ($arr_column) {
					$str_direction = $arr_column[0];
					$nr_order = 'a'.$arr_column[1];
				}
				
				$str_name = ($arr_column_info['title'] ?: $arr_column_info['text']);

				$arr_sorter[$nr_order] = ['value' => '<label>'.$str_name.'</label><select name="order['.$nr_column.']">'.cms_general::createDropdown($arr_sort_options, $str_direction, true).'</select>'];
			}
			
			ksort($arr_sorter);
		
			$html = '<form data-method="return_datatable_order" class="options">
				<fieldset><legend>'.getLabel('lbl_order').'</legend>
					'.cms_general::createSorter($arr_sorter, true).'
				</fieldset>
			</form>';
			
			$this->html = $html;
		}
		
		if ($method == "return_datatable_order") {
			
			$arr_order = [];
			$nr_order = 0;
			
			foreach ($_POST['order'] as $nr_column => $str_direction) {
				
				if (!$str_direction) {
					continue;
				}
				
				$arr_order[$nr_column] = [$str_direction, $nr_order];
				$nr_order++;
			}
			
			$this->html = $arr_order;
		}
									
		// QUERY

	}
	
	public static function selectModuleList($arr_modules, $small = false, $link = true, $max = 9999) {
		
		$cur_parent = '';
		$count = 0;
		$sub = false;
		$new_sub = false;
		
		foreach ($arr_modules as $key => $value) {
			
			if ($value['label']) {
				
				$label = $value['label'];
				$parent_label = $value['parent_label'];
			} else {
				
				$key::moduleProperties();
				$label = $key::$label;
				$parent_label = $key::$parent_label;
			}
			if (!$label) { // Skip non-interface modules
				continue;
			}
			
			sub:
			
			if ($count % $max == 0) { // new list and closing old
				if ($count) {
					if ($sub) {
						$return .= '</ul></li>';
					}
					$return .= '</ul>';
				}
				$return .= '<ul class="mod-list">';
			}
			if ($cur_parent != $parent_label) {
				$sub = false;
				if ($cur_parent != "" && $count % $max != 0) { // close old sub
					$return .= '</ul></li>';
				}
				if ($parent_label != "") { // create sub
					$return .= '<li class="sub"><h1>'.$parent_label.'</h1><ul>';
					$new_sub = true;
					$sub = true;
				}
			} else {
				if ($count % $max == 0 && $sub) { // continue old sub in new list
					$return .= '<li class="sub"><ul>';
				}
			}
			if (!$new_sub) {
				$module_var = (method_exists($key, 'moduleVariables') ? $key::moduleVariables() : '');
				$return .= '<li>'.($link ? '<a href="/'.$key.'/">'.$label.'</a>' : '<span id="mod-'.$key.'">'.$label.'</span>').$module_var.'</li>';
			}
			
			$cur_parent = $parent_label;
			$count++;
			
			if ($new_sub) {
				$new_sub = false;
				goto sub;
			}
		}
		
		if ($cur_parent != "") {
			$return .= '</ul></li>';
		}	
		
		$return .= '</ul>';
		
		return $return;
	}
	
	public static function getMonths() {
	
		return [['id' => 1, 'name' => getLabel('unit_month_january')], ['id' => 2, 'name' => getLabel('unit_month_february')], ['id' => 3, 'name' => getLabel('unit_month_march')], ['id' => 4, 'name' => getLabel('unit_month_april')], ['id' => 5, 'name' => getLabel('unit_month_may')], ['id' => 6, 'name' => getLabel('unit_month_june')], ['id' => 7, 'name' => getLabel('unit_month_july')], ['id' => 8, 'name' => getLabel('unit_month_august')], ['id' => 9, 'name' => getLabel('unit_month_september')], ['id' => 10, 'name' => getLabel('unit_month_october')], ['id' => 11, 'name' => getLabel('unit_month_november')], ['id' => 12, 'name' => getLabel('unit_month_december')]];
	}
	
	public static function getTimeUnits($unit = false) {
		
		$arr = [
			40320 => ['id' => 40320, 'name' => getLabel('unit_months')],
			10080 => ['id' => 10080, 'name' => getLabel('unit_weeks')],
			1440 => ['id' => 1440, 'name' => getLabel('unit_days')],
			60 => ['id' => 60, 'name' => getLabel('unit_hours')],
			1 => ['id' => 1, 'name' => getLabel('unit_minutes')]
		];
		
		return ($unit ? $arr[$unit] : $arr);
	}
	
	public static function createDropdown($arr, $selected = false, $empty = false, $label_column = 'name', $id_column = 'id') {

		$return .= ($empty ? '<option value="">'.(is_string($empty) ? $empty : '').'</option>' : '');
		$selected = (is_array($selected) ? $selected : [($selected ?: false)]); // Support for multiple select
		$arr_group = [];

		foreach ($arr as $arr_option) {
			
			$label = $arr_option[$label_column];
			
			if (strpos($label, ' | ') !== false) {
				
				$label = explode(' | ', $label);
				$arr_option[$label_column] = $label[1];
				$arr_group[$label[0]][] = $arr_option;
			} else {
				
				$arr_attr = [];
				
				if ($arr_option['attr']) {
					foreach ($arr_option['attr'] as $key => $value) {
						$arr_attr[] = $key.'="'.$value.'"';
					}
				}
				
				$return .= '<option value="'.htmlspecialchars($arr_option[$id_column]).'"'.($arr_attr ? ' '.implode(' ', $arr_attr) : '').(in_array($arr_option[$id_column], $selected) ? ' selected="selected"' : '').'>'.htmlspecialchars($label).'</option>';
			}
		}
		
		foreach ($arr_group as $group => $arr) {
			
			$return .= '<optgroup label="'.htmlspecialchars($group).'">'
				.self::createDropdown($arr, $selected, false, $label_column, $id_column)
			.'</optgroup>';
		}
		
		return $return;
	}
	
	public static function createSelector($arr, $name, $selected = [], $label_column = 'name', $id_column = 'id') {

		foreach ($arr as $arr_option) {
			$return .= '<label'.($arr_option['title'] ? ' title="'.htmlspecialchars($arr_option['title']).'"' : '').'><input type="checkbox" name="'.($name ? $name.'['.$arr_option[$id_column].']' : $arr_option[$id_column]).'" value="'.$arr_option[$id_column].'"'.($selected == 'all' || in_array($arr_option[$id_column], $selected) ? ' checked="checked"' : '').' /><span>'.$arr_option[$label_column].'</span></label>';
		}
		
		return $return;
	}
	
	public static function createSelectorRadio($arr, $name, $selected = false, $label_column = 'name', $id_column = 'id') {

		foreach ($arr as $arr_option) {
			$return .= '<label'.($arr_option['title'] ? ' title="'.htmlspecialchars($arr_option['title']).'"' : '').'><input type="radio" name="'.$name.'" value="'.$arr_option[$id_column].'"'.($arr_option[$id_column] == $selected ? ' checked="checked"' : '').' /><span>'.$arr_option[$label_column].'</span></label>';
		}
		
		return $return;
	}
	
	public static function createSelectorList($arr, $name, $selected = [], $label_column = 'name', $id_column = 'id') {
		
		$return .= '<ul class="select">';
		foreach ($arr as $arr_option) {
			$return .= '<li><label><input type="checkbox" name="'.($name ? $name.'['.$arr_option[$id_column].']' : $arr_option[$id_column]).'" value="'.$arr_option[$id_column].'"'.($selected == 'all' || in_array($arr_option[$id_column], $selected) ? ' checked="checked"' : '').' /><span>'.$arr_option[$label_column].'</span></label></li>';
		}
		$return .= '</ul>';
		
		return ($arr ? $return : '<span>'.getLabel('lbl_none').'</span>');
	}
	
	public static function createSelectorRadioList($arr, $name, $selected = false, $label_column = 'name', $id_column = 'id') {
		
		$return .= '<ul class="select">';
		foreach ($arr as $arr_option) {
			$return .= '<li><label><input type="radio" name="'.$name.'" value="'.$arr_option[$id_column].'"'.($arr_option[$id_column] == $selected ? ' checked="checked"' : '').' /><span>'.$arr_option[$label_column].'</span></label></li>';
		}
		$return .= '</ul>';
		
		return ($arr ? $return : '<span>'.getLabel('lbl_none').'</span>');
	}
	
	public static function createDefineDate($date, $name = '', $hide = true) {
		
		$date = ($date && $date != '0000-00-00 00:00:00' ? $date : time());
		$date = (is_string($date) ? strtotime($date) : $date);
		$name = ($name ? $name : 'date');
		
		return '<div class="hide-edit'.($hide ? ' hide' : '').'"><input type="text" class="date datepicker" name="'.$name.'" value="'.date('d-m-Y', $date).'" /><input type="text" class="date-time" name="'.$name.'_t" value="'.date('H:i', $date).'" /></div><span class="icon" title="'.getLabel('inf_edit_date').'">'.getIcon('date').'</span>';
	}
	
	public static function createFileBrowser($multi = false, $name = 'file') {
		
		$name_file = ($multi ? $name.'[]' : $name);
				
		return '<div class="input filebrowse"><div class="select"><input type="file" name="'.$name_file.'"'.($multi ? ' multiple="multiple"' : '').' /><label><span></span><input type="text" name="'.$name.'" /></label></div>'.($multi ? '<ul></ul>' : '').'<progress value="0" max="100"></progress></div>'; // Also include a input with type="text" and name="..." to make it 'visible' for normal POSTED data iteration
	}
	
	public static function createImageSelector($value, $name = 'img') {
		
		return '<input type="hidden" name="'.$name.'" id="y:cms_media:media_popup-0" value="'.$value.'" /><img class="select" src="'.($value ? SiteStartVars::getCacheUrl('img', [200, 200], $value) : '').'" data-prefix="'.SiteStartVars::getCacheUrl('img', [200, 200], '').'" />';
	}
	
	public static function createMultiSelect($name, $id, $arr_tags, $id_value = false, $arr_options = []) {
		
		$str_tags = '';
		foreach ($arr_tags as $key => $value) {
			$str_tags .= '<li><span><input type="hidden" name="'.$name.'['.$key.']" value="'.$key.'"/>'.$value.'</span><span class="handler"></span></li>';
		}
		
		return '<input type="hidden" name="'.$name.'" value=""'.($id_value ? ' id="'.$id_value.'"' : '').' /><div class="autocomplete tags'.($arr_options['list'] ? ' list' : '').'"><input type="hidden" name="'.$name.'" value="" /><ul>'.$str_tags.'</ul></div><input type="search" class="autocomplete multi" id="'.$id.'" value=""'.($arr_options['delay'] ? ' data-delay="'.$arr_options['delay'].'"' : '').' />';
	}
	
	public static function createIframeDynamic($body, $head = false, $body_class = false, $class = false) {
		
		return '<iframe src="javascript:\'\';" data-body="'.parseBody($body, ['function' => 'htmlspecialchars']).'"'.($head ? ' data-head="'.htmlspecialchars($head).'"' : '').($body_class ? ' data-body-class="'.$body_class.'"' : '').($class ? ' class="'.$class.'"' : '').'></iframe>';
	}
	
	public static function createSorter($arr_rows, $handle = false, $reverse = false, $arr_options = []) {
		
		$return .= '<ul class="sorter'.($reverse ? ' reverse' : '').($arr_options['full'] ? ' full' : '').'"'.($arr_options['auto_add'] ? ' data-sorter_auto_add="1"' : '').'>';
		
		$html_handle = '<span class="icon">'.getIcon('updown').'</span>';
		
		foreach ($arr_rows as $arr_row) {
			
			$str_class = (is_array($arr_row['class']) ? implode(' ', $arr_row['class']) : $arr_row['class']);
			
			if (is_array($arr_row['value'])) {
				$html = '<ul'.($str_class ? ' class="'.$str_class.'"' : '').'><li>'.implode('</li><li>', $arr_row['value']).'</li></ul>';
			} else {
				$html = '<div'.($str_class ? ' class="'.$str_class.'"' : '').'>'.$arr_row['value'].'</div>';
			}
			
			$return .= '<li'.($arr_row['source'] ? ' class="source"' : '').'>'.((int)$handle ? '<span>'.$html_handle.'</span>' : '').$html.((string)$handle == 'append' ? '<span>'.$html_handle.'</span>' : '').'</li>';
		}
		$return .= '</ul>';
		
		return $return;
	}
	
	public static function createDataTableHeading($id, $arr_options = ['search' => true]) {
		
		$arr_options_data = [];
		
		if (!keyIsUncontested('search', $arr_options)) {
			$arr_options_data[] = 'data-search="0"';
		}
		if ($arr_options['filter']) {
			$arr_options_data[] = 'data-filter="'.$arr_options['filter'].'"';
		}
		if ($arr_options['filter_settings']) {
			$arr_options_data[] = 'data-filter_settings="'.htmlspecialchars($arr_options['filter_settings']).'"';
		}
		if ($arr_options['filter_search']) {
			$arr_options_data[] = 'data-filter_search="'.htmlspecialchars($arr_options['filter_search']).'"';
		}
		if ($arr_options['order']) {
			$arr_options_data[] = 'data-order="'.($arr_options['order'] == true ? 'y:cms_general:order_datatable-0' : $arr_options['order']).'"';
		}
		if ($arr_options['delay']) {
			$arr_options_data[] = 'data-delay="'.$arr_options['delay'].'"';
		}
		if ($arr_options['pause']) {
			$arr_options_data[] = 'data-pause="1"';
		}
	
		$html = '<table class="display'.($arr_options['class'] ? ' '.$arr_options['class'] : '').'" id="'.$id.'"'.($arr_options_data ? ' '.implode(' ', $arr_options_data) : '').'>';
		
		return $html;
	}
	
	public static function prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, $sql_body = '', $sql_index_body = '', $sql_where_default = '') {
		
		$arr_sql_columns = array_values($arr_sql_columns);
		$arr_sql_columns_search = ($arr_sql_columns_search ? array_values($arr_sql_columns_search) : $arr_sql_columns);
		$arr_sql_columns_as = ($arr_sql_columns_as ? array_values($arr_sql_columns_as) : $arr_sql_columns);
					
		$sql_limit = '';
		
		if (isset($_POST['nr_records_start']) && $_POST['nr_records_length'] != '-1') {
			
			$sql_limit = "LIMIT ".(int)$_POST['nr_records_length']." OFFSET ".(int)$_POST['nr_records_start'];
		}
		
		$sql_order = '';
		
		if ($_POST['arr_order_column']) {
				
			foreach ($_POST['arr_order_column'] as $nr_order => list($nr_column, $str_direction)) {
				
				$sql_order .= $arr_sql_columns[$nr_column]." ".DBFunctions::strEscape($str_direction) .", ";
			}
			
			if ($sql_order) {
				
				$sql_order = "ORDER BY ".substr_replace($sql_order, '', -2);
			}
		}
		
		$sql_where_default = ($sql_where_default ? "WHERE ".$sql_where_default : "");
		$sql_where = $sql_where_default;
		
		if ($_POST['search'] != '')	{
			
			if ($sql_where == '') {
				$sql_where = "WHERE (";
			} else {
				$sql_where .= " AND (";
			}
			
			for ($i = 0; $i < count($arr_sql_columns_search); $i++) {
				
				if (!$arr_sql_columns_search[$i]) {
					continue;
				}
				
				$sql_where .= $arr_sql_columns_search[$i]." LIKE '%".DBFunctions::strEscape($_POST['search'])."%' OR ";
			}
			
			$sql_where = substr_replace($sql_where, '', -3);
			$sql_where .= ")";
		}
		
		for ($i = 0; $i < count($arr_sql_columns); $i++) {
			
			if ($_POST['search_column_'.$i] == true && $_POST['searching_column_'.$i] != '') {
				
				if ($sql_where == '') {
					$sql_where = "WHERE ";
				} else {
					$sql_where .= " AND ";
				}
				
				$sql_where .= $arr_sql_columns[$i]." LIKE '%".DBFunctions::strEscape($_POST['searching_column_'.$i])."%' ";
			}
		}
				
		$sql_body = ($sql_body ?: $sql_table);
		$sql_index_body = ($sql_index_body ?: $sql_index);
								 
		$result = DB::query("SELECT
			".implode(", ", array_filter($arr_sql_columns_as))."
				FROM ".$sql_body."
				".$sql_where." 
			GROUP BY ".$sql_index_body."
			".$sql_order."
			".$sql_limit."
		");
		
		$result_total_filtered = DB::query("SELECT
			COUNT(DISTINCT ".$sql_index.")
				FROM ".$sql_table."
				".$sql_where."
		");
		
		$arr_total_filtered = $result_total_filtered->fetchRow();
		$nr_total_filtered = $arr_total_filtered[0];

		if ($sql_where_default != $sql_where) {
			
			$result_total = DB::query("SELECT
				COUNT(DISTINCT ".$sql_index.")
					FROM ".$sql_table."
					".$sql_where_default."
			");
			
			$arr_total = $result_total->fetchRow();
			$nr_total = $arr_total[0];
		} else {
			
			$nr_total = $nr_total_filtered;
		}
		
		$arr_output = [
			'echo' => intval($_POST['echo']),
			'total_records' => $nr_total,
			'total_records_filtered' => $nr_total_filtered,
			'data' => []
		];
		
		return ['output' => $arr_output, 'result' => $result];
	}
		
	public static function createSelectTags($arr_tags, $name = '', $hide = true, $internal = false) {
		
		$name = ($name ? $name : 'tags');
			
		return '<div class="hide-edit'.($hide ? ' hide' : '').'">'.self::createMultiSelect($name, 'y:cms_general:'.($internal ? 'lookup_internal_tag' : 'lookup_tag').'-0', $arr_tags).'</div><span class="icon" title="'.getLabel('inf_edit_tags').'">'.getIcon('tags').'</span>';
	}
	
	public static function getTags($source_table, $obj_table, $obj_column, $internal = false) {
	
		$arr = [];
	
		$res = DB::query("SELECT t.*
			FROM ".$source_table." s
			JOIN ".$obj_table." o ON (o.".$obj_column." = s.id)
			JOIN ".DB::getTable(($internal ? 'TABLE_INTERNAL_TAGS' :'TABLE_TAGS'))." t ON (t.id = o.tag_id)
			GROUP BY t.id
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr[] = $arr_row;
		}
		
		return $arr;
	}
	
	public static function getObjectTags($obj_table, $obj_column, $obj_id, $internal = false) {
	
		$arr_tags = [];
	
		$res_tags = DB::query("SELECT t.*
			FROM ".$obj_table." ot
			LEFT JOIN ".DB::getTable(($internal ? 'TABLE_INTERNAL_TAGS' :'TABLE_TAGS'))." t ON (t.id = ot.tag_id)
			WHERE ot.".$obj_column." = ".(int)$obj_id."
		");
		
		while ($arr_row = $res_tags->fetchAssoc()) {
			
			$arr_tags['id_'.$arr_row['id']] = $arr_row['name'];
		}
		
		return $arr_tags;
	}
	
	public static function handleTags($obj_table, $obj_column, $obj_id, $tags, $internal = false) {
	
		DB::query("DELETE FROM ".$obj_table."
			WHERE ".$obj_column." IN (".(is_array($obj_id) ? implode(',', $obj_id) : $obj_id).")
		");

		$arr_tags = ($tags ?: []);
	
		$arr_tags_new = [];
		$arr_tags_save = [];
		
		$func_arr_tags_save = function($tag_id) use (&$arr_tags_save, $obj_id) {
			
			unset($arr_tags_save[$tag_id]); // Making $value as a $tags-key prevent double input
			
			foreach ((is_array($obj_id) ? $obj_id : [$obj_id]) as $cur_obj_id) {
				
				if ($arr_tags_save[$tag_id]) {
					$arr_tags_save[$tag_id] .= ",";
				}
				
				$arr_tags_save[$tag_id] .= "(".$cur_obj_id.", ".(int)$tag_id.")";
			}
		};
		
		foreach ($arr_tags as $value) {
			
			$is_id = (substr($value, 0, 3) == "id_" ? true : false);
			
			if ($is_id) {
				$tag_id = substr($value, 3);
				$func_arr_tags_save($tag_id);
			} else {
				$value = trim(str_replace('+', ' ' , $value)); // '+' character not allowed, exclusive for url related tasks
				$arr_tags_new[$value] = $value; // Making $value as a $tags-key prevent double input
			}
		}
		
		$arr_tags_cur = [];
		$res = DB::query("SELECT id, name FROM ".DB::getTable(($internal ? 'TABLE_INTERNAL_TAGS' : 'TABLE_TAGS'))."");
		
		while ($row = $res->fetchArray()) {
			$arr_tags_cur[$row['id']] = strtolower($row['name']); // strtolower to make case-insensitive comparisons
		}
		
		$arr_tags_check = [];
		
		if (count($arr_tags_new)) {
			
			foreach ($arr_tags_new as $value) {
				
				$tag_id = array_search(strtolower($value), $arr_tags_cur); // strtolower to make case-insensitive comparisons
				
				if ($tag_id == true) { // if tag already exists, add it to the $tags array
					
					$func_arr_tags_save($tag_id);
				} else {
					
					if($value == true && !in_array($value, $arr_tags_check)) { // Ignore the empty value(s) from the array, and check for double inputs.
						
						$ret = DB::query("INSERT INTO ".DB::getTable(($internal ? 'TABLE_INTERNAL_TAGS' : 'TABLE_TAGS'))." (name) VALUES ('".DBFunctions::strEscape($value)."')");
						
						$tag_id = DB::lastInsertID();
						$func_arr_tags_save($tag_id);
						$arr_tags_check[] = "$value";
					}
				}
			}
		}
		
		if (count($arr_tags_save)) {
			
			$ret = DB::query("INSERT INTO ".$obj_table." (".$obj_column.", tag_id) VALUES ".implode(",", $arr_tags_save)."");
		}
	}
			
	public static function editBody($body, $name = 'body', $arr_options = []) {
		
		if ($arr_options['data']) {
			
			$arr_options_data = [];
			
			foreach ($arr_options['data'] as $str_attribute => $str_value) {
				$arr_options_data[] = 'data-'.$str_attribute.'="'.htmlspecialchars($str_value).'"';
			}
			
			$str_attributes = implode(' ', $arr_options_data);
		}
		
		return '<textarea name="'.$name.'" class="editor body-content'.($arr_options['inline'] ? ' inline' : '').($arr_options['external'] ? ' external' : '').($arr_options['class'] ? ' '.$arr_options['class'] : '').'"'.($str_attributes ? ' '.$str_attributes : '').'>'.htmlspecialchars($body).'</textarea>';			
	}
}
