<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class intf_templates extends templates {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_templates');
		static::$parent_label = '';
	}

	public function contents() {
	
		$return = '<div class="section templates"><h1 id="x:intf_templates:new-0"><span>'.self::$label.'</span><input type="button" class="data add popup template_add" value="add" /></h1>
			<div>';
			
				$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_PAGE_TEMPLATES')."");
			
				if (!$res->getRowCount()) {
					
					$return .= '<p class="info">'.getLabel('msg_no_templates').'</p>';
				} else {
					
					$return .= '<div class="overview">';
						while ($row = $res->fetchAssoc()) {
							
							$return .= '<div id="x:intf_templates:template_id-'.$row['id'].'" class="object">
								<div class="template-preview">'.$row['preview'].'</div>
								<div class="title"><h3>'.($row['name'] ? $row['name'] : '&nbsp;').'</h3></div>
								<div class="object-info del-edit"><input type="button" class="data del msg template_del" value="del" /><input type="button" class="data edit popup template_edit" value="edit" /></div>
							</div>';
						}
					$return .= '</div>';
				}
				
			$return .= '</div>
		</div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '#mod-intf_templates .overview { margin: 7px; }
				#mod-intf_templates .overview > .object { position: relative; display: inline-block; margin: 3px; width: 125px; height: 160px; }
				#mod-intf_templates .overview > .object:hover { background-color: #e7f2f9; }
				#mod-intf_templates .overview > .object .template-preview { width: 85px; height: 85px; margin: 0px auto; margin-top: 20px; }
				#mod-intf_templates .overview > .object .title { margin: 4px 5px 0px 5px; text-align: center; }
				#mod-intf_templates .overview > .object .title h3 { display: inline; font-weight: bold; font-size: 14px; line-height: 14px; vertical-align: middle; white-space: nowrap;}
				#mod-intf_templates .overview > .object .object-info.del-edit { bottom: 6px; left: 6px; right: auto; }

				#frm-template .builder { display: inline-block; }
				#frm-template .builder .width-bar { margin-left: 42px; }
				#frm-template .builder .height-bar { display: inline-block; vertical-align: top; }
				#frm-template .builder .width-bar span
					{ display: inline-block; width: 70px; text-align: center; }
				#frm-template .builder .height-bar > span > span
					{ display: table-cell; text-align: center; height: 70px; vertical-align: middle; }
				#frm-template .builder .height-bar > span
					{ display: table-row; }
				#frm-template .builder .width-bar input,
				#frm-template .builder .height-bar input
					{ width: 30px; margin: 4px; vertical-align: bottom; }
				#frm-template .builder .main { display: inline-block; }
				#frm-template .builder .window { position: relative; display: inline-block; -ms-user-select: none; -moz-user-select: -moz-none; -khtml-user-select: none; -webkit-user-select: none; user-select: none; }
				#frm-template .builder .input { position:absolute; width: 100%; height: 100%; top: 0px; }
				#frm-template .builder .window > .back { display: table; border-collapse: collapse; border: 0px solid #c0c0c0; }
				#frm-template .builder .window > .back .row { display: table-row; }
				#frm-template .builder .window > .back .box { display: table-cell; border: 1px solid #e5e3e3; }
				#frm-template .builder .window > .back .box > div,
				#frm-template .builder .input div
					{ width: 70px; height: 70px; }
				#frm-template .builder .input div
					{ position: absolute; -ms-box-sizing: border-box; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; }
				#frm-template .builder .input .full { border: 2px solid #5ed754; }
				#frm-template .builder .input .back { border: 2px solid #56a7d3; }
				#frm-template .builder .input .con { border: 2px solid #979797; }
				#frm-template .builder .input .mod { border: 2px solid #fd5c4d; }
				#frm-template .builder .input .active
					{ border-color: #4c8efa; }
				#frm-template .builder .input .editing
					{ border-style: dashed; }
				#frm-template .builder .action { margin-top: 5px; }
				#frm-template .builder .action input[type="text"] { width: 100px; }
				#frm-template .builder .action .margin { display: inline-block; vertical-align: middle; }
				#frm-template .builder .action .margin > span { display: inline-block; margin: 1px 0px 1px 0px; }
				#frm-template .builder .action .margin > span:first-child,
				#frm-template .builder .action .margin > span+span+span+span
					{ display: block; text-align: center; }
				#frm-template .builder .action .margin > span:first-child+span+span,
				#frm-template .builder .action .margin.spacing > span:first-child+span
					{ margin-left: 4px; }
				#frm-template .builder .action .margin.extra > span > input,
				#frm-template .builder .action .margin.spacing > span > input { font-size: 11px; width: 20px; height: 16px; }
				#frm-template .builder .settings { display: inline-block; margin-left: 4px; vertical-align: top;}
				#frm-template .builder .settings > span { display: block; margin-bottom: 2px; }
				#frm-template .builder .settings input { width: 25px; }
				#frm-template .builder .settings input[name="name"] { width: 80px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-intf_templates', function(elm_scripter) {
				
				var elm_templates = elm_scripter.children('.section.templates');
				
				SCRIPTER.runDynamic(elm_templates);
			});
			
			SCRIPTER.dynamic('.section.templates', function(elm_scripter) {
		
				elm_scripter.find('.overview > div .title h3').each(function() {
					fitText(this);
				});
			});
			
			SCRIPTER.dynamic('#frm-template', function(elm_scripter) {
				
				elm_scripter.on('scripter ajaxloaded', function(e) {
				
					var box_size = elm_scripter.find('.builder .back').find('.box').outerWidth();
					
					elm_scripter.find('.builder .input *').each(function() {
						var arr = $(this).attr('info').split('-');
						var correction = $(this).parentsUntil('.input').length*6; // 6 = 2x border width (.back, .con, .mod) + 2x spacing width
						var start_x = arr[1]*box_size+1;
						var start_y = arr[2]*box_size+1;
						var width = arr[3]*box_size-1-correction;
						var height = arr[4]*box_size-1-correction;

						$(this).css({'left': start_x+'px', 'top': start_y+'px', 'width': width+'px', 'height': height+'px'});
					});
				}).on('ajaxsubmit', function() {
					elm_scripter.find('.builder .input').find('div').removeClass('editing active');
					$('input[name=html]').val(elm_scripter.find('.builder .input').html());
				}).on('change', '.action input[name=action]', function() {
					elm_scripter.find('.builder .action [id^=m], .builder .action [id^=p]').prop('disabled', elm_scripter.find('.builder .action input[name=action][value=con], .builder .action input[name=action][value=full]').is(':checked'));
				});
			
				elm_scripter.on('mousedown', '.builder .input', function(e) {
				
					var cur = $(e.target);
					var elm_action = elm_scripter.find('.builder .action');
					elm_scripter.find('.builder .input div').removeClass('editing');
					elm_action.find('#customclass').val('').trigger('focus');
					
					$(document).on('mouseup.window', function() {
						$(document).off('.window');
					});
				
					if (elm_scripter.find('.builder .action input[name=action][value=full]:checked, .builder .action input[name=action][value=back]:checked, .builder .action input[name=action][value=con]:checked, .builder .action input[name=action][value=mod]:checked').length) {
						
						var box_size = elm_scripter.find('.builder .back').find('.box').outerWidth();
						var selected = elm_scripter.find('.builder .action input[name=action]:checked').val();
						var parent = cur;
						var parent_class = parent.attr('class');
						if ((selected == 'full' && parent.closest('.full, .mod, .back, .con').length) || (selected == 'back' && parent.closest('.mod, .back').length) || (selected == 'con' && parent.closest('.mod').length) || (selected == 'mod' && parent.closest('.mod').length)) {
							return false;
						}
						var correction = (parent.attr('class') != 'input' ? ((parent.parentsUntil('.input').length+1)*6) : 0); // 6 = 2x border width (.back, .con, .mod) + 2x spacing width
						var box_x = Math.floor((e.pageX - parent.offset().left)/box_size);
						var box_y = Math.floor((e.pageY - parent.offset().top)/box_size);
						if (selected == 'full') {
							var start_x = 1;
						} else {
							var start_x = box_x*box_size+1;
						}
						var start_y = box_y*box_size+1;
						if (selected == 'full') {
							var width = parseInt(elm_scripter.find('[name=sizing_w]').val())*box_size-1-correction;
						} else {
							var width = box_size-1-correction;
						}
						var height = box_size-1-correction;
						var box_w = 1;
						var box_h = 1;
						var cur = $('<div class=\"'+selected+'\"></div>').appendTo(parent).css({'left': start_x+'px', 'top': start_y+'px', 'width': width+'px', 'height': height+'px'});
						cur.attr('info', selected+'-'+box_x+'-'+box_y+'-'+box_w+'-'+box_h);
						cur.attr('sort', box_y+'-'+box_x); // Keep all boxes in order for correct iteration
						parent.children('div').sort(function(elm_a, elm_b) {
							var a = elm_a.getAttribute('sort');
							var b = elm_b.getAttribute('sort');
							if(a > b) {
								return 1;
							}
							if(a < b) {
								return -1;
							}
							return 0;
						}).each(function() {
							parent.append(this);
						});
						
						if (selected != 'con' && selected != 'full') {
						
							cur.attr('mt', (elm_action.find('#mt').is(':checked') ? 1 : 0));
							cur.attr('mr', (elm_action.find('#mr').is(':checked') ? 1 : 0));
							cur.attr('mb', (elm_action.find('#mb').is(':checked') ? 1 : 0));
							cur.attr('ml', (elm_action.find('#ml').is(':checked') ? 1 : 0));
							cur.attr('mte', (elm_action.find('#mte').val() > 0 ? elm_action.find('#mte').val() : 0));
							cur.attr('mre', (elm_action.find('#mre').val() > 0 ? elm_action.find('#mre').val() : 0));
							cur.attr('mbe', (elm_action.find('#mbe').val() > 0 ? elm_action.find('#mbe').val() : 0));
							cur.attr('mle', (elm_action.find('#mle').val() > 0 ? elm_action.find('#mle').val() : 0));
							cur.attr('pt', (elm_action.find('#pt').val() > 0 ? elm_action.find('#pt').val() : 0));
							cur.attr('pr', (elm_action.find('#pr').val() > 0 ? elm_action.find('#pr').val() : 0));
							cur.attr('pb', (elm_action.find('#pb').val() > 0 ? elm_action.find('#pb').val() : 0));
							cur.attr('pl', (elm_action.find('#pl').val() > 0 ? elm_action.find('#pl').val() : 0));
						}
						
						cur.attr('aright', (elm_action.find('#aright').is(':checked') ? 1 : 0));
						
						$(document).on('mousemove.window', function(e) {
							var mouseX = (e.pageX > parent.offset().left + parent.width() ? parent.offset().left + parent.width() : e.pageX);
							var mouseY = (e.pageY > parent.offset().top + parent.height() ? parent.offset().top + parent.height() : e.pageY);
							var box_w = Math.ceil((mouseX - parent.offset().left - cur.position().left)/box_size);
							var box_h = Math.ceil((mouseY - parent.offset().top - cur.position().top)/box_size);
							if (selected != 'full') {
								var width = box_w*box_size-1-correction;
								cur.css('width', width+'px');
							}
							var height = box_h*box_size-1-correction;
							cur.css('height', height+'px');
							cur.attr('info', selected+'-'+box_x+'-'+box_y+'-'+box_w+'-'+box_h);
						});
					}
					if (elm_scripter.find('.builder .action input[name=action][value=edit]:checked').length && cur.attr('class') != 'input') {
					
						cur.addClass('editing');
					
						var elm_action = elm_scripter.find('.builder .action');

						var customclass = (cur.attr('customclass') ? cur.attr('customclass') : '');
						elm_action.find('#customclass').val(customclass);
						
						if (cur.hasClass('con') || cur.hasClass('full')) {
						
							elm_action.find('[id^=m], [id^=p]').prop('disabled', true);
						} else {
						
							elm_action.find('[id^=m], [id^=p]').prop('disabled', false);
							
							elm_action.find('#mt').prop('checked', (cur.attr('mt') > 0 ? true : false));
							elm_action.find('#mr').prop('checked', (cur.attr('mr') > 0 ? true : false));
							elm_action.find('#mb').prop('checked', (cur.attr('mb') > 0 ? true : false));
							elm_action.find('#ml').prop('checked', (cur.attr('ml') > 0 ? true : false));
							elm_action.find('#mte').val((cur.attr('mte') > 0 ? cur.attr('mte') : 0));
							elm_action.find('#mre').val((cur.attr('mre') > 0 ? cur.attr('mre') : 0));
							elm_action.find('#mbe').val((cur.attr('mbe') > 0 ? cur.attr('mbe') : 0));
							elm_action.find('#mle').val((cur.attr('mle') > 0 ? cur.attr('mle') : 0));
							elm_action.find('#pt').val((cur.attr('pt') > 0 ? cur.attr('pt') : 0));
							elm_action.find('#pr').val((cur.attr('pr') > 0 ? cur.attr('pr') : 0));
							elm_action.find('#pb').val((cur.attr('pb') > 0 ? cur.attr('pb') : 0));
							elm_action.find('#pl').val((cur.attr('pl') > 0 ? cur.attr('pl') : 0));
						}
						
						elm_action.find('#aright').prop('checked', (cur.attr('aright') > 0 ? true : false));
					}
					if (elm_scripter.find('.builder .action input[name=action][value=del]:checked').length && cur.attr('class') != 'input') {
						cur.add(cur.children()).remove();
					}
				}).on('change keyup', '.builder .action input', function(e) {
				
					if (!elm_scripter.find('.builder .action input[name=action][value=edit]:checked').length) {
						return;
					}
					
					var elm_box = elm_scripter.find('.builder .input .editing');
					
					if (!elm_box.length) {
						return;
					}
					
					var cur = $(e.currentTarget);
					
					var val = '';
					if (cur.filter(':checked').length) {
						val = 1;
					} else if (cur.attr('type') == 'text') {
						val = cur.val();
					} else {
						val = 0;
					}
					
					elm_box.attr(cur.attr('name'), val);
				}).on('mousemove', '.builder .input div', function(e) {
					if (elm_scripter.find('.builder .action input[name=action][value=edit]:checked, .builder .action input[name=action][value=del]:checked').length) {
						elm_scripter.find('.builder .input div').removeClass('active');
						$(e.target).addClass('active');
					}
				}).on('mouseleave', '.builder .input div', function(e) {
					if (elm_scripter.find('.builder .action input[name=action][value=edit]:checked, .builder .action input[name=action][value=del]:checked').length) {
						$(this).removeClass('active');
					}
				}).on('change', '[name=sizing_w], [name=sizing_h]', function() {
					var source = $('.sizing input:first-child');
					source.val($('input[name=sizing_w]').val()+'-'+$('input[name=sizing_h]').val());
					source.quickCommand(elm_scripter);
				}).on('change', '[name=copy_from]', function() {
					$(this).quickCommand(elm_scripter);
				});
			});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// POPUP
		
		if ($method == "template_edit" || $method == "template_add") {
		
			if ((int)$id) {
				
				$res = DB::query("SELECT * FROM ".DB::getTable("TABLE_PAGE_TEMPLATES")." WHERE id = '".$id."'");
				$row = $res->fetchAssoc();
				
				$mode = "template_update";
			} else {
				$mode = "template_insert";
			}	
								
			$this->html = '<form id="frm-template" class="'.$mode.'">
				'.($row ? self::createTemplateWindow($row, 0, 0) : self::createTemplateWindow()).'
				</form>';
			
			$this->validate = '';
		}
		
		// POPUP INTERACT
		
		if ($method == "sizing") {
			
			$wh = explode("-", $value);
			$width = $wh[0];
			$height = $wh[1];
						
			$this->html = self::createTemplateWindow(((int)$id ? self::getTemplates($id) : false), $width, $height);
		}
		
		if ($method == "copy_from") {
			$this->html = self::createTemplateWindow(self::getTemplates($value), false, false);
		}
					
		// QUERY
			
		if (($method == "template_insert" || $method == "template_update") && !empty($_POST["html"])) {
		
			$columns = [];
			for ($i = 0; isset($_POST['col-'.$i]); $i++) {
				$columns[] = $_POST['col-'.$i];
			}
			$rows = [];
			for ($i = 0; isset($_POST['row-'.$i]); $i++) {
				$rows[] = $_POST['row-'.$i];
			}

			$margin = [];
			$margin['full'] = 0;
			$margin['back'] = $_POST['margin_back'];
			$margin['con'] = 0;
			$margin['mod'] = $_POST['margin_mod'];
			$count_class = 0;
			$arr_mod_xy = [];
			$html_css = "#template-".$id." .site { width: 1000px; margin: 0px auto; }
						#template-".$id." .back-spacing { margin: ".$margin['back']."px; border-width: ".$margin['back']."px; }
						#template-".$id." .mod-spacing { margin: ".$margin['mod']."px; border-width: ".$margin['mod']."px; }\n";
		
			if ($method == 'template_insert') {
				$res = DB::query("SHOW TABLE STATUS LIKE '".DB::getTable('TABLE_PAGE_TEMPLATES')."'");
				$row = $res->fetchArray();
				$id = $row['Auto_increment'];
			}
			
			$doc = new HTMLDocument('<div id="template-'.$id.'" class="container">'.$_POST['html'].'</div>', false);
			$root = $doc->firstChild;
			
			$arr_boxes = [];
			foreach ($root->getElementsByTagName('div') as $box) {
				$arr_boxes[] = $box;
			}

			foreach ($arr_boxes as $box) {
			
				$vars = explode('-', $box->getAttribute('info'));
				// $vars[0] = full/back/con/mod
				// $vars[1] = x
				// $vars[2] = y
				// $vars[3] = width
				// $vars[4] = height

				$cur_margin = $margin[$vars[0]];
				
				$cur_x = (int)$box->parentNode->getAttribute('x')+$vars[1];
				$cur_bx = ($vars[0] == 'back' ? 0 : (int)$box->parentNode->getAttribute('bx')+$vars[1]);
				$cur_bw = ($vars[0] == 'back' ? $vars[3] : ((int)$box->parentNode->getAttribute('bw') ?: count($columns))); // Use full site width when no back is present
				$cur_by = ($vars[0] == 'back' ? 0 : (int)$box->parentNode->getAttribute('by')+$vars[2]);
				$cur_bh = ($vars[0] == 'back' ? $vars[4] : ((int)$box->parentNode->getAttribute('bh') ?: count($rows))); // Use full site height when no back is present
				$cur_y = (int)$box->parentNode->getAttribute('y')+$vars[2];
				$cur_z = ($vars[0] == 'back' || !$box->parentNode->hasAttribute('z') ? 0 : (int)$box->parentNode->getAttribute('z')+1);
								
				if ($vars[0] == 'mod') {
					$arr_mod_xy[$cur_y."-".$cur_x] = [$vars[3], $vars[4], $cur_x, $cur_y];
				}

				$width = 0;
				for ($i = $cur_x; $i < $cur_x+$vars[3]; $i++) {
					$width = $width+(100*((float)$columns[$i]/100));
				}

				$l_margin = ($box->getAttribute('ml') ? $cur_margin : 0)+((int)$box->getAttribute('mle') ?: 0); 
				$r_closing = ($box->getAttribute('mr') ? $cur_margin : 0)+((int)$box->getAttribute('mre') ?: 0);

				$cur_m = $l_margin+$r_closing;
				$cur_p = ((int)$box->getAttribute('pl') ?: 0)+((int)$box->getAttribute('pr') ?: 0);
				if ($vars[0] == 'con' || $vars[0] == 'full') {
					$cur_m = 0;
				}
						
				$height = 0;		
				for ($i = $cur_y; $i < $cur_y+$vars[4]; $i++) {
					if ($rows[$i]) {
						$height = $height+$rows[$i];
					} else {
						break;
					}
				}
				
				$t_margin = ($box->getAttribute('mt') ? $cur_margin : 0)+((int)$box->getAttribute('mte') ?: 0); 
				$b_closing = ($box->getAttribute('mb') ? $cur_margin : 0)+((int)$box->getAttribute('mbe') ?: 0);
				
				$cur_mh = $t_margin+$b_closing;
				$cur_ph = ((int)$box->getAttribute('pt') ?: 0)+((int)$box->getAttribute('pb') ?: 0);
				if ($vars[0] == 'con' || $vars[0] == 'full') {
					$cur_mh = 0;
				}
				
				$box->setAttribute('c', $vars[0]);
				$box->setAttribute('bx', $cur_bx);
				$box->setAttribute('bw', $cur_bw);
				$box->setAttribute('by', $cur_by);
				$box->setAttribute('bh', $cur_bh);
				$box->setAttribute('x', $cur_x);
				$box->setAttribute('y', $cur_y);
				$box->setAttribute('z', $cur_z);
				$box->setAttribute('w', $vars[3]);
				$box->setAttribute('rw', $width);
				$box->setAttribute('rh', $height);
				
				$html_id = ($vars[0] == 'mod' ? $vars[0].'-'.$cur_x.'_'.$cur_y : $vars[0].'-'.$count_class);
				$box->setAttribute('id', $html_id);
				$box->setAttribute('class', $vars[0].($box->getAttribute('customclass') ? ' '.$box->getAttribute('customclass') : ''));

				$html_css .= '#template-'.$id.' #'.$html_id.' { ';
				
				if ($height) {
					
					$parent_height = (int)$box->parentNode->getAttribute('rh');
					
					$height = $height-$cur_mh-$parent_height;
					$height = ($height < 0 ? 0 : $height);
					
					$html_css .= 'min-height: '.$height.'px;';
				}
				
				if ($vars[0] != 'full') {
					
					if ($width) {
						
						$parent_width = ((int)$box->parentNode->getAttribute('rw') ?: 100);
						
						$html_css .= 'width: calc('.(($width / $parent_width) * 100).'% - '.$cur_m.'px); ';
					}
										
					$html_css .= ' margin-left: '.($l_margin ?: 0).'px;';
					$html_css .= ' margin-right: '.($r_closing ?: 0).'px;';
					$html_css .= ' margin-top: '.($t_margin ?: 0).'px;';
					$html_css .= ' margin-bottom: '.($b_closing ?: 0).'px;';
					
					if ($box->getAttribute('aright')) { // Align right?
						$html_css .= ' float: right;';
					}
					
					$html_css .= ($box->getAttribute('pl') ? ' padding-left: '.(int)$box->getAttribute('pl').'px;' : '');
					$html_css .= ($box->getAttribute('pr') ? ' padding-right: '.(int)$box->getAttribute('pr').'px;' : '');
					$html_css .= ($box->getAttribute('pt') ? ' padding-top: '.(int)$box->getAttribute('pt').'px;' : '');
					$html_css .= ($box->getAttribute('pb') ? ' padding-bottom: '.(int)$box->getAttribute('pb').'px;' : '');
				}
				
				$html_css .= " }\n";
								
				$count_class++;
			}
			
			$arr_boxes = [];
			foreach ($root->childNodes as $box) {
				$arr_boxes[] = $box;
			}
			
			$elm_site = false;
			foreach ($arr_boxes as $box) {
							
				$vars = explode('-', $box->getAttribute('info'));

				if ($vars[0] == 'full') { // Store all boxes inside 'full' in a new 'site' element
					$elm_site = $doc->createElement('div');
					$elm_site->setAttribute('class', 'site');
					while ($box->childNodes->length) {
						$elm_site->appendChild($box->childNodes->item(0));
					}
					$box->appendChild($elm_site);
					$root->appendChild($box);
					$elm_site = false;
				} else { // Store all boxes not inside a 'full' in a existing 'site' element
					if (!$elm_site) {
						$elm_site = $doc->createElement('div');
						$elm_site->setAttribute('class', 'site');
						$root->appendChild($elm_site);
					}
					$elm_site->appendChild($box);
				}
			}
			
			$root->removeAttribute('x');
			$root->removeAttribute('y');
			$root->removeAttribute('z');
			foreach ($root->getElementsByTagName('div') as $box) {
				$box->removeAttribute('info');
				$box->removeAttribute('style');
				$box->removeAttribute('sort');
				$box->removeAttribute('c');
				$box->removeAttribute('bx');
				$box->removeAttribute('by');
				$box->removeAttribute('bw');
				$box->removeAttribute('bh');
				$box->removeAttribute('x');
				$box->removeAttribute('y');
				$box->removeAttribute('z');
				$box->removeAttribute('w');
				$box->removeAttribute('rw');
				$box->removeAttribute('rh');
				$box->removeAttribute('mt');
				$box->removeAttribute('mr');
				$box->removeAttribute('mb');
				$box->removeAttribute('ml');
				$box->removeAttribute('mte');
				$box->removeAttribute('mre');
				$box->removeAttribute('mbe');
				$box->removeAttribute('mle');
				$box->removeAttribute('pt');
				$box->removeAttribute('pr');
				$box->removeAttribute('pb');
				$box->removeAttribute('pl');
				$box->removeAttribute('aright');
				$box->removeAttribute('customclass');
			}

			$preview_table = '<table>';
			for ($x = 0; $x < count($columns); $x++) {
				$preview_table .= '<col style="width: '.(100/count($columns)).'%;" />';
			}
			for ($y = 0; $y < count($rows); $y++) {
				$preview_table .= '<tr style="height: '.(100/count($rows)).'%;">';
				for ($x = 0; $x < count($columns); $x++) {
					if ($arr_mod_xy[$y."-".$x] && $arr_mod_xy[$y."-".$x] != 1) {
						$preview_table .= '<td id="mod-'.$x.'_'.$y.'" colspan="'.$arr_mod_xy[$y."-".$x][0].'" rowspan="'.$arr_mod_xy[$y."-".$x][1].'"></td>';
						$temp = $arr_mod_xy[$y."-".$x];
							for ($h = 0; $h < $temp[1]; $h++) {
								for ($w = 0; $w < $temp[0]; $w++) {
									$arr_mod_xy[($temp[3]+$h)."-".($temp[2]+$w)] = 1;
								}
							}
					} else if ($arr_mod_xy[$y.'-'.$x] == false) {
						$preview_table .= '<td></td>';
					}
				}
				$preview_table .= '</tr>';
			}
			$preview_table .= '</table>';

			$clean_html .= $doc->getHTML();
		}
		
		if ($method == "template_insert") {
		
			if (!$_POST['html']) {
				error("Missing Information");
			}
						
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_PAGE_TEMPLATES')."
				(name, html_raw, html, css, preview, column_count, row_count, margin_back, margin_mod)
					VALUES
				('".DBFunctions::strEscape($_POST['name'])."', '".DBFunctions::strEscape($_POST['html'])."', '".DBFunctions::strEscape($clean_html)."', '".DBFunctions::strEscape($html_css)."', '".DBFunctions::strEscape($preview_table)."', '".DBFunctions::strEscape(implode(',', $columns))."', '".DBFunctions::strEscape(implode(',', $rows))."', '".$margin['back']."', '".$margin['mod']."')
			");

			self::writeTemplateSheet();
			
			$this->refresh = true;
			$this->msg = true;
		}
		
		if ($method == "template_update" && (int)$id && !empty($_POST['html'])) {
						
			$res = DB::query("UPDATE ".DB::getTable('TABLE_PAGE_TEMPLATES')."
					SET name = '".DBFunctions::strEscape($_POST['name'])."', html_raw = '".DBFunctions::strEscape($_POST['html'])."', html = '".DBFunctions::strEscape($clean_html)."', css = '".DBFunctions::strEscape($html_css)."', preview = '".DBFunctions::strEscape($preview_table)."', column_count = '".DBFunctions::strEscape(implode(',', $columns))."', row_count = '".DBFunctions::strEscape(implode(',', $rows))."', margin_back = '".$margin['back']."', margin_mod = '".$margin['mod']."'
				WHERE id = ".(int)$id."
			");

			self::writeTemplateSheet();
			
			$this->refresh = true;
			$this->msg = true;
		}
					
		if ($method == "template_del" && (int)$id){
		
			$res = DB::query("DELETE FROM ".DB::getTable('TABLE_PAGE_TEMPLATES')." WHERE id = ".(int)$id."");
			
			self::writeTemplateSheet();
			
			$this->msg = true;
		}
	}
	
	private static function createTemplateWindow($row = [], $width = 6, $height = 6) {
	
		$columns = explode(",", $row['column_count']);
		$rows = explode(",", $row['row_count']);
		
		if ($row && $width && $height && ($width < count($columns) || $height < count($rows))) {			
			$row['html_raw'] = '';
		}
		
		$width = 0+($width ? $width : count($columns));
		$height = 0+($height ? $height : count($rows));
			
		$return .= '<div class="builder">
			<div class="width-bar">';
			for ($i = 0; $i < $width; $i++) {
				$return .= '<span><input name="col-'.$i.'" type="text" value="'.($columns[$i] || $columns[$i] === "0" ? $columns[$i] : (100/$width)).'" /></span>';
			}
			$return .= '</div>
			<div class="height-bar">';
			for ($i = 0; $i < $height; $i++) {
				$return .= '<span><span><input name="row-'.$i.'" type="text" value="'.($rows[$i] || $rows[$i] === "0" ? $rows[$i] : 0).'" /></span></span>';
			}
			$return .= '</div>
			<div class="main">
				<div class="window">
					<div class="back">';
						for ($i = 1; $i <= $width*$height; $i++) {
							if ($i % $width == 1 || $i == 1) {
								$return .= '<div class="row">';
							}
							$return .= '<div class="box"><div></div></div>';
							if (($i+1) % $width == 1 || $i == ($width*$height)) {
								$return .= '</div>';
							}
						}
					$return .= '</div>
					<div class="input">'.($row['html_raw'] ? $row['html_raw'] : '').'</div>
				</div>
				<div class="action">
					'.cms_general::createSelectorRadio([['id' => 'full', 'name' => 'Full'], ['id' => 'back', 'name' => 'Back'], ['id' => 'con', 'name' => 'Container'], ['id' => 'mod', 'name' => 'Module'], ['id' => 'edit', 'name' => 'Edit'], ['id' => 'del', 'name' => 'Delete']], 'action', 'full').'
				</div>
				<div class="action">
					<div class="margin" title="Apply margin">'
						.'<span><input type="checkbox" name="mt" id="mt" value="mt" checked="checked" /><label for="mt"></label></span>'
						.'<span><input type="checkbox" name="ml" id="ml" value="ml" checked="checked" /><label for="ml"></label></span>'
						.'<span><input type="checkbox" name="mr" id="mr" value="mr" checked="checked" /><label for="mr"></label></span>'
						.'<span><input type="checkbox" name="mb" id="mb" value="mb" checked="checked" /><label for="mb"></label></span>'
					.'</div>
					<div class="margin extra" title="Add (extra) margin">'
						.'<span><input type="text" name="mte" id="mte" value="0" /></span>'
						.'<span><input type="text" name="mle" id="mle" value="0" /></span>'
						.'<span><input type="text" name="mre" id="mre" value="0" /></span>'
						.'<span><input type="text" name="mbe" id="mbe" value="0" /></span>'
					.'</div>
					<div class="margin spacing" title="Add spacing (custom puposes, e.g. borders)">'
						.'<span><input type="text" name="pt" id="pt" value="0" /></span>'
						.'<span><input type="text" name="pl" id="pl" value="0" /></span>'
						.'<span><input type="text" name="pr" id="pr" value="0" /></span>'
						.'<span><input type="text" name="pb" id="pb" value="0" /></span>'
					.'</div>
					<label><input type="checkbox" name="aright" id="aright" value="aright" /><span>Right</span></label>
					<input name="customclass" id="customclass" type="text" value="" />
				</div>
				<input name="html" type="hidden" value="'.strEscapeHTML($row['html_raw']).'" />
			</div>
			<div class="settings">
				<span><strong>Name</strong></span>
				<span><input type="text" name="name" value="'.$row['name'].'" /></span>
				<span><strong>Sizing</strong></span>
				<span class="sizing"><input id="y:intf_templates:sizing-'.$row['id'].'" type="hidden" value="" /><input type="text" name="sizing_w" value="'.($row['sizing_w'] ? $row['sizing_w'] : $width).'" /> x <input type="text" name="sizing_h" value="'.($row['sizing_h'] ? $row['sizing_h'] : $height).'" /></span>
				<span><strong>Margin</strong></span>
				<span><input type="text" name="margin_back" id="margin-back" value="'.($row['margin_back'] == '' ? 8 : $row['margin_back']).'" /><label for="margin-back">Back</label></span>
				<span><input type="text" name="margin_mod" id="margin-mod" value="'.($row['margin_mod'] == '' ? 4 : $row['margin_mod']).'" /><label for="margin-mod">Module</label></span>
				<span><strong>Copy</strong></span>
				<span><select name="copy_from" id="y:intf_templates:copy_from-0">'.cms_general::createDropdown(self::getTemplates(), 0, true).'</select></span>
			</div>
		</div>';
		
		return $return;
	}
}
