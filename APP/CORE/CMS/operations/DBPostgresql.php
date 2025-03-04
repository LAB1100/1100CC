<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

namespace DBBase\Postgresql;

class DB extends \DBBase\DB {
	
	const ENGINE = parent::ENGINE_POSTGRESQL;
	
	const ENGINE_IS_POSTGRESQL = true;
	
	public static function getTableTemporary($identifier) { // Postgresql has its own temporary schema
	
		$name = (static::$arr_override_tables[$identifier] ?: (static::$arr_tables[$identifier] ?: $identifier));
	
		$name = str_replace('"', '', $name); // Remove a possible previous getTable parsing
		$arr_database_table = explode('.', $name);
		
		if (count($arr_database_table) > 1) {
			
			$name = '"'.$arr_database_table[1].'"';
			static::setDatabase($arr_database_table[0]);
		} else {
			
			$name = '"'.$name.'"';
			static::setDatabase(false);
		}
		
		return $name;
	}

	protected static function createConnection($arr_connection_details) {

		$host = ($arr_connection_details['host'] == 'localhost' ? static::$localhost : $arr_connection_details['host']);
		$arr_host = explode(':', $host);
		$host = $arr_host[0];
		$port = ($arr_host[1] ?: null);
		$host = ($host == 'localhost' && $port ? '127.0.0.1' : $host); // Port is required, force TCP
		$database = ($arr_connection_details['database'] ?: static::$database_home);
		
		$connection = pg_connect('host='.$host.' user='.$arr_connection_details['user'].' password='.$arr_connection_details['password'].' dbname='.$database.''.($port ? ' port='.$port : '').'');
		
		if (!$connection) {
			
			$str = pg_last_error($connection);

			switch ($str) {
				case 1040:
				case 1203:
				case 2002:
					if (\SiteStartEnvironment::getRequestState() == \SiteStartEnvironment::REQUEST_INDEX) {
						error('Server connection problem. Please refresh page to retry.');
					} else {
						error('Server connection problem.');
					}
				default:
					error('Database trouble.');
			}
		}
		
		$q = "
			SET NAMES 'UTF8';
			SET SESSION TIME ZONE 'UTC';
								
			SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;
		";
		
		try {

			pg_query($connection, $q);
		} catch (\Exception $e) {

			static::error(new \DBTrouble(pg_last_error($connection)));
		}
						
		return $connection;
	}
	
	public static function clearConnection() {
		
		// Nothing to do
	}
		
	public static function query($q) {
		
		if (!static::$connection_active_is_async) {
			
			try {

				$res = pg_query(static::$connection_active, $q);
			} catch (\Exception $e) {

				static::$last_query = $q;
				static::error(new \DBTrouble(pg_last_error(static::$connection_active)));
			}
		} else {
			
			$connection_async = static::newConnectionAsync();
			
			try {
				
				$res = pg_query($connection_async, $q);
			} catch (\Exception $e) {

				static::$last_query = $q;
				static::error(new \DBTrouble(pg_last_error($connection_async)));
			}
		}
		
		return new \DBResult($res);
	}

	public static function queryAsync($q) {
							
		pg_send_query(static::$connection_active, $q);

		static::$connection_active_is_async = true;
					
		onUserPoll(function() {
			
			//if (!pg_connection_busy(static::$connection_active) && pg_transaction_status(static::$connection_active) === PGSQL_TRANSACTION_IDLE) {
			if (!pg_connection_busy(static::$connection_active)) {
				return true;
			} else {
				return false;
			}
		}, function() {
			
			pg_cancel_query(static::$connection_active);
		});
		
		$res = pg_get_result(static::$connection_active);
		
		static::$connection_active_is_async = false;
		
		if (pg_result_status($res) == PGSQL_FATAL_ERROR) { // Something went wrong, but could not be caught since it happened asynchronously

			static::$last_query = $q;
			static::error(new \DBTrouble(pg_result_error($res)));
		}
					
		return new \DBResult($res);
	}
	
	public static function queryMulti($q) {
		
		pg_send_query(static::$connection_active, $q);
		
		$arr_res = [];

		while ($res = pg_get_result(static::$connection_active)) {
			
			if (pg_result_status($res) == PGSQL_FATAL_ERROR) {
				
				static::$last_query = $q;
				static::error(new \DBTrouble(pg_result_error($res)));
			}
								
			$arr_res[] = new \DBResult($res);
		}
		
		return $arr_res;
	}
	
	public static function prepare($q) {
		
		try {
			
			$statement = new \DBStatement(false);
			
			$identifier = $statement->getIdentifier();
			
			$res = pg_prepare(static::$connection_active, $identifier, $q);
		} catch (\Exception $e) {
				
			\DBStatement::reset();
			
			static::$last_query = $q;
			static::error(new \DBTrouble(pg_last_error(static::$connection_active)));
		}
		
		return $statement;
	}
	
