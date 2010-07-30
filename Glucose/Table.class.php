<?php
/**
 * Provides access to a table per instance and holds information about {@link Constraint constraints},
 * {@link Column columns} and other inforatiom about that table.
 * This is the worker class of the framework.
 * It is the only class, that should communicate with the database.
 * @author andsens
 *
 * @property-read array $columns Indexed array of all {@link Column columns} in the table, ordered by how they appear in the table
 * @property-read Constraints\PrimaryKeyConstraint $primaryKeyConstraint {@link Constraint constraint} specifying the {@link Constraints\PrimaryKeyConstraint primary key constraint} of the table
 * @property-read array $uniqueConstraints {@link Constraint constraint} specifying the {@link Constraints\UniqueKeyConstraint unqiue constraints} of the table
 */
namespace Glucose;
use \Glucose\Exceptions\Table as E;
use \Glucose\Exceptions\Entity as EE;
require_once __DIR__.'/Exceptions/MySQL/MySQLErrorException.class.php'; // Workaround
use \Glucose\Exceptions\MySQL\MySQLErrorException;
use \Glucose\Exceptions\MySQL\MySQLConnectionException;
class Table {
	
	/**
	 * MySQLI connection to the database
	 * @var mysqli
	 */
	private static $mysqli;
	
	const REQUIRED_MYSQL_VERSION = 50136;
	
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
	
	private $insertStatements = array();
	
	/**
	 * Engine responsible for keeping two records seperate.
	 * @var EntityEngine
	 */
	private $entityEngine;

