<?php

class Event implements \SplSubject {
	public $args		= array();
	private $observers;
	
	public function __construct(){
		$this->observers = new \SplObjectStorage();
	}
	
	public function __set( $name, $value ) {
		$this->args[$name] = $value;
	}
	
	public function __get( $name ) {
		return isset( $this->args[$name] )? $this->args[$name] : null;
	}
	
	public function bind( \SplObserver $observer, $trigger = null ) {
		$this->observers->attach( $observer, $trigger );
	}
	
	public function attach( \SplObserver $observer ) {
		$this->observers->attach( $observer );
	}
	
	public function detach( \SplObserver $observer ) {
		$this->observers->detach( $observer );
	}
	
	public function notify( &$args = null ) {
		$this->observers->rewind();
		
		while( $this->observers->valid() ) {
			$trigger	= $this->observers->getInfo();
			$observer	= $this->observers->current();
			$observer->update( $this, $trigger, $args );
			
			$this->observers->next();
		}
	}
	
	public function getObservers() {
		return $this->observers;
	}
}
