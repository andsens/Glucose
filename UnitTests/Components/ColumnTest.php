<?php
require_once 'PHPUnit/Framework.php';

use Glucose\Column;
class ColumnTest extends PHPUnit_Framework_TestCase {
	
	
	protected function setUp() {
	}
	
	public function test_P_ColumnInitialization() {
		$column = new Column('some_timestamp', 'timestamp', null, true, 'CURRENT_TIMESTAMP');
		$this->assertEquals($column->name, 'some_timestamp');
	}
	public function test_P_ColumnStatementTypes() {
		$allTypes = array();
		$allTypes['i'] = array('tinyint(1)', 'smallint(1)', 'mediumint(1)', 'int(1)', 'bigint(1)');
		$allTypes['d'] = array('real', 'double', 'float', 'decimal');
		$allTypes['b'] = array('tinyblob', 'mediumblob', 'blob', 'longblob');
		foreach($allTypes as $statementType => $types) {
			foreach($types as $type) {
				$column = new Column('column', $type, null, false, null);
				$this->assertEquals($statementType, $column->statementType, "The type $type is not categorized as $statementType");
			}
		}
	}
	
	protected function tearDown() {
	}
}
