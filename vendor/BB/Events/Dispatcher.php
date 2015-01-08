<?php
/**
 * Event dispatcher and queue for time consuming operations
 * after content has been sent to the user
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.2
 */
namespace BB\Events;

class Dispatcher {
	private static $tasks = array();
	private static $events = array();

	public function __construct() {
		register_shutdown_function( "BB\Events\Dispatcher::execute" );
	}

	public static function hasEvent( $name ) {
		if ( isset( self::$events[$name] ) ) {
			return true;
		}

		return false;
	}

	public static function addEvent( $name, $args = null ) {
		if ( self::hasEvent( $name ) ) {
			return;
		}
		self::$events[$name] = $args;
	}

	public static function remove( $name ) {
		if ( self::hasEvent( $name ) ) {
			unset( self::$events[$name] );
			return true;
		}
		return false;
	}

	public static function getEvent( $name ) {
		if ( self::hasEvent( $name ) ) {
			return self::$events[$name];
		}
		return null;
	}

	public function trigger() {
		foreach ( self::$events as $event => $args ) {
			if ( null == $args ) {
				$class = new $event();
			} else {
				$class = new $event( $args );
			}
		}
	}

	public static function registerShutdown() {
		$task = func_get_args();
		if ( empty( $task ) ) {
			return;
		}

		if ( is_callable( $task[0] ) ) {
			self::$tasks[] = $task;
		}
	}

	/**
	 * Sequential Queue execution
	 */
	public static function execute() {
		// Set completing the request as the first task
		self::complete();
		foreach( self::$tasks as $args ) {
			$call = array_shift( $args );
			call_user_func_array( $call, $args );
		}
		// Testing:
		//echo 'True execution ' . round( microtime( true ) + START, 4 );
	}

	/**
	 * This ensures all subsequent "complete" tasks are done after
	 * the content has been sent to the user.
	 */
	public static function complete() {
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		} else {
			flush();
			ob_flush();
		}
	}
}
