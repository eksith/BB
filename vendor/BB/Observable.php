<?php
namespace BB;

class Observable implements \SplSubject {
	public $args		= array();
	private $observers;
	
	public function __construct() {
		$this->observers = new \SplObjectStorage();
	}
	
	public function __set( $name, $value ) {
		$this->args[$name] = $value;
	}
	
	public function __get( $name ) {
		return isset( $this->args[$name] ) ? $this->args[$name] : null;
	}
	
	public function attach( \SplObserver $observer ) {
		$this->observers->attach( $observer );
	}
	
	public function detach( \SplObserver $observer ) {
		if ( $this->observers->contains( $observer ) ) {
			$this->observers->detach( $observer );
		}
	}
	
	public function notify() {
		foreach( $this->observers as $observer ) {
			$observer->update( $this );
		}
	}
}
