<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_MEDIA', DB::$database_home.'.def_media');
DB::setTable('TABLE_MEDIA_INTERNAL_TAGS', DB::$database_home.'.def_media_internal_tags');

Settings::set('UPLOAD_IMAGE_WIDTH', '0');
Settings::set('UPLOAD_IMAGE_HEIGHT', '0');

class cms_media extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_media');
		static::$parent_label = getLabel('ttl_content');
	}
	
	const DIR_STORAGE_MEDIA = DIR_SITE_STORAGE.DIR_UPLOAD;
	
	private static $arr_database_locations = [];
	
	public function contents() {

		$return = '<div class="section"><h1 id="x:cms_media:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup add" value="add" /></h1>
		<div class="media">';
						
			$return .= '<div class="options">
				<label>'.getLabel('lbl_media_type').': </label><select name="type">'.cms_general::createDropdown(self::getFileTypes(), false, true).'</select>
				<label>'.getLabel('lbl_internal_tag').': </label><select name="tag_id">'.cms_general::createDropdown(cms_general::getTags(DB::getTable('TABLE_MEDIA'), DB::getTable('TABLE_MEDIA_INTERNAL_TAGS'), 'media_id', true), 0, true).'</select>
			</div>';
			
			$return .= '<table class="display" id="d:cms_media:data-0">
					<thead> 
						<tr>			
							<th class="max"><span>'.getLabel('lbl_label').'</span></th>
							<th><span>'.getLabel('lbl_type').'</span></th>
							<th class="limit"><span>'.getLabel('lbl_size').'</span></th>
							<th class="disable-sort limit"><span>'.getLabel('lbl_usage').'</span></th>
							<th class="limit"><span>'.getLabel('lbl_internal_tags').'</span></th>
							<th class="disable-sort menu" id="x:cms_media:media_id-0" title="'.getLabel('lbl_multi_select').'"><input type="button" class="data popup edit" value="e" title="'.getLabel('lbl_edit').'" />'
								.'<input type="button" class="data msg del" value="d" title="'.getLabel('lbl_delete').'" />'
								.'<input type="checkbox" class="multi all" value="" />'
							.'</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="7" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>
						
		</div></div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '	#frm-media .sizing input,
				#frm-media-popup .sizing input { width: 40px; }
				
				#tab-media-info-file embed { width: 100%; height: 100%; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-cms_media', function(elm_scripter) {
			
			var elm_media = elm_scripter.find('.media');
			
			SCRIPTER.runDynamic(elm_media);
		});
		
		SCRIPTER.dynamic('.media', function(elm_scripter) {
		
			elm_scripter.on('change', 'select[name=type]', function() {
				
				var elm_target = $(this).parent().parent().find('table[id^=d\\\:cms_media\\\:data]');
				
				COMMANDS.setData(elm_target[0], {type: $(this).val()});
				elm_target.dataTable('refresh');
			}).on('change', 'select[name=tag_id]', function() {
			
				var elm_target = $(this).parent().parent().find('table[id^=d\\\:cms_media\\\:data]');
				
				COMMANDS.setData(elm_target[0], {tag_id: $(this).val()});
				elm_target.dataTable('refresh');
			});
		});
		
		SCRIPTER.dynamic('#frm-media-popup', '.media');

		SCRIPTER.dynamic('#frm-media, #frm-media-popup', function(elm_scripter) {
		
			elm_scripter.on('change', 'input[name^=file]', function() {
				
				var img_ext = ['jpg', 'jpeg', 'png', 'bmp', 'gif'];
				var show_sizing = false;
				
				var files = $(this)[0].files;
				if (!files || files.length == 1) {
					var match = $(this).val().match(/([^\\\]*)\.(.*)$/);
					if (match[2] && $.inArray(match[2].toLowerCase(), img_ext) >= 0) {
						show_sizing = true;
					}
				} else {
					for (var i = 0; i < files.length; i++) {
						var match = files[i].name.match(/([^\\\]*)\.(.*)$/);
						if (match[2] && $.inArray(match[2].toLowerCase(), img_ext) >= 0) {
							show_sizing = true;
						}
					}
				}

				if (show_sizing) {
					elm_scripter.find('.sizing').removeClass('hide');
				} else {
					elm_scripter.find('.sizing').addClass('hide');
				}
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if (($method == "edit" && $id) || $method == "add") {
		
			if ((int)$id) {
				
				if (is_array($id)) {
					
					$arr_media = self::getMedia($id);
				
					$mode = 'multi_update';
					
					$arr_tags = [];
				} else {
					
					$arr_media_item = self::getMedia($id);	
					
					$arr_tags = cms_general::getTagsByObject(DB::getTable('TABLE_MEDIA_INTERNAL_TAGS'), 'media_id', $id, true);
					
					$mode = 'update';
				}
			} else {
				
				$mode = 'insert';
				
				$arr_tags = [];
			}
			
			$return_file = '<li>
				<label>'.getLabel('lbl_label').'</label>
				<div><input name="label" type="text" value="'.$arr_media_item['label'].'" placeholder="'.($mode == 'multi_update' ? getLabel('lbl_unchanged') : getLabel('lbl_filename')).'"></div>
			</li>
			<li>		
				<label>'.getLabel('lbl_description').'</label>		
				<div><textarea name="description"'.($mode == 'multi_update' ? ' placeholder="'.getLabel('lbl_unchanged').'"' : '').'>'.$arr_media_item['description'].'</textarea></div>
			</li>
			<li class="sizing hide">		
				<label>'.getLabel('lbl_size_max').'</label>		
				<div><label><input type="text" name="sizing_w" value="'.Settings::get('UPLOAD_IMAGE_WIDTH').'" /> <span>'.getLabel('lbl_width').'</span></label><label><input type="text" name="sizing_h" value="'.Settings::get('UPLOAD_IMAGE_HEIGHT').'" /> <span>'.getLabel('lbl_height').'</span></label></div>
			</li>
			<li>
				<label>'.getLabel('lbl_internal_tags').'</label>
				<div>'.cms_general::createSelectTags($arr_tags, '', !$arr_tags, true).'</div>
			</li>';
													
			$this->html = '<form id="frm-media" data-method="'.$mode.'">';
				
				if ($mode == "update" || $mode == "multi_update") {
					
					$this->html .= '<div class="files">';
					
						if ($mode == 'update') {
							
							$this->html .= '/'.DIR_CMS.DIR_UPLOAD.$arr_media_item['directory'].$arr_media_item['filename'];
						} else if ($mode == 'multi_update') {
							
							$this->html .= '<ul class="sorter">';
							
								foreach ($arr_media as $media_id => $value) {
									$this->html .= '<li><span><span class="icon">'.getIcon('updown').'</span></span><div><input type="hidden" name="ids[]" value="'.$media_id.'" />/'.DIR_CMS.DIR_UPLOAD.$value['directory'].$value['filename'].'</div></li>';
								}
							
							$this->html .= '</ul>';
						}
						
					$this->html .= '</div><hr />';
					
					$this->html .= '<div class="tabs">
						<ul>
							<li><a href="#tab-media-edit-file">'.getLabel('lbl_file').'</a></li>
							<li><a href="#tab-media-edit-actions">'.getLabel('lbl_actions').'</a></li>
						</ul>
						<div id="tab-media-edit-file">
							<fieldset><ul>
								'.$return_file.'
							</ul></fieldset>
						</div>
						<div id="tab-media-edit-actions">
							<fieldset><ul>
								<li>
									<label>'.getLabel('lbl_media_replace_occurrences').'</label>
									<div>'.cms_general::createImageSelector(false, 'replace_with').'</div>
								</li>
							</ul></fieldset>
						</div>
					</div>';
				} else {
					
					$this->html .= '<fieldset><ul>
						<li>
							<label>'.getLabel('lbl_files').'</label>
							<div>'.cms_general::createFileBrowser(true).'</div>
						</li>
						'.$return_file.'
					</ul></fieldset>';
				}
			
			$this->html .= '</form>';
			
			$this->validate = ['file[]' => 'required'];
		}
		
		if ($method == "media_info" && (int)$id) {
		
			$arr_media = self::getMedia($id);

			$arr_locations = self::searchMediaLocations($arr_media['filename']);
			$num_location_count = array_sum(arrValuesRecursive('count', $arr_locations));
		
			$this->html .= '<div class="tabs">
				<ul>
					<li><a href="#tab-media-info-file">'.getLabel('lbl_file').'</a></li>
					<li><a href="#tab-media-info-prop">'.getLabel('lbl_properties').'</a></li>
					<li><a href="#tab-media-info-loc">'.getLabel('lbl_location').''.($num_location_count ? ' ('.$num_location_count.')' : '').'</a></li>
				</ul>
				<div id="tab-media-info-file">';
			
					$enucleate = new EnucleateMedia($arr_media['directory'].$arr_media['filename'], static::DIR_STORAGE_MEDIA, '/'.DIR_CMS.DIR_UPLOAD);
					$enucleate->setSizing(false, false, false);
					$this->html .= $enucleate->enucleate(EnucleateMedia::VIEW_HTML, ['enlarge' => false]);
								
				$this->html .= '</div>
				<div id="tab-media-info-prop">
					<div class="record"><dl>
						<li>
							<dt>'.getLabel('lbl_label').'</dt>
							<dd>'.$arr_media['label'].'</dd>
						</li>
						<li>
							<dt>'.getLabel('lbl_type').'</dt>
							<dd>'.$arr_media['type'].'</dd>
						</li>
						<li>
							<dt>'.getLabel('lbl_size').'</dt>
							<dd>'.bytes2String($arr_media['size']).'</dd>
						</li>
						<li>
							<dt>'.getLabel('lbl_location').'</dt>
							<dd>'.$media_url.'</dd>
						</li>
						<li>
							<dt>'.getLabel('lbl_description').'</dt>
							<dd>'.nl2br($arr_media['description']).'</dd>
						</li>
					</dl></div>
				</div>
				<div id="tab-media-info-loc">
				<ul>';
			
					foreach ($arr_locations as $arr_location) {
						$this->html .= '<li><strong>['.strtoupper($arr_location['table']).']</strong> '.$arr_location['column'].' ('.$arr_location['count'].')</li>';
					}
					
				$this->html .= '</ul>';
					
				$this->html .= '</div>
			</div>';
		}
		
		if ($method == "media_popup") {
				
			$arr_type = [];	
			
			$res = DB::query("SELECT DISTINCT type FROM ".DB::getTable('TABLE_MEDIA')." AS media");
									
			if ($res->getRowCount() > 0) {
				
				while ($arr_row = $res->fetchAssoc()) {
					
					$arr_tmp = explode('/', $arr_row['type']);
					$arr_type[] = $arr_tmp[0]; 
				}
				
				$arr_type = array_unique($arr_type);
			}
		
			$this->html .= '<form id="frm-media-popup" data-method="media_select">
				
				<div class="tabs">
					<ul>
						<li><a href="#tab-media-popup-url">URL</a></li>
						<li><a href="#tab-media-popup-local">'.getLabel('lbl_computer').'</a></li>
						<li><a href="#tab-media-popup-media">'.getLabel('lbl_media').'</a></li>
					</ul>
					
					<div id="tab-media-popup-url">
						<fieldset><ul>
							<li>
								<label>'.getLabel('lbl_external').'</label>
								<div><input name="custom_url" type="text" placeholder="https://" /></div>
							</li>
						</ul></fieldset>
					</div>
					<div id="tab-media-popup-local">
						<fieldset><ul>
							<li>
								<label>'.getLabel('lbl_file').'</label>
								<div>
									'.cms_general::createFileBrowser().'
								</div>
							</li>
							<li>
								<label>'.getLabel('lbl_label').'</label>
								<div><input name="label" type="text" value="" placeholder="'.getLabel('lbl_filename').'"></div>
							</li>
							<li>		
								<label>'.getLabel('lbl_description').'</label>		
								<div><textarea name="description"></textarea></div>
							</li>
							<li class="sizing">		
								<label>'.getLabel('lbl_size_max').'</label>		
								<div><label><input type="text" name="sizing_w" value="'.Settings::get('UPLOAD_IMAGE_WIDTH').'" /> <span>'.getLabel('lbl_width').'</span></label><label><input type="text" name="sizing_h" value="'.Settings::get('UPLOAD_IMAGE_HEIGHT').'" /> <span>'.getLabel('lbl_height').'</span></label></div>
							</li>	
							<li>
								<label>'.getLabel('lbl_internal_tags').'</label>
								<div>'.cms_general::createSelectTags([], '', true, true).'</div>
							</li>
						</ul></fieldset>
					</div>
					<div id="tab-media-popup-media">
					
						<div class="options">
							<label>'.getLabel('lbl_media_type').': </label><select name="type">'.cms_general::createDropdown(self::getFileTypes(), false, true).'</select>
							<label>'.getLabel('lbl_internal_tag').': </label><select name="tag_id">'.cms_general::createDropdown(cms_general::getTags(DB::getTable('TABLE_MEDIA'), DB::getTable('TABLE_MEDIA_INTERNAL_TAGS'), 'media_id', true), 0, true).'</select>
						</div>
					
						<table class="display" id="d:cms_media:data_select-0">
							<thead> 
								<tr>			
									<th class="disable-sort"></th>
									<th class="max">'.getLabel('lbl_label').'</th>
									<th>'.getLabel('lbl_type').'</th>
									<th>'.getLabel('lbl_size').'</th>
									<th>'.getLabel('lbl_internal_tags').'</th>
								</tr> 
							</thead>
							<tbody>
								<tr>
									<td colspan="7" class="empty">'.getLabel('msg_loading_server_data').'</td>
								</tr>
							</tbody>
						</table>
						
					</div>';
				$this->html .= '</div>
			</form>';
		}
		
		// POPUP INTERACT
		
		// DATATABLE
							
		if ($method == "data" || $method == "data_select") {
			
			$sql_column_tags = "(SELECT
				".DBFunctions::sqlImplode('DISTINCT t.name')."
					FROM ".DB::getTable('TABLE_MEDIA_INTERNAL_TAGS')." mt
					LEFT JOIN ".DB::getTable('TABLE_INTERNAL_TAGS')." t ON (t.id = mt.tag_id)
				WHERE mt.media_id = m.id
					".($value['tag_id'] ? "AND mt.tag_id = ".(int)$value['tag_id'] : "")."
			)";
		
			if ($method == 'data_select') {
				$arr_sql_columns = ['TRUE', 'm.label', 'm.type', 'm.size', $sql_column_tags];
				$arr_sql_columns_search = ['', 'm.label', 'm.type', '', '', $sql_column_tags];
			} else {
				$arr_sql_columns = ['m.label', 'm.type', 'm.size', '', $sql_column_tags];
				$arr_sql_columns_search = ['m.label', 'm.type', '', '', $sql_column_tags];
			}
			
			$arr_sql_columns_as =  ['m.label', 'm.type', 'm.size', $sql_column_tags.' AS tags', 'm.filename', 'm.id'];

			$sql_index = 'm.id';
			
			$sql_table = DB::getTable('TABLE_MEDIA')." m";
			
			$sql_where = '';
			
			if ($value['type']) {
				
				$sql_where = "m.type LIKE '".DBFunctions::strEscape($value['type'])."%'";
			}
			if ($value['tag_id']) {
				
				$sql_table .= " JOIN ".DB::getTable('TABLE_MEDIA_INTERNAL_TAGS')." mt ON (mt.media_id = m.id)";
				$sql_where .= ($sql_where ? " AND" : "")." mt.tag_id = ".(int)$value['tag_id'];
			}
											 
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);

			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{

				$arr_data = [];
				
				$arr_data['id'] = 'x:cms_media:media_id-'.$arr_row['id'];
				$arr_data['class'] = 'popup';
				$arr_data['attr']['data-method'] = 'media_info';
				if ($method == 'data_select') {
					$arr_data[] = '<input name="media_item" value="'.$arr_row['id'].'" type="radio" />';
				}				
				$arr_data[] = $arr_row['label'];
				$arr_tmp = explode('/', $arr_row['type']);
				$arr_data[] = $arr_tmp[1];
				$arr_data[] = bytes2String($arr_row['size']);
				
				if ($method == 'data') {
					
					$arr_locations = self::searchMediaLocations($arr_row['filename']);
					$location_count = array_sum(arrValuesRecursive('count', $arr_locations));
					$arr_str_locations = [];
					
					foreach ($arr_locations as $value) {
						$arr_str_locations[] = '['.strtoupper($value['table']).'] '.$value['column'].' ('.$value['count'].')';
					}
					
					$arr_data[] = '<span class="info"><span class="icon" title="'.strEscapeHTML(($arr_str_locations ? implode('<br />', $arr_str_locations) : getLabel('inf_none'))).'">'.getIcon('info').'</span><span>'.(int)$location_count.'</span></span>';
				}
				
				$arr_data[] = $arr_row['tags'];
				
				if ($method == 'data') {
					$arr_data[] = '<input type="button" class="data edit popup edit" value="edit" /><input type="button" class="data del msg del" value="del" /><input class="multi" value="'.$arr_row['id'].'" type="checkbox" />';
				}
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
							
		// QUERY
	
		if ($method == "insert") {

			$arr_files = arrRearrangeParams($_FILES['file']);
		
			foreach ($arr_files as $file) {
				
				self::addMedia($file, $_POST, $_POST['tags']);
			}
						
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "update" && (int)$id) {
			
			self::updateMedia($id, $_POST, $_POST['tags']);

			if ($_POST['replace_with']) {
				self::replaceMediaLocations($id, $_POST['replace_with']);
			}
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "multi_update" && $id) {
			
			self::updateMedia($_POST['ids'], $_POST, ($_POST['tags'] ?: null));
			
			if ($_POST['replace_with']) {
				
				foreach ($_POST['ids'] as $media_id) {
					self::replaceMediaLocations($media_id, $_POST['replace_with']);
				}
			}
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del" && $id) {
			
			self::deleteMedia($id);
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "media_select") {
			
			$media_id = ((int)$_POST['media_item'] ?: false);
			
			if ($_FILES['file']['size']) {
				
				$media_id = self::addMedia($_FILES['file'], $_POST, $_POST['tags']);
			}
						
			if ($media_id) {
				
				$arr_media = self::getMedia($media_id);
				
				$str_url = ((int)$id ? URL_BASE_HOME : '/').DIR_CMS.DIR_UPLOAD.$arr_media['directory'].$arr_media['filename'];
				
				if ($value && $value['cache']) {
					
					$str_url_cache = SiteStartVars::getCacheURL('img', [200, 200], $str_url);
					
					$this->html = ['url' => $str_url, 'cache' => $str_url_cache];
				} else {
				
					$this->html = $str_url;
				}
			} else if ($_POST['custom_url']) {
				
				$this->html = $_POST['custom_url'];
			}
		}
	}
	
	public static function addMedia($file, $arr_media = [], $arr_tags = []) {
		
		$arr_destination = ['directory' => static::DIR_STORAGE_MEDIA.($arr_media['directory'] ?: ''), 'overwrite' => ($arr_media['overwrite'] ?? false)];
		
		$file_store = new FileStore($file, $arr_destination);
		
		if ($arr_media['sizing_w'] || $arr_media['sizing_h']) {
			$file_store->imageResize($arr_media['sizing_w'], $arr_media['sizing_h']);
		}
		
		$arr_result = $file_store->getDetails();
		
		$str_label = ($arr_media['label'] ?: filename2Name($arr_result['name']));
		$str_directory = ($arr_result['directory'] != static::DIR_STORAGE_MEDIA ? str_replace(static::DIR_STORAGE_MEDIA, '', $arr_result['directory']) : '');
		
		$res = DB::query("INSERT INTO ".DB::getTable('TABLE_MEDIA')."
			(label, description, directory, filename, type, size)
				VALUES
			('".DBFunctions::strEscape($str_label)."', '".DBFunctions::strEscape($arr_media['description'])."', '".DBFunctions::strEscape($str_directory)."', '".DBFunctions::strEscape($arr_result['name'])."', '".DBFunctions::strEscape($arr_result['type'])."', ".(int)$arr_result['size'].")
			".DBFunctions::onConflict('directory, filename', ['label', 'description', 'type', 'size'])."
		");
		
		$media_id = DB::lastInsertID();
		
		if (!$media_id) {
			
			$res = DB::query("SELECT id
					FROM ".DB::getTable('TABLE_MEDIA')."
				WHERE directory = '".DBFunctions::strEscape($str_directory)."'
					AND filename = '".DBFunctions::strEscape($arr_result['name'])."'
			");
			
			$media_id = $res->fetchRow();
			$media_id = $media_id[0];
		}
		
		if (!$media_id) {
			error(getLabel('msg_error_database_missing_record'));
		}
		
		cms_general::handleTags(DB::getTable('TABLE_MEDIA_INTERNAL_TAGS'), 'media_id', $media_id, $arr_tags, true);
		
		return $media_id;
	}
	
	public static function updateMedia($media_id, $arr_media = [], $arr_tags = null) {
		
		if (is_array($media_id)) {
			
			$arr_media_ids = arrParseRecursive($media_id, TYPE_INTEGER);
			$str_sql_ids = implode(',', $arr_media_ids);
			
			$str_sql_label = 'label';
			if ($arr_media['label'] && count($arr_media_ids) > 1) {
				$str_sql_label = "CONCAT('".DBFunctions::strEscape($arr_media['label'])."', ' ', LPAD(".DBFunctions::fieldToPosition('id', $arr_media_ids).", ".strlen((string)count($arr_media_ids)).", '0'))";
			} else if ($arr_media['label']) {
				$str_sql_label = "'".DBFunctions::strEscape($arr_media['label'])."'";
			}
			
			$res = DB::query("
				UPDATE ".DB::getTable('TABLE_MEDIA')." SET
					label = ".$str_sql_label.",
					description = ".($arr_media['description'] ? "'".DBFunctions::strEscape($arr_media['description'])."'" : "description")."
				WHERE id IN (".$str_sql_ids.")
				ORDER BY ".DBFunctions::fieldToPosition('id', $arr_media_ids)."
			");
		} else {

			$res = DB::query("UPDATE
					".DB::getTable('TABLE_MEDIA')."
				SET label = '".DBFunctions::strEscape($arr_media['label'])."', description = '".DBFunctions::strEscape($arr_media['description'])."'
				WHERE id = ".(int)$media_id
			);
			
			if (!$res->getRowCount()) {
				error(getLabel('msg_error_database_missing_record'));
			}
		}
		
		if (isset($arr_tags)) {
			
			cms_general::handleTags(DB::getTable('TABLE_MEDIA_INTERNAL_TAGS'), 'media_id', $media_id, $arr_tags, true);
		}
	}
	
	public static function deleteMedia($media_id) {
		
		if (!$media_id) {
			return false;
		}
		
		$arr_media = self::getMedia((array)$media_id);
		
		if (!$arr_media) {
			return false;
		}
		
		foreach ($arr_media as $arr_media_item) {
			
			if (!FileStore::deleteFile(static::DIR_STORAGE_MEDIA.$arr_media_item['directory'].$arr_media_item['filename'])) {
				continue;
			}
			
			$res = DB::queryMulti("
				DELETE FROM ".DB::getTable('TABLE_MEDIA_INTERNAL_TAGS')."
					WHERE media_id = ".(int)$arr_media_item['id']."
				;
				DELETE FROM ".DB::getTable('TABLE_MEDIA')."
					WHERE id = ".(int)$arr_media_item['id']."
				;
			");
		}
	}
	
	public static function replaceMediaLocations($media_id, $str_url) {
		
		$arr_media = self::getMedia($media_id);
		$str_url_target = '/'.DIR_CMS.DIR_UPLOAD.$arr_media['directory'].$arr_media['filename'];
		
		$arr_query = [];
		
		foreach (self::getMediaDatabaseLocations() as $value) {
			
			$arr_query[] = "UPDATE ".$value[0]." SET
					".$value[1]." = REPLACE(".$value[1].", '".DBFunctions::strEscape($str_url_target)."', '".DBFunctions::strEscape($str_url)."')
				WHERE ".$value[1]." LIKE '%".DBFunctions::strEscape($str_url_target)."%'";
		}
		
		DB::queryMulti(implode(';', $arr_query));
	}
	
	private static function getFileTypes() {
	
		$arr = [];
		
		$res = DB::query("SELECT DISTINCT type FROM ".DB::getTable('TABLE_MEDIA')." AS media");
					
		while ($row = $res->fetchAssoc()) {
			
			$arr_tmp = explode('/', $row['type']);
			$arr[$arr_tmp[0]] = ['id' => $arr_tmp[0], 'name' => $arr_tmp[0]]; 
		}
			
		return $arr;
	}
	
	public static function getMedia($media_id = 0, $tag_id = false) {
	
		$arr = [];
		
		$res = DB::query("SELECT
			m.*
				FROM ".DB::getTable('TABLE_MEDIA')." m
				".($tag_id ? "LEFT JOIN ".DB::getTable('TABLE_MEDIA_INTERNAL_TAGS')." mt ON (mt.media_id = m.id)" : "")."
			WHERE TRUE
				".($media_id ? "AND m.id IN (".(is_array($media_id) ? implode(',', arrParseRecursive($media_id, TYPE_INTEGER)) : (int)$media_id).")" : "")."
				".($tag_id ? "AND mt.tag_id = ".(int)$tag_id."" : "")."
			ORDER BY label
		");
		
		while ($row = $res->fetchAssoc()) {
			$arr[$row['id']] = $row;
		}
			
		return (!$media_id || is_array($media_id) ? $arr : current($arr));
	}
	
	public static function getMediaTags() {
	
		$arr = [];
		
		$res = DB::query("SELECT t.*
				FROM ".DB::getTable('TABLE_MEDIA_INTERNAL_TAGS')." mt
				LEFT JOIN ".DB::getTable('TABLE_INTERNAL_TAGS')." t ON (t.id = mt.tag_id)
			GROUP BY t.id
		");
					
		while ($row = $res->fetchAssoc()) {
			$arr[] = $row;
		}
			
		return $arr;
	}
	
	public static function getMediaDatabaseLocations() {
		
		if (self::$arr_database_locations) {
			
			return self::$arr_database_locations;
		}
		
		$arr_module_media_locations = getModuleConfiguration('mediaLocations');
		
		foreach ($arr_module_media_locations as $module => $arr_media_locations) {
		
			foreach ($arr_media_locations as $database => $arr_columns) {
				
				$database = DB::getTable($database);
				
				foreach ($arr_columns as $column) {
								
					self::$arr_database_locations[] = [$database, $column];
				}
			}
		}
		
		return self::$arr_database_locations;
	}
	
	public static function searchMediaLocations($search) {
		
		$arr_query = [];
		
		foreach (self::getMediaDatabaseLocations() as $value) {
			
			$arr_query[] = "SELECT '".$value[0]."' AS table_name, '".$value[1]."' AS column_name FROM ".$value[0]." WHERE ".$value[1]." LIKE '%".DBFunctions::strEscape($search)."%'";
		}
		
		$arr = [];
		
		$res = DB::query(implode(" UNION ALL ", $arr_query));
		
		while ($row = $res->fetchAssoc()) {
			
			$num_count = (($arr[$row['table_name'].$row['column_name']]['count'] ?? 0) + 1);
			
			$arr[$row['table_name'].$row['column_name']] = ['table' => $row['table_name'], 'column' => $row['column_name'], 'count' => $num_count];
		}
		
		return $arr;
	}
}