	public static function isReady($connection = false) {
		
		$connection = ($connection !== false ? $connection : static::$connection_active);
			
		if (!pg_connection_busy($connection) && pg_transaction_status($connection) === PGSQL_TRANSACTION_IDLE) {
			return true;
		}
				
		return false;
	}
	
	public static function isActive($connection = false) {
		
		if ($connection === false) {
			
			if (static::$connection_active_is_async) {
				$connection = static::newConnectionAsync();
			} else {
				$connection = static::$connection_active;
			}
		}
		
		try {
						
			if (!$connection || pg_transaction_status($connection) === PGSQL_TRANSACTION_UNKNOWN) {
				return false;
			}
		} catch (\Exception $e) {
			
			return false;
		}
		
		return true;
	}
	
	protected static function doClose($connection = false) {
		
		$connection = ($connection !== false ? $connection : static::$connection_active);
		
		try {
			
			pg_close($connection);
		} catch (\Exception $e) {
			
		}
	}
	
	public static function lastInsertID() {
		
		$res = pg_query(static::$connection_active, "SELECT LASTVAL()");
		$arr_row = pg_fetch_row($res);
			
		return $arr_row[0];
	}
	
	public static function getErrorMessage($code) {
		
		$msg = false;
		
		switch ($code) {
			case 1264:
			case 1406:
				$msg = getLabel('msg_error_database_data_field_limit');
				break;
		}
		
		return $msg;
	}
}

class DBStatement extends \DBBase\DBStatement {
	
	protected static $count_statement = 0;
	
	private $arr_parameters = [];
	private $arr_parameters_template = [];
	private $str_identifier = '';
	
	public function __construct($statement) {
		
		parent::__construct($statement);
		
		$this->arr_parameters_template = array_fill(0, count($this->arr_variables), null); // Use template to make sure the keys remain in original position/sequence
	}
	
	public function getIdentifier() {
		
		static::$count_statement++;
		
		$this->str_identifier = '1100CC_'.static::$count_statement;
		
		return $this->str_identifier;
	}

	public function bindParameters($arr) {

		$this->arr_parameters = $this->arr_parameters_template;
		
		foreach ($arr as $variable => $value) {
			
			$num_position = $this->arr_variables[$variable][0];
			
			$this->arr_parameters[$num_position] = $value;
		}
	}
	
	public static function assignParameter($variable) {
		
		$num_position = static::$arr_assign_variables[$variable][0];
	
		return '$'.($num_position+1);
	}
	
	public function execute() {
		
		$this->statement = pg_execute(\DB::$connection_active, $this->str_identifier, $this->arr_parameters);
		
		return new \DBResult($this->statement);
	}
	
	public function getAffectedRowCount() {
	
		return pg_affected_rows($this->statement);
	}
	
	public function close() {
		
		\DB::query('DEALLOCATE "'.$this->str_identifier.'"');
	}
}

class DBResult extends \DBBase\DBResult {
	
	public function fetchArray() {
		
		return pg_fetch_array($this->result);
	}
	
	public function fetchAssoc() {
		
		return pg_fetch_assoc($this->result);
	}
	
	public function fetchRow() {
		
		return pg_fetch_row($this->result);
	}
	
	public function seekRow($i) {
		
		return pg_result_seek($this->result, $i);
	}
	
	public function getRowCount() {
		
		return pg_num_rows($this->result);
	}
	
	public function getFieldCount() {
		
		return pg_num_fields($this->result);
	}
	
	public function getFieldMeta($i) {
		
		return [
			'name' => pg_field_name($this->result, $i),
			'table' => pg_field_table($this->result, $i)
		];
	}
	
	public function getFieldDataType($i) {
		
		$type = pg_field_type($this->result, $i);
		
		switch ($type) {
			case 'bool':
				$int = \DBFunctions::TYPE_BOOLEAN;
				break;
			default:
				$int = 0;
		}
		
		return $int;
	}
	
	public function getAffectedRowCount() {
		
		return pg_affected_rows($this->result);
	}
	
	public function freeResult() {
		
		return pg_free_result($this->result);
	}
}

class DBFunctions extends \DBBase\DBFunctions {
	
	const CAST_TYPE_INTEGER = 'INTEGER';
	const CAST_TYPE_DECIMAL = 'DECIMAL';
	const CAST_TYPE_STRING = 'VARCHAR';
	const CAST_TYPE_BOOLEAN = 'BOOLEAN';
	const CAST_TYPE_BINARY = 'BYTEA';
			
	public static function strEscape($str) {
		
		if (!$str) {
			return (string)$str;
		}
		
		return pg_escape_string(\DB::$connection_active, $str);
	}
	
