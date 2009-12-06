<?php
$root = realpath(dirname(__FILE__).'/..').'/';
require_once $root.'Glucose/autoloader.inc.php';
$buildProperties = file_get_contents($root.'build.properties');
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
?>