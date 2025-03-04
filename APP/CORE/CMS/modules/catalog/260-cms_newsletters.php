<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_NEWSLETTERS', DB::$database_home.'.def_newsletters');
DB::setTable('TABLE_NEWSLETTERS_TEMPLATES', DB::$database_home.'.def_newsletters_templates');
DB::setTable('TABLE_EMAIL_ADDRESSES', DB::$database_home.'.data_email_addresses');
DB::setTable('TABLE_EMAIL_ADDRESSES_BOUNCES', DB::$database_home.'.data_email_addresses_bounces');
DB::setTable('TABLE_EMAIL_ADDRESSES_OPT_OUT', DB::$database_home.'.data_email_addresses_opt_out');

class cms_newsletters extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_newsletters');
		static::$parent_label = getLabel('ttl_content');
	}
	
	public static function jobProperties() {
		return [
			'checkReturnedEmail' => ['label' => getLabel('lbl_check_returned_email')]
		];
	}
	
	public function contents() {

		$return = '<div class="section"><h1>'.self::$label.'</h1>
		<div class="newsletters">';
		
		$return .= '<div id="tabs-newsletters">
				<ul>
					<li id="x:cms_newsletters:add-0"><a href="#tab-newsletters">'.self::$label.'</a><input type="button" class="data add popup add" value="add" /></li>
					<li><a href="#tab-newsletters-email-manager">'.getLabel('lbl_email_manager').'</a></li>
					<li id="x:cms_newsletters:add-0"><a href="#tab-newsletters-templates">'.getLabel('lbl_templates').'</a><input type="button" class="data add popup add_template" value="add" /></li>
				</ul>
				<div id="tab-newsletters">

					<table class="display" id="d:cms_newsletters:data-0">
						<thead> 
							<tr>			
								<th><span title="'.getLabel('lbl_enabled').'">E</span></th>
								<th class="max">'.getLabel('lbl_title').'</th>
								<th data-sort="desc-0">'.getLabel('lbl_date').'</th>
								<th>'.getLabel('lbl_recipients').'</th>
								<th>'.getLabel('lbl_bounces').'</th>
								<th>'.getLabel('lbl_opt_out').'</th>
								<th class="disable-sort"></th>
							</tr> 
						</thead>
						<tbody>
							<tr>
								<td colspan="7" class="empty">'.getLabel('msg_loading_server_data').'</td>
							</tr>
						</tbody>
					</table>
				
				</div><div id="tab-newsletters-email-manager">
					
					<form id="frm-email_management">
						<table>
							<tr>
								<td>'.getLabel('lbl_total').'</td>
								<td>'.self::createDatabaseEmailAddressCountInfo().'<input id="y:cms_newsletters:view_email_addresses-0" type="button" class="data view popup" value="" /></td>
							</tr>
							<th colspan="2" class="split"><hr /></th>
							<tr>
								<td>'.getLabel('lbl_import').'</td>
								<td id="x:cms_newsletters:email-0"><textarea name="import"></textarea><input type="button" class="data add email_bulk_add" value="add" /><input type="button" class="data del email_bulk_del" value="del" />
								<p class="info">'.getLabel('inf_email_import_guideline').'</p></td>
							</tr>
							<th colspan="2" class="split"><hr /></th>
							<tr>
								<td>'.getLabel('lbl_export').'</td>
								<td><input type="button" id="y:cms_newsletters:email_export-0" value="'.getLabel('lbl_export').'" /></td>
							</tr>
							<tr>
								<td>'.getLabel('lbl_clear').'</td>
								<td><input type="button" id="y:cms_newsletters:email_empty-0" value="'.getLabel('lbl_clear').'" /></td>
							</tr>
						</table>
					</form>
					
				</div><div id="tab-newsletters-templates">
				
					'.self::contentTabTemplates().'
					
				</div>						
			</div></div>';
		
		return $return;
	}
	
	private static function contentTabTemplates() {
		
		$arr_templates = self::getTemplates();
					
		if (!$arr_templates) {
			
			$return .= '<section class="info">'.getLabel('msg_no_templates').'</section>';
		} else {
		
			$return .= '<table class="list">
				<thead>
					<tr>
						<th>'.getLabel('lbl_name').'</th>
					</tr>
				</thead>
				<tbody>';
					foreach ($arr_templates as $arr_row) {
						
						$return .= '<tr id="x:cms_newsletters:template_id-'.$arr_row['id'].'">
							<td class="max">'.$arr_row['name'].'</td>
							<td><input type="button" class="data edit popup edit_template" value="edit" /><input type="button" class="data del msg del_template" value="del" /></td>
						</tr>';
					}
				$return .= '</tbody>
			</table>';
		}
		
		return $return;
	}
		
	public static function css() {
	
		$return = '#tab-newsletters-email-manager .info { width: 300px; margin-bottom: 0px; }
					#tab-newsletters-email-manager textarea[name=import] { display: block; height: 200px; }
					#tab-newsletters-email-manager textarea[name=import] + input { margin: 6px 0px; }
					#frm-newsletter input[name=title] { width: 250px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-cms_newsletters', function(elm_scripter) {
			
			var elm_email_management = elm_scripter.find('#frm-email_management');
			
			elm_email_management.on('click', '.email_bulk_add, .email_bulk_del', function() {
				COMMANDS.setData($(this).closest('[id^=x\\\:cms_newsletters\\\:email-0]'), {addresses: elm_email_management.find('textarea[name=import]').val()});					
				$(this).quickCommand($('.email-count'), {html: 'replace'});
			}).on('click', '[id^=y\\\:cms_newsletters\\\:email_empty]', function() {
				COMMANDS.setMessage(this, 'conf_truncate');
				COMMANDS.setTarget(this, elm_email_management.find('.email-count'));
				$(this).messageCommand({html: 'replace', remove: false});
			}).on('click', '[id^=y\\\:cms_newsletters\\\:email_export]', function() {
				$(this).quickCommand();
			});
		});

		SCRIPTER.dynamic('#frm-newsletter', function(elm_scripter) {
		
			elm_scripter.on('change', '[id^=y\\\:cms_newsletters\\\:get_template]', function() {
				$(this).quickCommand(elm_scripter.find('textarea[name=body]'));
			}).on('click', 'input[name=email_me]', function() {
				elm_scripter.find('input[name^=recipients]').prop('disabled', function(i, val) {
					return !val;
				});
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
						
		if ($method == "edit" || $method == "add") {

			if ((int)$id) {
				
				$row = self::getNewsletters($id);
												
				$mode = "update";
			} else {
									
				$mode = "insert";
			}

			$arr_recipients = array_merge([['id' => 'database', 'name' => getLabel('ttl_email_manager')]], user_groups::getUserGroups());
						
			$this->html = '<form id="frm-newsletter" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label></label>
						<div>'.cms_general::createSelectorRadio([['id' => '0', 'name' => getLabel('lbl_publish')], ['id' => '1', 'name' => getLabel('lbl_draft')]], 'draft', ($mode == 'insert' || $row['draft'])).'</div>
					</li>
				</ul>
				<hr />
				<ul>
					<li>
						<label>'.getLabel('lbl_recipients').'</label>
						<div><label><input type="checkbox" name="email_me" value="1" /><span>'.getLabel('lbl_email_me').'</span></label>'.cms_general::createSelector($arr_recipients, 'recipients').'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_title').'</label>
						<div><input type="text" name="title" value="'.strEscapeHTML($row['title']).'"></div>
					</li>
					<li>
						<label>'.getLabel('lbl_template').'</label>
						<div><select id="y:cms_newsletters:get_template-0">'.cms_general::createDropdown(self::getTemplates(), 0, true).'</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_body').'</label>
						<div>'.cms_general::editBody($row['body'], 'body', ['external' => true]).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_date').'</label>
						<div>'.cms_general::createDefineDate($row['date']).'</div>
					</li>
				</ul></fieldset>
			</form>';
			
			$this->validate = ['title' => 'required', 'body' => 'required'];
		}
		
		if ($method == "add_template" || $method == "edit_template") {
		
			if ((int)$id) {
				
				$row = self::getTemplates($id);
												
				$mode = "update_template";
			} else {
									
				$mode = "insert_template";
			}
				
			$this->html = '<form id="frm-newsletters-templates" class="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($row['name']).'"></div>
					</li>
					<li>
						<label>'.getLabel('lbl_body').'</label>
						<div>'.cms_general::editBody($row['body'], 'body', true).'</div>
					</li>
				</ul></fieldset>
			</form>';
				
			$this->validate = ['name' => 'required', 'body' => 'required'];
		}
		
		if ($method == "view_email_addresses") {
		
			$this->html = '<table class="display" id="d:cms_newsletters:data_email_addresses-0">
				<thead> 
					<tr>
						<th>'.getLabel('lbl_email').'</th>
						<th>'.getLabel('lbl_name').'</th>
						<th>'.getLabel('lbl_bounced').'</th>
						<th>'.getLabel('lbl_opt_out').'</th>
						<th class="disable-sort"></th>
					</tr> 
				</thead>
				<tbody>
					<tr>
						<td colspan="4" class="empty">'.getLabel('msg_loading_server_data').'</td>
					</tr>
				</tbody>
			</table>';
		}
		
		// POPUP INTERACT
				
		if (($method == "email_bulk_add" || $method == "email_bulk_del") && $value['addresses']) {			
			
			$arr_email = preg_split("/\r?\n/", $value['addresses']);
			
			$arr_insert = [];
			foreach ($arr_email as $value) {
				preg_match('/(.*)\<(.*)\>/', $value, $matches);
				
				if (!$matches) {
					$name = '';
					$email = trim($value);
				} else {				
					$name = trim($matches[1]);
					$email = trim($matches[2]);
				}
				
				if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$arr_insert[$email] = "('".DBFunctions::strEscape($email)."', '".DBFunctions::strEscape($name)."')";
				}
			}
			
			$arr_email = array_keys($arr_insert);
			
			if (!$arr_email) {
				return;
			}

			if ($method == "email_bulk_add") {
				
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_EMAIL_ADDRESSES')."
					(email, name)
						VALUES
					".implode(', ', $arr_insert)." 
					".DBFunctions::onConflict('email, name', ['name'])."
				");
			} else {
				
				$res = DB::query("DELETE FROM ".DB::getTable('TABLE_EMAIL_ADDRESSES')."
					WHERE email IN ('".implode("','", $arr_email)."')
				");
			}
			
			$count = self::getDatabaseEmailAddressCount();
			$this->html = self::createDatabaseEmailAddressCountInfo();
			$this->reset_form = true;
			$this->msg = true;
		}
		
		if ($method == "email_export") {
		
			if ($this->is_download) {
			
				$arr_email = self::getDatabaseEmailAddressesRaw();
				
				Response::sendFileHeaders(false, 'email_addresses.csv');
				
				$resource = fopen('php://output', 'w');
					
				fputcsv($resource, ['email', 'name', 'bounced', 'opt_out'], ',', '"', CSV_ESCAPE);				
			
				foreach ($arr_email as $value) {
					fputcsv($resource, [$value['email'], $value['name'], $value['bounced'], $value['opt_out']], ',', '"', CSV_ESCAPE);
				}

				die;
			} else {
				
				$this->do_download = true;
			}
		}
		
		if ($method == "email_empty") {

			DB::query("DELETE FROM ".DB::getTable('TABLE_EMAIL_ADDRESSES')."");
			
			$this->html = self::createDatabaseEmailAddressCountInfo();
			$this->msg = true;
		}
		
		if ($method == "get_template" && (int)$value) {

			$arr_template = self::getTemplates($value);
			
			$this->html = $arr_template['body'];
		}
		
		if ($method == "edit_email_address") {
		
			$arr = self::getDatabaseEmailAddressesRaw($id);
		
			$this->html = '<form id="frm-email-address" class="update_email_address">
				<table>
					<tr>
						<td>'.getLabel('lbl_email').'</td>
						<td><input type="text" name="email" value="'.strEscapeHTML($arr['email']).'"></td>
					</tr>
					<tr>
						<td>'.getLabel('lbl_name').'</td>
						<td><input type="text" name="name" value="'.strEscapeHTML($arr['name']).'"></td>
					</tr>
					<tr>
						<td>'.getLabel('lbl_opt_out').'</td>
						<td><input name="opt_out" type="checkbox"'.($arr['opt_out'] ? ' checked="checked"' : '').' /></td>
					</tr>
					<tr>
						<td>'.getLabel('lbl_bounced').'</td>
						<td><input name="bounced" type="checkbox"'.($arr['bounced'] ? ' checked="checked"' : '').' /></td>
					</tr>
				</table>
				</form>';
				
			$this->validate = ['email' => 'required'];
		}
		
		// DATATABLE
					
		if ($method == "data") {
			
			$arr_sql_columns = ['n.draft', 'n.title', 'n.recipients', 'n.bounces', 'n.opt_out', 'n.date'];
			$arr_sql_columns_search = ['', 'n.title', '', '', '', DBFunctions::castAs('n.date', DBFunctions::CAST_TYPE_STRING)];
			$arr_sql_columns_as = ['n.draft', 'n.title', 'n.recipients', 'n.bounces', 'n.opt_out', 'n.date', 'n.id'];
						
			$sql_table = DB::getTable('TABLE_NEWSLETTERS').' n';

			$sql_index = 'n.id';
							 
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index);

			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:cms_newsletters:id-'.$arr_row['id'].'';
				
				$arr_data[] = '<span class="icon">'.getIcon((!DBFunctions::unescapeAs($arr_row['draft'], DBFunctions::TYPE_BOOLEAN) ? 'tick' : 'min')).'</span>';
				$arr_data[] = $arr_row['title'];
				$arr_data[] = date('d-m-Y', strtotime($arr_row['date']));
				$arr_data[] = $arr_row['recipients'];
				$arr_data[] = $arr_row['bounces'];
				$arr_data[] = $arr_row['opt_out'];
				$arr_data[] = '<input type="button" class="data edit popup edit" value="edit" /><input type="button" class="data del msg del" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
		
		if ($method == "data_email_addresses") {
			
			$arr_sql_columns = ['e.email', 'e.name', 'eb.failed', 'eo.email'];
			$arr_sql_columns_search = ['e.email', 'e.name', '', 'eo.email'];
			$arr_sql_columns_as = ['e.email', 'e.name', 'CASE WHEN eb.email IS NOT NULL THEN TRUE ELSE FALSE END AS bounced', 'CASE WHEN eo.email IS NOT NULL THEN TRUE ELSE FALSE END AS opt_out'];
						
			$sql_table = DB::getTable('TABLE_EMAIL_ADDRESSES').' e';

			$sql_index = 'e.email';
			$sql_index_body = 'e.email, eb.email, eo.email';
			
			$sql_table .= "
				LEFT JOIN ".DB::getTable('TABLE_EMAIL_ADDRESSES_BOUNCES')." eb ON (eb.email = e.email
					AND (eb.failed = TRUE
					OR eb.count >= 3)
				)
				LEFT JOIN ".DB::getTable('TABLE_EMAIL_ADDRESSES_OPT_OUT')." eo ON (eo.email = e.email)
			";

			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', $sql_index_body);

			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:cms_newsletters:email_address-'.$arr_row['email'];
				
				$arr_data[] = $arr_row['email'];
				$arr_data[] = $arr_row['name'];
				$arr_data[] = (DBFunctions::unescapeAs($arr_row['bounced'], DBFunctions::TYPE_BOOLEAN) ? '<span class="icon">'.getIcon('tick').'</span>' : '');
				$arr_data[] = (DBFunctions::unescapeAs($arr_row['opt_out'], DBFunctions::TYPE_BOOLEAN) ? '<span class="icon">'.getIcon('tick').'</span>' : '');
				$arr_data[] = '<input type="button" class="data edit popup edit_email_address" value="edit" /><input type="button" class="data del msg del_email_address" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
							
		// QUERY
			
		if (($method == 'insert' || $method == 'update') && $this->is_confirm !== false) {
								
			if ($_POST['email_me']) {
				
				$arr_email = [$_SESSION['CUR_USER']['email']];
				$recipients = 0;
			} else {
				
				$arr_email = self::getEmailAddresses($_POST['recipients']);
				$recipients = count($arr_email);
			}
			
			if ($arr_email && !$_POST['email_me'] && !$this->is_confirm) {
				
				Labels::setVariable('count', count($arr_email));
				$this->html = getLabel('conf_newsletter_send');
				$this->do_confirm = true;
				return;
			}

			if ($method == "update") {
				
				$res = DB::query("UPDATE ".DB::getTable('TABLE_NEWSLETTERS')." SET
						title = '".DBFunctions::strEscape($_POST['title'])."',
						body = '".DBFunctions::strEscape($_POST['body'])."',
						date = '".date('Y-m-d H:i:s', strtotime($_POST['date'].' '.$_POST['date_t']))."',
						draft = ".(int)$_POST['draft'].",
						recipients = recipients+".$recipients."
					WHERE id = ".(int)$id."
				");
			} else {
				
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_NEWSLETTERS')."
						(title, body, date, draft, recipients)
							VALUES
						('".DBFunctions::strEscape($_POST['title'])."', '".DBFunctions::strEscape($_POST['body'])."', '".date('Y-m-d H:i:s', strtotime($_POST['date'].' '.$_POST['date_t']))."', ".(int)$_POST['draft'].", ".$recipients.")
				");
				
				$id = DB::lastInsertID();
			}
			
			self::sendNewsletter($arr_email, $id, $_POST['title'], $_POST['body']);
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del" && (int)$id) {
		
			$res = DB::query("DELETE n FROM ".DB::getTable('TABLE_NEWSLETTERS')." n
									WHERE n.id='".$id."'");
			$this->msg = true;
		}
		
		if ($method == "insert_template") {
						
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_NEWSLETTERS_TEMPLATES')." (name, body) VALUES ('".DBFunctions::strEscape($_POST['name'])."', '".DBFunctions::strEscape($_POST['body'])."')");
												
			$this->html = self::contentTabTemplates();
			$this->msg = true;
		}
		
		if ($method == "update_template" && (int)$id) {
											
			$res = DB::query("UPDATE ".DB::getTable('TABLE_NEWSLETTERS_TEMPLATES')." SET name = '".DBFunctions::strEscape($_POST['name'])."', body = '".DBFunctions::strEscape($_POST['body'])."' WHERE id = ".$id."");
				
			$this->html = self::contentTabTemplates();
			$this->msg = true;
		}

		if ($method == "del_template" && (int)$id) {
		
			$res = DB::query("DELETE nt FROM ".DB::getTable('TABLE_NEWSLETTERS_TEMPLATES')." nt
									WHERE nt.id='".$id."'");
			$this->msg = true;
		}
		
		if ($method == "update_email_address" && $id) {
			
			if ($_POST['email'] != $id) {
				
				$res = DB::query("SELECT email FROM ".DB::getTable('TABLE_EMAIL_ADDRESSES')." WHERE email = '".DBFunctions::strEscape($_POST['email'])."'");
				
				if ($res->getRowCount()) {
					error(getLabel("msg_item_already_exists"));
				}

				$res = DB::query("UPDATE ".DB::getTable('TABLE_EMAIL_ADDRESSES_OPT_OUT')." SET email = '".DBFunctions::strEscape($_POST['email'])."' WHERE email = '".DBFunctions::strEscape($id)."'");
				$res = DB::query("UPDATE ".DB::getTable('TABLE_EMAIL_ADDRESSES_BOUNCES')." SET email = '".DBFunctions::strEscape($_POST['email'])."' WHERE email = '".DBFunctions::strEscape($id)."'");
				$res = DB::query("DELETE FROM ".DB::getTable('TABLE_EMAIL_ADDRESSES')." WHERE email = '".DBFunctions::strEscape($id)."'");
			}
			
			$res = DB::query("REPLACE INTO ".DB::getTable('TABLE_EMAIL_ADDRESSES')." (email, name) VALUES ('".DBFunctions::strEscape($_POST['email'])."', '".DBFunctions::strEscape($_POST['name'])."')");
			
			if ($_POST["opt_out"]) {
				$res = DB::query("REPLACE INTO ".DB::getTable('TABLE_EMAIL_ADDRESSES_OPT_OUT')." (email) VALUES ('".DBFunctions::strEscape($_POST['email'])."')");
			} else {
				$res = DB::query("DELETE FROM ".DB::getTable('TABLE_EMAIL_ADDRESSES_OPT_OUT')." WHERE email = '".DBFunctions::strEscape($_POST['email'])."'");
			}
			if ($_POST["bounced"]) {
				$res = DB::query("REPLACE INTO ".DB::getTable('TABLE_EMAIL_ADDRESSES_BOUNCES')." (email, failed) VALUES ('".DBFunctions::strEscape($_POST['email'])."', TRUE)");
			} else {
				$res = DB::query("DELETE FROM ".DB::getTable('TABLE_EMAIL_ADDRESSES_BOUNCES')." WHERE email = '".DBFunctions::strEscape($_POST['email'])."'");
			}
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del_email_address" && $id) {
			
			$res = DB::query("DELETE e, eo, eb FROM ".DB::getTable('TABLE_EMAIL_ADDRESSES')." e
								LEFT JOIN ".DB::getTable('TABLE_EMAIL_ADDRESSES_OPT_OUT')." eo ON (eo.email = e.email)
								LEFT JOIN ".DB::getTable('TABLE_EMAIL_ADDRESSES_BOUNCES')." eb ON (eb.email = e.email)
								WHERE e.email = '".DBFunctions::strEscape($id)."'");
			
			$this->refresh_table = true;
			$this->msg = true;
		}
	}
	
	private static function createDatabaseEmailAddressCountInfo() {
		
		$count = self::getDatabaseEmailAddressCount();
		return '<span class="info email-count"><span>'.(int)$count["filtered"].'</span><span class="icon" title="'.getLabel("lbl_excluding").':<br />'.(int)$count['bounces'].' '.getLabel('lbl_bounces').'<br />'.(int)$count['opt_out'].' '.getLabel('lbl_opt_out').'">'.getIcon('info').'</span></span>';
	}
		
	public static function getDatabaseEmailAddressesRaw($email = false) {
	
		$arr = [];
		
		$res = DB::query("SELECT
			e.email, e.name, CASE WHEN eb.email IS NOT NULL THEN TRUE ELSE FALSE END AS bounced, CASE WHEN eo.email IS NOT NULL THEN TRUE ELSE FALSE END AS opt_out
				FROM ".DB::getTable('TABLE_EMAIL_ADDRESSES')." e
				LEFT JOIN ".DB::getTable('TABLE_EMAIL_ADDRESSES_BOUNCES')." eb ON (eb.email = e.email
					AND (eb.failed = TRUE
					OR eb.count >= 3)
				)
				LEFT JOIN ".DB::getTable('TABLE_EMAIL_ADDRESSES_OPT_OUT')." eo ON (eo.email = e.email)
			".($email ? "WHERE e.email = '".DBFunctions::strEscape($email)."'" : "")."
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['bounced'] = DBFunctions::unescapeAs($arr_row['bounced'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['opt_out'] = DBFunctions::unescapeAs($arr_row['opt_out'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[] = $arr_row;
		}
		
		return ($email ? current($arr) : $arr);
	}
	
	public static function getDatabaseEmailAddressCount() {

		$res = DB::query("SELECT
			SUM(CASE WHEN eo.email IS NULL AND eb.email IS NULL THEN 1 ELSE 0 END) AS filtered, SUM(CASE WHEN eb.email IS NOT NULL THEN 1 ELSE 0 END) AS bounces, SUM(CASE WHEN eo.email IS NOT NULL THEN 1 ELSE 0 END) AS opt_out
				FROM ".DB::getTable('TABLE_EMAIL_ADDRESSES')." e
				LEFT JOIN ".DB::getTable('TABLE_EMAIL_ADDRESSES_BOUNCES')." eb ON (eb.email = e.email
						AND (eb.failed = TRUE
						OR eb.count >= 3)
				)
				LEFT JOIN ".DB::getTable('TABLE_EMAIL_ADDRESSES_OPT_OUT')." eo ON (eo.email = e.email)
		");
		
		$row = $res->fetchAssoc();
		
		return $row;
	}
	
	public static function getEmailAddresses($arr_recipients) {
	
		$arr_email_database = [];
		
		if ($arr_recipients["database"]) {
			
			$res = DB::query("SELECT e.email
								FROM ".DB::getTable('TABLE_EMAIL_ADDRESSES')." e
								LEFT JOIN ".DB::getTable('TABLE_EMAIL_ADDRESSES_BOUNCES')." eb ON (eb.email = e.email)
								LEFT JOIN ".DB::getTable('TABLE_EMAIL_ADDRESSES_OPT_OUT')." eo ON (eo.email = e.email)
								WHERE eo.email IS NULL
									AND eb.failed IS NULL
									AND COALESCE(eb.count, 0) < 3
									AND COALESCE(eb.date_postponed, 0) < NOW()
			");

			while($arr_row = $res->fetchAssoc()) {
				
				$arr_email_database[] = $arr_row['email'];
			}
			unset($arr_recipients['database']);
		}
		
		$arr_email_users = [];
		
		if ($arr_recipients) {
			
			DB::query("SELECT u.email FROM ".DB::getTable('TABLE_USERS')." u
								LEFT JOIN ".DB::getTable('TABLE_EMAIL_ADDRESSES_BOUNCES')." eb ON (eb.email = u.email)
								LEFT JOIN ".DB::getTable('TABLE_EMAIL_ADDRESSES_OPT_OUT')." eo ON (eo.email = u.email)
								WHERE eo.email IS NULL
									AND eb.failed IS NULL
									AND COALESCE(eb.count, 0) < 3
									AND COALESCE(eb.date_postponed, 0) < NOW()
									AND u.group_id IN (".implode(",", array_keys($arr_recipients)).")
			");
									
			while ($arr_row = $res->fetchAssoc()) {
				
				$arr_email_users[] = $arr_row['email'];
			}
		}		
		
		return arrMergeValues([$arr_email_database, $arr_email_users]);
	}
	
	public static function getTemplates($template = 0) {
	
		$arr = [];

		$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_NEWSLETTERS_TEMPLATES')."
								".($template ? "WHERE id = ".(int)$template."" : "")."
								ORDER BY id
		");

		while ($arr_row = $res->fetchAssoc()) {
			
			$arr[$arr_row['id']] = $arr_row;
		}
		
		return ((int)$template ? current($arr) : $arr);
	}
	
	public static function getNewsletters($newsletter = 0) {
	
		$arr = [];

		$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_NEWSLETTERS')."
								WHERE 
								".($newsletter ? "id = ".(int)$newsletter."" : "draft = 0")."
								ORDER BY id");

		while ($arr_row = $res->fetchAssoc()) {
			
			$arr[$arr_row['id']] = $arr_row;
		}
		
		return ((int)$newsletter ? current($arr) : $arr);
	}
	
	public static function addEmailAddress($email, $name = '') {
	
		$res = DB::query("INSERT INTO ".DB::getTable('TABLE_EMAIL_ADDRESSES')."
			(email, name)
				VALUES
			('".DBFunctions::strEscape($email)."', '".DBFunctions::strEscape($name)."')
			".DBFunctions::onConflict('email', ['email'])."
		");
	}
	
	public static function addNewsletterOptOut($newsletter_id) {
		
		$res = DB::query("UPDATE ".DB::getTable('TABLE_NEWSLETTERS')." SET opt_out = opt_out+1 WHERE id = ".(int)$newsletter_id."");
	}
	
	
	public static function addNewsletterBounce($newsletter_id, $count = 1) {
		
		$res = DB::query("UPDATE ".DB::getTable('TABLE_NEWSLETTERS')." SET bounces = bounces+".$count." WHERE id = ".(int)$newsletter_id."");
	}
	
	protected function sendNewsletter($arr_email, $id, $title, $body) {
		
		$subject = $title;
		
		$module_url = pages::getModuleURL(pages::getClosestModule('newsletters'));
		
		Labels::setVariable('link_view', $module_url.$id);
		Labels::setVariable('link_opt_out', $module_url.'unsubscribe/'.$id.'/[V][email]');
		
		$msg = '<table style="border: 0px; border-spacing: 0px; border-collapse: collapse; padding: 0px; margin: 0px; width: 100%">';
		$msg .= '<tr><td style="text-align: right;">'.getLabel('txt_trouble_viewing').'</td></tr>';
		$msg .= '<tr><td style="text-align: center;">'.$body.'</td></tr>';
		$msg .= '<tr><td style="text-align: center;">'.getLabel('txt_email_opt_out').'</td></tr>';
		$msg .= '</table>';
		
		$arr_vars = [];
		foreach ($arr_email as $value) {
			$arr_vars[] = ['email' => $value];
		}
		
		$arr_headers = ['X-Newsletter-ID' => $id];
		
		timeLimit(false);
		
		$mail = new Mail();
		$mail->to($arr_email, $arr_vars);
		$mail->subject($subject);
		$mail->message($msg);
		$mail->header($arr_headers);
		$mail->tag('l='.$id);
		
		$mail->send();
	}
	
	public static function checkReturnedEmail() {

		$host = getLabel('email_1100cc_host', 'D'); // e.g. imap.gmail.com:993/imap/ssl or other.host:888/pop3/novalidate-cert
		$user = getLabel('email_1100cc', 'D');
		$password = getLabel('email_1100cc_password', 'D');
		$host = Labels::printLabels($host);
		$folder = 'INBOX';
		$user = Labels::printLabels($user);
		$password = Labels::printLabels($password);

		$inbox = imap_open('{'.$host.'}'.$folder.'', $user, $password) or error('Cannot connect to 1100CC email account.');

		$emails = imap_search($inbox, 'ALL');

		if ($emails) {
		
			$output = '';
			
			foreach($emails as $email_number) {
			
				//$overview = imap_fetch_overview($inbox, $email_number, 0);
				//$overview[0]->seen
				//$overview[0]->subject
				//$overview[0]->from
				//$overview[0]->date
				
				$email = [imap_fetchheader($inbox, $email_number), imap_fetchbody($inbox, $email_number, 1)];
				$handled = self::handleBouncedEmail($email);
				
				if ($handled) {
					imap_delete($inbox, $email_number);
				}
			}
			
			//imap_delete($inbox, '1:*');
			//imap_expunge($inbox);
		} 
	
		imap_close($inbox);
	}
	
	protected function handleBouncedEmail($email_content) {
	
		$bouncehandler = new BounceHandler();

		// A regular expression to find a web beacon in the email body
		$bouncehandler->web_beacon_preg_1 = "/l=([0-9]+)/";

		// Find a web beacon in an X-header field (in the head section)
		$bouncehandler->x_header_search_1 = "X-Newsletter-ID";

		$arr = $bouncehandler->get_the_facts($email_content);
		
		/*$bouncehandler->type
		$bouncehandler->subject
		$bouncehandler->web_beacon_1
		$bouncehandler->web_beacon_2
		$bouncehandler->x_header_beacon_1
		$bouncehandler->x_header_beacon_2*/
		
		$id = (int)($bouncehandler->x_header_beacon_1 ?: $bouncehandler->web_beacon_1);

		$fail_count = 0;

		// Multipart-reports return multiple bounced emails and FBL's (Feedback Loop) report only one bounce
		foreach ($arr as $the) {
			
			switch ($the['action']) {
				case 'failed':
					$column = "failed";
					$value = "1";
					$fail_count++;
					break;
				case 'transient':
					$column = "count";
					$value = "count+1";
					break;
				case 'autoreply':
					$column = "date_postponed";
					$value = "(NOW() + ".DBFunctions::interval(7, 'DAY').")";
					break;
				default:
					break;
			}

			if ($column) {
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_EMAIL_ADDRESSES_BOUNCES')."
					(email, ".$column.")
						VALUES
					('".DBFunctions::strEscape($the['recipient'])."', ".$value.")
					".DBFunctions::onConflict('email', [$column])."
				");
			}
		}
		
		if ($fail_count && $id) {
			self::addNewsletterBounce($id, $fail_count);
		}

		return ($column ? true : false);
	}
}
