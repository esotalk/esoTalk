<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The database class handles the database connection and the running of queries against the database.
 *
 * @package esoTalk
 */
class ETDatabase extends ETPluggable {


/**
 * An instance of the PDO connection object.
 * @var PDO
 */
protected $pdoConnection;


/**
 * An instance of the database structure class.
 * @var ETDatabaseStructure
 */
protected $structure;


/**
 * The database host.
 * @var string
 */
protected $host;


/**
 * The database user.
 * @var string
 */
protected $user;


/**
 * The database password.
 * @var string
 */
protected $password;


/**
 * The database name.
 * @var string
 */
protected $dbName;


/**
 * An array of connection options to use when creating a PDO connection.
 * @var array
 */
protected $connectionOptions = array();


/**
 * The database table prefix.
 * @var string
 */
public $tablePrefix;


/**
 * Whether or not we are currently in the middle of a database transaction.
 * @var bool
 */
protected $inTransaction = false;


/**
 * An array of queries that have been run.
 * @var array
 */
public $queries = array();


/**
 * Get an instance of the database structure class.
 *
 * @return ETDatabaseStructure An instance of the database structure class.
 */
public function structure()
{
	if (!$this->structure) $this->structure = ETFactory::make("databaseStructure");
	return $this->structure;
}


/**
 * Create a new instance of the SQL query class.
 *
 * @return ETSQLQuery A new instance of the SQL query class.
 */
public function SQL()
{
	return ETFactory::make("sqlQuery");
}


/**
 * Get the PDO connection to the database, creating a new one if it does not already exist.
 *
 * @return PDO The PDO connection.
 */
public function connection()
{
	if (!$this->pdoConnection) {
		$dsn = "mysql:host=".$this->host.";dbname=".$this->dbName;
		$this->pdoConnection = @new PDO($dsn, $this->user, $this->password, $this->connectionOptions);
	}
	return $this->pdoConnection;
}


/**
 * Fetches the version of the database engine in use.
 *
 * @return string The database engine version.
 */
public function getVersion()
{
	return $this->query("SELECT VERSION()")->result();
}


/**
 * Initialize the database class with database details. These details will be used when a PDO connection
 * is made.
 *
 * @param string $host The database host.
 * @param string $user The database user.
 * @param string $password The database password.
 * @param string $dbName The database name.
 * @param string $tablePrefix The database table prefix.
 * @param array $connectionOptions An array of connection options to use when making the PDO connection.
 * @return void
 */
public function init($host, $user, $password, $dbName, $tablePrefix = "", $connectionOptions = array())
{
	$this->pdoConnection = null;

	$this->host = $host;
	$this->user = $user;
	$this->password = $password;
	$this->dbName = $dbName;
	$this->tablePrefix = $tablePrefix;
	$this->connectionOptions = $connectionOptions;

	// We don't use PDO's prepared statements as they have some flaws. We emulate parameter binding behaviour
	// (see ETSQLQuery and escapeValue()), and then send direct queries.
	$this->connectionOptions[PDO::MYSQL_ATTR_DIRECT_QUERY] = true;
}


/**
 * Close the connection to the database.
 *
 * @return void
 */
public function close()
{
	$this->commitTransaction();
	unset($this->pdoConnection);
}


/**
 * Begin a database transaction.
 *
 * @return void
 */
public function beginTransaction()
{
	$this->connection()->beginTransaction();
	$this->inTransaction = true;
}


/**
 * Roll back a database transaction.
 *
 * @return void
 */
public function rollbackTransaction()
{
	if (!$this->inTransaction) return;
	$this->connection()->rollback();
	$this->inTransaction = false;
}


/**
 * Commit a database transaction.
 *
 * @return void
 */
public function commitTransaction()
{
	if (!$this->inTransaction) return;
	$this->connection()->commit();
	$this->inTransaction = false;
}


/**
 * Get the ID of the last record inserted into the database.
 *
 * @return string The last insert ID.
 */
public function lastInsertId()
{
	return $this->query("SELECT LAST_INSERT_ID()")->result();
}


/**
 * Escape a value so that it is safe to use in an SQL query.
 *
 * @param mixed $value The value to escape.
 * @param int $dataType Explicit data type for the value using PDO::PARAM_* constants. If null,
 * 		the type of $value will be used.
 * @return mixed The escaped value.
 */
public function escapeValue($value, $dataType = null)
{
	// If the value is an array, escape each element individually and return a comma-separated string.
	if (is_array($value)) {
		foreach ($value as &$v) $v = $this->escapeValue($v, $dataType);
    	return implode(",", $value);
	}

	// If no data type was specified, work it out based on the variable content.
	if ($dataType === null) {
		if ($value === true or $value === false) $dataType = PDO::PARAM_BOOL;
		elseif ($value === null) $dataType = PDO::PARAM_NULL;
		elseif (is_int($value)) $dataType = PDO::PARAM_INT;
		else $dataType = PDO::PARAM_STR;
	}

	// Now escape the value according to the data type.
	switch ($dataType) {
		case PDO::PARAM_BOOL:
			return $value ? "1" : "0";

		case PDO::PARAM_NULL:
			return "NULL";

		case PDO::PARAM_INT:
			$value = (int)$value;
			return $value ? (string)$value : "0";

		default:
			$value = str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $value);
			return "'".$value."'";
	}
}


/**
 * Run a query against the database.
 *
 * @param string $query The query to run.
 * @return ETSQLResult|bool An SQL result, or false if the query was unsuccessful.
 */
public function query($query)
{
	// If the query is empty, don't bother proceeding.
	if (!$query) return false;

	// Get the database connection.
	$connection = $this->connection();

	$this->trigger("beforeQuery", array(&$query));
	$this->queries[] = $query;

	// Execute the query.
	$statement = $connection->query($query);

	// Was there an error?
	if (!$statement) {
		$error = $connection->errorInfo();
		throw new Exception("SQL Error (".$error[0].", ".$error[1]."): ".$error[2]." \n<pre>".$this->highlightQueryErrors($query, $error[2])."</pre>");
	}

	// Set up a new ETSQLResult object with the result statement.
	$result = ETFactory::make("sqlResult", $statement);

	$this->trigger("afterQuery", array($result));

	return $result;
}


/**
 * Find anything in single quotes in the error and make it red in the query (just to make debugging a bit
 * easier!)
 *
 * @param string $query The SQL query that failed.
 * @param string $error The error string that was returned by the connection.
 * @return string The SQL query with the guessed "errorous" parts highlighted.
 */
protected function highlightQueryErrors($query, $error)
{
	$query = sanitizeHTML($query);
	preg_match("/'(.+?)'/", $error, $matches);
	if (!empty($matches[1])) $query = str_replace($matches[1], "<span class='highlight'>{$matches[1]}</span>", $query);
	return $query;
}

}
