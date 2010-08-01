<?php
namespace Glucose\Statements;
use \Glucose\Exceptions\Table as E;
use \Glucose\Exceptions\MySQL\MySQLErrorException;
use \Glucose\Exceptions\MySQL\MySQLConnectionException;
abstract class Statement {
	
	private static $mysqli;
	
	/**
	 *
	 * Enter description here ...
	 * @var mysqli_stmt
	 */
	protected $statement;
	
	/**
	 *
	 * Enter description here ...
	 * @var string
	 */
	protected $types;
	
	public static function connect(\mysqli  $mysqli) {
		if($mysqli->connect_errno != 0)
			throw new MySQLConnectionException('The MySQLi instance is not connected to a database.');
		if($mysqli->server_version == '')
			throw new MySQLConnectionException('The MySQLi instance is not connected to a database.');
		if($mysqli->server_version < \Glucose\Table::REQUIRED_MYSQL_VERSION)
			throw new MySQLConnectionException('Glucose only works with MySQL version '.self::REQUIRED_MYSQL_VERSION.
			" or higher, the server you are trying to connect to is version $mysqli->server_version.");
		self::$mysqli = $mysqli;
	}
	
	public static function isConnected() {
		if(!isset(self::$mysqli))
			return false;
		return self::$mysqli->ping();
	}
	
	/**
	 *
	 * Enter description here ...
	 * @param string $query
	 * @param string $types
	 * @throws E\ParameterCountMismatchException
	 */
	public function __construct($query, $types) {
		$this->statement = self::$mysqli->prepare($query);
		if(self::$mysqli->errno > 0)
			throw MySQLErrorException::findClass(self::$mysqli);
		$this->types = $types;
		if($this->statement->param_count != strlen($types))
			throw new E\ParameterCountMismatchException('There is a mismatch between the number of statement types and parameters.');
	}
	
	public function bindAndExecute(array $values) {
		$statementValues = array($this->types);
		foreach($values as &$value)
			$statementValues[] = &$value;
		$noParams = count($values);
		if(strlen($types) != $noParams)
			throw new E\ParameterCountMismatchException('There is a mismatch between the number of statement types and parameters.');
		if($noParams > 0)
			call_user_func_array(array(&$this->statement, 'bind_param'), $statementValues);
		$statement->execute();
		if(self::$mysqli->errno > 0)
			throw MySQLErrorException::findClass(self::$mysqli);
	}
}