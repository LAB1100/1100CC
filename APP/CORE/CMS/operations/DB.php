<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2025 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

namespace DBBase;

abstract class DB {
	
	const ENGINE = 1;
	
	const ENGINE_MYSQL = 1;
	const ENGINE_POSTGRESQL = 2;
	
	const ENGINE_IS_MYSQL = false;
	const ENGINE_IS_MARIADB = false;
	const ENGINE_IS_POSTGRESQL = false;
	
	const CONNECT_HOME = 1;
	const CONNECT_CMS = 2;
	
	public static $engine;

	public static $connection_active = false;
	public static $connection_active_is_async = false;
	public static $connection_database = false;
	public static $database_selected = false;
	
	public static $connection_level_default = false;
	public static $connection_level = false;

	public static $localhost = 'localhost';
	public static $database_core = '';
	public static $database_cms = '';
	public static $database_home = '';
	public static $table_prefix = '';
			
	protected static $arr_database_level_connection_details = [];
	protected static $arr_database_level_connection = [];
	protected static $arr_database_level_connection_async = [];
	protected static $arr_connection_status = [];
			
	protected static $arr_tables = [];
	protected static $arr_override_tables = [];
	
	protected static $last_query = '';
	
	public static function setConnection($level = false, $is_default = false) {

		if ($level) {
			
			static::$connection_level = $level;
			
			if ($is_default) {
				static::$connection_level_default = static::$connection_level;
			}
		} else {
			
			static::$connection_level = (static::$connection_level_default ?: static::CONNECT_HOME);
		}
		
		try {
			
			static::newConnection();
		} catch (\Exception $e) {
			
			static::setDatabase(); // Make sure to set the database implicitly to unavailable
			throw($e);
		}
		
		static::setDatabase();
	}
	
	public static function getTable($identifier) {
	
		$name = (static::$arr_override_tables[$identifier] ?? (static::$arr_tables[$identifier] ?? $identifier));
		
		$name = str_replace('"', '', $name); // Remove a possible previous getTable parsing
		$arr_database_table = explode('.', $name);
		
		if (count($arr_database_table) > 1) {
			
			$name = '"'.$arr_database_table[0].'".'.$arr_database_table[1];
			static::setDatabase($arr_database_table[0]);
		} else {
			
			$name = '"'.$name.'"';
			static::setDatabase(false);
		}
		
		return $name;
	}
	
	public static function getTableTemporary($identifier) {
		
		return static::getTable($identifier);
	}
	
	public static function getTableName($identifier) {
	
		$name = (static::$arr_override_tables[$identifier] ?? (static::$arr_tables[$identifier] ?? $identifier));

		return $name;
	}

	public static function setTable($identifier, $name) {
		
		static::$arr_tables[$identifier] = $name;
	}

	public static function overrideTable($identifier, $name) {
		
		static::$arr_override_tables[$identifier] = $name;
	}
	
	public static function getDatabaseTables() {
		
		$arr_database_tables = [];
		
		foreach (array_merge(static::$arr_tables, static::$arr_override_tables) as $identifier => $name) {
			
			$database_table = explode('.', $name);
			$table = ($database_table[1] ?? $database_table[0]);
			$database = (count($database_table) > 1 ? $database_table[0] : static::$database_home);
			$arr_database_tables[$database][$table] = $table;
		}
		
		return $arr_database_tables;
	}
	
	public static function setConnectionDetails($host, $user, $password, $level = false, $database = false) {
		
		$password = \Settings::getSafeText($password);
		
		$level = ($level ?: static::CONNECT_HOME);
				
		static::$arr_database_level_connection_details[$database][$level] = ['host' => $host, 'user' => $user, 'password' => $password, 'level' => $level, 'database' => $database];
		
		ksort(static::$arr_database_level_connection_details[$database]); // Make sure the levels are sorted
	}
	
	public static function overrideConnectionDetails($host, $user, $password, $level = false, $database = false) {
		
		$level = ($level ?: static::CONNECT_HOME);
	
		unset(static::$arr_database_level_connection[$database]);
		
		static::setConnectionDetails($host, $user, $password, $level, $database);
	}
	
