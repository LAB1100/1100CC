<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class ParseXML2JSON {
	
	const MODE_COMPACT = 0;
	const MODE_VERBOSE = 1;
	
	protected $serializer = null;
	
	public function __construct($xml) {
		
		libxml_use_internal_errors(true);
		
		$this->serializer = new SerializeXML2JSON($xml);
	}
	
	public function setMode($mode) {
		
		$this->serializer->setMode($mode);
	}
	
	public function get($flags = 0) {
		
		return value2JSON($this->serializer, $flags);
	}
}

// See https://stackoverflow.com/a/31276221, https://stackoverflow.com/a/45236522

class SerializeXML2JSON implements JsonSerializable {

	const ATTRIBUTE_KEY = "@attributes";
	const CONTENT_KEY = "_value";
	
	protected $element = null;
	protected $mode = ParseXML2JSON::MODE_COMPACT;

	public function __construct($xml) {
		
		if (!($xml instanceof SimpleXmlElement)) {
			$xml = new SimpleXmlElement($xml);
		}
		
		$this->element = $xml;
	}
	
	public function setMode($mode) {
		
		$this->mode = $mode;
	}
	
	public function jsonSerialize():mixed {
		
		$value_return = null;
		
		if ($this->element->count()) {
			
			// Serialize children if there are children
			
			$value_return = [];

			foreach ($this->element as $str_tag => $child) {
				
				$value_children = new SerializeXML2JSON($child);
				$value_children->setMode($this->mode);
				$value_children = $value_children->jsonSerialize();
				
				if ($child->attributes()->count()) {
					
					$arr_attributes = [];

					foreach ($child->attributes() as $name => $value) {
						
						$arr_attributes[$name] = (string)$value; // Need to cast to string (or other type) to access the value
					}
					
					$value_append = [static::ATTRIBUTE_KEY => $arr_attributes];
					
					if ($value_children !== null) {
						
						if (is_array($value_children)) {
							$value_append += $value_children;
						} else {
							$value_append[static::CONTENT_KEY] = $value_children;
						}
					}
				} else {
					
					if ($this->mode == ParseXML2JSON::MODE_VERBOSE && !is_array($value_children) && $value_children !== null) {
						$value_append = [static::CONTENT_KEY => $value_children];
					} else {
						$value_append = $value_children;
					}
				}
				
				if ($this->mode == ParseXML2JSON::MODE_VERBOSE) {
					
					$value_return[$str_tag][] = $value_append;
				} else {
					
					if (isset($value_return[$str_tag])) {
						
						if (!is_array($value_return[$str_tag])) { // Convert to array when multiple same tags are present 
							$value_return[$str_tag] = [$value_return[$str_tag]];
						}
						$value_return[$str_tag][] = $value_append;
					} else {
						
						$value_return[$str_tag] = $value_append;
					}
				}
			}
		} else {
			
			// Serialize attributes and text for a leaf-elements
			
			$str_value = (string)$this->element; // Need to cast to string (or other type) to access the value

			if (trim($str_value) !== '') { // If empty string, it is actually an empty element
				$value_return = $str_value;
			}
		}
	
		if ($this->element->xpath('/*') == [$this->element]) { // The root element needs to be named
			
			$value_return = [$this->element->getName() => $value_return];
		}
	
		return $value_return;
	}
}
