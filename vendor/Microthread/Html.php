<?php
/**
 * HTML parsing, filtering and sanitization
 * This class depends on Tidy which is included in the core since PHP 5.3
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.3
 */
namespace Microthread;

final class Html {
	
	/**
	 * @var array HTML filtering options
	 */
	public static $options = array( 
		'rx_url'	=>
			'@(https?|ftp)://(-\.)?([^\s/?\.#-]+\.?)+(/[^\s]*)?$@iS',
		
		'rx_xss'	=> // XSS (<style> can also be a vector. Stupid IE 6!)
			'/(<(s(?:cript|tyle)).*?)/ism',
		
		'rx_xss2'	=> // More potential XSS
			'/(document\.|window\.|eval\(|(java)?script:)/ism',
		
		'rx_esc'	=> // Directory traversal/escaping
			'/(\\~\/|\.\.|\\\\)/sm'	,
		
		'scrub_depth'	=> 3, // URL Decoding depth (fails on exceeding this)
		
		'nofollow'	=> false // Set rel='nofollow' on all links (this breaks the web)
	);
	
	/**
	 * @var array List of HTML Tidy output settings
	 * @link http://tidy.sourceforge.net/docs/quickref.html
	 */
	public static $tidy = array(
		// Preserve whitespace inside tags
		'add-xml-space'			=> true,
		
		// Remove proprietary markup (E.G. og:tags)
		'bare'				=> true,
		
		// More proprietary markup
		'drop-proprietary-attributes'	=> true,
		
		// Remove blank (E.G. <p></p>) paragraphs
		'drop-empty-paras'		=> true,
		
		// Wraps bare text in <p> tags
		'enclose-text'			=> true,
		
		// Removes illegal/invalid characters in URIs
		'fix-uri'			=> true,
		
		// Removes <!-- Comments -->
		'hide-comments'			=> true,
		
		// Removing indentation saves storage space
		'indent'			=> false,
		
		// Combine individual formatting styles
		'join-styles'			=> true,
		
		// Converts <i> to <em> & <b> to <strong>
		'logical-emphasis'		=> true,
		
		// Byte Order Mark isn't really needed
		'output-bom'			=> false,
		
		// Ensure UTF-8 characters are preserved
		'output-encoding'		=> 'utf8',
		
		// W3C standards compliant markup
		'output-xhtml'			=> true,
		
		// Had some unexpected behavior with this
		//'markup'			=> true,

		// Merge multiple <span> tags into one		
		'merge-spans'			=> true,
		
		// Only outputs <body> (<head> etc... not needed)
		'show-body-only'		=> true,
		
		// Removing empty lines saves storage
		'vertical-space'		=> false,
		
		// Wrapping tags not needed (saves bandwidth)
		'wrap'			=> 0
	);
	
	/**
	 * @var array Whitelist of tags. Trim or expand these as necessary
	 * @example 'tag' => array( of, allowed, attributes )
	 */
	private static $whitelist = array(
		'p'		=> array( 'style', 'class', 'align' ),
		'div'		=> array( 'style', 'class', 'align' ),
		'span'		=> array( 'style', 'class' ),
		'br'		=> array( 'style', 'class' ),
		'hr'		=> array( 'style', 'class' ),
		
		'h1'		=> array( 'style', 'class' ),
		'h2'		=> array( 'style', 'class' ),
		'h3'		=> array( 'style', 'class' ),
		'h4'		=> array( 'style', 'class' ),
		'h5'		=> array( 'style', 'class' ),
		'h6'		=> array( 'style', 'class' ),
		
		'strong'	=> array( 'style', 'class' ),
		'em'		=> array( 'style', 'class' ),
		'u'		=> array( 'style', 'class' ),
		'strike'	=> array( 'style', 'class' ),
		'del'		=> array( 'style', 'class' ),
		'ol'		=> array( 'style', 'class' ),
		'ul'		=> array( 'style', 'class' ),
		'li'		=> array( 'style', 'class' ),
		'code'		=> array( 'style', 'class' ),
		'pre'		=> array( 'style', 'class' ),
		
		'sup'		=> array( 'style', 'class' ),
		'sub'		=> array( 'style', 'class' ),
		
		// Took out 'rel' and 'title', because we're using those below
		'a'		=> array( 'style', 'class', 'href' ),
		
		'img'		=> array( 'style', 'class', 'src', 'height',  'width', 'alt', 
						'longdesc', 'title', 'hspace', 'vspace' ),
		
		'table'		=> array( 'style', 'class', 'border-collapse', 'cellspacing', 
						'cellpadding' ),
					
		'thead'		=> array( 'style', 'class' ),
		'tbody'		=> array( 'style', 'class' ),
		'tfoot'		=> array( 'style', 'class' ),
		'tr'		=> array( 'style', 'class' ),
		'td'		=> array( 'style', 'class', 'colspan', 'rowspan' ),
		'th'		=> array( 'style', 'class', 'scope', 'colspan', 'rowspan' ),
		
		'q'		=> array( 'style', 'class', 'cite' ),
		'cite'		=> array( 'style', 'class' ),
		'abbr'		=> array( 'style', 'class' ),
		'blockquote'	=> array( 'style', 'class' ),
		
		// Stripped out
		'body'		=> array()
	);
	
	
	
