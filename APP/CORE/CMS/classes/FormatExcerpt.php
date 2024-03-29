<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2024 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class FormatExcerpt {

	public static function parse($html, $string, $is_url = false, $xlen = 255, $fill_start = '[...] ', $fill_end = ' [...]') {
		
		// the excerpt will come out as
		// [...] before linktext after [...]
		
		$retval = '';

		$f1len = mb_strlen($fill_start);
		$f2len = mb_strlen($fill_end);
		
		if ($is_url) {
			// extract all links
			preg_match_all('/<a[^>]*href=["]([^"]*)["][^>]*>(.*?)<\/a>/si', $html, $matches);
			
			$num_matches = count($matches[0]);
			
			for ($i = 0; $i < $num_matches; $i++) {
				if (rawurldecode($matches[1][$i]) == rawurldecode($string)) {
					$string = $matches[0][$i];
					$linktext = FormatHTML::getTextContent($matches[2][$i]);
					break;
				}
			}
		}
		
		$before = '';
		$after = '';
		$pos = mb_stripos($html, $string);
		$before = FormatHTML::getTextContent(mb_substr($html, 0, $pos));
		
		$strlen = mb_strlen($string);
		$linktext = ($linktext ?: FormatHTML::getTextContent(mb_substr($html, $pos, $strlen)));

		$pos += $strlen;
		$after = FormatHTML::getTextContent(mb_substr($html, $pos));

		$tlen = mb_strlen($linktext);
		if ($tlen >= $xlen) {
			// Special case: The actual link text is already longer (or as long) as
			// requested. We don't use the "fillers" here but only return the
			// (shortened) link text itself.
			if ($tlen > $xlen) {
				$retval = mb_substr($linktext, 0, $xlen - 3) . '...';
			} else {
				$retval = $linktext;
			}
		} else {
			if (!empty($before)) {
				$tlen++;
			}
			if (!empty($after)) {
				$tlen++;
			}

			// make "before" and "after" text have equal length
			$rest = ($xlen - $tlen) / 2;

			// format "before" text
			$blen = mb_strlen($before);
			if ($blen < $rest) {
				// if "before" text is too short, make "after" text longer
				$rest += ($rest - $blen);
				$retval .= $before;
			} else if ($blen > $rest) {
				$work = mb_substr($before, -($rest * 2));
				$w = explode(' ', $work);
				array_shift($w); // drop first word, as it's probably truncated
				$w = array_reverse($w);

				$fill = $rest - $f1len;
				$b = '';
				foreach ($w as $word) {
					if (mb_strlen($b) + mb_strlen($word) + 1 > $fill) {
						break;
					}
					$b = $word.' '.$b;
				}
				$b = ltrim($b);

				$retval .= $fill_start.$b;

				$blen = mb_strlen($b);
				if ($blen < $fill) {
					$rest += ($fill - $blen);
				}
			}

			// actual link text
			$retval .= $linktext;

			// format "after" text
			$alen = mb_strlen($after);
			if ($alen < $rest) {
				$retval .= $after;
			} else if ($alen > $rest) {
				$work = mb_substr($after, 0, ($rest * 2));
				$w = explode(' ', $work);
				array_pop($w); // drop last word, as it's probably truncated

				$fill = $rest - $f2len;
				$a = '';
				foreach ($w as $word) {
					if (mb_strlen($a) + mb_strlen($word) + 1 > $fill) {
						break;
					}
					$a .= $word.' ';
				}
				$retval .= rtrim($a).$fill_end;
			}
		}

		return $retval;
	}
		
	public static function performHighlight($text, $string) {

		$count = 0;
		$result = preg_replace_callback("/".$string."+/i",
			function ($matches) use (&$count) {
				$count++;
				return '<em>'.$matches[0].'</em>';
			},
            $text);
			
		return ['result' => $result, 'count' => $count];
	}
	
	public static function countString($text, $string) {

		return substr_count(strtolower(strip_tags($text)), strtolower($string));
	}
}
