<?php
namespace Glucose\Statements;
class DeleteStatement extends Statement {
	
	public function __get($name) {
		switch($name) {
			case 'rows':
				return $this->statement->affected_rows;
		}
	}
}