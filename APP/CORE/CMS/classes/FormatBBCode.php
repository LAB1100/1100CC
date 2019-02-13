<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FormatBBCode {
	
	private static $arr_codes = [];
	private static $arr_codes_special = [];

	static public function parse($text) {
		
		// Quotes
		$text = self::formatQuotes($text);
		
		foreach (self::$arr_codes as $name => list($code, $return)) {
			
			if (is_callable($return)) {
				$text = preg_replace_callback(
					$code,
					$return,
					$text);
			} else {
				$text = preg_replace($code, $return, $text);
			}
		}

		return $text;
	}
	
	static public function init() {
				
		self::$arr_codes = [
			// [h=1]Heading[/h]
			'heading' => [
				'/\[h=([1-9])\](.+?)\[\/h\]/si',
				'<h\1>\2</h\1>'
			],
			// [header=1]Heading[/header]
			'heading_full' => [
				'/\[header=([1-9])\](.+?)\[\/header\]/si',
				'<h\1>\2</h\1>'
			],

			// [b]Bold[/b]
			'bold' => [
				'/\[b\](.+?)\[\/b\]/si',
				'<strong>\1</strong>'
			],

			// [i]Italic[/i]
			'italic' => [
				'/\[i\](.+?)\[\/i\]/si',
				'<em>\1</em>'
			],

			// [u]Underline[/u]
			'underline' => [
				'/\[u\](.+?)\[\/u\]/si',
				'<ins>\1</ins>'
			],
		
			// [s]Strikethrough text[/s]
			'strikethrough' => [
				'/\[s\](.+?)\[\/s\]/si',
				'<del>\1</del>'
			],
		
			// [center]Center[/center]
			'center' => [
				'/\[center\](.+?)\[\/center\]/si',
				'<span class="center">\1</span>'
			],
		
			// [left]Center[/left]
			'left' => [
				'/\[left\](.+?)\[\/left\]/si',
				'<aside class="left">\1</aside>'
			],
		
			// [right]Center[/right]
			'right' => [
				'/\[right\](.+?)\[\/right\]/si',
				'<aside class="right">\1</aside>'
			],

			// [img]http://www.image.gif[/img]
			'img' => [
				'/\[img\]([^\s"<>]+?)\[\/img\]/i',
				'<img src="\1" alt="" />'
			],

			// [img=http://www/image.gif]
			'img_attr' => [
				'/\[img=([^\s"<>]+?)\]/i',
				'<img src="\1" alt="" />'
			],
		
			// [imgw=200]http://www/image.gif[/imgw]
			'imgw' => [
				'/\[imgw(?:=([0-9]+))?\]([^\s\'"<>]+(\.(jpg|jpeg|gif|png)))\[\/imgw\]/si', 
				function($matches) {
					return '<img src="'.$matches[2].'"'.($matches[1] ? ' width="'.$matches[1].'"' : '').'" class="enlarge" alt="" />';
				}
			],

			// [color=blue/#ffcc99/rgb()]Text[/color]
			'color' => [
				'/\[color=([#a-z0-9 ,\(\)]+)\](.+?)\[\/color\]/si',
				'<span style="color: \1;">\2</span>'
			],

			// [url=http://www.example.com]Text[/url]
			'url_attr' => [
				'/\[url=([^<>"\s]+?)\](.+?)\[\/url\]/si', 
				function($matches) {
					$str_first = substr($matches[1], 0, 1);
					return '<a href="'.$matches[1].'"'.(($str_first != '/' && $str_first != '#') || substr($matches[1], 0, 2) == '//' ? ' target="_blank"' : '').'>'.$matches[2].'</a>';
				}
			],

			// [url]http://www.example.com[/url]
			'url' => [
				'/\[url\]([^<>"\s]+?)\[\/url\]/i', 
				function($matches) {
					$str_first = substr($matches[1], 0, 1);
					return '<a href="'.$matches[1].'"'.(($str_first != '/' && $str_first != '#') || substr($matches[1], 0, 2) == '//' ? ' target="_blank"' : '').'>'.$matches[1].'</a>';
				}
			],

			// [size=100]Text[/size]
			'size' => [
				'/\[size=([1-9][0-9]+)\](.+?)\[\/size\]/si',
				'<span style="font-size: \1%;">\2</span>'
			],

			// [font=Arial]Text[/font]
			'font' => [
				'/\[font=([a-z ,]+)\](.+?)\[\/font\]/si',
				'<span style="font-family: \1;">\2</span>'
			],
			
			// [abbr=For Your Information]FYI[/abbr]
			'abbr' => [
				'/\[abbr=(.+?)\](.+?)\[\/abbr\]/si', 
				function($matches) {
					return '<abbr title="'.htmlspecialchars($matches[1]).'">'.$matches[2].'</abbr>';
				}
			],
			
			// [acronym=Laughing Out Loud]LOL[/acronym]
			'acronym' => [
				'/\[acronym=(.+?)\](.+?)\[\/acronym\]/si', 
				function($matches) {
					return '<acronym title="'.htmlspecialchars($matches[1]).'">'.$matches[2].'</acronym>';
				}
			],
			
			// [spoiler]Spoiler formatted[/spoiler]
			'spoiler' => [
				'/\[spoiler\](.+?)\[\/spoiler\]/si',
				'<div style="background-color: #000000; color: #000000;">\1</div>'
			],
		
			// [video=youtube]cHD6_33g-Gg[/video]
			'video_youtube' => [
				'/\[video=youtube\]([a-z0-9_\-]{11})\[\/video\]/i', 
				'<object class="video" type="application/x-shockwave-flash" data="//www.youtube.com/v/\1" width="550px" height="310">
				<param name="movie" value="//www.youtube.com/v/\1" />
				Flash Video Object
				</object>'
			],
			
			// [video=vimeo]66159123[/video]
			'video_vimeo' => [
				'/\[video=vimeo\]([0-9]+)\[\/video\]/i', 
				'<iframe class="video" src="//player.vimeo.com/video/\1" width="550" height="310" frameborder="0"></iframe>'
			],
			
			// [video=break]cHD633gG[/video]
			'video_break' => [
				'/\[video=break\]([a-z0-9=]{8,})\[\/video\]/i', 
				'<object class="video" type="application/x-shockwave-flash" data="//embed.break.com/\1" width="550" height="310">
				<param name="movie" value="//embed.break.com/\1" />
				Flash Video Object
				</object>'
			],
			
			// [video=liveleak]a2b_1210390974[/video]
			'video_liveleak' => [
				'/\[video=liveleak\]([a-z0-9]{3}_[0-9]{10})\[\/video\]/i', 
				'<object class="video" type="application/x-shockwave-flash" data="//www.liveleak.com/e/\1" width="550" height="310">
				<param name="movie" value="//www.liveleak.com/e/\1" />
				Flash Video Object
				</object>'
			],
			
			// URLs
			'url_raw' => [
				'/(?<=\A|[^=\]\'"a-z0-9])((http|ftp|https|ftps|irc):\/\/(?:[-a-z0-9@:%_+~#?&\/=]|(?:\.+[-a-z0-9@:%_+~#?&\/=]))+)/i', 
				function($matches) {
					return '<a href="'.$matches[1].'"'.(!strpos($matches[1], SERVER_NAME) ? ' target="_blank"' : '').'>'.$matches[1].'</a>';
				}
			],
			
			// [icon=name:categroy]
			'icon' => [
				'/\[icon=([^\s\'"<>:]+)(?::([^\s\'"<>]+))?\]/i', 
				function($matches) {
					return '<span class="icon"'.($matches[2] ? ' data-category="'.$matches[2].'"' : '').'>'.getIcon($matches[1]).'</span>';
				}
			]
		];
		
		self::$arr_codes_special = [
			//[quote=Author]Text[/quote]
			'quote' => [
				'/\[quote(?:=(.+?))?\]\s*([\s\S]+?)\s*\[\/quote\]\s*/i',
				'<blockquote><cite>\1</cite><p>\2</p></blockquote>'
			],
			'url_raw_all' => [
				'/(?<=\A|[^=\]\'"a-z0-9])((http|ftp|https|ftps|irc):\/\/[^<>\s]+)/i',
				function($matches) {
					return '<a href="'.$matches[1].'"'.(!strpos($matches[1], SERVER_NAME) ? ' target="_blank"' : '').'>'.$matches[1].'</a>';
				}
			]
		];
	}
	
	static private function formatQuotes($s) {

		while ($old_s != $s) {
			
			$old_s = $s;

			//find first occurrence of [/quote]
			$pos_close = strpos($s, '[/quote]');
			if ($pos_close === false) {
				return $s;
			}

			//find last [quote] before first [/quote]
			//note that there is no check for correct syntax
			$pos_open = self::strLastPos(substr($s, 0, $pos_close), '[quote');
			if ($pos_open === false)
				return $s;

			$quote = substr($s, $pos_open, $pos_close - $pos_open + 8);

			//[quote=Author]Text[/quote]
			$arr_code = self::$arr_codes_special['quote'];
			$quote = preg_replace($arr_code[0], $arr_code[1], $quote);

			$s = substr($s, 0, $pos_open).$quote.substr($s, $pos_close + 8);
		}

		return $s;
	}
	
	static public function formatUrls($s) {
		
		$arr_code = self::$arr_codes_special['url_raw_all'];
		$s = preg_replace_callback($arr_code[0], $arr_code[1], $s);
		
		return $s;
	}
	
	static public function addCode($name, $code, $return) {
		
		self::$arr_codes[$name] = [$code, $return];
	}
	
	static public function getCode($name) {
		
		$arr_code = (self::$arr_codes[$name] ?: self::$arr_codes_special[$name]);

		return $arr_code;
	}
	
	static public function addParagraphs($body) {

		// use \n for newline on all systems
		$body = str_replace(["\r\n", "\r"], "\n", $body); 
		$body = str_replace('<p></p>', '', '<p>'. preg_replace("/\n{2,}/si", '</p><p>', $body).'</p>');
		
		return $body;
	}
	
	static public function extractParagraphs($body, $max_p = 2) {
	
		preg_match("/^(.*?\n{2,}){0,".$max_p ."}/si", $body, $extract);
		$body = ($extract[0] ? trim($extract[0]) : $body); // if single line return body.
		
		return $body;
	}
		
	static private function strLastPos($haystack, $needle, $offset = 0) {
		
		$len_needle = strlen($needle);
		$pos_end = $offset - $len_needle;
		
		while (true) {
			
			$pos_new = strpos($haystack, $needle, $pos_end + $len_needle);
			
			if ($pos_new === false) {
				break;
			}
			
			$pos_end = $pos_new;
		}
		
		return ($pos_end >= 0 ? $pos_end : false);
	}
}
FormatBBCode::init();
