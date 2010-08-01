<?php
namespace Glucose\Statements;

class UpdateStatement extends Statement {
	
	public function __get($name) {
		switch($name) {
			case 'rows':
				return $this->statement->affected_rows;
		}
	}
}