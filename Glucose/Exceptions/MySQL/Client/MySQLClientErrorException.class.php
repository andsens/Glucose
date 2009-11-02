<?php
/**
 *
 * @author andsens
 * @package model
 * @subpackage model.exceptions.mysql
 */
namespace Glucose\Exceptions\MySQL\Client;
class MySQLClientErrorException extends \Glucose\Exceptions\MySQL\MySQLErrorException {
	/**
	 * Currently simply returns a new Instance of itself
	 * @todo Extend this class
	 * @param mysqli $mysqli The MySQLi instance where the error occurred
	 * @return MySQLClientErrorException
	 */
	public static function findClass(\mysqli $mysqli) {
		return new MySQLClientErrorException($mysqli->error, $mysqli->errno);
	}
}
?>