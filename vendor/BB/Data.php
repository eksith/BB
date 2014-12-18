<?php
namespace BB;

class Data {
	
	/**
	 * @var int Unique identifyer
	 */
	public $id		= 0;
	
	/**
	 * @var int Content status
	 */
	public $status		= 0;
	
	/**
	 * @var date Created date (set by the database upon row add)
	 */
	public $created_at;
	
	/**
	 * @var date Last updated date (set by the database upon row update)
	 */
	public $updated_at;
	
	/**
	 * @var object PDO connection object
	 */
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
