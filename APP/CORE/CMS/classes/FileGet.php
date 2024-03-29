<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FileGet {
	
	const PROTOCOL_LOCAL = 0;
	const PROTOCOL_EXTERNAL = 1;
	const PROTOCOL_DATA_URL = 2;
	
	protected $async = false;
	
	protected $url = '';
	protected $url_redirect = '';
	protected $mode_protocol = self::PROTOCOL_LOCAL;
	
	protected $file = false;
	protected $filename = false;
	protected $path = false;
	
	protected $get_body = true;
	protected $num_redirect = 2;
	protected $arr_request = [];
	protected $num_error = false;
	protected $str_error = '';
	
	protected $arr_settings = [
		'timeout' => 10,
		'timeout_connect' => 5,
		'headers' => false,
		'secure' => true,
		'redirect' => null
	];
	
	protected static $arr_external_protocols = ['http', 'https', 'ftp'];

	public function __construct($str_url, $arr_settings = [], $async = false) {
	
		$this->url = $str_url;
		$this->mode_protocol = static::getProtocol($this->url);
		
		$this->setConfiguration($arr_settings);
		
		$this->async = $async;
	}
	
	public function load() {
		
		if ($this->mode_protocol == static::PROTOCOL_LOCAL) {
			return false;
		} else if ($this->mode_protocol == static::PROTOCOL_DATA_URL) {
			
			$this->path = $this->storeDataURL();
		} else {
			
			$this->get_body = true;
			
			$this->path = $this->storeExternalSource();
		}
		
		if (!$this->path) {
			return false;
		}
		
		return true;
	}
	
	public function abort() {
		
		FileStore::deleteFile($this->path);
	}
	
	public function get() {
		
		if ($this->mode_protocol == static::PROTOCOL_LOCAL) {
			return false;
		} else if ($this->mode_protocol == static::PROTOCOL_DATA_URL) {

			$this->file = false;
				
			$result = $this->getDataURL();
		} else {
		
			$this->get_body = true;
			$this->file = false;
				
			$result = $this->getExternalSource();
		}

		return $result;
	}
	
	public function request() {
		
		if ($this->mode_protocol == static::PROTOCOL_LOCAL || $this->mode_protocol == static::PROTOCOL_DATA_URL) {
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
		
		if (isset($this->arr_settings['redirect'])) {
			 $this->num_redirect = (int)$this->arr_settings['redirect'];
		}
	}
	
	protected function storeExternalSource() {
		
		$str_path_temp = getPathTemporary();
		$this->file = fopen($str_path_temp, 'w');
		
		$store = $this->getExternalSource();
		
		fclose($this->file);
		
		if (!$store) { // Something went wrong
			FileStore::deleteFile($str_path_temp);
			return false;
		}
		
		return $str_path_temp;
	}
		
	protected function getExternalSource() {
		
		$do_continue = true;
		$has_redirect = false;
		$has_result = true;
		
		$str_url = ($this->url_redirect ?: $this->url);
		$str_url = str_replace(' ', '%20', $str_url); // Keep possible spaces in URL
		
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
				preg_match('/^(Location:|URI:)/i', $str_header, $arr_matches);
				
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
	
	protected function storeDataURL() {
				
		$str_path_temp = getPathTemporary();
		$this->file = fopen($str_path_temp, 'w');
		
		$store = $this->getDataURL();
		
		if (!$store) { // Something went wrong
			FileStore::deleteFile($str_path_temp);
			return false;
		}
		
		return $str_path_temp;
	}
	
	protected function getDataURL() {
		
		$arr_data_url = static::getProtocolDataURL($this->url);
				
		$data = substr($this->url, strlen($arr_data_url['protocol']) + 5 + 1); // Add back 'data:[protocol],'

		if ($arr_data_url['encoding'] == 'base64') {
			$data = base64_decode($data);
		} else {
			$data = rawurldecode($data);
		}
		
		$this->filename = 'dataurl.'.FileStore::getMIMETypeExtension($arr_data_url['mime']);
		
		if ($this->file) {
			
			fwrite($this->file, $data);
			return true;
		} else {
			
			return $data;
		}
	}
	
	public static function getProtocol($str_url) {

		$mode_protocol = (static::getProtocolExternal($str_url) ? static::PROTOCOL_EXTERNAL : false);
		
		if (!$mode_protocol) {
			
			$mode_protocol = (static::getProtocolDataURL($str_url, true) ? static::PROTOCOL_DATA_URL : false);
		}
		
		return ($mode_protocol ?: static::PROTOCOL_LOCAL);
	}

	public static function getProtocolExternal($str_url) {
		
		$arr_protocol_url = explode('://', $str_url);
		
		if (empty($arr_protocol_url[1])) {
			return false;
		}
		
		$str_protocol = $arr_protocol_url[0];
		
		if (!in_array($str_protocol, static::$arr_external_protocols)) {
			return false;
		}
		
		return $str_protocol;
	}
	
	public static function getProtocolDataURL($str_url, $do_check = false) {
		
		if (!strStartsWith($str_url, 'data:')) {
			return false;
		}
		
		if ($do_check) {
			return true;
		}
		
		$num_pos_data = strpos($str_url, ',');
		
		$str_type = substr($str_url, 5, $num_pos_data - 5);
		$arr_type = str2Array($str_type, ';');
		$str_mime = ($arr_type[0] ?? '');
		$str_encoding = end($arr_type);
		
		return ['protocol' => $str_type, 'mime' => $str_mime, 'encoding' => $str_encoding];
	}
	
	public function getSource() {
				
		return ($this->filename ?: $this->url);
	}
	
	public function getPath() {
				
		return ($this->mode_protocol == static::PROTOCOL_LOCAL ? $this->url : $this->path);
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
	
	public function getErrorResponse($num_snippet = true) {
		
		if (!$this->num_error) {
			return '';
		}
		
		if (!$num_snippet) {
			return $this->str_error;
		}
		
		$str_error = trim($this->str_error);
		
		if ($str_error) {
			
			$num_snippet = ($num_snippet === true ? 250 : $num_snippet);
			
			$str_error = strEscapeHTML((mb_strlen($str_error) > $num_snippet ? mb_substr($str_error, 0, $num_snippet).' [...]' : $str_error));
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
