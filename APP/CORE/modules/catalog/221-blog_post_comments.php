<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class blog_post_comments extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_blog_comments');
		static::$parent_label = getLabel('lbl_communication');
	}
			
	public static function widgetProperties() {
		return [
			'widgetComments' => ['label' => getLabel('lbl_blog_comments')],
			'widgetTest' => ['label' => getLabel('lbl_blog_comments')]
		];
	}
	
	public static function widgetComments() {

		$return = 'hello';
		
		return $return;
	}
	
	public static function widgetTest() {

		$return = 'hello2';
		
		return $return;
	}
	
	public function contents() {

		$arr_link = blog::findMainBlog();
		$arr_query = SiteStartVars::getModVariables($arr_link['id']);
		
		$blog_post_id = ($arr_query && $arr_query[0] ? (int)$arr_query[0] : false);

		if ($blog_post_id) {
				
			$return .= '<h1>'.getLabel('ttl_comments').'</h1>
			<span class="a">'.getLabel('lbl_add_comment').'</span>
			
			<form id="f:blog_post_comments:add_comment-'.$blog_post_id.'">
				<fieldset><ul>
					<li><label>'.getLabel('lbl_name').'</label><input type="text" name="name" value="'.($_SESSION['CUR_USER'] ? $_SESSION['CUR_USER'][TABLE_USER]['uname'] : '').'" /></li>
					<li><label>'.getLabel('lbl_comment').'</label><textarea name="body"></textarea></li>
					<li><label></label><div><input type="submit" value="Ok" class="invalid" /><input type="submit" value="'.getLabel('lbl_add_comment').'" /><input type="submit" value="Ok" class="invalid" /></div></li>
				</ul></fieldset>
			</form>
			
			<div>';
			
			$arr_blog_post_comments = cms_blog_post_comments::getBlogPostComments($blog_post_id);
			
			foreach ($arr_blog_post_comments as $arr_blog_post_comment) {
			
				$return .= self::createComment($arr_blog_post_comment);
			}
			
			$return .= '</div>';
		}

		return $return;
	}
		
	public static function css() {
	
		$return = '.blog_post_comments .comment { position: relative; margin: 10px 0px 0px 0px; background-color: #eeeeee; }
				.blog_post_comments .comment:first-child { margin-top: 0px; }
				.blog_post_comments .comment > div:first-child { float: left; position: relative; margin-top: 10px; }
				.blog_post_comments .comment > div:first-child + div { padding: 10px 14px 10px 14px; min-height: 50px; margin-left: 90px; }
				.blog_post_comments .comment > div:first-child + div > cite { margin: 4px 0px; display: block; font-size: 1.4rem; font-weight: bold; }
				.blog_post_comments .comment > div:first-child + div > div { margin-top: 4px; }
							
				.blog_post_comments time { display: block; }
				.blog_post_comments time span:first-child,
				.blog_post_comments time span:first-child + span,
				.blog_post_comments time span:first-child + span + span { display: inline-block; font-size: 11px; line-height: 11px; }
				.blog_post_comments time span:first-child ~ span { margin-left: 3px; }

				.blog_post_comments > span.a { display: block; text-decoration: none; color: #009cff; }
				.blog_post_comments > span.a:hover { text-decoration: underline; }
				
				.blog_post_comments form { display: none; margin: 20px 0px; }
				.blog_post_comments form textarea { height: 100px; }
				
				.blog_post_comments form + div { margin: 20px 0px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.blog_post_comments', function(elm_scripter) {
		
			var target = elm_scripter.find('[id^=\"f\\\:blog_post_comments\\\:add_comment\"]');
		
			elm_scripter.on('click', '> h1 + span', function() {
				target.show();
			});
			
			target.data({rules: {'name': 'required', 'body': 'required'}, options: {html: 'prepend', style: 'fade', hide: true}, target: target.next('div')});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY

		if ($method == "add_comment") {

			if (!$_POST['name'] || !$_POST['body']) {
				error(getLabel('msg_missing_information'));
			}
			
			$arr_link = blog::findMainBlog();
			$arr_query = SiteStartVars::getModVariables($arr_link['id']);
			
			if (!$arr_query[0]) {
				error(getLabel('msg_missing_information'));
			}
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_BLOG_POST_COMMENTS')."
				(blog_post_id, name, body, added, log_user_id)
					VALUES
				(".(int)$arr_query[0].", '".DBFunctions::strEscape($_POST['name'])."', '".DBFunctions::strEscape($_POST['body'])."', NOW(), ".Log::addToUserDB().")
			");
			
			$this->reset_form = true;
			$this->html = self::createComment(cms_blog_post_comments::getBlogPostComment(DB::lastInsertID()));
		}
	}
	
	private static function createComment($arr_comment) {
	
		$poster = ($arr_comment['pingback'] ? '<a href="'.strEscapeHTML($arr_comment['source']).'" target="_blank">'.strEscapeHTML($arr_comment['name']).'</a>' : strEscapeHTML($arr_comment['name']));
				
		$html_comment = strEscapeHTML($arr_comment['body']);
		$html_comment = parseBody($html_comment);
		
		$return .= '<div class="comment'.($arr_comment['pingback'] ? ' pingback' : '').'">
			<div><a id="'.($arr_comment['pingback'] ? 'ping' : 'comment').'_'.$arr_comment['id'].'"></a>'.createDate($arr_comment['added']).'</div>
			<div><cite>'.$poster.'</cite><div class="body">'.$html_comment.'</div></div>
		</div>';
		
		return $return;
	}
		
	public static function findBlogPostComments() {

		return pages::getClosestMod('blog_post_comments', SiteStartVars::$dir['id'], SiteStartVars::$page['id']);
	}
}
