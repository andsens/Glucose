<?php
/**
 * Model is responsible for the logical computation in the ORM,
 * it is the front-end of this framework as well.
 * In order to take advantage of this framewrok, this class needs to be extended
 * with classes matching the names of database tables.
 * @author andsens
 * @package glucose
 */
namespace Glucose;
use \Glucose\Exceptions\User as E;
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
	
	/**
	 * Given a mysqli connection, the model connects to a database.
	 * @param mysqli $mysqli
	 */
	public static final function connect(\mysqli $mysqli) {
		if(!isset(self::$inflector))
			self::$inflector = new Inflector();
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
	public function __construct() {
		$this->initializeModel();
		$className = get_class($this);
		$this->table = self::$tables[$className];
		$arguments = func_get_args();
		if(count($arguments) > 0) {
			if(count($this->table->primaryKeyConstraint->columns) != count($arguments))
				throw new E\ConstructorArgumentException('Wrong argument count.');
			if(in_array(null, $arguments, true))
				throw new E\ConstructorArgumentException('Illegal argument [null].');
			$this->entity = $this->table->select($arguments, $this->table->primaryKeyConstraint);
		} else {
			$this->entity = $this->table->newEntity();
		}
	}
	
	/**
	 * Initializes the class and maps it to a table.
	 * The table schema depends on the currently selected database.
	 */
	private final function initializeModel() {
		$className = get_class($this);
		if(!array_key_exists($className, self::$tables)) {
			$table = new Table(Model::$inflector->tableize($className));
			self::$tables[$className] = $table;
		}
	}
	
	/**
	 * Creates the model in the table and maps it to the new entry.
	 * @todo Check for duplicate key error
	 */
	private final function createModel() {
		try {
			$this->table->insertIntoDB($this->entity);
		} catch(MySQLDuplicateEntryException $exception) {
			throw new E\DuplicateEntityException($exception->getMessage());
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
		$this->table->updateIdentifiers($this->entity);
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
		if($this->entity->fields[$name]->updateModel)
			$this->table->syncWithDB($this->entity, $this->entity->fields[$name]);
		return $this->entity->fields[$name]->value;
	}
	
	/**
	 * Returns true if a field value has been set.
	 * False if it is null or has not been set yet.
	 * @ignore
	 * @param string $name Name of the field
	 * @return bool Wether the field is set
	 */
	public function __isset($name) {
		if($this->entity->delete)
			throw new E\EntityDeletedException('This entity has been deleted. You cannot read its fields any longer.');
		return isset($this->entity->fields[$name]->value);
	}
	
	/**
	 * Unsets a field
	 * @ignore
	 * @param string $name Name of the field
	 */
	public function __unset($name) {
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