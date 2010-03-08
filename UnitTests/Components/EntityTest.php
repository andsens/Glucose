<?php
use \Glucose\Entity as Entity;
require_once 'PHPUnit/Framework.php';

class EntityTest extends PHPUnit_Framework_TestCase {
	
	private static $columns = array();
	
	public static function setUpBeforeClass() {
		foreach(array('countries', 'cities', 'people') as $tableName) {
			$table = new Glucose\Table($tableName);
			self::$columns[$tableName] = $table->columns;
		}
	}
	
	public function test_P_FieldExistence() {
		$country = new Entity(self::$columns['countries']);
		$this->assertNotNull($country->fields['id'], '`ID` field of country does not exist.');
		$this->assertNotNull($country->fields['name'], '`name` field of country does not exist.');
	}
	
	public function test_N_CannotAccessNonExistentFields() {
		$country = new Entity(self::$columns['countries']);
		$this->setExpectedException('OutOfBoundsException', 'The offset you are trying to access does not exist.');
		$country->fields['blahrg'];
	}
	
	public function test_P_GetValues() {
		$denmark = new Entity(self::$columns['countries']);
		$denmark->fields['id']->modelValue = 1;
		$denmark->fields['name']->modelValue = 'Denmark';
		$expected = array(1, 'Denmark');
		$columnsToRetrieve = array('id' => self::$columns['countries']['id'], 'name' => self::$columns['countries']['name']);
		$this->assertEquals($expected, $denmark->getValues($columnsToRetrieve));
	}
	
	public function test_P_GetDBValues() {
		$denmark = new Entity(self::$columns['countries']);
		$denmark->fields['id']->dbValue = 1;
		$denmark->fields['name']->dbValue = 'Denmark';
		$expected = array(1, 'Denmark');
		$this->assertEquals($expected, $denmark->getDBValues(self::$columns['countries']));
	}
	
	protected function tearDown() {
	}
}
?>
