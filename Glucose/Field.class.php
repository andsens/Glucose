<?php
/**
 * Holds information about a field in a dataset.
 * @author andsens
 * @package glucose
 *
 * @property-read Column $column {@link Column Column} this field is a part of
 * @property-read mixed $value Value of the field
 * @property-write mixed $dbValue Value coming from the database
 * @property-write mixed $modelValue Value coming from the user
 * @property-read bool $updateDB Wether the database needs updating
 * @property-read bool $updateModel Wether the model needs updating
 *
 */
namespace Glucose;
class Field {
	/**
	 * The column this field belongs to
	 * @var Column
	 */
	private $column;
	
	private $currentValue;
	
	private $databaseValue;
	
	/**
	 * Wether the model needs updating
	 * @var bool
	 */
	private $updateModel = true;
	
	/**
	 * Wether the database needs updating
	 * @var bool
	 */
	private $updateDB = false;
	
	private $setToDefault = true;
	
	/**
	 * Constructs the Field.
	 * @param Column $column Column this field belongs to
	 */
	public function __construct(Column $column) {
		$this->column = $column;
		$this->currentValue = $this->column->default;
	}
	
	public function __get($name) {
		switch($name) {
			case 'column':
				return $this->column;
			case 'value':
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
			case 'modelValue':
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