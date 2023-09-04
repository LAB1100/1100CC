<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class feed extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_feed');
		static::$parent_label = getLabel('lbl_communication');
	}
		
	public static function moduleVariables() {
		
		$arr_return = [
			getLabel('lbl_feed') => '<select name="id">'.cms_general::createDropdown(cms_feeds::getFeeds()).'</select>',
			getLabel('lbl_limit') => '<input name="limit" type="number" min="1" max="100" step="1" value="" />'
		];

		return $arr_return;
	}
	
	public static function searchProperties() {
		
		return [
			'trigger' => [DB::getTable('TABLE_FEED_ENTRIES'), DB::getTable('TABLE_FEED_ENTRIES').'.body'],
			'title' => [DB::getTable('TABLE_FEED_ENTRIES'), DB::getTable('TABLE_FEED_ENTRIES').'.title'],
			'search_var' => [DB::getTable('TABLE_FEEDS'), DB::getTable('TABLE_FEEDS').'.id'],
			'module_link' => [
				[DB::getTable('TABLE_FEED_ENTRIES'), DB::getTable('TABLE_FEED_ENTRIES').'.id'],
				[DB::getTable('TABLE_FEED_ENTRY_LINK'), DB::getTable('TABLE_FEED_ENTRY_LINK').'.feed_entry_id'],
				[DB::getTable('TABLE_FEED_ENTRY_LINK'), DB::getTable('TABLE_FEED_ENTRY_LINK').'.feed_id'],
				[DB::getTable('TABLE_FEEDS'), DB::getTable('TABLE_FEEDS').'.id']
			],
			'module_var' => 'id',
			'module_query' => function($arr_result) {
				return $arr_result['object_link'].'/'.str2URL($arr_result['title']);
			}
		];
	}
	
	public function contents() {
		
		$open_feed_entry_id = (int)$this->arr_query[0];
		$arr_feed_options = cms_feeds::getFeeds($this->arr_variables['id']);
		$feed_id = $arr_feed_options['id'];

		SiteEndVars::setModuleVariables($this->mod_id);
		
		$str_html = '';					
		$str_tag = ($this->arr_query[0] == 'tag' ? $this->arr_query[1] : false);
		
		if ($str_tag) {
			
			$str_tag = str_replace('+', ' ', $str_tag);
			$arr_tags = cms_general::getTags(DB::getTable('TABLE_FEED_ENTRIES'), DB::getTable('TABLE_FEED_ENTRY_TAGS'), 'feed_entry_id', false, $str_tag);

			if ($arr_tags) {
				
				$str_tag = $arr_tags[0]['name'];
				$str_tag_view = Labels::parseTextVariables($str_tag);
				$str_tag_url = str_replace(' ', '+', $str_tag);
				
				SiteEndVars::addTitle(getLabel('lbl_tag'));
				SiteEndVars::addTitle($str_tag_view);
				
				SiteEndVars::addContentIdentifier('tag', $str_tag);
				SiteEndVars::setModuleVariables($this->mod_id, ['tag', $str_tag_url]);
				
				if ($this->arr_mod['shortcut']) {
					SiteEndVars::setShortcut($this->mod_id, $this->arr_mod['shortcut'], $this->arr_mod['shortcut_root']);
				}
			} else {
				
				$str_tag = false;
			}
		}
		
		$num_total = cms_feed_entries::getFeedEntriesCount($feed_id, $str_tag);
		$num_limit = (int)$this->arr_variables['limit'];
		$num_start = 0;
		
		if ($open_feed_entry_id) {
			$num_start = cms_feed_entries::getFeedEntryPosition($feed_id, $open_feed_entry_id);
		}
		
		if ($num_start > 0) {
			$str_html .= '<button type="button" id="y:feed:load-before_'.$num_start.'"><span class="icon">'.getIcon('plus').'</span><span class="icon-text">'.getLabel('lbl_more').'</span></button>';
		}
		
		$arr_feed_entries = cms_feed_entries::getFeedEntries($feed_id, false, $num_limit, $num_start, $str_tag);
		
		if ($arr_feed_entries) {

			foreach ($arr_feed_entries as $feed_entry_id => $arr_feed_entry) {
				
				$do_highlight = ($feed_entry_id == $open_feed_entry_id);
				
				$str_html .= $this->createFeedEntry($arr_feed_entry, $do_highlight);
			}
			
			if (isset($arr_feed_entries[$open_feed_entry_id])) {
			
				SiteEndVars::setModuleVariables($this->mod_id, [$open_feed_entry_id]);
				
				if ($this->arr_mod['shortcut']) {
					SiteEndVars::setShortcut($this->mod_id, $this->arr_mod['shortcut'], $this->arr_mod['shortcut_root']);
				}
			}
		}
		
		if (($num_start + $num_limit) < $num_total) {
			$str_html .= '<button type="button" id="y:feed:load-after_'.($num_start+$num_limit).'"><span class="icon">'.getIcon('plus').'</span><span class="icon-text">'.getLabel('lbl_more').'</span></button>';
		}
		
		return $str_html;
	}
		
	public static function css() {
	
		$return = '.feed {  }
			.feed > article { position: relative; overflow: hidden; }
			.feed > article + article { margin-top: 20px; }
			.feed > article > figure { display: block; text-align: left; }
			.feed > article > .album { display: flex; flex-flow: row nowrap; align-items: flex-start; }
			.feed > article > .album > figure { flex: 1 1 100%; }
			.feed > article > figure > img,
			.feed > article > figure > video,
			.feed > article > .album > figure > img,
			.feed > article > .album > figure > video { max-width: 100%; max-height: 100%; height: auto; }
			.feed > article > div.tags { margin-bottom: 0px; }
			.feed > article > div.tags .icon  { display: none; }
			.feed > article > a:first-child,
			.feed > article > a:first-child:hover { text-decoration: none; }
			.feed > article > a:first-child > time { display: inline-block; }
			
			.feed > article > figure,
			.feed > article > .album,
			.feed > article > h1,
			.feed > article > section.body,
			.feed > article > .tags { margin-top: 8px; margin-bottom: 0px; }
			
			.feed > article > section a.link-only { display: inline-block; vertical-align: top; white-space: nowrap; max-width: 26ch; overflow: hidden; text-overflow: ellipsis; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.mod.feed', function(elm_scripter) {
			
			elm_scripter.on('click', '[id^=y\\\:feed\\\:load-]', function(e) {
				
				var cur = $(this);
				var str_command = COMMANDS.getID(this, true);
				var str_direction = str_command.split('_')[0];
				
				COMMANDS.quickCommand(this, function(data) {
					
					let elms_feed = false;
					
					if (data.html) {
						
						elms_feed = $(data.html);
						
						if (str_direction == 'before') { // But insert after the button
							cur.after(elms_feed);
						} else {
							cur.before(elms_feed);
						}
					}
					
					if (!data.position) {
						cur.remove();
					}

					COMMANDS.setData(this, {position: data.position});
					
					return elms_feed;
				});
			})
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY
		
		if ($method == 'load') {
			
			$arr_id = explode('_', $id);
			$str_direction = $arr_id[0];
			$num_position = ($value['position'] ?? $arr_id[1]);
			
			$arr_feed_options = cms_feeds::getFeeds($this->arr_variables['id']);
			$feed_id = $arr_feed_options['id'];
			
			$str_tag = ($this->arr_query[0] == 'tag' ? $this->arr_query[1] : false);
			
			if ($str_tag) {
				
				$str_tag = str_replace('+', ' ', $str_tag);
				$arr_tags = cms_general::getTags(DB::getTable('TABLE_FEED_ENTRIES'), DB::getTable('TABLE_FEED_ENTRY_TAGS'), 'feed_entry_id', false, $str_tag);

				if ($arr_tags) {
					$str_tag = $arr_tags[0]['name'];
				} else {
					$str_tag = false;
				}
			}

			$num_limit = (int)$this->arr_variables['limit'];
			$num_start = 0;
			
			if ($str_direction == 'before') {
				
				$num_start = ($num_position - $num_limit);
				$num_start = ($num_start < 0 ? 0 : $num_start);
				$num_position = $num_start;
			} else {

				$num_start = $num_position;
				$num_position = ($num_start + $num_limit);
				
				$num_total = cms_feed_entries::getFeedEntriesCount($feed_id, $str_tag);
				if (($num_start + $num_limit) >= $num_total) { // End of the line
					$num_position = 0;
				}
			}

			$arr_feed_entries = cms_feed_entries::getFeedEntries($feed_id, false, $num_limit, $num_start, $str_tag);
						
			$str_html = '';
			
			foreach ($arr_feed_entries as $arr_feed_entry) {
					
				$str_html .= $this->createFeedEntry($arr_feed_entry);
			}

			$this->html = ['html' => $str_html, 'position' => $num_position];
		}
	}
	
	public function createFeedEntry($arr_feed_entry, $do_highlight = false) {
		
		$str_title = strEscapeHTML(Labels::parseTextVariables($arr_feed_entry['title']));
		$str_body = parseBody($arr_feed_entry['body']);
		$str_media = '';
		$str_link = '';
		
		$str_url_time = '<a href="'.SiteStartVars::getModuleURL($this->mod_id).$arr_feed_entry['id'].'">'.createDate($arr_feed_entry['date']).'</a>';
		
		if ($arr_feed_entry['media']) {
			
			foreach ($arr_feed_entry['media'] as $str_path_media) {
				
				$str_path_media = ltrim($str_path_media, '/'); // Need to remove web-oriented absolute '/' from path
				
				$enucleate = new EnucleateMedia($str_path_media, DIR_ROOT_STORAGE.DIR_HOME, '/'); // Add add absolute '/' path back
				$enucleate->setSizing(false, 300, true);
				$str_media .= '<figure>'.$enucleate->enucleate(EnucleateMedia::VIEW_HTML, ['autoplay' => true, 'loop' => true]).'</figure>';
			}
			
			if (count($arr_feed_entry['media']) > 1) {
				$str_media = '<div class="album">'.$str_media.'</div>';
			}
		}
		if ($arr_feed_entry['url']) {
			
			$is_internal = uris::isURLInternal($arr_feed_entry['url']);
			$str_title = ($str_title ? '<a href="'.$arr_feed_entry['url'].'"'.(!$is_internal ? ' target="_blank"' : '').'>'.$str_title.'</a>' : '');
			$str_link = '<a href="'.$arr_feed_entry['url'].'"'.(!$is_internal ? ' target="_blank"' : '').'></a>';
		}
		
		if ($str_title) {
			$str_title = '<h1>'.$str_title.'</h1>';
		}
		
		$arr_content_identifiers = [];
		$str_html_tags = '';
		$arr_tags = $arr_feed_entry['tags'];
		
		if ($arr_tags) {
			
			foreach ($arr_tags as $str_tag) {
				$arr_content_identifiers['tag'][$str_tag] = $str_tag;
			}
			
			$str_html_tags = cms_general::createViewTags($arr_tags, SiteStartVars::getModuleURL($this->mod_id).'tag/');
		}
	
		$html = '<article id="entry-'.$arr_feed_entry['id'].'"'.($do_highlight ? ' class="highlight"' : '').($arr_content_identifiers ? ' data-content="'.createContentIdentifier($arr_content_identifiers).'"' : '').'>'
			.$str_url_time
			.$str_title
			.'<section class="body">'.$str_body.'</section>'
			.$str_media
			.$str_html_tags
			.$str_link
		.'</article>';
		
		return $html;
	}
}
