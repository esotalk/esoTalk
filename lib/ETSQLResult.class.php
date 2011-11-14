<?php

// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * A wrapper around a PDOStatement which provides convenient functions for handling SQL results.
 *
 * @package esoTalk
 */
class ETSQLResult
{

	/**
	 * The PDOStatement object.
	 * @var PDOStatement
	 */
	protected $pdoStatement;

	/**
	 * The query string that was run to get this result.
	 * @var string
	 */
	public $queryString;

	/**
	 * An array of all rows returned by the query.
	 */
	protected $rows = null;

	/**
	 * Class constructor: set up the instance with a PDOStatement object and a query string.
	 *
	 * @param PDOStatement $pdoStatement The PDOStatement object.
	 * @return void
	 */
	public function __construct($pdoStatement)
	{
		$this->pdoStatement = $pdoStatement;
		$this->queryString = $pdoStatement->queryString;
	}

	/**
	 * Fetches all of the rows returned by the query.
	 *
	 * @return array
	 */
	protected function rows()
	{
		if ($this->rows === null)
		{
			$this->rows = $this->pdoStatement->fetchAll(PDO::FETCH_ASSOC);
		}
		return $this->rows;
	}

	/**
	 * Returns the number of rows in the result.
	 *
	 * @return int
	 */
	public function numRows()
	{
		$rows = $this->rows();
		return count($rows);
	}

	/**
	 * Returns a multi-dimensional array containing all rows in the result.
	 * A column to use for the array keys can optionally be specified, otherwise
	 * the array will be indexed.
	 *
	 * @param string $keyColumn The column to use for the array keys.
	 * @return array
	 */
	public function allRows($keyColumn = false)
	{
		$rows = $this->rows();

		if ($keyColumn)
		{
			$newRows = array();
			foreach ($rows as $row)
			{
				$newRows[$row[$keyColumn]] = $row;
			}
			return $newRows;
		}

		return $rows;
	}

	/**
	 * Returns the first row of the result as an array, or false if there are no rows.
	 *
	 * @return array|boolean
	 */
	public function firstRow()
	{
		$rows = $this->rows();
		return isset($rows[0]) ? $rows[0] : false;
	}

	/**
	 * Returns the next row in the result as an associative array, or false if there are no rows.
	 *
	 * @return array|boolean
	 */
	public function nextRow()
	{
		return $this->pdoStatement->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Returns the value of the first column of the first row in the result, or false if there are no rows.
	 *
	 * @return mixed
	 */
	public function result()
	{
		return $this->pdoStatement->fetchColumn();
	}

}