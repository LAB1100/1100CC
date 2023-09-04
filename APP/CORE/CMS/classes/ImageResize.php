<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */
 
class ImageResize {
	
	const IMAGE_QUALITY_LOW = 1;
	const IMAGE_QUALITY_NORMAL = 2;
	const IMAGE_QUALITY_BEST = 3;
	
	protected $str_path_source = '';
	protected $num_source_width;
	protected $num_source_height;
	protected $source_type;
	protected $source_mime_type;
	protected $source_img;
	
	protected $str_path_destination = null;
	protected $str_type;
	protected $num_width;
	protected $num_height;
	protected $img;
	protected $mode_quality = self::IMAGE_QUALITY_NORMAL;

	public function resize($str_path_source, $str_path_destination, $num_width, $num_height, $do_crop = false) {
		
		// $str_path_source = "path", $str_path_destination = "null, path, extension"
					
		$this->str_path_source = $str_path_source;
		
		$arr_info = getimagesize($this->str_path_source);
		if (!$arr_info) {
			return false;
		}
		
		list ($this->num_source_width, $this->num_source_height, $this->source_type, $tmp1, $tmp2, $this->source_mime_type) = array_values($arr_info);
		
		if (!self::isSupportedImageFormat($this->source_type)) {
			return false;
		}
		
		if ($this->source_type == IMAGETYPE_GIF) {
			
			if (self::isAnimation($this->str_path_source)) {
				return false;
			}
		}
		
		if ($str_path_destination) {
			
			$str_type = FileStore::getExtension($str_path_destination); // Extension
			
			if ($str_type == FileStore::EXTENSION_UNKNOWN) { // Or only string i.e. png, jpeg
				
				$str_type = $str_path_destination;
				$str_path_destination = null; // Output to memory
			}
		} else {
			
			$str_type = FileStore::getExtension($this->str_path_source);
		}
		
		$this->str_type = ($str_type == 'jpg' ? 'jpeg' : $str_type);
		$this->str_path_destination = $str_path_destination;
					
		$this->source_img = imagecreatefromstring(file_get_contents($this->str_path_source));
		
		// Resize
		$this->num_width = ($num_width ?: $this->num_source_width);
		$this->num_height = ($num_height ?: $this->num_source_height);
		
		if ($this->num_source_width <= $this->num_width && $this->num_source_height <= $this->num_height) { // Abort if source's dimensions are bigger
			return false;
		}
		
		if ($do_crop) {
			
			$ratio = max($this->num_width / $this->num_source_width, $this->num_height / $this->num_source_height);
			$this->num_source_height = (int)($this->num_height / $ratio);
			$num_x = ($this->num_source_width - $this->num_width / $ratio) / 2;
			$this->num_source_width = (int)($this->num_width / $ratio);
		} else {
			
			$ratio = min($this->num_width / $this->num_source_width, $this->num_height / $this->num_source_height);
			$this->num_width = (int)($this->num_source_width * $ratio);
			$this->num_height = (int)($this->num_source_height * $ratio);
			$num_x = 0;
		}

		$this->img = imagecreatetruecolor($this->num_width, $this->num_height);

		// Preserve transparency
		if ($this->str_type == 'gif' || $this->str_type == 'png' || $this->str_type == 'webp') {
			
			imagecolortransparent($this->img, imagecolorallocatealpha($this->img, 0, 0, 0, 127));
			imagealphablending($this->img, false);
			imagesavealpha($this->img, true);
		}

		imagecopyresampled($this->img, $this->source_img, 0, 0, $num_x, 0, $this->num_width, $this->num_height, $this->num_source_width, $this->num_source_height);
		
		switch ($this->str_type) {
			case 'png':
				imagepng($this->img, $this->str_path_destination, $this->getOutputQuality(true));
				break;
			case 'webp':
				imagewebp($this->img, $this->str_path_destination, $this->getOutputQuality());
				break;
			case 'jpeg':
				imagejpeg($this->img, $this->str_path_destination, $this->getOutputQuality());
				break;
			case 'bmp':
				imagewbmp($this->img, $this->str_path_destination);
				break;
			case 'gif':
				imagegif($this->img, $this->str_path_destination);
				break;
		}
		
		imagedestroy($this->img);
		
		return true;
	}
	
	public function setOutput($mode_quality = self::IMAGE_QUALITY_NORMAL) {
		
		$this->mode_quality = $mode_quality;
	}
	
	protected function getOutputQuality($is_compression = false) {
		
		$num_quality = -1; // Use GD default
		
		if ($this->mode_quality == static::IMAGE_QUALITY_BEST) {
			$num_quality = ($is_compression ? 9 : 100);
		} else if ($this->mode_quality == static::IMAGE_QUALITY_LOW) {
			$num_quality = ($is_compression ? 1 : 25);
		}
		
		return $num_quality;
	}
	
	public static function isSupportedImageFormat($type) {
		
		$types = imagetypes();
		
		$arr_supported = [
			IMAGETYPE_GIF => ($types & IMG_GIF),
			IMAGETYPE_JPEG => ($types & IMG_JPG),
			IMAGETYPE_PNG => ($types & IMG_PNG),
			IMAGETYPE_WBMP => ($types & IMG_WBMP),
			IMAGETYPE_XBM => ($types & IMG_XPM),
			IMAGETYPE_WEBP => ($types & IMG_WEBP)
		];

		return $arr_supported[$type];
	}
	
	public static function isAnimation($file) {
		
		return (bool)preg_match('/\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)/s', file_get_contents($file), $m);
	}
}
