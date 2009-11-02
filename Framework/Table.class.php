<?php
class Table {
	
	/**
	 * MySQLI connection to the database
	 * @var mysqli
	 */
	private static $mysqli;
	private static $fieldQuery;
	private static $tables;
	
	private $databaseName;
	private $tableName;
	
	/**
	 * Array containing the {@link Field} in the table ordered
	 * by how they are listed in the database
	 * @var array
	 */
	private $fields;
	
	private $primaryKeyConstraint;
	private $uniqueConstraints;
	private $foreignKeyConstraints;
	
	/**
	 * Statement containing the select clause to retrieve
	 * every field of this table.
	 * @var mysqli_stmt
	 */
	private $selectStatement;
	
	/**
	 * Insert statement specifying every field in this table
	 * @var mysqli_stmt
	 */
	private $insertStatement;
	
	public function __construct($tableName) {
		if(!isset(self::$mysqli))
			throw new MySQLConnectionException('No database connection has been defined');
		$dbNameResult = self::$mysqli->query('SELECT DATABASE();');
		list($databaseName) = $dbNameResult->fetch_array();
		$dbNameResult->free();
		if($databaseName == null)
			throw new TableException('You have not selected any database!');
		
		if(!isset(self::$tables[$databaseName]))
			$tables[$databaseName] = array();
		$tables[$databaseName][$tableName] = $this;
		
		$this->databaseName = $databaseName;
		$this->tableName = $tableName;
		$this->retrieveFields();
		$this->prepareSelectStatement();
		$this->prepareInsertStatement();
	}
	
	public static function prepareStatements(mysqli $mysqli) {
		if(!isset(self::$tables))
			self::$tables = array();
		self::$mysqli = $mysqli;
		self::prepareFieldRetrievalStatement();
	}
	
	public static function prepareFieldRetrievalStatement() {
		$sql = <<<End
SELECT
	`columns`.`COLUMN_NAME`, `columns`.`ORDINAL_POSITION`, `columns`.`COLUMN_DEFAULT`, `columns`.`IS_NULLABLE`,
	`columns`.`DATA_TYPE`, `columns`.`CHARACTER_MAXIMUM_LENGTH`, `columns`.`EXTRA`,
	`table_constraints`.`CONSTRAINT_TYPE`, `table_constraints`.`CONSTRAINT_NAME`,
	`column_usage`.`REFERENCED_TABLE_NAME`, `column_usage`.`REFERENCED_COLUMN_NAME`,
	`referential_constraints`.`UPDATE_RULE`, `referential_constraints`.`DELETE_RULE`,
	`column_references`.`CONSTRAINT_NAME`, `column_references`.`TABLE_NAME`, `column_references`.`COLUMN_NAME`
FROM `information_schema`.`COLUMNS` columns
LEFT JOIN `information_schema`.`KEY_COLUMN_USAGE` column_usage
	ON `column_usage`.`TABLE_SCHEMA` = `columns`.`TABLE_SCHEMA`
	AND `column_usage`.`TABLE_NAME` = `columns`.`TABLE_NAME`
	AND `column_usage`.`COLUMN_NAME` = `columns`.`COLUMN_NAME`
LEFT JOIN `information_schema`.`TABLE_CONSTRAINTS` table_constraints
	ON `table_constraints`.`TABLE_SCHEMA` = `column_usage`.`TABLE_SCHEMA`
	AND `table_constraints`.`TABLE_NAME` = `column_usage`.`TABLE_NAME`
	AND `table_constraints`.`CONSTRAINT_SCHEMA` = `column_usage`.`CONSTRAINT_SCHEMA`
	AND `table_constraints`.`CONSTRAINT_NAME` = `column_usage`.`CONSTRAINT_NAME`
LEFT JOIN `information_schema`.`REFERENTIAL_CONSTRAINTS` referential_constraints
	ON `referential_constraints`.`TABLE_NAME` = `column_usage`.`TABLE_NAME`
	AND `referential_constraints`.`CONSTRAINT_SCHEMA` = `column_usage`.`CONSTRAINT_SCHEMA`
	AND `referential_constraints`.`CONSTRAINT_NAME` = `column_usage`.`CONSTRAINT_NAME`
LEFT JOIN `information_schema`.`KEY_COLUMN_USAGE` column_references
	ON `column_references`.`REFERENCED_TABLE_SCHEMA` = `columns`.`TABLE_SCHEMA`
	AND `column_references`.`REFERENCED_TABLE_NAME` = `columns`.`TABLE_NAME`
	AND `column_references`.`REFERENCED_COLUMN_NAME` = `columns`.`COLUMN_NAME`
WHERE `columns`.`TABLE_SCHEMA` = ?
AND `columns`.`TABLE_NAME` = ?
ORDER BY `columns`.`ORDINAL_POSITION`
End;
		self::$fieldQuery = self::$mysqli->prepare($sql);
	}
	
