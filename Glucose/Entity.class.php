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
	
	public $inDB;
	
	public $deleted;
	
	private $referenceCount;
	
	private $observers;
	
	public function __construct(array $columns) {
		$fieldsArray = array();
		foreach($columns as $column)
			$fieldsArray[$column->name] = new Field($column);
		$this->fields = new ImmutableArrayObject($fieldsArray);
		$this->inDB = false;
		$this->deleted = false;
		$this->referenceCount = 0;
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
	
	public function getRefreshColumnNames() {
		$columns = array();
		foreach($this->fields as $name => $field)
			if($field->updateModel)
				$columns[] = $name;
		return $columns;
	}
	
	public function dbUpdated() {
		foreach($this->fields as $field)
			$field->dbUpdated();
	}
	
	public function __get($name) {
		switch($name) {
			case 'fields':
				return $this->fields;
			case 'referenceCount':
				return $this->referenceCount;
		}
	}
	
	public function __set($name, $value) {
		switch($name) {
			case 'referenceCount':
				$this->referenceCount = $value;
				if($this->referenceCount == 0)
					$this->notify();
				break;
		}
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
}
?>