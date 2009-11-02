<?php
require_once 'PHPUnit/Framework.php';

require_once 'ModelTest.php';
require_once 'InstanceTest.php';

class FrameworkTestSuite extends PHPUnit_Framework_TestSuite {
	public static function suite() {
		return new FrameworkTestSuite('Model Framework Test Suite');
	}			protected function setUp() {
		$this->addTestSuite('ModelTest');
		$this->addTestSuite('InstanceTest');
	}			protected function tearDown() {
	}
}
?>