<?php
namespace Glucose\Fields;
class ForeignKeyField extends Field {
	
	private $entityEngine;
	
	private $className;
	
	private $fieldName;
	
	private $constraint;
	
	private $object;
	
	public function __construct(\Glucose\Column $column, \Glucose\EntityEngine $entityEngine, \Glucose\Constraints\ForeignKeyConstraint $constraint) {
		$this->entityEngine = $entityEngine;
		$this->className = Inflector::getClassName($constraint->referencedTableName);
		$this->fieldName = Inflector::getFieldName($className, $constraint->referencedColumnName);
		$this->constraint = $constraint;
		parent::__construct($column);
	}
	
	public function __get($name) {
		switch($name) {
			case 'currentValue':
				return $this->object;
			default:
				return parent::__get($name);
		}
	}
	
	public function __set($name, $value) {
		switch($name) {
			case 'currentValue':
				 $simpleValue = $this->object->{$this->fieldName};
				parent::__set($name, $simpleValue);
				// TODO: Check type
				$this->object = $value;
				 // Should be before and after change if failsafe rollback is to be supported
				$this->entityEngine->subscribe($this, $simpleValue, $constraint);
				break;
			case 'dbValue':
				parent::__set($name, $value);
				// TODO: Check type
				$this->object = new $this->className($value);
				 // Should be before and after change if failsafe rollback is to be supported
				$this->entityEngine->subscribe($this, $value, $constraint);
				break;
			default:
				parent::__set($name, $value);
		}
	}
	
	public function __unset($name) {
		parent::__unset($name);
		if($name == 'currentValue') {
			// TODO: Check for default value
			$this->object = null;
		}
	}
}