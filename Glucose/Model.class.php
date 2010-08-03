<?php
namespace Glucose;
use Glucose\Exceptions\User as E;
abstract class Model {
	
	private static $entityEngines;
	
	private $entityEngine;
	
	private $inflector;
	
	private $entity;
	
	protected static $className = 'Model';
	
	protected static $compoundConstraintMapping = array();
	
	public static final function connect(\mysqli $mysqli, array $classNameMapping = null) {
		// TODO: Has already been connected?
		Inflector::setClassNameMapping($classNameMapping);
		Table::connect($mysqli);
	}
	
	public function __construct() {
		if(static::$className != self::$className && static::$className != get_class($this))
			throw new E\UnexpectedValueException('There is a discrepancy between the actual class name (\''.get_class($this).'\') and the value of $className (\''.static::$className.'\').');
		if(!isset(self::$entityEngines))
			self::$entityEngines = array();
		if(array_key_exists($tableName, self::$entityEngines))
			self::$entityEngines[$tableName] = new EntityEngine($tableName);
		$this->entityEngine = self::$entityEngines[$tableName];
		$this->inflector = $this->entityEngine->inflector;
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
		if(substr($name, 0, 5) != 'initBy')
			throw new E\UndefinedMethodException("The method '$name' does not exist.");
		if(static::$className == self::$className)
			throw new E\VariableExpectedException('In order to initialize entities by unique identifiers, you will have to add the static variable $className.');
		$tableName = Inflector::getTableName(static::$className);
		$entityEngine = self::$entityEngines[$tableName];
		$constraint = $this->inflector->getConstraint(substr($name, 6));
		$entity = $this->entityEngine->getEntity($constraint, $arguments);
		return new static($entity->primaryKey);
	}
	
	
	
	public function __get($name) {
		$constraint = $this->getConstraint($name, static::$compoundConstraintMapping);
		if($constraint !== null)
			return $entity->getConstraintValues($constraint);
		else
			return $entity->getColumnValue($this->inflector->getColumn($name));
	}
	
	public function __isset($name) {
		$constraint = $this->getConstraint($name, static::$compoundConstraintMapping);
		if($constraint != null)
			return $entity->constraintValuesAreSet($constraint);
		else
			return $entity->columnValueIsSet($this->inflector->getColumn($name));
	}
	
	public function __set($name, $value) {
		$constraint = $this->getConstraint($name, static::$compoundConstraintMapping);
		if($constraint != null)
			return $entity->setConstraintValues($constraint, $value);
		else
			$entity->setColumnValue($this->inflector->getColumn($name), $value);
	}
	
	
	public function __unset($name) {
		$constraint = $this->getConstraint($name, static::$compoundConstraintMapping);
		if($constraint != null)
			$entity->unsetConstraintValues($constraint);
		else
			$entity->unsetColumnValue($this->inflector->getColumn($name));
	}
	
	public function __sleep() {
		
	}
	
	public function __wakeup() {
		
	}
	
	public function __set_state(array $state) {
		
	}
	
	
	
	public function delete() {
		$this->entity->delete();
	}
	
	public final function __destruct() {
		$this->entity->referenceCount--;
	}
}