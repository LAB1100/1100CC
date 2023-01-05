<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FileArchive {

	protected $str_path = false;
	protected $zip = false;
	protected $num_count_add = 1;
	protected $mode_zip = null;

	public function __construct($str_path = false, $arr_contents = []) {
		
		$do_read_only = ($arr_contents === null || $arr_contents === false); // No contents, open only
		
		if (!$str_path) {

			if ($do_read_only) {
				error('Missing file.');
			}
			
			$this->str_path = tempnam(Settings::get('path_temporary'), '1100CC');
			$this->mode_zip = ZipArchive::CREATE;
		} else {
			
			$this->str_path = $str_path;
			$this->mode_zip = ($do_read_only ? ZipArchive::RDONLY : ZipArchive::CREATE);
		}
		
		$this->zip = new ZipArchive();			
		$this->zip->open($this->str_path, $this->mode_zip);
		
		$this->zip->close();
		
		if ($arr_contents && is_array($arr_contents)) {
			
			$this->add($arr_contents);
		}
	}
	
	public function add($arr_contents) {
		
		$this->zip->open($this->str_path, $this->mode_zip);
		
		foreach ($arr_contents as $filename => $content) { // Filename in zip => file/dir/memory
	
			// Prevent OS max opened file limit
			if ($this->num_count_add % 100 == 0) {
				$this->zip->close();
				$this->zip->open($this->str_path, $this->mode_zip);
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
	
	public function iterate() {
		
		$this->zip->open($this->str_path, $this->mode_zip);
				
		for ($i = 0; $i < $this->zip->numFiles; $i++) {
			
			$str_entry = $this->zip->getNameIndex($i);
			
			$arr_entry = str2Array($str_entry, '/');
			$str_target = $arr_entry[0];
			$arr_segments = array_slice($arr_entry, 1);
			
			$str_path_zip = $this->getEntry($str_entry);
			
			yield $str_path_zip => ['target' => $str_target, 'segments' => $arr_segments];
		}
		
		$this->zip->close();
	}
	
	public function getEntry($str_entry) {
		
		return 'zip://'.$this->str_path.'#'.$str_entry;
	}
	
	public function get() {
		
		return $this->str_path;
	}
}