	public static function escapeAs($value, $what) {
		
		switch ($what) {
			
			case static::TYPE_BOOLEAN:
				$value = ($value ? 'TRUE' : 'FALSE');
				break;
			case static::TYPE_BINARY:
				$value = 'E\'\\\\x'.bin2hex($value).'\'';
				break;
		}
		
		return $value;
	}
	
	public static function unescapeAs($value, $what) {
		
		switch ($what) {
			
			case static::TYPE_BOOLEAN:
				$value = ($value === 't' ? true : false);
				break;
			case static::TYPE_INTEGER:
				$value = (int)$value;
				break;
			case static::TYPE_FLOAT:
				$value = (float)$value;
				break;
			case static::TYPE_BINARY:
				$value = ($value ? hex2bin(substr($value, 2)) : ''); // Remove \x;
				break;
		}
		
		return $value;
	}
	
	public static function castAs($value, $what, $length = false) {
		
		if (!$what) {
			return $value;
		}
		
		return 'CAST('.$value.' AS '.$what.')';
	}
	
	public static function convertTo($value, $to, $from, $format = null) {
		
		if ($from === static::TYPE_BINARY && $to === static::TYPE_STRING) {
			if ($format === static::FORMAT_STRING_BASE64) {
				return 'ENCODE('.$value.', \'base64\')';
			} else {
				return 'ENCODE('.$value.', \'hex\')';
			}
		} else if ($from === static::TYPE_STRING && $to === static::TYPE_BINARY) {
			if ($format === static::FORMAT_STRING_BASE64) {
				return 'DECODE('.$value.', \'base64\')';
			} else {
				return 'DECODE('.$value.', \'hex\')';
			}
		}
		
		return parent::convertTo($value, $to, $from, $format);
	}
		
	public static function onConflict($key, $arr_values, $sql_other = false) {
		
		if (!$arr_values && !$sql_other) {
			return 'ON CONFLICT ('.$key.') DO NOTHING';
		}
		
		$sql = 'ON CONFLICT ('.$key.') DO UPDATE SET';
		$sql_affix = '';
		
		if ($arr_values) {
			
			foreach ($arr_values as &$value) {
			
				$value = $value.' = EXCLUDED.'.$value;
			}
			
			$sql_affix .= ' '.implode(',', $arr_values);
		}
		
		if ($sql_other) {
			
			$sql_other = preg_replace_callback('/\[(.*?)\]/', function ($arr_matches) { // [fieldname]
				return 'EXCLUDED.'.$arr_matches[1]; 
			}, $sql_other);
			
			$sql_affix .= ($sql_affix ? ',' : '').' '.$sql_other;
		}		
		
		return $sql.$sql_affix;
	}
	
	public static function group2String($sql_expression, $str_separator = ', ', $sql_clause = false) {
		
		if ($sql_clause && strStartsWith($sql_expression, 'DISTINCT')) {
			
			/* It's not valid to, per engine: first GROUP/DISTINCT the values and subsequently order the result on another value. We would end up with a possible irregular ordering.
			 * We have to let the engine do the ordering first (keep clause) and manually do the deduplication (function)
			 * Functions are in DBSetup, more here: https://stackoverflow.com/a/25192555
			 */
		
			$sql_expression = substr($sql_expression, 8); // Remove DISTINCT
			$sql = 'array_to_string(array_agg_uniq('.$sql_expression.' '.$sql_clause.'), \''.$str_separator.'\')';
			
			return $sql;
		}
		
		$sql = 'string_agg('.$sql_expression.', \''.$str_separator.'\' '.$sql_clause.')';

		return $sql;
	}
	
	public static function fields2String($str_separator, ...$sql_fields) {
		
		$str_sql_separator = ' || ';
		if ($str_separator) {
			$str_sql_separator = static::sqlFieldNotLiteral($str_separator);
			$str_sql_separator = ' || '.$str_sql_separator.' || ';
		}
		
		return implode($str_sql_separator, $sql_fields);
	}
	
	public static function interval($amount, $unit, $field = false) {
		
		$sql = 'INTERVAL \''.(int)$amount.' '.$unit.'\'';
		
		if ($field) {
			
			$sql = '('.$sql.' * '.$field.')';
		}
		
		return $sql;
	}
	
	public static function timeDifference($unit, $field_start, $field_end) {
		
		$sql_adjust = '';
		
		switch ($unit) {
			case 'MICROSECOND':
				$sql_adjust = ' * 1000 * 1000';
				break;
			case 'MILLISECOND':
				$sql_adjust = ' * 1000';
				break;
			case 'SECOND':
				$sql_adjust = '';
				break;
			case 'MINUTE':
				$sql_adjust = ' / 60';
				break;
		}
		
		$sql = 'EXTRACT(EPOCH FROM ('.$field_end.' - '.$field_start.'))'.$sql_adjust;
		
		return $sql;
	}
	
