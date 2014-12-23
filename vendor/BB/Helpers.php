<?php

namespace BB;

class Helpers {
	private static $method;
	
	private static $domain;
	
	public static function method() {
		if ( !isset( self::$method ) ) {
			self::$method = strtolower( $_SERVER['REQUEST_METHOD'] );
		}
		
		return self::$method;
	}
	
	public static function domain() {
		if ( !isset( self::$domain ) ) {
			self::$domain = ( $_SERVER['HTTP_HOST'] != 'localhost' ) ? 
					$_SERVER['HTTP_HOST'] : false;
		}
		
		return self::$domain;
	}
	
	/**
	 * User submitted data ( GET/POST ) filter
	 */
	public static function filter( 
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
	
	/**
	 * Gets available browser headers
	 */
	public static function headers() {
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
	
	/**
	 * Browser fingerprint by browser headers, user agent and IP address
	 */
	public static function fingerprint() {
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
	
	/**
	 * PHP 5.5 compatibility for hash_pbkdf2 courtesy of 
	 * @link https://defuse.ca/php-pbkdf2.htm
	 */
	public static function pbkdf2( 
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
	
	/**
	 * Creates a simple hash. Optionally uses PBKDF2
	 */
	public static function shash( $salt, $key, $pbkdf = false ) {
		if ( $pbkdf ) {
			return $salt . '.' . self::pbkdf2( 'tiger192,4', $key, $salt, 1000, 20 );
		}
		
		return $salt . '.' . hash( 'tiger160,4', $key . $salt );
	}
	
	/**
	 * Create a cryptographically secure hash in hex format
	 */
	public static function salt( $size ) {
		return bin2hex( mcrypt_create_iv( $size, MCRYPT_DEV_URANDOM ) );
	}
	
	/**
	 * Match a given hash to a key
	 */
	public static function matchHash( $hash, $key, $pbkdf = false ) {
		$p = strpos( $hash, '.' );
		if  ( false === $p ) {
			return false;
	}
		$salt = substr( $hash, 0, $p );
		return $hash === self::shash( $salt, $key, $pbkdf );
	}
	
	/**
	 * Shorter editing key shown to the user (no pbkdf2). Optionally generates one
	 */
	public static function editKey( $id, $auth = null ) {
		if ( null === $auth ) {
			$salt = self::salt( 5 );
			return self::shash( $salt, $id );
		}
		
		return self::matchHash( $auth, $id );
	}
	
	/**
	 * Generates session based editing key
	 */
	public static function getAuth( $salt = null, $pass = null ) {
		if ( null === $salt ) {
			$salt = self::salt( 16 );
		}
		if ( null === $pass ) {
			$pass = self::visitKey();
		}
		return self::shash( $salt, $pass, true );
	}
	
	/**
	 * Verify editing privileges by authorization key and created date
	 */
	public static function matchAuth( $hash, $created_at = null ) {
		if ( empty( $hash ) ) {
			return false;
		}
		
		if ( null !== $created_at ) {
			if ( !self::chkEditLimit( $created_at ) ) {
				return false;
			}
		}
		
		$p = strpos( $hash, '.' );
		if  ( false === $p ) {
			return false;
		}
		
		$salt = substr( $hash, 0, $p );
		if ( $hash === self::getAuth( $salt ) ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Get the maximum timelimit before editing privileges expire
	 */
	public static function getEditLimit() {
		if ( !defined( 'EDIT_LIMIT' ) ) {
			define( 'EDIT_LIMIT', ( int ) ini_get( 'session.gc_maxlifetime' ) );
		}
		
		return EDIT_LIMIT;
	}
	
	/**
	 * Verify editing time window
	 */
	public static function chkEditLimit( $created_at ) {
		$exp	= self::getEditLimit();
		return ( time() - strtotime( $created_at ) > $exp ) ? false : true;
	}
	
	/**
	 * Check for user authentication
	 */
	public static function chkSessAuth( $user, $pass ) {
		$mod	= self::getModAuth();
		if ( empty( $mod ) ) {
			return false;
		}
		if ( self::matchHash( $pass, $mod, true ) ) {
			return true;
		}
		return false;
	}
	
	/**
	 * Set the user session, and optionally the cookie, with hashed password
	 */
	public static function setSessAuth( $user, $pass, $cookie = true ) {
		$_SESSION[$user]	= self::shash(
						self::salt( 16 ), 
						$pass, 
						true 
					);
		if ( $cookie ) {
			setcookie(
				$user, 
				$_SESSION[$user], 
				strtotime( '+7 days' ), 
				'/', 
				self::domain() 
			);
		}
	}
	
	/**
	 * Check for user cookie or session
	 */
	public static function getSessAuth( $user, $pass ) {
		if ( isset( $_SESSION[$user] ) ) {
			return $_SESSION[$user];
		} elseif ( isset( $_COOKIE[$user] ) ) {
			$_COOKIE[$user];
			return $data;
		}
		
		return null;
	}
	
	public static function delSessAuth( $user ) {
		if ( isset( $_SESSION[$user] ) ) {
			unset( $_SESSION[$user] );
		}
		
		setcookie( $user, '-', 1, '/', self::domain() );
	}
	
	/**
	 * Store an array key-value pair in a specific array in session
	 */
	public static function putSessList( $key, $id, $value ) {
		if ( !isset( $_SESSION[$key] ) ) {
			$_SESSION[$key] = array();
		}
		$_SESSION[$key][] = array( $id, $value );
	}
	
	/**
	 * Check for a key-value pair in a specific array in session
	 */
	public static function chkSessList( $key, $id ) {
		$arr = self::getSessList( $key );
		if ( empty( $arr ) ) {
			return false;
		}
		foreach ( $arr as $k => $v ) {
			if ( $v[0] == $id ) {
				return $_SESSION[$key][$k];
			}
		}
	}
	
	/**
	 * Remove a key-value pair in a specific array in session
	 */
	public static function delSessList( $key, $id ) {
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
	
	/**
	 * Check if key-value pair array exists in session
	 */
	public static function getSessList( $key ){
		if ( !isset( $_SESSION[$key] ) || !is_array( $_SESSION[$key] ) ) {
			return array();
		}
		return $_SESSION[$key];
	}
	
	/**
	 * Unique key for this request (should be reset each time the page is loaded)
	 */
	public static function requestKey( $reset = false ) {
		if ( !isset( $_SESSION['req'] ) || $reset ) {
			$_SESSION['req'] = self::salt( 5 );
		}
		if ( !$reset ) {
			return $_SESSION['req'];
		}
	}
	
	/**
	 * Unique key for this visit
	 */
	public static function visitKey() {
		if ( !isset( $_SESSION['visit'] ) ) {
			$_SESSION['visit'] = self::salt( 16 );
		}
		return $_SESSION['visit'];
	}
	
	public static function field( $key ) {
		return hash( 'tiger160,4', $key . self::requestKey() );
	}
	
	public static function fields( $labels = array() ) {
		$fields = array();
		foreach( $label as $l ) {
			$fields[$l] = self::field( $l );
		}
		return $fields;
	}
	
	public static function fromArray( 
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
	
	public static function housekeeping() {
		flush();
		if ( function_exists( 'fastcgi_finish_request ' ) ) {
			fastcgi_finish_request();
		}
		
		//BB\Post::lockOld( AUTO_LOCK );
	}
}