	/**#@+
	 * HTML Filtering
	 */
	
	/**
	 * @link http://www.php.net/manual/en/function.strip-tags.php#68757
	 */
	public static function plainText( $data ) {
		$search = array('@<script[^>]*?>.*?</script>@si',	// Strip out javascript 
			'@<[\/\!]*?[^<>]*?>@si',			// Strip out HTML tags 
			'@<style[^>]*?>.*?</style>@siU',		// Strip style tags properly 
			'@<![\s\S]*?--[ \t\n\r]*>@'			// Strip multi-line comments including CDATA 
		);
		
		return preg_replace( $search, '', $data ); 
	}
	
	/**
	 * Convert content between code tags into HTML entities safe for display 
	 * 
	 * @param $val string Value to encode to entities
	 */
	public static function escapeCode( $val ) {
		if ( is_array( $val ) ) {
			$out = self::entities( $val[1], true );
			return '<code>' . $out . '</code>';
		}
		return '<code>' . $val . '</code>';
	}
	
	/**
	 * Convert an unformatted text block to paragraphs
	 * 
	 * @link http://stackoverflow.com/a/2959926
	 * @param $val string Filter variable
	 */
	protected function makeParagraphs( $val ) {
		/**
		 * Convert newlines to line breaks first
		 * This is why PHP both sucks and is awesome at the same time
		 */
		$out = nl2br( $val, true );
		
		/**
		 * Turn consecutive <br>s to paragraph breaks and wrap the 
		 * whole thing in a paragraph
		 */
		$out = '<p>' . preg_replace('~(?:<br\s*/?>\s*?){2,}~', '<p></p><p>', $out ) . '</p>';
		
		/**
		 * Remove <br> abnormalities
		 */
		$out = preg_replace( '~<p>(\s*<br\s*/?>)+~', '</p><p>', $out );
		$out = preg_replace( '~<br\s*/?>(\s*</p>)+~', '<p></p>', $out );
		$out = preg_replace( '~<p>(\s*<p>)+~', '<p>', $out );
		$out = preg_replace( '~</p>(\s*</p>)+~', '</p>', $out );
		return  '<body>' . $out . '</body>';
	}
	
	/**
	 * Filters HTML content through whitelist of tags and attributes
	 * 
	 * @param $val string Value filter
	 */
	public function filter( $val ) {
		if ( !isset( $val ) || empty( $val ) ) {
			return '';
		}
		
		/**
		 * Escape the content of any code blocks before we parse HTML or 
		 * they will get stripped
		 */
		$out = preg_replace_callback( 
				'/\<code\>(.+?)\<\/code\>/ims',
				"self::escapeCode", 
				$val
			);
		/**
		 * Convert to paragraphs and begin
		 */
		$out	= $this->makeParagraphs( $out );
		$errors = array();
		/**
		 * Hide parse warnings since we'll be cleaning the output anyway
		 */
		$err	= libxml_use_internal_errors( true );
		$dom	= new \DOMDocument();
		
		if ( $dom->loadHTML( tidy_repair_string( $out, self::$tidy ) ) ) { 
			$dom->encoding = 'utf-8';
			$out = $this->parse( $dom );
		} else {
			$xml	= explode( PHP_EOL, $out );
			foreach( libxml_get_errors() as $e ) {
				$errors[] = $this->xmlError( $e, $xml );
			}
		}
		
		/**
		 * Reset errors
		 */
		libxml_clear_errors();
		libxml_use_internal_errors( $err );
		/**
		 * These may be added by users as well.
		 */
		return trim( str_replace(
				array('<html>', '</html>', '<body>', '</body>' ), '', $out
		) );
	}
	
