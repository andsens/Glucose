<?php
class MySQLErrorException extends MySQLException {
	public static function findClass(mysqli $mysqli) {
		if(1000 <= $mysqli->errno && $mysqli->errno < 2000) {
			$exception = MySQLServerErrorException::findClass($mysqli);
		} elseif($mysqli->errno >= 2000) {
			$exception = MySQLClientErrorException::findClass($mysqli);
		} else {
			$exception = new MySQLErrorException($mysqli->error, $mysqli->errno);
		}
		return $exception;
	}
}
?>