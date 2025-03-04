<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class ProcessProgram extends Process {
	
	protected $process;
	protected $exitcode;
	
	protected $stdin;
	protected $stdout;
	protected $stderr;
	
	protected $str_stdout;
	protected $str_stdout_buffer;
	protected $str_stderr;
	protected $str_stderr_buffer;
	
	protected $eol = EOL_1100CC;
	
	const SIZE_BUFFER_MAX = 8092;
    
	protected function open() {

		$arr_descriptors = [
			0 => ['pipe', 'r'], // stdin
			1 => ['pipe', 'w'], // stdout
			2 => ['pipe', 'w'] // stderr
		];

		$arr_env = [];

		$this->process = proc_open('exec '.$this->command, $arr_descriptors, $arr_pipes, Settings::get('path_temporary'), $arr_env); // Put exec before the command to have more control over process termination
		
		if (!$this->process) {
			return false;
		}
			
		$this->stdin = $arr_pipes[0];
		$this->stdout = $arr_pipes[1];
		stream_set_blocking($this->stdout, false); 
		$this->stderr = $arr_pipes[2];
		stream_set_blocking($this->stderr, false);

		$arr_status = $this->status();
		$this->setPID($arr_status['pid']);
		
		$this->str_stdout_buffer = '';
		$this->str_stderr_buffer = '';
			
		return true;
    }
    
	public function writeInput($str, $add_return = false) {
		
		if ($add_return && substr($str, -1) != $this->eol) { // Make sure the input presents a newline return
			$str .= $this->eol;
		}
		
		fwrite($this->stdin, $str);
	}
	
	public function closeInput() {

		fclose($this->stdin);
		$this->stdin = false;
	}
    
    public function checkOutput($do_end = false, $do_continuous = false) { // $do_continuous = null to set mode to blocking

		$this->str_stdout = '';
		$this->str_stderr = '';
		
		$num_wait_seconds = 0;
		$num_wait_microseconds = (0.05 * 1000000); // Seconds to microseconds. Give the ouput a little time to become available
		if ($do_continuous === null) { // Set blocking mode
			$num_wait_seconds = null;
			$num_wait_microseconds = null;
		}
		
		while (true) {
			
			$arr_read = [$this->stdout, $this->stderr];
			$arr_write = null;
			$except = null;

			try {
				$nr_streams = stream_select($arr_read, $arr_write, $except, $num_wait_seconds, $num_wait_microseconds);
			} catch (Exception $e) {
				return false; // Encountered an error in our own process
			}
			
			if ($nr_streams) {
				
				foreach ($arr_read as $read) {
				
					$str = '';
					
					do {
						
						try {
							$buffer = fread($read, static::SIZE_BUFFER_MAX);
						} catch (Exception $e) {
							unset($e);
						}
						
						$nr_bytes = strlen($buffer);
						
						$str .= $buffer;
					} while ($nr_bytes > 0);
					
					if ($read === $this->stdout) {
						$this->str_stdout_buffer .= $str;
					}
					
					if ($read === $this->stderr) {
						$this->str_stderr_buffer .= $str;
					}
				}
			}
			
			// Check all: output, error, program
			
			$has_output = false;
			
			if ($this->str_stdout_buffer !== '') {
				
				$this->str_stdout .= $this->str_stdout_buffer;
				$this->str_stdout_buffer = '';
				
				$has_output = true;
			}
			
			$has_error = false;
			
			if ($this->str_stderr_buffer !== '') {
				
				$this->str_stderr .= $this->str_stderr_buffer;
				$this->str_stderr_buffer = '';
				
				$has_error = true;
			}

			if ($this->exitcode === null) {
					
				$arr_status = $this->status();

				if (!$arr_status['running']) {

					$this->exitcode = $arr_status['exitcode']; // proc_get_status will only pull a valid exitcode one time after process has ended
				}
			}
			
			// Return on its state
			
			if ($has_error) { // Found an error; stop
				return false;
			}

			if ($has_output && $do_end) { // Looking for an end in the output
				
				if ($do_end === true) {
					$str_end = $this->eol;
				} else {
					$str_end = $do_end;
				}
				
				$nr_end = strrpos($this->str_stdout, $str_end);
				
				if ($nr_end !== false) {
					
					$nr_end = $nr_end + strlen($str_end);
					
					$this->str_stdout_buffer = substr($this->str_stdout, $nr_end);
					$this->str_stdout = substr($this->str_stdout, 0, $nr_end);
					
					return true; // Found something needed; stop
				}
			}
			
			if ($this->exitcode !== null) { // Program is not running anymore; stop
				return true;
			}
			
			// Keep checking and loading data

			if ($do_continuous) {
				
				if ($do_continuous === true) {
					continue;
				} else {
					
					$abort = $do_continuous(); // Run a custom callback function
					
					if ($abort) {
						return false;
					}
				}
			} else {
				
				return false;
			}
		}
	}
	
	public function isRunning($get_code) {
		
		return ($this->exitcode !== null ? ($get_code ? $this->exitcode : false) : true);
	}
	
	public function getOutput() {
		
		return $this->str_stdout;
	}
	
	public function getError() {
		 
		return $this->str_stderr;
	}
	
	public function setEOL($eol) {
		
		$this->eol = $eol;
	}
	
	public function getEOL() {
		
		return $this->eol;
	}

	public function status() {
		
		return proc_get_status($this->process);
	}

	public function close($terminate = false) {
		
		if ($terminate) {
			
			proc_terminate($this->process);
		}
		
		if ($this->stdin) {
			fclose($this->stdin);
		}
		fclose($this->stdout);
		fclose($this->stderr);
			
		return proc_close($this->process);
	}
}
