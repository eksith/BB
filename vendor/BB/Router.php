<?php
/**
 * Forum path->function router
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.2
 */
namespace BB;

class Router extends Observable {
	/**
	 * @var array Substitute markers for path regular expressions
	 */
	public static $pathMarkers	= array(
		'*'		=> '(?P<all>.+?)',
		':id'		=> '(?P<id>[1-9][0-9]*)',
		':key'		=> '(?P<key>[0-9a-z]*)',
		':num'		=> '(?P<num>[0-9]*)',
		':auth'		=> '(?P<auth>[0-9a-f\.]*)', // Was '(?P<auth>[0-9a-f\.]{20})'
		':act'		=> '(?P<act>read|edit|delete|flag|lock)',
		':tag'		=> '(?P<tag>[\pL\pN\s_,-]{3,100})',
		':page'		=> '(?P<page>[1-9][0-9]*)',
		':vote'		=> '(?P<vote>up|down)'
	);
	
	public function __construct( $map = array() ) {
		if ( empty( $map ) ) {
			return;
		}
		
		$k	= array_keys( self::$pathMarkers );
		$v	= array_values( self::$pathMarkers );
		
		/**
		 * Sort to ensure first match
		 */
		ksort( $map );
		$map	= array_reverse( $map, true );
		
		/**
		 * Request route data
		 */
		$path	= $_SERVER['REQUEST_URI'];
				
		/**
		 * Request arguments
		 */
		$args	= array();
		$routes	= array();
		foreach ( $map as $pattern => $sendTo ) {
			/**
			 * Regex formatting clean up
			 */
			$regex = str_replace( '.', '\.', $pattern );
			$regex = str_replace( $k, $v, $regex );
			$regex = '@^/' . $regex . '/?$@i';
			
			if ( preg_match( $regex, $path, $matches ) ) {
				$m		= filterMatches( $matches );
				$args[]		= $m;
				$routes[]	= $sendTo;
				
				// All match is special. We can keep going
				if ( isset( $m['all'] ) ) {
					continue;
				}
				break;
			}
		}
		
		if ( empty( $routes ) ) {
			$this->notFound();
		} else {
			$this->args = $args;
			foreach( $routes as $route ) {
				$this->route( $route );
			}
			$this->notify();
		}
	}
	
	private function route( $sendTo, $args ) {
		if ( is_callable( $sendTo, true ) ) {
			call_user_func_array( $sendTo, $args );
		} else {
			$observer = new 'Controllers\\' . $sendTo;
			$this->attach( $observer );
		}
	}
	
	private function filterMatches( $matches ) {
		$arr = array();
		foreach( $matches as $k => $v ) {
			if ( is_numeric( $k ) ) {
				continue;
			}
			$arr[$k] = $v;
		}
		
		return $arr;
	}
	
	private function notFound() {
		echo 'Couldn\'t find the page you\'re looking for';
		exit();
	}
}
