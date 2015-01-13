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
	
	private $events = array();
	
	/**
	 * Default constructor.
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct() { }
	
	/**
	 * Determine the total number of events.
	 *
	 * @access	public
	 * @return	int
	 */
	public function count() {
		return count( $this->events );
	}
	
	/**
	 * Add a new event by name.
	 *
	 * @access	public
	 * @param	string	$name
	 * @param	mixed	$method
	 * @return	Event
	 */
	public function add( $name, $method = null ) {
		if ( !isset( $this->events[$name] ) ) {
			$this->events[$name] = new Event( $method );
		}
	}
	
	/**
	 * Retrieve an event by name. If one does not exist, it will be created
	 * on the fly.
	 *
	 * @access	public
	 * @param	string	$name
	 * @return	Event
	 */
	public function get( $name ) {
		if ( !isset( $this->events[$name] ) ) {
			return $this->add( $name );
		}
		return $this->events[$name];
	}
	
	/**
	 * Retrieves all events.
	 *
	 * @access	public
	 * @return	array
	 */
	public function getAll() {
		return $this->events;
	}
	
	/**
	 * Trigger an event. Returns the event for monitoring status.
	 *
	 * @access	public
	 * @param	string	$name
	 * @param	mixed	$data	The data to pass to the triggered event(s)
	 * @return	void
	 */
	public function trigger( $name, $data ) {
		$this->get( $name )->notify( $data );
	}
	
	/**
	 * Remove an event by name.
	 *
	 * @access	public
	 * @param	string	$name
	 * @return	bool
	 */
	public function remove( $name ) {
		if ( isset( $this->events[$name] ) ) {
			unset( $this->events[$name] );
			return true;
		}
		return false;
	}
	
	/**
	 * Retrieve the names of all current events.
	 *
	 * @access	public
	 * @return	array
	 */
	public function getNames() {
		return array_keys( $this->events );
	}
}
