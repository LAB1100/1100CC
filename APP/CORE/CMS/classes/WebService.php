<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class WebService extends WebSocketServer {

	protected static $class_user = 'WebServiceUser';
	
	protected $arr_services = [];
		
	public function __construct($addr, $port) {
		
		$arr_web_service_details = getModuleConfiguration('webServiceProperties');

		foreach($arr_web_service_details as $module => $classes) {
			foreach($classes as $class => $arr_options) {
											
				$obj_service = new $class();
				$obj_service->init();
				
				if ($arr_options['passkey']) {
					$obj_service->use_passkey = true;
				}
				
				$this->arr_services[$obj_service->name] = $obj_service;
			}
		}
		
		if (!$this->arr_services) {
			error('Web Service: No services available');
		}
		
		parent::__construct($addr, $port, 1048576); // 1MB... overkill for an echo server, but potentially plausible for other applications.
	}
	
	protected function check() {

		foreach ($this->arr_services as $service => $obj_service) {
			
			if ($obj_service->check()) {
				
				$class_user = static::$class_user;
				
				foreach ($class_user::getServiceUsers($service) as $user) {
					
					$arr_options = $user->getServiceOptions($service);
					$obj_service->setOptions(($obj_service->use_passkey ? $user->passkey : 0), $arr_options);
					
					$obj_service->ready();
					
					$data = $obj_service->get();
					
					if ($data) {
						$this->send($user, json_encode($data));
					}
				}
				
				$obj_service->reset(); // Reset service information
			}
		}
	}
	
	protected function process($user, $data) {
		
		$arr_data = json_decode($data, true);
		
		foreach ((array)$arr_data['arr_services'] as $service => $arr_service) {
			
			if (isset($arr_service['arr_options'])) {
				
				$user->addService($service, $arr_service['arr_options']);
			}
			
			foreach ((array)$arr_service['arr_data'] as $data) {
				
				$obj_service = $this->arr_services[$service];
				
				$obj_service->set(($obj_service->use_passkey ? $user->passkey : 0), $data);
			}
		}
	}
	
	protected function connected($user) {
		
	}
	
	protected function closed($user) {
		
	}
}
