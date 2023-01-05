<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class blog extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_blog');
		static::$parent_label = getLabel('lbl_communication');
	}
		
	public static function moduleVariables() {
		
		$return = '<select name="id">';
		$return .= cms_general::createDropdown(cms_blogs::getBlogs());
		$return .= '</select>';
		$return .= '<select name="limit">';
		for ($i = 1; $i <= 10; $i++) {
			$return .= '<option value="'.$i.'">'.$i.'</option>';
		}
		$return .= '</select>';
		
		return $return;
	}
	
	public static function searchProperties() {
		
		return [
			'trigger' => [DB::getTable('TABLE_BLOG_POSTS'), DB::getTable('TABLE_BLOG_POSTS').'.body', 'AND '.DB::getTable('TABLE_BLOG_POSTS').'.draft = FALSE'],
			'title' => [DB::getTable('TABLE_BLOG_POSTS'), DB::getTable('TABLE_BLOG_POSTS').'.title'],
			'search_var' => [DB::getTable('TABLE_BLOGS'), DB::getTable('TABLE_BLOGS').'.id'],
			'module_link' => [
				[DB::getTable('TABLE_BLOG_POSTS'), DB::getTable('TABLE_BLOG_POSTS').'.id'],
				[DB::getTable('TABLE_BLOG_POST_LINK'), DB::getTable('TABLE_BLOG_POST_LINK').'.blog_post_id'],
				[DB::getTable('TABLE_BLOG_POST_LINK'), DB::getTable('TABLE_BLOG_POST_LINK').'.blog_id'],
				[DB::getTable('TABLE_BLOGS'), DB::getTable('TABLE_BLOGS').'.id']
			],
			'module_var' => 'id',
			'module_query' => function($arr_result) {
				return '/'.$arr_result['object_link'].'/'.str2URL($arr_result['name']);
			}
		];
	}
	
	public function contents() {
		
		if ($this->arr_query[0] == 'pingback') {

			if (!PingbackUtility::getRawPostData()) {
				echo 'I need a XML-RPC pingback to work with man!';
				die;
			}
			
			Response::setFormat(Response::OUTPUT_XML | Response::RENDER_XML);

			$server = new PingbackServer();
			$server->execute(PingbackUtility::getRawPostData());

			Response::stop($server->getResponse(), '');
		}		
		if ($this->arr_query[0] == 'rss') {
			
			Response::setFormat(Response::OUTPUT_XML | Response::RENDER_XML);
			
			$rss = $this->generateRSS(cms_blog_posts::getBlogPosts($this->arr_variables['id'], 10));
			
			Response::stop($rss, '');
		}
		
		$blog_post_id = (int)$this->arr_query[0];
		$str_blog_url = SiteStartVars::getShortestModUrl($this->mod_id, false, $this->arr_mod['shortcut'], $this->arr_mod['shortcut_root'], 0, false);
		
		SiteEndVars::addHeadTag('<link rel="alternate" title="RSS Feed" type="application/rss+xml" href="'.$str_blog_url.'rss" />');
		Response::addHeaders('X-Pingback: '.SiteStartVars::getModUrl($this->mod_id, false, 0, false).'pingback');
				
		$return = '';
		
		if ($blog_post_id) {
			
			$arr_blog_post = cms_blog_posts::getBlogPost($blog_post_id);
			
			if (!$arr_blog_post) {
				Response::location(SiteStartVars::getPageUrl());
			}
			
			$return .= $this->createBlogPost($arr_blog_post);
			
			$str_title = Labels::parseTextVariables($arr_blog_post['title']);
			$str_title_url = str2URL($str_title);
			
			SiteEndVars::addTitle($str_title);
			SiteEndVars::setType('article');
			
			if ($arr_blog_post['abstract']) {
				SiteEndVars::addDescription($arr_blog_post['abstract']);
			}
			
			if ($arr_blog_post['tags']) {
				
				$arr_tags = $arr_blog_post['tags'];
				
				SiteEndVars::addKeywords($arr_tags);
				
				foreach ($arr_tags as $str_tag) {
					SiteEndVars::addContentIdentifier('tag', $str_tag);
				}
			}
			
			if ($this->arr_mod['shortcut']) {
				
				SiteEndVars::setShortcut($this->mod_id, $this->arr_mod['shortcut'], $this->arr_mod['shortcut_root']);
			}
			
			SiteEndVars::setModVariables($this->mod_id, [$arr_blog_post['id'], $str_title_url]);
		}
									
		$arr_comments_link = blog_post_comments::findBlogPostComments();

		if (!$arr_comments_link || !$blog_post_id) {
		
			$arr_blog_options = cms_blogs::getBlogs($this->arr_variables['id']);
				
			$str_tag = ($this->arr_query[0] == 'tag' ? $this->arr_query[1] : false);
			
			if ($str_tag) {
				
				$str_tag = str_replace('+', ' ', $str_tag);
				
				SiteEndVars::addTitle('Tag');
				SiteEndVars::addTitle($str_tag);
				
				SiteEndVars::addContentIdentifier('tag', $str_tag);
			}
			
			$start = array_search('go', $this->arr_query);
			$start = ($start !== false ? (int)$this->arr_query[$start+1] : 0);
			$start = ($start > 0 ? $start : 0);
			$limit = (int)$this->arr_variables['limit'];
			$end = $start + $limit;
			$next = $start - $limit;
			$next = ($next < 0 ? 0 : $next);
			$total = cms_blog_posts::getBlogPostsCount($arr_blog_options['id'], $str_tag);
		
			$arr_posts = cms_blog_posts::getBlogPosts($arr_blog_options['id'], ($blog_post_id ? 3 : $limit), $str_tag, $start);
			
			if ($blog_post_id) {
				unset($arr_posts[$blog_post_id]); // Remove blog post from preview list if showing
			}
			
			if ($arr_posts) {
			
				if ($blog_post_id) {
					$return .= '<h1>'.getLabel('ttl_latest_blog_posts').'</h1>';
				}
			
				$arr_link = ['page_name' => SiteStartVars::$page_name, 'id' => $this->mod_id];
				
				foreach ($arr_posts as $arr_post) {
					$return .= self::createBlogPostPreview($arr_post, $arr_link, $arr_comments_link);
				}
				
				if (!$blog_post_id) {
					
					if ($str_tag) {
						$str_tag = str_replace(' ', '+', strEscapeHTML(Labels::parseTextVariables($str_tag)));
					}
					
					if ($total > $end) {
						
						$next_prev .= '<a class="prev" href="'.SiteStartVars::getModUrl($this->mod_id).($str_tag ? 'tag/'.$str_tag.'/' : '').'go/'.$end.'">'
							.'<span class="icon" data-category="increase">'.getIcon('prev').getIcon('prev').'</span>'
							.'<span>'.getLabel('lbl_previous').'</span>'
						.'</a>';
					}
					if (($end - $limit) > 0) {
						
						$next_prev .= '<a class="next" href="'.SiteStartVars::getModUrl($this->mod_id).($str_tag ? 'tag/'.$str_tag.'/' : '').'go/'.$next.'">'
							.'<span>'.getLabel('lbl_next').'</span>'
							.'<span class="icon" data-category="increase">'.getIcon('next').getIcon('next').'</span>'
						.'</a>';	
					}
					if ($next_prev) {
						$return .= '<nav class="nextprev">'.$next_prev.'</nav>';
					}
				}
			}
		}
										
		return $return;
	}
		
	public static function css() {
	
		$return = '.blog {  }
				.blog > article { position: relative; overflow: hidden; }
				.blog > article + h1 { margin-top: 25px; }
				.blog > article > time { display: inline-block; vertical-align: top; width: 50px; padding: 8px; box-sizing: border-box; }
				.blog > article > time span:first-child { font-size: 1.0rem; }
				.blog > article > time span:first-child + span { font-size: 1.5rem; }
				.blog > article > time span:first-child + span + span { font-size: 1.2rem; }
				.blog > article > h1 { display: inline-block; vertical-align: top; margin: 0px; margin-left: 10px; margin-top: 10px; width: calc(100% - 60px); box-sizing: border-box; }
				.blog > article > h1 > a,
				.blog > article > h1 > a:hover { text-decoration: none; color: #000000; }
				.blog > article > section.body { margin-top: 15px; }
				.blog > article > section.body .more { margin-left: 4px; color: #009cff; }
				.blog > article > div.tags { text-align: left; min-height: 16px; margin-top: 15px; }
				.blog > article > div.tags > span.icon { margin-right: 5px; color: #666666; }
				.blog > article > div.tags > span.icon svg { height: 1.3em; }
				.blog > article > div.tags a { color: #666666; text-decoration: none; font-size: 11px; }
				.blog > article > div.tags a:hover { color: #000000; text-decoration: underline; }
				.blog > article > a { display: block; text-align: right; white-space: nowrap; }
				.blog > article > a > span { display: inline-block; }
				.blog > article > a > span + span { margin-left: 5px; }
				.blog > article > a,
				.blog > article > section.body a.more { text-decoration: none; color: #009cff; }
				.blog > article > a:hover span,
				.blog > article > section.body a.more:hover { text-decoration: underline; }
				.blog .nextprev { text-align: center; margin: 40px 0px 0px 0px;	font-size: 14px; font-weight: bold; }
				.blog .nextprev > a > span.icon svg { height: 0.9em; }
				.blog .nextprev > a > span.icon { color: #009cff; }
				.blog .nextprev > a > span + span { margin-left: 10px; }
				.blog .nextprev > a > span:not(.icon) { vertical-align: middle; }
				.blog .nextprev > a.prev + a.next { margin-left: 40px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY
				

	}
	
	public function createBlogPost($arr_post) {
	
		$body = parseBody($arr_post['body']);
		
		$arr_content_identifiers = [];
		
		$html_tags = '';
		$arr_tags = $arr_post['tags'];
		
		foreach ($arr_tags as $str_tag) {
			
			$arr_content_identifiers['tag'][$str_tag] = $str_tag;
			
			$str_tag = strEscapeHTML(Labels::parseTextVariables($str_tag));
				
			$html_tags .= '<a title="'.getLabel('lbl_tags').'" href="'.SiteStartVars::getModUrl($this->mod_id).'tag/'.str_replace(' ', '+', $str_tag).'" data-tag="'.$str_tag.'">'.$str_tag.'</a> ';
		}
				
		$return .= '<article'.($arr_content_identifiers ? ' data-content="'.createContentIdentifier($arr_content_identifiers).'"' : '').'>'
			.createDate($arr_post['date'])
			.'<h1>'.strEscapeHTML(Labels::parseTextVariables($arr_post['title'])).'</h1>'
			.'<cite>'.$arr_post['cms_user_name'].'</cite>'
			.'<section class="body">'.$body.'</section>';
			
			if ($html_tags) {
				
				$return .= '<div class="tags"><span class="icon">'.getIcon('tags').'</span>'.$html_tags.'</div>';
			}
			
		$return .= '</article>';
		
		return $return;
	}
	
	public static function createBlogPostPreview($arr_post, $arr_link, $arr_comments_link) {
		
		$str_title = Labels::parseTextVariables($arr_post['title']);
		
		$str_title_url = ($arr_link ? SiteStartVars::getModUrl($arr_link['id'], $arr_link['page_name'], $arr_link['sub_dir']).$arr_post['id'].'/'.str2URL($str_title) : '');
		
		$body = parseBody($arr_post['body'], ['extract' => $arr_post['para_preview'], 'append' => ($arr_post['para_preview'] ? ($arr_link ? '<a href="'.$str_title_url.'" class="more" title="'.getLabel('lbl_read_more').'">[....]</a>' : '<span class="more">[....]</span>') : '')]);
		
		$arr_content_identifiers = [];
		
		$html_tags = '';
		$arr_tags = $arr_post['tags'];
		
		foreach ($arr_tags as $str_tag) {
			
			$arr_content_identifiers['tag'][$str_tag] = $str_tag;
			
			$str_tag = strEscapeHTML(Labels::parseTextVariables($str_tag));
				
			$html_tags .= '<a title="'.getLabel('lbl_tags').'"'.($arr_link ? ' href="'.SiteStartVars::getModUrl($arr_link['id'], $arr_link['page_name'], $arr_link['sub_dir']).'tag/'.str_replace(' ', '+', $str_tag).'"': '').' data-tag="'.$str_tag.'">'.$str_tag.'</a> ';
		}
			
		$return = '<article class="preview"'.($arr_content_identifiers ? ' data-content="'.createContentIdentifier($arr_content_identifiers).'"' : '').'>'
			.createDate($arr_post['date'])
			.'<h1>'.($arr_link ? '<a href="'.$str_title_url.'">'.strEscapeHTML($str_title).'</a>' : strEscapeHTML($str_title)).'</h1>'
			.'<cite>'.$arr_post['cms_user_name'].'</cite>'
			.'<section class="body">'.$body.'</section>';

			if ($html_tags) {
				
				$return .= '<div class="tags"><span class="icon">'.getIcon('tags').'</span>'.$html_tags.'</div>';
			}
			
			if ($arr_link) {
				
				$str_url_text = '<span>'.getLabel('lbl_read_more').'</span>'.($arr_comments_link ? '<span>'.getLabel('lbl_comment').'</span>' : '');
				$return .= '<a href="'.$str_title_url.'">'.$str_url_text.'</a>';
			}
			
		$return .= '</article>';
		
		return $return;
	}
		
	private function generateRSS($arr_posts) {
		
		$title = getLabel('name', 'D');
		$descr = getLabel('description', 'D');
		$title = Labels::printLabels($title);
		$descr = Labels::printLabels($descr);
		
		$return .= '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0">
		<channel>
			<title>'.strEscapeXML($title).'</title>
			<link>'.URL_BASE.'</link>
			<description>'.strEscapeXML($descr).'</description>
			<language>en</language>
			<managingEditor>'.getLabel('email', 'D').'</managingEditor>
			<webMaster>'.getLabel('email', 'D').'</webMaster>
			<generator>Custom RSS</generator>
			<docs>http://cyber.law.harvard.edu/rss/rss.html</docs>
			<copyright>Copyright '.strEscapeXML($title).'</copyright>
			<lastBuildDate>'.date("r").'</lastBuildDate>';

			foreach ($arr_posts as $row) {
			
				$body = $row['body'];
				$body = parseBody($body, ['extract' => $row['para_preview'], 'append' => ($row['para_preview'] ? '<span class="more">.....</span>' : '')]);
				
				$arr_link = ['page_name' => SiteStartVars::$page_name, 'id' => $this->mod_id];
				
				$str_link = SiteStartVars::getModUrl($arr_link['id'], $arr_link['page_name'], $arr_link['sub_dir'], false).$row['id'].'/'.str2URL($row['title']);
			
				$return .= '<item>
					<title>'.strEscapeXML(Labels::parseTextVariables($row['title'])).'</title>
					<description>'.$body.'</description>
					<pubDate>'.date("r", strtotime($arr['date'])).'</pubDate>
					<link>'.$str_link.'</link>
					<guid>'.$str_link.'</guid>
				</item>';
			}

		$return .= '</channel>
		</rss>';
		
		return $return;
	}
	
	public static function findMainBlog($id = 0) {
		
		if ($id) {
			return pages::getClosestMod('blog', 0, 0, 0, $id, 'id');
		} else {
			return pages::getClosestMod('blog', SiteStartVars::$dir['id'], SiteStartVars::$page['id'], 0, $id, 'id');
		}
	}
}
