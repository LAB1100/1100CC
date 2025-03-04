<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FileStore {
	
	const EXTENSION_UNKNOWN = 'unknown';
	
	const STORE_FILE_DOCUMENT = 1;
	const STORE_FILE_IMAGE = 2;
	const STORE_FILE_VIDEO = 4;
	const STORE_FILE = (self::STORE_FILE_DOCUMENT | self::STORE_FILE_IMAGE | self::STORE_FILE_VIDEO);
	const STORE_TEXT = 8;
	
	protected static $arr_mime_types = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/gif' => 'gif',
		'image/webp' => 'webp',
		'image/bmp' => 'bmp',
		'image/x-icon' => 'icon',
		'image/svg+xml' => 'svg',
		'text/csv' => 'csv',
		'application/pdf' => 'pdf',
		'application/zip' => 'zip',
		'application/json' => 'json',
		'application/javascript' => 'js',
		'text/plain' => 'txt',
		'text/css' => 'css',
		'audio/mpeg' => 'mp3',
		'audio/ogg' => 'oga',
		'audio/webm' => 'weba',
		'video/mp4' => 'mp4',
		'video/ogv' => 'ogv',
		'video/webm' => 'webm'
	];
	protected static $arr_disallowed_extensions = ['php', 'ini', 'py', 'dll', 'exe', 'html', 'htm', 'sh'];
	protected static $arr_img_extensions = ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'webp'];
	protected static $arr_store_limit = [];

	protected $str_path_source = false;
	protected $str_source_name = false;

	protected $num_size = false;
	protected $str_extension = false;
	protected $str_path_destination = false;
	
	protected $from_cache = false;

	public function __construct($file, $destination, $num_size_limit = false, $do_image_only = false) {
	
		// $file = $_FILE or FileGet();
		// $destination = string OR array("directory" => string, "filename" => string, "path" => string, "overwrite" => boolean)

		try {
			if (is_object($file)) {
				$this->fromFileGet($file);
			} else if (is_array($file)) {
				$this->fromUpload($file);
			} else {
				error();
			}

			$this->num_size = filesize($this->str_path_source);
			
			if ($num_size_limit && $this->num_size > $num_size_limit) {
				
				Labels::setVariable('max_size', bytes2String($num_size_limit));
				error(getLabel('msg_file_too_large'));
			}
			
			if (isset($destination['path'])) {
				$destination['directory'] = dirname($destination['path']);
				$destination['filename'] = basename($destination['path']);
			}
			
			$str_directory = (is_array($destination) ? $destination['directory'] : $destination);
			if (!strEndsWith($str_directory, '/')) {
				$str_directory .= '/';
			}
			static::makeDirectoryTree($str_directory);
			
			$str_filename = (is_array($destination) && $destination['filename'] ? $destination['filename'] : basename($this->str_source_name));
			
			$str_extension = static::getExtension($str_filename);
			if ($str_extension == static::EXTENSION_UNKNOWN) {
				$str_extension = static::getExtension($this->str_path_source);
			}
			
			$this->str_extension = $str_extension;

			if (strpos($str_filename, '.')) {
				$str_filename = substr($str_filename, 0, strpos($str_filename, '.'));
			}
			$str_filename = $str_filename.'.'.$this->str_extension;
			
			$do_overwrite = (is_array($destination) && $destination['overwrite']);	

			if ($do_image_only && !in_array($this->str_extension, static::$arr_img_extensions)) {
				
				Labels::setVariable('type', 'image');
				error(getLabel('msg_invalid_file_type_specific'));
			}
			if (!$do_image_only && (!$this->str_extension || in_array($this->str_extension, static::$arr_disallowed_extensions))) {
				
				error(getLabel('msg_invalid_file_type'));
			}
			
			$str_filename = static::cleanFilename($str_filename);
			
			if ($do_overwrite) {
				
				if (isPath($str_directory.$str_filename)) {
					static::deleteFile($str_directory.$str_filename);
				}
				
				$this->str_path_destination = $str_directory.$str_filename;
			} else {
				
				$this->str_path_destination = static::checkDuplicates($str_directory.$str_filename);
			}
			
			if ($this->from_cache) {
				
				rename($this->str_path_source, $this->str_path_destination);
			} else {
				
				move_uploaded_file($this->str_path_source, $this->str_path_destination);
			}
			
			static::setFilePermission($this->str_path_destination);
						
		} catch (Exception $e) {
			
			if ($this->from_cache) {
				$file->abort();
			}
			
			$e->setMessage(getLabel('msg_upload_failed').' '.$e->getMessage());
			
			throw($e);
		}
	}
	
	protected function fromFileGet($file) {
		
		$this->from_cache = true;
		
		$this->str_path_source = $file->getPath();
		$this->str_source_name = $file->getSource();
		
		if (!isPath($this->str_path_source)) {
			
			Labels::setVariable('max_size', bytes2String(static::getSizeLimitClient()));
			error(getLabel('msg_no_file_or_too_large'));
		}
	}
	
	protected function fromUpload($file) {
		
		$this->str_path_source = $file['tmp_name'];
		$this->str_source_name = $file['name'];
		
		if (!is_uploaded_file($this->str_path_source)) {
			
			Labels::setVariable('max_size', bytes2String(static::getSizeLimitClient()));
			error(getLabel('msg_no_file_or_too_large'));
		}
	}
		
	public function rename($str_path) {
		
		$is_renamed = static::renameFile($this->str_path_destination, $str_path);
		
		if ($is_renamed) {
			$this->str_path_destination = $str_path;
		}
		
		return $is_renamed;
	}
	
	public function get() {
		
		return $this->str_path_destination;
	}
	
	public function getDetails() {
	
		$arr = [];
	
		$arr['size'] = $this->num_size;
		$arr['extension']  = $this->str_extension;
		$arr['name'] = pathinfo($this->str_path_destination, PATHINFO_BASENAME);
		$arr['directory'] = pathinfo($this->str_path_destination, PATHINFO_DIRNAME);
		if (!strEndsWith($arr['directory'], '/')) {
			$arr['directory'] .= '/';
		}
		
		$file_info = new finfo(FILEINFO_MIME_TYPE);
		$file_type = $file_info->file($this->str_path_destination);
		$arr['type']  = $file_type;
		
		return $arr;
	}
	
	public function imageResize($width, $height) {
	
		if (in_array($this->str_extension, static::$arr_img_extensions)) {
			
			$resize = new ImageResize();
			$resize->resize($this->str_path_destination, $this->str_path_destination, $width, $height);
			
			$this->num_size = filesize($this->str_path_destination);
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
		
		$str_extension = (static::$arr_mime_types[$file_type] ?? false);
			
		return $str_extension;
	}
	
	public static function getFilenameExtension($str_filename) {
		
		$str_extension = strtolower(substr(strrchr($str_filename, '.'), 1));
			
		if (preg_match('/[^a-z0-9]/', $str_extension)) {
			$str_extension = false;
		}
		
		return $str_extension;
	}
	
	public static function getMIMETypeExtension($mime) {
		
		return (static::$arr_mime_types[$mime] ?? static::EXTENSION_UNKNOWN);
	}
	
	public static function getExtensionMIMEType($what) {
		
		$type = array_search($what, static::$arr_mime_types);
		
		return ($type ?: false);
	}
	
	public static function setSizeLimit($mode, $num_size = false) {
		
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
	
	public static function getSizeLimit($mode = 0) {
		
		$num_size = false;
		
		if ($mode & static::STORE_FILE) {
			$num_size = (static::$arr_store_limit[$mode] ?? static::$arr_store_limit[static::STORE_FILE] ?? false);
		}
		
		return $num_size;
	}
	
	public static function getSizeLimitClient($mode = 0) {
		
		$num_system = min(str2Bytes(ini_get('upload_max_filesize')), str2Bytes(ini_get('post_max_size')));
		$num_size = static::getSizeLimit($mode);
		
		if ($num_size) {
			return min($num_size, $num_system);
		}
		
		return $num_system;
	}
	
	public static function cleanFilename($str_filename) {
		
		$arr_check = ['\\', '/', ' ', '\'', '"', '%20', '!', '@', '#', '$', '%', '^', '&', '*'];
		
		return str_replace($arr_check, '', $str_filename); 
	}

	public static function checkDuplicates($str_target, $count = 0) {		
		
		if (isPath($str_target)) {
			
			$count++;
			
			if (preg_match("/(\()([0-9]+)(\))/", pathinfo($str_target, PATHINFO_BASENAME))) {
				
				$str_filename_new = preg_replace("/(\()([0-9]+)(\))/", '('.$count.')', pathinfo($str_target, PATHINFO_BASENAME));
			} else {
				
				$str_extension = pathinfo($str_target, PATHINFO_EXTENSION);
				$str_filename_new = pathinfo($str_target, PATHINFO_FILENAME).'('.$count.')'.($str_extension ? '.'.$str_extension : '');
			}
			
			$str_target_new = pathinfo($str_target, PATHINFO_DIRNAME).'/'.$str_filename_new;
						
			return static::checkDuplicates($str_target_new, $count);
		} else {	
					
			return $str_target;
		}
	}
	
	public static function setFilePermission($str_path, $mode = false) {
		
		try {
			chmod($str_path, ($mode ?: Settings::get('chmod_file')));
		} catch (Exception $e) { }
	}
	
	public static function setFilePermissionRecursive($str_directory, $mode = false) {
		
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($str_directory), RecursiveIteratorIterator::SELF_FIRST);

		foreach ($iterator as $item) {
			static::setFilePermission($item, $mode);
		}
	}
	
	public static function makeDirectoryTree($str_path, $mode = false) {
		
		if (isPath($str_path) || !trim($str_path)) {
			return true;
		}
		
		$str_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $str_path);
		$str_path_next = substr($str_path, 0, strrpos($str_path, DIRECTORY_SEPARATOR));

		if (static::makeDirectoryTree($str_path_next, $mode)) {
			
			if (!isPath($str_path)) {
				
				$mode = ($mode ?: Settings::get('chmod_directory'));
				
				mkdir($str_path, $mode);
				static::setFilePermission($str_path, $mode);
				
				return true;
			}
		}

		return false;
	}
	
	public static function deleteDirectoryTree($str_directory) {
		
		if (!isPath($str_directory) || !trim($str_directory)) {
			return false;
		}
		
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($str_directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
		
		foreach ($iterator as $file) {
			
			if ($file->isDir()) {
				rmdir($file);
			} else {
				unlink($file);
			}
		}
		
		if (!strEndsWith($str_directory, '/')) { // Also remove self
			rmdir($str_directory);
		}
		
		return true;
	}
	
	public static function storeFile($str_path, $data = '', $str_path_destination = false) {
		
		$str_directory = dirname($str_path);
		
		static::makeDirectoryTree($str_directory);
		$file = fopen($str_path, 'c');
		static::setFilePermission($str_path);
		
		if (flock($file, LOCK_EX)) {
			
			ftruncate($file, 0);
			fwrite($file, $data);
			flock($file, LOCK_UN);
		}
		
		fclose($file);
		
		if ($str_path_destination) {
			rename($str_path, $str_path_destination);
		}
	}
	
	public static function renameFile($str_path, $str_path_destination) {
		
		if (trim($str_path != '') && ($str_path != $str_path_destination)) {
						
			if (!isPath($str_path) || !$str_path_destination) {
				return false;
			}
			
			return rename($str_path, $str_path_destination);
		}
				
		return false;
	}
		
	public static function deleteFile($str_path) {
		
		if (trim($str_path != '')) {
						
			if (!isPath($str_path)) {			
				return true;
			}
			
			return unlink($str_path);
		}
		
		return true;
	}
	
	public static function readFile($file, $str_filename, $do_delete = false) {
		
		ob_end_clean();
		
		Response::sendFileHeaders($file, $str_filename);
		
		read($file, true);
		
		if ($do_delete) {
			static::deleteFile($file);
		}
	}
	
	public static function getDataURL($file, $str_mime = false) {
		
		$is_file = (isResource($file) || isPath($file));
		
		if (!$str_mime) {
			
			if ($is_file) {
				$str_mime = mime_content_type($file);
			} else {
				$str_mime = 'application/octet-stream';
			}
		}
		
		$str_encode = ($is_file ? read($file) : $file);
		$str_encode = base64_encode($str_encode);
		
		return 'data:'.$str_mime.';base64,'.$str_encode;
	}
}
