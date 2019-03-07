<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class blog_tag_cloud extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_blog').' '.getLabel('lbl_tag_cloud');
		static::$parent_label = getLabel('lbl_communication');
	}
	
	public static function moduleVariables() {
		
		$return .= '<select name="limit" title="Limit">';
			for ($i = 10; $i <= 100; $i = $i+10) {
				$return .= '<option value="'.$i.'">'.$i.'</option>';
			}
		$return .= '</select>'
		.'<select name="preview" title="Preview">'
			.'<option value=""></option>';
			for ($i = 1; $i <= 10; $i++) {
				$return .= '<option value="'.$i.'">'.$i.'</option>';
			}
		$return .= '</select>'
		.'<select name="id">'
			.cms_general::createDropdown(cms_blogs::getBlogs(), 0, true)
		.'</select>';
		
		return $return;
	}
	
	public function contents() {
		
		$blog_id = ($this->arr_variables['id'] ?: 0);
		
		$arr_link = blog::findMainBlog($blog_id);
		$arr_link_mod_var = json_decode($arr_link['var'], true);
		
		$arr_tags = $this->getTags($arr_link_mod_var['id']);
		
		if (!$arr_tags) {
			return;
		}

		$arr_tags_sorted = [];

		foreach ($arr_tags as $arr_row) {
			
			if ($arr_row['name']) {
				$arr_tags_sorted[$arr_row['name']] = $arr_row['count'];
			}
		}
		
		ksort($arr_tags_sorted); // the query sorted it to highest count first, now it is resorted to the tag name
		
		$max_size = 100; // max font size in %
		$min_size = 1; // min font size in %
		$max_quantity = max(array_values($arr_tags_sorted)); // get the largest and smallest array values
		$min_quantity = min(array_values($arr_tags_sorted)); // get the largest and smallest array values
		$spread = $max_quantity - $min_quantity; // find the range of values
		$spread = ($spread == 0 ? 1 : $spread); // we don't want to divide by zero
		$step = (($max_size - $min_size) / $spread); // determine the font-size increment, this is the increase per tag quantity (times used)

		$nr_preview = (int)$this->arr_variables['preview'];
		$nr_preview = ($nr_preview && $nr_preview < count($arr_tags_sorted) ? $nr_preview : 0);
			
		$arr_html = [];
		$str_inf_tags = getLabel('inf_tags_tag', 'L', true);
		$count_tags = 0;
		
		foreach ($arr_tags_sorted as $str_tag => $count) {
			
			$size = (($count - $min_quantity) * $step) + $min_size;
			
			Labels::setVariable('count', $count);
			Labels::setVariable('tag', $str_tag);
			
			$title = Labels::printLabels(Labels::parseTextVariables($str_inf_tags));
			
			$str_tag = htmlspecialchars($str_tag);
			
			$arr_html[] = '<a'.($arr_link ? ' href="'.SiteStartVars::getModUrl($arr_link['id'], $arr_link['page_name'], $arr_link['sub_dir']).'tag/'.str_replace(' ', '+', $str_tag).'"' : '').' style="font-size: '.$size.'%" title="'.htmlspecialchars($title).'" data-tag="'.$str_tag.'"'.($nr_preview && $count_tags >= $nr_preview ? ' class="hide-show"' : '').'><span>'.$str_tag.'</span><sup>'.$count.'</sup></a>';
			
			$count_tags++;
		}
				
		if ($nr_preview) {

			$html_tags = '<input id="hide-show-'.$this->mod_id.'" type="checkbox" />'
			.implode('', $arr_html)
			.'<label class="a" for="hide-show-'.$this->mod_id.'">'.getLabel('lbl_tags_show_all').'</label><label class="a" for="hide-show-'.$this->mod_id.'">'.getLabel('lbl_tags_hide').'</label>';
		} else {
			
			$html_tags = implode('', $arr_html);
		}
		
		$return .= '<h1>'.getLabel('lbl_tag_cloud').'</h1>';
		
		$return .= '<div>'.$html_tags.'</div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.blog_tag_cloud { }
				.blog_tag_cloud > div { margin: 8px 0px 0px 0px; text-align: justify; }
				.blog_tag_cloud > div a { display: inline-block; color: #000000; text-decoration: none; vertical-align: middle; line-height: 1.25; margin: 3px 0px; padding: 3px 4px; max-width: 100%; box-sizing: border-box; background-color: #e1e1e1; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
				.blog_tag_cloud > div a + a { margin-left: 3px; }
				.blog_tag_cloud > div:last-child > a:last-child { margin-bottom: 3px; }
				.blog_tag_cloud > div a:hover { color: #666666; text-decoration: none; }
				.blog_tag_cloud > div a > sup { margin-left: 2px; }
				
				.blog_tag_cloud > div > input[id^=hide-show] ~ label { margin-left: 5px; }
				
				.blog_tag_cloud > div { font-size: 1.0rem; }
				.blog_tag_cloud > div a > span { font-size: calc(150% + 1.0rem); }
				.blog_tag_cloud > div a > sup { font-size: calc(100% + 0.9rem); }
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
	
	public function getTags($blog_id = 0) {
	
		$arr = [];
	
		if ($blog_id) {
			
			$res = DB::query("SELECT t.*, COUNT(t.id) AS count
					FROM ".DB::getTable('TABLE_BLOG_POSTS')." p
					JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = p.id)
					JOIN ".DB::getTable('TABLE_BLOG_POST_TAGS')." bt ON (bt.blog_post_id = p.id)
					JOIN ".DB::getTable('TABLE_TAGS')." t ON (t.id = bt.tag_id)
				WHERE l.blog_id = ".(int)$blog_id."
				GROUP BY t.id
				ORDER BY count DESC
				".($this->arr_variables['limit'] ? "LIMIT ".(int)$this->arr_variables['limit'] : '')
			);
			
			while ($arr_row = $res->fetchAssoc()) {
				
				$arr[] = $arr_row;
			}
		}
		
		return $arr;
	}
}
