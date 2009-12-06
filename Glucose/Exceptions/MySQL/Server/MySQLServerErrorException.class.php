<?php
/**
 *
 * @author andsens
 * @package glucose
 * @subpackage glucose.exceptions.mysql
 */
namespace Glucose\Exceptions\MySQL\Server;
class MySQLServerErrorException extends \Glucose\Exceptions\MySQL\MySQLErrorException {
	/**
	 * Depending on the error code, this method returns a very specific MySQLException
	 * If no error code fits, it returns a new instance of itself
	 * @todo Add support for more error codes
	 * @param mysqli $mysqli The MySQLi instance where the error occurred
	 * @return MySQLServerErrorException
	 */
	public static function findClass(\mysqli $mysqli) {
		switch($mysqli->errno) {
			case 1048: $exception = new MySQLBadNullException($mysqli->error, $mysqli->errno); break;
			case 1054: $exception = new MySQLBadFieldException($mysqli->error, $mysqli->errno); break;
			case 1062: $exception = new MySQLDuplicateEntryException($mysqli->error, $mysqli->errno); break;
			default:   $exception = new MySQLUnknownErrorException($mysqli->error, $mysqli->errno); break;
		}
		return $exception;
	}
}
?>