<?php
namespace Glucose;
use Glucose\Exceptions\User as E;
abstract class Model {
	
	private static $entityEngines;
	
	private $entityEngine;
	
	private $inflector;
	
	private $entity;
	
	protected static $className = 'Model';
	
	protected static $compoundForeignKeysMapping = array();
	
	public static final function connect(\mysqli $mysqli) {
		self::$entityEngines = array();
		Table::connect($mysqli);
	}
	
	public function __construct() {
		if(static::$className != self::$className && static::$className != get_class($this))
			throw new E\UnexpectedValueException('There is a discrepancy between the actual class name (\''.get_class($this).'\') and the value of $className (\''.static::$className.'\').');
		try {
			$tableName = static::getTableName();
		} catch(E\MethodExpectedException $e) {
			$tableName = Inflector::tableize(get_class($this));
		}
		if(array_key_exists($tableName, self::$entityEngines))
			self::$entityEngines[$tableName] = new EntityEngine($tableName);
		$this->entityEngine = self::$entityEngines[$tableName];
		$this->inflector = $this->entityEntine->inflector;
		$arguments = func_get_args();
		// Allow subclasses to have a constructor by allowing them to be able to call parent::__construct(func_get_args())
		if(count($arguments) == 1 && is_array($arguments[0]))
			$arguments = $arguments[0];
		if(count($arguments) > 0)
			$this->entity = $this->entityEngine->getEntityByPrimaryKey($arguments);
		else
			$this->entity = $this->entityEngine->newEntity();
		$this->entity->referenceCount++;
	}
	
	public static function __callStatic($name, $arguments) {
		if($name == 'getTableName')
			throw new E\MethodExpectedException('The method Glucose\Model::getTableName() cannot be called from a static context unless implemented in a subclass.');
		if(substr($name, 0, 5) != 'initBy')
			throw new E\UndefinedMethodException("The method '$name' does not exist.");
		try {
			$tableName = static::getTableName();
		} catch(E\MethodExpectedException $e) {
			if(static::$className == self::$className)
				throw new E\VariableExpectedException('In order to initialize entities by unique identifiers, you will have to add the static variable $className.');
			$tableName = Inflector::tableize(static::$className);
		}
		$entityEngine = self::$entityEngines[$tableName];
		$constraint = $this->inflector->getConstraint(substr($name, 6));
		$entity = $this->entityEngine->getEntity($constraint, $arguments);
		return new static($entity->primaryKey);
	}
	
	public function __call($name, $arguments) {
		if(substr($name, 0, 3) != 'set')
			throw new E\UndefinedMethodException("The method '$name' does not exist.");
		$constraint = $this->inflector->getConstraint(substr($name, 4));
		$this->entity->atomicChange($constraint, $arguments);
	}
	
	public function __get($name) {
		$foreignKeyConstraint = $this->getCompoundFKConstraint($name);
		if($foreignKeyConstraint != null)
			return $entity->getCompoundFKObject($foreignKeyConstraint);
		else
			return $entity->getValue($this->inflector->getColumn($name));
	}
	
	public function __isset($name) {
		$foreignKeyConstraint = $this->getCompoundFKConstraint($name);
		if($foreignKeyConstraint != null)
			return $entity->compoundFKObjectIsSet($foreignKeyConstraint);
		else
			return $entity->valueIsSet($this->inflector->getColumn($name));
	}
	
	public function __set($name, $value) {
		$foreignKeyConstraint = $this->getCompoundFKConstraint($name);
		if($foreignKeyConstraint != null)
			$entity->setCompoundFKObject($foreignKeyConstraint, $value);
		else
			$entity->setValue($this->inflector->getColumn($name), $value);
	}
	
	
	public function __unset($name) {
		$foreignKeyConstraint = $this->getCompoundFKConstraint($name);
		if($foreignKeyConstraint != null)
			$entity->unsetCompoundFKObject($foreignKeyConstraint);
		else
			$entity->unsetValue($this->inflector->getColumn($name));
	}
	
	private function getCompoundFKConstraint($name) {
		if(false !== $fieldNames = array_key_search($name, static::$compoundForeignKeysMapping))
			return $this->inflector->getCompoundFKConstraintByFieldNames($fieldNames);
		$foreignKeyConstraint = $this->inflector->getCompoundFKConstraintByFieldName($name);
		if($foreignKeyConstraint != null) {
			try {
				$this->inflector->getColumn($name);
				throw new E\NamingCollisionException("The foreign key '$foreignKeyConstraint->name' and the column `$name` map to the same field.");
			} catch(E\UndefinedFieldException $e) {}
			return $foreignKeyConstraint;
		}
		return null;
	}
	
	public function delete() {
		$this->entity->delete();
	}
	
	public final function __destruct() {
		$this->entity->referenceCount--;
	}
}