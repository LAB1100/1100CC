<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class GenerateSitemap {
	
	protected $str_directory = false;
	protected $mode_format = Response::OUTPUT_TEXT;
	protected $str_host_name = false;
	protected $uri_translator = false;
	protected $do_encode_url = true;
	
	protected $resource = false;
	protected $str_identifier = false;
	protected $str_filename = false;
	protected $num_entries = 0;
	
	protected $arr_files = [];
	
	protected static $num_files_max = 20000;

	public function __construct($str_directory) {
		
		$this->str_directory = $str_directory;
		
		if (Response::getFormat() & Response::OUTPUT_XML) {
			$this->mode_format = Response::OUTPUT_XML;
		}
	}
	
	public function setHostName($str_host_name) {
		
		$this->str_host_name = $str_host_name;
	}
	
	public function addEntries($iterator, $str_identifier) {
		
		$this->str_identifier = $str_identifier;
		
		$this->handleFile();
		
		$this->num_entries = 0;

		foreach ($iterator as $arr_entry) {
			
			if (!is_array($arr_entry)) {
				
				$str_location = $arr_entry;
			} else {
				
				$str_location = $arr_entry['location'];
			}
			
			$str_url = $this->getURL($str_location);
			
			fwrite($this->resource, $str_url.EOL_1100CC);
			$this->num_entries++;
			
			if ($this->num_entries == static::$num_files_max) {
				
				$this->handleFile();
				$this->num_entries = 0;
			}
		}
		
		$this->handleFile(true);
		
		return $this->arr_files[$this->str_identifier];
	}
		
	protected function handleFile($do_close = false) {
		
		if ($this->resource) {
			
			if ($this->num_entries) {

				rewind($this->resource);
				FileStore::storeFile($this->str_directory.$this->str_filename, read($this->resource));

				$this->arr_files[$this->str_identifier][] = $this->str_filename;
			}
			
			fclose($this->resource);
			$this->resource = false;
		}
		
		if (!$do_close) {
			
			$num_filename = 0;
			
			if (isset($this->arr_files[$this->str_identifier])) {
				$num_filename = (count($this->arr_files[$this->str_identifier]) + 1);
			}
			
			$this->resource = getStreamMemory();
			$this->str_filename = $this->str_identifier.($num_filename > 0 ? '-'.$num_filename : '').'.txt';
		}
	}
	
	protected function getURL($str_location) {
		
		if ($this->uri_translator) {
			
			$str_location_use = substr($str_location, 1); // Identifier does not contain any leading path's '/'
			$arr_uri = uris::getURI($this->uri_translator['id'], uris::MODE_OUT, $str_location_use);
				
			if ($arr_uri && uris::isURLInternal($arr_uri['url'], $this->uri_translator['host_name'])) {
				
				$str_location = $arr_uri['url'];
			}
		}
		
		if ($this->do_encode_url) {
			$str_location = implode('/', array_map('rawurlencode', explode('/', $str_location)));
		}
			
		$str_url = SERVER_SCHEME.($this->str_host_name ?: SERVER_NAME_1100CC).$str_location;
		
		return $str_url;
	}
	
	public function setURITranslator($arr_uri_translator) {
		
		$this->uri_translator = $arr_uri_translator;
	}
	
	public function get() {
		
		return $this->arr_files;
	}
}
