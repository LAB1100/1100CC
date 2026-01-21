<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class HTMLDocument extends DOMDocument {
		
	protected $is_wrapped = true;
	
	public function __construct($html, $do_wrap = true) {
		
		$this->is_wrapped = $do_wrap;
		
		parent::__construct();
		$this->strictErrorChecking = false;
		
		try {
			$this->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>'.$html.'</body></html>');
		} catch (Exception $e) {
			
		}
		
		// Remove <!DOCTYPE 
		$this->removeChild($this->firstChild);
		// Remove <html><head></head>(<body></body>)</html>
		if ($do_wrap) { // Keep <body> as wrapper
			$this->replaceChild($this->firstChild->lastChild, $this->firstChild);
		} else {
			$this->replaceChild($this->firstChild->lastChild->firstChild, $this->firstChild);
		}
	}
	
	public function addToNode($node, $content) {
		
		if ($content instanceof DOMDocument) {
			$doc = $content;
		} else {
			$doc = new HTMLDocument($content);
		}
		
		$node_new = $this->importNode($doc->firstChild, true);
		
		while ($node_select = $node_new->firstChild) {
			$node->appendChild($node_select);
		}
		
		/*$frag = $this->createDocumentFragment(); // create fragment
		$frag->appendXML($content); // insert arbitary html into the fragment
		
		$node->appendChild($frag);*/
	}
	
	public function getHTML() {
	
		$html = $this->saveHTML($this->firstChild); // Read from first child to prevent character encoding

		if ($this->is_wrapped) {
			$html = substr($html, 6, -7); // Remove wrapper <body>
		}
		
		return $html;
	}
	
	public function getXHTML() {

		$html = $this->saveXML($this->firstChild); // Read from first child to prevent character encoding
		
		if ($this->is_wrapped) {
			$html = substr($html, 6, -7); // Remove wrapper <body>
		}
		
		return $html;
	}


}
