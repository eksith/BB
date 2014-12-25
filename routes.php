<?php

function method() {
	return strtolower( $_SERVER['REQUEST_METHOD'] );
}

function index( $page = 1 ) {
	$m = method();
	if ( 'get' == $m ) {
		$thread = BB\Post::find( array( 'page' => $page ) );
		displayThread( $thread, 'index' );
	} elseif ( 'post' == $m ) {
		echo "Index $page";
		//TODO New topic
	}
}

function topic( $id, $page = 1 ) {
	$m = method();
	if ( 'get' == $m ) {
		$thread = BB\Post::find( array( 'sub' => $id, 'page' => $page ) );
		displayThread( $thread, 'topic' );
		
	} elseif ( 'post' == $m ) {
		echo "Topic $id on page $page";
		//TODO New reply
	}
}

function thread( $id, $page = 1 ) {
	$m = method();
	if ( 'get' == $m ) {
		$thread = BB\Post::find( array( 'thread' => $id, 'page' => $page ) );
		displayThread( $thread, 'thread' );
		
	} elseif ( 'post' == $m ) {
		echo "Thread $id on page $page";
		//TODO New reply
	}
}

function displayThread( $thread, $mode ) {
	switch ( $mode ) {
		case 'index':
			require( TEMPLATES . '_index.php' );
			break;
			
		case 'topic':
			require( TEMPLATES . '_topic.php' );
			break;
			
		case 'thread':
			require( TEMPLATES . '_thread.php' );
			break;
			
		default:
			require( TEMPLATES . '_notfound.php' );
	}
}

function post( $id, $act = 'view', $auth = '' ) {
	$m = method();
	if ( 'get' == $m ) {
		echo "Post $id act $act and authorization $auth";
	} elseif ( 'post' == $m ) {
		//TODO Post actions
	}
}

function vote( $id, $vote ) {
	$m = method();
	if ( 'get' == $m ) {
		echo "Vote $id $vote";
	} elseif ( 'post' == $m ) {
		//TODO vote
	}
}

function search( $page = 1 ) {
	$m = method();
	if ( 'get' == $m ) {
	} elseif( 'post' == $m ) {
		//TODO search
	}
}

function autocomplete( $search ) {
	$m = method();
	if ( 'get' == $m ) {
		//TODO autocomplete
	}
}
