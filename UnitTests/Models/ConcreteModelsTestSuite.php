<?php
require_once 'PHPUnit/Framework.php';

require_once 'CountryTest.php';
require_once 'CityTest.php';
require_once 'CustomerTest.php';

class ConcreteModelsTestSuite extends PHPUnit_Framework_TestSuite {
	
	public static $mysqli;
		public static function suite() {
		return new ConcreteModelsTestSuite('Concrete Models Test Suite');
	}	protected function setUp() {
		include 'localSettings.inc.php';
		self::$mysqli = new MySQLi($dbAccess['hostname'], $dbAccess['username'], $dbAccess['password']);
		exec($mysqlExecutable.' -h'.$dbAccess['hostname'].' -u'.$dbAccess['username'].' -p'.$dbAccess['password'].' < '.realpath('UnitTests/Database/setup.sql'));
		self::$mysqli->select_db($dbAccess['defaultSchema']);		Glucose\Model::connect(self::$mysqli);
		$this->addTestSuite('CountryTest');
		$this->addTestSuite('CityTest');
		$this->addTestSuite('CustomerTest');
	}	protected function tearDown() {
//		$this->mysqli->query('DROP SCHEMA `'.ConcreteModelsTestSuite::TEST_SCHEMA_NAME.'`');
	}
}
?>