<?php
spl_autoload_register(function($className) {
	$file = __DIR__.'/../'.str_replace('\\', '/', $className).'.class.php';
	if(file_exists($file)) {
		require_once($file);
		return true;
	} else {
		return false;
	}
});