<?php
class MySQLServerErrorException extends MySQLErrorException {
	public static function findClass(mysqli $mysqli) {
		$className;
		switch($mysqli->errno) {
			case 1048: $className = 'MySQLBadNullException'; break;
			case 1054: $className = 'MySQLBadFieldException'; break;
			default:
				$className = 'MySQLUnknownErrorException';
				break;
		}
		if(class_exists($className)) {
			$exception = new $className($mysqli->error, $mysqli->errno);
		} else {
			$exception = new MySQLServerErrorException($mysqli->error, $mysqli->errno);
		}
		return $exception;
	}
}
?>