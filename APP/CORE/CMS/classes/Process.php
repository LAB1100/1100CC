<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class Process {
	
	protected $command;
	protected $pid;

	public function __construct($cl = false) {
		
		if ($cl == false) {
			return;
		}
			
		$this->command = $cl;
		
		$this->open();
	}
	
	protected function open() {
		
		/*
		* Do no wait/block (nohub) command
		* Redirect standard output (stdout) to /dev/null
		* Redirect standard error (2) to standard output (1) (2>&1) 
		* Return process ID by shell ($!)
		*/
		
		$command = 'nohup '.$this->command.' > /dev/null 2>&1 & echo $!';
		exec($command, $op);
		
		$this->setPID((int)$op[0]);
	}

	public function setPID($pid) {
		
		$this->pid = $pid;
	}

	public function getPID() {
		
		return $this->pid;
	}

	public function status() {
		
		$command = 'ps -p '.$this->pid;
		
		exec($command, $op);
		
		if (!isset($op[1])) {
			return false;
		} else {
			return true;
		}
	}

	public function start() {
		
		if ($this->command != '') {
			$this->open();
		} else {
			return true;
		}
	}

	public function close() {
		
		$command = 'kill '.$this->pid;
		exec($command);
		
		if ($this->status() == false) {
			return true;
		} else {
			return false;
		}
	}
}
