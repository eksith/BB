<?php

namespace BB\Models;

class Post extends Data {
	const TITLE_LENGTH	= 60;
	
	const ERROR_NOTFOUND	= 0;
	
	const ERROR_STORAGE	= 1;
	
	const POST_LIMIT	= 30;
	
	const TOPIC_LIMIT	= 60;
	
	public $root_id		= 0;
	
	public $parent_id	= 0;
	
	public $title;
	
	public $parent_title;
	
	public $last_reply;
	
	public $summary;
	
	public $body;
	
	public $raw;
	
	public $plain;
	
	public $reply_count	= 0;
	
	public $thread_replies	= 0;
	
	public $quality;
	
	public $auth_key;
	
	public $topic_status;
	
	public $parent_status;
	
	public function __construct() {}
	
	public function hasReplies() {
		return ( $this->reply_count > 0 )? true : false;
	}
	
	public function isRoot() {
		return ( $this-> id == $this->root_id )? true : false;
	}
	
	public function isParent() {
		return ( $this-> id == $this->parent_id )? true : false;
	}
	
	public function update( \SplSubject $subject, $args ) {
		$params = $this->buildParams();
		
		if ( isset( $this->id ) ) {
			return parent::edit( 'posts', $params, array( 'id' => $this->id ) );
		} else {
			$this->id = parent::put( 'posts', $params );
			if ( $this->id ) {
				$this->saveFamily();
				return $this->id;
			}
			return false;
		}
	}
	
	public static function find( $filter = array() ) {
		$params	= array();
		$where	= ' WHERE ';
		$fetch	= '';
		$order	=  ' ORDER BY';
		$sql	= "SELECT p.id AS id, p.title AS title, p.created_at AS created_at, 
				p.reply_count AS reply_count, 
				parent.title AS parent_title, 
				parent.status AS parent_status, 
				parent.reply_at AS last_reply, 
				root.reply_count AS thread_replies, 
				root.status AS topic_status, 
				posts_family.root_id AS root_id, 
				posts_family.parent_id AS parent_id";
		
		if ( isset( $filter['thread'] ) ) {
			$where			.= 'posts_family.root_id = :root_id';
			$params['root_id']	= $filter['thread'];
			$sql			.= ', p.body AS body';
			$fetch			= 'classList';
			$order			= ' id ASC';
			
		} elseif ( isset( $filter['id'] ) || isset( $filter['edit'] ) ) {
			if ( isset( $filter['edit'] ) ) {
				$params['id']		= $filter['edit'];
				$sql			.= ', p.raw AS raw';
			} else {
				$params['id']		= $filter['id'];
				$sql			.= ', p.body AS body';
			}
			$where			.= 'p.id = :id';
			$fetch			= 'class';
			
		} else {
			$where			.= 'posts_family.root_id = p.id';
			$sql			.= ', p.summary AS summary';
			$fetch			= 'class';
			$order			= ' id DESC';
		}
		
		if ( isset( $filter['new'] ) ) {
			$where .= ' AND p.status > 0';
		} else {
			$where .= ' AND p.status >= 1';
		}
		
		$sql .= " FROM posts AS p
				INNER JOIN posts_family ON p.id = posts_family.child_id 
				LEFT JOIN posts AS parent ON posts_family.parent_id = parent.id 
				LEFT JOIN posts AS root ON posts_family.root_id = root.id $where";
		
		if ( isset( $filter['id'] ) || isset( $filter['edit'] ) ) {
			return parent::find( $sql . ';', $params, 'class' );
		}
		
		$params['limit']	= ( isset( $filter['thread'] ) )? 
						self::POST_LIMIT : self::TOPIC_LIMIT;
						
		$params['offset']	= ( isset( $filter['page'] ) )?
						( $filter['page'] - 1 ) * $params['limit'] : 0;
		
		$sql	.= " $order LIMIT :limit OFFSET :offset;";
		
		$posts	= parent::find( $sql, $params, 'classList' );
		if ( count( $posts ) ) {
			return $posts;
		}
		return self::ERROR_NOTFOUND;
	}
	
	public static function delete( $id, $permanent = false ) {
		return parent::delete( 'posts', $id, $permanent );
	}
	
	private function saveFamily() {
		// This is a new thread
		if ( empty( $this->root_id ) && empty( $this->parent_id ) ) {
			$params	= array(
					'root_id'	=> $this->id,
					'parent_id'	=> $this->id,
					'child_id'	=> $this->id
				);
		
		// Reply to the root post
		} elseif ( empty( $this->parent_id ) ) {
			$params	= array(
					'root_id'	=> $this->root_id,
					'parent_id'	=> $this->id,
					'child_id'	=> $this->id
				);
			
		} else {
			$params	= array(
					'root_id'	=> $this->root_id,
					'parent_id'	=> $this->parent_id,
					'child_id'	=> $this->id
				);
		}
		
		parent::put( 'posts_family', $params, 'rows' );
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
	
	protected function buildParams() {
		$params = array();
		
		if ( isset( $this->raw ) && '' != trim( $this->raw ) ) {
			$html = new \Microthread\Html();
			$this->body		= $html->filter( $this->raw );
			$this->plain		= \Microthread\Html::plainText( $this->body );
			$this->summary		= self::smartTrim( $this-plain );
			
			$params['raw']		= $this->raw;
			$params['body']		= $this->body
			$params['plain']	= $this->plain;
			$params['summary']	= self::smartTrim( $this->plain );
		}
		
		if ( isset( $this->title ) ) {
			$this->title = \Microthread\Html::entities( $this->title );
			$this->title = self::smartTrim( $this->title, self::TITLE_LENGTH );
			
		} elseif ( isset( $this->plain ) ) {
			$this->title = self::smartTrim( $this->plain, self::TITLE_LENGTH );
			
		} else {
			$this->title = 'No title';
		}
		
		$params['title'] = $this->title;
		
		return $params;
	}
}
