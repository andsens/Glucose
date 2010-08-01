<?php
namespace Glucose;
class ForeignKeyField extends Field {
	
	public function __set($name, $value) {
		switch($name) {
			case 'object':
				$this->setToDefault = false;
				$this->updateDB = $this->databaseValue != $value;
				$this->updateModel = false;
				$this->currentValue = $value;
				break;
		}
	}
	
	public function __unset($name) {
		if($name == 'value') {
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
			case 'value':
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