<?php
/**
 * Model is responsible for the logical computation in the ORM,
 * it is the front-end of this framework as well.
 * In order to take advantage of this framewrok, this class needs to be extended
 * with classes matching the names of database tables.
 * @author andsens
 * @package Model
 */
namespace Glucose;
use Exceptions\User as E;
abstract class Model {
	/**
	 * Associativ array of tables that have been initialized
	 * @var array
	 */
	private static $tables = array();
	
	/**
	 * CakePHPs Inflector class, slightly modified.
	 * Works quite well.
	 * @var Inflector
	 */
	private static $inflector;
	
	/**
	 * Table this model is associated with
	 * @var Table
	 */
	private $table;
	
	/**
	 * Allows multiple instances with the
	 * same PK, to point to the same fields
	 * @var Entity
	 */
	private $entity;
	
	private $className;
	
	/**
	 * Given a mysqli connection, the model connects to a database.
	 * @param mysqli $mysqli
	 */
	public static final function connect(\mysqli $mysqli) {
		if(!isset(self::$inflector))
			Model::$inflector = new Inflector();
		if(!isset(self::$tables))
			self::$tables = array();
		Table::connect($mysqli);
	}
	
	/**
	 * Constructs an object of the extending class
	 * If initialized with a full set of primary keys,
	 * it maps the object directly to an entry in
	 * the corresponding table.
	 * If no arguments a passed, the object will be treated as a
	 * new entry in the table.
	 *
	 * @param mixed $primaryKeys Set of primary key values depending on the tables definition, it varies in size.
	 */
	public final function __construct() {
		$this->className = get_class($this);
		$this->initializeModel();
		$this->table = self::$tables[$this->className];
		$arguments = func_get_args();
		if(count($arguments) > 0) {
			if(count($this->table->primaryKeyConstraint->columns) != count($arguments))
				throw new E\ConstructorArgumentException('Wrong argument count!');
			if(in_array(null, $arguments, true))
				throw new E\ConstructorArgumentException('Illegal argument [null]!');
			$this->entity = Entity::join($this->className, $arguments, $this->table->primaryKeyConstraint);
		} else {
			$this->entity = new Entity($this->className);
		}
	}
	
	/**
	 * Initializes the class and maps it to a table.
	 * The table schema depends on the currently selected database.
	 */
	private final function initializeModel() {
		if(!array_key_exists($this->className, self::$tables)) {
			$tableName = Model::$inflector->tableize($this->className);
			$table = new Table($tableName);
			self::$tables[$this->className] = $table;
			Entity::initialize($this->className, $table->columns, $table->uniqueConstraints);
		}
	}
	
	/**
	 * Forces an update of the object and either updates the table or inserts the object into it.
	 */
	private final function forceUpdate() {
		if($this->entity->shouldBeInDB)
			if(!$this->entity->delete)
				$this->updateDB();
			else
				$this->deleteModel();
		else
			if(!$this->entity->delete)
				$this->createModel();
	}
	
	/**
	 * Creates the model in the table and maps it to the new entry.
	 * @todo Check for duplicate key error
	 */
	private final function createModel() {
		$insertValues = array();
		$insertTypes = '';
		foreach($this->entity->fields as $field) {
			if(!isset($field->value))
				$insertValues[] = $field->column->insertDefault;
			else
				$insertValues[] = $field->value;
			$insertTypes .= $field->column->statementType;
		}
		try {
			$insertID = $this->table->insert($insertValues, $insertTypes);
			foreach($this->entity->fields as $field)
				if($field->updateModel && $field->column == $this->table->primaryKeyConstraint->autoIncrementColumn)
					$field->dbValue = $insertID;
				else
					$field->dbUpdated();
			$this->entity->updateIdentifiers();
			$this->entity->existsInDB = true;
		} catch(MySQLDuplicateEntryException $exception) {
			throw new E\DuplicateEntityException($exception->getMessage());
		}
	}
	
	/**
	 * Updates the database by saving fields,
	 * that have been changed since the last update.
	 * @todo Check for PK defined, with num affected rows
	 */
	private final function updateDB() {
		$updateValues = array();
		foreach($this->entity->fields as $field)
			if($field->updateDB)
				$updateValues[$field->column->name] = $field->value;
		if(count($updateValues) > 0) {
			try {
				$this->table->update($updateValues, $this->getDatabasePrimaryKeyValues());
				foreach($this->entity->fields as $field)
					$field->dbUpdated();
				$this->entity->updateIdentifiers();
				$this->entity->existsInDB = true;
			} catch(NoAffectedRowException $exception) {
				throw new E\UndefinedPrimaryKeyException(
					'The primary key you specified is not represented in the database or the row already had the same values as the new values.');
			}
		}
	}
	
