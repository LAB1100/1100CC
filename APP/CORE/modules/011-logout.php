<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class logout extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_logout');
		static::$parent_label = getLabel('lbl_users');
	}

	private static $arr_elements_extra = [];
	
	public static function addElement($class, $html) {
		
		self::$arr_elements_extra[$class] = $html;
	}

	public function contents() {
		
		$return = '';
	
		if ($_SESSION['USER_ID']) {
		
			$arr_account_link = account::findAccount();
			
			if (class_exists('messaging')) {
				$arr_messaging_link = messaging::findMessaging();
				$unread_count = messaging::getUnreadMessages();
			}
			
			$domain = strEscapeHTML(($_SESSION['CUR_USER'][DB::getTableName('VIEW_USER_PARENT')]['parent_name'] ?: $_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_GROUPS')]['name']));
			$name = strEscapeHTML($_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['name']);

			$return .= '<ul>';
			$return .= '<li class="info"><div><span title="'.$domain.'">'.$domain.'</span><span title="'.$name.'">'.$name.'</span></div></li>';
			foreach (self::$arr_elements_extra as $class => $html) {
				$return .= '<li class="'.$class.'">'.$html.'</li>';
			}
			$return .= ($arr_messaging_link && pages::filterClearance([$arr_messaging_link], $_SESSION['USER_GROUP'], $_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]) ? '<li class="messaging"><a title="'.getLabel('lbl_messaging').'" href="'.SiteStartVars::getPageUrl($arr_messaging_link['page_name'], $arr_messaging_link['sub_dir']).'"><span class="icon">'.getIcon('message').'</span>'.($unread_count ? '<sup>'.$unread_count.'</sup>' : '').'</a></li>' : '');
			$return .= ($arr_account_link && pages::filterClearance([$arr_account_link], $_SESSION['USER_GROUP'], $_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]) ? '<li class="account"><a title="'.getLabel('lbl_account').'" href="'.SiteStartVars::getPageUrl($arr_account_link['page_name'], $arr_account_link['sub_dir']).'"><span class="icon">'.getIcon('settings').'</span></a></li>' : '');
			$return .= '<li class="logout-button"><span title="'.getLabel('lbl_logout').'" class="a"><span class="icon">'.getIcon('logout').'</span></span></li><li class="logout-options"><span>'.getLabel('lbl_logout').'?</span><span><a href="'.SiteStartVars::getBasePath().'logout.l">'.getLabel('lbl_yes').'</a></span><span>|</span><span class="no a">'.getLabel('lbl_no').'</span></li>';
			$return .= '</ul>';
		}
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.logout { height: 30px; line-height: 30px; padding: 0px 15px; text-align:center; font-weight:bold; color: #ffffff; background-color: #000000; float: right;}
				.logout:hover { background-color: #ffffff; }
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
				.logout li.logout-options { display: none; }
				.logout li.info { text-align: left; }
				.logout li.info > div > span { display: block; line-height: 1.1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
				.logout li.info > div > span:first-child { font-size: 10px; }
				.logout li.info > div > span:first-child + span { }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.logout', function(elm_scripter) {
		
			elm_scripter.find('.logout-button').on('click', function() {
				elm_scripter.find('ul > li.logout-options').show();
				elm_scripter.find('ul > li:not(.logout-options)').hide();
			});
			
			elm_scripter.on('click', '.logout-options .no', function() {
				elm_scripter.find('ul > li.logout-options').hide();
				elm_scripter.find('ul > li:not(.logout-options)').show();
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
			
	}
}
