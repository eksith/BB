<?php
namespace BB\Exceptions;

class ExceptionObservable extends \BB\Observable {
	public $errType;
	public $message;
	
	public function __construct() {
		set_exception_handler( array( $this, 'exceptionHandler') );
		set_error_handler( array( $this, 'errorHandler' ) );
	}
	
	public function exceptionHandler( \Exception $e ) {
		$errType	= 'exception';
		$class		= get_class( $e );
		$message	= $e->getMessage;
		$file		= $e->getFile;
		$line		= $e->getLine;
		$code		= $e->getCode;
		$trace		= $e->getTrace;
		
		$this->message = "Exeption [$code] $message \nOccured on line $line in class $class " .
			" and file $file. PHP " . PHP_VERSION . " ( " . PHP_OS . 
			" ) \nTrace: \n$trace \n\n";
		
		$this->notify();
	}
	
	public function errorHandler( $level, $message, $file, $line ) {
		$errType	= 'error';
		$msg = '';
		switch( $level ) {
			case E_WARNING:
				$msg .= 'Warning ';
				break;
			case E_PARSE:
				$msg .= 'Parse warning ';
				break;
			case E_NOTICE:
				$msg .= 'Notice ';
				break;
			case E_USER_ERROR:
				$msg .= 'User error ';
				break;
			case E_USER_WARNING:
				$msg .= 'User warning ';
				break;
			case E_USER_NOTICE:
				$msg .= 'User notice ';
				break;
			case E_RECOVERABLE_ERROR:
				$msg .= 'Recoverable error ';
				break;
			case E_DEPRECATED:
				$msg .= 'Deprecated warning ';
				break;
			case E_USER_DEPRECATED:
				$msg .= 'User deprecated uarning ';
				break;
			default:
				$msg .= 'Unknown error ';
		}
		
		$msg .= "[$level] $message \nOccured on line $line in file $file. PHP " . 
			PHP_VERSION . " ( " . PHP_OS . " )\n";
		
		$this->message	= $msg;
		$this->notify();
	}
}
