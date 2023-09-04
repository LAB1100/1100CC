<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class blog_preview extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_blog_preview');
		static::$parent_label = getLabel('lbl_communication');
	}
	
	public static function moduleVariables() {
		
		$return = '<select name="type">'
			.'<option value="preview">Preview Posts</option>'
			.'<option value="titles">Post Titles</option>'
			.'<option value="comments">Post Comments</option>'
		.'</select>';
		
		$return .= '<select name="limit">';
		for ($i = 2; $i <= 30; $i = $i+2) {
			$return .= '<option value="'.$i.'">'.$i.'</option>';
		}
		$return .= '</select>';
		
		$return .= '<select name="id">'
			.cms_general::createDropdown(cms_blogs::getBlogs(), 0, true)
		.'</select>';
		
		return $return;
	}
	
	public function contents() {

		$return = '<div class="blog '.$this->arr_variables['type'].'">';
		
		$blog_id = ($this->arr_variables['id'] ?: 0);
		
		$arr_link = blog::findMainBlog($blog_id);
		$arr_link_mod_var = json_decode($arr_link['var'], true);
		
		$arr_blog_options = cms_blogs::getBlogs($arr_link_mod_var['id']);
					
		if ($this->arr_variables['type'] == 'preview') {
		
			$arr_comments_link = blog_post_comments::findBlogPostComments();
		
			foreach (cms_blog_posts::getBlogPosts($arr_blog_options['id'], false, $this->arr_variables['limit']) as $row) {
				
				$return .= blog::createBlogPostPreview($row, $arr_link, $arr_comments_link, $arr_comments_link);
			}
			
			$total = cms_blog_posts::getBlogPostsCount($arr_blog_options['id']);
			
			if ($total > $this->arr_variables['limit']) {
				
				$next_prev .= '<a class="prev" href="'.SiteStartVars::getModuleURL($arr_link['id'], $arr_link['page_name'], $arr_link['sub_dir']).'go/'.$this->arr_variables['limit'].'"><span class="icon" data-category="increase">'.getIcon('prev').getIcon('prev').'</span><span>'.getLabel('lbl_previous').'</span></a>';
				
				$return .= '<nav class="nextprev">'.$next_prev.'</nav>';
			}
		} else if ($this->arr_variables['type'] == 'titles') {
		
			$return .= '<h1>'.($this->arr_variables['id'] ? strEscapeHTML(Labels::parseTextVariables($arr_blog_options['name'])) : getLabel('ttl_posts')).'</h1>'
			.'<ul>';
			
				foreach (cms_blog_posts::getBlogPosts($arr_blog_options['id'], false, $this->arr_variables['limit'], 0, false) as $row) {
					
					$title = Labels::parseTextVariables($row['title']);
					$return .= '<li><a title="'.strEscapeHTML($title).'" href="'.SiteStartVars::getModuleURL($arr_link['id'], $arr_link['page_name'], $arr_link['sub_dir']).$row['id'].'/'.str2URL($title).'"><span></span><span>'.strEscapeHTML($title).'</span></a></li>';
				}
				
			$return .= '</ul>';
		} else if ($this->arr_variables['type'] == 'comments') {
		
			$return .= '<h1>'.($this->arr_variables['id'] ? strEscapeHTML(Labels::parseTextVariables($arr_blog_options['name'])).' ' : '').getLabel('ttl_comments').'</h1>'
			.'<ul>';
			
				foreach (cms_blog_post_comments::getBlogComments($arr_blog_options['id'], $this->arr_variables['limit']) as $row) {
					
					$return .= '<li><a title="'.strEscapeHTML($row['name']).' on '.date('d-m-y H:i', strtotime($row['added'])).'" href="'.SiteStartVars::getModuleURL($arr_link['id'], $arr_link['page_name'], $arr_link['sub_dir']).$row['blog_post_id'].'/'.str2URL(Labels::parseTextVariables($row['blog_post_title'])).'#'.$row['id'].'">
						<span></span>
						<span><span>'.strEscapeHTML($row['name']).':</span><span>'.strEscapeHTML($row['body']).'</span></span></a>
					</li>';
				}
				
			$return .= '</ul>';
		}
								
		$return .= '</div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.blog.titles ul,
					.blog.comments ul { margin: 0px; padding: 0px; list-style: none }
					.blog.titles li a,
					.blog.comments li a { display: block; min-height: 22px; line-height: 22px; border-bottom: 1px dashed #c0c0c0; color: #000000; text-decoration: none; }
					.blog.titles li:first-child a,
					.blog.comments li:first-child a { border-top: 1px dashed #c0c0c0; }
					.blog.titles li a span,
					.blog.comments li a span { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
					.blog.titles li a > span:first-child,
					.blog.comments li a > span:first-child { float:left; margin-right: 6px; text-align: center; color: #ffffff; font-weight: bold; width: 4px; height: 22px; }
					.blog.titles li a:hover,
					.blog.comments li a:hover { text-decoration: none;}
					.blog.titles li a:hover > span:first-child,
					.blog.comments li a:hover > span:first-child { background-color: #c0c0c0;}
					.blog.titles li a:hover > span + span,
					.blog.comments li a:hover > span + span { color: #666666;}
					
					.blog.comments li a { height: 38px;}
					.blog.comments li a > span:first-child { height: 38px;}
					.blog.comments li a > span + span > span + span { margin-top: -6px;}';
		
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
