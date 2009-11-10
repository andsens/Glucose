<?php
$root = realpath(dirname(__FILE__).'/..').'/';
require_once $root.'Glucose/autoloader.inc.php';
$buildProperties = file_get_contents($root.'build.properties');
$properties = array('hostname' => 'mysql.hostname',
                    'username' => 'mysql.username',
                    'password' => 'mysql.password',
                    'schema'   => 'tests.schema');
$matches = array();
foreach($properties as $name => $property) {
	preg_match('/'.str_replace('.', '\.', $property).'\s*=\s*(\w+)/', $buildProperties, $match);
	$matches[$name] = $match[1];
}
$mysqli = new MySQLi($matches['hostname'], $matches['username'], $matches['password']);
$mysqli->select_db($matches['schema']);
Glucose\Model::connect($mysqli);
?>