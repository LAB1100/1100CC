<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

abstract class api_io extends base_module {
	
	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
		
	public function api() {
		
	}
	
	public function getEndpointURL() {
		
		$arr_request_vars = SiteStartEnvironment::getRequestVariables();
		
		// Remove possible .openapi from end of array (value)
		
		$str_last = end($arr_request_vars);
		$str_last = str_replace('.openapi', '', $str_last);
		$arr_request_vars[key($arr_request_vars)] = $str_last;
		
		if ($arr_request_vars && !end($arr_request_vars)) { // Remove the last empty request variable to allow for a final '/'
			unset($arr_request_vars[key($arr_request_vars)]);
		}
		
		$str_url = URL_BASE.($arr_request_vars ? arr2String($arr_request_vars, '/').'/' : '');
		
		return $str_url;
	}
	
	public function getEndpointDescriptionOpenAPI() {

		$arr_config = [
			'openapi' => '3.1.1',
			'info' => [
				'title' => SiteStartEnvironment::getAPI('name'),
				'version' => date('Ymd.His'),
				'description' => 'General usage description for the '.SiteStartEnvironment::getAPI('name').' API.'
			],
			'externalDocs' => [
				'url' => SiteStartEnvironment::getAPI('documentation_url'),
				'description' => 'Full documentation for '.SiteStartEnvironment::getAPI('name').' API.'
			],
			'servers' => [
				[
					'url' => $this->getEndpointURL(),
					'description' => ''
				]
			],
			'paths' => [],
			'components' => [
				'schemas' => [],
				'parameters' => [],
				'requestBodies' => [],
				'securitySchemes' => [
					'bearerAuth' => [
						'type' => 'http',
						'scheme' => 'bearer',
						'bearerFormat' => 'JWT'
					]
				]				
			],
			'security' => [
				[],
				['bearerAuth' => []]
			]
		];
		
		$this->extendEndpointDescriptionOpenAPI($arr_config);
		
		Response::holdFormat(true);
		Response::setFormat(Response::OUTPUT_JSON);
		
		$arr_config = JSON2Value(Response::parse($arr_config));
		
		Response::holdFormat();
		
		$str_yaml = value2YAML($arr_config);
		
		return $str_yaml;
	}
	
	protected function extendEndpointDescriptionOpenAPI(&$arr_config) {
		
	}
	
	/*public function getAIPluginDescription() {
		
		{
			"schema_version": "v1",
			"name_for_model": "nodegoat_query",
			"name_for_human": "Nodegoat Query Tool",
			"description_for_model": "Query Nodegoat data models and objects via dynamic type IDs.",
			"description_for_human": "Search and retrieve records from a Nodegoat instance using dynamic filters.",
			"auth": {
				"type": "user_http"
			},
			"api": {
				"type": "openapi",
				"url": "https://yourdomain.com/.well-known/openapi.yaml"
			},
			"logo_url": "https://yourdomain.com/logo.png",
			"contact_email": "you@yourdomain.com",
			"legal_info_url": "https://yourdomain.com/legal"
		}
	}*/
}
