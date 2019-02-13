<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

// 1100CC Framework:

	$JSON = Response::getObject();

	SiteStartVars::preloadModules();

	$module = SiteStartVars::$api['module'];
	$module = new $module;
		
	$module->data = [];
	
	$JSON->data =& $module->data;
	
	$module->api();
	
	SiteStartVars::cooldownModules();

	$JSON = Log::addToObj($JSON);
	
	Response::stop('', $JSON);
