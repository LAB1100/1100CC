<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FileArchive {

	protected $str_path = false;
	protected $zip = false;
	protected $mode_zip = null;
	
	protected $num_count_files = 0;
	protected $num_count_memory = 0;
	protected $arr_paths_temporary = [];
	protected $func_update = null;
	
	const MEMORY_OPEN_FILES = 100;
	const MEMORY_THRESHOLD_COPY = 100 * BYTE_MULTIPLIER * BYTE_MULTIPLIER;

	public function __construct($str_path = false, $arr_contents = []) {
		
		$do_read_only = ($arr_contents === null || $arr_contents === false); // No contents, open only
		
		if (!$str_path) {

			if ($do_read_only) {
				error('Missing file.');
			}
			
			$this->str_path = getPathTemporary();
			$mode_zip_init = ZipArchive::OVERWRITE;
			$this->mode_zip = ZipArchive::CREATE;
		} else {
			
			$this->str_path = $str_path;
			$mode_zip_init = ($do_read_only ? ZipArchive::RDONLY : ZipArchive::CREATE);
			$this->mode_zip = $mode_zip_init;
		}
		
		$this->zip = new ZipArchive();			
		$this->zip->open($this->str_path, $mode_zip_init);
		
		$this->zip->close();
		
		if ($arr_contents && is_array($arr_contents)) {
			
			$this->add($arr_contents);
		}
	}
	
	public function add($arr_contents) {
		
		$this->zip->open($this->str_path, $this->mode_zip);
		
		foreach ($arr_contents as $str_filename => $content) { // Filename in zip => file/dir/memory
			
			if ($this->num_count_files != 0 && $this->num_count_files % static::MEMORY_OPEN_FILES == 0) { // Prevent OS max opened file limit
				
				$this->zip->close();
				$this->closeAddResourceFiles();
		
				$this->zip->open($this->str_path, $this->mode_zip);
			}
			
			if (is_resource($content)) {
				
				if (fstat($content)['size'] < static::MEMORY_THRESHOLD_COPY) {
					
					$content = read($content);
				} else {
				
					$str_path = $this->getAddResourceFile($content);
				
					$this->zip->addFile($str_path, $str_filename); // File
					$this->updateStatistics();
					
					continue;
				}
			}
			
			if (is_string($content)) {
		
				if (is_file($content)) {
					
					$this->zip->addFile($content, $str_filename); // File
					$this->updateStatistics();
				} else if (is_dir($content)) {
					
					$str_path = realpath($content);
					
					$iterator_directories = new RecursiveDirectoryIterator($str_path, RecursiveDirectoryIterator::SKIP_DOTS);
					$iterator_files = new RecursiveIteratorIterator($iterator_directories);

					foreach ($iterator_files as $file) {
						
						if (!$file->isFile()) {
							continue;
						}
							
						$this->zip->addFile($file->getPathname(), $str_filename.substr($file->getPathname(), strlen($str_path))); // Directory
						$this->updateStatistics();
					}
				} else {
				
					$this->zip->addFromString($str_filename, $content); // Memory
					$this->updateStatistics(true);
				}
			}
		}

		$this->zip->close();
		$this->closeAddResourceFiles();
	}
	
	protected function getAddResourceFile($content) {
		
		$str_path = getPathTemporary();
		$this->arr_paths_temporary[] = $str_path;
		
		$file = fopen($str_path, 'w+');
		stream_copy_to_stream($content, $file);
		rewind($content);
		fclose($file);
		
		return $str_path;
	}
	
	protected function closeAddResourceFiles() {
		
		if (!$this->arr_paths_temporary) {
			return;
		}

		foreach ($this->arr_paths_temporary as $str_path) {
			unlink($str_path);
		}
		
		$this->arr_paths_temporary = [];
	}
		
	protected function updateStatistics($is_memory = false) {
		
		if ($is_memory) {
			$this->num_count_memory++;
		} else {
			$this->num_count_files++;
		}
		
		if ($this->func_update) {
			
			$num_total = ($this->num_count_files + $this->num_count_memory);
			
			$func = $this->func_update;
			$func($num_total);
		}
	}
	
	public function getStatistics($func = false) {
		
		if ($func) {
			$this->func_update = $func;
		}
		
		return ($this->num_count_files + $this->num_count_memory);
	}
	
	public function iterate() {
		
		$this->zip->open($this->str_path, ZipArchive::RDONLY);
				
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
	
	public function getEntryAsArchive($str_entry) {

		$this->zip->open($this->str_path, ZipArchive::RDONLY);
		
		$arr_add = [];
		
		if (strEndsWith($str_entry, '/')) {
			
			for ($i = 0; $i < $this->zip->numFiles; $i++) {
			
				$str_entry_check = $this->zip->getNameIndex($i);
				
				if (!strStartsWith($str_entry_check, $str_entry)) {
					continue;
				}
				
				//$file = $this->zip->getStreamName($str_entry_check);
				$file = $this->zip->getStream($str_entry_check);
				
				$arr_add[basename($str_entry_check)] = $file;
			}
		} else {
		
			//$file = $this->zip->getStreamName($str_entry);
			$file = $this->zip->getStream($str_entry);
			
			if ($file) {
				$arr_add[basename($str_entry)] = $file;
			}
		}
		
		if (!$arr_add) {
			
			$this->zip->close();
			
			return false;
		}
		
		$archive_temporary = new FileArchive();
		
		$archive_temporary->add($arr_add);
		
		foreach ($arr_add as $file) {
			fclose($file);
		}
		
		$this->zip->close();
		
		return $archive_temporary->get();
	}
	
	public function get() {
		
		return $this->str_path;
	}
}
