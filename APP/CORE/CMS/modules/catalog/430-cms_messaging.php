<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('MESSAGING_CONVERSATIONS', DB::$database_home.'.data_messaging_conversations');
DB::setTable('MESSAGING_CONVERSATION_PARTICIPANTS', DB::$database_home.'.data_messaging_conversation_participants');
DB::setTable('MESSAGING_CONVERSATION_MESSAGES', DB::$database_home.'.data_messaging_conversation_messages');
DB::setTable('MESSAGING_CONVERSATION_LAST_SEEN', DB::$database_home.'.data_messaging_conversation_last_seen');
DB::setTable('USER_PREFERENCES_MESSAGING', DB::$database_home.'.user_preferences_messaging');

class cms_messaging extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_messaging');
		static::$parent_label = getLabel('ttl_data');
	}
	
	public function contents() {
		
		$return .= '<div class="section"><h1 id="x:cms_messaging:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup add" value="add" /></h1>
		<div class="messaging">';

			$return .= '<table class="display" id="d:cms_messaging:data-0">
					<thead> 
						<tr>							
							<th class="max"><span>'.getLabel('lbl_user').'</span></th>
							<th class="max"><span>'.getLabel('lbl_subject').'</span></th>
							<th class="limit"><span>'.getLabel('lbl_message').'</span></th>
							<th class="disable-sort limit"><span>'.getLabel('lbl_message_count').'</span></th>
							<th class="limit"><span>'.getLabel('lbl_participants').'</span></th>
							<th data-sort="desc-0 limit"><span>'.getLabel('lbl_last_activity').'</span></th>
							<th class="disable-sort"></th>
						</tr> 
					</thead>
					<tbody>
						<tr>
							<td colspan="7" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
					</table>';
						
		$return .= '</div></div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '#frm-messaging ul.conversation { margin: 10px 0px; width: 600px; }
					#frm-messaging ul.conversation > li { margin-top: 10px; margin-right: 20%; }
					#frm-messaging ul.conversation > li:first-child { margin-top: 0px; }
					#frm-messaging ul.conversation > li.own { margin-right: 0px; margin-left: 20%; }
					#frm-messaging ul.conversation > li > cite { display: block; }
					#frm-messaging ul.conversation > li.own > cite { text-align: right; }
					#frm-messaging ul.conversation > li > cite > span:first-child { font-weight: bold; }
					#frm-messaging ul.conversation > li.own > cite > span:first-child { display: none; }
					#frm-messaging ul.conversation > li > cite > span:first-child + span { display: inline-block; margin-left: 5px; }
					#frm-messaging ul.conversation > li > cite + div { margin-top: 5px; padding: 8px 10px; background-color: #f5f5f5; }					
					
					#frm-messaging textarea.body-content { width: 600px; height: 175px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('#frm-messaging', function(elm_scripter) {

			elm_scripter.on('scripter', function() {
				elm_scripter.find('[name=message_type]').trigger('change');
			}).on('change', '[name=message_type]', function() {
			
				var value = $(this).siblings('[name=message_type]').addBack().filter(':checked').val();
				var elm_target = $(this).closest('ul');
				if (value == 'system') {
					elm_target.find('[name^=participant_user_groups]').closest('li').show();
					elm_target.find('[name=participants]').closest('li').hide();
				} else if (value == 'personal') {
					elm_target.find('[name=participants]').closest('li').show();
					elm_target.find('[name^=participant_user_groups]').closest('li').hide();
				}
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "add" || ($method == "view" && (int)$id)) {
			
			$arr_conversation_set = [];
			$arr_conversation_set_participants = [];
			$arr_conversation_set_participant_groups = [];
			
			if ((int)$id) {
				
				$arr_conversation_set = self::getConversationSet($id);
				
				if ($arr_conversation_set['participants']) {
					foreach ($arr_conversation_set['participants'] as $key => $value) {
						$arr_conversation_set_participants[$key] = $value['name'].(strtotime($value['date_last_seen']) ? '<span title="'.getLabel('inf_last_activity').'"> ('.date('d-m H:i', strtotime($value['date_last_seen'])).')</span>' : '');
					}
					unset($arr_conversation_set_participants[0]);
				}
				
				$arr_conversation_set_participant_groups = ($arr_conversation_set['participant_user_groups'] ? array_filter(array_keys($arr_conversation_set['participant_user_groups'])) : []);
				
				$mode = "update";
			} else {
									
				$mode = "insert";
			}
														
			$this->html = '<form id="frm-messaging" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_subject').'</label>
						<div><input type="text" name="subject" value="'.htmlspecialchars($arr_conversation_set['details']['subject']).'" /></div>
					</li>';
					if ($mode == 'insert') {
						
						$this->html .= '<li>
							<label>'.getLabel('lbl_message_type').'</label>
							<div><span class="radios">'.cms_general::createSelectorRadio([['id' => 'personal', 'type' => getLabel('lbl_message_personal')], ['id' => 'system', 'type' => getLabel('lbl_message_system')]], 'message_type', ($arr_conversation_set_participant_groups ? "system" : "personal"), 'type').'</span></div>
						</li>';
					}
					if ($mode == 'insert' || $arr_conversation_set_participant_groups) {
						
						$this->html .= '<li>
							<label>'.getLabel('lbl_user_groups').'</label>
							<div><span class="checkboxes">'.cms_general::createSelector(user_groups::getUserGroups(), 'participant_user_groups', ($arr_conversation_set_participant_groups ?: [])).'</span></div>
						</li>';
					}
					if ($mode == 'insert' || $arr_conversation_set_participants) {
						
						$this->html .= '<li>
							<label>'.getLabel('lbl_participants').'</label>
							<div>'.cms_general::createMultiSelect('participants', 'y:cms_messaging:lookup_user-0', ($arr_conversation_set_participants ?: [])).'</div>
						</li>';
					}
					if ($mode == 'update') {
						
						$this->html .= '<th colspan="2" class="split"><hr /></th>
						<li>
							<label>'.getLabel('lbl_conversation').'</label>
							<div><ul class="conversation">';
			
							if ($mode == 'update') {
								
								foreach ($arr_conversation_set['messages'] as $key => $value) {
									$this->html .= '<li'.(!$value['message_user_id'] ? ' class="own"' : '').'>
													<cite><span>'.htmlspecialchars($value['message_user_name']).'</span><span>'.date('d-m-Y H:i', strtotime($value['message_date'])).'</span></cite>
													<div>'.parseBody($value['message_body']).'</div>
												</li>';
								}
							}
			
							$this->html .= '</ul></div>
						</li>';
					}
					$this->html .= '<li>
						<label>'.getLabel(($mode == 'update' ? 'lbl_reply' : 'lbl_message')).'</label>
						<div>'.cms_general::editBody(false).'</div>
					</li>
				</ul></fieldset>
				</form>';
						
			$this->validate = [
				'subject' => 'required',
				'body' => ['required' => ($mode == 'insert')],
				'participants' => ['required' => "function(elm) {
					return ($('#frm-messaging [name=message_type]:checked').val() == 'personal');
				}"],
				'participant_user_groups' => ['required' => "function(elm) {
					return ($('#frm-messaging [name=message_type]:checked').val() == 'system');
				}"]
			];
		}
		
		// POPUP INTERACTION
		
		if ($method == 'lookup_user') {
		
			$arr_users = user_management::filterUsers($value);
			
			$arr = [];
			foreach ($arr_users as $row) {
				$arr[] = ['id' => $row['id'], 'label' => $row['name'], 'value' => $row['name']];
			}

			$this->html = $arr;
		}
						
		// DATATABLE
					
		if ($method == "data") {
			
			$sql_message_count = "(SELECT COUNT(*)
				FROM ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')." cmsgc
				WHERE (cmsgc.conversation_id = c.id)
			)";
			$sql_participant_count = "(SELECT 
				COUNT(cp.user_id) AS participant_count
					FROM ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." cp
				WHERE cp.conversation_id = c.id
			)";
			$sql_participant_summary = "(SELECT 
				CASE
					WHEN COUNT(cp.user_id) > 5 THEN COUNT(cp.user_id)
					ELSE ".DBFunctions::sqlImplode('u.name', ', ', "ORDER BY cp.is_owner DESC")."
				END
					FROM ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." cp
					LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = cp.user_id)
				WHERE cp.conversation_id = c.id
			)";
			$sql_participant_user_groups = "(SELECT 
				".DBFunctions::sqlImplode('ug.name', ', ')."
					FROM ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." cp
					LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = cp.user_id)
					LEFT JOIN ".DB::getTable('TABLE_USER_GROUPS')." ug ON (ug.id = cp.user_group_id)
				WHERE cp.conversation_id = c.id
			)";
			
			$arr_sql_columns = ['cmsgu.name', 'c.subject', 'cmsg.body', $sql_message_count, $sql_participant_count, 'cmsg.date'];
			$arr_sql_columns_search = ['cmsgu.name', 'c.subject', 'cmsg.body', '', '', 'cmsg.date'];
			$arr_sql_columns_as = ['c.id', "CASE WHEN cmsg.user_id != 0 THEN cmsgu.name ELSE '".getLabel('name', 'D')."' END AS last_message_user_name", 'c.subject', 'cmsg.body AS last_message_body', $sql_message_count.' AS message_count', $sql_participant_summary.' AS participants', 'cmsg.date AS last_message_activity', $sql_participant_user_groups.' AS participant_user_groups'];
			
			$sql_table = DB::getTable('MESSAGING_CONVERSATIONS')." c
				JOIN ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')." cmsg ON (cmsg.conversation_id = c.id
					AND cmsg.date = (SELECT MAX(date)
							FROM ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')."
						WHERE conversation_id = c.id)
				)";

			$sql_index = 'c.id, cmsg.id';
			
			$sql_where = "EXISTS (SELECT 1 FROM ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." cp WHERE cp.conversation_id = c.id)"; // Check for participants
			
			$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_USERS')." cmsgu ON (cmsgu.id = cmsg.user_id)";
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{

				$arr_data = [];
				
				$arr_data['id'] = 'x:cms_messaging:view-'.$arr_row['id'].'';
				$arr_data['class'] = 'popup';
				$arr_data['attr']['data-method'] = 'view';
			
				$arr_data[] = $arr_row['last_message_user_name'];
				$arr_data[] = htmlspecialchars($arr_row['subject']);
				$arr_data[] = strip_tags($arr_row['last_message_body']);
				$arr_data[] = (int)$arr_row['message_count'];
				$arr_data[] = ($arr_row['participant_user_groups'] ?: $arr_row['participants']);
				$arr_data[] = date('d-m-Y H:i', strtotime($arr_row['last_message_activity']));
				$arr_data[] = '<input type="button" class="data del msg del" value="del" />';				
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
							
		// QUERY
		
		if ($method == "insert") {
			
			$arr_paricipants = ($_POST['participants'] ? array_filter($_POST['participants']) : []);
			$arr_paricipant_user_groups = array_keys(array_filter(($_POST['participant_user_groups'] ?: [])));
		
			if (!$_POST['subject'] || (!$arr_paricipants && !$arr_paricipant_user_groups)) {
				error(getLabel('msg_missing_information'));
			}
		
			self::sendMessage(false, 0, $_POST['subject'], $_POST['body'], ($_POST['message_type'] == 'personal' ? $arr_paricipants : false), ($_POST['message_type'] == 'system' ? $arr_paricipant_user_groups : false));

			$this->refresh_table = true;
			$this->msg = true;
		}
		if ($method == "update" && (int)$id) {

			$arr_paricipants = ($_POST['participants'] ? array_filter($_POST['participants']) : []);
			$arr_paricipant_user_groups = array_keys(array_filter(($_POST['participant_user_groups'] ?: [])));
			
			if (!$_POST['subject'] || (!$arr_paricipants && !$arr_paricipant_user_groups)) {
				error(getLabel('msg_missing_information'));
			}
			
			self::sendMessage($id, 0, $_POST['subject'], $_POST['body'], ($arr_paricipants ?: false), ($arr_paricipant_user_groups ?: false));
		
			$this->refresh_table = true;
			$this->msg = true;
		}
				
		if ($method == "del" && (int)$id) {
		
			self::delConversation($id);
			$this->msg = true;
		}
	}
	
	public static function sendMessage($conversation_id, $user_id, $subject, $body, $arr_participants = [], $arr_participant_user_groups = [], $arr_options = []) {
		
		// $arr_options = array('individual' => true/false, 'limit' => #) 
		
		$arr_participants = ($arr_participants ? (array)$arr_participants : []);
		$body = Labels::printLabels($body);
		
		if ($arr_options['individual'] && !(int)$conversation_id) { // Start a conversation, or add to a named conversation, for each participant seperately
			
			unset($arr_options['individual']);
			
			$arr_conversation_ids = [];
			
			foreach ($arr_participants as $participant_user_id) {
				$arr_conversation_ids[] = self::sendMessage($conversation_id, $user_id, $subject, $body, [$participant_user_id], false, $arr_options);
			}
			
			return $arr_conversation_ids;
		}
		
		$conversation_id = self::handleConversation($conversation_id, $user_id, $subject, $arr_participants, $arr_participant_user_groups, $arr_options);

		if (trim($body)) {
			self::addMessage($conversation_id, $user_id, $body);
		}
			
		return $conversation_id;
	}
		
	public static function handleConversation($conversation_id, $user_id, $subject, $arr_participants = [], $arr_participant_user_groups = [], $arr_options = []) {
		
		// $conversation_id = id or string (to identify an existing conversation between participants and a name)
		$arr_participants = ($arr_participants ? (array)$arr_participants : []);
		
		if (!$arr_participant_user_groups) { // Make sure user is part of conversation
			$arr_participants[] = $user_id;
			$arr_participants = array_filter(array_unique($arr_participants));
		}
		
		if ($conversation_id && !(int)$conversation_id) { // Name, find conversation by name and participants
			$name = $conversation_id;
			$conversation_id = false;
		}
		
		// Find existing named conversations
		if ($name) {
			
			$res = DB::query("SELECT
				c.id
					FROM ".DB::getTable('MESSAGING_CONVERSATIONS')." c
					JOIN ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." cp ON (cp.conversation_id = c.id)
				WHERE c.name = '".DBFunctions::strEscape($name)."'
					AND ".(!$arr_participant_user_groups ? "cp.user_id IN (".implode(',', $arr_participants).")" : "cp.user_group_id IN (".implode(',', $arr_participant_user_groups).")")."
				GROUP BY c.id
				HAVING ".(!$arr_participant_user_groups ? "COUNT(cp.user_id) = ".count($arr_participants) : "COUNT(cp.user_group_id) = ".count($arr_participant_user_groups))."
			");
			
			$row = $res->fetchRow();
			$named_conversation_id = (int)$row[0];
						
			if ($named_conversation_id) {
				
				if ($arr_options['limit']) { // Limit a named conversation to a number of messages, ascending

					$res = DB::query("DELETE FROM ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')."
						WHERE conversation_id = ".$named_conversation_id."
							AND date <= (
								SELECT date FROM (
									SELECT date
										FROM ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')."
									WHERE conversation_id = ".$named_conversation_id."
									ORDER BY date DESC
									LIMIT 1 OFFSET ".((int)$arr_options['limit']-1)."
								) foo
							)
					");
				}
				
				return $named_conversation_id;
			}
		}
		
		$subject = Labels::printLabels($subject);
		
		if ($conversation_id) {
			
			$res = DB::query("UPDATE ".DB::getTable('MESSAGING_CONVERSATIONS')." SET
				subject = '".DBFunctions::strEscape($subject)."'
				WHERE id = ".(int)$conversation_id
			);
				
			$update = true;
		} else {
			
			$res = DB::query("INSERT INTO ".DB::getTable('MESSAGING_CONVERSATIONS')."
				(subject, name)
					VALUES
				('".DBFunctions::strEscape($subject)."', '".DBFunctions::strEscape($name)."')
			");
			
			$conversation_id = DB::lastInsertID();
		}
		
		$arr_participants_values = [];
		
		if (!$arr_participant_user_groups) {
			
			foreach ($arr_participants as $value) {
				$arr_participants_values[] = (int)$conversation_id.", ".(int)$value.", ".((int)$value == (int)$user_id ? 1 : 0);
			}
			
			if ($update) {
				$res = DB::query("DELETE FROM ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." WHERE conversation_id = ".(int)$conversation_id." AND user_id NOT IN (".implode(",", $arr_participants).")");
			}
		} else {
			
			foreach ($arr_participant_user_groups as $value) {
				$arr_participants_values[] = (int)$conversation_id.", ".(int)$value.", 0";
			}
			
			if ($update) {
				$res = DB::query("DELETE FROM ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." WHERE conversation_id = ".(int)$conversation_id." AND user_group_id NOT IN (".implode(",", $arr_participant_user_groups).")");
			}
		}

		$res = DB::query("INSERT INTO ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')."
			(conversation_id, ".(!$arr_participant_user_groups ? "user_id" : "user_group_id").", is_owner)
				VALUES
			(".implode("),(", $arr_participants_values).")
			".DBFunctions::onConflict('conversation_id', ['conversation_id'])."
		");
							
		return $conversation_id;
	}
	
	public static function delConversation($id) {
		
		$res = DB::query("DELETE c, cp, cmsg, cls
				FROM ".DB::getTable('MESSAGING_CONVERSATIONS')." c
				LEFT JOIN ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." cp ON (cp.conversation_id = c.id)
				LEFT JOIN ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')." cmsg ON (cmsg.conversation_id = c.id)
				LEFT JOIN ".DB::getTable('MESSAGING_CONVERSATION_LAST_SEEN')." cls ON (cls.conversation_id = c.id)
			WHERE c.id = ".(int)$id."");
	}
	
	public static function addMessage($conversation_id, $user_id, $body) {
			
		$res = DB::query("INSERT INTO ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')."
			(conversation_id, user_id, body, date)
				VALUES
			(".(int)$conversation_id.", ".(int)$user_id.", '".DBFunctions::strEscape($body)."', NOW())
		");

		$message_id = DB::lastInsertID();
		
		self::sendNotificationEmails($conversation_id, $user_id);
				
		return $message_id;
	}
	
	private static function sendNotificationEmails($conversation_id, $source_user_id = 0) {
		
		$arr_conversation_set = self::getConversationSet($conversation_id);
		
		$res = DB::query("SELECT u.*
				FROM ".DB::getTable('MESSAGING_CONVERSATIONS')." c
				JOIN ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." cp ON (cp.conversation_id = c.id)
				JOIN ".DB::getTable('TABLE_USERS')." u ON (".($arr_conversation_set['participant_user_groups'] ? "u.group_id = cp.user_group_id" : "u.id = cp.user_id").")
				LEFT JOIN ".DB::getTable('USER_PREFERENCES_MESSAGING')." up ON (up.user_id = u.id)
				LEFT JOIN ".DB::getTable('MESSAGING_CONVERSATION_LAST_SEEN')." cls ON (cls.conversation_id = c.id AND cls.user_id = u.id)
			WHERE c.id = ".(int)$conversation_id."
				AND u.id != ".(int)$source_user_id."
				AND (SELECT COUNT(*)
						FROM ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')." cmsg
					WHERE cmsg.conversation_id = c.id AND cmsg.date > COALESCE(cls.date, 0)
				) <= 1
				AND (up.notify_email OR up.user_id IS NULL)
		");
		
		$arr_users = [];
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_users[$arr_row['group_id']]['email'][] = $arr_row['email'];
			$arr_users[$arr_row['group_id']]['vars'][] = ['name' => $arr_row['name']];
		}
								
		foreach ($arr_users as $user_group_id => $arr_email_vars) {
			
			$module_url = pages::getModUrl(pages::getClosestMod('messaging', false, false, $user_group_id));
			$account_url = pages::getModUrl(pages::getClosestMod('account', false, false, $user_group_id));
			
			Labels::setVariable('link', $module_url);
			Labels::setVariable('link_opt_out', $account_url);
			Labels::setVariable('subject', $arr_conversation_set['details']['subject']);
			Labels::setVariable('name', false); // Name is printed in the email
			
			$mail = new Mail();
			$mail->to($arr_email_vars['email'], $arr_email_vars['vars']);
			$mail->subject(getLabel('mail_messaging_notification_title'));
			$mail->message(getLabel('mail_messaging_notification', 'L', true).getLabel('txt_email_opt_out', 'L', true));
			$mail->send();
		}
	}
	
	public static function getConversations($user_id, $user_group_id) {
		
		$arr = [];

		$res = DB::query("SELECT c.*,
					COUNT(cp.user_id) AS participant_count,
					".DBFunctions::sqlImplode('u.name', ', ', "ORDER BY cp.is_owner DESC")." AS participants,
					".DBFunctions::sqlImplode('ug.name', ', ')." AS participant_user_groups,
					cls.date AS date_last_seen,
					(SELECT COUNT(*)
						FROM ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')."
						WHERE (conversation_id = c.id)
					) AS message_count,
					cmsg.body AS last_message_body,
					CASE
						WHEN cmsgu.id != 0 THEN cmsgu.name
						ELSE '".getLabel('name', 'D')."'
					END AS last_message_user_name,
					cmsg.date AS last_message_activity
				FROM ".DB::getTable('MESSAGING_CONVERSATIONS')." c
				JOIN ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." cu ON (cu.conversation_id = c.id)
				JOIN ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." cp ON (cp.conversation_id = c.id)
				LEFT JOIN ".DB::getTable('MESSAGING_CONVERSATION_LAST_SEEN')." cls ON (cls.conversation_id = c.id AND cls.user_id = cp.user_id)
				LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = cp.user_id)
				LEFT JOIN ".DB::getTable('TABLE_USER_GROUPS')." ug ON (ug.id = cp.user_group_id)
				JOIN ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')." cmsg ON (cmsg.conversation_id = c.id
						AND cmsg.date = (SELECT MAX(date)
							FROM ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')."
							WHERE conversation_id = c.id)
					)
				LEFT JOIN ".DB::getTable('TABLE_USERS')." cmsgu ON (cmsgu.id = cmsg.user_id)
			WHERE cu.user_id = ".(int)$user_id." OR cu.user_group_id = ".(int)$user_group_id."
			GROUP BY c.id
			ORDER BY last_message_activity DESC
		");

		while($row = $res->fetchAssoc()) {
			$arr[$row['id']] = $row;
		}

		return $arr;
	}
	
	public static function getConversationSet($conversation_id) {
		
		$arr = [];

		$res = DB::query("SELECT c.*,
					cp.user_id,
					cp.user_group_id,
					u.name,
					cp.is_owner,
					cls.date AS date_last_seen,
					cmsg.id AS message_id,
					cmsg.body AS message_body,
					cmsg.user_id AS message_user_id,
					cmsg.date AS message_date,
					CASE
						WHEN cmsg.user_id != 0 THEN cmsgu.name
						ELSE '".getLabel('name', 'D')."'
					END AS message_user_name
				FROM ".DB::getTable('MESSAGING_CONVERSATIONS')." c
				JOIN ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." cp ON (cp.conversation_id = c.id)
				LEFT JOIN ".DB::getTable('MESSAGING_CONVERSATION_LAST_SEEN')." cls ON (cls.conversation_id = c.id AND cls.user_id = cp.user_id)
				LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = cp.user_id)
				JOIN ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')." cmsg ON (cmsg.conversation_id = c.id)
				LEFT JOIN ".DB::getTable('TABLE_USERS')." cmsgu ON (cmsgu.id = cmsg.user_id)
			WHERE c.id = ".(int)$conversation_id."
			ORDER BY cmsg.date ASC, u.name
		");

		while($row = $res->fetchAssoc()) {
			
			$arr['details'] = $row;
			$arr['messages'][$row['message_id']] = $row;
			if ($row['user_id']) {
				$arr['participants'][(int)$row['user_id']] = $row;
			}
			if ($row['user_group_id']) {
				$arr['participant_user_groups'][(int)$row['user_group_id']] = $row;
			}
		}

		return $arr;
	}
}
