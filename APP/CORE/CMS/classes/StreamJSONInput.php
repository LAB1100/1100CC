<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class StreamJSONInput {
	
	// With some help from https://github.com/salsify/jsonstreamingparser
	
	const STATE_START_DOCUMENT = 0;
	const STATE_IN_OBJECT = 1;
	const STATE_IN_ARRAY = 2;
	const STATE_IN_KEY = 3;
	const STATE_IN_VALUE = 4;
	const STATE_IN_VALUE_STRING = 5;
	const STATE_END_KEY = 6;
	const STATE_AFTER_KEY = 7;
	const STATE_AFTER_VALUE = 8;
	const STATE_START_ESCAPE_IN_KEY = 9;
	const STATE_START_ESCAPE_IN_VALUE_STRING = 10;
	const STATE_DONE = 11;
	
	const STACK_OBJECT = 1;
	const STACK_ARRAY = 2;
	const STACK_VALUE_STRING = 3;
	
	const BOM_UTF8 = 1;
    const BOM_UTF16 = 2;
    const BOM_UTF32 = 3;
	
	protected $stream;
	protected $str_path_capture;
	protected $func_return_capture;
	
	protected $num_buffer_size = 8192;
	protected $bom_utf;
	protected $do_parse;
	protected $state;
	protected $str_character;
	protected $num_position;
	
	protected $arr_path;
	protected $str_object_key;
	
	protected $arr_stack;
	protected $num_stack_level_count;
	protected $stacked;
	
	protected $num_stack_level_capture;
	protected $str_capture;
	protected $do_capture;
	protected $do_capture_positions = false;
	protected $num_capture_position = null;
	protected $arr_capture_positions = [];
	
	protected $str_buffer = null;
	protected $num_buffer_length = 0;
	protected $num_buffer_count = 0;
	
	public function __construct($stream) {
		
		$this->stream = $stream;
	}
	
	public function init($str_path_capture, $func_return_capture) {
		
		$this->str_path_capture = $str_path_capture;
		$this->func_return_capture = $func_return_capture;
		
		$this->num_stack_level_capture = 0;
		$this->num_stack_level_capture += substr_count($this->str_path_capture, '{');
		$this->num_stack_level_capture += substr_count($this->str_path_capture, '[');

		$this->reset();
		
		$this->parse();
	}
	
	public function reset() {
		
		$this->do_parse = true;
		
		// Buffer
		$this->str_buffer = null;
		$this->num_position = 0;
		
		// Stack
		$this->state = self::STATE_START_DOCUMENT;
		$this->arr_path = [];
		$this->str_object_key = '';
		$this->arr_stack = [];
		$this->stacked = null;
		$this->num_stack_level_count = 0;
		$this->str_capture = '';
		$this->do_capture = false;
		$this->num_capture_position = null;

		rewind($this->stream);
	}
	
	public function stop() {
		
		$this->do_parse = false;
	}
	
	public function resume() {
		
		$this->do_parse = true;
		
		return $this->parse();
	}
	
	protected function parse() {
		
		while ($this->str_buffer !== null || !feof($this->stream)) {
			
			if ($this->str_buffer === null) {
				
				$this->num_position = ftell($this->stream);
				
				$this->str_buffer = fread($this->stream, $this->num_buffer_size);
				$this->num_buffer_length = strlen($this->str_buffer);
				$this->num_buffer_count = 0;
			}
			
			for ($this->num_buffer_count; $this->num_buffer_count < $this->num_buffer_length; $this->num_buffer_count++) {
				
				$this->str_character = $this->str_buffer[$this->num_buffer_count];
				
				$this->num_position++;
				
				$this->consumeCharacter();
				
				if (!$this->do_parse) {
					return true;
				}
			}
			
			$this->str_buffer = null;
		}
		
		return false;
	}
	
	public function returnCapture() {
		
		$func_return_capture = $this->func_return_capture;
		
		$is_array = ($this->stacked === self::STACK_ARRAY);
		
		if ($is_array) {
			$func_return_capture('['.$this->str_capture.']');
		} else {
			$func_return_capture('{'.$this->str_capture.'}');
		}
		
		$this->str_capture = '';
		
		if (!$this->do_capture_positions) {
			return;
		}
		
		$this->arr_capture_positions[] = [$this->num_capture_position, $this->num_position, $is_array];
		$this->num_capture_position = null;
	}
	
	public function getCapture($num_capture) {
		
		if (!isset($this->arr_capture_positions[$num_capture])) {
			return null;
		}
		
		list($num_position_start, $num_position_end, $is_array) = $this->arr_capture_positions[$num_capture];
		
		$num_position = ftell($this->stream);
		
		fseek($this->stream, $num_position_start);
		$str_data = fread($this->stream, ($num_position_end - $num_position_start));
		
		fseek($this->stream, $num_position);
		
		if ($is_array) {
			return '['.$str_data.']';
		} else {
			return '{'.$str_data.'}';
		}
	}
	
	public function setCapturePositions($arr) {
		
		if (is_bool($arr)) {
			
			$this->do_capture_positions = (bool)$arr;
			return;
		}
		
		$this->arr_capture_positions += $arr;
	}
	
	public function getCapturePositions() {
		
		return $this->arr_capture_positions;
	}
	
	private function consumeCharacter() {
				
		if ($this->num_position < 5 && $this->checkAndSkipUtfBom()) { // See https://en.wikipedia.org/wiki/Byte_order_mark
			return;
		}
		
		if (
			($this->str_character === " " || $this->str_character === "\t" || $this->str_character === "\n" || $this->str_character === "\r")
			&&
			$this->state !== self::STATE_IN_KEY && $this->state !== self::STATE_START_ESCAPE_IN_KEY && $this->state !== self::STATE_IN_VALUE_STRING && $this->state !== self::STATE_START_ESCAPE_IN_VALUE_STRING
		) { // Valid whitespace characters in JSON (from RFC4627 for JSON) include: space, horizontal tab, line feed or new line, and carriage return. thanks: http://stackoverflow.com/questions/16042274/definition-of-whitespace-in-json

			return;
		}

		switch ($this->state) {
			
			case self::STATE_IN_KEY:
			
				if ($this->str_character === '"') {
					$this->state = self::STATE_END_KEY;
				} else {
					if ($this->str_character === '\\') {
						$this->state = self::STATE_START_ESCAPE_IN_KEY;
					}
					$this->str_object_key .= $this->str_character;
				}
			
				break;
				
			case self::STATE_IN_VALUE_STRING:
			
				if ($this->str_character === '\\') {
					$this->state = self::STATE_START_ESCAPE_IN_VALUE_STRING;
				} else if ($this->str_character === '"') {
					$this->endString();
				}
			
				break;
				
			case self::STATE_IN_VALUE:
					
				if ($this->stacked === self::STACK_OBJECT) {
					
					if ($this->str_character === '}') {
						$this->checkObject();
						$this->endObject();
					} elseif ($this->str_character === ',') {
						$this->checkObject();
					}
				} else if ($this->stacked === self::STACK_ARRAY) {
					
					if ($this->str_character === ']') {
						$this->checkArray();
						$this->endArray();
					} elseif ($this->str_character === ',') {
						$this->checkArray();
					}
				} else {
                
					$this->checkValue();
				}
			
				break;
	
			case self::STATE_IN_ARRAY:
			
				if ($this->str_character === ']') {
					$this->endArray();
				} else {
					$this->checkValue();
				}
				
				break;
	
			case self::STATE_IN_OBJECT:
			
				if ($this->str_character === '}') {
					$this->endObject();
				} elseif ($this->str_character === '"') {
					$this->state = self::STATE_IN_KEY;
				}
				
				break;
	
			case self::STATE_END_KEY:
				
				// $this->str_character = ':'
				$this->checkKey();

				break;
	
			case self::STATE_AFTER_KEY:
				
				$this->checkValue();
	
				break;
			
			case self::STATE_START_ESCAPE_IN_KEY:
			
				$this->state = self::STATE_IN_KEY;
	
				break;
				
			case self::STATE_START_ESCAPE_IN_VALUE_STRING:
			
				$this->state = self::STATE_IN_VALUE_STRING;
	
				break;
	
			case self::STATE_AFTER_VALUE:

				if ($this->stacked === self::STACK_OBJECT) {
					
					if ($this->str_character === '}') {
						$this->checkObject();
						$this->endObject();
					} elseif ($this->str_character === ',') {
						$this->checkObject();
					}
				} elseif ($this->stacked === self::STACK_ARRAY) {
					
					if ($this->str_character === ']') {
						$this->checkArray();
						$this->endArray();
					} elseif ($this->str_character === ',') {
						$this->checkArray();
					}
				}
				
				break;
	
			case self::STATE_START_DOCUMENT:
				
				if ($this->str_character === '[') {
					$this->startArray();
				} elseif ($this->str_character === '{') {
					$this->startObject();
				}
				
				break;
	
			case self::STATE_DONE:
				
				$this->do_parse = false;
				
				break;
		}
		
		if (!$this->do_capture || $this->str_character === null) {
			return;
		}
		
		if ($this->num_capture_position === null) {
			$this->num_capture_position = ($this->num_position - 1); // Subtract 1 to start before (include) the starting character
		}
		
		$this->str_capture .= $this->str_character;
	}
	
	private function checkKey() {
		
		if (!$this->do_capture && $this->num_stack_level_count <= $this->num_stack_level_capture) {
			
			$this->arr_path[$this->num_stack_level_count] = ($this->num_stack_level_count > 1 ? $this->arr_path[$this->num_stack_level_count-1] : '').'{"'.$this->str_object_key.'":';
		}
		
		$this->str_object_key = '';
		
		$this->state = self::STATE_AFTER_KEY;
	}
	
	private function checkValue() {
		
		if ($this->str_character === '[') {
			$this->startArray();
		} elseif ($this->str_character === '{') {
			$this->startObject();
		} else if ($this->str_character === '"') {
			$this->startString();
		} else {
			$this->state = self::STATE_IN_VALUE;
		}
	}
	
	private function startString() {
		
		$this->stacked = self::STACK_VALUE_STRING;
		$this->arr_stack[] = self::STACK_VALUE_STRING;
		
		$this->state = self::STATE_IN_VALUE_STRING;
	}
	
	private function endString() {
		
		array_pop($this->arr_stack);
		$this->stacked = end($this->arr_stack);
		
		$this->state = self::STATE_AFTER_VALUE;
	}
	
	private function startArray() {
		
		$this->num_stack_level_count++;
		$this->stacked = self::STACK_ARRAY;
		$this->arr_stack[] = self::STACK_ARRAY;
		
		$this->state = self::STATE_IN_ARRAY;
		
		if (!$this->do_capture && $this->num_stack_level_count <= $this->num_stack_level_capture) {

			$this->arr_path[$this->num_stack_level_count] = ($this->num_stack_level_count > 1 ? $this->arr_path[$this->num_stack_level_count-1] : '').'[';
			
			if ($this->num_stack_level_count === $this->num_stack_level_capture && $this->arr_path[$this->num_stack_level_count] === $this->str_path_capture) {
								
				$this->do_capture = true;
				$this->str_character = null;
			}
		}
	}
	
	private function checkArray() {
		
		if ($this->num_stack_level_count === $this->num_stack_level_capture && $this->arr_path[$this->num_stack_level_count] === $this->str_path_capture) {
			
			if ($this->do_capture) {
				$this->returnCapture();
			}
			
			$this->do_capture = true;
			$this->str_character = null;
		}
		
		$this->state = self::STATE_IN_ARRAY;
	}	
	
	private function endArray() {
		
		$this->num_stack_level_count--;
		array_pop($this->arr_stack);
		$this->stacked = end($this->arr_stack);
		
		if ($this->do_capture && $this->num_stack_level_count === $this->num_stack_level_capture) {
			
			$this->str_capture .= ']';

			$this->returnCapture();
			
			$this->do_capture = false;
		}
		
		$this->state = self::STATE_AFTER_VALUE;
	
		if (!$this->num_stack_level_count) {
			$this->endDocument();
		}
	}
	
	private function startObject() {
		
		$this->num_stack_level_count++;
		$this->stacked = self::STACK_OBJECT;
		$this->arr_stack[] = self::STACK_OBJECT;
		
		$this->state = self::STATE_IN_OBJECT;
		
		if (!$this->do_capture && $this->num_stack_level_count <= $this->num_stack_level_capture) {

			$this->arr_path[$this->num_stack_level_count] = ($this->num_stack_level_count > 1 ? $this->arr_path[$this->num_stack_level_count-1] : '').'{';
			
			if ($this->num_stack_level_count === $this->num_stack_level_capture && $this->arr_path[$this->num_stack_level_count] === $this->str_path_capture) {
				
				$this->do_capture = true;
				$this->str_character = null;
			}
		}
	}
	
	private function checkObject() {
					
		if ($this->num_stack_level_count === $this->num_stack_level_capture && $this->arr_path[$this->num_stack_level_count] === $this->str_path_capture) {
			
			if ($this->do_capture) {
				$this->returnCapture();
			}
			
			$this->do_capture = true;
			$this->str_character = null;
		}
		
		$this->state = self::STATE_IN_OBJECT;
	}		
	
	private function endObject() {
		
		$this->num_stack_level_count--;
		array_pop($this->arr_stack);
		$this->stacked = end($this->arr_stack);
		
		$this->state = self::STATE_AFTER_VALUE;
		
		if ($this->do_capture && $this->num_stack_level_count === $this->num_stack_level_capture) {
			
			$this->str_capture .= '}';

			$this->returnCapture();
			
			$this->do_capture = false;
		}

		if (!$this->num_stack_level_count) {
			$this->endDocument();
		}
	}
	
	private function endDocument() {
		
		$this->state = self::STATE_DONE;
	}
		
    private function checkAndSkipUtfBom() {
		
		if ($this->num_position == 1) {
			
			if ($this->str_character == chr(239)) {
				$this->bom_utf = self::BOM_UTF8;
			} elseif ($this->str_character == chr(254) || $this->str_character == chr(255)) {
				// NOTE: could also be BOM_UTF32
				// second character will tell
				$this->bom_utf = self::BOM_UTF16;
			} elseif ($c == chr(0)) {
				$this->bom_utf = self::BOM_UTF32;
			}
		}
	
		if ($this->bom_utf == self::BOM_UTF16 && $this->num_position == 2 && $this->str_character == chr(254)) {
			$this->bom_utf = self::BOM_UTF32;
		}
	
		if ($this->bom_utf == self::BOM_UTF8 && $this->num_position < 4) {
			// UTF-8 BOM starts with chr(239) . chr(187) . chr(191)
			return true;
		} elseif ($this->bom_utf == self::BOM_UTF16 && $this->num_position < 3) {
			return true;
		} elseif ($this->bom_utf == self::BOM_UTF32 && $this->num_position < 5) {
			return true;
		}
	
		return false;
	}
}