	public static function setConnectionAlias($alias, $database) {
		
		if (!isset(static::$arr_database_level_connection[$database])) {
			static::$arr_database_level_connection[$database] = [];
		}
		
		static::$arr_database_level_connection[$alias] = &static::$arr_database_level_connection[$database];
	}

	protected static function newConnection() {
		
		foreach (static::$arr_database_level_connection_details as $database => $arr_level_connection_details) {

			if (!empty(static::$arr_database_level_connection[$database][static::$connection_level])) {
				continue;
			}
			
			foreach ($arr_level_connection_details as $level => $arr_connection_details) {

				if ($level < static::$connection_level) {
					continue;
				}
				
				$connection = static::createConnection($arr_connection_details);
				
				for ($i = static::$connection_level; $i <= $level; $i++) {
					
					static::$arr_database_level_connection[$database][$i] = $connection;
				}
				
				break;
			}
		}
	}
	
	public static function closeConnection() {
				
		static::doClose();
		
		foreach (static::$arr_database_level_connection[static::$connection_database] as $level => $connection) { // The same connection could be used by multiple levels
			
			if ($connection !== static::$connection_active) {
				continue;
			}
				
			unset(static::$arr_database_level_connection[static::$connection_database][$level]);
		}
	}

	public static function fixConnection() {
		
		static::closeConnection();
		
		static::newConnection();
		
		static::setDatabase(static::$database_selected);
	}
	
	abstract public static function clearConnection();
	
	protected static function newConnectionAsync() {
		
		if (isset(static::$arr_database_level_connection_async[static::$connection_database][static::$connection_level])) {
			
			foreach (static::$arr_database_level_connection_async[static::$connection_database][static::$connection_level] as $connection_async) {
				
				if (static::isReady($connection_async)) {
					return $connection_async;
				}
			}
		}
		
		foreach (static::$arr_database_level_connection_details[static::$connection_database] as $level => $arr_connection_details) {
			
			if ($level < static::$connection_level) {
				continue;
			}
				
			$connection_async = static::createConnection($arr_connection_details);
			
			break;
		}
		
		static::$arr_database_level_connection_async[static::$connection_database][static::$connection_level][] = $connection_async;

		return $connection_async;
	}
	
	abstract protected static function createConnection($arr_connection_details);
	
	public static function setDatabase($database = false) {
		
		static::$database_selected = $database;
		static::$connection_database = (isset(static::$arr_database_level_connection[$database]) ? $database : false);
		
		static::$connection_active = (static::$arr_database_level_connection[static::$connection_database][static::$connection_level] ?? false);
		
		return static::$connection_active;
	}
	
	abstract public static function query($q);
	
	abstract public static function queryAsync($q);
	
	abstract public static function queryMulti($q);
			
	abstract public static function prepare($q);
	
