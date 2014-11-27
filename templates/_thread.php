<?php


function formatLink( $id, $title = null, $trim = false ) {
	if ( empty( $title ) ) {
		return "( <a href='/posts/{$id}'>{$id}</a> )";
	}
	if ( $trim ) {
		$title = ( mb_strlen( $title ) > 20 )? mb_substr( $title, 0, 20 ) . '...' : $title;
	}
	return "<a href='/posts/{$id}'>{$title}</a>";
}


function printPost( $post ) {
	$parent = formatLink( $post->id, $post->title );
	if ( isset( $post->parent_title ) && $post->id !== $post->parent_id ) {
		$parent .= ' ( @' . 
			formatLink( $post->parent_id, $post->parent_title, true ) . ' )';
	}
	$time	= formatTime( $post->created_at );
	echo <<<HTML
<div class='post' id='{$post->id}'>
	<p><span class='meta'>{$parent} {$time}</span></p>
	<p>{$post->body}</p>
</div>
HTML;
}


function printThread( $posts ) {
	if ( !count( $posts ) ) {
		echo '<p>No more posts</p>';
	}
	echo "<div class='posts'>";
	foreach( $posts as $post ) {
		printPost( $post );
	}
	echo '</div>';
}

printThread();
