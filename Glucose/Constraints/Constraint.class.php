<?php
/**
 * Represents a constraint on a {@link Table table}.
 * @author andsens
 * @package glucose
 * @subpackage glucose.constraints
 *
 * @property-read array $columns Indexed array of {@link Column columns} which are part of this constraint
 * @property-read string $statementTypes Concatenated string of statement types this constraint consists of
 */
namespace Glucose\Constraints;
abstract class Constraint {
	/**
	 * Name of the constraint
	 * @var string
	 */
	protected $name;
	
	/**
	 * Indexed array of columns which are part of this constraint
	 * @var array
	 * @see Column
	 */
	protected $columns;
	
	/**
	 * Since columns can't be removed or rearranged
	 * the statement types don't need to be computed dynamically
	 * @var string
	 */
	protected $statementTypes;
	
	/**
	 * Constructs the constraint.
	 * @param string $name Name of the constraint
	 */
	public function __construct($name) {
		$this->name = $name;
		$this->columns = array();
		$this->statementTypes = '';
	}
	
	/**
	 * Adds a column to the constraint.
	 * @param Column $column Column to be added
	 */
	public function addColumn(\Glucose\Column $column) {
		$this->columns[] = $column;
		$this->statementTypes .= $column->statementType;
	}
	
	/**
	 * Magic method, which returns various properties of the constraint.
	 * @ignore
	 * @param string $name Name of the property to return
	 * @return mixed Value of the property
	 */
	public function __get($name) {
		switch($name) {
			case 'name':
				return $this->name;
			case 'columns':
				return $this->columns;
			case 'statementTypes':
				return $this->statementTypes;
		}
	}
}