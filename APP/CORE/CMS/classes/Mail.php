<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class Mail {
	
    private $from_1100cc = '';
	private $from_name = '';
	private $header = '';
	private $footer = '';
	
	private $from = '';
	private $subject = '';
	private $message = '';
	private $tag = '';
	private $arr_headers = [];
	private $arr_to = [];
	private $arr_vars = [];
	private $arr_attachments = [];

    public function __construct($email = false, $subject = false, $message = false) {
		
		if ($email) {
			$this->to($email);
		}
		if ($subject) {
			$this->subject($subject);
		}
		if ($message) {
			$this->message($message);
		}
		
		$this->from_1100cc = getLabel('email_1100cc', 'D');
		$this->from = $this->from_1100cc;
		$this->from_name = getLabel('name', 'D');
		
		$this->header = getLabel('email_header', 'D');
		$this->footer = getLabel('email_footer', 'D');
    }
    
    public function subject($subject) {
		
		$this->subject = Labels::parseTextVariables($subject);
	}
	
	public function message($message) {
		
		$this->message = Labels::parseTextVariables($message);
	}
	
    public function to($to, $arr_vars = false) {
		
		if (is_array($to)) {
			$this->arr_to = array_merge($this->arr_to, $to);
			if ($arr_vars) {
				$this->arr_vars = array_merge($this->arr_vars, $arr_vars);
			}
		} else {
			$this->arr_to[] = Labels::printLabels(Labels::parseTextVariables($to));
			if ($arr_vars) {
				$this->arr_vars[] = $arr_vars;
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
	
	public function tag($tag) {
		
		$this->tag = $tag;
	}
    
    public function send() {
		
		$from_1100cc = Labels::printLabels($this->from_1100cc);
		$from = Labels::printLabels($this->from);
		$from_name = Labels::printLabels($this->from_name);
		$subject = Labels::printLabels($this->subject);
		$header = Labels::printLabels($this->header);
		$footer = Labels::printLabels($this->footer);
		
		$message = $header.$this->message.$footer;
		$message = parseBody($message);
		
		$cur_format = Response::getFormat();
		Response::setFormat(Response::OUTPUT_HTML | Response::RENDER_HTML);
		
		$message = Response::parse($message);
		
		Response::setFormat($cur_format); // Restore output format
		
		$semi_rand = md5(time());
		$boundary_mixed = "==_MESSAGE_BOUNDARY_MIXED_".$semi_rand."_".$this->tag;
		$boundary_alt = "==_MESSAGE_BOUNDARY_".$semi_rand."_".$this->tag;

		if ($this->arr_attachments) {
			
			$attachments = '';
		
			foreach ($this->arr_attachments as $key => $value) {
						
				$data = (is_file($value) ? file_get_contents($value) : $value);
				$data_encoded = chunk_split(base64_encode($data));
				
				$attachments .= "--".$boundary_mixed."\n";
				$attachments .= "Content-Type: application/octet-stream; name=\"".$key."\"\n";
				$attachments .= "Content-Disposition: attachment; filename=\"".$key."\"\n";
				$attachments .= "Content-Transfer-Encoding: base64\n\n";
				
				$attachments .= $data_encoded."\n\n";
			}
			
			$attachments .= "--".$boundary_mixed."--\n\n";
		}
		
		$message_html = wordWrapMB($message);
		
		$message_text = new FormatHTML2Text($message);
		$message_text = $message_text->getText();
		
		foreach ($this->arr_to as $key => $email) {
			
			$body = '';
			$headers = "From: ".$from_name." <".$from.">\n";
			$headers .= "X-Priority: Normal\n";
			foreach ($this->arr_headers as $header => $value) {
				$headers .= $header.": ".$value."\n";
			}
			$headers .= "MIME-Version: 1.0\n";
			
			if ($this->arr_attachments) {
				$headers .= "Content-Type: multipart/mixed; boundary=\"".$boundary_mixed."\"\n\n";
				$body .= "--".$boundary_mixed."\n";
				$body .= "Content-Type: multipart/alternative; boundary=\"".$boundary_alt."\"\n\n";
			} else {
				$headers .= "Content-Type: multipart/alternative; boundary=\"".$boundary_alt."\"\n\n";
			}
		
			$body .= "--".$boundary_alt."\n";
			$body .= "Content-Type: text/plain; charset=UTF-8\n";
			$body .= "Content-Transfer-Encoding: 8bit\n\n";
		
			$body .= $message_text."\n\n";
			
			$body .= "--".$boundary_alt."\n";
			$body .= "Content-Type: text/html; charset=UTF-8\n";
			$body .= "Content-Transfer-Encoding: 8bit\n\n";
			
			$body .= $message_html."\n\n";

			$body .= "--".$boundary_alt."--\n\n";
						
			if ($this->arr_vars[$key]) {
				
				foreach ($this->arr_vars[$key] as $var => $value) {
					Labels::setVariable($var, $value);
				}
				
				$headers = Labels::printLabels(Labels::parseTextVariables($headers));
				$body = Labels::printLabels(Labels::parseTextVariables($body));
			}
			
			$body .= ($attachments ?: "");
			
			try {
				
				mail($email, $subject, $body, $headers, "-f".$from_1100cc.""); //  Last parameter, set the Return-Path
			} catch (Exception $e) {
				
				error('Mail ERROR: '.$e->getMessage(), TROUBLE_NOTICE, LOG_BOTH, false, $e); // Make notice
			}
		}
    }
}
