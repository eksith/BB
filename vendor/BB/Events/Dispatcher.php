<?php

namespace BB\Events;
/**
 * The Dispatcher class provides a container for storing and dispatching
 * events. Modifications have been added to trigger specific methods (events)
 * by name as opposed to forcing the usage of update(). The singleton pattern
 * was also removed in addition to adding methods to override the default usage
 * of __call().
 *
 * Based on the original code:
 * http://forrst.com/posts/PHP_Event_handling-5Ke
 *
 * Ideas for scaling in the cloud:
 * http://www.slideshare.net/beberlei/towards-the-cloud-eventdriven-architectures-in-php
 *
 * @author 	Thomas RAMBAUD
 * @author	Corey Ballou
 * @author	Eksith Rodrigo <reksith at gmail.com>
 * @version 1.1
 * @access 	public
 */
class Dispatcher {
	
	private static $handlers	= array();
	private static $loaded		= array();
	
	/**
	 * Default constructor.
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct() { }
	
	/**
	 * Determine the total number of handlers.
	 *
	 * @access	public
	 * @return	int
	 */
	public function count() {
		return count( self::$handlers );
	}
	
	/**
	 * Check if a handler has already been added
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function hasHandler( $name ) {
		if ( isset( self::$handlers[$name] ) ) {
			return true;
		}

		return false;
	}
	
	/**
	 * Add a new handler by name.
	 *
	 * @access	public
	 * @param	string	$name
	 * @param	mixed	$args
	 * @return	Event
	 */
	public function addHandler( $name, $args = null ) {
		if ( $this->hasHandler( $name ) ) {
			return;
		}
		self::$handlers[$name] = $args;
	}
	
	/**
	 * Retrieve an event by name. If one does not exist, it will be created
	 * on the fly.
	 *
	 * @access	public
	 * @param	string	$name
	 * @return	Event|null
	 */
	public function getHandler( $name ) {
		return isset( self::$handlers[$name] )? self::$handlers[$name] : null;
	}
	
	/**
	 * Retrieves all handlers.
	 *
	 * @access	public
	 * @return	array
	 */
	public function getAll() {
		return self::$handlers;
	}
	
	/**
	 * Trigger a specific handler. Returns the event for monitoring status.
	 *
	 * @access	public
	 * @param	string	$name
	 * @param	mixed	$data	The data to pass to the triggered event(s)
	 * @return	void
	 */
	public function trigger( $name, $data ) {
		if ( $handler = self::getHandler( $name ) ) {
			$handler->notify( $data );
		}
	}
	
	/**
	 * Remove a handler by name.
	 *
	 * @access	public
	 * @param	string	$name
	 * @return	bool
	 */
	public function removeHandler( $name ) {
		if ( $this->hasHandler( $name ) ) {
			unset( self::$handlers[$name] );
			return true;
		}
		return false;
	}
	
	/**
	 * Retrieve the names of all current handlers.
	 *
	 * @access	public
	 * @return	array
	 */
	public function getNames() {
		return array_keys( self::$handlers );
	}
	
	/**
	 * Load and dispatch handle events
	 *
	 * @access	public
	 */
	public function dispatch() {
		foreach ( self::$handlers as $event => $args ) {
			$class	= 'BB\\'. $event
			
			if ( null == $args ) {
				self::$loaded[] = new $class();
			} else {
				self::$loaded[] = new $class( $args );
			}
		}
	}
}
