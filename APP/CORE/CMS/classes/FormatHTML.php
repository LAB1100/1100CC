<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FormatHTML {
	
	private $html = '';
	private $doc = false;
	
	public $count_paragraphs = 0;
	public $count_elements_removed = 0;

	public function __construct($html) {
	
		$this->html = $html;
		$this->html = str_replace(["\r\n", "\r"], "\n", $this->html); // Standardize newline characters to "\n"
	
		if ($this->hasUntaggedContent()) {
			$this->html = self::autoP($this->html);
		} else {
			$this->handleNewlines();
		}
		
		$this->doc = self::openHTMLDocument($this->html);
	}
	
	public function getHTML() {
		
		$this->html = self::closeHTMLDocument($this->doc);
		
		$this->html = str_replace("  ", " &#160;", $this->html); // Maintain spacing
		$this->html = str_replace("\t", '<span class="tab"></span>', $this->html); // Tab
		
		return $this->html;
	}
	
	public function getXHTML() {
		
		$this->html = self::closeXHTMLDocument($this->doc);
		
		$this->html = str_replace("  ", " &#160;", $this->html); // Maintain spacing
		$this->html = str_replace("\t", '<span class="tab"></span>', $this->html); // Tab
		
		return $this->html;
	}

	public function extractParagraphs($max_p = 2) {
		
		$this->count_paragraphs = 0;
		$target = $this->doc->firstChild;
		$arr_remove = [];

		foreach ($target->childNodes as $child) {
			
			if ($this->count_paragraphs >= $max_p) {
				
				$arr_remove[] = $child;
				$this->count_elements_removed++;
			}
			
			if ($child->nodeName == 'p') {
				
				$this->count_paragraphs++;
			}
		}
		
		foreach ($arr_remove as $child) {
			
			$child->parentNode->removeChild($child);
		} 
	}
	
	public function addToLastParagraph($content) {
	
		$frag = $this->doc->createDocumentFragment(); // create fragment
		$frag->appendXML($content); // insert arbitary html into the fragment
		
		$target = $this->doc->firstChild;
		$p = $target->getElementsByTagName('p');
		$p->item($p->length-1)->appendChild($frag);
	}
	
	public function cacheImages() {
				
		foreach ($this->doc->getElementsByTagName('img') as $img) {
			
			$url = $img->getAttribute('src');
			
			if (substr($url, 0, 1) == '/' && substr($url, 0, 2) != '//') { // Only cache local images
				
				$width = (int)($img->getAttribute('width') ?: $img->getAttribute('data-width'));
				$height = (int)($img->getAttribute('height') ?: $img->getAttribute('data-height'));
			
				if ($width || $height) {
					
					$img->setAttribute('src', SiteStartVars::getCacheURL('img', [$width, $height], $url));
				}
			}
		}
	}
	
	private function hasUntaggedContent() {
		
		$doc = self::openHTMLDocument($this->html);
		
		$target = $doc->firstChild;
		$arr_remove = [];
		foreach ($target->getElementsByTagName('*') as $child) {
			$arr_remove[] = $child;
		}
		foreach($arr_remove as $child) {
			$child->parentNode->removeChild($child);
		}
		
		// Remove comments
		$xpath = new DOMXPath($doc);
		foreach ($xpath->query('//comment()') as $comment) {
			$comment->parentNode->removeChild($comment);
		}

		return (str_replace("\n" , "", self::closeHTMLDocument($doc)) != '');
	}
	
	private function handleNewlines() {
	
		$this->html = preg_replace("/>\s*\n+/si", '><OutputNewline>', $this->html); // Keep and clean newlines between tags
		$this->html = nl2br($this->html); // Add the newlines
		$this->html = str_replace('<OutputNewline>', "\n", $this->html); // Restore newlines in output
	}
	
	public static function openHTMLDocument($html) {
	
		$html = '<div>'.$html.'</div>'; // Wrap in <div>

		$doc = new DOMDocument();
		$doc->strictErrorChecking = false;
		
		try {
			$doc->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>'.$html.'</body></html>');
		} catch (Exception $e) {
			
		}
		
		// Remove <!DOCTYPE 
		$doc->removeChild($doc->firstChild);
		// Remove <html><head></head><body></body></html> 
		$doc->replaceChild($doc->firstChild->lastChild->firstChild, $doc->firstChild);
		
		return $doc;
	}
	
	public static function closeHTMLDocument($doc) {
	
		$html = $doc->saveHTML($doc->firstChild); // Read from first child to prevent character encoding

		$html = substr($html, 5, -6); // Remove wrapper <div>

		return $html;
	}
	
	public static function closeXHTMLDocument($doc) {

		$html = $doc->saveXML($doc->firstChild); // Read from first child to prevent character encoding
		
		$html = substr($html, 5, -6); // Remove wrapper <div>

		return $html;
	}
	
	public static function getTextContent($text) {
		
		// replace <br> with spaces so that Text<br>Text becomes two words
		$text = preg_replace('/\<br(\s*)?\/?\>/i', ' ', $text);

		// add extra space between tags, e.g. <p>Text</p><p>Text</p>
		$text = str_replace('><', '> <', $text);

		// only now remove all HTML tags
		$text = self::strip($text);

		// replace all tabs, newlines, and carrriage returns with spaces
		$text = str_replace(["\t", "\n", "\r"], ' ', $text);

		// replace entities with plain spaces
		$text = str_replace(['&#20;', '&#160;', '&nbsp;'], ' ', $text);

		// collapse whitespace
		$text = preg_replace('/\s\s+/', ' ', $text);

		return $text;
	}
	
	public static function strip($text) {
		
		$text = strip_tags($text);
		
		return $text;
	}
	
	public static function test($str) {
		
		// Crude test to check if string contains possible set of html tags
		
		$has_html = (strpos($str, '<') !== false && ((strpos($str, '</') !== false && substr_count($str, '>') > 1) || strpos($str, '/>') !== false) && preg_match('/<[a-zA-Z]+(?:[\s\S]+=[\'"][\s\S]+>|>)/', $str) === 1);
		
		return $has_html;
	}
	
	// https://codex.wordpress.org/Function_Reference/wpautop
	public static function autoP($pee, $br = true) {
		
		$pre_tags = [];
		if (trim($pee) === '') {
			return '';
		}
		// Just to make things a little easier, pad the end.
		$pee = $pee."\n";
		/*
		* Pre tags shouldn't be touched by autop.
		* Replace pre tags with placeholders and bring them back after autop.
		*/
		if (strpos($pee, '<pre') !== false) {
			$pee_parts = explode('</pre>', $pee);
			$last_pee = array_pop($pee_parts);
			$pee = '';
			$i = 0;
			foreach ($pee_parts as $pee_part) {
				$start = strpos($pee_part, '<pre');
				// Malformed html?
				if ($start === false) {
					$pee .= $pee_part;
					continue;
				}
				$name = "<pre wp-pre-tag-$i></pre>";
				$pre_tags[$name] = substr($pee_part, $start) . '</pre>';
				$pee .= substr($pee_part, 0, $start) . $name;
				$i++;
			}
			$pee .= $last_pee;
		}
		// Change multiple <br>s into two line breaks, which will turn into paragraphs.
		$pee = preg_replace('|<br\s*/?>\s*<br\s*/?>|', "\n\n", $pee);
		$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|option|form|map|area|blockquote|address|math|style|input|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
		// Add a double line break above block-level opening tags.
		$pee = preg_replace('!(<' . $allblocks . '[\s/>])!', "\n\n$1", $pee);
		// Add a double line break below block-level closing tags.
		$pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
		// Find newlines in all elements and add placeholders.
		// N/A
		// Collapse line breaks before and after <option> elements so they don't get autop'd.
		if (strpos($pee, '<option') !== false) {
			$pee = preg_replace('|\s*<option|', '<option', $pee);
			$pee = preg_replace('|</option>\s*|', '</option>', $pee);
		}
		/*
		* Collapse line breaks inside <object> elements, before <param> and <embed> elements
		* so they don't get autop'd.
		*/
		if (strpos($pee, '</object>') !== false) {
			$pee = preg_replace('|(<object[^>]*>)\s*|', '$1', $pee);
			$pee = preg_replace('|\s*</object>|', '</object>', $pee);
			$pee = preg_replace('%\s*(</?(?:param|embed)[^>]*>)\s*%', '$1', $pee);
		}
		/*
		* Collapse line breaks inside <audio> and <video> elements,
		* before and after <source> and <track> elements.
		*/
		if (strpos($pee, '<source') !== false || strpos( $pee, '<track') !== false) {
			$pee = preg_replace('%([<\[](?:audio|video)[^>\]]*[>\]])\s*%', '$1', $pee);
			$pee = preg_replace('%\s*([<\[]/(?:audio|video)[>\]])%', '$1', $pee);
			$pee = preg_replace('%\s*(<(?:source|track)[^>]*>)\s*%', '$1', $pee);
		}
		// Collapse line breaks before and after <figcaption> elements.
		if (strpos( $pee, '<figcaption') !== false) {
			$pee = preg_replace('|\s*(<figcaption[^>]*>)|', '$1', $pee);
			$pee = preg_replace('|</figcaption>\s*|', '</figcaption>', $pee);
		}
		// Remove more than two contiguous line breaks.
		$pee = preg_replace("/\n\n+/", "\n\n", $pee);
		// Split up the contents into an array of strings, separated by double line breaks.
		$pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
		// Reset $pee prior to rebuilding.
		$pee = '';
		// Rebuild the content as a string, wrapping every bit with a <p>.
		foreach ($pees as $tinkle) {
			$pee .= '<p>'.trim($tinkle, "\n")."</p>\n";
		}
		// Under certain strange conditions it could create a P of entirely whitespace.
		$pee = preg_replace('|<p>\s*</p>|', '', $pee);
		// Add a closing <p> inside <div>, <address>, or <form> tag if missing.
		$pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);
		// If an opening or closing block element tag is wrapped in a <p>, unwrap it.
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);
		// In some cases <li> may get wrapped in <p>, fix them.
		$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee);
		// If a <blockquote> is wrapped with a <p>, move it inside the <blockquote>.
		$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
		$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
		// If an opening or closing block element tag is preceded by an opening <p> tag, remove it.
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
		// If an opening or closing block element tag is followed by a closing <p> tag, remove it.
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);
		// Optionally insert line breaks.
		if ($br) {
			// Replace newlines that shouldn't be touched with a placeholder.
			$pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', function($matches) {
				return str_replace("\n", "<PreserveNewline />", $matches[0]);
			}, $pee);
			// Normalize <br>
			$pee = str_replace(['<br>', '<br/>'], '<br />', $pee);
			// Replace any new line characters that aren't preceded by a <br /> with a <br />.
			$pee = preg_replace('|(?<!<br />)\s*\n|', "<br />", $pee);
			// Replace newline placeholders with newlines.
			$pee = str_replace('<PreserveNewline />', "\n", $pee);
		}
		// If a <br /> tag is after an opening or closing block tag, remove it.
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
		// If a <br /> tag is before a subset of opening or closing block tags, remove it.
		$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
		$pee = preg_replace("|\n</p>$|", '</p>', $pee);
		// Replace placeholder <pre> tags with their original content.
		if (!empty($pre_tags)) {
			$pee = str_replace(array_keys($pre_tags), array_values($pre_tags), $pee);
		}
		
		$pee = trim($pee);
		
		return $pee;
	}
}
