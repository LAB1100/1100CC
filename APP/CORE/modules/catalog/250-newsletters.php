<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class newsletters extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_newsletters');
		static::$parent_label = getLabel('lbl_communication');
	}
	
	public static function searchProperties() {
	
		return [
			'trigger' => [DB::getTable('TABLE_NEWSLETTERS'), DB::getTable('TABLE_NEWSLETTERS').'.body'],
			'title' => [DB::getTable('TABLE_NEWSLETTERS'), DB::getTable('TABLE_NEWSLETTERS').'.title'],
			'search_var' => false,
			'module_link' => [
				[DB::getTable('TABLE_NEWSLETTERS'), DB::getTable('TABLE_NEWSLETTERS').'.id']
			],
			'module_var' => false,
			'module_query' => function($arr_result) {
				return '/'.$arr_result['object_link'].'/'.str2URL($arr_result['title']);
			}
		];
	}
		
	public function contents() {
		
		if ($this->arr_query[0] == 'unsubscribe' && (int)$this->arr_query[1] && filter_var($this->arr_query[2], FILTER_VALIDATE_EMAIL)) {
		
			$return = '<h1>'.getLabel('ttl_newsletters').'</h1>';
			
			Labels::setVariable('email', $this->arr_query[2]);
			$return .= '<form id="f:newsletters:unsubscribe-'.$this->arr_query[1].'">
				<input type="hidden" name="email" value="'.$this->arr_query[2].'" />
				<p>'.getLabel('conf_email_opt_out').'</p>
				<input type="submit" value="'.getLabel('lbl_opt_out').'" />';
			$return .= '</form>';
			
			return $return;
		}
		
		if ((int)$this->arr_query[0]) {
			
			$arr_newsletter = cms_newsletters::getNewsletters($this->arr_query[0]);
			
			$return .= '<div class="content">';
			$return .= '<h1>'.strEscapeHTML(Labels::parseTextVariables($arr_newsletter['title'])).'</h1>';
			$return .= cms_general::createIframeDynamic($arr_newsletter['body']);
			//$return .= '<div class="body">'.parseBody($arr_newsletter["body"]).'</div>';
			$return .= '</div>';
			
			$return .= $body;
			
			return $return;
		}
		
		$arr_newsletters = cms_newsletters::getNewsletters();
		
		if ($arr_newsletters) {
		
			$return = '<h1>'.getLabel('ttl_newsletters').'</h1>';
			
			$return .= '<ul>';
			foreach ($arr_newsletters as $value) {
				$title = Labels::parseTextVariables($value['title']);
				$return .= '<li>'.createDate($value['date']).'<a href="'.SiteStartVars::getModUrl($this->mod_id).$value['id'].'/'.str2URL($title).'">'.$title.'</a></li>';
			}
			$return .= '</ul>';
		}
								
		return $return;
	}
	
	public static function css() {
	
		$return = '.newsletters > ul { margin: -4px -8px; display: table; border-collapse: separate; border-spacing: 8px 4px; }
					.newsletters > ul > li { display: table-row; }
					.newsletters > ul > li > a { display: table-cell; }
					.newsletters > ul > li > time { display: table-cell; }
					.newsletters > ul > li > time > span { font-size: 1.2em; display: inline; margin-left: 2px; }
					.newsletters > ul > li > time > span:first-child { margin-left: 0px; }
					
					.newsletters > .content { position: relative; }
					.newsletters > .content > h1 { margin-top: 0px; }
					.newsletters > .content > iframe { width: 100%; height: 100%; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY
		if ($method == "unsubscribe" && (int)$id) {
		
			if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				error(getLabel('msg_invalid_email'));
			}
			
			$res = DB::query("SELECT
				email
					FROM ".DB::getTable('TABLE_EMAIL_ADDRESSES')."
				WHERE email = '".DBFunctions::strEscape($_POST['email'])."'
			");
			
			if ($res->getRowCount()) {
				
				$res = DB::query("INSERT INTO ".DB::getTable('TABLE_EMAIL_ADDRESSES_OPT_OUT')."
					(email)
						VALUES
					('".DBFunctions::strEscape($_POST['email'])."')
					".DBFunctions::onConflict('email', ['email'])."
				");
				
				if ($res->getAffectedRowCount()) {
					cms_newsletters::addNewsletterOptOut($id);
				}
			}
			
			$this->html = '<p>'.getLabel('msg_email_opt_out_success').'</p>';
		}
	}
	
	public static function findMainNewsletters() {
	
		return pages::getClosestMod('newsletters', SiteStartVars::$dir['id'], SiteStartVars::$page['id']);
	}
}
