<?php
use \Glucose\Model as Model;
require_once 'Models/Country.class.php';
require_once 'Models/City.class.php';
require_once 'Models/Person.class.php';
require_once 'Models/User.class.php';

class TransparentConcurrencyTest extends TableComparisonTestCase {
	
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
	
	public function test_P_InstanceJoin() {
		$andsens1 = new User(1);
		$andsens2 = new User(1);
		$andsens1->nickname = 'somethingelse';
		$this->assertEquals('somethingelse', $andsens2->nickname);
	}
	
	public function test_P_InitByInstanceJoin() {
		$aarhus = City::initByCountryAndPostalCode(2, 8000);
		$aarhus->name = 'Dublin';
		$dublin = new City(1);
		$this->assertEquals('Dublin', $dublin->name);
	}
	
	public function test_N_NoChangeOnCollision() {
		$aarhus = new City(1);
		$hamburg = new City(2);
		try {
			$aarhus->id = 2;
		} catch(\Glucose\Exceptions\User\EntityCollisionException $e) { }
		$this->assertEquals(1, $aarhus->id);
	}
	
	public function test_N_DBConsolidationForCollisionDetection() {
		$aarhus = new City(1);
		$this->setExpectedException('\Glucose\Exceptions\User\EntityCollisionException', 'Your changes collide with the unique values of an existing entity.');
		$aarhus->id = 4;
	}
	
	protected function tearDown() {
		self::$mysqli->query('ROLLBACK;');
	}
}
?>