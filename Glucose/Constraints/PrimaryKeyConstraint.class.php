<?php
/**
 * Represents a primary key {@link Constraint constraint} in a {@link Table table}.
 * @author andsens
 */
namespace Glucose\Constraints;
class PrimaryKeyConstraint extends UniqueConstraint {
	
	public $existenceStatement;
	
	public $updateStatements = array();
	
	public $refreshStatements = array();
	
	public $deleteStatement;
}