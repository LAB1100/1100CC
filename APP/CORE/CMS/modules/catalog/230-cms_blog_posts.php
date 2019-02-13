<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_BLOG_POSTS', DB::$database_home.'.def_blog_posts');
DB::setTable('TABLE_BLOG_POST_LINK', DB::$database_home.'.def_blog_post_link');
DB::setTable('TABLE_BLOG_POST_XREFS', DB::$database_home.'.data_blog_post_xrefs');
DB::setTable('TABLE_BLOG_POST_TAGS', DB::$database_home.'.def_blog_post_tags');

class cms_blog_posts extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_blog_posts');
		static::$parent_label = getLabel('ttl_content');
	}
	
	public static function mediaLocations() {
		return [
			'TABLE_BLOG_POSTS' => [
				'body',
				'abstract'
			]
		];
	}

	public function contents() {
		
		$return .= '<div class="section"><h1 id="x:cms_blog_posts:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup blog_post_add" value="add" /></h1>
		<div class="blog_posts">';

			$return .= '<table class="display" id="d:cms_blog_posts:blog_posts_data-0">
					<thead> 
						<tr>
							<th><span title="'.getLabel('lbl_enabled').'">E</span></th>
							<th class="max">'.getLabel('lbl_title').'</th>
							<th class="max">'.getLabel('lbl_posted_by').'</th>
							<th data-sort="desc-0">'.getLabel('lbl_date').'</th>';
							$arr_blogs = cms_blogs::getBlogs();
							if (count($arr_blogs) > 1) {
								foreach ($arr_blogs as $blog) {
									$return .= '<th>B: '.$blog['name'].'</th>';
								}
							}
							$return .= '<th class="disable-sort"></th>
						</tr> 
					</thead>
					<tbody>
						<tr>
							<td colspan="'.(count($arr_blogs)+3).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
					</table>';
						
		$return .= '</div></div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '#frm-blog_post input[name=title] { width: 250px; }
					#frm-blog_post textarea[name=abstract] { width: 400px; height: 50px; }
					#frm-blog_post .icon[id*=get_pingback_box] { cursor: pointer; }
					#frm-blog_post .icon[id*=get_pingback_box] + div { display: inline-block; margin-left: 4px; }
					#frm-blog_post ul#pingbacklist { }
					#frm-blog_post ul#pingbacklist li { display: block; padding: 1px 0px;}
					#frm-blog_post ul#pingbacklist li a.inactive { color: #cccccc; }
					#frm-blog_post ul#pingbacklist li a.double { color: #be0000; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('#frm-blog_post', function(elm_scripter) {
			
			elm_scripter.on('click', '[id^=y\\\:cms_blog_posts\\\:get_pingback_box-]', function() {
				$(this).data('value', elm_scripter.find('textarea[name=body]').val());
				$(this).quickCommand($(this).next('div'));
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "blog_post_edit" || $method == "blog_post_add") {
		
			$arr_tags = [];
			$arr_blogs = cms_blogs::getBlogs();
		
			if ((int)$id) {
													
				$arr_row = self::getBlogPost($id);
				
				$arr_tags = cms_general::getObjectTags(DB::getTable('TABLE_BLOG_POST_TAGS'), 'blog_post_id', $arr_row['id']);
								
				$mode = "blog_post_update";
			} else {
			
				if (!$arr_blogs) {
					
					$this->html = '<section class="info">'.getLabel('msg_no_blogs').'</section>';
					return;
				}
						
				$mode = "blog_post_insert";
			}
														
			$this->html = '<form id="frm-blog_post" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label></label>
						<div>'.cms_general::createSelectorRadio([['id' => '0', 'name' => getLabel('lbl_publish')], ['id' => '1', 'name' => getLabel('lbl_draft')]], 'draft', ($mode == 'blog_post_insert' || $arr_row['draft'])).'</div>
					</li>
				</ul>
				<hr />
				<ul>
					<li>
						<label>'.getLabel('lbl_blog').'</label>';
						
						if (count($arr_blogs) > 1) {
							
							$this->html .= '<div>'.cms_general::createSelector($arr_blogs, 'blog', ($mode == 'blog_post_insert' ? 'all' : self::getBlogPostLinks($arr_row['id']))).'</div>';
						} else if (count($arr_blogs) == 1) {
							
							$arr_row_blog = current($arr_blogs);
							
							$this->html .= '<div><input type="hidden" name="blog['.$arr_row_blog['id'].']" value="1" />'.$arr_row_blog['name'].'</div>';
						}
						
					$this->html .= '</li>
					<li>
						<label>'.getLabel('lbl_title').'</label>
						<div><input type="text" name="title" value="'.htmlspecialchars($arr_row['title']).'"></div>
					</li>
					<li>
						<label>'.getLabel('lbl_date').'</label>
						<div>'.cms_general::createDefineDate($arr_row['date'], '', false).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_body').'</label>
						<div>'.cms_general::editBody($arr_row['body']).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_abstract').'</label>
						<div><textarea name="abstract">'.htmlspecialchars($arr_row['abstract']).'</textarea></div>
					</li>
					<li>
						<label title="'.getLabel('inf_preview_paragraphs').'"">¶</label>
						<div><select name="para_preview"><option value=""></option>';
						
						$selected = ($mode == 'blog_post_update' ? $arr_row['para_preview'] : 2);
						
						for ($i = 1; $i <= 15; $i++) {
							$this->html .= '<option value="'.$i.'"'.($i == $selected ? ' selected="selected"' : '').'>'.$i.'</option>';
						}
						
						$this->html .= '</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_tags').'</label>
						<div>'.cms_general::createSelectTags($arr_tags, '', false).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_pingback').'</label>
						<div><span id="y:cms_blog_posts:get_pingback_box-'.$arr_row['id'].'" class="icon" title="Find URLs supporting pingback">'.getIcon('refresh').'</span><div></div></div>
					</li>
				</ul></fieldset>
				</form>';
			
			$this->validate = ['title' => 'required', 'date' => 'required', 'blog' => 'required'];
		}
		
		// POPUP INTERACT
				
		if ($method == "get_pingback_box") {
			
			$ref_arr = [];
			
			if ($id) {
				
				if (!(int)$id) {
					error('Invalid blog ID!');
				}
			
				$res = DB::query("SELECT
					source
						FROM ".DB::getTable('TABLE_BLOG_POST_XREFS')."
					WHERE direction = 'out' AND blog_post_id = ".(int)$id
				);
				
				while ($arr = $res->fetchArray()) {
					
					array_push($ref_arr, $arr['source']);
				}
			}
			
			$this->html = '<ul id="pingbacklist">';
				
				libxml_use_internal_errors(true);
				$doc = new DOMDocument();
				$doc->strictErrorChecking = false;
				$doc->loadHTML(Labels::printLabels(parseBody($value)));
				$count = 0;
				
				foreach($doc->getElementsByTagName('a') as $link) {
					
					$double = false;
					$inactive = false;
					if (in_array(rawurldecode($link->getAttribute('href')), $ref_arr)) {
						$double = true;
					}
					if (!PingbackUtility::isPingbackEnabled($link->getAttribute('href'))) {
						$inactive = true;
					}
					
					$this->html .= '<li><input type="checkbox" name="pingback-url[]" value="'.$link->getAttribute('href').'"'.($double == false && $inactive == false ? ' checked="checked"' : '').($inactive == true ? ' disabled="disabled"' : '').' /> <a href="'.$link->getAttribute('href').'" target="_blank" class="'.($double == true ? 'double' : '').($inactive == true ? 'inactive' : '').'">'.rawurldecode($link->getAttribute('href')).'</a></li>';
					
					$count++;
				}
				
				if (!$link) {
					$this->html .= '<li>'.getLabel('msg_no_pingbacks').'</li>';
				}
			
			$this->html .= '</ul>';

		}
		
		// DATATABLE
					
		if ($method == "blog_posts_data") {
			
			$arr_sql_columns = ['draft', 'title', 'cu.name', 'date'];
			$arr_sql_columns_search = ['', 'title', 'cu.name', DBFunctions::castAs('date', DBFunctions::CAST_TYPE_STRING)];
			$arr_sql_columns_as = ['draft', 'title', 'cms_user_id', 'cu.name AS cms_user_name', 'date', 'bp.id'];
			
			$arr_blogs = cms_blogs::getBlogs();
			
			foreach ($arr_blogs as $arr_blog) {
				
				$arr_sql_columns[] = 'blog_'.$arr_blog['id'].'.blog_id';
				$arr_sql_columns_as[] = 'blog_'.$arr_blog['id'].'.blog_id AS blog_'.$arr_blog['id'];
			}
			
			$sql_table = DB::getTable('TABLE_BLOG_POSTS').' bp';

			$sql_index = 'bp.id';
			$sql_index_body = 'bp.id, cu.id';
			
			$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_CMS_USERS')." cu ON (cu.id = bp.cms_user_id)";
			
			foreach ($arr_blogs as $arr_blog) {
				
				$sql_index_body .= ', blog_'.$arr_blog['id'].'.blog_id';
				$sql_table .= " LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." blog_".$arr_blog['id']." ON (blog_".$arr_blog['id'].".blog_post_id = bp.id AND blog_".$arr_blog['id'].".blog_id = ".$arr_blog['id'].")";
			}
				 
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', $sql_index_body);
			
			$arr_cms_users_core = [];
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:cms_blog_posts:blog_post_id-'.$arr_row['id'].'';
				$arr_data[] = '<span class="icon" data-category="status">'.getIcon((DBFunctions::unescapeAs($arr_row['draft'], DBFunctions::TYPE_BOOLEAN) ? 'min' : 'tick')).'</span>';
				$arr_data[] = $arr_row['title'];
				
				if ($arr_row['cms_user_name'] === null) {
				
					if (!$arr_cms_users_core[$arr_row['cms_user_id']]) {
						
						$arr_user = cms_users::getCMSUsers($arr_row['cms_user_id'], true);
						$arr_cms_users_core[$arr_row['cms_user_id']] = $arr_user['name'];
					} 
					
					$arr_row['cms_user_name'] = $arr_cms_users_core[$arr_row['cms_user_id']];
				}
				
				$arr_data[] = $arr_row['cms_user_name'];
				$arr_data[] = date('d-m-Y', strtotime($arr_row['date']));
				
				if (count($arr_blogs) > 1) {
					
					foreach ($arr_blogs as $arr_blog) {
						$arr_data[] = ($arr_row['blog_'.$arr_blog['id']] ? '<span class="icon">'.getIcon('linked').'</span>' : '');
					}
				}
				
				$arr_data[] = '<input type="button" class="data edit popup blog_post_edit" value="edit" />'
					.'<input type="button" class="data del msg blog_post_del" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
							
		// QUERY
	
		if ($method == "blog_post_insert") {
		
			$body = $_POST['body'];
			
			$para = ((int)$_POST['para_preview'] ?: '0');
				
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_BLOG_POSTS')."
				(title, body, abstract, cms_user_id, date, para_preview, draft)
					VALUES
				(
					'".DBFunctions::strEscape($_POST['title'])."',
					'".DBFunctions::strEscape($body)."',
					'".DBFunctions::strEscape($_POST['abstract'])."',
					".$_SESSION['USER_ID'].",
					'".date('Y-m-d H:i:s', strtotime($_POST['date'].' '.$_POST['date_t']))."',
					".$para.", ".DBFunctions::escapeAs($_POST['draft'], DBFunctions::TYPE_BOOLEAN)."
				)
			");
						
			$new_id = DB::lastInsertID();
			
			if ($_POST['blog']) {
				
				foreach ($_POST['blog'] as $key => $value) {
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_BLOG_POST_LINK')."
						(blog_id, blog_post_id)
							VALUES
						(".(int)$key.", ".$new_id.")
					");
				
					if ($_POST['pingback-url']) {
						self::doPings($_POST['pingback-url'], $key, $new_id, $_POST['title']);
					}
				}
			}
			
			cms_general::handleTags(DB::getTable('TABLE_BLOG_POST_TAGS'), 'blog_post_id', $new_id, $_POST['tags']);
						
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "blog_post_update" && (int)$id) {
		
			$body = $_POST['body'];
			
			$para = ((int)$_POST['para_preview'] ?: '0');
					
			$res = DB::query("UPDATE ".DB::getTable('TABLE_BLOG_POSTS')." SET
				title = '".DBFunctions::strEscape($_POST['title'])."',
				body = '".DBFunctions::strEscape($body)."',
				abstract = '".DBFunctions::strEscape($_POST['abstract'])."',
				date = '".date('Y-m-d H:i:s', strtotime($_POST['date'].' '.$_POST['date_t']))."',
				para_preview = ".$para.",
				draft = ".DBFunctions::escapeAs($_POST['draft'], DBFunctions::TYPE_BOOLEAN)."
					WHERE id = ".(int)$id."
			");
						
			$res = DB::query("DELETE FROM ".DB::getTable('TABLE_BLOG_POST_LINK')."
				WHERE blog_post_id = ".(int)$id."
			");
			
			if ($_POST['blog']) {
				
				foreach ($_POST['blog'] as $key => $value) {
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_BLOG_POST_LINK')."
						(blog_id, blog_post_id)
							VALUES
						(".(int)$key.", ".(int)$id.")
					");
					
					if ($_POST['pingback-url']) {
						self::doPings($_POST['pingback-url'], $key, $id, $_POST['title']);
					}
				}
			}
						
			cms_general::handleTags(DB::getTable('TABLE_BLOG_POST_TAGS'), 'blog_post_id', $id, $_POST['tags']);
									
			$this->refresh_table = true;
			$this->msg = true;
		}
			
		if ($method == "blog_post_del" && (int)$id) {
		
			$res = DB::queryMulti("
				DELETE FROM ".DB::getTable('TABLE_BLOG_POST_TAGS')."
					WHERE blog_post_id = ".(int)$id."
				;
				DELETE FROM ".DB::getTable('TABLE_BLOG_POST_LINK')."
					WHERE blog_post_id = ".(int)$id."
				;				
				DELETE FROM ".DB::getTable('TABLE_BLOG_POSTS')."
					WHERE id = ".(int)$id."
				;
			");
			
			$this->msg = true;
		}
	}
		
	private static function doPings($arr_pings, $blog, $blog_post_id, $title) {

		$arr_link = self::findMainBlog($blog);

		foreach ($arr_pings as $value) {
			$to = $value;
			if ($arr_link['shortcut']) {
				$from = pages::getShortcutUrl($arr_link);
			} else {
				$from = pages::getModUrl($arr_link);
			}
			$from .= $blog_post_id.'/'.str_replace(' ', '-', htmlspecialchars($title));

			$server = PingbackUtility::getPingbackServerURL($to);

			if (PingbackUtility::sendPingback($from, $to, $server)) {
				$source = rawurldecode($to);
				$ret = DB::query("INSERT INTO ".DB::getTable('TABLE_BLOG_POST_XREFS')." (direction, added, source, blog_post_id) VALUES ('out', NOW(), '".DBFunctions::strEscape($source)."', ".(int)$blog_post_id.")");
			}
		}
	}
	
	private static function findMainBlog($blog_link) {

		return pages::getClosestMod('blog', 0, 0, 0, $blog_link, 'id');
	}
		
	public static function getBlogPostLinks($blog_post_id = 0) {
	
		$arr = [];

		$res = DB::query("SELECT blog_id FROM ".DB::getTable('TABLE_BLOG_POST_LINK')." WHERE blog_post_id = ".(int)$blog_post_id."");
		
		while($row = $res->fetchAssoc()) {
			$arr[] = $row['blog_id'];
		}	

		return $arr;
	}
	
	public static function getBlogPostsCount($blog = 0, $tag = false) {
		
		$res = DB::query("SELECT
			COUNT(bp.id)
				FROM ".DB::getTable('TABLE_BLOG_POSTS')." bp
				JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = bp.id)
				".($tag ? "LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_TAGS')." btsel ON (btsel.blog_post_id = l.blog_post_id)
				LEFT JOIN ".DB::getTable('TABLE_TAGS')." tsel ON (tsel.id = btsel.tag_id)" : "")."
			WHERE l.blog_id = ".(int)$blog."
			AND bp.draft = FALSE
			".($tag ? "AND tsel.name = '".DBFunctions::strEscape($tag)."'" : "")
		);
		
		$arr_row = $res->fetchRow();
		
		return $arr_row[0];
	}
	
	public static function getBlogPosts($blog = 0, $limit = 0, $tag = false, $start = 0) {
	
		$arr = [];
		
		$blog_options = cms_blogs::getBlogs($blog);

		$res = DB::query("SELECT
			bp.*,
			cu.name AS cms_user_name,
			".DBFunctions::sqlImplode('t.name', ',')." AS tags
				FROM ".DB::getTable('TABLE_BLOG_POSTS')." bp
				LEFT JOIN ".DB::getTable('TABLE_CMS_USERS')." cu ON (cu.id = bp.cms_user_id)
				LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = bp.id)
				LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_TAGS')." bt ON (bt.blog_post_id = l.blog_post_id)
				LEFT JOIN ".DB::getTable('TABLE_TAGS')." t ON (t.id = bt.tag_id)
				".($tag ? "LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_TAGS')." btsel ON (btsel.blog_post_id = l.blog_post_id)
				LEFT JOIN ".DB::getTable('TABLE_TAGS')." tsel ON (tsel.id = btsel.tag_id)" : "")."
			WHERE l.blog_id = ".(int)$blog."
			AND bp.draft = FALSE
				".($tag ? "AND tsel.name = '".DBFunctions::strEscape($tag)."'" : "")."
			GROUP BY bp.id, cu.id
			ORDER BY bp.date DESC
			".($start || $limit ? "LIMIT ".$limit." OFFSET ".$start : "")."
		");
		
		$arr_cms_users_core = [];
							
		while ($arr_row = $res->fetchAssoc()) {
			
			if ($arr_row['cms_user_name'] === null) {
				
				if (!$arr_cms_users_core[$arr_row['cms_user_id']]) {
					
					$arr_user = cms_users::getCMSUsers($arr_row['cms_user_id'], true);
					$arr_cms_users_core[$arr_row['cms_user_id']] = $arr_user['name'];
				} 
				
				$arr_row['cms_user_name'] = $arr_cms_users_core[$arr_row['cms_user_id']];
			}
			
			$arr_tags = $arr_row['tags'];
			
			if ($arr_tags) {
			
				$arr_tags = explode(',', $arr_tags);
				$arr_tags = array_combine($arr_tags, $arr_tags);
				ksort($arr_tags);
			}
			
			$arr_row['tags'] = ($arr_tags ?: []);
			
			$arr_row['draft'] = DBFunctions::unescapeAs($arr_row['draft'], DBFunctions::TYPE_BOOLEAN);

			$arr[$arr_row['id']] = $arr_row;
		}
		
		return $arr;
	}
	
	public static function getBlogPost($blog_post_id = 0) {
	
		$res = DB::query("SELECT
			bp.*,
			cu.name AS cms_user_name,
			".DBFunctions::sqlImplode('t.name', ',')." AS tags
				FROM ".DB::getTable('TABLE_BLOG_POSTS')." bp
				LEFT JOIN ".DB::getTable('TABLE_CMS_USERS')." cu ON (cu.id = bp.cms_user_id)
				LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_TAGS')." bt ON (bt.blog_post_id = bp.id)
				LEFT JOIN ".DB::getTable('TABLE_TAGS')." t ON (t.id = bt.tag_id)
			WHERE bp.id = ".(int)$blog_post_id."
			GROUP BY bp.id, cu.id
		");
						
		$arr_row = $res->fetchAssoc();
		
		if ($arr_row['cms_user_name'] === null) {
			
			$arr_row['cms_user_name'] = cms_users::getCMSUsers($arr_row['cms_user_id'], true);
			$arr_row['cms_user_name'] = $arr_row['cms_user_name']['name'];
		}
		
		$arr_tags = $arr_row['tags'];
			
		if ($arr_tags) {
		
			$arr_tags = explode(',', $arr_tags);
			$arr_tags = array_combine($arr_tags, $arr_tags);
			ksort($arr_tags);
		}
		
		$arr_row['tags'] = ($arr_tags ?: []);
		
		$arr_row['draft'] = DBFunctions::unescapeAs($arr_row['draft'], DBFunctions::TYPE_BOOLEAN);
		
		return $arr_row;
	}
}
