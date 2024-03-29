<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_BLOG_POSTS', DB::$database_home.'.def_blog_posts');
DB::setTable('TABLE_BLOG_POST_LINK', DB::$database_home.'.def_blog_post_link');
DB::setTable('TABLE_BLOG_POST_XREFS', DB::$database_home.'.data_blog_post_xrefs');
DB::setTable('TABLE_BLOG_POST_TAGS', DB::$database_home.'.def_blog_post_tags');

class cms_blog_posts extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_blog_posts');
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
	
	public static function webLocations() {
		
		return [
			'name' => 'blogs',
			'entries' => function() {
				
				$arr_blogs = cms_blogs::getBlogs();

				foreach ($arr_blogs as $arr_blog) {
					
					$arr_link = cms_blogs::findMainBlog($arr_blog['id']);
					
					if (!$arr_link || $arr_link['require_login']) {
						continue;
					}
					
					$str_location_base = pages::getModuleURL($arr_link, true);
					
					$arr_blog_posts = static::getBlogPosts($arr_blog['id']);
					
					foreach ($arr_blog_posts as $arr_blog_post) {
							
						$str_location = $str_location_base.$arr_blog_post['id'].'/'.str2URL($arr_blog_post['title']);
						
						yield $str_location;
					}
				}
			}
		];
	}

	public function contents() {
		
		$return = '<div class="section"><h1 id="x:cms_blog_posts:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup add_blog_post" value="add" /></h1>
		<div class="blog_posts">';
			
			$arr_blogs = cms_blogs::getBlogs();
			$num_columns = 5;
			
			$return .= '<table class="display" id="d:cms_blog_posts:data_blog_posts-0">
				<thead> 
					<tr>
						<th><span title="'.getLabel('lbl_enabled').'">E</span></th>
						<th class="max">'.getLabel('lbl_title').'</th>
						<th class="max">'.getLabel('lbl_posted_by').'</th>
						<th data-sort="desc-0">'.getLabel('lbl_date').'</th>';
				
						if (count($arr_blogs) > 1) {
							
							foreach ($arr_blogs as $arr_blog) {
								$return .= '<th>B: '.$arr_blog['name'].'</th>';
							}
							
							$num_columns += count($arr_blogs);
						}
						
						$return .= '<th class="disable-sort"></th>
					</tr> 
				</thead>
				<tbody>
					<tr>
						<td colspan="'.$num_columns.'" class="empty">'.getLabel('msg_loading_server_data').'</td>
					</tr>
				</tbody>
			</table>';
						
		$return .= '</div></div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '#frm-blog_post input[name=title] { width: 500px; }
					#frm-blog_post textarea[name=abstract] { width: 400px; height: 50px; }
					#frm-blog_post .icon[id*=get_pingback_box] { cursor: pointer; }
					#frm-blog_post .icon[id*=get_pingback_box] + div { display: inline-block; vertical-align:middle; margin-left: 5px; }
					#frm-blog_post ul#pingbacklist { }
					#frm-blog_post ul#pingbacklist li { display: block; padding: 1px 0px;}
					#frm-blog_post ul#pingbacklist li > input + a { margin-left: 4px; }
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
		
		if ($method == "edit_blog_post" || $method == "add_blog_post") {
		
			$arr_tags = [];
			$arr_blogs = cms_blogs::getBlogs();
		
			if ((int)$id) {
													
				$arr_row = self::getBlogPosts(false, $id);
				
				$arr_tags = cms_general::getTagsByObject(DB::getTable('TABLE_BLOG_POST_TAGS'), 'blog_post_id', $arr_row['id']);
								
				$mode = "update_blog_post";
			} else {
			
				if (!$arr_blogs) {
					
					Labels::setVariable('name', getLabel('lbl_blogs'));
			
					$this->html = '<section class="info">'.getLabel('msg_no', 'L', true).'</section>';
					return;
				}
						
				$mode = "insert_blog_post";
			}
														
			$this->html = '<form id="frm-blog_post" data-method="'.$mode.'" data-lock="1">
				<fieldset><ul>
					<li>
						<label></label>
						<div>'.cms_general::createSelectorRadio([['id' => '0', 'name' => getLabel('lbl_publish')], ['id' => '1', 'name' => getLabel('lbl_draft')]], 'draft', ($mode == 'insert_blog_post' || $arr_row['draft'])).'</div>
					</li>
				</ul>
				<hr />
				<ul>
					<li>
						<label>'.getLabel('lbl_blog').'</label>';
						
						if (count($arr_blogs) > 1) {
							
							$this->html .= '<div>'.cms_general::createSelector($arr_blogs, 'blog', ($mode == 'insert_blog_post' ? 'all' : self::getBlogPostLinks($arr_row['id']))).'</div>';
						} else if (count($arr_blogs) == 1) {
							
							$arr_blog = current($arr_blogs);
							
							$this->html .= '<div><input type="hidden" name="blog['.$arr_blog['id'].']" value="1" />'.$arr_blog['name'].'</div>';
						}
						
					$this->html .= '</li>
					<li>
						<label>'.getLabel('lbl_title').'</label>
						<div><input type="text" name="title" value="'.strEscapeHTML($arr_row['title']).'"></div>
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
						<div><textarea name="abstract">'.strEscapeHTML($arr_row['abstract']).'</textarea></div>
					</li>
					<li>
						<label title="'.getLabel('inf_preview_paragraphs').'"">¶</label>
						<div><select name="para_preview"><option value=""></option>';
						
						$selected = ($mode == 'update_blog_post' ? $arr_row['para_preview'] : 2);
						
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

				$doc = new DOMDocument();
				$doc->strictErrorChecking = false;
				
				$cur_format = Response::getFormat();
				Response::setFormat(Response::OUTPUT_XML | Response::RENDER_HTML);
					
				try {

					$doc->loadHTML(Response::parse(parseBody($value)));
				} catch (Exception $e) {
					
				}
				
				Response::setFormat($cur_format); // Restore output format
				
				$num_count = 0;
				
				foreach($doc->getElementsByTagName('a') as $link) {
					
					$double = false;
					$inactive = false;
					if (in_array(rawurldecode($link->getAttribute('href')), $ref_arr)) {
						$double = true;
					}
					if (!PingbackUtility::isPingbackEnabled($link->getAttribute('href'))) {
						$inactive = true;
					}
					
					$this->html .= '<li><input type="checkbox" name="pingback-url[]" value="'.$link->getAttribute('href').'"'.($double == false && $inactive == false ? ' checked="checked"' : '').($inactive == true ? ' disabled="disabled"' : '').' /><a href="'.$link->getAttribute('href').'" target="_blank" class="'.($double == true ? 'double' : '').($inactive == true ? 'inactive' : '').'">'.rawurldecode($link->getAttribute('href')).'</a></li>';
					
					$num_count++;
				}
				
				if (!$link) {
					$this->html .= '<li>'.getLabel('msg_no_pingbacks').'</li>';
				}
			
			$this->html .= '</ul>';

		}
		
		// DATATABLE
					
		if ($method == "data_blog_posts") {
			
			$arr_sql_columns = ['draft', 'title', 'cu.name', 'date'];
			$arr_sql_columns_search = ['', 'title', 'cu.name', DBFunctions::castAs('date', DBFunctions::CAST_TYPE_STRING), 'body'];
			$arr_sql_columns_as = ['draft', 'title', 'cms_user_id', 'cu.name AS cms_user_name', 'date', 'bp.id'];
			
			$arr_blogs = cms_blogs::getBlogs();
			$arr_urls_base = [];
			
			foreach ($arr_blogs as $arr_blog) {
				
				$arr_link = cms_blogs::findMainBlog($arr_blog['id']);
				$arr_urls_base[$arr_blog['id']] = pages::getModuleURL($arr_link);

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
				
				if (!isset($arr_row['cms_user_name'])) {
				
					if (!isset($arr_cms_users_core[$arr_row['cms_user_id']])) {
						
						$arr_user = cms_users::getCMSUsers($arr_row['cms_user_id'], true);
						$arr_cms_users_core[$arr_row['cms_user_id']] = $arr_user['name'];
					} 
					
					$arr_row['cms_user_name'] = $arr_cms_users_core[$arr_row['cms_user_id']];
				}
				
				$arr_data[] = $arr_row['cms_user_name'];
				$arr_data[] = date('d-m-Y', strtotime($arr_row['date']));
				
				if (count($arr_blogs) > 1) {
					
					foreach ($arr_blogs as $arr_blog) {
						
						if ($arr_row['blog_'.$arr_blog['id']]) {
							
							$str_title_url = $arr_urls_base[$arr_blog['id']].$arr_row['id'].'/'.str2URL($arr_row['title']);
							
							$arr_data[] = '<a href="'.$str_title_url.'" target="_blank"><span class="icon">'.getIcon('tick').'</span></a>';
						} else {
							
							$arr_data[] = '';
						}
					}
				}
				
				$arr_data[] = '<input type="button" class="data edit popup edit_blog_post" value="edit" />'
					.'<input type="button" class="data del msg del_blog_post" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
							
		// QUERY
	
		if ($method == "insert_blog_post") {
		
			$body = $_POST['body'];
			
			$num_para = ((int)$_POST['para_preview'] ?: '0');
				
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_BLOG_POSTS')."
				(title, body, abstract, cms_user_id, date, para_preview, draft)
					VALUES
				(
					'".DBFunctions::strEscape($_POST['title'])."',
					'".DBFunctions::strEscape($body)."',
					'".DBFunctions::strEscape($_POST['abstract'])."',
					".$_SESSION['USER_ID'].",
					'".date('Y-m-d H:i:s', strtotime($_POST['date'].' '.$_POST['date_t']))."',
					".$num_para.",
					".DBFunctions::escapeAs($_POST['draft'], DBFunctions::TYPE_BOOLEAN)."
				)
			");
						
			$new_id = DB::lastInsertID();
			
			if ($_POST['blog']) {
				
				foreach ($_POST['blog'] as $blog_id => $value) {
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_BLOG_POST_LINK')."
						(blog_id, blog_post_id)
							VALUES
						(".(int)$blog_id.", ".$new_id.")
					");
				
					if ($_POST['pingback-url']) {
						self::doPings($_POST['pingback-url'], $blog_id, $new_id, $_POST['title']);
					}
				}
			}
			
			cms_general::handleTags(DB::getTable('TABLE_BLOG_POST_TAGS'), 'blog_post_id', $new_id, $_POST['tags']);
						
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "update_blog_post" && (int)$id) {
		
			$body = $_POST['body'];
			
			$num_para = ((int)$_POST['para_preview'] ?: '0');
					
			$res = DB::query("UPDATE ".DB::getTable('TABLE_BLOG_POSTS')." SET
				title = '".DBFunctions::strEscape($_POST['title'])."',
				body = '".DBFunctions::strEscape($body)."',
				abstract = '".DBFunctions::strEscape($_POST['abstract'])."',
				date = '".date('Y-m-d H:i:s', strtotime($_POST['date'].' '.$_POST['date_t']))."',
				para_preview = ".$num_para.",
				draft = ".DBFunctions::escapeAs($_POST['draft'], DBFunctions::TYPE_BOOLEAN)."
					WHERE id = ".(int)$id."
			");
						
			$res = DB::query("DELETE FROM ".DB::getTable('TABLE_BLOG_POST_LINK')."
				WHERE blog_post_id = ".(int)$id."
			");
			
			if ($_POST['blog']) {
				
				foreach ($_POST['blog'] as $blog_id => $value) {
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_BLOG_POST_LINK')."
						(blog_id, blog_post_id)
							VALUES
						(".(int)$blog_id.", ".(int)$id.")
					");
					
					if ($_POST['pingback-url']) {
						self::doPings($_POST['pingback-url'], $blog_id, $id, $_POST['title']);
					}
				}
			}
						
			cms_general::handleTags(DB::getTable('TABLE_BLOG_POST_TAGS'), 'blog_post_id', $id, $_POST['tags']);
									
			$this->refresh_table = true;
			$this->msg = true;
		}
			
		if ($method == "del_blog_post" && (int)$id) {
		
			$res = DB::queryMulti("
				DELETE FROM ".DB::getTable('TABLE_BLOG_POST_TAGS')."
					WHERE blog_post_id = ".(int)$id."
				;
				DELETE FROM ".DB::getTable('TABLE_BLOG_POST_LINK')."
					WHERE blog_post_id = ".(int)$id."
				;
				DELETE FROM ".DB::getTable('TABLE_BLOG_POST_XREFS')."
					WHERE blog_post_id = ".(int)$id."
				;	
				DELETE FROM ".DB::getTable('TABLE_BLOG_POSTS')."
					WHERE id = ".(int)$id."
				;
			");
			
			$this->msg = true;
		}
	}
		
	private static function doPings($arr_pings, $blog_id, $blog_post_id, $str_title) {

		$arr_link = cms_blogs::findMainBlog($blog_id);

		foreach ($arr_pings as $value) {
			
			$str_url_to = $value;
			
			if ($arr_link['shortcut']) {
				$str_url_from = pages::getShortcutURL($arr_link);
			} else {
				$str_url_from = pages::getModuleURL($arr_link);
			}
			$str_url_from .= $blog_post_id.'/'.str2URL($str_title);

			$server = PingbackUtility::getPingbackServerURL($str_url_to);

			if (PingbackUtility::sendPingback($str_url_from, $str_url_to, $server)) {
				$str_url_source = rawurldecode($str_url_to);
				$ret = DB::query("INSERT INTO ".DB::getTable('TABLE_BLOG_POST_XREFS')." (direction, added, source, blog_post_id) VALUES ('out', NOW(), '".DBFunctions::strEscape($str_url_source)."', ".(int)$blog_post_id.")");
			}
		}
	}

	public static function getBlogPostLinks($blog_post_id) {
	
		$arr = [];

		$res = DB::query("SELECT blog_id FROM ".DB::getTable('TABLE_BLOG_POST_LINK')." WHERE blog_post_id = ".(int)$blog_post_id."");
		
		while ($arr_row = $res->fetchAssoc()) {
			$arr[] = $arr_row['blog_id'];
		}	

		return $arr;
	}
	
	public static function getBlogPostsCount($blog_id = 0, $str_tag = false) {
		
		$res = DB::query("SELECT
			COUNT(bp.id)
				FROM ".DB::getTable('TABLE_BLOG_POSTS')." bp
				JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = bp.id)
				".($str_tag ? "
					JOIN ".DB::getTable('TABLE_BLOG_POST_TAGS')." btsel ON (btsel.blog_post_id = bp.id)
					JOIN ".DB::getTable('TABLE_TAGS')." tsel ON (tsel.id = btsel.tag_id AND tsel.name = '".DBFunctions::strEscape($str_tag)."')"
				: "")."
			WHERE l.blog_id = ".(int)$blog_id."
				AND bp.draft = FALSE
		");
		
		$arr_row = $res->fetchRow();
		
		return $arr_row[0];
	}
	
	public static function getBlogPosts($blog_id = false, $blog_post_id = false, $num_limit = 0, $num_start = 0, $str_tag = false, $do_draft = false) {
	
		$arr = [];
		
		$do_draft = ($blog_post_id ? null : $do_draft);
		
		$res = DB::query("SELECT
			bp.*,
			cu.name AS cms_user_name,
			".DBFunctions::sqlImplode('t.name', ',')." AS tags
				FROM ".DB::getTable('TABLE_BLOG_POSTS')." bp
				LEFT JOIN ".DB::getTable('TABLE_CMS_USERS')." cu ON (cu.id = bp.cms_user_id)
				".($blog_id ? "JOIN ".DB::getTable('TABLE_BLOG_POST_LINK')." l ON (l.blog_post_id = bp.id AND l.blog_id = ".(int)$blog_id.")" : "")."
				LEFT JOIN ".DB::getTable('TABLE_BLOG_POST_TAGS')." bt ON (bt.blog_post_id = bp.id)
				LEFT JOIN ".DB::getTable('TABLE_TAGS')." t ON (t.id = bt.tag_id)
				".($str_tag ? "
					JOIN ".DB::getTable('TABLE_BLOG_POST_TAGS')." btsel ON (btsel.blog_post_id = bp.id)
					JOIN ".DB::getTable('TABLE_TAGS')." tsel ON (tsel.id = btsel.tag_id AND tsel.name = '".DBFunctions::strEscape($str_tag)."')"
				: "")."
			WHERE TRUE
				".($blog_post_id ? "AND bp.id = ".(int)$blog_post_id : "")."
				".($do_draft !== null ? "AND bp.draft = ".($do_draft ? 'TRUE' : 'FALSE') : '')."
			GROUP BY bp.id, cu.id
			ORDER BY bp.date DESC
			".($num_start || $num_limit ? "LIMIT ".(int)$num_limit." OFFSET ".(int)$num_start : "")."
		");
		
		$arr_cms_users_core = [];
							
		while ($arr_row = $res->fetchAssoc()) {
			
			if ($arr_row['cms_user_name'] === null) {
				
				if (!isset($arr_cms_users_core[$arr_row['cms_user_id']])) {
					
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
		
		return ($blog_post_id ? current($arr) : $arr);
	}
}
