<?php
require_once 'TableComparisonTestCase.class.php';

use \Glucose\Entity as Entity;
class TableTest extends TableComparisonTestCase {
	
	private static $tables = array();
	private static $mysqli;
	
	public static function setUpBeforeClass() {
		self::$mysqli = $GLOBALS['mysqli'];
		foreach(array('countries', 'cities', 'people') as $tableName) {
			self::$tables[$tableName] = new Glucose\Table($tableName);
		}
	}
	
	protected function setUp() {
		self::$mysqli->query('START TRANSACTION;');
	}
	
	protected function getConnection() {
		return self::$mysqli;
	}
	
	public function insertInto($tableName, array $values) {
		$fields = '(`'.implode('`, `', array_keys($values)).'`)';
		$values = "('".implode("', '", $values)."')";
		self::$mysqli->query("INSERT INTO `{$GLOBALS['comparisonSchema']}`.`{$tableName}`
		{$fields} VALUES {$values}");
		return self::$mysqli->insert_id;
	}
	
	public function testInsert1() {
		$values = array(
			'country' => 2,
			'name' => 'København',
			'postal_code' => 1000);
		$cities = self::$tables['cities'];
		$copenhagen = $cities->newEntity();
		$copenhagen->referenceCount++;
		foreach($values as $field => $value)
			$copenhagen->fields[$field]->modelValue = $value;
		$copenhagen->referenceCount--;
		$insertID = $this->insertInto('cities', $values);
		$this->assertEquals(self::$mysqli->insert_id, $copenhagen->fields['id']->value);
		$this->assertTablesEqual($GLOBALS['comparisonSchema'], 'cities', $GLOBALS['schema'], 'cities');
	}
	
	public function testInsert2() {
		$values = array(
			'first_name' => 'Anders',
			'last_name' => 'And',
			'email' => 'anders@ande.net',
			'address' => 'Paradisæblevej 111',
			'city' => 4);
		$people = self::$tables['people'];
		$donaldDuck = $people->newEntity();
		$donaldDuck->referenceCount++;
		foreach($values as $field => $value)
			$donaldDuck->fields[$field]->modelValue = $value;
		$donaldDuck->referenceCount--;
		$insertID = $this->insertInto('people', $values);
		$this->assertEquals($insertID, $donaldDuck->fields['id']->value);
		$this->assertTablesEqual($GLOBALS['comparisonSchema'], 'people', $GLOBALS['schema'], 'people');
	}
	
	public function testSelectUpdate1() {
		
	}
	
	public function testSelectUpdate2() {
		
	}
	
	public function testSelectDelete1() {
		
	}
	
	public function testSelectDelete2() {
		
	}
	
	public function testDeleteAnonymous() {
		
	}
	
	public function testCollision() {
		$countries = self::$tables['countries'];
		$uganda1 = $countries->newEntity();
		$uganda1->fields['name']->modelValue = 'Uganda';
		$countries->updateIdentifiers($uganda1);
		$uganda2 = $countries->newEntity();
		$uganda2->fields['name']->modelValue = 'Uganda';
		$this->setExpectedException('Glucose\Exceptions\Entity\ModelConstraintCollisionException',
		'An entity with the same set of values for the unique constraint UNIQUE_countries__name already exists in the model');
		$countries->updateIdentifiers($uganda2);
		$this->assertTablesEqual($GLOBALS['comparisonSchema'], 'countries', $GLOBALS['schema'], 'countries');
	}
	
	protected function tearDown() {
		$GLOBALS['mysqli']->query('ROLLBACK;');
	}
}
?>
