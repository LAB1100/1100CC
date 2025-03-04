<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class ImageSplitter {
	
	const IMAGE_SPLITTER_CENTER_NONE = 0; // Mode: none. Split directly without find the center
	const IMAGE_SPLITTER_CENTER_NORMAL = 1; // Mode: normal. Make a rectangular canvas which can be covered by integral number of the tiles, then put the source image in the center
	const IMAGE_SPLITTER_CENTER_SQUARE = 2; // Mode: square(default for the center_mode attribute). Make a square canvas which can be covered by integral number of the tiles, then put the source image in the center

	public $center_mode = self::IMAGE_SPLITTER_CENTER_NORMAL;
	public $force_tile_size = true;
	public $tile_width = 256;
	public $tile_height = 256;
	public $ratio = 1;
	public $output_type = IMAGETYPE_PNG;
	
	private $src;
	private $src_img;
	private $src_width;
	private $src_height;
	private $src_type;
	private $src_mime_type;
	private $width;
	private $height;
	
	private $real_tile_width;
	private $real_tile_height;
	
	private $offset_x = 0;
	private $offset_y = 0;
	
	private $count_x = 0;
	private $count_y = 0;
	
	private $start_x = 0;
	private $start_y = 0;
		
	public function __construct($src) {
		
		if (is_resource($src)) {
			
			$this->src_width = imagesx($src);
			$this->src_height = imagesy($src);
			if ($this->src_width && $this->src_height && !$this->src_img) {
				$this->src_img = $src;
			} else {
				return false;
			}
		} else if (is_file($src)) {
			
			$this->src = $src;
			
			$arr_info = getimagesize($src);
			if (!$arr_info) {
				return false;
			}
			
			list ($this->src_width, $this->src_height, $this->src_type, $tmp1, $tmp2, $this->src_mime_type) = array_values($arr_info);
						
			if (!ImageResize::isSupportedImageFormat($this->src_type)) {
				return false;
			}
		}
	}

	public function init() {
				
		$this->real_tile_width = round($this->tile_width / $this->ratio);
		$this->real_tile_height = round($this->tile_height / $this->ratio);
		
		switch($this->center_mode){
			case self::IMAGE_SPLITTER_CENTER_NONE:
				$this->count_x = ceil($this->src_width / $this->real_tile_width);
				$this->count_y = ceil($this->src_height / $this->real_tile_height);
				$this->width = round($this->ratio * $this->src_width);
				$this->height = round($this->ratio * $this->src_height);
				break;
			case self::IMAGE_SPLITTER_CENTER_NORMAL:
				$this->count_x = ceil($this->src_width / $this->real_tile_width / 2) * 2;
				$this->count_y = ceil($this->src_height / $this->real_tile_height / 2) * 2;
				$this->width = $this->count_x * $this->tile_width;
				$this->height = $this->count_y * $this->tile_height;
				$this->offset_x = round(($this->count_x * $this->real_tile_width - $this->src_width) / 2);
				$this->offset_y = round(($this->count_y * $this->real_tile_height - $this->src_height) / 2);
				break;
			case self::IMAGE_SPLITTER_CENTER_SQUARE:
				$this->count_x = ceil($this->src_width / $this->real_tile_width / 2) * 2;
				$this->count_y = ceil($this->src_height / $this->real_tile_height / 2) * 2;
				$this->width = $this->count_x * $this->tile_width;
				$this->height = $this->count_y * $this->tile_height;
				$this->offset_x = round(($this->count_x * $this->real_tile_width - $this->src_width) / 2);
				$this->offset_y = round(($this->count_y * $this->real_tile_height - $this->src_height) / 2);
				
				$diff = ($this->count_x - $this->count_y) / 2;
				
				if ($diff > 0) {
					$this->start_y = $diff;
				} else {
					$this->start_x = -$diff;
				}
				break;
			default:
				return false;
		}
		
		return true;
	}
		
	public function getTile($x, $y, $filename = null) {
		
		$x = (int)$x;
		$y = (int)$y;

		if ($x<$this->start_x || $y<$this->start_y || ($this->start_x + $this->count_x)<=$x || ($this->start_y + $this->count_y)<=$y) {
			return false;
		}
		if (!$this->src_img) {
			$this->src_img = imagecreatefromstring(file_get_contents($this->src));
		}

		if ($this->center_mode == static::IMAGE_SPLITTER_CENTER_NONE) {
			$src_x = $x * $this->real_tile_width;
			$src_y = $y * $this->real_tile_height;
		} else {
			$src_x = ($x - $this->start_x) * $this->real_tile_width - $this->offset_x;
			$src_y = ($y - $this->start_y) * $this->real_tile_height - $this->offset_y;
		}
		
		// Force tile size to fit contents or not
		if (!$this->force_tile_size && ($src_x < 0 || $src_x+$this->real_tile_width > $this->src_width)) {
			$cur_src_width = $this->real_tile_width - $this->offset_x;
			$cur_tile_width = ceil($this->tile_width*($cur_src_width/$this->real_tile_width));
			$cur_src_x = ($src_x < 0 ? 0 : $src_x);
		} else {
			$cur_src_width = $this->real_tile_width;
			$cur_tile_width = ceil($this->tile_width);
			$cur_src_x = $src_x;
		}
		if (!$this->force_tile_size && ($src_y < 0 || $src_y+$this->real_tile_height > $this->src_height)) {
			$cur_src_height = $this->real_tile_height - $this->offset_y;
			$cur_tile_height = ceil($this->tile_height*($cur_src_height/$this->real_tile_height));
			$cur_src_y = ($src_y < 0 ? 0 : $src_y);
		} else {
			$cur_src_height = $this->real_tile_height;
			$cur_tile_height = ceil($this->tile_height);
			$cur_src_y = $src_y;
		}
		
		$im = imagecreatetruecolor($cur_tile_width, $cur_tile_height);
		// Preserve transparancy (image)
		if ($this->output_type == IMAGETYPE_PNG || $this->output_type == IMAGETYPE_GIF) {
			$colour_trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
			
			//imagecolortransparent($im, $colour_trans);

			imagealphablending($im, false);
			imagesavealpha($im, true);
		}
		
		imagecopyresampled($im, $this->src_img, 0, 0, $cur_src_x, $cur_src_y, $cur_tile_width, $cur_tile_height, $cur_src_width, $cur_src_height);
		
		// Preserve transparancy (background)
		if ($this->output_type == IMAGETYPE_PNG || $this->output_type == IMAGETYPE_GIF) {
			imagefill($im, 0, 0, $colour_trans);
		}
		
		$this->output($im, $filename);
		
		imagedestroy($im);
		
	}
	
	public function getAllTiles($path, $prefix = 'tile', $suffix = '.png', $splitter = "-") {
		
		for ($i = 0; $i < $this->count_x; $i++) {
			for($j = 0; $j < $this->count_y; $j++){
				$x = $i + $this->start_x;
				$y = $j + $this->start_y;
				$this->getTile($x, $y, $path.$prefix.($x).$splitter.($y).$suffix);
			}
		}
	}
	
	public function free() {

		if($this->src_img) {
			imagedestroy($this->src_img);
		}
	}
	
	private function output($res, $dest = null) {
		switch($this->output_type) {
			case IMAGETYPE_GIF:
				return imagegif($res, $dest);
			case IMAGETYPE_JPEG:
				return imagejpeg($res, $dest);
			default:
				return imagepng($res, $dest);
		}
	}
}
