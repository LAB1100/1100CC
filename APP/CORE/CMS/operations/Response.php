<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class Response {
		
	const OUTPUT_XML = 1;
	const OUTPUT_JSON = 2;
	const OUTPUT_JSONP = 4 + 2;
	const OUTPUT_TEXT = 8;
	const OUTPUT_CSV = 16;
	
	const RENDER_HTML = 32;
	const RENDER_XML = 64;
	const RENDER_LINKED_DATA = 128;
	const RENDER_TEXT = 256;
	
	const PARSE_PRETTY = 512;
	
	private static $do_output_updates = null; // true, null, false
	
	private static $object = false;
	
	private static $buffer_sent = false;
	private static $stream_sent = 0;
	private static $str_stream_close = '';
	private static $response_sent = 0;
	private static $disable_encoding = false;
	private static $format = self::OUTPUT_XML | self::RENDER_HTML;
	private static $format_settings = null;
	private static $arr_headers = [];
	
	private static $arr_parse_callbacks = [];
	private static $arr_hold_parse_callbacks = [];
	private static $arr_parse_delays = [];
	private static $arr_parse_post_identifiers = [];
	private static $arr_parse_post_options = [];
	private static $do_clear_parse = false;
	private static $format_hold = null;
	private static $format_settings_hold = null;
	
	const PARSE_METHOD = 1;
	const PARSE_DELAY = 2;
	const PARSE_POST = 4;
	
	public static function setOutputUpdates(?bool $do_output = true) {
		
		self::$do_output_updates = $do_output;
	}
	
	public static function isSent() {
		
		return (static::$buffer_sent || self::$stream_sent ? true : false);
	}
	
	public static function getObject() {
		
		if (self::$object === false) {
			self::$object = (object)[];
		}
		
		return self::$object;
	}
	
	public static function getStream($str_open = ' ', $str_close = ' ') {
		
		return '['.$str_open.'[STREAM]'.$str_close.']';
	}
	
	public static function openStream($index, $dynamic) {
		
		if (!self::$buffer_sent && !self::$stream_sent) {
			static::sendHeaders();
		}
			
		if (bitHasMode(self::$format, self::OUTPUT_XML)) {
			
			if (is_callable($index)) {
				
				$index = $index();
			}
			
			$str = self::parse($index);
			$str = self::output($str);
			
			// Open ...[?[STREAM]?]... => ...
			$pos = strrpos($str, '[STREAM]');
			self::$str_stream_close = substr($str, $pos + 10);
			$str = substr($str, 0, $pos - 2);
			
			// Close a possible earlier ...[STREAM]?]
			$pos = strrpos($str, '[STREAM]');
			if ($pos !== false) {
				$str = substr($str, $pos + 10);
			}
			
			if (!self::$buffer_sent && !self::$stream_sent) {
				
				header('Content-Type: text/html;charset=utf-8');
			}
		} else {
							
			if (is_callable($dynamic)) {
				
				$dynamic = $dynamic();
			}
			
			$str = self::parse($dynamic);
			$str = self::output($str);
			
			// Open ..."[?[STREAM]?]"... => ...?
			$pos = strrpos($str, '[STREAM]');
			$str_open = substr($str, $pos - 1, 1);
			self::$str_stream_close = substr($str, $pos + 8, 1);
			self::$str_stream_close .= substr($str, $pos + 11);
			$str = substr($str, 0, $pos - 3);
			$str = $str.$str_open;
			
			// Close a possible earlier ...[STREAM]?]"
			$pos = strrpos($str, '[STREAM]');
			if ($pos !== false) {
				$str_close = substr($str, $pos + 8, 1);
				$str = substr($str, $pos + 11);
				$str = $str_close.$str;
			}

			if (SiteStartEnvironment::getRequestState() == SiteStartEnvironment::REQUEST_DOWNLOAD) {
				
				if (!self::$buffer_sent && !self::$stream_sent) {
					header('Content-Type: text/html;charset=utf-8');
				}
				
				$str = '<textarea>'.$str;
			} else {
				
				if (!self::$buffer_sent && !self::$stream_sent) {
					header('Content-Type: '.self::getFormatContentType().';charset=utf-8');
				}
			}
		}
		
		if (!self::$buffer_sent) {

			echo str_pad('', 4096, ' ');
			
			self::$buffer_sent = true;
		}

		self::$stream_sent = 1;
		self::clearParse();
		
		echo $str;
		
		ob_flush();
		flush();
	}
	
	public static function stream($value) {
		
		if (!$value) {
			return;
		}
		
		$str = self::parse($value);
		$str = self::output($str);
		
		$str = substr($str, 1, -1); // Remove the encapsulating {} or []
		
		if (self::$stream_sent > 1) {
			$str = ', '.$str;
		}
		
		self::$stream_sent++;
		self::clearParse();
		
		echo $str;
		
		ob_flush();
		flush();
	}
			
	public static function stop($index, $dynamic) {
					
		self::$response_sent++;
		
		if (!self::$buffer_sent && !self::$stream_sent) {
			static::sendHeaders();
		}
		
		if (bitHasMode(self::$format, self::OUTPUT_XML)) {
			
			if (!self::$buffer_sent && !self::$stream_sent) {
				header('Content-Type: text/html;charset=utf-8');
			}
			
			if (is_callable($index)) {
				$index = $index();
			}
			
			if ((Mediator::getShutdown() & Mediator::SHUTDOWN_HARD)) {
				
				$str = self::encode($index);
				
				if (self::$stream_sent) {
					$str = self::$str_stream_close.$str;
				}
			} else {
				
				$str = self::parse($index, (self::$response_sent > 1 || (Mediator::getShutdown() & (Mediator::SHUTDOWN_INIT_UNDETERMINED | Mediator::SHUTDOWN_INIT_SYSTEM)) ? self::PARSE_POST : null)); // Also prevent errors originating from parsing functions
				$str = self::output($str);
				
				if (self::$stream_sent) {
					
					if ((Mediator::getShutdown() & (Mediator::SHUTDOWN_SILENT | MEDIATOR::SHUTDOWN_SOFT))) {
						
						$str = self::$str_stream_close.$str;
					} else {
						
						// Close the last ...[STREAM]?]
						$pos = strrpos($str, '[STREAM]');
						$str = substr($str, $pos + 10);
					}
				}
			}

			echo $str;
		} else {
			
			if (is_callable($dynamic)) {
				$dynamic = $dynamic();
			}
			
			if ((Mediator::getShutdown() & Mediator::SHUTDOWN_HARD)) {

				$str = self::encode($dynamic);
				
				if (self::$stream_sent) {
					
					$str_open = rtrim(substr(self::$str_stream_close, 0, -1));
					$str_close = ltrim(substr($str, 1));
					$str = $str_open.','.$str_close;
				}
			} else {
				
				$str = self::parse($dynamic, (self::$response_sent > 1 || (Mediator::getShutdown() & (Mediator::SHUTDOWN_INIT_UNDETERMINED | Mediator::SHUTDOWN_INIT_SYSTEM)) ? self::PARSE_POST : null)); // Also prevent errors originating from parsing functions
				$str = self::output($str);
				
				if (self::$stream_sent) {
					
					if ((Mediator::getShutdown() & (Mediator::SHUTDOWN_SILENT | MEDIATOR::SHUTDOWN_SOFT))) {
						
						$str_open = rtrim(substr(self::$str_stream_close, 0, -1));
						$str_close = ltrim(substr($str, 1));
						$str = $str_open.','.$str_close;
					} else {
						
						// Close the last ...[STREAM]?]"
						$pos = strrpos($str, '[STREAM]');
						$str_close = substr($str, $pos + 8, 1);
						$str = substr($str, $pos + 11);
						$str = $str_close.$str;
					}
				}
			}
			
			if (SiteStartEnvironment::getRequestState() == SiteStartEnvironment::REQUEST_DOWNLOAD) {
				
				if (!self::$buffer_sent && !self::$stream_sent) {
					header('Content-Type: text/html;charset=utf-8');
				}
				
				echo (!self::$stream_sent ? '<textarea>' : '').$str.'</textarea>';
			} else {
				
				if (!self::$buffer_sent && !self::$stream_sent) {
					header('Content-Type: '.self::getFormatContentType().';charset=utf-8');
				}
				
				if (bitHasMode(self::$format, self::OUTPUT_JSONP) && !empty($_REQUEST['callback'])) {
					echo $_REQUEST['callback'].'('.$str.')';
				} else {
					echo $str;
				}
			}
		}
		
		if (!Mediator::getShutdown()) { // Check if already in shutdown cyclus, otherwise start it
			exit;
		}
	}
	
	public static function update($response = false) {

		if (bitHasMode(self::$format, self::OUTPUT_XML)) {
			if (!self::$do_output_updates || !$response) {
				return;
			}
		} else {
			if (self::$do_output_updates === null && $response) { // Does allow for buffer and client status checks
				return;
			} else if (self::$do_output_updates === false) {
				return;
			}
		}
		
		$str_buffer = false;
		
		if (!self::$buffer_sent) {

			$str_buffer = str_pad('', 4096, ' ');
			
			$request_state = SiteStartEnvironment::getRequestState();

			if ($request_state == SiteStartEnvironment::REQUEST_COMMAND || $request_state == SiteStartEnvironment::REQUEST_DOWNLOAD) {

				$str_buffer = '[PROCESS]'.$str_buffer.'[-PROCESS]';

				header('Content-Type: text/plain;charset=utf-8');
			} else {
				
				header('Content-Type: '.self::getFormatContentType().';charset=utf-8');
			}
			
			self::$buffer_sent = true;
		}
		
		if ($response) {

			$str = self::parse($response, self::PARSE_POST);
			$str = self::output($str);
			
			$str = '[PROCESS]'.$str.'[-PROCESS]';
		} else {
			
			$str = ' ';
		}
		
		if ($str_buffer) {
			
			$str = $str_buffer.$str;
		}
		
		echo $str;
		
		ob_flush();
		flush();
	}
	
	public static function location($url) {
		
		if (is_array($url)) {
			
			$object = static::getObject();
			
			if (!isset($object->location)) {
				$object->location = [];
			}
			
			$object->location += $url;

			return;
		}

		self::stop(function() use ($url) {
				
				header('Location: '.$url);
				exit;
			}, function() use ($url) {
				
				if (SiteStartEnvironment::getRequestState() == SiteStartEnvironment::REQUEST_API) {
					
					header('Location: '.$url);
					exit;
				}
								
				$JSON = (object)[];
				$JSON->location = ['reload' => true, 'real' => $url];
				$JSON = Log::addToObj($JSON);
				
				return $JSON;
			}
		);
	}
	
	public static function setFormat($format) {
		
		self::$format = $format;
		self::$format_settings = null;
	}
	public static function setFormatSettings($settings) {
		
		self::$format_settings = $settings;
	}
	
	public static function getFormat() {
		
		return self::$format;
	}
	public static function getFormatSettings() {
		
		return self::$format_settings;
	}
	public static function getFormatContentType() {
		
		$str_header = '';
		
		if (self::$format & self::OUTPUT_JSON) {
			if (self::$format & self::RENDER_LINKED_DATA) {
				$str_header = 'application/ld+json';
			} else {
				$str_header = 'application/json';
			}
		} else if (self::$format & self::OUTPUT_XML) {
			if (self::$format & self::RENDER_HTML) {
				$str_header = 'text/html';
			} else {
				$str_header = 'text/xml';
			}
		} else if (self::$format & self::OUTPUT_TEXT) {
			$str_header = 'text/plain';
		} else if (self::$format & self::OUTPUT_CSV) {
			$str_header = 'text/csv';
		}
		
		return $str_header;
	}
	
	public static function holdFormat($do_hold = false) { // Quick store and release of format
		
		if ($do_hold) {
			
			if (self::$format_hold !== null && self::$format_hold != self::$format) {
				error('Already holding response format.');
			}

			self::$format_hold = self::$format;
			self::$format_settings_hold = self::$format_settings;
		} else if (self::$format_hold !== null) {
			
			self::$format = self::$format_hold;
			self::$format_settings = self::$format_settings_hold;
			
			self::$format_hold = self::$format_settings = null;
		}		
	}

	public static function encode($value) {
		
		if (!$value || self::$disable_encoding || (bitHasMode(self::$format, self::OUTPUT_XML, self::OUTPUT_TEXT) && is_string($value))) {
			return $value;
		}
		
		if (bitHasMode(self::$format, self::OUTPUT_CSV)) {
			return self::encodeCSV($value);
		}
		
		return self::encodeJSON($value);
	}
	
	public static function encodeJSON($value) {
		
		$is_string = is_string($value);
		
		if (bitHasMode(self::$format, self::PARSE_PRETTY)) {
			$value = value2JSON($value, JSON_PRETTY_PRINT);
		} else {
			$value = value2JSON($value);
		}
		
		if ($is_string) { // In case $value is not an e.g. object/array, but a string, remove the added ""
			$value = substr($value, 1, -1);
		}
		
		return $value;
	}
	
	public static function encodeCSV($value) {
		
		$is_csv = (strpos($value, "\n", -1) !== false);
		
		if ($is_csv) { // Do not work on full CSV
			return $value;
		}
		
		$str_escape = (self::$format_settings ?? '"');
		$value = str_replace($str_escape, $str_escape.$str_escape, $value);
		
		return $value;
	}
	
	public static function decode($str, $is_object = false) {
		
		if (!$str || self::$disable_encoding || bitHasMode(self::$format, self::OUTPUT_XML, self::OUTPUT_TEXT)) {
			return $str;
		}
		
		if (bitHasMode(self::$format, self::OUTPUT_CSV)) {	
			return self::decodeCSV($str, $is_object);
		}
		
		return self::decodeJSON($str, $is_object);
	}
	
	public static function decodeJSON($str, $is_object = false) {
		
		if (!$is_object) { // In case $str is not an encoded object/array, but a string, add ""
			$str = '"'.$str.'"';
		}
		
		return json_decode($str);
	}
	
	public static function decodeCSV($str, $is_object = false) {
		
		$is_csv = (strpos($str, "\n", -1) !== false);
		
		if ($is_csv) { // Do not work on full CSV
			return $str;
		}
		
		$str_escape = (self::$format_settings ?? '"');
		$str = str_replace($str_escape.$str_escape, $str_escape, $str);
		
		return $str;
	}
	
	public static function addParse($function, $identifier = false) {
		
		if ($identifier !== false) {
			self::$arr_parse_callbacks[$identifier] = $function;
		} else {
			$identifier = array_push(self::$arr_parse_callbacks, $function);
		}
		
		return $identifier;
	}
	
	public static function addParseDelay($str, $function, $post = false) {
		
		if ($post) {
			$arr_settings = ['function' => $function, 'str' => $str];
			$str = false;
		} else {
			$arr_settings = ['function' => $function, 'str' => null];
		}
		
		$id = array_push(self::$arr_parse_delays, $arr_settings);

		return '[PARSE]['.($id-1).']'.($str ?: '').'[-PARSE]';
	}
	
	public static function addParsePost($value, $arr_options) {
		
		$identifier = '';
		
		foreach ($arr_options as $key_option => $value_option) {
			
			switch ($key_option) {
				case 'limit':
					$identifier .= ':l:'.$value_option;
					break;
				case 'affix':
					$identifier .= ':a:'.$value_option;
					break;
				case 'strip':
					$identifier .= ':s:'.$value_option;
					break;
				case 'case':
					$identifier .= ':c:'.$value_option;
					break;
				case 'regex':
					$identifier .= ':x:'.$value_option['pattern'].$value_option['flags'].$value_option['template'];
					break;
			}
		}
		
		// $identifier = value2Hash($arr_options);
		
		$id = self::$arr_parse_post_identifiers[$identifier];
		
		if (!$id) {
			
			$id = array_push(self::$arr_parse_post_options, $arr_options);
			
			self::$arr_parse_post_identifiers[$identifier] = $id;
		}
					
		if ($value) {
			return '[PARSE+]['.$id.']'.$value.'[-PARSE+]';
		} else {
			return ['open' => '[PARSE+]['.$id.']', 'close' => '[-PARSE+]'];
		}
	}
	
	public static function holdParse($identifier, $do_hold) {
		
		self::$arr_hold_parse_callbacks[$identifier] = ($do_hold ? true : null);
	}
		
	private static function clearParse() {
		
		self::$arr_parse_delays = [];
		
		self::$arr_parse_post_options = [];
		self::$arr_parse_post_identifiers = [];
	}
	
	public static function setClearParseAuto($do_clear = false) { // Auto-clear Parse Delays when used
		
		self::$do_clear_parse = $do_clear;
	}
	
	public static function parse($value, $mode = null) {
		
		$value = self::encode($value);
		
		$value = Labels::printLabels($value, true);
		
		if ($mode === null || bitHasMode($mode, self::PARSE_METHOD)) {

			foreach (self::$arr_parse_callbacks as $key => $function) {				
				$value = self::parseMethod($key, $value);
			}
		}
		
		if ($mode === null || bitHasMode($mode, self::PARSE_DELAY)) {
			$value = self::parseDelay($value);
		}
		
		if ($mode === null || bitHasMode($mode, self::PARSE_POST)) {
			$value = self::parsePost($value);
		}
		
		if (bitHasMode(self::$format, self::RENDER_XML)) {
			$value = strEscapeXMLEntities($value);
		}
		
		return $value;
	}
	
	public static function output($value) {
		
		if (Settings::$server_file_host_name) {
			
			$arr_replace = [];
			$arr_storage_paths = Settings::$arr_storage_paths;
			
			if (getLabel('caching', 'D', true)) {
				$arr_storage_paths = array_merge($arr_storage_paths, Settings::$arr_storage_paths_cacheable);
			}
			
			foreach ($arr_storage_paths as $value_path) {
				
				$arr_replace[SERVER_NAME_BASE.'/'.$value_path] = Settings::$server_file_host_name.'/'.$value_path; // server_name_base/path/ => server_file_host_name/path/
				$arr_replace['"/'.$value_path] = '"//'.SERVER_NAME_SUB.Settings::$server_file_host_name.'/'.$value_path; // "/path/ => "http://server_name_sub.server_file_host_name/path/
				$arr_replace['&quot;/'.$value_path] = '&quot;//'.SERVER_NAME_SUB.Settings::$server_file_host_name.'/'.$value_path; // &quot;/path/ => "http://server_name_sub.server_file_host_name/path/
				$arr_replace["'/".$value_path] = "'//".SERVER_NAME_SUB.Settings::$server_file_host_name.'/'.$value_path; // '/path/ => 'http://server_name_sub.server_file_host_name/path/
			}
			
			$value = str_replace(array_keys($arr_replace), $arr_replace, $value);
		}
		
		return $value;
	}
	
	public static function parseMethod($identifier, $value) {
		
		if (isset(self::$arr_hold_parse_callbacks[$identifier])) {
			return $value;
		}
		
		$function = self::$arr_parse_callbacks[$identifier];
		
		return $function($value);
	}
	
	public static function parseDelay($value) {
			
		if (strpos($value, '[PARSE]') === false) {
			return $value;
		}
		
		/*
		\[PARSE\]\[(\d+)\]((?:(?!\[-?PARSE\]).)*)\[-PARSE\] => Slow but readable
		\[PARSE\]\[(\d+)\]((?>(?:(?>[^\[]+)|\[(?!-?PARSE\]))*))\[-PARSE\] => Fast but more complex
		\[PARSE\]\[(\d+)\]([^\[]*(?:(?:\[(?!-?PARSE\]))[^\[]*)*)\[-PARSE\] => Fast but complex
		*/
					
		$value = preg_replace_callback('/\[PARSE\]\[(\d+)\]((?>(?:(?>[^\[]+)|\[(?!-?PARSE\]))*))\[-PARSE\]/', function($arr_match) {
			
			$parse_id = $arr_match[1];
			$arr_settings = self::$arr_parse_delays[$parse_id];
			
			if (!$arr_settings) {
				return '';
			}
			
			if (self::$do_clear_parse) {
				self::$arr_parse_delays[$parse_id] = null;
			}

			$function = $arr_settings['function'];
			
			if (isset($arr_settings['str'])) {
				$str = self::parse($arr_settings['str']);
			} else {
				$str = $arr_match[2];
			}
			
			$str = self::decode($str);
			
			self::$disable_encoding = true; // Pause encoding when the output format requires it
			
			$str = $function($str);
			
			self::$disable_encoding = false; // Reset encoding
			
			$str = self::encode($str);
			
			return $str;
		}, $value, -1, $count);
		
		if ($count) { // Some parsing has been done, check for more
			$value = self::parseDelay($value);
		}
		
		return $value;
	}
	
	public static function parsePost($value) {
			
		if (strpos($value, '[PARSE+]') === false) {
			return $value;
		}
		
		/*
		\[PARSE\+\]\[(\d+)\]((?:(?!\[-?PARSE\+\]).)*)\[-PARSE\+\] => Slow but readable
		\[PARSE\+\]\[(\d+)\]((?>(?:(?>[^\[]+)|\[(?!-?PARSE\+\]))*))\[-PARSE\+\] => Fast but more complex
		\[PARSE\+\]\[(\d+)\]([^\[]*(?:(?:\[(?!-?PARSE\+\]))[^\[]*)*)\[-PARSE\+\] => Fast but complex
		*/
		
		$value = preg_replace_callback('/\[PARSE\+\]\[(\d+)\]((?>(?:(?>[^\[]+)|\[(?!-?PARSE\+\]))*))\[-PARSE\+\]/', function($arr_match) {
			
			$arr_options = self::$arr_parse_post_options[($arr_match[1] - 1)];
			$str = self::decode($arr_match[2]);
			
			if ($arr_options['strip']) {
				
				$str = strip_tags($str);
				
				if (bitHasMode(self::$format, self::RENDER_HTML)) {
					$str = strEscapeHTML($str);
				}
			}
			if ($arr_options['case']) {
				$str = ($arr_options['case'] == 'upper' ? strtoupper($str) : strtolower($str));
			}	
			if (isset($arr_options['limit'])) {
				
				$is_limited = false;
				
				if (!$arr_options['limit']) {
					
					if ($str != '') {
						
						$is_limited = true;
						$str = '';
					}
				} else if (strpos($str, '<') !== false) {
					
					$value = '';
					
					$length = mb_strlen($str);
					$in_tag = false;
					$stop = false;
					$count = 0;
					$count_tag = 0;
					
					for ($i = 0; $i < $length; $i++) {

						if ($stop && !$in_tag && !$count_tag) {
							break;
						}
						
						$char = mb_substr($str, $i, 1);
						
						if ($char === '<') {
							$value .= $char;
							$in_tag = true;
							$count_tag++;
						} else if ($char === '>') {
							$value .= $char;
							$in_tag = false;
						} else if ($in_tag) {
							if ($char == '/' && $char_prev = '<') {
								$count_tag = $count_tag-2;
							}
							$value .= $char;
						} else {
							
							if ($stop) {
								
								$is_limited = true;
							} else {
								
								$value .= $char;
								$count++;
										
								if ($count == $arr_options['limit']) {
									$stop = true;
								}
							}
						}
						
						$char_prev = $char;
					}
					
					$str = $value;
				} else if (mb_strlen($str) > $arr_options['limit']) {
				
					$str = mb_substr($str, 0, $arr_options['limit']);
					$is_limited = true;
				}
				
				if ($arr_options['affix'] && $is_limited) {
					
					$str = $str.$arr_options['affix'];
				}
			}
			if ($arr_options['regex']) {
				
				$arr_regex = $arr_options['regex'];
				$str = strRegularExpression($str, $arr_regex['pattern'], $arr_regex['flags'], $arr_regex['template']);
			}
			
			$str = self::encode($str);
			
			return $str;
		}, $value, -1, $count);
		
		if ($count) { // Some parsing has been done, check for more
			$value = self::parsePost($value);
		}
		
		return $value;
	}
	
	public static function extractParse(&$value) { // Remove Parse tags from value
		
		if (!is_array($value)) {
			
			if (!is_string($value) || !strStartsWith($value, '[PARSE')) {
				return false;
			}
			
			preg_match('/^(\[PARSE\+?\]\[\d+\])(.*)(\[-PARSE\+?\])$/', $value, $arr_match);
			
			$value = $arr_match[2];
			
			return ['open' => $arr_match[1], 'close' => $arr_match[3]];
		}
		
		$arr_parse = [];
		
		foreach ($value as $key => &$str) {
			
			if (!is_string($str) || !strStartsWith($str, '[PARSE')) {
				continue;
			}
			
			preg_match('/^(\[PARSE\+?\]\[\d+\])(.*)(\[-PARSE\+?\])$/', $str, $arr_match);
			
			$str = $arr_match[2];
			
			$arr_parse[$key] = ['open' => $arr_match[1], 'close' => $arr_match[3]];
		}
		
		return ($arr_parse === [] ? false : $arr_parse);
	}
	
	public static function restoreParse(&$value, $arr_parse) { // Restore Parse tags to value
		
		if (isset($arr_parse['open'])) {
			
			$value = $arr_parse['open'].$value.$arr_parse['close'];
			
			return;
		}
		
		foreach ($arr_parse as $key => $arr_parse_value) {
			
			$value[$key] = $arr_parse_value['open'].$value[$key].$arr_parse_value['close'];
		}
	}

	public static function addHeaders($arr_headers) {
		
		if (is_array($arr_headers)) {
			static::$arr_headers = array_merge(static::$arr_headers, $arr_headers);
		} else {
			static::$arr_headers[] = $arr_headers;
		}
	}
	
	public static function sendHeaders() {
		
		SiteEndEnvironment::checkRequestPolicy();
		
		foreach (static::$arr_headers as $header) {
			header($header);
		}
	}
	
	public static function sendFileHeaders($file, $download = true, $arr_headers = []) {
		
		static::sendHeaders();
		
		if ($file) {
			
			$is_file = false;
			$is_resource = false;
			$num_size = 0;
			
			if (is_string($file) && is_file($file)) {
				$is_file = true;
				$num_size = filesize($file);
			} else if (is_resource($file)) {
				$is_resource = true;
				$arr_stat = fstat($file);
				$num_size = $arr_stat['size'];
			} else {
				$num_size = strlen($file);
			}
			
			$filename = (!is_bool($download) ? $download : basename($file));
			
			if ($num_size) {
				
				$finfo = new finfo(FILEINFO_MIME_TYPE);
				
				if ($is_file) {
					
					$type = $finfo->file($file);
					
					if ($type == 'text/plain') {
						$type = (FileStore::getExtensionMIMEType(FileStore::getFilenameExtension($filename)) ?: $type);
					}
				} else {
					
					$type = FileStore::getExtensionMIMEType(FileStore::getFilenameExtension($filename));
					
					if (!$type) {
						if ($is_resource) {
							$type = $finfo->buffer(stream_get_contents($file, 100));
						} else {
							$type = $finfo->buffer(substr($file, 0, 100));
						}
					}
				}

				header('Content-Type: '.$type);
				header('Content-Length: '.$num_size);
			}
		} else {
			
			if (!is_bool($download)) {
				
				$filename = $download;
				$type = FileStore::getExtensionMIMEType(FileStore::getFilenameExtension($filename));
			} else {
				
				$filename = 'unnamed';
				$type = false;
			}

			if (!$type) {
				$type = 'application/octet-stream';
			}
			
			header('Content-Type: '.$type);
		}
		
		if ($download && (!$file || $file && $num_size)) {

			header('Content-Disposition: attachment; filename='.$filename);
		}
		
		foreach	($arr_headers as $header) {
			header($header);
		}
	}
}
