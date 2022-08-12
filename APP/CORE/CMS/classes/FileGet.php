<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FileGet {
	
	private $async = false;
	
	private $url = '';
	private $url_redirect = '';
	
	private $file = false;
	private $path = false;
	private $get_body = true;
	private $external_protocol = false;
	
	private $num_redirect = 2;
	private $arr_request = [];
	private $num_error = false;
	private $str_error = '';
	
	private $arr_settings = [
		'timeout' => 10,
		'timeout_connect' => 5,
		'headers' => false,
		'secure' => true
	];
	
	private static $arr_external_protocols = ['http', 'https', 'ftp'];

	public function __construct($str_url, $arr_settings = [], $async = false) {
	
		$this->url = $str_url;
		$this->external_protocol = self::getExternalProtocol($this->url);
		
		$this->setConfiguration($arr_settings);
		
		$this->async = $async;
	}
			
	public function load() {
		
		if (!$this->external_protocol) {
			return false;
		}
		
		$this->get_body = true;
		$this->path = $this->storeExternalSource();
			
		if (!$this->path) {
			return false;
		}
		
		return true;
	}
	
	public function abort() {
		
		FileStore::deleteFile($this->path);
	}
	
	public function get() {
		
		if (!$this->external_protocol) {
			return false;
		}
		
		$this->get_body = true;
		$this->file = false;
				
		$result = $this->getExternalSource();

		return $result;
	}
	
	public function request() {
		
		if (!$this->external_protocol) {
			return false;
		}
		
		$this->get_body = false;
		$this->file = false;
	
		$result = $this->getExternalSource();

		return $result;
	}
	
	public function setConfiguration($arr_settings = [], $do_overwrite = false) {
		
		if ($do_overwrite) {
			
			$this->arr_settings = $arr_settings;
		} else if ($arr_settings) {

			$this->arr_settings = array_merge($this->arr_settings, $arr_settings);
		}
	}
	
	private function storeExternalSource() {
		
		$temp_path = tempnam(Settings::get('path_temporary'), '1100CC');
		$this->file = fopen($temp_path, 'w');
		
		$store = $this->getExternalSource();
		
		fclose($this->file);
		
		if (!$store) { // Something went wrong
			FileStore::deleteFile($temp_path);
			return false;
		}
		
		return $temp_path;
	}
	
	private function getExternalSource() {
		
		$do_continue = true;
		$has_redirect = false;
		$has_result = true;
		
		$str_url = ($this->url_redirect ?: $this->url);
		
		$curl = curl_init($str_url);
		
		$arr_proxy = Settings::get('proxy');
		
		if ($arr_proxy) {
			
			curl_setopt($curl, CURLOPT_PROXY, $arr_proxy['url']);
			curl_setopt($curl, CURLOPT_PROXYUSERPWD, (!empty($arr_proxy['user']) ? Settings::getSafeText($arr_proxy['user']).':'.Settings::getSafeText($arr_proxy['password']) : null));
			
			curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, false);
			curl_setopt($curl, CURLOPT_SUPPRESS_CONNECT_HEADERS, true); // Suppress CONNECT response in header output
		}
		
		if ($this->arr_settings['timeout_connect']) {
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->arr_settings['timeout_connect']);
		}
		if ($this->arr_settings['timeout']) {
			curl_setopt($curl, CURLOPT_TIMEOUT, $this->arr_settings['timeout']);
		}
		if ($this->arr_settings['headers']) {
			
			$arr_headers = [];
			
			foreach ($this->arr_settings['headers'] as $key => $value) {
				
				$key = trim($key);
				$value = trim($value);
				
				if (!$key) {
					continue;
				}
				
				$arr_headers[] = $key.': '.$value;
			}
			
			if ($arr_headers) {
				curl_setopt($curl, CURLOPT_HTTPHEADER, $arr_headers);
			}
		}
		if ($this->arr_settings['post']) {
			
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $this->arr_settings['post']);
		}
		
		curl_setopt($curl, CURLOPT_ENCODING, ''); // Accept any supported encoding
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, (bool)$this->arr_settings['secure']);
		
		curl_setopt($curl, CURLOPT_USERAGENT, Labels::getServerVariable('user_agent'));
		
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $str_header) use (&$do_continue, &$has_redirect, &$has_result) { // Check each header for errors
			
			$code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
			
			if ($code != '200') { // Should be found (200) to be stored
				
				$do_continue = (!$this->file ? true : false); // Continue to capture possible error message, only when not storing to file
				$has_result = false;
			}
			
			if ($code == '301' || $code == '302') { // Source is redirected/moved
				
				$do_continue = true;
				
				$arr_matches = [];
				preg_match('/^(Location:|URI:)/', $str_header, $arr_matches);
				
				if ($arr_matches) {
					
					$str_url = trim(str_replace($arr_matches[1], '', $str_header));
					
					if (parse_url($str_url)) {
						
						$do_continue = false;
						$has_redirect = true;
						
						$this->url_redirect = $str_url;
					}
				}
			}
			
			if (!empty($this->arr_settings['header_callback'])) {
				
				$this->arr_settings['header_callback']($str_header);
			}
			
			return ($do_continue ? strlen($str_header) : -1);
		});
				
		if ($this->file) {
			
			curl_setopt($curl, CURLOPT_FILE, $this->file); // Return response to $file
		} else {
			
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Return response to $result
			
			if (!$this->get_body) {
				curl_setopt($curl, CURLOPT_NOBODY, true);
			}
		}
		
		curl_setopt($curl, CURLINFO_HEADER_OUT, true); // Include request header in $arr_request
		
		if ($this->async) {
			
			$mh = curl_multi_init();
			curl_multi_add_handle($mh, $curl);
			
			$running = false;
			
			// Execute/initiate the handles
			do {
				$mrc = curl_multi_exec($mh, $running);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			
			onUserPollContinuous(function() use (&$running, &$mrc, $mh) {
				
				// Perform the handles' actions
				if ($running && $mrc == CURLM_OK) {
					
					curl_multi_select($mh, 1); // Wait for activity on the curl connection, 1 second
						
					do {
						$mrc = curl_multi_exec($mh, $running);
					} while ($mrc == CURLM_CALL_MULTI_PERFORM);
				} else {
					
					$running = false;
				}
				
				return !$running;
			}, function() use (&$has_result) {
				
				$has_result = false;
			});
						
			$result = (!$this->file ? curl_multi_getcontent($curl) : false);
			
			$arr_info = curl_multi_info_read($mh);
			$this->num_error = ($arr_info['result'] ?: (!$has_result ? true : false));
			
			if ($this->num_error) {
				
				$this->str_error = $result;
				$result = false;
			}
			
			$this->arr_request = curl_getinfo($curl);
			
			curl_multi_remove_handle($mh, $curl);
			
			curl_close($curl);
			
			curl_multi_close($mh);
		} else {
		
			$result = curl_exec($curl);
			
			$this->num_error = (curl_errno($curl) ?: (!$has_result ? true : false));
			
			if ($this->num_error) {
				
				$this->str_error = $result;
				$result = false;
			}
			
			$this->arr_request = curl_getinfo($curl);
			
			curl_close($curl);
		}
				
		if ($has_redirect && $this->num_redirect > 0) {
			
			$this->num_redirect--;

			$result = $this->getExternalSource();
			$has_result = (bool)$result;
		}
		
		if ($this->file) {
			return $has_result;
		} else {
			return ($has_result ? $result : false);
		}
	}

	public static function getExternalProtocol($str_url) {
		
		$arr_protocol_url = explode('://', $str_url);
		$str_protocol = (!empty($arr_protocol_url[1]) ? $arr_protocol_url[0] : false);
		$str_protocol = ($str_protocol && in_array($str_protocol, self::$arr_external_protocols) ? $str_protocol : false);
		
		return $str_protocol;
	}
	
	public function getSource() {
				
		return $this->url;
	}
	
	public function getPath() {
				
		return $this->path;
	}
	
	public function getRequest() {
		
		return $this->arr_request;
	}
	
	public function getError() {
		
		if ($this->num_error === true) {
			return 'http_code';
		} else if ($this->num_error == 28) {
			return 'timeout';
		} else {
			return $this->num_error;
		}
	}
	
	public function getErrorResponse($do_snippet = true) {
		
		if (!$this->num_error) {
			return '';
		}
		
		if (!$do_snippet) {
			return $this->str_error;
		}
		
		$str_error = trim($this->str_error);
		
		if ($str_error) {
			
			$str_error = strEscapeHTML((mb_strlen($str_error) > 200 ? mb_substr($str_error, 0, 200).' [...]' : $str_error));
		} else {
			
			$str_error = $this->getError();
			
			if (!is_numeric($str_error)) {
				
				if ($str_error == 'http_code') {
					Labels::setVariable('code', $this->arr_request['http_code']);
				}
				
				$str_error = getLabel('msg_file_get_error_'.$str_error);
			} else {
				
				$str_error = getLabel('msg_file_get_error');
			}
		}
		
		return $str_error;
	}
}
