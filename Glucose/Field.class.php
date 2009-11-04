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
	
	private $databaseValue;
	
	private $currentValue;
	
	/**
	 * Wether the database needs updating
	 * @var bool
	 */
	private $updateDB;
	
	/**
	 * Wether the model needs updating
	 * @var bool
	 */
	private $updateModel;
	
	/**
	 * Constructs the Field.
	 * @param Column $column Column this field belongs to
	 */
	public function __construct(Column $column) {
		$this->column = $column;
		$this->updateDB = false;
		$this->updateModel = true;
	}
	
	/**
	 * Magic method for retrieving various information about the field
	 * @ignore
	 * @param string $name Name of the field property
	 * @return mixed
	 */
	public function __get($name) {
		switch($name) {
			case 'column':
				return $this->column;
			case 'value':
				return $this->currentValue;
			case 'dbValue':
				return $this->databaseValue;
			case 'updateDB':
				return $this->updateDB;
			case 'updateModel':
				return $this->updateModel;
		}
	}
	
	/**
	 * Magic method for specifying the fields value
	 * @ignore
	 * @param string $name Can be either 'modelValue' or 'dbValue', depending on where the value originates from
	 * @param mixed $value New value of the field
	 */
	public function __set($name, $value) {
		switch($name) {
			case 'dbValue':
				$this->updateDB = false;
				$this->updateModel = false;
				$this->databaseValue = $value;
				$this->currentValue = $value;
				break;
			case 'modelValue':
				$this->updateDB = $this->databaseValue != $value;
				$this->updateModel = false;
				$this->currentValue = $value;
				break;
		}
	}
	
	/**
	 * Unsets the value of the field.
	 * @ignore
	 * @param string $name If $name is not equal to 'value', nothing will be unset
	 */
	public function __unset($name) {
		if($name == 'value') {
			unset($this->currentValue);
			$this->updateDB = true;
			$this->updateModel = false;
		}
	}
	
	/**
	 * Returns wether the value of the field has been set.
	 * @ignore
	 * @param string $name If $name is not equals to 'value', null will be returned
	 * @return bool
	 */
	public function __isset($name) {
		if($name == 'value') {
			return isset($this->currentValue);
		}
	}
	
	/**
	 * Signals that the database has been updated, depending on the column type
	 * the object takes corresponding action and either sets it's value to the default,
	 * does nothing or reports that it needs updating.
	 */
	public function dbUpdated() {
		$this->updateDB = false;
		// TODO: What about other timestamp types with null values?
		if($this->column->type == 'timestamp' && strtoupper($this->column->default) == 'CURRENT_TIMESTAMP' && !$this->updateDB)
			$this->updateModel = true;
		elseif(!isset($this->value))
			$this->dbValue = $this->column->default;
		else
			$this->dbValue = $this->value;
	}
	
	/**
	 * Converts the field into a string
	 * @ignore
	 * @return mixed Value of the field
	 */
	public function __toString() {
		return $this->modelValue;
	}
}
?>