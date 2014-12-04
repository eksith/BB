<?php

ini_set( 'display_errors', '1' ); // Remove this in production
date_default_timezone_set( 'UTC' );

// Performance testing. Remove in production (remember to also remove references below)
define( 'START',	-microtime( true ) ); // Used with + microtime(true)
$initialMemory		= memory_get_usage();

session_start();


// Database file location
define( 'CONN',		'sqlite:data\bb.sqlite' );

define( 'TOPIC_LIMIT',	60 );	// Number of topics per page
define( 'POST_LIMIT',	2 );	// Number of posts per page

define( 'EDIT_LIMIT',	1800 ); // ( Optional ) Edit period in seconds ( 1800 = 30 minutes )


define( 'AUTO_LOCK',	8 );	// Automatically close topics after this many days of inactivity

// Hashed admin password ( default is 'password' ) Not doing anything yet
define( 'ADMIN', 	'8d04e70695d4cf206099501be404e0e6.85adf6bf85ca4fc7ef8b04b5cbf04f5b2b0107a9' );



/**
 * Application base path.
 */
define( 'PATH',		realpath( dirname( __FILE__ ) ) . '/' );

define( 'PKGS',		PATH . 'vendor/' );
define( 'TEMPLATES',	PATH . 'templates/' );

/**
 * Autoloader
 */
set_include_path( get_include_path() . PATH_SEPARATOR . PKGS );
spl_autoload_extensions( '.php' );
spl_autoload_register( function( $class ) {
	spl_autoload( str_replace( "\\", "/", $class ) );
});

new BB\Firewall();

require ( 'functions.php' );
require ( 'routes.php' );

$routes 	= array(
	''			=> 'index',
	':page'			=> 'index',
	'firehose'		=> 'firehose',
	'firehose/:page'	=> 'firehose',
	'threads/:id'		=> 'thread',
	'threads/:id/:page'	=> 'thread',
	'posts/:id'		=> 'view',
	'posts/:id/:act'	=> 'view',
	'posts/:id/:act/:auth'	=> 'view',
	'tags/:tag'		=> 'tag',
	'tags/:tag/:page'	=> 'tag',
	'vote/:id/:vote'	=> 'vote'
);

$router = new BB\Router();
$router->route( $routes );

housekeeping();
