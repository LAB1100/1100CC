<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class CombineJSCSS {

	private static $cache_length = 31356000;

	public static function combine($arr_files, $arr_modules, $type, $ie_tag) {
		
		$create_archive = getLabel('caching', 'D', true);
		$ie_tag = preg_replace('/[^0-9_]/', '', $ie_tag);
		$cur_ie_tag = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') : false);
		
		if ($ie_tag) {
			if ($cur_ie_tag == $ie_tag) {
				header($_SERVER['SERVER_PROTOCOL'].' 304 Not Modified');
				exit;
			}
		} else {
			$create_archive = false;
		}
		
		$path = DIR_SITE_CACHE.$type.'/'.$ie_tag.'.cache';
		
		// Get code from archive folder if it exists, otherwise grab latest files, merge and save in archive folder
		if ($create_archive && isPath($path)) {
			
			$code = file_get_contents($path);
		} else {
			
			// Get and merge code
			$code = '/**start**/'.PHP_EOL;
			
			$arr_last_modified = [];
		
			foreach ($arr_files as $file) {
				
				if ($file == 'modules') {
					
					foreach ($arr_modules as $key => $value) {
			
						$arr_last_modified[] = $value['time'];
						
						if (method_exists($key, $type)) {
							$code .= $key::$type().PHP_EOL;
						}
					}
					
					continue;
				}

				$arr_last_modified[] = filemtime($file);
				if ($type == SiteStartEnvironment::MATERIAL_CSS) {
					$path_check = realpath($file);
					if (strpos($path_check, DIR_ROOT_CORE) !== false) {
						$root = DIR_ROOT_CORE;
					} else if (strpos($path_check, DIR_ROOT_STORAGE) !== false) {
						$root = DIR_ROOT_STORAGE;
					} else {
						$root = DIR_ROOT_SITE;
					}
					$code .= CSSUriRewriter::rewrite(file_get_contents($file), dirname($file), $root).PHP_EOL;
				} else {
					$code .= file_get_contents($file).PHP_EOL;
				}
			}

			$code .= '/**end**/';
			
			$code = Response::parse($code);
			
			// Sort dates, newest first
			rsort($arr_last_modified);
							
			if ($create_archive) {
				
				$check_ie_tag = $arr_last_modified[0].'_'.(int)getLabel('use_servers', 'D', true);
				
				if ($ie_tag == $check_ie_tag) { // Check for valid etag, we don't want invalid requests to fill up archive folder
					
					FileStore::storeFile($path, $code);
				} else {
					
					// Archive file no longer exists or invalid etag specified
					pages::noPage(true);
				}
			}
		}
				
		Response::sendFileHeaders($code, false, [
			'Content-Type: '.($type == SiteStartEnvironment::MATERIAL_JS ? 'application/javascript': 'text/css'),
			'ETag: "'.$ie_tag.'"',
			'Cache-Control: max-age='.self::$cache_length,
			'1100CC-Cached: '.(int)$create_archive
		]);
		
		echo $code;
	}
	
	public static function getVersion($arr_files, $arr_modules) {
					
		// get file last modified dates
		$arr_last_modified = [];
		unset($arr_files['modules']);
		foreach ($arr_files as $file) {
			$arr_last_modified[] = filemtime($file);
		}
		foreach ($arr_modules as $key => $value) {
			$arr_last_modified[] = $value['time'];
		}
		// sort dates, newest first
		rsort($arr_last_modified);
	
		// output latest timestamp and other
		return $arr_last_modified[0].'_'.(int)getLabel('use_servers', 'D', true);
	}
}

