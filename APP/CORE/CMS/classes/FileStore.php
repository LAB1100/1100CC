<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FileStore {
	
	const EXTENSION_UNKNOWN = 'unknown';
	
	private static $arr_mime_type_ext = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/gif' => 'gif',
		'image/bmp' => 'bmp',
		'image/x-icon' => 'icon',
		'image/svg+xml' => 'svg',
		'text/csv' => 'csv',
		'application/pdf' => 'pdf',
		'application/zip' => 'zip',
		'application/json' => 'json'
	];
	private static $disallowed_ext = ['php', 'ini', 'py', 'dll', 'exe', 'html', 'htm', 'sh'];
	private static $img_ext = ['jpg', 'jpeg', 'png', 'bmp', 'gif'];

	private $path_source = false;
	private $source_name = false;

	private $size = false;
	private $ext = false;
	private $path_destination = false;
	
	private $from_cache = false;

	public function __construct($file, $destination, $file_size = 0, $img_only = false) {
	
		// $file = $_FILE or FileGet();
		// $destination = string OR array("dir" => string, "filename" => string, "overwrite" => boolean)

		if (is_object($file)) {
			$this->path_source = $file->getPath();
			$this->source_name = $file->getSource();
			$this->from_cache = true;
		} else {
			$this->path_source = $file['tmp_name'];
			$this->source_name = $file['name'];
		}
	
		try {
			if ((!$this->from_cache && !is_uploaded_file($this->path_source)) || ($this->from_cache && !isPath($this->path_source))) {
				Labels::setVariable('max_size', getMaxUploadSize());
				error(getLabel('msg_no_file_or_too_large'));
			}
			$this->size = filesize($this->path_source);
			if ($file_size > 0 && $this->size > $file_size) {
				error(getLabel('msg_file_too_large'));
			}
			
			$dir = (is_array($destination) ? $destination['dir'] : $destination);
			self::makeDirectoryTree($dir);
			
			$filename = (is_array($destination) && $destination['filename'] ? $destination['filename'] : basename($this->source_name));
			
			$ext = self::getExtension($this->source_name);
			if ($ext == self::EXTENSION_UNKNOWN) {
				$ext = self::getExtension($filename);
			}
			
			$this->ext = $ext;

			if (strpos($filename, '.')) {
				$filename = substr($filename, 0, strpos($filename, '.'));
			}
			$filename = $filename.'.'.$this->ext;
			
			$overwrite = (is_array($destination) && $destination['overwrite']);	

			if ($img_only && !in_array($this->ext, self::$img_ext)) {
				
				error('Not an image');
			}
			if (!$img_only && (!$this->ext || in_array($this->ext, self::$disallowed_ext))) {
				
				error(getLabel('msg_invalid_file_type'));
			}
			
			$filename = self::cleanFilename($filename);
			
			if ($overwrite) {
				
				if (isPath($dir.$filename)) {
					self::deleteFile($dir.$filename);
				}
				
				$this->path_destination = $dir.$filename;
			} else {
				
				$this->path_destination = self::checkDuplicates($dir.$filename);
			}
			
			if ($this->from_cache) {
				
				rename($this->path_source, $this->path_destination);
			} else {
				
				move_uploaded_file($this->path_source, $this->path_destination);
			}
			
			self::setFilePermission($this->path_destination);
						
		} catch (Exception $e) {
			
			if ($this->from_cache) {
				$file->abort();
			}
			
			error(getLabel('msg_upload_failed').' '.$e->getMessage(), TROUBLE_NOTICE, LOG_BOTH, false, $e); // Make notice
		}
	}
	
	public function getDetails() {
	
		$arr = [];
	
		$arr['size'] = $this->size;
		$arr['ext']  = $this->ext;
		$arr['name'] = pathinfo($this->path_destination, PATHINFO_BASENAME);
		
		$file_info = new finfo(FILEINFO_MIME_TYPE);
		$file_type = $file_info->file($this->path_destination);
		$arr['type']  = $file_type;
		
		return $arr;
	}
	
	public function imageResize($width, $height) {
	
		if (in_array($this->ext, self::$img_ext)) {
			
			$resize = new ImageResize();
			$resize->resize($this->path_destination, $this->path_destination, $width, $height);
			
			$this->size = filesize($this->path_destination);
		}
	}
	
	static public function getExtension($value) {
		
		if (isPath($value)) { // Try to determine extension by mime type
			
			$ext = self::getFileExtension($value);
		}
		
		if (!$ext) {
			
			$ext = self::getFilenameExtension($value);
		}
		
		if (!$ext) {
			$ext = self::EXTENSION_UNKNOWN;
		}
		
		return $ext;
	}

	static public function getFileExtension($file) {
		
		$file_info = new finfo(FILEINFO_MIME_TYPE);
		$file_type = $file_info->file($file);
			
		$ext = self::$arr_mime_type_ext[$file_type];
		
		return ($ext ?: false);
	}
	
	static public function getFilenameExtension($filename) {
		
		$ext = strtolower(substr(strrchr($filename, '.'), 1));
			
		if (preg_match('/[^a-z0-9]/', $ext)) {
			$ext = false;
		}
		
		return $ext;
	}
	
	static public function getExtensionMIMEType($what) {
		
		$type = array_search($what, self::$arr_mime_type_ext);
		
		return ($type ?: false);
	}

	static public function cleanFilename($filename) {
		
		$arr_check = ['\\', '/', ' ', '\'', '"', '%20', '!', '@', '#', '$', '%', '^', '&', '*'];
		
		return str_replace($arr_check, '', $filename); 
	}

	static public function checkDuplicates($target, $count = 0) {		
		
		if (isPath($target)) {
			
			$count++;
			
			if (preg_match("/(\()([0-9]+)(\))/", pathinfo($target, PATHINFO_BASENAME))) {
				
				$new_filename = preg_replace("/(\()([0-9]+)(\))/", "(".$count.")", pathinfo($target, PATHINFO_BASENAME));
			} else {
				
				$new_filename = pathinfo($target, PATHINFO_FILENAME)."(".$count.").".pathinfo($target, PATHINFO_EXTENSION);
			}
			
			$new_target = pathinfo($target, PATHINFO_DIRNAME).'/'.$new_filename;
						
			return self::checkDuplicates($new_target, $count);
		} else {	
					
			return $target;
		}
	}
	
	static public function setFilePermission($path, $mode = false) {
		
		chmod($path, ($mode ?: Settings::get('chmod_file')));
	}
	
	static public function setFilePermissionRecursive($dir, $mode = false) {
		
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);

		foreach ($iterator as $item) {
			self::setFilePermission($item, $mode);
		}
	}
	
	static public function makeDirectoryTree($path, $mode = false) {
		
		if (isPath($path) || !trim($path)) {
			return true;
		}
		
		$path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
		$next_path = substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR));

		if (self::makeDirectoryTree($next_path, $mode)) {
			
			if (!isPath($path)) {
				
				$mode = ($mode ?: Settings::get('chmod_directory'));
				
				mkdir($path, $mode);
				self::setFilePermission($path, $mode);
				
				return true;
			}
		}

		return false;
	}
		
	static public function delDirectoryTree($dir) {
		
		$files = array_diff(scandir($dir), ['.','..']);
		
		foreach ($files as $file) {
			if (is_dir($dir.'/'.$file)) {
				self::delDirectoryTree($dir.'/'.$file);
			} else {
				unlink($dir.'/'.$file);
			}
		}
		
		return rmdir($dir);
	}
	
	static public function storeFile($path, $data, $path_destination = false) {
		
		$dir = dirname($path);
		
		self::makeDirectoryTree($dir);
		$file = fopen($path, 'c');
		self::setFilePermission($path);
		
		if (flock($file, LOCK_EX)) {
			
			ftruncate($file, 0);
			fwrite($file, $data);
			flock($file, LOCK_UN);
		}
		
		fclose($file);
		
		if ($path_destination) {
			rename($path, $path_destination);
		}
	}
		
	static public function deleteFile($path) {
		
		if (trim($path != '')) {
						
			if (isPath($path)) {			
				return unlink($path);
			}
		}
		
		return true;
	}
}
