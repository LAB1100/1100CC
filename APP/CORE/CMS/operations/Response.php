<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class Response {
		
	const OUTPUT_HTML = 1;
	const OUTPUT_JSON = 2;
	const OUTPUT_TEXT = 4;
	
	const RENDER_HTML = 8;
	const RENDER_LINKED_DATA = 16;
	
	const PARSE_PRETTY = 32;
	
	public static $show_updates = false;
	
	private static $object = false;
	
	private static $buffer_sent = false;
	private static $stream_sent = 0;
	private static $str_stream_close = '';
	private static $response_sent = 0;
	private static $disable_encoding = false;
	private static $format = self::OUTPUT_HTML | self::RENDER_HTML;
	
	private static $arr_parse_callbacks = [];
	private static $arr_parse_delays = [];
	private static $arr_parse_post_identifiers = [];
	private static $arr_parse_post_options = [];
	
	public static function getObject() {
		
		if (self::$object === false) {
			self::$object = (object)[];
		}
		
		return self::$object;
	}
	
	public static function getStream($open = ' ', $close = ' ') {
		
		return '['.$open.'[STREAM]'.$close.']';
	}
	
	public static function openStream($index, $dynamic) {
			
		if (self::$format & self::OUTPUT_HTML) {
			
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

			if (SiteStartVars::getRequestState() == 'iframe') {
				
				if (!self::$buffer_sent && !self::$stream_sent) {
					header('Content-Type: text/html;charset=utf-8');
				}
				
				$str = '<textarea>'.$str;
			} else {
				
				if (!self::$buffer_sent && !self::$stream_sent) {
					header('Content-Type: '.(self::$format & self::RENDER_LINKED_DATA ? 'application/ld+json' : 'application/json').';charset=utf-8');
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
	
		if (self::$format & self::OUTPUT_HTML) {
							
			if (!self::$stream_sent) {
				
				header('Content-Type: text/html;charset=utf-8');
			}
			
			if (is_callable($index)) {
				
				$index = $index();
			}
			
			if (Mediator::$shutdown == 'hard') {
				
				$str = self::encode($index);
				
				if (self::$stream_sent) {
					
					$str = self::$str_stream_close.$str;
				}
			} else {
				
				$str = self::parse($index, (self::$response_sent > 1 || Mediator::$shutdown ? false : true)); // Also prevent errors originating from parsing functions
				$str = self::output($str);
				
				if (self::$stream_sent) {
					
					if (Mediator::$shutdown == 'soft') {
						
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
			
			if (Mediator::$shutdown == 'hard') {

				$str = self::encode($dynamic);
				
				if (self::$stream_sent) {
					
					$str_open = rtrim(substr(self::$str_stream_close, 0, -1));
					$str_close = ltrim(substr($str, 1));
					$str = $str_open.','.$str_close;
				}
			} else {
				
				$str = self::parse($dynamic, (self::$response_sent > 1 || Mediator::$shutdown ? false : true)); // Also prevent errors originating from parsing functions
				$str = self::output($str);
				
				if (self::$stream_sent) {
					
					if (Mediator::$shutdown == 'soft') {
						
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
			
			if (SiteStartVars::getRequestState() == 'iframe') {
				
				if (!self::$buffer_sent && !self::$stream_sent) {
					header('Content-Type: text/html;charset=utf-8');
				}
				
				echo (!self::$stream_sent ? '<textarea>' : '').$str.'</textarea>';
			} else {
				
				if (!self::$buffer_sent && !self::$stream_sent) {
					header('Content-Type: '.(self::$format & self::RENDER_LINKED_DATA ? 'application/ld+json' : 'application/json').';charset=utf-8');
				}
				
				echo $str;
			}
		}
		
		if (!Mediator::$shutdown) { // Check if already in shutdown cyclus, otherwise start it
			exit;
		}
	}
	
	public static function update($response = false) {

		if (self::$format & self::OUTPUT_HTML) {
			if (!self::$show_updates || !$response) {
				return;
			}
		} else {
			if (!self::$show_updates && $response) { // Do allow for buffer and client status checks
				return;
			}
		}
					
		if (!self::$buffer_sent) {

			$str_buffer = str_pad('', 4096, ' ');
			
			$request_state = SiteStartVars::getRequestState();

			if ($request_state == 'command' || $request_state == 'iframe') {

				$str_buffer = '[PROCESS]'.$str_buffer.'[-PROCESS]';

				header('Content-Type: text/plain;charset=utf-8');
			} else {
				
				header('Content-Type: '.(self::$format & self::RENDER_LINKED_DATA ? 'application/ld+json' : 'application/json').';charset=utf-8');
			}
			
			self::$buffer_sent = true;
		}
		
		if ($response) {

			$str = self::parse($response, false);
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
	
	public static function encode($value) {
		
		if (!$value || self::$disable_encoding || ((self::$format & self::OUTPUT_HTML || self::$format & self::OUTPUT_TEXT) && is_string($value))) {
			return $value;
		}
		
		$is_object = (is_object($value) || is_array($value));
		
		if (self::$format & self::PARSE_PRETTY) {
			$str = json_encode($value, JSON_PRETTY_PRINT);
		} else {
			$str = json_encode($value);
		}
		
		if (!$is_object) { // In case $value is not an object/array, remove the ""
			$str = substr($str, 1, -1);
		}
		
		return $str;
	}
			
	public static function setFormat($format) {
		
		self::$format = $format;
	}
	
	public static function getFormat() {
		
		return self::$format;
	}
	
	public static function decode($str, $is_object = false) {
		
		if (!$str || self::$disable_encoding || self::$format & self::OUTPUT_HTML || self::$format & self::OUTPUT_TEXT) {
			return $str;
		}
		
		if (!$is_object) { // In case $str is not an object/array, add ""
			$str = '"'.$str.'"';
		}
		
		$value = json_decode($str);
		
		return $value;
	}
	
	public static function addParse($function) {
		
		$id = array_push(self::$arr_parse_callbacks, $function);
		
		return $id;
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
		
		$identifier = 'l:'.$arr_options['limit'].'_a:'.$arr_options['affix'].'_'.$arr_options['strip'].'_'.$arr_options['case'];
		
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
	
	private static function clearParse() {
		
		self::$arr_parse_delays = [];
		
		self::$arr_parse_post_options = [];
		self::$arr_parse_post_identifiers = [];
	}
	
	public static function parse($value, $callbacks = true) {
		
		$value = self::encode($value);
		
		$value = Labels::printLabels($value, true);
		
		if ($callbacks) {

			foreach (self::$arr_parse_callbacks as $key => $function) {
				
				$value = $function($value);
			}
			
			$value = self::parseDelay($value);
		}
		
		$value = self::parsePost($value);
		
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
	
	public static function parseDelay($value) {
			
		if (strpos($value, '[PARSE]') === false) {
			return $value;
		}
					
		$value = preg_replace_callback('/\[PARSE\]\[(\d+)\]((?:(?!\[-?PARSE\]).)*)\[-PARSE\]/', function($arr_match) {

			$arr_settings = self::$arr_parse_delays[$arr_match[1]];
			
			if (!$arr_settings) {
				return '';
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
		
		$value = preg_replace_callback('/\[PARSE\+\]\[(\d+)\]((?:(?!\[-?PARSE\+\]).)*)\[-PARSE\+\]/', function($arr_match) {

			$arr_options = self::$arr_parse_post_options[($arr_match[1] - 1)];
			$str = self::decode($arr_match[2]);
			
			if ($arr_options['strip']) {
				
				$str = strip_tags($str);
				
				if (self::$format & self::RENDER_HTML) {
					$str = htmlspecialchars($str);
				}
			}
			if ($arr_options['case']) {
				$str = ($arr_options['case'] == 'upper' ? strtoupper($str) : strtolower($str));
			}	
			if ($arr_options['limit'] !== null) {
				
				$limited = false;
				
				if (!$arr_options['limit']) {
					
					if ($str != '') {
						
						$limited = true;
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
								
								$limited = true;
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
					$limited = true;
				}
				
				if ($arr_options['affix'] && $limited) {
					
					$str = $str.$arr_options['affix'];
				}
			}
			
			$str = self::encode($str);
			
			return $str;
		}, $value, -1, $count);
		
		if ($count) { // Some parsing has been done, check for more
			$value = self::parsePost($value);
		}
		
		return $value;
	}
	
	public static function sendHeader($file, $download = true, $arr_headers = []) {
		
		if ($file) {
			
			try {
				$is_file = is_file($file);
			} catch (Exception $e) {
				$is_file = false;
			}
			$size = ($is_file ? filesize($file) : mb_strlen($file, '8bit'));
			
			$filename = (!is_bool($download) ? $download : basename($file));
			
			if ($size) {
				
				$finfo = new finfo(FILEINFO_MIME_TYPE);
				
				if ($is_file) {
					
					$type = $finfo->file($file);
				} else {
					
					$type = FileStore::getExtensionMIMEType(FileStore::getFilenameExtension($filename));
					
					if (!$type) {
						$finfo->buffer($file);
					}
				}

				header('Content-Type: '.$type);
				header('Content-Length: '.$size);
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
		
		if ($download && (!$file || $file && $size)) {

			header('Content-Disposition: attachment; filename='.$filename);
		}
		
		foreach	($arr_headers as $header) {
			header($header);
		}
	}
	
	public static function location($url) {

		self::stop(function() use ($url) {
				
				header('Location: '.$url);
				exit;
			}, function() use ($url) {
				
				if (SiteStartVars::getRequestState() == 'api') {
					header('Location: '.$url);
					exit;
				}
								
				$JSON = (object)[];
				$JSON->location = ['reload' => true, 'url' => $url];
				$JSON = Log::addToObj($JSON);
				
				return $JSON;
			}
		);
	}
}
