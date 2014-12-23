<?php

namespace BB;

class Display {
	
	public static function render( $template, $data = array() ) {
		Helpers::requestKey( true );
		require ( TEMPLATES . $template );
	}
	
	public static function formatBytes( $bytes, $precision = 2 ) {
		$units	= array( 'B', 'KB', 'MB', 'GB', 'TB' );
		
		$bytes	= max( $bytes, 0 );
		$pow	= floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow	= min( $pow, count( $units ) - 1 );
		
		return round( $bytes, $precision ) . ' ' . $units[$pow];
	}
	
	public static function formatTime( $time ) {
		$time  = strtotime( $time );
		return "<time datetime='". gmdate("Y-m-d H:i:s T", $time) ."'>" . 
			gmdate("M d, Y H:i", $time) . "</time>";
	}

	public static function formatNav( $url, $text ) {
		static return "<li><a href='{$url}'>{$text}</a></li>";
	}
	
	protected static function pageLink( $prefix, $num, $text, $curr = false ) {
		if ( $curr ) {
			return "<li><strong>{$num}</strong></li>";
		}
		
		return "<li><a href='{$prefix}{$num}'>{$text}</a></li>";
	}
	
	protected function firstLinks( $prefix, $num1, $num2 ) {
		$out	= self::pageLink( $prefix, $num1, $num1 );
		$out	.= self::pageLink( $prefix, $num2, $num2 );
		$out	.= '<li>...</li>';
		
		return $out;
		
	}
	
	protected function lastLinks( $prefix, $num1, $num2 ) {
		$out	= '<li>...</li>';
		$out	.= self::pageLink( $prefix, $num1, $num1 );
		$out	.= self::pageLink( $prefix, $num2, $num2 );
		
		return $out;
	}
	
	protected function countLink( $prefix, $counter, $page ) {
		if ( $counter === $page ) {
			return self::pageLink( $prefix, $counter, $counter, true );
		}
		
		return self::pageLink( $prefix, $counter, $counter );
	}
	
	protected static function countedLinks( 
		$prefix, 
		$start, 
		$end, 
		$showfirst = true 
	) {
		$out = '';
		if ( $showFirst ) {
			$out .= self::firstLinks( $prefix, 1, 2 );
		}
		for ( $counter = $start; $counter < $end; $counter++ ) {
			$out .= self::countLink( $prefix, $counter, $counter );
		}
		return $out;
	}
	
	public static function paginate_numbered( $post, $prefix, $page = 1 ) {
		$total	= $post->topic_replies;
		$limit	= $post->posts_limit;
		$last	= ceil( $total / $limit );
		
		if ( $page ) {
			$start = ( $page - 1 ) * $limit;
		} else {
			$start = 0;
		}
		
		$adjs	= 3;
		$prev	= $page - 1;
		$next	= $page + 1;
		
		$lpm1	= $last - 1;
		$out	= '<nav class=\'page\'><ul>';
		
		if ( $page > 1 ) {
			$out .= self::pageLink( $prefix, $prev, '&#8592; Previous' );
		}
		
		if ( $last < 7 + ( $adjs * 2 ) ) {
			$out .= self::countedLinks( $prefix, 1, ( $last + 1 ), false );
			
		} elseif ( $last > 5 + ( $adjs * 2 ) ) {
			if ( $page < 1 + ( $adjs * 2 ) ) {
				$out .= self::countedLinks( $prefix, 1, ( 4 + ( $adjs * 2 ) ), false );
				$out .= self::lastLinks( $prefix, $lpm1, $last );
				
			} elseif ( $last - ( $adjs * 2 ) > 
				$page && $page > ( $adjs * 2 ) ) {
				$out .= self::countedLinks( 
						$prefix, 
						( $page - $adjs ), 
						( $page + $adjs + 1 ), 
						true
					);
				$out .= self::lastLinks( $prefix, $lpm1, $last );
				
			} else {
				$out .= self::firstLinks( $prefix, 1, 2 );
			
				for ( $counter = $last - ( 2 + ( $adjs * 2 ) ); 
					$counter <= $last; $counter ++ ) {
					$out .= self::countLink( $prefix, $counter, $page );
				}
			}
		}
		
		if ( $page > $counter - 1 ) {
			$out .= self::pageLink( $prefix, $next, 'Next &#8594;' );
		}
		
		return $out . '</ul></nav>';
	}

