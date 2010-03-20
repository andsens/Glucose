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
	
	public function test_P_ConstructWithCorrectTypes() {
		$arhus = new City(1);
	}
	
	public function test_P_ConstructWithWrongTypes() {
		$this->setExpectedException('', '');
		$arhus = new City('1');
	}
	
	public function test_P_AssignIntegerToIntegerField() {
		$arhus = new City(1);
		$arhus->postalCode = 7000;
	}
	
	public function test_N_AssignStringToIntegerField() {
		$arhus = new City(1);
		$this->setExpectedException('', '');
		$arhus->postalCode = '7000';
	}
	
	public function test_N_AssignFloatToIntegerField() {
		$arhus = new City(1);
		$this->setExpectedException('', '');
		$arhus->postalCode = 7000.00;
	}
	
	public function test_P_InitWithCorrectTypes() {
		$arhus = City::initByCountryAndPostalCode(2, 8000);
		$this->assertNotNull($arhus);
	}
	
	public function test_N_InitWithWrongTypes() {
		$this->setExpectedException('', '');
		$arhus = City::initByCountryAndPostalCode('2', 8000);
	}
	
	public function test_P_BatchChangeWithCorrectTypes() {
		$arhus = new City(1);
		$arhus->setCountryAndPostalCode(2, 7000);
	}
	
	public function test_N_BatchChangeWithWrongTypes() {
		$arhus = new City(1);
		$this->setExpectedException('', '');
		$arhus->setCountryAndPostalCode(2, '7000');
	}
	
	protected function tearDown() {
		self::$mysqli->query('ROLLBACK;');
	}
}
?>