<?php
/**
 * This {@link Constraint constraint} specifies a set of unique {@link Column columns} in a {@link Table table}.
 * @author andsens
 * @package model
 * @subpackage model.table
 *
 */
namespace Glucose\Constraints;
class UniqueConstraint extends Constraint {
	
	/**
	 * Prepared statement, which retrieves a dataset from the table
	 * using the values the fields in this constraint respond to.
	 * @var MySQLi_STMT
	 */
	public $selectStatement;
	
	private $updateStatements = array();
	
	public $deleteStatement;
	
	public function setUpdateStatement(array $columnNames, mysqli_stmt $statement) {
		$this->updateStatements[$this->createHash($columnNames)] = $statement;
	}
	
	public function getUpdateStatement(array $columnNames) {
		$hash = $this->createHash($columnNames);
		if(array_key_exists($hash, $this->updateStatements))
			return $this->updateStatements[$hash];
		else
			return null;
	}
	
	private function createHash(array $columnNames) {
		$compoundHash = '';
		foreach($columnNames as $columnName)
			$compoundHash .= sha1($columnName);
		return sha1($compoundHash);
	}
}
?>