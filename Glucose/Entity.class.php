<?php
namespace Glucose;
use Glucose\Constraints\ForeignKeyConstraint;

use \Glucose\Exceptions\User as E;
class Entity {
	
	private $identifier;
	
	public $deleted;
	
	private $referenceCount;
	
	private $entityEngine;
	
	private $fields;
	
	private $compoundConstraintFields;
	
	public function __construct(array $columns, array $constraints, EntityEngine $entityEngine) {
		$this->entityEngine = $entityEngine;
		
		$constraintFields = array();
		$fields = array();
		foreach($columns as $index => $column) {
			foreach($constraints as $constraint) {
				if(!in_array($column, $constrain->columns))
					continue;
				if(count($constraint->columns) > 1) {
					if(!array_key_exists($column->name, $constraintFields))
						$constraintFields[$constraint->name] = array();
					$constraintFields[$constraint->name][] = $index;
				} elseif($constraint instanceof Constraints\ForeignKeyConstraint) {
					$className = Inflector::getClassName($constraint->referencedTableName);
					if(class_exists($className, true) && in_array('Model', class_parents($className)))
						$fields[$index] = new Fields\ForeignKeyField($column, $this->entityEngine, $constraint);
				}
			}
			if(!array_key_exists($index, $fields))
				$fields[$index] = new Fields\SimpleField($column);
		}
		
		$this->compoundConstraintFields = array();
		foreach($constraints as $constraint)
			if(count($constraint->columns) > 1)
				if($constraint instanceof ForeignKeyConstraint)
					$this->compoundConstraintFields[$constraint->name] = new CompoundForeignKeyField($constraintFields[$constraint->name]);
				else
					$this->compoundConstraintFields[$constraint->name] = new CompoundUniqueKeyField($constraintFields[$constraint->name]);
		
		$this->fields = new ImmutableFixedArray($fields);
		$this->deleted = false;
		$this->referenceCount = 0;
	}
	
	
	public function getColumnValue(Column $column) {
		$field = $this->fields[$column->position];
		return $this->getValue($field);
	}
	
	public function getConstraintValues(Constraints\Constraint $constraint) {
		$field = $this->compoundConstraintFields[$constraint->name];
		return $this->getValue($field);
	}
	
	private function getValue(Fields\Field $field) {
		$this->canAccess();
		if($field->updateModel)
			$this->entityEngine->refresh($this);
		return $field->currentValue;
	}
	
	
	
	public function columnValueIsSet(Column $column) {
		$field = $this->fields[$column->position];
		return $this->valueIsSet($field);
	}
	
	public function constraintValuesAreSet(Constraints\Constraint $constraint) {
		$field = $this->compoundConstraintFields[$constraint->name];
		return $this->valueIsSet($field);
	}
	
	private function valueIsSet(Fields\Field $field) {
		$this->canAccess();
		if($field->updateModel)
			$this->entityEngine->refresh($this);
		return isset($field->currentValue);
	}
	
	
	
	public function setColumnValue(Column $column, $value) {
		$field = $this->fields[$column->position];
		$this->setValue($field, $value);
		$this->entityEngine->columnValueChanged($this, $column);
	}
	
	public function setConstraintValues(Constraints\Constraint $constraint, $value) {
		$field = $this->compoundConstraintFields[$constraint->name];
		$this->setValue($field, $value);
		$this->entityEngine->constraintValuesChanged($this, $constraint);
	}
	
	public function setValue(Fields\Field $field, $value) {
		$this->canAccess();
		if($field->equalsCurrentValue($value))
			return;
		$tentativeValues = $field->getTentativeValues($this->simpleCurrentValues, $value);
		if($this->entityEngine->isColliding($tentativeValues))
			throw new E\EntityCollisionException('Your changes collide with the unique values of an existing entity.');
		$field->currentValue = $value;
	}
	
	
	
	public function unsetColumnValue(Column $column) {
		$field = $this->fields[$column->position];
		$this->unsetValue($field);
		$this->entityEngine->columnValueChanged($this, $column);
	}
	
	public function unsetConstraintValues(Constraints\Constraint $constraint) {
		$field = $this->compoundConstraintFields[$constraint->name];
		$this->unsetValue($field);
		$this->entityEngine->constraintValuesChanged($this, $constraint);
	}
	
	private function unsetValue(Fields\Field $field) {
		$this->canAccess();
		// TODO: What if a default value causes a collision?
		unset($field->currentValue);
	}
	
	private function canAccess() {
		if($this->entity->deleted)
			throw new E\EntityDeletedException('This entity has been deleted. You can no longer access its fields.');
	}
	
	
	
	public function delete() {
		$this->deleted = true;
	}
	
	public function __get($name) {
		switch($name) {
			case 'primaryKey':
				return null;
			case 'simpleCurrentValues':
				return array_map(function(Field $field) {return $field->currentValue;}, $this->fields);
			case 'simpleDBValues':
				return array_map(function(Field $field) {return $field->dbValue;}, $this->fields);
			case 'referenceCount':
				return $this->referenceCount;
		}
	}
	
	public function __set($name, $value) {
		switch($name) {
			case 'identifier':
				$this->identifier = $value;
				break;
			case 'referenceCount':
				$this->referenceCount = $value;
				if($this->referenceCount == 0)
					$this->entityEngine->dereference($this);
				break;
		}
	}
}