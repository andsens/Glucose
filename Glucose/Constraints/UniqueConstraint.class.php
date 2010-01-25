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
	
	/**
	 * Prepared statement, which retrieves a dataset from the table
	 * using the values the fields in this constraint respond to.
	 * @var MySQLi_STMT
	 */
	public $selectStatement;
	
	private $refreshStatements = array();
	
	public function setRefreshStatement(array $columnNames, \mysqli_stmt $statement) {
		$this->refreshStatements[$this->createHash($columnNames)] = $statement;
	}
	
	public function getRefreshStatement(array $columnNames) {
		$hash = $this->createHash($columnNames);
		if(array_key_exists($hash, $this->refreshStatements))
			return $this->refreshStatements[$hash];
		else
			return null;
	}
	
	protected function createHash(array $columnNames) {
		$compoundHash = '';
		foreach($columnNames as $columnName)
			$compoundHash .= sha1($columnName);
		return sha1($compoundHash);
	}
}
?>