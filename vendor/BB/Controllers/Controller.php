<?php
namespace BB\Controllers;
use BB\Models;

abstract class Controller implements \SplObserver {
	public static $output = array();
	
	abstract public function update( \SplSubject $subject );

	/***
	Security and privileges
	 ***/
	protected static function filter( 
		$method, 
		$key, 
		$default, 
		$type = 'text' 
	) {
		if ( 'post' === $method ) {
			$value = self::fromArray( $_POST, $key, $default, true );
		} else {
			$value = self::fromArray( $_GET, $key, $default );
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
				$regex = '/^(up|down)$/';		// Vote up (1) or down (-1)
				break;
			
			case 'raw' :
				return $value;				// This is very dangerous!
				
			case 'text' :
				return \Microthread\Html::entities( $value );
				
			default:
				$html = new \Microthread\Html();
				return $html->filter( $value );
		}
		
		if ( preg_match( $regex, $value ) ) {
			return $value;
		}
		
		return $default;
	}

	protected static function headers() {
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

	protected static function fingerprint() {
		$str		= '';
		$headers	= self::headers();
		
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
	protected static function pbkdf2( 
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

	protected static function shash( $salt, $key, $pbkdf = false ) {
		if ( $pbkdf ) {
			return $salt . '.' . self::pbkdf2( 'sha256', $key, $salt, 1000, 20 );
		}
		return $salt . '.' . hash( 'tiger160,4', $key . $salt );
	}
	
	protected static function salt( $size ) {
		return bin2hex( mcrypt_create_iv( $size, MCRYPT_DEV_URANDOM ) );
	}
	
	protected static function matchHash( $hash, $key, $pbkdf = false ) {
		$p = strpos( $hash, '.' );
		if  ( false === $p ) {
			return false;
	}
		$salt = substr( $hash, 0, $p );
		return $hash === self::shash( $salt, $key, $pbkdf );
	}

	protected static function editKey( $id, $auth = null ) {
		if ( null === $auth ) {
			$salt = self::salt( 5 );
			return self::shash( $salt, $id );
		}
		
		return self::matchHash( $auth, $id );
	}
	
	protected static function getAuth( $salt = null, $pass = null ) {
		if ( null === $salt ) {
			$salt = self::salt( 16 );
		}
		if ( null === $pass ) {
			$pass = session_id();
		}
		return self::shash( $salt, $pass, true );
	}
	
	protected static function matchAuth( $key, $created_at = null ) {
		if ( empty( $key ) ) {
			return false;
		}
		
		if ( null !== $created_at ) {
			if ( !self::checkEditLimit( $created_at ) ) {
				return false;
			}
		}
		
		$p = strpos( $key, '.' );
		if  ( false === $p ) {
			return false;
		}
		
		$salt = substr( $key, 0, $p );
		if ( $key === self::getAuth( $salt ) ) {
			return true;
		}
		
		return false;
	}
	
	protected static function getEditLimit() {
		if ( !defined( 'EDIT_LIMIT' ) ) {
			define( 'EDIT_LIMIT', ( int ) ini_get( 'session.gc_maxlifetime' ) );
		}
		
		return EDIT_LIMIT;
	}
	
	protected static function checkEditLimit( $created_at ) {
		$exp = self::getEditLimit();
		return ( time() - strtotime( $created_at ) > $exp ) ? false : true;
	}
	
	protected static function putSessList( $key, $id, $value ) {
		if ( !isset( $_SESSION[$key] ) ) {
			$_SESSION[$key] = array();
		}
		$_SESSION[$key][] = array( $id, $value );
	}
	
	protected static function delSessList( $key, $id ) {
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
	
	protected static function getSessList( $key ){
		if ( !isset( $_SESSION[$key] ) || !is_array( $_SESSION[$key] ) ) {
			return array();
		}
		return $_SESSION[$key];
	}
	
	protected static function requestKey( $reset = false ) {
		if ( !isset( $_SESSION['req'] ) || $reset ) {
			$_SESSION['req'] = self::salt( 5 );
		}
		if ( !$reset ) {
			return $_SESSION['req'];
		}
	}
	
	protected static function field( $key ) {
		return hash( 'tiger160,4', $key . self::requestKey() );
	}
	
	protected static function fromArray( 
		$array, 
		$key, 
		$default, 
		$hash = false 
	) {
		if  ( $hash ) {
			$key = self::field( $key );
		}
		return isset( $array[$key] )? $array[$key] : $default;
	}
}
