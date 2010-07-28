<?php
require_once __DIR__.'/autoloader.inc.php';
$buildProperties = file_get_contents(__DIR__.'/../build.properties');
$properties = array('hostname' => 'mysql.hostname',
                    'port'     => 'mysql.port',
                    'username' => 'mysql.username',
                    'password' => 'mysql.password',
                    'schema'   => 'test.schema',
                    'comparisonSchema' => 'test.schema.comparison');
$matches = array();
foreach($properties as $name => $property) {
	preg_match('/'.str_replace('.', '\.', $property).'\s*=\s*(\w+)/', $buildProperties, $match);
	$matches[$name] = $match[1];
}
$GLOBALS['mysqli'] = new MySQLi($matches['hostname'], $matches['username'], $matches['password'], $matches['schema'], $matches['port']);
$GLOBALS['mysqli']->set_charset("utf8");
$GLOBALS['schema'] = $matches['schema'];
$GLOBALS['comparisonSchema'] = $matches['comparisonSchema'];
Glucose\Model::connect($GLOBALS['mysqli']);

spl_autoload_register(function($className) {
	switch($className) {
		case 'TableComparisonTestCase':
			require_once __DIR__.'/TableComparisonTestCase.class.php';
			return true;
		default:
			return false;
	}
});