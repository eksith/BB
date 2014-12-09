<?php
namespace BB\Controllers;
use BB\Models;

abstract class Controller implements \SplObserver {
	public static $output = array();
	abstract public function update( \SplSubject $subject );
}
