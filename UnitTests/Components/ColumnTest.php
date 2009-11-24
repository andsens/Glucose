<?php
require_once 'PHPUnit/Framework.php';

use Glucose\Column;
class ColumnTest extends PHPUnit_Framework_TestCase {
	
	
	protected function setUp() {
	}
	
	public function testColumnInitialization() {
		$column = new Column('some_timestamp', 'timestamp', null, true, 'CURRENT_TIMESTAMP');
		$this->assertEquals($column->name, 'some_timestamp');
	}
	
	public function testColumnStatementTypes() {
		$allTypes = array();
		$allTypes['i'] = array('tinyint', 'smallint', 'mediumint', 'int', 'bigint');
		$allTypes['d'] = array('real', 'double', 'float', 'decimal');
		$allTypes['b'] = array('tinyblob', 'mediumblob', 'blob', 'longblob');
		foreach($allTypes as $statementType => $types) {
			foreach($types as $type) {
				$column = new Column('column', $type, null, false, null);
				$this->assertEquals($statementType, $column->statementType);
			}
		}
	}
	
	protected function tearDown() {
	}
}
?>