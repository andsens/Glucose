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
}
?>