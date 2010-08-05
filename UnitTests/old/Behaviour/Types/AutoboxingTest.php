<?php
require_once 'PHPUnit/Framework.php';

use Glucose\Column;
class AutoboxingTest extends PHPUnit_Framework_TestCase {
	
	
	protected function setUp() {
	}
	
	public function test_P_tinyIntSignedRange() {
		$column = new Column('', 'tinyint(1)', null);
		$value = $column->autobox(-128.00);
		$this->assertEquals(gettype($value), 'integer');
		$this->assertEquals($value, -128);
		$value = $column->autobox(127);
		$this->assertEquals(gettype($value), 'integer');
		$this->assertEquals($value, 127);
	}
	
	public function test_N_tinyIntSignedRange() {
		$column = new Column('', 'tinyint(1)', null);
		$this->setExpectedException('\Glucose\Exceptions\User\Type\OutOfRangeException', 'The value is out of range.');
		$this->assertEquals(gettype($column->autobox(128)), 'integer');
	}
	
	public function test_P_tinyIntUnsignedRange() {
		$column = new Column('', 'tinyint(1) unsigned', null);
		$value = $column->autobox(255);
		$this->assertEquals(gettype($value), 'integer');
		$this->assertEquals($value, 255);
	}
	
	public function test_N_tinyIntUnsignedRange() {
		$column = new Column('', 'tinyint(1) unsigned', null);
		$this->setExpectedException('\Glucose\Exceptions\User\Type\OutOfRangeException', 'The value is out of range.');
		$this->assertEquals(gettype($column->autobox(-1)), 'integer');
	}
	
	public function test_P_stringFromInt() {
		$column = new Column('', 'varchar(255)', 255);
		$value = $column->autobox(1234567);
		$this->assertEquals(gettype($value), 'string');
		$this->assertEquals($value, '1234567');
	}
	
	public function test_N_stringTooLong() {
		$this->setExpectedException('\Glucose\Exceptions\User\Type\CharacterLengthException', 'The value is too long.');
		$column = new Column('', 'varchar(25)', 25);
		$value = $column->autobox('A string that is too long.');
		$this->assertEquals(gettype($value), 'string');
	}
	
	protected function tearDown() {
	}
}
