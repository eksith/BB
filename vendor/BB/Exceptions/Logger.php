<?php
namespace BB\Exceptions;

class Logger implements \SplObserver {
	public function update( \SplSubject $subject ) {
		return error_log( $subject->message );
	}
}
