#!/usr/bin/php
<?php
chdir(__DIR__);
require_once 'Console/CommandLine.php';
$console = Console_CommandLine::fromXmlFile('Glucose/cli.xml');
require_once 'autoloader.inc.php';
use Glucose\CommandLine;
try {
	$result = $console->parse();
	CommandLine::main($result);
} catch (Exception $e) {
	$console->displayError($e->getMessage());
}