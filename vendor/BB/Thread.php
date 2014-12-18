<?php
/**
 * Post collection
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.1
 */
namespace BB;

class Thread {
	public $id		= 0;
	
	public $parent_id	= 0;
	
	public $page		= 1;
	
	public $limit		= 1;
	
	public $total;
	
	public $posts;
	
	public function __construct() {
		$this->posts	= new \SplObjectStorage();
	}
	
	public function add( $post ) {
		$this->attach( $post );
	}
	
	public function each( $callback ) {
		if ( !is_callable( $callback ) ) {
			return;
		}
		$this->posts->rewind();
		
		while( $this->posts->valid() ) {
			call_user_func( $callback, $this->posts->current() );
		}
		$this->posts->rewind();
	}
	
	public function hasPosts() {
		return ( $this->posts->count() > 0 ) ? true : false;
	}
	
	public function postCount() {
		return $this->posts->count();
	}
}
