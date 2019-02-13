<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class upload extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_upload');
		static::$parent_label = getLabel('ttl_input');
	}
	
	public function contents() {
				
		$return .= '<h1>'.getLabel('lbl_upload').' '.getLabel('lbl_file').'</h1>
		
		<form id="f:upload:upload_file-0">
			<fieldset><ul>
				<li><label></label><div>'.cms_general::createFileBrowser().'</div></li>
				<li><label></label><input type="submit" value="'.getLabel('lbl_upload').'" /></li>
			</ul></fieldset>
		</form>
		
		<div class="result"></div>';
		
		return $return;
	}
	
	public static function css() {
	
		$return = '
			.upload dl > li > dt { font-weight: bold; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.upload', function(elm_scripter) {
			
			elm_upload = elm_scripter.find('[id^=\"f\\\:upload\\\:upload_file\"]');
			
			COMMANDS.setOptions(elm_upload, {html: 'html', style: 'fade'});
			COMMANDS.setTarget(elm_upload, elm_scripter.find('.result'));
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		if ($method == "upload_file") {
			
			$file_upload = new FileStore($_FILES['file'], DIR_SITE_STORAGE.DIR_UPLOAD);
			
			$arr_result = $file_upload->getDetails();
						
			$this->html = '<h2>'.getLabel('lbl_result').'</h2>
			<div class="record"><dl>
				<li><dt>URL</dt><dd><a href="'.BASE_URL.DIR_UPLOAD.$arr_result['name'].'">'.BASE_URL.DIR_UPLOAD.$arr_result['name'].'</a></dd></li>
				<li><dt>Size</dt><dd>'.bytes2String($arr_result['size']).'</dd></li>
				<li><dt>Type</dt><dd>'.$arr_result['type'].'</dd></li>
			</dl></div>';
			
			msg(getLabel('msg_file_upload_successful').' Name: '.$arr_result['name'].' Size: '.bytes2String($arr_result['size']).' Type: '.$arr_result['type'], 'UPLOAD');

			$this->reset_form = true;
			$this->msg = true;
		}
	}
}
