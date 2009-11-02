<?php
require_once 'PHPUnit/Framework.php';

require_once 'CountryTest.php';
require_once 'CityTest.php';
require_once 'CustomerTest.php';

class ConcreteModelsTestSuite extends PHPUnit_Framework_TestSuite {
	const TEST_SCHEMA_NAME = 'model_test_schema';
	private $mysqli;	public static function suite() {
		return new ConcreteModelsTestSuite('Concrete Models Test Suite');
	}	protected function setUp() {
		$dbHost = 'localhost';
		$dbUser = 'model';
		$dbPass = 'g45dKl39';
		$this->mysqli = new MySQLi($dbHost, $dbUser, $dbPass);
		exec('/usr/bin/mysql -h'.$dbHost.' -u'.$dbUser.' -p'.$dbPass.' < '.realpath('UnitTests/Database/setup.sql'));
		$this->mysqli->select_db(ConcreteModelsTestSuite::TEST_SCHEMA_NAME);				Model::connect($this->mysqli);
		$this->addTestSuite('CountryTest');
		$this->addTestSuite('CityTest');
		 $this->addTestSuite('CustomerTest');
	}	protected function tearDown() {
//		$this->mysqli->query('DROP SCHEMA `'.ConcreteModelsTestSuite::TEST_SCHEMA_NAME.'`');
	}
}
?>