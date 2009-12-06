<?php
/**
 * Provides access to a table per instance and holds information about {@link Constraint constraints},
 * {@link Column columns} and other inforatiom about that table.
 * This is the worker class of the framework.
 * It is the only class, that should communicate with the database.
 * @author andsens
 * @package glucose
 *
 * @property-read array $columns Indexed array of all {@link Column columns} in the table, ordered by how they appear in the table
 * @property-read Constraints\PrimaryKeyConstraint $primaryKeyConstraint {@link Constraint constraint} specifying the {@link Constraints\PrimaryKeyConstraint primary key constraint} of the table
 * @property-read array $uniqueConstraints {@link Constraint constraint} specifying the {@link Constraints\UniqueKeyConstraint unqiue constraints} of the table
 */
namespace Glucose;
use \Glucose\Exceptions\Table as E;
use \Glucose\Exceptions\Entity as EE;
use \Glucose\Exceptions\MySQL\MySQLErrorException;
use \Glucose\Exceptions\MySQL\MySQLConnectionException;
class Table implements \SplObserver {
	
	/**
	 * MySQLI connection to the database
	 * @var mysqli
	 */
	private static $mysqli;
	
	/**
	 * Prepared statement that retrieves all columns and meta-information of a table.
	 * @var mysqli_stmt
	 */
	private static $tableInformationQuery;
	
	/**
	 * Name of the table schema.
	 * @var string
	 */
	private $databaseName;
	
	/**
	 * Name of the table
	 * @var string
	 */
	private $tableName;
	
	/**
	 * Indexed array containing the {@link Column} in the table ordered
	 * by how they are listed in the database
	 * @var array
	 */
	private $columns;
	
	/**
	 * Constraint containing all the primary key columns of the table.
	 * @var Constraints\PrimaryKeyConstraint
	 */
	private $primaryKeyConstraint;
	
	/**
	 * Associative array containing all unique constraints of the table.
	 * @var array
	 */
	private $uniqueConstraints;
	
	/**
	 * Associative array containing all foreign key constraints of the table.
	 * @var array
	 */
	private $foreignKeyConstraints;
	
	/**
	 * Prepared statement containing the SELECT clause to retrieve every column of the table, primary keys excluded.
	 * @var mysqli_stmt
	 */
	private $selectStatement;
	
	/**
	 * INSERT statement specifying every column in the table.
	 * @var mysqli_stmt
	 */
	private $insertStatement;
	
	/**
	 * Engine responsible for keeping two records seperate.
	 * @var EntityEngine
	 */
	private $entityEngine;
	
	/**
	 * Constructs a table and maps it to a table in the currently selected schema given a table name.
	 * @param string $tableName Name of the table
	 */
	public function __construct($tableName) {
		if(!isset(self::$mysqli))
			throw new MySQLConnectionException('No database connection has been defined.');
		$dbNameResult = self::$mysqli->query('SELECT DATABASE();');
		list($databaseName) = $dbNameResult->fetch_array();
		$dbNameResult->free();
		if($databaseName == null)
			throw new E\NoDatabaseSelectedException('You have not selected any database.');
		
		$this->databaseName = $databaseName;
		$this->tableName = $tableName;
		$this->retrieveTableInformation();
		$this->entityEngine = new EntityEngine($this->uniqueConstraints);
		$this->prepareSelectStatement();
		$this->prepareInsertStatement();
	}

	/**
	 * Connects the table to a database and prepares the column retrieval statement.
	 * @param mysqli $mysqli MySQLi connection to the database
	 */
	public static function connect(\mysqli $mysqli) {
		if($mysqli->connect_errno == 0) {
			self::$mysqli = $mysqli;
			self::prepareTableInformationRetrievalStatement();
		} else {
			throw new MySQLConnectionException('The MySQLi instance is not connected to a database.');
		}
	}

	/**
	 * Prepares the column retrieval statement.
	 */
	private static function prepareTableInformationRetrievalStatement() {
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
		self::$tableInformationQuery = self::$mysqli->prepare($sql);
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
	}

