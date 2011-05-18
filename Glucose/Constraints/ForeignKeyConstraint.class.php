<?php
/**
 * This {@link Constraint constraint} represents a foreign key constraint on a {@link Table table}.
 * Information like update and delete rules as well as referencing information is stored here.
 * @author andsens
 *
 */
namespace Glucose\Constraints;
use MySQLi_Classes\Statements\SelectStatement;
use Glucose\ImmutableObjectStorage;

class ForeignKeyConstraint extends Constraint {
	private $updateRule;
	private $deleteRule;
	private $referencedTableName;
	private $referencedColumnNames;
	
	public function __construct($name, $columns) {
		$this->referencedColumnNames = new ImmutableObjectStorage();
		$fetchUpdateRulesStatement = new SelectStatement("", $types);
	}
}