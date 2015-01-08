<?php
<?php

namespace BB\Models;

class Data {	
	/**
	 * @var int Unique identifier
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
	 *  @var array List of PDO connections
	 */
	protected static $connections = array();
	
	protected static init( $cn, $db ) {
		if ( isset( $connections[$cn] ) && is_object( self::$connections[$cn] ) ) {
			return;
		}
		
		self::$connections[$cn] = new \PDO( $db );
		self::$connections[$cn]->setAttribute( \PDO::ATTR_TIMEOUT, 5 );
		self::$connections[$cn]->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		self::$connections[$cn]->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC );
	}
	
	protected static getDb( $db ) {
		$cn = md5( $db );
		
		self::init( $cn, $db );
		return self::$connections[$cn];
	}
	
	/**
	 * Add parameters to conditional IN/NOT IN ( x,y,z ) query
	 */
	protected static function addParams( 
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
	protected static function setParams( 
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
						return "$field = :$field";
					}, $columns );
				return implode( ', ', $v );
		}
	}
	
	/**
	 * Multiple table select with optional aliases
	 */
	protected static multiSelect( $fields ) {
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
	
	/**
	 * Composite field for multi-column aggregate data from a single table
	 * E.G. label,term1|label,term2 format
	 * 
	 * @param string $table Parent database table
	 * @param array $fields List of columns to aggregate from parent table
	 */
	protected static function aggregateField( 
		$table, 
		$fields = array()
	) {
		if ( empty( $fields ) ) {
			return '';
		}
		
		$params = $table . '.' . implode( "||','||{$table}.", $fields );
		return "GROUP_CONCAT( {$params}, '|' ) AS {$field}";
	}
	
	/**
	 * Converts label,term1|label,term2 format into
	 * label (term1, term2) format
	 * 
	 * @param string $labels Unformatted string from database
	 * @returns array
	 */
	protected static function parseAggregate( $labels ) {
		/**
		 * taxonomy("tags", "categories", "forum" etc...),
		 * label("computers", "programming", "tech" etc...)
		 */
		$params	= array();
		$taxos	= explode( '|', $labels );
		
		foreach( $taxos as $t ) {
			if ( empty( $t ) ) { continue; }
			
			$tx = explode( ',', $t );
			if ( empty( $tx ) ) { continue; }
			
			/**
			 * Do we have an array for this taxonomy label already?
			 * If not, create it
			 */
			if ( !isset( $params[$tx[0]] ) ) {
				$params[$tx[0]] = array();
			}
			
			if ( isset( $tx[1] ) ) {
				$params[$tx[0]][] = $tx[1];
			}
		}
		
		return $params;
	}	
}
