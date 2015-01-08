<?php

namespace BB;

class Listener implements \SplObserver {
	private $states;

	public function addState( $state, $value = null ) {
		$this->states[$state] = $value;
	}

	public function removeState( $state ) {
		if ( $this->hasState( $state ) ) {
			unset( $this->states[$state] );
			return true;
		}
		return false;
	}

	public function hasState( $state ) {
		return isset( $this->states[$state] );
	}

	public function getState( $state ) {
		if ( $this->hasState( $state ) ) {
			return $this->states[$state];
		}

		return null;
	}

	public function getAllStates(){
		return $this->states;
	}

	public function update(
		\SplSubject $subject,
		$trigger	= null,
		&$args		= null
	) {
		if ( $trigger ) {
			if ( method_exists( $this, $trigger ) ) {
				call_user_func_array(
					array( &$this, $trigger ), $args
				);
			} else {
				// method not found exception
			}
		} else {
			// direct update
		}
	}
}
