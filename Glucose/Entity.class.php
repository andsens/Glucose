<?php
/**
 *
 * @author andsens
 * @package glucose
 *
 */
namespace Glucose;
use \Glucose\Exceptions\Entity as E;
class Entity {
	
	public $modelHashes = array();
	public $dbHashes = array();
	
	private $fields;
	
	public function __construct(array $columns) {
		$this->fields = new \ArrayObject();
		foreach($columns as $column)
			$this->fields[$column->name] = new Field($column);
	}
	
	public function getValues() {
		$values = array();
		foreach($this->fields as $field)
			$values[] = $field->value;
		return $values;
	}
	
	public function getModelValues(array $columns) {
		$values = array();
		foreach($columns as $column)
			$values[] = $this->fields[$column->name]->value;
		return $values;
	}
	
	public function getDBValues(array $columns) {
		$values = array();
		foreach($columns as $column)
			$values[] = $this->fields[$column->name]->dbValue;
		return $values;
	}
	
	public function getUpdateValues() {
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
		}
	}
}
?>