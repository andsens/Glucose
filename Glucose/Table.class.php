<?php
namespace Glucose;
use MySQLi_Classes\Exceptions\Assertion\TooManyAffectedRowsException;

use MySQLi_Classes\Exceptions\Assertion\TooFewAffectedRowsException;

use MySQLi_Classes\Exceptions\Assertion\TooFewResultingRowsException;

use MySQLi_Classes\Statements\Statement;
use MySQLi_Classes\Statements\UpdateStatement;
use MySQLi_Classes\Statements\DeleteStatement;
use MySQLi_Classes\Statements\SelectStatement;
use MySQLi_Classes\Statements\InsertStatement;
use \Glucose\Exceptions\Table as E;
class Table {
	
	const REQUIRED_MYSQL_VERSION = 50136;
	
	private $databaseName;
	
	private $tableName;
	
	private $columns;
	
	private $primaryKeyConstraint;
	
	private $uniqueConstraints;
	
	private $foreignKeyConstraints;
	
	/**
	 *
	 * Enter description here ...
	 * @var Statements\SelectStatement
	 */
	private static $tableInformationQuery;
	
	private $insertStatements = array();
	
	public static function connect(\mysqli $mysqli) {
		if($mysqli->server_version < self::REQUIRED_MYSQL_VERSION)
			throw new ConnectionException('Glucose only works with MySQL version '.self::REQUIRED_MYSQL_VERSION.
			" or higher, the server you are trying to connect to is version $mysqli->server_version.");
		Statement::connect($mysqli);
		self::prepareTableInformationRetrievalStatement();
	}
	
	
	public function __construct($databaseName, $tableName) {
		if(Statements\Connector::isConnected())
			throw new MySQLConnectionException('Glucose is not connected to any server.');
		
		$this->databaseName = $databaseName;
		$this->tableName = $tableName;
		$this->retrieveTableInformation();
	}

	
	private static function prepareTableInformationRetrievalStatement() {
		$query = <<<End
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
		self::$tableInformationQuery = new SelectStatement($query, 'ss');
	}

	
	private function retrieveTableInformation() {
		$statement = self::$tableInformationQuery;
		$this->columns = array();
		$this->uniqueConstraints = array();
		$this->foreignKeyConstraints = array();
		
		$statement->bindAndExecute(array($this->databaseName, $this->tableName));
		if($statement->rows == 0)
			throw new E\MissingTableException("The table $this->name does not exist.");
		
		$statement->stmt->bind_result($name, $position, $defaultValue, $isNullable, $type, $maxLength, $extra,
		$constraintType, $constraintName, $referencedTableName, $referencedColumnName, $updateRule, $deleteRule,
		$refererConstraintName, $refererTableName, $refererColumnName);
		
		while($statement->fetch()) {
			if(!isset($this->columns[$position-1]))
				$this->columns[$position-1] = new Column($name, $position, $type, $maxLength, $isNullable == 'NO', $defaultValue, $extra);
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
		$statement->free_result();
		
		if(!isset($this->primaryKeyConstraint))
			throw new E\MissingPrimaryKeyConstraintException("The table $this->name does not have any primary key constraints.");
	}
	
	
	public function __get($name) {
		switch($name) {
			case 'name':
				return "`$this->databaseName`.`$this->tableName`";
			case 'columns':
				return $this->columns;
			case 'primaryKeyConstraint':
				return $this->primaryKeyConstraint;
			case 'uniqueConstraints':
				return $this->uniqueConstraints;
		}
	}
	
	private function insert(array $insertValues) {
		$insertValuesColumnNames = array_keys($insertValues);
		
		$statementIdentifier = sha1(array_reduce($insertValuesColumnNames, function($previous, $value) {return $previous.sha1($value);}, ''));
		if(!array_key_exists($statementIdentifier, $this->insertStatements)) {
			$placeholders = array();
			$statementTypes = "";
			foreach($this->columns as $column) {
				if(in_array($column->name, $insertValuesColumnNames)) {
					$placeholders[] = '?';
					$statementTypes .= $column->statementType;
				} elseif($column->defaultCurrentTimestamp) {
					$placeholders[] = 'DEFAULT';
				} else {
					$placeholders[] = "DEFAULT(`$column->name`)";
				}
			}
			$query  = 'INSERT INTO '.$this->name;
			$query .= ' (`'.implode('`, `', $this->columns).'`) ';
			$query .= 'VALUES ('.implode(',', $placeholders).')';
			$this->insertStatements[$statementIdentifier] = new InsertStatement($query, $statementTypes);
		}
		$statement = $this->insertStatements[$statementIdentifier];
		$statement->bindAndExecute(array_values($insertValues));
		return $statement->insertID;
	}
	
	
	public function select(array $uniqueValues, Constraints\UniqueConstraint $constraint) {
		if(!in_array($constraint, $this->uniqueConstraints, true))
			throw new E\InvalidUniqueConstraintException("The unique constraint '$constraint->name' does not match any constraint in the table $this->name.");
		if(false !== $index = array_search(null, $uniqueValues, true))
			throw new E\InvalidUniqueValuesException($this->name.'.`'.$constraint->columns[$index]->name.'` cannot contain null values when selecting a single row.');
		if(count($uniqueValues) != count($constraint->columns))
			throw new E\InvalidUniqueValuesException("The number of unique values (".count($uniqueValues).") must match the number of columns in the constraint '$constraint->name' (".count($constraint->columns).")");
		
		if(!isset($constraint->selectStatement)) {
			$query  = 'SELECT `'.implode('`, `', array_diff($this->columns, $constraint->columns)).'` ';
			$query .= 'FROM '.$this->name;
			$query .= ' WHERE `'.implode('` = ? AND `', $constraint->columns).'` = ?';
			$constraint->selectStatement = new SelectStatement($query, $constraint->statementTypes);
			$constraint->selectStatement->assertResultingRows = 1;
		}
		$statement = $constraint->selectStatement;
		try {
			$statement->bindAndExecute($uniqueValues);
			$fields = array();
			$values = array();
			foreach($this->columns as $index => $column)
				if(false !== $argumentPosition = array_search($column, $constraint, true))
					$values[$index] = $uniqueValues[$argumentPosition];
				else
					$fields[] = &$values[$index];
			$statement->stmt->bind_result($fields);
			$statement->fetch();
			$statement->freeResult();
			return $values;
		} catch(TooFewResultingRowsException $e) {
			$statement->freeResult();
			throw new E\NoSuchRowException(
				"The values you specified for the constraint '$constraint->name' do not match any row in the table $this->name.", null, $e);
		} catch(TooManyResultingRowsException $e) {
			$statement->freeResult();
			throw new E\MultipleRowsReturnedException(
				"The values you specified for the constraint '$constraint->name' match two or more rows in the table $this->name.", null, $e);
		}
	}
	
	private function update(array $primaryKeyValues, array $updateValues, array $updateDefaultsColumnNames) {
		$updateValuesColumnNames = array_keys($updateValues);
		
		$statementIdentifier = sha1(
				array_reduce($updateValuesColumnNames, function($previous, $value) {return $previous.sha1($value);}, '').
				array_reduce($updateDefaultsColumnNames, function($previous, $value) {return $previous.sha1($value);}, ''));
		if(!array_key_exists($statementIdentifier, $this->primaryKeyConstraint->updateStatements)) {
			$parameters = array();
			foreach($updateValuesColumnNames as $columnName)
				$parameters[] = "`$columnName`=?";
			foreach($updateDefaultsColumnNames as $columnName)
				if($this->columns[$columnName]->defaultCurrentTimestamp)
					$parameters[] = "`$columnName`=DEFAULT";
				else
					$parameters[] = "`$columnName`=DEFAULT(`$columnName`)";
			$query  = 'UPDATE '.$this->name;
			$query .= ' SET '.implode(',', $parameters);
			$query .= ' WHERE `'.implode('` = ? AND `', $this->primaryKeyConstraint->columns).'` = ?';
			
			$statementTypes = "";
			foreach($updateValuesColumnNames as $columnName)
				$statementTypes .= $this->columns[$columnName]->statementType;
			$statementTypes .= $this->primaryKeyConstraint->statementTypes;
			
			$this->primaryKeyConstraint->updateStatements[$statementIdentifier] = new UpdateStatement($query, $statementTypes);
			$this->primaryKeyConstraint->updateStatements[$statementIdentifier]->assertAffectedRows = 1;
		}
		$statement = $this->primaryKeyConstraint->updateStatements[$statementIdentifier];
		try {
			$statement->bindAndExecute(array_merge(array_values($updateValues), $primaryKeyValues));
		} catch(TooFewAffectedRowsException $e) {
			throw new E\NoAffectedRowException(
				"The values you specified for the primary key do not match any row in the table $this->name.", null, $e);
		} catch(TooManyAffectedRowsException $e) {
			throw new E\MultipleRowsAffectedException(
				"The values you specified for the primary key match two or more rows in the table $this->name.", null, $e);
		}
	}
	
	public function delete(array $primaryKeyValues) {
		if(!isset($this->primaryKeyConstraint->deleteStatement)) {
			$query = 'DELETE FROM '.$this->name.' ';
			$query .= 'WHERE `'.implode('` = ? AND `', $this->primaryKeyConstraint->columns).'` = ?';
			$this->primaryKeyConstraint->deleteStatement = new DeleteStatement($query, $this->primaryKeyConstraint->statementTypes);
			$this->primaryKeyConstraint->deleteStatement->assertAffectedRows = 1;
		}
		$statement = $this->primaryKeyConstraint->deleteStatement;
		try {
			$statement->bindAndExecute($primaryKeyValues);
		} catch(TooFewAffectedRowsException $e) {
			throw new E\NoAffectedRowException(
				"The values you specified for the primary key do not match any row in the table $this->name.", null, $e);
		} catch(TooManyAffectedRowsException $e) {
			throw new E\MultipleRowsAffectedException(
				"The values you specified for the primary key match two or more rows in the table $this->name.", null, $e);
		}
	}
	
	public function exists(array $uniqueValues, Constraints\UniqueConstraint $constraint) {
		if(false !== $index = array_search(null, $uniqueValues, true))
			throw new E\InvalidUniqueValuesException($this->name.'.`'.$constraint->columns[$index]->name.'` cannot contain null values when selecting a single row.');
		if(!isset($constraint->existenceStatement)) {
			$query = 'SELECT NULL FROM '.$this->name.' ';
			$query .= 'WHERE `'.implode('` = ? AND `', $constraint->columns).'` = ?';
			$constraint->existenceStatement = new SelectStatement($query, $constraint->statementTypes);
		}
		$statement = $constraint->existenceStatement;
		$statement->bindAndExecute($uniqueValues);
		$numberOfRowsReturned = $statement->rows;
		$statement->freeResult();
		if($numberOfRowsReturned == 1)
			return true;
		elseif($numberOfRowsReturned < 1)
			return false;
		else
			throw new E\MultipleRowsReturnedException("The values you specified for the constraint '$constraint->name' match two or more rows in the table $this->name.");
		return false;
	}
}