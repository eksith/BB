<?php
namespace BB\Exceptions;

class Logger extends \BB\Events\Listener {
	public function update( \SplSubject $subject ) {
		return error_log( $subject->message );
	}
}
