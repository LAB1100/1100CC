<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
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
	protected $func_return_captured;
	
	protected $num_buffer_size = 8192;
	protected $str_line_end = "\n";
	protected $bom_utf;
	protected $do_parse;
	protected $state;
	protected $str_character;
	protected $pos_line;
	protected $pos_character;
	
	protected $arr_path;
	protected $str_object_key;
	
	protected $arr_stack;
	protected $count_stack_level;
	protected $stacked;
	
	protected $num_capture_stack_level;
	protected $str_capture;
	protected $do_capture;
	
	protected $str_buffer = false;
	protected $length_buffer = false;
	protected $count_buffer = false;
	protected $is_eol = false;
	
	public function __construct($stream) {
		
		$this->stream = $stream;
	}
	
	public function init($str_path_capture, $func_return_captured) {
		
		$this->str_path_capture = $str_path_capture;
		$this->func_return_captured = $func_return_captured;
		
		$this->num_capture_stack_level = 0;
		$this->num_capture_stack_level += substr_count($this->str_path_capture, '{');
		$this->num_capture_stack_level += substr_count($this->str_path_capture, '[');

		$this->reset();
		
		$this->parse();
	}
	
	public function reset() {
		
		$this->do_parse = true;
		
		// Buffer
		$this->str_buffer = false;
		$this->pos_line = 1;
		$this->pos_character = 1;
		
		// Stack
		$this->state = self::STATE_START_DOCUMENT;
		$this->arr_path = [];
		$this->str_object_key = '';
		$this->arr_stack = [];
		$this->stacked = null;
		$this->count_stack_level = 0;
		$this->str_capture = '';
		$this->do_capture = false;

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
		
		$is_eof = false;
		
		while ($this->str_buffer !== false || (!feof($this->stream) && !$is_eof)) {
			
			if ($this->str_buffer === false) {
				
				$pos = ftell($this->stream);
				
				$this->str_buffer = stream_get_line($this->stream, $this->num_buffer_size, $this->str_line_end);
				$this->length_buffer = strlen($this->str_buffer);
				$this->is_eol = (bool)(ftell($this->stream) - $this->length_buffer - $pos); // Is end of line
				$this->count_buffer = 0;
				
				// If we're still at the same place after stream_get_line, we're done
				$is_eof = ftell($this->stream) == $pos;
			}
			
			for ($this->count_buffer; $this->count_buffer < $this->length_buffer; $this->count_buffer++) {
				
				$this->str_character = $this->str_buffer[$this->count_buffer];
				
				$this->consumeCharacter();
				
				$this->pos_character++;
				
				if (!$this->do_parse) {
					return true;
				}
			}
			
			$this->str_buffer = false;
			
			if ($this->is_eol) {
				
				$this->pos_line++;
				$this->pos_character = 1;
			}
		}
		
		return false;
	}
	
	public function returnCaptured() {
		
		$func_return_captured = $this->func_return_captured;
		
		if ($this->stacked === self::STACK_ARRAY) {
			$func_return_captured('['.$this->str_capture.']');
		} else {
			$func_return_captured('{'.$this->str_capture.'}');
		}
		
		$this->str_capture = '';
	}
	
	private function consumeCharacter() {
				
		if ($this->pos_character < 5 && $this->pos_line == 1 && $this->checkAndSkipUtfBom()) { // See https://en.wikipedia.org/wiki/Byte_order_mark
			return;
		}
		
		// Valid whitespace characters in JSON (from RFC4627 for JSON) include: space, horizontal tab, line feed or new line, and carriage return. thanks: http://stackoverflow.com/questions/16042274/definition-of-whitespace-in-json
		if (
			($this->str_character === " " || $this->str_character === "\t" || $this->str_character === "\n" || $this->str_character === "\r")
			&&
			!(
				$this->state === self::STATE_IN_KEY ||
				$this->state === self::STATE_START_ESCAPE_IN_KEY ||
				$this->state === self::STATE_IN_VALUE_STRING ||
				$this->state === self::STATE_START_ESCAPE_IN_VALUE_STRING
			)
		) {
			
			// We wrap this so that we don't make a ton of unnecessary function calls
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
		
		if ($this->do_capture && $this->str_character !== false) {
			
			$this->str_capture .= $this->str_character;
		}
	}
	
	private function checkKey() {
		
		if (!$this->do_capture && $this->count_stack_level <= $this->num_capture_stack_level) {
			
			$this->arr_path[$this->count_stack_level] = ($this->count_stack_level > 1 ? $this->arr_path[$this->count_stack_level-1] : '').'{"'.$this->str_object_key.'":';
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
		
		$this->count_stack_level++;
		$this->stacked = self::STACK_ARRAY;
		$this->arr_stack[] = self::STACK_ARRAY;
		
		$this->state = self::STATE_IN_ARRAY;
		
		if (!$this->do_capture && $this->count_stack_level <= $this->num_capture_stack_level) {

			$this->arr_path[$this->count_stack_level] = ($this->count_stack_level > 1 ? $this->arr_path[$this->count_stack_level-1] : '').'[';
			
			if ($this->count_stack_level === $this->num_capture_stack_level && $this->arr_path[$this->count_stack_level] === $this->str_path_capture) {
								
				$this->do_capture = true;
				$this->str_character = false;
			}
		}
	}
	
	private function checkArray() {
		
		if ($this->count_stack_level === $this->num_capture_stack_level && $this->arr_path[$this->count_stack_level] === $this->str_path_capture) {
			
			if ($this->do_capture) {
				
				$this->returnCaptured();
			}
			
			$this->do_capture = true;
			$this->str_character = false;
		}
		
		$this->state = self::STATE_IN_ARRAY;
	}	
	
	private function endArray() {
		
		$this->count_stack_level--;
		array_pop($this->arr_stack);
		$this->stacked = end($this->arr_stack);
		
		if ($this->do_capture && $this->count_stack_level === $this->num_capture_stack_level) {
			
			$this->str_capture .= ']';

			$this->returnCaptured();
			
			$this->do_capture = false;
		}
		
		$this->state = self::STATE_AFTER_VALUE;
	
		if (!$this->count_stack_level) {
			$this->endDocument();
		}
	}
	
	private function startObject() {
		
		$this->count_stack_level++;
		$this->stacked = self::STACK_OBJECT;
		$this->arr_stack[] = self::STACK_OBJECT;
		
		$this->state = self::STATE_IN_OBJECT;
		
		if (!$this->do_capture && $this->count_stack_level <= $this->num_capture_stack_level) {

			$this->arr_path[$this->count_stack_level] = ($this->count_stack_level > 1 ? $this->arr_path[$this->count_stack_level-1] : '').'{';
			
			if ($this->count_stack_level === $this->num_capture_stack_level && $this->arr_path[$this->count_stack_level] === $this->str_path_capture) {
				
				$this->do_capture = true;
				$this->str_character = false;
			}
		}
	}
	
	private function checkObject() {
					
		if ($this->count_stack_level === $this->num_capture_stack_level && $this->arr_path[$this->count_stack_level] === $this->str_path_capture) {
			
			if ($this->do_capture) {
				
				$this->returnCaptured();
			}
			
			$this->do_capture = true;
			$this->str_character = false;
		}
		
		$this->state = self::STATE_IN_OBJECT;
	}		
	
	private function endObject() {
		
		$this->count_stack_level--;
		array_pop($this->arr_stack);
		$this->stacked = end($this->arr_stack);
		
		$this->state = self::STATE_AFTER_VALUE;
		
		if ($this->do_capture && $this->count_stack_level === $this->num_capture_stack_level) {
			
			$this->str_capture .= '}';

			$this->returnCaptured();
			
			$this->do_capture = false;
		}

		if (!$this->count_stack_level) {
			
			$this->endDocument();
		}
	}
	
	private function endDocument() {
		
		$this->state = self::STATE_DONE;
	}
		
    private function checkAndSkipUtfBom() {
		
		if ($this->pos_character == 1) {
			
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
	
		if ($this->bom_utf == self::BOM_UTF16 && $this->pos_character == 2 && $this->str_character == chr(254)) {
			
			$this->bom_utf = self::BOM_UTF32;
		}
	
		if ($this->bom_utf == self::BOM_UTF8 && $this->pos_character < 4) {
			// UTF-8 BOM starts with chr(239) . chr(187) . chr(191)
			return true;
		} elseif ($this->bom_utf == self::BOM_UTF16 && $this->pos_character < 3) {
			return true;
		} elseif ($this->bom_utf == self::BOM_UTF32 && $this->pos_character < 5) {
			return true;
		}
	
		return false;
	}
}
