<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_BLOGS', DB::$database_home.'.def_blogs');

class cms_blogs extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_blogs');
		static::$parent_label = getLabel('ttl_site');
	}
	
	public function contents() {
		
		$return = '<div class="section"><h1 id="x:cms_blogs:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup add_blog" value="add" /></h1>
		<div class="blogs">';

			$res = DB::query("SELECT
				b.*,
				(SELECT
					COUNT(*)
						FROM ".DB::getTable('TABLE_BLOG_POST_LINK')." bl
					WHERE bl.blog_id = b.id
				) AS blog_post_count,
				(SELECT
					".DBFunctions::group2String('bp.title', '<br />', 'ORDER BY bp.date DESC')."
						FROM ".DB::getTable('TABLE_BLOG_POST_LINK')." bl
						LEFT JOIN ".DB::getTable('TABLE_BLOG_POSTS')." bp ON (bp.id = bl.blog_post_id)
					WHERE bl.blog_id = b.id
				) AS blog_posts,
				".DBFunctions::group2String(DBFunctions::castAs('d.id', DBFunctions::CAST_TYPE_STRING), ',', 'ORDER BY p.id')." AS directories,
				".DBFunctions::group2String('p.name', ',', 'ORDER BY p.id')." AS pages
					FROM ".DB::getTable('TABLE_BLOGS')." b
					LEFT JOIN ".DB::getTable('TABLE_PAGE_MODULES')." m ON (m.var LIKE CONCAT('%\"id\":\"', b.id, '\"%') AND m.module = 'blog')
					LEFT JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = m.page_id)
					LEFT JOIN ".DB::getTable('TABLE_DIRECTORIES')." d ON (d.id = p.directory_id)
				GROUP BY b.id
			");
		
			if ($res->getRowCount() == 0) {
				
				Labels::setVariable('name', getLabel('lbl_blogs'));
			
				$return .= '<section class="info">'.getLabel('msg_no', 'L', true).'</section>';
			} else {
		
				$return .= '<table class="list">
					<thead>
						<tr>
							<th>'.getLabel('lbl_name').'</th>
							<th>'.getLabel('lbl_path').'</th>
							<th>'.getLabel('lbl_blog_posts').'</th>
						</tr>
					</thead>
					<tbody>';
						while ($arr_row = $res->fetchAssoc()) {
							
							$arr_pages = str2Array($arr_row['pages'], ',');
							$arr_directories = array_filter(str2Array($arr_row['directories'], ','));
							$arr_paths = [];
							
							for ($i = 0; $i < count($arr_directories); $i++) {
								$arr_dir = directories::getDirectories($arr_directories[$i]);
								if ($arr_dir['id']) {
									$arr_paths[] = $arr_dir['path'].' / '.$arr_pages[$i];
								}
							}
							$arr_paths = array_unique($arr_paths);
							
							$return .= '<tr id="x:cms_blogs:blog_id-'.$arr_row['id'].'">
								<td>'.$arr_row['name'].'</td>
								<td><span class="info"><span class="icon" title="'.($arr_paths ? implode('<br />', $arr_paths) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.count($arr_paths).'</span></span></td>
								<td><span class="info"><span class="icon" title="'.(strEscapeHTML($arr_row['blog_posts']) ?: getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)$arr_row['blog_post_count'].'</span></span></td>
								<td><input type="button" class="data edit popup edit_blog" value="edit" /><input type="button" class="data del msg del_blog" value="del" /></td>
							</tr>';
						}
					$return .= '</tbody>
				</table>';
			}
						
		$return .= '</div></div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// POPUP
		
		if ($method == "edit_blog" || $method == "add_blog") {
		
			if ((int)$id) {
				
				$res = DB::query("SELECT b.*
						FROM ".DB::getTable('TABLE_BLOGS')." b
					WHERE b.id = ".(int)$id."");
									
				$arr = $res->fetchAssoc();
				
				$mode = "update_blog";
			} else {
				$mode = "insert_blog";
			}
														
			$this->html = '<form id="frm-blogs" data-method="'.$mode.'">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($arr['name']).'"></div>
					</li>		
				</ul></fieldset>
			</form>';
			
			$this->validate = ['name' => 'required'];
		}
		
		// POPUP INTERACT
							
		// QUERY
	
		if ($method == "insert_blog") {
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_BLOGS')."
				(name)
					VALUES
				('".DBFunctions::strEscape($_POST['name'])."')
			");
						
			$this->refresh = true;
			$this->msg = true;
		}
		
		if ($method == "update_blog" && (int)$id) {
					
			$res = DB::query("UPDATE ".DB::getTable('TABLE_BLOGS')." SET
					name = '".DBFunctions::strEscape($_POST['name'])."'
				WHERE id = ".(int)$id."
			");
						
			$this->refresh = true;
			$this->msg = true;
		}
			
		if ($method == "del_blog" && (int)$id) {
		
			$res = DB::queryMulti("
				DELETE FROM ".DB::getTable('TABLE_BLOG_POST_LINK')."
					WHERE blog_id = ".(int)$id."
				;
				DELETE FROM ".DB::getTable('TABLE_BLOGS')."
					WHERE id = ".(int)$id."
				;
			");
				
			$this->msg = true;
		}
	}

	public static function getBlogs($blog_id = 0) {
	
		$arr = [];

		if ($blog_id) {
			
			$res = DB::query("SELECT *
				FROM ".DB::getTable('TABLE_BLOGS')."
				WHERE id = ".(int)$blog_id."
			");
			
			$arr = ($res->fetchAssoc() ?: []);
		} else {
			
			$res = DB::query("SELECT *
				FROM ".DB::getTable('TABLE_BLOGS')."
				ORDER BY id
			");
			
			while ($arr_row = $res->fetchAssoc()) {
				$arr[$arr_row['id']] = $arr_row;
			}
		}
			
		return $arr;
	}
	
	public static function findMainBlog($blog_id) {

		return pages::getClosestModule('blog', 0, 0, 0, $blog_id, 'id');
	}
}
