<?php
/**
 * Experimental form building, user input ( GET, POST ) and validation
 * 
 * This version is meant to load configuration as raw JSON text
 * instead of hitting the database or loading a file
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.1a
 */
namespace Microthread;

class Forms {
	
	/**
	 * string Form expiration message
	 */
	const FORM_EXPIRED	= 'This form has expired';
	
	/**
	 * string Field/token hashing algorithm from hash_algos()
	 */
	const HASH_ALGO	= 'tiger160,4';
	
	/**#@+
	 * Error message tokens (for validator use)
	 */
	 
	const MISSING_TOKEN	= 'missing';
	const INVALID_TOKEN	= 'invalid';
	const BIND_TOKEN	= 'bind';
	
	/**#@-*/
	
	
	/**#@+
	 * Input field settings for most forms. Extend as necessary
	 */
	
	/**
	 * @var array Only HTML input attributes directly sent to the user
	 */
	private static $clientAttr	= array(
		'length', 'maxlength', 'pattern', 'required', 'rows', 'cols', 'placeholder', 
		'title', 'checked', 'size', 'disabled', 'value', 'readonly', 'size', 'href'
	);
	
	/**
	 * @var array HTML5 input field types
	 */
	private static $validInputs	= array(
		'text', 'password', 'email', 'search', 'url', 'textarea', 'select', 'file', 
		'radio', 'checkbox', 'hidden', 'datetime'
	);
	
	/**
	 * @var array Template helper attributes. These are not meant to be directly rendered 
	 * 		for the user
	 */
	private static $controlAttr	= array(
		'label', 'prefix', 'suffix', 'errors', 'autocomplete', 'multiple'
	);
	
	/**#@-*/
	
	
	/**
	 * Inputs are validated in the order they are specified in the config files
	 */
	private static function setValidation( 
		$fname, 
		$method = 'post', 
		&$fields, 
		&$errors = array() 
	) {
		$keys	= array_keys( $fields );
		$out	= self::retrieve( $fname, $fields, $method );
		
		foreach( $keys as $input ) {
			/**
			 * Anti XSRF token gets special treatment
			 */
			if ( 'cxn' === $input ) {
				if ( $out[$input] !== self::antiXSRF( $fname ) ) {
					$errors[] = self::FORM_EXPIRED;
					return null;
				}
			}
			self::validate( $input, $fields[$input], $out[$input], $errors );
			
			/**
			 * Bound inputs need special consideration
			 */
			self::boundInputs( $input,  $fields[$input], $errors, $fields );
		}
		
		return $out;
	}
	
	/**
	 * Validates given input according to specified options
	 */
	private static function validate(
		$input, 
		&$options, 
		&$value, 
		&$errors = array()
	) {
		if ( isset( $options['required'] ) ) {
			if ( empty( $value ) ) {
				$errors[$input] = self::errorMessage( 
					$options, self::MISSING_TOKEN 
				);
				return;
			}
		}
		
		/**
		 * No other validation options set? Nothing to do
		 */
		if ( !isset( $options['validate'] ) ) { return; }
		
		/**
		 * Modify validation regex as necessary
		 */
		switch( strtolower( $options['validate'] ) ) {
			case 'alphanum':
			case 'action':
			case 'command':
				self::clean( $value );
				$options['validate'] = '/^([A-Za-z0-9\-]{1,32})$/i';
				break;
				
			case 'id':
			case 'int':
			case 'page':
			case 'num':
			case 'number':
				self::clean( $value, true );
				$options['validate'] = '/^([1-9])(:?[0-9]+)?$/';
				//'/^([1-9][0-9]?+)$/';
				break;
				
			case 'search':
				self::clean( $value, true );
				$options['validate'] = '/^([\pL\pN\s_,.-]{3,100})$/i';
				break;
				
			case 'text':
			case 'title':
				self::clean( $value );
				break;
				
			case 'usr':
			case 'user':
			case 'username':
				self::clean( $value, true );
				$options['validate'] = '/^([\pL\pN\s_.-]{2,30})$/i';
				break;
				
			case 'pwd':
			case 'pass':
			case 'password':
				$options['validate'] = '/^(.*){4,255}$/i';
				break;
				
			case 'binary':
				$options['validate'] = '/^(0|1){1}$/i';
				break;
				
			case 'tag':
			case 'tags':
				self::clean( $value );
				$value = strtolower( $value );
				$options['validate'] = '/^(?:([\w\s\-_]{3,20}+)(?:,\s*)){1,3}$/';
				break;
				
			case 'mail':
			case 'email':
				$options['validate'] = '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i';
				break;
				
			case 'html':
				self::htmlValidate( $value, $options, $errors );
				return; // <-- IMPORTANT! We're not performing regex validation on 
					// HTML
		}
		
		/**
		 * Send custom regular expression
		 */
		self::regexValidate( $input, $value, $options, $errors );
	}
	
