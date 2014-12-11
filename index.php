<?php

ini_set( 'display_errors', '1' ); // Remove this in production
date_default_timezone_set( 'UTC' );

// Performance testing. Remove in production (remember to also remove references below)
define( 'START',	-microtime( true ) ); // Used with + microtime(true)
$initialMemory		= memory_get_usage();

session_start();


/**
 * Application base path.
 */
define( 'PATH',		realpath( dirname( __FILE__ ) ) . '/' );

require( PATH . 'vendor/bootstrap.php' );