	public static function startTransaction($identifier = 'default', $do_force = false) {
		
		$arr_connection_status =& static::$arr_connection_status[static::$connection_database];
		
		// When requested, force close any existing transaction
		if ($do_force) {
			static::commitTransaction($arr_connection_status['transaction']);
		}
		
		if ($arr_connection_status['transaction']) {
			return false;
		}
		
		// Enter transaction
		static::queryMulti('
			SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ;
			START TRANSACTION;
		');
		
		$arr_connection_status['transaction'] = $identifier;
		
		return true;
	}
	
	public static function commitTransaction($identifier = 'default', $commit = true) {
		
		$arr_connection_status =& static::$arr_connection_status[static::$connection_database];
		
		if (!$arr_connection_status['transaction'] || $arr_connection_status['transaction'] != $identifier) {
			return false;
		}
		
		// Commit or rollback transaction
		static::queryMulti('
			'.($commit ? 'COMMIT' : 'ROLLBACK').';
			SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;
		');
		
		$arr_connection_status['transaction'] = false;
		
		return true;
	}
	
	public static function rollbackTransaction($identifier = 'default') {
		
		if (!$identifier) {
			
			$arr_connection_status = (static::$arr_connection_status[static::$connection_database] ?? null);
			
			if (!$arr_connection_status) {
				return false;
			}
			
			$identifier = $arr_connection_status['transaction'];
			
			if (!$identifier) {
				return false;
			}
		}
		
		return static::commitTransaction($identifier, false);
	}
	
	public static function getTransaction() {
		
		$arr_connection_status = (static::$arr_connection_status[static::$connection_database] ?? null);
		
		if (!$arr_connection_status || !$arr_connection_status['transaction']) {
			return false;
		}
		
		return $arr_connection_status['transaction'];
	}

	abstract public static function lastInsertID();
	
	abstract public static function isReady($connection);
	
	abstract public static function isActive($connection);
	
	abstract protected static function doClose($connection);

	public static function error($e) {
		
		if (!\DB::isActive()) { // Possible DB communication error
			
			try {
				\DB::fixConnection();
			} catch (\Exception $ee) { }
		}
				
		$msg = $e->getMessage();
		$debug = static::$last_query;
		
		$msg_client = static::getErrorMessage($e->getCode());

		if ($msg_client) {
			msg($msg_client, \Trouble::label(TROUBLE_ERROR), LOG_CLIENT, false, \Trouble::type(TROUBLE_NOTICE));
		}

		error($msg, TROUBLE_DATABASE, LOG_BOTH, $debug, $e);
	}
	
	abstract public static function getErrorMessage($code);
	
	public static function noCache() {
		
		static::query("SET SESSION query_cache_type = 0;");
	}
	
	public static function getRealNamespace() {
		
		$class = new \ReflectionClass('DB');
		return $class->getNamespaceName(); // e.g. DBBase\Mysql
	}
}

abstract class DBTrouble extends \Exception {
	
}

abstract class DBStatement {
	
	public $statement;
	public $arr_variables;
	
	protected static $arr_assign_variables = [];
	
	public function __construct($statement) {
		
		$this->statement = $statement;
		$this->arr_variables = static::$arr_assign_variables;
		
		static::reset();
	}
	
	abstract public function bindParameters($arr);
	
	abstract public function execute();
	
	abstract public function getAffectedRowCount();
	
	abstract public function close();

	public static function assign($variable, $type) {
		
		$num_position = count(static::$arr_assign_variables);
		
		static::$arr_assign_variables[$variable] = [$num_position, $type];
		
		return static::assignParameter($variable);
	}
	
	public static function assignParameter($variable) {
		
		//$num_position = static::$arr_assign_variables[$variable][0];
		
		return '?';
	}
	
	public static function reset() {
		
		static::$arr_assign_variables = [];
	}
}

abstract class DBResult {
	
	public $result;
	
	public function __construct($result) {
		
		$this->result = $result;
	}
	
	abstract public function fetchArray();
	
	abstract public function fetchAssoc();
	
	abstract public function fetchRow();
	
	abstract public function seekRow($i);
	
	abstract public function getRowCount();
	
	abstract public function getFieldCount();
	
	abstract public function getFieldMeta($i);
	
	abstract public function getFieldDataType($i);
	
	abstract public function getAffectedRowCount();
	
	abstract public function freeResult();
}

abstract class DBFunctions {
	
	const TABLE_OPTION_MEMORY = 1;
	
	const TYPE_INTEGER = 1;
	const TYPE_STRING = 2;
	const TYPE_BOOLEAN = 3;
	const TYPE_BINARY = 4;
	const TYPE_FLOAT = 5;
	
	const CAST_TYPE_INTEGER = false;
	const CAST_TYPE_DECIMAL = false;
	const CAST_TYPE_STRING = false;
	const CAST_TYPE_BOOLEAN = false;
	const CAST_TYPE_BINARY = false;
	
	const FORMAT_STRING_HEX = 'hex';
	const FORMAT_STRING_BASE64 = 'base64';
	
	const INDEX_HASH = '';
	const INDEX_LTF = ''; // Left-to-right index (tree)
	
	const COLLATE_AI_CI = '';
	const COLLATE_AS_CI = '';
	const COLLATE_BINARY = '';
	
	const SQL_GROUP_SEPERATOR = '$|$'; // Use in queries
	const SQL_VALUE_SEPERATOR = ':|:'; // Use for storage
	const SQL_IS_LITERAL = 'LITERAL:';
	const SQL_IS_FIELD = 'FIELD:';
	
	protected static $count_sql_index = 0;
	
	public static function specific($arr_sql, $sql_default = '') {
		
		$sql_select = $arr_sql[\DB::ENGINE];
		
		if (!$sql_select) {
			
			$sql_select = $sql_default;
		}
		
		return (is_callable($sql_select) ? $sql_select() : $sql_select);
	}
	
	abstract public static function strEscape($str);
	
	public static function arrEscape($arr) {
	
		if (is_array($arr)) {
			
			foreach ($arr as &$v) {
				$v = static::arrEscape($v); //recursive
			}
		} else if (is_string($arr)) {
			
			$arr = static::strEscape($arr);
		}
		
		return $arr;
	}
	
	abstract public static function escapeAs($value, $what);
	
	abstract public static function unescapeAs($value, $what);
			
	abstract public static function castAs($value, $what, $length = false);
	
	public static function convertTo($value, $to, $from, $format = null) {
		
		switch ($to) {
			
			case static::TYPE_BOOLEAN:
				$to = static::CAST_TYPE_BOOLEAN;
				break;
			case static::TYPE_INTEGER:
				$to = static::CAST_TYPE_INTEGER;
				break;
			case static::TYPE_FLOAT:
				$to = static::CAST_TYPE_DECIMAL;
				break;
			case static::TYPE_BINARY:
				$to = static::CAST_TYPE_BINARY;
				break;
			default:
				$to = static::CAST_TYPE_STRING;
				break;
		}
		
		return static::castAs($value, $to);
	}
		
	public static function sqlFieldLiteral($sql, $get_type = false) {
		
		if (strStartsWith($sql, \DBFunctions::SQL_IS_LITERAL)) {
			
			$sql = substr($sql, strlen(\DBFunctions::SQL_IS_LITERAL));
			
			if ($get_type) {
				return ['sql' => $sql, 'type' => \DBFunctions::SQL_IS_LITERAL];
			}
			
			$sql = '\''.$sql.'\'';
			
			return $sql;
		}
		
		if ($get_type) {
			return ['sql' => $sql, 'type' => \DBFunctions::SQL_IS_FIELD];
		}
		
		return $sql;
	}
	
	public static function sqlFieldNotLiteral($sql, $get_type = false) {
		
		if (strStartsWith($sql, \DBFunctions::SQL_IS_FIELD)) {
			
			$sql = substr($sql, strlen(\DBFunctions::SQL_IS_FIELD));
			
			if ($get_type) {
				return ['sql' => $sql, 'type' => \DBFunctions::SQL_IS_FIELD];
			}
						
			return $sql;
		}
				
		if ($get_type) {
			return ['sql' => $sql, 'type' =>  \DBFunctions::SQL_IS_LITERAL];
		}
		
		$sql = '\''.$sql.'\'';
		
		return $sql;
	}
	
	public static function withKeys($table, $alias, $column) {
		
		$arr = ['select' => [], 'where' => []];
		
		if (is_array($column)) {
			
			foreach ($column as $key => $value) {
				$arr['select'][] = $alias.".".$value;
				$arr['where'][] = $table.".".$value." = ".$alias.".".$value;
			}
		} else {
			
			$arr['select'][] = $alias.".".$column;
			$arr['where'][] = $table.".".$column." = ".$alias.".".$column;
		}
		
		return $arr;
	}
	
	public static function updateWith($table, $alias, $column, $with, $arr_set) {
		
		$arr_keys = static::withKeys('table_use', $alias, $column);
		
		if (is_array($with)) {
			$sql_join = $with[0];
			$sql_include = $with[1];
		} else {
			$sql_join = $with;
			$sql_include = '';
		}
		
		foreach ($arr_set as $field => &$value) {
			$value = $field.' = '.$value;
		}
		unset($value);
				
		$sql = "WITH table_use AS (SELECT
				".implode(',', $arr_keys['select']).($sql_include ? ','.$sql_include : '')."
					FROM ".$table." AS ".$alias."
				".$sql_join."
			)
			UPDATE ".$table." AS ".$alias."
			SET ".implode(',', $arr_set)."
				FROM table_use
				WHERE ".implode(' AND ', $arr_keys['where'])."
		";
		
		return $sql;
	}
	
	public static function deleteWith($table, $alias, $column, $with) {
		
		$arr_keys = static::withKeys('table_use', $alias, $column);
		
		if (is_array($with)) {
			$sql_join = $with[0];
			$sql_include = $with[1];
		} else {
			$sql_join = $with;
			$sql_include = '';
		}
		
		$sql = "WITH table_use AS (SELECT
				".implode(',', $arr_keys['select']).($sql_include ? ','.$sql_include : '')."
					FROM ".$table." AS ".$alias."
				".$sql_join."
			)
			DELETE FROM ".$table." AS ".$alias."
				USING table_use
				WHERE ".implode(' AND ', $arr_keys['where'])."
		";
		
		return $sql;
	}
	