	/**
	 * Input pre-filter
	 */
	private static function clean( &$value, $entities = false ) {
		$value = preg_replace( '/\s{2,}/g', ' ', $value );
		$value = trim( $value, ', ' );
		if ( $entities ) {
			$value = Html::entities( $value );
		} else {
			$value = Html::plainText( $value );
		}
	}
	
	/**
	 * Regular expression form validation (this should NOT be used for HTML)
	 */
	private static function regexValidate( 
		$input, 
		&$value, 
		&$options, 
		&$errors
	) {
		var_dump( $options['validate'] );
		if ( !preg_match( $options['validate'], $value ) ) {
			$errors[$input] = self::errorMessage( $options, self::INVALID_TOKEN );
		}
	}
	
	/**
	 * Special case for HTML validation where regular expression filtering 
	 * doesn't work.
	 */
	private static function htmlValidate( 
		$input, 
		&$value, 
		&$options, 
		&$errors
	) {
		$html	= new Html;
		$value	= $html->filter( $value );
		if ( empty( $value ) && $options['required'] ) {
			$errors[$input] = self::errorMessage( $options, self::MISSING_TOKEN );
		}
	}
	
	/**
	 * Returns matching error message to invalid/missing input fields
	 */
	private static function errorMessage( &$options, $error ) {
		if ( !isset( $options['errorMsg'] ) ) { return; }
		foreach( $options['errorMsg'] as $msg ) {
			if ( $error === $msg ) {
				return $msg;
			}
		}
	}
	
	/**
	 * Validate inputs that are bound to others in the same form.
	 * I.E. They require certain other inputs to be valid and error-free.
	 */
	private static function boundInputs( 
		$input, 
		&$options, 
		&$errors, 
		&$fields
	) {
		$out = '';
		
		/**
		 * Check for the presence of bound inputs
		 */
		if ( isset( $options['bind'] ) ) {
			$ekeys = array_keys( $errors );
			foreach( $options['bind'] as $binput ) {
				if ( in_array( $binput, $ekeys ) ) {
					/**
					 * Add the label/friendly name
					 */
					if ( isset( $fields[$binput]['label'] ) ) {
						$out .= $fields[$binput]['label'] . ', ';
					}
				}
			}
			
			$out = rtrim( $out, ', ' );
		}
		
		/**
		 * Errors were present...
		 */
		if ( !empty( $out ) ) {
			/**
			 * Add the core error message first and append the rest
			 */
			$errors[$input] = 
				self::errorMessage( $options, self::BIND_TOKEN ) . '<br /> ' . $out;
		}
	}
	
	/**
	 * Sets the anti cross-site-request forgery (XSRF) token in the session.
	 * This should be called for each page refresh.
	 */
	private static function resetXSRF( $fname = null ) {
		if ( !isset( $_SESSION['cxs'] ) || empty ( $_SESSION['cxs'] ) ) {
			$_SESSION['cxs'] = array();
		}
		if ( null !== $fname ) {
			$_SESSION['cxs'][$fname] = hash( self::HASH_ALGO, uCrypt::IV( 5 ) );
		}
	}
	
	/**
	 * Gets the XSRF token (optionally calls resetXSRF() to generate it if 
	 * it doesn't already exist or if regeneration is requested)
	 * 
	 * @param string $fname Form name/id (each one gets a unique token)
	 * @param boolean $regen If true regenerates the token for a forum
	 */
	private static function antiXSRF( $fname, $regen = false ) {
		if ( 
			!isset( $_SESSION['cxs'][$fname] ) || 
			empty ( $_SESSION['cxs'][$fname] ) || 
			$regen 
		) {
			self::resetXSRF( $fname );
		}
		
		return $_SESSION['cxs'][$fname];
	}
	
	/**#@-*/
	
	
	
	/**#@+
	 * User input retrieval
	 */
	
	/**
	 * Encoded input field key (hashed with anti-XSRF token)
	 */
	private static function fieldKey( $fname, $name ) {
		return  hash(
				self::HASH_ALGO, 
				$name . self::antiXSRF( $fname ) . Util::getSig() 
			);
	}
	
