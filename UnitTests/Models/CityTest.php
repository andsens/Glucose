<?php
require_once 'PHPUnit/Framework.php';

require_once 'ConcreteModelsTestSuite.php';
require_once 'City.class.php';
/**
 * Test class for City.
 * Generated by PHPUnit on 2009-04-20 at 13:21:47.
 */
class CityTest extends PHPUnit_Framework_TestCase
{

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @access protected
	 */
	protected function setUp() {
		ConcreteModelsTestSuite::$mysqli->query('START TRANSACTION;');
	}
		public function testObjectCreation() {
		$denmark = new Country(2);
		$this->assertEquals('Denmark', $denmark->name);
		$københavn = new City;
		$københavn->name = 'Købehavn';
		$københavn->country = $denmark->id;
		$københavn->postalCode = 1000;
		$this->assertNotNull($københavn->id);
		$københavnID = $københavn->id;
		unset($københavn);
		$københavn = new City($københavnID);
		$this->assertEquals('Købehavn', $københavn->name);
	}
		public function testObjectUpdate() {
		$århus = new City(1);
		$this->assertEquals('Århus', $århus->name);
		$this->assertEquals(8000, $århus->postalCode);
		$this->assertEquals(2, $århus->country);
		$århus->name = 'Andeby';
		$århus->postalCode = 1234;
		unset($århus);
		$andeby = new City(1);
		$this->assertEquals('Andeby', $andeby->name);
		$this->assertEquals(1234, $andeby->postalCode);
	}
	
//	public function testInitByPostalCodeAndCountry() {
//		$århus = new City;
//		$århus->country = 2;
//		$århus->postalCode = 8000;
//		$århus->init();
//		$this->assertEquals('Århus', $århus->name);
//	}
	
//	public function testUniqueKeyFinding() {
//		$frederiksberg = new City;
//		$frederiksberg->name = 'Frederiksberg';
//		$frederiksberg->country = 2;
//		$frederiksberg->postalCode = 2000;
//
//		$frederiksberg2 = new City;
//		$frederiksberg2->country = 2;
//		$frederiksberg2->postalCode = 2000;
//		$frederiksberg2->init();
//		$this->assertEquals('Frederiksberg', $frederiksberg2->name);
//	}
	
//	public function testInitInstanceJoining1() {
//		$city1 = new City(1);
//		$this->assertEquals('Århus', $city1->name);
//		$city2 = new City;
//		$city2->country = 2;
//		$city2->postalCode = 8000;
//		$city2->init();
//		$this->assertEquals('Århus', $city2->name);
//		$city2->name = 'København';
//		$this->assertEquals('København', $city1->name);
//	}
	
//	public function testInitInstanceJoining2() {
//		$city1 = new City(1);
//		$city1->id = 2;
//		$this->assertEquals('Andeby', $city1->name);
//		$city2 = new City;
//		$city2->country = 1;
//		$city2->postalCode = 1234;
//		$city2->init();
//		$this->assertEquals('Andeby', $city2->name);
//		$city2->name = 'Andebu';
//		$this->assertEquals('Andebu', $city1->name);
//		$city1->name = 'Andeby';
//	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @access protected
	 */
	protected function tearDown() {
		ConcreteModelsTestSuite::$mysqli->query('ROLLBACK;');
	}
}
?>
