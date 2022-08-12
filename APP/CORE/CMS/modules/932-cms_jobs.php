<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_SITE_JOBS', DB::$database_cms.'.site_jobs');
DB::setTable('TABLE_SITE_JOBS_TIMER', DB::$database_cms.'.site_jobs_timer');

class cms_jobs extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_jobs');
		static::$parent_label = getLabel('ttl_settings');
	}

	public function contents() {
			
		$is_running = self::isSchedulingJobs();

		$return = '<div class="section"><h1>'.self::$label.'</h1>
		<div class="cms_jobs">
		
			<form id="f:cms_jobs:update-0">
				
				<div class="dynamic" id="y:cms_jobs:refresh-0">
					'.$this->createTable().'
				</div>
			
				<menu>'
					.'<button type="button" class="msg'.(!$is_running ? ' hide' : '').'" id="y:cms_jobs:stop-0" title="'.getLabel('inf_jobs_stop').'"><span class="icon">'.getIcon('stop').'</span></button>'
					.'<button type="button" class="quick'.($is_running ? ' hide' : '').'" id="y:cms_jobs:run-0" title="'.getLabel('inf_jobs_run').'"><span class="icon">'.getIcon('play').'</span></button>'
					.'<input type="submit" value="'.getLabel('lbl_save').'" />'
				.'</menu>
			
			</form>
		
		</div></div>';
		
		return $return;
	}
	
	public static function createTable() {
	
		$arr_job_properties = getModuleConfiguration('jobProperties');
		$arr_jobs = self::getJobs();
		$arr_timer = self::getTimerOptions();
		$arr_timer_service = self::getTimerOptions('service');

		$return = '<table class="list">
			<thead>
				<tr>
					<th class="max"><span>'.getLabel('lbl_task').'</span></th>
					<th></th>
					<th><span>'.getLabel('lbl_timing').'</span></th>
					<th class="limit"><span>'.getLabel('lbl_executed').'</span></th>
					<th class="limit"><span>'.getLabel('lbl_running').'</span></th>
				</tr>
			</thead>
			<tbody>';
		
			foreach($arr_job_properties as $module => $methods) {
				foreach($methods as $method => $arr_options) {
					
					$arr_job = ($arr_jobs[$module][$method] ?? []);

					$seconds = 0;
					
					if (isset($arr_job['seconds'])) {
						
						$seconds = ($arr_job['seconds'] === -1 ? 'manual' : $arr_job['seconds']);
						if ($arr_options['service']) {
							$seconds = ($arr_job['seconds'] === 0 ? 'always' : $seconds);
						}
					}
					
					$return .= '<tr id="x:cms_jobs:job-'.$module.'.'.$method.'">
						<td>'.$arr_options['label'].'</td>
						<td><input type="hidden" id="y:cms_jobs:set_job_options-'.$module.'.'.$method.'" name="jobs['.$module.']['.$method.'][options]" value="'.(!empty($arr_job['options']) ? strEscapeHTML($arr_job['options']) : 'null').'" /><input type="button" class="data edit" value="edit" /></td>
						<td><select name="jobs['.$module.']['.$method.'][timer]">'.cms_general::createDropdown(($arr_options['service'] ? $arr_timer_service : $arr_timer), $seconds).'</select></td>
						<td>'.(!empty($arr_job['date_executed']) ? date('d-m-Y H:i:s', strtotime($arr_job['date_executed'])) : '<span class="icon" data-category="status">'.getIcon('min').'</span>').'</td>
						<td><span class="icon" data-category="status">'.getIcon((!empty($arr_job['running']) ? 'tick' : 'min')).'</span></td>
						<td>'.($arr_job && !$arr_job['running'] ? '<input type="button" class="data add quick run_job" value="run" />' : ($arr_job && $arr_options['service'] ? '<input type="button" class="data del msg stop_job" value="stop" />' : '')).'</td>
					</tr>';
				}
			}
		
		$return .= '</tbody>
		</table>';
			
		return $return;
	}
	
	public static function css() {
	
		$return = '.cms_jobs table.list th:first-child + th,
			.cms_jobs table.list td:first-child + td { padding-right: 4px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-cms_jobs', function(elm_scripter) {
			
			elm_scripter.find('[id^=f\\\:cms_jobs\\\:update]').data({'target': elm_scripter.find('.dynamic'), 'options': {'html': ''}});
			
			var elm_refresh = elm_scripter.find('[id^=y\\\:cms_jobs\\\:refresh]');
			var elm_toolbox = getContainerToolbox(elm_scripter);
			
			setInterval(function() {
			
				elm_refresh.quickCommand(function(html) {
				
					var elm_table = elm_scripter.find('table > tbody');
					
					$(html).find('> tbody > tr').each(function() {
					
						var cur = $(this);
						var target = elm_table.children('tr[id=\"'+cur.attr('id')+'\"]');
						
						if (target.length) {
						
							elm_tds = cur.children('td');
							
							var count = 2;
							target.children('td:gt('+count+')').each(function() {
							
								var elm_td = $(this);
								elm_td.replaceWith(elm_tds.eq(count+1));
								
								count++;
							});
						} else {
						
							elm_table.append(cur);
						}
					});
				});
				
				return true;
			}, 2000);
			
			elm_scripter.on('command', '[id=y\\\:cms_jobs\\\:stop-0], [id=y\\\:cms_jobs\\\:run-0]', function() {
			
				var cur = $(this);
				if (cur.is('[id=y\\\:cms_jobs\\\:stop-0]')) {
					var elm_target = elm_scripter.find('[id=y\\\:cms_jobs\\\:run-0]');
				} else {
					var elm_target = elm_scripter.find('[id=y\\\:cms_jobs\\\:stop-0]');
				}
			
				COMMANDS.setTarget(cur, function() {
					cur.addClass('hide');
					elm_target.removeClass('hide');
				});
			}).on('command', '.run_job, .stop_job', function() {
			
				var elm_target = $(this).closest('[id^=x\\\:cms_jobs\\\:job-]');
				
				COMMANDS.setTarget(elm_target[0], elm_scripter.find('.dynamic'));
				COMMANDS.setOptions(elm_target[0], {'html': ''});
			}).on('command', '.run_job', function() {
				
				LOADER.keepAlive(this);
			}).on('click', '[id^=y\\\:cms_jobs\\\:set_job_options] + .edit', function() {
			
				var elm_target = $(this).prev('input');
			
				COMMANDS.setTarget(elm_target[0], $(this).prev('input'));
				
				elm_target.popupCommand();
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
		
		if ($method == "refresh") {
			
			$is_running = self::isSchedulingJobs();
			
			if (!$is_running) {
				self::cleanupJobs();
			}

			$this->html = $this->createTable();
		}
		
		if ($method == "run") {
			
			self::scheduleJobs();
		}
		
		if ($method == "stop") {
			
			try {
				
				self::unscheduleJobs();
			} catch (Exception $e) {
				
				error('Process does not exist', TROUBLE_ERROR, LOG_BOTH, false, $e);
			}
		}
		
		if ($method == "run_job") {

			$id = explode('.', $id);

			self::runJob($id[0], $id[1]);
			
			$this->html = $this->createTable();
			$this->msg = true;
		}
		
		if ($method == "stop_job") {

			$id = explode('.', $id);
			$msg = 'Process does not exist';

			$arr_job = self::getJob($id[0], $id[1]);
			
			if (!$arr_job['process_id']) {
				error($msg);
			}
			
			$process = new Process();
			$process->setPID($arr_job['process_id']);
		
			if (!$process->status()) {
				error($msg);
			}
			
			$stopped = Mediator::stopAsync($id[0], $id[1], $arr_job['process_id']);
			
			if (!$stopped) {
				msg('Process closing.');
			} else {
				msg('Process stopped.');
			}
				
			$this->html = $this->createTable();
			$this->msg = true;
		}
		
		if ($method == "set_job_options") {
		
			$id = explode('.', $id);
			
			$arr_job_properties = getModuleConfiguration('jobProperties');
			$arr_options = $arr_job_properties[$id[0]][$id[1]];

			if (!$arr_options['options']) {
				
				$this->html = '<section class="info">'.getLabel('msg_no_options').'</section>';
			} else {
				
				$arr_settings = JSON2Value(strUnescapeHTML($value));
				
				$this->html = '<form id="frm-job-options" class="return_job_options">
					'.$arr_options['options']($arr_settings).'
				</form>';
			}
		}
		
		if ($method == "return_job_options") {
				
			$this->html = strEscapeHTML(value2JSON($_POST['options']));
		}
									
		// QUERY
		
		if ($method == "update") {
			
			self::handleJobs($_POST['jobs']);
			
			$this->html = $this->createTable();
			$this->msg = true;
		}
	}
	
	private static function getTimerOptions($type = 'job') {

		$arr = [];
		
		$arr[] = ['id' => 0, 'name' => ''];
		$arr[] = ['id' => 'manual', 'name' => getLabel('lbl_manual')];
		if ($type == 'service') {
			$arr[] = ['id' => 'always', 'name' => getLabel('lbl_always')];
		}
		$arr[] = ['id' => 1, 'name' => getLabel('unit_second')];
		$arr[] = ['id' => 5, 'name' => '5 '.getLabel('unit_second')];
		$arr[] = ['id' => 15, 'name' => '15 '.getLabel('unit_second')];
		$arr[] = ['id' => 30, 'name' => '30 '.getLabel('unit_second')];
		$arr[] = ['id' => 60, 'name' => getLabel('unit_minute')];
		$arr[] = ['id' => 5*60, 'name' => '5 '.getLabel('unit_minutes')];
		$arr[] = ['id' => 15*60, 'name' => '15 '.getLabel('unit_minutes')];
		$arr[] = ['id' => 30*60, 'name' => '30 '.getLabel('unit_minutes')];
		$arr[] = ['id' => 3600, 'name' => getLabel('unit_hour')];
		$arr[] = ['id' => 3600*12, 'name' => '12 '.getLabel('unit_hours')];
		$arr[] = ['id' => 86400, 'name' => getLabel('unit_day')];
		$arr[] = ['id' => 86400*7, 'name' => getLabel('unit_week')];
		$arr[] = ['id' => 86400*28, 'name' => getLabel('unit_month')];

		return $arr;
	}
	
	public static function getJob($module, $method) {
				
		$res = DB::query("SELECT j.*
				FROM ".DB::getTable('TABLE_SITE_JOBS')." j
			WHERE j.module = '".DBFunctions::strEscape($module)."' AND j.method = '".DBFunctions::strEscape($method)."'
		");
		
		$row = $res->fetchAssoc();
		
		if (!$row) {
			
			return false;
		} else {
		
			$arr = json_decode($row['options'], true);
			$arr['process_id'] = $row['process_id'];
			$arr['date_executed'] = [
				'previous' => $row['date_executed'],
				'now' => false
			];

			return $arr;
		}
	}
	
	private static function handleJobs($arr_jobs) {
		
		$arr_jobs_cur = [];

		foreach($arr_jobs as $module => $methods) {
			foreach($methods as $method => $arr_job) {
				
				if ((int)$arr_job['timer'] || $arr_job['timer'] == 'manual' || $arr_job['timer'] == 'always') {
					
					$str_options = strUnescapeHTML($arr_job['options']);
					$str_options = ($str_options == 'null' ? '' : $str_options);
					
					$timer = $arr_job['timer'];
					if ($timer == 'manual') {
						$timer = -1;
					}
					if ($timer == 'always') {
						$timer = 0;
					}
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_SITE_JOBS')."
						(module, method, seconds, options, date_executed)
							VALUES
						('".DBFunctions::strEscape($module)."', '".DBFunctions::strEscape($method)."', ".(int)$timer.", '".DBFunctions::strEscape($str_options)."', NOW())
						".DBFunctions::onConflict('module, method', ['seconds', 'options'])."
					");
									
					$arr_jobs_cur[] = $module.$method;
				}
			}
		}
		
		if ($arr_jobs) {

			$res = DB::query("DELETE FROM ".DB::getTable('TABLE_SITE_JOBS')."
				WHERE CONCAT(module, method) NOT IN ('".implode("','", $arr_jobs_cur)."')
			");
		}
	}

	private static function getJobs() {

		$arr = [];

		$res = DB::query("SELECT j.*
			FROM ".DB::getTable('TABLE_SITE_JOBS')." j
		");
					
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['running'] = DBFunctions::unescapeAs($arr_row['running'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['seconds'] = (int)$arr_row['seconds'];
			
			$arr[$arr_row['module']][$arr_row['method']] = $arr_row;
		}

		return $arr;
	}
	
	private static function isSchedulingJobs() {
		
		$res = DB::query("SELECT TRUE
				FROM ".DB::getTable('TABLE_SITE_JOBS_TIMER')." jt
		");
			
		$is_running = ($res->getRowCount() ? true : false);
		
		return $is_running;
	}
	
	private static function scheduleJobs() {
		
		$insert = DB::query("INSERT INTO ".DB::getTable('TABLE_SITE_JOBS_TIMER')."
			(date)
				VALUES
			(NOW())
			".DBFunctions::onConflict('unique_row', false, 'unique_row = TRUE')."
		");
	}
	
	private static function unscheduleJobs() {
		
		$res = DB::query("SELECT
			jt.date, jt.process_id
				FROM ".DB::getTable('TABLE_SITE_JOBS_TIMER')." jt
		");
		
		if (!$res->getRowCount()) {
			error();
		}
		
		$arr_job_timer = $res->fetchAssoc();
		
		if ($arr_job_timer['process_id']) {
		
			$process = new Process();
			$process->setPID($arr_job_timer['process_id']);
		
			if (!$process->status()) {
				error();
			}
			
			$stopped = Mediator::stopAsync('cms_jobs', 'runJobs', $arr_job_timer['process_id']);
			
			if (!$stopped) {
				msg('Process closing.');
			} else {
				msg('Process stopped.');
			}
		}
		
		DB::query("DELETE FROM ".DB::getTable('TABLE_SITE_JOBS_TIMER')."
			WHERE process_id = ".(int)$arr_job_timer['process_id']."
				OR process_id IS NULL
		");
	}
	
	public static function callJobs() {
		
		// Check if there are pending jobs (max every second), otherwise quit
		
		$res = DB::query("SELECT TRUE
				FROM ".DB::getTable('TABLE_SITE_JOBS')." j,
				".DB::getTable('TABLE_SITE_JOBS_TIMER')." jt
			WHERE j.seconds >= 0 AND j.date_executed < NOW()
				AND (jt.unique_row IS NOT NULL AND jt.date IS NULL OR jt.date < NOW())
			LIMIT 1
		");
		
		if (!$res->getRowCount()) {
			return;
		}
		
		// Try to claim the call jobs sequence
		
		$lock = Mediator::checkLock('jobs');
		
		if (!$lock) { // Already claimed
			return;
		}
		
		DB::setConnection(DB::CONNECT_CMS);
		
		$res = DB::query("SELECT
			jt.process_id
				FROM ".DB::getTable('TABLE_SITE_JOBS_TIMER')." jt
		");
		
		$arr_row = $res->fetchAssoc();
		$process_id = (int)$arr_row['process_id'];
	
		// Update job timer to allow for a new update run
		
		$insert = DB::query("UPDATE ".DB::getTable('TABLE_SITE_JOBS_TIMER')." SET
			date = NOW()
		");
		
		// Start a new job service if it does not exist
		
		if ($process_id) {
			
			$process = new Process();
			$process->setPID($process_id);
		
			if (!$process->status()) {
				$process_id = false;
			}
		}
		
		if (!$process_id) {
			
			// Start a new job service

			$process_id = Mediator::runAsync('cms_jobs', 'runJobs');
		}
					
		// Set the job timer's process_id
			
		DB::query("UPDATE ".DB::getTable('TABLE_SITE_JOBS_TIMER')." SET
			process_id = ".(int)$process_id
		);
		
		$lock = Mediator::removeLock('jobs');
								
		DB::setConnection();
	}
	
	// Automated and scheduled Jobs processing through CMS
	
	public static function runJobs() {
		
		$count = 0;
		
		while (true) {
			
			$count++;
				
			self::cleanupJobs();
									
			// Run jobs that are passed their excution interval and do not pass the timer (pending)
			
			$res = DB::query("SELECT j.*
					FROM ".DB::getTable('TABLE_SITE_JOBS')." j
				WHERE j.seconds >= 0
					AND (j.date_executed + ".DBFunctions::interval(1, 'SECOND', 'j.seconds').") < NOW()
					AND j.date_executed < (SELECT date FROM ".DB::getTable('TABLE_SITE_JOBS_TIMER').")
					AND j.running = FALSE
			");

			if ($res->getRowCount()) {
				
				while ($arr = $res->fetchAssoc()) {

					$module = $arr['module'];
					$method = $arr['method'];

					$res_check = DB::query("SELECT TRUE
							FROM".DB::getTable('TABLE_SITE_JOBS')." j
						WHERE j.module = '".$module."' AND j.method = '".$method."'
							AND j.seconds >= 0
							AND (j.date_executed + ".DBFunctions::interval(1, 'SECOND', 'j.seconds').") < NOW()
							AND j.date_executed < (SELECT date FROM ".DB::getTable('TABLE_SITE_JOBS_TIMER').")
							AND j.running = FALSE
					");
					
					if ($res_check->getRowCount()) {
						
						$arr_job_properties = getModuleConfiguration('jobProperties', true, DIR_CMS, $module);
						$is_service = $arr_job_properties[$method]['service'];
					
						if ($is_service) {
							
							$do = Mediator::setLock($module.$method, 'job');
		
							if ($do) {

								$update = DB::query("UPDATE ".DB::getTable('TABLE_SITE_JOBS')." SET 
										running = TRUE
									WHERE module = '".$module."' AND method = '".$method."'
										AND seconds >= 0
										AND (date_executed + ".DBFunctions::interval(1, 'SECOND', 'seconds').") < NOW()
										AND date_executed < (SELECT date FROM ".DB::getTable('TABLE_SITE_JOBS_TIMER').")
								");
								
								if ($update->getAffectedRowCount()) {
						
									$executed = self::executeJobService($module, $method);
								}
								
								Mediator::removeLock($module.$method);
							}
						} else {
					
							$executed = self::openJobTask($module, $method);
						}
					}
				}	
			}
			
			// Check if there could be pending jobs or services to be checked, run again until the timer has really passed, otherwise quit
			
			$res = DB::query("SELECT j.*
				FROM ".DB::getTable('TABLE_SITE_JOBS')." j
				WHERE 
					(j.seconds > 0 AND j.date_executed < (SELECT date FROM ".DB::getTable('TABLE_SITE_JOBS_TIMER')."))
						OR
					(j.seconds = 0 AND j.process_date < (SELECT date FROM ".DB::getTable('TABLE_SITE_JOBS_TIMER')."))
				LIMIT 1
			");
			
			if (!$res->getRowCount()) {
				break;
			}
			
			Mediator::runListeners('cleanup.program');
			
			if ($count % 100 == 0) { // Run system-related garbage collections

				// Cleanup PHP sessions
				session_start();
				session_gc();
				session_destroy();
			}
			
			Mediator::checkState();
			
			sleep(1);
		}
	}
	
	private static function openJobTask($module, $method) {
		
		$arr_job = self::getJob($module, $method);
		
		if (!$arr_job) {
			return false;
		}
		
		$process_id = Mediator::runAsync('cms_jobs', 'runJobTask', ['module' => $module, 'method' => $method, 'options' => $arr_job]);
		
		$res = DB::query("UPDATE ".DB::getTable('TABLE_SITE_JOBS')." SET
				process_id = ".(int)$process_id.",
				process_date = NOW()
			WHERE module = '".$module."' AND method = '".$method."'
		");
				
		return true;
	}
	
	public static function runJobTask($arr_module_method) {
		
		$module = $arr_module_method['module'];
		$method = $arr_module_method['method'];
		
		$do = Mediator::setLock($module.$method, 'job');
		
		if ($do) {
			
			$update = DB::query("UPDATE ".DB::getTable('TABLE_SITE_JOBS')." SET 
					running = TRUE
				WHERE module = '".$module."' AND method = '".$method."'
					AND seconds >= 0
					AND (date_executed + ".DBFunctions::interval(1, 'SECOND', 'seconds').") < NOW()
					AND date_executed < (SELECT date FROM ".DB::getTable('TABLE_SITE_JOBS_TIMER').")
			");
			
			if ($update->getAffectedRowCount()) {

				self::executeJobTask($arr_module_method);
			}
			
			Mediator::removeLock($module.$method);
		}
	}
	
	// Manual or programmatically called Job

	public static function runJob($module, $method, $key = false, $arr_job = []) {
				
		if (!$arr_job) {
			$arr_job = self::getJob($module, $method);
		}
		
		if (!$arr_job) {
			return false;
		}

		SiteStartVars::stopSession();

		$do = Mediator::setLock($module.$method, ($key ?: 'job'));
		
		if ($do) {
			
			self::cleanupJobs($module, $method);
			
			$arr_job_properties = getModuleConfiguration('jobProperties', true, DIR_CMS, $module);
			$is_service = $arr_job_properties[$method]['service'];
						
			DB::setConnection(DB::CONNECT_CMS);

			$update = DB::query("UPDATE ".DB::getTable('TABLE_SITE_JOBS')." SET 
					running = TRUE
				WHERE module = '".$module."' AND method = '".$method."'
			");

			if ($update->getAffectedRowCount()) {
				
				DB::setConnection();
				
				if ($is_service) {
					
					self::executeJobService($module, $method);
				} else {
					
					DB::setConnection(DB::CONNECT_CMS);
					
					$update = DB::query("UPDATE ".DB::getTable('TABLE_SITE_JOBS')." SET 
							process_date = NOW()
						WHERE module = '".$module."' AND method = '".$method."'
					");
					
					DB::setConnection();
					
					timeLimit(0);
					
					self::executeJobTask(['module' => $module, 'method' => $method, 'options' => $arr_job]);
				}
			}
			
			DB::setConnection();
			
			Mediator::removeLock($module.$method);
		}
		
		SiteStartVars::startSession();
	}
	
	// Actual execution of the Job
	
	private static function executeJobService($module, $method) {
		
		$arr_job = self::getJob($module, $method);
		
		if (!$arr_job) {
			return false;
		}
							
		$process_id = Mediator::runAsync($module, $method, $arr_job);
		
		DB::setConnection(DB::CONNECT_CMS);
		
		$res = DB::query("UPDATE ".DB::getTable('TABLE_SITE_JOBS')." SET
				date_executed = NOW(),
				process_id = ".(int)$process_id.",
				process_date = NOW()
			WHERE module = '".$module."' AND method = '".$method."'
		");

		DB::setConnection();
				
		return true;
	}
	
	public static function executeJobTask($arr_module_method) {
		
		$module = $arr_module_method['module'];
		$method = $arr_module_method['method'];
		$arr_job = $arr_module_method['options'];
				
		$func_update = function() use ($module, $method) {
			
			DB::setConnection(DB::CONNECT_CMS);
			
			DB::query("UPDATE ".DB::getTable('TABLE_SITE_JOBS')." SET 
					running = FALSE,
					process_id = NULL,
					process_date = NULL
				WHERE module = '".$module."' AND method = '".$method."'
			");
			
			DB::setConnection();
		};
		
		try {

			$float_time = microtime(true);

			// Make sure the Job does not run in this same second; this allows us to catch the true state of things for the current second as well
			time_sleep_until($float_time + 1);
			
			$arr_job['date_executed']['now'] = DBFunctions::str2Date((int)$float_time);
			
			$module::$method($arr_job);
		} catch (Exception $e) {
		
			$func_update();
			throw($e);
		}
		
		DB::setConnection(DB::CONNECT_CMS);
		
		DB::query("UPDATE ".DB::getTable('TABLE_SITE_JOBS')." SET
				date_executed = '".DBFunctions::str2Date($arr_job['date_executed']['now'])."'
			WHERE module = '".$module."' AND method = '".$method."'
		");
		
		DB::setConnection();
		
		$func_update();
	}
	
	// Cleaning
	
	private static function cleanupJobs($module = false, $method = false) {
		
		// Cleanup non-existing services (max every second)
				
		$res = DB::query("SELECT j.*
			FROM ".DB::getTable('TABLE_SITE_JOBS')." j
			WHERE j.running = TRUE
				AND j.process_date < NOW()
				".($module ? "AND j.module = '".$module."'" : "")."
				".($method ? "AND j.method = '".$method."'" : "")."
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			DB::setConnection(DB::CONNECT_CMS);
			
			$update = DB::query("UPDATE ".DB::getTable('TABLE_SITE_JOBS')." SET
					process_date = NOW()
				WHERE module = '".$arr_row['module']."' AND method = '".$arr_row['method']."'
			");
			
			if ($arr_row['process_id']) {
						
				$process = new Process();
				$process->setPID($arr_row['process_id']);
			
				if (!$process->status()) {
				
					$update = DB::query("UPDATE ".DB::getTable('TABLE_SITE_JOBS')." SET
							running = FALSE,
							process_id = NULL,
							process_date = NULL
						WHERE module = '".$arr_row['module']."' AND method = '".$arr_row['method']."'
					");
				}
			} else {
				
				if (Mediator::checkLock($arr_row['module'].$arr_row['method'])) { // Job was not locked
					
					$update = DB::query("UPDATE ".DB::getTable('TABLE_SITE_JOBS')." SET
							running = FALSE,
							process_id = NULL,
							process_date = NULL
						WHERE module = '".$arr_row['module']."' AND method = '".$arr_row['method']."'
					");
					
					Mediator::removeLock($arr_row['module'].$arr_row['method']);
				}
			}
			
			DB::setConnection();
		}
	}
}
