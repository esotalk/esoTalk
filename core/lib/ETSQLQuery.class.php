<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Enables dynamic construction of SQL queries.
 *
 * The purpose of this class is not so much to be a database abstraction layer and prevent
 * the need to write straight SQL. It is more to allow query components be added/changed dynamically
 * (by plugins, for example), and to aid in writing safe queries.
 *
 * This implementation tries to be as SQL-neutral as possible, but is ultimately written to work
 * with SQLite. It can be extended to provide a query constructor for a different database engine.
 *
 * @package esoTalk
 */
class ETSQLQuery {


/**
 * The type of query that is being constructed (select, update, insert, replace, delete, or union).
 * @var string
 */
protected $mode = "select";


/**
 * An array of expressions to SELECT.
 * @var array
 */
public $select = array();


/**
 * An array of tables to select FROM, including JOIN clauses, or to INSERT into, UPDATE, or DELETE from.
 * @var array
 */
public $tables = array();


/**
 * An array of WHERE conditions.
 * @var array
 */
public $where = array();


/**
 * An array of GROUP BY expressions.
 * @var array
 */
public $groupBy = array();


/**
 * The number of results to limit the query to.
 * @var int
 */
public $limit = null;


/**
 * The result number to start from.
 * @var int
 */
public $offset = null;


/**
 * An array of ORDER BY expressions.
 * @var array
 */
public $orderBy = array();


/**
 * An array of HAVING expressions.
 * @var array
 */
public $having = array();


/**
 * The name of an index to force use of.
 * @var string
 */
public $index = null;


/**
 * An array of fields to set in an INSERT query.
 * @var array
 */
public $insertFields = array();


/**
 * An array of fields => values to set for an UPDATE query, or an array of arrays of values to INSERT.
 * @var array
 */
public $set = array();


/**
 * An array of fields => values to set ON DUPLICATE KEY.
 * @var array
 */
public $setDuplicateKey = array();


/**
 * An array of SQL queries to UNION.
 * @var array
 */
public $union = array();


/**
 * An array of bound parameters to replace when the query is constructed.
 * @var array
 */
public $parameters = array();


/**
 * Bind a value to a parameter that will be substituted safely when the query is constructed.
 *
 * @param string $parameter The name of the parameter. This must begin with a colon (:).
 * @param mixed $value The value to substitute.
 * @param int $dataType Explicit data type for the parameter using PDO::PARAM_* constants. If null,
 * 		the type of $value will be used.
 * @return ETSQLQuery
 */
public function bind($parameter, $value, $dataType = null)
{
	$this->parameters[$parameter] = array($value, $dataType);
	return $this;
}


/**
 * Add an expression to the SELECT clause.
 *
 * @param string|array $expression The expression to select. If an array is passed, all values will be added.
 * @param string $as An optional identifier to select the expression AS.
 * @return ETSQLQuery
 */
public function select($expression, $as = false)
{
	$this->mode = "select";

	// If an AS name was specified, set a keyed value in the array.
	if ($as !== false) $this->select[$as] = $expression;

	// Otherwise, cast the expression to an array and add all its values to the SELECTs array.
	else {
		$expressions = (array)$expression;
		foreach ($expressions as $expression) {
			if (!empty($expression)) $this->select[] = $expression;
		}
	}

	return $this;
}


/**
 * Add a table to the FROM clause, optionally as a JOIN.
 *
 * @param string $table The name of the table. This can include an alias at the end. The table prefix will
 * 		automatically be added.
 * @param string $on An optional condition to JOIN the table ON.
 * @param string $type The type of JOIN (eg. left, inner, etc.)
 * @return ETSQLQuery
 */
public function from($table, $on = false, $type = false)
{
	// If the first character is an opening bracket, then assume the table is a SELECT query. Otherwise,
	// add the table prefix.
	if ($table[0] != "(") {
		$parts = explode(" ", ET::$database->tablePrefix.$table);
		$parts[0] = "`".$parts[0]."`";
		$table = implode(" ", $parts);
	}

	// If a JOIN type or condition was specified, add the table with JOIN syntax.
	if (!empty($type) or !empty($on))
		$this->tables[] = strtoupper($type ? $type : "inner")." JOIN $table".(!empty($on) ? " ON ($on)" : "");

	// Otherwise, just add the table name normally.
	else array_unshift($this->tables, $table);

	return $this;
}


/**
 * Add a WHERE predicate to the query.
 *
 * @param string $predicate The predicate to add. This can be either:
 * 		1. A string and the only argument, and it is added as is.
 * 		2. A string with the $value argument specified, and it is used as the field name to be tested for equality.
 * 		3. An array of predicates. Non-numeric keys will be used as field names to be tested for equality with
 * 		   their values, while numeric keys will be added as is.
 * @param mixed $value The value to test for equality with in case 2 above.
 * @return ETSQLQuery
 */
public function where($predicate, $value = false)
{
	if (empty($predicate)) return $this;

	// If a value was specified, use the predicate as the field name.
	if ($value !== false) $predicate = array($predicate => $value);

	// Go through the predicates and add them to the query one by one.
	$predicates = (array)$predicate;
	foreach ($predicates as $field => $predicate) {

		// If the key is non-numeric, use it as the field name add an equality predicate.
		// Bind the value with a parameter called :where#.
		if (!is_numeric($field)) {
			$i = count($this->where);
			$this->where[] = "$field=:where$i";
			$this->bind(":where$i", $predicate);
		}

		// If the key is numeric, add the predicate as is.
		else $this->where[] = $predicate;
	}

	return $this;
}


/**
 * Add an expression to the GROUP BY clause.
 *
 * @param string|array $expression The expression, or an array of expressions, to add.
 * @return ETSQLQuery
 */
public function groupBy($expression)
{
	$expressions = (array)$expression;
	foreach ($expressions as $expression) {
		if (!empty($expression)) $this->groupBy[] = $expression;
	}
	return $this;
}


/**
 * Add an expression to the ORDER BY clause.
 *
 * @param string|array $expression The expression, or an array of expressions, to add.
 * @return ETSQLQuery
 */
public function orderBy($expression)
{
	$expressions = (array)$expression;
	foreach ($expressions as $expression) {
		if (!empty($expression)) $this->orderBy[] = $expression;
	}
	return $this;
}


/**
 * Add an expression to the HAVING clause.
 *
 * @param string|array $expression The expression, or an array of expressions, to add.
 * @return ETSQLQuery
 */
public function having($expression)
{
	$expressions = (array)$expression;
	foreach ($expressions as $expression) {
		if (!empty($expression)) $this->having[] = $expression;
	}
	return $this;
}


/**
 * Force the use of an index in the query.
 *
 * @param string $index The name of the index to force use of.
 * @return ETSQLQuery
 */
public function useIndex($index)
{
	$this->index = $index;
	return $this;
}


/**
 * Set the maximum number of results for the query to return.
 *
 * @param string $limit The maximum number of results.
 * @return ETSQLQuery
 */
public function limit($limit)
{
	$this->limit = $limit;
	return $this;
}


/**
 * Set the row number to start getting results from.
 *
 * @param string $offset The row number to start from.
 * @return ETSQLQuery
 */
public function offset($offset)
{
	$this->offset = $offset;
	return $this;
}


/**
 * Begin an UPDATE query and add a table to update.
 *
 * @param string $table The name of the table to update.
 * @return ETSQLQuery
 */
public function update($table)
{
	$this->mode = "update";
	$this->tables[] = ET::$database->tablePrefix.$table;
	return $this;
}


/**
 * Set a field to a value in an UPDATE or INSERT query.
 *
 * @param string|array $field The name of the field to set, or an array of fields => values to set.
 * @param mixed $value The value to set the field to.
 * @param bool $sanitize Whether or not to escape and quote the value.
 * @return ETSQLQuery
 */
public function set($field, $value = false, $sanitize = true)
{
	if (!is_array($field)) $field = array($field => $value);
	foreach ($field as $field => $value) {

		$value = $sanitize ? ET::$database->escapeValue($value) : $value;

		// For an UPDATE query, simply add the field and value to the SET array.
		if ($this->mode == "update")
			$this->set[$field] = $value;

		// But for an INSERT query, we need to add the field to $this->insertFields and the value to the
		// first row in the SET array.
		else {
			$this->insertFields[] = $field;
			$this->set[0][] = $value;
		}

	}
	return $this;
}


/**
 * Begin an INSERT query and add a table to insert into.
 *
 * @param string $table The name of the table to insert into.
 * @return ETSQLQuery
 */
public function insert($table)
{
	$this->mode = "insert";
	$this->tables[] = ET::$database->tablePrefix.$table;
	return $this;
}


/**
 * Set multiple rows of data in an INSERT query.
 *
 * @param array $fields An array of field names to set.
 * @param array $valueSets An array of arrays of values to insert.
 * @return ETSQLQuery
 */
public function setMultiple($fields, $valueSets)
{
	$this->insertFields = $fields;
	foreach ($valueSets as &$row) {
		foreach ($row as &$value) {
			$value = ET::$database->escapeValue($value);
		}
	}
	$this->set = $valueSets;
	return $this;
}


/**
 * Set a field to a value when there is a duplicate key in an INSERT query.
 *
 * @param string|array $field The name of the field to set, or an array of fields => values to set.
 * @param mixed $value The value to set the field to.
 * @param bool $sanitize Whether or not to escape and quote the value.
 * @return ETSQLQuery
 */
public function setOnDuplicateKey($field, $value = false, $sanitize = true)
{
	if (!is_array($field)) $field = array($field => $value);
	foreach ($field as $field => $value) {
		$this->setDuplicateKey[$field] = $sanitize ? ET::$database->escapeValue($value) : $value;
	}
	return $this;
}


/**
 * Begin a REPLACE query and add a table to replace into.
 *
 * @param string $table The name of the table to replace into.
 * @return ETSQLQuery
 */
public function replace($table)
{
	$this->mode = "replace";
	$this->tables[] = ET::$database->tablePrefix.$table;
	return $this;
}


/**
 * Begin a DELETE query and add a table to delete.
 *
 * @param string $table The name of the table to delete.
 * @return ETSQLQuery
 */
public function delete($table = null)
{
	$this->mode = "delete";
	if ($table) $this->select[] = $table;
	return $this;
}


/**
 * Add a SELECT query to be UNIONed.
 *
 * @param ETSQLQuery $query The ETSQLQuery object to UNION.
 * @return ETSQLQuery
 */
public function union($query)
{
	$this->mode = "union";
	$this->union[] = $query;
	return $this;
}


/**
 * Indent each line in each value of an array. This is used on each item in the SELECT, WHERE, and FROM clause
 * so that sub-SELECTs appear indented.
 *
 * @param mixed $value The value to apply indentation to.
 * @return mixed The value with indentation applied.
 */
protected function indent($value)
{
	if (is_array($value)) return array_map(array($this, "indent"), $value);
	else return str_replace("\n", "\n\t\t", $value);
}


/**
 * Construct a WHERE clause from the query structure information gathered in this class.
 *
 * @return string
 */
protected function getWhere()
{
	return count($this->where) ? "\nWHERE (".implode(")\n\tAND (", $this->indent($this->where)).")" : "";
}


/**
 * Construct a FROM clause from the query structure information gathered in this class.
 *
 * @return string
 */
protected function getOrderBy()
{
	return count($this->orderBy) ? "\nORDER BY ".implode(", ", $this->orderBy) : "";
}


/**
 * Construct a SELECT SQL query from the query structure information gathered in this class.
 *
 * @return string
 */
protected function getSelect()
{
	// Construct the SELECT clause.
	$select = array();
	foreach ($this->select as $k => $v) {
		if (!is_numeric($k)) $select[] = "$v AS $k";
		else $select[] = $v;
	}
	$select = "SELECT ".implode(", \n\t", $this->indent($select));

	// Construct some other clauses.
	$from = count($this->tables) ? "\nFROM ".implode("\n\t", $this->indent($this->tables)) : "";
	$having = count($this->having) ? "\nHAVING (".implode(") AND (", $this->indent($this->having)).")" : "";
	$groupBy = count($this->groupBy) ? "\nGROUP BY ".implode(", ", $this->groupBy) : "";
	$limit = $this->limit ? "\nLIMIT $this->limit" : "";
	$offset = $this->offset ? "\nOFFSET $this->offset" : "";

	// Put the whole query together and return it.
	return $select.$from.$this->getWhere().$groupBy.$this->getOrderBy().$limit.$offset;
}


/**
 * Construct an UPDATE SQL query from the query structure information gathered in this class.
 *
 * @return string
 */
protected function getUpdate()
{
	// Put together the tables to update.
	$tables = implode(", ", $this->tables);

	// Construct the SET clause.
	$set = array();
	foreach ($this->set as $k => $v) $set[] = "$k=$v";
	$set = implode(", ", $set);

	return "UPDATE $tables SET $set ".$this->getWhere();
}


/**
 * Construct an INSERT SQL query from the query structure information gathered in this class.
 *
 * @return string
 */
protected function getInsert()
{
	// Put together the tables to insert into.
	$tables = implode(", ", $this->tables);

	// Make a list of fields to insert data into.
	$fields = implode(", ", $this->insertFields);

	// Make a list of rows and their values to insert.
	$rows = array();
	foreach ($this->set as $row) $rows[] = "(".implode(", ", $row).")";
	$values = implode(", ", $rows);

	// Construct the ON DUPLICATE KEY UPDATE clause.
	$onDuplicateKey = array();
	foreach ($this->setDuplicateKey as $k => $v) $onDuplicateKey[] = "$k=$v";
	$onDuplicateKey = implode(", ", $onDuplicateKey);

	return "INSERT ".($onDuplicateKey ? " OR REPLACE" : "")." INTO $tables ($fields) VALUES $values";
}


/**
 * Construct a REPLACE SQL query.
 *
 * @return string
 */
protected function getReplace()
{
	// Simply construct an INSERT query, replacing the word INSERT with REPLACE.
	$query = $this->getInsert();
	$query = "REPLACE".substr($query, 6);
	return $query;
}


/**
 * Construct a DELETE SQL query from the query structure information gathered in this class.
 *
 * @return string
 */
protected function getDelete()
{
	$from = implode("\n\t", $this->indent($this->tables));

	return "DELETE FROM $from ".$this->getWhere();
}


/**
 * Construct a UNION SQL query from the query structure information gathered in this class.
 *
 * @return string
 */
protected function getUnion()
{
	// Convert the queries that we want to UNION to strings.
	$selects = $this->union;
	foreach ($selects as &$sql) $sql = $sql->get();

	// Implode them with the UNION keyword.
	$selects = implode("\nUNION\n", $this->indent($selects));

	// Add order by, limit, and offset clauses.
	$limit = $this->limit ? "\nLIMIT $this->limit" : "";
	$offset = $this->offset ? "\nOFFSET $this->offset" : "";

	// Put the query together.
	return $selects.$this->getOrderBy().$limit.$offset;
}


/**
 * Construct the SQL from the query structure information we've gathered in this class, substitute in parameter
 * values, and return the final product it as a string.
 *
 * @return string
 */
public function get()
{
	// Run the appropriate get function depending on this query's mode.
	switch ($this->mode) {

		case "select":
			$query = $this->getSelect();
			break;

		case "update":
			$query = $this->getUpdate();
			break;

		case "insert":
			$query = $this->getInsert();
			break;

		case "replace":
			$query = $this->getReplace();
			break;

		case "delete":
			$query = $this->getDelete();
			break;

		case "union":
			$query = $this->getUnion();
			break;

		default:
			$query = "";
	}

	// Substitute in bound parameter values.
	$query = preg_replace('/(:[A-Za-z0-9_]+)/e', 'array_key_exists("$1", $this->parameters)
		? ET::$database->escapeValue($this->parameters["$1"][0], $this->parameters["$1"][1])
		: "$1"', $query);

	return $query;
}


/**
 * Construct the SQL query and execute it, returning the result.
 *
 * @return ETSQLResult
 */
public function exec()
{
	$query = $this->get();
	return ET::$database->query($query);
}

public function __toString()
{
	return $this->get();
}

}