	private function retrieveFields() {
		$this->fields = array();
		$this->uniqueConstraints = array();
		$this->foreignKeyConstraints = array();
		
		self::$fieldQuery->bind_param('ss', $this->databaseName, $this->tableName);
		self::$fieldQuery->execute();
		self::$fieldQuery->store_result();
		
		self::$fieldQuery->bind_result($name, $ordinalPosition, $defaultValue, $isNullable, $type, $maxLength, $extra,
		$constraintType, $constraintName, $referencedTableName, $referencedColumnName, $updateRule, $deleteRule,
		$refererConstraintName, $refererTableName, $refererColumnName);
		
		while(self::$fieldQuery->fetch()) {
			if($extra == 'auto_increment' || strtoupper($default) == 'CURRENT_TIMESTAMP')
				$default = null;
			if(!isset($this->fields[$ordinalPosition]))
				$this->fields[$ordinalPosition] = new Field($name, $type, $maxLength, $isNullable == 'NO', $defaultValue);
			$field = $this->fields[$ordinalPosition];
			if($constraintType !== null) {
				switch($constraintType) {
					case 'PRIMARY KEY':
						if(!isset($this->primaryKeyConstraint))
							$this->primaryKeyConstraint = new PrimaryKeyConstraint($constraintName);
						$this->primaryKeyConstraint->addField($field);
						if($extra == 'auto_increment')
							$this->primaryKeyConstraint->autoIncrementField = $field;
						break;
					case 'UNIQUE':
						if(!isset($this->uniqueConstraints[$constraintName]))
							$this->uniqueConstraints[$constraintName] = new UniqueConstraint($constraintName);
						$this->uniqueConstraints[$constraintName]->addField($field);
						break;
					case 'FOREIGN KEY':
						// TODO: Foreign keys should point at fields and fields at tables. Lazy loading
						if(!isset($this->foreignKeyConstraints[$constraintName]))
							$this->foreignKeyConstraints[$constraintName] = new ForeignKeyConstraint($constraintName);
						$this->foreignKeyConstraints[$constraintName]->addField($field);
						break;
				}
			}
		}
		if(!isset($this->primaryKeyConstraint))
			throw new MissingPrimaryKeyConstraintException('The table "'.$this->tableName.'" does not have any primary key constraints.');
		self::$fieldQuery->free_result();
	}
	
	// Lock constraints by using constructor instead of addField
	public function __get($name) {
		switch($name) {
			case 'fields':
				return $this->fields;
			case 'primaryKeys':
				return $this->primaryKeyConstraint->fields;
			case 'primaryKeyConstraint':
				return $this->primaryKeyConstraint;
		}
	}
	
	private function prepareSelectStatement() {
		//var_dump($this->primaryKeyConstraint);
		$sql = 'SELECT `'.implode('`, `', array_diff($this->fields, $this->primaryKeyConstraint->fields)).'` ';
		$sql .= 'FROM `'.$this->databaseName.'`.`'.$this->tableName.'` ';
		$sql .= 'WHERE `'.implode('` = ? AND `', $this->primaryKeyConstraint->fields).'` = ?';
		$this->selectStatement = self::$mysqli->prepare($sql);
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
	}
	
	private function prepareInsertStatement() {
		$sql = 'INSERT INTO `'.$this->databaseName.'`.`'.$this->tableName.'` (';
		$sql .= '`'.implode('`, `', $this->fields).'`) ';
		$sql .= 'VALUES ('.str_repeat('?, ', count($this->fields)-1).'?)';
		$this->insertStatement = self::$mysqli->prepare($sql);
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
	}
	
	public function select(array $primaryKeyValues) {
		$primaryKeyStatementTypes = $this->primaryKeyConstraint->statementTypes;
		$statementValues = array(&$primaryKeyStatementTypes);
		foreach($primaryKeyValues as $key => $value)
			$statementValues[] = &$primaryKeyValues[$key];
		call_user_func_array(array(&$this->selectStatement, 'bind_param'), $statementValues);
		$this->selectStatement->execute();
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
		$this->selectStatement->store_result();
		$values = null;
		if($this->selectStatement->num_rows() != 0) {
			$metadata = $this->selectStatement->result_metadata();
			$values = array();
			while ($field = $metadata->fetch_field())
				$fields[] = &$values[$field->name]; 
			call_user_func_array(array(&$this->selectStatement, 'bind_result'), $fields);
			$this->selectStatement->fetch();
		}
		$this->selectStatement->free_result();
		return $values;
	}
	
	public function insert(array $insertValues) {
		$statementTypes = '';
		foreach($this->fields as $field)
			$statementTypes .= $field->statementType;
		$statementValues = array(&$statementTypes);
		foreach($insertValues as $key => $value)
			$statementValues[] = &$insertValues[$key];
		call_user_func_array(array(&$this->insertStatement, 'bind_param'), $statementValues);
		$this->insertStatement->execute();
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
		return self::$mysqli->insert_id;
	}
	
	public function update(array $updateValues, $updateFieldStatementTypes, array $primaryKeyValues) {
		$primaryKeyFieldNames = array();
		foreach($this->primaryKeys as $field)
			$primaryKeyFieldNames[] = $field->name;
		$sql = 'UPDATE `'.$this->databaseName.'`.`'.$this->tableName.'` ';
		$sql .= 'SET `'.implode('` = ?, `', array_keys($updateValues)).'` = ? ';
		$sql .= 'WHERE `'.implode('` = ? AND `', $primaryKeyFieldNames).'` = ?';
		$updateStatement = self::$mysqli->prepare($sql);
		$statementTypes = $updateFieldStatementTypes.$this->primaryKeyConstraint->statementTypes;
		$statementValues = array(&$statementTypes);
		foreach($updateValues as $key => $value)
			$statementValues[] = &$updateValues[$key];
		foreach($primaryKeyValues as $key => $value)
			$statementValues[] = &$primaryKeyValues[$key];
		call_user_func_array(array(&$updateStatement, 'bind_param'), $statementValues);
		$updateStatement->execute();
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
	}
}
?>