<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class header_dropdown extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_header_dropdown');
		static::$parent_label = getLabel('ttl_site');
	}

	public function contents() {

		$return = '<div class="toolbar">
				<div class="info"></div>
			</div>
			<div class="menu">'
				.'<span class="toolbar-handle"></span>'
				.'<div id="y:header_dropdown:time-0" class="clock"><span>'.date('H:i').'</span></div>'
				.'<div class="navigation">'.navigation::contents().'</div>'
			.'</div>';

		return $return;
	}
	
	public static function css() {

		$return = '.header_dropdown { }
			.header_dropdown .toolbar { height:300px; display:none; background:#0068B1; }
			.header_dropdown .toolbar > .info { position:relative; float:left; width:150px; height:150px; border: 1px solid #e5e3e3; border-radius: 4px; color: #000; background:#fcfcfc; margin:15px; }
			.header_dropdown .menu { position:relative; background-color: #f5f5f5; border-bottom: 1px solid #e5e3e3; border-top: 5px solid #0068B1; height:40px; width:100%; }
			.header_dropdown .toolbar-handle { display: block; position:absolute; left:50%; margin-left:-15px; width:30px; height: 22px; border-left: 1px solid #e5e3e3; border-right: 1px solid #e5e3e3; border-bottom: 1px solid #e5e3e3; border-radius: 0px 0px 4px 4px; color: #e5e3e3; background: #fcfcfc url(/css/images/icon_slide_default.png) no-repeat center center;}
			.header_dropdown .toolbar-handle:hover, .header_dropdown .toolbar-handle.active { border-left: 1px solid #00497E; border-right: 1px solid #00497E; border-bottom: 1px solid #00497E; background:#0068B1 url(/css/images/icon_slide_active.png) no-repeat center center; cursor:pointer; }
			.header_dropdown .menu .clock { position: relative; float: right; width: 75px; height: 28px; line-height: 28px; text-align: center; font-weight: bold; font-size: 16px; margin: 5px; border: 1px solid #e5e3e3; border-radius: 4px; color: #000000; background:#fcfcfc; }
			
			.header_dropdown .navigation > div { position:relative; float:left; padding:0px; border: 1px solid #e5e3e3; border-radius: 4px; background:#fcfcfc; margin:5px; }
			.header_dropdown .navigation > div ul,
			.header_dropdown .navigation > div ul li { display: inline-block; margin: 0px; padding: 0px; border: 0px; background: none; height: auto; }
			.header_dropdown .navigation ul:before { content:"\00BB"; color: #e5e3e3; font-size: 20px; line-height: 1; font-weight: bold; padding: 0px 4px; }
			.header_dropdown .navigation ul:first-child:before,
			.header_dropdown .navigation ul.dropdown:before { content:""; padding: 0px; }
			.header_dropdown .navigation ul li a { display: none; }
			.header_dropdown .navigation ul li.active a,
			.header_dropdown .navigation  > div.no-active-page ul:last-child li:first-child a,
			.header_dropdown .navigation ul.dropdown li a,
			.header_dropdown .navigation > .logout a,
			.header_dropdown .navigation > .logout .a  { position:relative; display: block; line-height:26px; height:26px; color:#0068B1; font-size:14px; text-align: left; font-weight: bold; padding:0px 8px; border:1px solid transparent; white-space: nowrap; }
			.header_dropdown .navigation ul li a:hover,
			.header_dropdown .navigation  > div.no-active-page ul:last-child li:first-child a:hover,
			.header_dropdown .navigation > .logout a:hover,
			.header_dropdown .navigation > .logout .a:hover { border: 1px solid #00497E; border-radius: 4px; background: #0068B1; cursor:pointer; color: #ffffff; text-decoration: none; }
			.header_dropdown .navigation ul.dropdown { position: absolute; display: block; margin: -1px 0px 0px -1px;  }
			.header_dropdown .navigation ul.dropdown li { display: block; background:#fcfcfc; border-left: 1px solid #e5e3e3; border-right: 1px solid #e5e3e3; }
			.header_dropdown .navigation ul.dropdown li:last-child { border-bottom: 1px solid #e5e3e3; border-bottom-left-radius: 4px; border-bottom-right-radius: 4px; }
			.header_dropdown .navigation ul.dropdown li:first-child { border-top: 1px solid #e5e3e3; border-left: 1px solid #fcfcfc; border-right: 1px solid #fcfcfc; border-bottom-left-radius: 0px; border-bottom-right-radius: 0px; }
			.header_dropdown .navigation ul.dropdown.first li:last-child { border-bottom-left-radius: 4px; }
			.header_dropdown .navigation ul.dropdown.last li:last-child { border-bottom-right-radius: 4px; }
			.header_dropdown .navigation ul.dropdown.first li:first-child { border-left: 1px solid #e5e3e3; border-top-left-radius: 4px; }
			.header_dropdown .navigation ul.dropdown.last li:first-child { border-right: 1px solid #e5e3e3; border-top-right-radius: 4px; }
			.header_dropdown .navigation ul.dropdown li.active a:not(:hover) { color: #515151; }

			.header_dropdown .navigation > .logout { float: right; height: auto; line-height: 28px; }
			.header_dropdown .navigation > .logout span { float: left; margin-left: 4px; }
			.header_dropdown .navigation > .logout span:first-child, .container .header_dropdown .navigation > .logout span.logout-options { margin-left: 0px; }
			.header_dropdown .navigation > .logout > span + span { font-size: 20px; color: #e5e3e3; font-weight: bold; }
			.header_dropdown .navigation > .logout > .logout-options > span:first-child { padding-left: 8px; font-size:14px; color: #000000; line-height: 28px; }
			';
		return $return;
	}

	public static function js() {

		$return = "SCRIPTER.static('.header_dropdown', function(elm_scripter) {
		
			elm_scripter.find('.toolbar-handle').toggle(function() {
				$(this).addClass('active');
				elm_scripter.find('.toolbar').clearQueue().delay(300).slideDown('fast');
			}, function() {
				$(this).removeClass('active');
				elm_scripter.find('.toolbar:visible').clearQueue().slideUp('fast');
			});
			
			elm_scripter.on('mouseenter', '.navigation ul:not(.dropdown) li', function() {
				var cur = $(this).parent('ul');
				cur.parent('div').children('.dropdown').remove();
				var index = (cur.index() == 0 ? ' first' : '');
				index = (cur.index() == cur.siblings().length ? index+' last' : index);
				var overlay = cur.clone().appendTo(cur.parent('div')).addClass('dropdown'+index).css({top: 0, left: cur.children('li').position().left}).on('mouseleave', function() {
					overlay.remove();
					cur.css('width', 'auto');
				});
				cur.css('width', Math.max(cur.width(), overlay.width()-parseFloat(cur.parent().css('border-top-width'))*2));
			});
			
			var elm_clock = elm_scripter.find('.clock');
			
			if (elm_clock.length) {
				
				var interval_clock = setInterval(function() {
					COMMANDS.quickCommand(elm_clock, elm_clock);
				}, 60000);
			}
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
		
		if ($method == "time") {
			
			$this->html = '<span>'.date("H:i").'</span>';
		}
	
		// QUERY 
		
	}
}
