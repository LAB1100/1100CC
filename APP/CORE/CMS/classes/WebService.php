<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class WebService extends WebSocketServer {

	protected static $class_user = 'WebServiceUser';
	
	protected $arr_tasks = [];
		
	public function __construct($addr, $port, $use_ssl = false) {
	
		parent::__construct($addr, $port, $use_ssl, 1048576); // 1MB... overkill for an echo server, but potentially plausible for other applications.
	}
	
	public function addTask($task) {
		
		$this->arr_tasks[$task::$name] = $task;
	}
	
	public function init() {
		
		if (!$this->arr_tasks) {
			error('Web Service: No tasks available');
		}
		
		parent::init();
	}
	
	protected function process($user, $str_data) {
		
		$arr_data = json_decode($str_data, true);
		
		if (!is_array($arr_data['arr_tasks'])) {
			return;
		}
		
		foreach ($arr_data['arr_tasks'] as $str_task => $arr_task) {
			
			$task = $this->arr_tasks[$str_task];
			
			if (isset($arr_task['arr_options'])) {
				
				$user->addTaskOptions($str_task, $arr_task['arr_options']); // Register
			}
			
			if (!is_array($arr_task['arr_data'])) {
				continue;
			}
			
			$task->setActiveUser($user);
			
			if ($user->is_owner) {
	
				$task->setData($arr_task['arr_data']);
			} else {
					
				$task->setUserData($arr_task['arr_data']);
			}
		}
	}
	
	protected function check() {

		foreach ($this->arr_tasks as $str_task => $task) {
			
			if (!$task->check()) {
				continue;
			}
				
			$class_user = static::$class_user;
			
			foreach ($class_user::getTaskUsers($str_task) as $user) {
				
				$task->setActiveUser($user);
				
				$task->readyUserData();
				
				$data = $task->getUserData();
				
				if ($data) {
					$this->send($user, value2JSON([$str_task => $data]));
				}
			}
			
			$task->resetUserData(); // Reset task information
			
			foreach ($class_user::getTaskOwnerUsers($str_task) as $user) {
				
				$task->setActiveUser($user);
				
				$data = $task->getData();

				if ($data) {
					$this->send($user, value2JSON([$str_task => $data]));
				}
			}
		}
	}
		
	protected function connected($user) {
		
	}
	
	protected function closed($user) {
		
	}
}
