<?php
/**
 * Basic forum post.
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.4
 */
namespace BB\Models;

class Post extends Model {
	const TITLE_LENGTH	= 80;
	
	/**
	 * @var int Post topic id ( 0 = this is a new topic )
	 */
	public $topic_id	= 0;
	
	/**
	 * @var int Post parent id ( 0 = no parent )
	 */
	public $parent_id	= 0;
	
	/**
	 * @var string Topic title
	 */
	public $topic_title;
	
	/**
	 * @var int Number of replies to topic
	 */
	public $topic_replies	= 0;
	
	/**
	 * @var int Post topic status
	 */
	public $topic_status;
	
	/**
	 * @var string Parent title
	 */
	public $parent_title;
	
	/**
	 * @var int Post parent status
	 */
	public $parent_status;
	
	/**
	 * @var string Post title without HTML
	 */
	public $title;
	
	/**
	 * @var string Content summary stripped of HTML. If empty, will extract from $plain
	 */
	public $summary;
	
	/**
	 * @var string Raw user input set directly for editing (don't display this as HTML!)
	 */
	public $raw;
	
	/**
	 * @var string Filtered, HTML formatted content (don't set this property directly)
	 */
	public $body;
	
	/**
	 * @var string Content stripped of HTML formatting (don't set this property directly)
	 */
	public $plain;
	
	/**
	 * @var date Last reply to parent
	 */
	public $last_reply;
	
	/**
	 * @var int Number of replies to this post
	 */
	public $reply_count	= 0;
	
	/**
	 * @var float Post quality measured by vote (set by database)
	 */
	public $quality;
	
	/**
	 * @var int Display helper for maximum number of pages
	 */
	public $posts_limit	= 1;
	
	/**
	 * @var string Edit/Delete authorization key
	 */
	public $auth_key;
	
	/**
	 * @var array Tags, Categories, etc...
	 */
	public $taxonomy	= array();
	
	public static $fields	= array(
		'p'		=> array( 'id', 'title', 'created_at', 'reply_count', 'auth_key' ), 
		'parent'	=> array(
				'title'		=> 'parent_title',
				'status'	=> 'parent_status',
				'reply_at'	=> 'last_reply'
			),
		'topic'		=> array(
				'title'		=> 'topic_title',
				'reply_count'	=> 'topic_replies',
				'status'	=> 'topic_status',
			),
		'posts_family'	=> array(
				'topic_id'	=> 'topic_id',
				'parent_id'	=> 'parent_id',
			)
		);
	
	public static $from	= 
			" FROM posts AS p
			INNER JOIN posts_family ON p.id = posts_family.child_id 
			LEFT JOIN posts AS parent ON posts_family.parent_id = parent.id 
			LEFT JOIN posts AS topic ON posts_family.topic_id = topic.id";
			
	public static $order	= ' ORDER BY id ';
	
	public function hasReplies() {
		return ( $this->reply_count > 0 ) ? true : false;
	}
	
	public function isTopic() {
		return ( $this->id == $this->topic_id ) ? true : false;
	}
	
	public function isParent() {
		return ( $this->id == $this->parent_id ) ? true : false;
	}
	
	public static function find( $filter = array() ) {
		if ( !isset( $filter['id'] ) || !isset( $filter['edit'] ) ) {
			return null;
		}
		$params = array();
		$fields = self::$fields;
		
		if ( isset( $filter['id'] ) ) {
			$fields['p'][] = 'body';
			$id	= $filter['id'];
			
		} else {
			$fields['p'][] = 'raw';
			$id	= $filter['edit'];
		}
		
		$params['id'] = $id;
		$from	= self::$from . ' WHERE p.id = :id';
		$order	= self::$order . 'DESC';
		
		$sql	= Storage::select( $fields );
		$sql	= $sql . $from . $order;
	}
	
	public static function save( &$post ) {
		$post->filterProperties();
		$params = array(
			'title'		=> $post->title, 
			'body'		=> $post->body, 
			'raw'		=> $post->raw,
			'plain'		=> $post->plain,
			'summary'	=> $post->summary,
			'status'	=> $post->status,
			'auth_key'	=> $post->auth_key
		);
			
		if ( $edit ) {	// Editing post
			Storage::edit(
				CONTENT_STORE, 
				$params, 
				'posts', 
				array( 'id' => $post-> id )
			);
		} else {
			$params['topic_id']	= $post->topic_id,
			$params['parent_id']	= $post->parent_id,
			$post->id = Storage::put( 
					CONTENT_STORE, 
					$params, 
					'posts', 
					true 
				);
		}
	}
	
	/**
	 * Format filter object properties to acceptable formats
	 */
	public function filterProperties() {
		if ( isset( $this->raw ) && !empty( trim( $this->raw ) ) ) {
			$html			= new Microthread\Html();
			$this->body		= $html->filter( $this->raw );
			$this->plain		= Microthread\Html::plainText( $this->body );
		}
		
		if ( isset( $this->summary ) ) {
			$this->summary		= Microthread\Html::plainText( $this->summary );
			$this->summary		= Helpers::smartTrim( $this-summary );
			
		} else {
			$this->summary		= Helpers::smartTrim( $this-plain );
		}
		
		if ( isset( $this->title ) ) {
			$this->title = Helpers::smartTrim( $this->title, self::TITLE_LENGTH );
			$this->title = Microthread\Html::entities( $this->title );
			
		} elseif ( isset( $this->plain ) ) {
			$this->title = Helpers::smartTrim( $this->plain, self::TITLE_LENGTH );
			
		} else {
			$this->title = 'No title';
		}
	}
}
