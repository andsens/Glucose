<?php
/**
 * Model is responsible for the logical computation in the ORM,
 * it is the front-end of this framework as well.
 * In order to take advantage of this framewrok, this class needs to be extended
 * with classes matching the names of database tables.
 * @author andsens
 */
namespace Glucose {
use \Glucose\Exceptions\User as E;
use \Glucose\Exceptions\Table as TE;
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
	protected static $inflector;
	
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
	
	protected static $className = 'Model';
	
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
		if(static::$className != self::$className && static::$className != get_class($this))
			throw new E\UnexpectedValueException('There is a discrepancy between the actual class name (\''.get_class($this).'\') and the value of $className (\''.static::$className.'\').');
		$tableName = static::getTableName();
		if(!array_key_exists($tableName, self::$tables))
			self::$tables[$tableName] = new Table($tableName);
		$this->table = self::$tables[$tableName];
		
		$arguments = func_get_args();
		if(count($arguments) == 1 && is_array($arguments[0]))
			$arguments = $arguments[0];
		if(count($arguments) > 0) {
			if(count($this->table->primaryKeyConstraint->columns) != count($arguments))
				throw new E\ConstructorArgumentException('Wrong argument count.');
			if(in_array(null, $arguments, true))
				throw new E\ConstructorArgumentException('Illegal argument [null].');
			foreach($this->table->primaryKeyConstraint->columns as $index => $column)
				$column->testValueType($arguments[$index]);
			try {
				$this->entity = $this->table->select($arguments, $this->table->primaryKeyConstraint);
				if($this->entity->deleted)
					throw new E\EntityDeletedException('This entity has been deleted. You can no longer instantiate it.');
			} catch(TE\NonExistentEntityException $e) {
				throw new E\UndefinedPrimaryKeyException('The primary key you specified does not exist in the table.');
			}
		} else {
			$this->entity = $this->table->newEntity();
		}
		
