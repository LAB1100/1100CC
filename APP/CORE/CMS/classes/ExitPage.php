<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class ExitPage {

    private $msg;
    private $header;
	private $type;
	private $html;
	private $title;
	
	private $arr_head_tags = [];

    public function __construct($msg, $header, $type) {

		$this->msg = $msg;
		$this->header = $header;
		$this->type = $type;
		
		$this->addStyle($this->getDefaultStyle());
    }
	
	public function getPage() {
		
		$this->createPage();
	
		return $this->html;
	}
	
	protected function createPage() {
		
		if ($this->title) {
			$str_title = $this->title;
		} else {
			$str_title = 'OOPS - '.strtoupper($this->header).' - '.SiteEndVars::getTitle();
		}
		
		$html = '<!DOCTYPE html>
				<html lang="en">
					<head>
					
					<title>'.$str_title.'</title>
					'.SiteEndVars::getIcons().'
					'.$this->getHeadTags().'

				</head>
				<body id="'.$this->type.'">
				
					<div class="result">
						<h1>'.$this->header.'</h1>
						'.$this->msg.'
					</div>
					
				</body>
			</html>';

		$this->html = $html;
	}
		
	protected function getDefaultStyle() {
	
		$style = '* { -ms-box-sizing: border-box; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; margin: 0px; padding: 0px; }
				body,
				html { height: 100%; }
				body { padding: 100px; font-family: arial,helvetica,sans-serif; font-size: 12px; background: #ffffff; }
				
				body > .result { width: 100%; height: 100%; padding: 50px; line-height: 1; }
				body > .result > h1 { font-size: 70px; text-transform: uppercase; }
				body > .result > ul { list-style: none; margin-top: 40px; }
				
				body > .result > ul > li { margin-top: 10px; display: flex; flex-flow: row nowrap; align-items: baseline; }
				body > .result > ul > li:first-child { margin-top: 0px; }
				body > .result > ul > li > * { vertical-align: top; flex: 0 1 auto; }
				body > .result > ul > li > label,
				body > .result > ul > li > span { display: inline-block; }
				body > .result > ul > li > span { margin-left: 10px; white-space: pre-wrap; }
				body > .result > ul > li > span > a { color: inherit; text-decoration: underline; }
				body > .result > ul > li > label,
				body > .result > ul > li:first-child > span { padding: 5px 10px; font-size: 14px; font-weight: bold; }
				body > .result > ul > li:first-child > label { min-width: 30px; }
				body > .result > ul > li:first-child > label::before { content: "\200B"; } ';
		
		switch ($this->type) {
			case 'error':
				$color = '#a00000';
				$color_reverse = '#ffffff';
				break;
			case '404':
				$color = '#009cff';
				$color_reverse = '#ffffff';
				break;
			case 'cookie':
			case 'script':
				$color = '#000000';
				$color_reverse = '#ffffff';
				break;
			case 'redirect':
				$color = '#fff839';
				$color_reverse = '#000000';
				break;
		}
		
		$style .= '
			body { color: '.$color_reverse.'; }
			body > .result { background-color: '.$color.'; }
			body > .result > ul > li > label,
			body > .result > ul > li:first-child > span { background-color: '.$color_reverse.'; color: '.$color.'; }
		';
		
		return $style;
	}
	
	protected function getHeadTags() {
		
		$return = '';
	
		foreach ($this->arr_head_tags as $tag) {
			
			$return .= $tag;
		}
		
		return $return;
	}
	
	public function addHeadTag($tag) {
	
		$this->arr_head_tags[] = $tag;
	}
	
	public function setTitle($title) {
		
		$this->title = $title;
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
