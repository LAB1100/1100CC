<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Throw all MySQL errors
		
class DB extends DBBase {
	
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
			
			$connection = new mysqli($host, $arr_connection_details['user'], $arr_connection_details['password'], $database, $port);
		} catch (Exception $e) {

			switch ($e->getCode()) {
				case 1040:
				case 1203:
				case 2002:
					if (SiteStartVars::getRequestState() == SiteStartVars::REQUEST_INDEX) {
						error('Too many users. Please press the refresh button in your browser to retry.');
					} else {
						error('The server load is very high at the moment.');
					}
				default:
					error('Database trouble.');
			}
		}
		
		$connection->multi_query("
			SET NAMES utf8mb4;
			SET SESSION time_zone = '+00:00';
			
			SET SESSION sql_mode = (SELECT CONCAT(@@sql_mode, ',ANSI_QUOTES'));
			SET SESSION group_concat_max_len = 100000;
			
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
		} catch (Exception $e) {

			return false;
		}
		
		return true;
	}
	
	public static function query($q, $modifier = null) {
		
		$modifier = ($modifier ?? MYSQLI_STORE_RESULT);
		
		if (!static::$connection_active_is_async) {
			
			try {

				$res = static::$connection_active->query($q, $modifier);
			} catch (Exception $e) {

				static::$last_query = $q;
				static::error(new DBTrouble($e->getMessage(), $e->getCode(), $e));
			}
		} else {
			
			$connection_async = static::newConnectionAsync();
			
			try {
				
				$res = $connection_async->query($q, $modifier);
			} catch (Exception $e) {

				static::$last_query = $q;
				static::error(new DBTrouble($e->getMessage(), $e->getCode(), $e));
			}
		}
		
		return new DBResult($res);
	}

	public static function queryAsync($q) {
					
		try {
			
			static::$connection_active->query($q, MYSQLI_ASYNC);
		} catch (Exception $e) {

			static::$last_query = $q;
			static::error(new DBTrouble($e->getMessage(), $e->getCode(), $e));
		}
		
		static::$connection_active_is_async = true;
					
		onUserPoll(function() {
			
			$links = $errors = $reject = [static::$connection_active];
			
			return mysqli_poll($links, $errors, $reject, 0);
		}, function() {
			
			// Create new database connection
			$connection_async = static::newConnectionAsync();
			$connection_async->query('KILL QUERY '.static::$connection_active->thread_id);
			
			// Remove and switch closed database connection with new connection
			static::$arr_database_level_connection[static::$connection_database][static::$connection_level] = $connection_async;
			static::$connection_active_is_async = false;
			static::setDatabase(static::$database_selected);
			
			// Remove new connection from async library
			foreach (static::$arr_database_level_connection_async[static::$connection_database][static::$connection_level] as $key => $cur_connection_async) {
				
				if ($cur_connection_async == $connection_async) {
					unset(static::$arr_database_level_connection_async[static::$connection_database][static::$connection_level][$key]);
					break;
				}					
			}
		});
		
		$res = static::$connection_active->reap_async_query();
		
		static::$connection_active_is_async = false;
		
		if (!$res) { // Something went wrong, but could not be caught since it happened asynchronously

			$e = new DBTrouble(static::$connection_active->error, static::$connection_active->errno);
			
			static::$last_query = $q;
			static::error($e);
		}
		
		return new DBResult($res);
	}
	
	public static function queryMulti($q) {
		
		$arr_res = [];

		try {
				
			if (static::$connection_active->multi_query($q)) {
				
				do {
					$arr_res[] = new DBResult(static::$connection_active->store_result());
				}
				while (static::$connection_active->more_results() && static::$connection_active->next_result());
			}
		} catch (Exception $e) {

			static::$last_query = $q;
			static::error(new DBTrouble($e->getMessage(), $e->getCode(), $e));
		}

		if (static::$connection_active->errno) { // Something went wrong, but could not be caught because only the first query can be caught
			
			$e = new DBTrouble(static::$connection_active->error, static::$connection_active->errno);
			
			static::$last_query = $q;
			static::error($e);
		}
		
		return $arr_res;
	}
	
	public static function prepare($q) {

		try {
			
			$statement = static::$connection_active->prepare($q);
		} catch (Exception $e) {
			
			DBStatement::reset();

			static::$last_query = $q;
			static::error(new DBTrouble($e->getMessage(), $e->getCode(), $e));
		}
		
		return new DBStatement($statement);
	}
	
	public static function isReady($connection = false) {
		
		$connection = ($connection !== false ? $connection : static::$connection_active);
			
		$links = $errors = $reject = [$connection]; 
		
		if (mysqli_poll($links, $errors, $reject, 0) !== false) {				
			return true;
		}
				
		return false;
	}
	
	public static function isActive($connection = false) {
		
		$connection = ($connection !== false ? $connection : static::$connection_active);
		
		if (!$connection || $connection->errno == 2006 || $connection->errno == 2014 || !$connection->stat()) { // 2006: Server has gone away. 2014: Command out of sync. No stat: Server does not respond at all
			return false;
		}
		
		return true;
	}
	
	public static function lastInsertID() {

		return static::$connection_active->insert_id;
	}
	
	public static function getErrorMessage($code) {
		
		$msg = false;
		
		switch ($code) {
			case 1264:
			case 1406:
				$msg = getLabel('msg_error_database_data_field_limit');
				break;
			case 1062:
				$msg = getLabel('msg_error_database_duplicate_record');
				break;
			case 1070:
				$msg = getLabel('msg_error_database_index_limit');
				break;
		}
		
		return $msg;
	}
}