	public static function createIndex($table, $column, $identifier = false, $using = self::INDEX_HASH) {

		if (!$identifier) {
			
			static::$count_sql_index++;
			
			$identifier = 'index_'.static::$count_sql_index;
		}
		
		$sql_index = (is_array($column) ? implode(',', $column) : $column);
		$sql_using = ($using ? ' USING '.$using : '');
		
		return "CREATE INDEX ".$identifier." ON ".$table." (".$sql_index.")".$sql_using;
	}
	
	abstract public static function onConflict($key, $arr_values, $sql_other = false);
	
	abstract public static function group2String($sql_expression, $str_separator = ', ', $sql_clause = false);
	
	public static function fields2String($str_separator, ...$sql_fields) {
		
		$str_sql_separator = ',';
		if ($str_separator) {
			$str_sql_separator = static::sqlFieldNotLiteral($str_separator);
			$str_sql_separator = ','.$str_sql_separator.',';
		}
		
		return 'CONCAT('.implode($str_sql_separator, $sql_fields).')';
	}
	
	abstract public static function interval($amount, $unit, $field = false);
	
	abstract public static function timeDifference($unit, $field_start, $field_end);
	
	abstract public static function timeNow($do_transaction = false);
	
	abstract public static function searchRegularExpression($sql_field, $sql_expression, $str_flags = false);
	
