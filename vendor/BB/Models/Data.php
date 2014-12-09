<?php
namespace BB\Models;

abstract class Data implements \SplObserver {
	public $id		= 0;
	
	public $created_at;
	
	public $updated_at;
	
	public $status;
	
	protected static $db = null;
	
	abstract public static function find( $filter = array() );
	
	abstract public function update( \SplSubject $subject );
	
	public static function init() {
		if ( is_object( self::$db ) ) {
			return;
		}
		
		self::$db = new \PDO( CONN );
		self::$db->setAttribute( \PDO::ATTR_TIMEOUT, 5 );
		self::$db->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		self::$db->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC );
	}
	
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
	
	protected static function edit( $table, $params, $where ) {
		$sql	= self::updateQuery( $table, $params, $where );
		$params = self::parametize( $params );
		
		self::init();
		$stmt	= self::$db->prepare( $sql . ';' );
		return $stmt->execute( $params );
	}
	
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
