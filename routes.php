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
		$data		= array();
		
		$data['action']	= "/";
		$data['thread']	= BB\Post::find( array( 'page' => $page ) );
		$data['root']	= 0;
		$data['parent']	= 0;
		
		$data['fields'] = BB\Helpers::fields( array( 'title', 'body' ) );
		
		BB\Display::render( '_index.php', $data );
		
	} elseif ( 'post' == $m ) {
		$title	= filter( 'post', 'title', null, 'raw' );
		$body	= filter( 'post', 'body', null, 'raw' );
		
		$post	= initPost( $title, $body, 0, 0, 0 );
		savePost( $post );
	}
}

function firehose( $page = 1 ) {
	if ( 'get' == $m ) {
		$data		= array();
		$data['action']	= "/";
		$data['thread']	= BB\Post::find( array( 'page' => $page, 'new' => true ) );
		$data['root']	= 0;
		$data['parent']	= 0;
		
		$data['fields'] = BB\Helpers::fields( array( 'title', 'body' ) );
		
		BB\Display::render( '_index.php', $data );
	}
}

function thread( $id = 0, $page = 1 ) {
	$m = method();
	if ( 'get' == $m ) {
		$data		= array();
		$data['action']	= "/threads/{$id}";
		$data['thread']	= BB\Post::find( array( 'thread' => $id, 'page' => $page ) );
		$data['root']	= $id;
		$data['parent']	= 0;		
		
		$data['fields'] = BB\Helpers::fields( array( 'title', 'body', 'root' ) );
		
		BB\Display::render( '_thread.php', $data );
		
	} elseif ( 'post' == $m ) {
		$title	= filter( 'post', 'title', null, 'raw' );
		$body	= filter( 'post', 'body', null, 'raw' );
		$root	= filter( 'post', 'root', $id, 'num' );
		$parent	= filter( 'post', 'parent', $id, 'num' );
		
		$post	= initPost( $title, $body, $root, $parent );
		\Microthread\Queue::register(
			"savePost", array( $post )
		);
	}
}

function post( $id, $act = 'view', $auth = '' ) {
	$m = method();
	if ( 'get' == $m ) {
		if ( $act == 'edit' || $act == 'delete' ) {
			if ( !checkEditPriv( $id, $auth, $info ) ) {
				die( 'Edit window expired' );
			}
		}
		
		$posts = array();
		
		switch( $act ) {
			case 'edit'	:
				editView( $id, $info );
				break;
			case 'delete'	:
				deleteView( $id, $info );
				break;
			default		:
				plainView( $id );
		}
	} elseif ( 'post' == $m ) {
		if ( !checkEditPriv( $id, $auth, $info ) ) {
			die( 'Edit window expired' );
		}
		
		$title	= filter( 'post', 'title', null, 'raw' );
		$body	= filter( 'post', 'body', null, 'raw' );
		$post	= initPost( $title, $body, null, null, true );
		\Microthread\Queue::register(
			"savePost", array( $post )
		);
		
	}
}

function plainView( $id ) {
	$data		= array();
	$data['post']	= BB\Post::find( array( 'id' => $id ) );
	$data['fields']	= BB\Helpers::fields( array( 'title', 'body', 'root', 'parent' ) );
	
	BB\Display::render( '_post.php', $data );
}

function editView( $id ) {
	$data		= array();
	$data['post']	= BB\Post::find( array( 'edit' => $id ) );
	$data['fields']	= BB\Helpers::fields( array( 'title', 'body', 'edit' ) );
	
	BB\Display::render( '_edit.php', $data );
}

function deleteView( $id ) {
	$data		= array();
	$data['post']	= BB\Post::find( array( 'id' => $id ) );
	$data['fields']	= BB\Helpers::fields( array( 'edit' ) );
	
	BB\Display::render( '_delete.php', $data );
}

// TODO: Moderation view
function modView( $id ) {
	$data		= array();
	$data['post']	= BB\Post::find( array( 'id' => $id ) );
	$data['fields']	= BB\Helpers::fields( array( 'edit' ) );
	
	BB\Display::render( '_mod.php', $data );
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
		$pass	= filter( 'post', 'password', null, 'raw' );
		
		if ( BB\Helpers::matchAuth( $info['auth_key'], $info['created_at'] ) ) {
			return true;
		}
		
		if( empty( $body ) ) {
			die( 'Post cannot be empty' );
		}
		
		$title	= BB\Helpers::filter( 'post', 'title', null, 'title' );
		$root	= BB\Helpers::filter( 'post', 'root', 0, 'num' );
		$parent	= BB\Helpers::filter( 'post', 'parent', 0, 'num' );
		$edit	= BB\Helpers::filter( 'post', 'edit', 0, 'num' );
		$pass	= BB\Helpers::filter( 'post', 'password', 0, 'num' );
		
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
		
		$key	= BB\Helpers\chkSessList( 'votes', $id );
		if ( false !== $key ) {
			die( 'already voted' );
		}
		
		BB\Helpers\putSessList( 'votes', $id, $vote );
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

function initPost( $title, $body, $root = 0, $parent = 0, $edit = null ) {
	if ( empty( $edit ) ) {
		$post = new BB\Post;
		if ( !empty( $root ) ) {
			$post->root_id		= $root;
		}
		if ( !empty( $parent ) ) {
			$post->parent_id	= $parent;
		}
		if ( !empty( $pass ) ) {
			$post->auth_key		= $pass;
		}
	} else {
		$posts = BB\Posts:find( 'edit' => $edit );
		if ( count( $posts ) ) {
			$post = $posts[0];
			if ( !verify_password( $pass, $post['auth_key'] ) ) {
				die ( 'Invalid password' );
			}
			
			
		} else {
			die ( 'Post not found' );
		}
	}
	
	$post->title	= $title;
	$post->raw	= $body;
	
	return $post;
}

function savePost( $post ) {
	if ( $post->save( BB\Helpers::getAuth() ) ) {
		$edit = BB\Helpers::editKey( $post->id );
		BB\Helpers::deSessList( 'edits', $post->id );
		BB\Helpers::putSessList( 'edits', $post->id, $edit );
		header( 'Location: /posts/' . $post->id );
		die();
		
	} else {
		die( 'error' );
	}
}

function checkEditPriv( $id, $auth, &$info ) {
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
	
	return BB\Helpers::matchAuth( $post['auth_key'], $post['created_at'] );
}
