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
	
	public function test_P_InitByInstanceJoin() {
		$aarhus = City::initByCountryAndPostalCode(2, 8000);
		$aarhus->name = 'Dublin';
		$dublin = new City(1);
		$this->assertEquals('Dublin', $dublin->name);
	}
	
	public function test_P_PrimaryKeyIdentifierUpdate() {
		$hamburg1 = new City(2);
		$hamburg1->id = 50;
		$hamburg2 = new City(50);
		$this->assertEquals('Hamburg', $hamburg2->name);
	}
	
	public function test_P_UniqueKeyIdentifierUpdate() {
		$hamburg1 = new City(2);
		$hamburg1->postalCode = 1002;
		$hamburg2 = City::initByCountryAndPostalCode(1, 1002);
		$this->assertEquals('Hamburg', $hamburg2->name);
	}

	public function test_N_ObjectCreationFailOnNull() {
		$this->markTestIncomplete();
		$this->setExpectedException('\Glucose\Exceptions\MySQL\Server\MySQLBadNullException', "Column 'name' cannot be null");
		$country = new Country;
		$country->name;
		unset($country);
	}

	public function test_N_ObjectInitFailOnUndefined() {
		$this->markTestIncomplete();
		$this->setExpectedException('UndefinedPrimaryKeyException', 'The primary key you specified is not represented in the database.');
		$country = new Country(0);
		$country->name;
	}
	
	public function test_P_Equality1() {
		$hamburg1 = new City(2);
		$hamburg2 = new City(2);
		$this->assertTrue($hamburg1 == $hamburg2);
	}
	
	public function test_P_Equality2() {
		$hamburg1 = new City(2);
		$hamburg2 = new City(2);
		$hamburg2->name = 'Hamburg - Das Tor zur Welt ';
		$this->assertTrue($hamburg1 == $hamburg2);
	}
	
	public function test_P_Inequality() {
		$aarhus = new City(1);
		$hamburg = new City(2);
		$this->assertFalse($aarhus == $hamburg);
	}
	
	public function test_P_NoChangeOnCollision() {
		$aarhus = new City(1);
		$hamburg = new City(2);
		try {
			$aarhus->id = 2;
		} catch(\Glucose\Exceptions\User\EntityCollisionException $e) { }
		$this->assertEquals(1, $aarhus->id);
	}
	
	public function test_P_DBConsolidationForCollisionDetection() {
		$aarhus = new City(1);
		$this->setExpectedException('\Glucose\Exceptions\User\EntityCollisionException', 'Your changes collide with the unique values of an existing entity.');
		$aarhus->id = 4;
	}
	
	public function test_N_InitByWrongArgumentNumber() {
		$this->setExpectedException('\Glucose\Exceptions\User\InitializationArgumentException', 'The function \'initByCountryAndPostalCode\' was called with 1 arguments but requires 2.');
		City::initByCountryAndPostalCode(2);
	}
	
	public function test_N_InitByUndefinedMethod() {
		$this->setExpectedException('\Glucose\Exceptions\User\UndefinedMethodException', 'Call to undefined method \'initBySomeUndefinedMethod\'.');
		City::initBySomeUndefinedMethod(2);
	}
	
	protected function tearDown() {
		self::$mysqli->query('ROLLBACK;');
	}
}
?>
