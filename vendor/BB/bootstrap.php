<?php

// Database file location
define( 'CONN',		'sqlite:data\bb.sqlite' );

define( 'EDIT_LIMIT',	1800 ); // ( Optional ) Edit period in seconds ( 1800 = 30 minutes )


define( 'AUTO_LOCK',	8 );	// Automatically close topics after this many days of inactivity

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

$routes 	= array(
	''			=> 'Index',
	':page'			=> 'Index',
	//'firehose'		=> 'Firehose',
	//'firehose/:page'	=> 'Firehose',
	'posts/:id'		=> 'Posts',
	'posts/:id/:act'	=> 'Posts',
	'posts/:id/:act/:auth'	=> 'Posts',
	'threads/:id'		=> 'Threads',
	'threads/:id/:page'	=> 'Threads',
	'tags/:tag'		=> 'Tag',
	'tags/:tag/:page'	=> 'Tag',
	'vote/:id/:vote'	=> 'Posts'
);


$router = new BB\Router();
$router->route( $routes );
