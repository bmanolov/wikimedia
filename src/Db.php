<?php

class Db extends \PDO {

	public static function pageNameForDb($pageName) {
		return str_replace(' ', '_', $pageName);
	}

	public static function pageNameFromDb($pageName) {
		return str_replace('_', ' ', $pageName);
	}

	public function __construct($host, $dbname, $user, $password) {
		parent::__construct("mysql:dbname=$dbname;host=$host;charset=UTF8", $user, $password);
	}

}
