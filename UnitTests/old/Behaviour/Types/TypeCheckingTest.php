<?php
require_once 'Models/Country.class.php';
require_once 'Models/City.class.php';
require_once 'Models/Person.class.php';
require_once 'Models/User.class.php';
require_once 'PHPUnit/Framework.php';

class TypeCheckingTest extends PHPUnit_Framework_TestCase {
	
	private static $mysqli;
	
	protected function setUp() {
		self::$mysqli = $GLOBALS['mysqli'];
		
		self::$mysqli->query('START TRANSACTION;');
	}
	
	protected function getConnection() {
		return self::$mysqli;
	}
	
	public function test_P_ConstructWithInvalidIntString() {
		$this->setExpectedException('', '');
		// Should become int(0)
		$this->setExpectedException('Glucose\Exceptions\User\UndefinedPrimaryKeyException',
			'The primary key you specified does not exist in the table.');
		$arhus = new City('abc1sdgvs');
	}
	
	public function test_P_AssignFloatToIntegerField() {
		$arhus = new City(1);
		$arhus->postalCode = 7000.00;
		$this->assertEquals(gettype($arhus->postalCode), 'integer');
		$this->assertEquals($arhus->postalCode, 7000);
	}
	
	public function test_P_AssignStringToIntegerField() {
		$arhus = new City(1);
		$arhus->postalCode = '7000';
		$this->assertEquals(gettype($arhus->postalCode), 'integer');
		$this->assertEquals($arhus->postalCode, 7000);
	}
	
	public function test_N_unsetNotNullFieldWithNotNullAsDefault() {
		$this->setExpectedException('Glucose\Exceptions\User\Type\NotNullValueExpectedException', 'A not null field without a default value cannot be unset.');
		$anders = new Person(1);
		unset($anders->id);
	}
	
	public function test_N_assignNullToNotNullField() {
		$this->setExpectedException('Glucose\Exceptions\User\Type\NotNullValueExpectedException', 'A not null field cannot be set to null.');
		$anders = new Person(1);
		$anders->id = null;
	}
	
	public function test_N_assignNullToNotNullFieldViaSetMacro() {
		$this->setExpectedException('Glucose\Exceptions\User\Type\NotNullValueExpectedException', 'A not null field cannot be set to null.');
		$helsinki = new City(3);
		$helsinki->setCountryAndPostalCode(8, null);
	}
	
	public function test_P_unsetNotNullFieldWithNullAsDefault() {
		$anders = new Person(1);
		unset($anders->email);
		$this->assertNull($anders->email);
	}
	
	protected function tearDown() {
		self::$mysqli->query('ROLLBACK;');
	}
}