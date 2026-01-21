<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class poll extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_poll');
		static::$parent_label = getLabel('lbl_communication');
	}
	
	public static function moduleVariables() {
		
		$return = '<select>';
		$return .= cms_general::createDropdown(cms_polls::getPolls());
		$return .= '</select>';
		
		return $return;
	}
	
	public function contents() {
	
		$arr_poll_sets = cms_poll_sets::getPollSets($this->arr_variables);
		
		if ($arr_poll_sets) {

			$return .= '<h1>'.getLabel('ttl_poll').'</h1>';
			
			$arr_vote_history = self::getUserVoteHistory(array_keys($arr_poll_sets));
			
			$return .= '<ul>';
			foreach ($arr_poll_sets as $key => $arr_set) {
			
				$row_poll = current($arr_set);
					
				$return .= '<li>';
				$return .= parseBody('<blockquote>'.$row_poll['label'].'</blockquote>');
				
				$return .= self::createPollSet($arr_set, $row_poll['id'], $arr_vote_history[$key]);
				
				$return .= '</li>';
			}
			$return .= '</ul>';
		}
								
		return $return;
	}
	
	public static function css() {
	
		$return = '.poll form input[type=submit] { text-transform: uppercase; }
				.poll dl dt { margin-top: 6px; font-size: 0.9em; }
				.poll dl dt:first-child { margin-top: 0px; }
				.poll dl dd { position: relative; background: #f2f2f2; padding: 2px 4px; }
				.poll dl dd > span:first-child { position: absolute; top: 0px; left: 0px; background: #00497e; height: 100%; }
				.poll dl dd > span:first-child + span { position: relative; color: #ffffff; font-weight: bold; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY
		if ($method == "vote") {
		
			if (!$_POST['poll']) {
				return;
			}

			$poll_set_id = (int)key($_POST['poll']);
			$poll_set_option_id = (int)current($_POST['poll']);
			$arr_vote_history = self::getUserVoteHistory($poll_set_id);
			
			if ($poll_set_option_id && !$arr_vote_history[$poll_set_id]) {
				
				$log_user_id = Log::addToUserDB();
				
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_POLL_SET_OPTION_VOTES')."
					(poll_set_option_id, log_user_id)
						VALUES
					(".$poll_set_option_id.", ".$log_user_id.")
				");
			}
			
			$this->html = self::createPollSet(cms_poll_sets::getPollSet($poll_set_id), $poll_set_id, true);
		}
	}
	
	private static function getUserVoteHistory($set_id) {
	
		$arr_set_id = (is_array($set_id) ? $set_id : [$set_id]);
		
		$log_user_where = Log::getWhereUserDB();
		
		$arr = [];
		
		$res = DB::query("SELECT
			poll_set_id, poll_set_option_id
				FROM ".DB::getTable('TABLE_POLL_SET_OPTION_VOTES')." sov
				JOIN ".DB::getTable('TABLE_LOG_USERS')." lu ON (lu.id = sov.log_user_id)
				JOIN ".DB::getTable('TABLE_POLL_SET_OPTIONS')." so ON (so.id = sov.poll_set_option_id)
			WHERE so.poll_set_id IN (".implode(",", $arr_set_id).")
			AND ".$log_user_where."
		");
		
		while($row = $res->fetchAssoc()) {
			
			$arr[$row['poll_set_id']] = $row['poll_set_option_id'];
		}
		
		return $arr;
	}
	
	private static function createPollSet($arr_set, $pol_set_id, $voted) {
			
		if (!$voted) {
		
			$return .= '<form id="f:poll:vote-'.$pol_set_id.'">'
				.Labels::parseTextVariables(cms_general::createSelectorRadioList($arr_set, 'poll['.$pol_set_id.']', false, 'option_label', 'option_id'))
				.'<input type="submit" value="'.getLabel('lbl_vote').'" />';
			$return .= '</form>';
			
		} else {
		
			$return .= '<dl>';
			
				$total_votes = array_sum(arrValuesRecursive('option_vote_count', $arr_set));
				
				foreach ($arr_set as $key => $row) {
				
					$perc = (($row['option_vote_count']/$total_votes)*100);
					
					$return .= '<dt>'.Labels::parseTextVariables($row['option_label']).'</dt><dd><span style="width: '.$perc.'%;"></span><span>'.round($perc).'%</span></dd>';
				}
				
			$return .= '</dl>';

			$return .= '<p>'.getLabel('lbl_total').': <strong>'.$total_votes.'</strong></p>';
		}
			
		return $return;
	}
}
