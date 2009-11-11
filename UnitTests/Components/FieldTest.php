<?php
require_once 'PHPUnit/Framework.php';
use Glucose\Field;
use Glucose\Column;
class FieldTest extends PHPUnit_Framework_TestCase {
	
	private static $columns = array();
	
	public static function setUpBeforeClass() {
		$inflector = Glucose\Inflector::getInstance();
		foreach(array('countries', 'cities', 'people') as $className) {
			$tableName = $inflector->tableize($className);
			$table = new Glucose\Table($tableName);
			self::$columns[$className] = $table->columns;
		}
	}
	
	public function testFieldInitialization() {
		$field = new Field(self::$columns['cities']['id']);
		$this->assertEquals(self::$columns['cities']['id'], $field->column);
	}
	
	public function testInitialUpdateStates() {
		$field = new Field(self::$columns['cities']['id']);
		$this->assertTrue($field->updateModel);
		$this->assertFalse($field->updateDB);
	}
	
	public function testModelValueChanged() {
		$field = new Field(self::$columns['cities']['id']);
		$field->modelValue = 2;
		$this->assertEquals(2, $field->value);
	}
	
	public function testDBValueChanged() {
		$field = new Field(self::$columns['cities']['id']);
		$field->dbValue = 2;
		$this->assertEquals(2, $field->value);
	}
	
	public function testUpdateModelFlag() {
		$column = new Column('some_timestamp', 'timestamp', null, true, 'CURRENT_TIMESTAMP');
		$field = new Field($column);
		$field->dbValue = new DateTime();
		$field->dbUpdated();
		$this->assertTrue($field->updateModel);
	}
	
	public function testUpdateDBFlag() {
		$field = new Field(self::$columns['cities']['id']);
		$field->modelValue = 2;
		$this->assertTrue($field->updateDB);
	}
	
	public function testIsNotSet() {
		$field = new Field(self::$columns['cities']['id']);
		$this->assertFalse(isset($field->value));
	}
	
	public function testIsSet() {
		$field = new Field(self::$columns['cities']['id']);
		$field->modelValue = 2;
		$this->assertTrue(isset($field->value));
	}
	
	public function testDefaultOnUpdate() {
		$column = new Column('some_string', 'varchar', 16, true, 'String');
		$field = new Field($column);
		$field->dbUpdated();
		$this->assertEquals('String', $field->value);
		$this->assertFalse($field->updateModel);
	}
	
	public function testModelValueOnUpdate() {
		$field = new Field(self::$columns['cities']['id']);
		$field->modelValue = 2;
		$field->dbUpdated();
		$this->assertEquals(2, $field->dbValue);
	}
	
	protected function tearDown() {
	}
}
?>
