<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class blog extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_blog');
		static::$parent_label = getLabel('lbl_communication');
	}
		
	public static function moduleVariables() {
		
		$arr_return = [
			getLabel('lbl_blog') => '<select name="id">'.cms_general::createDropdown(cms_blogs::getBlogs()).'</select>'
		];
		
		$str_html = '<select name="limit">';
		for ($i = 1; $i <= 10; $i++) {
			$str_html .= '<option value="'.$i.'">'.$i.'</option>';
		}
		$str_html .= '</select>';
		
		$arr_return[getLabel('lbl_limit')] = $str_html;

		return $arr_return;
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
				return $arr_result['object_link'].'/'.str2URL($arr_result['title']);
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
			
			$rss = $this->generateRSS(cms_blog_posts::getBlogPosts($this->arr_variables['id'], false, 10));
			
			Response::stop($rss, '');
		}
		
		$blog_post_id = (int)$this->arr_query[0];
		$str_blog_url = SiteStartEnvironment::getShortestModuleURL($this->mod_id, false, $this->arr_mod['shortcut'], $this->arr_mod['shortcut_root'], 0, false);
		
		SiteEndEnvironment::addHeadTag('<link rel="alternate" title="RSS Feed" type="application/rss+xml" href="'.$str_blog_url.'rss" />');
		Response::addHeaders('X-Pingback: '.SiteStartEnvironment::getModuleURL($this->mod_id, false, 0, false).'pingback');
		
		SiteEndEnvironment::setModuleVariables($this->mod_id);
		
		$str_html = '';
		
		if ($blog_post_id) {
			
			$arr_blog_post = cms_blog_posts::getBlogPosts($this->arr_variables['id'], $blog_post_id);
			
			if (!$arr_blog_post) {
				Response::location(SiteStartEnvironment::getPageURL());
			}
			
			$str_html .= $this->createBlogPost($arr_blog_post);
			
			$str_title = Labels::parseTextVariables($arr_blog_post['title']);
			$str_title_url = str2URL($str_title);
			
			SiteEndEnvironment::addTitle($str_title);
			SiteEndEnvironment::setType('article');
			
			if ($arr_blog_post['abstract']) {
				SiteEndEnvironment::addDescription($arr_blog_post['abstract']);
			}
			
			if ($arr_blog_post['tags']) {
				
				$arr_tags = $arr_blog_post['tags'];
				
				SiteEndEnvironment::addKeywords($arr_tags);
				
				foreach ($arr_tags as $str_tag) {
					SiteEndEnvironment::addContentIdentifier('tag', $str_tag);
				}
			}
			
			if ($this->arr_mod['shortcut']) {
				SiteEndEnvironment::setShortcut($this->mod_id, $this->arr_mod['shortcut'], $this->arr_mod['shortcut_root']);
			}
			
			SiteEndEnvironment::setModuleVariables($this->mod_id, [$arr_blog_post['id'], $str_title_url]);
		}
									
		$arr_comments_link = blog_post_comments::findBlogPostComments();

		if (!$arr_comments_link || !$blog_post_id) {
		
			$arr_blog_options = cms_blogs::getBlogs($this->arr_variables['id']);
				
			$str_tag = ($this->arr_query[0] == 'tag' ? $this->arr_query[1] : false);
			$str_tag_url = false;
			
			if ($str_tag) {
				
				$str_tag = str_replace('+', ' ', $str_tag);
				$arr_tags = cms_general::getTags(DB::getTable('TABLE_BLOG_POSTS'), DB::getTable('TABLE_BLOG_POST_TAGS'), 'blog_post_id', false, $str_tag);

				if ($arr_tags) {
					
					$str_tag = $arr_tags[0]['name'];
					$str_tag_view = Labels::parseTextVariables($str_tag);
					$str_tag_url = str_replace(' ', '+', $str_tag);
				
					SiteEndEnvironment::addTitle(getLabel('lbl_tag'));
					SiteEndEnvironment::addTitle($str_tag_view);
					
					SiteEndEnvironment::addContentIdentifier('tag', $str_tag);
					SiteEndEnvironment::setModuleVariables($this->mod_id, ['tag', $str_tag_url]);
					
					if ($this->arr_mod['shortcut']) {
						SiteEndEnvironment::setShortcut($this->mod_id, $this->arr_mod['shortcut'], $this->arr_mod['shortcut_root']);
					}
				} else {
					
					$str_tag = false;
				}
			}
			
			$num_go = array_search('go', $this->arr_query);
			$num_start = 0;
			
			if ($num_go !== false) {
				
				$num_start = (int)$this->arr_query[$num_go+1];
				$num_start = ($num_start > 0 ? $num_start : 0);
				
				if ($num_start) {
					SiteEndEnvironment::setModuleVariables($this->mod_id, ['go', $num_start], false);
				}
			}
			
			$num_limit = (int)$this->arr_variables['limit'];
			$num_end = $num_start + $num_limit;
			$num_next = $num_start - $num_limit;
			$num_next = ($num_next < 0 ? 0 : $num_next);
			$num_total = cms_blog_posts::getBlogPostsCount($arr_blog_options['id'], $str_tag);
		
			$arr_posts = cms_blog_posts::getBlogPosts($arr_blog_options['id'], false, ($blog_post_id ? 3 : $num_limit), $num_start, $str_tag);
			
			if ($blog_post_id) {
				unset($arr_posts[$blog_post_id]); // Remove blog post from preview list if showing
			}
			
			if ($arr_posts) {
				
				$arr_labels = [
					'latest' => getLabel('lbl_blog_posts_latest'),
					'previous' => getLabel('lbl_previous'),
					'next' => getLabel('lbl_next')
				];
				Settings::get('hook_blog_labels', false, [$this->arr_mod, &$arr_labels]);
			
				if ($blog_post_id) {
					$str_html .= '<h1>'.$arr_labels['latest'].'</h1>';
				}
			
				$arr_link = ['page_name' => SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_PAGE_NAME), 'id' => $this->mod_id];
				
				foreach ($arr_posts as $arr_post) {
					$str_html .= $this->createBlogPostPreview($arr_post, $arr_link, $arr_comments_link);
				}
				
				if (!$blog_post_id) {
										
					$str_html_next_prev = '';
					
					if ($num_total > $num_end) {
						
						$str_html_next_prev .= '<a class="prev" href="'.SiteStartEnvironment::getModuleURL($this->mod_id).($str_tag ? 'tag/'.strEscapeHTML($str_tag_url).'/' : '').'go/'.$num_end.'">'
							.'<span class="icon" data-category="increase">'.getIcon('prev').getIcon('prev').'</span>'
							.'<span>'.$arr_labels['previous'].'</span>'
						.'</a>';
					}
					if (($num_end - $num_limit) > 0) {
						
						$str_html_next_prev .= '<a class="next" href="'.SiteStartEnvironment::getModuleURL($this->mod_id).($str_tag ? 'tag/'.strEscapeHTML($str_tag_url).'/' : '').'go/'.$num_next.'">'
							.'<span>'.$arr_labels['next'].'</span>'
							.'<span class="icon" data-category="increase">'.getIcon('next').getIcon('next').'</span>'
						.'</a>';	
					}
					if ($str_html_next_prev) {
						$str_html .= '<nav class="nextprev">'.$str_html_next_prev.'</nav>';
					}
				}
			}
		}
										
		return $str_html;
	}
		
	public static function css() {
	
		$return = '.blog {  }
				.blog > article { position: relative; overflow: hidden; margin-top: 20px; }
				.blog > article + h1 { margin-top: 25px; }
				.blog > article > time { display: inline-block; vertical-align: top; padding: 8px; }
				.blog > article > h1 { display: inline-block; vertical-align: top; margin: 0px; margin-left: 10px; margin-top: 10px; max-width: calc(100% - 100px); box-sizing: border-box; }
				.blog > article > h1 > a,
				.blog > article > h1 > a:hover { text-decoration: none; color: #000000; }
				.blog > article > cite { display: block; }
				.blog > article > section.body { margin-top: 15px; }
				.blog > article > section.body .more { margin-left: 4px; }
				.blog > article > div.tags { margin-top: 15px; }
				.blog > article > a { display: block; text-align: right; white-space: nowrap; }
				.blog > article > a > span { display: inline-block; text-decoration: inherit; }
				.blog > article > a > span + span { margin-left: 5px; }
				.blog .nextprev { text-align: center; margin: 40px 0px 0px 0px;	font-size: 14px; font-weight: bold; }
				.blog .nextprev > a > span.icon svg { height: 0.9em; }
				.blog .nextprev > a > span.icon { color: var(--highlight); }
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
	
		$str_title = strEscapeHTML(Labels::parseTextVariables($arr_post['title']));
		$str_body = parseBody($arr_post['body']);
		
		$arr_content_identifiers = [];
		$str_html_tags = '';
		$arr_tags = $arr_post['tags'];
		
		if ($arr_tags) {
			
			foreach ($arr_tags as $str_tag) {
				$arr_content_identifiers['tag'][$str_tag] = $str_tag;
			}
			
			$str_html_tags = cms_general::createViewTags($arr_tags, SiteStartEnvironment::getModuleURL($this->mod_id).'tag/');
		}
		
		$html = '<article'.($arr_content_identifiers ? ' data-content="'.createContentIdentifier($arr_content_identifiers).'"' : '').'>'
			.createDate($arr_post['date'])
			.'<h1>'.$str_title.'</h1>'
			.'<cite>'.$arr_post['cms_user_name'].'</cite>'
			.'<section class="body">'.$str_body.'</section>'
			.$str_html_tags
		.'</article>';
		
		return $html;
	}
	
	public function createBlogPostPreview($arr_post, $arr_link, $arr_comments_link) {
		
		$str_title = Labels::parseTextVariables($arr_post['title']);
		$str_url_base = '';
		$str_url_title = '';
		
		if ($arr_link) {
			
			$str_url_base = SiteStartEnvironment::getModuleURL($arr_link['id'], $arr_link['page_name'], $arr_link['sub_dir']);
			$str_url_title = $str_url_base.$arr_post['id'].'/'.str2URL($str_title);
		}
		
		$arr_labels = [
			'read_more' => getLabel('lbl_read_more'),
			'comment' => getLabel('lbl_comment')
		];
		Settings::get('hook_blog_post_labels', false, [$this->arr_mod, &$arr_labels]);
		
		$str_body = parseBody($arr_post['body'], [
			'extract' => $arr_post['para_preview'],
			'append' => ($arr_post['para_preview'] ? ($str_url_title ? '<a href="'.$str_url_title.'" class="more" title="'.$arr_labels['read_more'].'">[....]</a>' : '<span class="more">[....]</span>') : '')
		]);
		
		$arr_content_identifiers = [];
		$str_html_tags = '';
		$arr_tags = $arr_post['tags'];
		
		if ($arr_tags) {
			
			foreach ($arr_tags as $str_tag) {
				$arr_content_identifiers['tag'][$str_tag] = $str_tag;
			}
			
			$str_html_tags = cms_general::createViewTags($arr_tags, ($str_url_base ? $str_url_base.'tag/' : false));
		}
		
		$str_title = strEscapeHTML($str_title);
		$str_link = '';
		
		if ($arr_link) {
			
			$str_title = '<a href="'.$str_url_title.'">'.$str_title.'</a>';
			$str_url_text = '<span>'.$arr_labels['read_more'].'</span>'.($arr_comments_link ? '<span>'.$arr_labels['comment'].'</span>' : '');
			$str_link = '<a class="more" href="'.$str_url_title.'">'.$str_url_text.'</a>';
		}
			
		$html = '<article class="preview"'.($arr_content_identifiers ? ' data-content="'.createContentIdentifier($arr_content_identifiers).'"' : '').'>'
			.createDate($arr_post['date'])
			.'<h1>'.$str_title.'</h1>'
			.'<cite>'.$arr_post['cms_user_name'].'</cite>'
			.'<section class="body">'.$str_body.'</section>'
			.$str_html_tags
			.$str_link
		.'</article>';
		
		return $html;
	}
		
	private function generateRSS($arr_posts) {
		
		$str_title = getLabel('name', 'D');
		$str_descr = getLabel('description', 'D');
		$str_title = Labels::printLabels($str_title);
		$str_descr = Labels::printLabels($str_descr);
		
		$html = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0">
		<channel>
			<title>'.strEscapeXML($str_title).'</title>
			<link>'.URL_BASE.'</link>
			<description>'.strEscapeXML($str_descr).'</description>
			<language>en</language>
			<managingEditor>'.getLabel('email', 'D').'</managingEditor>
			<webMaster>'.getLabel('email', 'D').'</webMaster>
			<generator>Custom RSS</generator>
			<docs>http://cyber.law.harvard.edu/rss/rss.html</docs>
			<copyright>Copyright '.strEscapeXML($str_title).'</copyright>
			<lastBuildDate>'.date('r').'</lastBuildDate>';

			foreach ($arr_posts as $arr_post) {
			
				$str_body = $arr_post['body'];
				$str_body = parseBody($str_body, ['extract' => $arr_post['para_preview'], 'append' => ($arr_post['para_preview'] ? '<span class="more">.....</span>' : '')]);
				
				$arr_link = ['page_name' => SiteStartEnvironment::getContext(SiteStartEnvironment::CONTEXT_PAGE_NAME), 'id' => $this->mod_id];
				
				$str_link = SiteStartEnvironment::getModuleURL($arr_link['id'], $arr_link['page_name'], $arr_link['sub_dir'], false).$arr_post['id'].'/'.str2URL($arr_post['title']);
			
				$html .= '<item>
					<title>'.strEscapeXML(Labels::parseTextVariables($arr_post['title'])).'</title>
					<description>'.$str_body.'</description>
					<pubDate>'.date('r', strtotime($arr_post['date'])).'</pubDate>
					<link>'.$str_link.'</link>
					<guid>'.$str_link.'</guid>
				</item>';
			}

		$html .= '</channel>
		</rss>';
		
		return $html;
	}
	
	public static function findMainBlog($id = 0) {
		
		if ($id) {
			return pages::getClosestModule('blog', 0, 0, 0, $id, 'id');
		} else {
			return pages::getClosestModule('blog', SiteStartEnvironment::getDirectory('id'), SiteStartEnvironment::getPage('id'), 0, false, false);
		}
	}
}
