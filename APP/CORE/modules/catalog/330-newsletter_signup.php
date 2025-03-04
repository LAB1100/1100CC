<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class newsletter_signup extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_newsletter_signup');
		static::$parent_label = getLabel('ttl_input');
	}
		
	public function contents() {
		
		$arr_link = newsletters::findMainNewsletters();
		
		$return .= '<h1>'.getLabel('ttl_newsletter_signup').'</h1>
			<form id="f:newsletter_signup:signup-0" autocomplete="on">
			<p>'.getLabel('txt_newsletter_signup').' <a href="'.SiteStartEnvironment::getPageURL($arr_link['page_name'], $arr_link['sub_dir']).'">'.getLabel('lnk_archive').'</a></p>
			<fieldset><ul>		
				<li><label>'.getLabel('lbl_name').'</label><input name="name" type="text" /></li>
				<li><label>'.getLabel('lbl_email').'</label><input name="email" type="text" /></li>
				<li><label></label><input type="submit" value="'.getLabel('lbl_signup').'" /></li>
			</ul></fieldset>
		</form>';
		
		$this->validate = ['name' => 'required', 'email' => ['required' => true, 'email' => true]];
		
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
	
		if ($method == 'signup') {
			
			if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				error(getLabel('msg_invalid_email'));
			}
			
			cms_newsletters::addEmailAddress($_POST['email'], $_POST['name']);
			
			$this->msg = true;
			$this->reset_form = true;
		}
	}
}
