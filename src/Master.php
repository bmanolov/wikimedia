<?php
class Master {

	public static function createDb() {
		$config = self::readConfig();
		$dbc = $config['db'];
		return new Db($dbc['host'], $dbc['dbname'], $dbc['user'], $dbc['password']);
	}

	private static function readConfig() {
		$configFile = realpath(__DIR__.'/../config/config.ini');
		if (!file_exists($configFile)) {
			throw new \RuntimeException("Config file '$configFile' does not exist.");
		}
		return parse_ini_file($configFile, true);
	}
}
