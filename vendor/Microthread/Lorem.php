<?php
/**
 * Lorem ipsum pseudo language generator
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.1
 */
namespace Microthread;

final class Lorem {
	
	const W_MIN = 2;	// Minimum word size
	const W_MAX = 10;	// Maximum word size
	const S_MIN = 4;	// Minimum sentence size;
	const S_MAX = 20;	// Maximum sentence size;
	const P_MIN = 1;	// Minimum sentences per paragraph
	const P_MAX = 3;	// Maximum sentences per paragraph
	
	const V_FRQ = 3;	// Frequency of vowels in a word (I.E. A vowel in every x chars)
	
	private static $vowels		= "eaoiu";
	
	private static $consonants	= "tnrshdlfcmgypwbvkxjqz";
	
	private static $conPairs	= 
			array( "th", "he", "nd", "st", "te", "ti", "hi", "to" );
	
	private static $voPairs		=
			array( "an", "er", "in", "on", "at", "es", "en", "of", "ed", "or", "as" );
	
	private $range = '';
	
	public function __construct() {
		self::$consonants	= self::frequency( self::$consonants );
		self::$vowels		= self::frequency( self::$vowels );
	}
	
	public function getLorem( $min = 1, $max = 20 ) {
		$r = mt_rand( $min, $max );
		$t = '';
		
		for ( $i = 0; $i < $r; $i++ ) {
			$t .= self::paragraph() . "\n\n";
		}
		
		return $t;
	}
	
	private static function frequency( $chars ) {
		$cn = '';
		$c = strlen( $chars );
		
		for ( $i = $c; $i > 0; $i-- ) {
			$ch	= $chars[$c - $i];
			$f	= $i * $i + 1;
			for ( $j = $f; $j >= 0; $j-- ) {
				$cn .= $ch;
			}
		}
		return $cn;
	}
	
	public static function word( $u = false ) {
		$r = mt_rand( self::W_MIN, self::W_MAX );
		$w = '';
		$c = '';
		for( $i = 0; $i < $r; $i++ ) {
			if ( $i % self::V_FRQ === 0 ) {
				$c = self::fromRange( self::$consonants );
			} else {
				$c = self::fromRange( self::$vowels, $c );
			}
			
			$w .= $c;
		}
		
		if ( false === strpos($w, $c) ) {
			$w .= self::pair( self::$voPairs, $c );
		} else {
			$w .= self::pair( self::$conPairs, $c );
		}
		
		return ( $u )? ucfirst( $w ) : $w;
	}
	
	public static function sentence() {
		$r = mt_rand( self::S_MIN, self::S_MAX );
		$s = '';
		
		for ( $i = 0; $i < $r; $i++ ) {
			$s .= ( $i === 0 )? 
				self::word( true ) . ' ' : self::word() . ' ';
		}
		
		return rtrim( $s )  . '. ';
	}
	
	public static function paragraph() {
		$r = mt_rand( self::P_MIN, self::P_MAX );
		$p = '';
		
		for ( $i = 0; $i < $r; $i++ ) {
			$p .= self::sentence();
		}
		return $p;
	}
	
	public static function pair( $pairs, $p ) {
		$nc = '';
		$c = count( $pairs ) - 1;
		for ( $i = 0; $i < $c; $i++ ) {
			if ( $pairs[$i][0] === $p ) {
				$nc .= $pairs[$i][1];
			}
		}
		if ( $nc === '') {
			return $nc;
		}
		
		return self::fromRange( $nc );
	}
	
	public static function fromRange( $chars, $p = null) {
		if ( is_array( $chars ) ) {
			$l = count( $chars ) - 1;
			$c = null;
			while( $c === null ) {
				$c = $chars[mt_rand( 0, $l )];
			}
			return $c;
		}
		return  $chars[mt_rand( 0, strlen( $chars ) - 1 )];
	}
	
	public function charAt( $str, $pos ) {
		return ( substr( $str, $pos, 1 ) === false )?
			-1 : substr( $str, $pos );
			
	}
}
