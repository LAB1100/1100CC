<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_BLOG_POST_COMMENTS', DB::$database_home.'.data_blog_post_comments');

class cms_blog_post_comments extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_blog_comments');
		static::$parent_label = getLabel('ttl_data');
	}
	
	public static function widgetProperties() {
		return [
			'widgetBlogPostComments' => ['label' => getLabel('lbl_blog_comments')]
		];
	}
	
	public static function logUserLocations() {
		return [
			'TABLE_BLOG_POST_COMMENTS' => 'log_user_id'
		];
	}
	
	public static function widgetBlogPostComments() {
		
		$arr_blog_posts = self::getBlogComments(false, 10);
		$return = '';
		
		if (!$arr_blog_posts) {
			
			Labels::setVariable('name', getLabel('lbl_blog_comments'));
			$msg = getLabel('msg_no', 'L', true);

			$return .= '<section class="info">'.Labels::printLabels(Labels::parseTextVariables($msg)).'</section>';
		} else {
					
			$return .= '<table class="list">
				<thead>
					<tr>
						<th class="limit"><span>'.getLabel('lbl_blog_post').'</span></th>
						<th class="limit"><span>'.getLabel('lbl_name').'</span></th>
						<th class="limit max"><span>'.getLabel('lbl_comment').'</span></th>
						<th><span>'.getLabel('lbl_date').'</span></th>
						<th><span>'.getLabel('lbl_ping').'</span></th>
					</tr>
				</thead>
				<tbody>';
					foreach ($arr_blog_posts as $arr_blog_post) {
						
						$return .= '<tr id="x:cms_blog_post_comments:blog_post_comment_id-'.($arr_blog_post['pingback'] ? 'x' : 'c').'_'.$arr_blog_post['id'].'" class="popup" data-method="blog_post_comment_info">
							<td>'.$arr_blog_post['blog_post_title'].'</td>
							<td>'.strEscapeHTML($arr_blog_post['name']).'</td>
							<td>'.strEscapeHTML($arr_blog_post['body']).'</td>
							<td>'.date('d-m-\'y H:i', strtotime($arr_blog_post['added'])).'</td>
							<td>'.($arr_blog_post['pingback'] ? '<span class="icon">'.getIcon('tick').'</span>' : '').'</td>
						</tr>';
					}
				$return .= '</tbody>
			</table>';
		}
			
		return $return;
	}
	
	public function contents() {
		
		$return = '<div class="section"><h1>'.self::$label.'</h1>
			<div>';
					
			$return .= '<form class="options">
				<label>'.getLabel('lbl_blog').':</label><select name="blog" id="y:cms_blog_post_comments:get_blog_posts-0">'.cms_general::createDropdown(cms_blogs::getBlogs(), 0, true, 'name').'</select>
				<label>'.getLabel('lbl_blog_post').':</label><select name="post"></select>
			</form>';

			$return .= '<table class="display" id="d:cms_blog_post_comments:data_blog_post_comments-0">
				<thead> 
					<tr>				
						<th class="limit"><span>'.getLabel('lbl_blog').'</span></th>
						<th class="limit"><span>'.getLabel('lbl_blog_post').'</span></th>
						<th class="limit max"><span>'.getLabel('lbl_name').'</span></th>
						<th class="limit"><span>'.getLabel('lbl_comment').'</span></th>
						<th data-sort="desc-0"><span>'.getLabel('lbl_date').'</span></th>
						<th><span>'.getLabel('lbl_ping').'</span></th>
						<th class="disable-sort menu" id="x:cms_blog_post_comments:comment_id-0" title="'.getLabel('lbl_multi_select').'">'
							.'<input type="button" class="data del msg del_blog_post_comment" value="del" />'
							.'<input type="checkbox" class="multi all" value="" />'
						.'</th>
					</tr> 
				</thead>
				<tbody>
					<tr>
						<td colspan="7" class="empty">'.getLabel('msg_loading_server_data').'</td>
					</tr>
				</tbody>
			</table>';
						
			$return .= '</div>
		</div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '.blog-post-comment textarea[name=body] { width: 400px; height: 250px; background-color: #ffffff; }
					.blog-post-comment dl .body { padding: 10px; background-color: #f5f5f5; max-width: 1000px; overflow: auto; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-cms_blog_post_comments', function(elm_scripter) {
		
			elm_scripter.on('change', 'select[name=blog]', function() {
					
				var elm_target = $('[id=d\\\:cms_blog_post_comments\\\:data_blog_post_comments-0]');
				
				COMMANDS.setData(elm_target, {blog: $(this).val()});
				$(this).quickCommand($(this).nextAll('select'));
				elm_target.dataTable('refresh');
			}).on('change', 'select[name=post]', function() {
				
				var elm_target = $('[id=d\\\:cms_blog_post_comments\\\:data_blog_post_comments-0]');
				
				COMMANDS.setData(elm_target, {blog: elm_scripter.find('select[name=blog]').val(), post: $(this).val()});
				elm_target.dataTable('refresh');
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// MAIN PAGE
				
		if ($method == "get_blog_posts") {
			
			$this->html .= cms_general::createDropdown(cms_blog_posts::getBlogPosts($value), 0, true, "title");
		}
		
		// POPUP
				
		if ($method == "blog_post_comment_info" && $id) {
		
			$arr_id = explode("_", $id);
			$id = (int)$arr_id[1];
			$type = $arr_id[0];
						
			$arr_blog_post_comment = self::getBlogPostCommentSet($type, $id);
			
			$html_comment = strEscapeHTML($arr_blog_post_comment['body']);
			$html_comment = parseBody($html_comment);
			
			$return = '<div class="blog-post-comment">
				<div class="record"><dl>
					<li>
						<dt>'.getLabel('lbl_blog_post').'</dt>
						<dd><strong>'.$arr_blog_post_comment['blog_post_title'].'</strong></dd>
					</li>
					<li>
						<dt>'.getLabel('lbl_blog').'</dt>
						<dd>'.$arr_blog_post_comment['blog_names'].'</dd>
					</li>
					<li>
						<dt>'.getLabel('lbl_name').'</dt>
						<dd>'.strEscapeHTML($arr_blog_post_comment['name']).'</dd>
					</li>';
					if ($type == 'x') {
						$return .= '<li>
							<dt>'.getLabel('lbl_source').'</dt>
							<dd><a href="'.strEscapeHTML($arr_blog_post_comment['source']).' target="_blank"><span class="icon">'.getIcon('link').'</span></a></dd>
						</li>';
					}
					$return .= '<li>
						<dt>'.getLabel('lbl_date').'</dt>
						<dd>'.date('d-m-Y h:i', strtotime($arr_blog_post_comment['added'])).'</dd>
					</li>
					<li>
						<dt>'.getLabel('lbl_comment').'</dt>
						<dd><div class="body">'.$html_comment.'</div></dd>
					</li>
				</dl></div>
			</div>';
			
			$this->html = $return;
		}
		
		if ($method == "edit_blog_post_comment" && $id) {
			
			$arr_id = explode('_', $id);
			$id = (int)$arr_id[1];
			$type = $arr_id[0];
						
			$arr_blog_post_comment = self::getBlogPostCommentSet($type, $id);
			
			$mode = "update_blog_post_comment";
														
			$return = '<form class="blog-post-comment" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_blog_post').'</label>
						<div><strong>'.$arr_blog_post_comment['blog_post_title'].'</strong></div>
					</li>
					<li>
						<label>'.getLabel('lbl_blog').'</label>
						<div>'.$arr_blog_post_comment['blog_names'].'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($arr_blog_post_comment['name']).'" /></div>
					</li>';
					if ($type == 'x') {
						$return .= '<li>
							<label>'.getLabel('lbl_source').'</label>
							<div><input type="text" name="source" value="'.strEscapeHTML($arr_blog_post_comment['source']).'" /><a href="'.strEscapeHTML($arr_blog_post_comment['source']).'" class="input" target="_blank"><span class="icon">'.getIcon('link').'</span></a></div>
						</li>';
					}
					$return .= '<li>
						<label>'.getLabel('lbl_date').'</label>
						<div>'.cms_general::createDefineDate($arr_blog_post_comment['added'], 'added', false).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_comment').'</label>
						<div><textarea name="body">'.strEscapeHTML($arr_blog_post_comment['body']).'</textarea></div>
					</li>
				</ul></fieldset>
			</form>';
			
			$this->html = $return;
			$this->validate = ['title' => 'required', 'date' => 'required', 'blog' => 'required'];
		}
		
		// POPUP INTERACT
						
		// DATATABLE
					
		if ($method == "data_blog_post_comments") {
			
			$arr_sql_columns = [DBFunctions::sqlImplode('b.name'), 'p.title', 'c.name', 'c.body', 'c.added', 'c.pingback'];
			$arr_sql_columns_search = ['b.name', 'p.title', 'c.name', 'c.body', DBFunctions::castAs('c.added', DBFunctions::CAST_TYPE_STRING), ''];
			$arr_sql_columns_as = [DBFunctions::sqlImplode('b.name').' AS blog_names', 'p.title', 'c.name', 'c.body', 'c.added', 'c.pingback', 'c.id'];
			
			if ($value) {
				if ($value['post']) {
					$sql_filter = "p.id = ".(int)$value['post'];
				} else if ($value['blog']) {
					$sql_filter = "l.blog_id = ".(int)$value['blog'];
				}
			}

			$sql_table = "(
				(
					SELECT
						cc.id, cc.blog_post_id, cc.name, cc.body, cc.added, 0 AS pingback
							FROM ".DB::getTable('TABLE_BLOG_POST_COMMENTS')." cc
							LEFT JOIN ".DB::getTable('TABLE_BLOG_POSTS')." p ON (p.id = cc.blog_post_id)
							LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = p.id)
						".($sql_filter ? "WHERE ".$sql_filter : "")."
				) UNION ALL (
					SELECT
						xx.id, xx.blog_post_id, xx.title AS name, xx.excerpt AS body, xx.added, 1 AS pingback
							FROM ".DB::getTable('TABLE_BLOG_POST_XREFS')." xx
							LEFT JOIN ".DB::getTable('TABLE_BLOG_POSTS')." p ON (p.id = xx.blog_post_id)
							LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = p.id)
						WHERE xx.direction = 'in'
							".($sql_filter ? "AND ".$sql_filter : "")."
				)
			) c";

			$sql_index = 'c.id';
			$sql_index_body = 'c.id, p.id, c.name, c.body, c.added, c.pingback';
			
			$sql_table .= "
				LEFT JOIN ".DB::getTable('TABLE_BLOG_POSTS')." p ON (p.id = c.blog_post_id)
				LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = p.id)
				LEFT JOIN ".DB::getTable('TABLE_BLOGS')." b ON (b.id = l.blog_id)
			";
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', $sql_index_body);
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{

				$arr_data = [];
				
				$arr_data['id'] = 'x:cms_blog_post_comments:blog_post_comment_id-'.($arr_row['pingback'] ? 'x' : 'c').'_'.$arr_row['id'];
				$arr_data['class'] = 'popup';
				$arr_data['attr']['data-method'] = 'blog_post_comment_info';
				
				$arr_data[] = $arr_row['blog_names'];
				$arr_data[] = $arr_row['title'];
				$arr_data[] = strEscapeHTML($arr_row['name']);
				$arr_data[] = strEscapeHTML($arr_row['body']);
				$arr_data[] = date('d-m-Y h:i', strtotime($arr_row['added']));
				$arr_data[] = ($arr_row['pingback'] ? '<span class="icon">'.getIcon('tick').'</span>' : '');
				$arr_data[] = '<input type="button" class="data edit popup edit_blog_post_comment" value="edit" /><input type="button" class="data del msg del_blog_post_comment" value="del" /><input class="multi" value="'.($arr_row['pingback'] ? 'x' : 'c').'_'.$arr_row['id'].'" type="checkbox" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
							
		// QUERY
			
		if ($method == "update_blog_post_comment" && $id) {
		
			$arr_id = explode("_", $id);
			$id = (int)$arr_id[1];
			$type = $arr_id[0];
						
			if ($type == 'c') {
				
				$res = DB::query("UPDATE ".DB::getTable('TABLE_BLOG_POST_COMMENTS')."
					SET name = '".DBFunctions::strEscape($_POST['name'])."', body = '".DBFunctions::strEscape($_POST['body'])."', added = '".date('Y-m-d H:i:s', strtotime($_POST['added'].' '.$_POST['added_t']))."'
					WHERE id = ".(int)$id."
				");
			} else if ($type == 'x') {
				
				$res = DB::query("UPDATE ".DB::getTable('TABLE_BLOG_POST_XREFS')."
					SET title = '".DBFunctions::strEscape($_POST['name'])."', excerpt = '".DBFunctions::strEscape($_POST['body'])."', source = '".DBFunctions::strEscape($_POST['source'])."', added = '".date('Y-m-d H:i:s', strtotime($_POST['added'].' '.$_POST['added_t']))."'
					WHERE id = ".(int)$id."
				");
			}
			$this->refresh_table = true;
			$this->msg = true;
		}
			
		if ($method == "del_blog_post_comment" && $id) {
			
			$arr_ids = [];
			
			if (is_array($id)) {
				
				foreach ($id as $cur_id) {
					$arr_id = explode('_', $cur_id);
					$type = $arr_id[0];
					$arr_ids[$type][] = (int)$arr_id[1];
				}
			} else {
				
				$arr_id = explode('_', $id);
				$type = $arr_id[0];
				$arr_ids[$type][] = (int)$arr_id[1];
			}
							
			if ($arr_ids['c']) {
				
				$res = DB::query("DELETE FROM ".DB::getTable('TABLE_BLOG_POST_COMMENTS')."
								WHERE id IN (".implode(',', $arr_ids['c']).")
				");
			}
			if ($arr_ids['x']) {
				
				$res = DB::query("DELETE FROM ".DB::getTable('TABLE_BLOG_POST_XREFS')."
								WHERE id IN (".implode(',', $arr_ids['x']).")
				");
			}
		
			$this->refresh_table = true;
			$this->msg = true;
		}
	}
	
	public static function getBlogComments($blog_id = 0, $limit = false) {
	
		$arr = [];
		
		if ($blog_id) {
			
			$res = DB::query("(
					SELECT c.id, c.blog_post_id, c.name, c.body, c.added, 0 AS pingback, '' AS source
						FROM ".DB::getTable('TABLE_BLOG_POST_COMMENTS')." c
						LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = c.blog_post_id)
					WHERE l.blog_id = ".(int)$blog_id."
				) UNION ALL (
					SELECT x.id, x.blog_post_id, x.title as name, x.excerpt as body, x.added, 1 AS pingback, x.source
						FROM ".DB::getTable('TABLE_BLOG_POST_XREFS')." x
						LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = x.blog_post_id)
					WHERE direction = 'in'
						AND l.blog_id = ".(int)$blog_id."
				)
				ORDER BY added DESC
				".($limit ? "LIMIT ".$limit : "")."
			");
		} else {
			
			$res = DB::query("(
					SELECT c.id, c.blog_post_id, c.name, c.body, c.added, 0 AS pingback, '' AS source, p.title AS blog_post_title, ".DBFunctions::sqlImplode('b.name')." AS blog_names
						FROM ".DB::getTable('TABLE_BLOG_POST_COMMENTS')." c
						LEFT JOIN ".DB::getTable('TABLE_BLOG_POSTS')." p ON (p.id = c.blog_post_id)
						LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = c.blog_post_id)
						LEFT JOIN ".DB::getTable('TABLE_BLOGS')." b ON (b.id = l.blog_id)
					GROUP BY c.id, p.id
				) UNION ALL (
					SELECT x.id, x.blog_post_id, x.title AS name, x.excerpt AS body, x.added, 1 AS pingback, x.source, p.title AS blog_post_title, ".DBFunctions::sqlImplode('b.name')." AS blog_names
						FROM ".DB::getTable('TABLE_BLOG_POST_XREFS')." x
						LEFT JOIN ".DB::getTable('TABLE_BLOG_POSTS')." p ON (p.id = x.blog_post_id)
						LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = x.blog_post_id)
						LEFT JOIN ".DB::getTable('TABLE_BLOGS')." b ON (b.id = l.blog_id)
					WHERE direction = 'in'
					GROUP BY x.id, p.id
				)
				ORDER BY added DESC
				".($limit ? "LIMIT ".$limit : "")."
			");
		}
							
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr[$arr_row['id']] = $arr_row;
		}
		
		return $arr;
	}
				
	public static function getBlogPostComments($blog_post_id = 0) {
	
		$arr = [];
		
		$res = DB::query("(
				SELECT c.id, c.blog_post_id, c.name, c.body, c.added, 0 AS pingback, '' AS source
					FROM ".DB::getTable('TABLE_BLOG_POST_COMMENTS')." c
					WHERE c.blog_post_id = ".(int)$blog_post_id."
			) UNION ALL (
				SELECT x.id, x.blog_post_id, x.title as name, x.excerpt as body, x.added, 1 AS pingback, x.source
					FROM ".DB::getTable('TABLE_BLOG_POST_XREFS')." x
					WHERE direction = 'in' AND blog_post_id = ".(int)$blog_post_id.")
				ORDER BY added DESC
		");
							
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr[$arr_row['id']] = $arr_row;
		}
		
		return $arr;
	}
	
	public static function getBlogPostCommentSet($type, $blog_post_comment_id) {
		
		$arr = [];
		
		if ($type == 'c') {
			
			$res = DB::query("SELECT
				c.*,
				p.title AS blog_post_title,
				".DBFunctions::sqlImplode('b.name')." AS blog_names
					FROM ".DB::getTable('TABLE_BLOG_POST_COMMENTS')." c
					LEFT JOIN ".DB::getTable('TABLE_BLOG_POSTS')." p ON (p.id = c.blog_post_id)
					LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = p.id)
					LEFT JOIN ".DB::getTable('TABLE_BLOGS')." b ON (b.id = l.blog_id)
				WHERE c.id = ".(int)$blog_post_comment_id."
				GROUP BY c.id, p.id
			");
		} else if ($type == 'x') {
			
			$res = DB::query("SELECT
				x.*,
				p.title AS blog_post_title,
				".DBFunctions::sqlImplode('b.name')." AS blog_names,
				x.title as name,
				x.excerpt as body,
				x.source
					FROM ".DB::getTable('TABLE_BLOG_POST_XREFS')." x
					LEFT JOIN ".DB::getTable('TABLE_BLOG_POSTS')." p ON (p.id = x.blog_post_id)
					LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = p.id)
					LEFT JOIN ".DB::getTable('TABLE_BLOGS')." b ON (b.id = l.blog_id)
				WHERE x.id = ".(int)$blog_post_comment_id." AND direction = 'in'
				GROUP BY x.id, p.id
			");
		}
		
		$arr_row = $res->fetchAssoc();
		
		return $arr_row;
	}
	
	public static function getBlogPostComment($blog_post_comment_id = 0) {
	
		$res = DB::query("SELECT c.*
				FROM ".DB::getTable('TABLE_BLOG_POST_COMMENTS')." c
			WHERE c.id = ".(int)$blog_post_comment_id."
		");
								
		$arr_row = $res->fetchAssoc();
	
		return $arr_row;
	}
}
