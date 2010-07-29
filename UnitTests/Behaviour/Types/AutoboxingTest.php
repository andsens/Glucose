<?php
require_once 'PHPUnit/Framework.php';

use Glucose\Column;
class AutoboxingTest extends PHPUnit_Framework_TestCase {
	
	
	protected function setUp() {
	}
	
	/*
	 * echo 'inRange(-129, 255, signed)                                      : '.var_export(inRange(-129, 255, false), true)."\n";
echo 'inRange(-127, 255, signed)                                      : '.var_export(inRange(-127, 255, false), true)."\n";
echo 'inRange(-127, 255, unsigned)                                    : '.var_export(inRange(-127, 255, true), true)."\n";
echo 'inRange(-127, 16777215, unsigned)                               : '.var_export(inRange(-127, 16777215, true), true)."\n";
echo 'inRange(16777214, 16777215, signed)                             : '.var_export(inRange(16777214, 16777215, false), true)."\n";
echo 'inRange(18446744073709551615, 18446744073709551615, unsigned)   : '.var_export(inRange(18446744073709551615, 18446744073709551615, true), true)."\n";

	
function inRange($value, $range, $unsigned) {
	$min = $unsigned?0:-$range/2-0.5;
	$max = $unsigned?$range:$range/2-0.5;
	return $min < $value && $value < $max;
}


	 */
	
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
