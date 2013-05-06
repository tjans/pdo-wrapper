<?php
class pdodb {

    private $dbh;
    private $stmt;
	private $sql;
	private $bindValues;
	
	public $error;
	
    public function __construct($dbServer, $dbName, $dbUser, $dbPass='') {
		$dsn = "mysql:host=$dbServer;dbname=$dbName";
		$options = array(
					PDO::ATTR_PERSISTENT => true, 
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
				);		
		
		try {
			$this->dbh = new pdo($dsn, $dbUser, $dbPass, $options);
		} catch (PDOException $e) {
			if($this) $this->error = $e->getMessage();
		}
    }

    /**
     * This function is basically a wrapper for the Prepare statement.  It sets up the base query.
     * @param  string $query - a SQL statement
     * @return pdodb object  - returns $this for method chaining
     */
    public function query($query) {
		$this->sql = $query;
        $this->stmt = $this->dbh->prepare($query);
        return $this;
    }
	
	/*
    public function bind($pos, $value, $type = null) {
        if( is_null($type) ) {
            switch( true ) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
	
        $this->stmt->bindValue($pos, $value, $type);
        return $this;
    }
	*/

	/**
	 * Opens up a new transaction
	 */
	public function beginTransaction()
	{
		return $this->dbh->beginTransaction();
	}
	
	/**
	 * Rolls an open transaction bak
	 */
	public function rollBack()
	{
		$this->dbh->rollBack();
	}
	
	/**
	 * Commits an open transaction
	 */
	public function commit()
	{
		$this->dbh->commit();
	}
	
	/**
	 * Binds an integer value to a placeholder in the query
	 * @param string $pos   placeholder name (:itemId)
	 * @param int 	 $value the value to inject into the sql (47)
	 */
	public function setInt($pos, $value)
	{
		$value = intval(trim($value));
		$this->stmt->bindValue($pos, $value, PDO::PARAM_INT);
        return $this;
	}

	/**
	 * Binds a boolean value to a placeholder in the query
	 * @param string  $pos   placeholder name (:isActive)
	 * @param boolean $value the value to inject into the query (true)
	 */
	public function setBool($pos, $value)
	{
		$this->stmt->bindValue($pos, $value, PDO::PARAM_BOOL);
        return $this;
	}

	/**
	 * Binds a value to a placeholder in the query
	 * @param var     $pos   placeholder name (:anyParameter)
	 * @param boolean $value the value to inject into the query ('Travis')
	 */
	public function set($pos, $value, $type=PDO::PARAM_STR)
	{
		$this->stmt->bindValue($pos, $value, $type);
        return $this;
	}
		
	/**
	 * Helper to reset internal vars after a query is performed
	 */
	private function reset()
	{
		$this->sql = "";
		$this->bindValues = array();
	}
		
	/**
	 * Helper function that sets up the base of a delete statement
	 * @param  string $table 	the name of a table you want to delete from
	 * @return pdodb - instance of this class is returned for method chaining
	 */
	public function delete($table)
	{
		$this->sql = "DELETE FROM $table ";
		return $this;
	}
	
	/**
	 * Helper function that builds an insert statement based on an array and executes it
	 * @param  string $table  the name of a table you want to insert into
	 * @param  array $fields  array of fields you want to insert where key = FielName and value = FieldValue
	 */
	public function insert($table, $fields)
	{		
		$this->sql = "INSERT INTO " . $table . " (" . implode(array_keys($fields), ", ") . ") VALUES (:" . implode(array_keys($fields), ", :") . ");";
		
		foreach($fields as $field=>$value)
		{
			$this->bindValues[":".$field] = array(
				'value'=>$value,
				'type'=>PDO::PARAM_STR
			);
		}
			
		return $this->run();
	}
	
	/**
	 * Helper function that sets up the base of an update statement
	 * @param  string $table  the name of a table you want to insert into
	 * @param  array $fields  array of fields you want to update where key = FielName and value = FieldValue
	 * @return pdodb - instance of this class is returned for method chaining
	 */
	public function update($table, $fields) {
		
		$fieldSize = sizeof($fields);

		$this->sql = "UPDATE " . $table . " SET ";
		foreach($fields as $field=>$value)
		{
			$bindField = ":update_".$value;
			$this->sql .= $field . " = $bindField"; 
			$this->bindValues[$bindField] = array(
				'value'=>$value,
				'type'=>PDO::PARAM_STR
			);
		}
		
		return $this;
	}
	
