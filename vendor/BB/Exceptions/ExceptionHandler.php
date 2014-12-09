<?php
namespace BB\Exceptions;

class ExceptionHandler extends \BB\Observable {
	public $exception;
	
	public function handle( \Exception $e ) {
		$this->exception = $e;
		$this->notify();
	}
}
