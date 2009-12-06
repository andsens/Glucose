<?php
require_once 'PHPUnit/Framework.php';

abstract class TableComparisonTestCase extends PHPUnit_Framework_TestCase {
	
	private static $columnRetrievalStatement;
	
	/**
	 *
	 * @return mysqli
	 */
	abstract protected function getConnection();
	
	private final function prepareColumnRetrievalStatement() {
		$mysqli = $this->getConnection();
		$sql = <<<End
SELECT `columns`.`COLUMN_NAME`
FROM `information_schema`.`COLUMNS` columns
WHERE `columns`.`TABLE_SCHEMA` = ?
AND `columns`.`TABLE_NAME` = ?
ORDER BY `columns`.`ORDINAL_POSITION`
End;
		self::$columnRetrievalStatement = $mysqli->prepare($sql);
	}
	
	protected function assertTablesEqual($expectedDB, $expectedTable, $actualDB, $actualTable) {
		if(!isset(self::$columnRetrievalStatement))
			$this->prepareColumnRetrievalStatement();
		$statement = self::$columnRetrievalStatement;
		
		$statement->bind_param('ss', $expectedDB, $expectedTable);
		$statement->bind_result($columnName);
		$statement->execute();
		$expectedColumns = array();
		while($statement->fetch())
			$expectedColumns[] = $columnName;
		$statement->free_result();
		
		$statement->bind_param('ss', $actualDB, $actualTable);
		$statement->bind_result($columnName);
		$statement->execute();
		$actualColumns = array();
		while($statement->fetch())
			$actualColumns[] = $columnName;
		$statement->free_result();
		
		if($expectedColumns !== $actualColumns)
			throw new Exception('The tables you want to compare do not match.');
		
		$columns = '`'.implode('`, `', $actualColumns).'`';
		$comparisonSQL = <<<End
SELECT Origin, COUNT(*) AS 'Rows', {$columns}
FROM (
	SELECT *, 'expected' AS 'Origin'
	FROM `{$expectedDB}`.`{$expectedTable}`
	UNION
	SELECT *, 'actual' AS 'Origin'
	FROM `{$actualDB}`.`{$actualTable}`
) comparison
GROUP BY {$columns}
HAVING `rows` != 2
End;
		$columns = $expectedColumns;
		array_unshift($columns, 'Rows');
		array_unshift($columns, 'Origin');
		$mysqli = $this->getConnection();
		$result = $mysqli->query($comparisonSQL);
		$rows = array();
		$maxFieldLengths = array();
		foreach($columns as $column)
			$maxFieldLengths[$column] = strlen($column);
		while($row = $result->fetch_array()) {
			foreach($columns as $column)
				$maxFieldLengths[$column] = max($maxFieldLengths[$column], strlen($row[$column]));
			$rows[] = $row;
		}
		if(count($rows) > 0) {
			$maxLineLength = 80;
			while(array_sum($maxFieldLengths)+(count($expectedColumns)-1)*3 > $maxLineLength) {
				$largest = end($columns);
				foreach($maxFieldLengths as $key => $maxFieldLength)
					if($maxFieldLengths[$largest] < $maxFieldLength)
						$largest = $key;
				$maxFieldLengths[$largest]--;
			}
			$headers = array();
			foreach($columns as $column) {
				if(strlen($column) > $maxFieldLengths[$column]) {
					$headers[$column] = substr($column, 0, max(0, $maxFieldLengths[$column]-3)).'...';
				} else {
					$headers[$column] = $column.str_repeat(' ', $maxFieldLengths[$column]-strlen($column));
				}
			}
			$header = implode(' | ', $headers);
			$excessRows = array();
			foreach($rows as $row) {
				$newRow = array();
				foreach($columns as $column) {
					if(strlen($row[$column]) > $maxFieldLengths[$column]) {
						$newRow[$column] = substr($row[$column], 0, max(0, $maxFieldLengths[$column]-3)).'...';
					} else {
						$newRow[$column] = $row[$column].str_repeat(' ', $maxFieldLengths[$column]-strlen($row[$column]));
					}
				}
				$excessRows[] = implode(' | ', $newRow);
			}
			$excess = implode("\n	", $excessRows);
			$failureDescription = <<<End
Failed asserting that `{$expectedDB}`.`{$expectedTable}` and `{$actualDB}`.`{$actualTable}` are equal.
Excess rows:
	{$header}
	{$excess}
End;
			$e = new PHPUnit_Framework_ExpectationFailedException($failureDescription, NULL);
//			$this->testResult->addFailure($this, $e);
			throw $e;
		}
	}
	
}
?>