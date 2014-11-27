<?php

function printTopic( $post ) {
	$time	= formatTime( $post->created_at );
echo <<<HTML
<li class='post' id='{$post->id}'>
	<span class='meta'><a href='/threads/{$post->id}'>{$post->title}</a> {$time}</span>
</li>
HTML;
}

function printIndex( $posts ) {
	if ( !count( $posts ) ) {
		return;
	}
	echo "<ul class='posts'>";
	foreach( $posts as $post ) { 
		printTopic( $post ); 
	}
	echo '</ul>';
}


require( '_header.php' );
printIndex();
require( '_form.php' );
require( '_footer.php' );
