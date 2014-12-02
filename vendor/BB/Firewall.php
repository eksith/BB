<?php
namespace BB;

class Firewall extends Data {
	
	const RESPONSE_KILL 	= 0;
	const RESPONSE_SLOW	= 1;
	const RESPONSE_NOPOST	= 2;
	
	const SLOW_MIN		= 5;
	const SLOW_MAX		= 10;
	
	public function __construct() {
		$ip	= inet_pton( $this->determineIP() );
		$sql	= 'SELECT ip, expires_at, response FROM firewall WHERE ip = :ip';
		
		parent::init();
		$stmt	= self::$db->prepare( $sql );
		$stmt->execute( array( ':ip' => $ip ) );
	
		$result	= $stmt->fetch();
		if ( !empty( $result ) ) {
			if ( $result['expires_at'] < time() ) {
				
			}
		}
	}
	
	public function remove( $ip ) {
		$ips	= array_map( "inet_pton", explode( ',', $ip ) );
		$params	= array();
		$ins	= '';
		for ( $i = 0; $i < count( $params ); $i++ ) {
			$ins		.= ':p' . $i . ',';
			$params[':p' . $i] = $ips[$i];
		}
		$ins	= rtrim( $ins, ',' );
		$sql	= "DELETE FROM firewall WHERE ip IN ( $ins )";
		
		parent::init();
		$stmt	= self::$db->prepare( $sql );
		$stmt->execute( $params );
	}
	
	public function put( $ip, $expires, $response ){
		$params	= array_map( "inet_pton", explode( ',', $ip ) );
		$sql	= 'INSERT INTO firewall ( ip, expires_at, response ) VALUES ( :i, :e, :r );';
		
		parent::init();
		$stmt	= self::$db->prepare( $sql );
		
		foreach( $params as $p ) {
			$stmt->execute( array( 
				':i' => $p, 
				':e' => $expires,
				':r' => $response
			) );
		}
	}
	
	
	protected function response( $response ) {
		switch( $response ) {
			case self::RESPONSE_SLOW:
				sleep( mt_rand( self::SLOW_MIN, self::SLOW_MAX ) );
				break;
			
			case self::RESPONSE_NOPOST:
				define( 'NOPOST', true );
				break;
				
			default:
				die();
		}
	}
	
	protected function checkIp( $ip ) {
		return ( filter_var( $ip, 
			FILTER_VALIDATE_IP | 
			FILTER_FLAG_IPV4 | 
			FILTER_FLAG_IPV6 | 
			FILTER_FLAG_NO_PRIV_RANGE | 
			FILTER_FLAG_NO_RES_RANGE ) ) ? true : false;
	}
	
	// Adapted from :
	// http://www.grantburton.com/2008/11/30/fix-for-incorrect-ip-addresses-in-wordpress-comments/
	protected function determineIP() {
		if ( checkIp( $_SERVER["HTTP_CLIENT_IP"] ) ) {
			return $_SERVER["HTTP_CLIENT_IP"];
		}
		foreach ( explode(',', $_SERVER["HTTP_X_FORWARDED_FOR"] ) as $ip ) {
			if ( checkIP( trim( $ip ) ) ) {
				return $ip;
			}
	
		}
		if ( checkIP( $_SERVER["HTTP_X_FORWARDED"] ) ) {
			return $_SERVER["HTTP_X_FORWARDED"];
		} elseif ( checkIP( $_SERVER["HTTP_X_CLUSTER_CLIENT_IP"] ) ) {
			return $_SERVER["HTTP_X_CLUSTER_CLIENT_IP"];
		} elseif ( checkIP( $_SERVER["HTTP_FORWARDED_FOR"] ) ) {
			return $_SERVER["HTTP_FORWARDED_FOR"];
		} elseif ( checkIP( $_SERVER["HTTP_FORWARDED"] ) ) {
			return $_SERVER["HTTP_FORWARDED"];
		} else {
			return $_SERVER["REMOTE_ADDR"];
		}
	}
}
