<?php
class Constraint implements IteratorAggregate {
	protected $name;
	protected $fields;
	protected $statementTypes;
	
	public function __construct($name) {
		$this->name = $name;
		$this->fields = array();
		$this->statementTypes = '';
	}
	
	public function addField(Field $field) {
		$this->fields[] = $field;
		$this->statementTypes .= $field->statementType;
	}
	
	public function __get($name) {
		switch($name) {
			case 'fields':
				return $this->fields;
			case 'statementTypes':
				return $this->statementTypes;
		}
	}
	
	public function __set($name, $value) {
		
	}
	
	public function getIterator() {
		return new ArrayIterator($this->fields);
	}
}
?>