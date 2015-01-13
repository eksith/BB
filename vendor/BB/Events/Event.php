<?php
namespace BB\Events;
/**
 * Attach event handlers to an event to be notified
 * @author	Thomas RAMBAUD
 * @author	Eksith Rodrigo <reksith at gmail.com>
 * @version	1.1
 * @access	public
 */
class Event implements \SplSubject {
	/**
	 * @var SplObjectStorage stores all attached observers
	 */
	private $observers;
	
	/**
	 * Default constructor to initialize the observers.
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct() {
		 $this->observers = new \SplObjectStorage();
	}
	
	/**
	 * Wrapper for the attach method, allowing for the addition
	 * of a method name to call within the observer.
	 *
	 * @access	public
	 * @param	SplObserver	$event
	 * @param	mixed		$method
	 * @return	Event
	 */
	public function bind( \SplObserver $event, $method = null ) {
		if ( $this->has( $event ) ) {
			$this->observers->rewind();
			while( $this->observers->valid() ) {
				if ( $this->observers->current() == $event ) {
					$this->observers->setInfo( $method );
					break;
				}
				$this->observers->next();
			}
			$this->observers->rewind();
		} else {
			$this->observers->attach( $event, $method );
		}
		return $this;
	}
	
	/**
	 * Attach a new observer for the particular event.
	 *
	 * @access	public
	 * @param	SplObserver	$event
	 * @return	Event
	 */
	public function attach( \SplObserver $event ) {
		if ( !$this->has( $event ) ) {
			$this->observers->attach( $event );
		}
		return $this;
	}
	
	/**
	 * Detach an existing observer from the particular event.
	 *
	 * @access	public
	 * @param	SplObserver	$event
	 * @return	Event
	 */
	public function detach( \SplObserver $event ) { 
		if ( $this->has( $event ) ) {
			$this->observers->detach( $event );	
		}
		return $this;
	}
	
	/**
	 * Find if an observer already exists in the collection.
	 *
	 * @access	public
	 * @param	SplObserver	$event
	 * @return	boolean
	 */
	public function has( \SplObserver $event ) {
		if ( $this->observers->contains( $event ) ) {
			return true;
		}
		return false;
	}
	
	/**
	 * Notify all event observers that the event was triggered.
	 *
	 * @access	public
	 * @param	mixed	&$args
	 */
	public function notify( &$args = null ) {
		$this->observers->rewind();
		while ( $this->observers->valid() ) {
			$method		= $this->observers->getInfo();
			$observer	= $this->observers->current();
			$observer->update( $this, $method, $args );
			
			/**
			 * On to the next observer for notification
			 */
			$this->observers->next();
		}
	}
	
	/**
	 * Retrieves all observers.
	 *
	 * @access	public
	 * @return	SplObjectStorage
	 */
	public function getHandlers() {
		return $this->observers;
	}
}
