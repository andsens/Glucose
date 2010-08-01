<?php
namespace Glucose\Statements;
class SelectStatement extends Statement {
	
	public function __get($name) {
		switch($name) {
			case 'rows':
				return $this->statement->num_rows;
			case 'stmt':
				return $this->statement;
		}
	}
	
	public function bindAndExecute(array $values) {
		parent::bindAndExecute($values);
		$this->statement->store_result();
	}
	
	public function bindResult(array $pointerArray) {
		call_user_func_array(array(&$this->statement, 'bind_result'), $pointerArray);
	}
	
	public function fetch() {
		return $this->statement->fetch();
	}
	
	public function freeResult() {
		$this->statement->free_result();
	}
}