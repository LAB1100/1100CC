
/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

function DocumentEmbeddingListener() {
	
	// Initialise and load communication with embedded 1100CC documents
	// Only use vanilla JavaScript for cross-framework support
	
	let str_selector_frame = 'iframe.resize, iframe.embed';
	
	window.addEventListener('message', function handler(e) {
	
		if (!e.data.embedded) {
			return;
		}
		
		this.removeEventListener('message', handler);
		e.source.postMessage({script: true}, '*');

		window.addEventListener('message', function handler(e) {
			
			if (!e.data.script) {
				return;
			}
			
			this.removeEventListener('message', handler);
			eval?.('"use strict"; '+e.data.script); // Use global scope, but apply variables in contained scope.
		}, false);
		
	}, false);
	
	this.getSelector = function() {
		
		return str_selector_frame;
	};
	
	this.setSelector = function(str_selector) {
		
		str_selector_frame = str_selector;
	};
	
	this.checkLocationEmbed = function(arr_location, elm_frame) { // Default solution, override checkLocation() for custom procedures

		const arr_url = new URL(LOCATION.getURL(), LOCATION.getHost()); // 1100CC
		//const arr_url = new URL(window.location.pathname+window.location.search, window.location.origin); // Vanilla JS
		
		let str_location = arr_url.pathname; // Only need the path
		const regex_embed = new RegExp('(/\\d+\.m)?/embed.v/([^/]*)');
		
		if (arr_location.client && arr_location.client != '/') {
			
			const arr_url_client = new URL(arr_location.client, LOCATION.getHost()); // 1100CC
			//const arr_url_client = new URL(arr_location.client, window.location.origin); // Vanilla JS
			
			let str_location_client = arr_url_client.pathname; // Only need the path, no passing of search/hash
			str_location_client = str_location_client.replace(new RegExp('^/+'), ''); // Trim left '/'
			str_location_client = str_location_client.replaceAll('/', '|'); // Replace '/' with '|', the safe path separators
			
			if (regex_embed.test(str_location)) {
				str_location = str_location.replace(regex_embed, '$1/embed.v/'+str_location_client);
			} else {
				str_location = str_location.replace(new RegExp('/+$'), ''); // Trim right '/'
				str_location = str_location+'/0.m/embed.v/'+str_location_client;
			}
		} else {
			
			if (regex_embed.test(str_location)) {
				str_location = str_location.replace(regex_embed, '');
			}
		}
		
		arr_location.client = str_location+arr_url.search+arr_url.hash;
		
		return arr_location;
	};
	
	this.checkLocation = this.checkLocationEmbed;
}
var DOCUMENTEMBEDDINGLISTENER = new DocumentEmbeddingListener();
