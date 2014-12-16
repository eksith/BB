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
	
	public function __construct( array $data = null ) {
		if ( empty( $data ) ) {
			return;
		}
		
		foreach ( $data as $field => $value ) {
			$this->$field = $value;
		}
	}
	
	public function hasReplies() {
		return ( $this->reply_count > 0 )? true : false;
	}
	
	public function isRoot() {
		return ( $this-> id == $this->root_id )? true : false;
	}
	
	public function isParent() {
		return ( $this-> id == $this->parent_id )? true : false;
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
		$id	= 0;
		$order	= ' ORDER BY ';
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
			
		} elseif ( $isset( $filter['thread'] ) ) {
			$params[':root_id'] = $id;
			$sql .= ', p.body AS body';
			$from	.= ' WHERE posts_family.root_id = :root_id';
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
			$this->posts_limit	= 1;
			$sql			.= ';';
			
		} else {
			$page	= ( isset( $filter['page'] ) ) ? ( $filter['page'] ) : 1;
		
			$this->posts_limit	= ( $id > 0 ) ? self::POST_LIMIT : self::TOPIC_LIMIT;
			$params[':limit']	= $this->posts_limit;
			$params[':offset']	= ( $page - 1 ) * $params[':limit'];
		
			$sql	.= ' LIMIT :limit OFFSET :offset;';
		}
		parent::init();
		$stmt	= self::$db->prepare( $sql );
		$stmt->execute( $params );
		
		return $stmt->fetchAll( \PDO::FETCH_CLASS, 'BB\Post' );
	}
	
	/**
	 * Create or edit this post
	 */
	public function save( $auth ) {
		$edit = empty( $this->id )? false : true;
		$this->filterProperties();
		
		$params = array(
			':t'	=> $this->title, 
			':b'	=> $this->body, 
			':r'	=> $this->raw,
			':p'	=> $this->plain,
			':s'	=> $this->summary,
			':a'	=> $auth
		);
		
		if ( $edit ) { // Editing an existing post
			$params[':id'] = $this->id;
			$sql = "UPDATE posts SET title = :t, body = :b, raw = :r, plain = :p, 
					summary = :s, auth_key = :a WHERE id = :id;";
		
		} else {	// Creating a new post
			$sql = "INSERT INTO posts ( title, body, raw, plain, summary, auth_key ) 
				VALUES ( :t, :b, :r, :p, :s, :a );";
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
			$sql	= 'DELETE FROM posts WHERE id = :i;';
		} else {
			$sql	= 'UPDATE posts SET status = -1 WHERE id = :i;';
		}
		
		parent::init();
		$stmt = self::$db->prepare( $sql );
		$stmt->execute( array( ':i' => $id ) );
	}
	
	public static function lockOld( $period ) {
		$sql = "UPDATE posts SET status = 2 
			WHERE ( julianday( 'now' ) - julianday( 'reply_at' ) ) > :p 
			AND status NOT IN ( -1, 1, 2, 99);";
		
		parent::init();
		$stmt = self::$db->prepare( $sql );
		$stmt->execute( array( ':p' => $period ) );
	}
	
	public static function changeStatus( $id, $status ) {
		$sql  = 'UPDATE posts SET status = :s WHERE id = :i ;';
		
		parent::init();
		$stmt = self::$db->prepare( $sql );
		$stmt->execute( array(
			':s' => $status,
			':i' => $id
		) );
	}
	
	public static function putVote( $id, $vote ) {
		$sql  = 'INSERT INTO post_votes ( post_id, vote ) VALUES ( :p, :v );';
		
		parent::init();
		$stmt = self::$db->prepare( $sql );
		$stmt->execute( array(
			':p' => $id,
			':v' => $vote
		) );
	}
	
	private function saveFamily() {
		$sql	 = "INSERT INTO posts_family ( root_id, parent_id, child_id ) 
				VALUES ( :r, :p, :i );";
		
		// This is a new thread
		if ( empty( $this->root_id ) && empty( $this->parent_id ) ) {
			$this->root_id		= $this->id;
			$this->parent_id	= $this->id;
		// Reply to the root post
		} elseif ( empty( $this->parent_id ) ) {
			$this->parent_id	= $this->id;	
		}
		
		$params	= array(
				'root_id'	=> $this->root_id,
				'parent_id'	=> $this->parent_id,
				'child_id'	=> $this->id
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
			$html = new \Microthread\Html();
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
