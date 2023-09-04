<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

DB::setTable('TABLE_SITE_DETAILS', DB::$database_cms.'.site_details');
DB::setTable('TABLE_SITE_DETAILS_CUSTOM', DB::$database_cms.'.site_details_custom');
DB::setTable('TABLE_SITE_DETAILS_SERVERS', DB::$database_cms.'.site_details_servers');
DB::setTable('TABLE_SITE_DETAILS_HOSTS', DB::$database_cms.'.site_details_hosts');
DB::setTable('SITE_CACHE_FILES', DB::$database_home.'.site_cache_files');

Settings::set('arr_server_files_paths', [
	DIR_STORAGE.DIR_HOME => DIR_STORAGE,
	DIR_CACHE.DIR_HOME => DIR_CACHE,
	DIR_HOME.DIR_CSS => DIR_STORAGE.DIR_CSS
]);

class cms_details extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_details');
		static::$parent_label = getLabel('ttl_settings');
	}
	
	public static function jobProperties() {
		return [
			'cleanCache' => [
				'label' => getLabel('lbl_cleanup_cache'),
				'options' => function($arr_options) {
					return '<label>'.getLabel('lbl_age').'</label><input type="text" name="options[age_amount]" value="'.$arr_options['age_amount'].'" /><select name="options[age_unit]">'.cms_general::createDropdown(cms_general::getTimeUnits(), $arr_options['age_unit']).'</select>';
				},
			],
			'clearStaticServerFiles' => [
				'label' => getLabel('lbl_static_server_file_clear')
			],
			'runWebService' => [
				'label' => getLabel('lbl_run_web_service'),
				'service' => true,
				'options' => function($arr_options) {
					return '<fieldset><ul>
						<li><label>'.getLabel('lbl_server_host_port').'</label><input type="text" name="options[port]" value="'.$arr_options['port'].'" /> <span>(+1 SSL)</span></li>
						<li><label>'.getLabel('lbl_verbose').'</label><input type="checkbox" name="options[verbose]"'.($arr_options['verbose'] ? ' checked="checked"' : '').'" /></li>
					</ul></fieldset>';
				},
			],
			'generateSitemap' => [
				'label' => getLabel('lbl_generate_sitemap'),
				'options' => function($arr_options) {
					return '<fieldset><ul>
						<li><label>'.getLabel('lbl_host_name').'</label><input type="text" name="options[host_name]" value="'.strEscapeHTML($arr_options['host_name']).'" placeholder="'.SERVER_NAME_1100CC.'" /></li>
					</ul></fieldset>';
				}
			],
		];
	}

	public function contents() {
	
		$return = '<div class="section"><h1>'.self::$label.'</h1>
		<div class="details">';
		
			$arr = self::getSiteDetails();
			$arr_custom = self::getSiteDetailsCustom();
			$arr_hosts = self::getSiteDetailsHosts();
			$arr_servers = self::getSiteDetailsServers();

			$return .= '<div id="tabs-details">
				<ul>
					<li><a href="#">'.getLabel('lbl_general').'</a></li>
					<li><a href="#">'.getLabel('lbl_technical').'</a></li>
					<li><a href="#">'.getLabel('lbl_appearance').'</a></li>
					<li><a href="#">'.getLabel('lbl_script').'</a></li>
				</ul>
				<div>
					<form id="f:cms_details:update-general">
						
						<div class="fieldsets options"><div>
							
							<fieldset><legend>Info</legend><ul>
								<li>
									<label>'.getLabel('lbl_name').'</label>
									<div><input type="text" name="details[name]" value="'.strEscapeHTML($arr['name']).'" /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_address').'</label>
									<div><input type="text" name="details[address]" value="'.strEscapeHTML($arr['address']).'" /><input type="text" name="details[address_nr]" value="'.strEscapeHTML($arr['address_nr']).'" /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_zipcode').'</label>
									<div><input type="text" name="details[zipcode]" value="'.strEscapeHTML($arr['zipcode']).'" /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_city').'</label>
									<div><input type="text" name="details[city]" value="'.strEscapeHTML($arr['city']).'" /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_country').'</label>
									<div><input type="text" name="details[country]" value="'.strEscapeHTML($arr['country']).'" /></div>
								</li>
							</ul></fieldset>
						
							<fieldset><legend>Info</legend><ul>
								<li>
									<label>'.getLabel('lbl_email').'</label>
									<div><input type="text" name="details[email]" value="'.strEscapeHTML($arr['email']).'" /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_phone').'</label>
									<div><input type="text" name="details[tel]" value="'.strEscapeHTML($arr['tel']).'" /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_fax').'</label>
									<div><input type="text" name="details[fax]" value="'.strEscapeHTML($arr['fax']).'" /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_bank').'</label>
									<div><input type="text" name="details[bank]" value="'.strEscapeHTML($arr['bank']).'" /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_bank_nr').'</label>
									<div><input type="text" name="details[bank_nr]" value="'.strEscapeHTML($arr['bank_nr']).'" /></div>
								</li>
							</ul></fieldset>
						
							<fieldset><legend>Meta</legend><ul>
								<li>
									<label>'.getLabel('lbl_title').'</label>
									<div><input type="text" name="details[title]" value="'.strEscapeHTML($arr['title']).'" /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_description').'</label>
									<div><textarea name="details[description]" />'.strEscapeHTML($arr['description']).'</textarea></div>
								</li>
								<li>
									<label></label>
									<div><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span></div>
								</li>
								<li>
									<label>'.getLabel('lbl_head_tags').'</label>
									<div>';
										$arr_sorter = [];
										$arr_tags = preg_replace("/><(?=[^\/])/", ">>,<<", $arr['head_tags']);
										$arr_tags = explode(">,<", $arr_tags);
										foreach ($arr_tags as $value) {
											$arr_sorter[] = ['value' => '<input type="text" name="details[head_tags][]" value="'.strEscapeHTML($value).'" />'];
										}
										$return .= cms_general::createSorter($arr_sorter, false);
									$return .= '</div>
								</li>
								<li>
									<label>'.getLabel('lbl_html').'</label>
									<div><textarea name="details[html]" />'.strEscapeHTML($arr['html']).'</textarea></div>
								</li>
							</ul></fieldset>
							
							<fieldset><legend>Mail</legend><ul>
								<li>
									<label>'.getLabel('lbl_email_header').'</label>
									<div><textarea name="details[email_header]" />'.strEscapeHTML($arr['email_header']).'</textarea></div>
								</li>
								<li>
									<label>'.getLabel('lbl_email_footer').'</label>
									<div><textarea name="details[email_footer]" />'.strEscapeHTML($arr['email_footer']).'</textarea></div>
								</li>
							</ul></fieldset>
						
							<fieldset><legend>Services</legend><ul>
								<li>
									<label>'.getLabel('lbl_analytics_account').'</label>
									<div><input type="text" name="details[analytics_account]" value="'.strEscapeHTML($arr['analytics_account']).'" /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_facebook').'</label>
									<div><input type="text" name="details[facebook]" value="'.strEscapeHTML($arr['facebook']).'" /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_twitter').'</label>
									<div><input type="text" name="details[twitter]" value="'.strEscapeHTML($arr['twitter']).'" /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_youtube').'</label>
									<div><input type="text" name="details[youtube]" value="'.strEscapeHTML($arr['youtube']).'" /></div>
								</li>
								<li>
									<label></label>
									<div><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span></div>
								</li>
								<li>
									<label>'.getLabel('lbl_custom').'</label>
									<div>';
									
										$arr_sorter = [];
										
										foreach (($arr_custom ?: [[]]) as $value) {
											
											$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
											$arr_sorter[] = ['value' => '<input type="text" name="details_custom['.$unique.'][name]" value="'.strEscapeHTML($value['name']).'" title="'.getLabel('lbl_name').'" /><input type="text" name="details_custom['.$unique.'][value]" value="'.strEscapeHTML($value['value']).'" />'];
										}
										
										$return .= cms_general::createSorter($arr_sorter, false);
									$return .= '</div>
								</li>
							</ul></fieldset>
						
						</div></div>
						
						<menu><input type="submit" value="'.getLabel('lbl_save').'" /></menu>
					</form>				
				</div>
				<div>
					<form id="f:cms_details:update-technical">
						
						<div class="fieldsets options"><div>
							
							<fieldset><legend>Hosts</legend><ul>
								<li>
									<label></label>
									<div><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span></div>
								</li>
								<li>
									<label>'.getLabel('lbl_server_hosts').'</label>
									<div>';
										$arr_sorter = [];
										
										foreach (($arr_hosts ?: [[]]) as $arr_host) {
											
											$arr_sorter[] = ['value' => '<input type="text" name="details_hosts[]" value="'.strEscapeHTML($arr_host['name']).'" />'];
										}
										
										$return .= cms_general::createSorter($arr_sorter, false);
									$return .= '</div>
								</li>
							</ul></fieldset>
							
							<fieldset><legend>Mail</legend><ul>
								<li>
									<label>'.getLabel('lbl_email_1100cc').'</label>
									<div><input type="text" name="details[email_1100cc]" value="'.strEscapeHTML($arr['email_1100cc']).'" /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_email_1100cc_host').'</label>
									<div><input type="text" name="details[email_1100cc_host]" value="'.strEscapeHTML($arr['email_1100cc_host']).'" /><span>(e.g. imap.gmail.com:993/imap/ssl)</span></div>
								</li>
								<li>
									<label>'.getLabel('lbl_email_1100cc_password').'</label>
									<div><input type="password" name="details[email_1100cc_password]" value="'.strEscapeHTML($arr['email_1100cc_password']).'" /></div>
								</li>
							</ul></fieldset>
						
							<fieldset><legend>Infrastructure</legend><ul>
								<li>
									<label>'.getLabel('lbl_caching').'</label>
									<div><input type="checkbox" name="details[caching]" value="1"'.($arr['caching'] ? ' checked="checked"' : '').' /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_caching_external').'</label>
									<div><input type="checkbox" name="details[caching_external]" value="1"'.($arr['caching_external'] ? ' checked="checked"' : '').' /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_logging').'</label>
									<div><input type="checkbox" name="details[logging]" value="1"'.($arr['logging'] ? ' checked="checked"' : '').' /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_request_throttle').'</label>
									<div><input type="checkbox" name="details[throttle]" value="1"'.($arr['throttle'] ? ' checked="checked"' : '').' /></div>
								</li>
								<li>
									<label>HTTPS</label>
									<div><input type="checkbox" name="details[https]" value="1"'.($arr['https'] ? ' checked="checked"' : '').' /></div>
								</li>
							</ul></fieldset>
							
							<fieldset><legend>Trouble</legend><ul>
								<li>
									<label>'.getLabel('lbl_show_404').'</label>
									<div><input type="checkbox" name="details[show_404]" value="1"'.($arr['show_404'] ? ' checked="checked"' : '').' /></div>
								</li>
								<li>
									<label>'.getLabel('lbl_show_system_errors').'</label>
									<div><input type="checkbox" name="details[show_system_errors]" value="1"'.($arr['show_system_errors'] ? ' checked="checked"' : '').' /></div>
								</li>
							</ul></fieldset>
							
							<fieldset><legend>Servers</legend><ul>
								<li>
									<label>'.getLabel('lbl_use_servers').'</label>
									<div><input type="checkbox" name="details[use_servers]" value="1"'.($arr['use_servers'] ? ' checked="checked"' : '').' /></div>
								</li>
								<li>
									<label></label>
									<div><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span></div>
								</li>
								<li>
									<label>'.getLabel('lbl_servers').'</label>
									<div>';
									
										$arr_sorter = [];
										
										foreach ($arr_servers ?: [[[]]] as $arr_server_type) {
											
											foreach ($arr_server_type as $value) {
												
												$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
												$arr_sorter[] = ['value' => 
													'<input type="text" name="details_servers['.$unique.'][host_name]" value="'.strEscapeHTML($value['host_name']).'" title="'.getLabel('lbl_server_host').'" />
													<select name="details_servers['.$unique.'][host_type]">'.cms_general::createDropdown(self::getServerTypes(), $value['host_type'], false, 'label').'</select>
													<input type="text" name="details_servers['.$unique.'][host_port]" value="'.strEscapeHTML($value['host_port']).'" title="'.getLabel('lbl_server_host_port').'" />
													<select name="details_servers['.$unique.'][login_type]">'.cms_general::createDropdown(self::getServerLoginTypes(), $value['login_type'], false, 'label').'</select>
													<input type="text" name="details_servers['.$unique.'][login_name]" value="'.strEscapeHTML($value['login_name']).'" title="'.getLabel('lbl_login').'" />
													<textarea name="details_servers['.$unique.'][passkey]" title="'.getLabel('lbl_passkey').'">'.strEscapeHTML($value['passkey']).'</textarea>
													<input type="text" name="details_servers['.$unique.'][extra]" value="'.strEscapeHTML($value['extra']).'" title="'.getLabel('lbl_extra').'" />'
												];
											}
										}
										$return .= cms_general::createSorter($arr_sorter, false);
									$return .= '</div>
								</li>
							</ul></fieldset>
							
						</div></div>
						
						<menu><input type="submit" value="'.getLabel('lbl_save').'" /></menu>
					</form>
				</div>
				<div class="appearance">
				
					'.$this->contentTabAppearance().'

				</div>
				<div>
					<form id="f:cms_details:update_script-0">
					
						<div class="fieldsets options"><div>
					
							<fieldset><ul>
								<li>
									<label>'.getLabel('lbl_script').'</label>
									<div>';
									
										
										if (isPath(DIR_ROOT_SITE.'js/home.js')) {
											
											try {
												$str_script = file_get_contents(DIR_ROOT_SITE.'js/home.js');
											} catch (Exception $e) {
												$str_script = '';
											}
										}
										
										$return .= '<textarea name="script">'.strEscapeHTML($str_script).'</textarea>
									</div>
								</li>
							</ul></fieldset>
						
						</div></div>
					
						<menu><input type="submit" value="'.getLabel('lbl_save').'" /></menu>
					</form>
				</div>
			</div>';
		
		$return .= '</div></div>';
		
		return $return;
	}
	
	private static function contentTabAppearance() {
		
		$return = '<form id="f:cms_details:update_appearance-0">
		
			<div class="fieldsets options"><div>

				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_icon').'</label>
						<div>'.cms_general::createFileBrowser().(isPath(DIR_ROOT_SITE.'css/image.png') ? '<img src="'.URL_BASE_HOME.'css/image.png" />' : '').'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_theme').'</label>
						<div>';
														
							if (isPath(DIR_ROOT_SITE.'css/theme.json')) {
								
								try {
									$arr_theme = file_get_contents(DIR_ROOT_SITE.'css/theme.json');
									$arr_theme = json_decode($arr_theme, true);
								} catch (Exception $e) {
									$arr_theme = false;
								}
							}
							
							if (!$arr_theme) {
									
								$arr_theme = [
									'theme_color' => '',
									'background_color' => ''
								];
							}
							
							$str_theme = value2JSON($arr_theme, JSON_PRETTY_PRINT);
							
							$return .= '<textarea name="theme">'.strEscapeHTML($str_theme).'</textarea>
						</div>
					</li>
					<li>
						<label>'.getLabel('lbl_css').'</label>
						<div>';
							
							$str_style = '';
							
							if (isPath(DIR_ROOT_SITE.'css/home.css')) {
							
								try {
									$str_style = file_get_contents(DIR_ROOT_SITE.'css/home.css');
								} catch (Exception $e) {
									$str_style = '';
								}
							}
							
							$return .= '<textarea name="style">'.strEscapeHTML($str_style).'</textarea>
						</div>
					</li>
				</ul></fieldset>
				
			</div></div>
		
			<menu><input type="submit" value="'.getLabel('lbl_save').'" /></menu>
		</form>';
			
		return $return;
	}
	
	public static function css() {
	
		$return = '.details {}
					.details form fieldset div input + span { display: block; font-size: 11px; margin-top: 2px; }
					.details input[name=details\[address\]] { width: 115px; }
					.details input[name=details\[address_nr\]] { margin-left: 5px; width: 30px; }
					.details input[name^=details\[head_tags\]] { width: 300px; }
					.details input[name$=\[host_port\]] { width: 30px; }
					.details input[name$=\[login_name\]] { width: 80px; }
					.details textarea[name$=\[passkey\]] { width: 150px; height: 40px; }
					.details input[name^=details_custom]:first-child { width: 100px; }

					.details .appearance fieldset div > img,
					.details .appearance fieldset div > .filebrowse { display: inline-block; vertical-align: middle; }
					.details .appearance fieldset div > img { margin-left: 8px; max-height: 75px; }
					.details textarea[name=theme] { width: 300px; height: 100px; }
					.details textarea[name=style],
					.details textarea[name=script] { width: 700px; height: 500px; }
					';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('#mod-cms_details', function(elm_scripter) {
			
			elm_scripter.on('click', 'fieldset div .add', function() {
				$(this).closest('li').next('li').find('.sorter').sorter('addRow');
			}).on('click', 'fieldset div .del', function() {
				$(this).closest('li').next('li').find('.sorter').sorter('clean');
			}).on('ajaxsubmit', '[id^=f\\\:cms_details\\\:update-]', function() {
				$(this).find('input:checkbox:not(:checked)').each(function() {
					$(this).after('<input type=\"hidden\" value=\"\" class=\"temp\" name=\"'+$(this).attr('name')+'\" />')
				});
				$(this).find(':disabled').prop('disabled', false).attr('data-disabled', true);
			}).on('ajaxsubmitted', '[id^=f\\\:cms_details\\\:update-]', function() {
				$(this).find('input:hidden.temp').remove();
				$(this).find('[data-disabled]').prop('disabled', true).removeAttr('data-disabled');
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
											
		// QUERY
		
		if ($method == "update") {
			
			// Details
			
			foreach ($_POST['details'] as &$value) {
				if (is_array($value)) {
					$value = implode('', $value);
				}
			}
			unset($value);
			
			$arr_update_details = [
				'name', 'address', 'address_nr', 'zipcode', 'city', 'country', 'tel', 'fax', 'bank', 'bank_nr', 'email', 'title', 'description', 'head_tags', 'html', 'email_header', 'email_footer', 'analytics_account', 'facebook', 'twitter', 'youtube',
				'email_1100cc', 'email_1100cc_host', 'email_1100cc_password', 'caching', 'caching_external', 'logging', 'throttle', 'https', 'show_system_errors', 'show_404', 'use_servers'
			];
			
			foreach ($arr_update_details as $key => $value) {
				
				if ($_POST['details'][$value] !== null) {
					continue;
				}
				
				unset($arr_update_details[$key]);
			}
			
			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_SITE_DETAILS')."
					(
						name, address, address_nr, zipcode, city, country, tel, fax, bank, bank_nr, email, title, description, head_tags, html, email_header, email_footer, analytics_account, facebook, twitter, youtube,
						email_1100cc, email_1100cc_host, email_1100cc_password, caching, caching_external, logging, throttle, https, show_system_errors, show_404, use_servers
					)
						VALUES
					(
						'".DBFunctions::strEscape($_POST['details']['name'])."',
						'".DBFunctions::strEscape($_POST['details']['address'])."',
						'".DBFunctions::strEscape($_POST['details']['address_nr'])."',
						'".DBFunctions::strEscape($_POST['details']['zipcode'])."',
						'".DBFunctions::strEscape($_POST['details']['city'])."',
						'".DBFunctions::strEscape($_POST['details']['country'])."',
						'".DBFunctions::strEscape($_POST['details']['tel'])."',
						'".DBFunctions::strEscape($_POST['details']['fax'])."',
						'".DBFunctions::strEscape($_POST['details']['bank'])."',
						'".DBFunctions::strEscape($_POST['details']['bank_nr'])."',
						'".DBFunctions::strEscape($_POST['details']['email'])."',
						'".DBFunctions::strEscape($_POST['details']['title'])."',
						'".DBFunctions::strEscape($_POST['details']['description'])."',
						'".DBFunctions::strEscape($_POST['details']['head_tags'])."',
						'".DBFunctions::strEscape($_POST['details']['html'])."',
						'".DBFunctions::strEscape($_POST['details']['email_header'])."',
						'".DBFunctions::strEscape($_POST['details']['email_footer'])."',
						'".DBFunctions::strEscape($_POST['details']['analytics_account'])."',
						'".DBFunctions::strEscape($_POST['details']['facebook'])."',
						'".DBFunctions::strEscape($_POST['details']['twitter'])."',
						'".DBFunctions::strEscape($_POST['details']['youtube'])."',
						'".DBFunctions::strEscape($_POST['details']['email_1100cc'])."',
						'".DBFunctions::strEscape($_POST['details']['email_1100cc_host'])."',
						'".DBFunctions::strEscape($_POST['details']['email_1100cc_password'])."',
						".DBFunctions::escapeAs($_POST['details']['caching'], DBFunctions::TYPE_BOOLEAN).",
						".DBFunctions::escapeAs($_POST['details']['caching_external'], DBFunctions::TYPE_BOOLEAN).",
						".DBFunctions::escapeAs($_POST['details']['logging'], DBFunctions::TYPE_BOOLEAN).",
						".DBFunctions::escapeAs($_POST['details']['throttle'], DBFunctions::TYPE_BOOLEAN).",
						".DBFunctions::escapeAs($_POST['details']['https'], DBFunctions::TYPE_BOOLEAN).",
						".DBFunctions::escapeAs($_POST['details']['show_system_errors'], DBFunctions::TYPE_BOOLEAN).",
						".DBFunctions::escapeAs($_POST['details']['show_404'], DBFunctions::TYPE_BOOLEAN).",
						".DBFunctions::escapeAs($_POST['details']['use_servers'], DBFunctions::TYPE_BOOLEAN)."
					)
				".DBFunctions::onConflict('unique_row', $arr_update_details)."
			");
			
			// Details Custom
			
			if (isset($_POST['details_custom'])) {
				
				$arr_details_custom = [];
				
				foreach ((array)$_POST['details_custom'] as $arr_details) {
					
					if (!str2Label($arr_details['name']) || !$arr_details['value']) {
						continue;
					}
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_SITE_DETAILS_CUSTOM')."
						(name, value)
							VALUES
						('".str2Label($arr_details['name'])."', '".DBFunctions::strEscape($arr_details['value'])."')
						".DBFunctions::onConflict('name', ['name'])."
					");
					
					$arr_details_custom[] = $arr_details['name'];
				}
				
				$res = DB::query("DELETE
						FROM ".DB::getTable('TABLE_SITE_DETAILS_CUSTOM')."
					WHERE name NOT IN ('".implode("','", DBFunctions::arrEscape($arr_details_custom))."')
				");
			}
			
			// Details hosts
						
			if (isset($_POST['details_hosts'])) {
				
				$arr_details_hosts = [];
								
				foreach ((array)$_POST['details_hosts'] as $host) {
					
					if (!$host) {
						continue;
					}
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_SITE_DETAILS_HOSTS')."
						(name)
							VALUES
						('".DBFunctions::strEscape($host)."')
						".DBFunctions::onConflict('name', ['name'])."
					");
					
					$arr_details_hosts[] = $host;
				}
				
				$res = DB::query("DELETE
						FROM ".DB::getTable('TABLE_SITE_DETAILS_HOSTS')."
					WHERE name NOT IN ('".implode("','", DBFunctions::arrEscape($arr_details_hosts))."')
				");
			}
			
			// Details servers
			
			if (isset($_POST['details_servers'])) {
				
				$arr_server_hosts = [];
				
				foreach ((array)$_POST['details_servers'] as $arr_server) {
					
					if (!$arr_server['host_name'] || !$arr_server['host_type']) {
						continue;
					}
					
					$res = DB::query("INSERT INTO ".DB::getTable('TABLE_SITE_DETAILS_SERVERS')."
							(host_name, host_type, host_port, login_type, login_name, passkey, extra)
								VALUES
							(
								'".DBFunctions::strEscape($arr_server['host_name'])."',
								'".DBFunctions::strEscape($arr_server['host_type'])."',
								".(int)$arr_server['host_port'].",
								'".DBFunctions::strEscape($arr_server['login_type'])."',
								'".DBFunctions::strEscape($arr_server['login_name'])."',
								'".DBFunctions::strEscape($arr_server['passkey'])."',
								'".DBFunctions::strEscape($arr_server['extra'])."'
							)
						".DBFunctions::onConflict('host_name, host_type', ['host_port', 'login_type', 'login_name', 'passkey', 'extra'])."
					");
					
					$arr_server_hosts[] = $arr_server['host_name'].'_'.$arr_server['host_type'];
				}
				
				$res = DB::query("DELETE
						FROM ".DB::getTable('TABLE_SITE_DETAILS_SERVERS')."
					WHERE CONCAT(host_name, '_', host_type) NOT IN ('".implode("','", DBFunctions::arrEscape($arr_server_hosts))."')
				");
			}

			$this->msg = true;				
		}
		
		if ($method == "update_appearance") {
		
			if ($_FILES['file']['size']) {
				$file_upload = new FileStore($_FILES['file'], ['directory' => DIR_ROOT.DIR_HOME.'css/', 'filename' => 'image.png', 'overwrite' => true]);
			}
		
			$str_theme = json_decode($_POST['theme'], true);
			$str_theme = value2JSON($str_theme, JSON_PRETTY_PRINT);
			
			$handle = fopen(DIR_ROOT.DIR_HOME.'css/theme.json', 'w');
			fwrite($handle, $str_theme);
			fclose($handle);
		
			$handle = fopen(DIR_ROOT.DIR_HOME.'css/home.css', 'w');
			fwrite($handle, $_POST['style']);
			fclose($handle);
			
			$this->html = $this->contentTabAppearance();
			$this->msg = true;
		}
		
		if ($method == "update_script") {
				
			$handle = fopen(DIR_ROOT.DIR_HOME.'js/home.js', 'w');
			fwrite($handle, $_POST['script']);
			fclose($handle);
			
			$this->msg = true;
		}
	}
	
	private static function getServerTypes() {

		$arr = [];
		
		$arr[] = ['id' => 'file', 'label' => getLabel('lbl_static_server_file')];

		return $arr;
	}
	
	private static function getServerLoginTypes() {

		$arr = [];
		
		$arr[] = ['id' => 'ssh', 'label' => 'SHH'];
		$arr[] = ['id' => 'ftp', 'label' => 'FTP'];

		return $arr;
	}
		
	public static function getSiteDetails($details = false) {
					
		$arr = [];

		if (!$details) {
			
			$res = DB::query("SELECT *
					FROM ".DB::getTable('TABLE_SITE_DETAILS')."
			");

			if ($res->getRowCount()) {
					
				$arr = $res->fetchAssoc();
				$count = 0;
				
				foreach ($arr as $key => &$value) {
					
					$type = $res->getFieldDataType($count);
					
					if ($type == DBFunctions::TYPE_BOOLEAN) {
						$value = DBFunctions::unescapeAs($value, DBFunctions::TYPE_BOOLEAN);
					}
					
					$count++;
				}
				unset($value);
			}
		} else if (is_string($details)) {
			
			$res = DB::query("SELECT
				".str2Label($details)."
					FROM ".DB::getTable('TABLE_SITE_DETAILS')."			
			");
		
			if ($res->getRowCount()) {
				
				$arr_row = $res->fetchRow();
				$value = $arr_row[0];
				
				$type = $res->getFieldDataType(0);
				
				if ($type == DBFunctions::TYPE_BOOLEAN) {
					$value = DBFunctions::unescapeAs($value, DBFunctions::TYPE_BOOLEAN);
				}

				$arr = ['identifier' => $details, 'label' => $value];
			}
		} else if (is_array($details)) {
			
			$res = DB::query("SELECT
				".implode(',', arrParseRecursive($details, 'str2Label'))."
					FROM ".DB::getTable('TABLE_SITE_DETAILS')."
			");
		
			if ($res->getRowCount()) {
				
				$arr_row = $res->fetchAssoc();
				$count = 0;
				
				foreach ($arr_row as $key => $value) {
					
					$type = $res->getFieldDataType($count);
				
					if ($type == DBFunctions::TYPE_BOOLEAN) {
						$value = DBFunctions::unescapeAs($value, DBFunctions::TYPE_BOOLEAN);
					}					
					
					$arr[] = ['identifier' => $key, 'label' => $value];
					
					$count++;
				}
			}
		}
		
		return $arr;
	}
	
	public static function getSiteDetailsCustom($details = false) {
					
		$arr = [];
		
		if (!$details) {
			
			$res = DB::query("SELECT *
				FROM ".DB::getTable('TABLE_SITE_DETAILS_CUSTOM')."
			");
			
			while ($row = $res->fetchAssoc()) {
				
				$arr[$row['name']] = $row;
			}
		} else {
			
			if (is_array($details)) {
				$sql = "IN ('".implode("','", DBFunctions::arrEscape($details))."')";
			} else  {
				$sql = "= ".DBFunctions::strEscape($details);
			}

			$res = DB::query("SELECT *
					FROM ".DB::getTable('TABLE_SITE_DETAILS_CUSTOM')."
				WHERE name ".$sql."
			");

			while ($row = $res->fetchAssoc()) {
				
				$arr[] = ['identifier' => $row['name'], 'label' => $row['value']];
			}
		}
		
		return $arr;
	}
	
	public static function getSiteDetailsHosts() {
					
		$arr = [];

		$res = DB::query("SELECT *
				FROM ".DB::getTable('TABLE_SITE_DETAILS_HOSTS')."
		");

		while ($row = $res->fetchAssoc()) {
			
			$arr[$row['name']] = $row;
		}
		
		return $arr;
	}
	
	public static function getSiteDetailsServers() {
					
		$arr = [];

		$res = DB::query("SELECT *
				FROM ".DB::getTable('TABLE_SITE_DETAILS_SERVERS')."
		");

		while ($row = $res->fetchAssoc()) {
			
			$arr[$row['host_type']][$row['host_name']] = $row;
		}
		
		return $arr;
	}
		
	public static function useServerFiles() {
			
		$arr_servers = self::getSiteDetailsServers();
		
		if ($arr_servers['file']) {
			
			$server = current($arr_servers['file']);
			Settings::setServerFileHostName($server['host_name']);
		}
	}
	
	public static function generateSitemap($arr_options) {
		
		$str_path_sitemap = DIR_ROOT_STORAGE.DIR_HOME.'sitemap/';
		$str_path_sitemap_new = getPathTemporary(false, true);
		$arr_modules = getModuleConfiguration('webLocations');
		
		$str_host_name = ($arr_options['host_name'] ? rawurldecode($arr_options['host_name']) : false);
				
		Response::holdFormat(true);
		Response::setFormat(Response::RENDER_TEXT);
		
		$sitemap = new GenerateSitemap($str_path_sitemap_new);
		$sitemap->setHostName($str_host_name);
		
		foreach ($arr_modules as $module => $arr_settings) {
			
			$iterator = $arr_settings['entries'];
			
			if (!$iterator) {
				continue;
			}
			
			if (is_callable($iterator)) {
				$iterator = $iterator();
			}
			
			$sitemap->addEntries($iterator, $arr_settings['name']);
		}
		
		Response::holdFormat();
		
		// File management
		
		FileStore::makeDirectoryTree(DIR_ROOT_STORAGE.DIR_HOME.'sitemap/');
		
		// Remove old files
		
		$iterator_files = new DirectoryIterator($str_path_sitemap);
		
		foreach ($iterator_files as $file) {
				
			if (!$file->isFile()) {
				continue;
			}
			
			FileStore::deleteFile($str_path_sitemap.$file->getFilename());
		}
		
		// Move new files
		
		$arr_sitemap_files = $sitemap->get();
		$str_index = '';
		
		foreach ($arr_sitemap_files as $str_identifier => $arr_files) {
			foreach ($arr_files as $str_filename) {
							
				FileStore::renameFile($str_path_sitemap_new.$str_filename, $str_path_sitemap.$str_filename);
				
				$str_index .= 'Sitemap: '.SERVER_SCHEME.($str_host_name ?: SERVER_NAME_1100CC).'/sitemap/'.$str_filename.EOL_1100CC;
			}
		}
		
		FileStore::deleteDirectoryTree(rtrim($str_path_sitemap_new, '/'));
		
		FileStore::storeFile($str_path_sitemap.'index.robots.txt', $str_index);
	}
	
	public static function clearStaticServerFiles($arr_options) {
		
		$arr_servers = self::getSiteDetailsServers();
		
		if (!$arr_servers['file']) {
			return false;
		}
		
		foreach ($arr_servers['file'] as $arr_server) {
			
			if (!$arr_server['host_name']) {
				return;
			}
			
			$str_path = DIR_SITE_STORAGE.'command';
			$str_url = SERVER_SCHEME.$arr_server['host_name'].'/command';
			
			FileStore::storeFile($str_path, 'clear');
			
			$data = new FileGet($str_url);
			$str_result = $data->get();
						
			unlink($str_path);
			
			if (trim($str_result) == 'success') {
				
				msg(__METHOD__.' SUCCESS.');
				return true;
			} else {
				
				error(__METHOD__.' ERROR: '.($str_result ?: $data->getError()), TROUBLE_ERROR, LOG_BOTH, print_r($str_result, true));
			}
		}
	}

	public static function cleanCache($arr_options) {
		
		if (stream_resolve_include_path(DIR_ROOT_CACHE.DIR_HOME)) {
		
			$dir_it = new RecursiveDirectoryIterator(DIR_ROOT_CACHE.DIR_HOME);
			$it = new RecursiveIteratorIterator($dir_it);

			foreach ($it as $file) {
				if ($file->isFile() && $file->getMTime()+(($arr_options['age_amount']*$arr_options['age_unit'])*60) < time()) {
					unlink($file->getPathname());
				}
			}
		}
		
		if (stream_resolve_include_path(DIR_ROOT_CACHE.DIR_HOME.DIR_CMS)) {
		
			$dir_it = new RecursiveDirectoryIterator(DIR_ROOT_CACHE.DIR_HOME.DIR_CMS);
			$it = new RecursiveIteratorIterator($dir_it);

			foreach ($it as $file) {
				if ($file->isFile() && $file->getMTime()+(($options['age_amount']*$options['age_unit'])*60) < time()) {
					unlink($file->getPathname());
				}
			}
		}
		
		$res = DB::query('DELETE FROM '.DB::getTable('SITE_CACHE_FILES').'');
	}
	
	public static function runWebService($arr_options) {
		
		if (!$arr_options) {
			error(getLabel('msg_missing_information'));
		}

		$service = new WebService('0.0.0.0', $arr_options['port'], getLabel('https', 'D', true));
		$service->verbose = (bool)$arr_options['verbose'];
		
		$arr_web_service_details = getModuleConfiguration('webServiceProperties');
				
		foreach($arr_web_service_details as $module => $classes) {
			foreach($classes as $class => $arr_options) {
											
				$task = new $class();
				$task->init();
				
				$service->addTask($task);
			}
		}
		
		$service->init();
	}
	
	public static function checkWebService() {
		
		$arr_job = cms_jobs::getJob('cms_details', 'runWebService');
		
		if ($arr_job && $arr_job['process_id']) {
			
			$arr_webservice = (Settings::get('webservice') ?: []);
			
			$str_server_name = ($arr_webservice['server_name'] ? $arr_webservice['server_name'].'.' : '');
			$num_port = null;
			if (!empty($arr_webservice['ports'][SERVER_SCHEME])) {
				$num_port = (int)$arr_webservice['ports'][SERVER_SCHEME];
			} else {
				$num_port = (SERVER_SCHEME == URI_SCHEME_HTTPS ? (int)$arr_job['port']+1 : (int)$arr_job['port']);
			}

			$arr_job['host'] = $str_server_name.SERVER_NAME;
			$arr_job['port_local'] = (int)$arr_job['port'];
			$arr_job['port'] = $num_port;
			
			return $arr_job;
		} else {
			return false;
		}		
	}
	
	public static function setWebServiceActiveUser($user_id, $passkey) {
		
		$arr_job = self::checkWebService();
		
		if ($arr_job) {
			
			$arr_passkey = WebServiceUser::setPasskey($user_id, $passkey);
			WebServiceUser::usePasskey($user_id);
			
			return ['host' => $arr_job['host'], 'port' => $arr_job['port'], 'port_local' => $arr_job['port_local']];
		}
	}
	
	public static function isWebServiceActiveUser($user_id) {
		
		$arr_job = self::checkWebService();
		
		if (!$arr_job) {
			return false;
		}
		
		$passkey = WebServiceUser::usePasskey($user_id);
		
		if ($passkey) {
			return ['host' => $arr_job['host'], 'port' => $arr_job['port'], 'port_local' => $arr_job['port_local'], 'passkey' => $passkey];
		} else {
			return false;
		}
	}
}
