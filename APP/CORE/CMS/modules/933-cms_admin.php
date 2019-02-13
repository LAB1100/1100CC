<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class cms_admin extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_admin');
		static::$parent_label = getLabel('ttl_settings');
	}
	
	public static function backupProperties() {
		return [
			'1100cc_home' => [
				'label' => getLabel('lbl_backup_1100cc').' HOME',
				'database' => DB::$database_home,
				'tables' => [],
				'download' => false
			],
			'1100cc_cms' => [
				'label' => getLabel('lbl_backup_1100cc').' CMS',
				'database' => DB::$database_cms,
				'tables' => [],
				'download' => false
			]
		];
	}
	
	private static $arr_backup_files = [];
	private static $path_update = 'update.php';
	
	public function contents() {

		$return .= '<div class="section"><h1>'.self::$label.'</h1>
		<div class="cms_admin">';
		
			$arr_backup_details = self::getModuleBackupDetails();
			$arr_backup_selector = [];
			
			foreach ($arr_backup_details as $key => $value) {
				$arr_backup_selector[] = ['id' => $key, 'name' => $value['label']];
			}
			
			$arr_backup_files_filtered = $this->createBackupRestore();
			$first_name = current(array_keys($arr_backup_files_filtered));
			$arr_backup_files_filtered_date = $this->createBackupRestoreDate($first_name);
			$last_date = array_keys($arr_backup_files_filtered_date);
			$last_date = end($last_date);
			$arr_backup_files_filtered_time = $this->createBackupRestoreTime($first_name, $last_date);
			$last_time = array_keys($arr_backup_files_filtered_time);
			$last_time = end($last_time);
		
			$return .= '<div class="tabs">
				<ul>
					<li><a href="#">'.getLabel('lbl_backup').'</a></li>
					<li><a href="#">'.getLabel('lbl_restore').'</a></li>
					'.($_SESSION['CORE'] ? '<li><a href="#">'.getLabel('lbl_download').' 1100CC</a></li>' : '').'
					'.($_SESSION['CORE'] ? '<li><a href="#">'.getLabel('lbl_update').' 1100CC</a></li>' : '').'
				</ul>
				
				<div class="backup">
				
					<form id="f:cms_admin:backup-0">
						
						<div class="options">'
							.'<select name="backup[]" multiple="multiple">'.cms_general::createDropdown($arr_backup_selector).'</select>'
						.'</div>
						
						<menu><input title="'.getLabel('inf_backup_download').'" name="download" type="submit" value="'.getLabel('lbl_download').'" /><input type="submit" value="'.getLabel('lbl_save').'" /></menu>
					</form>
					
				</div>
				
				<div class="backup-restore">
					
					<div class="tabs">
						<ul>
							<li><a href="#">'.getLabel('lbl_storage').'</a></li>
							'.($_SESSION['CORE'] ? '<li><a href="#">'.getLabel('lbl_upload').'</a></li>' : '').'
						</ul>
						
						<div>
							<form id="f:cms_admin:backup_restore-0">
							
								<div class="options">'
									.'<select name="name">'.cms_general::createDropdown($arr_backup_files_filtered, $first_name).'</select>'
									.'<select name="date" id="y:cms_admin:select_backup_restore_date-0">'.cms_general::createDropdown($arr_backup_files_filtered_date, $last_date).'</select>'
									.'<select name="file" id="y:cms_admin:select_backup_restore_time-0">'.cms_general::createDropdown($arr_backup_files_filtered_time, $last_time).'</select>'
								.'</div>
								
								<menu><input type="submit" value="'.getLabel('lbl_save').'" /></menu>
							</form>
						</div>
							
						'.($_SESSION['CORE'] ? '<div>
							<form id="f:cms_admin:upload_database-0">
							
								<div class="options">
									'.cms_general::createFileBrowser().'
								</div>
								
								<menu><input type="submit" value="'.getLabel('lbl_upload').'" /></menu>
							</form>
						</div>' : '').'
						
					</div>
					
				</div>';
				
				if ($_SESSION['CORE']) {
					
					$return .= '<div>
					
						<form id="f:cms_admin:dump-0">
							
							<div class="options">'
								.'<label><input type="checkbox" name="obfuscate[full]" value="1" /><span>'.getLabel('lbl_obfuscate').'</span></label>'
								.'<label><input type="checkbox" name="obfuscate[classes]" value="1" /><span>'.getLabel('lbl_obfuscate').' [classes]</span></label>'
								.'<label><input type="checkbox" name="storage_include" value="1" /><span>'.getLabel('lbl_storage').'</span></label>'
							.'</div>
							
							<menu><input type="submit" value="'.getLabel('lbl_download').'" /></menu>
						</form>
						
					</div>';
					
					$return .= '<div class="update">
						
						'.$this->contentTabUpdate().'
						
					</div>';
				}			
					
			$return .= '</div>';
				
		$return .= '</div></div>';
		
		return $return;
	}
	
	private function contentTabUpdate() {
		
		$path_update = DIR_ROOT_SETTINGS.DIR_HOME.self::$path_update;
		
		$return = '<form id="f:cms_admin:update-0">

			<div class="record options"><dl>
				<li>
					<dt>'.getLabel('lbl_available').'</dt>
					<dd><span class="icon">'.getIcon((isPath($path_update) ? 'tick' : 'min')).'</span></dd>
				</li>
			</dl></div>
			
			<menu><input type="submit" value="'.getLabel('lbl_update').'" /></menu>
			
		</form>';
		
		return $return;
	}
	
	private function createBackupRestore() {
	
		$arr_backup_files = self::getBackupFiles();
		$arr_backup_details = self::getModuleBackupDetails();
		
		$arr_backup_files_filtered = [];
		
		foreach(($arr_backup_files ?: []) as $key => $value) {
			$arr_backup_files_filtered[$key] = ['id' => $key, 'name' => $arr_backup_details[$key]['label']];
		}
		
		return $arr_backup_files_filtered;
	}
	
	private function createBackupRestoreDate($name) {
	
		$arr_backup_files = self::getBackupFiles();
		
		$arr_backup_files_filtered_date = [];
		
		foreach(($arr_backup_files[$name] ?: []) as $key => $value) {
			$arr_backup_files_filtered_date[$key] = ['id' => $key, 'name' => date('d-m-Y', strtotime($key))];
		}
		
		return $arr_backup_files_filtered_date;
	}
	
	private function createBackupRestoreTime($name, $date) {
	
		$arr_backup_files = self::getBackupFiles();
		
		$arr_backup_files_filtered_time = [];
		
		foreach(($arr_backup_files[$name][$date] ?: []) as $key => $value) {
			$arr_backup_files_filtered_time[$value] = ['id' => $value, 'name' => $key];
		}

		return $arr_backup_files_filtered_time;
	}
	
	public static function css() {
	
		$return = '
			.cms_admin form { text-align: center; }
			.cms_admin .tabs > .backup select { display: block; margin: 0px auto 8px auto; }
			.cms_admin .tabs > .update dl { display: inline-table; margin-left: auto; margin-right: auto; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-cms_admin', function(elm_scripter) {
		
			elm_scripter.on('change', '.tabs > .backup-restore [name=name]', function() {
				$(this).next('[name=date]').data('value', {'name': $(this).val()}).quickCommand([$(this).next('[name=date]'), $(this).nextAll('[name=file]')]);
			}).on('change', '.tabs > .backup-restore [name=date]', function() {
				$(this).next('[name=file]').data('value', {'name': $(this).prev('[name=name]').val(), 'date': $(this).val()}).quickCommand($(this).next('[name=file]'));
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
		
		
		if ($method == 'upload_database' && $_SESSION['CORE']) {
			
			if (!$_FILES['file']) {
				error(getLabel('msg_missing_information'));
			}
			
			self::uploadSQL($_FILES['file']['tmp_name']);
			
			$this->msg = true;
		}
		
		if ($method == "backup") {
		
			if (!$_POST['backup']) {
				error(getLabel('msg_missing_information'));
			}

			$do_download = $_POST['download'];
			
			$arr_backup_details = self::getModuleBackupDetails();
			$arr_backup_identifiers = [];
		
			foreach ($_POST['backup'] as $key => $identifier) {
				
				$arr_backup_identifier = $arr_backup_details[$identifier];

				if (!$arr_backup_identifier) {
					continue;
				}
				
				$arr_backup_identifiers[$identifier] = $identifier;
				
				if ($do_download && !$arr_backup_identifier['download'] && !$_SESSION['CORE']) { // Check if the databases are allowed to be downloaded
					error(getLabel('msg_not_allowed'));
				}
			}
			
			if (!$arr_backup_identifiers) {
				return;
			}
		
			if (!$do_download || ($do_download && $_POST['get-download'])) {

				self::runBackup($arr_backup_identifiers, $do_download);
			
				if ($do_download) {
					exit;
				}
				
				$this->msg = true;			
			} else if ($do_download) {
				
				$this->download = true;
			}
		}
		
		if ($method == "select_backup_restore_date") {
						
			$arr_backup_files_filtered_date = $this->createBackupRestoreDate($value['name']);
			
			$last_date = array_keys($arr_backup_files_filtered_date);
			$last_date = end($last_date);
			
			$arr_backup_files_filtered_time = $this->createBackupRestoreTime($value['name'], $last_date);
			
			$last_time = array_keys($arr_backup_files_filtered_time);
			$last_time = end($last_time);

			$this->html = [cms_general::createDropdown($arr_backup_files_filtered_date, $last_date), cms_general::createDropdown($arr_backup_files_filtered_time, $last_time)];
		}
		
		if ($method == "select_backup_restore_time") {

			$arr_backup_files_filtered_time = $this->createBackupRestoreTime($value['name'], $value['date']);
			
			$last_time = end(array_keys($arr_backup_files_filtered_time));
			
			$this->html = cms_general::createDropdown($arr_backup_files_filtered_time, $last_time);
		}
		
		if ($method == "backup_restore") {
			
			if (!$_POST['name'] || !$_POST['file']) {
				error(getLabel('msg_missing_information'));
			}
			
			$arr_backup_details = self::getModuleBackupDetails();
			$arr_file = self::parseBackupFilename($_POST['file']);
			
			if (!$arr_file || !$arr_backup_details[$arr_file['name']]) {
				error(getLabel('msg_missing_information'));
			}
			
			Labels::setVariable('name', $arr_backup_details[$arr_file['name']]['label']);
			Labels::setVariable('date',  date('d-m-Y', strtotime($arr_file['date'])));
			Labels::setVariable('time', $arr_file['time']);
						
			$this->html = getLabel('conf_backup_restore');
			$this->confirm = true;
		}
		
		if (is_array($method) && $method['method'] == "backup_restore" && $method['confirmed']) {
			
			if (!$_POST['name'] || !$_POST['file']) {
				error(getLabel('msg_missing_information'));
			}
			
			$arr_backup_details = self::getModuleBackupDetails();
		
			if (!$arr_backup_details[$_POST['name']]) {
				return false;
			}
			
			self::uploadSQL($_POST['file']);
			
			$this->msg = true;
		}
		
		if ($method == "dump" && $_SESSION['CORE']) {
				
			if ($_POST['get-download']) {

				self::runDump(['obfuscate' => $_POST['obfuscate'], 'storage_include' => (int)$_POST['storage_include']]);
				
				exit;
			}
			
			$this->download = true;
		}
		
		if ($method == "update" && $_SESSION['CORE']) {
							
			self::runUpdate();
			
			$this->msg = true;
			$this->html = $this->contentTabUpdate();
		}
	}
				
	private static function getModuleBackupDetails() {

		$arr_database_tables = DB::getDatabaseTables();
		$arr_backup_details = [];
		
		foreach (getModuleConfiguration('backupProperties') as $arr) {
			
			foreach ($arr as $key => $value) {
				
				$value['tables'] = ($value['tables'] ?: $arr_database_tables[$value['database']]);
				
				$arr_backup_details[$key] = $value;
			}
		}

		if ($_SESSION['CORE']) {
			
			$arr_databases = arrValuesRecursive('database', $arr_backup_details);
			
			foreach ($arr_database_tables as $database => $arr_tables) {
				
				if (in_array($database, $arr_databases) || $database == DB::$database_core) {
					continue;
				}
				
				$arr_backup_details[$database] = ['label' => getLabel('lbl_unknown').' - '.$database, 'database' => $database, 'tables' => $arr_tables,  'download' => false];
			}
		}

		return $arr_backup_details;
	}
	
	private static function getBackupFiles() {
	
		if (self::$arr_backup_files) {
			return self::$arr_backup_files;
		}
		
		$arr_backup_details = self::getModuleBackupDetails();
		
		self::$arr_backup_files = [];
		
		FileStore::makeDirectoryTree(DIR_SITE_STORAGE.DIR_BACKUP);
		
		foreach (new DirectoryIterator(DIR_SITE_STORAGE.DIR_BACKUP) as $file_info) {
			
			$filename = $file_info->getFilename();
			$arr_file = self::parseBackupFilename($filename);
			
			if ($arr_file && $arr_backup_details[$arr_file['name']]) {
				
				self::$arr_backup_files[$arr_file['name']][$arr_file['date']][$arr_file['time']] = $filename;
			}
		}
		
		self::$arr_backup_files = arrKsortRecursive(self::$arr_backup_files);
		
		return self::$arr_backup_files;
	}
	
	private static function parseBackupFilename($filename) {
		
		preg_match('/^database-(.*)-(\d.*)\.zip$/', $filename, $arr_match);
		
		if (!$arr_match) {
			return false;
		}
		
		$name = $arr_match[1];
		$date = date('Y-m-d', $arr_match[2]);
		$time = date('H:i:s', $arr_match[2]);
				
		return ['name' => $name, 'date' => $date, 'time' => $time];
	}
	
	private static function runBackup($arr_backup_identifiers, $download = false) {
		
		$arr_backup_details = self::getModuleBackupDetails();
		
		$arr_database_tables = [];

		foreach ($arr_backup_identifiers as $identifier) {
			
			$database = $arr_backup_details[$identifier]['database'];
			
			$res = DB::query("SELECT
				TABLE_NAME AS name
					FROM information_schema.TABLES
				WHERE TABLE_SCHEMA = '".$database."'
			");
			
			while ($arr_row = $res->fetchAssoc()) {
			
				$arr_database_tables[$database][] = ['name' => $arr_row['name'], 'create' => true, 'truncate' => true];
			}
			
			if (!$download) { // Store one database per archive
			
				$path = DIR_SITE_STORAGE.DIR_BACKUP.'database-'.$identifier.'-'.time().'.zip';
			
				self::createSQL($arr_database_tables, false, $path);
				
				$arr_database_tables = [];
			}
		}
		
		if ($download) { // Download all databases together (when applicable)
			
			self::createSQL($arr_database_tables);
		}
	}
	
	public static function createSQL($arr_database_tables, $target_id = false, $path = false) {
		
		/*
		$arr_database_tables = [
			DATABASE => [
				['name' => DB::getTable('TABLE'), 'create' => bool, 'truncate' => bool, 'field' => 'from_id', 'on' => [
					['to_id', DB::getTable('TABLE'), 'with_id']
				]],
			]
		];
		*/
		
		$archive = new FileArchive($path);
		
		$path_dump = tempnam(Settings::get('path_temporary'), '1100CC');
		
		$arr_filenames = [];
		
		timeLimit(300);

		foreach ($arr_database_tables as $database => $arr_tables) {
			
			foreach ($arr_tables as $arr_table) {
				
				$arr_table_name = explode('.', $arr_table['name']);
				
				$res = DB::query("SELECT
					TABLE_NAME AS name,
					TABLE_TYPE AS type
						FROM information_schema.TABLES
					WHERE TABLE_SCHEMA = '".$database."'
						AND TABLE_NAME = '".($arr_table_name[1] ?: $arr_table_name[0])."'
				");
				
				$arr_sql_table = $res->fetchAssoc();
				
				if (!$res->getRowCount()) {
					continue;
				}
				
				$file_dump = fopen($path_dump, 'w');
				
				$sql_table_name = DB::getTable($arr_table['name']); // Parse for processing
				
				if ($arr_table['create']) {
						
					$return = 'DROP '.($arr_sql_table['type'] == 'VIEW' ? 'VIEW' : 'TABLE').' IF EXISTS '.$sql_table_name.";\n\n";
					
					$res = DB::query('SHOW CREATE '.($arr_sql_table['type'] == 'VIEW' ? 'VIEW' : 'TABLE').' '.$sql_table_name);
					
					$arr_sql_create = $res->fetchRow();
					
					$return .= $arr_sql_create[1].";\n\n";
					
					fwrite($file_dump, $return);
				}
							
				if ($arr_sql_table['type'] != 'VIEW') {
						
					DB::setDatabase($database);

					$sql = 'SELECT DISTINCT table_0.* FROM '.$sql_table_name.' AS table_0';

					$arr_on_prev = [false, $sql_table_name, $arr_table['field']];
					$count = 1;
					
					if ($arr_table['on']) {
						
						foreach ($arr_table['on'] as $arr_on) {
							
							$sql .= ' JOIN '.DB::getTable($arr_on[1]).' AS table_'.$count.' ON (table_'.$count.'.'.$arr_on[0].' = table_'.($count-1).'.'.$arr_on_prev[2].')';
							
							$count++;
							$arr_on_prev = $arr_on;
						}
					}
					
					if ($target_id !== false) {
						
						$sql .= ' WHERE table_'.($count-1).'.'.$arr_on_prev[2].' = '.(int)$target_id;
					}
					
					$res = DB::query($sql);
					
					$nr_fields = $res->getFieldCount();
					$arr_types = [];
					
					for ($i = 0; $i < $nr_fields; $i++) {
						
						$arr_types[$i] = $res->getFieldDataType($i);
						//$arr_types[$i] = $res->fetch_field_direct($i)->type;
					}
					
					if ($arr_table['truncate']) {
						
						$return = 'DELETE FROM '.$sql_table_name.";\n\n";
						
						fwrite($file_dump, $return);
					}

					while ($arr_row = $res->fetchRow()) {
						
						$return = 'INSERT INTO '.$sql_table_name.' VALUES(';
						
						for ($i = 0; $i < $nr_fields; $i++) {
							
							$value = $arr_row[$i];
							
							if ($value === null) {
								
								$return .= 'NULL';
							} else {
								
								$type = $arr_types[$i];
								
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
							
							$return .= ($i < ($nr_fields-1) ? "," : "");
						}
						
						$return .= ");\n";
						
						fwrite($file_dump, $return);
					}
					
					$res->freeResult();
					
					DB::setDatabase();
				}

				fclose($file_dump);
				
				$filename = $database.'/'.$arr_table['name'].'.sql';
				
				if ($arr_filenames[$filename]) {
					
					$arr_filenames[$filename]++;
					$filename = $database.'/'.$arr_table['name'].'_'.$arr_filenames[$filename].'.sql';
				} else {

					$arr_filenames[$filename] = 1;
				}

				$archive->add([$filename => $path_dump]);
			}
		}
		
		if (!$path) {
			
			$archive->read('databases-'.implode('-', array_keys($arr_database_tables)).'-'.(int)$target_id.'.zip');
		}
	}
	
	public static function uploadSQL($path) {
		
		$zip = new ZipArchive();
		
		$zip->open($path);

		for ($i = 0; $i < $zip->numFiles; $i++) {
			
			$entry = $zip->getNameIndex($i);
			
			$arr_entry = explode('/', $entry);
			
			$database = $arr_entry[0];
			
			DB::setDatabase($database);
			
			$file = fopen('zip://'.$path.'#'.$entry, 'r');

			$sql = '';
			$count = 0;
			$arr_row = [];
			
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

			DB::setDatabase();
			
			fclose($file);
		}
	}
		
	private static function runDump($arr_options = []) {

		$archive = new FileArchive();
		$dump = new PhpDump();
		
		$dump->removecomments = true;
		if ($arr_options['obfuscate']) {
			$dump->obfuscateclass = true;
			$dump->obfuscatefunction = true;
			$dump->obfuscatevariable = true;
			$dump->addPredefinedObject(['JSON']);
			$dump->addPredefinedClassVariable(['firstChild', 'lastChild', 'parentNode', 'strictErrorChecking', 'length']); // DOMDocument not showing properties bug
		}
		$dump->init();
		
		$arr_php = [];
		$arr_module_files = [];
		
		foreach ([SiteStartVars::$modules, getModules(DIR_HOME)] as $arr_modules) {
			
			foreach ($arr_modules as $class => $value) {
				
				$arr_module_files[$class] = $value['file'];
			}
		}
		
		timeLimit(120);
		
		$arr_folders = ['CORE', SITE_NAME];
		if ($arr_options['storage_include']) {
			$arr_folders[] = DIR_STORAGE.SITE_NAME;
		}
		
		foreach ($arr_folders as $core_site_storage) {

			$dir = DIR_ROOT.$core_site_storage.'/';
			$dir_it = new RecursiveDirectoryIterator($dir);
			$it = new RecursiveIteratorIterator($dir_it);

			foreach ($it as $file) {
				
				if (!$file->isFile()) {
					continue;
				}
				
				$file_path = $file->getPathname();
				$zip_path = $core_site_storage.'/'.substr($file_path, strlen($dir));
				
				if ($file->getExtension() != 'php') {
					$archive->add([$zip_path => $file_path]);
				} else {

					$do_add = false;
					
					// Only add from modules and catalog folder when used
					if ((self::inDir($file->getPath(), $dir.DIR_MODULES) || self::inDir($file->getPath(), $dir.DIR_CMS.DIR_MODULES)) && !self::inDir($file->getPath(), DIR_MODULES.DIR_MODULES_ABSTRACT)) {

						$class = array_search($file->getFilename(), $arr_module_files);
						if ($class) {
							$dump->addPredefinedClass($class);
							$do_add = true;
						}
					} else {
						$do_add = true;
					}
					
					if ($do_add) {
						$arr_php[$zip_path] = $file_path;
					}
				}
			}
		}
		
		// Pre-run to find all classes and functions
		if ($arr_options['obfuscate']) {
			
			foreach ($arr_php as $zip_path => $file_path) {
				
				$dir_name = self::dirName($file_path).'/';
				
				if ($arr_options['obfuscate']['classes'] && $dir_name != DIR_CLASSES) {
					continue;
				}

				$dump->prerun($file_path);
			}
			
			$dump->obfuscateclass = false;
			$dump->obfuscatefunction = false;
			$dump->obfuscatevariable = false;
		}
		
		$dump->resetUsedClasses();
		
		// Start dump
		$arr_php_classes = [];
		
		foreach ($arr_php as $zip_path => $file_path) {
		
			$dir_name = self::dirName($file_path).'/';

			if ($dir_name == DIR_CLASSES || $dir_name == DIR_MODULES_ABSTRACT) {
				$arr_php_classes[$zip_path] = $file_path;
				continue;
			}

			$content = $dump->trash($file_path);
			
			$archive->add([$zip_path => $content]);
		}
		
		// Only add from classes and base folder when used
		$used = true;
		while ($used) {
		
			$used = false;
			
			foreach ($arr_php_classes as $zip_path => $file_path) {
			
				$class = basename($file_path, '.php');
				
				if ($dump->isUsedClass($class) && $class != 'PhpDump') {

					$zip_path_new = ($dump->returnClass($class) ? dirname($zip_path).'/'.$dump->returnClass($class).'.php' : $zip_path);
					$content = $dump->trash($file_path);
					
					$archive->add([$zip_path_new => $content]);
					
					$used = true;
					unset($arr_php_classes[$zip_path]);
				}
			}
		}
		
		$archive->read('dump-'.SITE_NAME.'-'.date('dmY-His').'.zip');
	}
	
	private static function runUpdate() {
		
		$path_update = DIR_ROOT_SETTINGS.DIR_HOME.self::$path_update;
		
		if (!isPath($path_update)) {
				
			error(getLabel('msg_not_available'));
		}
		
		set_time_limit(0);
		
		try {
			
			require($path_update);		
		} catch (Exception $e) {
			
			error('1100CC Update Failed', 0, LOG_BOTH, false, $e);
		}
		
		msg('1100CC Successfully Updated');
		
		unlink($path_update);
	}
	
	private static function inDir($path, $dir) {

		return (strpos(rtrim(self::fixPath($path), '/'), rtrim(self::fixPath($dir), '/')) !== false);
	}
	
	private static function dirName($path) {
	
		$arr_path = explode('/', self::fixPath($path));
			
		return $arr_path[count($arr_path)-2];
	}
	
	private static function fixPath($path) {
		
		return str_replace('\\', '/', $path);
	}
}
