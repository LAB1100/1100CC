<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class ExchangePackage {
	
	protected $str_path = false;
	protected $archive = null;
	
	protected $str_filename = '';
	protected $do_updates = false;

    public function __construct($str_path = false, $do_read_only = false) {
					
		$this->archive = new FileArchive($str_path, ($do_read_only ? null : true));
		
		$this->str_path = $this->archive->get();
    }
    
    public function setUpdates($do = true) {
		
		$this->do_updates = (bool)$do;
	}
    
	public function create($arr = []) {
		
		/*$arr = [
			'sql' => ['database_tables' => [], 'target_id' => null],
			'files' => []
		];

		database_tables = [
			DATABASE:TO_DATABASE => [
				['name' => DB::getTable('TABLE'), 'create' => bool, 'truncate' => bool, 'field' => 'from_id', 'exclude' => string/array, 'print' => ['names' => bool], 'on' => [
					['to_id', DB::getTable('TABLE'), 'with_id']
				]],
			]
		];*/
						
		$this->str_filename = 'package';
		
		$this->add1100CCText();
		
		$this->addCollection($arr);
		
		$this->str_filename .= '.1100CC';
					
		return ['path' => $this->get(), 'filename' => $this->str_filename];
	}
	
	public function add1100CCText($str_append = '') {
		
		Response::holdFormat(true);
		Response::setFormat(Response::RENDER_TEXT);
		
		$str = Labels::getServerVariable('version');
		
		if ($str_append) {
			
			$str .= EOL_1100CC.EOL_1100CC.$str_append;
		}
		
		$this->archive->add(['1100CC.txt' => $str]);
		
		Response::holdFormat();
	}
	
	public function addCollection($arr) {
		
		if ($arr['sql']) {
			
			$this->addSQL($arr['sql']);
			
			$str_sql = implode('-', array_keys($arr['sql']['database_tables'])).'-'.(int)$arr['sql']['target_id'];
			
			$this->str_filename .= '-SQL-'.$str_sql;
		}
		
		if ($arr['files']) {
			
			$this->addFiles($arr['files']);
			
			$str_files = count($arr['files']);
			
			$this->str_filename .= '-FILES-'.$str_files;
		}
		
		if ($arr['APP']) {
			
			$this->addAPPFiles($arr['APP']);
			
			$this->str_filename .= '-APP';
		}
	}
	
	public function addSQL($arr_sql) {
		
		$arr_sql_database_tables = ($arr_sql['database_tables'] ?: []);
		$sql_target_id = $arr_sql['target_id'];
		$sql_target_id = ($sql_target_id === false ? null : $sql_target_id);

		$path_dump = getPathTemporary();
		
		$arr_filenames = [];
				
		if ($this->do_updates) {
			status('Storing: '.num2String(count($arr_sql_database_tables)).' databases.');
		}

		foreach ($arr_sql_database_tables as $database => $arr_tables) {
			
			$arr_database = explode(':', $database);
			$database_from = $arr_database[0];
			$database_to = ($arr_database[1] ?: $database_from);
			
			foreach ($arr_tables as $arr_table) {
				
				$arr_table_name = explode('.', $arr_table['name']);
				$table_name = trim(($arr_table_name[1] ?? $arr_table_name[0]), '"'); // Table only
				$sql_table_name = DB::getTable($table_name);
				
				$res = DB::query("SELECT
					TABLE_NAME AS name,
					TABLE_TYPE AS type
						FROM information_schema.TABLES
					WHERE TABLE_SCHEMA = '".DBFunctions::strEscape($database_from)."'
						AND TABLE_NAME = '".DBFunctions::strEscape($table_name)."'
				");
				
				$arr_sql_table = $res->fetchAssoc();
				
				if (!$res->getRowCount()) {
					continue;
				}
				
				$file_dump = fopen($path_dump, 'w');
				
				$sql_table_name_source = DB::getTable($arr_table['name']); // Parse for processing
				DB::setConnectionDatabase($database_from);
				
				if (DB::ENGINE_IS_MYSQL) {
					DB::query('USE '.$database_from);
				} else {
					DB::query('SET search_path TO '.$database_from);
				}
				
				if ($arr_table['create']) {
						
					$return = 'DROP '.($arr_sql_table['type'] == 'VIEW' ? 'VIEW' : 'TABLE').' IF EXISTS '.$sql_table_name.";\n\n";
					
					$res = DB::query('SHOW CREATE '.($arr_sql_table['type'] == 'VIEW' ? 'VIEW' : 'TABLE').' '.$sql_table_name_source);
					
					$arr_sql_create = $res->fetchRow();
					
					$return .= $arr_sql_create[1].";\n\n";
					
					fwrite($file_dump, $return);
				}
							
				if ($arr_sql_table['type'] != 'VIEW') {

					$sql = 'SELECT DISTINCT table_0.* FROM '.$sql_table_name_source.' AS table_0';

					$arr_on_prev = [false, $sql_table_name_source, $arr_table['field']];
					$count = 1;
					
					if ($arr_table['on']) {
						
						foreach ($arr_table['on'] as $arr_on) {
							
							$sql .= ' JOIN '.DB::getTable($arr_on[1]).' AS table_'.$count.' ON (table_'.$count.'.'.$arr_on[0].' = table_'.($count-1).'.'.$arr_on_prev[2].')';
							
							$count++;
							$arr_on_prev = $arr_on;
						}
					}
					
					if ($sql_target_id !== null) {
						
						$sql .= ' WHERE table_'.($count-1).'.'.$arr_on_prev[2].' = '.(int)$sql_target_id;
					}
					
					$res = DB::query($sql);
					
					$num_fields = $res->getFieldCount();
					$arr_types = [];
					$arr_fields_callback = [];
					$arr_fields_exclude = [];
					$arr_sql_fields = [];
					
					for ($i = 0; $i < $num_fields; $i++) {
						
						$arr_types[$i] = $res->getFieldDataType($i);
						
						$arr_meta = $res->getFieldMeta($i);
						
						if (isset($arr_table['exclude'])) {
							
							$arr_exclude = (array)$arr_table['exclude'];
							
							if (in_array($arr_meta['name'], $arr_exclude)) {
								
								$arr_fields_exclude[$i] = true;
								continue;
							}
						}
						
						$arr_sql_fields[] = $arr_meta['name'];
						
						if (isset($arr_table['callback'][$arr_meta['name']])) {
							$arr_fields_callback[$i] = $arr_table['callback'][$arr_meta['name']];
						}
					}
					
					if ($arr_table['truncate']) {
						
						$return = 'DELETE FROM '.$sql_table_name.";\n\n";
						
						fwrite($file_dump, $return);
					}

					while ($arr_row = $res->fetchRow()) {
						
						$return = 'INSERT INTO '.$sql_table_name;
						if (!empty($arr_table['print']['names'])) {
							$return .= ' ("'.implode('", "', $arr_sql_fields).'")';
						}
						$return .= ' VALUES (';
						
						$do_separator = false;
						
						for ($i = 0; $i < $num_fields; $i++) {
							
							if (isset($arr_fields_exclude[$i])) {
								continue;
							}
							
							if ($do_separator) {
								$return .= ',';
							} else {
								$do_separator = true;
							}
							
							$value = $arr_row[$i];
							
							if ($value === null) {
								
								$return .= 'NULL';
							} else {
								
								$type = $arr_types[$i];

								if (isset($arr_fields_callback[$i])) {
									
									$func_callback = $arr_fields_callback[$i];
									$value = $func_callback($value);
								}
								
								//if ($type == MYSQLI_TYPE_SHORT || $type == MYSQLI_TYPE_LONG) {
								if ($type == DBFunctions::TYPE_INTEGER) {
									$return .= (int)$value;
								//} else if ($type == MYSQLI_TYPE_FLOAT) {
								} else if ($type == DBFunctions::TYPE_FLOAT) {
									$return .= (float)$value;
								//} else if ($type == MYSQLI_TYPE_TINY) {
								} else if ($type == DBFunctions::TYPE_BOOLEAN) {
									$return .= ($value ? 'TRUE' : 'FALSE');
								} else {
									
									if ($value) {
										$value = DBFunctions::strEscape($value);
										$value = str_replace("\n", "\\n", $value);
									}
								
									$return .= "'".$value."'";
								}
							}
						}
						
						$return .= ");\n";
						
						fwrite($file_dump, $return);
					}
					
					$res->freeResult();
				}
				
				DB::setConnectionDatabase(false);

				fclose($file_dump);

				$filename = $database_to.'/'.$table_name.'.sql';
				
				if ($arr_filenames[$filename]) {
					
					$arr_filenames[$filename]++;
					$filename = $database_to.'/'.$table_name.'_'.$arr_filenames[$filename].'.sql';
				} else {

					$arr_filenames[$filename] = 1;
				}

				$this->archive->add(['sql/'.$filename => $path_dump]);
				
				if ($this->do_updates) {
					
					status('Table stored: '.$filename.'.');
				}
			}
		}
	}
	
	public function addFiles($arr_files) {
		
		$arr_files_collect = [];
		
		if ($this->do_updates) {
			
			$num_total = count($arr_files);
			
			status('Storing: '.num2String($num_total).' files.');
			
			$num_archive_total = $this->archive->getStatistics();
			
			$this->archive->getStatistics(function($num_count) use ($num_total, $num_archive_total) {
				
				$num_count -= $num_archive_total;
				
				if (($num_count % 10) != 0) {
					return;
				}
				
				status('File stored: '.num2String($num_count).' of '.num2String($num_total).'.');
			});
		}
		
		foreach ($arr_files as $str_path_file) {
			
			if (is_array($str_path_file)) {
				$str_path_source = DIR_ROOT_STORAGE.DIR_HOME.$str_path_file['source'];
				$str_path_file = $str_path_file['target'];
			} else {
				$str_path_source = DIR_ROOT_STORAGE.DIR_HOME.$str_path_file;
			}
			
			if (!isPath($str_path_source)) {
				continue;
			}
			
			$arr_files_collect['files/'.$str_path_file] = $str_path_source;			
		}
				
		$this->archive->add($arr_files_collect);
	}
	
	public function addAPPFiles($arr_files) {
		
		$arr_files_collect = [];
		
		foreach ($arr_files as $str_path => $content) {
						
			$arr_files_collect['APP/'.$str_path] = $content;
		}
		
		$this->archive->add($arr_files_collect);
	}
	
	public function read1100CCText() {
		
		try {
			
			return file_get_contents($this->archive->getEntry('1100CC.txt'));
		} catch (Exception $e) {
			
			error(getLabel('msg_not_found'));
		}
	}
	
	public function upload() {
		
		$do_process_core = false;
		$do_process_site = false;
		
		foreach ($this->archive->iterate() as $str_path_zip => $arr_entry) {
			
			$str_target = $arr_entry['target'];
			
			if ($str_target == 'sql') {
				
				$database = $arr_entry['segments'][0];
				
				$this->uploadSQL($str_path_zip, $database);
			} else if ($str_target == 'files') {
				
				$str_path_file = arr2String($arr_entry['segments'], '/');
				
				$this->uploadFile($str_path_zip, $str_path_file);
			} else if ($str_target == 'APP') {
				
				$str_folder = $arr_entry['segments'][0];
				
				if (empty($arr_entry['segments'][1])) { // Only a directory
					continue;
				}
				
				$str_path_file = arr2String(array_slice($arr_entry['segments'], 1), '/');

				if ($str_folder == 'update') {

					$this->uploadAPPUpdateFile($str_path_zip, $str_path_file);
				} else {
									
					if ($str_folder == 'CORE') {
						$do_process_core = true;
					} else if ($str_folder == SITE_NAME) {
						$do_process_site = true;
					} else {
						continue;
					}
					
					$str_folder = $str_folder.'_new';
					
					$this->uploadAPPFile($str_path_zip, $str_folder, $str_path_file);
				}
			}
		}
		
		if ($do_process_core || $do_process_site) {
			
			if ($do_process_core) {
				
				FileStore::renameFile(DIR_ROOT.'CORE', DIR_ROOT.'CORE_old');
				FileStore::renameFile(DIR_ROOT.'CORE_new', DIR_ROOT.'CORE');
				
				Mediator::attach('cleanup', false, function() {
					FileStore::deleteDirectoryTree(DIR_ROOT.'CORE_old');
				});
			}
			if ($do_process_site) {
				
				FileStore::renameFile(DIR_ROOT.SITE_NAME, DIR_ROOT.SITE_NAME.'_old');
				FileStore::renameFile(DIR_ROOT.SITE_NAME.'_new', DIR_ROOT.SITE_NAME);
				
				Mediator::attach('cleanup', false, function() {
					FileStore::deleteDirectoryTree(DIR_ROOT.SITE_NAME.'_old');
				});
			}
		}
	}
	
	protected function uploadSQL($str_resource, $database) {
		
		DB::setConnectionDatabase($database);
		
		if (DB::ENGINE_IS_MYSQL) {
			DB::query('USE '.$database);
		} else {
			DB::query('SET search_path TO '.$database);
		}
		
		$file = fopen($str_resource, 'r');

		$sql = '';
		$count = 0;
		$arr_row = [];
				
		DB::startTransaction('cms_admin_sql');
		
		while (!feof($file)) {
			
			$arr_row[] = fgets($file);
			
			// Match the end of the query defined by a ';'
			if (preg_match('/;\s*$/iS', end($arr_row))) {
			
				$sql .= trim(implode('', $arr_row));
		
				$arr_row = [];
				$count++;
				
				if ($count > 1000) {
					
					DB::queryMulti($sql);
					
					$sql = '';
					$count = 0;
				}
			}
		}
		
		if ($sql) {
			DB::queryMulti($sql);
		}
		
		DB::commitTransaction('cms_admin_sql');

		DB::setConnectionDatabase(false);
		
		fclose($file);
	}
	
	protected function uploadFile($str_resource, $str_path) {
		
		FileStore::storeFile(DIR_ROOT_STORAGE.DIR_HOME.$str_path, file_get_contents($str_resource));
	}
	
	protected function uploadAPPFile($str_resource, $str_folder, $str_path) {
		
		FileStore::storeFile(DIR_ROOT.$str_folder.'/'.$str_path, file_get_contents($str_resource));
	}
	
	protected function uploadAPPUpdateFile($str_resource, $str_path) {
		
		$str_directory_update = Settings::getUpdatePath(false);
		
		FileStore::storeFile($str_directory_update.$str_path, file_get_contents($str_resource));
	}
	
	public function get() {
		
		return $this->str_path;
	}
}
