<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class contact_form extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_contact').' '.getLabel('lbl_form');
		static::$parent_label = getLabel('lbl_communication');
	}
	
	public static function moduleVariables() {
		
		$return = '<input type="checkbox" name="show_text" title="Show form text" value="1" />';
		
		return $return;
	}
	
	public function contents() {

		$return = '<form id="f:contact_form:form-0" autocomplete="on">
			<fieldset><ul>
				'.($this->arr_variables['show_text'] ? '<li><label></label><span>'.getLabel('txt_contact_form_info').'</span></li>' : '').'
				<li><label>'.getLabel('lbl_name').'</label><input name="name" type="text" value="" placeholder="'.getLabel('lbl_name').'" /></li>
				<li><label>'.getLabel('lbl_email').'</label><input name="email" type="text" value="" placeholder="'.getLabel('lbl_email').'" /></li>
				<li><label>'.getLabel('lbl_message').'</label><textarea name="body" placeholder="'.getLabel('lbl_message').'"></textarea></li>
				<li><label></label><div><input type="submit" value="Ok" class="invalid" /><input type="submit" value="'.getLabel('lbl_send').'" /><input type="submit" value="Ok" class="invalid" /></div></li>
			</ul></fieldset>
		</form>';
		
		$this->validate = ['name' => 'required', 'email' => 'required', 'body' => 'required'];
				
		return $return;
	}
	
	public static function css() {
	
		$return = '.contact_form form { overflow: hidden; }
				.contact_form form fieldset textarea { height: 200px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// QUERY
		
		if ($method == "form") {

			if (!$_POST['name'] || !$_POST['body'] || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				error(getLabel('msg_missing_information'));
			}
			
			$name = $_POST['name'];
			$email = $_POST['email'];
			$body = $_POST['body'];
			
			$mail = new Mail();
			$mail->to(getLabel('email', 'D'));
			$mail->subject(getLabel('name', 'D').' Contact Form');
			$mail->message($body);
			$mail->header(['Reply-To' => $name.' <'.$email.'>']);
			
			$mail->send();

			Labels::setVariable('from', $name);
			Labels::setVariable('email', $email);
			msg(getLabel('msg_contact_mail_sent'), 'CONTACT');
			
			$this->message = true;
			$this->reset_form = true;
		}
	}
}