/**
 * Rewrite file-relative URIs as root-relative in CSS files
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
 
class CSSUriRewriter {
    
    /**
     * rewrite() and rewriteRelative() append debugging information here
     *
     * @var string
     */
	public static $debugText = '';
    
    /**
     * In CSS content, rewrite file relative URIs as root relative
     * 
     * @param string $css
     * 
     * @param string $currentDir The directory of the current CSS file.
     * 
     * @param string $docRoot The document root of the web site in which 
     * the CSS file resides (default = $_SERVER['DOCUMENT_ROOT']).
     * 
     * @param array $symlinks (default = array()) If the CSS file is stored in 
     * a symlink-ed directory, provide an array of link paths to
     * target paths, where the link paths are within the document root. Because 
     * paths need to be normalized for this to work, use "//" to substitute 
     * the doc root in the link paths (the array keys). E.g.:
     * <code>
     * array('//symlink' => '/real/target/path') // unix
     * array('//static' => 'D:\\staticStorage')  // Windows
     * </code>
     * 
     * @return string
     */
	public static function rewrite($css, $currentDir, $docRoot = null, $symlinks = []) {
		
        self::$_docRoot = self::_realpath(
            $docRoot ? $docRoot : $_SERVER['DOCUMENT_ROOT']
        );
        self::$_currentDir = self::_realpath($currentDir);
        self::$_symlinks = [];
        
        // normalize symlinks
        foreach ($symlinks as $link => $target) {
            $link = ($link === '//')
                ? self::$_docRoot
                : str_replace('//', self::$_docRoot . '/', $link);
            $link = strtr($link, '/', DIRECTORY_SEPARATOR);
            self::$_symlinks[$link] = self::_realpath($target);
        }
        
        self::$debugText .= "docRoot    : " . self::$_docRoot . "\n"
                          . "currentDir : " . self::$_currentDir . "\n";
        if (self::$_symlinks) {
            self::$debugText .= "symlinks : " . var_export(self::$_symlinks, 1) . "\n";
        }
        self::$debugText .= "\n";
        
        $css = self::_trimUrls($css);
        
        // rewrite
        $css = preg_replace_callback('/@import\\s+([\'"])(.*?)[\'"]/'
            ,['CSSUriRewriter', '_processUriCB'], $css);
        $css = preg_replace_callback('/url\\(\\s*([\'"](.*?)[\'"]|[^\\)\\s]+)\\s*\\)/'
            ,['CSSUriRewriter', '_processUriCB'], $css);

        return $css;
    }
    
    /**
     * In CSS content, prepend a path to relative URIs
     * 
     * @param string $css
     * 
     * @param string $path The path to prepend.
     * 
     * @return string
     */
    public static function prepend($css, $path) {
		
        self::$_prependPath = $path;
        
        $css = self::_trimUrls($css);
        
        // append
        $css = preg_replace_callback('/@import\\s+([\'"])(.*?)[\'"]/'
            ,['CSSUriRewriter', '_processUriCB'], $css);
        $css = preg_replace_callback('/url\\(\\s*([\'"](.*?)[\'"]|[^\\)\\s]+)\\s*\\)/'
            ,['CSSUriRewriter', '_processUriCB'], $css);

        self::$_prependPath = null;
        return $css;
    }
    
    /**
     * Get a root relative URI from a file relative URI
     *
     * <code>
     * Minify_CSS_UriRewriter::rewriteRelative(
     *       '../img/hello.gif'
     *     , '/home/user/www/css'  // path of CSS file
     *     , '/home/user/www'      // doc root
     * );
     * // returns '/img/hello.gif'
     * 
     * // example where static files are stored in a symlinked directory
     * Minify_CSS_UriRewriter::rewriteRelative(
     *       'hello.gif'
     *     , '/var/staticFiles/theme'
     *     , '/home/user/www'
     *     , array('/home/user/www/static' => '/var/staticFiles')
     * );
     * // returns '/static/theme/hello.gif'
     * </code>
     * 
     * @param string $uri file relative URI
     * 
     * @param string $realCurrentDir realpath of the current file's directory.
     * 
     * @param string $realDocRoot realpath of the site document root.
     * 
     * @param array $symlinks (default = array()) If the file is stored in 
     * a symlink-ed directory, provide an array of link paths to
     * real target paths, where the link paths "appear" to be within the document 
     * root. E.g.:
     * <code>
     * array('/home/foo/www/not/real/path' => '/real/target/path') // unix
     * array('C:\\htdocs\\not\\real' => 'D:\\real\\target\\path')  // Windows
     * </code>
     * 
     * @return string
     */
    public static function rewriteRelative($uri, $realCurrentDir, $realDocRoot, $symlinks = []) {
        // prepend path with current dir separator (OS-independent)
        $path = strtr($realCurrentDir, '/', DIRECTORY_SEPARATOR)  
            . DIRECTORY_SEPARATOR . strtr($uri, '/', DIRECTORY_SEPARATOR);
        
        self::$debugText .= "file-relative URI  : {$uri}\n"
                          . "path prepended     : {$path}\n";
        
        // "unresolve" a symlink back to doc root
        foreach ($symlinks as $link => $target) {
            if (0 === strpos($path, $target)) {
                // replace $target with $link
                $path = $link . substr($path, strlen($target));
                
                self::$debugText .= "symlink unresolved : {$path}\n";
                
                break;
            }
        }
        // strip doc root
        $path = substr($path, strlen($realDocRoot));
        
        self::$debugText .= "docroot stripped   : {$path}\n";
        
        // fix to root-relative URI
        $uri = strtr($path, '/\\', '//');
        $uri = self::removeDots($uri);
      
        self::$debugText .= "traversals removed : {$uri}\n\n";
        
        return $uri;
    }

    /**
     * Remove instances of "./" and "../" where possible from a root-relative URI
     *
     * @param string $uri
     *
     * @return string
     */
    public static function removeDots($uri) {
        $uri = str_replace('/./', '/', $uri);
        // inspired by patch from Oleg Cherniy
        do {
            $uri = preg_replace('@/[^/]+/\\.\\./@', '/', $uri, 1, $changed);
        } while ($changed);
        return $uri;
    }

    /**
     * Get realpath with any trailing slash removed. If realpath() fails,
     * just remove the trailing slash.
     * 
     * @param string $path
     * 
     * @return mixed path with no trailing slash
     */
    protected static function _realpath($path) {
        $realPath = realpath($path);
        if ($realPath !== false) {
            $path = $realPath;
        }
        return rtrim($path, '/\\');
    }

    /**
     * Directory of this stylesheet
     *
     * @var string
     */
    private static $_currentDir = '';

    /**
     * DOC_ROOT
     *
     * @var string
     */
    private static $_docRoot = '';

    /**
     * directory replacements to map symlink targets back to their
     * source (within the document root) E.g. '/var/www/symlink' => '/var/realpath'
     *
     * @var array
     */
    private static $_symlinks = [];

    /**
     * Path to prepend
     *
     * @var string
     */
    private static $_prependPath = null;

    /**
     * @param string $css
     *
     * @return string
     */
    private static function _trimUrls($css) {
		
        return preg_replace('/
            url\\(      # url(
            \\s*
            ([^\\)]+?)  # 1 = URI (assuming does not contain ")")
            \\s*
            \\)         # )
        /x', 'url($1)', $css);
    }

    /**
     * @param array $m
     *
     * @return string
     */
    private static function _processUriCB($m) {
		
        // $m matched either '/@import\\s+([\'"])(.*?)[\'"]/' or '/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
        $isImport = ($m[0][0] === '@');
        // determine URI and the quote character (if any)
        if ($isImport) {
            $quoteChar = $m[1];
            $uri = $m[2];
        } else {
            // $m[1] is either quoted or not
            $quoteChar = ($m[1][0] === "'" || $m[1][0] === '"')
                ? $m[1][0]
                : '';
            $uri = ($quoteChar === '')
                ? $m[1]
                : substr($m[1], 1, strlen($m[1]) - 2);
        }
        // if not root/scheme relative and not starts with scheme
        if (!preg_match('~^(/|[a-z]+\:)~', $uri)) {
            // URI is file-relative: rewrite depending on options
            if (self::$_prependPath === null) {
                $uri = self::rewriteRelative($uri, self::$_currentDir, self::$_docRoot, self::$_symlinks);
            } else {
                $uri = self::$_prependPath . $uri;
                if ($uri[0] === '/') {
                    $root = '';
                    $rootRelative = $uri;
                    $uri = $root . self::removeDots($rootRelative);
                } elseif (preg_match('@^((https?\:)?//([^/]+))/@', $uri, $m) && (false !== strpos($m[3], '.'))) {
                    $root = $m[1];
                    $rootRelative = substr($uri, strlen($root));
                    $uri = $root . self::removeDots($rootRelative);
                }
            }
        }
        return $isImport
            ? "@import {$quoteChar}{$uri}{$quoteChar}"
            : "url({$quoteChar}{$uri}{$quoteChar})";
    }
}
