<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

namespace DBBase\Mysql;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Throw all MySQL errors
		
class DB extends \DBBase\DB {
	
	const ENGINE = parent::ENGINE_MYSQL;
	
	const ENGINE_IS_MYSQL = true;
		
	protected static function createConnection($arr_connection_details) {

		$host = ($arr_connection_details['host'] == 'localhost' ? static::$localhost : $arr_connection_details['host']);
		$arr_host = explode(':', $host);
		$host = $arr_host[0];
		$port = ($arr_host[1] ?? null);
		$host = ($host == 'localhost' && $port ? '127.0.0.1' : $host); // Port is required, force TCP
		$database = ($arr_connection_details['database'] ?: static::$database_home);
		
		try {
			
			$connection = new \mysqli($host, $arr_connection_details['user'], $arr_connection_details['password'], $database, $port);
		} catch (\Exception $e) {

			$str_message = 'Database trouble.';
			
			switch ($e->getCode()) {
				case 1040:
				case 1203:
				case 2002:
				
					if (\SiteStartEnvironment::getRequestState() == \SiteStartEnvironment::REQUEST_INDEX) {
						$str_message = 'Server connection problem. Please refresh page to retry.';
					} else {
						$str_message = 'Server connection problem.';
					}
					break;
			}
			
			error($str_message, TROUBLE_ERROR, LOG_BOTH, false, $e);
		}
		
		$connection->multi_query("
			SET NAMES utf8mb4 COLLATE ".\DBFunctions::COLLATE_AI_CI.";
			
			SET SESSION
				time_zone = '+00:00',
				sql_mode = (SELECT CONCAT(@@sql_mode, ',ANSI_QUOTES')),
				group_concat_max_len = 1000000,
				wait_timeout = 28800
			;
			
			SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;
		");
		
		while ($connection->more_results()) { // Flush multi query
			$connection->next_result();
		}
		
		return $connection;
	}
	
	public static function clearConnection() {
		
		if (!static::isActive()) {
			return false;
		}
		
		try {
			
			while (static::$connection_active->more_results()) { // Flush multi query
				static::$connection_active->next_result();
			}
			
			static::query('SELECT TRUE');
		} catch (\Exception $e) {

			return false;
		}
		
		return true;
	}
	
	public static function query($q, $modifier = null) {
		
		$modifier = ($modifier ?? MYSQLI_STORE_RESULT);
		
		try {

			$res = static::$connection_active->query(static::checkSQL($q), $modifier);
		} catch (\Exception $e) {

			static::$last_query = $q;
			static::error(new \DBTrouble($e->getMessage(), $e->getCode(), $e));
		}
		
		return new \DBResult($res);
	}

	public static function queryAsync($q) {
					
		try {
			
			static::$connection_active->query(static::checkSQL($q), MYSQLI_ASYNC);
		} catch (\Exception $e) {

			static::$last_query = $q;
			static::error(new \DBTrouble($e->getMessage(), $e->getCode(), $e));
		}
		
		$connection_async = static::$connection_active;
		
		static::setConnection(static::$connection_level, DB::MODE_CONNECT_SECONDARY); // Prepare/ready secondary connection
		
		onUserPoll(function() use ($connection_async) {
			
			$links = $errors = $reject = [$connection_async];
			
			return mysqli_poll($links, $errors, $reject, 0);
		}, function() use ($connection_async) {
			
			// Set/initiate secondary connection
			static::setConnectionDatabase(null);
			
			// Switch to-be-closed connection with new connection
			foreach (static::$arr_databases_levels_connection_primary[static::$connection_database] as $level => $connection) {
				
				if ($connection !== $connection_async) {
					continue;
				}
				
				static::$arr_databases_levels_connection_primary[static::$connection_database][$level] = static::$connection_active;
			}
			
			// Remove new connection from secondary library
			foreach (static::$arr_databases_levels_connections_secondary[static::$connection_database][static::$connection_level] as $key => $connection) {
				
				if ($connection !== static::$connection_active) {
					continue;
				}
				
				unset(static::$arr_databases_levels_connections_secondary[static::$connection_database][static::$connection_level][$key]);
				break;					
			}
			
			// Close old connection
			static::$connection_active->query('KILL QUERY '.$connection_async->thread_id);
			
			static::setConnection(static::$connection_level); // Exit with newly set primary connection
		});
		
		static::setConnection(static::$connection_level); // Continue with primary connection
		
		try {
			
			$res = static::$connection_active->reap_async_query();
			
			if (!$res) { // Possibly something else went wrong, but could not be caught since it happened asynchronously
				error();
			}
		} catch (\Exception $e) {
			
			$e = new \DBTrouble(static::$connection_active->error, static::$connection_active->errno);
				
			static::$last_query = $q;
			static::error($e);
		}
			
		return new \DBResult($res);
	}
	
	public static function queryMulti($q) {
		
		$arr_res = [];

		try {
				
			if (static::$connection_active->multi_query(static::checkSQL($q))) {
				
				do {
					$arr_res[] = new \DBResult(static::$connection_active->store_result());
				}
				while (static::$connection_active->more_results() && static::$connection_active->next_result());
			}
		} catch (\Exception $e) {

			static::$last_query = $q;
			static::error(new \DBTrouble($e->getMessage(), $e->getCode(), $e));
		}

		if (static::$connection_active->errno) { // Something went wrong, but could not be caught because only the first query can be caught
			
			$e = new \DBTrouble(static::$connection_active->error, static::$connection_active->errno);
			
			static::$last_query = $q;
			static::error($e);
		}
		
		return $arr_res;
	}
	
	public static function prepare($q) {

		try {
			
			$statement = static::$connection_active->prepare(static::checkSQL($q));
		} catch (\Exception $e) {
			
			\DBStatement::reset();

			static::$last_query = $q;
			static::error(new \DBTrouble($e->getMessage(), $e->getCode(), $e));
		}
		
		return new \DBStatement($statement);
	}
	
	public static function isReady($connection = null) {
		
		$connection = ($connection !== null ? $connection : static::$connection_active);
			
		$links = $errors = $reject = [$connection]; 
		
		if (mysqli_poll($links, $errors, $reject, 0) !== false) {				
			return true;
		}
				
		return false;
	}
	
	public static function isActive($connection = null) {
		
		if ($connection === null) {
			$connection = static::$connection_active;
		}
		
		try {
			
			if (!$connection || $connection->errno == 2006 || $connection->errno == 2013 || $connection->errno == 2014 || !$connection->stat()) { // 2006: Server has gone away. 2013: Lost connection during query. 2014: Command out of sync. No stat: Server does not respond at all
				return false;
			}
		} catch (\Exception $e) {
			
			return false;
		}
		
		return true;
	}
	
	protected static function doClose($connection = null) {
		
		$connection = ($connection !== null ? $connection : static::$connection_active);
		
		try {
			
			$connection->close();
		} catch (\Exception $e) {
			
		}
	}
	
	public static function lastInsertID() {

		return static::$connection_active->insert_id;
	}

	/*public static function checkSQL($sql) {
		
		if (static::getTransaction() === false) {
			return $sql;
		}
		
		$has_ddl = \DBFunctions::detectDDL($sql);
		
		if ($has_ddl !== false) {
			error(getLabel('msg_error_database_transaction'), TROUBLE_DATABASE);
		}
		
		return $sql;
	}*/
	
	public static function noCache() {
		
		static::query("SET SESSION query_cache_type = 0;");
	}
}

class DBStatement extends \DBBase\DBStatement {
	
