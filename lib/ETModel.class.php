<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The ETModel class provides basic functions to get, insert, update, and delete data from the database for
 * a given type of record. It can be extended so that functionality can be altered and made more specfic to a
 * type of record.
 *
 * @package esoTalk
 */
class ETModel extends ETPluggable {


/**
 * An array of errors that occurred in the last action performed by the model.
 * @var array
 */
protected $errors = array();


/**
 * The database table which the model is associated with.
 * @var string
 */
protected $table;


/**
 * The name of the column that is the primary key of the table.
 * @var string
 */
protected $primaryKey;


/**
 * Class constructor: sets up the model to be associated with a particular table, and to use a specific column
 * as the primary key when managing data.
 *
 * @param string $table The name of the table to associate the model with.
 * @param string $primaryKey The name of the column that is the primary key of the table. [tablename]Id will
 * 		be used if this is not provided.
 * @return void
 */
public function __construct($table = "", $primaryKey = "")
{
	$this->table = $table;
	$this->primaryKey = $table."Id";
}


/**
 * Create a new record in the model's table.
 *
 * @param array $values An array of data to insert into the table.
 * @return int The new record's ID.
 */
public function create($values)
{
	ET::SQL()->insert($this->table)
		->set($values)
		->exec();

	return ET::$database->lastInsertId();
}


/**
 * Update existing record(s) in the model's table.
 *
 * @param array $values An array of data to update.
 * @param array $wheres An array of where conditions to match.
 * @return ETSQLResult
 */
public function update($values, $wheres = array())
{
	return ET::SQL()->update($this->table)
		->set($values)
		->where($wheres)
		->exec();
}


/**
 * Update an existing record in the model's table with a particular ID.
 *
 * @param mixed $id The ID of the record to update.
 * @param array $values An array of data to update.
 * @return ETSQLResult
 */
public function updateById($id, $values)
{
	return $this->update($values, array($this->primaryKey => $id));
}


/**
 * Delete existing record(s) in the model's table.
 *
 * @param array $wheres An array of where conditions to match.
 * @return ETSQLResult
 */
public function delete($wheres = array())
{
	return ET::SQL()
		->delete()
		->from($this->table)
		->where($wheres)
		->exec();
}


/**
 * Delete an existing record in the model's table with a particular ID.
 *
 * @param mixed $id The ID of the record to delete.
 * @return ETSQLResult
 */
public function deleteById($id)
{
	return $this->delete(array($this->primaryKey => $id));
}


/**
 * Get the number of record(s) in the model's table.
 *
 * @param array $wheres An array of where conditions to match.
 * @return int
 */
public function count($wheres = array())
{
	return ET::SQL()
		->select("COUNT(*)", "count")
		->from($this->table)
		->where($wheres)
		->exec()
		->result();
}


/**
 * Fetch record(s) from the model's table.
 *
 * @param array $wheres An array of where conditions to match.
 * @return array A multi-dimensional array of matching rows.
 */
public function get($wheres = array())
{
	return ET::SQL()
		->select("*")
		->from($this->table)
		->where($wheres)
		->exec()
		->allRows();
}


/**
 * Fetch a record from the model's table with a particular ID.
 *
 * @param mixed $id The ID of the record to fetch.
 * @return array An array containing the row's data.
 */
public function getById($id)
{
	return reset($this->get(array($this->primaryKey => $id)));
}


/**
 * Get all of the errors that occurred in the last action performed by the model, and clear the error storage.
 *
 * @return array An array of errors.
 */
public function errors()
{
	$errors = $this->errors;
	$this->errors = array();
	return $errors;
}


/**
 * Get the number of errors that occurred in the last action.
 *
 * @return int
 */
public function errorCount()
{
	return count($this->errors);
}


/**
 * Set an error on a particular field in the model.
 *
 * @param string $field The name of the field to set the error for.
 * @param string $code The error code.
 * @return void
 */
public function error($field, $code = null)
{
	if ($code !== null) $field = array($field => $code);
	$this->errors = array_merge($this->errors, (array)$field);
}


/**
 * Run a value against a validation callback function, and set an error on a particular field in the model
 * if validation fails.
 *
 * @param string $field The name of the field to set the error on if validation fails.
 * @param mixed $value The value to validate.
 * @param mixed $callback The validation callback function to run.
 * @return void
 */
public function validate($field, $value, $callback)
{
	if ($message = call_user_func($callback, $value))
		$this->error($field, $message);
}

}