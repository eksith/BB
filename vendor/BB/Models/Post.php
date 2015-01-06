<?php
/**
 * Basic forum post.
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.3
 */
namespace BB\Models;
use Microthread;

class Post extends Data {
	const TITLE_LENGTH	= 80;
	
	const POST_LIMIT	= 30;
	
	const TOPIC_LIMIT	= 60;
	
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
	public $taxonomy		= array();
	
	
	public function __construct( array $data = null ) {
		if ( empty( $data ) ) {
			return;
		}
		
		foreach ( $data as $field => $value ) {
			$this->$field = $value;
		}
	}
	
	public function __set( $name, $value ) {
		if ( 'taxonomyData' == $name ) {
			$this->taxonomy	= parent::parseAggregate( $value );
		}
	}
	
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
		$thread = false;
		$result = new Thread();
		$fields = array(
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
		
		$from	= 
			" FROM posts AS p
			INNER JOIN posts_family ON p.id = posts_family.child_id 
			LEFT JOIN posts AS parent ON posts_family.parent_id = parent.id 
			LEFT JOIN posts AS topic ON posts_family.topic_id = topic.id";
		
		if ( isset( $filter['id'] ) || isset( $filter['edit'] ) ) {
			if ( isset( $filter['id'] ) ) {
				$fields['p'][] = 'body';
				$id	= $filter['id'];
				
			} elseif ( isset( $filter['edit'] ) ) {
				$fields['p'][] = 'raw';
				$id	= $filter['edit'];
			}
			
			$params[':id'] = $id;
			$from	.= ' WHERE p.id = :id';
			$order	.= 'id DESC';
			
		} elseif ( isset( $filter['thread'] ) || isset( $filter['sub'] ) ) {
			$thread	= true;
			if ( isset( $filter['sub'] ) ) {
				$result->parent_id	= $filter['sub'];
				$params[':parent_id']	= $filter['sub'];
				$from			.= ' WHERE posts_family.parent_id = :parent_id';
				
			} elseif( isset( $filter['thread'] ) ) {
				$result->id		= $filter['thread'];
				$params[':root_id']	= $filter['thread'];
				$from			.= ' WHERE posts_family.root_id = :root_id';
			}
			
			$fields['p'][] = 'body';
			$order	.= 'id ASC';
			
		} else {
			$fields['p'][] = 'summary';
			$order	.= 'id DESC';
		}
		
		if ( isset( $filter['new'] ) ) {
			$from	.= ' AND p.status > -1';
		} else {
			$from	.= ' AND p.status > 0';
		}
		
		
		$sql	= parent::_select( $fields );
		$sql	= $sql . $from . $order;
		
		// No need for limits if this is a single post
		if ( isset( $filter['id'] ) || isset( $filter['edit'] ) ) {
			$sql			.= ';';
			
		} else {
			$page			= ( isset( $filter['page'] ) ) ? 
							( $filter['page'] ) : 1;
			
			$result->page		= $page;
			$result->limit		= ( $thread ) ? 
							self::POST_LIMIT : self::TOPIC_LIMIT;
			
			$params[':limit']	= $result->limit;
			$params[':offset']	= ( $page - 1 ) * $params[':limit'];
		
			$sql	.= ' LIMIT :limit OFFSET :offset;';
		}
		
		parent::init();
		$stmt	= parent::$db->prepare( $sql );
		$stmt->execute( $params );
		
		if ( isset( $filter['id'] ) || isset( $filter['edit'] ) ) {
			return $stmt->fetchAll( \PDO::FETCH_CLASS, '\BB\Post' )[0];
		}
		
		$stmt->setFetchMode( \PDO::FETCH_CLASS, 'BB\Post');
		while( $row = $stmt->fetch() ) {
			$result->add( $row );
		}
		
		return $result;
	}
	
	/**
	 * Create or edit this post
	 */
	public function save( $auth ) {
		$edit = empty( $this->id )? false : true;
		$this->filterProperties();
		
		$params = array(
			':title'	=> $this->title, 
			':body'		=> $this->body, 
			':raw'		=> $this->raw,
			':plain'	=> $this->plain,
			':summary'	=> $this->summary,
			':status'	=> $this->status,
			':auth_key'	=> $auth
		);
		
		if ( $edit ) { // Editing an existing post
			$params[':id'] = $this->id;
			$sql = "UPDATE posts SET " . parent:: _setParams(
			
			$sql. = "WHERE id = :id;";
		
		} else {	// Creating a new post
			$params[':topic_id']	= $this->topic_id,
			$params[':parent_id']	= $this->parent_id,
			
			$sql = "INSERT INTO posts ( topic_id, parent_id, title, body, raw, plain, summary, status, auth_key ) 
				VALUES ( :topic_id, :parent_id, :title, :body, :raw, :plain, :summary, :status, :auth );";
		}
		
		parent::init();
		$stmt	= parent::$db->prepare( $sql );
		
		if ( $edit ) {	// Editing post
			$rows = $stmt->execute( $params );
			if ( $rows > 0 ) {
				return true;
			}
		} else {	// New post
			$stmt->execute( $params );
			$this->id = parent::$db->lastInsertId();
			if ( $this->id ) {
				return true;
			}
		}
		
		return false;
	}
	
	public static function getInfo( $id ) {
		$sql	= "SELECT title, created_at, auth_key FROM posts WHERE id = :id";
		
		parent::init();
		$stmt	= self::$db->prepare( $sql );
		$stmt->execute( array( ':id' => $id ) );
	
		$result	= $stmt->fetchAll( \PDO::FETCH_ASSOC );
		if ( empty( $result ) ) {
			return array();
		}
		
		return $result[0];
	}
	
	public static function delete( $id, $permanant = false ) {
		if ( $permanant ) {
			$sql	= 'DELETE FROM posts WHERE id = :id;';
		} else {
			$sql	= 'UPDATE posts SET status = -1 WHERE id = :id;';
		}
		
		parent::init();
		$stmt = parent::$db->prepare( $sql );
		$stmt->execute( array( ':id' => $id ) );
	}
	
	public static function lockOld( $period ) {
		$sql = "UPDATE posts SET status = 2 
			WHERE ( julianday( 'now' ) - julianday( 'reply_at' ) ) > :period 
			AND status NOT IN ( -1, 1, 2, 99);";
		
		parent::init();
		$stmt = parent::$db->prepare( $sql );
		$stmt->execute( array( ':period' => $period ) );
	}
	
	public static function changeStatus( $id, $status ) {
		$sql  = 'UPDATE posts SET status = :status WHERE id = :id ;';
		
		parent::init();
		$stmt = parent::$db->prepare( $sql );
		$stmt->execute( array(
			':status' => $status,
			':id' => $id
		) );
	}
	
	public static function putVote( $id, $vote ) {
		$sql  = 'INSERT INTO post_votes ( post_id, vote ) VALUES ( :parent, :vote );';
		
		parent::init();
		$stmt = parent::$db->prepare( $sql );
		$stmt->execute( array(
			':parent'	=> $id,
			':vote'		=> $vote
		) );
	}
	
	protected static function applyTaxonomy( $taxonomy = array() ) {
		foreach( $taxonomy as $taxo => $values ) {
			if ( !is_array( $values ) ) {
				$values = array_map( 'trim', explode( ',', $values ) );
			}
			
			
		}
	}
	
	/**
	 * Format filter object properties to acceptable formats
	 */
	protected function filterProperties() {
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
