<?php
spl_autoload_register(function($class) {
	$file = dirname(__FILE__).'/../'.str_replace('\\', '/', $class).'.class.php';
	if(file_exists($file)) {
		require_once($file);
		return true;
	} else {
		return false;
	}
});
?>