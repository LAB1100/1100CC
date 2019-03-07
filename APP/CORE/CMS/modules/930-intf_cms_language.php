<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class intf_cms_language extends cms_language {

	public static function moduleProperties() {
		self::$label = getLabel('ttl_language');
		self::$parent_label = getLabel('ttl_settings');
	}
	
	public function contents() {
		
		$arr_hosts = cms_details::getSiteDetailsHosts();
	
		$return .= '<div class="section"><h1>'.self::$label.'</h1>
		<div class="cms_language">';
		
			$return .= '<div id="tabs-language">
			<ul>
				'.($arr_hosts ? '<li><a href="#tab-language-hosts">Hosts</a></li>' : '').'
				<li id="x:intf_cms_language:new-cms"><a href="#tab-language-cms">Site</a><input type="button" class="data add popup language_add" value="add" /></li>
				<li id="x:intf_cms_language:new-core"><a href="#tab-language-core">Shared</a>'.($_SESSION['CORE'] ? '<input type="button" class="data add popup language_add" value="add" />' : '').'</li>
			</ul>
			
			'.($arr_hosts ? '<div id="tab-language-hosts">
			
				'.self::contentTabHosts().'
				
			</div>' : '').'<div id="tab-language-cms">
			
				'.self::contentTabCms().'
				
			</div><div id="tab-language-core">
			
				'.self::contentTabCore().'
			
			</div>
				</div>';
				
		$return .= '</div></div>';
		
		return $return;
	}
	
	private static function contentTabHosts() {
		
		$arr_hosts = cms_details::getSiteDetailsHosts();
		$arr_language = self::getLanguage();
		$arr_language_hosts = self::getLanguageHosts();

		$return .= '<form id="f:intf_cms_language:host_language-0">
			<table class="list">
				<thead>
					<tr>
						<th><span>Host</span></th>
						<th><span>Language</span></th>
					</tr>
				</thead>
				<tbody>';
					foreach ($arr_hosts as $value) {
						
						$return .= '<tr>
							<td>'.$value['name'].'</td>	
							<td><select name="host_name['.rawurlencode($value['name']).']">'.cms_general::createDropdown($arr_language, $arr_language_hosts[$value['name']], true, 'label', 'lang_code').'</select></td>	
						</tr>';
					}
				$return .= '</tbody>
			</table>
		</form>';

		return $return;
	}
	
	private static function contentTabCms() {
					
		$res = DB::query("SELECT lang_code, label, user, is_default FROM ".DB::getTable('TABLE_CMS_LANGUAGE')." AS language ORDER BY lang_code");
		
		if ($res->getRowCount() == 0) {
			$return .= '<section class="info">'.getLabel('msg_no_language').'</section>';
		} else {
		
			$return .= '<table class="list">
				<thead>
					<tr>
						<th><span>Code</span></th>
						<th class="max"><span>Label</span></th>
						<th><span>User</span></th>
						<th><span>Default</span></th>
						<th></th>
					</tr>
				</thead>
				<tbody>';
				
					while ($row = $res->fetchAssoc()) {
								
						$return .= '<tr id="x:intf_cms_language:language_id-'.$row['lang_code'].'">
							<td>'.$row['lang_code'].'</td>	
							<td>'.$row['label'].'</td>	
							<td><span class="icon" data-category="status">'.getIcon(($row['user'] ? 'tick' : 'min')).'</span></td>
							<td><input type="radio" name="is_default_language" id="y:intf_cms_language:language_default-'.$row['lang_code'].'" value="'.$row['lang_code'].'"'.(($row['is_default'] == 1) ? ' checked="checked"':'').' /><label for="y:intf_cms_language:language_default-'.$row['lang_code'].'"></label></td>
							<td><input type="button" class="data edit popup language_cms_edit" value="edit" /><input type="button" class="data del msg language_cms_del" value="del" /></td>
						</tr>';
					}
					
				$return .= '</tbody>
			</table>';
		}
		
		return $return;
	}
	
	private static function contentTabCore() {
			
		$res = DB::query("SELECT lang_code, label
								FROM ".DB::getTable('TABLE_CORE_LANGUAGE')." AS language
							ORDER BY lang_code
		");
		
		if ($res->getRowCount() == 0) {
			$return .= '<section class="info">'.getLabel('msg_no_language').'</section>';
		} else {
		
			$return .= '<table class="list">
				<thead>
					<tr>
						<th><span>Code</span></th>
						<th class="max"><span>Label</span></th>
						'.($_SESSION['CORE'] ? '<th></th>' : '').'
					</tr>
				</thead>
				<tbody>';
					while($row = $res->fetchAssoc()) {	
							
						$return .= '<tr id="x:intf_cms_language:language_id-'.$row['lang_code'].'">
							<td>'.$row['lang_code'].'</td>	
							<td>'.$row['label'].'</td>	
							'.($_SESSION['CORE'] ? '<td><input type="button" class="data edit popup language_core_edit" value="edit" /><input type="button" class="data del msg language_core_del" value="del" /></td>' : '').'
						</tr>';
					}
				$return .= '</tbody>
			</table>';
		}
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.cms_language .list tr > :first-child { padding: 1px 4px 1px 4px; }
			.cms_language .list tr > :first-child img { vertical-align: middle; }
			
			#frm-language > fieldset { display: inline-block; vertical-align: top; }';
		
		return $return;
	}
	
	public static function js() {

		$return = "SCRIPTER.static('#mod-intf_cms_language', function(elm_scripter) {
		
			elm_scripter.on('change', '[id=f\\\:intf_cms_language\\\:host_language-0] [name^=host_name]', function() {
				COMMANDS.formCommand(this);		
			}).on('change', '.cms_language input[name=is_default_language]', function() {
				COMMANDS.quickCommand(this);
			});
		})";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// INTERACT
		
		if ($method == "language_default" && $id) {
		
			$res = DB::query("UPDATE ".DB::getTable('TABLE_CMS_LANGUAGE')." SET is_default = 0");
			$res = DB::query("UPDATE ".DB::getTable('TABLE_CMS_LANGUAGE')." SET is_default = 1 WHERE lang_code='".DBFunctions::strEscape($id)."'");
			
			$this->msg = true;
		}
		
		if ($method == "host_language") {
			
			$res = DB::query("DELETE FROM ".DB::getTable('TABLE_CMS_LANGUAGE_HOSTS'));
		
			foreach ($_POST['host_name'] as $host_name => $value) {
				
				$host_name = rawurldecode($host_name);
				
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_CMS_LANGUAGE_HOSTS')." 
									(host_name, lang_code)
										VALUES
									('".DBFunctions::strEscape($host_name)."',
									'".DBFunctions::strEscape($value)."')
				");
			}
			
			$this->msg = true;
		}
	
		// POPUP
		
		if ($method == "language_cms_edit" || $method == "language_core_edit" || $method == "language_add") {
		
			if (($method == "language_cms_edit" || $method == "language_core_edit") && $id) {
			
				$table = ($method == 'language_cms_edit' ? 'TABLE_CMS_LANGUAGE' : 'TABLE_CORE_LANGUAGE'); 
			
				$res = DB::query("SELECT * FROM ".DB::getTable($table)." WHERE lang_code = '".DBFunctions::strEscape($id)."'");
				$row = $res->fetchAssoc();
				
				$mode = ($method == 'language_cms_edit' ? 'language_cms_update' : 'language_core_update');
			} else if ($method == 'language_add' && $id) {
				
				$mode = ($id == 'cms' ? 'language_cms_insert' : 'language_core_insert');
			}
								
			$this->html = '<form id="frm-language" data-method="'.$mode.'">
				<fieldset><ul>';
					if ($mode == 'language_cms_insert') {
						
							$this->html .= '<li>
								<label>Copy</label>
								<div><select name="copy">'.cms_general::createDropdown(self::getLanguage('core'), false, true, 'label', 'lang_code').'</select></div>
							</li>
						</ul>
						<hr />
						<ul>';
					}
					$this->html .= '<li>
						<label>Code</label>
						<div><input type="text" name="lang_code" value="'.htmlspecialchars($row['lang_code']).'" /></div>
					</li>
					<li>
						<label>Label</label>
						<div><input type="text" name="label" value="'.htmlspecialchars($row['label']).'" /></div>
					</li>';
					if ($mode == 'language_cms_insert' || $mode == 'language_cms_update') {
						
						$this->html .= '</ul>
						<hr />
						<ul>
						<li>
							<label>User Selectable</label>
							<div><input type="checkbox" name="user" value="1"'.($row['user'] ? ' checked="checked"' : '').'" /></div>
						</li>';
					}
				$this->html .= '</ul></fieldset>
			</form>';
			
			$this->validate = json_encode(
				[
					'lang_code' => ['required' => "function() {
						return (!$('[name=\"copy\"]').val());
					}"],
					'label' => ['required' => "function() {
						return (!$('[name=\"copy\"]').val());
					}"]
				]
			);
		}
				
		// QUERY
		
		if ($method == "language_cms_insert" || $method == "language_core_insert") {
		
			if ($method == "language_core_insert" && !$_SESSION['CORE']) {
				error(getLabel("msg_not_allowed"));
			}
			
			if ($method == "language_cms_insert" && $_POST['copy']) {
				$arr_copy = self::getLanguage("core", $_POST['copy']);
				$_POST = $arr_copy;
			}
				
			$res = DB::query("INSERT INTO ".DB::getTable(($method == 'language_cms_insert' ? 'TABLE_CMS_LANGUAGE' : 'TABLE_CORE_LANGUAGE'))."
				(
					lang_code,
					label
					".($method == 'language_cms_insert' ? ", user" : "")."
				)
					VALUES
				(
					'".DBFunctions::strEscape($_POST['lang_code'])."',
					'".DBFunctions::strEscape($_POST['label'])."'
					".($method == 'language_cms_insert' ? ", ".(int)$_POST['user'] : "")."
				)
			");
			
			$this->html = ($method == 'language_cms_insert' ? self::contentTabCms() : self::contentTabCore());
			$this->msg = true;
		}
		
		if (($method == "language_cms_update" || $method == "language_core_update") && $id){
		
			if ($method == "language_core_update" && !$_SESSION['CORE']) {
				error(getLabel('msg_not_allowed'));
			}
				
			$res = DB::query("UPDATE ".DB::getTable(($method == 'language_cms_update' ? 'TABLE_CMS_LANGUAGE' : 'TABLE_CORE_LANGUAGE'))." SET
					lang_code = '".DBFunctions::strEscape($_POST['lang_code'])."',
					label = '".DBFunctions::strEscape($_POST['label'])."'
					".($method == 'language_cms_update' ? ", user = ".(int)$_POST['user'] : "")."
				WHERE lang_code = '".DBFunctions::strEscape($id)."'
			");
			
			$this->html = ($method == 'language_cms_update' ? self::contentTabCms() : self::contentTabCore());
			$this->msg = true;
		}
		
		if (($method == "language_cms_del" || $method == "language_core_del") && $id) {
		
			if ($method == 'language_core_del' && !$_SESSION['CORE']) {
				error(getLabel('msg_not_allowed'));
			}
				
			$res = DB::query("DELETE FROM ".DB::getTable(($method == 'language_cms_del' ? 'TABLE_CMS_LANGUAGE' : 'TABLE_CORE_LANGUAGE'))."
				WHERE lang_code = '".DBFunctions::strEscape($id)."' LIMIT 1
			");
			
			$this->msg = true;
		}
	}
}