		$this->entity->referenceCount++;
	}
	
	public static function __callStatic($name, $arguments) {
		if($name == 'getTableName')
			throw new E\MethodExpectedException('The method Glucose\Model::getTableName() cannot be called from a static context unless implemented in a subclass.');
		
		if(substr($name, 0, 6) == 'initBy') {
			try {
				$tableName = static::getTableName();
			} catch(E\MethodExpectedException $e) {
				if(static::$className == 'Model')
					throw new E\VariableExpectedException('In order to initialize entities by unique identifiers, you will have to add the static variable $className or implement the static method getTableName().');
				$tableName = self::$inflector->tableize(static::$className);
			}
			if(!array_key_exists($tableName, self::$tables))
				self::$tables[$tableName] = new Table($tableName);
			$table = self::$tables[$tableName];
			
			foreach($table->uniqueConstraints as $constraint) {
				if($constraint == $table->primaryKeyConstraint)
					continue;
				$camelized = array();
				foreach($constraint->columns as $column)
					$camelized[] = self::$inflector->camelize($column->name);
				if('initBy'.implode('And', $camelized) == $name) {
					if(count($constraint->columns) == count($arguments)) {
						foreach($constraint->columns as $index => $column)
							$column->testValueType($arguments[$index]);
						return new static($table->select($arguments, $constraint)->getValues($table->primaryKeyConstraint->columns));
					} else {
						$requiredNumberOfArguments = count($constraint->columns);
					}
				}
			}
			if(isset($requiredNumberOfArguments))
				throw new E\InitializationArgumentException('The method \''.$name.'\' was called with '.count($arguments).' arguments but requires '.$requiredNumberOfArguments.'.');
		}
		throw new E\UndefinedMethodException('Call to undefined method \''.$name.'\'.');
	}
	
	public function __call($name, $arguments) {
		if($name == 'getTableName')
			return self::$inflector->tableize(get_class($this));
		
		if($this->entity->deleted)
			throw new E\EntityDeletedException('This entity has been deleted. You can no longer modify its fields.');
		
		if(substr($name, 0, 3) == 'set') {
			foreach($this->table->uniqueConstraints as $constraint) {
				if(count($constraint->columns) < 2)
					continue;
				$camelized = array();
				foreach($constraint->columns as $column)
					$camelized[] = self::$inflector->camelize($column->name);
				if('set'.implode('And', $camelized) == $name) {
					if(count($constraint->columns) == count($arguments)) {
						if(!in_array(null, $arguments, true) && $this->table->exists($arguments, $constraint))
							throw new E\EntityCollisionException('Your changes collide with the unique values of an existing entity.');
						foreach($constraint->columns as $index => $column)
							$column->testValueType($arguments[$index]);
						foreach($constraint->columns as $index => $column)
							if($arguments[$index] !== $this->entity->fields[$column->name]->value)
								$this->entity->fields[$column->name]->modelValue = $arguments[$index];
						$this->table->updateIdentifiers($this->entity);
						return;
					} else {
						$requiredNumberOfArguments = count($constraint->columns);
					}
				}
			}
			if(isset($requiredNumberOfArguments))
				throw new E\InitializationArgumentException('The method \''.$name.'\' was called with '.count($arguments).' arguments but requires '.$requiredNumberOfArguments.'.');
			
		}
		throw new E\UndefinedMethodException('Call to undefined method \''.$name.'\'.');
	}
	
	public function delete() {
		$this->table->delete($this->entity);
	}
	
	/**
	 * Magic method for retrieving values of fields that correspond to a field in the referenced table.
	 * @ignore
	 * @param string $name Name of the field
	 * @return mixed Value of the field
	 */
	public function __get($name) {
		$name = Model::$inflector->underscore($name);
		$this->simulateRead($name);
		$field = $this->entity->fields[$name];
		if($field->updateModel)
			$this->table->syncWithDB($this->entity);
		if($field->updateModel)
			$this->table->refresh($this->entity);
		return $field->value;
	}
	
	/**
	 * Returns true if a field value has been set.
	 * False if it is null or has not been set yet.
	 * @ignore
	 * @param string $name Name of the field
	 * @return bool Wether the field is set
	 */
	public function __isset($name) {
		$name = Model::$inflector->underscore($name);
		$this->simulateRead($name);
		$field = $this->entity->fields[$name];
		if($this->entity->inDB && $field->updateModel)
			$this->table->syncWithDB($this->entity);
		return isset($field->value);
	}
	
	/**
	 * Magic method for setting values that correspond to a field in the referenced table.
	 * @ignore
	 * @param string $name Name of the field
	 * @param mixed $value Value of the field
	 */
	public function __set($name, $value) {
		$name = Model::$inflector->underscore($name);
		$this->simulateModify($name);
		$field = $this->entity->fields[$name];
		if($value === $field->value)
			return;
		$field->column->testValueType($value);
		foreach($this->table->uniqueConstraints as $constraint) {
			if(false !== $index = array_search($field->column, $constraint->columns)) {
				$values = $this->entity->getValues($constraint->columns);
				$values[$index] = $value;
				if(!in_array(null, $values, true) && $this->table->exists($values, $constraint))
					throw new E\EntityCollisionException('Your changes collide with the unique values of an existing entity.');
				$field->modelValue = $value;
				$this->table->updateIdentifiers($this->entity);
				return;
			}
		}
		$field->modelValue = $value;
	}
	
	/**
	 * Unsets a field
	 * @param string $name Name of the field
	 */
	public function __unset($name) {
		$name = Model::$inflector->underscore($name);
		$this->simulateModify($name);
		$field = $this->entity->fields[$name];
		$field->column->testValueUnset();
		unset($field->value);
		foreach($this->table->uniqueConstraints as $constraint) {
			if(in_array($field->column, $constraint->columns)) {
				$this->table->updateIdentifiers($this->entity);
				return;
			}
		}
	}
	
	private function simulateRead($name) {
		if($this->entity->deleted)
			throw new E\EntityDeletedException('This entity has been deleted. You can no longer read its fields.');
		if(!isset($this->entity->fields[$name]))
			throw new E\UndefinedPropertyException('The field \''.Model::$inflector->variable($name).'\' does not exists.');
	}
	
	private function simulateModify($name) {
		if($this->entity->deleted)
			throw new E\EntityDeletedException('This entity has been deleted. You can no longer modify its fields.');
		if(!isset($this->entity->fields[$name]))
			throw new E\UndefinedPropertyException('The field \''.Model::$inflector->variable($name).'\' does not exists.');
	}
	
	/**
	 * Saves the object into the table if there are no other instances referring to the same entity.
	 * @ignore
	 */
	public final function __destruct() {
		$this->entity->referenceCount--;
	}
}
}
?>