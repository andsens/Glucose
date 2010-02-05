<?php
use \Glucose\Model as Model;
require_once 'TableComparisonTestCase.class.php';
require_once 'Components/Models/Country.class.php';
require_once 'Components/Models/City.class.php';
require_once 'Components/Models/Person.class.php';
require_once 'Components/Models/User.class.php';

class EqualityTest extends TableComparisonTestCase {
	
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
	
	protected function tearDown() {
		self::$mysqli->query('ROLLBACK;');
	}
}
?>