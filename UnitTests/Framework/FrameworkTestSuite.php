<?php
require_once 'PHPUnit/Framework.php';

require_once 'EntityTest.php';

class FrameworkTestSuite extends PHPUnit_Framework_TestSuite {
	public static function suite() {
		return new FrameworkTestSuite('Model Framework Test Suite');
	}	protected function setUp() {
//		$this->addTestSuite('EntityTest');
	}	protected function tearDown() {
	}
}
?>