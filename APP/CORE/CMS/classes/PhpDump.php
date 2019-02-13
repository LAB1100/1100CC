<?php 

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class PhpDump {

	public $removecomments = false;
	public $removelinebreaks = false;
	public $obfuscateclass = false;
	public $obfuscatefunction = false;
	public $obfuscatevariable = false;
	
	private $variables = [];
	private $functions = [];
	private $classes = [];
	private $classes_used = [];
	
	private $predefined_constants = [];
	private $predefined_functions = [];
	private $predefined_classes = [];
	private $predefined_class_variables = [];
	private $predefined_objects = [];
	
	public $salt_variable = '}2OuA.lPjf|Tyzt4wD$-:6F Rp6@di^=,qbxfaU:_3r,Uf=I|_nj~z]A[qDci4yF';
	public $salt_function = 'K@|?:U+xR+[Kc/(_&B-!l&k@x_+U5!u7Qa}C^6XGXcnl%mH`y{d,QT+&<kYrpaq#';
	public $salt_class = 'XH6o*<EU`-R+kIT_Zn<w)MkGoaFC5Z3&}[0Zoh*S?O}GSu]UT+-6,OghHXQi6Sl$';
	
	function init() {

		$arr_predefined_classes = get_declared_classes();
		$arr_predefined_classes_internal = array_slice($arr_predefined_classes, 0, array_search('Error', $arr_predefined_classes)); // Currently Error is first user defined class
		foreach ($arr_predefined_classes_internal as $class) {
			$this->addPredefinedClass($class);
			$reflect = new ReflectionClass($class);
			
			$arr_methods = $reflect->getMethods(ReflectionProperty::IS_PUBLIC);
			foreach ($arr_methods as $value) {
				$this->addPredefinedFunction($value->getName());
			}

			$arr_vars = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
			foreach ($arr_vars as $value) {
				$this->addPredefinedClassVariable($value->getName());
			}
		}

		$arr_predefined_functions = get_defined_functions();
		foreach ($arr_predefined_functions['internal'] as $function) {
			$this->addPredefinedFunction($function);
		}
		
		$arr_predefined_constants = get_defined_constants(true);
		unset($arr_predefined_constants['user']);
		foreach ($arr_predefined_constants as $arr) {
			foreach ($arr as $key => $value) {
				$this->predefined_constants[strtolower($key)] = 1;
			}
		}
	}
	
	private function isPredefinedVariable($var) {
		$var = (substr($var, 0, 1) == '$' ? substr($var, 1) : $var);
		return $this->predefined_class_variables[strtolower($var)];
	}
	private function isPredefinedFunction($var) {
		return $this->predefined_functions[strtolower($var)];
	}
	private function isPredefinedConstant($constant) {
		return $this->predefined_constants[strtolower($constant)];
	}
	private function isPredefinedClass($class) {
		return $this->predefined_classes[strtolower($class)];
	}
	private function isPredefinedObject($var) {
		$var = (substr($var, 0, 1) == '$' ? substr($var, 1) : $var);
		return $this->predefined_objects[strtolower($var)];
	}
	
	public function addPredefinedFunction($var) {
		foreach ((is_array($var) ? $var : [$var]) as $value) {
			$this->predefined_functions[strtolower($value)] = 1;
		}
	}
	public function addPredefinedClass($var) {
		foreach ((is_array($var) ? $var : [$var]) as $value) {
			$this->predefined_classes[strtolower($value)] = 1;
		}
	}
	public function addPredefinedClassVariable($var) {
		foreach ((is_array($var) ? $var : [$var]) as $value) {
			$this->predefined_class_variables[strtolower($value)] = 1;
		}
	}
	public function addPredefinedObject($var) {
		foreach ((is_array($var) ? $var : [$var]) as $value) {
			$value = (substr($value, 0, 1) == '$' ? substr($value, 1) : $value);
			$this->predefined_objects[strtolower($value)] = 1;
		}
	}
	
	private function getVariable($text_org) {
		
		$text = (substr($text_org, 0, 1) == '$' ? substr($text_org, 1) : $text_org);
		if (!$this->variables[$text]) {
			//$this->variables[$text] = ($this->obfuscatevariable ? '_'.md5($text.$salt_variable) : $text);
			$this->variables[$text] = ($this->obfuscatevariable ? $this->format2Alpha(count($this->variables)) : $text);
		}
		return ($text != $text_org ? '$' : '').$this->variables[$text];
	}
	
	private function getFunction($text_org) {
		
		if (strtolower($text_org) === $text_org || strtoupper($text_org) === $text_org) {
			return $text_org;
		}
		$text = $text_org;
		
		if (!$this->functions[$text]) {
			//$this->functions[$text] = ($this->obfuscatefunction ? '_'.md5($text.$salt_function) : $text);
			$this->functions[$text] = ($this->obfuscatefunction ? 'funcExport1100CCBuilder'.$this->format2Alpha(count($this->functions)) : $text);
		}
		return $this->functions[$text];
	}
	public function returnFunction($text_org) {
		
		if (strtolower($text_org) === $text_org || strtoupper($text_org) === $text_org) {
			return false;
		}
		$text = $text_org;
		
		if ($this->isPredefinedFunction($text)) {
			return false;
		}
		return $this->functions[$text];
	}
	
	private function getClass($text_org) {
		
		$text = $text_org;
		
		$this->classes_used[$text] = 1;

		if (strtolower($text_org) === $text_org) {
			return $text_org;
		}
		
		if (!$this->classes[$text]) {
			//$this->classes[$text] = ($this->obfuscateclass ? '_'.md5($text.$salt_class) : $text);
			$this->classes[$text] = ($this->obfuscateclass ? 'classExport1100CCBuilder'.$this->format2Alpha(count($this->classes)) : $text);
		}
		return $this->classes[$text];
	}
	public function returnClass($text_org) {
		
		if (strtolower($text_org) === $text_org) {
			return false;
		}
		$text = $text_org;
		
		if ($this->isPredefinedClass($text)) {
			return false;
		}
		return $this->classes[$text];
	}
	public function isUsedClass($text_org) {
		
		$text = $text_org;
		
		return $this->classes_used[$text];
	}
	public function resetUsedClasses() {
				
		$this->classes_used = [];
	}
	
	public function format2Alpha($nr) {
		
		$arr_alphabet = range('a', 'z');
		$alpha_flip = array_flip($arr_alphabet);
		if ($nr <= 25) {
			return $arr_alphabet[$nr];
		} else if($nr > 25) {
			$dividend = ($nr + 1);
			$alpha = '';
			$modulo = 0;
			while ($dividend > 0) {
				$modulo = ($dividend - 1) % 26;
				$alpha = $arr_alphabet[$modulo] . $alpha;
				$dividend = floor((($dividend - $modulo) / 26));
			} 
			return $alpha;
		}
	}
	
	public function prerun($file = '') {
		
		$in_var = false;
		$in_function = false;
		$in_class = false;
				
		$code = @file_get_contents($file); 
		if ($code !== false) {
		
		 	$arr_tokens = token_get_all($code);
			$next_token = current($arr_tokens);
			
			$length = count($arr_tokens);
			for ($i = 0; $i < $length; $i++) {
				
				$prev_token = $token;
				$token = $next_token;
				$next_token = next($arr_tokens);

				if (is_string($token)) {
												
					$in_function = false;
					$in_class = false;		 						
					
				} else {
				
					list ($id, $text) = $token;
					switch ($id) {
						case T_VARIABLE:
							if (!$in_var) {
								if (!($text == '$this' || substr($text, 0, 2) == '$_' || substr($text, 0, 8) == '$GLOBALS' || $this->isPredefinedVariable($text))) {
									$this->getVariable($text);
								}
							} else {
								$in_var = false;
							}
							break;
						case T_STRING:
							if ($in_function) {
								if (!$this->isPredefinedFunction($text)) {
									$this->getFunction($text);
								}
								$in_function = false;
							} else if ($in_class) {
								if (!$this->isPredefinedClass($text)) {
									$this->getClass($text);		 	
								}
								$in_class = false;
							}
							break; 
						case T_FUNCTION: 
							$in_function = true;		 	 	
							break; 
						case T_CLASS: 
							$in_class = $text; 	 	
							break; 
						case T_VAR:
							$in_var = true;	 	
							break; 
					}
				}
			}
		}
		return false;
	}
		
	public function trash($file = '') {
	
		$in_function = false;
		$in_class = false;
		$in_class_graph = false;
		$in_new_class = false;
		$in_class_ref = false;
		$in_var = false;
		$in_define_constant = false;
		$in_object = false;
		
		$in_class_count = 0;
		
		$result = '';
		$code = @file_get_contents($file); 
		if ($code !== false) {
		
		 	$arr_tokens = token_get_all($code);
			$next_token = current($arr_tokens);
			
			$length = count($arr_tokens);
			for ($i = 0; $i < $length; $i++) {
				
				$prev_token = $token;
				$token = $next_token;
				$next_token = next($arr_tokens);

				if (is_string($token)) {
				
					$result .= $token;
								
					$in_function = false;
					$in_class = false;		 	
					$in_new_class = false;	
					$in_class_ref = false;					
					$in_var = false;
					$in_object = false;
					
					if ((trim($token) == '{') && ($in_class_graph)) {
						$in_class_count++; 
					} elseif ((trim($token) == '}') && ($in_class_graph)) {
						$in_class_count--; 
						if ($in_class_count == 0) {
							$in_class_graph = false;
						}
					}
					
				} else {
				
					list ($id, $text) = $token;
					switch ($id) {
						case T_VARIABLE:
							if (!$in_var) {
								if ($text == '$this' || substr($text, 0, 2) == '$_' || substr($text, 0, 8) == '$GLOBALS' || $this->isPredefinedVariable($text)) {
									$result .= $text;
								} else {
									$result .= $this->getVariable($text);
								}
								$in_class_ref = false;
								$in_object = $this->isPredefinedObject($text);
							} else {
								$result .= $text;
								$in_var = false;
							}
							break;
						case T_STRING:
							if ($text == 'parent' || $text == 'static' || $text == 'self') {
								$result .= $text;
							} else if ($in_function) {
								if ($in_class_count) {
									if (!$this->isPredefinedFunction($text)) {
										$result .= $this->getFunction($text);
									} else {
										$result .= $text;
									}
								} else {
									$result .= $this->getFunction($text);
								}
								$in_function = false;
							} else if ($in_class || $in_new_class) {
								if (!$this->isPredefinedClass($text)) {
									$result .= $this->getClass($text);
								} else {
									$result .= $text;
								}
								$in_class = false;
								$in_new_class = false;
							} else if ($in_class_ref) {
								if ($in_object) {
									$result .= $text;
									if ($next_token[0] != T_OBJECT_OPERATOR) {
										$in_object = false;
									}
								} else if ($next_token != '(') {
									if (!$this->isPredefinedVariable($text)) {
										$result .= $this->getVariable($text);
									} else {
										$result .= $text;
									}
								} else if (!$this->isPredefinedFunction($text)) {
									$result .= $this->getFunction($text);
								} else {
									$result .= $text;
								}
								$in_class_ref = false;
							} else if ($next_token == '(') {
								if ($text == 'define') {
									$in_define_constant = true;
									$result .= $text;
								} else if (!$this->isPredefinedFunction($text)) {
									$result .= $this->getFunction($text);
								} else {
									$result .= $text;
								}
							} else if (!$this->isPredefinedClass($text) && !$this->isPredefinedConstant($text)) {
								$result .= $this->getClass($text);
							} else {
								$result .= $text;
							}
							break; 
						case T_CONSTANT_ENCAPSED_STRING:
							if ($next_token != ']') {
								$quote = substr($text, 0, 1);
								$text_nq = substr($text, 1, -1);
								if ($in_define_constant) {
									$result .= $quote.$this->getClass($text_nq).$quote;
									$in_define_constant = false;
								} else if ($this->returnFunction($text_nq)) {
									$result .= $quote.$this->returnFunction($text_nq).$quote;
								} else if ($this->returnClass($text_nq)) {
									$result .= $quote.$this->returnClass($text_nq).$quote;
								} else {
									$result .= $text;
								}
							} else {
								$result .= $text;
							}
							break;
						case T_DOUBLE_COLON:
							$in_class_ref = true;
							$result .= $text;		 	
							break;
						case T_OBJECT_OPERATOR:
							$in_class_ref = true;
							$result .= $text;		 	
							break;
						case T_FUNCTION: 
							$in_function = true;		 	
							$result .= $text;		 	
							break; 
						case T_NEW: 
							$in_new_class = true;
							$result .= $text;		 	
							break; 
						case T_CLASS: 
							$in_class = $text;
							$in_class_graph = true;		 	
							$result .= $text;		 	
							break; 
						case T_VAR:
							$in_var = true;
							$result .= $text;		 	
							break; 
						case T_COMMENT:
						case T_DOC_COMMENT:
							if (!$this->removecomments) {
								$result .= $text;		 
							}
							break; 
						case T_WHITESPACE:
							if ($this->removelinebreaks) {
								$result = trim($result).' '.trim($text);
							} else {
								$result .= $text;		 	
							}
							break; 
						default: 
							$result .= $text;		 	
							break; 
					}
				}
			}	
			return $result;		
		}
		return false;
	}
}