	/**
	 * Retrieves all the columns and their meta-information of the table and constructs new column objects for every one.
	 * @todo Lock constraints by using constructor instead of addColumn
	 * @todo Foreign keys should point at columns and columns at tables. Lazy loading
	 */
	private function retrieveTableInformation() {
		$this->columns = array();
		$this->uniqueConstraints = array();
		$this->foreignKeyConstraints = array();

		self::$tableInformationQuery->bind_param('ss', $this->databaseName, $this->tableName);
		self::$tableInformationQuery->execute();
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
		self::$tableInformationQuery->store_result();
		if(self::$tableInformationQuery->num_rows() == 0)
			throw new E\MissingTableException("The table '".$this->tableName."' does not exist.");

		self::$tableInformationQuery->bind_result($name, $ordinalPosition, $defaultValue, $isNullable, $type, $maxLength, $extra,
		$constraintType, $constraintName, $referencedTableName, $referencedColumnName, $updateRule, $deleteRule,
		$refererConstraintName, $refererTableName, $refererColumnName);

		while(self::$tableInformationQuery->fetch()) {
			if(!isset($this->columns[$name]))
				$this->columns[$name] = new Column((string) $name, (string) $type, (integer) $maxLength, (boolean) $isNullable == 'NO', $defaultValue);
			$column = $this->columns[$name];
			if($constraintType !== null) {
				switch($constraintType) {
					case 'PRIMARY KEY':
						if(!isset($this->primaryKeyConstraint))
							$this->primaryKeyConstraint = new Constraints\PrimaryKeyConstraint($constraintName);
						if(!isset($this->uniqueConstraints[$constraintName]))
							$this->uniqueConstraints[$constraintName] = $this->primaryKeyConstraint;
						$this->primaryKeyConstraint->addColumn($column);
						if($extra == 'auto_increment')
							$this->primaryKeyConstraint->autoIncrementColumn = $column;
						break;
					case 'UNIQUE':
						if(!isset($this->uniqueConstraints[$constraintName]))
							$this->uniqueConstraints[$constraintName] = new Constraints\UniqueConstraint($constraintName);
						$this->uniqueConstraints[$constraintName]->addColumn($column);
						break;
					case 'FOREIGN KEY':
						if(!isset($this->foreignKeyConstraints[$constraintName]))
							$this->foreignKeyConstraints[$constraintName] = new Constraints\ForeignKeyConstraint($constraintName);
						$this->foreignKeyConstraints[$constraintName]->addColumn($column);
						break;
				}
			}
		}
		self::$tableInformationQuery->free_result();
		if(!isset($this->primaryKeyConstraint))
			throw new E\MissingPrimaryKeyConstraintException('The table "'.$this->tableName.'" does not have any primary key constraints.');
	}

	/**
	 * Magic method, which returns various properties of the table.
	 * @ignore
	 * @param string $name Name of the property to return
	 * @return mixed Value of the property
	 */
	public function __get($name) {
		switch($name) {
			case 'columns':
				return $this->columns;
			case 'primaryKeyConstraint':
				return $this->primaryKeyConstraint;
			case 'uniqueConstraints':
				return $this->uniqueConstraints;
		}
	}
	
	/**
	 * Prepares the INSERT statement for the table.
	 */
	private function prepareInsertStatement() {
		$sql = 'INSERT INTO `'.$this->databaseName.'`.`'.$this->tableName.'` (';
		$sql .= '`'.implode('`, `', $this->columns).'`) ';
		$sql .= 'VALUES ('.str_repeat('?, ', count($this->columns)-1).'?)';
		$this->insertStatement = self::$mysqli->prepare($sql);
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
	}

	/**
	 * Prepares the SELECT statement for the table.
	 */
	private function prepareSelectStatement() {
		$sql = 'SELECT `'.implode('`, `', array_diff($this->columns, $this->primaryKeyConstraint->columns)).'` ';
		$sql .= 'FROM `'.$this->databaseName.'`.`'.$this->tableName.'` ';
		$sql .= 'WHERE `'.implode('` = ? AND `', $this->primaryKeyConstraint->columns).'` = ?';
		$this->selectStatement = self::$mysqli->prepare($sql);
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
	}
	
