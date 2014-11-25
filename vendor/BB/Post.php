<?php
namespace BB;

class Post extends Data {
	public $root_id		= 0;
	
	public $parent_id	= 0;
	
	public $title;
	
	public $parent_title;
	
	public $created_at;
	
	public $updated_at;
	
	public $last_reply;
	
	public $summary;
	
	public $body;
	
	public $raw;
	
	public $plain;
	
	public $reply_count	= 0;
	
	public $quality;
	
	public $auth_key;
	
	public $topic_status;
	
	public $status;
	
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
		$sql	= "SELECT created_at, auth_key FROM posts WHERE id = :id";
		
		parent::init();
		$stmt	= self::$db->prepare( $sql );
		$stmt->execute( array( ':id' => $id ) );
	
		return $stmt->fetch();
	}
	
	public static function getPosts( $id = 0, $page = 1, $thread = true, $raw = false ) {
		$params	= array();
		$sql	= "SELECT p.id AS id, p.title AS title, p.created_at AS created_at, 
				p.reply_count AS reply_count, 
				parent.title AS parent_title, 
				parent.status AS topic_status, 
				parent.reply_at AS last_reply, 
				posts_family.root_id AS root_id, 
				posts_family.parent_id AS parent_id";
		
		if ( $id > 0 || $thread ) {
			$sql .= ', p.body AS body';
		} else {
			$sql .= ', p.summary AS summary';
		}
		
		if ( $raw ) {
			$sql .= ', p.raw AS raw, p.auth_key AS auth_key';
		}
		
		$sql .= " FROM posts AS p
				INNER JOIN posts_family ON p.id = posts_family.child_id 
				LEFT JOIN posts AS parent ON posts_family.parent_id = parent.id";
		
		if ( $thread && $id > 0) {	// Viewing thread
			$params[':root_id'] = $id;
			$sql .= ' WHERE posts_family.root_id = :root_id ORDER BY id ASC';
			
		} elseif ( $id > 0 ) {		// Viewing a single post
			$params[':id'] = $id;
			$sql .= ' WHERE p.id = :id';
			
		} else {			// Viewing thread
			$sql .= ' WHERE posts_family.root_id = p.id ORDER BY id DESC';
		}
		
		$params[':limit']	= ( $id > 0 )? POST_LIMIT : TOPIC_LIMIT;
		$params[':offset']	= ( $page - 1 ) * $params[':limit'];
		
		$sql	.= ' LIMIT :limit OFFSET :offset;';
		
		parent::init();
		$stmt	= self::$db->prepare( $sql );
		$stmt->execute( $params );
		
		return $stmt->fetchAll( \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, 'BB\Post' );
	}
	
	/**
	 * Create or edit this post
	 */
	public function save( $auth ) {
		$edit = empty( $this->id )? false : true;
		
		$html = new \Microthread\Html();
		
		$this->plain	= \Microthread\Html::plainText( $this->raw );
		$this->summary	= self::smartTrim( $this->plain );
		$this->body	= $html->filter( $this->raw );
		$this->auth_key	= $auth;
		
		if ( empty( $this->title ) || '' == $this->title ) {
			$this->title = self::smartTrim( $this->plain, 60 );
		}
		
		$params = array(
			':t'	=> $this->title, 
			':b'	=> $this->body, 
			':r'	=> $this->raw,
			':p'	=> $this->plain,
			':s'	=> $this->summary,
			':a'	=> $this->auth_key
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
		$sql	 = 'INSERT INTO posts_family ( root_id, parent_id, child_id ) VALUES ( :r, :p, :i );';
		
		if ( empty( $this->root_id ) && empty( $this->parent_id ) ) {	// This is a new thread
			$params	= array(
					':r' => $this->id,
					':p' => $this->id,
					':i' => $this->id
				);
		
		} elseif ( empty( $this->parent_id ) ) {	// Reply to the root post
			$params	= array(
					':r' => $this->root_id,
					':p' => $this->id,
					':i' => $this->id
				);
			
		} else {
			$params	= array(
					':r' => $this->root_id,
					':p' => $this->parent_id,
					':i' => $this->id
				);
		}
		
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
	
	private function extractTitle( $body, $smax = 60, $smin = 5 ) {
		$body = trim( $body );
		
		if ( empty( $body ) || mb_strlen( $body ) < $smax ) {
			return $body;
		}
		$body = \Microthread\Html::plainText( $body );
		
		$i = strpos( $body, "\n" );
		$i--;
		if ( $i < $smin || $i > $smax ) {
			$i = strpos( $body, "." );
		}
		if ( false === $i || $i < $smin || $i > $smax ) {
			$i = $smax;
		}
		return mb_substr( $body, 0, $i );
	}
}
