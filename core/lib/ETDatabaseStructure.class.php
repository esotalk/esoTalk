<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The database structure class provides a wrapper for defining table structures so that they can be easily
 * manipluated and upgraded without having to run arbitrary queries in a linear upgrade script.
 *
 * Similar to the SQLQuery class, this implementation tries to be as SQL-neutral as possible, but is
 * ultimately written to work with SQLite. It can be extended to provide structure management for a different
 * database engine.
 *
 * @package esoTalk
 */
class ETDatabaseStructure {


/**
 * The name of the table currently being constructed.
 * @var string
 */
protected $tableName = "";


/**
 * The engine of the table currently being constructed.
 * @var string
 */
protected $engine = "";


/**
 * Whether or not the table currently exists.
 * @var bool
 */
protected $exists = null;


/**
 * An array of existing columns and their information.
 * @var array
 */
protected $existingColumns = null;


/**
 * An array of existing keys and their information.
 * @var array
 */
protected $existingKeys = null;


/**
 * An array of columns in the table currently being constructed.
 * @var array
 */
protected $columns = array();


/**
 * An array of keys in the table currently being constructed.
 * @var array
 */
protected $keys = array();


/**
 * Class constructor: reset all variables in the instance so we have a fresh slate.
 *
 * @return void
 */
public function __construct()
{
	$this->reset();
}


/**
 * Reset the instance so that all structure information is cleared.
 *
 * @return ETDatabaseStructure
 */
public function reset()
{
	$this->tableName = "";
	$this->engine = "InnoDB";
	$this->columns = array();
	$this->keys = array();
	$this->exists = null;
	$this->existingColumns = null;
	$this->existingKeys = null;

	return $this;
}


/**
 * Start the construction of a table.
 *
 * @param string $tableName The name of the table.
 * @param string $engine The table engine that the table should use.
 * @return ETDatabaseStructure
 */
public function table($tableName, $engine = "InnoDB")
{
	$this->reset();
	$this->tableName = $tableName;
	$this->engine = $engine;

	return $this;
}


/**
 * Add a column to the table.
 *
 * @param string $name The name of the column.
 * @param string $type The type signature of the column.
 * @param mixed $default The default value of the column. If this is false, the column will be NOT NULL.
 * @return ETDatabaseStructure
 */
public function column($name, $type, $default = null)
{
	$this->columns[$name] = array("type" => $type, "null" => $default !== false, "default" => $default);

	return $this;
}


/**
 * Add a key to the table.
 *
 * @param mixed $columns The name of the column, or an array of columns, to index.
 * @param string $type The type of key: primary or unique.
 * @return ETDatabaseStructure
 */
public function key($columns, $type)
{
	$columns = (array)$columns;

	// If this is a primary key, and there's only one column, and that column's type is an int, set auto
	// increment on it.
	if ($type == "primary" and count($columns) == 1 and substr($this->columns[$columns[0]]["type"], 0, 3) == "int")
		$this->columns[$columns[0]]["autoIncrement"] = true;

	// If it's not a primary key, we must work out a name for the key.
	if ($type != "primary") $name = $this->tableName."_".implode("_", $columns);
	else {
		$name = "PRIMARY";
		foreach ($columns as $column) $this->columns[$column]["null"] = false;
	}

	// Add the key to the keys array.
	$this->keys[$name] = array("type" => $type, "columns" => $columns);

	return $this;
}


/**
 * Execute the queries necessary to bring the database structure up-to-date with the one that has been defined
 * in the instance.
 *
 * @param bool $drop Whether or not an existing table should be dropped before creating one adhering to the
 * 		structure definition.
 * @return ETDatabaseStructure
 */
public function exec($drop = false)
{
	// Do we need to drop the table before we recreate it?
	if ($drop) {
		ET::SQL("DROP TABLE IF EXISTS `".ET::$database->tablePrefix.$this->tableName."`");
		$this->exists = false;
	}

	// Firstly, work out whether or not the table exists. If it doesn't, we'll have to create the table from
	// scratch.
	if (!$this->exists()) {
		$sql = "CREATE TABLE `".ET::$database->tablePrefix.$this->tableName."` (\n\t";
		$lines = array();

		// Add the columns.
		foreach ($this->columns as $name => $column) {
			$lines[] = "`$name` ".$this->columnDefinition($column);
		}

		// Add the keys.
		foreach ($this->keys as $name => $key) {
			$lines[] = $this->keyDefinition($name, $key);
		}

		// Put it all together.
		$sql .= implode(",\n\t", $lines)."\n)";

		ET::SQL($sql);
	}

	// Otherwise, based on what's already there, we need to modify the table to be up-to-date.
	else {
		$alterPrefix = "ALTER TABLE `".ET::$database->tablePrefix.$this->tableName."`";

		// Go through the columns and add/modify them as necessary.
		$existingColumns = $this->existingColumns();
		foreach ($this->columns as $name => $column) {

			$definition = $this->columnDefinition($column);

			// If the column doesn't exist, we'll need to add it.
			if (!array_key_exists($name, $existingColumns)) {
				ET::SQL($alterPrefix." ADD `$name` ".$definition);
			}

		}
	}

	$this->reset();
}


/**
 * Get the SQL definition string for a column (eg. int(11) unsigned NOT NULL AUTO_INCREMENT).
 *
 * @param array $column The column details.
 * @return string
 */
protected function columnDefinition($column)
{
	$definition = $column["type"];
	if (!$column["null"]) $definition .= " NOT NULL";
	if ($column["default"] !== false) $definition .= " DEFAULT ".ET::$database->escapeValue(is_null($column["default"]) ? null : (string)$column["default"]);
	return $definition;
}


/**
 * Get the SQL definition string for a key (eg. UNIQUE KEY `keyname` (`column1`,`column2`)).
 *
 * @param string $name The name of the the key.
 * @param array $key The key details.
 * @return string
 */
protected function keyDefinition($name, $key)
{
	foreach ($key["columns"] as &$column) $column = "`$column`";
	if ($name == "PRIMARY")
		return "PRIMARY KEY (".implode(",", $key["columns"]).")";
	else
		return "UNIQUE (".implode(",", $key["columns"]).")";
}


/**
 * Returns whether or not the current table exists in the database.
 *
 * @return bool
 */
public function exists()
{
	if ($this->exists === null) {
		$this->exists = (bool)ET::SQL("SELECT name FROM sqlite_master WHERE type = 'table' AND name LIKE '".ET::$database->tablePrefix.$this->tableName."'")->numRows();
	}
	return $this->exists;
}


/**
 * Returns whether or not a column exists in the database.
 *
 * @param string $name The name of the column to check.
 * @return bool
 */
public function columnExists($name)
{
	return array_key_exists($name, $this->existingColumns());
}


/**
 * Returns whether or not an key exists in the database.
 *
 * @param string $name The name of the key, or "PRIMARY" for the primary key.
 * @return bool
 */
public function keyExists($name)
{
	return array_key_exists($name, $this->existingKeys());
}


/**
 * Returns a list of columns and their information in the current table.
 *
 * @return array
 */
public function existingColumns()
{
	if ($this->existingColumns === null) {
		$result = ET::SQL("PRAGMA table_info(".ET::$database->tablePrefix.$this->tableName.")")->allRows();
		$this->existingColumns = array();
		foreach ($result as $column) {
			$this->existingColumns[$column["name"]] = array(
				"type" => $column["type"],
				"null" => $column["notnull"] == "0",
				"default" => ($column["dflt_value"] !== null or $column["notnull"] != "0") ? $column["dflt_value"] : false,
				"autoIncrement" => $column["pk"] == "1"
			);
		}
	}
	return $this->existingColumns;
}


/**
 * Returns a list of keys and their information in the current table.
 *
 * @return array
 */
public function existingKeys()
{
	return $this->existingKeys;
}


/**
 * Drop the current table from the database.
 *
 * @return ETDatabaseStructure
 */
public function drop()
{
	ET::SQL("DROP TABLE IF EXISTS `".ET::$database->tablePrefix.$this->tableName."`");
	return $this;
}


/**
 * Drop the specified column from the current table.
 *
 * @param string $name The name of the column.
 * @return ETDatabaseStructure
 */
public function dropColumn($name)
{
	if ($this->columnExists($name))
		ET::SQL("ALTER TABLE `".ET::$database->tablePrefix.$this->tableName."` DROP COLUMN `$name`");
	return $this;
}


/**
 * Drop the specified key from the current table.
 *
 * @param string $name The name of the key, or "PRIMARY" to drop the primary key.
 * @return ETDatabaseStructure
 */
public function dropKey($name)
{
	return $this;
}


/**
 * Rename the current table.
 *
 * @param string $newName The new name of the table.
 * @return ETDatabaseStructure
 */
public function rename($newName)
{
	if ($this->exists())
		ET::SQL("ALTER TABLE `".ET::$database->tablePrefix.$this->tableName."` RENAME TO `$newName`");
	return $this;
}


/**
 * Rename the specified column on the current table.
 *
 * @param string $name The name of the column.
 * @param string $newName The new name of the column.
 * @return ETDatabaseStructure
 */
public function renameColumn($name, $newName)
{
	return $this;
}

}
