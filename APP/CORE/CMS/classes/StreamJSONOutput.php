<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class StreamJSONOutput {
	
	protected $resource = false;
	protected $is_temporary = false;
	
	protected $str_identifier = false;
	protected $use_parse_simple = false;
	
	protected $str_open = false;
	protected $str_close = false;
	
	protected $str_stream_close = '';
	protected $count_stream = 1;
	
	protected static $count_streams = 0;
	
	public function __construct($resource = false, $use_parse_simple = true) {
		
		if (!$resource) {
			
			$this->resource = fopen('php://temp/maxmemory:'.(100 * BYTE_MULTIPLIER * BYTE_MULTIPLIER), 'w'); // Keep resource in memory until it reaches 100MB, otherwise create a temporary file
			$this->is_temporary = true;
		} else {
			
			$this->resource = $resource;
		}

		static::$count_streams++;
		$this->str_identifier = '[STREAM'.static::$count_streams.']';
		
		$this->use_parse_simple = (bool)$use_parse_simple;
	}
	
	public function getStream($str_open = '{', $str_close = '}') {
		
		$this->str_open = $str_open;
		$this->str_close = $str_close;
		
		return $this->str_identifier;
	}
	
	public function open($obj) {
		
		if ($this->use_parse_simple) {
			$str = static::parse($obj);
		} else {
			$str = Response::parse($obj);
			$str = Response::output($str);
		}
		
		// Open ..."[STREAM]"... => ...?
		$pos = strpos($str, $this->str_identifier);
		$this->str_stream_close = substr($str, $pos + strlen($this->str_identifier) + 1);
		$str = substr($str, 0, $pos - 1);
		$str = $str.$this->str_open;
		
		fwrite($this->resource, $str);
	}
	
	public function stream($value) {
		
		if (!$value) {
			return;
		}
		
		if ($this->use_parse_simple) {
			$str = static::parse($value);
		} else {
			$str = Response::parse($value);
			$str = Response::output($str);
		}
		
		$str = substr($str, 1, -1); // Remove the encapsulating {} or []
		
		if ($this->count_stream > 1) {
			$str = ', '.$str;
		}
		
		fwrite($this->resource, $str);
		
		$this->count_stream++;
	}
	
	public function close() {
		
		fwrite($this->resource, $this->str_close.$this->str_stream_close);
				
		if ($this->is_temporary) {
			
			rewind($this->resource);
			
			return $this->resource;
		} else {
			return true;
		}
	}
	
	public static function parse($value) {
		
		if (!$value) {
			return $value;
		}
		
		$is_string = is_string($value);
		
		$str = value2JSON($value);
		
		if ($is_string) { // In case $value is not an e.g. object/array, but a string, remove the added ""
			$str = substr($str, 1, -1);
		}
		
		return $str;
	}
}
