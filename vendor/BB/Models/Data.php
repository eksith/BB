<?php
namespace BB\Models;

abstract class Data implements \SplObserver {
	
	/**#@+
	 * Common object properties
	 */
	
	/**
	 * @var Object id. Populated on creation or should be populated before editing
	 */
	public $id		= 0;
	
	/**
	 * @var Created date and time. Set by the database
	 */
	public $created_at;
	
	/**
	 * @var Last update date and time. Also set by the database
	 */
	public $updated_at;
	
	/**
	 * @var Treatment status
	 * @example -1 For pseudo delete
	 */
	public $status;
	
	/**
	 * @var Any errors during editing, creating
	 */
	public $errors		= array();
	
	/**#@-*/
	
	/**
	 * @var Data connection object (may be changed to connection array later)
	 */
	protected static $db = null;
	
	abstract public static function find( $filter = array() );
	
	abstract public function update( \SplSubject $subject );
	
	/**
	 * Connection initiator
	 */
	public static function init() {
		if ( is_object( self::$db ) ) {
			return;
		}
		
		self::$db = new \PDO( CONN );
		self::$db->setAttribute( \PDO::ATTR_TIMEOUT, 5 );
		self::$db->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		self::$db->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC );
	}
	
	/**
	 * Execute a query with parameters and fetch by selector options
	 */
	protected static function find( $sql, $params, $fetch = 'class' ) {
		$params = self::parametize( $params );
		
		self::init();
		$stmt	= self::$db->prepare( $sql );
		
		switch( $fetch ) {
			case 'class':
				$stmt->fetchMode( \PDO::FETCH_CLASS, get_called_class() );
				$stmt->execute( $params );
				return $stmt->fetch();
				
			case 'object':
				$stmt->fetchMode( \PDO::FETCH_OBJECT );
				$stmt->execute( $params );
				return $stmt->fetch();
				
			case 'classList':
				$stmt->execute( $params );
				return $stmt->fetchAll( \PDO::FETCH_CLASS, get_called_class() );
				
			case 'array':
				$stmt->execute( $params );
				return $stmt->fetchAll( \PDO::FETCH_ASSOC );
				
			case 'column':
				$stmt->execute( $params );
				return $stmt->fetchAll( \PDO::FETCH_COLUMN );
				
			default:
				$stmt->execute( $params );
				return $stmt->fetch();
		}
	}
	
	/**
	 * TODO: Select query
	 */
	protected static select( $table, $params, $where ) {
		
	}
	
	/**
	 * Single table update
	 */
	protected static function edit( $table, $params, $where ) {
		$sql	= self::updateQuery( $table, $params, $where );
		$params = self::parametize( $params );
		
		self::init();
		$stmt	= self::$db->prepare( $sql . ';' );
		return $stmt->execute( $params );
	}
	
	/**
	 * Single table insert
	 * @return bool|int Insert if requested or execute success
	 */
	protected static function put( $table, $params, $response = 'id' ) {
		$sql	= insertQuery( $table, $params );
		$params = self::parametize( $params );
		
		self::init();
		$stmt	= self::$db->prepare( $sql . ';' );
		if ( 'id' == $response ) {
			$stmt->execute( $params );
			return self::$db->lastInsertId();
		}
		
		return $stmt->execute( $params );
	}
	
	/**
	 * Single table delete
	 * @return bool Execute success
	 */
	protected static function delete( $table, $id, $permanant = false ) {
		if ( $permanant ) {
			$sql	= "DELETE FROM $table WHERE id = :i;";
		} else {
			$sql	= "UPDATE $table SET status = -1 WHERE id = :i;";
		}
		
		self::init();
		$stmt = self::$db->prepare( $sql );
		return $stmt->execute( array( ':i' => $id ) );
	}
	
	private static function selectQuery( $table, $fields, $where ) {
		$sql	= 'SELECT ' . implode( ',', $fields ) . " FROM $table";
		if ( empty( $where ) ) {
			return $sql;
		}
		
		$sql	.= ' WHERE';
		$keys	= array_keys( $where );
		
		foreach( $keys as $v ) {
			$sql .= " $v = :$v,";
		}
		return rtrim( $sql, ',' ) . ';';
	}
	
	private static function updateQuery( $table, &$params, $where ) {
		$sql	= "UPDATE $table SET ";
		$keys	= array_keys( $params );
		
		foreach( $keys as $v ) {
			$sql .= " $v = :$v,";
		}
		
		$sql	= rtrim( $sql, ',' );
		if ( !empty( $where ) ) {
			$sql .= self::whereQuery( $where, &$params );
		}
		
		return $sql . ';';
	}
	
	private static function whereQuery( $where, &$params ) {
		$sql = ' WHERE ';
		foreach ( $where as $k => $v ) {
			$sql			.=  " $k = :param_$k AND ";
			$params["param_$k"]	= $v;
		}
		
		return rtrim( $sql, ' AND ' );
	}
	
	private static function insertQuery( $table, $params ) {
		$sql	= "INSERT INTO $table ( ";
		$keys	= array_keys( $params );
		
		foreach( $keys as $v ) {
			$sql .= " $v,";
		}
		$sql	= rtrim( $sql, ',' ) . ' ) VALUES (';
		
		foreach( $keys as $v ) {
			$sql .= " :$v,";
		}
		return rtrim( $sql, ',' ) . ' )';
	}
	
	
	/**
	 * Converts an array of parameter=>values to :parameter=> values
	 */
	private static function parametize( $params ) {
		$keys = array_map( 
			function( $k ) {
				return ':' . $k;
			}, 
			array_keys( $params )
		);
		return array_combine( $keys, array_values( $params ) );
	}
}
