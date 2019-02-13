<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class intf_uri_translators extends uris {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_uri_translators');
		static::$parent_label = '';
	}

	public function contents() {
		
		$arr_hosts = cms_details::getSiteDetailsHosts();
	
		$return .= '<div class="section"><h1>'.self::$label.'</h1>
		<div class="uri_translators">';
		
			$return .= '<div class="tabs">
				<ul>
					<li><a href="#">Hosts</a></li>
					<li><a href="#">URI Translators</a><span><input type="button" id="y:intf_uri_translators:add_uri_translator-0" class="data add popup" value="add" /></span></li>
				</ul>
				
				<div>
				
					'.$this->contentTabHosts().'
					
				</div><div>
				
					'.$this->contentTabURITranslators().'
					
				</div>
			</div>';
				
		$return .= '</div></div>';
		
		return $return;
	}
	
	private function contentTabHosts() {
		
		$arr_hosts = cms_details::getSiteDetailsHosts();
		
		if (!$arr_hosts) {
			
			$msg = getLabel('msg_no', 'L', true);
			
			Labels::setVariable('name', getLabel('lbl_server_hosts'));
			
			$return .= '<section class="info">'.Labels::printLabels(Labels::parseTextVariables($msg)).'</section>';
		} else {
			
			$arr_uri_translators = self::getURITranslators();
			$arr_uri_translators_hosts = self::getURITranslatorHosts();

			$return .= '<form id="f:intf_uri_translators:host_uri_translator-0">
				<table class="list">
					<thead>
						<tr>
							<th><span>Host</span></th>
							<th><span>URI Translator</span></th>
						</tr>
					</thead>
					<tbody>';
						foreach ($arr_hosts as $arr_host) {
							
							$return .= '<tr>
								<td>'.$arr_host['name'].'</td>
								<td><select name="host_name['.rawurlencode($arr_host['name']).']">'.cms_general::createDropdown($arr_uri_translators, $arr_uri_translators_hosts[$arr_host['name']], true).'</select></td>	
							</tr>';
						}
					$return .= '</tbody>
				</table>
			</form>';
		}

		return $return;
	}
	
	private function contentTabURITranslators() {
					
		$arr_uri_translators = self::getURITranslators();
		
		if (!$arr_uri_translators) {
			
			$msg = getLabel('msg_no', 'L', true);
			
			Labels::setVariable('name', getLabel('lbl_uri_translators'));
			
			$return .= '<section class="info">'.Labels::printLabels(Labels::parseTextVariables($msg)).'</section>';
		} else {
		
			$return .= '<table class="list">
				<thead>
					<tr>
						<th class="max"><span>'.getLabel('lbl_name').'</span></th>
						<th><span>'.getLabel('lbl_host_name').'</span></th>
						<th class="limit"><span>'.getLabel('lbl_delay').'</span></th>
						<th class="limit"><span>'.getLabel('lbl_remarks').'</span></th>
						<th></th>
					</tr>
				</thead>
				<tbody>';
				
					foreach ($arr_uri_translators as $uri_translator_id => $arr_uri_translator) {
													
						$return .= '<tr id="x:intf_uri_translators:uri_translator_id-'.$uri_translator_id.'">
							<td>'.$arr_uri_translator['name'].'</td>
							<td>'.($arr_uri_translator['host_name'] ? $arr_uri_translator['host_name'] : SERVER_NAME_SITE_NAME).'</td>
							<td>'.($arr_uri_translator['delay'] ? ((int)$arr_uri_translator['delay']/1000).' '.getLabel('unit_seconds') : '<span class="icon" data-category="status">'.getIcon('min').'</span>').'</td>
							<td><span class="icon" data-category="status">'.getIcon(($arr_uri_translator['show_remark'] ? 'tick' : 'min')).'</span></td>
							<td><input type="button" class="data edit popup edit_uri_translator" value="edit" /><input type="button" class="data del msg del_uri_translator" value="del" /></td>
						</tr>';
					}
					
				$return .= '</tbody>
			</table>';
		}
		
		return $return;
	}
	
	public static function css() {
	
		$return = '';
		
		return $return;
	}
	
	public static function js() {

		$return = "SCRIPTER.static('#mod-intf_uri_translators', function(elm_scripter) {
		
			elm_scripter.on('change', '[id=f\\\:intf_uri_translators\\\:host_uri_translator-0] [name^=host_name]', function() {
				$(this).formCommand();		
			});
		});
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// INTERACT
		
		if ($method == "host_uri_translator") {
			
			$res = DB::query("DELETE FROM ".DB::getTable('SITE_URI_TRANSLATOR_HOSTS'));
		
			foreach ($_POST['host_name'] as $host_name => $value) {
				
				if (!$value) {
					continue;
				}
				
				$host_name = rawurldecode($host_name);
				
				$res = DB::query("INSERT INTO ".DB::getTable('SITE_URI_TRANSLATOR_HOSTS')." 
					(host_name, uri_translator_id)
						VALUES
					(
						'".DBFunctions::strEscape($host_name)."',
						".(int)$value."
					)
				");
			}
			
			$this->msg = true;
		}
	
		// POPUP
		
		if ($method == "edit_uri_translator" || $method == "add_uri_translator") {
			
			$arr_uri_translator = [];
			$mode = 'insert_uri_translator';
		
			if ($method == 'edit_uri_translator' && $id) {
				
				$arr_uri_translator = self::getURITranslators($id);
				$mode = 'update_uri_translator';
			}
								
			$this->html = '<form id="frm-uri_translator" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.htmlspecialchars($arr_uri_translator['name']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_host_name').'</label>
						<div><input type="text" name="host_name" value="'.htmlspecialchars($arr_uri_translator['host_name']).'" placeholder="'.SERVER_NAME_SITE_NAME.'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_delay').'</label>
						<div><input type="range" min="0" max="10" step="0.1" /><input type="number" name="delay" value="'.((int)$arr_uri_translator['delay']/1000).'" /><label>'.getLabel('unit_seconds').'</label></div>
					</li>
					<li>
						<label>'.getLabel('lbl_remarks').'</label>
						<div><input type="checkbox" name="show_remark" value="1"'.($arr_uri_translator['show_remark'] ? ' checked="checked"' : '').'" /></div>
					</li>
				</ul></fieldset>
			</form>';
			
			$this->validate = ['name' => 'required'];
		}
		
		// POPUP INTERACTION
				
		// QUERY
		
		if ($method == "insert_uri_translator") {
		
			$res = DB::query("INSERT INTO ".DB::getTable('SITE_URI_TRANSLATORS')."
				(name, host_name, delay, show_remark)
					VALUES
				(
					'".DBFunctions::strEscape($_POST['name'])."',
					".($_POST['host_name'] ? "'".DBFunctions::strEscape($_POST['host_name'])."'" : "NULL").",
					".((float)$_POST['delay']*1000).",
					".(int)$_POST['show_remark']."
				)
			");
			
			$this->html = $this->contentTabURITranslators();
			$this->msg = true;
		}
		
		if ($method == "update_uri_translator" && $id){
						
			$res = DB::query("UPDATE ".DB::getTable('SITE_URI_TRANSLATORS')." SET
					name = '".DBFunctions::strEscape($_POST['name'])."',
					host_name = ".($_POST['host_name'] ? "'".DBFunctions::strEscape($_POST['host_name'])."'" : "NULL").",
					delay = ".((float)$_POST['delay']*1000).",
					show_remark = ".(int)$_POST['show_remark']."
				WHERE id = ".(int)$id."
			");
								
			$this->html = $this->contentTabURITranslators();
			$this->msg = true;
		}
		
		if ($method == "del_uri_translator" && $id) {
						
			$res = DB::query("DELETE FROM ".DB::getTable('SITE_URI_TRANSLATORS')."
				WHERE id = ".(int)$id."
			");
			
			$this->msg = true;
		}
	}
}