	/**
	 * Parse HTML
	 */
	protected function parse( &$dom ) {
		/**
		 * Clean body only (rest gets filtered)
		 */
		$body	= $dom->getElementsByTagName( 'body' )->item( 0 );
		if ( !$body->nodeName ) {
			/**
			 * Someone got creative with their XSS. Kill everything.
			 */
			return '';
		}
		
		$this->cleanNodes( $body, $badTags );
		/**
		 * Iterate through bad tags found above and convert them into harmless text
		 */
		foreach ( $badTags as $node ) {
			if ( $node->nodeName != "#text" ) {
				$ctext = $dom->createTextNode( $dom->saveHTML( $node ) );
				$node->parentNode->replaceChild( $ctext, $node );
			}
		}
		
		/**
		 * Filter the junk and return only the contents of the body tag
		 */
		return $this->postClean( $dom->saveHTML( $body ) );
	}
	
	/**
	 * Post-processing clean up
	 */
	protected function postClean( $out ) {
		$out = preg_replace( '~(<p><br\s*/?></p>)+~', '', $out );
		$out = preg_replace( '~(<li style="list-style: none"><br></li>)+~', '', $out );
		
		$out = preg_replace( '~>\n{1,}+<code~', '<br /><code', $out );
		$out = preg_replace( '~</code>\n{1,}+~', '</code><br />', $out );
		
		$out = preg_replace( '~>\s+<code~', '><code', $out );
		$out = preg_replace( '~</code>\s+<~', '</code><', $out );
		
		//$out = preg_replace( '~>\s+<~', '><', $val );
		
		//$out = preg_replace('~>\n<~', '><', $val);
		//$out = preg_replace( '~\s{2,}(?=[^>]*(<|$))+~', '', $out );
		//$out = preg_replace( '~</code></p><p><code>+~', "\n\n", $out );
		return $out;
	}
	
	/**
	 * Libxml custom error handling.
	 * @link http://www.php.net/manual/en/function.libxml-get-errors.php
	 */
	protected function xmlError( $e, $xml ) {
		$text = '';
		switch( $e->level ) {
			case LIBXML_ERR_FATAL:
				$text = "Fatal error";
				break;
				
			case LIBXML_ERR_ERROR:
				$text = "Error";
				break;
				
			case LIBXML_ERR_WARNING:
				$text = "Warning";
				break;
		}
		
		$text .= " {$e->code} at line {$e->line}, column {$e->column}\n\n" . 
			$xml[$e->line - 1] . "\n" . str_repeat( '-', $e->column ) . "\n\n";
		
		return $text . trim($e->message);
	}
	
	protected function cleanAttributeNode( 
		&$node, 
		&$attr, 
		&$goodAttributes, 
		&$href 
	) {
		/**
		 * Why the devil is an attribute name called "nodeName"?!
		 */
		$name = $attr->nodeName;
		
		/**
		 * And an attribute value is still "nodeValue"?? Damn you PHP!
		 */
		$val = $attr->nodeValue;
		
		/**
		 * Default action is to remove the attribute completely
		 * It's reinstated only if it's allowed and only after it's filtered
		 */
		$node->removeAttributeNode( $attr );
		
		if ( in_array( $name, $goodAttributes ) ) {
			switch ( $name ) {
				
				/**
				 * Validate URL attribute types
				 */
				case 'url':
				case 'cite':
				case 'src':
				case 'href':
				case 'longdesc':
					if ( self::urlFilter( $val ) ) {
						$href = $val;
					} else {
						$val = '';
					}
					break;
				
				/**
				 * Everything else gets default scrubbing
				 */
				default:
					if ( self::decodeScrub( $val ) ) {
						$val = self::entities( $val );
					} else {
						$val = '';
					}
			}
			
			if ( '' !== $val ) {
				$node->setAttribute( $name, $val );
			}
		}
	}
	
	/**
	 * Modify links to display their domains and add 'nofollow'.
	 * Also puts the linked domain in the title as well as the file name
	 */
	protected static function linkAttributes( &$node, $href ) {
		try {
			$parsed	= parse_url( $href );
			$title	= $parsed['host'];
			
			if ( isset( $parsed['path'] ) ) {
				$f	= pathinfo( $parsed['path'] );
				$title	.= empty( $f['basename'] )? 
						'' : ' ( ' . $f['basename'] . ' ) ';
			}
			$node->setAttribute( 'title', $title );
			
			if ( self::$options['nofollow'] ) {
				$node->setAttribute( 'rel', 'nofollow' );
			}
			
		} catch ( Exception $e ) { }
	}
	
