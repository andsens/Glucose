<?php
/**
 * Corresponds to a column in a table.
 * This class holds information about a specific column in a table.
 * @author andsens
 * @package Glucose
 *
 * @property-read string $name Column name
 * @property-read string $type MySQL type of the column
 * @property-read string $statementType Prepared statement type
 * @property-read int $maxLength Maximum length of the value
 * @property-read bool $notNull Wether the column cannot be null
 * @property-read mixed $default Default value
 */
namespace Glucose;
use \Glucose\Exceptions\User as E;
class Column {
	/**
	 * Name of the column
	 * @var string
	 */
	private $name;
	
	/**
	 * MySQL type of the column
	 * @var string
	 */
	private $type;
	
	private $unsigned;
	
	private $statementType;
	
	/**
	 * Maximum length of the column
	 * @var int
	 */
	private $maxLength;
	
	/**
	 * Wether this column cannot be null
	 * @var bool
	 */
	private $notNull;
	
	/**
	 * Default value of the field
	 * @var mixed
	 */
	private $default;
	
	private $isAutoIncrement = false;
	
	private $onUpdateCurrentTimestamp = false;
	private $defaultCurrentTimestamp = false;
	
	/**
	 * Constructs the column.
	 * @param string $name Column name
	 * @param string $type MySQL type of the column
	 * @param int $maxLength Maximum length of the value
	 * @param bool $notNull Wether the column cannot be null
	 * @param mixed $default Default value
	 */
	public function __construct($name, $columnType, $maxLength, $notNull = false, $default = null, $extra = '') {
		$this->name = $name;
		$this->parseType($columnType);
		$this->maxLength = $maxLength;
		$this->notNull = $notNull;
		$this->default = $default;
		if(strtolower($extra) == 'auto_increment') {
			$this->isAutoIncrement = true;
		}
		if($this->type == 'timestamp') {
			$this->onUpdateCurrentTimestamp = strtolower($extra) == 'on update current_timestamp';
			$this->defaultCurrentTimestamp = strtolower($this->default) == 'current_timestamp';
		}
	}
	
	private function parseType($columnType) {
		if(preg_match('/^([a-z]+)(\([^\)]+\))?( unsigned)?$/', $columnType, $matches) != 1)
			throw new \Exception($columnType); // TODO: Make an exception for this
		$this->type = $matches[1];
		$this->unsigned = array_key_exists(3, $matches);
		switch($this->type) {
			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'int':
			case 'bigint':
				$this->statementType = 'i';
				break;
			case 'real':
			case 'double':
			case 'float':
			case 'decimal':
				$this->statementType = 'd';
				break;
			case 'tinyblob':
			case 'mediumblob':
			case 'blob':
			case 'longblob':
				$this->statementType = 'b';
				break;
			default:
				$this->statementType = 's';
				break;
		}
	}
	
	/**
	 * Returns various properties of the column.
	 * @ignore
	 * @param string $name Name of the property
	 * @return mixed Value of the property
	 */
	public function __get($name) {
		switch($name) {
			case 'name':
				return $this->name;
			case 'type':
				return $this->type;
			case 'unsigned':
				return $this->unsigned;
			case 'statementType':
				return $this->statementType;
			case 'maxLength':
				return $this->maxLength;
			case 'notNull':
				return $this->notNull;
			case 'default':
				return $this->default;
			case 'isAutoIncrement':
				return $this->isAutoIncrement;
			case 'onUpdateCurrentTimestamp':
				return $this->onUpdateCurrentTimestamp;
			case 'defaultCurrentTimestamp':
				return $this->defaultCurrentTimestamp;
		}
	}
	
	public function testValueType($value) {
		if($value === null && $this->notNull) {
			throw new E\Type\NotNullValueExpectedException('A not null field cannot be set to null.');
		}
		$type = gettype($value);
		if($type == 'array')
			throw new E\Type\TypeMismatchException('A string field can only hold a string.');
		switch($this->type) {
			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'int':
			case 'bigint':
			case 'real':
			case 'double':
//				if(gettype($value) != 'double')
//					throw new TypeMismatchException('A string field can only hold a string.');
			case 'float':
			case 'decimal':
			case 'tinyblob':
			case 'mediumblob':
			case 'blob':
			case 'longblob':
				break;
			default:
				break;
		}
	}
	
	public function testValueUnset() {
		// TODO: not entirely sure this is even possible with mysql
		if($this->default === null && $this->notNull) {
			throw new E\Type\NotNullValueExpectedException('A not null field with nothing as a default value cannot be unset.');
		}
	}
	
	/**
	 * Converts the column into a string.
	 * @ignore
	 * @return string Name of the column
	 */
	public function __toString() {
		return $this->name;
	}
}