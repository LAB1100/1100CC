<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FileCache {
		
	private $type = 'img';
	private $url = [];
	private $arr_options = [];
	private $target = false;

	private $external_protocol = false;
	private $external_file = false;
	private $str_options = false;
	private $str_url = false;
	
	private $check = true;
	private $create_archive = false;
	private $archive_folder = '';
	private $filename = false;
	private $path_source = false;
	private $path_destination = false;
	private $data = false;
	private $is_new = false;
	
	public function __construct($type, $arr_options, $url, $target = false) {
	
		// $arr_options array(# => $type dependent options, "error_source" => local path to source on error)

		$this->type = $type;
		$this->arr_options = (array)(!is_array($arr_options) && $arr_options ? json_decode(base64_decode($arr_options), true) : $arr_options);
		$this->url = (base64_decode($url, true) ? base64_decode($url) : $url);
		$this->target = $target;
		
		$this->external_protocol = FileGet::getProtocolExternal($this->url);
		
		$this->create_archive = getLabel(($this->external_protocol ? 'caching_external' : 'caching'), 'D', true);
		
		$this->str_options = base64_encode(value2JSON($this->arr_options));
		$this->str_url = base64_encode($this->url);
		
		$this->archive_folder = ($this->target == DIR_HOME ? DIR_ROOT_CACHE.DIR_HOME : DIR_SITE_CACHE).$this->type.'/';
		$this->filename = value2HashExchange($this->str_options.'_'.$this->str_url);
		$this->path_destination = $this->archive_folder.$this->filename;
	}
	
	public function generate($check = true) {
		
		$this->check = $check;
		
		if ($this->check && $this->create_archive && !isPath($this->path_destination)) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('SITE_CACHE_FILES')."
				(filename)
					VALUES
				('".DBFunctions::strEscape($this->filename)."')
				".DBFunctions::onConflict('filename', ['filename'])."
			");
		}
	}
		
	public function cache() {
				
		if (!$this->create_archive || ($this->create_archive && !isPath($this->path_destination))) {
			
			$this->is_new = true;
			
			if ($this->create_archive && $this->check) {
				
				$res = DB::query("SELECT *
					FROM ".DB::getTable('SITE_CACHE_FILES')."
					WHERE filename = '".DBFunctions::strEscape($this->filename)."'
				");
				
				if (!$res->getRowCount()) {
					$this->abort();
				}
			}
			
			if ($this->external_protocol) {
				
				$this->external_file = new FileGet($this->url);
				$this->external_file->load();
				$this->path_source = $this->external_file->getPath();
			} else {
				
				$this->path_source = ($this->target == DIR_HOME ? DIR_ROOT_STORAGE.DIR_HOME : DIR_SITE_STORAGE).$this->url;
				if (!isPath($this->path_source)) {
					if ($this->target == DIR_HOME) {
						$this->path_source = (isPath(DIR_ROOT_SITE.$this->url) ? DIR_ROOT_SITE : DIR_ROOT_CORE).$this->url;
					} else {
						$this->path_source = (isPath(DIR_SITE.$this->url) ? DIR_SITE : DIR_CORE).$this->url;
					}
				}
			}
			
			if (!isPath($this->path_source)) {
				
				if (!empty($this->arr_options['error_source'])) { // Local path
					
					$error_source = ltrim($this->arr_options['error_source'], '/');
					if ($this->target == DIR_HOME) {
						$this->path_source = (isPath(DIR_ROOT_SITE.$error_source) ? DIR_ROOT_SITE : DIR_ROOT_CORE).$error_source;
					} else {
						$this->path_source = (isPath(DIR_SITE.$error_source) ? DIR_SITE : DIR_CORE).$error_source;
					}
					$this->external_protocol = false; // Do not remove the new path later on
				} else {
					
					$this->abort();
				}
			}

			if ($this->type == 'img') {
				$this->cacheImage();
			} else if ($this->type == 'json') {
				$this->cacheJSON();
			} else {
				$this->abort();
			}
			
			if ($this->external_protocol) {
				$this->external_file->abort();
			}
			
			if ($this->create_archive) {
				$this->write();
			}
		}
	}
	
	private function abort() {
		
		if ($this->external_file) {
			$this->external_file->abort();
		}
		if (SiteStartVars::getRequestState() == SiteStartVars::REQUEST_INDEX) {
			pages::noPage();
		} else {
			error(getLabel('msg_not_found'));
		}
	}
	
	private function cacheImage() {
	
		ob_start();
		
		$resize = new ImageResize();
		$resize = $resize->resize($this->path_source, 'png', $this->arr_options[0], $this->arr_options[1]);
		
		if (!$resize) {
			echo file_get_contents($this->path_source);
		}
		
		$this->data = ob_get_clean();
		
		if (!empty($this->arr_options[2])) {
			
			$temp_path = tempnam(Settings::get('path_temporary'), '1100CC');
			$file = fopen($temp_path, 'w');
			fwrite($file, $this->data);
			fclose($file);
			
			$arr_padding = [];
			$no_plain_image = false;
			
			if ($this->arr_options[2]['shadow']) {
				
				if ($this->arr_options[2]['shadow']) {
				
					$arr_shadow = [
						'color' => ($this->arr_options[2]['shadow']['color'] ?: 'black'),
						'opacity' => (isset($this->arr_options[2]['shadow']['opacity']) ? (int)$this->arr_options[2]['shadow']['opacity'] : 80),
						'spread' => (isset($this->arr_options[2]['shadow']['spread']) ? (int)$this->arr_options[2]['shadow']['spread'] : 8),
						'x' => (isset($this->arr_options[2]['shadow']['x']) ? (int)$this->arr_options[2]['shadow']['x'] : 5),
						'y' => (isset($this->arr_options[2]['shadow']['y']) ? (int)$this->arr_options[2]['shadow']['y'] : 5)
					];
					
					$arr_padding['shadow'] = max([2*$arr_shadow['spread']+$arr_shadow['x'], 2*$arr_shadow['spread']+$arr_shadow['y']]);
				}
				if ($this->arr_options[2]['blur']) {
					
					$arr_blur = ['amount' => (isset($this->arr_options[2]['blur']['amount']) ? (int)$this->arr_options[2]['blur']['amount'] : 3)];
					
					$arr_padding['blur'] = ($this->arr_options[2]['blur']['amount'] ?? 3)*2; // Border [amount] to make sure edges are blurred as well
					$no_plain_image = true;
				}
				
				$padding = ($arr_padding ? max($arr_padding) : 0);

				$cmd = 'convert '.$temp_path.' -write mpr:source -delete 0'
				.' ('
					.(!$no_plain_image ? ' ( mpr:source -bordercolor none -border '.$padding.' )' : '')
					
					.($this->arr_options[2]['blur'] ? ' ( mpr:source '
						.' -bordercolor none -border '.$arr_padding['blur']
						.' -channel RGBA -blur 0x'.$arr_blur['amount']
					.' -bordercolor none -border '.($padding-$arr_padding['blur']).' )' : '') // Make sure it's centered
				
					.($this->arr_options[2]['shadow'] ? ' ('
						.' ( mpr:source'
							.' -bordercolor none -border '.$arr_padding['shadow'].'x'.$arr_padding['shadow']
							.' -channel A -evaluate set 0'
						.' )'
						.' ( mpr:source'
							.' -bordercolor none -border '.$arr_padding['shadow'].'x'.$arr_padding['shadow']
							.' -background '.escapeshellarg($arr_shadow['color'])
							.' -shadow '.$arr_shadow['opacity'].'x'.$arr_shadow['spread'].'+'.$arr_shadow['x'].'+'.$arr_shadow['y']
						.' )'
					.' -background none -compose dst-over -flatten' // Pad out the original image with some extra space (border) for the shadow, and then underlay the shadow image directly
					.' -bordercolor none -border '.($padding-$arr_padding['shadow']).' )' : '') // Make sure it's centered
					
				.' -background none -layers merge ) -append'
				.' +repage PNG:'.$temp_path.'';
				
				exec(escapeshellcmd($cmd));
			}
			
			$this->data = file_get_contents($temp_path);
			
			unlink($temp_path);
		}
	}
	
	private function cacheJSON() {
	
		$this->data = file_get_contents($this->path_source);
	}
		
	private function write() {

		FileStore::storeFile($this->path_destination, $this->data);
	}
		
	public function read() {
		
		$ie_tag = 0;
		if ($this->create_archive && !$this->is_new) {
			
			$ie_tag = filemtime($this->path_destination);
			$cur_ie_tag = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') : false);
			
			if ($cur_ie_tag == $ie_tag) {
				header($_SERVER['SERVER_PROTOCOL'].' 304 Not Modified');
				exit;
			}
		}

		Response::sendFileHeaders(($this->is_new ? $this->data : $this->path_destination), false, [
			'ETag: "'.$ie_tag.'"',
			'Cache-Control: max-age='.(60*60*24),
			'1100CC-Cached: '.(int)$this->create_archive
		]);
		
		if ($this->is_new) {
			echo $this->data;
		} else {
			readfile($this->path_destination);
		}
	}
	
	public function getStringOptions() {
		
		return $this->str_options;
	}
	
	public function getStringUrl() {
		
		return $this->str_url;
	}
	
	public function getPath() {
		
		if (!$this->create_archive) {
			return false;
		}
		
		return $this->path_destination;
	}
	
	public function getData() {
		
		if ($this->is_new) {
			return $this->data;
		} else {
			return file_get_contents($this->path_destination);
		}
	}
}
