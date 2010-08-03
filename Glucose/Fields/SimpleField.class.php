<?php
namespace Glucose\Fields;
class SimpleField implements Field {
	
	private $column;
	
	private $currentValue;
	
	private $databaseValue;
	
	
	private $updateModel = true;
	
	private $updateDB = false;
	
	private $setToDefault = true;
	
	
	public function __construct(\Glucose\Column $column) {
		$this->column = $column;
		$this->currentValue = $this->column->default;
	}
	
	public function equalsCurrentValue($value) {
		
	}
	
	public function getTentativeValues(array $currentValues, $tentativeValue) {
		
	}
	
	public function __get($name) {
		switch($name) {
			case 'column':
				return $this->column;
			case 'currentValue':
				return $this->currentValue;
			case 'dbValue':
				return $this->databaseValue;
			case 'updateModel':
				return $this->updateModel;
			case 'updateDB':
				return $this->updateDB;
			case 'setToDefault':
				return $this->setToDefault;
		}
	}
	
	public function __set($name, $value) {
		switch($name) {
			case 'currentValue':
				$this->setToDefault = false;
				$this->updateDB = $this->databaseValue != $value;
				$this->updateModel = false;
				$this->currentValue = $value;
				break;
			case 'dbValue':
				$this->updateDB = false;
				$this->updateModel = false;
				$this->databaseValue = $value;
				$this->currentValue = $value;
				break;
		}
	}
	
	public function __unset($name) {
		if($name == 'currentValue') {
			if($this->column->default === null && $this->column->notNull)
				throw new E\Type\NotNullValueExpectedException("The field $this->exceptionName cannot be unset because it has no default value.");
			$this->setToDefault = true;
			$this->updateDB = true;
			if($this->column->defaultCurrentTimestamp)
				$this->updateModel = true;
			elseif($this->column->isAutoIncrement)
				$this->currentValue = 0;
			else
				$this->currentValue = $this->column->default;
		}
	}
	
	public function __isset($name) {
		// TODO: Should we check for $this->setToDefault here instead?
		switch($name) {
			case 'currentValue':
				return $this->currentValue !== null;
		}
	}
	
	public function dbInserted() {
		if($this->setToDefault && $this->column->defaultCurrentTimestamp) {
			$this->updateModel = true;
		} else {
			$this->updateModel = false;
			$this->dbValue = $this->currentValue;
		}
		$this->updateDB = false;
		$this->setToDefault = false;
	}
	
	public function dbUpdated() {
		if(($this->column->onUpdateCurrentTimestamp && $this->updateDB)
		|| ($this->setToDefault && $this->column->defaultCurrentTimestamp)) {
			$this->updateModel = true;
		} else {
			$this->updateModel = false;
			$this->dbValue = $this->currentValue;
		}
		$this->updateDB = false;
		$this->setToDefault = false;
	}
}