<?php
class Field {
	private $table;
	private $name;
	private $type;
	private $maxLength;
	private $notNull;
	private $default;
	
	public function __construct($name, $type = 'int', $maxLength = null, $notNull = false, $default = null) {
		$this->name = $name;
		$this->type = $type;
		$this->maxLength = $maxLength;
		$this->notNull = $notNull;
		$this->default = $default;
	}
	
	public function __get($name) {
		switch($name) {
			case 'name':
				return $this->name;
			case 'type':
				return $this->type;
			case 'statementType':
				return $this->getStatementType();
			case 'maxLength':
				return $this->maxLength;
			case 'notNull':
				return $this->notNull;
			case 'default':
				return $this->default;
		}
	}
	
	private function getStatementType() {
		switch($this->type) {
			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'int':
			case 'bigint':
				return 'i';
			case 'real':
			case 'double':
			case 'float':
			case 'decimal':
				return 'd';
			case 'tinyblob':
			case 'mediumblob':
			case 'blob':
			case 'longblob':
				return 'b';
			default:
				return 's';
		}
	}
	
	public function __toString() {
		return $this->name;
	}
}
?>