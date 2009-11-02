<?php
class PrimaryKeyConstraint extends Constraint {
	
	private $autoIncrementField;
	
	public function __get($name) {
		switch($name) {
			case 'autoIncrementField':
				return $this->autoIncrementField;
			default:
				return parent::__get($name);
		}
	}
	
	public function __set($name, $value) {
		switch($name) {
			case 'autoIncrementField':
				$this->autoIncrementField = $value;
				break;
			default:
				return parent::__set($name, $value);
		}
	}
}
?>