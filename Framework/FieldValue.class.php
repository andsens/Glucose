<?php
/**
 * 
 * @author Anders
 * @property-read Field $field
 * @property-read mixed $value
 * @property-write mixed $dbValue
 * @property-write mixed $modelValue
 * @property-read bool $updateDB
 * @property-read bool $updateModel
 *
 */
class FieldValue {
	private $field;
	private $value;
	private $updateDB;
	private $updateModel;
	
	public function __construct($field) {
		$this->field = $field;
		$this->updateDB = false;
		$this->updateModel = true;
	}
	
	public function __unset($name) {
		unset($this->value);
		$this->updateDB = true;
		$this->updateModel = false;
	}
	
	public function __isset($name) {
		return isset($this->value);
	}
	
	public function __get($name) {
		switch($name) {
			case 'field':
				return $this->field;
			case 'value':
				return $this->value;
			case 'updateDB':
				return $this->updateDB;
			case 'updateModel':
				return $this->updateModel;
			case 'isAutoIncrement':
				return $this->field->isAutoIncrement;
		}
	}
	
	public function dbUpdated() {
		$this->updateDB = false;
		$defaultValueTypesOnNull = array('date', 'datetime', 'time', 'timestamp');
		if(!isset($this->value))
			if(in_array($this->field->type, $defaultValueTypesOnNull))
				$this->updateModel = true;
			else
				$this->dbValue = $this->field->default;
	}
	
	public function __set($name, $value) {
		switch($name) {
			case 'dbValue':
				$this->updateDB = false;
				$this->updateModel = false;
				$this->value = $value;
				break;
			case 'modelValue':
				if(!$this->updateDB)
					$this->updateDB = $this->value !== $value;
				$this->updateModel = false;
				$this->value = $value;
				break;
		}
	}
	
	public function __toString() {
		return $this->value;
	}
}
?>