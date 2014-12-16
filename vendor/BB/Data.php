<?php
namespace BB;

class Data {
	public $id		= 0;
	
	protected static $db = null;
	
	public static function init() {
		if ( is_object( self::$db ) ) {
			return;
		}
		
		self::$db = new \PDO( CONN );
		self::$db->setAttribute( \PDO::ATTR_TIMEOUT, 5 );
		self::$db->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		self::$db->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC );
	}
}
