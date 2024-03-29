<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_POLL_SETS', DB::$database_home.'.def_poll_sets');
DB::setTable('TABLE_POLL_SET_LINK', DB::$database_home.'.def_poll_set_link');
DB::setTable('TABLE_POLL_SET_OPTIONS', DB::$database_home.'.def_poll_set_options');
DB::setTable('TABLE_POLL_SET_OPTION_VOTES', DB::$database_home.'.data_poll_set_option_votes');

class cms_poll_sets extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_poll_sets');
		static::$parent_label = getLabel('ttl_content');
	}
	
	public static function logUserLocations() {
		return [
			'TABLE_POLL_SET_OPTION_VOTES' => 'log_user_id'
		];
	}

	public function contents() {
		
		$return = '<div class="section"><h1 id="x:cms_poll_sets:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup add" value="add" /></h1>
		<div class="poll_sets">';

			$return .= '<table class="display" id="d:cms_poll_sets:data-0">
				<thead> 
					<tr>				
						<th data-sort="desc-0"><span title="'.getLabel('lbl_enabled').'">E</span></th>
						<th class="max"><span>'.getLabel('lbl_label').'</span></th>
						<th><span>'.getLabel('lbl_votes').'</span></th>
						<th data-sort="desc-1"><span>'.getLabel('lbl_date').'</span></th>';
						
						$arr_polls = cms_polls::getPolls();
						
						if (count($arr_polls) > 1) {
							
							foreach ($arr_polls as $poll) {
								$return .= '<th class="limit"><span>P: '.$poll['name'].'</span></th>';
							}
						}
						$return .= '<th class="disable-sort"></th>
					</tr> 
				</thead>
				<tbody>
					<tr>
						<td colspan="'.(count($arr_polls)+5).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
					</tr>
				</tbody>
			</table>';
						
		$return .= '</div></div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('#frm-poll_set', function(elm_scripter) {
			
			elm_scripter.on('click', '.add', function() {
				elm_scripter.find('.sorter').sorter('addRow');
			}).on('click', '.del', function() {
				elm_scripter.find('.sorter').sorter('clean');
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "edit" || $method == "add") {

			$arr_polls = cms_polls::getPolls();
		
			if ((int)$id) {
				
				$arr_poll_set = self::getPollSet($id);
								
				$row = current($arr_poll_set);
								
				$mode = "update";
			} else {
			
				if (!$arr_polls) {
					$this->html = '<section class="info">'.getLabel('msg_no_polls').'</section>';
					return;
				}
						
				$mode = "insert";
			}
														
			$this->html = '<form id="frm-poll_set" data-method="'.$mode.'">
				<table>
					<tr>
						<td></td>
						<td>'.cms_general::createSelectorRadio([['id' => '1', 'name' => getLabel('lbl_active')], ['id' => '0', 'name' => getLabel('lbl_inactive')]], 'enabled', ($mode == 'insert' || $row['enabled'])).'</td>
					</tr>
					<th colspan="2" class="split"><hr /></th>
					<tr>
						<td>'.getLabel('lbl_poll').'</td>';
						
						if (count($arr_polls) > 1) {
							
							$this->html .= '<td class="checkboxes">'.cms_general::createSelector(cms_polls::getPolls(), 'poll', ($mode == 'insert' ? 'all' : self::getPollSetLinks($row['id'])), 'name').'</td>';
						} else if (count($arr_polls) == 1) {
							
							$row_poll = current($arr_polls);
							
							$this->html .= '<td><input type="hidden" name="poll['.$row_poll["id"].']" value="1" />'.$row_poll["name"].'</td>';
						}
					$this->html .= '</tr>
					<tr>
						<td>'.getLabel('lbl_label').'</td>
						<td><textarea name="label">'.strEscapeHTML($row['label']).'</textarea></td>
					</tr>';
					if ($mode == "insert") {
						
						$this->html .= '<tr>
							<td></td>
							<td><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></td>
						</tr>';
					}
					$this->html .= '<tr>
						<td>'.getLabel('lbl_options').'</td>
						<td>';
						
							$arr_sorter = [];
							
							if ($mode == 'update') {
							
								foreach ($arr_poll_set as $value) {
									$arr_sorter[] = ['value' => '<input type="text" name="set_option['.$value['option_id'].']" value="'.$value['option_label'].'" />'];
								}
							} else {
								
								for ($i = 0; $i < 5; $i++) {
									$arr_sorter[] =  ['value' => '<input type="text" name="set_option[]" value="" />'];
								}
							}	
							$this->html .= cms_general::createSorter($arr_sorter, 'append');
						$this->html .= '</td>
					</tr>
					<tr>
						<td>'.getLabel('lbl_date').'</td>
						<td>'.cms_general::createDefineDate($row['date']).'</td>
					</tr>
				</table>
				</form>';
			
			$this->validate = ['label' => 'required', 'date' => 'required'];
		}
		
		// POPUP INTERACT
				
		
		// DATATABLE
					
		if ($method == "data") {
			
			$sql_column_set_vote_count = "SUM((SELECT
				COUNT(sov.poll_set_option_id)
					FROM ".DB::getTable('TABLE_POLL_SET_OPTION_VOTES')." sov
				WHERE sov.poll_set_option_id = so.id
			))";
			$sql_column_options_combined = DBFunctions::sqlImplode("CONCAT(
				'<li><label>', so.label, '</label>', '<span>', (SELECT
					COUNT(sov.poll_set_option_id)
						FROM ".DB::getTable('TABLE_POLL_SET_OPTION_VOTES')." sov
					WHERE sov.poll_set_option_id = so.id
				), '</span></li>'
			)", '', "ORDER BY so.sort");
			
			$arr_sql_columns = ['s.enabled', 's.label', '', 's.date'];
			$arr_sql_columns_search = ['', 's.label', '', DBFunctions::castAs('s.date', DBFunctions::CAST_TYPE_STRING)];
			$arr_sql_columns_as = ['s.enabled', 's.label', $sql_column_set_vote_count.' AS set_vote_count', 's.date', $sql_column_options_combined.' AS options_combined', 's.id'];
			
			$arr_polls = cms_polls::getPolls();
			$arr_urls_base = [];
			
			foreach ($arr_polls as $arr_poll) {
				
				$arr_link = cms_polls::findMainPoll($arr_poll['id']);
				$arr_urls_base[$arr_poll['id']] = pages::getModuleURL($arr_link);
				
				$arr_sql_columns[] = 'poll_'.$arr_poll['id'].'.poll_id';
				$arr_sql_columns_as[] = 'poll_'.$arr_poll['id'].'.poll_id AS poll_'.$arr_poll['id'];
			}
			
			$sql_table = DB::getTable('TABLE_POLL_SETS').' s';

			$sql_index = 's.id';
			$sql_index_body = 's.id, so.poll_set_id';
			
			$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_POLL_SET_OPTIONS')." so ON (so.poll_set_id = s.id)";
			foreach ($arr_polls as $arr_poll) {
				
				$sql_index_body .= ', poll_'.$arr_poll['id'].'.poll_id';
				$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_POLL_SET_LINK')." poll_".$arr_poll['id']." ON (poll_".$arr_poll['id'].".poll_set_id = s.id AND poll_".$arr_poll['id'].".poll_id = ".$arr_poll['id'].")";
			}
				 
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', $sql_index_body);

			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:cms_poll_sets:poll_set_id-'.$arr_row['id'];
				$arr_data[] = '<span class="icon" data-category="status">'.getIcon((DBFunctions::unescapeAs($arr_row['enabled'], DBFunctions::TYPE_BOOLEAN) ? 'tick' : 'min')).'</span>';
				$arr_data[] = $arr_row['label'];
				$arr_data[] = '<span class="info"><span class="icon" title="'.($arr_row['options_combined'] ? strEscapeHTML('<ul>'.$arr_row['options_combined'].'</ul>') : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.$arr_row['set_vote_count'].'</span></span>';
				$arr_data[] = date('d-m-Y', strtotime($arr_row['date']));
				
				if (count($arr_polls) > 1) {
					
					foreach ($arr_polls as $arr_poll) {
						
						if ($arr_row['poll_'.$arr_poll['id']]) {
							
							$str_title_url = $arr_urls_base[$arr_poll['id']].$arr_row['id'].'/'.str2URL($arr_row['title']);
							
							$arr_data[] = '<a href="'.$str_title_url.'" target="_blank"><span class="icon">'.getIcon('tick').'</span></a>';
						} else {
							
							$arr_data[] = '';
						}
					}
				}
								
				$arr_data[] = '<input type="button" class="data edit popup edit" value="edit" /><input type="button" class="data del msg del" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];

		}
							
		// QUERY
	
		if ($method == "insert") {
					
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_POLL_SETS')."
				(label, enabled, date)
					VALUES
				('".DBFunctions::strEscape($_POST['label'])."', ".DBFunctions::escapeAs($_POST['enabled'], DBFunctions::TYPE_BOOLEAN).", '".date("Y-m-d H:i:s", strtotime($_POST['date'].' '.$_POST['date_t']))."')
			");
						
			$new_id = DB::lastInsertID();
			
			if ($_POST['poll']) {
				
				foreach ($_POST['poll'] as $key => $value) {
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_POLL_SET_LINK')."
						(poll_id, poll_set_id)
							VALUES
						(".(int)$key.", ".$new_id.")
					");
				}
			}
			
			$sort = 0;
			
			foreach ($_POST['set_option'] as $key => $value) {
				
				if ($value) {
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_POLL_SET_OPTIONS')."
						(poll_set_id, label, sort)
							VALUES
						(".$new_id.", '".DBFunctions::strEscape($value)."', ".$sort.")
					");
					
					$sort++;
				}
			}
						
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "update" && (int)$id) {
						
			$res = DB::query("UPDATE ".DB::getTable('TABLE_POLL_SETS')."
				SET
					label = '".DBFunctions::strEscape($_POST['label'])."',
					enabled = ".DBFunctions::escapeAs($_POST['enabled'], DBFunctions::TYPE_BOOLEAN).",
					date = '".date('Y-m-d H:i:s', strtotime($_POST['date'].' '.$_POST['date_t']))."'
				WHERE id = ".(int)$id."
			");
						
			$res = DB::query("DELETE FROM ".DB::getTable('TABLE_POLL_SET_LINK')."
				WHERE poll_set_id = ".(int)$id."
			");
			
			if ($_POST['poll']) {
				
				foreach ($_POST['poll'] as $key => $value) {
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_POLL_SET_LINK')."
						(poll_id, poll_set_id)
							VALUES
						(".(int)$key.", ".(int)$id.")
					");
				}
			}
			
			$sort = 0;
			foreach ($_POST['set_option'] as $key => $value) {
				
				if ($value) {
					
					$res = DB::query("UPDATE ".DB::getTable('TABLE_POLL_SET_OPTIONS')."
						SET
							label = ".($value ? "'".DBFunctions::strEscape($value)."'" : "label").",
							sort = ".$sort."
						WHERE id = ".(int)$key."
					");
					
					$sort++;
				}
			}
									
			$this->refresh_table = true;
			$this->msg = true;
		}
			
		if ($method == "del" && (int)$id) {
		
			$res = DB::queryMulti("
				".DBFunctions::deleteWith(
					DB::getTable('TABLE_POLL_SET_OPTION_VOTES'), 'v', 'poll_set_option_id',
					"JOIN ".DB::getTable('TABLE_POLL_SET_OPTIONS')." so ON (so.id = v.poll_set_option_id AND so.poll_set_id = ".(int)$id.")"
				)."
				;
				DELETE FROM ".DB::getTable('TABLE_POLL_SET_OPTIONS')."
					WHERE poll_set_id = ".(int)$id."
				;
				DELETE FROM ".DB::getTable('TABLE_POLL_SET_LINK')."
					WHERE poll_set_id = ".(int)$id."
				;
				DELETE FROM ".DB::getTable('TABLE_POLL_SETS')."
					WHERE id = ".(int)$id."
				;
			");
							
			$this->msg = true;
		}
		
	}

	public static function getPollSetLinks($poll_set = 0) {
	
		$arr = [];

		$res = DB::query("SELECT poll_id
				FROM ".DB::getTable('TABLE_POLL_SET_LINK')."
			WHERE poll_set_id = ".(int)$poll_set."
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr[] = $arr_row['poll_id'];
		}
		
		return $arr;
	}
	
	public static function getPollSets($poll_id, $enabled = true) {
	
		$arr = [];
		
		$poll_options = cms_polls::getPolls($poll_id);

		$res = DB::query("SELECT
			s.*,
			so.id AS option_id,
			so.label AS option_label,
			so.sort AS option_sort,
			(SELECT
				COUNT(*)
					FROM ".DB::getTable('TABLE_POLL_SET_OPTION_VOTES')." sov
					WHERE sov.poll_set_option_id = so.id
			) AS option_vote_count
				FROM ".DB::getTable('TABLE_POLL_SETS')." s
				JOIN ".DB::getTable('TABLE_POLL_SET_OPTIONS')." so ON (so.poll_set_id = s.id)
				JOIN ".DB::getTable('TABLE_POLL_SET_LINK')." l ON (l.poll_set_id = s.id)
			WHERE l.poll_id = ".(int)$poll_id."
				".($enabled ? "AND s.enabled = TRUE" : "")."
			GROUP BY so.id, s.id
			ORDER BY s.date, so.sort
		");
				
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['enabled'] = DBFunctions::unescapeAs($arr_row['enabled'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[$arr_row['id']][$arr_row['option_id']] = $arr_row;
		}
		
		return $arr;
	}
	
	public static function getPollSet($poll_set_id) {
	
		$arr = [];
		
		$res = DB::query("SELECT
			s.*,
			so.id AS option_id,
			so.label AS option_label,
			so.sort AS option_sort,
			(SELECT
				COUNT(*)
					FROM ".DB::getTable('TABLE_POLL_SET_OPTION_VOTES')." sov
				WHERE sov.poll_set_option_id = so.id
			) AS option_vote_count
				FROM ".DB::getTable('TABLE_POLL_SETS')." s
				LEFT JOIN ".DB::getTable('TABLE_POLL_SET_OPTIONS')." so ON (so.poll_set_id = s.id)
			WHERE s.id = ".(int)$poll_set_id."
			GROUP BY so.id, s.id
			ORDER BY so.sort
		");
									
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['enabled'] = DBFunctions::unescapeAs($arr_row['enabled'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[$arr_row['option_id']] = $arr_row;
		}	
		
		return $arr;
	}
}
