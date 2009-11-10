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
}
?>