	private $arr_parameters = [];
	private $arr_parameters_template = [];
	
	public function __construct($statement) {
		
		parent::__construct($statement);
		
		$this->arr_parameters_template = array_fill(0, (count($this->arr_variables) + 1), null); // Use template to make sure the keys remain in original position/sequence, add one first position for 'format bind'
		
		$str_format = '';
		$this->arr_parameters_template[0] =& $str_format;
		
		foreach ($this->arr_variables as $arr_variable) {
			$str_format .= $arr_variable[1];
		}
	}
	
	public function bindParameters($arr) {

		$this->arr_parameters = $this->arr_parameters_template;
				
		foreach ($arr as $variable => $value) {
			
			$num_position = $this->arr_variables[$variable][0]+1;
			
			$this->arr_parameters[$num_position] = $value;
		}
		
		$this->statement->bind_param(...$this->arr_parameters);
	}
	
	public function execute() {
		
		$this->statement->execute();
		
		return new \DBResult($this->statement->get_result());
	}
	
	public function getAffectedRowCount() {
	
		return $this->statement->affected_rows;
	}
	
	public function close() {
		
		$this->statement->close();
	}
}

class DBResult extends \DBBase\DBResult {
	
	public function fetchArray() {
		
		return $this->result->fetch_array();
	}
	
