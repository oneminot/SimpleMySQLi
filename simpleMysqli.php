<?php

/*
 * simpleMysqli
 * 
 * @usage This class is meant to be extended, with
 * the resulting class setting the $table parameter
 * in it's __construct().
 * 
 */

class simpleMysqli extends mysqli
{
	private $database = array(
		'host' => '',
		'username' => '',
		'password' => '',
		'database' => ''
	);
	public $table = '';
	
	/*
	 * __construct($databaseConfig = array())
	 * 
	 * Instantiate simpleMysqli class with optional database config.
	 * 
	 * @param (databaseConfig) An optional database configuration
	 * containing host, username, password, database.
	 * 
	 */	
	public function __construct($databaseConfig = array())
	{
		$this->database = ($databaseConfig) ? $databaseConfig : $this->database;
		
		parent::__construct();
		$this->real_connect(
			$this->database['host'], 
			$this->database['username'],
			$this->database['password'],
			$this->database['database']
		);
	}
	
	/*
	 * preparedQuery
	 * 
	 * Structures, maps and executes a prepared MySQLi query.
	 * 
	 * @param (mappedQuery) A properly formatted SimpleMYSQLi query.
	 * @param (mappedParameters) An associative array of parameters
	 * to map into the mappedQuery.
	 * @param (returnInsertId) Returns the insert id for insert queries.
	 * @param (returnAffectedRows) Returns the number of rows affected by
	 * the executed query.
	 * 
	 * @return A MySQLi result, or insert id, or affected rows depending
	 * on what parameter values were passed. 
	 */
	public function preparedQuery($mappedQuery, $mappedParameters = array(), $returnInsertId = 0, $returnAffectedRows = 0)
	{
		$dynamicBindingParameters = array();
		
		$types = '';
		foreach ($mappedParameters as $mapping => $value) {
			// replace first occurence only
			$position = strpos($mappedQuery, $mapping);
			if ($position !== false) {
				$mappedQuery = substr_replace($mappedQuery, '?', $position, strlen($mapping));
			}

			$dynamicBindingParameters[] = &$mappedParameters[$mapping];
			$types .= (is_int($value)) ? 'i' : 's';
			
			$mappedParameters[$mapping] = (is_null($value)) ? '' : $value;
		}
		$dynamicBindingParameters = array_merge(array(&$types), $dynamicBindingParameters);
		
		$statement = $this->prepare($mappedQuery);
		if ($statement == false) {
			trigger_error('Bad SQL: ' . $mappedQuery . ' Error: ' . $this->errno . ' ' . $this->error);
		}
		
		// dynamically bind parameters.
		call_user_func_array(array($statement, 'bind_param'), $dynamicBindingParameters);
		
		$statement->execute();
		
		$return = $statement->get_result();
		$return = ($returnInsertId) ? (string)$this->insert_id : $return;
		$return = ($returnAffectedRows) ? $statement->affected_rows : $return;
		
		return $return;
	}
	
	/*
	 * genericQuery
	 * 
	 * Executes a non-prepared statement query against
	 * the specified table. NOTE: Do not trust passing user
	 * submitted data into the query parameter as 
	 * MySQL injection will then be a possibility.
	 * 
	 * @param (query) A non-prepared statement query.
	 * @param (returnInsertId) Returns 
	 * 
	 * @return A query result or insert id depending
	 * on what parameter values were passed.
	 * 
	 */
	public function genericQuery($query, $returnInsertId = 0)
	{
		$result = $this->query($query);

		return ($returnInsertId) ? (string)$this->insert_id : $result;
	}
	
	
	/*
	 * insert
	 * 
	 * Executes an insert query using an associative
	 * array of data.
	 * 
	 * @param (data) An associative array containing
	 * the data to be inserted. Array keys represent
	 * the columns to insert into, the corresponding
	 * values of those keys are the values that will
	 * be inserted into the table.
	 * 
	 * @return Returns an insert id.
	 * 
	 */
	public function insert($data = array())
	{
		$mappedQuery = "INSERT INTO $this->table";
		$mappedQueryColumnString = '(';
		$mappedQueryValueString = 'VALUES(';
		
		$mappedParameters = array();
		
		$i = 0;
		foreach ($data as $column => $value) {
			$mappedQueryColumnString .= ($i > 0) ? ", $column" : $column; 
			$mappedQueryValueString .= ($i > 0) ? ", :$column" : ":$column";
			$mappedParameters[":$column"] = ($value) ? $value : '';
			
			$i++;
		}
		$mappedQueryColumnString .= ')';
		$mappedQueryValueString .= ')';
	
		$mappedQuery .= ' ' . $mappedQueryColumnString . ' ' . $mappedQueryValueString;
		
		return $this->preparedQuery($mappedQuery, $mappedParameters, 1);
	}
	
