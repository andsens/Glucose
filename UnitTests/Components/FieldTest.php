<?php
require_once 'PHPUnit/Framework.php';
use Glucose\Field;
use Glucose\Column;
class FieldTest extends PHPUnit_Framework_TestCase {
	
	private static $columns = array();
	
	public static function setUpBeforeClass() {
		foreach(array('countries', 'cities', 'people') as $tableName) {
			$table = new Glucose\Table($tableName);
			self::$columns[$tableName] = $table->columns;
		}
	}
	
	public function test_P_FieldInitialization() {
		$field = new Field(self::$columns['cities']['id']);
		$this->assertEquals(self::$columns['cities']['id'], $field->column);
	}
	
	public function test_P_InitialUpdateStates() {
		$field = new Field(self::$columns['cities']['id']);
		$this->assertTrue($field->updateModel);
		$this->assertFalse($field->updateDB);
	}
	
	public function test_P_ModelValueChanged() {
		$field = new Field(self::$columns['cities']['id']);
		$field->modelValue = 2;
		$this->assertEquals(2, $field->value);
	}
	
	public function test_P_DBValueChanged() {
		$field = new Field(self::$columns['cities']['id']);
		$field->dbValue = 2;
		$this->assertEquals(2, $field->value);
	}
	
	public function test_P_UpdateModelFlag() {
		$column = new Column('some_timestamp', 'timestamp', null, true, 'CURRENT_TIMESTAMP');
		$field = new Field($column);
		$field->dbValue = new DateTime();
		$field->dbUpdated();
		$this->assertTrue($field->updateModel);
	}
	
	public function test_P_UpdateDBFlag() {
		$field = new Field(self::$columns['cities']['id']);
		$field->modelValue = 2;
		$this->assertTrue($field->updateDB);
	}
	
	public function test_P_IsNotSet() {
		$field = new Field(self::$columns['cities']['id']);
		$this->assertFalse(isset($field->value));
	}
	
	public function test_P_IsSet() {
		$field = new Field(self::$columns['cities']['id']);
		$field->modelValue = 2;
		$this->assertTrue(isset($field->value));
	}
	
	public function test_P_UnSet() {
		$field = new Field(self::$columns['cities']['id']);
		$field->modelValue = 2;
		unset($field->value);
		$this->assertFalse(isset($field->value));
	}
	
	public function test_P_DefaultOnUpdate() {
		$column = new Column('some_string', 'varchar', 16, true, 'String');
		$field = new Field($column);
		$field->dbUpdated();
		$this->assertEquals('String', $field->value);
		$this->assertFalse($field->updateModel);
	}
	
	public function test_P_ModelValueOnUpdate() {
		$field = new Field(self::$columns['cities']['id']);
		$field->modelValue = 2;
		$field->dbUpdated();
		$this->assertEquals(2, $field->dbValue);
	}
	
	protected function tearDown() {
	}
}
?>
