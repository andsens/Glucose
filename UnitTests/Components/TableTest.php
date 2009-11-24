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
	
	public function testSelect1() {
		$cities = self::$tables['cities'];
		$arhus = $cities->select(array(1), $cities->primaryKeyConstraint);
		$this->assertEquals(1, $arhus->fields['id']->value);
		$this->assertEquals(2, $arhus->fields['country']->value);
		$this->assertEquals('Århus', $arhus->fields['name']->value);
		$this->assertEquals(8000, $arhus->fields['postal_code']->value);
	}
	
	public function testInsert1() {
		$cityComparison = '`'.$GLOBALS['comparisonSchema'].'`.`cities`';
		$cityFields = '(`country`, `name`, `postal_code`)';
		$cities = self::$tables['cities'];
		$copenhagen = new Entity($cities->columns);
		$copenhagen->fields['country']->modelValue = 2;
		$copenhagen->fields['name']->modelValue = 'København';
		$copenhagen->fields['postal_code']->modelValue = 1000;
		$cities->insert($copenhagen);
		self::$mysqli->query("INSERT INTO {$cityComparison} {$cityFields} VALUES (2, 'København', 1000)");
		$this->assertTablesEqual($GLOBALS['comparisonSchema'], 'cities', $GLOBALS['schema'], 'cities');
	}
	
	public function testInsert2() {
		$peopleComparison = '`'.$GLOBALS['comparisonSchema'].'`.`people`';
		$peopleFields = '(`first_name`, `last_name`, `email`, `address`, `city`)';
		$people = self::$tables['people'];
		$donaldDuck = new Entity($people->columns);
		$donaldDuck->fields['first_name']->modelValue = 'Anders';
		$donaldDuck->fields['last_name']->modelValue = 'And';
		$donaldDuck->fields['email']->modelValue = 'anders@ande.net';
		$donaldDuck->fields['address']->modelValue = 'Paradisæblevej 111';
		$donaldDuck->fields['city']->modelValue = 4;
		$people->insert($donaldDuck);
		self::$mysqli->query("INSERT INTO {$peopleComparison} {$peopleFields} VALUES ('Anders', 'And', 'anders@ande.net', 'Paradisæblevej 111', 4)");
		$this->assertTablesEqual($GLOBALS['comparisonSchema'], 'people', $GLOBALS['schema'], 'people');
	}
	
	protected function tearDown() {
		$GLOBALS['mysqli']->query('ROLLBACK;');
	}
}
?>
