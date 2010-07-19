<?php
use \Glucose\Model as Model;
require_once 'Models/Country.class.php';
require_once 'Models/City.class.php';
require_once 'Models/Person.class.php';
require_once 'Models/User.class.php';

class BasicInteractionTest extends TableComparisonTestCase {
	
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

	public function test_N_ObjectInitFailOnUndefined() {
		$this->setExpectedException('\Glucose\Exceptions\User\UndefinedPrimaryKeyException', 'The primary key you specified does not exist in the table.');
		$country = new Country(0);
		$country->name;
	}
	
	public function test_N_UndefinedEntity() {
		$this->setExpectedException('\Glucose\Exceptions\User\UndefinedPrimaryKeyException', 'The primary key you specified does not exist in the table.');
		$nonExistentCity = new City(0);
	}
	
	public function test_P_Deletion() {
		$helsinki = new City(3);
		$helsinki->delete();
		$this->deleteFrom('cities', array('id' => 3));
		unset($helsinki);
		$this->assertTablesEqual('cities');
	}
	
	public function test_P_Isset() {
		$newYork = new City;
		$this->assertFalse(isset($newYork->name));
		$newYork->delete();
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

	public function test_N_ObjectCreationFailOnNull() {
		$this->setExpectedException('\Glucose\Exceptions\MySQL\Server\MySQLNoDefaultForFieldException', "Field 'name' doesn't have a default value");
		$country = new Country;
		$country->name;
		unset($country);
	}
	
	public function test_N_ReadUndefinedField() {
		$country = new Country;
		$message = '';
		try {
			$blah = $country->postalCode;
		} catch(\Glucose\Exceptions\User\UndefinedPropertyException $e) {
			$message = $e->getMessage();
		}
		$country->delete();
		$this->assertEquals("The field 'postalCode' does not exists.", $message);
		$this->assertFalse(isset($blah));
	}
	
	public function test_N_ModifyUndefinedField() {
		$country = new Country;
		$message = '';
		try {
			$country->postalCode = 1534;
		} catch(\Glucose\Exceptions\User\UndefinedPropertyException $e) {
			$message = $e->getMessage();
		}
		$country->delete();
		$this->assertEquals("The field 'postalCode' does not exists.", $message);
	}
	
	public function test_N_ReadDeleted() {
		$arhus = new City(1);
		$arhus->delete();
		$this->setExpectedException('\Glucose\Exceptions\User\EntityDeletedException', "This entity has been deleted. You can no longer read its fields.");
		$name = $arhus->name;
	}
	
	public function test_N_ModifyDeleted() {
		$arhus = new City(1);
		$arhus->delete();
		$this->setExpectedException('\Glucose\Exceptions\User\EntityDeletedException', "This entity has been deleted. You can no longer modify its fields.");
		$arhus->name = 'SomethingElse';
	}
	
	protected function tearDown() {
		self::$mysqli->query('ROLLBACK;');
	}
}