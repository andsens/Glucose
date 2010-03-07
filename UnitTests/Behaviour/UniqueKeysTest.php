<?php
use \Glucose\Model as Model;
require_once 'Models/Country.class.php';
require_once 'Models/City.class.php';
require_once 'Models/Person.class.php';
require_once 'Models/User.class.php';

class UniqueKeysTest extends TableComparisonTestCase {
	
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
	
	public function test_P_PrimaryKeyIdentifierUpdate() {
		$hamburg1 = new City(2);
		$hamburg1->id = 50;
		$hamburg2 = new City(50);
		$this->assertEquals('Hamburg', $hamburg2->name);
	}
	
	public function test_P_InitBy() {
		$aarhus = City::initByCountryAndPostalCode(2, 8000);
		$this->assertEquals('Århus', $aarhus->name);
	}
	
	public function test_P_UniqueKeyIdentifierUpdate() {
		$hamburg1 = new City(2);
		$hamburg1->postalCode = 1002;
		$hamburg2 = City::initByCountryAndPostalCode(1, 1002);
		$this->assertEquals('Hamburg', $hamburg2->name);
	}
	
	public function test_N_InitDeletedEntity() {
		$this->markTestIncomplete();
		$anders1 = new Person(1);
		$anders1->delete();
		$this->setExpectedException('\Glucose\Exceptions\User\EntityDeletedException', 'This entity has been deleted. You can no longer instantiate it.');
		$anders2 = new Person(1);
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