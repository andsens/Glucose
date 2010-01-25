<?php
use \Glucose\Model as Model;
require_once 'TableComparisonTestCase.class.php';
require_once 'Components/Models/Country.class.php';
require_once 'Components/Models/City.class.php';
require_once 'Components/Models/Person.class.php';
require_once 'Components/Models/User.class.php';

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
	
	public function test_P_Initialization() {
		$anders = new Person(1);
		$this->assertEquals('Anders', $anders->firstName);
		$this->assertEquals('Ingemann', $anders->lastName);
		$this->assertEquals('anders@ingemann.de', $anders->email);
		$this->assertEquals('Vej 13', $anders->address);
		$this->assertEquals(1, $anders->city);
	}
	
	public function test_P_Creation() {
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
	
	public function test_P_FieldPopulation() {
		$aarhus = new City(1);
		$this->assertEquals('Århus', $aarhus->name);
		$this->assertEquals(8000, $aarhus->postalCode);
	}
	
	public function test_P_Deletion() {
		$helsinki = new City(3);
		$helsinki->delete();
		$this->deleteFrom('cities', array(5));
		$this->assertTablesEqual('cities');
	}
	
	public function test_P_Unset() {
		$anders = new Person(1);
		unset($anders->email);
		$this->assertFalse(isset($anders->email));
		unset($anders);
		$this->update('people', array('id' => 1), array('email' => null));
		$this->assertTablesEqual('people');
	}
	
	public function test_P_Update() {
		$hamburg = new City(2);
		$hamburg->country = 2;
		unset($hamburg);
		$this->update('cities', array('id' => 2), array('country' => 2));
		$this->assertTablesEqual('people');
	}
	
	public function test_P_InstanceJoin() {
		$andsens1 = new User(1);
		$andsens2 = new User(1);
		$andsens1->nickname = 'somethingelse';
		$this->assertEquals('somethingelse', $andsens2->nickname);
	}
	
	public function test_P_InitBy() {
		$aarhus = City::initByCountryAndPostalCode(2, 8000);
		$this->assertEquals('Århus', $aarhus->name);
	}
	
	protected function tearDown() {
		self::$mysqli->query('ROLLBACK;');
	}
}
?>
