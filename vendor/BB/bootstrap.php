<?php

// Database file location
define( 'CONN',		'sqlite:data\bb.sqlite' );

define( 'EDIT_LIMIT',	1800 ); // ( Optional ) Edit period in seconds ( 1800 = 30 minutes )


define( 'AUTO_LOCK',	8 );	// Automatically close topics after this many days of inactivity

define( 'GRACE',	1800 );	// Grace period in seconds after which auto-lock posts can still be 
				// updated. This is for people who left their from open for a while 
				// before clicking 'Post'

// Hashed admin password ( default is 'password' ) Not doing anything yet
define( 'ADMIN', 	'8d04e70695d4cf206099501be404e0e6.85adf6bf85ca4fc7ef8b04b5cbf04f5b2b0107a9' );


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

// Idea from http://devzone.zend.com/1732/implementing-the-observer-pattern-with-splobserver-and-splsubject/
$errors		= new BB\Exceptions\ExceptionObservable();
$errors->attach( new BB\Exceptions\Logger() );
$errors->attach( new BB\Exceptions\Display() );

$routes 	= array(
	''			=> 'index',
	':page'			=> 'index',
	//'firehose'		=> 'Firehose',
	//'firehose/:page'	=> 'Firehose',
	'posts/:id'		=> 'post',
	'posts/:id/:act'	=> 'post',
	'posts/:id/:act/:auth'	=> 'post',
	'threads/:id'		=> 'threads',
	'threads/:id/:page'	=> 'threads',
	'tags/:tag'		=> 'tag',
	'tags/:tag/:page'	=> 'tag',
	'vote/:id/:vote'	=> 'post',
	'/search/'		=> 'search',
	'/search/:page'		=> 'search'
);


$router = new BB\Router();
$router->route( $routes );

$queue	= new Microthread\Queue();
