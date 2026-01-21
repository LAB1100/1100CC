<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class EnucleateMedia {
	
	const VIEW_HTML = 1;
	const VIEW_URL = 2;
	const VIEW_TYPE = 3;
	const VIEW_DATA_URL = 4;
	const VIEW_DATA_BASE64 = 5;
	
	protected $str_path_file = null;
	protected $str_path_enucleate = '';
	protected $str_path_system = '';
	
	protected $width = false; // Can also be set to HTML values like 'cm'
	protected $height = false;
	
	protected $is_external = false;
	protected $use_cache = true;
	protected $num_cache_width = 800;
	
	public function __construct($str_path_file, $str_path_system = '', $str_path_enucleate = null) {
	
		$this->str_path_file = $str_path_file;
		$this->is_external = (bool)FileGet::getProtocolExternal($this->str_path_file);
		
		if (!$this->is_external) {
			$this->str_path_file = FileStore::cleanPath($this->str_path_file);
		}
		
		$this->str_path_system = $str_path_system;
		
		if ($str_path_enucleate !== null) {
			$this->setPath($str_path_enucleate);
		}
	}

	public function setPath($str_path_enucleate, $do_prefix = false) {
		
		if ($this->is_external) {
			return false;
		}
		
		$this->str_path_enucleate = $str_path_enucleate.($do_prefix ? $this->str_path_enucleate : '');
	}
	
	public function isExternal() {
		
		return $this->is_external;
	}
	
	public function setSizing($width, $height, $use_cache = null) {
		
		$this->width = $width;
		$this->height = $height;
		
		if ($use_cache !== null) {
			
			$this->use_cache = (bool)$use_cache;
			
			if (is_numeric($use_cache)) {
				$this->num_cache_width = $use_cache;
			}
		}
	}
	
	public function getSizing($use_source = true) {
				
		if (!$use_source || $this->is_external) {
			
			return [
				'width' => $this->width,
				'height' => $this->height
			];
		}
		
		$arr_info = getimagesize($this->str_path_system.$this->str_path_file);
		
		if (!$arr_info) {
			return false;
		}
		
		return [
			'width' => $arr_info[0],
			'height' => $arr_info[1]
		];
	}
	
	public function enucleate($mode = self::VIEW_HTML, $arr_options = []) {

		$str_return = null;
		
		if ($mode == EnucleateMedia::VIEW_URL) {
			
			$str_return = $this->str_path_enucleate.$this->str_path_file;
		} else if ($mode == EnucleateMedia::VIEW_DATA_URL) {
			
			if (!$this->is_external) {
				$str_return = $this->str_path_system.$this->str_path_file;
			} else {
				$str_return = read($this->str_path_file);
			}
			
			$str_return = FileStore::getDataURL($str_return);
		} else if ($mode == EnucleateMedia::VIEW_DATA_BASE64) {
			
			if (!$this->is_external) {
				$str_return = read($this->str_path_system.$this->str_path_file);
			} else {
				$str_return = read($this->str_path_file);
			}
			
			$str_return = base64_encode($str_return);
		} else {
			
			$str_extension = '';
			
			if (!$this->is_external) {
				$str_extension = FileStore::getExtension($this->str_path_system.$this->str_path_file);
			} else {
				$str_extension = FileStore::getFilenameExtension($this->str_path_file);
			}

			switch ($str_extension) {
				case 'jpeg':
				case 'jpg':
				case 'png':
				case 'gif':
				case 'webp':
				case 'bmp':
				case 'svg':
				
					if ($mode == EnucleateMedia::VIEW_TYPE) {
						
						$str_return = 'image';
					} else {
						
						$str_html_width = ($this->width ? ' width="'.$this->width.'"' : '');
						$str_html_height = ($this->height ? ' height="'.$this->height.'"' : '');
						$str_html_class = '';
						
						if (keyIsUncontested('enlarge', $arr_options)) {
							$str_html_class = ' class="enlarge"';
						}
						
						if ($this->use_cache && $str_extension != 'svg') {
							$str_return = '<img'.$str_html_class.$str_html_width.$str_html_height.' src="'.SiteStartEnvironment::getCacheURL('img', [($this->width ?: $this->num_cache_width), ($this->height ?: false)], $this->str_path_enucleate.$this->str_path_file).'" />';
						} else {
							$str_return = '<img'.$str_html_class.$str_html_width.$str_html_height.' src="'.$this->str_path_enucleate.$this->str_path_file.'" />';
						}
					}
					break;
				case 'mp3':
				case 'oga':
				case 'weba':

					if ($mode == EnucleateMedia::VIEW_TYPE) {
						$str_return = 'audio';
					} else {
						
						$str_type = (FileStore::getExtensionMIMEType($str_extension) ?: 'audio/mpeg');
						
						$str_return = '<audio controls="1" height="100" width="200"><source src="'.$this->str_path_enucleate.$this->str_path_file.'" type="'.$str_type.'" /></audio>';
					}
					break;
				case 'mp4':
				case 'ogv':
				case 'webv':
				
					if ($mode == EnucleateMedia::VIEW_TYPE) {
						
						$str_return = 'video';
					} else {
						
						$str_html_options = '';
						if ($arr_options['autoplay']) {
							$str_html_options .= ' autoplay="1"';
						}
						if ($arr_options['loop']) {
							$str_html_options .= ' loop="1"';
						}
						if ($arr_options['muted'] || $arr_options['autoplay']) {
							$str_html_options .= ' muted="1"';
						}
						$str_html_sizing = ($this->height ? ' height="'.$this->height.'"' : '').($this->width ? ' width="'.$this->width.'"' : '');
						
						$str_type = (FileStore::getExtensionMIMEType($str_extension) ?: 'video/mp4');
						
						$str_return = '<video controls="1"'.$str_html_options.$str_html_sizing.'><source src="'.$this->str_path_enucleate.$this->str_path_file.'" type="'.$str_type.'" /></video>';
					}
					break;
				case 'pdf':
				
					if ($mode == EnucleateMedia::VIEW_TYPE) {
						$str_return = 'text';
					} else {
						$str_return = '<object type="application/pdf" width="'.($this->width ?: ($this->height ? ($this->height * 0.66) : '100%')).'" height="'.($this->height ?: ($this->width ? ($this->width * 1.33) : '100%')).'" data="'.$this->str_path_enucleate.$this->str_path_file.'"></object>';
					}
					break;
			}
			
			if (!$str_return) {
				
				$arr_info = pathinfo($this->str_path_file);
				$str_directory = $arr_info['dirname'];
				$str_file = $arr_info['file'];
				
				switch ($str_directory) {
					case 'http://youtu.be':
					case 'https://youtu.be':
					case 'youtube.com':
					case 'www.youtube.com':
					case 'http://www.youtube.com':
					case 'https://www.youtube.com':
						
						$str_return = '<iframe width="'.($this->width ?: ($this->height ? ($this->height * 0.66) : '100%')).'" height="'.($this->height ?: ($this->width ? ($this->width * 1.33) : '100%')).'" src="//www.youtube.com/embed/'.$str_file.'"></iframe>';
						break;
					case 'http://vimeo.com':
					case 'https://vimeo.com':
						
						$str_return ='<iframe src="//player.vimeo.com/video/'.$str_file.'" width="'.($this->width ?: ($this->height ? ($this->height * 0.66) : '100%')).'" height="'.($this->height ?: ($this->width ? ($this->width * 1.33) : '100%')).'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
						break;
				}
			}
			
			if (!$str_return) {
				$str_return = $this->str_path_file;
			}
		}
		
		return $str_return;
	}
}
