<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class PingbackUtility {

	const REGEXP_PINGBACK_LINK = '<link rel="pingback" href="([^"]+)" ?/?>';

	public static function isURL($url) {
		return filter_var($url, FILTER_VALIDATE_URL);
	}
	
	public static function isURLHost($url) {
		
		preg_match("/^https?:\/\/([^\/]+)\//", $url, $matches);
		
		if ($_SERVER['HTTP_HOST'] == $matches[1]) {
			return true;
		} else {
			return false;
		}
	}
	
	public static function getBlogPostId($url) {
	
		preg_match("/\.[m|s]\/(\d+)\//", $url, $match);
	
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
	
	public static function isNewEntry($url, $id) {
		
		$query = "SELECT COUNT(id) AS count
				FROM ".DB::getTable('TABLE_BLOG_POST_XREFS')."
			WHERE direction = 'in' AND source = '".DBFunctions::strEscape(rawurldecode($url))."'
				AND blog_post_id = ".(int)$id;
		
		$res = DB::query($query);
		
		$row = $res->fetchArray();
		
		if($row['count'] > 0) {
			return false;
		} else {
			return true;
		}
	}
	
	public static function addEntry($url, $id, $title, $excerpt) {
		
		$res = DB::query("INSERT INTO ".DB::getTable('TABLE_BLOG_POST_XREFS')."
				(direction, added, source, blog_post_id, title, excerpt)
					VALUES
				('in', NOW(), '".DBFunctions::strEscape(rawurldecode($url))."', ".(int)$id.", '".DBFunctions::strEscape($title)."', '".DBFunctions::strEscape($excerpt)."')
		");

		return $res;
	}
	
	public static function isPingbackEnabled($url) {
		
		return (bool)self::getPingbackServerURL($url);
	}
	
	public static function getRawPostData()	{
		
		return file_get_contents('php://input');
	}

	public static function getPingbackServerURL($url) {
		
		$url_pingback = '';
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_USERAGENT, Labels::getServerVariable('user_agent'));
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($url, $header) use ($curl, &$url_pingback) {
						
			if (strpos($header, 'X-Pingback:') !== false) {
				$url_pingback = trim(str_replace('X-Pingback:', '', $header));
			}
			
			return strlen($header);
		});
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		$res = curl_exec($curl);
		
		curl_close($curl);

		if ($url_pingback) {
			return $url_pingback;
		}
		
		try {
			$response = file_get_contents($url, null, null, null, 4096); // Get first 4096 bytes of a file, limit resources needed
		} catch (Exception $e) {
		
		}

		return preg_match(self::REGEXP_PINGBACK_LINK, $response, $match) ? $match[1] : false;
	}

	public static function isBacklinking($from, $to) {
		
		try {
			
			$content = file_get_contents($from);

			if($content !== false) {
				
				libxml_use_internal_errors(true);
				$doc = new DOMDocument();
				$doc->strictErrorChecking = false;
				$doc->loadHTML($content);
				
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

	public static function sendPingback($from, $to, $server) {
		
		$request = xmlrpc_encode_request('pingback.ping', [$from, $to]);
		$curl = curl_init($server);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		$response = xmlrpc_decode(curl_exec($curl));
		curl_close($curl);

		if ($response && !$response['faultCode']) {
			return true;
		} else {
			return false;
		}
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
