<?php
namespace Glucose;
use Glucose\Exceptions\User as E;
abstract class Model {
	
	private $inflector;
	
	private $entity;
	
	protected static $compoundConstraintMapping = array();
	
	public static final function connect(\mysqli $mysqli, array $classNameMapping = null) {
		// TODO: Has already been connected?
		Inflector::setClassNameMapping($classNameMapping);
		Table::connect($mysqli);
	}
	
	public function __construct() {
		$entityEngine = self::getEntityEngine(get_class($this));
		$this->inflector = $entityEngine->inflector;
		
		$arguments = func_get_args();
		// Allow subclasses to have a constructor by allowing them to be able to call parent::__construct(func_get_args())
		if(count($arguments) == 1 && is_array($arguments[0]))
			$arguments = $arguments[0];
		if(count($arguments) > 0)
			$this->entity = $entityEngine->getEntityByPrimaryKey($arguments);
		else
			$this->entity = $entityEngine->newEntity();
		$this->entity->referenceCount++;
	}
	
	public static function __callStatic($name, $arguments) {
		if(substr($name, 0, 5) != 'initBy')
			throw new E\UndefinedMethodException("The method '$name' does not exist.");
		$entityEngine = self::getEntityEngine(get_called_class());
		$constraint = $entityEngine->inflector->getConstraint(substr($name, 6));
		$entity = $entityEngine->getEntity($constraint, $arguments);
		return new static($entity->primaryKey);
	}
	
	private static $entityEngines;
	private static function getEntityEngine($className) {
		if(!isset(self::$entityEngines))
			self::$entityEngines = array();
		$tableName = Inflector::getTableName($className);
		if(!array_key_exists($tableName, self::$entityEngines))
			self::$entityEngines[$tableName] = new EntityEngine($tableName, static::$compoundConstraintMapping);
		return self::$entityEngines[$tableName];
	}
	
	public function __get($name) {
		$constraint = $this->inflector->getConstraint($name);
		if($constraint == null)
			return $this->entity->getColumnValue($this->inflector->getColumn($name));
		else
			return $this->entity->getConstraintValues($constraint);
	}
	
	public function __isset($name) {
		$constraint = $this->inflector->getConstraint($name);
		if($constraint == null)
			return $this->entity->columnValueIsSet($this->inflector->getColumn($name));
		else
			return $this->entity->constraintValuesAreSet($constraint);
	}
	
	public function __set($name, $value) {
		$constraint = $this->inflector->getConstraint($name);
		if($constraint == null)
			$this->entity->setColumnValue($this->inflector->getColumn($name), $value);
		else
			$this->entity->setConstraintValues($constraint, $value);
	}
	
	
	public function __unset($name) {
		$constraint = $this->inflector->getConstraint($name);
		if($constraint == null)
			$entity->unsetColumnValue($this->inflector->getColumn($name));
		else
			$entity->unsetConstraintValues($constraint);
	}
	
	public function __sleep() {
		
	}
	
	public function __wakeup() {
		
	}
	
	public function __set_state(array $state) {
		
	}
	
	public function __clone() {
		
	}
	
	public function create() {
		$this->entity->create();
	}
	
	public function update() {
		$this->entity->update();
	}
	
	public function delete() {
		$this->entity->delete();
	}
	
	public final function __destruct() {
		$this->entity->referenceCount--;
	}
}