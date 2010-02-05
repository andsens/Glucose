<?php
/**
 * This {@link Constraint constraint} specifies a set of unique {@link Column columns} in a {@link Table table}.
 * @author andsens
 * @package glucose
 * @subpackage glucose.constraints
 *
 */
namespace Glucose\Constraints;
class UniqueConstraint extends Constraint {
	
	private $insertStatements = array();
	
	/**
	 * Prepared statement, which retrieves a dataset from the table
	 * using the values the fields in this constraint respond to.
	 * @var MySQLi_STMT
	 */
	public $selectStatement;
	
	public $existenceStatement;
	
	private $updateStatements = array();
	
	private $refreshStatements = array();
	
	public $deleteStatement;
	
	public function setUpdateStatement(array $columnNames, \mysqli_stmt $statement) {
		$this->updateStatements[\Glucose\Column::createHash($columnNames)] = $statement;
	}
	
	public function getUpdateStatement(array $columnNames) {
		return $this->getStatement($this->updateStatements, $columnNames);
	}
	
	public function setInsertStatement(array $columnNames, \mysqli_stmt $statement) {
		$this->insertStatements[\Glucose\Column::createHash($columnNames)] = $statement;
	}
	
	public function getInsertStatement(array $columnNames) {
		return $this->getStatement($this->insertStatements, $columnNames);
	}
	
	public function setRefreshStatement(array $columnNames, \mysqli_stmt $statement) {
		$this->refreshStatements[\Glucose\Column::createHash($columnNames)] = $statement;
	}
	
	public function getRefreshStatement(array $columnNames) {
		return $this->getStatement($this->refreshStatements, $columnNames);
	}
	
	private function getStatement(array $statementPool, array $columnNames) {
		$hash = \Glucose\Column::createHash($columnNames);
		if(array_key_exists($hash, $statementPool))
			return $statementPool[$hash];
		else
			return null;
	}
}
?>