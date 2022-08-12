<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FileArchive {

	private $str_path = false;
	private $zip = false;
	private $num_count_add = 1;

	public function __construct($str_path = false, $arr_contents = []) {
		
		if (!$str_path) {
			
			$this->str_path = tempnam(Settings::get('path_temporary'), '1100CC');
			$this->zip = new ZipArchive();
			$this->zip->open($this->str_path, ZipArchive::CREATE);
		} else {
			
			$this->str_path = FileStore::checkDuplicates($str_path);
			
			$this->zip = new ZipArchive();
			$this->zip->open($this->str_path, ZipArchive::CREATE);
		}
		
		$this->zip->close();
		
		if ($arr_contents) {
			
			$this->add($arr_contents);
		}
	}
	
	public function add($arr_contents) {
		
		$this->zip->open($this->str_path);
		
		foreach ($arr_contents as $filename => $content) { // Filename in zip => file/dir/memory
	
			// Prevent OS max opened file limit
			if ($this->num_count_add % 100 == 0) {
				$this->zip->close();
				$this->zip->open($this->str_path);
			}
			
			try {
				$is_file = is_file($content);
			} catch (Exception $e) { }
			
			if ($is_file) {
		
				$this->zip->addFile($content, $filename); // File
				$this->num_count_add++;
			} else {
				
				try {
					$is_directory = is_dir($content);
				} catch (Exception $e) { }
				
				if ($is_directory) {
					
					$str_path = realpath($content);
					
					$iterator_directories = new RecursiveDirectoryIterator($str_path);
					$iterator_files = new RecursiveIteratorIterator($iterator_directories);

					foreach ($iterator_files as $file) {
						
						if ($file->isFile()) {
							
							$this->zip->addFile($file->getPathname(), $filename.substr($file->getPathname(), strlen($str_path))); // Directory
							$this->num_count_add++;
						}
					}
				} else {
			
					$this->zip->addFromString($filename, $content); // Memory
				}
			}
		}
		
		$this->zip->close();
	}
	
	public function get() {
		
		return $this->str_path;
	}
}