	public static function paginate_nextprev( 
		$thread, 
		$id, 
		$page, 
		$posts 
	) {
		$out	= "<nav class='page'><ul>";
		$amt	= count( $posts );
		if ( !$amt ) {
			if( $thread ) {
				$out	.= self::formatNav( "/threads/{$thread}", 'Back to first page' );
			}
			echo $out . '</ul></nav>';
			return;
		}
		
		$prev	= $page - 1;
		$next	= $page + 1;
		
		if ( $id ) { // To parent post
			if ( $posts[0]->isRoot() ) {
				$out	.= self::formatNav( "/threads/{$first->id}", 'Back to thread' );
			} elseif ( $posts[0]->isParent() ) {
				$out	.= self::formatNav( "/threads/{$first->root_id}", 'Back to thread' );
			} else {
				$out	.= self::formatNav( "/threads/{$first->root_id}", 'Back to thread' );
				$out	.= self::formatNav( "/posts/{$first->parent_id}", 'To parent post' );
			}
		} elseif( $thread ) {
			if ( 1 === $prev ) {
				$out .= self::formatNav( "/threads/{$thread}", 'Back' );
			} elseif ( 1 >= $prev ) {
				$out .= self::formatNav( '/', 'Back' );
			} else {
				$out .= self::formatNav( "/threads/{$thread}/{$prev}", 'Back' );
			}
			if ( $amt >= Post::POST_LIMIT ) {
				$out .= self::formatNav( "/threads/{$thread}/{$next}", 'Next' );
			}
		} else {
			if ( $prev == 1 ) {
				$out .= self::formatNav( '/', 'Back' );
			} elseif ( $prev <= 1 ) {
				$out .= "<li>&nbsp;</li>";
			} else {
				$out .= self::formatNav( "/{$prev}", 'Back' );
			}
			if ( $amt >= Post::TOPIC_LIMIT ) {
				$out .= self::formatNav( "/{$next}", 'Next' );
			}
		}
		
		echo $out . '</ul></nav>';
	}
	
	/**
	 * @link http://php.net/manual/en/function.time.php#108581
	 */
	public static function elapsed($secs){
		$bit = array(
			'y' => $secs / 31556926 % 12,
			'w' => $secs / 604800 % 52,
			'd' => $secs / 86400 % 7,
			'h' => $secs / 3600 % 24,
			'm' => $secs / 60 % 60,
			's' => $secs % 60
		);
		
		foreach($bit as $k => $v) {
			if ( $v > 0 ) {
				$ret[] = $v . $k;
			}
		}
		echo join( ' ', $ret );
	}

	public static function printPosts( 
		$thread, 
		$id, 
		$canEdit, 
		$posts 
	) {
		if ( $thread === 0 ) {
			if ( $id === 0 ) {
				printIndex( $posts );
			} elseif( !$canEdit ) {
				printThread( $posts );
			}
		} else {
			printThread( $posts );
		}
	}
	
	// JavasScript settings for voting, edit links etc...
	public static function printJS() {
		$out	= "<script type='text/javascript'>";
		$out	.= 'var el = ' . EDIT_LIMIT .'; ';
		$out	.= 'var votes = {';
		
		$votes	= Helpers::getSessList( 'votes' );
		foreach ( $votes as $v ) {
			$out .= "'{$v[0]}':'{$v[1]}',";
		}
		$out	= rtrim( $out, ',') . '}; ';
		
		$out	.= 'var edits = {';
		$edits	= Helpers::getSessList( 'edits' );
		foreach ( $edits as $e ) {
			$out .= "'{$e[0]}':'{$e[1]}',";
		}
		$out	= rtrim( $out, ',') . '};';
		
		echo $out . '</script>';
	}
}