	/**
	 * Iterate through each tag and add non-whitelisted tags to the 
	 * bad list. Also filter the attributes and remove non-whitelisted ones.
	 * 
	 * @param htmlNode $node Current HTML node
	 * @param array $badTags Cumulative list of tags for deletion
	 */
	protected function cleanNodes( $node, &$badTags = array() ) {
		if ( array_key_exists( $node->nodeName, self::$whitelist ) ) {
			if ( $node->hasAttributes() ) {
				
				/**
				 * Prepare for href attribute which gets special 
				 * treatment
				 */
				$href = '';
				
				/**
				 * Filter through attribute whitelist for this tag
				 */
				$goodAttributes = self::$whitelist[$node->nodeName];
				
				/**
				 * Check out each attribute in this tag
				 */
				foreach ( iterator_to_array( $node->attributes ) as $attr ) {
					$this->cleanAttributeNode( 
						$node, $attr, $goodAttributes, $href
					);
				}
				
				/**
				 * This is a link. Treat it accordingly
				 */
				if ( 'a' === $node->nodeName && '' !== $href ) {
					self::linkAttributes( $node, $href );
				}
				
			} // End if( $node->hasAttributes() )
			
			/**
			 * If we have childnodes, recursively call cleanNodes on those as well
			 */
			if ( $node->childNodes ) {
				foreach ( $node->childNodes as $child ) {
					$this->cleanNodes( $child, $badTags );
				}
			}
		} else {
			/**
			 * Not in whitelist so no need to check its child nodes. 
			 * Simply add to array of nodes pending deletion.
			 */
			$badTags[] = $node;
			
		} // End if array_key_exists( $node->nodeName, self::$whitelist )
		
	}
	
	/**#@-*/
	
	
	/**
	 * Returns true if the URL passed value is harmless.
	 * This takes into account Unicode domain names however, it doesn't check for TLD 
	 * (.com, .net, .mobi, .museum etc...) as that list is too long and changes a lot.
	 * The purpose is to ensure visitors are not harmed by invalid markup, not that they 
	 * get a functional domain name.
	 * 
	 * @param string $v Raw URL to validate
	 * @return boolean
	 */
	public static function urlFilter( $v ) {
		return (
			preg_match( self::$options['rx_url'], $v ) && 
			!preg_match( self::$options['rx_xss'], $v ) && 
			!preg_match( self::$options['rx_xss2'], $v ) && 
			!preg_match( self::$options['rx_esc'], $v )
		);
	}
	
	/**
	 * Regular expressions don't work well when used for validating HTML.
	 * It really shines when evaluating text so that's what we're doing here.
	 * 
	 * @param string $v string Attribute name
	 * @param int $depth Number of times to URL decode
	 * @return boolean True if nothing unsavory was found.
	 */
	public static function decodeScrub( $v ) {
		if ( empty( $v ) ) {
			return true;
		}
		
		$depth		= self::$options['scrub_depth'];
		$i		= 1;
		$success	= false;
		$old		= '';
		
		while( $i <= $depth && !empty( $v ) ) {
			// Check for any JS and other shenanigans
			if (
				preg_match( self::$options['rx_xss'], $v ) || 
				preg_match( self::$options['rx_xss2'], $v ) || 
				preg_match( self::$options['rx_esc'], $v )
			) {
				$success = false;
				break;
			} else {
				$old	= $v;
				$v	= self::utfdecode( $v );
				
				/**
				 * We found the the lowest decode level.
				 * No need to continue decoding.
				 */
				if ( $old === $v ) {
					$success = true;
					break;
				}
			}
			
			$i++;
		}
		
		
		/**
		 * If after decoding a number times, we still couldn't get to 
		 * the original string, then there's something still wrong
		 */
		//if ( $old !== $v && $i === $depth ) {
		//	return false;
		//}
		
		return $success;
	}
	
	public static function setEncoding( &$out ) {
		$enc = mb_detect_encoding( $out, "auto" );
		return mb_convert_encoding( $out, "UTF-8", $enc );
	}
	
	/**
	 * UTF-8 compatible URL decoding
	 * 
	 * @link http://www.php.net/manual/en/function.urldecode.php#79595
	 * @return string
	 */
	public static function utfdecode( $v ) {
		$v = urldecode( $v );
		$v = preg_replace( '/%u([0-9a-f]{3,4})/i', '&#x\\1;', $v );
		return html_entity_decode( $v, null, 'UTF-8' );
	}
	
	/**
	 * HTML safe character entities in UTF-8
	 * 
	 * @return string
	 */
	public static function entities( $v, $quotes = true ) {
		if ( $quotes ) {
			return htmlentities( 
				iconv( 'UTF-8', 'UTF-8', $v ), 
				ENT_NOQUOTES | ENT_SUBSTITUTE, 
				'UTF-8'
			);
		}
	
		return htmlentities( 
			iconv( 'UTF-8', 'UTF-8', $v ), 
			ENT_QUOTES | ENT_SUBSTITUTE, 
			'UTF-8'
		);
	}	
}
