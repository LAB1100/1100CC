<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class ExitPage {

    protected $msg;
    protected $str_header;
	protected $str_type;
	protected $system_msg;
	protected $str_system_type;
	
	protected $str_html;
	protected $str_title;
	
	protected $arr_head_tags = [];

    public function __construct($msg, $str_header, $str_type) {

		$this->msg = $msg;
		$this->str_header = $str_header;
		$this->str_type = $str_type;
		
		$this->addStyle($this->getDefaultStyle());
    }
    
    public function setTitle($str_title) {
		
		$this->str_title = $str_title;
	}
	
	public function addHeadTag($tag) {
	
		$this->arr_head_tags[] = $tag;
	}
	
	public function setSystem($msg, $str_type) {

		$this->system_msg = $msg;
		$this->str_system_type = $str_type;
		
		$this->addStyle($this->getSystemDefaultStyle());
    }
	
	public function getPage() {
		
		$this->createPage();
	
		return $this->str_html;
	}
	
	protected function createPage() {
		
		if ($this->str_title) {
			$str_title = $this->str_title;
		} else {
			$str_title = 'OOPS - '.strtoupper($this->str_header).' - '.SiteEndEnvironment::getTitle();
		}
		
		$str_html = '<!DOCTYPE html>
				<html lang="en">
					<head>
					
					<title>'.$str_title.'</title>
					'.SiteEndEnvironment::getIcons().'
					'.$this->getHeadTags().'

				</head>
				<body id="'.$this->str_type.'">';
				
					if ($this->system_msg) {
						
						$str_html .= '<div class="system">
							'.$this->system_msg.'
						</div>';
					}
					
					$str_html .= '<div class="result">
						<h1>'.$this->str_header.'</h1>
						'.$this->msg.'
					</div>';
					
				$str_html .= '</body>
			</html>';

		$this->str_html = $str_html;
	}
		
	protected function getDefaultStyle() {
	
		$str_style = '
			* { box-sizing: border-box; margin: 0px; padding: 0px; }
			html { height: 100%; font-size: 62.5%; }
			body {
				display: flex; flex-flow: column nowrap; align-content: stretch; align-items: stretch; justify-content: flex-start;
				height: 100%; line-height: 1.26; padding: 100px; font-family: arial,helvetica,sans-serif; font-size: 12px; font-size: 1.2rem; background: #ffffff; }
			
			body > .result { width: 100%; height: 100%; padding: 50px; line-height: 1; }
			body > .result > h1 { font-size: 7rem; text-transform: uppercase; }
			body > .result > ul { list-style: none; margin-top: 40px; }
			
			body > .result > ul > li { margin-top: 10px; display: flex; flex-flow: row nowrap; align-items: baseline; }
			body > .result > ul > li:first-child { margin-top: 0px; }
			body > .result > ul > li > * { vertical-align: top; flex: 0 1 auto; }
			body > .result > ul > li > label,
			body > .result > ul > li > div { display: inline-block; }
			body > .result > ul > li > div { margin-left: 10px; white-space: pre-wrap; }
			body > .result > ul > li > div > a { color: inherit; text-decoration: underline; }
			body > .result > ul > li > div code { font-family: monospace; }
			body > .result > ul > li > label,
			body > .result > ul > li:first-child > div { padding: 5px 10px; font-size: 1.4rem; font-weight: bold; }
			body > .result > ul > li:first-child > label { min-width: 30px; }
			body > .result > ul > li:first-child > label::before { content: "\200B"; }
		';
		
		switch ($this->str_type) {
			case 'error':
				$color = '#a00000';
				$color_reverse = '#ffffff';
				break;
			case '404':
				$color = '#009cff';
				$color_reverse = '#ffffff';
				break;
			case 'redirect':
				$color = '#fff839';
				$color_reverse = '#000000';
				break;
			case 'cookie':
			case 'script':
				$color = '#000000';
				$color_reverse = '#ffffff';
				break;
			default:
				$color = '#ffffff';
				$color_reverse = '#000000';
				break;
		}
		
		$str_style .= '
			body { color: '.$color_reverse.'; }
			body > .result { background-color: '.$color.'; }
			body > .result > ul > li > label,
			body > .result > ul > li:first-child > div { background-color: '.$color_reverse.'; color: '.$color.'; }
		';
		
		return $str_style;
	}
	
	protected function getSystemDefaultStyle() {
	
		$str_style = '
			body > .system { position: relative; top: 0px; left: 0px; }
		';
		
		switch ($this->str_system_type) {
			case 'important':
				$color = '#ffb400';
				$color_reverse = '#ffffff';
				break;
		}
		
		$str_style .= '
			body > .system > div { box-sizing: border-box; text-align: center; font-size: 1.6rem; color: '.$color_reverse.'; background-color: '.$color.'; }
			body > .system > div > p:first-child > span.icon { vertical-align: middle; margin-right: 10px; }
			body > .system > div > p:first-child > span.icon svg { height: 30px; }
			body > .system > div > p { display: inline-block; vertical-align: middle; margin: 12px 16px; }
			
			span.icon { display: inline-block; }
			span.icon svg { height: 16px; width: auto; fill: currentColor; vertical-align: middle; }
		';
		
		return $str_style;
	}
	
	protected function getHeadTags() {
		
		$return = '';
	
		foreach ($this->arr_head_tags as $tag) {
			$return .= $tag;
		}
		
		return $return;
	}
		
	public function addScript($script, $is_url = false) {
		
		if ($is_url) {
			$this->arr_head_tags[] = '<script type="text/javascript" src="'.$script.'"></script>';
		} else {
			$this->arr_head_tags[] = '<script type="text/javascript">'.$script.'</script>';
		}
	}
	
	public function addStyle($style, $is_url = false) {
		
		if ($is_url) {
			$this->arr_head_tags[] = '<link rel="stylesheet" type="text/css" href="'.$style.'" />';
		} else {
			$this->arr_head_tags[] = '<style>'.$style.'</style>';
		}
	}
}
