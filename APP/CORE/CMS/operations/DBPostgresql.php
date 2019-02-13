<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class DB extends DBBase {
	
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
					if (SiteStartVars::getRequestState() == 'index') {
						error('Too many users. Please press the refresh button in your browser to retry.');
					} else {
						error('The server load is very high at the moment.');
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
		} catch (Exception $e) {

			static::error(new DBTrouble(pg_last_error($connection)));
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
			} catch (Exception $e) {

				static::$last_query = $q;
				static::error(new DBTrouble(pg_last_error(static::$connection_active)));
			}
		} else {
			
			$connection_async = static::newConnectionAsync();
			
			try {
				
				$res = pg_query($connection_async, $q);
			} catch (Exception $e) {

				static::$last_query = $q;
				static::error(new DBTrouble(pg_last_error($connection_async)));
			}
		}
		
		return new DBResult($res);
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
			static::error(new DBTrouble(pg_result_error($res)));
		}
					
		return new DBResult($res);
	}
	
	public static function queryMulti($q) {
		
		pg_send_query(static::$connection_active, $q);
		
		$arr_res = [];

		while ($res = pg_get_result(static::$connection_active)) {
			
			if (pg_result_status($res) == PGSQL_FATAL_ERROR) {
				
				static::$last_query = $q;
				static::error(new DBTrouble(pg_result_error($res)));
			}
								
			$arr_res[] = new DBResult($res);
		}
		
		return $arr_res;
	}
	
	public static function prepare($q) {
		
		try {
			
			$statement = new DBStatement(false);
			
			$identifier = $statement->getIdentifier();
			
			$res = pg_prepare(static::$connection_active, $identifier, $q);
		} catch (Exception $e) {
				
			DBStatement::reset();
			
			static::$last_query = $q;
			static::error(new DBTrouble(pg_last_error(static::$connection_active)));
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
		
		$connection = ($connection !== false ? $connection : static::$connection_active);
							
		if (!$connection || pg_transaction_status($connection) === PGSQL_TRANSACTION_UNKNOWN) {
			return false;
		}
		
		return true;
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

class DBStatement extends DBStatementBase {
	
	protected static $count_statement = 0;
	
	private $arr_parameters = [];
	private $identifier = '';
	
	public function getIdentifier() {
		
		static::$count_statement++;
		
		$this->identifier = '1100CC_'.static::$count_statement;
		
		return $this->identifier;
	}

	public function bindParameters($arr) {

		$this->arr_parameters = [];
		
		foreach ($arr as $variable => $value) {
			
			$position = $this->arr_variables[$variable][0];
			
			$this->arr_parameters[$position] = $value;
		}
	}
	
	public static function assignParameter($variable) {
		
		$position = static::$arr_assign_variables[$variable][0];
	
		return '$'.($position+1);
	}
	
	public function execute() {
		
		$this->statement = pg_execute(DB::$connection_active, $this->identifier, $this->arr_parameters);
		
		return new DBResult($this->statement);
	}
	
	public function getAffectedRowCount() {
	
		return pg_affected_rows($this->statement);
	}
	
	public function close() {
		
		DB::query('DEALLOCATE "'.$this->identifier.'"');
	}
}

class DBResult extends DBResultBase {
	
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
				$int = DBFunctions::TYPE_BOOLEAN;
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

class DBFunctions extends DBFunctionsBase {
	
	const CAST_TYPE_INTEGER = 'INTEGER';
	const CAST_TYPE_STRING = 'VARCHAR';
			
	public static function strEscape($str) {
		
		return pg_escape_string(DB::$connection_active, $str);
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
			case static::TYPE_BINARY:
				$value = ($value ? hex2bin(substr($value, 2)) : ''); // Remove \x;
				break;
		}
		
		return $value;
	}
						
	public static function sqlImplode($expression, $separator = ', ', $clause = false) {
		
		$sql = 'string_agg('.$expression.', \''.$separator.'\' '.$clause.')';
		
		return $sql;
	}
	
	public static function onConflict($key, $arr_values, $sql_other = false) {
		
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
	
	public static function interval($amount, $unit, $field = false) {
		
		$sql = 'INTERVAL \''.(int)$amount.' '.$unit.'\'';
		
		if ($field) {
			
			$sql = '('.$sql.' * '.$field.')';
		}
		
		return $sql;
	}
	
	public static function regexpMatch($sql, $expression, $flags = false) {
		
		return $sql.' ~ \''.$expression.'\'';
	}
	
	public static function sqlTableOptions($engine) {
		
		$sql = '';

		return $sql;
	}
	
	public static function bulkSelect($q) {
		
		$statement = new DBStatement(false);
		
		$identifier = $statement->getIdentifier();
		
		DB::startTransaction('bulk_select');

		DB::query('DECLARE "'.$identifier.'_cursor" NO SCROLL CURSOR FOR ('.$q.')');
		
		$res = pg_prepare(DB::$connection_active, $identifier, 'FETCH NEXT FROM "'.$identifier.'_cursor"');
		$arr_parameters = [];
		
		while ($arr_row = pg_fetch_row(pg_execute(DB::$connection_active, $identifier, $arr_parameters))) {
			
			yield $arr_row;
		}
		
		DB::query('DEALLOCATE "'.$identifier.'"');
		
		DB::commitTransaction('bulk_select');
	}
}
