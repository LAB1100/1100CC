<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class Mail {
	
	private $from_1100cc = '';
	private $from_name = '';
	private $str_header = '';
	private $str_footer = '';
	
	private $from = '';
	private $str_subject = '';
	private $str_message = '';
	private $str_tag = '';
	private $arr_headers = [];
	private $arr_to = [];
	private $arr_to_variables = [];
	private $arr_attachments = [];
	
	public function __construct($email = false, $str_subject = false, $str_message = false) {
		
		if ($email) {
			$this->to($email);
		}
		if ($str_subject) {
			$this->subject($str_subject);
		}
		if ($str_message) {
			$this->message($str_message);
		}
		
		$this->from_1100cc = getLabel('email_1100cc', 'D');
		$this->from = $this->from_1100cc;
		$this->from_name = getLabel('name', 'D');
		
		$this->str_header = getLabel('email_header', 'D');
		$this->str_footer = getLabel('email_footer', 'D');
	}

	public function subject($str_subject) {
		
		$this->str_subject = Labels::parseTextVariables($str_subject);
	}
	
	public function message($str_message) {
		
		$this->str_message = Labels::parseTextVariables($str_message);
	}
	
	public function to($to, $arr_to_variables = false) {
		
		if (is_array($to)) {
			$this->arr_to = array_merge($this->arr_to, $to);
			if ($arr_to_variables) {
				$this->arr_to_variables = array_merge($this->arr_to_variables, $arr_to_variables);
			}
		} else {
			$this->arr_to[] = Labels::printLabels(Labels::parseTextVariables($to));
			if ($arr_to_variables) {
				$this->arr_to_variables[] = $arr_to_variables;
			}
		}
		
		if ($this->arr_to_variables) { // Set variable to a default empty value to make it renderable
			
			$arr_collect = [];
			
			foreach ($this->arr_to_variables as $to => $arr_variables) {
				foreach ($arr_variables as $variable => $value) {
					$arr_collect[$variable] = $variable;
				}
			}
			
			foreach ($arr_collect as $variable) {
				Labels::setVariable($variable, false);
			}
		}
	}

	public function from($from) {
		
		$this->from = Labels::parseTextVariables($from);
	}

	public function attachment($arr_attachments) {
		
		// $arr_attachments = array("filename" => file/string)
		
		$this->arr_attachments = array_merge($this->arr_attachments, $arr_attachments);
	}
	
	public function header($header) {
		
		if (is_array($header)) {
			$this->arr_headers = array_merge($this->arr_headers, $header);
		} else {
			$this->arr_headers[] = $header;
		}
	}
	
	public function tag($str_tag) {
		
		$this->str_tag = $str_tag;
	}
	
	public function send() {
		
		$from_1100cc = Labels::printLabels($this->from_1100cc);
		$from = Labels::printLabels($this->from);
		$from_name = Labels::printLabels($this->from_name);
		$str_subject = Labels::printLabels($this->str_subject);
		$str_message_header = Labels::printLabels($this->str_header);
		$str_message_footer = Labels::printLabels($this->str_footer);
		
		$str_message_raw = $str_message_header.$this->str_message.$str_message_footer;
		$str_message_raw = parseBody($str_message_raw);
		
		$cur_format = Response::getFormat();
		Response::setFormat(Response::OUTPUT_XML | Response::RENDER_HTML);
		
		$str_message_raw = Response::parse($str_message_raw);
		
		Response::setFormat($cur_format); // Restore output format
		
		$semi_rand = md5(time());
		$boundary_mixed = "==_MESSAGE_BOUNDARY_MIXED_".$semi_rand."_".$this->str_tag;
		$boundary_alt = "==_MESSAGE_BOUNDARY_".$semi_rand."_".$this->str_tag;

		if ($this->arr_attachments) {
			
			$str_attachments = '';
		
			foreach ($this->arr_attachments as $key => $value) {
						
				$data = (is_file($value) ? file_get_contents($value) : $value);
				$data_encoded = chunk_split(base64_encode($data));
				
				$str_attachments .= "--".$boundary_mixed.EOL_EXCHANGE;
				$str_attachments .= "Content-Type: application/octet-stream; name=\"".$key."\"".EOL_EXCHANGE;
				$str_attachments .= "Content-Disposition: attachment; filename=\"".$key."\"".EOL_EXCHANGE;
				$str_attachments .= "Content-Transfer-Encoding: base64".EOL_EXCHANGE.EOL_EXCHANGE;
				
				$str_attachments .= $data_encoded.EOL_EXCHANGE.EOL_EXCHANGE;
			}
			
			$str_attachments .= "--".$boundary_mixed."--".EOL_EXCHANGE.EOL_EXCHANGE;
		}

		foreach ($this->arr_to as $key => $email) {
			
			$str_headers = "From: ".$from_name." <".$from.">".EOL_EXCHANGE;
			$str_headers .= "X-Priority: Normal".EOL_EXCHANGE;
			foreach ($this->arr_headers as $header => $value) {
				$str_headers .= $header.": ".$value.EOL_EXCHANGE;
			}
			$str_headers .= "MIME-Version: 1.0".EOL_EXCHANGE;
			
			$str_message = $str_message_raw;
			
			if ($this->arr_to_variables[$key]) {
				
				foreach ($this->arr_to_variables[$key] as $variable => $value) {
					Labels::setVariable($variable, $value);
				}
				
				$str_headers = Labels::printLabels(Labels::parseTextVariables($str_headers));
				$str_message = Labels::printLabels(Labels::parseTextVariables($str_message));
			}
			
			$message_text = new FormatHTML2Text($str_message, ['width' => 70, 'break' => EOL_EXCHANGE, 'cut' => true]);
			$str_message_text = $message_text->getText();
			
			$str_message_html = strWrap($str_message, 70, EOL_EXCHANGE, true);

			$str_body = '';

			if ($this->arr_attachments) {
				$str_headers .= "Content-Type: multipart/mixed; boundary=\"".$boundary_mixed."\"".EOL_EXCHANGE.EOL_EXCHANGE;
				$str_body .= "--".$boundary_mixed.EOL_EXCHANGE;
				$str_body .= "Content-Type: multipart/alternative; boundary=\"".$boundary_alt."\"".EOL_EXCHANGE.EOL_EXCHANGE;
			} else {
				$str_headers .= "Content-Type: multipart/alternative; boundary=\"".$boundary_alt."\"".EOL_EXCHANGE.EOL_EXCHANGE;
			}
		
			$str_body .= "--".$boundary_alt.EOL_EXCHANGE;
			$str_body .= "Content-Type: text/plain; charset=UTF-8".EOL_EXCHANGE;
			$str_body .= "Content-Transfer-Encoding: 8bit".EOL_EXCHANGE.EOL_EXCHANGE;
		
			$str_body .= $str_message_text.EOL_EXCHANGE.EOL_EXCHANGE;
			
			$str_body .= "--".$boundary_alt.EOL_EXCHANGE;
			$str_body .= "Content-Type: text/html; charset=UTF-8".EOL_EXCHANGE;
			$str_body .= "Content-Transfer-Encoding: 8bit".EOL_EXCHANGE.EOL_EXCHANGE;
			
			$str_body .= $str_message_html.EOL_EXCHANGE.EOL_EXCHANGE;

			$str_body .= "--".$boundary_alt."--".EOL_EXCHANGE.EOL_EXCHANGE;
			
			$str_body .= ($str_attachments ?: '');
			
			try {
				
				mail($email, $str_subject, $str_body, $str_headers, "-f".$from_1100cc.""); //  Last parameter, set the Return-Path
			} catch (Exception $e) {
				
				error('Mail ERROR: '.$e->getMessage(), TROUBLE_NOTICE, LOG_BOTH, false, $e); // Make notice
			}
		}
	}
}
