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
	
	private $identifier;
	
	private $fields;
	
	public $inDB;
	
	public $deleted;
	
	private $referenceCount;
	
	private $table;
	
	public function __construct(array $columns) {
		$fieldsArray = array();
		foreach($columns as $column)
			$fieldsArray[$column->name] = new Field($column);
		$this->fields = new ImmutableArrayObject($fieldsArray);
		$this->inDB = false;
		$this->deleted = false;
		$this->referenceCount = 0;
	}
	
	public function getValues(array $columns) {
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
			case 'identifier':
				$this->identifier = $value;
				break;
			case 'referenceCount':
				$this->referenceCount = $value;
				if($this->referenceCount == 0 && isset($this->table))
					$this->table->dereference($this);
				break;
			case 'table':
				$this->table = $value;
				break;
		}
	}
}