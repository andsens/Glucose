<?php
namespace Glucose;
class CommandLine {
	
	/**
	 *
	 * Enter description here ...
	 * @var \mysqli
	 */
	private $mysqli;
	
	public function main(array $arguments) {
		
	}
	
	private function cacheTableInfo($databaseName) {
		$this->mysqli->select_db($databaseName);
		if(self::$mysqli->errno > 0)
			throw MySQLErrorException::findClass(self::$mysqli);
		$tablesInsert = <<<End
INSERT INTO `tables` (`name`) (
	SELECT `TABLE_NAME`
	FROM `information_schema`.`TABLES`
	WHERE `TABLE_SCHEMA` = '$databaseName')
End;
		$columnsInsert = <<<End
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
		$constraintsInsert = <<<End
INSERT INTO `constraints` (`name`, `table`) (
	SELECT `CONSTRAINT_NAME`, `TABLE_NAME`
	FROM `information_schema`.`TABLE_CONSTRAINTS`
	WHERE `TABLE_SCHEMA` = '$databaseName'
	AND `CONSTRAINT_NAME` != 'PRIMARY')
End;
		$uniqueKeyConstraintsInsert = <<<End
INSERT INTO `unique_key_constraints` (`name`) (
	SELECT `CONSTRAINT_NAME`
	FROM `information_schema`.`TABLE_CONSTRAINTS`
	WHERE `TABLE_SCHEMA` = '$databaseName'
	AND `CONSTRAINT_TYPE` = 'UNIQUE')
End;
		$uniqueColumnsInsert = <<<End
INSERT INTO `unique_columns` (`column`, `table`, `constraint`) (
	SELECT `column_usage`.`COLUMN_NAME`, `column_usage`.`TABLE_NAME`, `column_usage`.`CONSTRAINT_NAME`
	FROM `information_schema`.`KEY_COLUMN_USAGE` column_usage
	JOIN `information_schema`.`TABLE_CONSTRAINTS` constraints
		ON `constraints`.`CONSTRAINT_SCHEMA` = `column_usage`.`CONSTRAINT_SCHEMA`
		AND `constraints`.`CONSTRAINT_NAME` = `column_usage`.`CONSTRAINT_NAME`
	WHERE `constraints`.`CONSTRAINT_SCHEMA` = '$databaseName'
	AND `constraints`.`CONSTRAINT_TYPE` = 'UNIQUE')
End;
		$foreignKeyConstraintsInsert = <<<End
INSERT INTO `foreign_key_constraints` (`name`, `on_update`, `on_delete`) (
	SELECT `CONSTRAINT_NAME`, `UPDATE_RULE`, `DELETE_RULE`
	FROM `information_schema`.`REFERENTIAL_CONSTRAINTS`
	WHERE `CONSTRAINT_SCHEMA` = '$databaseName')
End;
		$uniqueColumnsInsert = <<<End
INSERT INTO `references` (
		`source_column`, `source_table`, `destination_column`,
		`destination_table`, `constraint`) (
	SELECT
		`column_usage`.`TABLE_NAME`, `column_usage`.`COLUMN_NAME`, `column_usage`.`REFERENCED_TABLE_NAME`,
		`column_usage`.`REFERENCED_COLUMN_NAME`, `column_usage`.`CONSTRAINT_NAME`
	FROM `information_schema`.`KEY_COLUMN_USAGE` column_usage
	JOIN `information_schema`.`REFERENTIAL_CONSTRAINTS` constraints
		ON `constraints`.`CONSTRAINT_SCHEMA` = `column_usage`.`CONSTRAINT_SCHEMA`
		AND `constraints`.`CONSTRAINT_NAME` = `column_usage`.`CONSTRAINT_NAME`
	WHERE `constraints`.`CONSTRAINT_SCHEMA` = '$databaseName')
End;
		
		
		
		
		
		
		$query = <<<End
SELECT
	`columns`.`COLUMN_NAME`, `columns`.`ORDINAL_POSITION`, `columns`.`COLUMN_DEFAULT`, `columns`.`IS_NULLABLE`,
	`columns`.`COLUMN_TYPE`, `columns`.`CHARACTER_MAXIMUM_LENGTH`, `columns`.`EXTRA`,
	`table_constraints`.`CONSTRAINT_TYPE`, `table_constraints`.`CONSTRAINT_NAME`,
	`column_usage`.`REFERENCED_TABLE_NAME`, `column_usage`.`REFERENCED_COLUMN_NAME`,
	`referential_constraints`.`UPDATE_RULE`, `referential_constraints`.`DELETE_RULE`,
	`column_references`.`CONSTRAINT_NAME`, `column_references`.`TABLE_NAME`, `column_references`.`COLUMN_NAME`
FROM `information_schema`.`COLUMNS` columns
LEFT JOIN `information_schema`.`KEY_COLUMN_USAGE` column_usage
	ON `column_usage`.`TABLE_SCHEMA` = `columns`.`TABLE_SCHEMA`
	AND `column_usage`.`TABLE_NAME` = `columns`.`TABLE_NAME`
	AND `column_usage`.`COLUMN_NAME` = `columns`.`COLUMN_NAME`
LEFT JOIN `information_schema`.`TABLE_CONSTRAINTS` table_constraints
	ON `table_constraints`.`TABLE_SCHEMA` = `column_usage`.`TABLE_SCHEMA`
	AND `table_constraints`.`TABLE_NAME` = `column_usage`.`TABLE_NAME`
	AND `table_constraints`.`CONSTRAINT_SCHEMA` = `column_usage`.`CONSTRAINT_SCHEMA`
	AND `table_constraints`.`CONSTRAINT_NAME` = `column_usage`.`CONSTRAINT_NAME`
LEFT JOIN `information_schema`.`REFERENTIAL_CONSTRAINTS` referential_constraints
	ON `referential_constraints`.`TABLE_NAME` = `column_usage`.`TABLE_NAME`
	AND `referential_constraints`.`CONSTRAINT_SCHEMA` = `column_usage`.`CONSTRAINT_SCHEMA`
	AND `referential_constraints`.`CONSTRAINT_NAME` = `column_usage`.`CONSTRAINT_NAME`
LEFT JOIN `information_schema`.`KEY_COLUMN_USAGE` column_references
	ON `column_references`.`REFERENCED_TABLE_SCHEMA` = `columns`.`TABLE_SCHEMA`
	AND `column_references`.`REFERENCED_TABLE_NAME` = `columns`.`TABLE_NAME`
	AND `column_references`.`REFERENCED_COLUMN_NAME` = `columns`.`COLUMN_NAME`
WHERE `columns`.`TABLE_SCHEMA` = ?
AND `columns`.`TABLE_NAME` = ?
ORDER BY `columns`.`ORDINAL_POSITION`
End;
	}
}