	public static function searchMatch($sql_field, $str, $do_dynamic = true, $do_array = false) {
		
		if ($do_dynamic && trim($str) != '') {
			
			$arr_sql = [];
			$num_pos_quote = strpos($str, '"');
			
			if ($num_pos_quote !== false) {
				
				$str = preg_replace_callback('/"((?:[^"]|"")*)"/', function($arr_match) use ($sql_field, &$arr_sql) {

					$str_search = str_replace('""', '"', $arr_match[1]); // Remove escapes
					$sql_search = \DBFunctions::SQL_IS_LITERAL.' '.static::str2Search($str_search).' ';
					
					$sql_field_adapt = 'CONCAT(\' \', '.$sql_field.', \' \')';
			
					$arr_sql[$str_search] = static::searchMatchSensitivity($sql_field_adapt, $sql_search);
					
					return '';
				}, $str);
			}

			$arr_str = explode(' ', $str);
			
			foreach ($arr_str as $str_search) {
				
				$str_search = trim($str_search);
				
				if (!$str_search || isset($arr_sql[$str_search])) {
					continue;
				}
				
				$sql_search = \DBFunctions::SQL_IS_LITERAL.static::str2Search($str_search);
				
				$arr_sql[$str_search] = static::searchMatchSensitivity($sql_field, $sql_search);
			}
			
			if ($do_array) {
				return $arr_sql;
			}
			
			$sql = '('.arr2String($arr_sql, ' AND ').')';
			
			return $sql;
		}
		
		$sql_search = \DBFunctions::SQL_IS_LITERAL.static::str2Search($str);
		
		$sql = static::searchMatchSensitivity($sql_field, $sql_search);
		
		if ($do_array) {
			return [$str => $sql];
		}

		return $sql;
	}
	
	abstract public static function searchMatchSensitivity($sql_field, $sql_search, $mode_wildcards = MATCH_ANY, $do_case = false, $do_diacritics = false);
	
	public static function fieldToPosition($sql_field, $arr_values) {
		
		$sql_order = 'CASE '.$sql_field;
		$count = 1;

		foreach ($arr_values as $value) {
			
			$sql_order .= ' WHEN '.$value.' THEN '.$count;
			$count++;
		}
		
		$sql_order .= ' ELSE '.$count.' END';
		
		return $sql_order;
	}
	
