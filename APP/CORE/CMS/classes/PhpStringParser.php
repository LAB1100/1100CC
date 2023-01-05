<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class PhpStringParser {

	protected static $arr_calls_allowed = [
		'explode','implode','date','time','round','trunc','rand','ceil','floor','srand','strtolower','strtoupper','substr','stristr','strpos','print_r',
		'getLabel'
	];
	protected static $arr_classes_allowed = [
		'Labels', 'SiteStartVars', 'SiteEndVars', 'cms_general'
	];
	protected $variables;
	protected $safe;
	
	public function __construct($variables = [], $safe = true) {
		$this->variables = $variables;
		$this->safe = $safe;
	}
	
	public function parse($string) {
		return preg_replace_callback('/(\<\?=|\<\?php=|\<\?php)(.*?)\?\>/si', [&$this, 'evalBlock'], $string);
	}
	
	public function charge($string) {
	
		ob_start(); // Catch the echos
		
		eval($string);
		
		return ob_get_clean();
	}
	
	protected function evalBlock($matches) {
		
		if (is_array($this->variables)) {
			
			foreach($this->variables as $var_name => $var_value) {
				$$var_name = $var_value;
			}
		}
		
		$matches[2] = trim($matches[2]);
		$eval_end = '';
		
		if ($matches[1] == '<?=' || $matches[1] == '<?php=') { /* <?= $var ?> => <?= $var; ?> */ 
			if (substr($matches[2], -1) !== ';') {
				$eval_end = ';';
			}
		}
		
		if ($this->safe) {
			$this->checkIfSafe($matches[2].$eval_end);
		}

		$return_block = $this->charge($matches[2].$eval_end);
		
		return $return_block;
	}
	
	protected function checkIfSafe($string) {
		
		$tokens = token_get_all('<?php '.$string.' ?>');
		$vcall = ""; // Prevent $func = "unlink"; $func();
		
		foreach ($tokens as $token) {
			if (is_array($token)) {
				switch ($token[0]) {
					case(T_VARIABLE):
						$vcall .= 'v';
						break;
					case(T_STRING): 
						$vcall .= 's';
						if (!$in_allowed_class && array_search($token[1], self::$arr_classes_allowed) !== false) {
							$in_allowed_class = true;
							break;
						}
					case(T_REQUIRE_ONCE): case(T_REQUIRE): case(T_NEW):
					case(T_CLONE): case(T_EXIT): case(T_GLOBAL): case(T_INCLUDE_ONCE):
					case(T_INCLUDE): case(T_EVAL): case(T_FUNCTION):
						if (!$in_allowed_class && array_search($token[1], self::$arr_calls_allowed) === false) {
							error('Invalid or not allowed code found');
						}
						$in_allowed_class = false;
					case T_DOUBLE_COLON:
						break;
					case T_OBJECT_OPERATOR:
						break;
					default:
						$in_allowed_class = false;
				}
			} else {
				$vcall .= $token;
			}
			if (stristr($vcall, 'v(')) {
				error('Invalid or not allowed code found');
			}
		}
	}
}
