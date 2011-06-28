#!/usr/bin/php
<?php
namespace Glucose;

use MySQLi_Classes\Connector;

use MySQLi_Classes\Queries\Query;
use MySQLi_Classes\Exceptions\ErrorException;
use MySQLi_Classes\Queries\SelectQuery;

chdir(__DIR__);
require_once 'Console/CommandLine.php';
require_once 'autoloader.inc.php';
require_once '../MySQLi_Classes/MySQLi_Classes/autoloader.inc.php';
$console = \Console_CommandLine::fromXmlFile('cli.xml');
try {
	$result = $console->parse();
	CommandLine::main($result);
} catch (\Exception $e) {
	$console->displayError($e->getMessage());
}

class CommandLine {
	
	public static function main(\Console_CommandLine_Result $result) {
		$commandLine = new self($result);
	}
	
	/**
	 *
	 * Enter description here ...
	 * @var \mysqli
	 */
	private $mysqli;
	
	private function __construct(\Console_CommandLine_Result $result) {
		$username = $result->options['username'];
		$password = $result->options['password'];
		$hostname = $result->options['hostname'];
		$port = $result->options['port'];
		$socket = $result->options['socket'];
		if($username === null)
			$username = $_SERVER['USER'];
		if($password === null)
			$password = $this->prompt_silent('Password: ');
		if($password == '')
			$password = null;
		if($hostname === null)
			$hostname = 'localhost';
		if($port === null)
			$port = 3306;
		$this->mysqli = @new \MySQLi(
			$hostname,
			$username,
			$password,
			null,
			$port,
			$socket);
		Connector::connect($this->mysqli);
		switch($result->command_name) {
			case 'cache-tables':
				$this->cacheTableInfo($result->command->args['schema']);
				break;
		}
	}
	
