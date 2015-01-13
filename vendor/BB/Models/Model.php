<?php
/**
 * Model Event
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.1
 */
namespace BB\Models;

class Model extends \BB\Events\Listener {
	
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
	 * Composite field catcher
	 */
	public function __set( $name, $value ) {
		if ( false !== strtr( 'aggregate_', $name ) ) {
			$name = substr( $name, strpos( $name, '_' ) );
			$this->{$name}	= self::parseAggregate( $value );
		}
	}
	
	/**
	 * Composite field for multi-column aggregate data from a single table
	 * E.G. label,term1|label,term2 format
	 * 
	 * @param string $table Parent database table
	 * @param array $fields List of columns to aggregate from parent table
	 * @param string $marker Aggregate field marker
	 */
	protected static function aggregateField( 
		$table, 
		$fields = array(),
		$marker
	) {
		if ( empty( $fields ) ) {
			return '';
		}
		
		$params = $table . '.' . implode( "||','||{$table}.", $fields );
		return "GROUP_CONCAT( {$params}, '|' ) AS aggregate_{$field}";
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
