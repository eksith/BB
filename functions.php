<?php

/***
 Display helpers
 ***/

function formatBytes( $bytes, $precision = 2 ) {
	$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
	
	$bytes = max( $bytes, 0 );
	$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
	$pow = min( $pow, count( $units ) - 1 );
	
	return round( $bytes, $precision ) . ' ' . $units[$pow];
}

function formatTime( $time ) {
	$time  = strtotime( $time );
	return "<time datetime='". gmdate("Y-m-d H:i:s T", $time) ."'>" . gmdate("M d, Y H:i", $time) . "</time>";
}

function formatNav( $url, $text ) {
	return "<li><a href='{$url}'>{$text}</a></li>";
}

function pagination( $thread, $id, $page, $posts ) {
	$out	= "<nav class='page'><ul>";
	$amt	= count( $posts );
	if ( !$amt ) {
		if( $thread ) {
			$out	.= formatNav( "?thread={$thread}", 'Back to first page' );
		}
		echo $out . '</ul></nav>';
		return;
	}
	
	$first	= $posts[0];
	$prev	= $page - 1;
	$next	= $page + 1;
	
	if ( $id ) { // To parent post
		if ( $first->isRoot() ) {
			$out	.= formatNav( "/threads/{$first->id}", 'Back to thread' );
		} elseif ( $first->isParent() ) {
			$out	.= formatNav( "/threads/{$first->root_id}", 'Back to thread' );
		} else {
			$out	.= formatNav( "/threads/{$first->root_id}", 'Back to thread' );
			$out	.= formatNav( "/posts/{$first->parent_id}", 'To parent post' );
		}
	} elseif( $thread ) {
		if ( 1 === $prev ) {
			$out .= formatNav( "/threads/{$thread}", 'Back' );
		} elseif ( 1 >= $prev ) {
			$out .= formatNav( '/', 'Back' );
		} else {
			$out .= formatNav( "/threads/{$thread}/{$prev}", 'Back' );
		}
		if ( $amt >= POST_LIMIT ) {
			$out .= formatNav( "/threads/{$thread}/{$next}", 'Next' );
		}
	} else {
		if ( $prev == 1 ) {
			$out .= formatNav( '/', 'Back' );
		} elseif ( $prev <= 1 ) {
			$out .= "<li>&nbsp;</li>";
		} else {
			$out .= formatNav( "/{$prev}", 'Back' );
		}
		if ( $amt >= TOPIC_LIMIT ) {
			$out .= formatNav( "/{$next}", 'Next' );
		}
	}
	
	echo $out . '</ul></nav>';
}

function printPosts( $thread, $id, $canEdit, $posts ) {
	if ( $thread === 0 ) {
		if ( $id === 0 ) {
			printIndex( $posts );
		} elseif( !$canEdit ) {
			printThread( $posts );
		}
	} else {
		printThread( $posts );
	}
}

// JavasScript settings for voting, edit links etc...
function printJS() {
	$out	= "<script type='text/javascript'>";
	$out	.= 'var el = ' . EDIT_LIMIT .'; ';
	$out	.= 'var votes = {';
	
	$votes	= getSessList( 'votes' );
	foreach ( $votes as $v ) {
		$out .= "'{$v[0]}':'{$v[1]}',";
	}
	$out	= rtrim( $out, ',') . '}; ';
	
	$out	.= 'var edits = {';
	$edits	= getSessList( 'edits' );
	foreach ( $edits as $e ) {
		$out .= "'{$e[0]}':'{$e[1]}',";
	}
	$out	= rtrim( $out, ',') . '};';
	
	echo $out . '</script>';
}

/***
 Routing
 ***/

function method() {
	return strtolower( $_SERVER['REQUEST_METHOD'] );
}

