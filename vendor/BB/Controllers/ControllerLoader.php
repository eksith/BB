<?php

namespace BB\Controllers;

class ControllerLoader {
	protected $controllers = array();
	
	public function __construct() {}
	
	public function append( $controllers = array() ) {
		if ( empty( $controllers ) ) {
			return;
		}
		$this->controllers = array_merge( $this->controllers, $controllers );
	}
	
	public function load( \SplSubject $router ) {
		foreach( $this->controllers as $controller ) {
			$class = new $controller;
			$router->attach( $class );
		}
	}
}