	public static function timeNow($do_transaction = false) {
		
		if ($do_transaction) {
			return 'TRANSACTION_TIMESTAMP()'; // Transaction init time
		}
		
		return 'STATEMENT_TIMESTAMP()'; // Statement time
	}
	
	public static function searchRegularExpression($sql_field, $sql_expression, $str_flags = false) {
		
		$sql_field = static::sqlFieldLiteral($sql_field);
		$sql_expression = static::sqlFieldLiteral($sql_expression);
		
		$str_flags = parseRegularExpressionFlags($str_flags, [REGEX_NOFLAG.REGEX_CASE_INSENSITIVE => 'c', REGEX_CASE_INSENSITIVE => 'i', REGEX_LINE => 'w', REGEX_DOT_SPECIAL => 'p']);
		
		return 'REGEXP_LIKE('.$sql_field.', '.$sql_expression.', \''.$str_flags.'\')';
	}
	
	public static function searchMatchSensitivity($sql_field, $sql_search, $mode_wildcards = MATCH_ANY, $do_case = false, $do_diacritics = false) {
		
		$sql_field = static::sqlFieldLiteral($sql_field);
		$sql_search = static::sqlFieldLiteral($sql_search, true);
		$is_literal_search = ($sql_search['type'] === static::SQL_IS_LITERAL);
		$sql_search = $sql_search['sql'];
		
		if (!$do_case) {
			$sql_field = 'LOWER('.$sql_field.')';
			$sql_search = ($is_literal_search ? strtolower($sql_search) : 'LOWER('.$sql_search.')');
		}
		if (!$do_diacritics) {
			$sql_field = 'UNACCENT('.$sql_field.')';
		}
		
		$sql = '';
		
		if ($is_literal_search) {
			$sql = $sql_field.' LIKE \''.($mode_wildcards === MATCH_END || $mode_wildcards === MATCH_ANY ? '%' : '').$sql_search.($mode_wildcards === MATCH_START || $mode_wildcards === MATCH_ANY ? '%' : '').'\'';
		} else {
			$sql = $sql_field.' LIKE CONCAT(\''.($mode_wildcards === MATCH_END || $mode_wildcards === MATCH_ANY ? '%' : '').'\', '.$sql_search.', \''.($mode_wildcards === MATCH_START || $mode_wildcards === MATCH_ANY ? '%' : '').'\')';
		}
			
		return $sql;
	}
	
	public static function sqlTableOptions($engine) {
		
		$sql = '';

		return $sql;
	}
	
	public static function bulkSelect($q) {
		
		$statement = new \DBStatement(false);
		
		$identifier = $statement->getIdentifier();
		
		\DB::startTransaction('bulk_select');

		\DB::query('DECLARE "'.$identifier.'_cursor" NO SCROLL CURSOR FOR ('.$q.')');
		
		/*$res = pg_prepare(\DB::$connection_active, $identifier, 'FETCH NEXT FROM "'.$identifier.'_cursor"');
		$arr_parameters = [];

		while ($arr_row = pg_fetch_row(pg_execute(\DB::$connection_active, $identifier, $arr_parameters))) {
				
			yield $arr_row;
		}*/
		
		$res = pg_prepare(\DB::$connection_active, $identifier, 'FETCH FORWARD 1000 FROM "'.$identifier.'_cursor"');
		$arr_parameters = [];
		
		while ($res = pg_execute(\DB::$connection_active, $identifier, $arr_parameters)) {
			
			while ($arr_row = pg_fetch_row($res)) {
				
				yield $arr_row;
			}
		}
		
		\DB::query('DEALLOCATE "'.$identifier.'"');
		
		\DB::commitTransaction('bulk_select');
	}
}

class DBTrouble extends \DBBase\DBTrouble {}

class DBSetup extends \DBBase\DBSetup {
	
	public static function init() {
		
		\DB::queryMulti("
			CREATE OR REPLACE FUNCTION f_array_append_uniq (anyarray, anyelement)
				RETURNS anyarray
				LANGUAGE sql STRICT IMMUTABLE AS
			'SELECT CASE WHEN $1[array_upper($1, 1)] = $2 THEN $1 ELSE $1 || $2 END'; -- Only add next element to array if it is different from the previous value (discard adjacent duplicates)
			
			CREATE OR REPLACE AGGREGATE array_agg_uniq (anyelement) (
				SFUNC = f_array_append_uniq
				, STYPE = anyarray
				, INITCOND = '{}'
			);
		");
		
		return true;
	}
}
