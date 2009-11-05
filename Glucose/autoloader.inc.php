<?php
function __autoload($class) { require_once(dirname(__FILE__).'/../'.str_replace('\\', '/', $class).'.class.php'); }
?>