	/**
	 * Connects the table to a database and prepares the column retrieval statement.
	 * @param mysqli $mysqli MySQLi connection to the database
	 */
	public static function connect(\mysqli $mysqli) {
		if($mysqli->connect_errno != 0)
			throw new MySQLConnectionException('The MySQLi instance is not connected to a database.');
		if($mysqli->server_version == '')
			throw new MySQLConnectionException('The MySQLi instance is not connected to a database.');
		if($mysqli->server_version < self::REQUIRED_MYSQL_VERSION)
			throw new MySQLConnectionException('Glucose only works with MySQL version '.self::REQUIRED_MYSQL_VERSION.
			" or higher, the server you are trying to connect to is version $mysqli->server_version.");
		self::$mysqli = $mysqli;
		self::prepareTableInformationRetrievalStatement();
	}
	
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
	}

	/**
	 * Prepares the column retrieval statement.
	 */
	private static function prepareTableInformationRetrievalStatement() {
		$sql = <<<End
SELECT
	`columns`.`COLUMN_NAME`, `columns`.`ORDINAL_POSITION`, `columns`.`COLUMN_DEFAULT`, `columns`.`IS_NULLABLE`,
	`columns`.`COLUMN_TYPE`, `columns`.`CHARACTER_MAXIMUM_LENGTH`, `columns`.`EXTRA`,
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
		
		$this->bindAndExecute(self::$tableInformationQuery, 'ss', array($this->databaseName, $this->tableName));
		self::$tableInformationQuery->store_result();
		if(self::$tableInformationQuery->num_rows() == 0)
			throw new E\MissingTableException("The table '".$this->tableName."' does not exist.");
		
		self::$tableInformationQuery->bind_result($name, $ordinalPosition, $defaultValue, $isNullable, $type, $maxLength, $extra,
		$constraintType, $constraintName, $referencedTableName, $referencedColumnName, $updateRule, $deleteRule,
		$refererConstraintName, $refererTableName, $refererColumnName);
		
		while(self::$tableInformationQuery->fetch()) {
			if(!isset($this->columns[$name]))
				$this->columns[$name] = new Column((string) $name, (string) $type, (integer) $maxLength, (boolean) ($isNullable == 'NO'), $defaultValue, (string) $extra);
			$column = $this->columns[$name];
			if($constraintType !== null) {
				switch($constraintType) {
					case 'PRIMARY KEY':
						if(!isset($this->primaryKeyConstraint))
							$this->primaryKeyConstraint = new Constraints\PrimaryKeyConstraint($constraintName);
						if(!isset($this->uniqueConstraints[$constraintName]))
							$this->uniqueConstraints[$constraintName] = $this->primaryKeyConstraint;
						$this->primaryKeyConstraint->addColumn($column);
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
		
		// TODO: We may be able to work without one! Would be kewl and kind of easy, because there wouldn't be any foreign keys.
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
	
	private function bindAndExecute(\mysqli_stmt $statement, $types, array $values) {
		$statementValues = array($types);
		foreach($values as &$value)
			$statementValues[] = &$value;
		$noParams = count($values);
		if(strlen($types) != $noParams)
			throw new E\ParameterCountMismatchException('There is a mismatch between the number of statement types and parameters.');
		if($noParams > 0)
			call_user_func_array(array(&$statement, 'bind_param'), $statementValues);
		$statement->execute();
		if(self::$mysqli->errno > 0) throw MySQLErrorException::findClass(self::$mysqli);
	}
	
	private function implode($tokens, $glue = '', $wrapper = '', $replacement = null) {
		$string = '';
		$noTokens = count($tokens);
		if($noTokens == 0)
			return $string;
		foreach($tokens as $token)
			$string .= $wrapper.(isset($replacement)?$replacement:$token).$wrapper.$glue;
		return substr($string, 0, -strlen($glue));
	}
	
	private static function createColumnHash(array $columnNames) {
		$compoundHash = '';
		foreach($columnNames as $columnName)
			$compoundHash .= sha1($columnName);
		return sha1($compoundHash);
	}
	
	/**
	 * INSERTs a set of data into the table.
	 * @param array $insertValues Full set of values as an indexed array
	 * @return int The last mysql insert id
	 */
	private function insert(Entity $entity) {
		if($entity->inDB)
			throw new E\EntityAlreadyInDatabaseException('The entity you are trying to insert already exists in the database.');
		
		$insertValuesColumnNames = array();
		$statementTypes = "";
		$statementValues = array();
		foreach($entity->fields as $columnName => $field) {
			if(!$field->setToDefault) {
				$insertValuesColumnNames[] = $columnName;
				$statementTypes .= $field->column->statementType;
				$statementValues[] = $field->value;
			}
		}
		
		$statementIdentifier = $this->createColumnHash($insertValuesColumnNames);
		if(!array_key_exists($statementIdentifier, $this->insertStatements)) {
			$sql = 'INSERT INTO `'.$this->databaseName.'`.`'.$this->tableName.'` ';
			$sql .= '('.$this->implode($this->columns, ',', '`').') ';
			$placeholders = array();
			foreach($this->columns as $column)
				if(in_array($column->name, $insertValuesColumnNames))
					$placeholders[] = '?';
				elseif($column->defaultCurrentTimestamp)
					$placeholders[] = 'DEFAULT';
				else
					$placeholders[] = "DEFAULT(`$column->name`)";
			$sql .= 'VALUES ('.$this->implode($placeholders, ',').')';
			$stmt = self::$mysqli->prepare($sql);
			if($stmt === false) throw MySQLErrorException::findClass(self::$mysqli);
			$this->insertStatements[$statementIdentifier] = $stmt;
		}
		$this->bindAndExecute($this->insertStatements[$statementIdentifier], $statementTypes, $statementValues);
		foreach($entity->fields as $columnName => $field) {
			if($field->column->isAutoIncrement && self::$mysqli->insert_id > 0)
				$field->dbValue = self::$mysqli->insert_id;
			$field->dbInserted();
		}
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
	public function select(array $uniqueValues, Constraints\UniqueConstraint $constraint) {
		if(!in_array($constraint, $this->uniqueConstraints, true))
			throw new E\InvalidUniqueConstraintException('The unique constraint does not match any constraint in the table.');
		if(in_array(null, $uniqueValues, true))
			throw new E\InvalidUniqueValuesException('Unique values cannot be null.');
		
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
			$stmt = self::$mysqli->prepare($sql);
			if($stmt === false) throw MySQLErrorException::findClass(self::$mysqli);
			$constraint->selectStatement = $stmt;
		}
		$this->bindAndExecute($constraint->selectStatement, $constraint->statementTypes, $uniqueValues);
		$constraint->selectStatement->store_result();
		
		$values = null;
		$numberOfRowsReturned = $constraint->selectStatement->num_rows;
		if($numberOfRowsReturned == 1) {
			$metadata = $constraint->selectStatement->result_metadata();
			$fields = array();
			$values = array();
			while($field = $metadata->fetch_field())
				$fields[] = &$values[$field->name];
			call_user_func_array(array(&$constraint->selectStatement, 'bind_result'), $fields);
			$constraint->selectStatement->fetch();
			$constraint->selectStatement->free_result();
			
			/* TODO: Not sure if this is neccessary any longer. We always fetch all values anyways.
			 * Probably a left over from the lazy loading feature.
			 */
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
		} elseif($numberOfRowsReturned < 1) {
			$constraint->selectStatement->free_result();
			throw new E\NonExistentEntityException('The values you specified do not match any entry in the table.');
		} else {
			$constraint->selectStatement->free_result();
			throw new E\MultipleEntitiesException('The values you specified match two or more entries in the table.');
		}
	}
	
	private function refresh(Entity $entity) {
		if(!$entity->inDB)
			throw new E\EntityNotInDatabaseException('The entity you are trying to refresh does not exist in the database.');
		
		$refreshColumnNames = array();
		foreach($entity->fields as $name => $field)
			if($field->updateModel)
				$refreshColumnNames[] = $name;
		
		if(empty($refreshColumnNames))
			return;
		$constraint = $this->primaryKeyConstraint;
		$statementIdentifier = $this->createColumnHash($refreshColumnNames);
		if(!array_key_exists($statementIdentifier, $constraint->refreshStatements)) {
			$sql = 'SELECT `'.implode('`, `', $refreshColumnNames).'` ';
			$sql .= 'FROM `'.$this->databaseName.'`.`'.$this->tableName.'` ';
			$sql .= 'WHERE `'.implode('` = ? AND `', $constraint->columns).'` = ?';
			$stmt = self::$mysqli->prepare($sql);
			if($stmt === false) throw MySQLErrorException::findClass(self::$mysqli);
			$constraint->refreshStatements[$statementIdentifier] = $stmt;
		}
		$refreshStatement = $constraint->refreshStatements[$statementIdentifier];
		
		$entityValues = array();
		foreach($constraint->columns as $column)
			$entityValues[$column->name] = $entity->fields[$column->name]->value;
		$this->bindAndExecute($refreshStatement, $constraint->statementTypes, $entityValues);
		$refreshStatement->store_result();
		
		$numberOfRowsReturned = $refreshStatement->num_rows;
		if($numberOfRowsReturned == 1) {
			$metadata = $refreshStatement->result_metadata();
			$fields = array();
			$values = array();
			while($field = $metadata->fetch_field())
				$fields[] = &$values[$field->name];
			call_user_func_array(array(&$refreshStatement, 'bind_result'), $fields);
			$refreshStatement->fetch();
			$refreshStatement->free_result();
		} elseif($numberOfRowsReturned < 1) {
			$refreshStatement->free_result();
			throw new E\NonExistentEntityException('The values you specified do not match any entry in the table.');
		} else {
			$refreshStatement->free_result();
			throw new E\MultipleEntitiesException('The values you specified match two or more entries in the table.');
		}
		
		foreach($values as $columnName => $value)
			$entity->fields[$columnName]->dbValue = $value;
	}
	
	/**
	 * Updates a dataset in the table
	 * @param array $updateValues Associative array containing the names and values of the fields to be updated
	 * @param string $updateFieldStatementTypes Concatenated string composed of the types of the fields to be updated
	 * @param $primaryKeyValues Indexed array containing the primary key values of the dataset to be updated
	 */
	private function update(Entity $entity) {
		if(!$entity->inDB)
			throw new E\EntityNotInDatabaseException('The entity you are trying to delete does not exist in the database.');
		
		$updateValuesColumnNames = array();
		$updateDefaultsColumnNames = array();
		$statementTypes = "";
		$statementValues = array();
		foreach($entity->fields as $columnName => $field) {
			if($field->updateDB && !$field->setToDefault) {
				$updateValuesColumnNames[] = $columnName;
				$statementTypes .= $field->column->statementType;
				$statementValues[] = $field->value;
			} elseif($field->updateDB) {
				$updateDefaultsColumnNames[] = $columnName;
			}
		}
		if(count($updateValuesColumnNames) == 0 && count($updateDefaultsColumnNames) == 0)
			return;
		$statementTypes .= $this->primaryKeyConstraint->statementTypes;
		$statementValues = array_merge($statementValues, $entity->getDBValues($this->primaryKeyConstraint->columns));
		
		$statementIdentifier =
			$this->createColumnHash($updateValuesColumnNames).
			$this->createColumnHash($updateDefaultsColumnNames);
		if(!array_key_exists($statementIdentifier, $this->primaryKeyConstraint->updateStatements)) {
			$sql = 'UPDATE `'.$this->databaseName.'`.`'.$this->tableName.'` ';
			$placeholders = array();
			foreach($updateValuesColumnNames as $columnName)
				$placeholders[] = "`$columnName`=?";
			foreach($updateDefaultsColumnNames as $columnName)
				if($this->columns[$columnName]->defaultCurrentTimestamp)
					$placeholders[] = "`$columnName`=DEFAULT";
				else
					$placeholders[] = "`$columnName`=DEFAULT(`$columnName`)";
			$sql .= 'SET '.implode(',', $placeholders);
			$sql .= ' WHERE `'.implode('` = ? AND `', $this->primaryKeyConstraint->columns).'` = ?';
			$stmt = self::$mysqli->prepare($sql);
			if($stmt === false) throw MySQLErrorException::findClass(self::$mysqli);
			$this->primaryKeyConstraint->updateStatements[$statementIdentifier] = $stmt;
		}
		$updateStatement = $this->primaryKeyConstraint->updateStatements[$statementIdentifier];
		$this->bindAndExecute($updateStatement, $statementTypes, $statementValues);
		$numberOfRowsAffected = $updateStatement->affected_rows;
		if($numberOfRowsAffected < 1) {
			throw new E\NoAffectedRowException('The values you specified do not match any entry in the table or the update caused no changes.');
		} elseif($numberOfRowsAffected > 1) {
			throw new E\MultipleEntitiesException('The values you specified match two or more entries in the table.');
		}
		foreach($entity->fields as $field)
			$field->dbUpdated();
		$this->entityEngine->updateIdentifiersDB($entity);
	}
	
	public function delete(Entity $entity) {
		if(!$entity->inDB) {
			$entity->deleted = true;
			return;
		}
		if(!isset($this->primaryKeyConstraint->deleteStatement)) {
			$sql = 'DELETE FROM `'.$this->databaseName.'`.`'.$this->tableName.'` ';
			$sql .= 'WHERE `'.implode('` = ? AND `', $this->primaryKeyConstraint->columns).'` = ?';
			$stmt = self::$mysqli->prepare($sql);
			if($stmt === false) throw MySQLErrorException::findClass(self::$mysqli);
			$this->primaryKeyConstraint->deleteStatement = $stmt;
		}
		$this->bindAndExecute(
			$this->primaryKeyConstraint->deleteStatement,
			$this->primaryKeyConstraint->statementTypes,
			$entity->getDBValues($this->primaryKeyConstraint->columns));
		$numberOfRowsAffected = $this->primaryKeyConstraint->deleteStatement->affected_rows;
		if($numberOfRowsAffected < 1) {
			throw new E\NoAffectedRowException('The values you specified do not match any entry in the table.');
		} elseif($numberOfRowsAffected > 1) {
			throw new E\MultipleEntitiesException('The values you specified match two or more entries in the table.');
		}
		$entity->deleted = true;
		$entity->inDB = false;
	}
	
	public function exists(array $uniqueValues, Constraints\UniqueConstraint $constraint) {
		if(in_array(null, $uniqueValues, true))
			throw new E\InvalidUniqueValuesException('Unique values cannot be null.');
		$fromModel = $this->entityEngine->findModel($uniqueValues, $constraint);
		// TODO: When returning true her, we need to be sure that this change will be committed before any potential update.
		if($fromModel !== null && !$fromModel->deleted)
			return true;
		if($this->entityEngine->findDB($uniqueValues, $constraint) === null) {
			if(!isset($constraint->existenceStatement)) {
				$sql = 'SELECT NULL FROM `'.$this->databaseName.'`.`'.$this->tableName.'` ';
				$sql .= 'WHERE `'.implode('` = ? AND `', $constraint->columns).'` = ?';
				$stmt = self::$mysqli->prepare($sql);
				if($stmt === false) throw MySQLErrorException::findClass(self::$mysqli);
				$constraint->existenceStatement = $stmt;
			}
			$this->bindAndExecute($constraint->existenceStatement, $constraint->statementTypes, $uniqueValues);
			$constraint->existenceStatement->store_result();
			$numberOfRowsReturned = $constraint->existenceStatement->num_rows;
			$constraint->existenceStatement->free_result();
			if($numberOfRowsReturned == 1)
				return true;
			elseif($numberOfRowsReturned < 1)
				return false;
			else
				throw new E\MultipleEntitiesException('The values you specified match two or more entries in the table.');
		}
		return false;
	}
	
	public function syncWithDB(Entity $entity, Field $requiredField = null) {
		if(!$entity->deleted) {
			if($entity->inDB) {
				$this->update($entity);
			} else {
				$this->insert($entity);
				if($requiredField !== null && $requiredField->updateModel)
					$this->refresh($entity);
			}
		} else {
			$this->delete($entity);
		}
	}
	
	public function newEntity() {
		$entity = new Entity($this->columns);
		$entity->table = $this;
		return $entity;
	}
	
	public function updateIdentifiers(Entity $entity) {
		$this->entityEngine->updateIdentifiersModel($entity);
	}
	
	public function dereference(Entity $entity) {
		if($entity->referenceCount == 0) {
			try {
				$this->syncWithDB($entity);
			} catch(\Exception $e) { }
			$this->entityEngine->dereference($entity);
			if(isset($e))
				throw $e;
		}
	}
}