<?php
/**
 *
 * @author andsens
 * @package glucose
 *
 */
namespace Glucose;
use \Glucose\Exceptions\Entity as E;
class Entity implements \SplSubject {
	
	private $fields;
	private $instanceCount;
	
	private $observers;
	
	public function __construct(array $columns) {
		$fieldsArray = array();
		foreach($columns as $column)
			$fieldsArray[$column->name] = new Field($column);
		$this->fields = new ImmutableArrayObject($fieldsArray);
		$this->instanceCount = 0;
		
		$this->observers = new \SplObjectStorage();
	}
	
	public function getValues(array $columns) {
		$values = array();
		foreach($columns as $name => $column)
			$values[$name] = $this->fields[$column->name]->value;
		return $values;
	}
	
	public function getDBValues(array $columns) {
		$values = array();
		foreach($columns as $name => $column)
			$values[$name] = $this->fields[$column->name]->dbValue;
		return $values;
	}
	
	public function getUpdateValues() {
		$values = array();
		foreach($this->fields as $name => $field)
			if($field->updateDB)
				$values[$name] = $field->value;
		return $values;
	}

	public function attach(\SplObserver $observer) {
		$this->observers->attach($observer);
	}
	
	public function detach(\SplObserver $observer) {
		$this->observers->detach($observer);
	}
	
	public function notify() {
		foreach($this->observers as $observer)
			$observer->update($this);
	}
	
	private $shouldBeInDB;
	
	private $existsInDB;
	
	private $deleted;
	
	public function __set($name, $value) {
		switch($name) {
			case 'shouldBeInDB':
				$this->shouldBeInDB = $value;
				break;
			case 'existsInDB':
				if($value === true)
					$this->deleted = false;
				$this->shouldBeInDB = $value;
				$this->existsInDB = $value;
				break;
			case 'deleted':
				if($value === true)
					$this->shouldBeInDB = false;
					$this->existsInDB = false;
				$this->deleted = $value;
				break;
			case 'instanceCount':
				$this->instanceCount = $value;
				if($this->instanceCount == 0)
					$this->notify();
				break;
		}
	}
	
	public function __get($name) {
		switch($name) {
			case 'shouldBeInDB':
				return $this->shouldBeInDB;
			case 'existsInDB':
				return $this->existsInDB;
			case 'deleted':
				return $this->deleted;
			case 'instanceCount':
				return $this->instanceCount;
			case 'fields':
				return $this->fields;
		}
	}
}
?>