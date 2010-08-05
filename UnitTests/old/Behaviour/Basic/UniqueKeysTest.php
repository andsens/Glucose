<?php
use \Glucose\Model as Model;
require_once 'Models/Country.class.php';
require_once 'Models/City.class.php';
require_once 'Models/Person.class.php';
require_once 'Models/User.class.php';
require_once 'Models/OverlappingUniqueKeys.class.php';

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
		$anders1 = new Person(1);
		$anders1->delete();
		$this->setExpectedException('\Glucose\Exceptions\User\EntityDeletedException', 'This entity has been deleted. You can no longer instantiate it.');
		$anders2 = new Person(1);
	}
	
	public function test_N_InitByWrongArgumentNumber() {
		$this->setExpectedException('\Glucose\Exceptions\User\InitializationArgumentException', 'The method \'initByCountryAndPostalCode\' was called with 1 arguments but requires 2.');
		City::initByCountryAndPostalCode(2);
	}
	
	public function test_N_InitByUndefinedMethod() {
		$this->setExpectedException('\Glucose\Exceptions\User\UndefinedMethodException', 'Call to undefined method \'initBySomeUndefinedMethod\'.');
		City::initBySomeUndefinedMethod(2);
	}
	
	public function test_P_canHaveTwoUniquesWithNull() {
		$mette = new Person;
		$mette->firstName = 'Mette';
		$mette->lastName = 'Larsen';
		$mette->address = 'Blah 2';
		$mette->city = 1;
		$mette->id;
		
		$anders = Person::initByEmail('anders@ingemann.de');
		$anders->email = null;
		unset($anders);
	}
	
	public function test_N_SingleChange() {
		$helsinki = new City(3);
		$this->setExpectedException('\Glucose\Exceptions\User\EntityCollisionException', 'Your changes collide with the unique values of an existing entity.');
		$helsinki->country = 8;
		$helsinki->postalCode = 10;
	}
	
	public function test_P_SingleChange() {
		$helsinki = new City(3);
		$helsinki->postalCode = 10;
		$helsinki->country = 8;
	}
	
	public function test_P_SingleChangeChangeBack() {
		$helsinki = new City(3);
		$oldCountry = $helsinki->country;
		$helsinki->country = 8;
		$helsinki->country = $oldCountry;
	}
	
	public function test_P_MacroChange() {
		$helsinki = new City(3);
		$helsinki->setCountryAndPostalCode(8, 10);
	}
	
	public function test_P_MacroChangeChangeBack() {
		// TODO: Not failing. It should! Right now, that is.
		// Maybe we should rely on the EntityEngine for checks like this instead?
		$helsinki = new City(3);
		$oldCountry = $helsinki->country;
		$oldPostalCode = $helsinki->postalCode;
		$helsinki->setCountryAndPostalCode(8, 10);
		$helsinki->setCountryAndPostalCode($oldCountry, $oldPostalCode);
	}
	
	public function test_N_OverlappingConstraintsCollision1() {
		$model1 = new OverlappingUniqueKeys(0, 1);
		$model2 = new OverlappingUniqueKeys(1, 2);
		$model1->column3 = 3;
		$this->setExpectedException('\Glucose\Exceptions\User\EntityCollisionException', 'Your changes collide with the unique values of an existing entity.');
		$model2->column2 = 1;
	}
	
	public function test_N_OverlappingConstraintsCollision2() {
		$model1 = new OverlappingUniqueKeys(0, 1);
		$model2 = new OverlappingUniqueKeys(1, 2);
		$model1->column3 = 3;
		unset($model1);
		$this->setExpectedException('\Glucose\Exceptions\User\EntityCollisionException', 'Your changes collide with the unique values of an existing entity.');
		$model2->column2 = 1;
	}
	
	public function test_N_OverlappingConstraintsCollisionWithMacro1() {
		$model1 = new OverlappingUniqueKeys(0, 1);
		$model2 = new OverlappingUniqueKeys(1, 2);
		$model1->setColumn2AndColumn3(1, 3);
		$this->setExpectedException('\Glucose\Exceptions\User\EntityCollisionException', 'Your changes collide with the unique values of an existing entity.');
		$model2->setColumn1AndColumn2(1, 1);
	}
	
	public function test_N_OverlappingConstraintsCollisionWithMacro2() {
		$model1 = new OverlappingUniqueKeys(0, 1);
		$model2 = new OverlappingUniqueKeys(1, 2);
		$model1->setColumn2AndColumn3(1, 3);
		unset($model1);
		$this->setExpectedException('\Glucose\Exceptions\User\EntityCollisionException', 'Your changes collide with the unique values of an existing entity.');
		$model2->setColumn1AndColumn2(1, 1);
	}
	
	protected function tearDown() {
		self::$mysqli->query('ROLLBACK;');
	}
}