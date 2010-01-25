<?php
/**
 * Represents a primary key {@link Constraint constraint} in a {@link Table table}.
 * @author andsens
 * @package glucose
 * @subpackage glucose.constraints
 *
 * @property-read Column autoIncrementColumn The auto incrementing {@link Column column}
 */
namespace Glucose\Constraints;
class PrimaryKeyConstraint extends UniqueConstraint {
	
	/**
	 * Points at the {@link Column column}, which auto increments in this {@link Constraint constraint}
	 * @var unknown_type
	 */
	public $autoIncrementColumn;
	
	private $updateStatements = array();
	
	public $deleteStatement;
	
	public function setUpdateStatement(array $columnNames, \mysqli_stmt $statement) {
		$this->updateStatements[$this->createHash($columnNames)] = $statement;
	}
	
	public function getUpdateStatement(array $columnNames) {
		$hash = $this->createHash($columnNames);
		if(array_key_exists($hash, $this->updateStatements))
			return $this->updateStatements[$hash];
		else
			return null;
	}
}
?>