	/**
	 * Used (via chaining) in conjuction with query/delete methods
	 * @param  string $where the where clause, including placeholders, e.g., "Id >= :minId"
	 * @return pdodb - instance of this class is returned for method chaining
	 */
	public function where($where)
	{
		$this->sql .= " where $where ";
		return $this;
	}
	
	/**
	 * Binds an integer value to a "where" clause placeholder. Used (via chaining) in conjunction with "where" method
	 * @param string $field the placeholder you are replacing including colon, e.g., :minId
	 * @param int $value the value you are injecting into the query
	 * @return pdodb - instance of this class is returned for method chaining
	 */
	public function setWhereInt($field,$value)
	{
		$this->bindValues[$field] = array(
			'value'=>$value,
			'type'=>PDO::PARAM_INT
		);
		return $this;
	}

	/**
	 * Binds a boolean value to a "where" clause placeholder. Used (via chaining) in conjunction with "where" method
	 * @param string $field the placeholder you are replacing including colon, e.g., :minId
	 * @param bool $value the value you are injecting into the query
	 * @return pdodb - instance of this class is returned for method chaining
	 */
	public function setWhereBool($field,$value)
	{
		$this->bindValues[$field] = array(
			'value'=>$value,
			'type'=>PDO::PARAM_BOOL
		);
		return $this;
	}

	/**
	 * Binds a value to a "where" clause placeholder. Used (via chaining) in conjunction with "where" method
	 * @param string $field the placeholder you are replacing including colon, e.g., :minId
	 * @param var $value the value you are injecting into the query
	 * @return pdodb - instance of this class is returned for method chaining
	 */
	public function setWhere($field,$value)
	{
		$this->bindValues[$field] = array(
			'value'=>$value,
			'type'=>PDO::PARAM_STR
		);
		return $this;
	}
	
	/**
	 * This is the function that finalizes an "update" or "delete" method chain.  This will gather up all the
	 * bind values and clauses and execute the query
	 * @return integer - the number of rows affected by the statement
	 */
	public function run()
	{		
		// This prepares the query
		$this->query($this->sql);
		
		// This loops through the bindValues that represent the where clause and binds them appropriately
		foreach($this->bindValues as $field=>$fieldInfo)
		{
			$this->set($field, $fieldInfo['value'], $fieldInfo['type']);
		}
		
		$returnVal = $this->execute();
				
		// clear out any previous stuff (from updates/inserts)
		$this->reset();
		
		return $returnVal;
	}
	
	/**
	 * Helper function for printing out debug data for queries
	 */
	public function debug()
	{
		echo $this->sql;
		if($result !== false) {
				if(preg_match("/^(" . implode("|", array("select", "describe", "pragma")) . ") /i", $this->sql))
					echo "<div>Select, Describe, or Pragma</div>";
				elseif(preg_match("/^(" . implode("|", array("insert")) . ") /i", $this->sql))
					echo "<div>Insert</div>";
				elseif(preg_match("/^(" . implode("|", array("delete", "update")) . ") /i", $this->sql))
					echo "<div>Update or Delete</div>";
			}	
		
	}
	
	/**
	 * A wrapper for the execute method that determines the correct return value for the given query type
	 * e.g., Insert = LastInsertId, Update/Delete = RowCount
	 */
    private function execute() {		
		$result = $this->stmt->execute();
				
		if($result !== false) {
				if(preg_match("/^(" . implode("|", array("select", "describe", "pragma")) . ") /i", $this->sql))
					$returnVal = $result;
				elseif(preg_match("/^(" . implode("|", array("insert")) . ") /i", $this->sql))
					$returnVal = $this->dbh->lastInsertId();
				elseif(preg_match("/^(" . implode("|", array("delete", "update")) . ") /i", $this->sql))
					$returnVal = $this->stmt->rowCount();
			}	
		
		$this->reset();
		return $returnVal;
    }

    /**
     * This is a chainable function that fetches a multi-dimensional array of records (plural) from the data store.  Used when you expect
     * a possible list (2 or more) of results e.g., getUsersByGroupId, getLogHistory
     * @return array - the results from the query
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * This is a chainable function that fetches a single array representing one record (singular) from the data store. Used when you are
     * expecting one record e.g., getUserById, getUserCount, etc
     * @return [type] [description]
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }
}