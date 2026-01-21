<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FileCache {
		
	private $type = 'img';
	private $str_path_url = null;
	private $arr_options = [];
	private $target = false;

	private $is_external = false;
	private $external_file = false;
	private $str_hash_options = null;
	private $str_hash_url = null;
	
	private $do_check = true;
	private $do_create_archive = false;
	private $str_hash_filename = null;
	private $str_path_source = null;
	private $str_path_destination = null;
	private $data = false;
	private $is_new = false;
	
	public function __construct($type, $arr_options, $str_url, $target = false) {
	
		// $arr_options array(# => $type dependent options, "error_source" => local path to source on error)

		$this->type = $type;
		$this->arr_options = (array)(!is_array($arr_options) && $arr_options ? json_decode(base64_decode($arr_options), true) : $arr_options);
		$this->str_path_url = (base64_decode($str_url, true) ? base64_decode($str_url) : $str_url);
		$this->target = $target;
		
		$this->str_hash_options = base64_encode(value2JSON($this->arr_options));
		$this->str_hash_url = base64_encode($this->str_path_url);
		
		$this->is_external = (bool)FileGet::getProtocolExternal($this->str_path_url);
		
		if ($this->is_external) {
			
			$this->do_create_archive = getLabel('caching_external', 'D', true);
		} else {
			
			$this->str_path_url = ltrim(FileStore::cleanPath($this->str_path_url), '/');
			$this->do_create_archive = getLabel('caching', 'D', true);
		}
		
		$str_path_archive = ($this->target == DIR_HOME ? DIR_ROOT_CACHE.DIR_HOME : DIR_SITE_CACHE).$this->type.'/';
		$this->str_hash_filename = value2HashExchange($this->str_hash_options.'_'.$this->str_hash_url);
		$this->str_path_destination = $str_path_archive.$this->str_hash_filename;
	}
	
	public function generate($do_check = true) {
		
		$this->do_check = $do_check;
		
		if ($this->do_check && $this->do_create_archive && !isPath($this->str_path_destination)) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('SITE_CACHE_FILES')."
				(filename)
					VALUES
				('".DBFunctions::strEscape($this->str_hash_filename)."')
				".DBFunctions::onConflict('filename', ['filename'])."
			");
		}
	}
		
	public function cache() {
		
		if (!$this->do_create_archive || ($this->do_create_archive && !isPath($this->str_path_destination))) {
			
			$this->is_new = true;
			
			if ($this->do_create_archive && $this->do_check) {
				
				$res = DB::query("SELECT *
					FROM ".DB::getTable('SITE_CACHE_FILES')."
					WHERE filename = '".DBFunctions::strEscape($this->str_hash_filename)."'
				");
				
				if (!$res->getRowCount()) {
					$this->abort();
				}
			}
			
			if ($this->is_external) {
				
				$arr_settings = [];
				
				if ($this->do_create_archive) {
					$arr_settings['redirect'] = 4; // Allow for more redirects when caching
				}

				$this->external_file = new FileGet($this->str_path_url, $arr_settings);
				$this->external_file->load();
				
				$this->str_path_source = $this->external_file->getPath();
			} else {

				$str_path_use = $this->str_path_url;
				$this->str_path_source = ($this->target == DIR_HOME ? DIR_ROOT_STORAGE.DIR_HOME : DIR_SITE_STORAGE).$str_path_use;
				
				if (!isPath($this->str_path_source)) {
					
					if ($this->target == DIR_HOME) {
						$this->str_path_source = (isPath(DIR_ROOT_SITE.$str_path_use) ? DIR_ROOT_SITE : DIR_ROOT_CORE).$str_path_use;
					} else {
						$this->str_path_source = (isPath(DIR_SITE.$str_path_use) ? DIR_SITE : DIR_CORE).$str_path_use;
					}
				}
			}
			
			if (!isPath($this->str_path_source)) {
				
				if (!empty($this->arr_options['error_source'])) { // Local path

					$str_path_use = ltrim(FileStore::cleanPath($this->arr_options['error_source']), '/');
										
					if ($this->target == DIR_HOME) {
						$this->str_path_source = (isPath(DIR_ROOT_SITE.$str_path_use) ? DIR_ROOT_SITE : DIR_ROOT_CORE).$str_path_use;
					} else {
						$this->str_path_source = (isPath(DIR_SITE.$str_path_use) ? DIR_SITE : DIR_CORE).$str_path_use;
					}
					
					if (!isPath($this->str_path_source)) {
						$this->abort();
					}
					
					$this->is_external = false; // Do not remove the new path later on
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
			
			if ($this->is_external) {
				$this->external_file->abort();
			}
			
			if ($this->do_create_archive) {
				$this->write();
			}
		}
	}
	
	private function abort() {
		
		if ($this->external_file) {
			$this->external_file->abort();
		}
		if (SiteStartEnvironment::getRequestState() == SiteStartEnvironment::REQUEST_INDEX) {
			pages::noPage();
		} else {
			error(getLabel('msg_not_found'), TROUBLE_ERROR, LOG_CLIENT);
		}
	}
	
	private function cacheImage() {
		
		$arr_settings = Settings::get('cache_image');
		$str_file_type = ($arr_settings['type'] ?? 'png');
		$mode_quality = ($arr_settings['quality'] ?? ImageResize::IMAGE_QUALITY_NORMAL);

		ob_start();
		
		$resize = new ImageResize();
		$resize->setOutput($mode_quality);
		$resize = $resize->resize($this->str_path_source, $str_file_type, $this->arr_options[0], $this->arr_options[1]);
		
		if (!$resize) {
			echo file_get_contents($this->str_path_source);
		}
		
		$this->data = ob_get_clean();
		
		if (!empty($this->arr_options[2])) {
			
			$temp_path = getPathTemporary();
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
	
		$this->data = file_get_contents($this->str_path_source);
	}
		
	private function write() {

		FileStore::storeFile($this->str_path_destination, $this->data);
	}
		
	public function read() {
		
		$ie_tag = 0;
		if ($this->do_create_archive && !$this->is_new) {
			
			$ie_tag = filemtime($this->str_path_destination);
			$cur_ie_tag = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') : false);
			
			if ($cur_ie_tag == $ie_tag) {
				header($_SERVER['SERVER_PROTOCOL'].' 304 Not Modified');
				exit;
			}
		}

		Response::sendFileHeaders(($this->is_new ? $this->data : $this->str_path_destination), false, [
			'ETag: "'.$ie_tag.'"',
			'Cache-Control: max-age='.(60*60*24),
			'1100CC-Cached: '.(int)$this->do_create_archive
		]);
		
		if ($this->is_new) {
			echo $this->data;
		} else {
			readfile($this->str_path_destination);
		}
	}
	
	public function getOptionsString() {
		
		return $this->str_hash_options;
	}
	
	public function getURLString() {
		
		return $this->str_hash_url;
	}
	
	public function getPath() {
		
		if (!$this->do_create_archive) {
			return false;
		}
		
		return $this->str_path_destination;
	}
	
	public function getData() {
		
		if ($this->is_new) {
			return $this->data;
		} else {
			return file_get_contents($this->str_path_destination);
		}
	}
}
