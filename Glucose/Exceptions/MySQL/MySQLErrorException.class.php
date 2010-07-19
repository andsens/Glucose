<?php
/**
 *
 * @author andsens
 * @package glucose
 * @subpackage glucose.exceptions.mysql
 */
namespace Glucose\Exceptions\MySQL;
class MySQLErrorException extends MySQLException {
	/**
	 * Depending on the error code, this method returns either a ServerException or a ClientException.
	 * If no error code fits, it returns a new instance of itself
	 * @param mysqli $mysqli The MySQLi instance where the error occurred
	 * @return MySQLErrorException
	 */
	public static function findClass(\mysqli $mysqli) {
		if(1000 <= $mysqli->errno && $mysqli->errno < 2000) {
			$exception = Server\MySQLServerErrorException::findClass($mysqli);
		} elseif($mysqli->errno >= 2000) {
			$exception = Client\MySQLClientErrorException::findClass($mysqli);
		} else {
			$exception = new MySQLErrorException($mysqli->error, $mysqli->errno);
		}
		return $exception;
	}
}