	/**
	 * INSERTs a set of data into the table.
	 * @param array $insertValues Full set of values as an indexed array
	 * @return int The last mysql insert id
	 */
	private function insertIntoDB(Entity $entity) {
		$statementTypes = '';
		foreach($this->columns as $column)
			$statementTypes .= $column->statementType;
		$statementValues = array(&$statementTypes);
		$insertValues = $entity->getValues($this->columns);
		foreach($insertValues as $key => $value)
			$statementValues[] = &$insertValues[$key];
		call_user_func_array(array(&$this->insertStatement, 'bind_param'), $statementValues);
		$this->insertStatement->execute();
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
		if(self::$mysqli->insert_id > 0)
			$entity->fields[$this->primaryKeyConstraint->autoIncrementColumn->name]->dbValue = self::$mysqli->insert_id;
		$entity->dbUpdated();
		$entity->inDB = true;
		$this->entityEngine->updateIdentifiersDB($entity);
	}
	
	/**
	 * SELECTs an entity in the database and returns its values.
	 * @param array $uniqueValues Indexed array of unique values identifying the entry
	 * @param Constraints\UniqueConstraint $constraint {@link Constraint Constraint}the values identify,
	 * if null {@link Constraints\PrimaryKeyConstraint primary key} is assumed
	 * @throws NonExistentEntityException
	 * @throws MultipleEntitiesException
	 * @return Associative array over the resulting values
	 */
	public function select(array $uniqueValues, Constraints\UniqueConstraint $constraint = null) {
		if($constraint === null)
			$constraint = $this->primaryKeyConstraint;
		elseif(!in_array($constraint, $this->uniqueConstraints, true))
			throw new E\InvalidUniqueConstraintException('The unique constraint does not match any constraint in the table.');
		
		$entity = $this->entityEngine->findModel($uniqueValues, $constraint);
		if($entity !== null)
			return $entity;
		$entity = $this->entityEngine->findDB($uniqueValues, $constraint);
		if($entity !== null)
			throw new E\EntityValuesChangedException('The values you specified no longer match an entity.');
		
		if(!isset($constraint->selectStatement)) {
			$sql = 'SELECT `'.implode('`, `', array_diff($this->columns, $constraint->columns)).'` ';
			$sql .= 'FROM `'.$this->databaseName.'`.`'.$this->tableName.'` ';
			$sql .= 'WHERE `'.implode('` = ? AND `', $constraint->columns).'` = ?';
			$constraint->selectStatement = self::$mysqli->prepare($sql);
			if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
		}
		$constraintStatementTypes = $constraint->statementTypes;
		$statementValues = array(&$constraintStatementTypes);
		foreach($uniqueValues as $key => $value)
			$statementValues[] = &$uniqueValues[$key];
		call_user_func_array(array(&$constraint->selectStatement, 'bind_param'), $statementValues);
		$constraint->selectStatement->execute();
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
		$constraint->selectStatement->store_result();
		
		$values = null;
		$numberOfRowsReturned = $constraint->selectStatement->num_rows();
		if($numberOfRowsReturned == 1) {
			$metadata = $constraint->selectStatement->result_metadata();
			$fields = array();
			$values = array();
			while ($field = $metadata->fetch_field())
				$fields[] = &$values[$field->name];
			call_user_func_array(array(&$constraint->selectStatement, 'bind_result'), $fields);
			$constraint->selectStatement->fetch();
			$constraint->selectStatement->free_result();
		} elseif($numberOfRowsReturned < 1) {
			$constraint->selectStatement->free_result();
			throw new E\NonExistentEntityException('The values you specified do not match any entry in the table.');
		} else {
			$constraint->selectStatement->free_result();
			throw new E\MultipleEntitiesException('The values you specified match two or more entries in the table.');
		}
		
		if($constraint != $this->primaryKeyConstraint) {
			$primaryKeyValues = array();
			foreach($this->primaryKeyConstraint->columns as $column)
				$primaryKeyValues[] = $values[$column->name];
			$entity = $this->entityEngine->findDB($primaryKeyValues, $this->primaryKeyConstraint);
		}
		if($entity === null)
			$entity = $this->newEntity();
		foreach($constraint->columns as $index => $column)
			if(!isset($entity->fields[$column->name]->value))
				$entity->fields[$column->name]->dbValue = $uniqueValues[$index];
		foreach($values as $fieldName => $fieldValue)
			if(!isset($entity->fields[$fieldName]->value))
				$entity->fields[$fieldName]->dbValue = $fieldValue;
		$entity->inDB = true;
		$this->entityEngine->updateIdentifiersDB($entity);
		$this->entityEngine->updateIdentifiersModel($entity);
		return $entity;
	}
	
