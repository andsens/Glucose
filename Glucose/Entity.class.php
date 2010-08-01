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
	
	private $compoundForeignKeyFields;
	
	public function __construct(array $columns, array $foreignKeyConstraints, EntityEngine $entityEngine) {
		$this->entityEngine = $entityEngine;
		$this->compoundForeignKeys = array();
		$foreignKeyColumns = array();
		foreach($foreignKeyConstraints as $foreignKeyConstraint)
			if(count($foreignKeyConstraint->columns) == 1)
				$foreignKeyColumns = array_merge($foreignKeysColumns, $foreignKeyConstraint->columns);
		
		$fieldsArray = array();
		foreach($columns as $column)
			if(in_array($column, $foreignKeysColumns))
				$fieldsArray[] = new ForeignKeyField($column);
			else
				$fieldsArray[] = new Field($column);
		
		foreach($foreignKeyConstraints as $foreignKeyConstraint) {
			if(count($foreignKeyConstraint->columns) > 1) {
				$foreignKeyFields = array();
				foreach($foreignKeyConstraint->columns as $index => $column)
					$foreignKeyFields[] = $fieldsArray[$index];
				$this->compoundForeignKeyFields[$constraint->name] = new CompoundForeignKeyField($foreignKeyFields);
			}
		}
		
		$this->fields = new ImmutableFixedArray($fieldsArray);
		$this->deleted = false;
		$this->referenceCount = 0;
	}
	
	public function atomicChange(Constraint $constraint, array $values) {
		$this->canAccess();
		$columnsToChange = array();
		$i = 0;
		foreach($constraint->columns as $index => $column) {
			$values[$i] = $column->autobox($values[$i]);
			if($this->fields[$column->position]->value != $values[$i])
				$columnsToChange[] = $column;
			$i++;
		}
		if(count($columnsToChange) == 0)
			return;
		
		$tentativeValues = $this->values;
		foreach($constraint->columns as $index => $column)
			$tentativeValues[$index] = $values[$column->position];
		if($this->entityEngine->isColliding($tentativeValues))
			throw new E\EntityCollisionException('Your changes collide with the unique values of an existing entity.');
		
		foreach($columnsToChange as $column)
			$this->fields[$column->position]->modelValue = $values[$i];
		$this->entityEngine->valuesChanged($this, $changedColumns);
	}
	
	public function getValue(Column $column) {
		$this->canAccess();
		$field = $this->fields[$column->position];
		if($field->updateModel)
			$this->entityEngine->refresh($this);
		if($field instanceof ForeignKeyField)
			return $field->object;
		return $field->value;
	}
	
	public function valueIsSet(Column $column) {
		$this->canAccess();
		$field = $this->fields[$column->position];
		if($field->updateModel)
			$this->entityEngine->refresh($this);
		return isset($field->value);
	}
	
	public function setValue(Column $column, $value) {
		$this->canAccess();
		$field = $this->fields[$column->position];
		if($field instanceof ForeignKeyField) {
			$object = $value;
			$value = $field->getReferencedValue($object);
		}
		$value = $field->column->autobox($value);
		if($field->value == $value && (!$field instanceof ForeignKeyField))
			return;
		$tentativeValues = $this->values;
		$tentativeValues[$column->position] = $value;
		if($this->entityEngine->isColliding($tentativeValues))
			throw new E\EntityCollisionException('Your changes collide with the unique values of an existing entity.');
		
		if($field instanceof ForeignKeyField)
			$field->modelObject = $object;
		else
			$field->modelValue = $value;
		$this->entityEngine->valuesChanged($this, array($column));
	}
	
	public function unsetValue(Column $column) {
		$this->canAccess();
		$field = $this->fields[$column->position];
		// TODO: What if a default value causes a collision?
		unset($field->value);
		$this->entityEngine->valuesChanged($this, array($column));
	}
	
	public function getCompoundFKObject(ForeignKeyConstraint $constraint) {
		$this->canAccess();
		$field = $this->compoundForeignKeyFields[$constraint->name];
		if($field->updateModel)
			$this->entityEngine->refresh($this);
		return $field->object;
	}
	
	public function compoundFKObjectIsSet(ForeignKeyConstraint $constraint) {
		$this->canAccess();
		$field = $this->compoundForeignKeyFields[$constraint->name];
		if($field->updateModel)
			$this->entityEngine->refresh($this);
		return isset($field->value);
		
	}
	
	public function setCompoundFKObject(ForeignKeyConstraint $constraint, $object) {
		$this->canAccess();
		$field = $this->compoundForeignKeyFields[$constraint->name];
		$values = $field->getReferencedValues($object);
		$tentativeValues = $this->values;
		foreach($constraint as $index => $column) {
			$values[$index] = $column->autobox($value);
			$tentativeValues[$column->position] = $value;
		}
		if($this->entityEngine->isColliding($tentativeValues))
			throw new E\EntityCollisionException('Your changes collide with the unique values of an existing entity.');
		
		$field->modelObject = $object;
		$this->entityEngine->valuesChanged($this, $constraint->columns);
		
	}
	
	public function unsetCompoundFKObject(ForeignKeyConstraint $constraint) {
		$this->canAccess();
		$field = $this->compoundForeignKeyFields[$constraint->name];
		// TODO: What if a default value causes a collision?
		unset($field->value);
		$this->entityEngine->valuesChanged($this, $constraint->columns);
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
			case 'values':
				return array_map(function(Field $field) {return $field->value;}, $this->fields);
			case 'values':
				return null;
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