	public function fetchAssoc() {
		
		return $this->result->fetch_assoc();
	}
	
	public function fetchRow() {
		
		return $this->result->fetch_row();
	}
	
	public function seekRow($i) {
		
		return $this->result->data_seek($i);
	}
	
	public function getRowCount() {
		
		return $this->result->num_rows;
	}
	
	public function getFieldCount() {
		
		return $this->result->field_count;
	}
	
	public function getFieldMeta($i) {
		
		$arr_field = $this->result->fetch_field_direct($i);
		
		return [
			'name' => $arr_field->name,
			'table' => $arr_field->table
		];
	}
	
	public function getFieldDataType($i) {
		
		$arr_field = $this->result->fetch_field_direct($i);
		
		switch ($arr_field->type) {
			case MYSQLI_TYPE_TINY:
				if ($arr_field->length == 1) {
					$int = \DBFunctions::TYPE_BOOLEAN;
				} else {
					$int = \DBFunctions::TYPE_INTEGER;
				}
				break;
			case MYSQLI_TYPE_SHORT:
			case MYSQLI_TYPE_LONG:
				$int = \DBFunctions::TYPE_INTEGER;
				break;
			case MYSQLI_TYPE_FLOAT:
				$int = \DBFunctions::TYPE_FLOAT;
				break;
			default:
				$int = 0;
		}
		
		return $int;
	}
	
	public function getAffectedRowCount() {
		
		return \DB::$connection_active->affected_rows;
	}
	
	public function freeResult() {
		
		return $this->result->free_result();
	}
}

class DBFunctions extends \DBBase\DBFunctions {
	
	const CAST_TYPE_INTEGER = 'SIGNED';
	const CAST_TYPE_DECIMAL = 'DECIMAL';
	const CAST_TYPE_STRING = 'CHAR';
	const CAST_TYPE_BOOLEAN = 'SIGNED';
	const CAST_TYPE_BINARY = 'BINARY';
	
	const INDEX_HASH = 'HASH';
	const INDEX_LTF = 'BTREE';
	
	const COLLATE_AI_CI = 'utf8mb4_unicode_ci'; // utf8mb4_0900_ci
	const COLLATE_AS_CI = 'utf8mb4_unicode_as_ci'; // utf8mb4_0900_as_ci
	const COLLATE_BINARY = 'utf8mb4_bin'; // utf8mb4_0900_bin
			
	public static function strEscape($str) {
		
		if (!$str) {
			return (string)$str;
		}
		
		return \DB::$connection_active->real_escape_string($str);
	}
	
	public static function escapeAs($value, $what) {
		
		switch ($what) {
			
			case static::TYPE_BOOLEAN:
				$value = ($value ? 'TRUE' : 'FALSE');
				break;
			case static::TYPE_BINARY:
				$value = '\''.\DB::$connection_active->real_escape_string($value).'\'';
				break;
		}
		
		return $value;
	}
	
	public static function unescapeAs($value, $what) {
		
		switch ($what) {
			
			case static::TYPE_BOOLEAN:
				$value = ($value ? true : false);
				break;
			case static::TYPE_INTEGER:
				$value = (int)$value;
				break;
			case static::TYPE_FLOAT:
				$value = (float)$value;
				break;
			case static::TYPE_BINARY:
				$value = $value;
				break;
		}
		
		return $value;
	}
	
	public static function castAs($value, $what, $length = false) {
		
		if (!$what) {
			return $value;
		}
		
		return 'CAST('.$value.' AS '.$what.($length ? '('.$length.')' : '').')';
	}
	
	public static function castColumnAs($column, $what, $length = false) {
		
		$str_length = ($length ? '('.$length.')' : '');
		
		if ($what === static::CAST_TYPE_BINARY && !$length) {
			$what = 'VARBINARY(1000)'; // Length is arbitrary, but still indexable
		} else if ($what === static::CAST_TYPE_STRING && !$length) {
			$what = 'VARCHAR(1000)'; // Length is arbitrary
		}
		
		return $column.' '.$what.$str_length;
	}
	