	private function cacheTableInfo($databaseName) {
		$this->mysqli->select_db($databaseName);
		if($this->mysqli->errno > 0)
			throw ErrorException::findClass($this->mysqli, __LINE__);
		
		$tables = new SelectQuery("SELECT `TABLE_NAME`
FROM `information_schema`.`TABLES`
WHERE `TABLE_SCHEMA` = '$databaseName'");
		echo "Found ".count($tables)." tables...\n";
		$queries['tablesTruncate'] = 'TRUNCATE `tables`';
		$queries['tablesInsert'] = <<<End
INSERT INTO `tables` (`name`) (
	SELECT `TABLE_NAME`
	FROM `information_schema`.`TABLES`
	WHERE `TABLE_SCHEMA` = '$databaseName')
End;
		$queries['columnsInsert'] = <<<End
INSERT INTO `columns` (
	`name`, `table`, `position`,
	`primary`, `default`, `is_nullable`,
	`column_type`, `maximum_length`,
	`on_update_current_timestamp`,
	`auto_increment`) (
	SELECT
		`columns`.`COLUMN_NAME`, `columns`.`TABLE_NAME`, `columns`.`ORDINAL_POSITION`,
		IF(`column_usage`.`CONSTRAINT_NAME` = 'PRIMARY', 1, 0), `columns`.`COLUMN_DEFAULT`, `columns`.`IS_NULLABLE` = 'YES',
		`columns`.`COLUMN_TYPE`, `columns`.`CHARACTER_MAXIMUM_LENGTH`,
		IF(LOWER(`columns`.`EXTRA`) = 'on update current_timestamp', 'yes', NULL),
		IF(LOWER(`columns`.`EXTRA`) = 'auto_increment', 'yes', NULL)
	FROM `information_schema`.`COLUMNS`
	LEFT JOIN `information_schema`.`KEY_COLUMN_USAGE` column_usage
		ON `column_usage`.`TABLE_SCHEMA` = `columns`.`TABLE_SCHEMA`
		AND `column_usage`.`TABLE_NAME` = `columns`.`TABLE_NAME`
		AND `column_usage`.`COLUMN_NAME` = `columns`.`COLUMN_NAME`
		AND `column_usage`.`CONSTRAINT_NAME` = 'PRIMARY'
	WHERE `columns`.`TABLE_SCHEMA` = '$databaseName'
	ORDER BY `columns`.`TABLE_NAME`, `columns`.`ORDINAL_POSITION`)
End;
		$queries['constraintsInsert'] = <<<End
INSERT INTO `constraints` (`name`, `table`) (
	SELECT `CONSTRAINT_NAME`, `TABLE_NAME`
	FROM `information_schema`.`TABLE_CONSTRAINTS`
	WHERE `TABLE_SCHEMA` = '$databaseName'
	AND `CONSTRAINT_NAME` != 'PRIMARY')
End;
		$queries['uniqueKeyConstraintsInsert'] = <<<End
INSERT INTO `unique_key_constraints` (`name`) (
	SELECT `CONSTRAINT_NAME`
	FROM `information_schema`.`TABLE_CONSTRAINTS`
	WHERE `TABLE_SCHEMA` = '$databaseName'
	AND `CONSTRAINT_TYPE` = 'UNIQUE')
End;
		$queries['uniqueColumnsInsert'] = <<<End
INSERT INTO `unique_columns` (`column`, `table`, `constraint`) (
	SELECT `column_usage`.`COLUMN_NAME`, `column_usage`.`TABLE_NAME`, `column_usage`.`CONSTRAINT_NAME`
	FROM `information_schema`.`KEY_COLUMN_USAGE` column_usage
	JOIN `information_schema`.`TABLE_CONSTRAINTS` constraints
		ON `constraints`.`CONSTRAINT_SCHEMA` = `column_usage`.`CONSTRAINT_SCHEMA`
		AND `constraints`.`CONSTRAINT_NAME` = `column_usage`.`CONSTRAINT_NAME`
	WHERE `constraints`.`CONSTRAINT_SCHEMA` = '$databaseName'
	AND `constraints`.`CONSTRAINT_TYPE` = 'UNIQUE')
End;
		$queries['foreignKeyConstraintsInsert'] = <<<End
INSERT INTO `foreign_key_constraints` (`name`, `on_update`, `on_delete`) (
	SELECT `CONSTRAINT_NAME`, `UPDATE_RULE`, `DELETE_RULE`
	FROM `information_schema`.`REFERENTIAL_CONSTRAINTS`
	WHERE `CONSTRAINT_SCHEMA` = '$databaseName')
End;
		$queries['foreignKeyColumnsInsert'] = <<<End
INSERT INTO `foreign_key_columns` (
		`source_column`, `source_table`,
		`destination_column`, `destination_table`,
		`constraint`) (
	SELECT
		`column_usage`.`COLUMN_NAME`, `column_usage`.`TABLE_NAME`,
		`column_usage`.`REFERENCED_COLUMN_NAME`, `column_usage`.`REFERENCED_TABLE_NAME`,
		`column_usage`.`CONSTRAINT_NAME`
	FROM `information_schema`.`KEY_COLUMN_USAGE` column_usage
	JOIN `information_schema`.`REFERENTIAL_CONSTRAINTS` constraints
		ON `constraints`.`CONSTRAINT_SCHEMA` = `column_usage`.`CONSTRAINT_SCHEMA`
		AND `constraints`.`CONSTRAINT_NAME` = `column_usage`.`CONSTRAINT_NAME`
	WHERE `constraints`.`CONSTRAINT_SCHEMA` = '$databaseName')
End;
		foreach($queries as $query) {
			$this->mysqli->query($query);
			if($this->mysqli->errno > 0)
				throw ErrorException::findClass($this->mysqli, __LINE__);
		}
		
	}
	
	function prompt_silent($prompt = "Enter Password:") {
		if (preg_match('/^win/i', PHP_OS)) {
			$vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
			file_put_contents(
				$vbscript, 'wscript.echo(InputBox("'
				. addslashes($prompt)
				. '", "", "password here"))');
			$command = "cscript //nologo " . escapeshellarg($vbscript);
			$password = rtrim(shell_exec($command));
			unlink($vbscript);
			return $password;
		} else {
			$command = "/usr/bin/env bash -c 'echo OK'";
			if (rtrim(shell_exec($command)) !== 'OK') {
				trigger_error("Can't invoke bash");
				return;
			}
			$command = "/usr/bin/env bash -c 'read -s -p \""
				. addslashes($prompt)
				. "\" mypassword && echo \$mypassword'";
			$password = rtrim(shell_exec($command));
			echo "\n";
			return $password;
		}
	}
}