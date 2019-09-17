<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FileGet {
	
	private $async = false;
	
	private $url = '';
	private $url_redirect = '';
	
	private $file = false;
	private $path = false;
	private $external_protocol = false;
	
	private $redirect_max = 2;
	private $error = false;
	
	private $arr_options = [
		'timeout' => 10,
		'timeout_connect' => 5
	];
	
	private static $external_protocols = ['http', 'https', 'ftp'];

	public function __construct($url, $arr_options = [], $async = false) {
	
		$this->url = $url;
		$this->external_protocol = self::getExternalProtocol($this->url);
		
		$this->setOptions($arr_options);
		
		$this->async = $async;
	}
			
	public function load() {
		
		if (!$this->external_protocol) {
			return false;
		}
				
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
		
		$this->file = false;
				
		$result = $this->getExternalSource();

		return $result;
	}
	
	public function setOptions($arr_options) {
		
		if ($arr_options) {
			$this->arr_options = array_merge($this->arr_options, $arr_options);
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
		
		$continue = true;
		$redirect = false;
		$store = true;
		
		$curl = curl_init(($this->url_redirect ?: $this->url));
		if ($this->arr_options['timeout_connect']) {
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->arr_options['timeout_connect']);
		}
		if ($this->arr_options['timeout']) {
			curl_setopt($curl, CURLOPT_TIMEOUT, $this->arr_options['timeout']);
		}
		curl_setopt($curl, CURLOPT_USERAGENT, Labels::getServerVariable('user_agent'));
		
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$continue, &$redirect, &$store) { // Check each header for errors
			
			$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			
			if ($code != '200') { // Should be found (200) to be stored
				
				$continue = false;
				$store = false;
			}
			
			if ($code == '301' || $code == '302') { // Source is redirected/moved
				
				$continue = true;
				
				$matches = [];
				preg_match('/^(Location:|URI:)/', $header, $matches);
				
				if ($matches) {
					
					$url = trim(str_replace($matches[1], '', $header));
					
					if (parse_url($url)) {
						
						$continue = false;
						$redirect = true;
						
						$this->url_redirect = $url;
					}
				}
			}
			
			return ($continue ? strlen($header) : -1);
		});
		
		if ($this->file) {
			curl_setopt($curl, CURLOPT_FILE, $this->file); // Return response to $file
		} else {
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Return response to $result
		}
		
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
					
					curl_multi_select($mh); // Wait for activity on the curl connection
						
					do {
						$mrc = curl_multi_exec($mh, $running);
					} while ($mrc == CURLM_CALL_MULTI_PERFORM);
				} else {
					
					$running = false;
				}
				
				return !$running;
			}, function() use (&$store) {
				
				$store = false;
			});
						
			$result = (!$this->file && $store ? curl_multi_getcontent($curl) : false);
			
			$arr_info = curl_multi_info_read($mh);
			$this->error = $arr_info['result'];
							
			curl_multi_remove_handle($mh, $curl);

			curl_close($curl);
			
			curl_multi_close($mh);
		} else {
		
			$result = curl_exec($curl);
			
			$this->error = curl_errno($curl);
			
			curl_close($curl);
		}
		
		if ($redirect && $this->redirect_max > 0) {
			
			$this->redirect_max--;

			$store = $this->getExternalSource();
		}
		
		if ($this->file) {
			return $store;
		} else {
			return ($store ? $result : false);
		}
	}

	public static function getExternalProtocol($url) {
		
		$arr_protocol_url = explode('://', $url);
		$protocol = ($arr_protocol_url[1] ? $arr_protocol_url[0] : false);
		$protocol = ($protocol && in_array($protocol, self::$external_protocols) ? $protocol : false);
		
		return $protocol;
	}
	
	public function getSource() {
				
		return $this->url;
	}
	
	public function getPath() {
				
		return $this->path;
	}
	
	public function getError() {
		
		if ($this->error == 28) {
			return 'timeout';
		} else {
			return $this->error;
		}
	}
}
