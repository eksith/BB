<?php
/**
 * Basic forum post.
 *
 * @author Eksith Rodrigo <reksith at gmail.com>
 * @license http://opensource.org/licenses/ISC ISC License
 * @version 0.3
 */
namespace BB;

class Post extends Data {
	const TITLE_LENGTH	= 80;
	
	const POST_LIMIT	= 30;
	
	const TOPIC_LIMIT	= 60;
	
	/**
	 * @var int Post root id ( 0 = no root )
	 */
	public $root_id		= 0;
	
	/**
	 * @var int Post parent id ( 0 = no parent )
	 */
	public $parent_id	= 0;
	
	/**
	 * @var string Root title
	 */
	public $topic_title;
	
	/**
	 * @var int Number of replies to root
	 */
	public $topic_replies	= 0;
	
	/**
	 * @var int Post root status
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
	
	public function __construct( array $data = null ) {
		if ( empty( $data ) ) {
			return;
		}
		
		foreach ( $data as $field => $value ) {
			$this->$field = $value;
		}
	}
	
	public function hasReplies() {
		return ( $this->reply_count > 0 ) ? true : false;
	}
	
	public function isRoot() {
		return ( $this->id == $this->root_id ) ? true : false;
	}
	
	public function isParent() {
		return ( $this->id == $this->parent_id ) ? true : false;
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
	
	public static function find( $filter = array() ) {
		$params	= array();
		$result	= new Thread();
		$thread	= false;
		$order	= ' ORDER BY ';
		$id	= 0;
		
		$sql	=
			"SELECT p.id AS id, p.title AS title, p.created_at AS created_at, 
			p.reply_count AS reply_count, parent.title AS parent_title, 
			parent.status AS parent_status, parent.reply_at AS last_reply, 
			root.title as topic_title, root.reply_count AS topic_replies, 
			root.status AS topic_status, posts_family.root_id AS root_id, 
			posts_family.parent_id AS parent_id, p.auth_key AS auth_key";
		
		$from	= 
			" FROM posts AS p
			INNER JOIN posts_family ON p.id = posts_family.child_id 
			LEFT JOIN posts AS parent ON posts_family.parent_id = parent.id 
			LEFT JOIN posts AS root ON posts_family.root_id = root.id";
		
		if ( isset( $filter['id'] ) || isset( $filter['edit'] ) ) {
			if ( isset( $filter['id'] ) ) {
				$sql	.= ', p.body AS body';
				$id	= $filter['id'];
				
			} elseif ( isset( $filter['edit'] ) ) {
				$sql .= ', p.raw AS raw';
				$id	= $filter['edit'];
			}
			
			$params[':id'] = $id;
			$from	.= ' WHERE p.id = :id';
			$order	.= 'id DESC';
			
		} elseif ( isset( $filter['thread'] ) || isset( $filter['sub'] ) ) {
			$thread = true;
			if ( isset( $filter['sub'] ) ) {
				$params[':parent_id'] = $filter['sub'];
				$from	.= ' WHERE posts_family.parent_id = :parent_id';
				
			} elseif( isset( $filter['thread'] ) ) {
				$params[':root_id'] = $filter['thread'];
				$from	.= ' WHERE posts_family.root_id = :root_id';
			}
			
			$sql	.= ', p.body AS body';
			$order	.= 'id ASC';
			
		} else {
			$sql	.= ', p.summary AS summary';
			$order	.= 'id DESC';
		}
		
		if ( isset( $filter['new'] ) ) {
			$from	.= ' AND p.status > -1';
		} else {
			$from	.= ' AND p.status > 0';
		}
		
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
		$stmt	= self::$db->prepare( $sql );
		$stmt->execute( $params );
		
		
		if ( isset( $filter['id'] ) || isset( $filter['edit'] ) ) {
			return $stmt->fetchAll( \PDO::FETCH_CLASS, '\BB\Post' )[0];
		}
		
		if ( isset( $filter['thread'] ) ) {
			$result->id		= $filter['thread'];
		}
		
		$stmt->setFetchMode( \PDO::FETCH_CLASS, 'BB\Post');
		while( $row = $stmt->fetch() ) {
			if ( isset( $filter['sub'] ) ) {
				$result->parent_id = $row->parent_id;
			} else {
				$result->parent_id = $row->root_id;
			}
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
			':auth'		=> $auth
		);
		
		if ( $edit ) { // Editing an existing post
			$params[':id'] = $this->id;
			$sql = "UPDATE posts SET title = :title, body = :body, raw = :raw, 
					plain = :plain, summary = :summary, status = :status, 
					auth_key = :auth WHERE id = :id;";
		
		} else {	// Creating a new post
			$sql = "INSERT INTO posts ( title, body, raw, plain, summary, status, auth_key ) 
				VALUES ( :title, :body, :raw, :plain, :summary, :status, :auth );";
		}
		
		parent::init();
		$stmt	= self::$db->prepare( $sql );
		
		if ( $edit ) {	// Editing post
			$rows = $stmt->execute( $params );
			if ( $rows > 0 ) {
				return true;
			}
		} else {	// New post
			$stmt->execute( $params );
			$this->id = self::$db->lastInsertId();
			if ( $this->id ) {
				$this->saveFamily();
				return true;
			}
		}
		
		return false;
	}
	
	public static function delete( $id, $permanant = false ) {
		if ( $permanant ) {
			$sql	= 'DELETE FROM posts WHERE id = :id;';
		} else {
			$sql	= 'UPDATE posts SET status = -1 WHERE id = :id;';
		}
		
		parent::init();
		$stmt = self::$db->prepare( $sql );
		$stmt->execute( array( ':id' => $id ) );
	}
	
	public static function lockOld( $period ) {
		$sql = "UPDATE posts SET status = 2 
			WHERE ( julianday( 'now' ) - julianday( 'reply_at' ) ) > :period 
			AND status NOT IN ( -1, 1, 2, 99);";
		
		parent::init();
		$stmt = self::$db->prepare( $sql );
		$stmt->execute( array( ':period' => $period ) );
	}
	
	public static function changeStatus( $id, $status ) {
		$sql  = 'UPDATE posts SET status = :status WHERE id = :id ;';
		
		parent::init();
		$stmt = self::$db->prepare( $sql );
		$stmt->execute( array(
			':status' => $status,
			':id' => $id
		) );
	}
	
	public static function putVote( $id, $vote ) {
		$sql  = 'INSERT INTO post_votes ( post_id, vote ) VALUES ( :parent, :vote );';
		
		parent::init();
		$stmt = self::$db->prepare( $sql );
		$stmt->execute( array(
			':parent'	=> $id,
			':vote'		=> $vote
		) );
	}
	
	private function saveFamily() {
		$sql	 = "INSERT INTO posts_family ( root_id, parent_id, child_id ) 
				VALUES ( :root, :parent, :child );";
		
		// This is a new thread
		if ( empty( $this->root_id ) && empty( $this->parent_id ) ) {
			$this->root_id		= $this->id;
			$this->parent_id	= $this->id;
			
		// Reply to the root post
		} elseif ( empty( $this->parent_id ) ) {
			$this->parent_id	= $this->id;	
		}
		
		$params	= array(
				'root'		=> $this->root_id,
				'parent'	=> $this->parent_id,
				'child'		=> $this->id
			);
		
		parent::init();
		$stmt = self::$db->prepare( $sql );
		$stmt->execute( $params );
	}
	
	/**
	 * Cut off excess content without cutting words in the middle
	 */
	public static function smartTrim( $val, $max = 100 ) {
		$val	= trim( $val );
		$len	= mb_strlen( $val );
		
		if ( $len <= $max ) {
			return $val;
		}
		
		$out	= '';
		$words	= preg_split( '/([\.\s]+)/', $val, -1, 
				PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_DELIM_CAPTURE );
			
		for ( $i = 0; $i < count( $words ); $i++ ) {
			$w = $words[$i];
			// Add if this word's length is less than total string length
			if ( $w[1] <= $max ) {
				$out .= $w[0];
			}
		}
		
		return $out;
	}
	
	/**
	 * Format filter object properties to acceptable formats
	 */
	protected function filterProperties() {
		if ( isset( $this->raw ) && '' != trim( $this->raw ) ) {
			$html			= new \Microthread\Html();
			$this->body		= $html->filter( $this->raw );
			$this->plain		= \Microthread\Html::plainText( $this->body );
		}
		
		if ( isset( $this->summary ) ) {
			$this->summary		= \Microthread\Html::plainText( $this->summary );
			$this->summary		= self::smartTrim( $this-summary );
			
		} else {
			$this->summary		= self::smartTrim( $this-plain );
		}
		
		if ( isset( $this->title ) ) {
			$this->title = \Microthread\Html::entities( $this->title );
			$this->title = self::smartTrim( $this->title, self::TITLE_LENGTH );
			
		} elseif ( isset( $this->plain ) ) {
			$this->title = self::smartTrim( $this->plain, self::TITLE_LENGTH );
			
		} else {
			$this->title = 'No title';
		}
	}
}