	/**
	 * Retrieves user input from encoded $_POST or $_GET field key
	 */
	private static function fromUser( $fname, $name, $isGet = true ) {
		$name = self::fieldKey( $fname, $name );
		if ( $isGet ) {
			return isset( $_GET[$name] )? $_GET[$name] : null;
		}
		return isset( $_POST[$name] )? $_POST[$name] : null;
	}
	
	/**
	 * Retrieves user input from specified (encoded) form field keys
	 */
	private static function retrieve( $fname, &$form, $method = 'post' ) {
		$out	= array();
		$fields = array_keys( $form );
		$m	= ( 'post' === $method )? false : true;
		
		foreach( $fields as $input ) {
			if ( is_array( $form[$input] ) && isset( $form[$input]['type'] ) ) {
				$out[$input] = self::fromUser( $fname, $input, $m );
			}
		}
		
		return $out;
	}
	
	/**
	 * Load form configuration and return validation results
	 */
	public static function loadData( $form, $method,  &$errors = array() ) {
		$fields	= json_decode( $form, true );
		return self::setValidation( $form, $method, $fields, $errors );
	}
	
	
	/**#@+
	 * Form building helpers
	 */
	
	/**
	 * Loads form configuration and sets anti-XSRF field (each page refresh).
	 * Send to builder
	 * NOTE: If the user sends a form back, it's important to validate it first before 
	 * 	rendering the form again as a re-render will destroy the anti-XSRF token.
	 * 
	 * @param string $form The form name (unique anti-XSRF token for each one)
	 * @param array $config Form field configuration in JSON format
	 * @param object $data Item object this form is applying to
	 */
	public static function buildForm( $form, $config, $data = null ) {
		$inputs			= json_decode( $config, true );
		if ( empty( $inputs ) ) {
			return null;
		}
		$inputs['cxn']['value']	= self::antiXSRF( $form, true );
		if ( isset( $inputs['captcha'] ) ) {
			$captcha		= 'Hello'; // Testing for now
			$_SESSION['captcha']	= $captcha;
		}
		
		if ( null !== $data ) {
			foreach( $data as $k => $v ) {
				foreach( $inputs as $i => $j ) {
					if ( $k === $i ) {
						$inputs[$i]['value'] = $v;
					}
				}
			}
		}
		return self::build( $form, $inputs );
	}
	
	/**
	 * Builds HTML input field
	 */
	private static function input( $fname, $name, $options = array() ) {
		$name		= self::fieldKey( $fname, $name );
		$type		= 'text';
		$out		= '';
		$value		= '';
		
		/**
		 * 'type' Is a special option
		 */
		if ( isset( $options['type'] ) ) {
			$type	= $options['type'];
			if ( 'captcha' == $type ) {
				$type = 'text';
			}
		}
		
		/**
		 * Likewise, 'value'
		 */
		if ( isset( $options['value'] ) ) {
			$value	= $options['value'];
		}
		
		if ( 'textarea' === $type ) {
			$out .= '<textarea';
		} elseif ( 'link' === $type ) {
			$out .= "<a ";
		} else {
			$out .= "<input type='$type'";
		}
		$out .= " id='$name' name='$name'";
		
		/**
		 * Set client attribute values
		 */
		foreach( self::$clientAttr as $k ) {
			if ( isset( $options[$k] ) ) {
				$out .= " $k='$options[$k]'";
			}
		}
		
		/**
		 * Set special property 'value'
		 */
		if ( isset( $value ) ) {
			if ( 'textarea' === $type || 'link' === $type ) {
				$out .= '>' . $value;
			} else {
				$out .= " value='$value'";
			}
		} else {
			if ( 'textarea' === $type ) {
				$out .= '>';
			}
		}
		
		/**
		 * Complete input/textarea closing tags
		 */
		if ( 'textarea' === $type ) {
			$out .= '</textarea>';
		} elseif ( 'link' === $type ) {
			$out .= '</a>';
		} else {
			$out .= ' />';
		}
		
		$options['value'] = $value;
		return $out;
	}
	
	/**
	 * Build input form according to provided configuration
	 */
	private static function build( $fname, $form ) {
		$out = array();
		
		foreach( $form as $input => $meta ) {
			if ( !is_array( $meta ) || !isset( $meta['type'] ) ) {
				continue;
			}
			$mkeys		= array_keys( $meta );
			$out[$input]	= array();
			foreach( self::$controlAttr as $attr ) {
				if ( in_array( $attr, $mkeys ) ) {
					$out[$input][$attr] = $meta[$attr];
				}
			}
			
			$out[$input]['html'] = self::input( $fname, $input, $meta );	
		}
		
		return $out;
	}
	
	/**#@-*/
}
