<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FileStore {
	
	const EXTENSION_UNKNOWN = 'unknown';
	
	const STORE_FILE_DOCUMENT = 1;
	const STORE_FILE_IMAGE = 2;
	const STORE_FILE_VIDEO = 3;
	const STORE_FILE = self::STORE_FILE_DOCUMENT && self::STORE_FILE_IMAGE & self::STORE_FILE_VIDEO;
	const STORE_TEXT = 4;
	
	protected static $arr_mime_type_ext = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/gif' => 'gif',
		'image/bmp' => 'bmp',
		'image/x-icon' => 'icon',
		'image/svg+xml' => 'svg',
		'text/csv' => 'csv',
		'application/pdf' => 'pdf',
		'application/zip' => 'zip',
		'application/json' => 'json',
		'application/javascript' => 'js',
		'text/css' => 'css'
	];
	protected static $arr_disallowed_extensions = ['php', 'ini', 'py', 'dll', 'exe', 'html', 'htm', 'sh'];
	protected static $arr_img_extensions = ['jpg', 'jpeg', 'png', 'bmp', 'gif'];
	protected static $arr_store_limit = [];

	protected $path_source = false;
	protected $source_name = false;

	protected $num_size = false;
	protected $str_extension = false;
	protected $path_destination = false;
	
	protected $from_cache = false;

	public function __construct($file, $destination, $num_size_limit = false, $img_only = false) {
	
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
				
				Labels::setVariable('max_size', bytes2String(static::getSizeLimitClient()));
				error(getLabel('msg_no_file_or_too_large'));
			}
			
			$this->num_size = filesize($this->path_source);
			
			if ($num_size_limit && $this->num_size > $num_size_limit) {
				
				Labels::setVariable('max_size', bytes2String($num_size_limit));
				error(getLabel('msg_file_too_large'));
			}
			
			$dir = (is_array($destination) ? $destination['dir'] : $destination);
			static::makeDirectoryTree($dir);
			
			$filename = (is_array($destination) && $destination['filename'] ? $destination['filename'] : basename($this->source_name));
			
			$str_extension = static::getExtension($this->path_source);
			if ($str_extension == static::EXTENSION_UNKNOWN) {
				$str_extension = static::getExtension($filename);
			}
			
			$this->str_extension = $str_extension;

			if (strpos($filename, '.')) {
				$filename = substr($filename, 0, strpos($filename, '.'));
			}
			$filename = $filename.'.'.$this->str_extension;
			
			$overwrite = (is_array($destination) && $destination['overwrite']);	

			if ($img_only && !in_array($this->str_extension, static::$arr_img_extensions)) {
				
				Labels::setVariable('type', 'image');
				error(getLabel('msg_invalid_file_type_specific'));
			}
			if (!$img_only && (!$this->str_extension || in_array($this->str_extension, static::$arr_disallowed_extensions))) {
				
				error(getLabel('msg_invalid_file_type'));
			}
			
			$filename = static::cleanFilename($filename);
			
			if ($overwrite) {
				
				if (isPath($dir.$filename)) {
					static::deleteFile($dir.$filename);
				}
				
				$this->path_destination = $dir.$filename;
			} else {
				
				$this->path_destination = static::checkDuplicates($dir.$filename);
			}
			
			if ($this->from_cache) {
				
				rename($this->path_source, $this->path_destination);
			} else {
				
				move_uploaded_file($this->path_source, $this->path_destination);
			}
			
			static::setFilePermission($this->path_destination);
						
		} catch (Exception $e) {
			
			if ($this->from_cache) {
				$file->abort();
			}
			
			$e->setMessage(getLabel('msg_upload_failed').' '.$e->getMessage());
			
			throw($e);
		}
	}
	
	public function getDetails() {
	
		$arr = [];
	
		$arr['size'] = $this->num_size;
		$arr['ext']  = $this->str_extension;
		$arr['name'] = pathinfo($this->path_destination, PATHINFO_BASENAME);
		
		$file_info = new finfo(FILEINFO_MIME_TYPE);
		$file_type = $file_info->file($this->path_destination);
		$arr['type']  = $file_type;
		
		return $arr;
	}
	
	public function imageResize($width, $height) {
	
		if (in_array($this->str_extension, static::$arr_img_extensions)) {
			
			$resize = new ImageResize();
			$resize->resize($this->path_destination, $this->path_destination, $width, $height);
			
			$this->num_size = filesize($this->path_destination);
		}
	}
	
	public static function getExtension($value) {
		
		$str_extension = false;
		
		if (isPath($value)) { // Try to determine extension by mime type
			$str_extension = static::getFileExtension($value);
		}
		
		if (!$str_extension) {
			$str_extension = static::getFilenameExtension($value);
		}
		
		if (!$str_extension) {
			$str_extension = static::EXTENSION_UNKNOWN;
		}
		
		return $str_extension;
	}

	public static function getFileExtension($file) {
		
		$file_info = new finfo(FILEINFO_MIME_TYPE);
		$file_type = $file_info->file($file);
				
		$str_extension = (static::$arr_mime_type_ext[$file_type] ?? false);
			
		return $str_extension;
	}
	
	public static function getFilenameExtension($filename) {
		
		$str_extension = strtolower(substr(strrchr($filename, '.'), 1));
			
		if (preg_match('/[^a-z0-9]/', $str_extension)) {
			$str_extension = false;
		}
		
		return $str_extension;
	}
	
	public static function getExtensionMIMEType($what) {
		
		$type = array_search($what, static::$arr_mime_type_ext);
		
		return ($type ?: false);
	}
	
	public static function setUploadLimit($mode, $num_size = false) {
		
		if ($mode & static::STORE_FILE) {
			
			if ($mode & static::STORE_FILE_DOCUMENT) {
				static::$arr_store_limit[static::STORE_FILE_DOCUMENT] = $num_size;
			} else if ($mode & static::STORE_FILE_IMAGE) {
				static::$arr_store_limit[static::STORE_FILE_IMAGE] = $num_size;
			} else if ($mode & static::STORE_FILE_VIDEO) {
				static::$arr_store_limit[static::STORE_FILE_VIDEO] = $num_size;
			}

			if ($num_size > static::$arr_store_limit[static::STORE_FILE]) {
				static::$arr_store_limit[static::STORE_FILE] = $num_size;
			}
		}
	}
	
	public static function getSizeLimit($mode = false) {
		
		$num_size = false;
		
		if ($mode & static::STORE_FILE) {
			$num_size = (static::$arr_store_limit[$mode] ?? static::$arr_store_limit[static::STORE_FILE] ?? false);
		}
		
		return $num_size;
	}
	
	public static function getSizeLimitClient($mode = false) {
		
		$num_system = min(str2Bytes(ini_get('upload_max_filesize')), str2Bytes(ini_get('post_max_size')), str2Bytes(ini_get('memory_limit')));
		$num_size = static::getSizeLimit($mode);
		
		if ($num_size) {
			return min($num_size, $num_system);
		}
		
		return $num_system;
	}
	
	public static function cleanFilename($filename) {
		
		$arr_check = ['\\', '/', ' ', '\'', '"', '%20', '!', '@', '#', '$', '%', '^', '&', '*'];
		
		return str_replace($arr_check, '', $filename); 
	}

	public static function checkDuplicates($target, $count = 0) {		
		
		if (isPath($target)) {
			
			$count++;
			
			if (preg_match("/(\()([0-9]+)(\))/", pathinfo($target, PATHINFO_BASENAME))) {
				
				$new_filename = preg_replace("/(\()([0-9]+)(\))/", '('.$count.')', pathinfo($target, PATHINFO_BASENAME));
			} else {
				
				$str_extension = pathinfo($target, PATHINFO_EXTENSION);
				$new_filename = pathinfo($target, PATHINFO_FILENAME).'('.$count.')'.($str_extension ? '.'.$str_extension : '');
			}
			
			$new_target = pathinfo($target, PATHINFO_DIRNAME).'/'.$new_filename;
						
			return static::checkDuplicates($new_target, $count);
		} else {	
					
			return $target;
		}
	}
	
	public static function setFilePermission($path, $mode = false) {
		
		chmod($path, ($mode ?: Settings::get('chmod_file')));
	}
	
	public static function setFilePermissionRecursive($dir, $mode = false) {
		
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);

		foreach ($iterator as $item) {
			static::setFilePermission($item, $mode);
		}
	}
	
	public static function makeDirectoryTree($path, $mode = false) {
		
		if (isPath($path) || !trim($path)) {
			return true;
		}
		
		$path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
		$next_path = substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR));

		if (static::makeDirectoryTree($next_path, $mode)) {
			
			if (!isPath($path)) {
				
				$mode = ($mode ?: Settings::get('chmod_directory'));
				
				mkdir($path, $mode);
				static::setFilePermission($path, $mode);
				
				return true;
			}
		}

		return false;
	}
		
	public static function delDirectoryTree($dir) {
		
		$files = array_diff(scandir($dir), ['.','..']);
		
		foreach ($files as $file) {
			if (is_dir($dir.'/'.$file)) {
				static::delDirectoryTree($dir.'/'.$file);
			} else {
				unlink($dir.'/'.$file);
			}
		}
		
		return rmdir($dir);
	}
	
	public static function storeFile($path, $data = '', $path_destination = false) {
		
		$dir = dirname($path);
		
		static::makeDirectoryTree($dir);
		$file = fopen($path, 'c');
		static::setFilePermission($path);
		
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
	
	public static function renameFile($path, $path_destination) {
		
		if (trim($path != '')) {
						
			if (isPath($path) && $path_destination) {
				return rename($path, $path_destination);
			}
		}
				
		return false;
	}
		
	public static function deleteFile($path) {
		
		if (trim($path != '')) {
						
			if (isPath($path)) {			
				return unlink($path);
			}
		}
		
		return true;
	}
	
	public static function readFile($path, $filename, $do_delete = false) {
		
		ob_end_clean();
		
		Response::sendFileHeaders($path, $filename);
		
		readfile($path);
		
		if ($do_delete) {
			static::deleteFile($path);
		}
	}
}
