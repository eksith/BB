<?php
/**
 * Forum path->flow router
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.2
 */
namespace BB\Auth;

class Router extends \BB\Events\Event {

	/**
	 * @var array Substitute markers for path regular expressions
	 */
	public static $pathMarkers	= array(
		'*'		=> '(?P<all>.+?)',
		':id'		=> '(?P<id>[1-9][0-9]*)',
		':key'		=> '(?P<key>[0-9a-z]*)',
		':num'		=> '(?P<num>[0-9]*)',
		':auth'		=> '(?P<auth>[0-9a-f\.]*)',
		':act'		=> '(?P<act>read|edit|delete|flag|lock)',
		':tag'		=> '(?P<tag>[\pL\pN\s_,-]{3,100})',
		':page'		=> '(?P<page>[1-9][0-9]*)',
		':search'	=> '(?P<search>[\pL\pN\s_,.-\\/]{1,120})',
		':vote'		=> '(?P<vote>up|down)'
	);

	public function __construct( $map = array() ) {
		if ( empty( $map ) ) {
			return;
		}

		$this->route( $map );
	}

	public function route( $map ) {
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
		$found	= false;

		foreach ( $map as $pattern => $sendTo ) {
			/**
			 * Regex formatting clean up
			 */
			$regex = str_replace( '.', '\.', $pattern );
			$regex = str_replace( $k, $v, $regex );
			$regex = '@^/' . $regex . '/?$@i';

			if ( preg_match( $regex, $path, $matches ) ) {
				$found		= true;
				$args		= $this->filterMatches(
							$matches
						);

				$this->selectSend( $sendTo, $args );
				break;
			}
		}

		$this->notify( $args );
		if ( !$found ) {
			$this->notFound();
		}
	}

	private function selectSend( $sendTo, &$args ) {
		if ( is_callable( $sendTo, true ) ) {
			call_user_func_array( $sendTo, $args );
		} else {
			if ( is_array( $sendTo ) ) {
				foreach( $sendTo as $send ) {
					$this->load( $send, $args );
				}
			} else {
				$this->load( $sendTo, $args );
			}
		}
	}

	private function load( $sendto, &$args ) {
		$flow = explode( '/', $sendTo );
		if ( count( $flow ) <= 1 ) {
			$this->notFound();
		}
		$ctrl	= new $flow[0];
		$ctrl->bind( $ctrl, $flow[1] );
		$ctrl->addState(
			'method',
			strtolower( $_SERVER['REQUEST_METHOD'] )
		);
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
		//require( TEMPLATES . '_notfound.php' );
		die('Not found');
	}
}
