<?php
require_once 'PHPUnit/Framework.php';

require_once 'Framework/FrameworkTestSuite.php';
require_once 'Models/ConcreteModelsTestSuite.php';

class ModelTestSuite extends PHPUnit_Framework_TestSuite
{
	public static function suite() {
		return new ModelTestSuite('Model Test Suite');
	}			protected function setUp() {
		$this->addTestSuite('FrameworkTestSuite');
		$this->addTestSuite('ConcreteModelsTestSuite');
	}			protected function tearDown() {
	}
}
?>