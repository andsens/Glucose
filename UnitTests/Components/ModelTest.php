<?php
use \Glucose\Model as Model;
require_once 'TableComparisonTestCase.class.php';
require_once 'Components/Models/Country.class.php';
require_once 'Components/Models/City.class.php';
require_once 'Components/Models/Person.class.php';

class ModelTest extends TableComparisonTestCase {
	
	private static $mysqli;
	
	protected function setUp() {
		$this->comparisonSchema = $GLOBALS['comparisonSchema'];
		$this->actualSchema = $GLOBALS['schema'];
		self::$mysqli = $GLOBALS['mysqli'];
		
		self::$mysqli->query('START TRANSACTION;');
	}
	
	protected function getConnection() {
		return self::$mysqli;
	}
	
	public function test_P_ModelInitialization() {
		$anders = new Person(1);
		$this->assertEquals('Anders', $anders->firstName);
		$this->assertEquals('Ingemann', $anders->lastName);
		$this->assertEquals('anders@ingemann.de', $anders->email);
		$this->assertEquals('Vej 13', $anders->address);
		$this->assertEquals(1, $anders->city);
	}
	
	public function test_P_ModelCreation() {
		$copenhagen = new City();
		$copenhagen->country = 2;
		$copenhagen->name = 'København';
		$copenhagen->postalCode = 1000;
		
		$values = array(
			'country' => 2,
			'name' => 'København',
			'postal_code' => 1000);
		$insertID = $this->insertInto('cities', $values);
		$this->assertEquals($insertID, $copenhagen->id);
		$this->assertTablesEqual('cities');
	}
	
	protected function tearDown() {
		self::$mysqli ->query('ROLLBACK;');
	}
}
?>