	public static function convertTo($value, $to, $from, $format = null) {
		
		if ($from === static::TYPE_BINARY && $to === static::TYPE_STRING) {
			if ($format === static::FORMAT_STRING_BASE64) {
				return 'TO_BASE64('.$value.')';
			} else {
				return 'HEX('.$value.')';
			}
		} else if ($from === static::TYPE_STRING && $to === static::TYPE_BINARY) {
			if ($format === static::FORMAT_STRING_BASE64) {
				return 'FROM_BASE64('.$value.')';
			} else {
				return 'UNHEX('.$value.')';
			}
		}
		
		return parent::convertTo($value, $to, $from, $format);
	}

	public static function group2String($sql_expression, $str_separator = ', ', $sql_clause = false) {
		
		$sql = 'GROUP_CONCAT('.$sql_expression.' '.$sql_clause.' SEPARATOR \''.$str_separator.'\')';
		
		return $sql;
	}
	
	public static function updateWith($table, $alias, $column, $with, $arr_set) {
		
		$sql_join = (is_array($with) ? $with[0] : $with);
		
		foreach ($arr_set as $field => &$value) {
			$value = $alias.'.'.$field.' = '.$value;
		}
		unset($value);
				
		$sql = "UPDATE ".$table." AS ".$alias."
				".$sql_join."
			SET ".implode(',', $arr_set)."
			WHERE TRUE
		";
		
		return $sql;
	}
	
	public static function deleteWith($table, $alias, $column, $with) {
		
		$sql_join = (is_array($with) ? $with[0] : $with);
				
		$sql = "DELETE ".$alias."
			FROM ".$table." AS ".$alias."
				".$sql_join."
			WHERE TRUE
		";
		
		return $sql;
	}
	
	public static function onConflict($key, $arr_values, $sql_other = false) {
		
		$sql = 'ON DUPLICATE KEY UPDATE';
		$sql_affix = '';
		
		if (!$arr_values && !$sql_other) {
			
			$arr_key = explode(',', $key);
			
			$sql_affix = ' '.$arr_key[0].' = VALUES('.$arr_key[0].')'; // Select the first field of the key constraint and dummy-update that one
		} else {
		
			if ($arr_values) {
				
				foreach ($arr_values as &$value) {
					
					$value = $value.' = VALUES('.$value.')';
				}
				
				$sql_affix .= ' '.implode(',', $arr_values);
			}
			
			if ($sql_other) {
				
				$sql_other = preg_replace_callback('/\[(.*?)\]/', function ($arr_matches) { // [fieldname]
					return 'VALUES('.$arr_matches[1].')'; 
				}, $sql_other);
				
				$sql_affix .= ($sql_affix ? ',' : '').' '.$sql_other;
			}
		}
		
		return $sql.$sql_affix;
	}
	
	public static function interval($amount, $unit, $field = false) {
		
		$sql = 'INTERVAL '.($field ? $field.' * ' : '').(int)$amount.' '.$unit;
		
		return $sql;
	}
	
	public static function timeDifference($unit, $field_start, $field_end) {
		
		$sql = 'TIMESTAMPDIFF('.$unit.', '.$field_start.', '.$field_end.')';
		
		return $sql;
	}
	
	public static function dateTimeNow($do_transaction = false, $do_precision = false) {
		
		if ($do_transaction) {
			// Not implemented, could use a SET variable with time at start of transaction 
		}
		
		if ($do_precision) { // Precision is not the default in MySQL
			return 'NOW(6)';
		}
		
		return 'NOW()'; // Statement time
	}
	
	public static function numTimeNow($do_precision = false) {
		
		return parent::numTimeNow($do_precision);
	}
	
	public static function searchRegularExpression($sql_field, $sql_expression, $str_flags = false) {
		
		$sql_field = static::sqlFieldLiteral($sql_field);
		$sql_expression = static::sqlFieldLiteral($sql_expression);
		
		$str_flags = parseRegularExpressionFlags($str_flags, [REGEX_NOFLAG.REGEX_CASE_INSENSITIVE => 'c', REGEX_CASE_INSENSITIVE => 'i', REGEX_LINE => 'm', REGEX_NOFLAG.REGEX_DOT_SPECIAL => 'n']);
		
		return 'REGEXP_LIKE('.$sql_field.', '.$sql_expression.', \''.$str_flags.'\')';
	}
	
