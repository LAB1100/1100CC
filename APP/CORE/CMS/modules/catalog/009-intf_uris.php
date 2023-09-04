<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class intf_uris extends uris {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_uris');
		static::$parent_label = '';
	}
	
	public function contents() {
		
		$arr_uri_translators = self::getURITranslators();
	
		$return .= '<div class="section"><h1 id="x:intf_uris:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup add_uri" value="add" /></h1>
			<div class="uris">';

				$return .= '<table class="display" id="d:intf_uris:uris_data-0">
					<thead> 
						<tr>
							<th><span></span></th>
							<th class="max" data-sort="desc-0"><span>'.getLabel('lbl_identifier').'</span></th>
							<th><span>'.getLabel('lbl_url').'</span></th>
							<th class="limit"><span>'.getLabel('lbl_remark').'</span></th>
							<th class="limit"><span>'.getLabel('lbl_service').'</span></th>
							'.(count($arr_uri_translators) > 1 ? '<th class="limit"><span>'.getLabel('lbl_uri_translator').'<span></th>' : '').'
							<th class="disable-sort"></th>
						</tr> 
					</thead>
					<tbody>
						<tr>
							<td colspan="'.(5 + (count($arr_uri_translators) > 1 ? 1 : 0)).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>';
						
			$return .= '</div>
		</div>';
		
		return $return;
	}

	public static function css() {
	
		$return = '';
		
		return $return;
	}
	
	public static function js() {

		$return = "
			SCRIPTER.dynamic('#frm-uri', function(elm_scripter) {
			
				elm_scripter.on('scripter', function() {
					
					SCRIPTER.triggerEvent(elm_scripter.find('input[name^=uri_translator_id]:checked'), 'change');
				}).on('change', 'input[name^=uri_translator_id]', function() {
					
					var elms_target = elm_scripter.find('[name^=in_out]');
					
					elms_target.closest('label').addClass('hide');
					
					for (let i = 0; i < elms_target.length; i++) {
						
						const elm_target = elms_target[i];
						
						if (this.dataset['mode_'+elm_target.value]) {
							elm_target.closest('label').classList.remove('hide');
						}
					}
				});
			});
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "edit_uri" || $method == "add_uri") {
			
			$arr_uri_translators = static::getURITranslators();
			$arr_modes = static::getModes();
			
			$arr_uri = [];
			$mode = 'insert_uri';
			
			if ($method == 'edit_uri' && $id) {
				
				$arr_id = explode('_', $id);
				$uri_translator_id = (int)$arr_id[0];
				$num_in_out = (int)$arr_id[1];
				$str_identifier = base64_decode($arr_id[2]);
				
				$arr_uri = static::getURI($uri_translator_id, $num_in_out, $str_identifier, true);
				$mode = 'update_uri';
			}
								
			$this->html = '<form id="frm-uri" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_uri_translator').'</label>
						<div>';
					
							if (count($arr_uri_translators) > 1 ) {
								
								foreach ($arr_uri_translators as &$arr_uri_translator) {
									
									$arr_uri_translator['attr']['data-mode_'.static::MODE_IN] = bitHasMode($arr_uri_translator['mode'], static::MODE_IN);
									$arr_uri_translator['attr']['data-mode_'.static::MODE_OUT] = bitHasMode($arr_uri_translator['mode'], static::MODE_OUT);
								}
								unset($arr_uri_translator);
						
								$this->html .= cms_general::createSelectorRadio($arr_uri_translators, 'uri_translator_id', $arr_uri['uri_translator_id']);
							} else {
									
								$arr_uri_translator = current($arr_uri_translators);
								$this->html .= '<input type="hidden" name="uri_translator_id" value="'.$arr_uri_translator['id'].'" />'.$arr_uri_translator['name'];
								
								if (!bitHasMode($arr_uri_translator['mode'], static::MODE_IN)) {
									unset($arr_modes[static::MODE_IN]);
								}
								if (!bitHasMode($arr_uri_translator['mode'], static::MODE_OUT)) {
									unset($arr_modes[static::MODE_OUT]);
								}
							}
				
						$this->html .= '<div>
					</li>
					<li>
						<label>'.getLabel('lbl_mode').'</label>
						<div>'.cms_general::createSelectorRadio($arr_modes, 'in_out', $arr_uri['in_out']).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_identifier').'</label>
						<div><input type="text" name="identifier" value="'.strEscapeHTML($arr_uri['identifier']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_url').'</label>
						<div><input type="text" name="url" value="'.strEscapeHTML($arr_uri['url']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_remark').'</label>
						<div><textarea name="remark">'.strEscapeHTML($arr_uri['remark']).'</textarea></div>
					</li>
				</ul></fieldset>
			</form>';
			
			$this->validate = ['uri_translator_id' => 'required', 'in_out' => 'required'];
		}
		
		// DATATABLE
					
		if ($method == "uris_data") {
			
			$arr_sql_columns = ['u.in_out', 'u.identifier', 'u.url', 'u.remark', 'u.service'];
			$arr_sql_columns_as = ['u.in_out', 'u.identifier', 'u.url', 'u.remark', 'u.service', 'u.uri_translator_id'];

			$arr_uri_translators = self::getURITranslators();
			
			if (count($arr_uri_translators) > 1 ) {
				
				$arr_sql_columns[] = 'ut.name';
				$arr_sql_columns_as[] = 'ut.name AS uri_translator_name';
			}

			$sql_table = DB::getTable('SITE_URIS')." AS u
				LEFT JOIN ".DB::getTable('SITE_URI_TRANSLATORS')." ut ON (ut.id = u.uri_translator_id)
			";

			$sql_index = 'u.uri_translator_id, u.in_out, u.identifier';
						
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, false, $arr_sql_columns_as, $sql_table, $sql_index);
			
			$has_uri_translators = (count($arr_uri_translators) > 1 ? true : false);
			$arr_modes = static::getModes();
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:intf_uris:uri_id-'.$arr_row['uri_translator_id'].'_'.$arr_row['in_out'].'_'.base64_encode($arr_row['identifier']);
				$arr_data[] = $arr_modes[$arr_row['in_out']]['name'];
				$arr_data[] = $arr_row['identifier'];
				$arr_data[] = '<a href="'.self::getURL($arr_row['url'], $arr_uri_translators[$arr_row['uri_translator_id']]['host_name']).'" target="_blank">'.$arr_row['url'].'</a>';
				$arr_data[] = $arr_row['remark'];
				$arr_data[] = ($arr_row['service'] ?: '<span class="icon" data-category="status">'.getIcon('min').'</span>');
				if ($has_uri_translators) {
					$arr_data[] = $arr_row['uri_translator_name'];
				}
				$arr_data[] = '<input type="button" class="data edit popup edit_uri" value="edit" /><input type="button" class="data del msg del_uri" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
				
		// QUERY
		
		if ($method == "insert_uri" || $method == "update_uri") {
			
			$arr_modes = uris::getModes();
			$num_in_out = (int)$_POST['in_out'];
			$str_url = $_POST['url'];
			
			if (!$arr_modes[$num_in_out]) {
				error(getLabel('msg_missing_information'));
			}

			if (!FileGet::getProtocolExternal($str_url)) {
				
				if (substr($str_url, 0, 1) != '/') {
					$str_url = '/'.$str_url;
				}
			}
			
			$str_identifier = $_POST['identifier'];
			
			if (substr($str_identifier, 0, 1) == ':') {
				
				$str_identifier = substr($str_identifier, 1);
				
				// Make sure the pattern is not erroneous
				try {
					preg_replace('<'.$str_identifier.'>', $str_url, 'TEST');
				} catch (Exception $e) {
					$str_identifier = preg_quote($str_identifier);
				}
				
				$str_identifier = ':'.$str_identifier;
			} else {
			
				$str_identifier = str2URL($str_identifier, '/');
			}
		}

		if ($method == "insert_uri") {

			$res = DB::query("INSERT INTO ".DB::getTable('SITE_URIS')."
				(uri_translator_id, in_out, identifier, url, remark, service)
					VALUES
				(
					".(int)$_POST['uri_translator_id'].",
					".(int)$num_in_out.",
					'".DBFunctions::strEscape($str_identifier)."',
					'".DBFunctions::strEscape($str_url)."',
					'".DBFunctions::strEscape($_POST['remark'])."',
					'".DBFunctions::strEscape($_POST['service'])."'
				)
			");
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "update_uri" || $method == "del_uri") {
			
			$arr_id = explode('_', $id);
			$uri_translator_id = (int)$arr_id[0];
			$num_cur_in_out = (int)$arr_id[1];
			$str_cur_identifier = base64_decode($arr_id[2]);
		}
		
		if ($method == "update_uri" && $id) {
						
			$res = DB::query("UPDATE ".DB::getTable('SITE_URIS')." SET
						uri_translator_id = ".(int)$_POST['uri_translator_id'].",
						in_out = ".(int)$num_in_out.",
						identifier = '".DBFunctions::strEscape($str_identifier)."',
						url = '".DBFunctions::strEscape($str_url)."',
						remark = '".DBFunctions::strEscape($_POST['remark'])."',
						service = '".DBFunctions::strEscape($_POST['service'])."'
				WHERE uri_translator_id = ".(int)$uri_translator_id."
					AND in_out = ".(int)$num_cur_in_out."
					AND identifier = '".DBFunctions::strEscape($str_cur_identifier)."'
			");
								
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del_uri" && $id) {
						
			$res = DB::query("DELETE FROM ".DB::getTable('SITE_URIS')."
				WHERE uri_translator_id = ".(int)$uri_translator_id."
					AND in_out = ".(int)$num_cur_in_out."
					AND identifier = '".DBFunctions::strEscape($str_cur_identifier)."'
			");
			
			$this->msg = true;
		}
	}
}
