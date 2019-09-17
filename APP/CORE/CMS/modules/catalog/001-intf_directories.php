<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class intf_directories extends directories {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_directories');
		static::$parent_label = '';
	}
		
	public function contents() {
	
		$return .= '<div class="section directories">
			<h1 id="x:intf_directories:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup directory_add" value="add" /></h1>
			<div>';
			
				$directories = array_values(self::getDirectories(0, 0, 0, false));
				
				$arr_user_groups = user_groups::getUserGroups();
				
				if (count($directories) == 0) {
					$return .= '<div class="options">'.getLabel('msg_no_directories').'</div>';
				} else {
				
					$return .= '<input type="hidden" id="y:intf_directories:directories_sort-0" name="sort" value="" />';
				
					for ($i = 0; $i < count($directories); $i++) {
					
						$cur_length = $directories[$i]['path_length'];
						$new_length = $directories[$i+1]['path_length'];
						$cur_dir = $directories[$i];
						$user_group_name = $arr_user_groups[$cur_dir['user_group_id']]['name'];
									
						$return .= '<div class="node">';
					
							$return .= '<div class="object'.($new_length > $cur_length ? ' parent' : '').'" id="x:intf_directories:directory_id-'.$cur_dir['id'].'">
								'.($cur_dir['root'] ? '' : '<div class="handle"><span class="icon">'.getIcon('handle-grid').'</span></div>').'
								<div class="link"><h3><a target="_blank" href="'.pages::getBaseUrl($cur_dir).'">/ '.$cur_dir['name'].'</a></h3></div>
								<div class="object-info">'
									.'<span class="icon'.($cur_dir['publish'] ? ' selected' : '').'" title="'.($cur_dir['publish'] ? getLabel('inf_publish_in_header') : getLabel('inf_publish_in_header_not')).'">'.getIcon('globe').'</span>'
									.'<span class="icon'.($cur_dir['require_login'] && $user_group_name ? ' selected' : '').'" title="'.($cur_dir['require_login'] && $user_group_name ? getLabel('inf_login_required') : getLabel('inf_login_required_not')).'">'.getIcon('clearance').'</span>'
									.'<span class="icon'.($user_group_name ? ' selected' : '').'" title="'.getLabel('lbl_user_group').': <br />'.($user_group_name ?: getLabel('inf_none')).'">'.getIcon('users').'</span>'
								.'</div>
								<div class="object-info del-edit"><input type="button" class="data del msg directory_del" value="del" /><input type="button" class="data edit popup directory_edit" value="edit" /></div>
							</div>';
											
						if ($new_length == $cur_length) {
							$return .= '</div>'; //close node;
						} else if ($new_length > $cur_length) {
							$return .= '<div class="sub-node">'; //open sub node;
						} else if ($new_length < $cur_length) {
							$return .= '</div>'; //close current node
							for ($c = 0; $c < $cur_length-$new_length; $c++) {
								$return .= '</div>'; //close sub nodes;
								$return .= '</div>'; //close old nodes;
							}
						} else if (!$directories[$i+1]) {
							$return .= '</div>'; //close current node
							for ($c = 0; $c < $cur_length; $c++) {
								$return .= '</div>'; //close sub nodes;
								$return .= '</div>'; //close old nodes;
							}
						}
					}
				}
			
			$return .= '</div>
		</div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '#mod-intf_directories { }
				#mod-intf_directories > .section > div { text-align: center; }
				#mod-intf_directories .object { margin: 5px; width: 150px; height: 100px; }
				#mod-intf_directories .node { display: inline-block; white-space: nowrap; vertical-align: top; }
				
				#mod-intf_directories .object .link { text-align: center; margin: 25px 5px 0px 5px; }
				#mod-intf_directories .object .link h3 { display: inline; font-weight: bold; font-size: 18px; line-height: 18px; vertical-align: middle; white-space: nowrap; }
				#mod-intf_directories .object .link a { color: #000000; }
				#mod-intf_directories .object .object-info.del-edit { bottom: 6px; left: 6px; right: auto; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-intf_directories', function(elm_scripter) {
				
				var elm_directories = elm_scripter.children('.section.directories');
				elm_directories.data('is_static', true);
				
				SCRIPTER.runDynamic(elm_directories);
			});
			
			SCRIPTER.dynamic('.section.directories', function(elm_scripter) {

				var elm_directories = elm_scripter.children('div');
				
				elm_directories.find('.object .link h3').each(function() {
					fitText(this);
				});
				
				var elm_sortsorter = elm_directories.find('> .node > .sub-node');
				
				new SortSorter(elm_sortsorter, {
					container: '.sub-node',
					items: '> .node',
					handle: '> .node > .object > .handle',
					nested: true,
					call_update: function (elm) {
						func_update_sort();
					}
				});
				
				var func_update_sort = function() {
				
					var arr_sort = {};
					elm_scripter.find('.object').each(function() {
						var id = $(this).attr('id').split('-')[1];
						var target = $(this).parents('div');
						arr_sort[id] = target.parents('div').children('div').index(target);
					});
					
					var elm_input = elm_scripter.find('input[name=\"sort\"]');
					elm_input.val($.param(arr_sort));
					elm_input.quickCommand(false);
				};
				
				if (!elm_scripter.data('is_static')) {
					func_update_sort();
				}
			});
			
			SCRIPTER.dynamic('#frm-directory', function(elm_scripter) {
			
				function showLoginRelated(elm) {
				
					var elm_target = $('#frm-directory [name=page_fallback], #frm-directory [name=require_login]').closest('li');
					elm_target.hide();
					if (elm.val() > 0) {
						elm_target.show();
					}
				}
				
				showLoginRelated(elm_scripter.find('select[name=user_groups]'));
				
				elm_scripter.on('change', 'select[name=user_groups]', function() {
					showLoginRelated($(this));
				});
			});
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// MAIN PAGE
				
		if ($method == "directories_sort") {
			
			parse_str($value, $arr_sort);
			
			foreach ($arr_sort as $key => $value) {
				
				$res = DB::query("UPDATE ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." SET sort = ".(int)$value." WHERE ancestor_id = ".(int)$key." AND path_length = 0");
			}
		}
	
		// POPUP
		
		if ($method == "directory_edit" || $method == "directory_add") {
		
			if ((int)$id) {
				
				$res = DB::query("SELECT
					d.*, p.ancestor_id AS parent
						FROM ".DB::getTable('TABLE_DIRECTORIES')." as d
						LEFT JOIN ".DB::getTable("TABLE_DIRECTORY_CLOSURE")." p ON (p.descendant_id = d.id AND path_length = 1)
					WHERE id = ".(int)$id
				);
				
				$arr_row = $res->fetchAssoc();
				
				$arr_row['publish'] = DBFunctions::unescapeAs($arr_row['publish'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['require_login'] = DBFunctions::unescapeAs($arr_row['require_login'], DBFunctions::TYPE_BOOLEAN);
				
				$mode = 'directory_update';
			} else {
						
				$mode = 'directory_insert';
			}
			
			$root = self::getRootDirectory();
			$parent_options = self::createDirectoriesDropdown(self::getDirectories(0, $arr_row['id']), $arr_row['parent']);
								
			$this->html = '<form id="frm-directory" class="'.$mode.'">
				<fieldset><ul>';
					if (($mode == "directory_insert" && $root) || ($mode == "directory_update" && $arr_row['id'] != $root)) {
							$this->html .= '<li>
								<label>'.getLabel('lbl_title').'</label>
								<div><input type="text" name="title" value="'.htmlspecialchars($arr_row['title']).'"></div>
							</li>
							<li>
								<label>'.getLabel('lbl_name').'</label>
								<div><input type="text" name="name" value="'.htmlspecialchars($arr_row['name']).'"></div>
							</li>
							<li>
								<label>'.getLabel('lbl_directory').'</label>
								<div><select name="parent">'.$parent_options.'</select></div>
							</li>
						</ul>
						<hr />
						<ul>';
					}
					$this->html .= '<li>
						<label>'.getLabel('lbl_user_group').'</label>
						<div><select name="user_groups">'.user_groups::createUserGroupsDropdown(user_groups::getUserGroups(), $arr_row['user_group_id'], true).'</select></div>
					</li>';
					if ($mode == "directory_update") {
						$this->html .= '<li>
							<label>'.getLabel('lbl_fallback_page').'</label>
							<div><select name="page_fallback">'.cms_general::createDropdown(pages::getPages(0, $arr_row['id']), $arr_row['page_fallback_id'], true).'</select></div>
						</li>';
					}
					$this->html .= '<li>
						<label>'.getLabel('lbl_login').'</label>
						<div><label><input type="checkbox" name="require_login" value="1"'.($arr_row['require_login'] || $mode == "directory_insert" ? ' checked="checked"' : '').'><span>'.getLabel('lbl_required').'</span></label></div>
					</li>
				</ul>
				<hr />
				<ul>';
					if ($mode == "directory_update") {
						$this->html .= '<li>
							<label>'.getLabel('lbl_index_page').'</label>
							<div><select name="page_index">'.cms_general::createDropdown(pages::getPages(0, $arr_row['id']), $arr_row['page_index_id'], true).'</select></div>
						</li>';
					}
					$this->html .= '<li>
						<label>'.getLabel('lbl_publish').'</label>
						<div><input type="checkbox" name="publish" value="1"'.($arr_row['publish'] ? ' checked="checked"' : '').' /></div>
					</li>';
				$this->html .= '</ul></fieldset>		
			</form>';
			
			if (($mode == "directory_insert" && $root) || ($mode == "directory_update" && $arr_row['id'] != $root)) {
				$this->validate = '{"title": "required", "parent": "required"}';
			} else {
				$this->validate = '{}';
			}
		}
		
		// POPUP INTERACT

		
		// QUERY
		
		if ($method == "directory_insert") {
		
			$require_login = ((int)$_POST['require_login'] && (int)$_POST['user_groups']);
		
			$res = DB::query("SELECT root FROM ".DB::getTable('TABLE_DIRECTORIES')." WHERE root = TRUE");
			
			if ($res->getRowCount() == 0) {
				
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_DIRECTORIES')."
					(name, title, user_group_id, require_login, publish, root)
						VALUES
					('', '', '".(int)$_POST['user_groups']."', ".DBFunctions::escapeAs($require_login, DBFunctions::TYPE_BOOLEAN).", ".DBFunctions::escapeAs($_POST['publish'], DBFunctions::TYPE_BOOLEAN).", TRUE)
				");
			} else {
			
				$name = ($_POST['name'] ?: $_POST['title']);
				$name = str2Name($name);
				$title = ($row['root'] ? '' : $_POST['title']);
				
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_DIRECTORIES')."
					(name, title, user_group_id, require_login, publish, root)
						VALUES
					('".DBFunctions::strEscape($name)."', '".DBFunctions::strEscape($title)."', '".(int)$_POST['user_groups']."', ".DBFunctions::escapeAs($require_login, DBFunctions::TYPE_BOOLEAN).", ".DBFunctions::escapeAs($_POST['publish'], DBFunctions::TYPE_BOOLEAN).", FALSE)
				");
			}
			
			$new_id = DB::lastInsertID();
			
			$res = DB::query("SELECT ancestor_id, path_length FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')."
									WHERE descendant_id = ".(int)$_POST['parent']."
			");
									
			while ($row = $res->fetchAssoc()) {
				
				DB::query("INSERT INTO ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." (ancestor_id, descendant_id, path_length) VALUES (".$row['ancestor_id'].", ".$new_id.", ".($row['path_length']+1).")");
			}
			
			DB::query("INSERT INTO ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." (ancestor_id, descendant_id, path_length) VALUES (".$new_id.", ".$new_id.", 0)");
													 
			$this->refresh = true;
			$this->msg = true;
		}
		
		if ($method == "directory_update" && (int)$id) {
		
			$res = DB::query("SELECT
				ancestor_id AS parent, root
					FROM ".DB::getTable('TABLE_DIRECTORIES')." d
					LEFT JOIN ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." c ON (c.descendant_id = d.id AND path_length = 1)
				WHERE id = ".(int)$id
			);
									
			$row = $res->fetchAssoc();
			
			$is_root = DBFunctions::unescapeAs($row['root'], DBFunctions::TYPE_BOOLEAN);

			$name = ($_POST['name'] ?: $_POST['title']);
			$name = str2Name($name);
			$name = ($is_root ? '' : $name);
			$title = ($is_root ? '' : $_POST['title']);
			$require_login = ((int)$_POST['require_login'] && (int)$_POST['user_groups']);

			$res = DB::query("UPDATE ".DB::getTable('TABLE_DIRECTORIES')." SET
					name = '".DBFunctions::strEscape($name)."',
					title = '".DBFunctions::strEscape($title)."',
					user_group_id = ".(int)$_POST['user_groups'].",
					require_login = ".DBFunctions::escapeAs($require_login, DBFunctions::TYPE_BOOLEAN).",
					publish = ".DBFunctions::escapeAs($_POST['publish'], DBFunctions::TYPE_BOOLEAN).",
					page_fallback_id = ".((int)$_POST['user_groups'] ? (int)$_POST['page_fallback'] : 0).",
					page_index_id = ".(int)$_POST['page_index']."
				WHERE id = ".(int)$id."");
			
			if ($_POST['parent'] != $row['parent'] && !$is_root) {
				
				// Remove own references
				
				$res = DB::query("DELETE FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." WHERE descendant_id = ".(int)$id."");

				// Remove only lower references from all children
				
				$arr = [];
				
				$res_children = DB::query("SELECT
					descendant_id, path_length
						FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')."
					WHERE ancestor_id = ".(int)$id."
				");
				
				while ($row_children = $res_children->fetchAssoc()) {
					$arr[] = $row_children['descendant_id'];
				}
				
				$arr[] = (int)$id;
				$del = DB::query("DELETE
					FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')."
					WHERE descendant_id IN ('".implode("','", $arr)."')
						AND ancestor_id NOT IN ('".implode("','", $arr)."')
				");
				
				// Insert new own references based on new parent
				
				DB::query("INSERT INTO ".DB::getTable('TABLE_DIRECTORY_CLOSURE')."
					(ancestor_id, descendant_id, path_length)
						VALUES
					(".(int)$id.", ".(int)$id.", 0)
				");		
				
				$res = DB::query("SELECT
					ancestor_id, path_length
						FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')."
					WHERE descendant_id = ".(int)$_POST['parent']
				);
				
				while ($row = $res->fetchAssoc()) {
					
					DB::query("INSERT INTO ".DB::getTable('TABLE_DIRECTORY_CLOSURE')."
						(ancestor_id, descendant_id, path_length)
							VALUES
						(".$row['ancestor_id'].", ".(int)$id.", ".($row['path_length']+1).")
					");
					
					// Insert new children references based on new parent
					
					if ($res_children->getRowCount()) {
						
						$res_children->seekRow(0);
						
						while($row_children = $res_children->fetchAssoc()) {
							
							DB::query("INSERT INTO ".DB::getTable('TABLE_DIRECTORY_CLOSURE')."
								(ancestor_id, descendant_id, path_length)
									VALUES
								(".$row['ancestor_id'].", ".$row_children['descendant_id'].", ".($row['path_length']+1+$row_children['path_length']).")							
							");
						}
					}
				}		
			}
				
			$this->refresh = true;
			$this->msg = true;
		}
			
		if ($method == "directory_del" && (int)$id) {
					
			// Remove references from all children & own
			
			$arr = [];
			
			$res_children = DB::query("SELECT
				descendant_id, path_length
					FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')."
				WHERE ancestor_id = ".(int)$id."
			");
			
			while ($row_children = $res_children->fetchAssoc()) {
				
				$arr[] = $row_children['descendant_id'];
			}
			
			$arr[] = (int)$id;
			
			$del = DB::query("DELETE FROM ".DB::getTable('TABLE_DIRECTORY_CLOSURE')." WHERE descendant_id IN (".implode(',', $arr).")");
			
			// Remove directory references from all children & own
			
			$res = DB::queryMulti("
				".DBFunctions::deleteWith(
					DB::getTable('TABLE_PAGE_MODULES'), 'pm', 'page_id',
					"JOIN ".DB::getTable('TABLE_PAGES')." p ON (p.id = pm.page_id AND p.directory_id IN (".implode(',', $arr)."))"
				)."
				;
				DELETE FROM ".DB::getTable('TABLE_PAGES')." 
					WHERE directory_id IN (".implode(',', $arr).")
				;
				DELETE FROM ".DB::getTable('TABLE_DIRECTORIES')."
					WHERE id IN (".implode(',', $arr).")
				;
			");
			
			$this->refresh = true;
			$this->msg = true;
		}
	}
}
