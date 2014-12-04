<?php
/**
 * Post flush resource intensive operations queue
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.1
 */
namespace Microthread;

class Queue {
	private static $tasks = array();
	
	public function __construct() {}
	
	public static function init() {
		register_shutdown_function(
			"Microthread\Queue::execute"
		);
	}
	
	public static function register() {
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
	 * This ensures all subsequent "complete" tasks are done after the content 
	 * has been sent to the user.
	 */
	public static function complete() {
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		} else {
			flush();
		}
	}
}