	/**
	 * Updates the object from the database and overwrites fields with fresh values from the table,
	 * if they have not explicitly been set by the user.
	 * @param UniqueConstraint $constraint Defines which constraint should be used to select the entry from the table
	 */
	private final function updateModel() {
		foreach($this->entity->fields as $field) {
			if($field->updateModel) {
				$updateModel = true;
				break;
			}
		}
		if($updateModel) {
			try {
				$newValues = $this->table->select($this->getDatabasePrimaryKeyValues());
				foreach($newValues as $columnName => $value)
					if($this->entity->fields[$columnName]->updateModel)
						$this->entity->fields[$columnName]->dbValue = $value;
				$this->entity->updateIdentifiers();
				$this->entity->existsInDB = true;
			} catch(NonExistentEntityException $exception) {
				throw new E\UndefinedPrimaryKeyException(
					'The primary key you specified is not represented in the database.');
			}
		}
	}
	
	private final function deleteModel() {
		try {
			$this->table->delete($this->getDatabasePrimaryKeyValues());
			$this->entity->deleted = true;
		} catch(NonExistentEntityException $exception) {
			throw new E\UndefinedPrimaryKeyException(
				'The primary key you specified is not represented in the database.');
		}
	}
	
	private function getDatabasePrimaryKeyValues() {
		$primaryKeyValues = array();
		foreach($this->table->primaryKeyConstraint->columns as $column) {
			$primaryKeyValues[] = $this->entity->fields[$column->name]->dbValue;
		}
		return $primaryKeyValues;
	}
	
	public function delete() {
		$this->entity->delete = true;
	}
	
	/**
	 * Magic method for setting values that correspond to a field in the referenced table.
	 * @ignore
	 * @param string $name Name of the field
	 * @param mixed $value Value of the field
	 */
	public function __set($name, $value) {
		if($this->entity->delete)
			throw new E\EntityDeletedException('This entity has been deleted. You cannot modify its fields any longer.');
		$name = Model::$inflector->underscore($name);
		if(!isset($this->entity->fields[$name]))
			throw new E\UndefinedFieldException('The field you specified does not exists.');
		$this->entity->fields[$name]->modelValue = $value;
		$this->entity->updateIdentifiers();
	}
	
	/**
	 * Magic method for retrieving values of fields that correspond to a field in the referenced table.
	 * @ignore
	 * @param string $name Name of the field
	 * @return mixed Value of the field
	 */
	public function __get($name) {
		if($this->entity->delete)
			throw new E\EntityDeletedException('This entity has been deleted. You cannot read its fields any longer.');
		$name = Model::$inflector->underscore($name);
		if(!isset($this->entity->fields[$name]))
			throw new E\UndefinedFieldException('The field you specified does not exists.');
		if($this->entity->fields[$name]->updateModel) {
			if($this->entity->shouldBeInDB) {
				$this->updateModel();
			} else {
				$this->createModel();
				if($this->entity->fields[$name]->updateModel) {
					$this->updateModel();
				}
			}
		}
		return $this->entity->fields[$name]->value;
	}
	
	/**
	 * Returns true if a field value has been set.
	 * False if it is null or has not been set yet.
	 * @ignore
	 * @param string $name Name of the field
	 * @return bool Wether the field is set
	 */
	public final function __isset($name) {
		if($this->entity->delete)
			throw new E\EntityDeletedException('This entity has been deleted. You cannot read its fields any longer.');
		return isset($this->entity->fields[$name]->value);
	}
	
	/**
	 * Unsets a field
	 * @ignore
	 * @param string $name Name of the field
	 */
	public final function __unset($name) {
		if($this->entity->delete)
			throw new E\EntityDeletedException('This entity has been deleted. You cannot modify its fields any longer.');
		unset($this->entity->fields[$name]->value);
	}
	
	/**
	 * Saves the object into the table if there are no other instances referring to the same entity.
	 * @ignore
	 */
	public final function __destruct() {
		$this->entity->instanceCount--;
		if($this->entity->instanceCount == 0) {
			if($this->entity->updateOnDestruct)
				$this->forceUpdate();
			if(isset($this->entity->hash))
				unset(self::$entities[$this->className][$this->entity->hash]);
		}
			
	}
}
?>