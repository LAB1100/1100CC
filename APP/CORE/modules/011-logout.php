<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class logout extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_logout');
		static::$parent_label = getLabel('lbl_users');
	}

	protected static $arr_elements_extra = [];
	protected static $str_url_logout = null;
	
	public static function addElement($class, $html) {
		
		static::$arr_elements_extra[$class] = $html;
	}
	
	public static function setLogoutURL($str_url) {
		
		static::$str_url_logout = $str_url;
	}
	
	public function contents() {

		if (empty($_SESSION['USER_ID'])) {
			return '';
		}
		
		$str_html = '';
		
		$arr_account_link = account::findAccount();
		
		if (class_exists('messaging')) {
			$arr_messaging_link = messaging::findMessaging();
			$num_unread_count = messaging::getUnreadMessages();
		}
		
		$domain = strEscapeHTML(($_SESSION['CUR_USER'][DB::getTableName('VIEW_USER_PARENT')]['parent_name'] ?: $_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_GROUPS')]['name']));
		$name = strEscapeHTML($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['name']);

		$str_html .= '<ul>'
			.'<li class="info"><div><span title="'.$domain.'">'.$domain.'</span><span title="'.$name.'">'.$name.'</span></div></li>';
			
			foreach (static::$arr_elements_extra as $class => $html) {
				$str_html .= '<li class="'.$class.'">'.$html.'</li>';
			}
			
			if ($arr_messaging_link && pages::filterClearance([$arr_messaging_link], $_SESSION['USER_GROUP'], $_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')])) {
				$str_html .= '<li class="messaging"><a title="'.getLabel('lbl_messaging').'" href="'.SiteStartEnvironment::getPageURL($arr_messaging_link['page_name'], $arr_messaging_link['sub_dir']).'"><span class="icon">'.getIcon('message').'</span>'.($num_unread_count ? '<sup>'.$num_unread_count.'</sup>' : '').'</a></li>';
			}
			if ($arr_account_link && pages::filterClearance([$arr_account_link], $_SESSION['USER_GROUP'], $_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')])) {
				$str_html .= '<li class="account"><a title="'.getLabel('lbl_account').'" href="'.SiteStartEnvironment::getPageURL($arr_account_link['page_name'], $arr_account_link['sub_dir']).'"><span class="icon">'.getIcon('settings').'</span></a></li>';
			}
			
			$str_url_logout = static::$str_url_logout;
			
			if (is_callable($str_url_logout)) {
				$str_url_logout = $str_url_logout();
			}
			
			if (!$str_url_logout) {
				$str_url_logout = SiteStartEnvironment::getBasePath().'logout.l';
			}
			
			$str_html .= '<li class="logout-button"><span title="'.getLabel('lbl_logout').'" class="a"><span class="icon">'.getIcon('logout').'</span></span></li><li class="logout-options hide"><span>'.getLabel('lbl_logout').'?</span><span><a href="'.$str_url_logout.'">'.getLabel('lbl_yes').'</a></span><span>|</span><span class="no a">'.getLabel('lbl_no').'</span></li>'
		.'</ul>';
		
		return $str_html;
	}
	
	public static function css() {
	
		$return = '.logout { height: 30px; line-height: 30px; padding: 0px 15px; text-align:center; font-weight:bold; color: #ffffff; background-color: #000000; float: right;}
				.logout li { display: inline-block; margin-left: 12px; }
				.logout li > a,
				.logout li > .a,
				.logout li.info > div { line-height:1; display: inline-block; vertical-align: middle; text-decoration: none; }
				.logout a:not(:hover),
				.logout .a:not(:hover) { color: #ffffff; }
				.logout a:hover,
				.logout .a:hover { text-decoration: underline; }
				.logout li > a > sup { margin-top: -4px; }
				.logout li:first-child,
				.logout li.logout-options { margin-left: 0px; }
				.logout li.logout-options > span { margin-left: 5px; vertical-align: middle; }
				.logout li.logout-options > span:first-child { margin-left: 0px; }
				.logout li.info { text-align: left; }
				.logout li.info > div > span { display: block; line-height: 1.1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
				.logout li.info > div > span:first-child { font-size: 10px; }
				.logout li.info > div > span:first-child + span { }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.logout', function(elm_scripter) {
		
			const elm_logout_options = elm_scripter.find('ul > li.logout-options');
			const elms_options = elm_scripter.find('ul > li:not(.logout-options)');
		
			elm_scripter.find('.logout-button').on('click', function() {
				elm_logout_options.removeClass('hide');
				elms_options.addClass('hide');
			});
			
			elm_scripter.on('click', '.logout-options .no', function() {
				elm_logout_options.addClass('hide');
				elms_options.removeClass('hide');
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
			
	}
}
