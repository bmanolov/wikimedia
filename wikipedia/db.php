<?php
function pageNameForDb($pageName) {
	return str_replace(' ', '_', $pageName);
}
function pageNameFromDb($pageName) {
	return str_replace('_', ' ', $pageName);
}

$configFile = __DIR__.'/config.ini';
if (!file_exists($configFile)) {
	die("Config file '$configFile' does not exist.");
}
$config = parse_ini_file($configFile, true);

$dsn = "mysql:dbname={$config['db']['dbname']};host={$config['db']['host']};charset=UTF8";
return new PDO($dsn, $config['db']['user'], $config['db']['password']);
