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
use \Glucose\Exceptions\Table as TE;
class Column {
	/**
	 * Name of the column
	 * @var string
	 */
	private $name;
	
	private $position;
	
	/**
	 * MySQL type of the column
	 * @var string
	 */
	private $type;
	
	private $columnType;
	
	private $unsigned;
	
	private $statementType;
	
	/**
	 * Maximum length of the column
	 * @var int
	 */
	private $maxLength;
	
	private $zerofill;
	
	private $paddingLength;
	
	/**
	 * Wether this column cannot be null
	 * @var bool
	 */
	private $notNull;
	
	/**
	 * Default value of the field
	 * @var mixed
	 */
	private $default = null;
	
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
	public function __construct($name, $position, $columnType, $maxLength, $notNull = false, $default = null, $extra = '') {
		$this->name = $name;
		$this->position = $position;
		$this->columnType = $columnType;
		$this->parseType($columnType);
		$this->maxLength = $maxLength;
		$this->notNull = $notNull;
		if($default !== null)
			$this->default = $this->autobox($default);
		if(strtolower($extra) == 'auto_increment')
			$this->isAutoIncrement = true;
		if($this->type == 'timestamp') {
			$this->onUpdateCurrentTimestamp = strtolower($extra) == 'on update current_timestamp';
			$this->defaultCurrentTimestamp = strtolower($this->default) == 'current_timestamp';
		}
	}
	
	private function parseType() {
		if(preg_match('/^([a-z]+)(\([^\)]+\))?( unsigned)?( zerofill)?$/', $this->columnType, $matches) != 1)
			throw new TE\ColumnTypeParsingException("Unable to parse the columnType '$this->columnType' for the field `$this->name`");
		$this->type = $matches[1];
		$this->paddingLength = array_key_exists(4, $matches)?$matches[2]:0;
		$this->unsigned = array_key_exists(3, $matches);
		$this->zerofill = array_key_exists(4, $matches);
		switch($this->type) {
			case 'tinyint': case 'smallint': case 'mediumint': case 'int': case 'bigint':
				$this->statementType = 'i';
				break;
			case 'real': case 'double': case 'float': case 'decimal':
				$this->statementType = 'd';
				break;
			case 'tinyblob': case 'mediumblob': case 'blob': case 'longblob':
				$this->statementType = 'b';
				break;
			default:
				$this->statementType = 's';
				break;
		}
	}
	
	public function autobox($value) {
		$type = gettype($value);
		if($type == 'NULL')
			if($this->notNull)
				throw new E\Type\NotNullValueExpectedException("The field $this->exceptionName cannot be set to null.");
			else
				return $value;
		if($type == 'array' || $type == 'resource' || $type == 'object')
			throw new E\Type\InvalidTypeException("You can not assign an $type to a field.");
		switch($this->type) {
			case 'tinyint':
				$value = intval($value);
				$this->checkRange($value, 256);
				return $value;
			case 'smallint':
				$value = intval($value);
				$this->checkRange($value, 65536);
				return $value;
			case 'mediumint':
				$value = intval($value);
				$this->checkRange($value, 16777216);
				return $value;
			case 'int':
				$value = $this->unsigned?floatval($value):intval($value);
				$this->checkRange($value, 4294967296);
				return $value;
			case 'bigint':
				$value = floatval($value);
				$this->checkRange($value, 18446744073709551616);
				return $value;
			case 'real':
			case 'double':
			case 'float':
			case 'decimal':
				$value = floatval($value);
				return $value;
			case 'tinyblob':
			case 'mediumblob':
			case 'blob':
			case 'longblob':
				$this->checkLength($value);
				return $value;
			default:
				$value = strval($value);
				$this->checkLength($value);
				return $value;
		}
	}
	
	private function checkRange($value, $range) {
		$min = $this->unsigned?0:-$range/2;
		$max = $this->unsigned?$range:$range/2-1;
		if($min > $value || $value > $max)
			throw new E\Type\OutOfRangeException("The value for the field $this->exceptionName is out of range.");
	}
	
	private function checkLength($value) {
		if(strlen($value) > $this->maxLength)
			throw new E\Type\CharacterLengthException("The value for the field $this->exceptionName is too long.");
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
			case 'exceptionName':
				return "`$this->name` $this->columnType";
			case 'position':
				return $this->position;
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
	
	/**
	 * Converts the column into a string.
	 * @ignore
	 * @return string Name of the column
	 */
	public function __toString() {
		return $this->name;
	}
}