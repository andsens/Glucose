<?php
use \Glucose\Entity as Entity;
require_once 'PHPUnit/Framework.php';

class EntityTest extends PHPUnit_Framework_TestCase {
	
	private static $columns = array();
	
	public static function setUpBeforeClass() {
		$inflector = Glucose\Inflector::getInstance();
		foreach(array('countries', 'cities', 'people') as $className) {
			$tableName = $inflector->tableize($className);
			$table = new Glucose\Table($tableName);
			self::$columns[$className] = $table->columns;
		}
	}
	
	public function testFieldsExistence() {
		$country = new Entity(self::$columns['countries']);
		$this->assertNotNull($country->fields['id'], '`ID` field of country does not exist.');
		$this->assertNotNull($country->fields['name'], '`name` field of country does not exist.');
	}
	
	public function testGetValues() {
		$denmark = new Entity(self::$columns['countries']);
		$denmark->fields['id']->modelValue = 1;
		$denmark->fields['name']->modelValue = 'Denmark';
		$expected = array('id' => 1, 'name' => 'Denmark');
		$columnsToRetrieve = array('id' => self::$columns['countries']['id'], 'name' => self::$columns['countries']['name']);
		$this->assertEquals($expected, $denmark->getValues($columnsToRetrieve));
	}
	
	public function testGetDBValues() {
		$denmark = new Entity(self::$columns['countries']);
		$denmark->fields['id']->dbValue = 1;
		$denmark->fields['name']->dbValue = 'Denmark';
		$expected = array('id' => 1, 'name' => 'Denmark');
		$this->assertEquals($expected, $denmark->getDBValues(self::$columns['countries']));
	}
	
	public function testGetUpdateValues() {
		$aarhus = new Entity(self::$columns['cities']);
		$aarhus->fields['id']->dbValue = 1;
		$aarhus->fields['country']->dbValue = 2;
		$aarhus->fields['name']->modelValue = 'Århus';
		$aarhus->fields['postal_code']->modelValue = 8000;
		$updateColumns = array('name' => 'Århus', 'postal_code' => 8000);
		$columnsToRetrieve = array('name' => self::$columns['cities']['name'], 'postal_code' => self::$columns['cities']['postal_code']);
		$this->assertEquals($updateColumns, $aarhus->getUpdateValues($columnsToRetrieve));
		
		
	}
	
	protected function tearDown() {
	}
}
?>
