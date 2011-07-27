<?php
/**
 * This {@link Constraint constraint} represents a foreign key constraint on a {@link Table table}.
 * Information like update and delete rules as well as referencing information is stored here.
 * @author andsens
 * @property string referencedTableName
 *
 */
namespace Glucose\Constraints;
use MySQLi_Classes\Statements\SelectStatement;
use Glucose\ImmutableObjectStorage;

class ForeignKeyConstraint extends Constraint {
	private $updateRule;
	private $deleteRule;
	private $referencedTableName;
	private $referencedColumns;
	
	public function __construct($name, $columns) {
		$this->referencedColumnNames = new ImmutableObjectStorage();
		$fetchUpdateRulesStatement = new SelectStatement("", $types);
	}
	
	public function __get($name) {
		switch($name) {
			case 'referencedTableName':
				return $this->referencedTableName;
			default:
				parent::__get($name);
		}
	}
}