<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
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
		
		SiteEndEnvironment::addScript("
			var labeler = ".(int)$_SESSION['CUR_USER']['labeler'].";
		");
	}
	
	const OPTION_GROUP_SEPARATOR = ' ||| ';
	const NAME_GROUP_ITERATOR = 'iterate_';
			
	public static function css() {
	
		$return = 'img.select { max-width: 200px; max-height: 200px; cursor: pointer; border: 1px dashed #bdbdbd; vertical-align: middle; }
				img.select.empty { width: 30px; height: 30px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "
			var labeler = false,
			IS_CMS = ".(int)IS_CMS.",
			DIR_CMS = '".DIR_CMS."',
			URL_BASE_HOME = '".URL_BASE_HOME."';
			
			const func_select_image_check = function(elm_image) {
				
				var elm_image = getElement(elm_image);
				
				if (!elm_image.getAttribute('src')) {
					elm_image.setAttribute('src', \"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'/%3E\");
					elm_image.classList.add('empty');
				} else {
					elm_image.classList.remove('empty');
				}
			};
			
			$(document).on('documentloaded ajaxloaded', function(e) {
			
				if (!getElement(e.detail.elm)) {
					return;
				}
				
				for (let i = 0, len = e.detail.elm.length; i < len; i++) {
			
					const elm = $(e.detail.elm[i]);
				
					if (labeler) {
				
						var elms_input = elm.find('form textarea[name], form input[type=text][name]').not('.editor');
						
						elms_input.each(function() {
							new LabelOption(this, {action: 'y:cms_labels:popup_labels-0', tag: 'L'});
						});
					}

					runElementSelectorFunction(elm, 'img.select', function(elm_found) {
						func_select_image_check(elm_found);
					});
				}
			}).on('editorloaded', function(e) {
				if (labeler) {
					new LabelOption(e.detail.source, {action: 'y:cms_labels:popup_labels-0', tag: 'L'});
				}
			}).on('click', 'img.select', function() {
				
				const elm_image = $(this);
				const elm_input = elm_image.prev('input');
				
				COMMANDS.setData(elm_input, {cache: true});
				COMMANDS.setTarget(elm_input, function(data) {
					
					var data = (data ? data : '');
					
					let src = false;
					let src_cache = false;
					if (typeof data == 'object') {
						src = data.url;
						src_cache = data.cache;
					} else {
						src = data;
						src_cache = data;
					}
					elm_input[0].value = src;
					elm_image[0].setAttribute('src', src_cache);
					
					SCRIPTER.triggerEvent(this, 'change');
					func_select_image_check(elm_image);
				});
				COMMANDS.popupCommand(elm_input);
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
				return;
			}
			
			if (!$value || !is_array($value)) {
				return;
			}
				
			$arr = [];
			
			foreach ($value as $identifier) {
				$arr[$identifier] = getLabel($identifier);
			}
			
			$this->html = $arr;
		}
		
		if ($method == "get_text") {
		
			$this->html = Labels::parseTextVariables($value);
		}
					
		if ($method == "set_language") {
		
			$str_language = ($value ?: $id);
			$str_cur_language = $_SESSION['LANGUAGE_SYSTEM'];
			
			if ($str_language != $str_cur_language) {
				
				$arr_language = cms_language::getLanguage($str_language);
				
				if ($arr_language['lang_code']) {
					$_SESSION['LANGUAGE_SYSTEM'] = $arr_language['lang_code'];
				} else {
					unset($_SESSION['LANGUAGE_SYSTEM']);
				}
				
				Response::location(SiteStartEnvironment::getPageURL());
			}
		}
		
		if ($method == "create_url") {
			
			$str_url = '';
			$str_text = '';
			
			if ($value && $value['selected']) {
				
				$arr_code = FormatTags::getCode('url_attr');
				preg_match($arr_code[0], $value['selected'], $arr_matches);
				
				if ($arr_matches) {
					
					$str_url = $arr_matches[1];
					$str_text = $arr_matches[2];
				} else {
					
					$arr_code = FormatTags::getCode('url');
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
						<div><input type="text" name="url" value="'.strEscapeHTML($str_url).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_text').'</label>
						<div><input type="text" name="text" value="'.strEscapeHTML($str_text).'" /><label>'.getLabel('lbl_optional').'</label></div>
					</li>
				</ul></fieldset>
			</form>';
		
			$this->html = $return;
		}
		
		if ($method == "create_quote") {
			
			$str_cite = '';
			$str_text = '';
			
			if ($value && $value['selected']) {
				
				$arr_code = FormatTags::getCode('quote');
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
						<div><input type="text" name="cite" value="'.strEscapeHTML($str_cite).'" /><label>'.getLabel('lbl_optional').'</label></div>
					</li>
					<li>
						<label>'.getLabel('lbl_text').'</label>
						<div><input type="text" name="text" value="'.strEscapeHTML($str_text).'" /></div>
					</li>
				</ul></fieldset>
			</form>';
		
			$this->html = $return;
		}
		
		if ($method == "return_url") {
		
			$str_url = $_POST['url'];
			
			if ($str_url) {
				
				$str_protocol = FileGet::getProtocolExternal($str_url);
				
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

			$res = DB::query("SELECT
				id, name
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
			
			$this->html = self::createIframeDynamic($value['html'], (!$value['external'] ? '<link href="'.URL_BASE_HOME.'combine/css/" rel="stylesheet" type="text/css" />' : ''), 'back mod '.$style, 'preview');
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
			
			foreach ($arr_order_column as $num_order => list($num_column, $str_direction)) {
				$arr_column_order[$num_column] = [$str_direction, $num_order];
			}
			
			$arr_sort_options = [['id' => 'asc', 'name' => getLabel('lbl_ascending')], ['id' => 'desc', 'name' => getLabel('lbl_descending')]];
			
			$arr_sorter = [];
			
			foreach ($arr_columns as $num_column => $arr_column_info) {
				
				if ($arr_column_info['disable_sort']) {
					continue;
				}

				$num_order = 'b'.$num_column;
				$str_direction = '';
				
				$arr_column = $arr_column_order[$num_column];
				
				if ($arr_column) {
					$str_direction = $arr_column[0];
					$num_order = 'a'.$arr_column[1];
				}
				
				$str_name = ($arr_column_info['title'] ?: $arr_column_info['text']);

				$arr_sorter[$num_order] = ['value' => '<label>'.$str_name.'</label><select name="order['.$num_column.']">'.cms_general::createDropdown($arr_sort_options, $str_direction, true).'</select>'];
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
			$num_order = 0;
			
			foreach ($_POST['order'] as $num_column => $str_direction) {
				
				if (!$str_direction) {
					continue;
				}
				
				$arr_order[$num_column] = [$str_direction, $num_order];
				$num_order++;
			}
			
			$this->html = $arr_order;
		}
									
		// QUERY

	}
	
	public static function selectModuleList($arr_modules, $do_link = true, $num_limit = 9999) {
		
		$cur_parent = '';
		$count = 0;
		$sub = false;
		$new_sub = false;
		
		$return = '';
		
		foreach ($arr_modules as $key => $value) {
			
			if ($value['label']) {
				
				$str_label = $value['label'];
				$str_parent_label = $value['parent_label'];
			} else {
				
				$key::moduleProperties();
				$str_label = $key::$label;
				$str_parent_label = $key::$parent_label;
			}
			
			if (!$str_label) { // Skip non-interface modules
				continue;
			}
			
			sub:
			
			if ($count % $num_limit == 0) { // new list and closing old
				if ($count) {
					if ($sub) {
						$return .= '</ul></li>';
					}
					$return .= '</ul>';
				}
				$return .= '<ul class="mod-list">';
			}
			if ($cur_parent != $str_parent_label) {
				$sub = false;
				if ($cur_parent != "" && $count % $num_limit != 0) { // close old sub
					$return .= '</ul></li>';
				}
				if ($str_parent_label != "") { // create sub
					$return .= '<li class="sub"><h1>'.$str_parent_label.'</h1><ul>';
					$new_sub = true;
					$sub = true;
				}
			} else {
				if ($count % $num_limit == 0 && $sub) { // continue old sub in new list
					$return .= '<li class="sub"><ul>';
				}
			}
			if (!$new_sub) {
				
				$return .= '<li>'.($do_link ? '<a href="/'.$key.'/">'.$str_label.'</a>' : '<span id="mod-'.$key.'">'.$str_label.'</span>').'</li>';
			}
			
			$cur_parent = $str_parent_label;
			$count++;
			
			if ($new_sub) {
				$new_sub = false;
				goto sub;
			}
		}
		
		if ($cur_parent != '') {
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
	
	public static function createDropdown($arr, $selected = false, $empty = false, $label_column = 'name', $id_column = 'id', $arr_options = []) {
		
		$is_strict = ($arr_options['strict'] ?? false);

		$return = ($empty ? '<option value="">'.(is_string($empty) ? $empty : '').'</option>' : '');
		
		// Support for multiple select
		if (!is_array($selected)) {
			
			if (!$is_strict) {
				$selected = ($selected ?: '');
			}
			
			$selected = [$selected];
		}
		$label_column = ($label_column ?? 'name');
		$id_column = ($id_column ?? 'id');
		
		$arr_group = [];

		foreach ($arr as $arr_option) {
			
			$str_label = $arr_option[$label_column];
			
			if (strpos($str_label, static::OPTION_GROUP_SEPARATOR) !== false) {
				
				$str_label = explode(static::OPTION_GROUP_SEPARATOR, $str_label);
				$arr_option[$label_column] = $str_label[1];
				$arr_group[$str_label[0]][] = $arr_option;
			} else {
				
				$arr_attributes = [];
				
				if (isset($arr_option['attr'])) {
					foreach ($arr_option['attr'] as $key => $value) {
						$arr_attributes[] = $key.'="'.$value.'"';
					}
				}
				
				$return .= '<option value="'.strEscapeHTML($arr_option[$id_column]).'"'.($arr_attributes ? ' '.implode(' ', $arr_attributes) : '').(in_array($arr_option[$id_column], $selected, $is_strict) ? ' selected="selected"' : '').'>'.Labels::addContainer(strEscapeHTML($str_label)).'</option>';
			}
		}
		
		foreach ($arr_group as $group => $arr) {
			
			$return .= '<optgroup label="'.Labels::addContainer(strEscapeHTML($group)).'">'
				.self::createDropdown($arr, $selected, false, $label_column, $id_column)
			.'</optgroup>';
		}
		
		return $return;
	}
	
	public static function createSelector($arr, $name, $selected = [], $label_column = 'name', $id_column = 'id') {
		
		$return = '';
		
		foreach ($arr as $arr_option) {
			
			$arr_attributes = [];
			
			if (isset($arr_option['attr'])) {
				foreach ($arr_option['attr'] as $key => $value) {
					$arr_attributes[] = $key.'="'.$value.'"';
				}
			}
			
			$return .= '<label'.(!empty($arr_option['title']) ? ' title="'.Labels::addContainer(strEscapeHTML($arr_option['title'])).'"' : '').'><input type="checkbox" name="'.($name ? $name.'['.$arr_option[$id_column].']' : $arr_option[$id_column]).'" value="'.$arr_option[$id_column].'"'.($arr_attributes ? ' '.implode(' ', $arr_attributes) : '').($selected == 'all' || in_array($arr_option[$id_column], $selected) ? ' checked="checked"' : '').' /><span>'.Labels::addContainer($arr_option[$label_column]).'</span></label>';
		}
		
		return $return;
	}
	
	public static function createSelectorRadio($arr, $name, $selected = false, $label_column = 'name', $id_column = 'id') {
		
		$return = '';
		
		foreach ($arr as $arr_option) {
			
			$arr_attributes = [];
				
			if (isset($arr_option['attr'])) {
				foreach ($arr_option['attr'] as $key => $value) {
					$arr_attributes[] = $key.'="'.$value.'"';
				}
			}
				
			$return .= '<label'.(!empty($arr_option['title']) ? ' title="'.Labels::addContainer(strEscapeHTML($arr_option['title'])).'"' : '').'><input type="radio" name="'.$name.'" value="'.$arr_option[$id_column].'"'.($arr_attributes ? ' '.implode(' ', $arr_attributes) : '').($arr_option[$id_column] == $selected ? ' checked="checked"' : '').' /><span>'.Labels::addContainer($arr_option[$label_column]).'</span></label>';
		}
		
		return $return;
	}
	
	public static function createSelectorList($arr, $name, $selected = [], $label_column = 'name', $id_column = 'id', $arr_options = []) {
				
		$return = '<ul class="select'.($arr_options['diverse'] ? ' diverse' : '').'">';
		
		$label_column = ($label_column ?? 'name');
		$id_column = ($id_column ?? 'id');
		
		foreach ($arr as $arr_option) {
			
			$str_class = (is_array($arr_option['class']) ? implode(' ', $arr_option['class']) : $arr_option['class']);
			$arr_attributes = [];
			
			if (isset($arr_option['attr'])) {
				foreach ($arr_option['attr'] as $key => $value) {
					$arr_attributes[] = $key.'="'.$value.'"';
				}
			}
			
			$return .= '<li'.($str_class ? ' class="'.$str_class.'"' : '').'><label'.(!empty($arr_option['title']) ? ' title="'.Labels::addContainer(strEscapeHTML($arr_option['title'])).'"' : '').'><input type="checkbox" name="'.($name ? $name.'['.$arr_option[$id_column].']' : $arr_option[$id_column]).'" value="'.$arr_option[$id_column].'"'.($arr_attributes ? ' '.implode(' ', $arr_attributes) : '').($selected == 'all' || in_array($arr_option[$id_column], $selected) ? ' checked="checked"' : '').' /><div>'.Labels::addContainer($arr_option[$label_column]).'</div></label></li>';
		}
		
		$return .= '</ul>';
		
		return ($arr ? $return : '<span>'.getLabel('lbl_none').'</span>');
	}
	
	public static function createSelectorRadioList($arr, $name, $selected = false, $label_column = 'name', $id_column = 'id', $arr_options = []) {
				
		$return = '<ul class="select'.($arr_options['diverse'] ? ' diverse' : '').'">';
		
		$label_column = ($label_column ?? 'name');
		$id_column = ($id_column ?? 'id');
		
		foreach ($arr as $arr_option) {
			
			$str_class = (is_array($arr_option['class']) ? implode(' ', $arr_option['class']) : $arr_option['class']);
			$arr_attributes = [];
			
			if (isset($arr_option['attr'])) {
				foreach ($arr_option['attr'] as $key => $value) {
					$arr_attributes[] = $key.'="'.$value.'"';
				}
			}
			
			$return .= '<li'.($str_class ? ' class="'.$str_class.'"' : '').'><label'.(!empty($arr_option['title']) ? ' title="'.Labels::addContainer(strEscapeHTML($arr_option['title'])).'"' : '').'><input type="radio" name="'.$name.'" value="'.$arr_option[$id_column].'"'.($arr_attributes ? ' '.implode(' ', $arr_attributes) : '').($arr_option[$id_column] == $selected ? ' checked="checked"' : '').' /><div>'.Labels::addContainer($arr_option[$label_column]).'</div></label></li>';
		}
		
		$return .= '</ul>';
		
		return ($arr ? $return : '<span>'.getLabel('lbl_none').'</span>');
	}
	
	public static function createDefineDate($date, $name = '', $hide = true) {
		
		$date = ($date && $date != '0000-00-00 00:00:00' ? $date : time());
		$date = (is_string($date) ? strtotime($date) : $date);
		$name = ($name ?: 'date');
		
		return '<div class="hide-edit'.($hide ? ' hide' : '').'"><input type="text" class="date datepicker" name="'.$name.'" value="'.date('d-m-Y', $date).'" /><input type="text" class="date-time" name="'.$name.'_t" value="'.date('H:i', $date).'" /></div><span class="icon" title="'.getLabel('inf_edit_date').'">'.getIcon('date').'</span>';
	}
	
	public static function createFileBrowser($multi = false, $name = 'file') {
		
		$name_file = ($multi ? $name.'[]' : $name);
		$num_size_limit = FileStore::getSizeLimitClient(FileStore::STORE_FILE);
		
		return '<div class="input filebrowse"><div class="select"><input type="file" name="'.$name_file.'" data-size="'.$num_size_limit.'"'.($multi ? ' multiple="multiple"' : '').' /><label><span></span><input type="text" name="'.$name.'" placeholder="'.getLabel('lbl_size_max').': '.bytes2String($num_size_limit).'" /></label></div>'.($multi ? '<ul></ul>' : '').'<progress value="0" max="100"></progress></div>'; // Also include a input with type="text" and name="..." to make it 'visible' for normal POSTED data iteration
	}
	
	public static function createPickColor($value, $name = '', $arr_options = []) {
		
		$name = ($name ?: 'color');
		
		return '<div class="input pickcolor'.($arr_options['class'] ? ' '.$arr_options['class'] : '').'"'.($arr_options['alpha'] ? ' data-alpha="1"' : '').($arr_options['info'] ? ' title="'.strEscapeHTML($arr_options['info']).'"' : '').'><input type="text" name="'.$name.'" value="'.$value.'" /></div>';
	}
	
	public static function createImageSelector($value, $name = 'img') {
		
		return '<input type="hidden" name="'.$name.'" id="y:cms_media:media_popup-0" value="'.$value.'" /><img class="select" src="'.($value ? SiteStartEnvironment::getCacheURL('img', [200, 200], $value) : '').'" />';
	}
	
	public static function createMultiSelect($name, $id, $arr_tags, $id_value = false, $arr_options = []) {
		
		$str_tags = '';
		foreach ($arr_tags as $key => $value) {
			$str_tags .= '<li><span><input type="hidden" name="'.$name.'['.$key.']" value="'.$key.'"/>'.Labels::addContainer($value).'</span><span class="handler"></span></li>';
		}

		if ($arr_options['delay']) {
			$arr_options['attr']['data-delay'] = $arr_options['delay'];
		}
		
		$arr_attributes = [];
			
		if (isset($arr_options['attr'])) {
			foreach ($arr_options['attr'] as $key => $value) {
				$arr_attributes[] = $key.'="'.$value.'"';
			}
		}
				
		return '<input type="hidden" name="'.$name.'" value=""'.($id_value ? ' id="'.$id_value.'"' : '').' /><div class="autocomplete tags'.($arr_options['list'] ? ' list' : '').'"><input type="hidden" name="'.$name.'" value="" /><ul>'.$str_tags.'</ul></div><input type="search" class="autocomplete multi" id="'.$id.'" value=""'.($arr_attributes ? ' '.implode(' ', $arr_attributes) : '').($arr_options['order'] ? ' data-order="1"' : '').' />';
	}
	
	public static function createRegularExpressionEditor($arr_regex, $name = '', $arr_options = []) {
		
		$name = ($name ?: 'regex');
		
		$str_info = ($arr_options['info'] ?: getLabel('inf_regular_expression_match'));

		return '<div class="input regex'.($arr_options['class'] ? ' '.$arr_options['class'] : '').'" title="'.strEscapeHTML($str_info).'"><span></span><input type="text" name="'.$name.'[pattern]" placeholder="'.getLabel('lbl_match').'" value="'.strEscapeHTML($arr_regex['pattern']).'" /><span></span><input type="text" name="'.$name.'[flags]" value="'.strEscapeHTML($arr_regex['flags']).'" /></div>';
	}
	
	public static function createRegularExpressionReplaceEditor($arr_regex, $name = '', $do_switch = false, $arr_options = []) {
		
		$name = ($name ?: 'regex');
		
		$str_info = ($arr_options['info'] ?: getLabel('inf_regular_expression_replace'));
		$html_enable = '';
		
		if ($do_switch) {
			$html_enable = '<label title="'.strEscapeHTML($str_info).'"><input type="checkbox" name="'.$name.'[enable]" value="1"'.($arr_regex['enable'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_regular_expression_abbr').'</span></label>';
		}
		
		return '<div class="input regex"'.(!$do_switch ? ' title="'.strEscapeHTML($str_info).'"' : '').'><span></span><input type="text" name="'.$name.'[pattern]" placeholder="'.getLabel('lbl_match').'" value="'.strEscapeHTML($arr_regex['pattern']).'" /><span></span><input type="text" name="'.$name.'[flags]" value="'.strEscapeHTML($arr_regex['flags']).'" /><span></span><input type="text" name="'.$name.'[template]" placeholder="'.getLabel('lbl_replace').'" value="'.strEscapeHTML($arr_regex['template']).'" />'.$html_enable.'</div>';
	}
	
	public static function createIframeDynamic($body, $head = false, $body_class = false, $class = false) {
		
		return '<iframe src="about:blank" data-body="'.parseBody($body, ['function' => 'strEscapeHTML']).'"'.($head ? ' data-head="'.strEscapeHTML($head).'"' : '').($body_class ? ' data-body-class="'.$body_class.'"' : '').($class ? ' class="'.$class.'"' : '').'></iframe>';
	}
	
	public static function createSorter($arr_rows, $handle = false, $reverse = false, $arr_options = []) {
		
		$arr_attributes = [];
		
		if ($arr_options['auto_add']) {
			$arr_attributes[] = 'data-auto_add="1"';
		}
		if ($arr_options['auto_clean']) {
			$arr_attributes[] = 'data-auto_clean="1"';
		}
		if ($arr_options['limit']) {
			$arr_attributes[] = 'data-limit="'.(int)$arr_options['limit'].'"';
		}
		if (isset($arr_options['attr'])) {
			foreach ($arr_options['attr'] as $key => $value) {
				$arr_attributes[] = $key.'="'.$value.'"';
			}
		}
		
		$return = '<ul class="sorter'.($reverse ? ' reverse' : '').($arr_options['full'] ? ' full' : '').($arr_options['diverse'] ? ' diverse' : '').'"'.($arr_attributes ? ' '.implode(' ', $arr_attributes) : '').'>';
		
		$html_handle = '<span class="icon">'.getIcon('updown').'</span>';
		
		foreach ($arr_rows as $arr_row) {
			
			$str_class = (is_array($arr_row['class']) ? implode(' ', $arr_row['class']) : $arr_row['class']);
			$arr_attributes = [];
			
			if (isset($arr_row['attr'])) {
				foreach ($arr_row['attr'] as $key => $value) {
					$arr_attributes[] = $key.'="'.$value.'"';
				}
			}
			
			if (is_array($arr_row['value'])) {
				$html = '<ul'.($str_class ? ' class="'.$str_class.'"' : '').'><li>'.implode('</li><li>', $arr_row['value']).'</li></ul>';
			} else {
				$html = '<div'.($str_class ? ' class="'.$str_class.'"' : '').'>'.$arr_row['value'].'</div>';
			}

			$return .= '<li'.($arr_row['source'] ? ' class="source"' : '').($arr_attributes ? ' '.implode(' ', $arr_attributes) : '').'>'.((int)$handle ? '<span>'.$html_handle.'</span>' : '').$html.((string)$handle == 'append' ? '<span>'.$html_handle.'</span>' : '').'</li>';
		}
		$return .= '</ul>';
		
		return $return;
	}
	
	public static function createDataTableHeading($id, $arr_options = ['search' => true]) {
		
		$arr_attributes = [];
		
		if (!keyIsUncontested('search', $arr_options)) {
			$arr_attributes[] = 'data-search="0"';
		}
		if ($arr_options['filter']) {
			$arr_attributes[] = 'data-filter="'.$arr_options['filter'].'"';
		}
		if ($arr_options['filter_settings']) {
			$arr_attributes[] = 'data-filter_settings="'.strEscapeHTML($arr_options['filter_settings']).'"';
		}
		if ($arr_options['search_settings']) {
			$arr_attributes[] = 'data-search_settings="'.strEscapeHTML($arr_options['search_settings']).'"';
		}
		if ($arr_options['order']) {
			$arr_attributes[] = 'data-order="'.($arr_options['order'] == true ? 'y:cms_general:order_datatable-0' : $arr_options['order']).'"';
		}
		if ($arr_options['delay']) {
			$arr_attributes[] = 'data-delay="'.$arr_options['delay'].'"';
		}
		if ($arr_options['pause']) {
			$arr_attributes[] = 'data-pause="1"';
		}
	
		$html = '<table class="display'.($arr_options['class'] ? ' '.$arr_options['class'] : '').'" id="'.$id.'"'.($arr_attributes ? ' '.implode(' ', $arr_attributes) : '').'>';
		
		return $html;
	}
	
	public static function prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, $sql_body = '', $sql_index_body = '', $sql_where_default = '', $arr_interact = null) {
		
		$arr_interact = ($arr_interact === null ? $_POST : $arr_interact);
		
		$arr_sql_columns = array_values($arr_sql_columns);
		$arr_sql_columns_search = ($arr_sql_columns_search ? array_values($arr_sql_columns_search) : $arr_sql_columns);
		$arr_sql_columns_as = ($arr_sql_columns_as ? array_values($arr_sql_columns_as) : $arr_sql_columns);
					
		$sql_limit = '';
		
		if (isset($arr_interact['num_records_start']) && $arr_interact['num_records_length'] != '-1') {
			$sql_limit = "LIMIT ".(int)$arr_interact['num_records_length']." OFFSET ".(int)$arr_interact['num_records_start'];
		}
		
		$sql_order = '';
		
		if ($arr_interact['arr_order_column']) {
				
			foreach ($arr_interact['arr_order_column'] as $num_order => list($num_column, $str_direction)) {
				$sql_order .= ($sql_order !== '' ? ', ' : '').$arr_sql_columns[$num_column]." ".DBFunctions::strEscape($str_direction);
			}
		}
		
		$sql_order = "ORDER BY ".($sql_order ?: $sql_index);
		
		$sql_where_default = ($sql_where_default ? "WHERE ".$sql_where_default : "");
		$sql_where = $sql_where_default;
		
		$str_search = $arr_interact['search'];
		
		if ($str_search != '')	{

			if ($sql_where == '') {
				$sql_where = "WHERE (";
			} else {
				$sql_where .= " AND (";
			}
			
			for ($i = 0; $i < count($arr_sql_columns_search); $i++) {
				
				if (!$arr_sql_columns_search[$i]) {
					continue;
				}
				
				$sql_column = $arr_sql_columns_search[$i];
				$str_search_use = $str_search;
				
				if (is_array($sql_column)) {
					
					$arr_column = $sql_column;
					$sql_column = $arr_column['field'];
					
					if ($arr_column['json']) {
						$str_search_use = substr(value2JSON($str_search_use), 1, -1);
					}
				}
				
				if (DB::ENGINE_IS_POSTGRESQL) {
					$sql_column = DBFunctions::castAs($sql_column, DBFunctions::CAST_TYPE_STRING);
				}
				
				$sql_where .= DBFunctions::searchMatch($sql_column, $str_search_use)." OR ";
			}
			
			$sql_where = substr_replace($sql_where, '', -3);
			$sql_where .= ")";
		}
		
		for ($i = 0; $i < count($arr_sql_columns); $i++) {
			
			$do_search = ($arr_interact['search_column_'.$i] ?? null);
			$str_search = ($arr_interact['searching_column_'.$i] ?? null);
			
			if ($do_search == true && $str_search != '') {
				
				if ($sql_where == '') {
					$sql_where = "WHERE ";
				} else {
					$sql_where .= " AND ";
				}
				
				$sql_column = $arr_sql_columns[$i];
				$str_search_use = $str_search;
				
				if (is_array($sql_column)) {
					
					$arr_column = $sql_column;
					$sql_column = $arr_column['field'];
					
					if ($arr_column['json']) {
						$str_search_use = substr(value2JSON($str_search_use), 1, -1);
					}
				}
				
				if (DB::ENGINE_IS_POSTGRESQL) {
					$sql_column = DBFunctions::castAs($sql_column, DBFunctions::CAST_TYPE_STRING);
				}
				
				$sql_where .= DBFunctions::searchMatch($sql_column, $str_search_use);
			}
		}
		
		$sql_body = ($sql_body ?: $sql_table);
		
		$result = DB::query("SELECT
			".implode(", ", array_filter($arr_sql_columns_as))."
				FROM ".$sql_body."
				".$sql_where." 
			".($sql_index_body ? "GROUP BY ".$sql_index_body : "")."
			".$sql_order."
			".$sql_limit."
		");
		
		if (DB::ENGINE_IS_POSTGRESQL) {
			
			$result_total_filtered = DB::query("SELECT
				COUNT(*) FROM (
					SELECT DISTINCT ".$sql_index."
						FROM ".$sql_table."
						".$sql_where."
					) AS foo
			");
		} else {
			
			$result_total_filtered = DB::query("SELECT
				COUNT(DISTINCT ".$sql_index.")
					FROM ".$sql_table."
					".$sql_where."
			");
		}
		
		$arr_total_filtered = $result_total_filtered->fetchRow();
		$num_total_filtered = $arr_total_filtered[0];

		if ($sql_where_default != $sql_where) {
			
			if (DB::ENGINE_IS_POSTGRESQL) {
				
				$result_total = DB::query("SELECT
					COUNT(*) FROM (
						SELECT DISTINCT ".$sql_index."
							FROM ".$sql_table."
							".$sql_where_default."
						) AS foo
				");
			} else {
				
				$result_total = DB::query("SELECT
					COUNT(DISTINCT ".$sql_index.")
						FROM ".$sql_table."
						".$sql_where_default."
				");
			}
			
			$arr_total = $result_total->fetchRow();
			$num_total = $arr_total[0];
		} else {
			
			$num_total = $num_total_filtered;
		}
		
		$arr_output = [
			'total_records' => $num_total,
			'total_records_filtered' => $num_total_filtered,
			'data' => []
		];
		
		return ['output' => $arr_output, 'result' => $result];
	}
		
	public static function createSelectTags($arr_tags, $name = '', $hide = true, $is_internal = false) {
		
		$name = ($name ? $name : 'tags');
			
		return '<div class="hide-edit'.($hide ? ' hide' : '').'">'.self::createMultiSelect($name, 'y:cms_general:'.($is_internal ? 'lookup_internal_tag' : 'lookup_tag').'-0', $arr_tags).'</div><span class="icon" title="'.getLabel('inf_edit_tags').'">'.getIcon('tags').'</span>';
	}
	
	public static function createViewTags($arr_tags, $str_url) {
		
		foreach ($arr_tags as $str_tag) {

			$str_tag = Labels::parseTextVariables($str_tag);
			$str_tag_title = strEscapeHTML(getLabel('lbl_tag').' <strong>'.$str_tag.'</strong>');
			$str_tag = strEscapeHTML($str_tag);
				
			$str_html_tags .= '<a title="'.$str_tag_title.'" href="'.($str_url ? $str_url.str_replace(' ', '+', $str_tag) : '#').'" data-tag="'.$str_tag.'">'.$str_tag.'</a>';
		}
		
		$str_html_tags = '<div class="tags content"><span class="icon">'.getIcon('tags').'</span>'.$str_html_tags.'</div>';
		
		return $str_html_tags;
	}
	
	public static function getTags($sql_table_source, $sql_table_object, $sql_column_object, $is_internal = false, $arr_tags = false) {
	
		$arr = [];
		
		$sql_tags = '';
		
		if ($arr_tags) {
			
			$arr_tags = DBFunctions::arrEscape((!is_array($arr_tags) ? (array)$arr_tags : $arr_tags));
			$sql_tags = 't.name '.(count($arr_tags) == 1 ? "= '".current($arr_tags)."'" : "IN ('".arr2String($arr_tags, "','")."')");
		}
	
		$res = DB::query("SELECT
			t.*
				FROM ".$sql_table_source." s
				JOIN ".$sql_table_object." o ON (o.".$sql_column_object." = s.id)
				JOIN ".DB::getTable(($is_internal ? 'TABLE_INTERNAL_TAGS' :'TABLE_TAGS'))." t ON (t.id = o.tag_id)
			".($sql_tags ? "WHERE ".$sql_tags : '')."
			GROUP BY t.id
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			$arr[] = $arr_row;
		}
		
		return $arr;
	}
	
	public static function getTagsByObject($sql_table_object, $sql_column_object, $object_id, $is_internal = false) {
	
		$arr_tags = [];
	
		$res_tags = DB::query("SELECT t.*
			FROM ".$sql_table_object." ot
			LEFT JOIN ".DB::getTable(($is_internal ? 'TABLE_INTERNAL_TAGS' :'TABLE_TAGS'))." t ON (t.id = ot.tag_id)
			WHERE ot.".$sql_column_object." = ".(int)$object_id."
		");
		
		while ($arr_row = $res_tags->fetchAssoc()) {
			
			$arr_tags['id_'.$arr_row['id']] = $arr_row['name'];
		}
		
		return $arr_tags;
	}
	
	public static function handleTags($sql_table_object, $sql_column_object, $object_id, $arr_tags, $is_internal = false) {
	
		DB::query("DELETE FROM ".$sql_table_object."
			WHERE ".$sql_column_object." IN (".(is_array($object_id) ? implode(',', $object_id) : $object_id).")
		");

		$arr_tags = ($arr_tags ?: []);
	
		$arr_tags_new = [];
		$arr_tags_save = [];
		
		$func_arr_tags_save = function($tag_id) use (&$arr_tags_save, $object_id) {
			
			unset($arr_tags_save[$tag_id]); // Making $value as a $tags-key prevent double input
			
			foreach ((is_array($object_id) ? $object_id : [$object_id]) as $cur_object_id) {
				
				if ($arr_tags_save[$tag_id]) {
					$arr_tags_save[$tag_id] .= ',';
				}
				
				$arr_tags_save[$tag_id] .= "(".$cur_object_id.", ".(int)$tag_id.")";
			}
		};
		
		foreach ($arr_tags as $value) {
			
			$is_id = (substr($value, 0, 3) == 'id_' ? true : false);
			
			if ($is_id) {
				$tag_id = substr($value, 3);
				$func_arr_tags_save($tag_id);
			} else {
				$value = trim(str_replace('+', ' ' , $value)); // '+' character not allowed, exclusive for url related tasks
				$arr_tags_new[$value] = $value; // Making $value as a $tags-key prevent double input
			}
		}
		
		$arr_tags_cur = [];
		$res = DB::query("SELECT id, name FROM ".DB::getTable(($is_internal ? 'TABLE_INTERNAL_TAGS' : 'TABLE_TAGS'))."");
		
		while ($row = $res->fetchArray()) {
			$arr_tags_cur[$row['id']] = strtolower($row['name']); // strtolower to make case-insensitive comparisons
		}
		
		$arr_tags_check = [];
		
		if (count($arr_tags_new)) {
			
			foreach ($arr_tags_new as $value) {
				
				$tag_id = array_search(strtolower($value), $arr_tags_cur); // strtolower to make case-insensitive comparisons
				
				if ($tag_id) { // if tag already exists, add it to the $tags array
					
					$func_arr_tags_save($tag_id);
				} else {
					
					if ($value && !in_array($value, $arr_tags_check)) { // Ignore the empty value(s) from the array, and check for double inputs.
						
						$res = DB::query("INSERT INTO ".DB::getTable(($is_internal ? 'TABLE_INTERNAL_TAGS' : 'TABLE_TAGS'))." (name) VALUES ('".DBFunctions::strEscape($value)."')");
						
						$tag_id = DB::lastInsertID();
						$func_arr_tags_save($tag_id);
						$arr_tags_check[] = "$value";
					}
				}
			}
		}
		
		if (count($arr_tags_save)) {
			
			$res = DB::query("INSERT INTO ".$sql_table_object." (".$sql_column_object.", tag_id) VALUES ".implode(",", $arr_tags_save)."");
		}
	}
			
	public static function editBody($body, $name = 'body', $arr_options = []) {
		
		$str_attributes = '';
		$str_menu = '';
		
		if (!empty($arr_options['data'])) {
			
			$arr_options_data = [];
			
			foreach ($arr_options['data'] as $str_attribute => $str_value) {
				$arr_options_data[] = 'data-'.$str_attribute.'="'.strEscapeHTML($str_value).'"';
			}
			
			$str_attributes = implode(' ', $arr_options_data);
		}
		
		if (!empty($arr_options['menu'])) {
			
			$str_menu = '<menu>'.$arr_options['menu'].'</menu>';
		}
		
		return '<textarea name="'.$name.'" class="editor body-content'.($arr_options['inline'] ? ' inline' : '').($arr_options['external'] ? ' external' : '').($arr_options['class'] ? ' '.$arr_options['class'] : '').'"'.($str_attributes ? ' '.$str_attributes : '').'>'
			.strEscapeHTML($body)
		.'</textarea>'
		.$str_menu;			
	}
}
