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
define( 'TEMPLATES',	PATH . 'vendor/BB/templates/' );

set_include_path( get_include_path() . PATH_SEPARATOR . PKGS );
spl_autoload_extensions( '.php' );
spl_autoload_register( function( $class ) {
	spl_autoload( str_replace( "\\", "/", $class ) );
});

$routes 	= array(
	''			=> 'index',
	':page'			=> 'index',
	'topics/:id'		=> 'topic',
	'topics/:id/:page'	=> 'topic',
	'threads/:id'		=> 'thread',
	'threads/:id/:page'	=> 'thread',
	'posts/:id'		=> 'post',
	'posts/:id/:act'	=> 'post',
	'posts/:id/:act/:auth'	=> 'post',
	'vote/:id/:vote'	=> 'vote',
	'search'		=> 'search',
	'search/:page'		=> 'search',
	'autocomplete/:all'	=> 'autocomplete'
);


$router = new BB\Router();
$router->route( $routes );
