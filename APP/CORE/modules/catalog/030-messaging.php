<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

Settings::set('messaging_allow_message_all', ['all_individual' => true, 'all' => true]); // Override $this->arr_variables['allow_message_all']

class messaging extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_messaging');
		static::$parent_label = getLabel('lbl_users');
	}
	
	public static function moduleVariables() {
		
		$return = '<input type="checkbox" name="cms_only" data-group="allow" class="unique" value="1" title="'.getLabel('lbl_cms_only').'" />'
		.'<input type="checkbox" name="user_group" data-group="allow" value="1" title="'.getLabel('lbl_user_group').'" />'
		.'<input type="checkbox" name="parent" data-group="allow" value="1" title="'.getLabel('lbl_parent').'" />'
		.'<input type="checkbox" name="siblings" data-group="allow" value="1" title="'.getLabel('lbl_siblings').'" />'
		.'<input type="checkbox" name="children" data-group="allow" value="1" title="'.getLabel('lbl_children').'" />'
		.'<input type="checkbox" name="allow_message_all" data-group="allow" value="1" title="'.getLabel('lbl_message_all').'" />';
		
		return $return;
	}
	
	public static function accountSettings() {

		return [
			'values' => function() {
				
				$arr = self::getUserPreferences($_SESSION['USER_ID']);
				
				return [
					getLabel('lbl_messaging') => '<label><input name="messaging_email" type="checkbox" value="1"'.($arr['notify_email'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_messaging_email_me').'</span></label>'
				];
			},
			'update' => function ($value) {
				self::updateUserPreferences($_SESSION['USER_ID'], ['notify_email' => $value['messaging_email']]);
			}
		];
	}

	public function contents() {
				
		$return = '<h1>'.getLabel('lbl_messaging').'</h1>'
		
		.'<menu><input type="button" id="y:messaging:inbox-0" value="'.getLabel('lbl_inbox').'" /><input type="button" id="y:messaging:new-0" value="'.getLabel('lbl_new').' '.getLabel('lbl_conversation').'" /></menu>'
		
		.'<div class="dynamic">'.self::createConversationList().'</div>';
		
		return $return;
	}
	
	private static function createConversationList() {

		$return = '<table class="conversations display" id="d:messaging:data-0">
			<thead> 
				<tr>							
					<th>'.getLabel('lbl_user').'</th>
					<th class="max limit">'.getLabel('lbl_subject').'</th>
					<th class="disable-sort">'.getLabel('lbl_message_count').'</th>
					<th>'.getLabel('lbl_participants').'</th>
					<th data-sort="desc-0">'.getLabel('lbl_last_activity').'</th>
					<th class="disable-sort menu" id="x:messaging:conversation_id-0" title="'.getLabel('lbl_multi_select').'">'
						.'<input type="button" class="data msg del" value="d" title="'.getLabel('lbl_delete').'" />'
						.'<input type="checkbox" class="multi all" value="" />'
					.'</th>
				</tr> 
			</thead>
			<tbody>
				<tr>
					<td colspan="5" class="empty">'.getLabel('msg_loading_server_data').'</td>
				</tr>
			</tbody>
		</table>';
		
		return $return;
	}
	
	private function createConversation($conversation_id = 0) {
	
		$arr_conversation_set = [];
		$arr_participants_selected = [];
		
		if ($conversation_id) {
		
			$arr_conversation_set = cms_messaging::getConversationSet($conversation_id);
			
			if (!$arr_conversation_set || (!$arr_conversation_set['participants'][$_SESSION['USER_ID']] && !$arr_conversation_set['participant_user_groups'][$_SESSION['USER_GROUP']])) {
				error(getLabel('msg_not_found'));
			}
		
			self::updateUserLastSeen($conversation_id, $_SESSION['USER_ID']);
			
			if ($arr_conversation_set['participants']) {
				$is_owner = $arr_conversation_set['participants'][$_SESSION['USER_ID']]['is_owner'];
						
				foreach ($arr_conversation_set['participants'] as $key => $value) {
					$arr_participants_selected[$key] = $value['name'].(strtotime($value['date_last_seen']) ? '<span title="'.getLabel('inf_last_activity').'"> ('.date('d-m H:i', strtotime($value['date_last_seen'])).')</span>' : '');
				}
				unset($arr_participants_selected[0], $arr_participants_selected[$_SESSION['USER_ID']]); // Remove CMS and own name
			}
		}
		
		if (!$conversation_id || $is_owner) {
			$str_subject = '<input type="text" name="subject" value="'.strEscapeHTML($arr_conversation_set['details']['subject']).'" />';
			$str_participants = cms_general::createMultiSelect('participants', 'y:messaging:lookup_user-'.(int)$conversation_id, $arr_participants_selected);
		} else {
			$str_subject = '<span>'.strEscapeHTML($arr_conversation_set['details']['subject']).'</span>';
			$str_participants = implode(", ", $arr_participants_selected);
		}
	
		$return = '<form class="conversation" id="f:messaging:'.($conversation_id ? 'update-'.$conversation_id : 'insert-0').'">
			
			<fieldset><ul>
				<li><label>'.getLabel('lbl_subject').'</label>'.$str_subject.'</li>
				'.(!$this->arr_variables['cms_only'] && $str_participants ? '<li><label>'.getLabel('lbl_participants').'</label><div>'.$str_participants.'</div></li>' : '').'
			</ul></fieldset>
			
			<ul class="options">';
		
				if ($conversation_id) {
					foreach ($arr_conversation_set['messages'] as $key => $value) {
						$return .= '<li'.($value['message_user_id'] == $_SESSION['USER_ID'] ? ' class="own"' : '').'>
							<cite><span>'.(strEscapeHTML($value['message_user_name']) ?: '[x]').'</span><span>'.date('d-m-Y H:i', strtotime($value['message_date'])).'</span></cite>
							<div class="body">'.parseBody($value['message_body']).'</div>
						</li>';
					}
				}
		
			$return .= '</ul>';
			
			if (!$conversation_id || $arr_conversation_set['participants'][$_SESSION['USER_ID']]) { // Only for user specific message (so sans system group messages)
				$return .= $this->createComposeMessage();
				
				$return .= '<input type="submit" value="'.getLabel('lbl_send').'" />';
			}
		
		$return .= '</form>';
		
		if (!$conversation_id || $is_owner) {
			
			$this->validate = ['subject' => 'required'];
			if (!$conversation_id) {
				$this->validate['body'] = 'required';
			}
		}
		
		return $return;
	}
	
	private function createComposeMessage() {
	
		$return = '<div class="compose"><textarea name="body"></textarea></div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.messaging .conversations > tbody tr.unread > td:first-child,
					.messaging .conversations > tbody tr.unread > td:first-child + td { font-weight: bold; }
					.messaging .conversations > tbody td + td:last-child { text-align: right; }
					.messaging .conversations > tbody td span.body { color: #aaaaaa; font-size: inherit; }
					
					.messaging .conversation { margin-top: 10px; }
					.messaging .conversation > fieldset { margin: 12px 0px; }
					.messaging .conversation input.autocomplete-multi { width: 175px; }
					.messaging .conversation > ul { }
					.messaging .conversation > ul > li { margin-top: 10px; margin-right: 20%; }
					.messaging .conversation > ul > li:first-child { margin-top: 0px; }
					.messaging .conversation > ul > li.own { margin-right: 0px; margin-left: 20%; }
					.messaging .conversation > ul > li > cite { display: block; }
					.messaging .conversation > ul > li.own > cite { text-align: right; }
					.messaging .conversation > ul > li > cite > span:first-child { font-weight: bold; }
					.messaging .conversation > ul > li.own > cite > span:first-child { display: none; }
					.messaging .conversation > ul > li > cite > span:first-child + span { display: inline-block; margin-left: 5px; }
					.messaging .conversation > ul > li > cite + div { margin-top: 5px; padding: 10px 10px; background-color: #ffffff; }
					.messaging .conversation > .compose textarea { width: 100%; height: 150px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.messaging', function(elm_scripter) {
		
			elm_scripter.on('click', '> menu > input', function(e) {

				$(this).quickCommand(elm_scripter.children('.dynamic'));
			}).on('click', '.conversations > tbody > tr', function(e) {
				
				var elm_target = $(e.target);
				
				if (elm_target.is('input, button') || elm_target.closest('input, button').length) {
					return;
				}
				
				$(this).quickCommand(elm_scripter.children('.dynamic'));
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
		
		if ($method == "inbox") {

			$this->html = $this->createConversationList();
		}
		
		if ($method == "new") {
		
			$this->html = $this->createConversation();
		}
		
		if ($method == "view") {
		
			$this->html = $this->createConversation($id);
		}
		
		if ($method == "lookup_user") {

			$arr_users = $this->getUsers($value);
			
			if (!$id && $this->arr_variables['allow_message_all'] && Settings::get('messaging_allow_message_all')) {
				if (Settings::get('messaging_allow_message_all', 'all_individual')) {
					$arr[] = ['id' => 'all_individual', 'label' => getLabel('lbl_message_all_individual'), 'value' => getLabel('lbl_message_all_individual')];
				}
				if (Settings::get('messaging_allow_message_all', 'all')) {
					$arr[] = ['id' => 'all', 'label' => getLabel('lbl_message_all'), 'value' => getLabel('lbl_message_all')];
				}
			}
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
			
			$arr_sql_columns = ['cmsgu.name', 'CONCAT(c.subject, cmsg.body)', $sql_message_count, $sql_participant_count, 'cmsg.date'];
			$arr_sql_columns_search = ['cmsgu.name', 'CONCAT(c.subject, cmsg.body)', '', '', 'cmsg.date'];
			$arr_sql_columns_as = [
				"CASE
					WHEN cmsg.user_id != 0 THEN cmsgu.name
					ELSE '".getLabel('name', 'D')."'
				END AS last_message_user_name",
				'c.subject',
				'cmsg.body AS last_message_body',
				$sql_message_count.' AS message_count',
				$sql_participant_summary.' AS participants',
				'cmsg.date AS last_message_activity',
				$sql_participant_user_groups.' AS participant_user_groups',
				'cls.date AS date_last_seen',
				'c.id'
			];
			
			$sql_table = DB::getTable('MESSAGING_CONVERSATIONS')." c
				JOIN ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." cu ON (cu.conversation_id = c.id)
				JOIN ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')." cmsg ON (cmsg.conversation_id = c.id
					AND cmsg.date = (SELECT MAX(date)
							FROM ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')."
						WHERE conversation_id = c.id)
				)";

			$sql_index = 'c.id, cmsg.id';
			
			$sql_where = "(cu.user_id = ".(int)$_SESSION['USER_ID']." OR cu.user_group_id = ".(int)$_SESSION['USER_GROUP'].")";
			
			$sql_table .= "
				LEFT JOIN ".DB::getTable('TABLE_USERS')." cmsgu ON (cmsgu.id = cmsg.user_id)
				LEFT JOIN ".DB::getTable('MESSAGING_CONVERSATION_LAST_SEEN')." cls ON (cls.conversation_id = c.id AND cls.user_id = ".(int)$_SESSION['USER_ID'].")
			";
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{

				$arr_data = [];
				
				$arr_data['id'] = 'x:messaging:conversation-'.$arr_row['id'].'';
				$arr_data['class'] = ($arr_row['date_last_seen'] < $arr_row['last_message_activity'] ? 'unread' : '');
				$arr_data['attr']['data-method'] = 'view';
			
				$arr_data[] = $arr_row['last_message_user_name'];
				$arr_data[] = '<span>'.strEscapeHTML($arr_row['subject']).'</span><span class="body"> - '.strip_tags($arr_row['last_message_body']).'</span>';
				$arr_data[] = (int)$arr_row['message_count'];
				$arr_data[] = ($arr_row['participant_user_groups'] ? '' : $arr_row['participants']);
				$arr_data[] = date('d-m-Y H:i', strtotime($arr_row['last_message_activity']));
				$arr_data[] = ($arr_row['participant_user_groups'] ? '' : '<input type="button" class="data msg del" value="del" /><input class="multi" value="'.$arr_row['id'].'" type="checkbox" />');
								
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
		
		// QUERY
		
		if ($method == 'insert' && $this->is_confirm !== false) {
		
			if (!$_POST['subject']) {
				error(getLabel('msg_missing_information'));
			}
			
			$arr_participants = ($_POST['participants'] ? array_filter($_POST['participants']) : []);
			
			if ($arr_participants) {
				
				$all = (in_array('all', $arr_participants) || in_array('all_individual', $arr_participants));
				$individual = in_array('all_individual', $arr_participants);
				$arr_participants = $this->getValidParticipants($arr_participants);
			
				if ($all && !$arr_participants) {
					error(getLabel('msg_missing_information'));
				}
			}
			
			if ($all && !$this->is_confirm) {
				
				Labels::setVariable('count', count($arr_participants));
				$this->html = getLabel(($individual ? 'conf_message_all_individual' : 'conf_message_all'));
				$this->do_confirm = true;
				return;
			}
		
			$conversation_id = cms_messaging::sendMessage(false, $_SESSION['USER_ID'], $_POST['subject'], $_POST['body'], $arr_participants, false, ['individual' => $individual]);
			
			self::updateUserLastSeen($conversation_id, $_SESSION['USER_ID']);
		
			$this->html = (is_array($conversation_id) ? $this->createConversationList() : $this->createConversation($conversation_id));
			$this->msg = true;
		}
		
		if ($method == "update") {
			
			$arr_conversation_set = cms_messaging::getConversationSet($id);
			
			if (!$arr_conversation_set['participants'][$_SESSION['USER_ID']] && !$arr_conversation_set['participant_user_groups'][$_SESSION['USER_GROUP']]) {
				error(getLabel('msg_not_found'));
			}
			
			if ($arr_conversation_set['participants'][$_SESSION['USER_ID']]['is_owner']) {
			
				$arr_participants = ($_POST['participants'] ? array_filter($_POST['participants']) : []);
				if ($arr_participants) {
					$arr_participants = $this->getValidParticipants($arr_participants, $id);
				}
				
				cms_messaging::handleConversation($id, $_SESSION['USER_ID'], $_POST['subject'], $arr_participants);
			}
			if (trim($_POST['body'])) {
				cms_messaging::addMessage($id, $_SESSION['USER_ID'], $_POST['body']);
			}
			
			self::updateUserLastSeen($id, $_SESSION['USER_ID']);
		
			$this->html = $this->createConversation($id);
			$this->msg = true;
		}
		
		if ($method == "del" && $id) {
			
			foreach ((is_array($id) ? $id : [$id]) as $id) {
				
				$arr_conversation_set = cms_messaging::getConversationSet($id);
				
				if (!$arr_conversation_set['participants'][$_SESSION['USER_ID']]) {
					error(getLabel('msg_not_found'));
				}
				
				if (count($arr_conversation_set['participants']) == 1) {

					cms_messaging::delConversation($id);
				} else {
				
					$res = DB::query("DELETE
							FROM ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')."
						WHERE conversation_id = '".(int)$id."' AND user_id = ".$_SESSION['USER_ID']."
					");
				}
			}
								
			$this->refresh_table = true;
			$this->msg = true;
		}
	}
	
	public static function getUnreadMessages() {
		
		$res = DB::query("SELECT
			COUNT(cmsg.id)
				FROM ".DB::getTable('MESSAGING_CONVERSATIONS')." c
				JOIN ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." cu ON (cu.conversation_id = c.id)
				LEFT JOIN ".DB::getTable('MESSAGING_CONVERSATION_LAST_SEEN')." cls ON (cls.conversation_id = c.id AND cls.user_id = ".$_SESSION['USER_ID'].")
				JOIN ".DB::getTable('MESSAGING_CONVERSATION_MESSAGES')." cmsg ON (cmsg.conversation_id = c.id)
			WHERE (cu.user_id = ".$_SESSION['USER_ID']." OR cu.user_group_id = ".$_SESSION['USER_GROUP'].")
				AND (cls.date IS NULL OR cmsg.date > cls.date)
		");
		
		$row = $res->fetchRow();
		
		return $row[0];
	}
		
	private function getUsers($value = false, $all = false) {
		
		$arr_users = user_management::filterUsers($value, [
				'group_id' => ($this->arr_variables['user_group'] ? $_SESSION['USER_GROUP'] : false),
				'parent_id' => ($this->arr_variables['parent'] ? $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'] : false),
				'siblings_parent_id' => ($this->arr_variables['siblings'] ? $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'] : false),
				'children_id' => ($this->arr_variables['children'] ? $_SESSION['USER_ID'] : false)
			], ($all ? false : 20)
		);
		unset($arr_users[$_SESSION['USER_ID']]);
		
		return $arr_users;
	}
	
	private static function updateUserLastSeen($conversation_id, $user_id) {
				
		$conversation_id = (is_array($conversation_id) ? $conversation_id : [$conversation_id]);
		
		$arr_sql = [];
		
		foreach ($conversation_id as $value) {
			$arr_sql[] = "(".(int)$value.", ".(int)$user_id.", NOW())";
		}
		
		$res = DB::query("REPLACE INTO ".DB::getTable('MESSAGING_CONVERSATION_LAST_SEEN')."
			(conversation_id, user_id, date)
				VALUES
			".implode(',', $arr_sql)."
		");
	}
	
	private function getValidParticipants($arr_participants, $conversation_id = false) {
		
		if ($this->arr_variables['allow_message_all'] && Settings::get('messaging_allow_message_all')) {
			
			if ((Settings::get('messaging_allow_message_all', 'all') && in_array('all', $arr_participants)) || (Settings::get('messaging_allow_message_all', 'all_individual') && in_array('all_individual', $arr_participants))) {
			
				unset($arr_participants[array_search('all', $arr_participants)]);
				unset($arr_participants[array_search('all_individual', $arr_participants)]);
			
				$all = (!$conversation_id ? true : false); // Only for new conversations
			}
		}
	
		// Only allow users who match the module selection, or already added participants
			
		$arr_allowed_users = user_management::filterUsers(false,
			[
				'group_id' => ($this->arr_variables['user_group'] ? $_SESSION['USER_GROUP'] : false),
				'parent_id' => ($this->arr_variables['parent'] ? $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'] : false),
				'siblings_parent_id' => ($this->arr_variables['siblings'] ? $_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['parent_id'] : false),
				'children_id' => ($this->arr_variables['children'] ? $_SESSION['USER_ID'] : false),
				'arr_filter' => $arr_participants
			],
			false
		);
		
		$arr_cur_participants = [];
		
		if ($conversation_id) {
			
			$res = DB::query("SELECT
				cp.user_id
					FROM ".DB::getTable('MESSAGING_CONVERSATION_PARTICIPANTS')." cp
				WHERE cp.conversation_id = ".(int)$conversation_id);
			
			while($row = $res->fetchAssoc()) {
				
				$arr_cur_participants[$row['user_id']] = 1;
			}
		}
		
		foreach($arr_participants as $key => $value) {
			
			if (!$arr_allowed_users[$value] && !$arr_cur_participants[$value]) {
				
				unset($arr_participants[$key]);
			}
		}
		
		if ($all) {
			$arr_participants = arrMergeValues([$arr_participants, array_keys($this->getUsers(false, $all))]);
		}
		
		return $arr_participants;
	}
	
	public static function getUserPreferences($user_id) {
	
		$res = DB::query("SELECT
			u.id, COALESCE(up.notify_email, TRUE) AS notify_email
				FROM ".DB::getTable('TABLE_USERS')." u
				LEFT JOIN ".DB::getTable('USER_PREFERENCES_MESSAGING')." up ON (up.user_id = u.id)
			WHERE u.id = ".(int)$user_id."
		");		

		$arr = $res->fetchAssoc();
		$arr = ($arr ?: []);
		
		if ($arr) {
			
			$arr['notify_email'] = DBFunctions::unescapeAs($arr['notify_email'], DBFunctions::TYPE_BOOLEAN);
		}
		
		return $arr;
	}
	
	private static function updateUserPreferences($user_id, $arr) {
		
		$arr_sql = [];
		
		foreach ($arr as $key => $value) {
			
			if ($key == 'notify_email') {
				
				$value = DBFunctions::escapeAs($value, DBFunctions::TYPE_BOOLEAN);
				$arr_sql[$key] = $value;
			}
		}
		
		$arr_sql_fields = array_keys($arr_sql);

		$res = DB::query("INSERT INTO ".DB::getTable('USER_PREFERENCES_MESSAGING')."
			(user_id, ".implode(',', $arr_sql_fields).")
				VALUES
			(".(int)$user_id.", ".implode(',', $arr_sql).")
			".DBFunctions::onConflict('user_id', $arr_sql_fields)."
		");
	}
		
	public static function findMessaging() {
	
		return pages::getClosestMod('messaging', SiteStartVars::$dir['id'], SiteStartVars::$page['id']);
	}
}
