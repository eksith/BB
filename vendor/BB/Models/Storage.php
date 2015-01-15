<?php

namespace BB\Models;

class Storage {
	const EPOCH	= 1421163159;
	
	/**
	 * @var array Database connections
	 */
	protected static $cxn = array();
	
	public static function getDb( $db ) {
		$cn = md5( $db );
		if ( isset( self::$cxn[$cn] ) is_object( self::$cxn[$cn] ) ) {
			return;
		}
		self::$cxn[$cn] = new \PDO( $db );
		self::$cxn[$cn]->setAttribute( \PDO::ATTR_TIMEOUT, 5 );
		self::$cxn[$cn]->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		self::$cxn[$cn]->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC );
		
		return self::$cxn[$cn];
	}
	
	public static genId() {
		list( $usec, $sec, $m, $d ) = gettimeofday();
		$k = 0;
		$e = $sec - self::EPOCH;
		while ( $k < 9 || $k > 100 ) { // Compensate for 32 bit abnormality in mt_rand
			$k = mt_rand( 10, 99 );
		}
		return $e + $k;
	}
	
	public static function addParams( &$stmt, &$params ) {
		foreach( $params as $k => &$v ) {
			$stmt->bindParam( ":$k", $v );
		}
	}
	
	public static function find( 
		$cxn, 
		$sql, 
		$params, 
		$type, 
		$model = null, 
		&$populate = null 
	) {
		$db	= self::getDb( $cxn );
		$stmt	= $db->prepare( $sql );
		
		self::addParams( $stmt, $params );
		$rows	= $stmt->execute();
		
		switch( $type ) {
			case 'single' :
				if ( $model ) {
					return $stmt->fetchAll( \PDO::FETCH_CLASS, $model )[0];
				}
				return $stmt->fetchAll( \PDO::FETCH_OBJECT )[0];
				
			class 'multiple' :
				if ( !is_object( $populate ) ) {
					if ( $model ) {
						return $stmt->fetchAll( \PDO::FETCH_CLASS, $model );
					}
					return $stmt->fetchAll( \PDO::FETCH_OBJECT );
				}
				if ( $model ) {
					$stmt->setFetchMode( \PDO::FETCH_CLASS, $model );
				}
				while ( $row = $stmt->fetch() ) {
					$populate->add( $row );
				}
				return true;
				
			case 'rows' :
				return $rows;
			default:
				return ( $rows ) ? true : false;
		}
	}
	
	public static function edit( $cxn, $table, $params, $where ) {
		$sql	= "UPDATE $table SET " . self::sqlParams( $params, 'update');
		
		foreach( $where as $selector => $fields ) {
			if ( is_array( $fields ) ) {
				$sql	.= self::concat( $selector, $fields, $params );
			} else {
				$sql			.= " $fields = :$fields";
				$params[$selector]	= $fields; 
			}
		}
		
		
		$db	= self::getDb( $cxn );
		$stmt	= $db->prepare( $sql );
		
		
		self::addParams( $stmt, $params );
		$rows	= $stmt->execute();
		
		if ( $rows > 0 ) {
			return true;
		}
		return false;
	}
	
	public static function put( $cxn, $table, $params, $id = true ) {
		if ( !preg_match('/[^a-z\_\.]/i', $table ) ) {
			throw new Exception( 'Invalid table name' );
		}
		$sql	= "INSERT INTO $table ( " . self::sqlParams( $params, 'select' ) . 
				') VALUES ( ' . self::sqlParams( $params, 'insert' ) . ')';
		
		$db	= self::getDb( $cxn );
		$stmt	= $db->prepare( $sql );
		
		self::addParams( $stmt, $params );
		if ( $stmt->execute() ) {
			return $id ? $db->lastInsertId() : true;
		}
		return false;
	}
	
	protected static concat( $type, $fields, &$params ) {
		if ( !preg_match('/^(and|or)$/i', $type ) ) {
			throw new Exception( 'Invalid concat selector' );
		}
		$sql	= ' ';
		foreach ( $where as $field => $value ) {
			$sql			.= "$filed = :$field $type ";
			$params[$field]	= $value;
		}
		
		return rtrim( $sql, " $type " );
	}
	
	/**
	 * Add parameters to conditional IN/NOT IN ( x,y,z ) query
	 */
	public static function inParams( 
		$t, 
		&$values, 
		&$params	= array(), 
		&$in		= '' 
	) {
		$vc = count( $values );
		for ( $i = 0; $i < $vc; $i++ ) {
			$in			= $in . ":v{$i},";
			$params["v{$i}"]	= array( $values[$i], $t );
		}
		
		$in = rtrim( $in, ',' );
	}
	
	/**
	 * Prepares parameters for SELECT, UPDATE or INSERT SQL statements.
	 * 
	 * E.G. For INSERT
	 * :name, :email, :password etc...
	 * 
	 * For UPDATE or DELETE
	 * name = :name, email = :email, password = :password etc...
	 */
	public static function sqlParams( 
		$fields		= array(), 
		$mode		= 'select', 
		$table		= '' 
	) {
		$columns = is_array( $fields ) ? 
				array_keys( $fields ) : 
				array_map( 'trim', explode( ',', $fields )
			);
		
		switch( $mode ) {
			case 'select':
				return implode( ', ', $columns );
				
			case 'insert':
				return ':' . implode( ', :', $columns );
			
			case 'update':
			case 'delete':
				$v = array_map( 
					function( $field ) use ( $table, $fields ) {
						if ( empty( $field ) ) { 
							return '';
						}
						if ( empty( $table ) ) {
							
						}
						return "$field = :$field";
					}, $columns );
				return implode( ', ', $v );
		}
	}
	
	public static select( $fields ) {
		$sql  = 'SELECT ';
		foreach( $fields as $table => $params ) {
			foreach( $params as $field ) {
				if ( is_array( $field ) ) {
					$sql .= "{$table}.{$field[0]} AS {$field[1]},";
				} else {
					$sql .= "{$table}.{$field} AS {$field},";
				}
			}
		}
		
		return rtrim( $sql, ',' );
	}
}
