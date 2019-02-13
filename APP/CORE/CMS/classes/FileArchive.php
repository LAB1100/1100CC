<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FileArchive {

	private $path = false;
	private $temp = true;
	private $zip = false;
	private $count_add = 1;

	public function __construct($path = false, $arr_contents = []) {
		
		if (!$path) {
			
			$this->path = tempnam(Settings::get('path_temporary'), '1100CC');
			$this->zip = new ZipArchive();
			$this->zip->open($this->path, ZipArchive::CREATE);
		} else {
			
			$this->temp = false;
			$this->path = FileStore::checkDuplicates($path);
			
			$this->zip = new ZipArchive();
			$this->zip->open($this->path, ZipArchive::CREATE);
		}
		
		$this->zip->close();
		
		if ($arr_contents) {
			
			$this->add($arr_contents);
		}
	}
	
	public function add($arr_contents) {
		
		$this->zip->open($this->path);
		
		foreach ($arr_contents as $filename => $content) { // Filename in zip => file/dir/memory
	
			// Prevent OS max opened file limit
			if ($this->count_add % 100 == 0) {
				$this->zip->close();
				$this->zip->open($this->path);
			}
		
			if (@is_file($content)) {
		
				$this->zip->addFile($content, $filename); // File
				$this->count_add++;
			} else if (@is_dir($content)) {

				$dir = realpath($content);
				$dir_it = new RecursiveDirectoryIterator($dir);
				$it = new RecursiveIteratorIterator($dir_it);

				foreach ($it as $file) {
					
					if ($file->isFile()) {
						
						$this->zip->addFile($file->getPathname(), $filename.substr($file->getPathname(), strlen($dir))); // Directory
						$this->count_add++;
					}
				}
			} else {
			
				$this->zip->addFromString($filename, $content); // Memory
			}
		}
		
		$this->zip->close();
	}
	
	public function get() {
		
		return $this->path;
	}
	
	public function read($filename = false) {
		
		ob_end_clean();
		
		Response::sendHeader($this->path, $filename);
		readfile($this->path);
		
		if ($this->temp) {
			unlink($this->path);
		}
	}
}
