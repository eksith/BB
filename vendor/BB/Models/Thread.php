<?php
/**
 * Post collection
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.2
 */
namespace BB\Models;

class Thread extends Model {
	const POST_LIMIT	= 30;
	
	const TOPIC_LIMIT	= 60;
	
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
	
	public static function find( $filter = array() ) {
		$thread	=  false;
		$params = array();
		$fields = Post::$fields;
		
		$page			= ( isset( $filter['page'] ) ) ? 
							( $filter['page'] ) : 1;
		
		if ( isset( $filter['thread'] ) || isset( $filter['sub'] ) ) {
			$thread	= true;
			$order	= Post::$order . 'ASC';
		} else {
			$order	= Post::$order . 'DESC';
		}
		
		$fields['p'][] = 'summary';
		$order	.= 'id DESC';
		$result->page		= $page;
		$result->limit		= ( $thread ) ? 
						self::POST_LIMIT : self::TOPIC_LIMIT;
			
		$params['limit']	= $result->limit;
		$params['offset']	= ( $page - 1 ) * $params['limit'];
	
		$sql	.= ' LIMIT :limit OFFSET :offset;';
		
		$result = new Thread();
		Storage::find( 
			CONTENT_STORE,
			$sql, 
			$params, 
			'multiple', 
			'\BB\Post', 
			$result 
		);
		return $result;
	}
}