	/**
	 * Updates a dataset in the table
	 * @param array $updateValues Associative array containing the names and values of the fields to be updated
	 * @param string $updateFieldStatementTypes Concatenated string composed of the types of the fields to be updated
	 * @param $primaryKeyValues Indexed array containing the primary key values of the dataset to be updated
	 */
	private function updateDB(Entity $entity) {
		$updateColumnNames = array();
		$statementTypes = "";
		$statementValues = array(&$statementTypes);
		$updateValues = $entity->getUpdateValues();
		if(empty($updateValues))
			return;
		foreach($updateValues as $columnName => $updateValue) {
			$updateColumnNames[] = $columnName;
			$statementTypes .= $this->columns[$columnName]->statementType;
			$statementValues[] = &$updateValue;
		}
		$identifier = array();
		foreach($this->primaryKeyConstraint->columns as $column) {
			$statementTypes .= $column->statementType;
			$identifier[$column->name] = $entity->fields[$column->name]->dbValue;
			$statementValues[] = &$identifier[$column->name];
		}
		
		$updateStatement = $this->primaryKeyConstraint->getUpdateStatement($updateColumnNames);
		if($updateStatement == null) {
			$sql = 'UPDATE `'.$this->databaseName.'`.`'.$this->tableName.'` ';
			$sql .= 'SET `'.implode('` = ?, `', $updateColumnNames).'` = ? ';
			$sql .= 'WHERE `'.implode('` = ? AND `', $this->primaryKeyConstraint->columns).'` = ?';
			$updateStatement = self::$mysqli->prepare($sql);
			if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
			$this->primaryKeyConstraint->setUpdateStatement($updateColumnNames, $updateStatement);
		}
		call_user_func_array(array(&$updateStatement, 'bind_param'), $statementValues);
		$updateStatement->execute();
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
		$numberOfRowsAffected = $updateStatement->affected_rows;
		if($numberOfRowsAffected < 1) {
			throw new E\NoAffectedRowException('The values you specified do not match any entry in the table or the update caused no changes.');
		} elseif($numberOfRowsAffected > 1) {
			throw new E\MultipleEntitiesException('The values you specified match two or more entries in the table.');
		}
		$entity->dbUpdated();
		$this->entityEngine->updateIdentifiersDB($entity);
	}
	
	private function deleteFromDB(Entity $entity) {
		// Make this prepared!
		if(!isset($this->primaryKeyConstraint->deleteStatement)) {
			$sql = 'DELETE FROM `'.$this->databaseName.'`.`'.$this->tableName.'` ';
			$sql .= 'WHERE `'.implode('` = ? AND `', $this->primaryKeyConstraint->columns).'` = ?';
			$this->primaryKeyConstraint->deleteStatement = self::$mysqli->prepare($sql);
			if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
		}
		$constraintStatementTypes = $this->primaryKeyConstraint->statementTypes;
		$statementValues = array(&$constraintStatementTypes);
		foreach($entity->getDBValues($this->primaryKeyConstraint->columns) as $value)
			$statementValues[] = &$value;
		call_user_func_array(array(&$this->primaryKeyConstraint->deleteStatement, 'bind_param'), $statementValues);
		$this->primaryKeyConstraint->deleteStatement->execute();
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
		$numberOfRowsAffected = $this->primaryKeyConstraint->deleteStatement->affected_rows;
		if($numberOfRowsAffected < 1) {
			throw new E\NoAffectedRowException('The values you specified do not match any entry in the table.');
		} elseif($numberOfRowsAffected > 1) {
			throw new E\MultipleEntitiesException('The values you specified match two or more entries in the table.');
		}
		$entity->inDB = false;
	}
	
	public function newEntity() {
		$entity = new Entity($this->columns);
		$entity->attach($this);
		return $entity;
	}
	
	public function updateIdentifiers(Entity $entity) {
		$this->entityEngine->updateIdentifiersModel($entity);
	}
	
	public function update(\SplSubject $subject) {
		if($subject instanceof Entity) {
			$entity = $subject;
			if($entity->referenceCount == 0) {
				if($entity->inDB)
					if($entity->deleted)
						$this->deleteFromDB($entity);
					else
						$this->updateDB($entity);
				else
					if(!$entity->deleted)
						$this->insertIntoDB($entity);
				$this->entityEngine->dereference($entity);
			}
		}
	}
}
?>