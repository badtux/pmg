<?php
class DbMySQLi {
	private static $_instance;

	private function __construct() {

    }

    public static function getInstance() {
        if(!(self::$_instance instanceof mysqli) ) {
			$mysqli = new mysqli(__db_mysql_host, __db_mysql_user, __db_mysql_password, 'pdna');
			if (!$mysqli->connect_errno) {
				$mysqli->set_charset('utf8');
				self::$_instance = $mysqli;
			}
			else {
				throw new Exception('Failed to connect to MySQL: ' . $mysqli->connect_error);
			}
        }
        return self::$_instance;
	}
}
?>