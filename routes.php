<?php
/***
 Routing
 ***/

function method() {
	return strtolower( $_SERVER['REQUEST_METHOD'] );
}

function index( $page = 1 ) {
	$m = method();
	if ( 'get' == $m ) {
		requestKey( true );
		$action	= "/threads/{$page}";
		$posts	= BB\Post::getPosts( 0, $page );
		$root	= 0;
		$parent	= 0;
		require ( TEMPLATES . '_index.php' );
		
	} elseif ( 'post' == $m ) {
		$title	= filter( 'post', 'title', null, 'raw' );
		$body	= filter( 'post', 'body', null, 'raw' );
		$post	= initPost( $title, $body, 0, 0, 0 );
		
		if ( $post->save( getAuth() ) ) {
			header( 'Location: /posts/' . $post->id );
			die();
		} else {
			die( 'error');
		}
	}
}

function firehose( $page = 1) {
	if ( 'get' == $m ) {
		requestKey( true );
		$action	= "/threads/{$page}";
		$posts	= BB\Post::getPosts( 0, $page, false, false, true );
		$root	= 0;
		$parent	= 0;
		require ( TEMPLATES . '_index.php' );
	}
}

function thread( $id = 0, $page = 1 ) {
	$m = method();
	if ( 'get' == $m ) {
		requestKey( true );
		$action	= "/threads/{$id}";
		$posts = BB\Post::getPosts( $id, $page );
		$root	= $id;
		$parent	= 0;
		require ( TEMPLATES . '_thread.php' );
		
	} elseif ( 'post' == $m ) {
		$title	= filter( 'post', 'title', null, 'raw' );
		$body	= filter( 'post', 'body', null, 'raw' );
		$root	= filter( 'post', 'root', $id, 'num' );
		$post	= initPost( $title, $body, $root, 0, 0 );
		
		if ( $post->save( getAuth() ) ) {
			header( 'Location: /posts/' . $post->id );
			die();
		} else {
			die( 'error');
		}
	}
}

// TODO: Moderation view
function modView( $id ) {
	
}

function flagView( $id ) {
	$post = BB\Post::getInfo( $id );
	if ( empty( $info ) ) {
		die( 'No post found');
	}
}

function editView( $id, $auth ) {
	if ( empty( $auth ) ) {
		die( 'Edit window expired' );
	}
	$posts		= BB\Post::getPosts( $id, 1, false, true );
	
	if ( empty( $posts ) ) {
		die( 'No post found');
	}
	
	$author		= matchAuth( $posts[0]->auth_key, $posts[0]->created_at );
	$akey		= editKey( $id, $auth );
	$canEdit	= ( $author && editKey( $id, $auth ) ) ? true : false;
	
	if ( !$canEdit ) {
		delSessList( 'edits', $id );
		die( 'Edit window expired' );
	}
	return $posts;
}

function view( $id, $act = 'read', $auth = '' ) {
	$m = method();
	if ( 'get' == $m ) {
		requestKey( true );
		$action	= "/posts/{$id}";
		
		switch( $act ) {
			case 'read' :
				$posts = BB\Post::getPosts( $id, 1, false );
				$root	= $posts[0]->root_id;
				$parent	= $id;
				require ( TEMPLATES . '_thread.php' );
				return;
				
			case 'lock':
			case 'delete':
				$posts = modView( $id );
				break;
				
			case 'flag':
				$posts = flagView( $id );
				break;
				
			default :
				$posts = editView( $id, $auth );
				$root	= $posts[0]->root_id;
				$parent	= $id;
				require ( TEMPLATES . '_thread.php' );
		}
	} elseif ( 'post' == $m ) {
		$body	= filter( 'post', 'body', null, 'raw' );
		
		if( empty( $body ) ) {
			die( 'Post cannot be empty' );
		}
		
		$title	= filter( 'post', 'title', null, 'title' );
		$root	= filter( 'post', 'root', 0, 'num' );
		$parent	= filter( 'post', 'parent', 0, 'num' );
		$edit	= filter( 'post', 'edit', 0, 'num' );
		
		$post	= initPost( $title, $body, $root, $parent, $edit );
		
		if ( $post->save( getAuth() ) ) {
			header( 'Location: /posts/' . $post->id );
			die();
		} else {
			die( 'error');
		}
	}
}

function vote( $id, $ud ) {
	if ( 'get' == method() ) {
		$vote	= ( $ud == 'up' )? 1 : -1;
		$info	= BB\Post::getInfo( $id );
		
		if ( empty( $info ) ) {
			die( 'No post found' );
		}
		if ( matchAuth( $info['auth_key'], $info['created_at'] ) ) {
			die( 'already voted' );
		}
		
		putSessList( 'votes', $id, $vote );
		die( 'voted' );
	}
	die( 'problem' );
}

function tag( $tag, $page = 1 ) {
	$m = method();
	if ( 'get' == $m ) {
	} else {
		return; // We only answer to get here
	}
}
