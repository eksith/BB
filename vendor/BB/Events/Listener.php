<?php

namespace BB\Events;
/**
 * You can attach an EventListener to an event to be notified when a specific
 * event has occurred.
 *
 * @author 	Thomas RAMBAUD
 * @author	Eksith Rodrigo <reksith at gmail.com>
 * @version	1.1
 * @access 	public
 */
abstract class Listener implements \SplObserver {
	/**
	 * @var array Holds all states
	 */
	private $states = array();
	
	/**
	 * Returns all states.
	 *
	 * @access	public
	 * @return	void
	 */
	public function getAllStates() {
		return $this->states;
	}
	
	/**
	 * Adds a new state.
	 *
	 * @access	public
	 * @param	mixed	$state
	 * @param	int		$stateValue
	 * @return	void
	 */
	public function addState( $state, $stateValue = 1 ) {
		$this->states[$state] = $stateValue;
	}
	
	/**
	 * @Removes a state.
	 *
	 * @access	public
	 * @param	mixed	$state
	 * @return 	bool
	 */
	public function removeState( $state ) {
		if ( $this->hasState( $state ) ) {
			unset( $this->states[$state] );
			return true;
		}
		return false;
	}
	
	/**
	 * Checks if a given state exists.
	 *
	 * @access	public
	 * @param	mixed	$state
	 * @return	bool
	 */
	public function hasState( $state ) {
		return isset( $this->states[$state] );
	}
	
	/**
	 * Return searched state if it exists.
	 *
	 * @access	public
	 * @param	mixed	$state
	 * @return	bool
	 */
	public function getState( $state ) {
		return isset( $this->states[$state] ) 
			$this->states[$state] : null;
	}
	
	/**
	 * Implementation of SplObserver::update().
	 *
	 * @access	public
	 * @param	SplSubject	$subject
	 * @param	mixed		$method
	 * @param	mixed		&$arg			Any passed in arguments
	 */
	public function update( \SplSubject $subject, $method = null, &$args = null ) {
		if ( $method ) {
			if ( method_exists( $this, $method ) ) {
				$this->{$method}( $args );
			} else {
				throw new \Exception(
						'The specified event method ' . get_called_class() . 
						'::' . $method . ' does not exist.'
					);
			}
		} else {
			throw new \Exception(
					'The specified event method ' . get_called_class() . 
					'::' . 'update() does not exist.'
				);
		}
	}
}
