<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class feed_ticker extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_feed_ticker');
		static::$parent_label = getLabel('lbl_communication');
	}
		
	public static function moduleVariables() {
		
		$arr_return = [
			getLabel('lbl_feed') => '<select name="id">'.cms_general::createDropdown(cms_feeds::getFeeds()).'</select>',
			getLabel('lbl_limit') => '<input name="limit" type="number" min="1" max="100" step="1" value="" />',
			getLabel('lbl_tags') => '<input name="tag" type="text" value="" />',
		];

		return $arr_return;
	}
		
	static public $num_media_height = 150;
	
	public function contents() {
		
		$arr_link = feed::findMainFeed($this->arr_variables['id']);
		$arr_link_mod_var = json_decode($arr_link['var'], true);
		
		if (!$arr_link_mod_var['id']) {
			return '';
		}
		
		$arr_feed_options = cms_feeds::getFeeds($arr_link_mod_var['id']);
		$feed_id = $arr_feed_options['id'];
		
		if (!$feed_id) {
			return '';
		}
		
		$str_feed_name = Labels::parseTextVariables($arr_feed_options['name']);
		$str_url_base = SiteStartEnvironment::getModuleURL($arr_link['id'], $arr_link['page_name'], $arr_link['sub_dir']);
		
		$arr_tags = false;
		if ($this->arr_variables['tag']) {
			$arr_tags = str2Array($this->arr_variables['tag'], ',');
			$arr_tags = arrParseRecursive($arr_tags, TYPE_STRING);
		}
		
		if ($arr_tags) {
			
			$arr_tags = cms_general::getTags(DB::getTable('TABLE_FEED_ENTRIES'), DB::getTable('TABLE_FEED_ENTRY_TAGS'), 'feed_entry_id', false, $arr_tags);

			if ($arr_tags) {
				
				$arr_tags = arrValuesRecursive('name', $arr_tags);
				$str_tags_view = Labels::parseTextVariables(arr2String($arr_tags, ', '));
			} else {
				$arr_tags = false;
			}
		}
		
		$num_limit = (int)$this->arr_variables['limit'];
		$num_start = 0;
						
		$arr_feed_entries = cms_feed_entries::getFeedEntries($feed_id, false, $num_limit, $num_start, $arr_tags);
		
		if (!$arr_feed_entries) {
			return '';
		}
		
		$arr_options = ['media' => ['height' => static::$num_media_height]];
		$str_html_entries = '';
		
		foreach ($arr_feed_entries as $feed_entry_id => $arr_feed_entry) {
								
			$str_html_entries .= feed::createFeedEntryPreview($arr_feed_entry, $arr_link, $arr_options);
		}
		
		$arr_labels = [
			'name' => $str_feed_name,
			'more' => getLabel('lbl_feed_more')
		];
		
		Settings::get('hook_feed_labels', false, [$this->arr_mod, &$arr_labels]);

		Labels::setVariable('feed_name', $arr_labels['name']);

		$str_html = '<h1>'.strEscapeHTML($arr_labels['name']).'</h1>'
		.'<div>'
			.$str_html_entries
			.'<a class="more" href="'.$str_url_base.'">'.$arr_labels['more'].'</a>'
		.'</div>';
				
		return $str_html;
	}
		
	public static function css() {
	
		$return = '
			.feed_ticker { }
			.feed_ticker > div > article { position: relative; overflow: hidden; }
			.feed_ticker > div > article + article { margin-top: 12px; }
			.feed_ticker > div > article,
			.feed_ticker > div > article > time,
			.feed_ticker > div > article > .body { font-size: 1.2rem; }
			.feed_ticker > div > article > a { position: absolute; top: 0px; bottom: 0px; left: 0px; right: 0px; }
			
			.feed_ticker > div > article > .feed-media,
			.feed_ticker > div > article > section.body { margin-top: 6px; margin-bottom: 0px; }
			
			.feed_ticker > div > a.more { display: block; margin-top: 12px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY
		
	}
}
