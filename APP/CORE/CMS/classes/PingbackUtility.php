<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class PingbackUtility {

	const REGEXP_PINGBACK_LINK = '<link rel="pingback" href="([^"]+)" ?/?>';

	public static function isURL($str_url) {
		return filter_var($str_url, FILTER_VALIDATE_URL);
	}
	
	public static function isURLHost($str_url) {
		
		preg_match("/^https?:\/\/([^\/]+)\//", $str_url, $matches);
		
		if ($_SERVER['HTTP_HOST'] == $matches[1]) {
			return true;
		} else {
			return false;
		}
	}
	
	public static function getBlogPostId($str_url) {
	
		preg_match("/\.[m|s]\/(\d+)\//", $str_url, $match);
	
		$id	= $match[1];

		if ($id) {
			return $id;
		}
		else {
			return false;
		}
	}
	
	public static function isBlogPost($id) {
		
		$res = DB::query("SELECT COUNT(id) AS count FROM ".DB::getTable('TABLE_BLOG_POSTS')." WHERE id = ".(int)$id."");
		
		$row = $res->fetchArray();
		
		if($row['count'] != 1) {
			return false;
		} else {
			return true;
		}	
	}
	
	public static function isNewEntry($str_url, $id) {
		
		$query = "SELECT COUNT(id) AS count
				FROM ".DB::getTable('TABLE_BLOG_POST_XREFS')."
			WHERE direction = 'in' AND source = '".DBFunctions::strEscape(rawurldecode($str_url))."'
				AND blog_post_id = ".(int)$id;
		
		$res = DB::query($query);
		
		$row = $res->fetchArray();
		
		if($row['count'] > 0) {
			return false;
		} else {
			return true;
		}
	}
	
	public static function addEntry($str_url, $id, $title, $excerpt) {
		
		$res = DB::query("INSERT INTO ".DB::getTable('TABLE_BLOG_POST_XREFS')."
				(direction, added, source, blog_post_id, title, excerpt)
					VALUES
				('in', NOW(), '".DBFunctions::strEscape(rawurldecode($str_url))."', ".(int)$id.", '".DBFunctions::strEscape($title)."', '".DBFunctions::strEscape($excerpt)."')
		");

		return $res;
	}
	
	public static function isPingbackEnabled($str_url) {
		
		return (bool)self::getPingbackServerURL($str_url);
	}
	
	public static function getRawPostData()	{
		
		return file_get_contents('php://input');
	}

	public static function getPingbackServerURL($str_url) {
		
		$str_pingback_url = '';
		
		$do_request = new FileGet($str_url);
		
		$do_request->setConfiguration([
			'header_callback' => function($str_header) use (&$str_pingback_url) {
				
				if (strpos($str_header, 'X-Pingback:') !== false) {
					$str_pingback_url = trim(str_replace('X-Pingback:', '', $str_header));
				}
			}
		]);
		
		$do_request->request();

		if ($str_pingback_url) {
			return $str_pingback_url;
		}
		
		try {
			$response = file_get_contents($str_url, null, null, null, 4096); // Get first 4096 bytes of a file, limit resources needed
		} catch (Exception $e) {
		
		}

		return (preg_match(self::REGEXP_PINGBACK_LINK, $response, $arr_match) ? $arr_match[1] : false);
	}

	public static function isBacklinking($from, $to) {
		
		try {
			
			$content = file_get_contents($from);

			if($content !== false) {
				
				$doc = new DOMDocument();
				$doc->strictErrorChecking = false;
				
				try {
					$doc->loadHTML($content);
				} catch (Exception $e) {
					
				}
				
				foreach($doc->getElementsByTagName('a') as $link) {
					
					if(rawurldecode($link->getAttribute('href')) == rawurldecode($to)) {
						return true;
					}
				}
			}
		} catch (Exception $e) {

		}

		return false;
	}

	public static function sendPingback($str_from_url, $str_to_url, $str_server_url) {
		
		$str_request = xmlrpc_encode_request('pingback.ping', [$str_from_url, $str_to_url]);
		
		$get_request = new FileGet($str_server_url);
		
		$get_request->setConfiguration([
			'post' => $str_request
		]);
		
		$response = $get_request->get();
		$arr_response = xmlrpc_decode($response);

		if ($arr_response && !$arr_response['faultCode']) {
			return true;
		}
		
		return false;
	}
	
	public static function getTitle($html) {
		
		if (preg_match("/<title>([^<]*)<\/title>/i", $html, $matches)) {
			$title = $matches[1];
		} else {
			$title = 'Unknown';
		}
		
		return $title;
	}
}