	/*
	 * update
	 * 
	 * Executes an update query using an associative
	 * array of data.
	 * 
	 * @param (data) An associative array containing
	 * the data to be inserted. Array keys represent
	 * the columns to insert into, the corresponding
	 * values of those keys are the values that will
	 * be inserted into the table.
	 * 
	 * @return Returns non 0 value on success.
	 * 
	 */	
	public function update($checkColumn, $checkValue, $data = array())
	{
		$mappedQuery = "UPDATE $this->table SET";
		$mappedQueryConditionString = "WHERE $checkColumn = :$checkColumn";
		$mappedQueryUpdateString = '';
		
		$mappedParameters = array();
		
		$i = 0;
		foreach ($data as $column => $value) {
			$mappedQueryUpdateString .= ($i > 0) ? ", $column = :$column" : "$column = :$column";
			$mappedParameters[":$column"] = ($value) ? $value : '';
			
			$i++;
		}
		$mappedParameters[":$checkColumn"] = $checkValue;
		
		$mappedQuery .= ' ' . $mappedQueryUpdateString . ' ' . $mappedQueryConditionString;

		return $this->preparedQuery($mappedQuery, $mappedParameters, 1);
	}
	
	/*
	 * get
	 * 
	 * @param (checkColumn) The column of the table to
	 * match the checkValue against.
	 * @param (checkValue) The value to match.
	 * 
	 * @return Returns a row from the table that matches
	 * a specified checkValue for a specified checkColumn.
	 * 
	 */
	public function get($checkColumn, $checkValue)
	{
		$mappedQuery = "SELECT * FROM $this->table WHERE $checkColumn = :$checkColumn LIMIT 1";
		$mappedParameters = array(
			":$checkColumn" => $checkValue
		);
		
		return $this->getSingle($mappedQuery, $mappedParameters);
	}
	
	/*
	 * getSingle
	 * 
	 * @param (query) A mappable select query.
	 * @param (mappedParameters) The parameters to
	 * map into the query.
	 * 
	 * @return Returns a single row from the table matching
	 * the mapped select query.
	 */	
	public function getSingle($query, $mappedParameters = array())
	{
		$queryResult = ($mappedParameters) ? $this->preparedQuery($query, $mappedParameters) : 
											 $this->genericQuery($query);

		return $queryResult->fetch_assoc();
	}
	
	/*
	 * getMulti
	 * 
	 * @param (query) A mappable select query.
	 * @param (mappedParameters) The parameters to
	 * map into the query.
	 * 
	 * @return Returns multiple rows from the table matching
	 * the mapped select query.
	 * 
	 */	
	public function getMulti($query, $mappedParameters = array())
	{
		$queryResult = ($mappedParameters) ? $this->preparedQuery($query, $mappedParameters) :
											 $this->genericQuery($query);
		$queryResult->data_seek(0);
		
		$results = array();
		while($row = $queryResult->fetch_assoc()) {
			$results[] = $row;
		}
		
		return $results;
	}
	
	/*
	 * getRows
	 * 
	 * @param (query) A mappable select query.
	 * @param (mappedParameters) The parameters to
	 * map into the query
	 * 
	 * @return Returns the number of rows matched by
	 * the mapped select query.
	 * 
	 */
	public function getRows($query, $mappedParameters = array())
	{
		$queryResult = ($mappedParameters) ? $this->preparedQuery($query, $mappedParameters) : 
											 $this->genericQuery($query);
		
		return $queryResult->num_rows;
	}	
}
