<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class PingbackServer {

	const RESPONSE_SUCCESS = -1;
	const RESPONSE_FAULT_GENERIC = 0;
	const RESPONSE_FAULT_SOURCE = 0x0010;
	const RESPONSE_FAULT_SOURCE_LINK = 0x0011;
	const RESPONSE_FAULT_TARGET = 0x0020;
	const RESPONSE_FAULT_TARGET_INVALID = 0x0021;
	const RESPONSE_FAULT_ALREADY_REGISTERED = 0x0030;
	const RESPONSE_FAULT_ACCESS_DENIED = 0x0031;
	
	public $arr_responses = [
		self::RESPONSE_SUCCESS => 'Success',
		self::RESPONSE_FAULT_GENERIC => 'Unknown error.',
		self::RESPONSE_FAULT_SOURCE => 'The source URI does not exist.',
		self::RESPONSE_FAULT_SOURCE_LINK => 'The source URI does not contain a link to the target URI, and so cannot be used as a source.',
		self::RESPONSE_FAULT_TARGET => 'The specified target URI does not exist.',
		self::RESPONSE_FAULT_TARGET_INVALID => 'The specified target URI cannot be used as a target.',
		self::RESPONSE_FAULT_ALREADY_REGISTERED => 'The pingback has already been registered.',
		self::RESPONSE_FAULT_ACCESS_DENIED => 'Access denied.'
	];

	protected $server;
	protected $response;
	protected $request;
	protected $request_source;
	protected $request_target;
	protected $source_title;
	protected $source_excerpt;
	protected $blog_post_id;
	protected $arr_options = [
		'encoding' => 'utf-8'
	];

	public function __construct($arr_options = []){
		
		$this->server = xmlrpc_server_create();
		
		$this->setOptions($arr_options);
		
		if (!xmlrpc_server_register_method($this->server, 'pingback.ping', [$this, 'ping'])) {
			error('Failed to register method to server');
		}
	}
	
	public function __destruct() {
		
		xmlrpc_server_destroy($this->server);
	}

	protected function ping($method, $parameters) {
		
		list($this->request_source, $this->request_target) = $parameters;

		$fault = false;

		if (!PingbackUtility::isURL($this->request_source)) {
			
			$fault = self::RESPONSE_FAULT_SOURCE;
			$this->setFault($fault);
			return $this->getFaultAsArray($fault);
		}

		if (!PingbackUtility::isURL($this->request_target) || !PingbackUtility::isURLHost($this->request_target)) {
			
			$fault = self::RESPONSE_FAULT_TARGET;
			$this->setFault($fault);
			return $this->getFaultAsArray($fault);
		}
				
		if (!PingbackUtility::isPingbackEnabled($this->request_target)) {
			
			$fault = self::RESPONSE_FAULT_TARGET_INVALID;
			$this->setFault($fault);
			return $this->getFaultAsArray($fault);
		}

		if (!PingbackUtility::isBacklinking($this->request_source, $this->request_target)) {
			
			$fault = self::RESPONSE_FAULT_SOURCE_LINK;
			$this->setFault($fault);
			return $this->getFaultAsArray($fault);
		}
		
		if (!$this->blog_post_id = PingbackUtility::getBlogPostId($this->request_target)) {
			
			$fault = self::RESPONSE_FAULT_TARGET_INVALID;
			$this->setFault($fault);
			return $this->getFaultAsArray($fault);
		}
		
		if (!PingbackUtility::isBlogPost($this->blog_post_id)) {
			
			$fault = self::RESPONSE_FAULT_TARGET_INVALID;
			$this->setFault($fault);
			return $this->getFaultAsArray($fault);
		}
		
		if (!PingbackUtility::isNewEntry($this->request_source, $this->blog_post_id)) {
			
			$fault = self::RESPONSE_FAULT_ALREADY_REGISTERED;
			$this->setFault($fault);
			return $this->getFaultAsArray($fault);
		}
		
		$html = file_get_contents($this->request_source);
		
		if (!$this->source_excerpt = FormatExcerpt::parse($html, $this->request_target, true)) {
			
			$fault = self::RESPONSE_FAULT_TARGET_INVALID;
			$this->setFault($fault);
			return $this->getFaultAsArray($fault);
		}
		
		$this->source_title = PingbackUtility::getTitle($html);
				
		if (!PingbackUtility::addEntry($this->request_source, $this->blog_post_id, $this->source_title, $this->source_excerpt)) {
			
			$fault = self::RESPONSE_FAULT_GENERIC;
			$this->setFault($fault);
			return $this->getFaultAsArray($fault);
		}
				
		$this->setSuccess();
		
		return $this->getSuccessAsArray();
	}
	
	public function getOption($option) {
		
		return isset($this->arr_options[$option]) ? $this->arr_options[$option] : null;
	}
	
	public function setOption($option, $value) {
		
		$this->arr_options[$option] = $value;
	}
	
	public function setOptions($options = []) {
		
		foreach ($options as $option => $value) {
			$this->setOption($option, $value);
		}
	}

	public function execute($request = null) {
		
		if ($request) {
			$this->request = $request;
		}
	
		$this->response = xmlrpc_server_call_method($this->server, $this->request, null, ['encoding' => $this->getOption('encoding')]);
	}

	public function setResponse($response) {
		
		$this->response = $response;
	}

	public function setRequest($request) {
		
		$this->request = $request;
	}

	public function getRequest() {
		
		return $this->request;
	}

	public function getResponse() {
		
		return $this->response;
	}

	public function getSourceURL() {
		
		return $this->request_source;
	}

	public function getTargetURL() {
		
		return $this->request_target;
	}
	
	public function getBlogPostId() {
		
		return $this->blog_post_id;
	}
	
	public function getSourceTitle() {
		
		return $this->source_title;
	}
	
	public function getSourceExcerpt() {
		
		return $this->source_excerpt;
	}
	
	public function getFaultAsArray($fault_code) {
		
		return ['faultCode' => $fault_code, 'faultString' => $this->arr_responses[$fault_code]];
	}
	
	public function getSuccessAsArray() {
		
		return [$this->arr_responses[self::RESPONSE_SUCCESS]];
	}

	public function setFault($fault_code) {
		
		error('Pingback reference received and failed. Error: '.$this->arr_responses[$fault_code].' Source: '.$this->request_source.' Target: '.$this->request_target);
		
		$this->response = xmlrpc_encode($this->getFaultAsArray($fault_code));
	}
	
	public function setSuccess() {
		
		msg('Pingback reference received. Source: '.$this->request_source.' Target: '.$this->request_target, 'PINGBACK');
		
		$this->response = xmlrpc_encode($this->getSuccessAsArray());
	}

	public function isValid() {
		
		return !xmlrpc_is_fault($this->response);
	}
}