	public static function searchMatchSensitivity($sql_field, $sql_search, $mode_wildcards = MATCH_ANY, $do_case = false, $do_diacritics = false) {
		
		$sql_field = static::sqlFieldLiteral($sql_field);
		$sql_collate = ' COLLATE '.static::COLLATE_AI_CI;
		
		if ($do_case) { // Also implies accent
			$sql_collate = ' COLLATE '.static::COLLATE_BINARY;
		} else if ($do_diacritics) {
			$sql_collate = ' COLLATE '.static::COLLATE_AS_CI;
		}
		
		$sql = '';
		
		if ($mode_wildcards === false) {
			
			$sql_search = static::sqlFieldLiteral($sql_search);
			$sql = $sql_field.' = '.$sql_search.$sql_collate;
		} else {
			
			$sql_search = static::sqlFieldLiteral($sql_search, true);
			$is_literal_search = ($sql_search['type'] === static::SQL_IS_LITERAL);
			$sql_search = $sql_search['sql'];

			if ($is_literal_search) {
				$sql = $sql_field.' LIKE \''.($mode_wildcards === MATCH_END || $mode_wildcards === MATCH_ANY ? '%' : '').$sql_search.($mode_wildcards === MATCH_START || $mode_wildcards === MATCH_ANY ? '%' : '').'\''.$sql_collate;
			} else {
				$sql = $sql_field.' LIKE CONCAT(\''.($mode_wildcards === MATCH_END || $mode_wildcards === MATCH_ANY ? '%' : '').'\', '.$sql_search.', \''.($mode_wildcards === MATCH_START || $mode_wildcards === MATCH_ANY ? '%' : '').'\')'.$sql_collate;
			}
		}
		
		return $sql;
	}
	
	public static function tableOptions($options) {
		
		$sql = '';
		
		if ($options == static::TABLE_OPTION_MEMORY) {
			$sql .= ' ENGINE=MEMORY';
		}
		
		return $sql;
	}
	
	public static function bulkSelect($q) {
			
		$res = \DB::query($q, MYSQLI_USE_RESULT);
		$res = $res->result; // Get native
		
		while ($arr_row = $res->fetch_row()) {
			
			yield $arr_row;
		}
	}
	
	public static function detectDDL($q) { // Data Definition Language (DDL) statements
		
		static $arr_statements = [
			'ALTER EVENT', 'ALTER FUNCTION', 'ALTER PROCEDURE', 'ALTER SERVER', 'ALTER TABLE', 'ALTER TABLESPACE', 'ALTER VIEW', 'CREATE DATABASE', 'CREATE EVENT', 'CREATE FUNCTION', 'CREATE INDEX',
			'CREATE PROCEDURE', 'CREATE ROLE', 'CREATE SERVER', 'CREATE SPATIAL REFERENCE SYSTEM', 'CREATE TABLE', 'CREATE TABLESPACE', 'CREATE TRIGGER', 'CREATE VIEW', 'DROP DATABASE', 'DROP EVENT', 'DROP FUNCTION', 'DROP INDEX',
			'DROP PROCEDURE', 'DROP ROLE', 'DROP SERVER', 'DROP SPATIAL REFERENCE SYSTEM', 'DROP TABLE', 'DROP TABLESPACE', 'DROP TRIGGER', 'DROP VIEW', 'INSTALL PLUGIN', 'RENAME TABLE', 'TRUNCATE TABLE', 'UNINSTALL PLUGIN'
		];
					
		foreach ($arr_statements as $str_sql) {
			
			if (strpos($q, $str_sql) !== false) {
				return $str_sql;
			}
		}
		
		return false;
	}
}

class DBTrouble extends \DBBase\DBTrouble {
	
	public function getClientMessage() {
		
		$str_message = null;
		
		switch ($this->getCode()) {
			case 1264:
			case 1406:
				$str_message = getLabel('msg_error_database_data_field_limit');
				break;
			case 1062:
				$str_message = getLabel('msg_error_database_duplicate_record');
				break;
			case 1070:
				$str_message = getLabel('msg_error_database_index_limit');
				break;
			case 1317:
			case 1053:
				$str_message = getLabel('msg_error_database_interruption');
				break;
			case 1213:
			case 1205:
				$str_message = getLabel('msg_error_database_deadlock');
				break;
		}
		
		return $str_message;
	}
}

class DBSetup extends \DBBase\DBSetup {}
