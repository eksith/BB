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
		$action	= "/";
		$thread	= BB\Post::find( array( 'page' => $page ) );
		$root	= 0;
		$parent	= 0;
		
		BB\Helpers::requestKey( true );
		$fields = getFields( array( 'title', 'body' ) );
		
		require ( TEMPLATES . '_index.php' );
		
	} elseif ( 'post' == $m ) {
		$title	= filter( 'post', 'title', null, 'raw' );
		$body	= filter( 'post', 'body', null, 'raw' );
		
		$post	= initPost( $title, $body, 0, 0, 0 );
		savePost( $post );
	}
}

function firehose( $page = 1 ) {
	if ( 'get' == $m ) {
		$action	= "/threads/{$page}";
		
		$thread	= BB\Post::find( array( 'page' => $page, 'new' => true ) );
		
		$root	= 0;
		$parent	= 0;
		
		BB\Helpers::requestKey( true );
		$fields = getFields( array( 'title', 'body' ) );
		
		require ( TEMPLATES . '_index.php' );
	}
}

function thread( $id = 0, $page = 1 ) {
	$m = method();
	if ( 'get' == $m ) {
		$action	= "/threads/{$id}";
		$thread = BB\Post::find( array( 'thread' => $id, 'page' => $page ) );
		$root	= $id;
		$parent	= 0;		
		
		BB\Helpers::requestKey( true );
		$fields = getFields( array( 'title', 'body', 'root' ) );
		
		require ( TEMPLATES . '_thread.php' );
		
	} elseif ( 'post' == $m ) {
		$title	= filter( 'post', 'title', null, 'raw' );
		$body	= filter( 'post', 'body', null, 'raw' );
		$root	= filter( 'post', 'root', $id, 'num' );
		$parent	= filter( 'post', 'parent', $id, 'num' );
		
		$post	= initPost( $title, $body, $root, $parent, 0 );
		savePost( $post );
	}
}

function post( $id, $act = 'view', $auth = '' ) {
	$m = method();
	if ( 'get' == $m ) {
		if ( $act == 'edit' || $act == 'delete' ) {
			if ( !checkEditPriv( $id, $auth ) ) {
				die( 'Edit window expired' );
			}
		}
		
		$posts = array();
		BB\Helpers::requestKey( true );
		
		switch( $act ) {
			case 'edit'	:
				editView( $id );
				break;
			case 'delete'	:
				deleteView( $id );
				break;
			default		:
				plainView( $id );
		}
	} elseif ( 'post' == $m ) {
		
		
	}
}

function plainView( $id ) {
	$post	= BB\Post::find( array( 'id' => $id ) );
	$fields	= getFields( array( 'title', 'body', 'root', 'parent' ) );
}

function editView( $id ) {
	$post	= BB\Post::find( array( 'edit' => $id ) );
	$fields	= getFields( array( 'title', 'body', 'edit' ) );
}

function deleteView( $id ) {
	$post	= BB\Post::find( array( 'id' => $id ) );
	$fields	= getFields( array( 'edit' ) );
	
}

// TODO: Moderation view
function modView( $id ) {
	
}

// TODO: Flag view
function flagView( $id ) {
	$post = BB\Post::getInfo( $id );
	if ( empty( $post ) ) {
		die( 'No post found');
	}
}

function editView1( $id, $auth ) {
	if ( empty( $auth ) ) {
		die( 'Edit window expired' );
	}
	$posts		= BB\Post::find( array( 'edit'=> $id ) );
	
	if ( empty( $posts ) ) {
		die( 'No post found');
	}
	
	$author		= BB\Helpers::matchAuth( $posts[0]->auth_key, $posts[0]->created_at );
	$akey		= BB\Helpers::editKey( $id, $auth );
	$canEdit	= ( $author && BB\Helpers::editKey( $id, $auth ) ) ? true : false;
	if ( !$canEdit ) {
		delSessList( 'edits', $id );
		die( 'Edit window expired' );
	}
	return $posts;
}

function view( $id, $act = 'read', $auth = '' ) {
	$m = method();
	if ( 'get' == $m ) {
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
				$posts	= editView( $id, $auth );
				$root	= $posts[0]->root_id;
				$parent	= $id;
				$title	= $posts[0]->title;
				require ( TEMPLATES . '_thread.php' );
		}
	} elseif ( 'post' == $m ) {
		$body	= filter( 'post', 'body', null, 'raw' );
		
		if( empty( $body ) ) {
			die( 'Post cannot be empty' );
		}
		
		$title	= BB\Helpers::filter( 'post', 'title', null, 'title' );
		$root	= BB\Helpers::filter( 'post', 'root', 0, 'num' );
		$parent	= BB\Helpers::filter( 'post', 'parent', 0, 'num' );
		$edit	= BB\Helpers::filter( 'post', 'edit', 0, 'num' );
		
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

function search( $page = 1 ) {
	$m = method();
	if ( 'get' == $m ) {
	} elseif( 'post' == $m ) {
		
	}
}

function initPost( $title, $body, $root, $parent, $edit = null ) {
	if ( empty( $edit ) ) {
		$post = new BB\Post;
		if ( !empty( $root ) ) {
			$post->root_id		= $root;
		}
		if ( !empty( $parent ) ) {
			$post->parent_id	= $parent;
		}
	} else {
		$posts = BB\Posts:find( 'edit' => $edit );
		if ( count( $posts ) ) {
			$post = $posts[0];
		} else {
			die ( 'post not found' );
		}
	}
	
	$post->title = $title;
	$post->raw = $body;
	
	return $post;
}

function savePost( $post ) {
	$auth	= BB\Helpers::getAuth();
	if ( $post->save( $auth ) ) {
		$edit = BB\Helpers::editKey( $post->id );
		BB\Helpers::deSessList( 'edits', $post->id );
		BB\Helpers::putSessList( 'edits', $post->id, $edit );
		header( 'Location: /posts/' . $post->id );
		die();
	} else {
		die( 'error' );
	}
}

function checkEditPriv( $id, $auth ) {
	if ( empty( $auth ) ) {
		return false;
	}
	$edit	= BB\Helpers::chkSessList( 'edits', $id );
	if ( false === $edits || 0 !== strcmp( $auth, $edit ) ) {
		return false;
	}
	
	if ( !BB\Helpers::editKey( $id, $edit ) ) {
		return false;
	}
	
	$info = BB\Post::getInfo( $id );
	if ( empty( $info ) ) {
		return false;
	}
	
	if ( BB\Helpers::matchAuth( $info['auth_key'], $info['created_at'] ) ) {
		return true;
	}
	
	return false;
}



function getFields( $labels = array() ) {
	$fields = array();
	foreach( $label as $l ) {
		$fields[$l] = BB\Helpers::field( $l );
	}
	return $fields;
}