function index( $page = 1 ) {
	$m = method();
	if ( 'get' == $m ) {
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


/***
 Helpers
 ***/

function initPost( $title, $body, $root, $parent, $id = 0 ) {
	$post = new BB\Post();
	$post->title		= $title;
	$post->raw 		= $body;
	$post->root_id		= $root;
	$post->parent_id	= $parent;
	
	if ( $id > 0 ) {
		$post->id = $id;
	}
	return $post;
}

/***
 Security and privileges
 ***/

function filter( 
	$method, 
	$key, 
	$default, 
	$type = 'text' 
) {
	if ( 'post' === $method ) {
		$value = fromArray( $_POST, $key, $default, true );
	} else {
		$value = fromArray( $_GET, $key, $default );
	}
	
	$value = trim( $value );
	if ( $default == $value || empty( $value ) ) {
		return $default;
	}
	
	switch( $type ) {
		case 'num':
			$regex = '/^([1-9])(:?[0-9]+)?$/';	// Starts with '0'
			break;
			
		case 'vote':
			$regex = '/^([-]?)[1]$/';		// Vote 1 or -1
			break;
		
		case 'raw' :
			return $value;				// This is very dangerous!
			
		case 'text' :
			return Microthread\Html::entities( $value );
			
		default:
			$html = new Microthread\Html();
			return $html->filter( $value );
	}
	
	if ( preg_match( $regex, $value ) ) {
		return $value;
	}
	
	return $default;
}

function headers() {
	if ( function_exists( 'getallheaders' ) ) {
		return getallheaders();
	}
	
	$headers = array();
	
	foreach( $_SERVER as $k => $v ) {
		if ( 0 === strpos( $k, 'HTTP_' ) ) {
			/**
			 * Remove HTTP_ and turn turn '_' to spaces
			 */
			$hd	= str_replace( '_', ' ', substr( $k, 5 ) );
			
			/**
			 * E.G. ACCEPT LANGUAGE to Accept-Language
			 */
			$uw	= ucwords( strtolower( $hd ) );
			$uw	= str_replace( ' ', '-', $uw );
			
			$headers[ $uw ] = $v;
		}
	}
	
	return $headers;
}

function fingerprint() {
	$str		= '';
	$headers	= headers();
	
	foreach ( $headers as $h => $v ) {
		switch( $h ) {
			case 'Accept-Charset':
			case 'Accept-Language':
			case 'Accept-Encoding':
			case 'Proxy-Authorization':
			case 'Authorization':
			case 'Max-Forwards':
			case 'Connection':
			case 'From':
			case 'Host':
			case 'DNT':
			case 'TE':
			case 'X-Requested-With':
			case 'X-Forwarded-For':
			case 'X-ATT-DeviceId':
			case 'User-Agent':
				$str .= $v;
				break;
		}
	}
	
	$str		.= $_SERVER['SERVER_PROTOCOL'] . $_SERVER['REMOTE_ADDR'];
	return hash( 'tiger160,4', $str );
}

// PHP 5.5 compatibility for hash_pbkdf2 courtesy of https://defuse.ca/php-pbkdf2.htm
function pbkdf2( 
	$algorithm, 
	$password, 
	$salt, 
	$count, 
	$key_length, 
	$raw_output = false 
) {
	$algorithm = strtolower( $algorithm );
	if ( !in_array( $algorithm, hash_algos() , true ) ) {
		throw new Exception( 'PBKDF2 ERROR: Invalid hash algorithm.' );
	}
	if ( $count <= 0 || $key_length <= 0 ) {
		throw new Exception( 'PBKDF2 ERROR: Invalid parameters.' );
	}
	// use the native implementation of the algorithm if available
	if ( function_exists( "hash_pbkdf2" ) ) {
		return hash_pbkdf2( 
			$algorithm, 
			$password, 
			$salt, 
			$count, 
			$key_length, 
			$raw_output 
		);
	}
	
	$hash_length	= strlen( hash( $algorithm, "", true ) );
	$block_count	= ceil( $key_length / $hash_length );

	$output		= '';
	for ( $i = 1; $i <= $block_count; $i++ ) {
		// $i encoded as 4 bytes, big endian.
		$last = $salt . pack( "N", $i );
		// first iteration
		$last = $xorsum = hash_hmac( 
					$algorithm, 
					$last, 
					$password, true 
				);
		// perform the other $count - 1 iterations
		for( $j = 1; $j < $count; $j++ ) {
			$xorsum ^= ( $last = hash_hmac( 
						$algorithm, 
						$last, 
						$password, true 
					) );
		}
		
		$output .= $xorsum;
	}

	if ( $raw_output ) {
		return mb_substr( $output, 0, $key_length );
	} else {
		return bin2hex( mb_substr( $output, 0, $key_length ) );
	}
}

function editKey( $id, $auth = null ) {
	if ( null === $auth ) {
		$salt = bin2hex( mcrypt_create_iv( 5, MCRYPT_DEV_URANDOM ) );
		return $salt . '.' . hash( 'tiger160,4', $id . $salt . session_id() );
	}
	
	$p = strpos( $auth, '.' );
	if  ( false === $p ) {
		return false;
	}
	
	$salt = substr( $auth, 0, $p );
	return $auth === $salt . '.' . hash(  'tiger160,4', $id . $salt . session_id() );
}

function matchAuth( $key, $created_at = null ) {
	if ( empty( $key ) ) {
		return false;
	}
	
	if ( null !== $created_at ) {
		if ( !checkEditLimit( $created_at ) ) {
			return false;
		}
	}
	
	$p = strpos( $key, '.' );
	if  ( false === $p ) {
		return false;
	}
	
	$salt = substr( $key, 0, $p );
	if ( $key === getAuth( $salt ) ) {
		return true;
	}
	
	return false;
}

function getAuth( $salt = null, $pass = null ) {
	if ( null === $salt ) {
		$salt = bin2hex( mcrypt_create_iv( 16, MCRYPT_DEV_URANDOM ) );
	}
	if ( null === $pass ) {
		$pass = session_id();
	}
	return $salt . '.' . pbkdf2( 'sha256', session_id(), $salt, 1000, 20 );
}

function getEditLimit() {
	if ( !defined( 'EDIT_LIMIT' ) ) {
		define( 'EDIT_LIMIT', ( int ) ini_get( 'session.gc_maxlifetime' ) );
	}
	
	return EDIT_LIMIT;
}

function checkEditLimit( $created_at ) {
	$exp = getEditLimit();
	return ( time() - strtotime( $created_at ) > $exp ) ? false : true;
}

function putSessList( $key, $id, $value ) {
	if ( !isset( $_SESSION[$key] ) ) {
		$_SESSION[$key] = array();
	}
	$_SESSION[$key][] = array( $id, $value );
}

function delSessList( $key, $id ) {
	if ( !isset( $_SESSION[$key] ) ) {
		$_SESSION[$key] = array();
		return false;
	}
	foreach ( $_SESSION[$key] as $k => $v ) {
		if ( $v[0] == $id ) {
			unset( $_SESSION[$key][$k] );
		}
	}
}

function getSessList( $key ){
	if ( !isset( $_SESSION[$key] ) || !is_array( $_SESSION[$key] ) ) {
		return array();
	}
	return $_SESSION[$key];
}


function field( $key ) {
	return hash( 'tiger160,4', $key . session_id() );
}

function fromArray( $array, $key, $default, $hash = false ) {
	if  ( $hash ) {
		$key = field( $key );
	}
	return isset( $array[$key] )? $array[$key] : $default;
}
