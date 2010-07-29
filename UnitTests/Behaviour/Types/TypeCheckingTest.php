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
	
	public function test_P_unsetNotNullFieldWithNotNullAsDefault() {
	}
	
	public function test_N_unsetNotNullFieldWithNullAsDefault() {
		$anders = new Person(1);
		unset($anders->email);
		$this->assertNull($anders->email);
	}
	
	protected function tearDown() {
		self::$mysqli->query('ROLLBACK;');
	}
}