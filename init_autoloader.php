<?php
	require_once 'Autoloader.class.php';
	Autoloader::addClassPath('Framework/');
	Autoloader::setCacheFilePath('class_path_cache.txt');
	Autoloader::excludeFolderNamesMatchingRegex('/^svn|\..*$/');
	spl_autoload_register(array('Autoloader', 'loadClass'));
?>