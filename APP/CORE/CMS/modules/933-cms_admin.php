<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
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
	
	public static function setupProperties() {
		
		return [
			'database_initialise' => [
				'label' => 'Initialise 1100CC database settings.',
				'run' => function() {

					(DB::getRealNamespace().'\DBSetup')::init();
				},
				'transform' => false
			]
		];
	}
	
	private static $arr_backup_files = [];
	
	public function contents() {

		$return = '<div class="section"><h1>'.self::$label.'</h1>
		<div class="cms_admin">';
		
			$arr_backup_details = self::getModuleBackupDetails();
			$arr_backup_selector = [];
			
			foreach ($arr_backup_details as $key => $value) {
				$arr_backup_selector[] = ['id' => $key, 'name' => $value['label']];
			}
			
			$arr_backup_files_filtered = $this->createBackupRestore();
			$str_name_first = current(array_keys($arr_backup_files_filtered));
			$arr_backup_files_filtered_name = $this->createBackupRestoreByName($str_name_first);
			$str_date_last = array_keys($arr_backup_files_filtered_name);
			$str_date_last = end($str_date_last);
		
			$return .= '<div class="tabs">
				<ul>
					<li><a href="#">'.getLabel('lbl_backup').'</a></li>
					<li><a href="#">'.getLabel('lbl_restore').'</a></li>
					'.($_SESSION['CORE'] ? '<li><a href="#">'.getLabel('lbl_update').' 1100CC</a></li>' : '').'
					'.($_SESSION['CORE'] ? '<li><a href="#">'.getLabel('lbl_setup').' 1100CC</a></li>' : '').'
				</ul>
				
				<div class="backup">
				
					<form id="f:cms_admin:backup-0">
						
						<div class="options">'
							.'<select name="backup[]" multiple="multiple"'.($arr_backup_selector ? ' size="'.count($arr_backup_selector).'"' : '').'>'.cms_general::createDropdown($arr_backup_selector).'</select>'
						.'</div>
						
						<menu><input title="'.getLabel('inf_backup_1100cc_download').'" name="download" type="submit" value="'.getLabel('lbl_download').'" /><input type="submit" value="'.getLabel('lbl_save').'" /></menu>
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
									.'<select name="name">'.cms_general::createDropdown($arr_backup_files_filtered, $str_name_first).'</select>'
									.'<select name="file" id="y:cms_admin:select_backup_restore-0">'.cms_general::createDropdown($arr_backup_files_filtered_name, $str_date_last).'</select>'
								.'</div>
								
								<menu><input type="submit" value="'.getLabel('lbl_save').'" /></menu>
							</form>
						</div>
							
						'.($_SESSION['CORE'] ? '<div>
							<form id="f:cms_admin:upload_package-0">
							
								<div class="options">
									'.cms_general::createFileBrowser().'
								</div>
								
								<menu><input type="submit" value="'.getLabel('lbl_upload').'" /></menu>
							</form>
						</div>' : '').'
						
					</div>
					
				</div>';
				
				if ($_SESSION['CORE']) {
					
					$return .= '<div class="update">
						
						'.$this->contentTabUpdate().'
						
					</div><div class="setup">
						
						'.$this->contentTabSetup().'
						
					</div>';
				}			
					
			$return .= '</div>';
				
		$return .= '</div></div>';
		
		return $return;
	}
	
	private function contentTabUpdate() {
				
		$return = '<form id="f:cms_admin:update-0">

			<div class="options"><dl>
				<div>
					<dt>'.getLabel('lbl_available').'</dt>
					<dd><span class="icon">'.getIcon((isPath(Settings::getUpdatePath()) ? 'tick' : 'min')).'</span></dd>
				</div>
			</dl></div>
			
			<menu><input type="submit" value="'.getLabel('lbl_update').'" /></menu>
			
		</form>';
		
		return $return;
	}
	
	private function contentTabSetup() {
				
		$return = '<form id="f:cms_admin:setup-0">

			<div class="options">
				<ul>';
				
				$arr_modules = getModuleConfiguration('setupProperties');
						
				foreach ($arr_modules as $module => $arr_settings) {
					foreach ($arr_settings as $str_name => $arr_setup) {
						
						$return .= '<li><span>'.$arr_setup['label'].'</span>'.($arr_setup['transform'] ? '<span>'.getLabel('lbl_transform').'</span>' : '').'</li>';
					}
				}
				
				$return .= '</ul>
				<p><label><input type="checkbox" name="transform" value="1" /><span>'.getLabel('lbl_transform').'</span></label></p>
			</div>
			
			<menu><input type="submit" value="'.getLabel('lbl_setup').'" /></menu>
			
		</form>';
		
		return $return;
	}
	
	private function createBackupRestore() {
	
		$arr_backup_files = self::getBackupFiles();
		$arr_backup_details = self::getModuleBackupDetails();
		
		$arr_backup_files_filtered = [];
		
		foreach (($arr_backup_files ?: []) as $key => $value) {
			$arr_backup_files_filtered[$key] = ['id' => $key, 'name' => $arr_backup_details[$key]['label']];
		}
		
		return $arr_backup_files_filtered;
	}
	
	private function createBackupRestoreByName($str_name) {
	
		$arr_backup_files = self::getBackupFiles();
		
		$arr_backup_files_filtered_name = [];
		
		foreach (($arr_backup_files[$str_name] ?? []) as $str_date => $str_filename) {
			$arr_backup_files_filtered_name[$str_date] = ['id' => $str_filename, 'name' => $str_date];
		}
		
		return $arr_backup_files_filtered_name;
	}
		
	public static function css() {
	
		$return = '
			.cms_admin form { text-align: center; }
			.cms_admin .tabs > .backup select { display: block; margin: 0px auto 8px auto; }
			.cms_admin .tabs > .update dl { display: inline-table; margin-left: auto; margin-right: auto; }
			.cms_admin .tabs > .setup form > div { text-align: left; }
			.cms_admin .tabs > .setup ul { list-style-type: disc; list-style-position: inside; }
			.cms_admin .tabs > .setup ul > li > span + span::before { content: \'[\'; }
			.cms_admin .tabs > .setup ul > li > span + span::after { content: \']\'; }
			.cms_admin .tabs > .setup ul > li > span + span { font-size: 0.85em; font-weight: bold; margin-left: 0.1em; text-transform: lowercase; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-cms_admin', function(elm_scripter) {
		
			elm_scripter.on('change', '.tabs > .backup-restore [name=name]', function() {
				
				const elm_target = $(this).next('[name=file]');
				
				COMMANDS.setData(elm_target, {'name': $(this).val()});
				COMMANDS.quickCommand(elm_target, elm_target);
			})
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
		
		if ($method == 'upload_package' && $_SESSION['CORE']) {
			
			$str_path = Settings::get('path_temporary').'exchangepackage.1100CC';
			
			if ($this->is_confirm === null) {
				
				if (!$_FILES['file']) {
					error(getLabel('msg_missing_information'));
				}
				
				try {

					$store_file = new FileStore($_FILES['file'], ['path' => $str_path, 'overwrite' => true]);
					$store_file->rename($str_path); // Make sure the extension matches
					
					$package = new ExchangePackage($str_path, true);
				
					$html = '<pre>'.$package->read1100CCText().'</pre>';
				} catch (Exception $e) {
					
					if (isPath($str_path)) {
						unlink($str_path);
					}
			
					Labels::setVariable('type', '1100CC');
					error(getLabel('msg_invalid_file_type_specific'));
				}
								
				$this->html = $html;
				$this->do_confirm = ['file' => false]; // Unset file upload
			} else if ($this->is_confirm) {
				
				if (!isPath($str_path)) {
					error(getLabel('msg_missing_information'));
				}
				
				timeLimit(false);
				memoryBoost(1000);
				
				$package = new ExchangePackage($str_path, true);
				$package->upload();
				
				unlink($str_path);
				
				msg('1100CC data package (upload) has successfully been processed.');
				
				$this->message = true;
			} else if ($this->is_confirm === false) {
				
				if (isPath($str_path)) {
					unlink($str_path);
				}
			}
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
		
			if (!$do_download || ($do_download && $this->is_download)) {
				
				timeLimit(false);

				self::runBackup($arr_backup_identifiers, $do_download);
			
				if ($do_download) {
					exit;
				}
				
				$this->message = true;			
			} else if ($do_download) {
				
				$this->do_download = true;
			}
		}
		
		if ($method == 'select_backup_restore') {
						
			$arr_backup_files_filtered_name = $this->createBackupRestoreByName($value['name']);
			
			$str_date_last = array_keys($arr_backup_files_filtered_name);
			$str_date_last = end($str_date_last);
			
			$this->html = cms_general::createDropdown($arr_backup_files_filtered_name, $str_date_last);
		}
				
		if ($method == 'backup_restore' && $this->is_confirm !== false) {
			
			if (!$_POST['name'] || !$_POST['file']) {
				error(getLabel('msg_missing_information'));
			}
			
			$arr_backup_details = self::getModuleBackupDetails();
			$arr_file = self::parseBackupFilename($_POST['file']);
			
			if (!$arr_file || !$arr_backup_details[$arr_file['name']]) {
				error(getLabel('msg_missing_information'));
			}
				
			if (!$this->is_confirm) {

				Labels::setVariable('name', $arr_backup_details[$arr_file['name']]['label']);
				Labels::setVariable('date',  $arr_file['date']);
							
				$this->html = getLabel('conf_backup_1100cc_restore');
				$this->do_confirm = true;
			} else {
								
				timeLimit(500);
				
				$package = new ExchangePackage($_POST['file'], true);
				$package->upload();
				
				msg('1100CC data package (restore) has successfully been processed.');
				
				$this->message = true;
			}
		}
						
		if ($method == "update" && $_SESSION['CORE']) {
			
			timeLimit(false);
			
			self::runUpdate();
			
			msg('1100CC has successfully been updated.');
			
			$this->message = true;
			$this->html = $this->contentTabUpdate();
		}
		
		if ($method == "setup" && $_SESSION['CORE']) {
			
			if ($this->is_confirm === null) {
				
				//Labels::setVariable('what', $str_what);
				$this->html = getLabel('conf_setup_1100cc');
				$this->do_confirm = true;
				return;
			} else if ($this->is_confirm) {
			
				timeLimit(false);
				
				self::runSetup((bool)$_POST['transform']);
				
				msg('1100CC has successfully been setup.');
				
				$this->message = true;
			}
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
				
				$arr_backup_details[$database] = ['label' => getLabel('lbl_unknown').' - '.$database, 'database' => $database, 'tables' => $arr_tables, 'download' => false];
			}
		}

		return $arr_backup_details;
	}
	
	private static function getBackupFiles() {
	
		if (self::$arr_backup_files) {
			return self::$arr_backup_files;
		}
		
		$arr_backup_details = self::getModuleBackupDetails();
		$str_path_backup = DIR_ROOT_STORAGE.DIR_HOME.DIR_CMS.DIR_PRIVATE.DIR_BACKUP;
		
		self::$arr_backup_files = [];
		
		FileStore::makeDirectoryTree($str_path_backup);
		
		foreach (new DirectoryIterator($str_path_backup) as $file_info) {
			
			$str_filename = $file_info->getFilename();
			$arr_file = self::parseBackupFilename($str_filename);
			
			if ($arr_file && $arr_backup_details[$arr_file['name']]) {
				
				self::$arr_backup_files[$arr_file['name']][$arr_file['date']] = $str_filename;
			}
		}
		
		self::$arr_backup_files = arrSortKeysRecursive(self::$arr_backup_files);
		
		return self::$arr_backup_files;
	}
	
	private static function parseBackupFilename($str_filename) {
		
		preg_match('/^database-(.*)-(\d.*)\.zip$/', $str_filename, $arr_match);
		
		if (!$arr_match) {
			return false;
		}
		
		$str_name = $arr_match[1];
		$str_date = date('Y-m-d H:i:s', $arr_match[2]);
				
		return ['name' => $str_name, 'date' => $str_date];
	}
	
	private static function runBackup($arr_backup_identifiers, $do_download = false) {
		
		$arr_backup_details = self::getModuleBackupDetails();
		
		$arr_database_tables = [];

		foreach ($arr_backup_identifiers as $str_identifier) {
			
			$database = $arr_backup_details[$str_identifier]['database'];
			
			$res = DB::query("SELECT
				TABLE_NAME AS name
					FROM information_schema.TABLES
				WHERE TABLE_SCHEMA = '".DBFunctions::strEscape($database)."'
			");
			
			while ($arr_row = $res->fetchAssoc()) {
			
				$arr_database_tables[$database][] = ['name' => $arr_row['name'], 'create' => true, 'truncate' => true];
			}
			
			if (!$do_download) { // Store one database per archive
			
				$str_path = DIR_ROOT_STORAGE.DIR_HOME.DIR_CMS.DIR_PRIVATE.DIR_BACKUP.'database-'.$str_identifier.'-'.time().'.zip';
				
				$package = new ExchangePackage($str_path);
				$package->create(['sql' => ['database_tables' => $arr_database_tables]]);
				
				$arr_database_tables = [];
			}
		}
		
		if ($do_download) { // Download all databases together (when applicable)
			
			$package = new ExchangePackage();
			$arr_file = $package->create(['sql' => ['database_tables' => $arr_database_tables]]);
						
			FileStore::readFile($arr_file['path'], $arr_file['filename'], true);
		}
	}
		
	private static function runUpdate() {
		
		$str_path_update = Settings::getUpdatePath();
		
		if (!isPath($str_path_update)) {
			error(getLabel('msg_not_available'));
		}
				
		try {
			
			require($str_path_update);		
		} catch (Exception $e) {
			
			error('1100CC Update Failed.', TROUBLE_ERROR, LOG_BOTH, null, $e);
		}
		
		$str_directory_update = rtrim(Settings::getUpdatePath(false), '/');
		
		FileStore::deleteDirectoryTree($str_directory_update);
	}
	
	private static function runSetup($do_transform = false) {
		
		$arr_modules = getModuleConfiguration('setupProperties');
		$has_error = false;
				
		foreach ($arr_modules as $module => $arr_settings) {
			foreach ($arr_settings as $str_name => $arr_setup) {
			
				if (!$arr_setup['run'] || ($arr_setup['transform'] && !$do_transform)) {
					continue;
				}
				
				try {
				
					$func_run = $arr_setup['run'];
					$func_run();
				} catch (Exception $e) {
					
					error('1100CC Setup ERROR: '.$str_name, TROUBLE_NOTICE, LOG_SYSTEM, null, $e);
					$has_error = true;
				}
			}
		}
		
		if ($has_error) {
			error('1100CC Setup Failed.', TROUBLE_ERROR, LOG_BOTH);
		}
	}
}