class DBStatement extends DBStatementBase {
	
	public function bindParameters($arr) {

		$arr_collect = [];
		
		$format = '';
		
		$arr_collect[0] =& $format;
		
		foreach ($arr as $variable => $value) {
			
			$position = $this->arr_variables[$variable][0]+1;
			
			$arr_collect[$position] = $value;
			
			$format .= $this->arr_variables[$variable][1];
		}
		
		ksort($arr_collect);
		
		$this->statement->bind_param(...$arr_collect);
	}
	
	public function execute() {
		
		$this->statement->execute();
		
		return new DBResult($this->statement->get_result());
	}
	
	public function getAffectedRowCount() {
	
		return $this->statement->affected_rows;
	}
	
	public function close() {
		
		$this->statement->close();
	}
}

class DBResult extends DBResultBase {
	
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
					$int = DBFunctions::TYPE_BOOLEAN;
				} else {
					$int = DBFunctions::TYPE_INTEGER;
				}
				break;
			case MYSQLI_TYPE_SHORT:
			case MYSQLI_TYPE_LONG:
				$int = DBFunctions::TYPE_INTEGER;
				break;
			case MYSQLI_TYPE_FLOAT:
				$int = DBFunctions::TYPE_FLOAT;
				break;
			default:
				$int = 0;
		}
		
		return $int;
	}
	
	public function getAffectedRowCount() {
		
		return DB::$connection_active->affected_rows;
	}
	
	public function freeResult() {
		
		return $this->result->free_result();
	}
}

class DBFunctions extends DBFunctionsBase {
	
	const CAST_TYPE_INTEGER = 'SIGNED';
	const CAST_TYPE_DECIMAL = 'DECIMAL';
	const CAST_TYPE_STRING = 'CHAR';
	const CAST_TYPE_BOOLEAN = 'SIGNED';
	const CAST_TYPE_BINARY = 'BINARY';
			
	public static function strEscape($str) {
		
		if (!$str) {
			return (string)$str;
		}
		
		return DB::$connection_active->real_escape_string($str);
	}
	
	public static function escapeAs($value, $what) {
		
		switch ($what) {
			
			case static::TYPE_BOOLEAN:
				$value = ($value ? 'TRUE' : 'FALSE');
				break;
			case static::TYPE_BINARY:
				$value = '\''.DB::$connection_active->real_escape_string($value).'\'';
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
	
	public static function sqlImplode($expression, $separator = ', ', $clause = false) {
		
		$sql = 'GROUP_CONCAT('.$expression.' '.$clause.' SEPARATOR \''.$separator.'\')';
		
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
	
	public static function regexpMatch($sql, $expression, $flags = false) {
		
		return $sql.' REGEXP '.$expression;
	}
	
	public static function sqlTableOptions($engine) {
		
		$sql = '';
		
		if ($engine == static::TABLE_OPTION_MEMORY) {
			
			$sql .= ' ENGINE=MEMORY';
		}
		
		return $sql;
	}
	
	public static function bulkSelect($q) {
			
		$res = DB::query($q, MYSQLI_USE_RESULT);
		$res = $res->result; // Get native
		
		while ($arr_row = $res->fetch_row()) {
			
			yield $arr_row;
		}
	}
}