	public static function str2Date($str) {
				
		if (is_numeric($str)) {
			$int = (($str > 0 && strlen((int)$str) == 4) ? strtotime('01-01-'.$str) : $str);
		} else if ($str) {
			$int = strtotime($str);
		}
				
		return ($int ? date('Y-m-d H:i:s', $int) : false);
	}
	
	public static function str2Search($str) {
		
		$str = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $str);
		$str = str_replace(['[*]', '[*1]', '[*2]', '[*3]'], ['%', '_', '__', '___'], $str);
		
		return static::strEscape($str);
	}
	
	public static function str2Name($str) { // Clean table names
		
		$str = str_replace(['-'], ['min'], $str);
		
		return $str;
	}
	
	abstract public static function sqlTableOptions($engine);
	
	abstract public static function bulkSelect($q);
	
	public static function cleanupTables($arr_tables, $nr_limit = 100000) {
				
		$arr_msg = [];
		
		$func_result = function() use (&$arr_msg) {
			
			$count = count($arr_msg);
			$debug = implode(EOL_1100CC, $arr_msg);
		
			msg('Cleaned up '.$count.' tables.', 'DATABASE', LOG_BOTH, $debug);
		};
		
		try {
			
			foreach ($arr_tables as $arr_table) {
				
				\DB::queryMulti("
					DROP ".(\DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE IF EXISTS cleanup_cache;
					
					CREATE TEMPORARY TABLE cleanup_cache (
						status SMALLINT,
						id INT,
						PRIMARY KEY (status, id)
					) ".static::sqlTableOptions(static::TABLE_OPTION_MEMORY).";

					INSERT INTO cleanup_cache
						(SELECT
							0 AS status,
							".$arr_table['delete'][1]." AS id
								FROM ".$arr_table['delete'][0]."
							WHERE TRUE
								".($arr_table['clause_not_empty'] ? "AND (".$arr_table['delete'][1]." IS NOT NULL AND ".$arr_table['delete'][1]." != '')" : "")."
								".($arr_table['clause'] ? "AND (".$arr_table['clause'].")" : "")."
								AND NOT EXISTS (SELECT TRUE
									FROM ".$arr_table['test'][0]." cleanup_test
									WHERE cleanup_test.".$arr_table['test'][1]." = ".$arr_table['delete'][1]."
								)
						)
						".\DBFunctions::onConflict('status, id', ['status'])."
					;
				");
				
				$res = \DB::query("SELECT COUNT(*) FROM cleanup_cache");
				$arr_row = $res->fetchRow();
				$total = $arr_row[0];
				
				if (!$total) {
					continue;
				}
				
				$stmt = \DB::prepare("
					".\DBFunctions::deleteWith(
						$arr_table['delete'][0], 'cleanup_delete', $arr_table['delete'][1],
						"JOIN cleanup_cache ON (
							cleanup_cache.id = cleanup_delete.".$arr_table['delete'][1]." 
							AND cleanup_cache.status = ".($nr_limit ? "1" : "0")."
						)"
					)."
				");				
				
				do {
					
					\DB::startTransaction('cleanup_tables');
					
					if ($nr_limit) {
						
						\DB::query("UPDATE cleanup_cache
							SET status = 1
							WHERE status = 0
							LIMIT ".$nr_limit
						);
					}
					
					$stmt->execute();
					
					$nr_rows_affected = $stmt->getAffectedRowCount();

					if ($nr_limit) {
						
						\DB::query("UPDATE cleanup_cache
							SET status = 2
							WHERE status = 1
						");
					}
					
					\DB::commitTransaction('cleanup_tables');
					
					$go = ($nr_limit && $nr_rows_affected ? true : false);
				} while ($go);
				
				$arr_msg[] = 'Deleted '.num2String($total).' rows using '.$arr_table['delete'][0].'.'.$arr_table['delete'][1].'.';
			
				$stmt->close();
			}
		} catch (\Exception $e) {
				
			$func_result();
				
			throw($e);
		}
		
		$func_result();
	}
}

abstract class DBSetup {
	
	public static function init() {
		
		return true;
	}
}
