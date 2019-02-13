<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */
 
class ImageResize {
	
	private $src;
	private $src_width;
	private $src_height;
	private $src_type;
	private $src_mime_type;
	private $src_img;
	
	private $dst;
	private $img;
	private $type;
	private $width;
	private $height;

	public function resize($src, $dst, $width, $height, $crop = 0) {
		
		// $src = "path", $dst = "null, path, extension"
					
		$this->src = $src;
		
		$arr_info = getimagesize($this->src);
		if (!$arr_info) {
			return false;
		}
		
		list ($this->src_width, $this->src_height, $this->src_type, $tmp1, $tmp2, $this->src_mime_type) = array_values($arr_info);
		
		if (!self::isSupportedImageFormat($this->src_type)) {
			return false;
		}
		
		if ($this->src_type == IMAGETYPE_GIF) {
			
			if (self::isAnimation($this->src)) {
				return false;
			}
		}
		
		if ($dst) {
			
			$type = FileStore::getExtension($dst); // Extension
			
			if ($type == FileStore::EXTENSION_UNKNOWN) { // Or only string i.e. png, jpeg
				
				$type = $dst;
				$dst = null; // Output to memory
			}
		} else {
			
			$type = FileStore::getExtension($src);
		}
		
		$this->type = ($type == 'jpg' ? 'jpeg' : $type);
		$this->dst = $dst;
					
		$this->src_img = imagecreatefromstring(file_get_contents($this->src));
		
		// Resize
		$this->width = ($width ?: $this->src_width);
		$this->height = ($height ?: $this->src_height);
		if ($this->src_width <= $this->width && $this->src_height <= $this->height) { // Abort if source's dimensions are bigger
			return false;
		}
		if ($crop) {
			$ratio = max($this->width/$this->src_width, $this->height/$this->src_height);
			$this->src_height = $this->height / $ratio;
			$x = ($this->src_width - $this->width / $ratio) / 2;
			$this->src_width = $this->width / $ratio;
		} else {
			$ratio = min($this->width/$this->src_width, $this->height/$this->src_height);
			$this->width = $this->src_width * $ratio;
			$this->height = $this->src_height * $ratio;
			$x = 0;
		}

		$this->img = imagecreatetruecolor($this->width, $this->height);

		// Preserve transparency
		if ($this->type == 'gif' || $this->type == 'png') {
			imagecolortransparent($this->img, imagecolorallocatealpha($this->img, 0, 0, 0, 127));
			imagealphablending($this->img, false);
			imagesavealpha($this->img, true);
		}

		imagecopyresampled($this->img, $this->src_img, 0, 0, $x, 0, $this->width, $this->height, $this->src_width, $this->src_height);
		
		switch ($this->type) {
			case 'bmp': imagewbmp($this->img, $this->dst); break;
			case 'gif': imagegif($this->img, $this->dst); break;
			case 'jpeg': imagejpeg($this->img, $this->dst); break;
			case 'png': imagepng($this->img, $this->dst); break;
		}
		
		imagedestroy($this->img);
		
		return true;
	}
	
	public static function isSupportedImageFormat($type) {
		
		$supported_format = [];
		$types = imagetypes();
		if ($types & IMG_GIF) $supported_format[IMAGETYPE_GIF] = IMAGETYPE_GIF;
		if ($types & IMG_JPG) $supported_format[IMAGETYPE_JPEG] = IMAGETYPE_JPEG;
		if ($types & IMG_PNG) $supported_format[IMAGETYPE_PNG] = IMAGETYPE_PNG;
		if ($types & IMG_WBMP) $supported_format[IMAGETYPE_WBMP] = IMAGETYPE_WBMP;
		if ($types & IMG_XPM) $supported_format[IMAGETYPE_XBM] = IMAGETYPE_XBM;
		
		return $supported_format[$type];
	}
	
	public static function isAnimation($file) {
		
		return (bool)preg_match('/\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)/s', file_get_contents($file), $m);
	}
}
