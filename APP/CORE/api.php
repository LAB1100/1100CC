<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// 1100CC Framework:
	
	$JSON = Response::getObject();

	SiteStartEnvironment::preloadModules();

	$module = SiteStartEnvironment::getAPI('module');
	$module = new $module;
	
	$arr_request_vars = SiteStartEnvironment::getRequestVariables();
	
	if (strEndsWith(end($arr_request_vars), '.openapi')) {
		
		$data = $module->getEndpointDescriptionOpenAPI();
		
		Response::setFormat(Response::OUTPUT_TEXT);
		$data = Response::parse($data);
		
		Response::sendFileHeaders($data, ['download' => true, 'name' => 'openapi.yaml']);
		
		echo $data;
		
		exit;
	}
	
	Labels::setVariable('api_endpoint', $module->getEndpointURL().'.openapi');
	$JSON->info .= ' '.Labels::getLabel('inf_api_openapi');
	
	$module->data = [];
	
	$JSON->data =& $module->data;
	
	$module->api();
	
	SiteStartEnvironment::cooldownModules();

	$JSON = Log::addToObject($JSON);
	
	if (Settings::get('timing') === true) {
		$JSON->timing = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
	}
	
	Response::stop('', $JSON);
