<?php
/**
 * This {@link Constraint constraint} represents a foreign key constraint on a {@link Table table}.
 * Information like update and delete rules as well as referencing information is stored here.
 * @author andsens
 * @package glucose
 * @subpackage glucose.constraints
 *
 */
namespace Glucose\Constraints;
class ForeignKeyConstraint extends Constraint {
	private $updateRule;
	private $deleteRule;
}
?>