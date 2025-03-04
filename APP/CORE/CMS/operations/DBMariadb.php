<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

namespace DBBase\Mariadb;

require_once('DBMysql.php');

class DB extends \DBBase\Mysql\DB {
	
	const ENGINE_IS_MARIADB = true;
}

class DBFunctions extends \DBBase\Mysql\DBFunctions {
	
	public static function searchRegularExpression($sql_field, $sql_expression, $str_flags = false) {
		
		$sql_field = static::sqlFieldLiteral($sql_field);
		$sql_expression = static::sqlFieldLiteral($sql_expression, true);
		$is_literal_expression = ($sql_expression['type'] === static::SQL_IS_LITERAL);
		$sql_expression = $sql_expression['sql'];
		
		$str_flags = parseRegularExpressionFlags($str_flags, [REGEX_NOFLAG.REGEX_CASE_INSENSITIVE => '(?-i)', REGEX_CASE_INSENSITIVE => '(?i)', REGEX_LINE => '(?m)', REGEX_NOFLAG.REGEX_DOT_SPECIAL => '(?s)']);
		$sql = '';
		
		if ($is_literal_expression) {
			$sql = $sql_field.' REGEXP \''.$str_flags.$sql_expression.'\'';
		} else {
			$sql = $sql_field.' REGEXP CONCAT(\''.$str_flags.'\', '.$sql_expression.')';
		}
		
		return $sql;
	}
}

class DBStatement extends \DBBase\Mysql\DBStatement {}
class DBResult extends \DBBase\Mysql\DBResult {}
class DBTrouble extends \DBBase\Mysql\DBTrouble {}
class DBSetup extends \DBBase\Mysql\DBSetup {}
