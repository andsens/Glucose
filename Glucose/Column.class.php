<?php
/**
 * Corresponds to a column in a table.
 * This class holds information about a specific column in a table.
 * @author andsens
 * @package glucose
 *
 * @property-read string $name Column name
 * @property-read string $type MySQL type of the column
 * @property-read string $statementType Prepared statement type
 * @property-read int $maxLength Maximum length of the value
 * @property-read bool $notNull Wether the column cannot be null
 * @property-read mixed $default Default value
 */
namespace Glucose;
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
	public function __construct($name, $type = 'int', $maxLength = null, $notNull = false, $default = null, $extra = '') {
		$this->name = $name;
		$this->type = strtolower($type);
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
			case 'statementType':
				return $this->getStatementType();
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
	 * Computes the prepared statement type based on the MySQL type.
	 * @return The type for a prepared statement
	 */
	private function getStatementType() {
		switch($this->type) {
			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'int':
			case 'bigint':
				return 'i';
			case 'real':
			case 'double':
			case 'float':
			case 'decimal':
				return 'd';
			case 'tinyblob':
			case 'mediumblob':
			case 'blob':
			case 'longblob':
				return 'b';
			default:
				return 's';
		}
	}
	
	public static function createHash(array $columnNames) {
		$compoundHash = '';
		foreach($columnNames as $columnName)
			$compoundHash .= sha1($columnName);
		return sha1($compoundHash);
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
?>